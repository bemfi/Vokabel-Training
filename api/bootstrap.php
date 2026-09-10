<?php
declare(strict_types=1);

// ---------------------------------------------------------------
// Sichere Session-Konfiguration (vor session_start!)
// ---------------------------------------------------------------
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_name('VOKABELSESSID');
session_start();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');

function config(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $configFile = __DIR__ . '/config.php';
        if (!file_exists($configFile)) {
            json_error('config.php fehlt. Bitte config.example.php kopieren und ausfüllen.', 500);
        }
        $cfg = require $configFile;
    }
    return $cfg;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $cfg = config();
        try {
            $pdo = new PDO(
                "mysql:host={$cfg['db_host']};dbname={$cfg['db_name']};charset=utf8mb4",
                $cfg['db_user'],
                $cfg['db_pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            json_error('Datenbankverbindung fehlgeschlagen.', 500);
        }
    }
    return $pdo;
}

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $message, int $status = 400): void
{
    json_response(['error' => $message], $status);
}

function request_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

// ---------------------------------------------------------------
// CSRF-Schutz: Token liegt in der Session, Client sendet es als Header
// ---------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function require_csrf(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        return;
    }
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if ($sent === '' || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $sent)) {
        json_error('Ungültiges CSRF-Token. Bitte Seite neu laden.', 403);
    }
}

function require_login(): int
{
    if (!isset($_SESSION['user_id'])) {
        json_error('Nicht angemeldet.', 401);
    }
    require_csrf();
    return (int) $_SESSION['user_id'];
}

/** Prüft, ob der Datensatz dem angemeldeten Nutzer gehört. */
function require_dataset_owner(int $datasetId, int $userId): array
{
    $stmt = db()->prepare('SELECT * FROM datasets WHERE id = ? AND user_id = ?');
    $stmt->execute([$datasetId, $userId]);
    $dataset = $stmt->fetch();
    if (!$dataset) {
        json_error('Datensatz nicht gefunden.', 404);
    }
    return $dataset;
}

// ---------------------------------------------------------------
// Brute-Force-Schutz
// ---------------------------------------------------------------
function client_ip(): string
{
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}

/** Max. $limit Fehlversuche pro Identifier/IP in $windowMinutes. */
function check_rate_limit(string $identifier, int $limit = 8, int $windowMinutes = 15): void
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM login_attempts
         WHERE (identifier = ? OR ip = ?) AND attempted_at > (NOW() - INTERVAL ? MINUTE)'
    );
    $stmt->execute([$identifier, client_ip(), $windowMinutes]);
    if ((int) $stmt->fetchColumn() >= $limit) {
        json_error('Zu viele Versuche. Bitte in 15 Minuten erneut versuchen.', 429);
    }
}

function record_failed_attempt(string $identifier): void
{
    $stmt = db()->prepare('INSERT INTO login_attempts (identifier, ip) VALUES (?, ?)');
    $stmt->execute([$identifier, client_ip()]);
    // Alte Einträge gelegentlich aufräumen
    if (random_int(1, 20) === 1) {
        db()->exec('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)');
    }
}

function clear_attempts(string $identifier): void
{
    $stmt = db()->prepare('DELETE FROM login_attempts WHERE identifier = ?');
    $stmt->execute([$identifier]);
}
