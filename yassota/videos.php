<?php
require __DIR__ . '/config.php';
$rows = fetch_feed('videos', '', 1);
layout_top([
    'title' => 'الفيديوهات | ' . setting('site_name', 'YASSOTA'),
    'description' => 'أحدث الفيديوهات العمودية على يسوتا.',
    'canonical' => url('videos'), 'active' => 'explore',
]);
?>
<div class="phead"><div><h1><?= icon('video',24) ?> الفيديوهات</h1><p class="sub">محتوى مرئي من مجتمع يسوتا</p></div></div>
<?php if ($rows): ?>
<div class="feed" data-feed="videos" data-layout="cards"><?= render_cards($rows) ?></div>
<?php else: ?><div class="empty"><?= icon('video',44) ?><p>لا توجد فيديوهات بعد. <a href="<?= e(url('create')) ?>" style="color:var(--brand-ink)">انشر فيديو</a></p></div><?php endif; ?>
<?php layout_bottom(); ?>
