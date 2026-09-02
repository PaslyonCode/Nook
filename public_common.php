<?php
// Shared helpers for the Nook public frontend add-on.

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function public_db(): PDO
{
    return db();
}

function public_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function public_setting(string $key, string $default = ''): string
{
    $stmt = public_db()->prepare('SELECT setting_value FROM app_settings WHERE setting_key=? LIMIT 1');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return $value === false || $value === null ? $default : (string)$value;
}

function public_setting_set(string $key, string $value): void
{
    $stmt = public_db()->prepare(
        'INSERT INTO app_settings(setting_key,setting_value) VALUES(?,?) '
        . 'ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)'
    );
    $stmt->execute([$key, $value]);
}

function public_slug_normalize(string $slug): string
{
    $slug = trim($slug);
    $slug = trim($slug, '/');
    if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]{0,63}$/', $slug)) {
        throw new InvalidArgumentException('Public path must contain only letters, numbers, underscores, and hyphens.');
    }
    return strtolower($slug);
}

function public_tag_normalize(string $tag): string
{
    $tag = trim($tag);
    $tag = preg_replace('/^[#＃]+/u', '', $tag) ?? '';
    $tag = preg_replace('/[^\p{L}\p{N}_-]+/u', '', $tag) ?? '';
    if (function_exists('mb_strtolower')) {
        $tag = mb_strtolower($tag, 'UTF-8');
        $tag = mb_substr($tag, 0, 80, 'UTF-8');
    } else {
        $tag = strtolower(substr($tag, 0, 80));
    }
    return $tag;
}

function public_publication(int $cardId): array
{
    $stmt = public_db()->prepare(
        'SELECT id,entry_type,title,deleted_at,is_published,public_tag,publish_as_page,is_public_pinned,public_page_order '
        . 'FROM cards WHERE id=? LIMIT 1'
    );
    $stmt->execute([$cardId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) throw new RuntimeException('Entry was not found.');
    return [
        'card_id' => $cardId,
        'entry_type' => (string)($row['entry_type'] ?? 'media'),
        'title' => (string)($row['title'] ?? ''),
        'is_published' => (int)($row['is_published'] ?? 0),
        'public_tag' => (string)($row['public_tag'] ?? ''),
        'is_page' => (int)($row['publish_as_page'] ?? 0),
        'is_public_pinned' => (int)($row['is_public_pinned'] ?? 0),
        'page_order' => (int)($row['public_page_order'] ?? 0),
    ];
}

function public_media_rows(int $cardId, ?string $role = null): array
{
    $sql = 'SELECT id,card_id,role,original_filename,media_type,mime,size_bytes,sort_order,preview_path FROM media_files WHERE card_id=?';
    $params = [$cardId];
    if ($role !== null) {
        $sql .= ' AND role=?';
        $params[] = $role;
    }
    $sql .= ' ORDER BY role ASC, sort_order ASC, id ASC';
    $stmt = public_db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) {
        $id = (int)$row['id'];
        $row['id'] = $id;
        $row['card_id'] = (int)$row['card_id'];
        $row['size_bytes'] = (int)$row['size_bytes'];
        $row['url'] = 'public_file.php?id=' . $id;
        $row['preview_url'] = trim((string)$row['preview_path']) !== ''
            ? 'public_file.php?id=' . $id . '&preview=1'
            : '';
        $row['download_url'] = 'public_file.php?id=' . $id . '&download=1';
        unset($row['preview_path']);
    }
    unset($row);
    return $rows;
}

function public_card(int $cardId): array
{
    $stmt = public_db()->prepare(
        "SELECT c.id,c.entry_type,c.title,c.description,c.body_html,c.created_at,c.updated_at,
                c.public_tag,c.publish_as_page AS is_page,c.is_public_pinned,c.public_pinned_at,c.public_page_order AS page_order
         FROM cards c
         WHERE c.id=? AND c.is_published=1 AND c.deleted_at IS NULL AND COALESCE(c.is_draft,0)=0 LIMIT 1"
    );
    $stmt->execute([$cardId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new RuntimeException('Published entry was not found.');
    }
    $row['id'] = (int)$row['id'];
    $row['is_page'] = (int)$row['is_page'];
    $row['is_public_pinned'] = (int)$row['is_public_pinned'];
    // Inline Editor.js images are stored with authenticated file.php URLs in the admin UI.
    // Rewrite them only inside an already-published card response.
    $row['body_html'] = preg_replace_callback(
        '~(?<![A-Za-z0-9_])(?:\./)?file\.php\?id=(\d+)(?:&amp;|&)preview=1~i',
        static fn(array $m): string => 'public_file.php?id=' . (int)$m[1] . '&preview=1',
        (string)($row['body_html'] ?? '')
    );
    $row['body_html'] = preg_replace_callback(
        '~(?<![A-Za-z0-9_])(?:\./)?file\.php\?id=(\d+)~i',
        static fn(array $m): string => 'public_file.php?id=' . (int)$m[1],
        (string)($row['body_html'] ?? '')
    );
    $row['files'] = public_media_rows($cardId);
    return $row;
}

function public_feed(?string $tag = null): array
{
    $params = [];
    $whereTag = '';
    if ($tag !== null && $tag !== '') {
        $whereTag = ' AND c.public_tag=?';
        $params[] = public_tag_normalize($tag);
    }
    $stmt = public_db()->prepare(
        "SELECT c.id,c.entry_type,c.title,c.description,c.body_html,c.created_at,c.updated_at,
                c.public_tag,c.is_public_pinned,c.public_pinned_at
         FROM cards c
         WHERE c.is_published=1 AND c.publish_as_page=0
           AND c.deleted_at IS NULL AND COALESCE(c.is_draft,0)=0{$whereTag}
         ORDER BY c.is_public_pinned DESC, c.public_pinned_at DESC, c.created_at DESC, c.id DESC"
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) {
        $row['id'] = (int)$row['id'];
        $row['is_public_pinned'] = (int)$row['is_public_pinned'];
        $files = public_media_rows((int)$row['id'], 'content');
        $row['files'] = array_slice($files, 0, 4);
        if (($row['entry_type'] ?? '') === 'note') {
            $plain = trim(preg_replace('/\s+/u', ' ', strip_tags((string)($row['body_html'] ?? ''))) ?? '');
            if (function_exists('mb_substr')) $plain = mb_substr($plain, 0, 260, 'UTF-8');
            else $plain = substr($plain, 0, 260);
            $row['excerpt'] = $plain;
        }
        unset($row['body_html']);
    }
    unset($row);
    return $rows;
}

function public_sidebar(): array
{
    $pages = public_db()->query(
        "SELECT id,title,public_page_order AS page_order
         FROM cards
         WHERE is_published=1 AND publish_as_page=1 AND entry_type='note'
           AND deleted_at IS NULL AND COALESCE(is_draft,0)=0
         ORDER BY public_page_order ASC,created_at ASC,id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
    foreach ($pages as &$p) { $p['id']=(int)$p['id']; $p['page_order']=(int)$p['page_order']; }
    unset($p);

    $tags = public_db()->query(
        "SELECT public_tag,COUNT(*) AS cnt
         FROM cards
         WHERE is_published=1 AND publish_as_page=0 AND public_tag<>''
           AND deleted_at IS NULL AND COALESCE(is_draft,0)=0
         GROUP BY public_tag ORDER BY public_tag ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
    foreach ($tags as &$t) $t['cnt']=(int)$t['cnt'];
    unset($t);
    return ['pages'=>$pages,'tags'=>$tags];
}

function public_storage_root(): string
{
    if (function_exists('storage_root')) {
        return storage_root(true);
    }
    $stmt = public_db()->prepare("SELECT setting_value FROM app_settings WHERE setting_key='storage_root' LIMIT 1");
    $stmt->execute();
    $root = trim((string)$stmt->fetchColumn());
    if ($root === '') throw new RuntimeException('Storage root is not configured.');
    return rtrim(str_replace('\\', '/', $root), '/');
}

function public_storage_path(string $relative): string
{
    $relative = ltrim(str_replace('\\', '/', $relative), '/');
    if ($relative === '' || str_contains($relative, '../')) throw new RuntimeException('Invalid storage path.');
    return public_storage_root() . '/' . $relative;
}
