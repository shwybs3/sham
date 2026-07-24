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
    require __DIR__ . '/404.php';
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
    $hasLocalApk    = false;
} else {
    $displayVersion = $app['version'];
    if ($hasLocalApk && $downloadSource === 'apk') {
        $url = url('download.php?slug=' . urlencode($app['slug']) . '&apk=1');
    } else {
        $url = $app['download_url'];
        if ($mirror === 2 && $app['mirror2_url']) $url = $app['mirror2_url'];
        if ($mirror === 3 && $app['mirror3_url']) $url = $app['mirror3_url'];
    }
}

// Direct APK stream
if (!empty($_GET['apk']) && $hasLocalApk) {
    serve_apk_file($app['apk_path'], $app['name'], $app['version'] ?: '1.0');
}

$hasLink = !empty($url) || $hasLocalApk;
if (!$hasLink) $url = '#';
if ($hasLocalApk && empty($url)) {
    $url = url('download.php?slug=' . urlencode($app['slug']) . '&apk=1');
}

$screenshots = json_decode($app['screenshots'] ?? '[]', true) ?: [];
$tgUrl       = get_cfg($pdo, 'telegram_channel_url', '');
$countdownSecs = max(3, min(30, (int)(get_cfg($pdo, 'download_countdown_secs', '7') ?: 7)));
$customAdCode  = get_cfg($pdo, 'download_custom_ad_code', '');

// Related apps (same category)
$relatedApps = [];
if (!empty($app['category_id'])) {
    $relStmt = $pdo->prepare("SELECT id,name,slug,icon_path,short_description FROM apps WHERE category_id=? AND id!=? AND status='published' ORDER BY downloads DESC LIMIT 6");
    $relStmt->execute([$app['category_id'], $app['id']]);
    $relatedApps = $relStmt->fetchAll();
}

$catName = '';
if (!empty($app['category_id'])) {
    $r = $pdo->prepare("SELECT name FROM categories WHERE id=?");
    $r->execute([$app['category_id']]);
    $catName = $r->fetchColumn() ?: '';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <?= nav_guard_script() ?>
  <meta charset="UTF-8">
  <?= head_extras($pdo) ?>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title>تحميل <?= h($app['name']) ?><?= $displayVersion ? ' v'.h($displayVersion) : '' ?> للأندرويد — Tenzil</title>
  <meta name="robots" content="noindex,follow">
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/main.css')) ?>">
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/download.css')) ?>">
  <?php if ($customAdCode): ?>
  <script><?= $customAdCode ?></script>
  <?php endif; ?>
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5506877998492189" crossorigin="anonymous"></script>
</head>
<body>

<header class="site-header">
  <a href="<?= h(url('')) ?>" class="logo">yass<span>ota</span></a>
  <nav class="header-nav">
    <a href="<?= h(url('')) ?>">الرئيسية</a>
    <a href="<?= h(app_url($app['slug'])) ?>">← صفحة التطبيق</a>
  </nav>
</header>

<div class="dlp-wrap">

  <!-- ── Steps indicator ── -->
  <div class="dlp-steps">
    <div class="dlp-step done" id="step1">
      <div class="dlp-step-dot">✓</div>
      <div class="dlp-step-label">التحقق من الرابط</div>
    </div>
    <div class="dlp-step-line"></div>
    <div class="dlp-step active" id="step2">
      <div class="dlp-step-dot">2</div>
      <div class="dlp-step-label">تجهيز التحميل</div>
    </div>
    <div class="dlp-step-line"></div>
    <div class="dlp-step" id="step3">
      <div class="dlp-step-dot">3</div>
      <div class="dlp-step-label">التحميل جاهز</div>
    </div>
  </div>

  <!-- ── App hero card ── -->
  <div class="dlp-hero">
    <div class="dlp-hero-icon">
      <?php if ($app['icon_path']): ?>
        <img src="<?= h(url($app['icon_path'])) ?>" alt="<?= h($app['name']) ?>" class="dlp-icon-img">
      <?php else: ?>
        <div class="dlp-icon-placeholder">
          <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="var(--cyan)" stroke-width="1.5">
            <rect x="5" y="2" width="14" height="20" rx="3"/>
            <path d="M9 7h6M9 11h6M9 15h4"/>
          </svg>
        </div>
      <?php endif; ?>
    </div>
    <div class="dlp-hero-info">
      <h1 class="dlp-app-name"><?= h($app['name']) ?></h1>
      <div class="dlp-app-meta">
        <?php if ($displayVersion): ?>
          <span class="dlp-meta-chip">v<?= h($displayVersion) ?></span>
        <?php endif; ?>
        <?php if ($app['size_mb']): ?>
          <span class="dlp-meta-chip"><?= h($app['size_mb']) ?> MB</span>
        <?php endif; ?>
        <?php if ($catName): ?>
          <span class="dlp-meta-chip"><?= h($catName) ?></span>
        <?php endif; ?>
      </div>
      <?php if ($app['link_verified']): ?>
      <div class="dlp-verified">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
        رابط تم التحقق منه بواسطة فريق Tenzil
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── Ad slot (top) ── -->
  <?php $topAd = ad_slot(); if ($topAd): ?>
  <div class="dlp-ad-zone"><?= $topAd ?></div>
  <?php endif; ?>

  <!-- ── Countdown section ── -->
  <div class="dlp-countdown-card" id="dl-timer-section">

    <?php if ($hasLink): ?>

    <div class="dlp-countdown-header">
      <div class="dlp-pulse-dot"></div>
      <span id="dl-status-text">جاري تجهيز رابط التحميل الآمن...</span>
    </div>

    <!-- Ring timer -->
    <div class="dlp-ring-wrap">
      <svg class="dlp-ring-svg" viewBox="0 0 120 120">
        <circle class="dlp-ring-bg" cx="60" cy="60" r="52"/>
        <circle class="dlp-ring-prog" id="ring-prog" cx="60" cy="60" r="52"
          stroke-dasharray="326.73" stroke-dashoffset="326.73"/>
      </svg>
      <div class="dlp-ring-inner">
        <div class="dlp-count" id="dl-count"><?= $countdownSecs ?></div>
        <div class="dlp-count-label">ثانية</div>
      </div>
    </div>

    <!-- Linear progress -->
    <div class="dlp-progress-bar">
      <div class="dlp-progress-fill" id="dl-progress"></div>
    </div>

    <!-- Download button (hidden until ready) -->
    <?php $manualUrl = $hasLocalApk ? h(url('download.php?slug='.urlencode($app['slug']).'&apk=1')) : h($url); ?>
    <a id="btn-manual" href="<?= $manualUrl ?>" class="dlp-btn-download hidden" <?= $hasLocalApk ? 'download' : '' ?> data-hardnav="1">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
        <path d="M12 3v12m0 0l-4-4m4 4l4-4"/>
        <path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2"/>
      </svg>
      <?= $hasLocalApk ? 'تحميل APK مباشرةً' : 'اضغط هنا لبدء التحميل' ?>
    </a>
    <p class="dlp-manual-hint" id="manual-label" style="display:none">
      لم يبدأ التحميل تلقائياً؟ اضغط الزر أعلاه
    </p>

    <?php else: ?>

    <div class="dlp-no-link">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
      <strong>رابط التحميل غير متوفر حالياً</strong>
      <p>سيقوم فريق Tenzil بإضافة رابط التحميل قريباً. تابع صفحة التطبيق للتحديثات.</p>
    </div>

    <?php endif; ?>

  </div><!-- /countdown -->

  <!-- ── Trust badges ── -->
  <div class="dlp-trust">
    <div class="dlp-trust-item">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      رابط آمن 100%
    </div>
    <div class="dlp-trust-item">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
      خالٍ من الفيروسات
    </div>
    <div class="dlp-trust-item">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V9l-7-7z"/><path d="M13 2v7h7"/></svg>
      ملف APK أصلي
    </div>
    <div class="dlp-trust-item">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
      تحميل سريع
    </div>
  </div>

  <!-- ── App meta grid ── -->
  <?php if ($displayVersion || $app['size_mb'] || $app['developer'] || $catName): ?>
  <div class="dlp-meta-grid">
    <?php if ($displayVersion): ?>
    <div class="dlp-meta-cell">
      <div class="dlp-meta-key">الإصدار</div>
      <div class="dlp-meta-val"><?= h($displayVersion) ?></div>
    </div>
    <?php endif; ?>
    <?php if ($app['size_mb']): ?>
    <div class="dlp-meta-cell">
      <div class="dlp-meta-key">الحجم</div>
      <div class="dlp-meta-val"><?= h($app['size_mb']) ?> MB</div>
    </div>
    <?php endif; ?>
    <?php if ($catName): ?>
    <div class="dlp-meta-cell">
      <div class="dlp-meta-key">التصنيف</div>
      <div class="dlp-meta-val"><?= h($catName) ?></div>
    </div>
    <?php endif; ?>
    <?php if ($app['developer']): ?>
    <div class="dlp-meta-cell">
      <div class="dlp-meta-key">المطوّر</div>
      <div class="dlp-meta-val"><?= h($app['developer']) ?></div>
    </div>
    <?php endif; ?>
    <div class="dlp-meta-cell">
      <div class="dlp-meta-key">النظام</div>
      <div class="dlp-meta-val">Android</div>
    </div>
    <div class="dlp-meta-cell">
      <div class="dlp-meta-key">الترخيص</div>
      <div class="dlp-meta-val">مجاني</div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── Screenshots ── -->
  <?php if ($screenshots): ?>
  <div class="dlp-screenshots">
    <div class="dlp-section-title">صور من التطبيق</div>
    <div class="dlp-screenshots-track">
      <?php foreach (array_slice($screenshots, 0, 6) as $shot): ?>
      <img src="<?= h(url($shot)) ?>" alt="<?= h($app['name']) ?> screenshot" class="dlp-screenshot" loading="lazy">
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── Mid AdSense ── -->
  <?php $midAd = ad_slot(); if ($midAd): ?>
  <div class="dlp-ad-zone"><?= $midAd ?></div>
  <?php endif; ?>

  <!-- ── Mirror links ── -->
  <?php if ($app['mirror2_url'] || $app['mirror3_url']): ?>
  <div class="dlp-mirrors">
    <span class="dlp-mirrors-label">روابط بديلة للتحميل:</span>
    <?php if ($app['mirror2_url']): ?>
      <a href="<?= h(download_url($app['slug'], 2)) ?>" class="dlp-mirror-btn" data-hardnav="1">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12m0 0l-4-4m4 4l4-4"/><path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2"/></svg>
        مرآة 2
      </a>
    <?php endif; ?>
    <?php if ($app['mirror3_url']): ?>
      <a href="<?= h(download_url($app['slug'], 3)) ?>" class="dlp-mirror-btn" data-hardnav="1">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12m0 0l-4-4m4 4l4-4"/><path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2"/></svg>
        مرآة 3
      </a>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- ── SHA-256 / hash details ── -->
  <?php if ($hasLocalApk && $app['apk_hash_sha256']): ?>
  <details class="dlp-hash-details">
    <summary>
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
      التحقق من سلامة الملف (SHA-256)
    </summary>
    <div class="dlp-hash-body">
      <?php if ($app['apk_size_bytes']): ?>
      <div><span>الحجم الفعلي:</span> <strong><?= h(format_apk_size((int)$app['apk_size_bytes'])) ?></strong></div>
      <?php endif; ?>
      <div style="margin-top:8px">
        <div style="color:var(--muted);margin-bottom:4px;font-size:11px">SHA-256 (انقر للنسخ):</div>
        <code class="dlp-hash-code" onclick="navigator.clipboard.writeText(this.textContent).then(()=>{this.dataset.copied='1';setTimeout(()=>delete this.dataset.copied,1500)})"><?= h($app['apk_hash_sha256']) ?></code>
      </div>
      <?php if ($app['apk_hash_md5']): ?>
      <div style="margin-top:8px">
        <div style="color:var(--muted);margin-bottom:4px;font-size:11px">MD5:</div>
        <code class="dlp-hash-code" onclick="navigator.clipboard.writeText(this.textContent).then(()=>{this.dataset.copied='1';setTimeout(()=>delete this.dataset.copied,1500)})"><?= h($app['apk_hash_md5']) ?></code>
      </div>
      <?php endif; ?>
      <p style="margin:10px 0 0;color:var(--muted);font-size:11px">ملف APK مُستضاف مباشرةً على خوادم Tenzil — تم التحقق من سلامته.</p>
    </div>
  </details>
  <?php endif; ?>

  <!-- ── Google Play fallback ── -->
  <?php if ($hasLocalApk && $downloadSource === 'both' && $app['download_url']): ?>
  <div class="dlp-gplay">
    أو
    <a href="<?= h($app['download_url']) ?>" target="_blank" rel="nofollow noopener">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M3.18 23.76c.37.2.8.2 1.17-.01l13.64-7.84-2.86-2.86-11.95 10.71zm-1.01-21.3A2 2 0 002 3.52v16.96a2 2 0 00.17.95l.09.1 9.5-9.5v-.23l-9.59-9.34zm16.53 9.13l-2.72-1.57-3.13 3.14 3.13 3.13 2.74-1.58a2 2 0 000-3.12zM4.35.25L17.99 8.1l-2.87 2.86L4.08.37.9.25z"/></svg>
      تحميل من Google Play
    </a>
  </div>
  <?php elseif (!$hasLocalApk && !empty($app['download_url']) && str_contains($app['download_url'], 'play.google.com')): ?>
  <div class="dlp-gplay">
    <a href="<?= h($app['download_url']) ?>" target="_blank" rel="nofollow noopener">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M3.18 23.76c.37.2.8.2 1.17-.01l13.64-7.84-2.86-2.86-11.95 10.71zm-1.01-21.3A2 2 0 002 3.52v16.96a2 2 0 00.17.95l.09.1 9.5-9.5v-.23l-9.59-9.34zm16.53 9.13l-2.72-1.57-3.13 3.14 3.13 3.13 2.74-1.58a2 2 0 000-3.12zM4.35.25L17.99 8.1l-2.87 2.86L4.08.37.9.25z"/></svg>
      تحميل من Google Play ↗
    </a>
  </div>
  <?php endif; ?>

  <!-- ── Telegram subscribe ── -->
  <?php if ($tgUrl): ?>
  <a href="<?= h($tgUrl) ?>" target="_blank" rel="nofollow noopener" class="dlp-tg-btn">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12l-6.869 4.326-2.96-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.829.941z"/></svg>
    اشترك في قناة Tenzil على تيليجرام
  </a>
  <?php endif; ?>

  <!-- ── Related apps ── -->
  <?php if ($relatedApps): ?>
  <div class="dlp-related">
    <div class="dlp-section-title">تطبيقات مشابهة قد تعجبك</div>
    <div class="dlp-related-grid">
      <?php foreach ($relatedApps as $r): ?>
      <a href="<?= h(app_url($r['slug'])) ?>" class="dlp-related-card">
        <?php if ($r['icon_path']): ?>
          <img src="<?= h(url($r['icon_path'])) ?>" alt="<?= h($r['name']) ?>" class="dlp-related-icon" loading="lazy">
        <?php else: ?>
          <div class="dlp-related-icon-ph">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--cyan)" stroke-width="1.5"><rect x="5" y="2" width="14" height="20" rx="3"/><path d="M9 7h6M9 11h6M9 15h4"/></svg>
          </div>
        <?php endif; ?>
        <div class="dlp-related-name"><?= h($r['name']) ?></div>
        <?php if (!empty($r['short_description'])): ?>
        <div class="dlp-related-desc"><?= h(mb_substr($r['short_description'], 0, 40)) ?>...</div>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── Footer nav ── -->
  <nav class="dlp-footer-nav">
    <a href="<?= h(app_url($app['slug'])) ?>">← صفحة <?= h($app['name']) ?></a>
    <a href="<?= h(url('privacy-policy')) ?>">سياسة الخصوصية</a>
    <a href="<?= h(url('terms')) ?>">الشروط</a>
    <a href="<?= h(url('contact')) ?>">اتصل بنا</a>
    <a href="<?= h(url('dmca')) ?>">DMCA</a>
  </nav>

</div><!-- /dlp-wrap -->

<?php render_cookie_banner(); ?>

<script>
const DOWNLOAD_URL = <?= $hasLocalApk ? json_encode(url('download.php?slug='.urlencode($app['slug']).'&apk=1')) : json_encode($url) ?>;
const HAS_LINK     = <?= $hasLink ? 'true' : 'false' ?>;
const IS_LOCAL_APK = <?= $hasLocalApk ? 'true' : 'false' ?>;
const TOTAL        = <?= $countdownSecs ?>;
const CIRC         = 326.73;

let remaining = TOTAL;

const countEl    = document.getElementById('dl-count');
const statusText = document.getElementById('dl-status-text');
const progressEl = document.getElementById('dl-progress');
const ringProg   = document.getElementById('ring-prog');
const btnManual  = document.getElementById('btn-manual');
const manualLbl  = document.getElementById('manual-label');
const step2      = document.getElementById('step2');
const step3      = document.getElementById('step3');

function tick() {
  remaining--;
  const pct = (TOTAL - remaining) / TOTAL;
  if (countEl) countEl.textContent = remaining;
  if (progressEl) progressEl.style.width = (pct * 100) + '%';
  if (ringProg) ringProg.style.strokeDashoffset = CIRC * (1 - pct);

  if (remaining <= 0) {
    // Step 3 active
    if (step2) { step2.classList.remove('active'); step2.classList.add('done'); step2.querySelector('.dlp-step-dot').textContent = '✓'; }
    if (step3) { step3.classList.add('active'); }

    if (statusText) statusText.innerHTML = '<strong style="color:var(--success)">✓ جاهز للتحميل!</strong>';
    if (countEl) { countEl.textContent = '✓'; countEl.style.fontSize = '28px'; }

    // Trigger download
    const a = document.createElement('a');
    a.href = DOWNLOAD_URL;
    if (IS_LOCAL_APK) a.download = '';
    document.body.appendChild(a); a.click(); document.body.removeChild(a);

    // Show manual button after 2s
    setTimeout(() => {
      if (btnManual)  { btnManual.classList.remove('hidden'); btnManual.scrollIntoView({behavior:'smooth', block:'center'}); }
      if (manualLbl)  manualLbl.style.display = 'block';
    }, 2000);
  }
}

if (HAS_LINK && countEl) {
  // kick off immediately
  if (ringProg) ringProg.style.strokeDashoffset = CIRC;
  const timer = setInterval(() => {
    if (remaining <= 0) { clearInterval(timer); return; }
    tick();
  }, 1000);
}
</script>
<script src="<?= h(asset_url('assets/js/main.js')) ?>"></script>
</body>
</html>
