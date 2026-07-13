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

$pdo->prepare("UPDATE apps SET downloads=downloads+1 WHERE id=?")->execute([$app['id']]);
$pdo->prepare("INSERT INTO page_events (event_type, app_id) VALUES ('download', ?)")->execute([$app['id']]);

$archivedVersion = null;
if (!empty($_GET['ver'])) {
    $verStmt = $pdo->prepare("SELECT * FROM app_versions WHERE id=? AND app_id=?");
    $verStmt->execute([(int)$_GET['ver'], $app['id']]);
    $archivedVersion = $verStmt->fetch();
}

$downloadSource = $app['download_source'] ?? 'playstore';
$hasLocalApk    = !empty($app['apk_path']) && in_array($downloadSource, ['apk','both'], true);

if ($archivedVersion && !empty($archivedVersion['download_url'])) {
    $url            = $archivedVersion['download_url'];
    $displayVersion = $archivedVersion['version'];
    $hasLocalApk    = false; // archived versions always use external URL
} else {
    $displayVersion = $app['version'];
    if ($hasLocalApk && $downloadSource === 'apk') {
        // Serve local APK directly — no redirect needed, we'll stream it
        $url = url('download.php?slug=' . urlencode($app['slug']) . '&apk=1');
    } else {
        $url = $app['download_url'];
        if ($mirror === 2 && $app['mirror2_url']) $url = $app['mirror2_url'];
        if ($mirror === 3 && $app['mirror3_url']) $url = $app['mirror3_url'];
    }
}

// Direct APK stream — triggered by ?apk=1 or when source=apk and the countdown fires
if (!empty($_GET['apk']) && $hasLocalApk) {
    serve_apk_file($app['apk_path'], $app['name'], $app['version'] ?: '1.0');
    // serve_apk_file() calls exit() internally
}

$hasLink = !empty($url) || $hasLocalApk;
if (!$hasLink) $url = '#';
if ($hasLocalApk && empty($url)) {
    $url = url('download.php?slug=' . urlencode($app['slug']) . '&apk=1');
}

$features    = json_decode($app['features']     ?? '[]', true) ?: [];
$screenshots = json_decode($app['screenshots']  ?? '[]', true) ?: [];
$tgUrl       = get_cfg($pdo, 'telegram_channel_url', '');
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

<header class="site-header">
  <a href="<?= h(url('')) ?>" class="logo">yass<span>ota</span></a>
  <nav class="header-nav">
    <a href="<?= h(url('')) ?>">الرئيسية</a>
    <a href="<?= h(app_url($app['slug'])) ?>">← صفحة التطبيق</a>
  </nav>
</header>

<div class="dl-page">
  <div class="dl-box">

    <!-- Top Ad -->
    <div class="ad-zone" style="margin-bottom:24px;min-height:60px">
      <?= ad_slot() ?>
    </div>

    <!-- App icon + name -->
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

    <?php if ($app['link_verified']): ?>
    <div class="verified-badge" style="justify-content:center;margin:10px auto">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
      رابط تم التحقق من سلامته بواسطة فريق yassota
    </div>
    <?php endif; ?>

    <!-- Short description -->
    <?php if (!empty($app['short_description'])): ?>
    <p class="dl-short-desc"><?= h($app['short_description']) ?></p>
    <?php endif; ?>

    <!-- Features list -->
    <?php if ($features): ?>
    <div class="dl-features">
      <div class="dl-features-title">✨ مميزات التطبيق</div>
      <ul class="dl-features-list">
        <?php foreach (array_slice($features, 0, 8) as $feat): ?>
        <li><?= h($feat) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <!-- Screenshots gallery -->
    <?php if ($screenshots): ?>
    <div class="dl-screenshots">
      <div class="dl-features-title" style="margin-bottom:10px">📱 صور من التطبيق</div>
      <div class="dl-screenshots-track">
        <?php foreach (array_slice($screenshots, 0, 6) as $shot): ?>
        <img src="<?= h(url($shot)) ?>" alt="<?= h($app['name']) ?> screenshot" class="dl-screenshot-img" loading="lazy">
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Early scroll-to-download button -->
    <?php if ($hasLink): ?>
    <button type="button" class="btn-scroll-early" onclick="document.getElementById('dl-timer-section').scrollIntoView({behavior:'smooth'})">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 3v12m0 0l-4-4m4 4l4-4"/><path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2"/></svg>
      انتقل لزر التحميل ↓
    </button>
    <?php endif; ?>

    <!-- Mid Ad -->
    <div class="ad-zone" style="margin:20px 0;min-height:60px">
      <?= ad_slot() ?>
    </div>

    <!-- ══ Download section ══ -->
    <div id="dl-timer-section">

      <?php if ($hasLink): ?>
      <!-- Circular Timer -->
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

      <!-- Manual download button (shown after countdown) -->
      <?php $manualUrl = $hasLocalApk ? h(url('download.php?slug='.urlencode($app['slug']).'&apk=1')) : h($url); ?>
      <a id="btn-manual" href="<?= $manualUrl ?>" class="btn-manual hidden" <?= $hasLocalApk ? 'download' : '' ?> data-hardnav="1">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
          <path d="M12 3v12m0 0l-4-4m4 4l4-4"/>
          <path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2"/>
        </svg>
        <?= $hasLocalApk ? 'تحميل APK مباشرةً' : 'ابدأ التحميل الآن' ?>
      </a>
      <div class="dl-manual-label" id="manual-label" style="display:none">
        لم يبدأ التحميل تلقائياً؟ اضغط الزر أعلاه
      </div>

      <?php else: ?>
      <div class="dl-status" style="background:rgba(255,68,102,.08);border:1px solid rgba(255,68,102,.25);border-radius:12px;padding:16px">
        <strong style="color:var(--danger)">رابط التحميل غير متوفر حالياً لهذا التطبيق</strong><br>
        لم يقم فريق yassota بإضافة رابط تحميل بعد لهذا التطبيق.
      </div>
      <?php endif; ?>

    </div><!-- /dl-timer-section -->

    <!-- Telegram subscribe button -->
    <?php if ($tgUrl): ?>
    <a href="<?= h($tgUrl) ?>" target="_blank" rel="nofollow noopener" class="btn-telegram-sub">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12l-6.869 4.326-2.96-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.829.941z"/></svg>
      اشترك في قناة تيليجرام yassota
    </a>
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

    <!-- Security / Hash Info -->
    <?php if ($hasLocalApk && $app['apk_hash_sha256']): ?>
    <details style="margin-top:16px;border:1px solid var(--border,rgba(0,0,0,.12));border-radius:10px;padding:12px 14px;text-align:right">
      <summary style="cursor:pointer;font-size:12px;font-weight:600;list-style:none;user-select:none">
        🔒 التحقق من سلامة الملف
      </summary>
      <div style="margin-top:10px;font-size:11px;line-height:1.8">
        <?php if ($app['apk_size_bytes']): ?>
        <div><span style="color:var(--muted)">الحجم الفعلي:</span> <strong><?= h(format_apk_size((int)$app['apk_size_bytes'])) ?></strong></div>
        <?php endif; ?>
        <?php if ($app['version']): ?>
        <div><span style="color:var(--muted)">الإصدار:</span> <strong><?= h($app['version']) ?></strong></div>
        <?php endif; ?>
        <div style="margin-top:8px">
          <div style="color:var(--muted);margin-bottom:2px">SHA-256 (انقر للنسخ):</div>
          <code style="font-size:10px;word-break:break-all;background:rgba(0,0,0,.04);padding:4px 6px;border-radius:5px;cursor:pointer;display:block"
                onclick="navigator.clipboard.writeText(this.textContent).then(()=>{this.style.color='green';setTimeout(()=>this.style.color='',1500)})"><?= h($app['apk_hash_sha256']) ?></code>
        </div>
        <?php if ($app['apk_hash_md5']): ?>
        <div style="margin-top:6px">
          <div style="color:var(--muted);margin-bottom:2px">MD5:</div>
          <code style="font-size:11px;background:rgba(0,0,0,.04);padding:3px 6px;border-radius:5px;cursor:pointer;display:block"
                onclick="navigator.clipboard.writeText(this.textContent).then(()=>{this.style.color='green';setTimeout(()=>this.style.color='',1500)})"><?= h($app['apk_hash_md5']) ?></code>
        </div>
        <?php endif; ?>
        <p style="margin:8px 0 0;color:var(--muted);font-size:10px">
          ملف APK مُستضاف مباشرةً على خوادم yassota — تم التحقق من سلامته وتطابق البصمة.
        </p>
      </div>
    </details>
    <?php endif; ?>

    <!-- Source option: both Play + APK -->
    <?php if ($hasLocalApk && $downloadSource === 'both' && $app['download_url']): ?>
    <div style="margin-top:12px;text-align:center;font-size:12px;color:var(--muted)">
      أو تحميل من
      <a href="<?= h($app['download_url']) ?>" target="_blank" rel="nofollow noopener" style="color:var(--accent,#0d6efd)">Google Play ↗</a>
    </div>
    <?php elseif (!$hasLocalApk && $app['download_url'] && str_contains($app['download_url'], 'play.google.com')): ?>
    <div style="margin-top:12px;text-align:center;font-size:12px">
      <a href="<?= h($app['download_url']) ?>" target="_blank" rel="nofollow noopener"
         style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border:1px solid var(--border,rgba(0,0,0,.15));border-radius:8px;color:var(--text,#0f172a);text-decoration:none;font-size:12px">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M3.18 23.76c.37.2.8.2 1.17-.01l13.64-7.84-2.86-2.86-11.95 10.71zm-1.01-21.3A2 2 0 002 3.52v16.96a2 2 0 00.17.95l.09.1 9.5-9.5v-.23l-9.59-9.34zm16.53 9.13l-2.72-1.57-3.13 3.14 3.13 3.13 2.74-1.58a2 2 0 000-3.12zM4.35.25L17.99 8.1l-2.87 2.86L4.08.37.9.25z"/></svg>
        تحميل من Google Play
      </a>
    </div>
    <?php endif; ?>

    <!-- Back link -->
    <a href="<?= h(app_url($app['slug'])) ?>"
       style="display:block;margin-top:18px;font-size:12px;color:var(--muted);text-align:center">
      ← العودة لصفحة <?= h($app['name']) ?>
    </a>

    <nav style="display:flex;flex-wrap:wrap;gap:12px;justify-content:center;margin-top:20px;font-size:11px">
      <a href="<?= h(url('privacy-policy')) ?>" style="color:var(--muted)">سياسة الخصوصية</a>
      <a href="<?= h(url('terms')) ?>" style="color:var(--muted)">شروط الاستخدام</a>
      <a href="<?= h(url('contact')) ?>" style="color:var(--muted)">اتصل بنا</a>
      <a href="<?= h(url('dmca')) ?>" style="color:var(--muted)">DMCA</a>
    </nav>
  </div>
</div>

<?php render_cookie_banner(); ?>

<script>
const DOWNLOAD_URL = <?= $hasLocalApk ? json_encode(url('download.php?slug='.urlencode($app['slug']).'&apk=1')) : json_encode($url) ?>;
const HAS_LINK = <?= $hasLink ? 'true' : 'false' ?>;
const IS_LOCAL_APK = <?= $hasLocalApk ? 'true' : 'false' ?>;
const TOTAL = 5;
let remaining = TOTAL;

const countEl   = document.getElementById('dl-count');
const statusEl  = document.getElementById('dl-status');
const secText   = document.getElementById('sec-text');
const progressEl= document.getElementById('dl-progress');
const ringProg  = document.getElementById('ring-prog');
const btnManual = document.getElementById('btn-manual');
const manualLbl = document.getElementById('manual-label');

const CIRC = 301.59;

function tick() {
  remaining--;
  const pct = (TOTAL - remaining) / TOTAL;
  countEl.textContent = remaining;
  if (secText) secText.textContent = remaining;
  progressEl.style.width = (pct * 100) + '%';
  ringProg.style.strokeDashoffset = CIRC * (1 - pct);

  if (remaining <= 0) {
    statusEl.innerHTML = '<strong style="color:var(--success)">✓ يبدأ التحميل الآن...</strong>';
    countEl.textContent = '✓';
    countEl.style.fontSize = '22px';

    const a = document.createElement('a');
    a.href = DOWNLOAD_URL; a.download = '';
    document.body.appendChild(a); a.click(); document.body.removeChild(a);

    setTimeout(() => {
      if (btnManual) btnManual.classList.remove('hidden');
      if (manualLbl) manualLbl.style.display = 'block';
    }, 2000);
  }
}

if (HAS_LINK && countEl) {
  const timer = setInterval(() => {
    if (remaining <= 0) { clearInterval(timer); return; }
    tick();
  }, 1000);
  if (ringProg) ringProg.style.strokeDashoffset = '0';
}
</script>
<script src="<?= h(asset_url('assets/js/main.js')) ?>"></script>

</body>
</html>
