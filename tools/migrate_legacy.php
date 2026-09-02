<?php
// CLI migration utility for older Nook/Image Cards installations.
// Usage:
// php tools/migrate_legacy.php --legacy-uploads="C:/laragon/www/old-nook/uploads" --storage-root="D:/NookStorage"

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

require_once dirname(__DIR__) . '/config.php';

$options = getopt('', ['legacy-uploads:', 'storage-root:', 'dry-run']);
$legacyRoot = rtrim(str_replace('\\', '/', (string)($options['legacy-uploads'] ?? '')), '/');
$storageRoot = rtrim(str_replace('\\', '/', (string)($options['storage-root'] ?? '')), '/');
$dryRun = array_key_exists('dry-run', $options);

if ($legacyRoot === '' || $storageRoot === '') {
    fwrite(STDERR, "Usage: php tools/migrate_legacy.php --legacy-uploads=PATH --storage-root=PATH [--dry-run]\n");
    exit(2);
}
if (!is_dir($legacyRoot)) {
    fwrite(STDERR, "Legacy uploads folder does not exist: {$legacyRoot}\n");
    exit(2);
}

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
$pdo->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');

function table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

function column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?');
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function ensure_dir(string $path, bool $dryRun): void
{
    if ($dryRun || is_dir($path)) return;
    if (!mkdir($path, 0775, true) && !is_dir($path)) {
        throw new RuntimeException('Could not create directory: ' . $path);
    }
}

function copy_file_checked(string $source, string $target, bool $dryRun): string
{
    if (!is_file($source)) throw new RuntimeException('Source file is missing: ' . $source);
    ensure_dir(dirname($target), $dryRun);
    if (!$dryRun && !copy($source, $target)) throw new RuntimeException('Could not copy: ' . $source);
    return hash_file('sha256', $source);
}

function sql_file(PDO $pdo, string $path, bool $dryRun): void
{
    if ($dryRun) return;
    $sql = (string)file_get_contents($path);
    $sql = preg_replace('/^\s*USE\s+[^;]+;\s*/mi', '', $sql) ?? $sql;
    foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [] as $statement) {
        $statement = trim($statement);
        if ($statement !== '' && !str_starts_with($statement, '--')) $pdo->exec($statement);
    }
}

try {
    echo "Database: " . DB_NAME . "\n";
    echo "Legacy uploads: {$legacyRoot}\nStorage root: {$storageRoot}\n";
    if ($dryRun) echo "DRY RUN: no files or database rows will be changed.\n";

    ensure_dir($storageRoot, $dryRun);
    foreach (['files', 'previews', 'note-images', 'exports', 'imports', 'tmp'] as $dir) ensure_dir($storageRoot . '/' . $dir, $dryRun);

    if (!$dryRun) {
        // Create auxiliary v2 tables. Existing tables are preserved.
        $pdo->exec("CREATE TABLE IF NOT EXISTS app_settings (setting_key VARCHAR(100) PRIMARY KEY, setting_value LONGTEXT NULL, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS spaces (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(160) NOT NULL UNIQUE, password_hash VARCHAR(255) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        if (!(int)$pdo->query('SELECT COUNT(*) FROM spaces')->fetchColumn()) {
            $pdo->prepare('INSERT INTO spaces(name,password_hash) VALUES(?,NULL)')->execute(['Основная нычка']);
        }
        $pdo->exec("CREATE TABLE IF NOT EXISTS space_access_tokens (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id INT UNSIGNED NOT NULL,space_id INT UNSIGNED NOT NULL,token_hash CHAR(64) NOT NULL UNIQUE,expires_at DATETIME NOT NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,KEY idx_space_access_lookup(user_id,space_id,expires_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    if (!table_exists($pdo, 'cards')) throw new RuntimeException('The cards table was not found.');
    $columns = [
        'space_id' => "INT UNSIGNED NOT NULL DEFAULT 1 AFTER id",
        'entry_type' => "ENUM('media','note') NOT NULL DEFAULT 'media' AFTER space_id",
        'body_json' => "LONGTEXT NULL AFTER description",
        'body_html' => "LONGTEXT NULL AFTER body_json",
        'is_hidden' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER body_html",
        'is_draft' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER is_hidden",
        'is_pinned' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER is_draft",
        'pinned_at' => "DATETIME NULL AFTER is_pinned",
        'deleted_at' => "DATETIME NULL AFTER pinned_at",
    ];
    foreach ($columns as $column => $definition) {
        if (!column_exists($pdo, 'cards', $column)) {
            echo "Adding cards.{$column}\n";
            if (!$dryRun) $pdo->exec("ALTER TABLE cards ADD COLUMN {$column} {$definition}");
        }
    }

    if (!$dryRun) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS media_files (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
          card_id BIGINT UNSIGNED NOT NULL,
          role ENUM('content','attachment','inline') NOT NULL DEFAULT 'content',
          original_filename VARCHAR(255) NOT NULL,
          stored_path VARCHAR(700) NOT NULL,
          preview_path VARCHAR(700) NOT NULL DEFAULT '',
          media_type ENUM('image','video','pdf','stl','file') NOT NULL DEFAULT 'file',
          mime VARCHAR(120) NOT NULL DEFAULT 'application/octet-stream',
          size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
          width INT UNSIGNED NULL,height INT UNSIGNED NULL,sort_order INT UNSIGNED NOT NULL DEFAULT 0,
          sha256 CHAR(64) NOT NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          KEY idx_media_card(card_id,role,sort_order),KEY idx_media_type(media_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $stmt = $pdo->prepare("INSERT INTO app_settings(setting_key,setting_value) VALUES('storage_root',?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
        $stmt->execute([$storageRoot]);
    }

    $migrated = 0;
    $missing = 0;
    if (table_exists($pdo, 'images')) {
        $rows = $pdo->query('SELECT * FROM images ORDER BY id')->fetchAll();
        $exists = $pdo->prepare("SELECT id,stored_path,preview_path,sha256 FROM media_files WHERE card_id=? AND original_filename=? AND role='content' LIMIT 1");
        // 12 bound values: card_id plus 11 values after the fixed role='content'.
        $insert = $pdo->prepare("INSERT INTO media_files(card_id,role,original_filename,stored_path,preview_path,media_type,mime,size_bytes,width,height,sort_order,sha256,created_at) VALUES(?,'content',?,?,?,?,?,?,?,?,?,?,?)");
        $repair = $pdo->prepare("UPDATE media_files SET stored_path=?,preview_path=?,media_type=?,mime=?,size_bytes=?,width=?,height=?,sort_order=?,sha256=? WHERE id=?");
        foreach ($rows as $row) {
            $cardId = (int)$row['card_id'];
            $name = (string)$row['original_filename'];
            $exists->execute([$cardId, $name]);
            $existing = $exists->fetch();
            $stored = (string)$row['stored_filename'];
            $thumb = (string)($row['thumb_filename'] ?? '');
            $source = $legacyRoot . '/originals/' . $stored;
            if (!is_file($source)) { fwrite(STDERR, "Missing: {$source}\n"); $missing++; continue; }

            $relative = 'files/1/legacy/' . $stored;
            $target = $storageRoot . '/' . $relative;
            $sourceSha = hash_file('sha256', $source);
            $targetSha = is_file($target) ? hash_file('sha256', $target) : '';
            if (!is_file($target) || !hash_equals(strtolower($sourceSha), strtolower($targetSha))) {
                $sha = copy_file_checked($source, $target, $dryRun);
            } else {
                $sha = $targetSha;
            }

            $previewRelative = '';
            if ($thumb !== '' && is_file($legacyRoot . '/thumbs/' . $thumb)) {
                $previewRelative = 'previews/1/legacy/' . $thumb;
                $previewTarget = $storageRoot . '/' . $previewRelative;
                if (!is_file($previewTarget)) {
                    copy_file_checked($legacyRoot . '/thumbs/' . $thumb, $previewTarget, $dryRun);
                }
            }

            $type = (string)($row['media_type'] ?? 'image');
            if (!in_array($type, ['image','video'], true)) $type = 'file';
            if (!$dryRun) {
                if ($existing) {
                    $repair->execute([
                        $relative,$previewRelative,$type,(string)($row['mime']??'application/octet-stream'),
                        filesize($source),$row['width']??null,$row['height']??null,(int)($row['sort_order']??0),
                        $sha,(int)$existing['id']
                    ]);
                } else {
                    $insert->execute([
                        $cardId,$name,$relative,$previewRelative,$type,(string)($row['mime']??'application/octet-stream'),
                        filesize($source),$row['width']??null,$row['height']??null,(int)($row['sort_order']??0),
                        $sha,$row['created_at']??date('Y-m-d H:i:s')
                    ]);
                }
            }
            $migrated++;
        }
    }

    // Preserve old inline note images and rewrite simple legacy URLs.
    $legacyNoteDir = $legacyRoot . '/note-images';
    if (is_dir($legacyNoteDir)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($legacyNoteDir, FilesystemIterator::SKIP_DOTS)) as $item) {
            if (!$item->isFile()) continue;
            $relativeName = str_replace('\\', '/', substr($item->getPathname(), strlen($legacyNoteDir) + 1));
            copy_file_checked($item->getPathname(), $storageRoot . '/note-images/1/legacy/' . $relativeName, $dryRun);
        }
    }

    echo "Migrated or repaired media rows: {$migrated}\nMissing originals: {$missing}\n";
    echo $dryRun ? "Dry run completed.\n" : "Migration completed. Open Nook and verify the storage integrity before removing the legacy installation.\n";
    exit($missing > 0 ? 1 : 0);
} catch (Throwable $e) {
    fwrite(STDERR, "Migration failed: {$e->getMessage()}\n");
    exit(1);
}
