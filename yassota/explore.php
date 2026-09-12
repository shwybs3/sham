<?php
require __DIR__ . '/config.php';
$cats = $pdo->query("SELECT * FROM categories ORDER BY sort_order")->fetchAll();
$rows = fetch_feed('explore', '', 1);
layout_top([
    'title' => 'استكشاف | ' . setting('site_name', 'YASSOTA'),
    'description' => 'اكتشف أحدث المنشورات والفيديوهات والمستخدمين والوسوم الرائجة على يسوتا.',
    'canonical' => url('explore'), 'active' => 'explore',
]);
?>
<div class="phead"><div><h1>استكشاف</h1><p class="sub">أحدث ما يُنشر على يسوتا</p></div></div>
<div class="chips">
  <a class="chip on" href="<?= e(url('explore')) ?>">الكل</a>
  <a class="chip ico" href="<?= e(url('trending')) ?>"><?= icon('fire', 16) ?> الترند</a>
  <a class="chip ico" href="<?= e(url('videos')) ?>"><?= icon('video', 16) ?> فيديوهات</a>
  <a class="chip ico" href="<?= e(url('people')) ?>"><?= icon('user', 16) ?> أشخاص</a>
  <?php foreach ($cats as $c): ?><a class="chip ico" href="<?= e(category_url($c['slug'])) ?>"><?= icon($c['icon'], 16) ?> <?= e($c['name']) ?></a><?php endforeach; ?>
</div>
<?php if ($rows): ?>
<div class="ex-grid" data-feed="explore" data-layout="tiles"><?= render_tiles($rows) ?></div>
<?php else: ?><div class="empty"><?= icon('compass',44) ?><p>لا توجد منشورات بعد.</p></div><?php endif; ?>
<?php layout_bottom(); ?>
