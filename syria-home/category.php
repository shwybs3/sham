<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$cat = $stmt->fetch();

if (!$cat) { http_response_code(404); require __DIR__ . '/404.php'; exit; }

if ($cat['type'] === 'tool') {
    $items = $pdo->prepare("SELECT * FROM tools WHERE category_id = ? AND status='published' ORDER BY uses_count DESC");
} else {
    $items = $pdo->prepare("SELECT a.*, c.name AS category_name FROM articles a LEFT JOIN categories c ON c.id=a.category_id WHERE a.category_id = ? AND a.status='published' ORDER BY a.published_at DESC");
}
$items->execute([$cat['id']]);
$items = $items->fetchAll();
?><!doctype html><html lang="en"><head>
<?php seo_head([
    'title' => e($cat['name']) . ' | ' . setting('site_name'),
    'description' => 'Everything tagged ' . $cat['name'] . ' on ' . setting('site_name') . '.',
    'canonical' => site_url('category.php?slug=' . $cat['slug']),
]); ?>
</head><body>
<?php site_header(); ?>

<div class="page-hero container">
  <span class="eyebrow"><i class="fa-solid <?= e($cat['icon']) ?>"></i> Category</span>
  <h1><?= e($cat['name']) ?></h1>
</div>

<div class="container">
  <?php if ($items): ?>
  <div class="grid <?= $cat['type'] === 'tool' ? 'grid-tools' : '' ?>">
    <?php foreach ($items as $i) $cat['type'] === 'tool' ? tool_card($i) : article_card($i); ?>
  </div>
  <?php else: ?>
    <div class="empty-state"><p>Nothing published in this category yet.</p></div>
  <?php endif; ?>
</div>

<?php site_footer(); ?>
</body></html>
