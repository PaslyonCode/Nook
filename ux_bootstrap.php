<?php
// Privacy bootstrap executed before assets/app.js.
// It only changes the remembered space when the previous nook is protected.

declare(strict_types=1);

header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

try {
    require_once __DIR__ . '/bootstrap.php';
    if (function_exists('require_user')) require_user();
    $pdo = db();
    $stmt = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key='default_space_id' LIMIT 1");
    $stmt->execute();
    $default = (int)$stmt->fetchColumn();
    if ($default <= 0) {
        $default = (int)$pdo->query("SELECT id FROM spaces WHERE COALESCE(password_hash,'')='' ORDER BY id LIMIT 1")->fetchColumn();
    }
    $protected = array_map('intval', $pdo->query("SELECT id FROM spaces WHERE COALESCE(password_hash,'')<>'' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN));
    $payload = json_encode(['defaultSpaceId'=>$default,'protectedSpaceIds'=>$protected], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    echo "(() => {\n";
    echo "  const cfg = {$payload};\n";
    echo <<<'JS'
  const keys = [
    'nook_ux_last_space_id','nook_space_id','nookSpaceId','space_id','current_space_id',
    'nook_current_space_id','currentSpaceId','activeSpaceId','nook_active_space_id'
  ];
  let last = 0;
  try {
    for (const key of keys) {
      const value = Number(localStorage.getItem(key) || 0);
      if (value > 0) { last = value; break; }
    }
  } catch (_) {}
  const protectedLast = cfg.protectedSpaceIds.includes(last);
  cfg.lastSpaceId = last;
  cfg.forcedDefault = Boolean(protectedLast && cfg.defaultSpaceId > 0);
  if (cfg.forcedDefault) {
    try {
      for (const key of keys) localStorage.setItem(key, String(cfg.defaultSpaceId));
      localStorage.setItem('nook_ux_last_space_id', String(cfg.defaultSpaceId));
    } catch (_) {}
    // Synchronize a possible server-side current-space session before the normal
    // Nook app starts its first state/list request. This small synchronous request
    // runs only on the privacy fallback path (protected -> default).
    try {
      const xhr = new XMLHttpRequest();
      xhr.open('POST', new URL('ux_api.php?action=startup_space', location.href), false);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded;charset=UTF-8');
      xhr.setRequestHeader('Accept', 'application/json');
      xhr.send('space_id=' + encodeURIComponent(String(cfg.defaultSpaceId)));
      cfg.serverSynced = xhr.status >= 200 && xhr.status < 300;
    } catch (_) { cfg.serverSynced = false; }
  }
  window.NookUXBootstrap = cfg;
})();
JS;
} catch (Throwable $e) {
    echo 'window.NookUXBootstrap={defaultSpaceId:0,protectedSpaceIds:[],lastSpaceId:0,forcedDefault:false};';
}
