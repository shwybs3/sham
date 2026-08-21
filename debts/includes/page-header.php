<?php
/**
 * includes/page-header.php — الترويسة العامة مع القائمة الجانبية
 */
$shopName    = $settings['shop_name'] ?? 'دفتر الدكان';
$bannerOn    = ($settings['banner_enabled'] ?? '0') === '1' && !empty($settings['banner_text']);
$chatEnabled = ($settings['chat_enabled'] ?? '1') === '1';
$pwaEnabled  = ($settings['pwa_enabled']  ?? '1') === '1';

// تحديد الصفحة النشطة
$currentFile = basename($_SERVER['PHP_SELF'] ?? '');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle ?? $shopName) ?></title>
<meta name="description" content="دفتر الديون الذكي — سجّل ديون الزبائن وتتبع المدفوعات بسهولة">
<meta name="theme-color" content="#0b0e13">
<meta name="robots" content="noindex, nofollow">
<?php if ($pwaEnabled): ?>
<link rel="manifest" href="manifest.json">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?= h($shopName) ?>">
<?php endif; ?>
<link rel="stylesheet" href="assets/css/style.css?v=<?= cssVersion() ?>">
<script>
if('serviceWorker' in navigator){
  navigator.serviceWorker.register('sw.js', {updateViaCache:'none'})
    .then(reg => {
      reg.addEventListener('updatefound', () => {
        const nw = reg.installing;
        nw.addEventListener('statechange', () => {
          if(nw.state === 'installed' && navigator.serviceWorker.controller){
            // إشعار التحديث
            const bar = document.createElement('div');
            bar.id = 'sw-update-bar';
            bar.style.cssText = 'position:fixed;bottom:0;inset-inline:0;background:#22c58e;color:#04150e;text-align:center;padding:12px 16px;font-weight:700;z-index:9999;display:flex;justify-content:center;align-items:center;gap:12px;';
            bar.innerHTML = '🔄 يوجد تحديث جديد للموقع <button onclick="nw.postMessage(\'SKIP_WAITING\');location.reload()" style="background:#04150e;color:#22c58e;border:none;padding:6px 14px;border-radius:50px;font-weight:700;cursor:pointer;">تحديث الآن</button>';
            document.body.appendChild(bar);
          }
        });
      });
    }).catch(()=>{});
  // استقبال رسالة التحديث
  navigator.serviceWorker.addEventListener('message', e => {
    if(e.data?.type === 'SW_UPDATED') location.reload();
  });
}
</script>
</head>
<body>

<!-- القائمة الجانبية + الغالق -->
<div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>
<nav class="sidebar" id="sidebar" role="dialog" aria-modal="true" aria-label="قائمة التنقل">
  <div class="sidebar-head">
    <div class="sidebar-brand">
      <?= icon('store', 20) ?> <?= h($shopName) ?>
    </div>
    <button class="sidebar-close" onclick="closeSidebar()" aria-label="إغلاق">✕</button>
  </div>
  <div class="sidebar-nav">
    <div class="sidebar-section">التنقل</div>
    <a class="sidebar-link <?= $currentFile === 'index.php' ? 'active' : '' ?>" href="index.php">
      <?= icon('home', 18) ?> الرئيسية
    </a>
    <?php if ($chatEnabled): ?>
    <a class="sidebar-link <?= $currentFile === 'community.php' ? 'active' : '' ?>" href="community.php">
      <?= icon('chat', 18) ?> المجموعة العامة
    </a>
    <?php endif; ?>
    <a class="sidebar-link <?= $currentFile === 'rates.php' ? 'active' : '' ?>" href="rates.php">
      <?= icon('trending-up', 18) ?> أسعار الصرف
    </a>
    <a class="sidebar-link <?= $currentFile === 'prices.php' ? 'active' : '' ?>" href="prices.php">
      <?= icon('tag', 18) ?> قائمة الأسعار
    </a>

    <div class="sidebar-divider"></div>
    <div class="sidebar-section">قانوني</div>
    <a class="sidebar-link <?= $currentFile === 'privacy-policy.php' ? 'active' : '' ?>" href="privacy-policy.php">
      <?= icon('shield', 18) ?> سياسة الخصوصية
    </a>
    <a class="sidebar-link <?= $currentFile === 'terms.php' ? 'active' : '' ?>" href="terms.php">
      <?= icon('receipt', 18) ?> شروط الاستخدام
    </a>
    <a class="sidebar-link" href="sitemap.php">
      <?= icon('search', 18) ?> خريطة الموقع
    </a>
    <a class="sidebar-link" href="rss.php">
      <?= icon('bell', 18) ?> RSS
    </a>

    <?php
    // عرض الملف الشخصي إن كان المستخدم مسجلاً
    if (isset($pdo)) {
        $sideUser = getCurrentSiteUser($pdo);
        if ($sideUser):
    ?>
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">حسابي</div>
    <a class="sidebar-link <?= $currentFile === 'profile.php' ? 'active' : '' ?>" href="profile.php">
      <?= icon('user', 18) ?> الملف الشخصي
    </a>
    <a class="sidebar-link" href="auth/logout.php">
      <?= icon('login', 18) ?> تسجيل الخروج
    </a>
    <?php
        endif;
    }
    ?>
  </div>
  <div class="sidebar-footer">
    © <?= date('Y') ?> <?= h($shopName) ?> · جميع الحقوق محفوظة
  </div>
</nav>

<!-- الهيدر -->
<header class="site-header">
  <div style="display:flex; align-items:center; gap:12px;">
    <button class="hamburger" id="hamburger-btn" onclick="openSidebar()" aria-label="القائمة" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
    <a class="brand" href="index.php">
      <div class="mark"><?= icon('store', 20) ?></div>
      <div class="brand-name"><?= h($shopName) ?></div>
    </a>
  </div>
  <div class="header-actions">
    <?php
    if (isset($pdo)) {
        $headerUser = getCurrentSiteUser($pdo);
        if ($headerUser):
    ?>
      <a class="btn btn-ghost btn-sm" href="profile.php">
        <?= icon('user', 15) ?> <?= h($headerUser['username'] ?: mb_substr($headerUser['display_name'], 0, 12, 'UTF-8')) ?>
      </a>
    <?php
        elseif ($chatEnabled):
    ?>
      <a class="btn btn-ghost btn-sm" href="login.php"><?= icon('login', 15) ?> دخول</a>
    <?php
        endif;
    }
    ?>
  </div>
</header>

<?php if ($bannerOn): ?>
<div class="marquee-banner">
  <div class="marquee-track">
    <span><?= h($settings['banner_text']) ?></span>
    <span><?= h($settings['banner_text']) ?></span>
  </div>
</div>
<?php endif; ?>

<script>
function openSidebar(){
  document.getElementById('sidebar').classList.add('open');
  document.getElementById('sidebar-overlay').classList.add('open');
  document.getElementById('hamburger-btn').classList.add('open');
  document.getElementById('hamburger-btn').setAttribute('aria-expanded','true');
  document.body.style.overflow='hidden';
}
function closeSidebar(){
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebar-overlay').classList.remove('open');
  document.getElementById('hamburger-btn').classList.remove('open');
  document.getElementById('hamburger-btn').setAttribute('aria-expanded','false');
  document.body.style.overflow='';
}
document.addEventListener('keydown', e => { if(e.key==='Escape') closeSidebar(); });
</script>
