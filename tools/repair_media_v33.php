<?php
// Nook UX v3.3 media repair utility.
// Repairs incorrect media_type values and missing image previews using the actual stored originals.

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

require_once dirname(__DIR__) . '/bootstrap.php';

function v33_storage_root(): string
{
    if (function_exists('storage_root')) return rtrim(str_replace('\\', '/', storage_root(true)), '/');
    $stmt=db()->prepare("SELECT setting_value FROM app_settings WHERE setting_key='storage_root' LIMIT 1");
    $stmt->execute();$root=(string)$stmt->fetchColumn();
    if(trim($root)==='') throw new RuntimeException('Storage root is not configured.');
    return rtrim(str_replace('\\','/',$root),'/');
}

function v33_type(array $row): string
{
    $mime=strtolower(trim((string)($row['mime']??'')));
    $name=(string)($row['original_filename']??$row['stored_path']??'');
    $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
    if(str_starts_with($mime,'image/')||in_array($ext,['jpg','jpeg','png','gif','webp','bmp','avif'],true))return 'image';
    if(str_starts_with($mime,'video/')||in_array($ext,['mp4','webm','ogg','ogv','mov','avi','mkv','m4v'],true))return 'video';
    if($mime==='application/pdf'||$ext==='pdf')return 'pdf';
    if($ext==='stl')return 'stl';
    return 'file';
}

function v33_rel(string $p): string
{
    $p=ltrim(str_replace('\\','/',trim($p)),'/');
    return ($p===''||str_contains($p,'../')||str_contains($p,"\0"))?'':$p;
}

function v33_preview(array $row,string $root): string
{
    if(!extension_loaded('gd'))return '';
    $stored=v33_rel((string)($row['stored_path']??''));if($stored==='')return '';
    $src=$root.'/'.$stored;if(!is_file($src))return '';
    $info=@getimagesize($src);if(!$info||empty($info[0])||empty($info[1]))return '';
    $mime=strtolower((string)($info['mime']??$row['mime']??''));
    $im=match($mime){
        'image/jpeg','image/jpg'=>function_exists('imagecreatefromjpeg')?@imagecreatefromjpeg($src):false,
        'image/png'=>function_exists('imagecreatefrompng')?@imagecreatefrompng($src):false,
        'image/gif'=>function_exists('imagecreatefromgif')?@imagecreatefromgif($src):false,
        'image/webp'=>function_exists('imagecreatefromwebp')?@imagecreatefromwebp($src):false,
        default=>false,
    };
    if(!$im)return '';
    $w=(int)$info[0];$h=(int)$info[1];$scale=min(1.0,640/max($w,$h));$tw=max(1,(int)round($w*$scale));$th=max(1,(int)round($h*$scale));
    $thumb=imagecreatetruecolor($tw,$th);$white=imagecolorallocate($thumb,255,255,255);imagefill($thumb,0,0,$white);imagecopyresampled($thumb,$im,0,0,0,0,$tw,$th,$w,$h);
    $spaceId=(int)($row['space_id']??1);$rel='previews/'.$spaceId.'/repair-v33/media-'.(int)$row['id'].'-'.substr(sha1($stored),0,12).'.jpg';$target=$root.'/'.$rel;
    if(!is_dir(dirname($target))&&!mkdir(dirname($target),0775,true)&&!is_dir(dirname($target))){imagedestroy($thumb);imagedestroy($im);return '';}
    $ok=imagejpeg($thumb,$target,84);imagedestroy($thumb);imagedestroy($im);return $ok?$rel:'';
}

try{
    $pdo=db();$root=v33_storage_root();
    $rows=$pdo->query("SELECT mf.*,c.space_id FROM media_files mf JOIN cards c ON c.id=mf.card_id ORDER BY mf.id")->fetchAll(PDO::FETCH_ASSOC);
    $upType=$pdo->prepare('UPDATE media_files SET media_type=? WHERE id=?');$upPreview=$pdo->prepare('UPDATE media_files SET preview_path=? WHERE id=?');
    $typeFixed=0;$previewFixed=0;$missing=0;
    foreach($rows as $row){
        $inferred=v33_type($row);$current=strtolower((string)($row['media_type']??'file'));
        if($inferred!=='file'&&$current!==$inferred){$upType->execute([$inferred,(int)$row['id']]);$row['media_type']=$inferred;$typeFixed++;echo "Type #{$row['id']}: {$current} -> {$inferred} ({$row['original_filename']})\n";}
        if(($row['media_type']??$inferred)==='image'){
            $preview=v33_rel((string)($row['preview_path']??''));
            if($preview===''||!is_file($root.'/'.$preview)||strtolower(pathinfo($preview,PATHINFO_EXTENSION))==='svg'||!@getimagesize($root.'/'.$preview)){
                $new=v33_preview($row,$root);
                if($new!==''){$upPreview->execute([$new,(int)$row['id']]);$previewFixed++;echo "Preview #{$row['id']}: {$new}\n";}
                else{$stored=v33_rel((string)($row['stored_path']??''));if($stored===''||!is_file($root.'/'.$stored))$missing++;}
            }
        }
    }
    echo "\nChecked: ".count($rows)."\nType fixes: {$typeFixed}\nPreview fixes: {$previewFixed}\nMissing image originals: {$missing}\n";
    exit($missing>0?1:0);
}catch(Throwable $e){fwrite(STDERR,"Repair failed: {$e->getMessage()}\n");exit(1);}
