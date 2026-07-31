<?php
// header.php — رأس الصفحة المشترك
if (!defined('SITE_NAME')) { require_once __DIR__ . '/config.php'; }

// تبديل اللغة — بدون إعادة تحميل كامل الصفحة
if (isset($_GET['lang']) && in_array($_GET['lang'], ['ar', 'en'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
    if (!empty($_GET['ajax'])) { echo 'ok'; exit; }
    $redir = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $redir); exit;
}
$lang = getLang();
$dir  = $lang === 'ar' ? 'rtl' : 'ltr';
$siteName = setting('site_name_ar', 'DarkStore');
$tagline  = t(setting('tagline_ar', 'متجر أدوات رقمية احترافية'), setting('tagline_en', 'Professional Digital Tools Store'));

$showPopup = setting('privacy_popup', '1') === '1' && !isset($_COOKIE['pv_ok']);

if (setting('maintenance', '0') === '1' && !isAdmin()) {
    http_response_code(503);
    ?>
<!DOCTYPE html><html lang="<?= $lang ?>" dir="<?= $dir ?>"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= clean($siteName) ?> — <?= t('الموقع تحت الصيانة','Under Maintenance') ?></title>
<link rel="stylesheet" href="<?= siteUrl('style.css') ?>"></head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;">
<div><h1><?= clean($siteName) ?></h1><p><?= t('الموقع تحت الصيانة حالياً، عودة قريباً.','Site is under maintenance, back soon.') ?></p></div>
</body></html>
    <?php exit;
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $dir ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? clean($pageTitle) . ' — ' : '' ?><?= clean($siteName) ?></title>
<meta name="description" content="<?= clean($tagline) ?>">
<meta name="robots" content="index, follow">
<link rel="canonical" href="<?= clean(siteUrl($_SERVER['REQUEST_URI'] ?? '')) ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= clean(isset($pageTitle) ? $pageTitle . ' — ' . $siteName : $siteName) ?>">
<meta property="og:description" content="<?= clean($tagline) ?>">
<meta property="og:url" content="<?= clean(siteUrl($_SERVER['REQUEST_URI'] ?? '')) ?>">
<meta name="twitter:card" content="summary">
<link rel="alternate" type="application/rss+xml" title="<?= clean($siteName) ?> RSS" href="<?= siteUrl('rss.php') ?>">
<link rel="icon" type="image/svg+xml" href="<?= siteUrl('favicon.svg') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= siteUrl('style.css') ?>">
<style>body{font-family:'Cairo',sans-serif;}</style>
</head>
<body>

<?php if ($showPopup): ?>
<!-- ===== Popup موافقة الشروط (يظهر عند كل فتح للموقع حتى تتم الموافقة) ===== -->
<div class="privacy-overlay" id="pvOverlay">
  <div class="privacy-modal">
    <div style="text-align:center;margin-bottom:18px;">
      <svg width="56" height="56" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="28" cy="28" r="28" fill="rgba(0,240,255,0.08)"/>
        <path d="M28 14C20.268 14 14 20.268 14 28C14 35.732 20.268 42 28 42C35.732 42 42 35.732 42 28C42 20.268 35.732 14 28 14ZM28 24C29.105 24 30 24.895 30 26V30C30 31.105 29.105 32 28 32C26.895 32 26 31.105 26 30V26C26 24.895 26.895 24 28 24ZM28 36C26.895 36 26 35.105 26 34C26 32.895 26.895 32 28 32C29.105 32 30 32.895 30 34C30 35.105 29.105 36 28 36Z" fill="#00f0ff"/>
      </svg>
    </div>
    <h2 style="text-align:center;"><?= t('مرحباً بك في المتجر','Welcome to the Store') ?></h2>
    <p style="text-align:center;"><?= t(
      'قبل المتابعة، يرجى قراءة والموافقة على سياسة الخصوصية وشروط الاستخدام وسياسة استرداد الأموال الخاصة بنا.',
      'Before continuing, please read and accept our Privacy Policy, Terms of Service, and Refund Policy.'
    ) ?></p>
    <div class="links" style="text-align:center;margin-bottom:8px;">
      <a href="privacy-policy.php" target="_blank"><?= t('سياسة الخصوصية','Privacy Policy') ?></a>
      &nbsp;|&nbsp;
      <a href="terms.php" target="_blank"><?= t('شروط الاستخدام','Terms of Service') ?></a>
      &nbsp;|&nbsp;
      <a href="refund-policy.php" target="_blank"><?= t('سياسة الاسترداد','Refund Policy') ?></a>
    </div>
    <div class="modal-btns">
      <button class="btn btn-primary" onclick="acceptPrivacy()">
        <i class="fa-solid fa-check"></i> <?= t('أوافق وأتابع','I Agree & Continue') ?>
      </button>
      <a href="https://www.google.com" class="btn btn-outline">
        <?= t('لا أوافق','Decline') ?>
      </a>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ===== HEADER ===== -->
<header class="header">
  <a href="index.php" class="logo"><?= clean($siteName) ?></a>
  <div class="header-right">
    <a href="<?= siteUrl('index.php') ?>?lang=<?= $lang === 'ar' ? 'en' : 'ar' ?>" class="lang-btn" onclick="return switchLang(event,'<?= $lang === 'ar' ? 'en' : 'ar' ?>')">
      <?= $lang === 'ar' ? 'EN' : 'عربي' ?>
    </a>
    <button class="btn-menu" onclick="toggleSidebar()" aria-label="menu">
      <i class="fa-solid fa-bars"></i>
    </button>
  </div>
</header>

<!-- ===== SIDEBAR ===== -->
<div class="sidebar-overlay" id="sbOverlay" onclick="toggleSidebar()"></div>
<aside class="sidebar" id="sidebar">
  <button class="sidebar-close" onclick="toggleSidebar()"><i class="fa-solid fa-xmark"></i></button>
  <div class="logo" style="margin-bottom:24px;"><?= clean($siteName) ?></div>
  <nav>
    <a href="index.php"><i class="fa-solid fa-house"></i> <?= t('الرئيسية','Home') ?></a>
    <a href="index.php#products"><i class="fa-solid fa-store"></i> <?= t('المنتجات','Products') ?></a>
    <a href="about.php"><i class="fa-solid fa-circle-info"></i> <?= t('من نحن','About Us') ?></a>
    <a href="contact.php"><i class="fa-solid fa-envelope"></i> <?= t('تواصل معنا','Contact') ?></a>
    <a href="privacy-policy.php"><i class="fa-solid fa-shield-halved"></i> <?= t('سياسة الخصوصية','Privacy Policy') ?></a>
    <a href="terms.php"><i class="fa-solid fa-file-contract"></i> <?= t('شروط الاستخدام','Terms of Service') ?></a>
    <a href="refund-policy.php"><i class="fa-solid fa-rotate-left"></i> <?= t('سياسة الاسترداد','Refund Policy') ?></a>
    <a href="cookie-policy.php"><i class="fa-solid fa-cookie-bite"></i> <?= t('سياسة الكوكيز','Cookie Policy') ?></a>
  </nav>
  <div style="margin-top:28px;padding-top:20px;border-top:1px solid var(--border);">
    <a href="<?= clean(setting('telegram','#')) ?>" target="_blank" rel="noopener" class="btn btn-primary w-full" style="justify-content:center;">
      <i class="fa-brands fa-telegram"></i> <?= t('تيليغرام','Telegram') ?>
    </a>
  </div>
</aside>
