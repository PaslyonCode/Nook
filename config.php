<?php
// config.php
// Edit the MySQL connection parameters for your server.

declare(strict_types=1);

const DB_HOST = 'localhost';
const DB_NAME = 'DBNAME';
const DB_USER = 'DBUSER';
const DB_PASS = 'DBPASS';
const DB_CHARSET = 'utf8mb4';

// These directories must be writable by PHP.
const UPLOAD_ORIGINAL_DIR = __DIR__ . '/uploads/originals';
const UPLOAD_THUMB_DIR    = __DIR__ . '/uploads/thumbs';
const UPLOAD_NOTE_DIR     = __DIR__ . '/uploads/note-images';

// Paths relative to the application root, used in HTML.
const UPLOAD_ORIGINAL_URL = 'uploads/originals';
const UPLOAD_THUMB_URL    = 'uploads/thumbs';
const UPLOAD_NOTE_URL     = 'uploads/note-images';

// Per-file limit. For videos, also increase upload_max_filesize and post_max_size in php.ini.
const MAX_UPLOAD_MB = 2048;

const APP_SESSION_NAME = 'nook_session';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}

function ensure_upload_dirs(): void
{
    foreach ([UPLOAD_ORIGINAL_DIR, UPLOAD_THUMB_DIR, UPLOAD_NOTE_DIR] as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        if (!is_writable($dir)) {
            throw new RuntimeException('Directory is not writable: ' . $dir);
        }
    }
}

function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name(APP_SESSION_NAME);
    session_start();
}

function current_user(): ?array
{
    start_app_session();
    if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
        return null;
    }
    return $_SESSION['user'];
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function login_user(string $username, string $password): bool
{
    $username = trim($username);
    if ($username === '' || $password === '') {
        return false;
    }

    $stmt = db()->prepare('SELECT id, username, password_hash FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, (string)$user['password_hash'])) {
        return false;
    }

    start_app_session();
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'username' => (string)$user['username'],
    ];

    db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([(int)$user['id']]);

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
