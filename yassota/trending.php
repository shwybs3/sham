<?php
require __DIR__ . '/config.php';
$rows = fetch_feed('trending', '', 1);
$tags = $pdo->query("SELECT * FROM hashtags ORDER BY posts_count DESC LIMIT 12")->fetchAll();
layout_top([
    'title' => 'الأكثر رواجاً | ' . setting('site_name', 'YASSOTA'),
    'description' => 'المنشورات والوسوم الأكثر تفاعلاً الآن على يسوتا.',
    'canonical' => url('trending'), 'active' => 'trending',
]);
?>
<div class="phead"><div><h1><?= icon('fire',24) ?> الأكثر رواجاً</h1><p class="sub">محتوى يحصد أعلى تفاعل الآن</p></div></div>
<?php if ($tags): ?>
<div class="chips"><?php foreach ($tags as $t): ?><a class="chip" href="<?= e(tag_url($t['slug'])) ?>">#<?= e($t['name']) ?></a><?php endforeach; ?></div>
<?php endif; ?>
<div class="feed" data-feed="trending" data-layout="cards"><?= $rows ? render_cards($rows) : '<div class="empty">لا يوجد محتوى رائج بعد.</div>' ?></div>
<?php layout_bottom(); ?>
