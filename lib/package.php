<?php
// Portable export/import package helpers.

declare(strict_types=1);

function rrmdir(string $path): void
{
    if (!is_dir($path)) {
        if (is_file($path)) {
            @unlink($path);
        }
        return;
    }
    $items = scandir($path) ?: [];
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $child = $path . '/' . $item;
        is_dir($child) ? rrmdir($child) : @unlink($child);
    }
    @rmdir($path);
}

function recursive_files(string $root, array $excludedTop = []): array
{
    $root = rtrim(str_replace('\\', '/', $root), '/');
    $out = [];
    if (!is_dir($root)) {
        return $out;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $path = str_replace('\\', '/', $file->getPathname());
        $relative = ltrim(substr($path, strlen($root)), '/');
        $top = explode('/', $relative, 2)[0];
        if (in_array($top, $excludedTop, true)) {
            continue;
        }
        $out[$relative] = $path;
    }
    ksort($out);
    return $out;
}

function package_json_encode(array $data): string
{
    try {
        return json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
        );
    } catch (JsonException $e) {
        throw new RuntimeException('Could not encode export JSON: ' . $e->getMessage(), 0, $e);
    }
}

function package_json_decode(string $json, string $label): array
{
    $json = preg_replace('/^\xEF\xBB\xBF/', '', $json) ?? $json;
    try {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        throw new RuntimeException('Invalid ' . $label . ': ' . $e->getMessage(), 0, $e);
    }
    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid ' . $label . '.');
    }
    return $decoded;
}

/**
 * Repairs the common UTF-8 text interpreted as Windows-1251 problem:
 * "РћСЃРЅ..." -> "Основ...". The conversion is only accepted when the
 * candidate is valid UTF-8 and clearly shorter, so normal Russian text is not changed.
 */
function repair_mojibake_string(string $value): string
{
    if ($value === '' || (!str_contains($value, 'Р') && !str_contains($value, 'С') && !str_contains($value, 'Ð') && !str_contains($value, 'Ñ'))) {
        return $value;
    }

    $encodings = ['Windows-1251', 'ISO-8859-1'];
    foreach ($encodings as $targetEncoding) {
        if (function_exists('mb_convert_encoding')) {
            $candidate = @mb_convert_encoding($value, $targetEncoding, 'UTF-8');
            $valid = is_string($candidate) && $candidate !== '' && mb_check_encoding($candidate, 'UTF-8');
        } elseif (function_exists('iconv')) {
            $candidate = @iconv('UTF-8', $targetEncoding . '//IGNORE', $value);
            $valid = is_string($candidate) && $candidate !== '' && preg_match('//u', $candidate) === 1;
        } else {
            return $value;
        }

        if (!$valid || $candidate === $value) {
            continue;
        }
        $oldLength = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
        $newLength = function_exists('mb_strlen') ? mb_strlen($candidate, 'UTF-8') : strlen($candidate);
        if ($newLength < $oldLength && preg_match('/[\p{L}]/u', $candidate)) {
            return $candidate;
        }
    }
    return $value;
}

function repair_mojibake_recursive(mixed $value): mixed
{
    if (is_string($value)) {
        return repair_mojibake_string($value);
    }
    if (!is_array($value)) {
        return $value;
    }
    foreach ($value as $key => $item) {
        $value[$key] = repair_mojibake_recursive($item);
    }
    return $value;
}

function normalize_package_relative_path(string $path): string
{
    $path = trim(str_replace('\\', '/', $path));
    $path = preg_replace('#^\./+#', '', $path) ?? $path;
    $path = preg_replace('#^storage/+#i', '', $path) ?? $path;
    $path = ltrim($path, '/');
    if ($path === '' || str_contains($path, "\0") || preg_match('#(^|/)\.\.(/|$)#', $path) || preg_match('/^[A-Za-z]:\//', $path)) {
        throw new RuntimeException('Unsafe path in package: ' . $path);
    }
    return $path;
}

function export_database_array(PDO $pdo): array
{
    $tables = ['users', 'app_settings', 'spaces', 'space_access_tokens', 'cards', 'media_files', 'tags', 'card_tags'];
    $data = ['schema_version' => 3, 'tables' => []];
    foreach ($tables as $table) {
        // Remembered space-access tokens are instance-specific and are intentionally not exported.
        $data['tables'][$table] = $table === 'space_access_tokens'
            ? []
            : $pdo->query('SELECT * FROM `' . $table . '`')->fetchAll();
    }
    return repair_mojibake_recursive($data);
}

function sql_literal(PDO $pdo, mixed $value): string
{
    if ($value === null) {
        return 'NULL';
    }
    return $pdo->quote((string)$value);
}

function export_database_sql(PDO $pdo, array $dbData): string
{
    $sql = "-- UTF-8 / utf8mb4\nSET FOREIGN_KEY_CHECKS=0;\nSET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
    foreach ($dbData['tables'] as $table => $rows) {
        $sql .= "DELETE FROM `{$table}`;\n";
        foreach ($rows as $row) {
            $columns = array_keys($row);
            $columnSql = implode(',', array_map(fn($c) => '`' . str_replace('`', '``', $c) . '`', $columns));
            $valueSql = implode(',', array_map(fn($c) => sql_literal($pdo, $row[$c]), $columns));
            $sql .= "INSERT INTO `{$table}` ({$columnSql}) VALUES ({$valueSql});\n";
        }
    }
    return $sql . "SET FOREIGN_KEY_CHECKS=1;\n";
}


function legacy_storage_roots(array $extraRoots = []): array
{
    $appRoot = dirname(__DIR__);
    $candidates = array_merge($extraRoots, [
        $appRoot . '/uploads',
        $appRoot . '/legacy-uploads',
    ]);
    $roots = [];
    foreach ($candidates as $candidate) {
        $candidate = rtrim(str_replace('\\', '/', trim((string)$candidate)), '/');
        if ($candidate !== '' && is_dir($candidate)) {
            $roots[$candidate] = $candidate;
        }
    }
    return array_values($roots);
}

function legacy_file_index(array $roots): array
{
    $index = [];
    foreach ($roots as $root) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $path = str_replace('\\', '/', $file->getPathname());
            $index[strtolower($file->getBasename())][] = $path;
        }
    }
    return $index;
}

function find_legacy_file(string $relativePath, string $expectedSha256, array $legacyIndex): ?string
{
    $basename = strtolower(basename(str_replace('\\', '/', $relativePath)));
    if ($basename === '') {
        return null;
    }
    $candidates = $legacyIndex[$basename] ?? [];
    if (!$candidates) {
        return null;
    }

    $expectedSha256 = strtolower(trim($expectedSha256));
    if (preg_match('/^[a-f0-9]{64}$/', $expectedSha256)) {
        foreach ($candidates as $candidate) {
            $actual = strtolower((string)@hash_file('sha256', $candidate));
            if ($actual !== '' && hash_equals($expectedSha256, $actual)) {
                return $candidate;
            }
        }
    }

    return count($candidates) === 1 ? $candidates[0] : null;
}

/**
 * Repairs media rows whose files were not copied during an interrupted legacy migration.
 * Originals are recovered only from explicit legacy folders or the local uploads folder.
 */
function repair_storage_references(PDO $pdo, array $extraLegacyRoots = []): array
{
    $root = storage_root(true);
    ensure_storage_structure($root);
    $legacyRoots = legacy_storage_roots($extraLegacyRoots);
    $legacyIndex = legacy_file_index($legacyRoots);
    $rows = $pdo->query(
        'SELECT id, role, original_filename, stored_path, preview_path, media_type, sha256
         FROM media_files ORDER BY id'
    )->fetchAll();

    $update = $pdo->prepare('UPDATE media_files SET stored_path=?, preview_path=?, sha256=? WHERE id=?');
    $recoveredOriginals = 0;
    $recoveredPreviews = 0;
    $generatedPreviews = 0;
    $missing = [];

    foreach ($rows as $row) {
        $id = (int)$row['id'];
        try {
            $storedRelative = normalize_package_relative_path((string)$row['stored_path']);
        } catch (Throwable) {
            $storedRelative = '';
        }
        $storedAbsolute = $storedRelative !== '' ? $root . '/' . $storedRelative : '';
        $expectedSha = strtolower(trim((string)$row['sha256']));
        $storedExists = $storedRelative !== '' && is_file($storedAbsolute);
        $storedSha = $storedExists ? strtolower((string)hash_file('sha256', $storedAbsolute)) : '';
        $storedDamaged = $storedExists
            && preg_match('/^[a-f0-9]{64}$/', $expectedSha)
            && !hash_equals($expectedSha, $storedSha);

        if (!$storedExists || $storedDamaged) {
            $legacySource = find_legacy_file((string)$row['stored_path'], $expectedSha, $legacyIndex);
            if ($legacySource !== null) {
                if ($storedRelative === '') {
                    $kind = (string)$row['role'] === 'inline' ? 'note-images' : 'files';
                    $storedRelative = $kind . '/recovered/' . basename($legacySource);
                    $storedAbsolute = $root . '/' . $storedRelative;
                }
                if (!is_dir(dirname($storedAbsolute)) && !mkdir(dirname($storedAbsolute), 0775, true) && !is_dir(dirname($storedAbsolute))) {
                    throw new RuntimeException('Could not create recovery directory: ' . dirname($storedAbsolute));
                }
                if (!copy($legacySource, $storedAbsolute)) {
                    throw new RuntimeException('Could not recover legacy file: ' . $legacySource);
                }
                $recoveredOriginals++;
                $storedExists = true;
                $storedSha = strtolower((string)hash_file('sha256', $storedAbsolute));
                $storedDamaged = false;
            }
        }

        if (!$storedExists || $storedDamaged) {
            $reason = $storedDamaged ? 'damaged' : 'missing';
            $missing[] = '#' . $id . ' ' . (string)$row['original_filename'] . ' [' . (string)$row['stored_path'] . '; ' . $reason . ']';
            continue;
        }

        $actualSha = $storedSha;
        $previewRelative = '';
        try {
            $previewRelative = trim((string)$row['preview_path']) !== ''
                ? normalize_package_relative_path((string)$row['preview_path'])
                : '';
        } catch (Throwable) {
            $previewRelative = '';
        }
        $previewAbsolute = $previewRelative !== '' ? $root . '/' . $previewRelative : '';

        if ($previewRelative === '' || !is_file($previewAbsolute)) {
            $legacyPreview = find_legacy_file((string)$row['preview_path'], '', $legacyIndex);
            if ($legacyPreview !== null) {
                if ($previewRelative === '') {
                    $previewRelative = 'previews/recovered/' . basename($legacyPreview);
                    $previewAbsolute = $root . '/' . $previewRelative;
                }
                if (!is_dir(dirname($previewAbsolute))) {
                    mkdir(dirname($previewAbsolute), 0775, true);
                }
                if (copy($legacyPreview, $previewAbsolute)) {
                    $recoveredPreviews++;
                }
            }
        }

        if ($previewRelative === '' || !is_file($root . '/' . $previewRelative)) {
            $previewRelative = 'previews/recovered/media-' . $id . '.svg';
            $previewAbsolute = $root . '/' . $previewRelative;
            if (!is_dir(dirname($previewAbsolute))) {
                mkdir(dirname($previewAbsolute), 0775, true);
            }
            if (!create_placeholder_preview(
                $previewAbsolute,
                (string)($row['media_type'] ?? 'file'),
                (string)($row['original_filename'] ?? 'file')
            )) {
                throw new RuntimeException('Could not create recovered preview for media #' . $id . '.');
            }
            $generatedPreviews++;
        }

        if ($storedRelative !== (string)$row['stored_path']
            || $previewRelative !== (string)$row['preview_path']
            || !hash_equals(strtolower((string)$row['sha256']), $actualSha)) {
            $update->execute([$storedRelative, $previewRelative, $actualSha, $id]);
        }
    }

    return [
        'media_rows' => count($rows),
        'legacy_roots' => $legacyRoots,
        'recovered_originals' => $recoveredOriginals,
        'recovered_previews' => $recoveredPreviews,
        'generated_previews' => $generatedPreviews,
        'missing' => $missing,
    ];
}

function assert_export_storage_integrity(PDO $pdo, array $extraLegacyRoots = []): array
{
    $report = repair_storage_references($pdo, $extraLegacyRoots);
    if ($report['missing']) {
        $details = implode('; ', array_slice($report['missing'], 0, 8));
        if (count($report['missing']) > 8) {
            $details .= '; and ' . (count($report['missing']) - 8) . ' more';
        }
        $roots = $report['legacy_roots']
            ? ' Checked legacy folders: ' . implode(', ', $report['legacy_roots']) . '.'
            : ' No legacy uploads folder was found beside the application.';
        throw new RuntimeException(
            'Export cancelled because storage is incomplete: ' . $details . '.' . $roots
            . ' Run tools/repair_storage.php with --legacy-uploads pointing to the old uploads folder.'
        );
    }
    return $report;
}

function create_export_package(): array
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('PHP ZipArchive is required for export.');
    }
    $root = storage_root(true);
    ensure_storage_structure($root);
    $integrity = assert_export_storage_integrity(db());
    $stamp = (new DateTimeImmutable())->format('Ymd-His');
    $filename = 'nook-export-' . $stamp . '.zip';
    $output = storage_path('exports/' . $filename);
    $temp = storage_path('tmp/export-' . bin2hex(random_bytes(6)));
    mkdir($temp, 0775, true);

    try {
        $dbData = export_database_array(db());
        file_put_contents($temp . '/database.json', package_json_encode($dbData));
        file_put_contents($temp . '/database.sql', export_database_sql(db(), $dbData));

        $storageFiles = recursive_files($root, ['exports', 'imports', 'tmp']);
        $manifestFiles = [];
        foreach ($storageFiles as $relative => $absolute) {
            $relative = normalize_package_relative_path($relative);
            $manifestFiles[] = [
                'path' => $relative,
                'size' => filesize($absolute),
                'sha256' => hash_file('sha256', $absolute),
            ];
        }
        $manifest = [
            'format' => 'nook-portable-package',
            'version' => 3,
            'created_at' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
            'encoding' => 'UTF-8',
            'file_count' => count($manifestFiles),
            'files' => $manifestFiles,
            'database_sha256' => hash_file('sha256', $temp . '/database.json'),
        ];
        file_put_contents($temp . '/manifest.json', package_json_encode($manifest));

        $zip = new ZipArchive();
        if ($zip->open($output, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create export archive.');
        }
        $zip->addFile($temp . '/manifest.json', 'manifest.json');
        $zip->addFile($temp . '/database.json', 'database.json');
        $zip->addFile($temp . '/database.sql', 'database.sql');
        foreach ($storageFiles as $relative => $absolute) {
            $zip->addFile($absolute, 'storage/' . normalize_package_relative_path($relative));
        }
        $zip->close();
        return [
            'filename' => $filename,
            'path' => $output,
            'size' => filesize($output),
            'integrity' => $integrity,
        ];
    } finally {
        rrmdir($temp);
    }
}

function import_packages(): array
{
    $dir = storage_path('imports');
    $items = [];
    if (is_file($dir . '/manifest.json') && is_file($dir . '/database.json')) {
        $items[] = ['name' => '__imports_root__', 'label' => 'Extracted package in imports/', 'kind' => 'folder', 'size' => null];
    }
    foreach (scandir($dir) ?: [] as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        $path = $dir . '/' . $name;
        if (is_file($path) && strtolower(pathinfo($name, PATHINFO_EXTENSION)) === 'zip') {
            $items[] = ['name' => $name, 'kind' => 'zip', 'size' => filesize($path)];
        } elseif (is_dir($path) && is_file($path . '/manifest.json') && is_file($path . '/database.json')) {
            $items[] = ['name' => $name, 'kind' => 'folder', 'size' => null];
        }
    }
    usort($items, fn($a, $b) => strcmp($b['name'], $a['name']));
    return $items;
}

function validate_package_dir(string $packageDir): array
{
    $manifestPath = $packageDir . '/manifest.json';
    $databasePath = $packageDir . '/database.json';
    if (!is_file($manifestPath) || !is_file($databasePath)) {
        throw new RuntimeException('Package does not contain manifest.json and database.json.');
    }
    $manifest = package_json_decode((string)file_get_contents($manifestPath), 'Nook package manifest');
    $dbData = package_json_decode((string)file_get_contents($databasePath), 'database export');
    if (($manifest['format'] ?? '') !== 'nook-portable-package') {
        throw new RuntimeException('Invalid Nook package manifest.');
    }
    if (!isset($dbData['tables'])) {
        throw new RuntimeException('Invalid database export.');
    }
    if (!hash_equals((string)($manifest['database_sha256'] ?? ''), hash_file('sha256', $databasePath))) {
        throw new RuntimeException('Database checksum does not match the manifest.');
    }

    $normalizedFiles = [];
    foreach (($manifest['files'] ?? []) as $file) {
        $relative = normalize_package_relative_path((string)($file['path'] ?? ''));
        $absolute = $packageDir . '/storage/' . $relative;
        if (!is_file($absolute)) {
            throw new RuntimeException('Package file is missing: ' . $relative);
        }
        $actualHash = hash_file('sha256', $absolute);
        if (!hash_equals(strtolower((string)($file['sha256'] ?? '')), strtolower($actualHash))) {
            throw new RuntimeException('Checksum mismatch: ' . $relative);
        }
        $normalizedFiles[] = [
            'path' => $relative,
            'size' => filesize($absolute),
            'sha256' => strtolower($actualHash),
        ];
    }
    $manifest['files'] = $normalizedFiles;
    $manifest['file_count'] = count($normalizedFiles);

    return [$manifest, repair_mojibake_recursive($dbData)];
}

function import_database_array(PDO $pdo, array $dbData): void
{
    $destinationStorageRoot = setting_get('storage_root', '');
    $dbData = repair_mojibake_recursive($dbData);
    $allowed = ['users', 'app_settings', 'spaces', 'space_access_tokens', 'cards', 'media_files', 'tags', 'card_tags'];
    $deleteOrder = ['card_tags', 'media_files', 'cards', 'space_access_tokens', 'tags', 'spaces', 'app_settings', 'users'];

    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    try {
        foreach ($deleteOrder as $table) {
            $pdo->exec('DELETE FROM `' . $table . '`');
        }
        foreach ($allowed as $table) {
            $rows = $dbData['tables'][$table] ?? [];
            foreach ($rows as $row) {
                if (!is_array($row) || !$row) {
                    continue;
                }
                $row = repair_mojibake_recursive($row);
                $columns = array_keys($row);
                $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $columns) . '`) VALUES ('
                    . implode(',', array_fill(0, count($columns), '?')) . ')';
                $pdo->prepare($sql)->execute(array_values($row));
            }
        }
        if ($destinationStorageRoot !== '') {
            $stmt = $pdo->prepare('INSERT INTO app_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
            $stmt->execute(['storage_root', $destinationStorageRoot]);
        }
    } finally {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }
}

function package_manifest_indexes(array $manifest): array
{
    $byPath = [];
    $byHash = [];
    $byBasename = [];
    foreach (($manifest['files'] ?? []) as $file) {
        $path = normalize_package_relative_path((string)$file['path']);
        $hash = strtolower((string)$file['sha256']);
        $byPath[$path] = $file;
        $byHash[$hash][] = $path;
        $byBasename[strtolower(basename($path))][] = $path;
    }
    return [$byPath, $byHash, $byBasename];
}

function resolve_manifest_media_path(string $storedPath, string $sha256, array $byPath, array $byHash, array $byBasename): ?string
{
    try {
        $normalized = normalize_package_relative_path($storedPath);
    } catch (Throwable) {
        $normalized = '';
    }
    if ($normalized !== '' && isset($byPath[$normalized])) {
        return $normalized;
    }

    $sha256 = strtolower(trim($sha256));
    if (preg_match('/^[a-f0-9]{64}$/', $sha256) && count($byHash[$sha256] ?? []) === 1) {
        return $byHash[$sha256][0];
    }

    $basename = strtolower(basename(str_replace('\\', '/', $storedPath)));
    if ($basename !== '' && count($byBasename[$basename] ?? []) === 1) {
        return $byBasename[$basename][0];
    }
    return null;
}

function reconcile_imported_media(PDO $pdo, string $root, array $manifest): array
{
    [$byPath, $byHash, $byBasename] = package_manifest_indexes($manifest);
    $update = $pdo->prepare('UPDATE media_files SET stored_path=?, preview_path=?, sha256=? WHERE id=?');
    $failures = [];
    $repairs = 0;

    $rows = $pdo->query('SELECT id, original_filename, stored_path, preview_path, media_type, sha256 FROM media_files ORDER BY id')->fetchAll();
    foreach ($rows as $row) {
        $id = (int)$row['id'];
        $stored = resolve_manifest_media_path((string)$row['stored_path'], (string)$row['sha256'], $byPath, $byHash, $byBasename);
        if ($stored === null || !is_file($root . '/' . $stored)) {
            $failures[] = '#' . $id . ' ' . (string)$row['original_filename'] . ' [' . (string)$row['stored_path'] . ']';
            continue;
        }

        $actualHash = strtolower(hash_file('sha256', $root . '/' . $stored));
        $preview = '';
        $rawPreview = trim((string)$row['preview_path']);
        if ($rawPreview !== '') {
            $preview = resolve_manifest_media_path($rawPreview, '', $byPath, $byHash, $byBasename) ?? '';
        }

        if ($preview === '' || !is_file($root . '/' . $preview)) {
            $previewDir = 'previews/imported';
            $preview = $previewDir . '/media-' . $id . '.svg';
            $previewAbsolute = $root . '/' . $preview;
            if (!is_dir(dirname($previewAbsolute))) {
                mkdir(dirname($previewAbsolute), 0775, true);
            }
            create_placeholder_preview(
                $previewAbsolute,
                (string)($row['media_type'] ?? 'file'),
                (string)($row['original_filename'] ?? 'file')
            );
        }

        if ($stored !== (string)$row['stored_path'] || $preview !== (string)$row['preview_path'] || !hash_equals(strtolower((string)$row['sha256']), $actualHash)) {
            $update->execute([$stored, $preview, $actualHash, $id]);
            $repairs++;
        }
    }

    if ($failures) {
        $details = implode('; ', array_slice($failures, 0, 8));
        if (count($failures) > 8) {
            $details .= '; and ' . (count($failures) - 8) . ' more';
        }
        throw new RuntimeException('Imported database references files that are absent from the package: ' . $details);
    }

    return ['repairs' => $repairs, 'media_rows' => count($rows)];
}

function run_import_package(string $packageName): array
{
    $safeName = basename($packageName);
    if ($packageName === '__imports_root__') {
        $source = storage_path('imports');
    } else {
        if ($safeName !== $packageName) {
            throw new RuntimeException('Invalid package name.');
        }
        $source = storage_path('imports/' . $safeName);
    }
    if (!file_exists($source)) {
        throw new RuntimeException('Import package was not found.');
    }
    $temp = storage_path('tmp/import-' . bin2hex(random_bytes(6)));
    mkdir($temp, 0775, true);
    $packageDir = $temp;
    try {
        if (is_file($source)) {
            if (!class_exists('ZipArchive')) {
                throw new RuntimeException('PHP ZipArchive is required for ZIP import.');
            }
            $zip = new ZipArchive();
            if ($zip->open($source) !== true) {
                throw new RuntimeException('Could not open import ZIP.');
            }
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = str_replace('\\', '/', (string)$zip->getNameIndex($i));
                if ($entry === '' || str_starts_with($entry, '/') || preg_match('/^[A-Za-z]:\//', $entry) || preg_match('#(^|/)\.\.(/|$)#', $entry)) {
                    $zip->close();
                    throw new RuntimeException('Unsafe path in import ZIP.');
                }
            }
            if (!$zip->extractTo($temp)) {
                $zip->close();
                throw new RuntimeException('Could not extract import ZIP.');
            }
            $zip->close();
        } else {
            $packageDir = $source;
        }

        [$manifest, $dbData] = validate_package_dir($packageDir);
        $root = storage_root(true);
        $backup = storage_path('tmp/preimport-' . date('Ymd-His'));
        mkdir($backup, 0775, true);
        $managed = ['files', 'previews', 'note-images'];
        foreach ($managed as $dir) {
            $current = $root . '/' . $dir;
            if (is_dir($current)) {
                rename($current, $backup . '/' . $dir);
            }
            mkdir($current, 0775, true);
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            foreach (($manifest['files'] ?? []) as $file) {
                $relative = normalize_package_relative_path((string)$file['path']);
                $src = $packageDir . '/storage/' . $relative;
                $dst = $root . '/' . $relative;
                $dstDir = dirname($dst);
                if (!is_dir($dstDir)) {
                    mkdir($dstDir, 0775, true);
                }
                if (!copy($src, $dst)) {
                    throw new RuntimeException('Could not copy imported file: ' . $relative);
                }
            }

            import_database_array($pdo, $dbData);
            $reconciliation = reconcile_imported_media($pdo, $root, $manifest);

            // Final verification after all repaired paths have been stored.
            $verificationFailures = [];
            $stmt = $pdo->query('SELECT id, original_filename, stored_path, sha256 FROM media_files ORDER BY id');
            foreach ($stmt->fetchAll() as $row) {
                $path = storage_path((string)$row['stored_path']);
                $actualHash = is_file($path) ? strtolower(hash_file('sha256', $path)) : '';
                if ($actualHash === '' || !hash_equals(strtolower((string)$row['sha256']), $actualHash)) {
                    $verificationFailures[] = '#' . (int)$row['id'] . ' ' . (string)$row['original_filename'] . ' [' . (string)$row['stored_path'] . ']';
                }
            }
            if ($verificationFailures) {
                throw new RuntimeException('Imported file verification failed: ' . implode('; ', array_slice($verificationFailures, 0, 8)));
            }

            $pdo->commit();
            rrmdir($backup);
            return [
                'files' => (int)($manifest['file_count'] ?? 0),
                'cards' => count($dbData['tables']['cards'] ?? []),
                'repaired_media_references' => (int)$reconciliation['repairs'],
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($managed as $dir) {
                rrmdir($root . '/' . $dir);
                if (is_dir($backup . '/' . $dir)) {
                    rename($backup . '/' . $dir, $root . '/' . $dir);
                }
            }
            rrmdir($backup);
            throw $e;
        }
    } finally {
        if ($packageDir === $temp) {
            rrmdir($temp);
        }
    }
}
