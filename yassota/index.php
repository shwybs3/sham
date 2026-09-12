<?php
require __DIR__ . '/config.php';
$me = current_user();
$rows = fetch_feed($me ? 'home' : 'explore', '', 1);

layout_top([
    'title' => setting('site_name', 'YASSOTA') . ' — ' . setting('site_desc'),
    'active' => 'home',
    'jsonld' => [[
        '@context' => 'https://schema.org', '@type' => 'WebSite',
        'name' => setting('site_name', 'YASSOTA'), 'url' => url(''),
        'potentialAction' => ['@type' => 'SearchAction', 'target' => url('search') . '?q={q}', 'query-input' => 'required name=q'],
    ]],
]);
?>
<?php if (!$me): ?>
<section class="card" style="padding:22px;margin-bottom:18px;background:var(--grad);color:#fff;border:0">
  <h1 style="font-family:var(--fd);font-size:24px;margin:0 0 6px">منصتك العربية للمشاركة والاكتشاف</h1>
  <p style="margin:0 0 16px;opacity:.95;font-size:14.5px">انشر صورك ومقالاتك، تابع من تحب، وكل منشور تنشره يصبح صفحة مستقلة تظهر في جوجل.</p>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a class="btn" style="background:#fff;color:var(--brand-ink)" href="<?= e(url('register')) ?>">أنشئ حسابك مجاناً</a>
    <a class="btn btn-outline" style="border-color:rgba(255,255,255,.5);color:#fff" href="<?= e(url('explore')) ?>">استكشف الآن</a>
  </div>
</section>
<?php endif; ?>

<div class="phead">
  <div><h1><?= $me ? 'الرئيسية' : 'أحدث المنشورات' ?></h1><p class="sub"><?= $me ? 'منشورات ممن تتابعهم وأحدث ما يُنشر' : 'اكتشف ما يشاركه مجتمع يسوتا' ?></p></div>
</div>

<div class="feed" data-feed="<?= $me ? 'home' : 'explore' ?>">
<?php if ($rows): echo render_cards($rows); else: ?>
  <div class="empty"><?= icon('compass', 44) ?><p>لا توجد منشورات بعد. <a href="<?= e(url('create')) ?>" style="color:var(--brand-ink)">كن أول من ينشر!</a></p></div>
<?php endif; ?>
</div>

<?php layout_bottom(); ?>
