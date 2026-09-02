<?php
// Anonymous logo endpoint for the Nook public frontend.

declare(strict_types=1);
require_once __DIR__ . '/public_common.php';
try {
    $relative = public_setting('public_logo_path', '');
    if ($relative === '') { http_response_code(404); exit; }
    $path = public_storage_path($relative);
    if (!is_file($path)) { http_response_code(404); exit; }
    header('Content-Type: image/png');
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: public, max-age=3600');
    readfile($path);
} catch (Throwable $e) { http_response_code(404); }
