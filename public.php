<?php
// Minimal anonymous frontend for explicitly published Nook entries.

declare(strict_types=1);
require_once __DIR__ . '/public_common.php';

try {
    $requested = public_slug_normalize((string)($_GET['public_slug'] ?? ''));
    $actual = public_setting('public_slug', 'blog');
    if ($requested !== $actual) { http_response_code(404); exit('Not found'); }
} catch (Throwable $e) { http_response_code(404); exit('Not found'); }
$basePath = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
if ($basePath === '.') $basePath = '';
?><!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <base href="<?= htmlspecialchars(($basePath !== '' ? $basePath : '') . '/', ENT_QUOTES, 'UTF-8') ?>">
  <title>Nook</title>
  <link rel="stylesheet" href="assets/public.css?v=1">
</head>
<body>
<header class="public-header">
  <a class="public-brand" href="./<?= htmlspecialchars($actual, ENT_QUOTES, 'UTF-8') ?>" aria-label="Nook">
    <img id="publicLogo" class="public-logo" alt="Nook" hidden>
    <span id="publicLogoFallback" class="public-logo-fallback">N</span>
  </a>
  <div id="publicHeaderHtml" class="public-header-html" hidden></div>
</header>
<div class="public-shell">
  <aside class="public-sidebar">
    <nav id="publicPages" class="public-pages"></nav>
    <div id="publicTags" class="public-tags"></div>
  </aside>
  <main id="publicMain" class="public-main">
    <div id="publicFeed" class="public-feed"></div>
  </main>
</div>
<div id="publicCardModal" class="public-modal" hidden>
  <div class="public-modal-backdrop" data-close="card"></div>
  <section class="public-modal-window public-modal-wide" role="dialog" aria-modal="true">
    <header class="public-modal-header"><div><h2 id="publicCardTitle"></h2><p id="publicCardMeta" class="public-muted"></p></div><button class="public-icon-btn" data-close="card">×</button></header>
    <div id="publicCardBody" class="public-card-body"></div>
  </section>
</div>
<div id="publicViewerModal" class="public-modal" hidden>
  <div class="public-modal-backdrop" data-close="viewer"></div>
  <section class="public-modal-window public-viewer-window" role="dialog" aria-modal="true">
    <header class="public-modal-header"><div><h2 id="publicViewerTitle"></h2></div><button class="public-icon-btn" data-close="viewer">×</button></header>
    <div id="publicViewer" class="public-viewer"></div>
  </section>
</div>
<script>window.NOOK_PUBLIC_SLUG=<?= json_encode($actual, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="assets/public.js?v=1"></script>
</body>
</html>
