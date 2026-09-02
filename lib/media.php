<?php
// Upload validation, preview generation and media metadata.

declare(strict_types=1);

function normalize_uploads(?array $files): array
{
    if (!$files || !isset($files['name'])) {
        return [];
    }
    if (!is_array($files['name'])) {
        return [$files];
    }
    $out = [];
    foreach ($files['name'] as $key => $name) {
        $entry = [
            'name' => $name,
            'type' => $files['type'][$key] ?? '',
            'tmp_name' => $files['tmp_name'][$key] ?? '',
            'error' => $files['error'][$key] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$key] ?? 0,
        ];
        if ((int)$entry['error'] !== UPLOAD_ERR_NO_FILE) {
            $out[] = $entry;
        }
    }
    return $out;
}

function upload_error_message(int $error): string
{
    return match ($error) {
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize.',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds the form limit.',
        UPLOAD_ERR_PARTIAL => 'File was uploaded only partially.',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Temporary upload folder is missing.',
        UPLOAD_ERR_CANT_WRITE => 'PHP could not write the uploaded file.',
        UPLOAD_ERR_EXTENSION => 'Upload was stopped by a PHP extension.',
        default => 'Upload error ' . $error . '.',
    };
}

function extension_from_name(string $name): string
{
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    return preg_match('/^[a-z0-9]{1,12}$/', $ext) ? $ext : '';
}

function inspect_upload(array $file, bool $allowAny = false): array
{
    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException(upload_error_message($error));
    }
    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > MAX_UPLOAD_MB * 1024 * 1024) {
        throw new RuntimeException('File is empty or exceeds the application limit of ' . MAX_UPLOAD_MB . ' MB.');
    }
    $tmp = (string)($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new RuntimeException('Invalid temporary upload file.');
    }

    $ext = extension_from_name((string)($file['name'] ?? ''));
    $mime = 'application/octet-stream';
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($tmp) ?: $mime;
    }

    $type = 'file';
    if (str_starts_with($mime, 'image/') && in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) {
        $type = 'image';
    } elseif (str_starts_with($mime, 'video/') || in_array($ext, ['mp4','webm','ogv','ogg','mov','avi','mkv','m4v'], true)) {
        $type = 'video';
    } elseif ($mime === 'application/pdf' || $ext === 'pdf') {
        $type = 'pdf';
        $mime = 'application/pdf';
    } elseif ($ext === 'stl' || in_array($mime, ['model/stl','application/sla','application/vnd.ms-pki.stl'], true)) {
        $type = 'stl';
        $mime = 'model/stl';
    }

    if (!$allowAny && $type === 'file') {
        throw new RuntimeException('This upload accepts only photos, videos, PDF documents and STL models.');
    }

    $width = null;
    $height = null;
    if ($type === 'image') {
        $info = @getimagesize($tmp);
        if ($info === false) {
            throw new RuntimeException('Invalid image file.');
        }
        $width = (int)$info[0];
        $height = (int)$info[1];
    }

    return [
        'extension' => $ext,
        'mime' => $mime,
        'media_type' => $type,
        'size_bytes' => $size,
        'width' => $width,
        'height' => $height,
    ];
}

function create_image_resource(string $path, string $mime)
{
    return match ($mime) {
        'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($path) : false,
        'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($path) : false,
        'image/gif' => function_exists('imagecreatefromgif') ? @imagecreatefromgif($path) : false,
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
        default => false,
    };
}

function generate_image_preview(string $source, string $target, string $mime): bool
{
    if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) {
        return false;
    }
    $info = @getimagesize($source);
    if ($info === false) {
        return false;
    }
    $srcW = (int)$info[0];
    $srcH = (int)$info[1];
    $scale = min(PREVIEW_MAX_WIDTH / $srcW, PREVIEW_MAX_HEIGHT / $srcH, 1);
    $dstW = max(1, (int)round($srcW * $scale));
    $dstH = max(1, (int)round($srcH * $scale));
    $src = create_image_resource($source, $mime);
    if (!$src) {
        return false;
    }
    $dst = imagecreatetruecolor($dstW, $dstH);
    $bg = imagecolorallocate($dst, 244, 246, 250);
    imagefilledrectangle($dst, 0, 0, $dstW, $dstH, $bg);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
    $ok = imagejpeg($dst, $target, 82);
    imagedestroy($src);
    imagedestroy($dst);
    return $ok;
}

function xml_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function create_placeholder_preview(string $target, string $type, string $filename): bool
{
    $labels = ['video' => 'VIDEO', 'pdf' => 'PDF', 'stl' => 'STL', 'file' => 'FILE', 'image' => 'IMAGE'];
    $label = $labels[$type] ?? 'FILE';
    $safeName = function_exists('mb_substr') ? mb_substr($filename, 0, 45, 'UTF-8') : substr($filename, 0, 45);
    $icon = match ($type) {
        'video' => '<polygon points="270,170 270,330 415,250" fill="#fff"/>',
        'pdf' => '<rect x="230" y="110" width="180" height="230" rx="14" fill="#fff"/><text x="320" y="250" text-anchor="middle" font-size="58" font-family="Arial" font-weight="700" fill="#c93434">PDF</text>',
        'stl' => '<path d="M320 105l135 77v150l-135 78-135-78V182z" fill="none" stroke="#fff" stroke-width="16"/><path d="M185 182l135 78 135-78M320 260v150" fill="none" stroke="#fff" stroke-width="12"/>',
        default => '<path d="M235 105h135l65 65v230H235z" fill="#fff"/><path d="M370 105v70h65" fill="none" stroke="#aeb8cc" stroke-width="12"/>',
    };
    $svg = '<?xml version="1.0" encoding="UTF-8"?>'
        . '<svg xmlns="http://www.w3.org/2000/svg" width="640" height="500" viewBox="0 0 640 500">'
        . '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#28344c"/><stop offset="1" stop-color="#101827"/></linearGradient></defs>'
        . '<rect width="640" height="500" rx="24" fill="url(#g)"/>' . $icon
        . '<rect x="28" y="420" width="584" height="52" rx="14" fill="#000" opacity=".36"/>'
        . '<text x="320" y="454" text-anchor="middle" font-size="20" font-family="Arial" fill="#fff">'
        . xml_escape($label . ' · ' . $safeName) . '</text></svg>';
    return file_put_contents($target, $svg) !== false;
}

function save_browser_preview(array $previewFile, string $target): bool
{
    try {
        $meta = inspect_upload($previewFile, true);
    } catch (Throwable) {
        return false;
    }
    if ($meta['media_type'] !== 'image') {
        return false;
    }
    return generate_image_preview((string)$previewFile['tmp_name'], $target, (string)$meta['mime']);
}

function media_storage_directory(int $spaceId, string $kind): string
{
    $date = new DateTimeImmutable();
    $relative = $kind . '/' . $spaceId . '/' . $date->format('Y/m');
    $absolute = storage_path($relative);
    if (!is_dir($absolute) && !mkdir($absolute, 0775, true) && !is_dir($absolute)) {
        throw new RuntimeException('Could not create storage subfolder.');
    }
    return $relative;
}

function persist_uploaded_file(
    PDO $pdo,
    int $cardId,
    int $spaceId,
    array $file,
    string $role,
    int $sortOrder,
    bool $allowAny,
    ?array $videoPreview = null
): int {
    $meta = inspect_upload($file, $allowAny);
    $ext = $meta['extension'] ?: match ($meta['media_type']) {
        'image' => 'jpg', 'video' => 'mp4', 'pdf' => 'pdf', 'stl' => 'stl', default => 'bin',
    };
    $dir = media_storage_directory($spaceId, $role === 'inline' ? 'note-images' : 'files');
    $storedName = random_filename($ext);
    $relativePath = $dir . '/' . $storedName;
    $absolutePath = storage_path($relativePath);
    if (!move_uploaded_file((string)$file['tmp_name'], $absolutePath)) {
        throw new RuntimeException('Could not save uploaded file.');
    }

    $previewDir = media_storage_directory($spaceId, 'previews');
    $previewPath = '';
    if ($meta['media_type'] === 'image') {
        $previewPath = $previewDir . '/' . pathinfo($storedName, PATHINFO_FILENAME) . '.jpg';
        if (!generate_image_preview($absolutePath, storage_path($previewPath), (string)$meta['mime'])) {
            $previewPath = $previewDir . '/' . pathinfo($storedName, PATHINFO_FILENAME) . '.svg';
            create_placeholder_preview(storage_path($previewPath), 'image', (string)$file['name']);
        }
    } elseif ($meta['media_type'] === 'video' && $videoPreview) {
        $previewPath = $previewDir . '/' . pathinfo($storedName, PATHINFO_FILENAME) . '.jpg';
        if (!save_browser_preview($videoPreview, storage_path($previewPath))) {
            $previewPath = '';
        }
    }
    if ($previewPath === '') {
        $previewPath = $previewDir . '/' . pathinfo($storedName, PATHINFO_FILENAME) . '.svg';
        create_placeholder_preview(storage_path($previewPath), (string)$meta['media_type'], (string)$file['name']);
    }

    $sha = hash_file('sha256', $absolutePath);
    $stmt = $pdo->prepare(
        'INSERT INTO media_files
         (card_id, role, original_filename, stored_path, preview_path, media_type, mime, size_bytes, width, height, sort_order, sha256)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $cardId, $role, clean_filename((string)$file['name']), $relativePath, $previewPath,
        $meta['media_type'], $meta['mime'], $meta['size_bytes'], $meta['width'], $meta['height'], $sortOrder, $sha,
    ]);
    return (int)$pdo->lastInsertId();
}

function clean_filename(string $name): string
{
    $name = basename(str_replace('\\', '/', $name));
    $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? $name;
    return function_exists('mb_substr') ? mb_substr($name, 0, 255, 'UTF-8') : substr($name, 0, 255);
}
