<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? '';
$body = request_body();

/** Passwort-Richtlinie: min. 8 Zeichen, Buchstabe + Zahl. */
function validate_password(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'Passwort muss mindestens 8 Zeichen haben.';
    }
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        return 'Passwort muss mindestens einen Buchstaben und eine Zahl enthalten.';
    }
    return null;
}

function send_reset_mail(string $email, string $username, string $token): bool
{
    $cfg = config();
    $base = rtrim($cfg['app_url'] ?? '', '/');
    if ($base === '') {
        // Fallback: URL aus Request ableiten
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $base = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
            . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    }
    $link = $base . '/index.html#reset=' . $token;

    $subject = 'Vokabeltrainer – Passwort zurücksetzen';
    $message = "Hallo $username,\r\n\r\n"
        . "für dein Konto wurde ein Passwort-Reset angefordert.\r\n"
        . "Öffne folgenden Link, um ein neues Passwort zu setzen (gültig für 1 Stunde):\r\n\r\n"
        . "$link\r\n\r\n"
        . "Falls du das nicht warst, kannst du diese E-Mail ignorieren.\r\n";

    $from = $cfg['mail_from'] ?? ('no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $headers = "From: $from\r\n"
        . "Content-Type: text/plain; charset=utf-8\r\n"
        . "X-Mailer: PHP/" . phpversion();

    return @mail($email, '=?UTF-8?B?' . base64_encode($subject) . '?=', $message, $headers);
}

switch ($action) {

    case 'register':
        $email = mb_strtolower(trim($body['email'] ?? ''));
        $username = trim($body['username'] ?? '');
        $password = $body['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            json_error('Bitte eine gültige E-Mail-Adresse angeben.');
        }
        if (!preg_match('/^[A-Za-z0-9_\-.]{3,50}$/', $username)) {
            json_error('Benutzername: 3–50 Zeichen, nur Buchstaben, Zahlen, _ - .');
        }
        if ($err = validate_password($password)) {
            json_error($err);
        }

        $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            json_error('Diese E-Mail-Adresse ist bereits registriert.');
        }
        $stmt = db()->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            json_error('Benutzername ist bereits vergeben.');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare('INSERT INTO users (email, username, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([$email, $username, $hash]);

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) db()->lastInsertId();
        $_SESSION['username'] = $username;
        json_response(['ok' => true, 'username' => $username, 'csrf' => csrf_token()]);

    case 'login':
        $email = mb_strtolower(trim($body['email'] ?? ''));
        $password = $body['password'] ?? '';

        if ($email === '' || $password === '') {
            json_error('E-Mail und Passwort angeben.');
        }

        check_rate_limit($email);

        $stmt = db()->prepare('SELECT id, username, password_hash FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            record_failed_attempt($email);
            json_error('E-Mail oder Passwort falsch.', 401);
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
            json_error('Bitte eine gültige E-Mail-Adresse angeben.');
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
            json_error('Ungültiger Reset-Link.');
        }
        if ($err = validate_password($password)) {
            json_error($err);
        }

        $stmt = db()->prepare(
            'SELECT pr.id, pr.user_id FROM password_resets pr
             WHERE pr.token_hash = ? AND pr.used = 0 AND pr.expires_at > NOW()'
        );
        $stmt->execute([hash('sha256', $token)]);
        $reset = $stmt->fetch();

        if (!$reset) {
            json_error('Reset-Link ist ungültig oder abgelaufen. Bitte neu anfordern.');
        }

        $pdo = db();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);
        $stmt = $pdo->prepare('UPDATE password_resets SET used = 1 WHERE user_id = ?');
        $stmt->execute([$reset['user_id']]);
        $pdo->commit();

        json_response(['ok' => true, 'message' => 'Passwort geändert. Du kannst dich jetzt anmelden.']);

    default:
        json_error('Unbekannte Aktion.', 404);
}
