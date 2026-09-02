<?php
// Authenticated media streaming endpoint with byte-range support.

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

try {
    require_user();
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        throw new RuntimeException('File ID is missing.');
    }

    $stmt = db()->prepare(
        'SELECT mf.*, c.space_id FROM media_files mf JOIN cards c ON c.id=mf.card_id WHERE mf.id=? LIMIT 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        http_response_code(404);
        exit('File not found.');
    }
    require_space_access((int)$row['space_id']);

    $preview = !empty($_GET['preview']);
    $relative = $preview ? (string)$row['preview_path'] : (string)$row['stored_path'];
    if ($relative === '') {
        http_response_code(404);
        exit('Preview not found.');
    }
    $path = storage_path($relative);
    if (!is_file($path)) {
        http_response_code(404);
        exit('Stored file not found.');
    }

    $size = filesize($path);
    $mime = $preview
        ? (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'svg' ? 'image/svg+xml' : 'image/jpeg')
        : (string)$row['mime'];
    $download = !empty($_GET['download']);
    $filename = $preview ? basename($path) : (string)$row['original_filename'];

    header('Content-Type: ' . $mime);
    header('Accept-Ranges: bytes');
    header('Cache-Control: private, max-age=86400');
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename*=UTF-8\'\'' . rawurlencode($filename));

    $start = 0;
    $end = max(0, $size - 1);
    if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', (string)$_SERVER['HTTP_RANGE'], $m)) {
        if ($m[1] !== '') $start = (int)$m[1];
        if ($m[2] !== '') $end = min($end, (int)$m[2]);
        if ($start > $end || $start >= $size) {
            header('Content-Range: bytes */' . $size);
            http_response_code(416);
            exit;
        }
        http_response_code(206);
        header("Content-Range: bytes {$start}-{$end}/{$size}");
    }

    $length = $end - $start + 1;
    header('Content-Length: ' . $length);
    $handle = fopen($path, 'rb');
    if (!$handle) throw new RuntimeException('Could not open stored file.');
    fseek($handle, $start);
    $remaining = $length;
    while ($remaining > 0 && !feof($handle)) {
        $chunk = fread($handle, min(1024 * 1024, $remaining));
        if ($chunk === false || $chunk === '') break;
        echo $chunk;
        $remaining -= strlen($chunk);
        flush();
    }
    fclose($handle);
} catch (SpaceLockedException $e) {
    http_response_code(403);
    exit('Space password required.');
} catch (Throwable $e) {
    http_response_code(500);
    exit($e->getMessage());
}
