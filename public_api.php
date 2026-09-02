<?php
// Anonymous API for the Nook public frontend.

declare(strict_types=1);

require_once __DIR__ . '/public_common.php';

try {
    $action = (string)($_GET['action'] ?? 'state');
    if ($action === 'state') {
        $requestedSlug = public_slug_normalize((string)($_GET['slug'] ?? 'blog'));
        $actualSlug = public_setting('public_slug', 'blog');
        if ($requestedSlug !== $actualSlug) public_json(['ok'=>false,'error'=>'Public frontend not found.'],404);
        $tag = public_tag_normalize((string)($_GET['tag'] ?? ''));
        public_json([
            'ok' => true,
            'settings' => [
                'slug' => $actualSlug,
                'header_html' => public_setting('public_header_html', ''),
                'logo_url' => public_setting('public_logo_path', '') !== '' ? 'public_logo.php' : '',
            ],
            'sidebar' => public_sidebar(),
            'feed' => public_feed($tag !== '' ? $tag : null),
            'active_tag' => $tag,
        ]);
    }
    if ($action === 'card') {
        $id = (int)($_GET['id'] ?? 0);
        public_json(['ok' => true, 'card' => public_card($id)]);
    }
    throw new RuntimeException('Unknown action.');
} catch (Throwable $e) {
    public_json(['ok' => false, 'error' => $e->getMessage()], 400);
}
