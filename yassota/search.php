<?php
require __DIR__ . '/config.php';
$q = trim($_GET['q'] ?? '');
$users = $tags = $rows = [];
if ($q !== '') {
    $rows = fetch_feed('search', $q, 1);
    $us = $pdo->prepare("SELECT * FROM users WHERE banned=0 AND (username LIKE ? OR name LIKE ?) LIMIT 8");
    $us->execute(['%' . $q . '%', '%' . $q . '%']); $users = $us->fetchAll();
    $ts = $pdo->prepare("SELECT * FROM hashtags WHERE name LIKE ? OR slug LIKE ? ORDER BY posts_count DESC LIMIT 8");
    $ts->execute(['%' . $q . '%', '%' . $q . '%']); $tags = $ts->fetchAll();
}
layout_top([
    'title' => ($q !== '' ? 'نتائج: ' . $q : 'بحث') . ' | ' . setting('site_name', 'YASSOTA'),
    'description' => 'ابحث في منشورات ومستخدمين ووسوم يسوتا.',
    'canonical' => url('search'), 'noindex' => true,
]);
?>
<div class="phead"><div><h1>بحث</h1><p class="sub"><?= $q !== '' ? 'نتائج «' . e($q) . '»' : 'ابحث عن منشورات، مستخدمين، ووسوم' ?></p></div></div>
<form action="<?= e(url('search')) ?>" method="get" class="field"><input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="اكتب كلمة البحث…" autofocus></form>

<?php if ($q === ''): ?>
  <div class="empty"><?= icon('search',44) ?><p>ابدأ الكتابة للبحث.</p></div>
<?php else: ?>
  <?php if ($users): ?>
    <h2 class="rel-h">مستخدمون</h2>
    <?php foreach ($users as $u): ?>
    <a class="card" style="display:flex;align-items:center;gap:12px;padding:12px;margin-bottom:8px" href="<?= e(user_url($u['username'])) ?>">
      <?= avatar_html($u,44) ?><span class="pu-t"><b><?= e($u['name'] ?: $u['username']) ?><?= verified($u) ?></b><small>@<?= e($u['username']) ?> · <?= num_fmt($u['followers_count']) ?> متابع</small></span>
    </a>
    <?php endforeach; ?>
  <?php endif; ?>
  <?php if ($tags): ?>
    <h2 class="rel-h">وسوم</h2>
    <div class="chips" style="flex-wrap:wrap"><?php foreach ($tags as $t): ?><a class="chip" href="<?= e(tag_url($t['slug'])) ?>">#<?= e($t['name']) ?> · <?= num_fmt($t['posts_count']) ?></a><?php endforeach; ?></div>
  <?php endif; ?>
  <?php if ($rows): ?>
    <h2 class="rel-h">منشورات</h2>
    <div class="ex-grid" data-feed="search" data-arg="<?= e($q) ?>" data-layout="tiles"><?= render_tiles($rows) ?></div>
  <?php elseif (!$users && !$tags): ?>
    <div class="empty"><?= icon('search',44) ?><p>لا نتائج لـ «<?= e($q) ?>».</p></div>
  <?php endif; ?>
<?php endif; ?>
<?php layout_bottom(); ?>
