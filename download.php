<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$slug   = trim($_GET['slug'] ?? '');
$id     = (int)($_GET['id'] ?? 0);
$mirror = (int)($_GET['m'] ?? 1);

if ($slug !== '') {
    $stmt = $pdo->prepare("SELECT * FROM apps WHERE slug=? AND status='published'");
    $stmt->execute([$slug]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM apps WHERE id=? AND status='published'");
    $stmt->execute([$id]);
}
$app = $stmt->fetch();

if (!$app) {
    http_response_code(404);
    echo '<html dir="rtl"><body style="font-family:sans-serif;background:#f5f7fb;color:#0f172a;display:flex;align-items:center;justify-content:center;height:100vh"><p>التطبيق غير موجود</p></body></html>';
    exit;
}

// سجّل التحميل
$pdo->prepare("UPDATE apps SET downloads=downloads+1 WHERE id=?")->execute([$app['id']]);

// حدد الرابط — يدعم تحميل إصدار قديم مؤرشف عبر ?ver=
$archivedVersion = null;
if (!empty($_GET['ver'])) {
    $verStmt = $pdo->prepare("SELECT * FROM app_versions WHERE id=? AND app_id=?");
    $verStmt->execute([(int)$_GET['ver'], $app['id']]);
    $archivedVersion = $verStmt->fetch();
}

if ($archivedVersion && !empty($archivedVersion['download_url'])) {
    $url = $archivedVersion['download_url'];
    $displayVersion = $archivedVersion['version'];
} else {
    $url = $app['download_url'];
    if ($mirror === 2 && $app['mirror2_url']) $url = $app['mirror2_url'];
    if ($mirror === 3 && $app['mirror3_url']) $url = $app['mirror3_url'];
    $displayVersion = $app['version'];
}
$hasLink = !empty($url);
if (!$hasLink) $url = '#';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <?= nav_guard_script() ?>
  <meta charset="UTF-8">
  <?= head_extras($pdo) ?>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title>تحميل <?= h($app['name']) ?> — yassota</title>
  <meta name="robots" content="noindex,follow">
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/main.css')) ?>">
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/download.css')) ?>">
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5506877998492189"
     crossorigin="anonymous"></script>
</head>
<body>

<!-- Header minimal (all links absolute — this page is served from a nested
     /{slug}/download URL, so plain relative paths would resolve wrong) -->
<header class="site-header">
  <a href="<?= h(url('')) ?>" class="logo">yass<span>ota</span></a>
  <nav class="header-nav">
    <a href="<?= h(url('')) ?>">الرئيسية</a>
    <a href="<?= h(app_url($app['slug'])) ?>">← صفحة التطبيق</a>
  </nav>
</header>

<div class="dl-page">
  <div class="dl-box">

    <!-- Ad top -->
    <div class="ad-zone" style="margin-bottom:24px;min-height:60px">
      <?= ad_slot() ?>
    </div>

    <!-- App icon -->
    <?php if ($app['icon_path']): ?>
      <img src="<?= h(url($app['icon_path'])) ?>" alt="<?= h($app['name']) ?>" class="dl-app-icon">
    <?php else: ?>
      <div class="dl-icon-placeholder">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--cyan)" stroke-width="1.5">
          <rect x="5" y="2" width="14" height="20" rx="3"/>
          <path d="M9 7h6M9 11h6M9 15h4"/>
        </svg>
      </div>
    <?php endif; ?>

    <div class="dl-app-name"><?= h($app['name']) ?></div>
    <div class="dl-app-version">
      <?php if ($displayVersion): ?>
        <span style="font-family:var(--f-mono)">v<?= h($displayVersion) ?></span>
      <?php endif; ?>
      <?php if ($app['size_mb']): ?>
        · <span style="font-family:var(--f-mono)"><?= h($app['size_mb']) ?> MB</span>
      <?php endif; ?>
    </div>

    <?php if ($app['vt_status'] === 'clean'): ?>
    <div style="display:inline-flex;align-items:center;gap:6px;background:rgba(22,163,74,.1);border:1px solid rgba(22,163,74,.25);color:var(--success);font-size:12px;padding:6px 14px;border-radius:40px;margin-bottom:16px">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
      رابط آمن — فحصه <?= (int)$app['vt_total_engines'] ?> محرك حماية عبر VirusTotal
    </div>
    <?php elseif ($app['vt_status'] === 'flagged'): ?>
    <div style="display:inline-flex;align-items:center;gap:6px;background:rgba(220,38,38,.1);border:1px solid rgba(220,38,38,.25);color:var(--danger);font-size:12px;padding:6px 14px;border-radius:40px;margin-bottom:16px">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 9v4m0 4h.01M10.3 3.86l-8.2 14.2A2 2 0 004 21h16a2 2 0 001.9-2.94l-8.2-14.2a2 2 0 00-3.4 0z"/></svg>
      تنبيه: <?= (int)$app['vt_malicious'] ?> من <?= (int)$app['vt_total_engines'] ?> محرك فحص أشار لهذا الرابط
    </div>
    <?php endif; ?>

    <!-- Circular Timer -->
    <?php if ($hasLink): ?>
    <div class="dl-timer-wrap">
      <svg width="110" height="110" class="dl-ring" viewBox="0 0 110 110">
        <circle class="dl-ring-bg" cx="55" cy="55" r="48" fill="none" stroke-width="4"/>
        <circle class="dl-ring-prog" id="ring-prog" cx="55" cy="55" r="48" fill="none" stroke-width="4"
          stroke-dasharray="301.59" stroke-dashoffset="0" stroke-linecap="round"/>
      </svg>
      <div class="dl-count" id="dl-count">10</div>
    </div>

    <div class="dl-status" id="dl-status">
      <strong style="color:var(--cyan)">جاري تجهيز التحميل...</strong><br>
      سيبدأ التحميل تلقائياً خلال <span id="sec-text">10</span> ثوانٍ
    </div>

    <!-- Progress bar -->
    <div class="dl-progress-bar">
      <div class="dl-progress-fill" id="dl-progress"></div>
    </div>
    <?php else: ?>
    <div class="dl-status" style="background:rgba(255,68,102,.08);border:1px solid rgba(255,68,102,.25);border-radius:12px;padding:16px">
      <strong style="color:var(--danger)">رابط التحميل غير متوفر حالياً لهذا التطبيق</strong><br>
      لم يقم فريق yassota بإضافة رابط تحميل بعد لهذا التطبيق. تابع صفحة التطبيق لاحقاً أو تصفح تطبيقات أخرى.
    </div>
    <?php endif; ?>


    <!-- Ad mid -->
    <div class="ad-zone" style="margin-bottom:20px;min-height:60px">
      <?= ad_slot() ?>
    </div>

    <!-- Manual download button (hidden until countdown ends) -->
    <?php if ($hasLink): ?>
    <a id="btn-manual" href="<?= h($url) ?>" class="btn-manual hidden" download data-hardnav="1">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
        <path d="M12 3v12m0 0l-4-4m4 4l4-4"/>
        <path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2"/>
      </svg>
      ابدأ التحميل الآن
    </a>
    <div class="dl-manual-label" id="manual-label" style="display:none">
      لم يبدأ التحميل تلقائياً؟ اضغط الزر أعلاه
    </div>
    <?php endif; ?>

    <!-- Mirror links -->
    <?php if ($app['mirror2_url'] || $app['mirror3_url']): ?>
    <div class="dl-mirrors" style="margin-top:16px">
      <span style="font-size:12px;color:var(--muted)">روابط بديلة:</span>
      <?php if ($app['mirror2_url']): ?>
        <a href="<?= h(download_url($app['slug'], 2)) ?>" class="dl-mirror-btn" data-hardnav="1">مرآة 2</a>
      <?php endif; ?>
      <?php if ($app['mirror3_url']): ?>
        <a href="<?= h(download_url($app['slug'], 3)) ?>" class="dl-mirror-btn" data-hardnav="1">مرآة 3</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Back link -->
    <a href="<?= h(app_url($app['slug'])) ?>"
       style="display:block;margin-top:18px;font-size:12px;color:var(--muted);text-align:center">
      ← العودة لصفحة <?= h($app['name']) ?>
    </a>

    <nav style="display:flex;flex-wrap:wrap;gap:12px;justify-content:center;margin-top:20px;font-size:11px">
      <a href="<?= h(url('privacy-policy.php')) ?>" style="color:var(--muted)">سياسة الخصوصية</a>
      <a href="<?= h(url('terms.php')) ?>" style="color:var(--muted)">شروط الاستخدام</a>
      <a href="<?= h(url('contact.php')) ?>" style="color:var(--muted)">اتصل بنا</a>
      <a href="<?= h(url('dmca.php')) ?>" style="color:var(--muted)">DMCA</a>
    </nav>
  </div>
</div>

<?php render_cookie_banner(url('cookie-policy.php')); ?>

<script>
const DOWNLOAD_URL = <?= json_encode($url) ?>;
const HAS_LINK = <?= $hasLink ? 'true' : 'false' ?>;
const TOTAL = 10;
let remaining = TOTAL;

const countEl   = document.getElementById('dl-count');
const statusEl  = document.getElementById('dl-status');
const secText   = document.getElementById('sec-text');
const progressEl= document.getElementById('dl-progress');
const ringProg  = document.getElementById('ring-prog');
const btnManual = document.getElementById('btn-manual');
const manualLbl = document.getElementById('manual-label');

// Circumference of the ring (r=48): 2π*48 ≈ 301.59
const CIRC = 301.59;

function tick() {
  remaining--;
  const pct = (TOTAL - remaining) / TOTAL;

  countEl.textContent = remaining;
  if (secText) secText.textContent = remaining;
  progressEl.style.width = (pct * 100) + '%';
  ringProg.style.strokeDashoffset = CIRC * (1 - pct);

  if (remaining <= 0) {
    // Launch download
    statusEl.innerHTML = '<strong style="color:var(--success)">✓ يبدأ التحميل الآن...</strong>';
    countEl.textContent = '✓';
    countEl.style.fontSize = '22px';

    const a = document.createElement('a');
    a.href = DOWNLOAD_URL;
    a.download = '';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

    // Show manual button after 2s as fallback
    setTimeout(() => {
      btnManual.classList.remove('hidden');
      manualLbl.style.display = 'block';
    }, 2000);
  }
}

// Start ticking every second — only when there's an actual link to download
if (HAS_LINK && countEl) {
  const timer = setInterval(() => {
    if (remaining <= 0) { clearInterval(timer); return; }
    tick();
  }, 1000);

  // Initialize ring
  if (ringProg) ringProg.style.strokeDashoffset = '0';
}
</script>
<script src="<?= h(asset_url('assets/js/main.js')) ?>"></script>

</body>
</html>
