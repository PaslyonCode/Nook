<?php
// Repairs missing media files after an interrupted legacy migration.
// Usage:
// php tools/repair_storage.php --legacy-uploads="C:/laragon/www/nook/uploads"

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/lib/media.php';
require_once dirname(__DIR__) . '/lib/package.php';

$options = getopt('', ['legacy-uploads:']);
$legacyRoot = trim((string)($options['legacy-uploads'] ?? ''));
$extraRoots = $legacyRoot !== '' ? [$legacyRoot] : [];

try {
    $report = repair_storage_references(db(), $extraRoots);
    echo "Storage root: " . storage_root(true) . "\n";
    echo "Media rows checked: " . $report['media_rows'] . "\n";
    echo "Recovered originals: " . $report['recovered_originals'] . "\n";
    echo "Recovered previews: " . $report['recovered_previews'] . "\n";
    echo "Generated preview placeholders: " . $report['generated_previews'] . "\n";
    if ($report['legacy_roots']) {
        echo "Legacy folders checked:\n";
        foreach ($report['legacy_roots'] as $root) {
            echo "  - {$root}\n";
        }
    }
    if ($report['missing']) {
        fwrite(STDERR, "Missing originals that could not be recovered:\n");
        foreach ($report['missing'] as $item) {
            fwrite(STDERR, "  - {$item}\n");
        }
        exit(1);
    }
    echo "Storage integrity repair completed. You can create a new export now.\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "Storage repair failed: {$e->getMessage()}\n");
    exit(1);
}
