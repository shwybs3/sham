<?php
if (!defined('YASSOTA')) require __DIR__ . '/config.php';
http_response_code(404);
$latest = fetch_feed('explore', '', 1);
layout_top(['title' => 'الصفحة غير موجودة | ' . setting('site_name', 'YASSOTA'), 'noindex' => true]);
?>
<div class="empty" style="padding:40px 20px 20px">
  <div style="font-family:var(--fd);font-size:64px;font-weight:800;background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent">404</div>
  <h1 style="font-size:22px;margin:8px 0">لم نجد هذه الصفحة</h1>
  <p>قد يكون المنشور حُذف أو أن الرابط غير صحيح.</p>
  <a class="btn btn-primary" href="<?= e(url('')) ?>">العودة للرئيسية</a>
</div>
<?php if ($latest): ?>
<h2 class="rel-h">منشورات قد تعجبك</h2>
<div class="ex-grid"><?= render_tiles($latest) ?></div>
<?php endif; ?>
<?php layout_bottom(); ?>
