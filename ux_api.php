<?php
// Nook UX v3 add-on API: default spaces, bulk actions and quick actions.

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function ux_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function ux_db(): PDO { return db(); }

function ux_user_id(): int
{
    foreach (['user_id', 'uid'] as $key) {
        if (!empty($_SESSION[$key])) return (int)$_SESSION[$key];
    }
    if (function_exists('current_user_id')) {
        try { $id = (int)current_user_id(); if ($id > 0) return $id; } catch (Throwable) {}
    }
    if (function_exists('current_user')) {
        try { $u = current_user(); if (is_array($u) && !empty($u['id'])) return (int)$u['id']; } catch (Throwable) {}
    }
    // Single-user legacy fallback.
    $rows = ux_db()->query('SELECT id FROM users ORDER BY id LIMIT 2')->fetchAll(PDO::FETCH_COLUMN);
    return count($rows) === 1 ? (int)$rows[0] : 0;
}

function ux_setting(string $key, string $default = ''): string
{
    $stmt = ux_db()->prepare('SELECT setting_value FROM app_settings WHERE setting_key=? LIMIT 1');
    $stmt->execute([$key]);
    $v = $stmt->fetchColumn();
    return ($v === false || $v === null) ? $default : (string)$v;
}

function ux_setting_set(string $key, string $value): void
{
    $stmt = ux_db()->prepare(
        'INSERT INTO app_settings(setting_key,setting_value) VALUES(?,?) '
        . 'ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)'
    );
    $stmt->execute([$key, $value]);
}

function ux_columns(string $table): array
{
    static $cache = [];
    if (isset($cache[$table])) return $cache[$table];
    $stmt = ux_db()->prepare(
        'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?'
    );
    $stmt->execute([$table]);
    return $cache[$table] = array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function ux_has_column(string $table, string $column): bool
{
    return in_array($column, ux_columns($table), true);
}

function ux_space(int $id): array
{
    $stmt = ux_db()->prepare('SELECT id,name,password_hash FROM spaces WHERE id=? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) throw new RuntimeException('Нычка не найдена.');
    $row['id'] = (int)$row['id'];
    $row['protected'] = trim((string)($row['password_hash'] ?? '')) !== '';
    return $row;
}

function ux_password_ok(array $space, string $password): bool
{
    $hash = trim((string)($space['password_hash'] ?? ''));
    if ($hash === '') return true;
    if ($password === '') return false;
    if (preg_match('/^[a-f0-9]{32}$/i', $hash)) return hash_equals(strtolower($hash), md5($password));
    return password_verify($password, $hash);
}

function ux_grant_space_access(int $spaceId, bool $remember = false): void
{
    $until = time() + 365 * 86400; // Session access remains valid until explicit leave/logout.
    $cookieUntil = time() + 30 * 86400;
    // Keep the UX key and several common legacy Nook session shapes in sync so
    // the normal api.php/file.php access checks see the same unlocked nook.
    foreach (['space_unlocks','nook_ux_space_access','space_access','nook_space_access','unlocked_spaces','space_unlocked','unlocked_space_ids'] as $key) {
        if (!isset($_SESSION[$key]) || !is_array($_SESSION[$key])) $_SESSION[$key] = [];
        $_SESSION[$key][$spaceId] = $until;
        $_SESSION[$key][(string)$spaceId] = $until;
    }
    if (!$remember) return;
    try {
        $uid = ux_user_id();
        if ($uid <= 0) return;
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', $cookieUntil);
        ux_db()->prepare('DELETE FROM space_access_tokens WHERE user_id=? AND space_id=?')->execute([$uid,$spaceId]);
        ux_db()->prepare('INSERT INTO space_access_tokens(user_id,space_id,token_hash,expires_at) VALUES(?,?,?,?)')->execute([$uid,$spaceId,$hash,$expires]);
        setcookie('nook_ux_space_' . $spaceId, $token, [
            'expires'=>$cookieUntil,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax'
        ]);
        $_COOKIE['nook_ux_space_' . $spaceId] = $token;
    } catch (Throwable) {}
}

function ux_restore_remembered_space_access(int $spaceId): bool
{
    $token = (string)($_COOKIE['nook_ux_space_' . $spaceId] ?? '');
    $uid = ux_user_id();
    if ($token === '' || $uid <= 0) return false;
    try {
        $stmt = ux_db()->prepare('SELECT id FROM space_access_tokens WHERE user_id=? AND space_id=? AND token_hash=? AND expires_at>NOW() LIMIT 1');
        $stmt->execute([$uid,$spaceId,hash('sha256',$token)]);
        if (!$stmt->fetchColumn()) return false;
        ux_grant_space_access($spaceId, false);
        return true;
    } catch (Throwable) { return false; }
}

function ux_space_access_cached(int $spaceId): bool
{
    if (!empty($_SESSION['nook_ux_space_access'][$spaceId])) {
        $until = (int)$_SESSION['nook_ux_space_access'][$spaceId];
        if ($until > time()) return true;
        unset($_SESSION['nook_ux_space_access'][$spaceId]);
    }
    if (ux_restore_remembered_space_access($spaceId)) return true;
    if (function_exists('require_space_access')) {
        try { require_space_access($spaceId); return true; } catch (Throwable) {}
    }
    return false;
}

function ux_require_space(int $spaceId, string $password = ''): void
{
    $space = ux_space($spaceId);
    if (!$space['protected']) return;
    if (ux_space_access_cached($spaceId)) return;
    if (ux_password_ok($space, $password)) {
        ux_grant_space_access($spaceId, false);
        return;
    }
    ux_json(['ok'=>false,'error'=>'Неверный пароль нычки.','code'=>'SPACE_PASSWORD_REQUIRED','space_id'=>$spaceId], 403);
}

function ux_default_space(): int
{
    $pdo = ux_db();
    $configured = (int)ux_setting('default_space_id', '0');
    if ($configured > 0) {
        $stmt = $pdo->prepare("SELECT id FROM spaces WHERE id=? AND COALESCE(password_hash,'')='' LIMIT 1");
        $stmt->execute([$configured]);
        if ($stmt->fetchColumn()) return $configured;
    }
    $id = (int)$pdo->query("SELECT id FROM spaces WHERE COALESCE(password_hash,'')='' ORDER BY id LIMIT 1")->fetchColumn();
    if ($id <= 0) throw new RuntimeException('Нет ни одной незапароленной нычки. Нычка по умолчанию должна быть открытой.');
    ux_setting_set('default_space_id', (string)$id);
    return $id;
}

function ux_parse_ids(mixed $raw): array
{
    if (is_string($raw)) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) $raw = $decoded;
        else $raw = preg_split('/[\s,;]+/', $raw) ?: [];
    }
    if (!is_array($raw)) return [];
    $ids = [];
    foreach ($raw as $v) { $id=(int)$v; if ($id>0) $ids[$id]=$id; }
    return array_values($ids);
}

function ux_card_rows(array $ids): array
{
    if (!$ids) return [];
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $sql = 'SELECT c.id,c.space_id,c.entry_type,c.title,c.is_hidden,c.deleted_at'
        . (ux_has_column('cards','is_pinned') ? ',c.is_pinned,c.pinned_at' : ',0 AS is_pinned,NULL AS pinned_at')
        . (ux_has_column('cards','is_published') ? ',c.is_published' : ',0 AS is_published')
        . ", (SELECT mf.id FROM media_files mf WHERE mf.card_id=c.id AND mf.role<>'inline' ORDER BY mf.sort_order,mf.id LIMIT 1) AS primary_media_id"
        . ", (SELECT mf.media_type FROM media_files mf WHERE mf.card_id=c.id AND mf.role<>'inline' ORDER BY mf.sort_order,mf.id LIMIT 1) AS primary_media_type"
        . ", (SELECT mf.mime FROM media_files mf WHERE mf.card_id=c.id AND mf.role<>'inline' ORDER BY mf.sort_order,mf.id LIMIT 1) AS primary_media_mime"
        . ", (SELECT mf.original_filename FROM media_files mf WHERE mf.card_id=c.id AND mf.role<>'inline' ORDER BY mf.sort_order,mf.id LIMIT 1) AS primary_media_name"
        . ", (SELECT mf.preview_path FROM media_files mf WHERE mf.card_id=c.id AND mf.role<>'inline' ORDER BY mf.sort_order,mf.id LIMIT 1) AS primary_preview_path"
        . " FROM cards c WHERE c.id IN ($marks)";
    $stmt = ux_db()->prepare($sql); $stmt->execute($ids);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        foreach (['id','space_id','is_hidden','is_pinned','is_published','primary_media_id'] as $k) $r[$k]=(int)($r[$k]??0);
        $primary = [
            'original_filename'=>(string)($r['primary_media_name']??''),
            'media_type'=>(string)($r['primary_media_type']??'file'),
            'mime'=>(string)($r['primary_media_mime']??''),
        ];
        $r['primary_media_type'] = ux_infer_media_type($primary);
        $r['primary_thumb_url'] = ($r['primary_media_id'] > 0 && trim((string)($r['primary_preview_path']??'')) !== '')
            ? 'file.php?id=' . $r['primary_media_id'] . '&preview=1' : '';
    }
    unset($r);
    return $rows;
}

function ux_require_cards(array $ids): array
{
    $rows = ux_card_rows($ids);
    if (count($rows) !== count($ids)) throw new RuntimeException('Часть выбранных записей не найдена.');
    foreach ($rows as $row) ux_require_space((int)$row['space_id']);
    return $rows;
}

function ux_tag_names(string $raw): array
{
    $parts = preg_split('/[\s,;]+/u', trim($raw)) ?: [];
    $out=[];
    foreach ($parts as $p) {
        $p=trim($p); $p=preg_replace('/^[#＃]+/u','',$p)??'';
        if ($p==='') continue;
        if (function_exists('mb_substr')) $p=mb_substr($p,0,100,'UTF-8'); else $p=substr($p,0,100);
        $out[$p]=$p;
    }
    return array_values($out);
}

function ux_add_tags(array $cardIds, string $raw): void
{
    $names = ux_tag_names($raw);
    if (!$names || !$cardIds) return;
    $pdo=ux_db();
    $cols=ux_columns('tags');
    $nameCol = in_array('name',$cols,true) ? 'name' : (in_array('tag',$cols,true) ? 'tag' : 'name');
    $insertTag=$pdo->prepare("INSERT INTO tags(`$nameCol`) VALUES(?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
    $findTag=$pdo->prepare("SELECT id FROM tags WHERE `$nameCol`=? LIMIT 1");
    $link=$pdo->prepare('INSERT IGNORE INTO card_tags(card_id,tag_id) VALUES(?,?)');
    foreach ($names as $name) {
        $insertTag->execute([$name]);
        $id=(int)$pdo->lastInsertId();
        if ($id<=0) { $findTag->execute([$name]); $id=(int)$findTag->fetchColumn(); }
        if ($id<=0) continue;
        foreach ($cardIds as $cardId) $link->execute([$cardId,$id]);
    }
}

function ux_replace_tags(int $cardId, string $raw): void
{
    $pdo=ux_db();
    $pdo->prepare('DELETE FROM card_tags WHERE card_id=?')->execute([$cardId]);
    ux_add_tags([$cardId], $raw);
}

function ux_storage_root(): string
{
    if (function_exists('storage_root')) return storage_root(true);
    $root=trim(ux_setting('storage_root',''));
    if ($root==='') throw new RuntimeException('Папка хранения не настроена.');
    return rtrim(str_replace('\\','/',$root),'/');
}

function ux_storage_copy(string $relative, string $suffix): string
{
    $relative=ltrim(str_replace('\\','/',$relative),'/');
    if ($relative==='' || str_contains($relative,'../')) return '';
    $root=ux_storage_root(); $source=$root.'/'.$relative;
    if (!is_file($source)) return '';
    $dir=dirname($relative); $ext=pathinfo($relative,PATHINFO_EXTENSION);
    $base=bin2hex(random_bytes(16)).'_'.$suffix.($ext!==''?'.'.$ext:'');
    $targetRel=($dir==='.'?'':$dir.'/').$base;
    $target=$root.'/'.$targetRel;
    if (!is_dir(dirname($target)) && !mkdir(dirname($target),0775,true) && !is_dir(dirname($target))) {
        throw new RuntimeException('Не удалось создать папку для копии файла.');
    }
    if (!copy($source,$target)) throw new RuntimeException('Не удалось скопировать файл '.$relative);
    return $targetRel;
}


function ux_infer_media_type(array $row): string
{
    $mime = strtolower(trim((string)($row['mime'] ?? '')));
    $name = (string)($row['original_filename'] ?? $row['stored_path'] ?? '');
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (str_starts_with($mime, 'image/') || in_array($ext, ['jpg','jpeg','png','gif','webp','bmp','avif'], true)) return 'image';
    if (str_starts_with($mime, 'video/') || in_array($ext, ['mp4','webm','ogg','ogv','mov','avi','mkv','m4v'], true)) return 'video';
    if ($mime === 'application/pdf' || $ext === 'pdf') return 'pdf';
    if (in_array($mime, ['model/stl','application/sla','application/vnd.ms-pki.stl','application/octet-stream'], true) && $ext === 'stl') return 'stl';
    if ($ext === 'stl') return 'stl';
    return 'file';
}

function ux_normalize_relative_path(string $relative): string
{
    $relative = ltrim(str_replace('\\', '/', trim($relative)), '/');
    if ($relative === '' || str_contains($relative, '../') || str_contains($relative, "\0")) return '';
    return $relative;
}

function ux_create_image_preview(array $row, int $spaceId): string
{
    if (!extension_loaded('gd')) return '';
    $root = ux_storage_root();
    $stored = ux_normalize_relative_path((string)($row['stored_path'] ?? ''));
    if ($stored === '') return '';
    $source = $root . '/' . $stored;
    if (!is_file($source)) return '';

    $existing = ux_normalize_relative_path((string)($row['preview_path'] ?? ''));
    if ($existing !== '' && is_file($root . '/' . $existing) && strtolower(pathinfo($existing, PATHINFO_EXTENSION)) !== 'svg' && @getimagesize($root . '/' . $existing)) return $existing;

    $info = @getimagesize($source);
    if (!$info || empty($info[0]) || empty($info[1])) return '';
    [$width, $height] = [(int)$info[0], (int)$info[1]];
    $mime = strtolower((string)($info['mime'] ?? $row['mime'] ?? ''));
    $image = match ($mime) {
        'image/jpeg', 'image/jpg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($source) : false,
        'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($source) : false,
        'image/gif' => function_exists('imagecreatefromgif') ? @imagecreatefromgif($source) : false,
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false,
        default => false,
    };
    if (!$image) return '';

    $max = 640;
    $scale = min(1.0, $max / max($width, $height));
    $tw = max(1, (int)round($width * $scale));
    $th = max(1, (int)round($height * $scale));
    $thumb = imagecreatetruecolor($tw, $th);
    $white = imagecolorallocate($thumb, 255, 255, 255);
    imagefill($thumb, 0, 0, $white);
    imagecopyresampled($thumb, $image, 0, 0, 0, 0, $tw, $th, $width, $height);

    $relative = 'previews/' . $spaceId . '/repair/media-' . (int)$row['id'] . '-' . substr(sha1($stored), 0, 12) . '.jpg';
    $target = $root . '/' . $relative;
    if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0775, true) && !is_dir(dirname($target))) {
        imagedestroy($thumb); imagedestroy($image); return '';
    }
    $ok = imagejpeg($thumb, $target, 84);
    imagedestroy($thumb); imagedestroy($image);
    return $ok ? $relative : '';
}

function ux_repair_media_for_space(int $spaceId): array
{
    ux_require_space($spaceId);
    $pdo = ux_db();
    $stmt = $pdo->prepare(
        "SELECT mf.* FROM media_files mf JOIN cards c ON c.id=mf.card_id " .
        "WHERE c.space_id=? AND c.deleted_at IS NULL ORDER BY mf.id"
    );
    $stmt->execute([$spaceId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $typeFixed = 0; $previewFixed = 0;
    $upType = $pdo->prepare('UPDATE media_files SET media_type=? WHERE id=?');
    $upPreview = $pdo->prepare('UPDATE media_files SET preview_path=? WHERE id=?');
    foreach ($rows as $row) {
        $current = strtolower(trim((string)($row['media_type'] ?? 'file')));
        $inferred = ux_infer_media_type($row);
        if ($inferred !== 'file' && $current !== $inferred) {
            $upType->execute([$inferred, (int)$row['id']]);
            $row['media_type'] = $inferred;
            $typeFixed++;
        }
        if (($row['media_type'] ?? $inferred) === 'image') {
            $preview = ux_normalize_relative_path((string)($row['preview_path'] ?? ''));
            $root = ux_storage_root();
            if ($preview === '' || !is_file($root . '/' . $preview) || strtolower(pathinfo($preview, PATHINFO_EXTENSION)) === 'svg' || !@getimagesize($root . '/' . $preview)) {
                $newPreview = ux_create_image_preview($row, $spaceId);
                if ($newPreview !== '') {
                    $upPreview->execute([$newPreview, (int)$row['id']]);
                    $previewFixed++;
                }
            }
        }
    }
    return ['checked'=>count($rows),'type_fixed'=>$typeFixed,'preview_fixed'=>$previewFixed];
}

function ux_repair_media_for_cards(array $cardIds): array
{
    $cardIds = ux_parse_ids($cardIds);
    if (!$cardIds) return ['checked'=>0,'type_fixed'=>0,'preview_fixed'=>0];
    $rows = ux_require_cards($cardIds);
    $allowed = [];
    foreach ($rows as $row) $allowed[(int)$row['id']] = (int)$row['space_id'];
    $marks = implode(',', array_fill(0, count($cardIds), '?'));
    $pdo = ux_db();
    $stmt = $pdo->prepare("SELECT mf.* FROM media_files mf WHERE mf.card_id IN ($marks) ORDER BY mf.card_id,mf.sort_order,mf.id");
    $stmt->execute($cardIds);
    $media = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $typeFixed=0; $previewFixed=0;
    $upType=$pdo->prepare('UPDATE media_files SET media_type=? WHERE id=?');
    $upPreview=$pdo->prepare('UPDATE media_files SET preview_path=? WHERE id=?');
    foreach($media as $row){
        $cardId=(int)$row['card_id']; $spaceId=(int)($allowed[$cardId]??0); if($spaceId<=0) continue;
        $current=strtolower(trim((string)($row['media_type']??'file')));
        $inferred=ux_infer_media_type($row);
        if($inferred!==$current && $inferred!=='file'){
            $upType->execute([$inferred,(int)$row['id']]);
            $row['media_type']=$inferred; $typeFixed++;
        }
        if(($row['media_type']??$inferred)==='image'){
            $preview=ux_normalize_relative_path((string)($row['preview_path']??''));
            $root=ux_storage_root();
            if($preview==='' || !is_file($root.'/'.$preview) || strtolower(pathinfo($preview, PATHINFO_EXTENSION))==='svg'){
                $new=ux_create_image_preview($row,$spaceId);
                if($new!==''){$upPreview->execute([$new,(int)$row['id']]);$previewFixed++;}
            }
        }
    }
    return ['checked'=>count($media),'type_fixed'=>$typeFixed,'preview_fixed'=>$previewFixed];
}

function ux_normalize_nav_type(string $type): string
{
    $type = strtolower(trim($type));
    return match ($type) {
        'photo','photos','image','images','фото' => 'image',
        'video','videos','видео' => 'video',
        'document','documents','pdf','документ','документы' => 'pdf',
        'note','notes','заметка','заметки' => 'note',
        'stl','model','models','модель','модели' => 'stl',
        'file','files','файл','файлы' => 'file',
        default => '',
    };
}

function ux_nav_card_ids(int $spaceId, array $filters): array
{
    ux_require_space($spaceId);
    $pdo=ux_db();
    $tagCols=ux_columns('tags');
    $tagCol=in_array('name',$tagCols,true)?'name':(in_array('tag',$tagCols,true)?'tag':'name');
    $where=['c.space_id=?','c.is_draft=0']; $args=[$spaceId];
    $trash=!empty($filters['trash']);
    $where[]=$trash?'c.deleted_at IS NOT NULL':'c.deleted_at IS NULL';
    $q=trim((string)($filters['q']??''));
    $tag=trim((string)($filters['tag']??''));
    $dateFrom=trim((string)($filters['date_from']??''));
    $dateTo=trim((string)($filters['date_to']??''));
    $type=ux_normalize_nav_type((string)($filters['type']??''));
    $visibility=strtolower(trim((string)($filters['visibility']??'')));
    if($dateFrom!=='' && preg_match('/^\\d{4}-\\d{2}-\\d{2}$/',$dateFrom)){$where[]='c.created_at>=?';$args[]=$dateFrom.' 00:00:00';}
    if($dateTo!=='' && preg_match('/^\\d{4}-\\d{2}-\\d{2}$/',$dateTo)){$where[]='c.created_at<=?';$args[]=$dateTo.' 23:59:59';}
    if($q!==''){
        $like='%'.$q.'%';
        $bodyExpr=ux_has_column('cards','body_html')?'c.body_html LIKE ?':'0';
        $where[]="(c.title LIKE ? OR c.description LIKE ? OR $bodyExpr OR EXISTS(SELECT 1 FROM media_files mq WHERE mq.card_id=c.id AND mq.original_filename LIKE ?) OR EXISTS(SELECT 1 FROM card_tags ctq JOIN tags tq ON tq.id=ctq.tag_id WHERE ctq.card_id=c.id AND tq.`$tagCol` LIKE ?))";
        $args[]=$like;$args[]=$like;
        if(ux_has_column('cards','body_html'))$args[]=$like;
        $args[]=$like;$args[]=$like;
    }
    if($tag!==''){
        $tag=preg_replace('/^[#＃]+/u','',$tag)??$tag;
        $where[]="EXISTS(SELECT 1 FROM card_tags ctt JOIN tags tt ON tt.id=ctt.tag_id WHERE ctt.card_id=c.id AND tt.`$tagCol`=?)";$args[]=$tag;
    }
    if($type==='note') $where[]="c.entry_type='note'";
    elseif($type!=='') {$where[]="c.entry_type<>'note' AND EXISTS(SELECT 1 FROM media_files mt WHERE mt.card_id=c.id AND mt.role<>'inline' AND mt.media_type=?)";$args[]=$type;}
    if($visibility==='hidden')$where[]='c.is_hidden=1';
    elseif($visibility==='visible')$where[]='c.is_hidden=0';
    elseif($q==='' && $tag==='' && $dateFrom==='' && $dateTo==='' && $type==='')$where[]='c.is_hidden=0';
    $order=ux_has_column('cards','is_pinned')?'c.is_pinned DESC,c.pinned_at DESC,c.created_at DESC,c.id DESC':'c.created_at DESC,c.id DESC';
    $sql='SELECT c.id FROM cards c WHERE '.implode(' AND ',$where).' ORDER BY '.$order;
    $st=$pdo->prepare($sql);$st->execute($args);
    return array_map('intval',$st->fetchAll(PDO::FETCH_COLUMN));
}

function ux_duplicate_card(int $cardId): int
{
    $pdo=ux_db();
    $rows=ux_require_cards([$cardId]); $old=$rows[0];
    $stmt=$pdo->prepare('SELECT * FROM cards WHERE id=? LIMIT 1'); $stmt->execute([$cardId]);
    $card=$stmt->fetch(PDO::FETCH_ASSOC); if(!$card) throw new RuntimeException('Запись не найдена.');
    $columns=ux_columns('cards');
    unset($card['id']);
    if (array_key_exists('title',$card)) $card['title']=trim((string)$card['title']).' — копия';
    if (array_key_exists('deleted_at',$card)) $card['deleted_at']=null;
    if (array_key_exists('is_draft',$card)) $card['is_draft']=0;
    if (array_key_exists('created_at',$card)) $card['created_at']=date('Y-m-d H:i:s');
    if (array_key_exists('updated_at',$card)) $card['updated_at']=date('Y-m-d H:i:s');
    if (array_key_exists('is_pinned',$card)) $card['is_pinned']=0;
    if (array_key_exists('pinned_at',$card)) $card['pinned_at']=null;
    foreach (['is_published','publish_as_page','is_public_pinned'] as $c) if(array_key_exists($c,$card)) $card[$c]=0;
    foreach (['public_tag','public_pinned_at','published_at'] as $c) if(array_key_exists($c,$card)) $card[$c]=($c==='public_tag'?'':null);
    if (array_key_exists('public_page_order',$card)) $card['public_page_order']=0;
    $insertCols=array_values(array_filter(array_keys($card),fn($c)=>in_array($c,$columns,true)));
    $sql='INSERT INTO cards (`'.implode('`,`',$insertCols).'`) VALUES('.implode(',',array_fill(0,count($insertCols),'?')).')';
    $pdo->prepare($sql)->execute(array_map(fn($c)=>$card[$c],$insertCols));
    $newCardId=(int)$pdo->lastInsertId();

    // Tags.
    try {
        $pdo->prepare('INSERT IGNORE INTO card_tags(card_id,tag_id) SELECT ?,tag_id FROM card_tags WHERE card_id=?')->execute([$newCardId,$cardId]);
    } catch (Throwable) {}

    // Media and attachments: make physical copies so either card can later be deleted independently.
    $mediaCols=ux_columns('media_files');
    $ms=$pdo->prepare('SELECT * FROM media_files WHERE card_id=? ORDER BY sort_order,id'); $ms->execute([$cardId]);
    $map=[];
    foreach($ms->fetchAll(PDO::FETCH_ASSOC) as $m){
        $oldMediaId=(int)$m['id']; unset($m['id']); $m['card_id']=$newCardId;
        if(isset($m['stored_path'])) $m['stored_path']=ux_storage_copy((string)$m['stored_path'],'copy');
        if(isset($m['preview_path']) && trim((string)$m['preview_path'])!=='') $m['preview_path']=ux_storage_copy((string)$m['preview_path'],'preview');
        if(isset($m['created_at'])) $m['created_at']=date('Y-m-d H:i:s');
        $cols=array_values(array_filter(array_keys($m),fn($c)=>in_array($c,$mediaCols,true)));
        $q='INSERT INTO media_files (`'.implode('`,`',$cols).'`) VALUES('.implode(',',array_fill(0,count($cols),'?')).')';
        $pdo->prepare($q)->execute(array_map(fn($c)=>$m[$c],$cols));
        $map[$oldMediaId]=(int)$pdo->lastInsertId();
    }
    if($map){
        $s=$pdo->prepare('SELECT body_html,body_json FROM cards WHERE id=?'); $s->execute([$newCardId]); $body=$s->fetch(PDO::FETCH_ASSOC)?:[];
        $html=(string)($body['body_html']??''); $json=(string)($body['body_json']??'');
        foreach($map as $oldId=>$newId){
            $html=preg_replace('~(file\\.php\\?id=)'.preg_quote((string)$oldId,'~').'(?=(&|&amp;|["\']))~','$1'.$newId,$html)??$html;
            $json=preg_replace('~(file\\.php\\?id=)'.preg_quote((string)$oldId,'~').'(?=(&|\\\\u0026|["\\\\]))~','$1'.$newId,$json)??$json;
        }
        $pdo->prepare('UPDATE cards SET body_html=?,body_json=? WHERE id=?')->execute([$html,$json,$newCardId]);
    }
    return $newCardId;
}

function ux_set_active_space_session(int $spaceId): void
{
    // Keep common server-side space selectors in sync before the core application starts.
    // This is intentionally limited to an already existing, unprotected nook selected by
    // the privacy bootstrap; it does not grant access to protected nooks.
    foreach (['space_id','current_space_id','active_space_id','nook_space_id','nook_current_space_id'] as $key) {
        $_SESSION[$key] = $spaceId;
    }
    if (isset($_SESSION['nook']) && is_array($_SESSION['nook'])) {
        $_SESSION['nook']['space_id'] = $spaceId;
        $_SESSION['nook']['current_space_id'] = $spaceId;
    }
    if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
        $_SESSION['user']['space_id'] = $spaceId;
    }
}

function ux_clear_space_access(int $spaceId): void
{
    $uid=ux_user_id();
    unset($_SESSION['nook_ux_space_access'][$spaceId]);
    foreach ($_SESSION as $key=>&$value) {
        if (!preg_match('/space|nook/i',(string)$key)) continue;
        if (is_array($value)) {
            unset($value[$spaceId],$value[(string)$spaceId]);
        }
    }
    unset($value);
    if($uid>0){
        try { ux_db()->prepare('DELETE FROM space_access_tokens WHERE user_id=? AND space_id=?')->execute([$uid,$spaceId]); } catch(Throwable) {}
    }
    foreach(array_keys($_COOKIE) as $name){
        if(($name==='nook_ux_space_'.$spaceId) || (preg_match('/space/i',$name) && str_contains($name,(string)$spaceId))){
            setcookie($name,'',time()-3600,'/'); unset($_COOKIE[$name]);
        }
    }
}

try {
    require_user();
    $action=(string)($_GET['action']??$_POST['action']??'');
    $pdo=ux_db();

    if($action==='spaces'){
        $default=ux_default_space();
        $rows=$pdo->query("SELECT s.id,s.name,s.password_hash,(SELECT COUNT(*) FROM cards c WHERE c.space_id=s.id AND c.deleted_at IS NULL) AS card_count FROM spaces s ORDER BY s.id")->fetchAll(PDO::FETCH_ASSOC);
        foreach($rows as &$r){
            $r['id']=(int)$r['id'];
            $r['protected']=trim((string)($r['password_hash']??''))!=='';
            $r['unlocked']=!$r['protected'] || ux_space_access_cached($r['id']);
            $r['is_default']=$r['id']===$default;
            $r['card_count']=(int)$r['card_count'];
            unset($r['password_hash']);
        }
        unset($r);
        ux_json(['ok'=>true,'spaces'=>$rows,'default_space_id'=>$default]);
    }

    if($action==='startup_space'){
        $id=(int)($_POST['space_id']??0); if($id<=0) throw new RuntimeException('Нычка не выбрана.');
        $space=ux_space($id);
        if($space['protected']) throw new RuntimeException('Приватный старт возможен только в открытой нычке.');
        ux_set_active_space_session($id);
        ux_json(['ok'=>true,'space_id'=>$id]);
    }

    if($action==='unlock_space'){
        $id=(int)($_POST['space_id']??0); if($id<=0) throw new RuntimeException('Нычка не выбрана.');
        $space=ux_space($id);
        if(!$space['protected']) ux_json(['ok'=>true,'space_id'=>$id]);
        $password=(string)($_POST['password']??'');
        if(!ux_password_ok($space,$password)) ux_json(['ok'=>false,'error'=>'Неверный пароль.','code'=>'SPACE_PASSWORD_REQUIRED','space_id'=>$id],403);
        ux_grant_space_access($id,!empty($_POST['remember']));
        ux_json(['ok'=>true,'space_id'=>$id]);
    }

    if($action==='set_default'){
        $id=(int)($_POST['space_id']??0); $s=ux_space($id);
        if($s['protected']) throw new RuntimeException('Запароленную нычку нельзя сделать нычкой по умолчанию.');
        ux_setting_set('default_space_id',(string)$id);
        ux_json(['ok'=>true,'default_space_id'=>$id]);
    }

    if($action==='leave_space'){
        $id=(int)($_POST['space_id']??0); if($id>0) ux_clear_space_access($id);
        ux_json(['ok'=>true,'default_space_id'=>ux_default_space()]);
    }

    if($action==='cards_meta'){
        $ids=ux_parse_ids($_GET['ids']??'');
        $repair=ux_repair_media_for_cards($ids);
        $rows=ux_card_rows($ids);
        ux_json(['ok'=>true,'cards'=>$rows,'repair'=>$repair]);
    }

    if($action==='nav_cards'){
        $spaceId=(int)($_GET['space_id']??0);
        if($spaceId<=0) throw new RuntimeException('Нычка не выбрана.');
        $filters=[
            'q'=>(string)($_GET['q']??''),
            'tag'=>(string)($_GET['tag']??''),
            'date_from'=>(string)($_GET['date_from']??''),
            'date_to'=>(string)($_GET['date_to']??''),
            'type'=>(string)($_GET['type']??''),
            'visibility'=>(string)($_GET['visibility']??''),
            'trash'=>!empty($_GET['trash']),
        ];
        ux_json(['ok'=>true,'ids'=>ux_nav_card_ids($spaceId,$filters)]);
    }

    if($action==='repair_media'){
        $spaceId=(int)($_GET['space_id']??$_POST['space_id']??0);
        if($spaceId<=0) throw new RuntimeException('Нычка не выбрана.');
        ux_json(['ok'=>true,'repair'=>ux_repair_media_for_space($spaceId)]);
    }

    if($action==='card_media'){
        $cardId=(int)($_GET['card_id']??0);
        if($cardId<=0) throw new RuntimeException('Запись не выбрана.');
        ux_require_cards([$cardId]);
        $cardRow=ux_card_rows([$cardId])[0]??null;
        if(!$cardRow) throw new RuntimeException('Запись не найдена.');
        $cardSpaceId=(int)$cardRow['space_id'];
        $st=$pdo->prepare("SELECT id,role,original_filename,stored_path,preview_path,media_type,mime,size_bytes,sort_order FROM media_files WHERE card_id=? AND role<>'inline' ORDER BY sort_order,id");
        $st->execute([$cardId]);
        $items=$st->fetchAll(PDO::FETCH_ASSOC);
        $upType=$pdo->prepare('UPDATE media_files SET media_type=? WHERE id=?');
        $upPreview=$pdo->prepare('UPDATE media_files SET preview_path=? WHERE id=?');
        foreach($items as &$m){
            $m['id']=(int)$m['id'];$m['size_bytes']=(int)($m['size_bytes']??0);$m['sort_order']=(int)($m['sort_order']??0);
            $inferred=ux_infer_media_type($m);
            if($inferred!=='file' && strtolower((string)($m['media_type']??'file'))!==$inferred){$upType->execute([$inferred,$m['id']]);$m['media_type']=$inferred;}
            if(($m['media_type']??$inferred)==='image'){
                $preview=ux_normalize_relative_path((string)($m['preview_path']??''));$root=ux_storage_root();
                if($preview===''||!is_file($root.'/'.$preview)||strtolower(pathinfo($preview,PATHINFO_EXTENSION))==='svg'||!@getimagesize($root.'/'.$preview)){$newPreview=ux_create_image_preview($m,$cardSpaceId);if($newPreview!==''){$upPreview->execute([$newPreview,$m['id']]);$m['preview_path']=$newPreview;}}
            }
            $url='file.php?id='.$m['id'];
            $m['file_url']=$url;$m['url']=$url;
            $m['thumb_url']=trim((string)($m['preview_path']??''))!==''?'file.php?id='.$m['id'].'&preview=1':'';
        }unset($m);
        ux_json(['ok'=>true,'media'=>$items]);
    }

    if($action==='card_action'){
        $id=(int)($_POST['card_id']??0); if($id<=0) throw new RuntimeException('Запись не выбрана.');
        $row=ux_require_cards([$id])[0]; $op=(string)($_POST['op']??'');
        if($op==='delete') $pdo->prepare('UPDATE cards SET deleted_at=NOW() WHERE id=?')->execute([$id]);
        elseif($op==='hidden') $pdo->prepare('UPDATE cards SET is_hidden=? WHERE id=?')->execute([!empty($_POST['value'])?1:0,$id]);
        elseif($op==='pin'){
            if(!ux_has_column('cards','is_pinned')) throw new RuntimeException('Сначала установи миграцию закрепления.');
            $value=!empty($_POST['value'])?1:0;
            $pdo->prepare('UPDATE cards SET is_pinned=?,pinned_at=? WHERE id=?')->execute([$value,$value?date('Y-m-d H:i:s'):null,$id]);
        } elseif($op==='duplicate') {
            $newId=ux_duplicate_card($id); ux_json(['ok'=>true,'new_card_id'=>$newId]);
        } else throw new RuntimeException('Неизвестное действие.');
        ux_json(['ok'=>true]);
    }

    if($action==='bulk_action'){
        $ids=ux_parse_ids($_POST['card_ids']??''); if(!$ids) throw new RuntimeException('Не выбраны записи.');
        ux_require_cards($ids); $op=(string)($_POST['op']??'');
        $marks=implode(',',array_fill(0,count($ids),'?'));
        if($op==='tag') ux_add_tags($ids,(string)($_POST['tag']??''));
        elseif($op==='delete') $pdo->prepare("UPDATE cards SET deleted_at=NOW() WHERE id IN ($marks)")->execute($ids);
        elseif($op==='move'){
            $target=(int)($_POST['target_space_id']??0); if($target<=0) throw new RuntimeException('Не выбрана целевая нычка.');
            ux_require_space($target,(string)($_POST['space_password']??''));
            $pdo->prepare("UPDATE cards SET space_id=? WHERE id IN ($marks)")->execute(array_merge([$target],$ids));
        }
        elseif($op==='pin'){
            if(!ux_has_column('cards','is_pinned')) throw new RuntimeException('Сначала установи миграцию закрепления.');
            $v=!empty($_POST['value'])?1:0; $args=[$v,$v?date('Y-m-d H:i:s'):null,...$ids];
            $pdo->prepare("UPDATE cards SET is_pinned=?,pinned_at=? WHERE id IN ($marks)")->execute($args);
        }
        elseif($op==='hidden'){
            $v=!empty($_POST['value'])?1:0; $pdo->prepare("UPDATE cards SET is_hidden=? WHERE id IN ($marks)")->execute([$v,...$ids]);
        }
        elseif($op==='publish'){
            if(!ux_has_column('cards','is_published')) throw new RuntimeException('Публичный фронтенд не установлен.');
            $v=!empty($_POST['value'])?1:0;
            if($v) $pdo->prepare("UPDATE cards SET is_published=1,published_at=COALESCE(published_at,NOW()) WHERE id IN ($marks)")->execute($ids);
            else $pdo->prepare("UPDATE cards SET is_published=0,publish_as_page=0,is_public_pinned=0,public_pinned_at=NULL WHERE id IN ($marks)")->execute($ids);
        } else throw new RuntimeException('Неизвестное групповое действие.');
        ux_json(['ok'=>true,'affected'=>count($ids)]);
    }

    if($action==='card_metadata_save'){
        $id=(int)($_POST['card_id']??0); if($id<=0) throw new RuntimeException('Запись не выбрана.');
        ux_require_cards([$id]);
        $sets=[];$args=[];
        if(array_key_exists('title',$_POST)){$sets[]='title=?';$args[]=trim((string)$_POST['title']);}
        if(array_key_exists('description',$_POST)){$sets[]='description=?';$args[]=(string)$_POST['description'];}
        if(array_key_exists('is_hidden',$_POST)){$sets[]='is_hidden=?';$args[]=!empty($_POST['is_hidden'])?1:0;}
        if($sets){$args[]=$id;$pdo->prepare('UPDATE cards SET '.implode(',',$sets).',updated_at=NOW() WHERE id=?')->execute($args);}
        if(array_key_exists('tags',$_POST)) ux_replace_tags($id,(string)$_POST['tags']);
        ux_json(['ok'=>true]);
    }

    if($action==='space_create'){
        $name=trim((string)($_POST['name']??''));$password=(string)($_POST['password']??'');
        if($name==='') throw new RuntimeException('Введите название нычки.');
        $hash=$password!==''?password_hash($password,PASSWORD_DEFAULT):null;
        $pdo->prepare('INSERT INTO spaces(name,password_hash) VALUES(?,?)')->execute([$name,$hash]);
        ux_json(['ok'=>true,'space_id'=>(int)$pdo->lastInsertId()]);
    }

    if($action==='space_update'){
        $id=(int)($_POST['space_id']??0);$space=ux_space($id);ux_require_space($id,(string)($_POST['current_password']??''));
        $name=trim((string)($_POST['name']??$space['name']));if($name==='')throw new RuntimeException('Введите название нычки.');
        $mode=(string)($_POST['password_mode']??'keep');$hash=$space['password_hash'];
        if($mode==='remove')$hash=null;elseif($mode==='set'){$p=(string)($_POST['password']??'');if($p==='')throw new RuntimeException('Введите новый пароль.');$hash=password_hash($p,PASSWORD_DEFAULT);}
        // Privacy invariant: the configured default must always be an unprotected nook.
        $configuredDefault=(int)ux_setting('default_space_id','0');
        $replacementDefault=0;
        if($hash && $configuredDefault===$id){
            $replacementDefault=(int)$pdo->query("SELECT id FROM spaces WHERE id<>".$id." AND COALESCE(password_hash,'')='' ORDER BY id LIMIT 1")->fetchColumn();
            if($replacementDefault<=0) throw new RuntimeException('Нельзя запаролить единственную открытую нычку. Сначала создай другую открытую нычку и сделай ее нычкой по умолчанию.');
        }
        $pdo->prepare('UPDATE spaces SET name=?,password_hash=? WHERE id=?')->execute([$name,$hash,$id]);
        if($replacementDefault>0)ux_setting_set('default_space_id',(string)$replacementDefault);
        if($mode==='set'||$mode==='remove')ux_clear_space_access($id);
        ux_json(['ok'=>true]);
    }

    if($action==='space_delete'){
        $id=(int)($_POST['space_id']??0);$space=ux_space($id);ux_require_space($id,(string)($_POST['space_password']??''));
        $count=(int)$pdo->query('SELECT COUNT(*) FROM spaces')->fetchColumn();if($count<=1)throw new RuntimeException('Нельзя удалить единственную нычку.');
        $target=(int)($_POST['target_space_id']??0);
        if($target<=0||$target===$id){$target=(int)$pdo->query("SELECT id FROM spaces WHERE id<>".$id." AND COALESCE(password_hash,'')='' ORDER BY id LIMIT 1")->fetchColumn();}
        if($target<=0)throw new RuntimeException('Для удаления нужна другая незапароленная нычка, куда перенести записи.');
        ux_require_space($target,(string)($_POST['target_password']??''));
        $pdo->beginTransaction();$pdo->prepare('UPDATE cards SET space_id=? WHERE space_id=?')->execute([$target,$id]);
        try{$pdo->prepare('DELETE FROM space_access_tokens WHERE space_id=?')->execute([$id]);}catch(Throwable){}
        $pdo->prepare('DELETE FROM spaces WHERE id=?')->execute([$id]);$pdo->commit();
        if((int)ux_setting('default_space_id','0')===$id)ux_setting_set('default_space_id',(string)$target);
        ux_json(['ok'=>true,'moved_to'=>$target]);
    }

    throw new RuntimeException('Неизвестное действие API.');
} catch (Throwable $e) {
    ux_json(['ok'=>false,'error'=>$e->getMessage()], 400);
}
