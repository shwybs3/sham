<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

// Block banned IPs before any work (admin IP is auto-exempt inside evil_check_ban)
evil_check_ban($pdo);
waf_check($pdo);

// ── CAPTCHA verify AJAX ─────────────────────────────────────────────────────
if (!empty($_GET['ajax']) && $_GET['ajax'] === 'verify_captcha') {
    header('Content-Type: application/json; charset=utf-8');
    $token = trim($_POST['token'] ?? '');
    $type  = trim($_POST['type']  ?? 'v2');
    $ok = false;
    if ($type === 'turnstile') {
        $ok = captcha_verify_turnstile($pdo, $token);
    } elseif ($type === 'v3') {
        $ok = captcha_verify_v3($pdo, $token);
    } else {
        $ok = captcha_verify_v2($pdo, $token);
    }
    if ($ok) captcha_mark_solved($pdo);
    echo json_encode(['ok' => $ok]);
    exit;
}

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

if (!evil_is_admin_ip()) {
    $pdo->prepare("UPDATE apps SET downloads=downloads+1 WHERE id=?")->execute([$app['id']]);
    $pdo->prepare("INSERT INTO page_events (event_type, app_id) VALUES ('download', ?)")->execute([$app['id']]);
}

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
$countdownSecs = max(3, min(30, (int)(get_cfg($pdo, 'download_countdown_secs', '12') ?: 12)));
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

// Detect if CAPTCHA is needed for suspicious visitors (full-page overlay)
$captchaType      = captcha_should_challenge($pdo); // 'none'|'turnstile'|'v2'|'v3'
$turnstileSiteKey = trim(get_cfg($pdo, 'turnstile_site_key'));
$v3SiteKey        = trim(get_cfg($pdo, 'recaptcha_v3_site_key'));
$v2SiteKey        = trim(get_cfg($pdo, 'recaptcha_v2_site_key'));

// Download button CAPTCHA — ALWAYS required when any key is configured,
// regardless of visitor risk level. Prefers Turnstile → v2 → v3.
$dlCaptchaType = 'none';
if ($turnstileSiteKey)      $dlCaptchaType = 'turnstile';
elseif ($v2SiteKey)         $dlCaptchaType = 'v2';
elseif ($v3SiteKey)         $dlCaptchaType = 'v3';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <?= nav_guard_script() ?>
  <meta charset="UTF-8">
  <?= head_extras($pdo) ?>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title>تحميل <?= h($app['name']) ?><?= $displayVersion ? ' v'.h($displayVersion) : '' ?> للأندرويد — yassota</title>
  <meta name="robots" content="noindex,follow">
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/main.css')) ?>">
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/download.css')) ?>">
  <?php if ($customAdCode): ?>
  <script><?= $customAdCode ?></script>
  <?php endif; ?>
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5506877998492189" crossorigin="anonymous"></script>
  <?php if ($captchaType === 'turnstile' || $dlCaptchaType === 'turnstile'): ?>
  <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
  <?php elseif ($captchaType === 'v3' || $dlCaptchaType === 'v3'): ?>
  <script src="https://www.google.com/recaptcha/api.js?render=<?= h($v3SiteKey) ?>" async defer></script>
  <?php elseif ($captchaType === 'v2' || $dlCaptchaType === 'v2'): ?>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <?php endif; ?>
</head>
<body>

<?php if ($captchaType !== 'none'): ?>
<div id="captcha-overlay" style="position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.92);display:flex;align-items:center;justify-content:center;padding:16px">
  <div style="background:#fff;border-radius:18px;padding:32px 28px;max-width:400px;width:100%;text-align:center;direction:rtl">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.5" style="margin-bottom:14px"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
    <h2 style="margin:0 0 8px;font-size:18px;color:#0f172a">تحقق سريع</h2>
    <p style="color:#64748b;font-size:14px;margin:0 0 20px">لحماية الموقع، نحتاج للتحقق أنك لست روبوتاً</p>
    <?php if ($captchaType === 'turnstile' && $turnstileSiteKey): ?>
    <div style="display:flex;justify-content:center;margin-bottom:18px">
      <div class="cf-turnstile" data-sitekey="<?= h($turnstileSiteKey) ?>" data-callback="onCaptchaSolvedTurnstile" data-theme="light"></div>
    </div>
    <p style="color:#94a3b8;font-size:11px;margin:8px 0 0">محمي بواسطة Cloudflare Turnstile</p>
    <?php elseif ($captchaType === 'v2' && $v2SiteKey): ?>
    <div style="display:flex;justify-content:center;margin-bottom:18px">
      <div class="g-recaptcha" data-sitekey="<?= h($v2SiteKey) ?>" data-callback="onCaptchaV2Done"></div>
    </div>
    <p style="color:#94a3b8;font-size:11px;margin:14px 0 0">محمي بواسطة Google reCAPTCHA</p>
    <?php else: ?>
    <button id="captcha-v3-btn" onclick="runV3Captcha()" style="background:#2563eb;color:#fff;border:none;border-radius:10px;padding:12px 28px;font-size:15px;cursor:pointer;width:100%">
      أنا لست روبوتاً — متابعة التحميل
    </button>
    <p style="color:#94a3b8;font-size:11px;margin:14px 0 0">محمي بواسطة Google reCAPTCHA</p>
    <?php endif; ?>
  </div>
</div>
<script>
(function(){
  var overlay = document.getElementById('captcha-overlay');
  var pageUrl = location.href;
  function verifyCaptcha(token, type) {
    fetch(pageUrl.split('?')[0] + '?ajax=verify_captcha', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'token=' + encodeURIComponent(token) + '&type=' + encodeURIComponent(type)
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
      if (d.ok) {
        overlay.style.transition = 'opacity .3s'; overlay.style.opacity = '0';
        setTimeout(function(){ overlay.remove(); }, 320);
      } else { alert('فشل التحقق، يرجى المحاولة مرة أخرى.'); }
    });
  }
  window.onCaptchaSolvedTurnstile = function(token) { verifyCaptcha(token, 'turnstile'); };
  window.onCaptchaV2Done = function(token) { verifyCaptcha(token, 'v2'); };
  window.runV3Captcha = function() {
    var btn = document.getElementById('captcha-v3-btn');
    if (btn) { btn.disabled = true; btn.textContent = 'جارٍ التحقق...'; }
    <?php if ($captchaType === 'v3' && $v3SiteKey): ?>
    grecaptcha.ready(function(){
      grecaptcha.execute('<?= h($v3SiteKey) ?>', {action:'download'}).then(function(token){ verifyCaptcha(token, 'v3'); });
    });
    <?php endif; ?>
  };
  <?php if ($captchaType === 'v3'): ?>
  document.addEventListener('DOMContentLoaded', function(){
    if (typeof grecaptcha !== 'undefined') { runV3Captcha(); }
    else { setTimeout(function(){ if (typeof grecaptcha !== 'undefined') runV3Captcha(); }, 1500); }
  });
  <?php endif; ?>
})();
</script>
<?php endif; ?>

<header class="site-header">
  <a href="<?= h(url('')) ?>" class="logo">yass<span style="color:var(--purple)">ota</span></a>
  <nav class="header-nav">
    <a href="<?= h(url('')) ?>">الرئيسية</a>
    <a href="<?= h(app_url($app['slug'])) ?>">← صفحة التطبيق</a>
  </nav>
</header>

<div class="dlp-wrap">

  <!-- ── Hero band ── -->
  <div class="dlp-hero">
    <div class="dlp-hero-icon">
      <?php if ($app['icon_path']): ?>
        <img src="<?= h(media_url($app['icon_path'])) ?>" alt="<?= h($app['name']) ?>" class="dlp-icon-img">
      <?php else: ?>
        <div class="dlp-icon-placeholder">
          <svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.45)" stroke-width="1.5">
            <rect x="5" y="2" width="14" height="20" rx="3"/><path d="M9 7h6M9 11h6M9 15h4"/>
          </svg>
        </div>
      <?php endif; ?>
    </div>
    <div class="dlp-hero-info">
      <h1 class="dlp-app-name"><?= h($app['name']) ?></h1>
      <div class="dlp-app-meta">
        <?php if ($displayVersion): ?><span class="dlp-meta-chip">v<?= h($displayVersion) ?></span><?php endif; ?>
        <?php if ($app['size_mb']): ?><span class="dlp-meta-chip"><?= h($app['size_mb']) ?> MB</span><?php endif; ?>
        <?php if ($catName): ?><span class="dlp-meta-chip"><?= h($catName) ?></span><?php endif; ?>
        <span class="dlp-meta-chip">Android</span>
      </div>
      <?php if ($app['link_verified']): ?>
      <div class="dlp-verified">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
        رابط تم التحقق منه بواسطة فريق yassota
      </div>
      <?php endif; ?>
      <?php if ($app['downloads'] > 0 || (!empty($app['rating']) && $app['rating'] > 0) || $app['developer']): ?>
      <div class="dlp-hero-stats">
        <?php if ($app['downloads'] > 0): ?>
        <div class="dlp-hero-stat">
          <strong><?= number_format($app['downloads']) ?>+</strong>
          <span>تحميل</span>
        </div>
        <?php endif; ?>
        <?php if (!empty($app['rating']) && $app['rating'] > 0): ?>
        <div class="dlp-hero-stat">
          <strong>⭐ <?= number_format($app['rating'], 1) ?></strong>
          <span><?= !empty($app['rating_count']) ? number_format($app['rating_count']).' تقييم' : 'تقييم' ?></span>
        </div>
        <?php endif; ?>
        <?php if ($app['developer']): ?>
        <div class="dlp-hero-stat">
          <strong><?= h($app['developer']) ?></strong>
          <span>المطوّر</span>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

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

  <!-- ── Top ad ── -->
  <div class="ad-zone"><?= ad_slot() ?></div>

  <!-- ── Main 2-column grid ── -->
  <div class="dlp-main-grid">

    <!-- LEFT: Download column -->
    <div class="dlp-download-col">

      <!-- Countdown card -->
      <div class="dlp-countdown-card" id="dl-timer-section">
        <?php if ($hasLink): ?>

        <div class="dlp-countdown-header">
          <div class="dlp-pulse-dot"></div>
          <span id="dl-status-text">جاري تجهيز رابط التحميل الآمن...</span>
        </div>

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

        <div class="dlp-progress-bar">
          <div class="dlp-progress-fill" id="dl-progress"></div>
        </div>

        <?php if ($dlCaptchaType !== 'none'): ?>
        <div id="dl-captcha-wrap" class="dl-cap-hidden" aria-hidden="true">
          <p style="font-size:13px;color:var(--muted);margin:0 0 12px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            حل التحقق أدناه لبدء التحميل
          </p>
          <?php if ($dlCaptchaType === 'turnstile'): ?>
          <div style="display:flex;justify-content:center">
            <div class="cf-turnstile" data-sitekey="<?= h($turnstileSiteKey) ?>" data-callback="onDlCaptchaSolved" data-theme="light" data-size="normal"></div>
          </div>
          <?php elseif ($dlCaptchaType === 'v2'): ?>
          <div style="display:flex;justify-content:center">
            <div class="g-recaptcha" data-sitekey="<?= h($v2SiteKey) ?>" data-callback="onDlCaptchaSolvedV2"></div>
          </div>
          <?php elseif ($dlCaptchaType === 'v3'): ?>
          <button id="dl-captcha-v3-btn" onclick="runDlV3Captcha()"
                  style="background:var(--cyan);color:#fff;border:none;border-radius:10px;padding:11px 24px;font-size:14px;cursor:pointer;display:inline-flex;align-items:center;gap:8px">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            تحقق وابدأ التحميل
          </button>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php $manualUrl = $hasLocalApk ? h(url('download.php?slug='.urlencode($app['slug']).'&apk=1')) : h($url); ?>
        <a id="btn-manual" href="<?= $manualUrl ?>" class="dlp-btn-download dl-btn-hidden"
           <?= $hasLocalApk ? 'download' : '' ?> data-hardnav="1"
           tabindex="-1" aria-hidden="true">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
            <path d="M12 3v12m0 0l-4-4m4 4l4-4"/>
            <path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2"/>
          </svg>
          <?= $hasLocalApk ? 'تحميل APK مباشرةً' : 'اضغط هنا لبدء التحميل' ?>
        </a>
        <p class="dlp-manual-hint dl-btn-hidden" id="manual-label">
          لم يبدأ التحميل تلقائياً؟ اضغط الزر أعلاه
        </p>

        <?php else: ?>
        <div class="dlp-no-link">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
          <strong>رابط التحميل غير متوفر حالياً</strong>
          <p>سيقوم فريق yassota بإضافة رابط التحميل قريباً. تابع صفحة التطبيق للتحديثات.</p>
        </div>
        <?php endif; ?>
      </div>

      <!-- Trust badges -->
      <div class="dlp-trust">
        <div class="dlp-trust-item">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          <div class="dlp-trust-text"><strong>رابط آمن 100%</strong><small>بروتوكول HTTPS مشفر</small></div>
        </div>
        <div class="dlp-trust-item">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
          <div class="dlp-trust-text"><strong>خالٍ من الفيروسات</strong><small>تم فحص الملف</small></div>
        </div>
        <div class="dlp-trust-item">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V9l-7-7z"/><path d="M13 2v7h7"/></svg>
          <div class="dlp-trust-text"><strong>ملف APK أصلي</strong><small>لم يُعدَّل أو يُحرَّف</small></div>
        </div>
        <div class="dlp-trust-item">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          <div class="dlp-trust-text"><strong>تحميل سريع</strong><small>خوادم عالية السرعة</small></div>
        </div>
      </div>

      <!-- Mirror links -->
      <?php if ($app['mirror2_url'] || $app['mirror3_url']): ?>
      <div class="dlp-mirrors">
        <span class="dlp-mirrors-label">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
          روابط بديلة:
        </span>
        <?php if ($app['mirror2_url']): ?>
          <a href="<?= h(download_url($app['slug'], 2)) ?>" class="dlp-mirror-btn" data-hardnav="1">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 3v12m0 0l-4-4m4 4l4-4"/><path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2"/></svg>
            مرآة 2
          </a>
        <?php endif; ?>
        <?php if ($app['mirror3_url']): ?>
          <a href="<?= h(download_url($app['slug'], 3)) ?>" class="dlp-mirror-btn" data-hardnav="1">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 3v12m0 0l-4-4m4 4l4-4"/><path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2"/></svg>
            مرآة 3
          </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Hash details -->
      <?php if ($hasLocalApk && $app['apk_hash_sha256']): ?>
      <details class="dlp-hash-details">
        <summary>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
          التحقق من سلامة الملف (SHA-256)
        </summary>
        <div class="dlp-hash-body">
          <?php if ($app['apk_size_bytes']): ?>
          <div style="margin-bottom:6px"><span style="color:var(--muted)">الحجم الفعلي:</span> <strong><?= h(format_apk_size((int)$app['apk_size_bytes'])) ?></strong></div>
          <?php endif; ?>
          <div>
            <div style="color:var(--muted);margin-bottom:4px;font-size:11px">SHA-256 (انقر للنسخ):</div>
            <code class="dlp-hash-code" onclick="navigator.clipboard.writeText(this.textContent).then(()=>{this.dataset.copied='1';setTimeout(()=>delete this.dataset.copied,1500)})"><?= h($app['apk_hash_sha256']) ?></code>
          </div>
          <?php if ($app['apk_hash_md5']): ?>
          <div style="margin-top:8px">
            <div style="color:var(--muted);margin-bottom:4px;font-size:11px">MD5:</div>
            <code class="dlp-hash-code" onclick="navigator.clipboard.writeText(this.textContent).then(()=>{this.dataset.copied='1';setTimeout(()=>delete this.dataset.copied,1500)})"><?= h($app['apk_hash_md5']) ?></code>
          </div>
          <?php endif; ?>
        </div>
      </details>
      <?php endif; ?>

      <!-- Google Play link -->
      <?php if ($hasLocalApk && $downloadSource === 'both' && $app['download_url']): ?>
      <div class="dlp-gplay">أو
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

    </div><!-- /download-col -->

    <!-- RIGHT: Info sidebar -->
    <div class="dlp-info-col">

      <!-- App details panel -->
      <div class="dlp-panel">
        <div class="dlp-panel-head">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--cyan)" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
          معلومات التطبيق
        </div>
        <?php
          // Build info cells as array to control even/odd layout
          $apkSizeBytes  = $app['apk_size_bytes'] ?? null;
          $fileSizeMb    = $apkSizeBytes ? round($apkSizeBytes / 1048576, 2) : ($app['size_mb'] ?: null);
          // Strip accidental "Android" prefix already in android_version field
          $androidVer    = trim(preg_replace('/^android\s*/i', '', $app['android_version'] ?? ''));
          $arabicMonths  = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
          $fmtDate = function(?string $d) use ($arabicMonths): string {
              if (!$d) return '';
              $ts = strtotime($d);
              return $ts ? (date('j', $ts) . ' ' . $arabicMonths[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts)) : '';
          };
          $infoCells = [];
          if ($displayVersion)         $infoCells[] = ['الإصدار',      '<span dir="ltr">' . h($displayVersion) . '</span>'];
          if ($fileSizeMb)             $infoCells[] = ['الحجم',        '<span dir="ltr">' . h($fileSizeMb) . ' MB</span>'];
          if ($catName)                $infoCells[] = ['التصنيف',      h($catName)];
          if ($app['developer'])       $infoCells[] = ['المطوّر',      h($app['developer'])];
          $infoCells[]                              = ['النظام',       'Android'];
          $infoCells[]                              = ['الترخيص',      h($app['license'] ?? 'مجاني')];
          if ($androidVer)             $infoCells[] = ['أدنى إصدار',   'Android ' . h($androidVer) . '+'];
          $fileType = !empty($app['apk_path']) ? 'APK' : 'APK/XAPK';
          $infoCells[]                              = ['نوع الملف',    $fileType];
          if ($app['downloads'] > 0)   $infoCells[] = ['التحميلات',    '<span dir="ltr">+' . number_format($app['downloads']) . '</span>'];
          if (!empty($app['updated_at']))  $infoCells[] = ['آخر تحديث',  $fmtDate($app['updated_at'])];
          if (!empty($app['release_date'])) $infoCells[] = ['تاريخ الإصدار', $fmtDate($app['release_date'])];
          $cellCount = count($infoCells);
        ?>
        <div class="dlp-meta-grid">
          <?php foreach ($infoCells as [$key, $val]): ?>
          <div class="dlp-meta-cell"><div class="dlp-meta-key"><?= $key ?></div><div class="dlp-meta-val"><?= $val ?></div></div>
          <?php endforeach; ?>
          <?php if ($cellCount % 2 !== 0): ?>
          <div class="dlp-meta-cell" aria-hidden="true"></div>
          <?php endif; ?>
          <?php if (!empty($app['package_name'])): ?>
          <div class="dlp-meta-cell" style="grid-column:1/-1">
            <div class="dlp-meta-key">اسم الحزمة</div>
            <div class="dlp-meta-val" style="font-family:var(--f-mono);font-size:11px;word-break:break-all;direction:ltr;text-align:left"><?= h($app['package_name']) ?></div>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Security panel -->
      <?php if (!empty($app['apk_hash_sha256']) || !empty($app['apk_hash_md5']) || !empty($app['virustotal_report_url'])): ?>
      <div class="dlp-panel">
        <div class="dlp-panel-head">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--cyan)" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          التحقق الأمني
        </div>
        <div class="dlp-panel-body" style="font-size:12px;display:flex;flex-direction:column;gap:10px">
          <?php if (!empty($app['apk_hash_sha256'])): ?>
          <div>
            <div style="color:var(--muted);margin-bottom:3px;font-size:10px">SHA-256 (انقر للنسخ):</div>
            <code style="display:block;background:var(--surface-2);padding:5px 8px;border-radius:6px;font-size:9.5px;word-break:break-all;cursor:pointer;color:var(--text);font-family:var(--f-mono)"
                  onclick="navigator.clipboard.writeText(this.textContent);this.style.color='var(--success)';setTimeout(()=>this.style.color='',2000)"
                  title="انقر للنسخ"><?= h($app['apk_hash_sha256']) ?></code>
          </div>
          <?php endif; ?>
          <?php if (!empty($app['apk_hash_md5'])): ?>
          <div>
            <div style="color:var(--muted);margin-bottom:3px;font-size:10px">MD5:</div>
            <code style="display:block;background:var(--surface-2);padding:5px 8px;border-radius:6px;font-size:9.5px;word-break:break-all;cursor:pointer;color:var(--text);font-family:var(--f-mono)"
                  onclick="navigator.clipboard.writeText(this.textContent);this.style.color='var(--success)';setTimeout(()=>this.style.color='',2000)"><?= h($app['apk_hash_md5']) ?></code>
          </div>
          <?php endif; ?>
          <?php if (!empty($app['virustotal_report_url'])): ?>
          <a href="<?= h($app['virustotal_report_url']) ?>" target="_blank" rel="nofollow noopener"
             style="color:var(--cyan);font-size:11px;display:inline-flex;align-items:center;gap:5px">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/></svg>
            🛡️ تقرير VirusTotal
          </a>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Screenshots panel -->
      <?php if ($screenshots): ?>
      <div class="dlp-panel">
        <div class="dlp-panel-head">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--cyan)" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
          صور من التطبيق
        </div>
        <div style="padding:12px">
          <div class="dlp-screenshots-track">
            <?php foreach (array_slice($screenshots, 0, 6) as $shot): ?>
            <img src="<?= h(url($shot)) ?>" alt="<?= h($app['name']) ?> screenshot" class="dlp-screenshot" loading="lazy">
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div><!-- /info-col -->

  </div><!-- /main-grid -->

  <!-- ── Installation guide ── -->
  <div class="dlp-install-guide">
    <h2 class="dlp-install-title">كيفية تثبيت تطبيق APK على أندرويد</h2>
    <div class="dlp-install-steps">
      <div class="dlp-install-step">
        <div class="dlp-install-num">1</div>
        <div class="dlp-install-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1"/>
            <polyline points="7 10 12 15 17 10"/>
            <line x1="12" y1="15" x2="12" y2="3"/>
          </svg>
        </div>
        <h3>تحميل الملف</h3>
        <p>اضغط زر التحميل وانتظر انتهاء العدّ التنازلي لبدء التحميل تلقائياً</p>
      </div>
      <div class="dlp-install-step">
        <div class="dlp-install-num">2</div>
        <div class="dlp-install-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            <path d="M9 12l2 2 4-4"/>
          </svg>
        </div>
        <h3>السماح بالتثبيت</h3>
        <p>الإعدادات ← الأمان ← فعّل "تثبيت تطبيقات من مصادر غير معروفة"</p>
      </div>
      <div class="dlp-install-step">
        <div class="dlp-install-num">3</div>
        <div class="dlp-install-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <rect x="5" y="2" width="14" height="20" rx="3"/>
            <path d="M9 10h6M9 14h4"/>
            <circle cx="12" cy="17.5" r="1" fill="currentColor" stroke="none"/>
          </svg>
        </div>
        <h3>فتح ملف APK</h3>
        <p>افتح الملف المُحمَّل من مجلد التنزيلات واضغط على "تثبيت"</p>
      </div>
      <div class="dlp-install-step">
        <div class="dlp-install-num">4</div>
        <div class="dlp-install-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="16 8 11 14 8 11"/>
          </svg>
        </div>
        <h3>الاستمتاع بالتطبيق</h3>
        <p>افتح التطبيق من الشاشة الرئيسية أو قائمة التطبيقات وتمتع!</p>
      </div>
    </div>
  </div>

  <!-- ── Mid AdSense ── -->
  <div class="ad-zone"><?= ad_slot() ?></div>

  <!-- ── Telegram subscribe ── -->
  <?php if ($tgUrl): ?>
  <a href="<?= h($tgUrl) ?>" target="_blank" rel="nofollow noopener" class="dlp-tg-btn">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12l-6.869 4.326-2.96-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.829.941z"/></svg>
    تابع قناة yassota على تيليجرام للحصول على أحدث التطبيقات
  </a>
  <?php endif; ?>

  <!-- ── Related apps ── -->
  <?php if ($relatedApps): ?>
  <div class="dlp-related">
    <div class="dlp-related-title">تطبيقات مشابهة قد تعجبك</div>
    <div class="dlp-related-grid">
      <?php foreach ($relatedApps as $r): ?>
      <a href="<?= h(app_url($r['slug'])) ?>" class="dlp-related-card">
        <?php if ($r['icon_path']): ?>
          <img src="<?= h(media_url($r['icon_path'])) ?>" alt="<?= h($r['name']) ?>" class="dlp-related-icon" loading="lazy">
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
const DOWNLOAD_URL    = <?= $hasLocalApk ? json_encode(url('download.php?slug='.urlencode($app['slug']).'&apk=1')) : json_encode($url) ?>;
const HAS_LINK        = <?= $hasLink ? 'true' : 'false' ?>;
const IS_LOCAL_APK    = <?= $hasLocalApk ? 'true' : 'false' ?>;
const TOTAL           = <?= $countdownSecs ?>;
const CIRC            = 326.73;
const DL_CAPTCHA_TYPE = <?= json_encode($dlCaptchaType) ?>; // 'none'|'turnstile'|'v2'|'v3'
<?php if ($dlCaptchaType === 'v3'): ?>
const DL_V3_SITE_KEY  = <?= json_encode($v3SiteKey) ?>;
<?php endif; ?>

let remaining          = TOTAL;
let dlTimerDone        = false;   // timer reached zero
let dlCaptchaSolved    = (DL_CAPTCHA_TYPE === 'none'); // no captcha = auto-solved

const countEl       = document.getElementById('dl-count');
const statusText    = document.getElementById('dl-status-text');
const progressEl    = document.getElementById('dl-progress');
const ringProg      = document.getElementById('ring-prog');
const btnManual     = document.getElementById('btn-manual');
const manualLbl     = document.getElementById('manual-label');
const captchaWrap   = document.getElementById('dl-captcha-wrap');
const step2         = document.getElementById('step2');
const step3         = document.getElementById('step3');
const dlCaptchaVerifyUrl = location.pathname + '?ajax=verify_captcha';

// Called after both timer AND captcha are ready
function triggerDownload() {
  if (!dlTimerDone || !dlCaptchaSolved) return;

  if (statusText) statusText.innerHTML = '<strong style="color:var(--success)">✓ جاهز للتحميل!</strong>';

  // Auto-fire the download
  const a = document.createElement('a');
  a.href = DOWNLOAD_URL;
  if (IS_LOCAL_APK) {
    a.download = '';
  } else {
    a.target = '_blank';
    a.rel = 'noopener nofollow';
  }
  document.body.appendChild(a); a.click(); document.body.removeChild(a);

  // Reveal manual button without scrolling the page
  setTimeout(function() {
    if (btnManual) {
      btnManual.classList.remove('dl-btn-hidden');
      btnManual.removeAttribute('tabindex');
      btnManual.removeAttribute('aria-hidden');
    }
    if (manualLbl) manualLbl.classList.remove('dl-btn-hidden');
  }, 1400);
}

// Called when any CAPTCHA widget fires its callback
function dlCaptchaUnlock(token, type) {
  dlCaptchaSolved = true;
  if (captchaWrap) captchaWrap.classList.add('dl-cap-hidden');
  triggerDownload();
  fetch(dlCaptchaVerifyUrl, {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'token=' + encodeURIComponent(token) + '&type=' + encodeURIComponent(type)
  }).catch(function(){});
}

// Turnstile callback
window.onDlCaptchaSolved = function(token) { dlCaptchaUnlock(token, 'turnstile'); };
// reCAPTCHA v2 callback
window.onDlCaptchaSolvedV2 = function(token) { dlCaptchaUnlock(token, 'v2'); };
<?php if ($dlCaptchaType === 'v3'): ?>
window.runDlV3Captcha = function() {
  var btn = document.getElementById('dl-captcha-v3-btn');
  if (btn) { btn.disabled = true; btn.textContent = 'جارٍ التحقق…'; }
  grecaptcha.ready(function(){
    grecaptcha.execute(DL_V3_SITE_KEY, {action:'download'}).then(function(token){
      dlCaptchaUnlock(token, 'v3');
    });
  });
};
<?php endif; ?>

function tick() {
  remaining--;
  const pct = (TOTAL - remaining) / TOTAL;
  if (countEl) countEl.textContent = remaining;
  if (progressEl) progressEl.style.width = (pct * 100) + '%';
  if (ringProg) ringProg.style.strokeDashoffset = CIRC * (1 - pct);

  if (remaining <= 0) {
    dlTimerDone = true;

    if (step2) { step2.classList.remove('active'); step2.classList.add('done'); step2.querySelector('.dlp-step-dot').textContent = '✓'; }
    if (step3) { step3.classList.add('active'); }
    if (countEl) { countEl.textContent = '✓'; countEl.style.fontSize = '28px'; }

    if (!dlCaptchaSolved) {
      // Show CAPTCHA — user must solve before download fires
      if (captchaWrap) {
        captchaWrap.classList.remove('dl-cap-hidden');
        captchaWrap.removeAttribute('aria-hidden');
      }
      <?php if ($dlCaptchaType === 'v3'): ?>
      if (statusText) statusText.innerHTML = '<strong style="color:#f59e0b">⬇ اضغط زر التحقق أدناه لبدء التحميل</strong>';
      <?php else: ?>
      if (statusText) statusText.innerHTML = '<strong style="color:#f59e0b">⬇ أكمل التحقق أدناه لبدء التحميل</strong>';
      <?php endif; ?>
    } else {
      // No captcha required — download immediately
      triggerDownload();
    }
  }
}

if (HAS_LINK && countEl) {
  if (ringProg) ringProg.style.strokeDashoffset = CIRC;
  const timer = setInterval(function() {
    if (remaining <= 0) { clearInterval(timer); return; }
    tick();
  }, 1000);
}
</script>
<script src="<?= h(asset_url('assets/js/main.js')) ?>"></script>
</body>
</html>
