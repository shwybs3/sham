<?php
require __DIR__ . '/config.php';
$site = setting('site_name', 'YASSOTA');
$ver = setting('apk_version'); $url = setting('apk_url'); $sha = setting('apk_sha256');
$size = setting('apk_size'); $min = setting('apk_min_android', '7.0 (API 24)'); $play = setting('play_url');
layout_top([
    'title' => 'تطبيق ' . $site . ' لأندرويد | تحميل APK',
    'description' => 'حمّل تطبيق ' . $site . ' الرسمي لأندرويد. تطبيق موقّع بمفتاح رسمي، بنفس حسابك وبياناتك على الموقع.',
    'canonical' => url('apps/yassota'),
    'jsonld' => [[
        '@context' => 'https://schema.org', '@type' => 'SoftwareApplication',
        'name' => $site, 'operatingSystem' => 'Android', 'applicationCategory' => 'SocialNetworkingApplication',
        'softwareVersion' => $ver ?: '1.0.0',
        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
    ]],
]);
?>
<div class="card" style="padding:26px;text-align:center;background:var(--grad);color:#fff;border:0;margin-bottom:16px">
  <span class="logo" style="width:70px;height:70px;font-size:38px;margin:0 auto 14px;background:rgba(255,255,255,.2)">Y</span>
  <h1 style="font-family:var(--fd);font-size:26px;margin:0 0 6px">تطبيق <?= e($site) ?> لأندرويد</h1>
  <p style="opacity:.95;margin:0 0 18px">نفس حسابك ومنشوراتك، بتجربة أسرع وإشعارات فورية.</p>
  <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
    <?php if ($url): ?><a class="btn" style="background:#fff;color:var(--brand-ink)" href="<?= e($url) ?>" rel="nofollow"><?= icon('download',20) ?> تحميل APK<?= $ver ? ' ' . e($ver) : '' ?></a><?php endif; ?>
    <?php if ($play): ?><a class="btn btn-outline" style="border-color:rgba(255,255,255,.6);color:#fff" href="<?= e($play) ?>">Google Play</a><?php endif; ?>
  </div>
</div>
<?php if (!$url): ?>
<div class="alert" style="background:var(--surface-2);color:var(--muted)"><?= icon('shield',16) ?> التطبيق قيد الإصدار. سيظهر رابط التحميل هنا فور رفع الحزمة الموقّعة، ويضبطه المشرف من لوحة الإدارة.</div>
<?php endif; ?>
<div class="card" style="padding:18px">
  <h2 style="margin:0 0 12px;font-family:var(--fd);font-size:18px">تفاصيل الحزمة</h2>
  <?php
  $rows = [['الإصدار', $ver ?: '—'], ['الحجم', $size ?: '—'], ['أقل إصدار أندرويد', $min], ['اسم الحزمة', 'com.yassota.app'], ['التوقيع', 'Release (APK Signature v2/v3)']];
  foreach ($rows as $r): ?>
    <div style="display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--line-2)"><span style="color:var(--muted)"><?= e($r[0]) ?></span><b dir="ltr"><?= e($r[1]) ?></b></div>
  <?php endforeach; ?>
  <?php if ($sha): ?>
  <div style="padding:9px 0"><div style="color:var(--muted);margin-bottom:4px">بصمة SHA-256 للشهادة</div><code style="display:block;background:var(--surface-2);padding:10px;border-radius:8px;font-size:12px;word-break:break-all;direction:ltr"><?= e($sha) ?></code></div>
  <?php endif; ?>
</div>
<div class="card" style="padding:18px;margin-top:14px">
  <h2 style="margin:0 0 10px;font-family:var(--fd);font-size:18px">لماذا تثق بهذا التطبيق؟</h2>
  <ul style="margin:0;padding-inline-start:20px;color:var(--muted);line-height:2">
    <li>موقّع بمفتاح إصدار رسمي (وليس Debug)، واسم الحزمة ثابت للتحديثات المستقبلية.</li>
    <li>يُحمّل من نطاقنا الرسمي عبر HTTPS.</li>
    <li>روابط منشوراتك تفتح داخل التطبيق تلقائياً عبر Android App Links.</li>
    <li>لا نطلب أذونات لا يحتاجها التطبيق.</li>
  </ul>
  <p style="color:var(--faint);font-size:12.5px;margin-top:10px">ملاحظة: عند التثبيت خارج Google Play قد يعرض أندرويد تحذيراً معتاداً لمصادر التثبيت — هذا طبيعي، والبصمة أعلاه تؤكد أصالة الحزمة.</p>
</div>
<?php layout_bottom(); ?>
