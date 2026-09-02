<?php
// Authenticated administration API for Nook public frontend.

declare(strict_types=1);

require_once __DIR__ . '/public_common.php';


function public_current_user_row(PDO $pdo): array
{
    $id = 0;
    foreach (['user_id', 'uid'] as $key) {
        if (!empty($_SESSION[$key])) {
            $id = (int)$_SESSION[$key];
            if ($id > 0) break;
        }
    }
    if ($id <= 0 && function_exists('current_user_id')) {
        try { $id = (int)current_user_id(); } catch (Throwable) { $id = 0; }
    }
    if ($id <= 0 && function_exists('current_user')) {
        try {
            $user = current_user();
            if (is_array($user)) $id = (int)($user['id'] ?? 0);
        } catch (Throwable) { $id = 0; }
    }
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT id,username,password_hash FROM users WHERE id=? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) return $row;
    }

    foreach (['username', 'user', 'login'] as $key) {
        $username = trim((string)($_SESSION[$key] ?? ''));
        if ($username === '') continue;
        $stmt = $pdo->prepare('SELECT id,username,password_hash FROM users WHERE username=? LIMIT 1');
        $stmt->execute([$username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) return $row;
    }

    // Nook is a single-admin application in the default configuration. This
    // fallback keeps the add-on compatible with old sessions that stored only
    // an authentication boolean rather than the user id.
    $rows = $pdo->query('SELECT id,username,password_hash FROM users ORDER BY id ASC LIMIT 2')->fetchAll(PDO::FETCH_ASSOC);
    if (count($rows) === 1) return $rows[0];
    throw new RuntimeException('Could not determine the current Nook user.');
}

function public_update_session_username(string $username): void
{
    foreach (['username', 'user', 'login'] as $key) {
        if (array_key_exists($key, $_SESSION)) $_SESSION[$key] = $username;
    }
}

try {
    require_user();
    $action = (string)($_GET['action'] ?? $_POST['action'] ?? '');
    $pdo = public_db();

    if ($action === 'publication') {
        $cardId = (int)($_GET['card_id'] ?? 0);
        public_json(['ok' => true, 'publication' => public_publication($cardId)]);
    }

    if ($action === 'publication_save') {
        $cardId = (int)($_POST['card_id'] ?? 0);
        if ($cardId <= 0) throw new RuntimeException('Entry ID is missing.');
        $current = public_publication($cardId);
        $published = !empty($_POST['is_published']) ? 1 : 0;
        $tag = $published ? public_tag_normalize((string)($_POST['public_tag'] ?? '')) : '';
        $isPage = $published && $current['entry_type'] === 'note' && !empty($_POST['is_page']) ? 1 : 0;
        $pinned = $published && !empty($_POST['is_public_pinned']) ? 1 : 0;

        $stmt = $pdo->prepare('SELECT is_published,is_public_pinned,public_pinned_at,public_page_order,published_at FROM cards WHERE id=? LIMIT 1');
        $stmt->execute([$cardId]);
        $old = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $publishedAt = $published ? ((int)($old['is_published'] ?? 0) ? ($old['published_at'] ?: date('Y-m-d H:i:s')) : date('Y-m-d H:i:s')) : null;
        $pinnedAt = $pinned ? ((int)($old['is_public_pinned'] ?? 0) && !empty($old['public_pinned_at']) ? (string)$old['public_pinned_at'] : date('Y-m-d H:i:s')) : null;
        $pageOrder = (int)($old['public_page_order'] ?? 0);
        if ($isPage && $pageOrder <= 0) {
            $pageOrder = (int)$pdo->query('SELECT COALESCE(MAX(public_page_order),0)+10 FROM cards WHERE publish_as_page=1')->fetchColumn();
        }
        if (!$isPage) $pageOrder = 0;

        $update = $pdo->prepare(
            'UPDATE cards SET is_published=?,public_tag=?,publish_as_page=?,is_public_pinned=?,public_pinned_at=?,public_page_order=?,published_at=? WHERE id=?'
        );
        $update->execute([$published,$tag,$isPage,$pinned,$pinnedAt,$pageOrder,$publishedAt,$cardId]);
        public_json(['ok' => true, 'publication' => public_publication($cardId)]);
    }

    if ($action === 'credentials_get') {
        $user = public_current_user_row($pdo);
        $hash = strtolower(trim((string)($user['password_hash'] ?? '')));
        public_json([
            'ok' => true,
            'credentials' => [
                'username' => (string)$user['username'],
                'password_format' => preg_match('/^[a-f0-9]{32}$/', $hash) ? 'md5' : 'legacy',
                'md5_auth_ready' => function_exists('nook_verify_user_password'),
            ],
        ]);
    }

    if ($action === 'credentials_save') {
        $user = public_current_user_row($pdo);
        $userId = (int)$user['id'];
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || strlen($username) > 100) {
            throw new RuntimeException('Login must contain 1 to 100 characters.');
        }
        if (preg_match('/[\\x00-\\x1F\\x7F]/', $username)) {
            throw new RuntimeException('Login contains invalid control characters.');
        }

        $check = $pdo->prepare('SELECT id FROM users WHERE username=? AND id<>? LIMIT 1');
        $check->execute([$username, $userId]);
        if ($check->fetchColumn()) throw new RuntimeException('This login is already in use.');

        if ($password !== '' && !function_exists('nook_verify_user_password')) {
            throw new RuntimeException('MD5 login support is not installed yet. Run: php tools/apply_public_frontend.php');
        }

        if ($password !== '') {
            // Intentionally requested by the Nook owner: store raw MD5 hex so
            // the hash can be replaced directly in MySQL if necessary.
            $stmt = $pdo->prepare('UPDATE users SET username=?, password_hash=? WHERE id=?');
            $stmt->execute([$username, md5($password), $userId]);
        } else {
            $stmt = $pdo->prepare('UPDATE users SET username=? WHERE id=?');
            $stmt->execute([$username, $userId]);
        }
        public_update_session_username($username);
        public_json(['ok' => true, 'username' => $username, 'password_changed' => $password !== '']);
    }

    if ($action === 'settings_get') {
        $slug = public_setting('public_slug', 'blog');
        $pages = public_sidebar()['pages'];
        public_json([
            'ok' => true,
            'settings' => [
                'public_slug' => $slug,
                'public_header_html' => public_setting('public_header_html', ''),
                'public_logo_path' => public_setting('public_logo_path', ''),
                'public_url' => rtrim(dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/' . rawurlencode($slug),
            ],
            'pages' => $pages,
        ]);
    }

    if ($action === 'settings_save') {
        $slug = public_slug_normalize((string)($_POST['public_slug'] ?? 'blog'));
        $header = (string)($_POST['public_header_html'] ?? '');
        if (strlen($header) > 200000) throw new RuntimeException('Header HTML is too large.');
        public_setting_set('public_slug', $slug);
        public_setting_set('public_header_html', $header);
        public_json(['ok' => true, 'public_slug' => $slug]);
    }

    if ($action === 'page_reorder') {
        $ids = json_decode((string)($_POST['ids'] ?? '[]'), true);
        if (!is_array($ids)) throw new RuntimeException('Invalid page order.');
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE cards SET public_page_order=? WHERE id=? AND publish_as_page=1');
        $order = 10;
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $stmt->execute([$order, $id]);
                $order += 10;
            }
        }
        $pdo->commit();
        public_json(['ok' => true]);
    }

    if ($action === 'logo_upload') {
        if (empty($_FILES['logo']) || (int)($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Logo image is missing.');
        }
        $tmp = (string)$_FILES['logo']['tmp_name'];
        if (!is_uploaded_file($tmp)) throw new RuntimeException('Invalid logo upload.');
        $raw = file_get_contents($tmp);
        if ($raw === false) throw new RuntimeException('Could not read logo image.');
        $src = @imagecreatefromstring($raw);
        if (!$src) throw new RuntimeException('GD could not decode the logo image.');
        $w = imagesx($src); $h = imagesy($src); $side = min($w,$h);
        $sx = (int)floor(($w-$side)/2); $sy = (int)floor(($h-$side)/2);
        $dst = imagecreatetruecolor(256,256);
        imagealphablending($dst,false); imagesavealpha($dst,true);
        $transparent = imagecolorallocatealpha($dst,0,0,0,127);
        imagefill($dst,0,0,$transparent);
        imagecopyresampled($dst,$src,0,0,$sx,$sy,256,256,$side,$side);
        $dir = public_storage_root() . '/public';
        if (!is_dir($dir) && !mkdir($dir,0775,true) && !is_dir($dir)) throw new RuntimeException('Could not create public storage folder.');
        $path = $dir . '/logo.png';
        if (!imagepng($dst,$path,6)) throw new RuntimeException('Could not save cropped logo.');
        imagedestroy($src); imagedestroy($dst);
        public_setting_set('public_logo_path', 'public/logo.png');
        public_json(['ok' => true, 'logo_url' => 'public_logo.php?v=' . time()]);
    }

    if ($action === 'logo_remove') {
        $relative = public_setting('public_logo_path', '');
        if ($relative !== '') {
            $path = public_storage_path($relative);
            if (is_file($path)) @unlink($path);
        }
        public_setting_set('public_logo_path', '');
        public_json(['ok' => true]);
    }

    throw new RuntimeException('Unknown public frontend action.');
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    public_json(['ok' => false, 'error' => $e->getMessage()], 400);
}
