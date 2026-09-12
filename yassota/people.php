<?php
require __DIR__ . '/config.php';
$me = current_user();
$q = trim($_GET['q'] ?? '');
$following = my_following_ids();

if ($q !== '') {
    $st = $pdo->prepare("SELECT * FROM users WHERE banned=0 AND (username LIKE ? OR name LIKE ? OR bio LIKE ?)
        ORDER BY followers_count DESC LIMIT 40");
    $st->execute(['%' . $q . '%', '%' . $q . '%', '%' . $q . '%']);
    $users = $st->fetchAll();
    $heading = 'نتائج «' . $q . '»';
} elseif ($me) {
    // اقتراحات: حسابات لا أتابعها، مرتّبة بمن يتابعهم من أتابعهم ثم بالشعبية
    $st = $pdo->prepare("SELECT u.*,
            (SELECT COUNT(*) FROM follows f2
              WHERE f2.following_id = u.id
                AND f2.follower_id IN (SELECT following_id FROM follows WHERE follower_id = ?)) mutual
        FROM users u
        WHERE u.banned = 0 AND u.id <> ?
          AND u.id NOT IN (SELECT following_id FROM follows WHERE follower_id = ?)
        ORDER BY mutual DESC, u.followers_count DESC, u.posts_count DESC
        LIMIT 40");
    $st->execute([$me['id'], $me['id'], $me['id']]);
    $users = $st->fetchAll();
    $heading = 'حسابات مقترحة لك';
} else {
    $users = $pdo->query("SELECT * FROM users WHERE banned=0 ORDER BY followers_count DESC LIMIT 40")->fetchAll();
    $heading = 'أبرز الحسابات';
}

layout_top([
    'title' => 'أشخاص | ' . setting('site_name', 'YASSOTA'),
    'description' => 'اكتشف حسابات جديدة وتابع من يشاركك اهتماماتك على يسوتا.',
    'canonical' => url('people'), 'active' => 'people',
]);
?>
<div class="phead"><div><h1><?= icon('user', 24) ?> أشخاص</h1><p class="sub">اكتشف حسابات وتابعها لتظهر منشوراتها في رئيسيتك</p></div></div>

<form action="<?= e(url('people')) ?>" method="get" class="field" style="margin-bottom:16px">
  <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="ابحث عن اسم أو معرّف…">
</form>

<h2 class="rel-h" style="margin-top:0"><?= e($heading) ?></h2>

<?php if ($users): ?>
  <div class="ulist">
    <?php foreach ($users as $u) user_row($u, isset($following[(int)$u['id']])); ?>
  </div>
<?php else: ?>
  <div class="empty"><?= icon('user', 44) ?><p><?= $q !== '' ? 'لا نتائج مطابقة.' : 'لا توجد حسابات بعد.' ?></p></div>
<?php endif; ?>

<?php layout_bottom(); ?>
