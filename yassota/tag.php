<?php
require __DIR__ . '/config.php';
$slug = $_GET['slug'] ?? '';
$st = $pdo->prepare("SELECT * FROM hashtags WHERE slug = ? LIMIT 1"); $st->execute([$slug]);
$tag = $st->fetch();
if (!$tag) { http_response_code(404); require __DIR__ . '/404.php'; exit; }
$rows = fetch_feed('tag', $slug, 1);
layout_top([
    'title' => '#' . $tag['name'] . ' | ' . setting('site_name', 'YASSOTA'),
    'description' => 'كل المنشورات الموسومة بـ #' . $tag['name'] . ' على يسوتا — ' . num_fmt($tag['posts_count']) . ' منشور.',
    'canonical' => tag_url($slug),
    'jsonld' => [['@context' => 'https://schema.org', '@type' => 'CollectionPage', 'name' => '#' . $tag['name'], 'url' => tag_url($slug)]],
]);
?>
<div class="phead"><div><h1>#<?= e($tag['name']) ?></h1><p class="sub"><?= num_fmt($tag['posts_count']) ?> منشور</p></div></div>
<?php if ($rows): ?>
<div class="ex-grid" data-feed="tag" data-arg="<?= e($slug) ?>" data-layout="tiles"><?= render_tiles($rows) ?></div>
<?php else: ?><div class="empty"><?= icon('grid',44) ?><p>لا منشورات بهذا الوسم بعد.</p></div><?php endif; ?>
<?php layout_bottom(); ?>
