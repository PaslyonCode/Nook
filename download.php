<?php
// Authenticated export-package download endpoint.

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

try {
    require_user();
    $name = basename((string)($_GET['name'] ?? ''));
    if ($name === '' || !str_ends_with(strtolower($name), '.zip')) {
        throw new RuntimeException('Invalid export filename.');
    }
    $path = storage_path('exports/' . $name);
    if (!is_file($path)) {
        http_response_code(404);
        exit('Export not found.');
    }
    header('Content-Type: application/zip');
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($name));
    readfile($path);
} catch (Throwable $e) {
    http_response_code(500);
    exit($e->getMessage());
}
