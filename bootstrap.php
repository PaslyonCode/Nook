<?php
// Shared bootstrap, authentication, storage and space-access helpers.

declare(strict_types=1);

require_once __DIR__ . '/config.php';

class SpaceLockedException extends RuntimeException
{
    public function __construct(public int $spaceId)
    {
        parent::__construct('Space password required.');
    }
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    // Enforce the connection encoding even on hosting stacks that ignore the DSN charset.
    $pdo->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
    return $pdo;
}

function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name(APP_SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
    $_SESSION['space_unlocks'] ??= [];
}

function current_user(): ?array
{
    start_app_session();
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

function require_user(): array
{
    $user = current_user();
    if (!$user) {
        throw new RuntimeException('Authentication required.');
    }
    return $user;
}

function login_user(string $username, string $password): bool
{
    $stmt = db()->prepare('SELECT id, username, password_hash FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([trim($username)]);
    $row = $stmt->fetch();
    if (!$row || !nook_verify_user_password($password, (string)$row['password_hash'])) {
        return false;
    }
    start_app_session();
    session_regenerate_id(true);
    $_SESSION['user'] = ['id' => (int)$row['id'], 'username' => (string)$row['username']];
    $_SESSION['space_unlocks'] = [];
    db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([(int)$row['id']]);
    return true;
}

function logout_user(): void
{
    start_app_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
    }
    session_destroy();
}

function setting_get(string $key, ?string $default = null): ?string
{
    $stmt = db()->prepare('SELECT setting_value FROM app_settings WHERE setting_key = ?');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return $value === false ? $default : (string)$value;
}

function setting_set(string $key, string $value): void
{
    $stmt = db()->prepare(
        'INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()'
    );
    $stmt->execute([$key, $value]);
}

function normalize_storage_root(string $path): string
{
    $path = trim($path, " \t\n\r\0\x0B\"'");
    $path = str_replace('\\', '/', $path);
    $path = rtrim($path, '/');
    if ($path === '') {
        throw new RuntimeException('Storage folder is empty.');
    }
    $isWindows = (bool)preg_match('/^[A-Za-z]:\//', $path);
    $isUnix = str_starts_with($path, '/');
    if (!$isWindows && !$isUnix) {
        throw new RuntimeException('Use an absolute storage path.');
    }
    return $path;
}

function storage_root(bool $required = true): ?string
{
    $raw = trim(STORAGE_ROOT_OVERRIDE) !== '' ? STORAGE_ROOT_OVERRIDE : setting_get('storage_root', '');
    if (trim((string)$raw) === '') {
        if ($required) {
            throw new RuntimeException('Storage folder is not configured.');
        }
        return null;
    }
    return normalize_storage_root((string)$raw);
}

function storage_path(string $relative = ''): string
{
    $root = storage_root(true);
    $relative = ltrim(str_replace('\\', '/', $relative), '/');
    if ($relative === '') {
        return $root;
    }
    if (str_contains($relative, '../') || $relative === '..') {
        throw new RuntimeException('Invalid storage path.');
    }
    return $root . '/' . $relative;
}

function ensure_storage_structure(string $root): void
{
    $root = normalize_storage_root($root);
    $dirs = ['', 'files', 'previews', 'note-images', 'exports', 'imports', 'tmp'];
    foreach ($dirs as $relative) {
        $dir = $relative === '' ? $root : $root . '/' . $relative;
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Could not create storage folder: ' . $dir);
        }
        if (!is_writable($dir)) {
            throw new RuntimeException('Storage folder is not writable: ' . $dir);
        }
    }
}

function random_filename(string $extension = ''): string
{
    $name = bin2hex(random_bytes(18));
    $extension = strtolower(trim($extension, '. '));
    return $extension === '' ? $name : $name . '.' . $extension;
}

function current_space_id(): int
{
    start_app_session();
    $id = (int)($_SESSION['current_space_id'] ?? 0);
    if ($id > 0) {
        $stmt = db()->prepare('SELECT id FROM spaces WHERE id=?');
        $stmt->execute([$id]);
        if ($stmt->fetchColumn()) return $id;
        unset($_SESSION['current_space_id']);
    }
    $id = (int)db()->query('SELECT id FROM spaces ORDER BY id ASC LIMIT 1')->fetchColumn();
    if ($id <= 0) {
        db()->prepare('INSERT INTO spaces (name) VALUES (?)')->execute(['Основная нычка']);
        $id = (int)db()->lastInsertId();
    }
    $_SESSION['current_space_id'] = $id;
    return $id;
}

function fetch_space(int $spaceId): ?array
{
    $stmt = db()->prepare('SELECT id, name, password_hash, created_at, updated_at FROM spaces WHERE id = ?');
    $stmt->execute([$spaceId]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    $row['id'] = (int)$row['id'];
    $row['protected'] = !empty($row['password_hash']);
    unset($row['password_hash']);
    return $row;
}

function space_cookie_name(int $spaceId): string
{
    return 'nook_space_' . $spaceId;
}

function persistent_space_access(int $userId, int $spaceId): bool
{
    $token = (string)($_COOKIE[space_cookie_name($spaceId)] ?? '');
    if ($token === '') {
        return false;
    }
    $hash = hash('sha256', $token);
    $stmt = db()->prepare(
        'SELECT id FROM space_access_tokens
         WHERE user_id = ? AND space_id = ? AND token_hash = ? AND expires_at > NOW() LIMIT 1'
    );
    $stmt->execute([$userId, $spaceId, $hash]);
    return (bool)$stmt->fetchColumn();
}

function has_space_access(int $spaceId): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }
    $stmt = db()->prepare('SELECT password_hash FROM spaces WHERE id = ?');
    $stmt->execute([$spaceId]);
    $hash = $stmt->fetchColumn();
    if ($hash === false) {
        return false;
    }
    if ($hash === null || $hash === '') {
        return true;
    }
    start_app_session();
    $expires = (int)($_SESSION['space_unlocks'][$spaceId] ?? 0);
    if ($expires > time()) {
        return true;
    }
    if (persistent_space_access((int)$user['id'], $spaceId)) {
        $_SESSION['space_unlocks'][$spaceId] = time() + SPACE_SESSION_SECONDS;
        return true;
    }
    return false;
}

function require_space_access(int $spaceId): void
{
    if (!has_space_access($spaceId)) {
        throw new SpaceLockedException($spaceId);
    }
}

function unlock_space(int $spaceId, string $password, bool $remember): bool
{
    $user = require_user();
    $stmt = db()->prepare('SELECT password_hash FROM spaces WHERE id = ?');
    $stmt->execute([$spaceId]);
    $hash = $stmt->fetchColumn();
    if ($hash === false) {
        return false;
    }
    if ($hash !== null && $hash !== '' && !password_verify($password, (string)$hash)) {
        return false;
    }
    start_app_session();
    $_SESSION['space_unlocks'][$spaceId] = time() + SPACE_SESSION_SECONDS;
    if ($remember && $hash !== null && $hash !== '') {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expires = (new DateTimeImmutable('+' . SPACE_REMEMBER_DAYS . ' days'))->format('Y-m-d H:i:s');
        db()->prepare('DELETE FROM space_access_tokens WHERE user_id = ? AND space_id = ?')->execute([(int)$user['id'], $spaceId]);
        db()->prepare(
            'INSERT INTO space_access_tokens (user_id, space_id, token_hash, expires_at) VALUES (?, ?, ?, ?)'
        )->execute([(int)$user['id'], $spaceId, $tokenHash, $expires]);
        setcookie(space_cookie_name($spaceId), $token, [
            'expires' => time() + SPACE_REMEMBER_DAYS * 86400,
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    return true;
}

function bool_input(mixed $value): bool
{
    return in_array(strtolower(trim((string)$value)), ['1', 'true', 'yes', 'on'], true);
}

function safe_html_text(string $html): string
{
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
    return trim($text);
}

/**
 * Verify the main Nook account password.
 * 32 hexadecimal characters are treated as a raw MD5 password hash.
 * Older password_hash() values remain accepted for a seamless transition.
 */
function nook_verify_user_password(string $plainPassword, string $storedHash): bool
{
    $storedHash = trim($storedHash);
    if (preg_match('/^[a-f0-9]{32}$/i', $storedHash)) {
        return hash_equals(strtolower($storedHash), md5($plainPassword));
    }
    return $storedHash !== '' && password_verify($plainPassword, $storedHash);
}
