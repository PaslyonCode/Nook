<?php
// api.php
// JSON API for media groups, notes, and trash.

declare(strict_types=1);

require_once __DIR__ . '/config.php';

ini_set('display_errors', '0');
error_reporting(E_ALL);

set_exception_handler(function (Throwable $e): void {
    json_response([
        'ok' => false,
        'error' => $e->getMessage(),
    ], 500);
});

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function require_auth_json(): void
{
    if (!is_logged_in()) {
        json_response([
            'ok' => false,
            'auth' => false,
            'error' => 'Login and password are required.',
        ], 401);
    }
}

function get_action(): string
{
    return (string)($_GET['action'] ?? $_POST['action'] ?? '');
}

function clean_text(?string $value, int $maxLen = 10000): string
{
    $value = trim((string)$value);
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $maxLen, 'UTF-8');
    }
    return substr($value, 0, $maxLen);
}

function normalize_tag_name(string $tag): string
{
    $tag = trim($tag);
    $tag = ltrim($tag, "#＃ ");
    $tag = preg_replace('/[^\p{L}\p{N}_\-]+/u', '', $tag) ?? '';

    if (function_exists('mb_strtolower')) {
        $tag = mb_strtolower($tag, 'UTF-8');
    } else {
        $tag = strtolower($tag);
    }

    if (function_exists('mb_substr')) {
        $tag = mb_substr($tag, 0, 80, 'UTF-8');
    } else {
        $tag = substr($tag, 0, 80);
    }

    return $tag;
}

function parse_hashtags(?string $input): array
{
    $input = trim((string)$input);
    if ($input === '') {
        return [];
    }

    $parts = preg_split('/[\s,;]+/u', $input) ?: [];
    $tags = [];

    foreach ($parts as $part) {
        $tag = normalize_tag_name($part);
        if ($tag !== '') {
            $tags[$tag] = true;
        }
    }

    return array_slice(array_keys($tags), 0, 50);
}

function bool_from_post(string $key): int
{
    $value = $_POST[$key] ?? '0';
    return in_array((string)$value, ['1', 'true', 'on', 'yes'], true) ? 1 : 0;
}

function normalize_files_array(?array $files): array
{
    if (!$files || !isset($files['name'])) {
        return [];
    }

    $normalized = [];

    if (is_array($files['name'])) {
        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            $normalized[] = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i] ?? '',
                'tmp_name' => $files['tmp_name'][$i] ?? '',
                'error'    => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size'     => $files['size'][$i] ?? 0,
            ];
        }
    } else {
        $normalized[] = $files;
    }

    return array_values(array_filter($normalized, static function (array $file): bool {
        return ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }));
}

function extension_for_mime(string $mime, string $originalName = ''): string
{
    $map = [
        'image/jpeg'       => 'jpg',
        'image/png'        => 'png',
        'image/gif'        => 'gif',
        'image/webp'       => 'webp',
        'video/mp4'        => 'mp4',
        'video/webm'       => 'webm',
        'video/ogg'        => 'ogv',
        'video/quicktime'  => 'mov',
        'video/x-msvideo'  => 'avi',
        'video/x-matroska' => 'mkv',
    ];

    if (isset($map[$mime])) {
        return $map[$mime];
    }

    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (preg_match('/^[a-z0-9]{1,8}$/', $ext)) {
        return $ext;
    }

    return 'bin';
}

function detect_media_mime(string $tmpFile): string
{
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($tmpFile);

    $allowed = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'video/mp4',
        'video/webm',
        'video/ogg',
        'video/quicktime',
        'video/x-msvideo',
        'video/x-matroska',
    ];

    if (!in_array($mime, $allowed, true)) {
        throw new RuntimeException('Allowed files: JPEG, PNG, GIF, WEBP images and MP4, WEBM, OGG, MOV, AVI, MKV videos. Detected type: ' . $mime);
    }

    return $mime;
}

function detect_note_image_mime(string $tmpFile): string
{
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($tmpFile);
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime, $allowed, true)) {
        throw new RuntimeException('Notes can contain JPEG, PNG, GIF, or WEBP images. Detected type: ' . $mime);
    }
    return $mime;
}

function media_type_from_mime(string $mime): string
{
    if (str_starts_with($mime, 'image/')) {
        return 'image';
    }
    if (str_starts_with($mime, 'video/')) {
        return 'video';
    }
    return 'file';
}

function upload_error_message(int $error): string
{
    $messages = [
        UPLOAD_ERR_INI_SIZE   => 'The file exceeds the upload_max_filesize limit in php.ini.',
        UPLOAD_ERR_FORM_SIZE  => 'The file exceeds the form upload limit.',
        UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'The server has no temporary upload directory.',
        UPLOAD_ERR_CANT_WRITE => 'PHP could not write the file to disk.',
        UPLOAD_ERR_EXTENSION  => 'The upload was stopped by a PHP extension.',
    ];
    return $messages[$error] ?? 'File upload error: ' . $error;
}

function validate_uploaded_media(array $file): array
{
    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException(upload_error_message($error));
    }

    $size = (int)($file['size'] ?? 0);
    $maxBytes = MAX_UPLOAD_MB * 1024 * 1024;
    if ($size <= 0 || $size > $maxBytes) {
        throw new RuntimeException('The file is empty or exceeds the limit of ' . MAX_UPLOAD_MB . ' MB.');
    }

    $tmp = (string)($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new RuntimeException('Invalid temporary upload file.');
    }

    $mime = detect_media_mime($tmp);
    $mediaType = media_type_from_mime($mime);
    $width = null;
    $height = null;

    if ($mediaType === 'image') {
        $info = getimagesize($tmp);
        if ($info === false) {
            throw new RuntimeException('The file is not a valid image.');
        }
        $width = (int)$info[0];
        $height = (int)$info[1];
    }

    return [
        'mime' => $mime,
        'media_type' => $mediaType,
        'width' => $width,
        'height' => $height,
    ];
}

function create_image_resource(string $path, string $mime)
{
    return match ($mime) {
        'image/jpeg' => function_exists('imagecreatefromjpeg') ? imagecreatefromjpeg($path) : false,
        'image/png'  => function_exists('imagecreatefrompng') ? imagecreatefrompng($path) : false,
        'image/gif'  => function_exists('imagecreatefromgif') ? imagecreatefromgif($path) : false,
        'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : false,
        default      => false,
    };
}

function save_image_resource($image, string $path, string $mime): bool
{
    return match ($mime) {
        'image/jpeg' => imagejpeg($image, $path, 86),
        'image/png'  => imagepng($image, $path, 6),
        'image/gif'  => imagegif($image, $path),
        'image/webp' => function_exists('imagewebp') ? imagewebp($image, $path, 86) : false,
        default      => false,
    };
}

function create_thumbnail(string $sourcePath, string $thumbPath, string $mime, int $maxW = 700, int $maxH = 700): void
{
    if (!extension_loaded('gd')) {
        copy($sourcePath, $thumbPath);
        return;
    }

    $size = getimagesize($sourcePath);
    if ($size === false) {
        copy($sourcePath, $thumbPath);
        return;
    }

    [$srcW, $srcH] = [(int)$size[0], (int)$size[1]];
    if ($srcW <= 0 || $srcH <= 0) {
        copy($sourcePath, $thumbPath);
        return;
    }

    $scale = min($maxW / $srcW, $maxH / $srcH, 1);
    $dstW = max(1, (int)round($srcW * $scale));
    $dstH = max(1, (int)round($srcH * $scale));

    $src = create_image_resource($sourcePath, $mime);
    if (!$src) {
        copy($sourcePath, $thumbPath);
        return;
    }

    $dst = imagecreatetruecolor($dstW, $dstH);

    if (in_array($mime, ['image/png', 'image/gif', 'image/webp'], true)) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $dstW, $dstH, $transparent);
    }

    imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

    if (!save_image_resource($dst, $thumbPath, $mime)) {
        copy($sourcePath, $thumbPath);
    }

    imagedestroy($src);
    imagedestroy($dst);
}

function public_media_url(string $base, string $filename): string
{
    return $base . '/' . rawurlencode($filename);
}

function sanitize_note_html(string $html): string
{
    $html = trim($html);
    if ($html === '') {
        return '';
    }

    if (function_exists('mb_substr')) {
        $html = mb_substr($html, 0, 10_000_000, 'UTF-8');
    } else {
        $html = substr($html, 0, 10_000_000);
    }

    // Basic protection against executable code. This is enough for a local personal app,
    // but a public server should use a full HTML sanitizer.
    $html = preg_replace('#<\s*(script|style|iframe|object|embed|link|meta)[^>]*>.*?<\s*/\s*\1\s*>#isu', '', $html) ?? '';
    $html = preg_replace('#<\s*(script|style|iframe|object|embed|link|meta)[^>]*\s*/?>#isu', '', $html) ?? '';
    $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/iu', '', $html) ?? '';
    $html = preg_replace('/(href|src)\s*=\s*("|\')\s*javascript:[^"\']*("|\')/iu', '$1="#"', $html) ?? '';
    $html = preg_replace('/expression\s*\(/iu', '', $html) ?? '';

    return $html;
}

function sanitize_note_json(string $json): string
{
    $json = trim($json);
    if ($json === '') {
        return '';
    }

    if (function_exists('mb_substr')) {
        $json = mb_substr($json, 0, 10_000_000, 'UTF-8');
    } else {
        $json = substr($json, 0, 10_000_000);
    }

    $decoded = json_decode($json, true);
    if (!is_array($decoded) || !isset($decoded['blocks']) || !is_array($decoded['blocks'])) {
        return '';
    }

    return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function plain_from_html(string $html, int $maxLen = 700): string
{
    $text = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
    if (function_exists('mb_substr')) {
        return mb_substr($text, 0, $maxLen, 'UTF-8');
    }
    return substr($text, 0, $maxLen);
}

function sync_tags(PDO $pdo, int $cardId, array $tags): void
{
    $pdo->prepare('DELETE FROM card_tags WHERE card_id = ?')->execute([$cardId]);

    if ($tags) {
        $insertTag = $pdo->prepare('INSERT IGNORE INTO tags (name) VALUES (?)');
        $selectTag = $pdo->prepare('SELECT id FROM tags WHERE name = ?');
        $linkTag = $pdo->prepare('INSERT IGNORE INTO card_tags (card_id, tag_id) VALUES (?, ?)');

        foreach ($tags as $tag) {
            $insertTag->execute([$tag]);
            $selectTag->execute([$tag]);
            $tagId = (int)$selectTag->fetchColumn();
            if ($tagId > 0) {
                $linkTag->execute([$cardId, $tagId]);
            }
        }
    }

    cleanup_orphan_tags($pdo);
}

function cleanup_orphan_tags(PDO $pdo): void
{
    $pdo->exec('DELETE t FROM tags t LEFT JOIN card_tags ct ON ct.tag_id = t.id WHERE ct.tag_id IS NULL');
}

function card_exists(PDO $pdo, int $cardId): bool
{
    $stmt = $pdo->prepare('SELECT id FROM cards WHERE id = ?');
    $stmt->execute([$cardId]);
    return (bool)$stmt->fetchColumn();
}

function active_card_exists(PDO $pdo, int $cardId, ?string $entryType = null): bool
{
    $sql = 'SELECT id FROM cards WHERE id = ? AND deleted_at IS NULL';
    $params = [$cardId];
    if ($entryType !== null) {
        $sql .= ' AND entry_type = ?';
        $params[] = $entryType;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (bool)$stmt->fetchColumn();
}

function insert_uploaded_media(PDO $pdo, int $cardId, array $file, int $sortOrder, array &$savedPaths): void
{
    $meta = validate_uploaded_media($file);
    $mime = $meta['mime'];
    $mediaType = $meta['media_type'];
    $ext = extension_for_mime($mime, (string)$file['name']);

    $baseName = bin2hex(random_bytes(16));
    $storedFilename = $baseName . '.' . $ext;
    $thumbFilename = '';

    $originalPath = UPLOAD_ORIGINAL_DIR . '/' . $storedFilename;

    if (!move_uploaded_file((string)$file['tmp_name'], $originalPath)) {
        throw new RuntimeException('Could not save file: ' . $file['name']);
    }
    $savedPaths[] = $originalPath;

    if ($mediaType === 'image') {
        $thumbFilename = $baseName . '_thumb.' . $ext;
        $thumbPath = UPLOAD_THUMB_DIR . '/' . $thumbFilename;
        create_thumbnail($originalPath, $thumbPath, $mime);
        $savedPaths[] = $thumbPath;
    }

    $insertImage = $pdo->prepare(
        'INSERT INTO images
         (card_id, original_filename, stored_filename, thumb_filename, media_type, mime, width, height, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $insertImage->execute([
        $cardId,
        clean_text((string)$file['name'], 255),
        $storedFilename,
        $thumbFilename,
        $mediaType,
        $mime,
        $meta['width'],
        $meta['height'],
        $sortOrder,
    ]);
}

function enrich_card_row(array $row): array
{
    $row['id'] = (int)$row['id'];
    $row['is_hidden'] = (int)($row['is_hidden'] ?? 0);
    $row['is_deleted'] = !empty($row['deleted_at']);
    $row['images'] = [];
    $row['tags'] = [];
    $row['body_text'] = plain_from_html((string)($row['body_html'] ?? ''));
    return $row;
}

function fetch_cards(PDO $pdo, array $ids): array
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("SELECT * FROM cards WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $cardsById = [];
    foreach ($stmt->fetchAll() as $row) {
        $row = enrich_card_row($row);
        $cardsById[$row['id']] = $row;
    }

    $stmt = $pdo->prepare("SELECT * FROM images WHERE card_id IN ($placeholders) ORDER BY sort_order ASC, id ASC");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $image) {
        $cardId = (int)$image['card_id'];
        if (!isset($cardsById[$cardId])) {
            continue;
        }
        $image['id'] = (int)$image['id'];
        $image['card_id'] = $cardId;
        $image['width'] = $image['width'] !== null ? (int)$image['width'] : null;
        $image['height'] = $image['height'] !== null ? (int)$image['height'] : null;
        $image['media_type'] = $image['media_type'] ?? media_type_from_mime((string)$image['mime']);
        $image['url'] = public_media_url(UPLOAD_ORIGINAL_URL, $image['stored_filename']);
        $image['thumb_url'] = $image['thumb_filename'] !== ''
            ? public_media_url(UPLOAD_THUMB_URL, $image['thumb_filename'])
            : null;
        $cardsById[$cardId]['images'][] = $image;
    }

    $stmt = $pdo->prepare(
        "SELECT ct.card_id, t.name
         FROM card_tags ct
         JOIN tags t ON t.id = ct.tag_id
         WHERE ct.card_id IN ($placeholders)
         ORDER BY t.name ASC"
    );
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $tagRow) {
        $cardId = (int)$tagRow['card_id'];
        if (isset($cardsById[$cardId])) {
            $cardsById[$cardId]['tags'][] = $tagRow['name'];
        }
    }

    $ordered = [];
    foreach ($ids as $id) {
        if (isset($cardsById[$id])) {
            $ordered[] = $cardsById[$id];
        }
    }

    return $ordered;
}

function fetch_all_tags(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT t.name, COUNT(ct.card_id) AS cards_count
         FROM tags t
         JOIN card_tags ct ON ct.tag_id = t.id
         JOIN cards c ON c.id = ct.card_id AND c.deleted_at IS NULL
         GROUP BY t.id, t.name
         ORDER BY t.name ASC"
    );

    return array_map(static function (array $row): array {
        return [
            'name' => $row['name'],
            'cards_count' => (int)$row['cards_count'],
        ];
    }, $stmt->fetchAll());
}

function build_filtered_ids(PDO $pdo, bool $trashMode): array
{
    $q = clean_text($_GET['q'] ?? '', 255);
    $dateFrom = clean_text($_GET['date_from'] ?? '', 20);
    $dateTo = clean_text($_GET['date_to'] ?? '', 20);
    $tag = normalize_tag_name((string)($_GET['tag'] ?? ''));

    $where = [$trashMode ? 'c.deleted_at IS NOT NULL' : 'c.deleted_at IS NULL'];
    $params = [];
    $hasFilter = ($q !== '' || $dateFrom !== '' || $dateTo !== '' || $tag !== '');

    if (!$trashMode && !$hasFilter) {
        $where[] = 'c.is_hidden = 0';
    }

    if ($q !== '') {
        $like = '%' . $q . '%';
        $where[] = "(
            c.title LIKE ?
            OR c.description LIKE ?
            OR c.body_html LIKE ?
            OR EXISTS (
                SELECT 1 FROM images i
                WHERE i.card_id = c.id AND i.original_filename LIKE ?
            )
            OR EXISTS (
                SELECT 1 FROM card_tags ct
                JOIN tags t ON t.id = ct.tag_id
                WHERE ct.card_id = c.id AND t.name LIKE ?
            )
        )";
        array_push($params, $like, $like, $like, $like, $like);
    }

    if ($dateFrom !== '') {
        $where[] = 'DATE(c.created_at) >= ?';
        $params[] = $dateFrom;
    }

    if ($dateTo !== '') {
        $where[] = 'DATE(c.created_at) <= ?';
        $params[] = $dateTo;
    }

    if ($tag !== '') {
        $where[] = "EXISTS (
            SELECT 1 FROM card_tags ct
            JOIN tags t ON t.id = ct.tag_id
            WHERE ct.card_id = c.id AND t.name = ?
        )";
        $params[] = $tag;
    }

    $order = $trashMode ? 'c.deleted_at DESC, c.id DESC' : 'c.created_at DESC, c.id DESC';
    $sql = 'SELECT c.id FROM cards c WHERE ' . implode(' AND ', $where) . ' ORDER BY ' . $order;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function action_list(PDO $pdo): void
{
    $mode = clean_text($_GET['mode'] ?? '', 20);
    $trashMode = ($mode === 'trash');
    $ids = build_filtered_ids($pdo, $trashMode);

    json_response([
        'ok' => true,
        'cards' => fetch_cards($pdo, $ids),
        'tags' => fetch_all_tags($pdo),
        'mode' => $trashMode ? 'trash' : 'active',
    ]);
}

function action_get(PDO $pdo): void
{
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        json_response(['ok' => false, 'error' => 'Entry ID is missing.'], 400);
    }

    $cards = fetch_cards($pdo, [$id]);
    if (!$cards) {
        json_response(['ok' => false, 'error' => 'Entry was not found.'], 404);
    }

    json_response(['ok' => true, 'card' => $cards[0]]);
}

function action_create(PDO $pdo): void
{
    ensure_upload_dirs();

    $files = normalize_files_array($_FILES['images'] ?? null);
    if (!$files) {
        json_response(['ok' => false, 'error' => 'Add at least one photo or video file.'], 400);
    }

    $title = clean_text($_POST['title'] ?? '', 255);
    $description = clean_text($_POST['description'] ?? '', 50000);
    $tags = parse_hashtags($_POST['hashtags'] ?? '');
    $isHidden = bool_from_post('is_hidden');

    foreach ($files as $file) {
        validate_uploaded_media($file);
    }

    $savedPaths = [];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('INSERT INTO cards (entry_type, title, description, body_html, is_hidden) VALUES (\'media\', ?, ?, NULL, ?)');
        $stmt->execute([$title, $description, $isHidden]);
        $cardId = (int)$pdo->lastInsertId();

        foreach ($files as $index => $file) {
            insert_uploaded_media($pdo, $cardId, $file, $index, $savedPaths);
        }

        sync_tags($pdo, $cardId, $tags);
        $pdo->commit();

        $card = fetch_cards($pdo, [$cardId])[0] ?? null;
        json_response(['ok' => true, 'card' => $card, 'tags' => fetch_all_tags($pdo)]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        foreach ($savedPaths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
        throw $e;
    }
}

function action_update(PDO $pdo): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        json_response(['ok' => false, 'error' => 'Group ID is missing.'], 400);
    }

    $title = clean_text($_POST['title'] ?? '', 255);
    $description = clean_text($_POST['description'] ?? '', 50000);
    $tags = parse_hashtags($_POST['hashtags'] ?? '');
    $isHidden = bool_from_post('is_hidden');

    if (!active_card_exists($pdo, $id, 'media')) {
        json_response(['ok' => false, 'error' => 'Active group was not found.'], 404);
    }

    $pdo->beginTransaction();
    $stmt = $pdo->prepare('UPDATE cards SET title = ?, description = ?, is_hidden = ?, updated_at = NOW() WHERE id = ? AND entry_type = \'media\'');
    $stmt->execute([$title, $description, $isHidden, $id]);
    sync_tags($pdo, $id, $tags);
    $pdo->commit();

    $card = fetch_cards($pdo, [$id])[0] ?? null;
    json_response(['ok' => true, 'card' => $card, 'tags' => fetch_all_tags($pdo)]);
}

function action_add_images(PDO $pdo): void
{
    ensure_upload_dirs();

    $cardId = (int)($_POST['id'] ?? $_POST['card_id'] ?? 0);
    if ($cardId <= 0) {
        json_response(['ok' => false, 'error' => 'Group ID is missing.'], 400);
    }
    if (!active_card_exists($pdo, $cardId, 'media')) {
        json_response(['ok' => false, 'error' => 'Active group was not found.'], 404);
    }

    $files = normalize_files_array($_FILES['images'] ?? null);
    if (!$files) {
        json_response(['ok' => false, 'error' => 'Select at least one photo or video file.'], 400);
    }

    foreach ($files as $file) {
        validate_uploaded_media($file);
    }

    $savedPaths = [];

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), -1) FROM images WHERE card_id = ?');
        $stmt->execute([$cardId]);
        $sortOrder = (int)$stmt->fetchColumn() + 1;

        foreach ($files as $file) {
            insert_uploaded_media($pdo, $cardId, $file, $sortOrder, $savedPaths);
            $sortOrder++;
        }

        $pdo->prepare('UPDATE cards SET updated_at = NOW() WHERE id = ?')->execute([$cardId]);
        $pdo->commit();

        $card = fetch_cards($pdo, [$cardId])[0] ?? null;
        json_response(['ok' => true, 'card' => $card]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        foreach ($savedPaths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
        throw $e;
    }
}

function action_delete_image(PDO $pdo): void
{
    $cardId = (int)($_POST['id'] ?? $_POST['card_id'] ?? 0);
    $imageId = (int)($_POST['image_id'] ?? 0);

    if ($cardId <= 0 || $imageId <= 0) {
        json_response(['ok' => false, 'error' => 'Group ID or file ID is missing.'], 400);
    }

    if (!active_card_exists($pdo, $cardId, 'media')) {
        json_response(['ok' => false, 'error' => 'Active group was not found.'], 404);
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT i.*, (SELECT COUNT(*) FROM images WHERE card_id = i.card_id) AS images_count
         FROM images i
         WHERE i.id = ? AND i.card_id = ?
         FOR UPDATE'
    );
    $stmt->execute([$imageId, $cardId]);
    $image = $stmt->fetch();

    if (!$image) {
        $pdo->rollBack();
        json_response(['ok' => false, 'error' => 'File was not found.'], 404);
    }

    if ((int)$image['images_count'] <= 1) {
        $pdo->rollBack();
        json_response(['ok' => false, 'error' => 'The only file cannot be deleted. Delete the whole group instead.'], 400);
    }

    $pdo->prepare('DELETE FROM images WHERE id = ?')->execute([$imageId]);
    $pdo->prepare('UPDATE cards SET updated_at = NOW() WHERE id = ?')->execute([$cardId]);
    $pdo->commit();

    $original = UPLOAD_ORIGINAL_DIR . '/' . $image['stored_filename'];
    $thumb = UPLOAD_THUMB_DIR . '/' . ($image['thumb_filename'] ?? '');
    if (is_file($original)) {
        @unlink($original);
    }
    if (($image['thumb_filename'] ?? '') !== '' && is_file($thumb)) {
        @unlink($thumb);
    }

    $card = fetch_cards($pdo, [$cardId])[0] ?? null;
    json_response(['ok' => true, 'card' => $card]);
}

function action_create_note(PDO $pdo): void
{
    $title = clean_text($_POST['title'] ?? '', 255);
    $bodyHtml = sanitize_note_html((string)($_POST['body_html'] ?? ''));
    $bodyJson = sanitize_note_json((string)($_POST['body_json'] ?? ''));
    $tags = parse_hashtags($_POST['hashtags'] ?? '');
    $isHidden = bool_from_post('is_hidden');

    $pdo->beginTransaction();
    $stmt = $pdo->prepare('INSERT INTO cards (entry_type, title, description, body_html, body_json, is_hidden) VALUES (\'note\', ?, \'\', ?, ?, ?)');
    $stmt->execute([$title, $bodyHtml, $bodyJson, $isHidden]);
    $cardId = (int)$pdo->lastInsertId();
    sync_tags($pdo, $cardId, $tags);
    $pdo->commit();

    $card = fetch_cards($pdo, [$cardId])[0] ?? null;
    json_response(['ok' => true, 'card' => $card, 'tags' => fetch_all_tags($pdo)]);
}

function action_update_note(PDO $pdo): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        json_response(['ok' => false, 'error' => 'Note ID is missing.'], 400);
    }
    if (!active_card_exists($pdo, $id, 'note')) {
        json_response(['ok' => false, 'error' => 'Active note was not found.'], 404);
    }

    $title = clean_text($_POST['title'] ?? '', 255);
    $bodyHtml = sanitize_note_html((string)($_POST['body_html'] ?? ''));
    $bodyJson = sanitize_note_json((string)($_POST['body_json'] ?? ''));
    $tags = parse_hashtags($_POST['hashtags'] ?? '');
    $isHidden = bool_from_post('is_hidden');

    $pdo->beginTransaction();
    $stmt = $pdo->prepare('UPDATE cards SET title = ?, body_html = ?, body_json = ?, is_hidden = ?, updated_at = NOW() WHERE id = ? AND entry_type = \'note\'');
    $stmt->execute([$title, $bodyHtml, $bodyJson, $isHidden, $id]);
    sync_tags($pdo, $id, $tags);
    $pdo->commit();

    $card = fetch_cards($pdo, [$id])[0] ?? null;
    json_response(['ok' => true, 'card' => $card, 'tags' => fetch_all_tags($pdo)]);
}

function action_note_upload_image(PDO $pdo): void
{
    ensure_upload_dirs();

    $files = normalize_files_array($_FILES['image'] ?? null);
    $file = $files[0] ?? null;
    if (!$file) {
        json_response(['ok' => false, 'error' => 'Select an image to insert.'], 400);
    }

    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException(upload_error_message($error));
    }
    $size = (int)($file['size'] ?? 0);
    $maxBytes = MAX_UPLOAD_MB * 1024 * 1024;
    if ($size <= 0 || $size > $maxBytes) {
        throw new RuntimeException('The image is empty or exceeds the limit of ' . MAX_UPLOAD_MB . ' MB.');
    }
    $tmp = (string)($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new RuntimeException('Invalid temporary upload file.');
    }

    $mime = detect_note_image_mime($tmp);
    $info = getimagesize($tmp);
    if ($info === false) {
        throw new RuntimeException('The file is not a valid image.');
    }

    $ext = extension_for_mime($mime, (string)$file['name']);
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $path = UPLOAD_NOTE_DIR . '/' . $filename;
    if (!move_uploaded_file($tmp, $path)) {
        throw new RuntimeException('Could not save the note image.');
    }

    $url = public_media_url(UPLOAD_NOTE_URL, $filename);
    json_response([
        'ok' => true,
        'success' => 1,
        'url' => $url,
        'file' => [
            'url' => $url,
            'filename' => $filename,
            'width' => (int)$info[0],
            'height' => (int)$info[1],
        ],
    ]);
}

function action_delete(PDO $pdo): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        json_response(['ok' => false, 'error' => 'Entry ID is missing.'], 400);
    }

    $stmt = $pdo->prepare('UPDATE cards SET deleted_at = NOW(), updated_at = NOW() WHERE id = ? AND deleted_at IS NULL');
    $stmt->execute([$id]);
    cleanup_orphan_tags($pdo);

    json_response(['ok' => true, 'tags' => fetch_all_tags($pdo)]);
}

function action_restore(PDO $pdo): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        json_response(['ok' => false, 'error' => 'Entry ID is missing.'], 400);
    }

    $stmt = $pdo->prepare('UPDATE cards SET deleted_at = NULL, updated_at = NOW() WHERE id = ? AND deleted_at IS NOT NULL');
    $stmt->execute([$id]);

    json_response(['ok' => true, 'tags' => fetch_all_tags($pdo)]);
}

function collect_note_image_paths(string $html): array
{
    $paths = [];
    if ($html === '') {
        return $paths;
    }

    $pattern = '#(?:src=["\'])(?:\.\/)?' . preg_quote(UPLOAD_NOTE_URL, '#') . '/([^"\'?#]+)#iu';
    if (preg_match_all($pattern, $html, $matches)) {
        foreach ($matches[1] as $encoded) {
            $filename = rawurldecode($encoded);
            $filename = basename($filename);
            if ($filename !== '') {
                $paths[] = UPLOAD_NOTE_DIR . '/' . $filename;
            }
        }
    }
    return array_values(array_unique($paths));
}

function collect_permanent_delete_paths(PDO $pdo, array $ids): array
{
    if (!$ids) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $paths = [];

    $stmt = $pdo->prepare("SELECT stored_filename, thumb_filename FROM images WHERE card_id IN ($placeholders)");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $image) {
        $paths[] = UPLOAD_ORIGINAL_DIR . '/' . $image['stored_filename'];
        if (($image['thumb_filename'] ?? '') !== '') {
            $paths[] = UPLOAD_THUMB_DIR . '/' . $image['thumb_filename'];
        }
    }

    $stmt = $pdo->prepare("SELECT body_html FROM cards WHERE id IN ($placeholders) AND entry_type = 'note'");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $html) {
        $paths = array_merge($paths, collect_note_image_paths((string)$html));
    }

    return array_values(array_unique($paths));
}

function action_empty_trash(PDO $pdo): void
{
    $ids = $pdo->query('SELECT id FROM cards WHERE deleted_at IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN);
    $ids = array_map('intval', $ids);
    if (!$ids) {
        json_response(['ok' => true, 'deleted' => 0, 'tags' => fetch_all_tags($pdo)]);
    }

    $paths = collect_permanent_delete_paths($pdo, $ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $pdo->beginTransaction();
    $stmt = $pdo->prepare("DELETE FROM cards WHERE id IN ($placeholders) AND deleted_at IS NOT NULL");
    $stmt->execute($ids);
    cleanup_orphan_tags($pdo);
    $pdo->commit();

    foreach ($paths as $path) {
        if (is_file($path)) {
            @unlink($path);
        }
    }

    json_response(['ok' => true, 'deleted' => count($ids), 'tags' => fetch_all_tags($pdo)]);
}

require_auth_json();

$pdo = db();
$action = get_action();

match ($action) {
    'list'              => action_list($pdo),
    'get'               => action_get($pdo),
    'create'            => action_create($pdo),
    'update'            => action_update($pdo),
    'add_images'        => action_add_images($pdo),
    'delete_image'      => action_delete_image($pdo),
    'create_note'       => action_create_note($pdo),
    'update_note'       => action_update_note($pdo),
    'note_upload_image' => action_note_upload_image($pdo),
    'delete'            => action_delete($pdo),
    'restore'           => action_restore($pdo),
    'empty_trash'       => action_empty_trash($pdo),
    default             => json_response(['ok' => false, 'error' => 'Unknown API action.'], 400),
};
