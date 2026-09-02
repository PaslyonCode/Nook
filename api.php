<?php
// Nook JSON API.

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/media.php';
require_once __DIR__ . '/lib/package.php';

ini_set('display_errors', '0');
error_reporting(E_ALL);

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

set_exception_handler(function (Throwable $e): void {
    if ($e instanceof SpaceLockedException) {
        json_response(['ok' => false, 'space_locked' => true, 'space_id' => $e->spaceId, 'error' => $e->getMessage()], 403);
    }
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
});

function action_name(): string
{
    return (string)($_GET['action'] ?? $_POST['action'] ?? '');
}

function text_value(mixed $value, int $max = 50000): string
{
    $value = trim((string)$value);
    return function_exists('mb_substr') ? mb_substr($value, 0, $max, 'UTF-8') : substr($value, 0, $max);
}

function normalize_tag(string $value): string
{
    $value = ltrim(trim($value), "#＃ ");
    $value = preg_replace('/[^\p{L}\p{N}_\-]+/u', '', $value) ?? '';
    $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    return function_exists('mb_substr') ? mb_substr($value, 0, 80, 'UTF-8') : substr($value, 0, 80);
}

function parse_tags(string $input): array
{
    $out = [];
    foreach (preg_split('/[\s,;]+/u', trim($input)) ?: [] as $part) {
        $tag = normalize_tag($part);
        if ($tag !== '') {
            $out[$tag] = true;
        }
    }
    return array_slice(array_keys($out), 0, 50);
}

function sanitize_note_html(string $html): string
{
    $allowed = '<p><br><h1><h2><h3><h4><ul><ol><li><strong><b><em><i><u><s><blockquote><pre><code><a><img><hr><div><span><table><thead><tbody><tr><th><td>';
    $html = strip_tags($html, $allowed);
    if (!class_exists('DOMDocument')) {
        $html = preg_replace('/\son\w+\s*=\s*(["\']).*?\1/isu', '', $html) ?? $html;
        $html = preg_replace('/javascript\s*:/iu', '', $html) ?? $html;
        return $html;
    }
    $doc = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="nook-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $root = $doc->getElementById('nook-root');
    if (!$root) return '';
    $nodes = [];
    foreach ($root->getElementsByTagName('*') as $node) $nodes[] = $node;
    foreach ($nodes as $node) {
        $remove = [];
        foreach ($node->attributes ?? [] as $attr) {
            $name = strtolower($attr->name);
            $value = trim($attr->value);
            $keep = false;
            if ($node->nodeName === 'a' && $name === 'href' && preg_match('~^(https?://|mailto:|file\.php\?)~i', $value)) $keep = true;
            if ($node->nodeName === 'img' && $name === 'src' && preg_match('~^(file\.php\?|data:image/)~i', $value)) $keep = true;
            if ($node->nodeName === 'img' && $name === 'alt') $keep = true;
            if ($node->nodeName === 'img' && $name === 'data-media-id') $keep = true;
            if ($node->nodeName === 'img' && $name === 'style' && preg_match('/^width\s*:\s*(25|50|75|100)%\s*;?$/i', $value)) $keep = true;
            if (!$keep) $remove[] = $attr->name;
        }
        foreach ($remove as $name) $node->removeAttribute($name);
    }
    $out = '';
    foreach ($root->childNodes as $child) $out .= $doc->saveHTML($child);
    return $out;
}

function sync_card_tags(PDO $pdo, int $cardId, array $tags): void
{
    $pdo->prepare('DELETE FROM card_tags WHERE card_id = ?')->execute([$cardId]);
    $insertTag = $pdo->prepare('INSERT IGNORE INTO tags (name) VALUES (?)');
    $getTag = $pdo->prepare('SELECT id FROM tags WHERE name = ?');
    $link = $pdo->prepare('INSERT IGNORE INTO card_tags (card_id, tag_id) VALUES (?, ?)');
    foreach ($tags as $tag) {
        $insertTag->execute([$tag]);
        $getTag->execute([$tag]);
        $tagId = (int)$getTag->fetchColumn();
        if ($tagId > 0) {
            $link->execute([$cardId, $tagId]);
        }
    }
    $pdo->exec('DELETE t FROM tags t LEFT JOIN card_tags ct ON ct.tag_id=t.id WHERE ct.tag_id IS NULL');
}

function tags_for_space(int $spaceId): array
{
    $stmt = db()->prepare(
        'SELECT t.name, COUNT(DISTINCT c.id) cards_count
         FROM tags t JOIN card_tags ct ON ct.tag_id=t.id JOIN cards c ON c.id=ct.card_id
         WHERE c.space_id=? AND c.deleted_at IS NULL AND c.is_draft=0
         GROUP BY t.id,t.name ORDER BY t.name'
    );
    $stmt->execute([$spaceId]);
    return array_map(fn($r) => ['name' => $r['name'], 'cards_count' => (int)$r['cards_count']], $stmt->fetchAll());
}

function card_tags(int $cardId): array
{
    $stmt = db()->prepare('SELECT t.name FROM tags t JOIN card_tags ct ON ct.tag_id=t.id WHERE ct.card_id=? ORDER BY t.name');
    $stmt->execute([$cardId]);
    return array_column($stmt->fetchAll(), 'name');
}

function media_row_payload(array $row): array
{
    return [
        'id' => (int)$row['id'],
        'role' => $row['role'],
        'name' => $row['original_filename'],
        'media_type' => $row['media_type'],
        'mime' => $row['mime'],
        'size_bytes' => (int)$row['size_bytes'],
        'width' => $row['width'] !== null ? (int)$row['width'] : null,
        'height' => $row['height'] !== null ? (int)$row['height'] : null,
        'url' => 'file.php?id=' . (int)$row['id'],
        'preview_url' => 'file.php?id=' . (int)$row['id'] . '&preview=1',
        'has_real_preview' => $row['preview_path'] !== '' && strtolower(pathinfo($row['preview_path'], PATHINFO_EXTENSION)) !== 'svg',
    ];
}

function fetch_media_for_cards(array $cardIds, bool $full = false): array
{
    if (!$cardIds) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($cardIds), '?'));
    $limitSql = $full ? '' : " AND mf.role='content' AND mf.sort_order < 4";
    $stmt = db()->prepare(
        "SELECT mf.* FROM media_files mf WHERE mf.card_id IN ($placeholders) $limitSql ORDER BY mf.card_id,mf.role,mf.sort_order,mf.id"
    );
    $stmt->execute($cardIds);
    $out = [];
    foreach ($stmt->fetchAll() as $row) {
        $out[(int)$row['card_id']][] = media_row_payload($row);
    }
    return $out;
}

function fetch_cards_by_ids(array $ids, bool $full = false): array
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT c.*, s.name space_name FROM cards c JOIN spaces s ON s.id=c.space_id WHERE c.id IN ($placeholders)");
    $stmt->execute($ids);
    $rows = [];
    foreach ($stmt->fetchAll() as $row) {
        $rows[(int)$row['id']] = $row;
    }
    $media = fetch_media_for_cards($ids, $full);
    $mediaCounts = [];
    $countStmt = db()->prepare("SELECT card_id,COUNT(*) cnt FROM media_files WHERE card_id IN ($placeholders) AND role='content' GROUP BY card_id");
    $countStmt->execute($ids);
    foreach ($countStmt->fetchAll() as $countRow) $mediaCounts[(int)$countRow['card_id']] = (int)$countRow['cnt'];
    $ordered = [];
    foreach ($ids as $id) {
        if (!isset($rows[$id])) continue;
        $r = $rows[$id];
        $payload = [
            'id' => (int)$r['id'],
            'space_id' => (int)$r['space_id'],
            'space_name' => $r['space_name'],
            'entry_type' => $r['entry_type'],
            'title' => $r['title'],
            'description' => $r['description'] ?? '',
            'is_hidden' => (bool)$r['is_hidden'],
            'is_draft' => (bool)$r['is_draft'],
            'is_pinned' => (bool)($r['is_pinned'] ?? false),
            'pinned_at' => $r['pinned_at'] ?? null,
            'deleted_at' => $r['deleted_at'],
            'created_at' => $r['created_at'],
            'updated_at' => $r['updated_at'],
            'tags' => card_tags($id),
            'media' => $media[$id] ?? [],
            'media_count' => $mediaCounts[$id] ?? 0,
        ];
        if ($r['entry_type'] === 'note') {
            $noteText = safe_html_text((string)($r['body_html'] ?? ''));
            $payload['snippet'] = function_exists('mb_substr') ? mb_substr($noteText, 0, 320, 'UTF-8') : substr($noteText, 0, 320);
            if ($full) {
                $payload['body_json'] = json_decode((string)($r['body_json'] ?? ''), true) ?: ['blocks' => []];
                $payload['body_html'] = (string)($r['body_html'] ?? '');
            }
        }
        $ordered[] = $payload;
    }
    return $ordered;
}

function fetch_card_accessible(int $cardId): array
{
    $stmt = db()->prepare('SELECT * FROM cards WHERE id=? AND is_draft=0');
    $stmt->execute([$cardId]);
    $row = $stmt->fetch();
    if (!$row) {
        throw new RuntimeException('Entry was not found.');
    }
    require_space_access((int)$row['space_id']);
    return $row;
}

function space_list_payload(): array
{
    $user = require_user();
    $rows = db()->query('SELECT id,name,password_hash,created_at FROM spaces ORDER BY name')->fetchAll();
    return array_map(function ($row) use ($user) {
        $id = (int)$row['id'];
        $protected = !empty($row['password_hash']);
        return [
            'id' => $id,
            'name' => $row['name'],
            'protected' => $protected,
            'unlocked' => !$protected || has_space_access($id),
            'current' => $id === current_space_id(),
        ];
    }, $rows);
}

function repair_instance_text_encoding_once(): void
{
    if (setting_get('text_encoding_repair_v3', '') === '1') {
        return;
    }

    $targets = [
        'spaces' => ['name'],
        'cards' => ['title', 'description', 'body_json', 'body_html'],
        'tags' => ['name'],
        'media_files' => ['original_filename'],
    ];
    $pdo = db();
    foreach ($targets as $table => $columns) {
        $selectColumns = implode(',', array_map(static fn(string $column): string => '`' . $column . '`', $columns));
        $rows = $pdo->query('SELECT id,' . $selectColumns . ' FROM `' . $table . '`')->fetchAll();
        foreach ($rows as $row) {
            $sets = [];
            $values = [];
            foreach ($columns as $column) {
                if ($row[$column] === null) {
                    continue;
                }
                $original = (string)$row[$column];
                $repaired = repair_mojibake_string($original);
                if ($repaired !== $original) {
                    $sets[] = '`' . $column . '`=?';
                    $values[] = $repaired;
                }
            }
            if ($sets) {
                $values[] = (int)$row['id'];
                $pdo->prepare('UPDATE `' . $table . '` SET ' . implode(',', $sets) . ' WHERE id=?')->execute($values);
            }
        }
    }
    setting_set('text_encoding_repair_v3', '1');
}

function action_state(): never
{
    $user = require_user();
    repair_instance_text_encoding_once();
    $root = storage_root(false);
    $spaceId = current_space_id();
    $current = fetch_space($spaceId);
    $trashStmt = db()->prepare('SELECT COUNT(*) FROM cards WHERE space_id=? AND deleted_at IS NOT NULL AND is_draft=0');
    $trashStmt->execute([$spaceId]);
    json_response([
        'ok' => true,
        'user' => $user,
        'storage_configured' => $root !== null,
        'storage_root' => $root,
        'spaces' => space_list_payload(),
        'current_space' => $current,
        'current_space_unlocked' => has_space_access($spaceId),
        'tags' => has_space_access($spaceId) ? tags_for_space($spaceId) : [],
        'trash_count' => has_space_access($spaceId) ? (int)$trashStmt->fetchColumn() : 0,
    ]);
}

function action_settings_save(): never
{
    require_user();
    $root = normalize_storage_root((string)($_POST['storage_root'] ?? ''));
    ensure_storage_structure($root);
    setting_set('storage_root', $root);
    json_response(['ok' => true, 'storage_root' => $root]);
}

function action_space_create(): never
{
    require_user();
    $name = text_value($_POST['name'] ?? '', 160);
    if ($name === '') throw new RuntimeException('Space name is required.');
    $password = (string)($_POST['password'] ?? '');
    $hash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null;
    db()->prepare('INSERT INTO spaces (name,password_hash) VALUES (?,?)')->execute([$name,$hash]);
    $id = (int)db()->lastInsertId();
    if ($hash !== null) unlock_space($id, $password, false);
    json_response(['ok' => true, 'space' => fetch_space($id), 'spaces' => space_list_payload()]);
}

function action_space_unlock(): never
{
    require_user();
    $id = (int)($_POST['space_id'] ?? 0);
    if (!unlock_space($id, (string)($_POST['password'] ?? ''), bool_input($_POST['remember'] ?? false))) {
        json_response(['ok' => false, 'error' => 'Wrong space password.'], 403);
    }
    json_response(['ok' => true, 'space_id' => $id, 'spaces' => space_list_payload()]);
}

function action_space_switch(): never
{
    require_user();
    $id = (int)($_POST['space_id'] ?? 0);
    if (!fetch_space($id)) throw new RuntimeException('Space was not found.');
    require_space_access($id);
    start_app_session();
    $_SESSION['current_space_id'] = $id;
    json_response(['ok' => true, 'space' => fetch_space($id)]);
}

function action_space_update(): never
{
    require_user();
    $id = (int)($_POST['space_id'] ?? 0);
    require_space_access($id);
    $name = text_value($_POST['name'] ?? '', 160);
    if ($name === '') throw new RuntimeException('Space name is required.');
    $passwordMode = (string)($_POST['password_mode'] ?? 'keep');
    if ($passwordMode === 'remove') {
        db()->prepare('UPDATE spaces SET name=?,password_hash=NULL WHERE id=?')->execute([$name,$id]);
        db()->prepare('DELETE FROM space_access_tokens WHERE space_id=?')->execute([$id]);
    } elseif ($passwordMode === 'set') {
        $password = (string)($_POST['password'] ?? '');
        if ($password === '') throw new RuntimeException('New password is empty.');
        db()->prepare('UPDATE spaces SET name=?,password_hash=? WHERE id=?')->execute([$name,password_hash($password,PASSWORD_DEFAULT),$id]);
        db()->prepare('DELETE FROM space_access_tokens WHERE space_id=?')->execute([$id]);
        start_app_session();
        unset($_SESSION['space_unlocks'][$id]);
        unlock_space($id,$password,false);
    } else {
        db()->prepare('UPDATE spaces SET name=? WHERE id=?')->execute([$name,$id]);
    }
    json_response(['ok' => true, 'spaces' => space_list_payload()]);
}

function action_space_delete(): never
{
    require_user();
    $id = (int)($_POST['space_id'] ?? 0);
    require_space_access($id);
    $fallback = (int)db()->query('SELECT id FROM spaces WHERE id<>' . $id . ' ORDER BY id LIMIT 1')->fetchColumn();
    if ($fallback <= 0) throw new RuntimeException('The last space cannot be deleted.');
    require_space_access($fallback);
    $pdo = db();
    $pdo->beginTransaction();
    $pdo->prepare('UPDATE cards SET space_id=? WHERE space_id=?')->execute([$fallback,$id]);
    $pdo->prepare('DELETE FROM spaces WHERE id=?')->execute([$id]);
    $pdo->commit();
    start_app_session();
    if (current_space_id() === $id) $_SESSION['current_space_id'] = $fallback;
    json_response(['ok' => true, 'spaces' => space_list_payload(), 'current_space_id' => $fallback]);
}

function action_list(): never
{
    require_user();
    $spaceId = current_space_id();
    require_space_access($spaceId);
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = max(5, min(100, (int)($_GET['per_page'] ?? GALLERY_PAGE_SIZE)));
    $offset = ($page - 1) * $perPage;
    $q = text_value($_GET['q'] ?? '', 255);
    $dateFrom = text_value($_GET['date_from'] ?? '', 20);
    $dateTo = text_value($_GET['date_to'] ?? '', 20);
    $tag = normalize_tag((string)($_GET['tag'] ?? ''));
    $type = (string)($_GET['type'] ?? 'all');
    $trash = bool_input($_GET['trash'] ?? false);
    $where = ['c.space_id=?', 'c.is_draft=0', $trash ? 'c.deleted_at IS NOT NULL' : 'c.deleted_at IS NULL'];
    $params = [$spaceId];
    $filtered = $q !== '' || $dateFrom !== '' || $dateTo !== '' || $tag !== '' || $type !== 'all';
    if (!$trash && !$filtered) $where[] = 'c.is_hidden=0';
    if ($q !== '') {
        $like = '%' . $q . '%';
        $where[] = '(c.title LIKE ? OR c.description LIKE ? OR c.body_html LIKE ? OR EXISTS(SELECT 1 FROM media_files mf WHERE mf.card_id=c.id AND mf.original_filename LIKE ?) OR EXISTS(SELECT 1 FROM card_tags ct JOIN tags t ON t.id=ct.tag_id WHERE ct.card_id=c.id AND t.name LIKE ?))';
        array_push($params,$like,$like,$like,$like,$like);
    }
    if ($dateFrom !== '') { $where[]='DATE(c.created_at)>=?'; $params[]=$dateFrom; }
    if ($dateTo !== '') { $where[]='DATE(c.created_at)<=?'; $params[]=$dateTo; }
    if ($tag !== '') { $where[]='EXISTS(SELECT 1 FROM card_tags ct JOIN tags t ON t.id=ct.tag_id WHERE ct.card_id=c.id AND t.name=?)'; $params[]=$tag; }
    if ($type === 'note') $where[]="c.entry_type='note'";
    elseif (in_array($type,['image','video','pdf','stl'],true)) { $where[]="EXISTS(SELECT 1 FROM media_files mf WHERE mf.card_id=c.id AND mf.media_type=?)"; $params[]=$type; }
    $whereSql = implode(' AND ', $where);
    $count = db()->prepare('SELECT COUNT(*) FROM cards c WHERE ' . $whereSql);
    $count->execute($params);
    $total = (int)$count->fetchColumn();
    $sql = 'SELECT c.id FROM cards c WHERE ' . $whereSql . ' ORDER BY c.is_pinned DESC, c.pinned_at DESC, c.created_at DESC, c.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    json_response([
        'ok' => true,
        'cards' => fetch_cards_by_ids($ids,false),
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'has_more' => $offset + count($ids) < $total,
        'tags' => tags_for_space($spaceId),
    ]);
}

function action_get(): never
{
    require_user();
    $id = (int)($_GET['id'] ?? 0);
    fetch_card_accessible($id);
    $cards = fetch_cards_by_ids([$id],true);
    json_response(['ok'=>true,'card'=>$cards[0] ?? null]);
}

function card_insert_media_files(int $cardId, int $spaceId, array $files, string $role, bool $allowAny, ?array $previews = null, ?int &$previewIndex = null): void
{
    $previews ??= normalize_uploads($_FILES['video_previews'] ?? null);
    if ($previewIndex === null) $previewIndex = 0;
    $sortStmt = db()->prepare('SELECT COALESCE(MAX(sort_order),-1)+1 FROM media_files WHERE card_id=? AND role=?');
    $sortStmt->execute([$cardId,$role]);
    $sort = (int)$sortStmt->fetchColumn();
    foreach ($files as $file) {
        $meta = inspect_upload($file,$allowAny);
        $preview = null;
        if ($meta['media_type']==='video') $preview = $previews[$previewIndex++] ?? null;
        persist_uploaded_file(db(),$cardId,$spaceId,$file,$role,$sort++,$allowAny,$preview);
    }
}

function action_media_create(bool $separate): never
{
    require_user();
    $spaceId=current_space_id(); require_space_access($spaceId); ensure_storage_structure(storage_root(true));
    $files=normalize_uploads($_FILES['files'] ?? $_FILES['images'] ?? null);
    if (!$files) throw new RuntimeException('Select at least one file.');
    foreach($files as $file) inspect_upload($file,false);
    $previews = normalize_uploads($_FILES['video_previews'] ?? null);
    $previewIndex = 0;
    $pdo=db(); $pdo->beginTransaction();
    try {
        $created=[];
        if ($separate) {
            $tags=array_slice(parse_tags((string)($_POST['hashtag'] ?? '')),0,1);
            $hidden=bool_input($_POST['is_hidden'] ?? false);
            foreach($files as $file){
                $title=clean_filename((string)$file['name']);
                $pdo->prepare("INSERT INTO cards(space_id,entry_type,title,is_hidden) VALUES(?,'media',?,?)")->execute([$spaceId,$title,$hidden?1:0]);
                $cardId=(int)$pdo->lastInsertId();
                card_insert_media_files($cardId,$spaceId,[$file],'content',false,$previews,$previewIndex);
                sync_card_tags($pdo,$cardId,$tags); $created[]=$cardId;
            }
        } else {
            $title=text_value($_POST['title'] ?? '',255);
            if ($title === '') $title = clean_filename((string)($files[0]['name'] ?? ''));
            $desc=text_value($_POST['description'] ?? '',50000);
            $hidden=bool_input($_POST['is_hidden'] ?? false);
            $pdo->prepare("INSERT INTO cards(space_id,entry_type,title,description,is_hidden) VALUES(?,'media',?,?,?)")->execute([$spaceId,$title,$desc,$hidden?1:0]);
            $cardId=(int)$pdo->lastInsertId();
            card_insert_media_files($cardId,$spaceId,$files,'content',false,$previews,$previewIndex);
            sync_card_tags($pdo,$cardId,parse_tags((string)($_POST['hashtags'] ?? ''))); $created[]=$cardId;
        }
        $pdo->commit();
        json_response(['ok'=>true,'created'=>count($created),'cards'=>fetch_cards_by_ids($created,true)]);
    } catch(Throwable $e){ if($pdo->inTransaction())$pdo->rollBack(); throw $e; }
}

function action_media_update(): never
{
    require_user(); $id=(int)($_POST['id']??0); $row=fetch_card_accessible($id);
    db()->prepare('UPDATE cards SET title=?,description=?,is_hidden=?,updated_at=NOW() WHERE id=?')->execute([
        text_value($_POST['title']??'',255),text_value($_POST['description']??'',50000),bool_input($_POST['is_hidden']??false)?1:0,$id
    ]);
    sync_card_tags(db(),$id,parse_tags((string)($_POST['hashtags']??'')));
    json_response(['ok'=>true,'card'=>fetch_cards_by_ids([$id],true)[0]]);
}

function action_media_add(): never
{
    require_user(); $id=(int)($_POST['id']??0); $row=fetch_card_accessible($id);
    $files=normalize_uploads($_FILES['files']??$_FILES['images']??null); if(!$files)throw new RuntimeException('Select files.');
    card_insert_media_files($id,(int)$row['space_id'],$files,'content',false);
    json_response(['ok'=>true,'card'=>fetch_cards_by_ids([$id],true)[0]]);
}

function action_note_draft(): never
{
    require_user(); $spaceId=current_space_id(); require_space_access($spaceId);
    db()->prepare("INSERT INTO cards(space_id,entry_type,is_draft,title) VALUES(?,'note',1,'')")->execute([$spaceId]);
    $id=(int)db()->lastInsertId();
    json_response(['ok'=>true,'card'=>fetch_cards_by_ids([$id],true)[0]??['id'=>$id,'space_id'=>$spaceId,'entry_type'=>'note','body_json'=>['blocks'=>[]],'media'=>[]]]);
}

function action_note_save(): never
{
    require_user(); $id=(int)($_POST['id']??0);
    $stmt=db()->prepare('SELECT * FROM cards WHERE id=?');$stmt->execute([$id]);$row=$stmt->fetch();
    if(!$row||$row['entry_type']!=='note')throw new RuntimeException('Note was not found.'); require_space_access((int)$row['space_id']);
    $bodyJson=(string)($_POST['body_json']??'{"blocks":[]}');
    json_decode($bodyJson,true); if(json_last_error()!==JSON_ERROR_NONE)throw new RuntimeException('Invalid editor data.');
    $bodyHtml=sanitize_note_html((string)($_POST['body_html']??''));
    db()->prepare('UPDATE cards SET title=?,body_json=?,body_html=?,is_hidden=?,is_draft=0,updated_at=NOW() WHERE id=?')->execute([
        text_value($_POST['title']??'',255),$bodyJson,$bodyHtml,bool_input($_POST['is_hidden']??false)?1:0,$id
    ]);
    sync_card_tags(db(),$id,parse_tags((string)($_POST['hashtags']??'')));
    json_response(['ok'=>true,'card'=>fetch_cards_by_ids([$id],true)[0]]);
}

function action_note_upload(string $role): never
{
    require_user(); $id=(int)($_POST['id']??0);
    $stmt=db()->prepare('SELECT * FROM cards WHERE id=?');$stmt->execute([$id]);$row=$stmt->fetch();
    if(!$row||$row['entry_type']!=='note')throw new RuntimeException('Note was not found.'); require_space_access((int)$row['space_id']);
    $files=normalize_uploads($_FILES['files']??$_FILES['file']??null); if(!$files)throw new RuntimeException('Select a file.');
    card_insert_media_files($id,(int)$row['space_id'],$files,$role,true);
    $card=fetch_cards_by_ids([$id],true)[0];
    json_response(['ok'=>true,'card'=>$card,'uploaded'=>array_slice($card['media'],-count($files))]);
}

function action_discard_draft(): never
{
    require_user(); $id=(int)($_POST['id']??0);
    $stmt=db()->prepare('SELECT space_id,is_draft FROM cards WHERE id=?');$stmt->execute([$id]);$row=$stmt->fetch();
    if(!$row){ json_response(['ok'=>true]); } require_space_access((int)$row['space_id']);
    if((int)$row['is_draft']===1) permanent_delete_cards([$id]);
    json_response(['ok'=>true]);
}

function permanent_delete_cards(array $ids): void
{
    if(!$ids)return;
    $ph=implode(',',array_fill(0,count($ids),'?'));
    $stmt=db()->prepare("SELECT stored_path,preview_path FROM media_files WHERE card_id IN ($ph)");$stmt->execute($ids);
    foreach($stmt->fetchAll() as $f){ foreach(['stored_path','preview_path'] as $k){$rel=(string)$f[$k];if($rel!==''&&is_file(storage_path($rel)))@unlink(storage_path($rel));}}
    db()->prepare("DELETE FROM cards WHERE id IN ($ph)")->execute($ids);
}

function action_media_delete(): never
{
    require_user(); $id=(int)($_POST['media_id']??0);
    $stmt=db()->prepare('SELECT mf.*,c.space_id FROM media_files mf JOIN cards c ON c.id=mf.card_id WHERE mf.id=?');$stmt->execute([$id]);$row=$stmt->fetch();
    if(!$row)throw new RuntimeException('File was not found.'); require_space_access((int)$row['space_id']);
    if($row['role']==='content'){
        $count=db()->prepare("SELECT COUNT(*) FROM media_files WHERE card_id=? AND role='content'");$count->execute([(int)$row['card_id']]);
        if((int)$count->fetchColumn()<=1)throw new RuntimeException('The last content file cannot be deleted separately.');
    }
    db()->prepare('DELETE FROM media_files WHERE id=?')->execute([$id]);
    foreach(['stored_path','preview_path'] as $k){$rel=(string)$row[$k];if($rel!==''&&is_file(storage_path($rel)))@unlink(storage_path($rel));}
    json_response(['ok'=>true,'card'=>fetch_cards_by_ids([(int)$row['card_id']],true)[0]]);
}

function action_soft_delete(): never
{
    require_user(); $id=(int)($_POST['id']??0);fetch_card_accessible($id);
    db()->prepare('UPDATE cards SET deleted_at=NOW(),updated_at=NOW() WHERE id=?')->execute([$id]);json_response(['ok'=>true]);
}
function action_restore(): never
{
    require_user();$id=(int)($_POST['id']??0);fetch_card_accessible($id);
    db()->prepare('UPDATE cards SET deleted_at=NULL,updated_at=NOW() WHERE id=?')->execute([$id]);json_response(['ok'=>true]);
}
function action_empty_trash(): never
{
    require_user();$space=current_space_id();require_space_access($space);
    $stmt=db()->prepare('SELECT id FROM cards WHERE space_id=? AND deleted_at IS NOT NULL');$stmt->execute([$space]);
    $ids=array_map('intval',$stmt->fetchAll(PDO::FETCH_COLUMN));permanent_delete_cards($ids);json_response(['ok'=>true,'deleted'=>count($ids)]);
}
function action_move(): never
{
    require_user();$id=(int)($_POST['id']??0);fetch_card_accessible($id);$dest=(int)($_POST['space_id']??0);require_space_access($dest);
    db()->prepare('UPDATE cards SET space_id=?,updated_at=NOW() WHERE id=?')->execute([$dest,$id]);json_response(['ok'=>true]);
}

function action_pin_toggle(): never
{
    require_user();
    $id=(int)($_POST['id']??0);
    $row=fetch_card_accessible($id);
    if (!empty($row['deleted_at'])) throw new RuntimeException('Trash entries cannot be pinned.');
    if ((int)($row['is_draft'] ?? 0) === 1) throw new RuntimeException('Save the note before pinning it.');
    $pin = !((bool)($row['is_pinned'] ?? false));
    if ($pin) {
        db()->prepare('UPDATE cards SET is_pinned=1,pinned_at=NOW(),updated_at=NOW() WHERE id=?')->execute([$id]);
    } else {
        db()->prepare('UPDATE cards SET is_pinned=0,pinned_at=NULL,updated_at=NOW() WHERE id=?')->execute([$id]);
    }
    json_response(['ok'=>true,'card'=>fetch_cards_by_ids([$id],true)[0]]);
}

function action_save_video_preview(): never
{
    require_user();$id=(int)($_POST['media_id']??0);
    $stmt=db()->prepare("SELECT mf.*,c.space_id FROM media_files mf JOIN cards c ON c.id=mf.card_id WHERE mf.id=? AND mf.media_type='video'");$stmt->execute([$id]);$row=$stmt->fetch();
    if(!$row)throw new RuntimeException('Video was not found.');require_space_access((int)$row['space_id']);
    $files=normalize_uploads($_FILES['preview']??null);$preview=$files[0]??null;if(!$preview)throw new RuntimeException('Preview is missing.');
    $dir=media_storage_directory((int)$row['space_id'],'previews');$rel=$dir.'/'.pathinfo($row['stored_path'],PATHINFO_FILENAME).'.jpg';
    if(!save_browser_preview($preview,storage_path($rel)))throw new RuntimeException('Could not save video preview.');
    $old=(string)$row['preview_path'];if($old!==''&&$old!==$rel&&is_file(storage_path($old)))@unlink(storage_path($old));
    db()->prepare('UPDATE media_files SET preview_path=? WHERE id=?')->execute([$rel,$id]);json_response(['ok'=>true,'preview_url'=>'file.php?id='.$id.'&preview=1&v='.time()]);
}

function action_export(): never
{
    require_user(); ensure_storage_structure(storage_root(true)); json_response(['ok'=>true,'export'=>create_export_package()]);
}
function action_export_list(): never
{
    require_user();$items=[];foreach(glob(storage_path('exports/*.zip'))?:[] as $f)$items[]=['name'=>basename($f),'size'=>filesize($f),'mtime'=>date('c',filemtime($f))];
    usort($items,fn($a,$b)=>strcmp($b['name'],$a['name']));json_response(['ok'=>true,'exports'=>$items]);
}
function action_import_list(): never {require_user();json_response(['ok'=>true,'packages'=>import_packages()]);}
function action_import_run(): never
{
    require_user();$result=run_import_package((string)($_POST['package']??''));json_response(['ok'=>true,'result'=>$result,'reload'=>true]);
}

$user=current_user();
if(!$user)json_response(['ok'=>false,'auth'=>false,'error'=>'Authentication required.'],401);
$action=action_name();
match($action){
    'state'=>action_state(), 'settings_save'=>action_settings_save(),
    'space_create'=>action_space_create(), 'space_unlock'=>action_space_unlock(), 'space_switch'=>action_space_switch(),
    'space_update'=>action_space_update(), 'space_delete'=>action_space_delete(),
    'list'=>action_list(), 'get'=>action_get(),
    'media_create'=>action_media_create(false), 'media_create_separate'=>action_media_create(true),
    'media_update'=>action_media_update(), 'media_add'=>action_media_add(), 'media_delete'=>action_media_delete(),
    'note_draft'=>action_note_draft(), 'note_save'=>action_note_save(),
    'note_inline_upload'=>action_note_upload('inline'), 'note_attachment_upload'=>action_note_upload('attachment'),
    'discard_draft'=>action_discard_draft(),
    'delete'=>action_soft_delete(), 'restore'=>action_restore(), 'empty_trash'=>action_empty_trash(), 'move'=>action_move(), 'pin_toggle'=>action_pin_toggle(),
    'save_video_preview'=>action_save_video_preview(),
    'export_create'=>action_export(), 'export_list'=>action_export_list(), 'import_list'=>action_import_list(), 'import_run'=>action_import_run(),
    default=>json_response(['ok'=>false,'error'=>'Unknown API action.'],400),
};
