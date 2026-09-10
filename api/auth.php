<?php
/**
 * Auth-API (öffentlich, kein Login nötig)
 *
 * POST ?action=register        Konto anlegen (email, username, password) – sendet Aktivierungsmail
 * POST ?action=activate        Konto mit Token aktivieren (token)
 * POST ?action=login           Anmelden (email, password) – nur aktivierte Konten
 * POST ?action=logout          Abmelden (benötigt CSRF-Token)
 * GET  ?action=me              Session-Status + CSRF-Token abfragen
 * POST ?action=request_reset   Passwort-Reset-Mail anfordern (email)
 * POST ?action=reset_password  Neues Passwort mit Token setzen (token, password; muss sich vom alten unterscheiden)
 * POST ?action=delete_account  Konto + alle Daten löschen (password) – Art. 17 DSGVO
 *
 * Nicht aktivierte Konten werden nach 48 Stunden automatisch gelöscht.
 */
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? '';
$body = request_body();

/** Passwort-Richtlinie: min. 8 Zeichen, Buchstabe + Zahl. Gibt Fehler-Code zurück. */
function validate_password(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'password_too_short';
    }
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        return 'password_too_weak';
    }
    return null;
}

const PASSWORD_ERROR_TEXT = [
    'password_too_short' => 'Passwort muss mindestens 8 Zeichen haben.',
    'password_too_weak' => 'Passwort muss mindestens einen Buchstaben und eine Zahl enthalten.',
];

/** Basis-URL der App (aus config oder Request abgeleitet). */
function app_base_url(): string
{
    $base = rtrim(config()['app_url'] ?? '', '/');
    if ($base === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $base = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
            . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    }
    return $base;
}

function send_app_mail(string $to, string $subject, string $message): bool
{
    $cfg = config();
    $from = $cfg['mail_from'] ?? ('no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $headers = "From: $from\r\n"
        . "Content-Type: text/plain; charset=utf-8\r\n"
        . "X-Mailer: PHP/" . phpversion();
    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $message, $headers);
}

function send_reset_mail(string $email, string $username, string $token): bool
{
    $link = app_base_url() . '/index.html#reset=' . $token;
    $message = "Hallo $username,\r\n\r\n"
        . "für dein Konto wurde ein Passwort-Reset angefordert.\r\n"
        . "Öffne folgenden Link, um ein neues Passwort zu setzen (gültig für 1 Stunde):\r\n\r\n"
        . "$link\r\n\r\n"
        . "Falls du das nicht warst, kannst du diese E-Mail ignorieren.\r\n";
    return send_app_mail($email, 'Vokabeltrainer – Passwort zurücksetzen', $message);
}

function send_activation_mail(string $email, string $username, string $token): bool
{
    $link = app_base_url() . '/index.html#activate=' . $token;
    $message = "Hallo $username,\r\n\r\n"
        . "willkommen beim Vokabeltrainer! Bitte bestätige deine E-Mail-Adresse,\r\n"
        . "um dein Konto zu aktivieren (Link 48 Stunden gültig):\r\n\r\n"
        . "$link\r\n\r\n"
        . "Nicht aktivierte Konten werden automatisch gelöscht.\r\n"
        . "Falls du dich nicht registriert hast, kannst du diese E-Mail ignorieren.\r\n";
    return send_app_mail($email, 'Vokabeltrainer – Konto aktivieren', $message);
}

/** Entfernt Konten, die nach 48h nicht aktiviert wurden (Datenmüll-Vorbeugung). */
function purge_unactivated_accounts(): void
{
    db()->exec(
        'DELETE FROM users
         WHERE activated_at IS NULL AND created_at < (NOW() - INTERVAL 48 HOUR)'
    );
}

switch ($action) {

    case 'register':
        $email = mb_strtolower(trim($body['email'] ?? ''));
        $username = trim($body['username'] ?? '');
        $password = $body['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            json_error('Bitte eine gültige E-Mail-Adresse angeben.', 400, 'email_invalid');
        }
        if (!preg_match('/^[A-Za-z0-9_\-.]{3,50}$/', $username)) {
            json_error('Benutzername: 3–50 Zeichen, nur Buchstaben, Zahlen, _ - .', 400, 'username_invalid');
        }
        if ($err = validate_password($password)) {
            json_error(PASSWORD_ERROR_TEXT[$err], 400, $err);
        }

        purge_unactivated_accounts();

        $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            json_error('Diese E-Mail-Adresse ist bereits registriert.', 400, 'email_taken');
        }
        $stmt = db()->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            json_error('Benutzername ist bereits vergeben.', 400, 'username_taken');
        }

        // Konto inaktiv anlegen; Login erst nach E-Mail-Bestätigung möglich
        $activationToken = bin2hex(random_bytes(32));
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare(
            'INSERT INTO users (email, username, password_hash, activation_token_hash)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$email, $username, $hash, hash('sha256', $activationToken)]);

        send_activation_mail($email, $username, $activationToken);

        json_response([
            'ok' => true,
            'needsActivation' => true,
            'message' => 'Konto angelegt. Bitte bestätige deine E-Mail-Adresse über den zugesendeten Link (48 Stunden gültig).',
        ]);

    case 'activate':
        $token = (string) ($body['token'] ?? '');
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            json_error('Ungültiger Aktivierungslink.', 400, 'activation_invalid');
        }

        purge_unactivated_accounts();

        $stmt = db()->prepare(
            'SELECT id FROM users WHERE activation_token_hash = ? AND activated_at IS NULL'
        );
        $stmt->execute([hash('sha256', $token)]);
        $userId = $stmt->fetchColumn();

        if (!$userId) {
            json_error('Aktivierungslink ist ungültig oder abgelaufen. Bitte registriere dich erneut.', 400, 'activation_expired');
        }

        $stmt = db()->prepare(
            'UPDATE users SET activated_at = NOW(), activation_token_hash = NULL WHERE id = ?'
        );
        $stmt->execute([$userId]);

        json_response(['ok' => true, 'message' => 'Konto aktiviert. Du kannst dich jetzt anmelden.']);

    case 'login':
        $email = mb_strtolower(trim($body['email'] ?? ''));
        $password = $body['password'] ?? '';

        if ($email === '' || $password === '') {
            json_error('E-Mail und Passwort angeben.', 400, 'credentials_missing');
        }

        check_rate_limit($email);

        $stmt = db()->prepare(
            'SELECT id, username, password_hash, activated_at FROM users WHERE email = ?'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            record_failed_attempt($email);
            json_error('E-Mail oder Passwort falsch.', 401, 'login_failed');
        }

        if ($user['activated_at'] === null) {
            json_error('Konto noch nicht aktiviert. Bitte den Link aus der Aktivierungs-E-Mail öffnen.', 403, 'not_activated');
        }

        clear_attempts($email);

        // Hash bei Bedarf auf aktuellen Algorithmus aktualisieren
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
        json_response(['ok' => true, 'username' => $user['username'], 'csrf' => csrf_token()]);

    case 'logout':
        require_csrf();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        json_response(['ok' => true]);

    case 'me':
        if (isset($_SESSION['user_id'])) {
            json_response([
                'loggedIn' => true,
                'username' => $_SESSION['username'],
                'csrf' => csrf_token(),
            ]);
        }
        json_response(['loggedIn' => false, 'csrf' => csrf_token()]);

    // Schritt 1: Reset anfordern – antwortet immer gleich (kein User-Enumeration-Leak)
    case 'request_reset':
        $email = mb_strtolower(trim($body['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_error('Bitte eine gültige E-Mail-Adresse angeben.', 400, 'email_invalid');
        }

        check_rate_limit('reset:' . $email, 3, 60);

        $stmt = db()->prepare('SELECT id, username FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $stmt = db()->prepare(
                'INSERT INTO password_resets (user_id, token_hash, expires_at)
                 VALUES (?, ?, NOW() + INTERVAL 1 HOUR)'
            );
            $stmt->execute([$user['id'], hash('sha256', $token)]);
            send_reset_mail($email, $user['username'], $token);
        }
        record_failed_attempt('reset:' . $email);

        json_response(['ok' => true, 'message' => 'Falls die E-Mail registriert ist, wurde ein Reset-Link gesendet.']);

    // Schritt 2: Neues Passwort mit Token setzen
    case 'reset_password':
        $token = (string) ($body['token'] ?? '');
        $password = $body['password'] ?? '';

        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            json_error('Ungültiger Reset-Link.', 400, 'reset_invalid');
        }
        if ($err = validate_password($password)) {
            json_error(PASSWORD_ERROR_TEXT[$err], 400, $err);
        }

        $stmt = db()->prepare(
            'SELECT pr.id, pr.user_id, u.password_hash
             FROM password_resets pr
             JOIN users u ON u.id = pr.user_id
             WHERE pr.token_hash = ? AND pr.used = 0 AND pr.expires_at > NOW()'
        );
        $stmt->execute([hash('sha256', $token)]);
        $reset = $stmt->fetch();

        if (!$reset) {
            json_error('Reset-Link ist ungültig oder abgelaufen. Bitte neu anfordern.', 400, 'reset_expired');
        }

        // Neues Passwort muss sich vom bisherigen unterscheiden
        if (password_verify($password, $reset['password_hash'])) {
            json_error('Das neue Passwort darf nicht mit dem alten übereinstimmen.', 400, 'password_same_as_old');
        }

        $pdo = db();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);
        $stmt = $pdo->prepare('UPDATE password_resets SET used = 1 WHERE user_id = ?');
        $stmt->execute([$reset['user_id']]);
        $pdo->commit();

        json_response(['ok' => true, 'message' => 'Passwort geändert. Du kannst dich jetzt anmelden.']);

    // Recht auf Löschung (Art. 17 DSGVO): entfernt Konto und via FK-Kaskade
    // alle Datensätze, Vokabeln, Fortschritte und Reset-Tokens.
    case 'delete_account':
        $userId = require_login();
        $password = $body['password'] ?? '';

        $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();

        if (!$hash || !password_verify($password, (string) $hash)) {
            json_error('Passwort falsch.', 401, 'password_wrong');
        }

        $stmt = db()->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$userId]);

        $_SESSION = [];
        session_destroy();
        json_response(['ok' => true]);

    default:
        json_error('Unbekannte Aktion.', 404, 'unknown_action');
}
