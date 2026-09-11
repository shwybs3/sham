<?php
require __DIR__ . '/config.php';
$slug = $_GET['slug'] ?? '';
$st = $pdo->prepare("SELECT * FROM categories WHERE slug = ? LIMIT 1"); $st->execute([$slug]);
$cat = $st->fetch();
if (!$cat) { http_response_code(404); require __DIR__ . '/404.php'; exit; }
$rows = fetch_feed('category', $slug, 1);
layout_top([
    'title' => $cat['name'] . ' | ' . setting('site_name', 'YASSOTA'),
    'description' => $cat['description'] ?: ('منشورات قسم ' . $cat['name'] . ' على يسوتا.'),
    'canonical' => category_url($slug),
    'jsonld' => [['@context' => 'https://schema.org', '@type' => 'CollectionPage', 'name' => $cat['name'], 'description' => $cat['description'], 'url' => category_url($slug)]],
]);
?>
<div class="phead"><div style="display:flex;align-items:center;gap:12px"><span class="logo" style="background:var(--grad-soft);color:var(--brand)"><?= icon($cat['icon'],22) ?></span><div><h1><?= e($cat['name']) ?></h1><p class="sub"><?= e($cat['description']) ?></p></div></div></div>
<?php if ($rows): ?>
<div class="ex-grid" data-feed="category" data-arg="<?= e($slug) ?>" data-layout="tiles"><?= render_tiles($rows) ?></div>
<?php else: ?><div class="empty"><?= icon('grid',44) ?><p>لا منشورات في هذا القسم بعد.</p></div><?php endif; ?>
<?php layout_bottom(); ?>
