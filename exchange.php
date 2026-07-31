<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

/* ── SYP rate (server-side) ─────────────────────────── */
$sypFallback = 13500; // updated constantly via admin settings
$sypRate     = (float)(get_cfg($pdo, 'syp_rate_usd', '') ?: $sypFallback);

/* ── SYP change notification (max once/hour) ────────── */
$lastNotifCheck = (int)get_cfg($pdo, 'syp_last_notif_check', '0');
if (time() - $lastNotifCheck >= 3600 && get_cfg($pdo, 'telegram_enabled', '0') === '1') {
    $pdo->prepare("INSERT INTO settings(`key`,`value`) VALUES(?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)")
        ->execute(['syp_last_notif_check', (string)time()]);
    $prev = (float)get_cfg($pdo, 'syp_rate_prev_notified', '0');
    if ($prev > 0) {
        $change = abs($sypRate - $prev) / $prev;
        if ($change >= 0.01) {
            $dir = $sypRate > $prev ? '⬆️ ارتفع' : '⬇️ انخفض';
            $pct = round($change * 100, 1);
            telegram_api($pdo, 'sendMessage', [
                'text' => "💱 <b>تنبيه — سعر الدولار / الليرة السورية</b>\n\n".
                          "📌 السعر الجديد: <b>" . number_format($sypRate, 0) . " ل.س</b>\n".
                          "📌 السعر السابق: " . number_format($prev, 0) . " ل.س\n".
                          "📊 التغيير: {$dir} بنسبة {$pct}%\n".
                          "⏰ " . date('Y-m-d H:i') . "\n\n".
                          "🔗 yassota.com/exchange",
                'parse_mode' => 'HTML',
            ]);
        }
    }
    if ($prev <= 0 || abs($sypRate - $prev) / max($prev, 1) >= 0.01) {
        $pdo->prepare("INSERT INTO settings(`key`,`value`) VALUES(?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)")
            ->execute(['syp_rate_prev_notified', (string)$sypRate]);
    }
}

/* ── SEO ─────────────────────────────────────────────── */
$seoTitle = 'سعر الدولار مقابل الليرة السورية اليوم — أسعار الصرف | yassota';
$metaDesc = 'سعر الدولار الأمريكي مقابل الليرة السورية لحظة بلحظة، مع محوّل عملات شامل لجميع العملات العربية والعالمية — الريال السعودي والدرهم والجنيه المصري ومئات العملات.';
$canonical = url('exchange');
$today     = date('Y-m-d');

$breadcrumbSchema = json_encode([
    '@context' => 'https://schema.org', '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type'=>'ListItem','position'=>1,'name'=>'الرئيسية','item'=>url('')],
        ['@type'=>'ListItem','position'=>2,'name'=>'أسعار الصرف','item'=>$canonical],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$faqSchema = json_encode([
    '@context' => 'https://schema.org', '@type' => 'FAQPage',
    'mainEntity' => [
        ['@type'=>'Question','name'=>'كم سعر الدولار مقابل الليرة السورية اليوم؟',
         'acceptedAnswer'=>['@type'=>'Answer','text'=>'سعر الدولار مقابل الليرة السورية اليوم يبلغ تقريباً '.number_format($sypRate,0).' ليرة سورية — هذا سعر السوق المرجعي. يمكن للأدمن تحديثه يدوياً من لوحة إعدادات yassota.']],
        ['@type'=>'Question','name'=>'ما أفضل طريقة لتحويل الأموال إلى سوريا؟',
         'acceptedAnswer'=>['@type'=>'Answer','text'=>'أكثر الطرق شيوعاً هي شركات الصرافة المرخصة (Western Union, MoneyGram)، والحوالات عبر شبكات المغتربين السوريين، بالإضافة إلى بعض تطبيقات التحويل الرقمي. دائماً تحقق من الترخيص القانوني قبل الإرسال.']],
        ['@type'=>'Question','name'=>'لماذا تتغير قيمة الليرة السورية باستمرار؟',
         'acceptedAnswer'=>['@type'=>'Answer','text'=>'تتأثر الليرة السورية بالأوضاع الاقتصادية والسياسية، وشُح احتياطي النقد الأجنبي، والطلب على العملة الصعبة في السوق المحلي. السعر المعروض هنا هو سعر السوق وقد يختلف عن السعر الرسمي في بعض الأحيان.']],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <?= nav_guard_script() ?>
  <meta charset="UTF-8">
  <?= head_extras($pdo) ?>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($seoTitle) ?></title>
  <meta name="description" content="<?= h($metaDesc) ?>">
  <link rel="canonical" href="<?= h($canonical) ?>">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= h($seoTitle) ?>">
  <meta property="og:description" content="<?= h($metaDesc) ?>">
  <meta property="og:url" content="<?= h($canonical) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= h($seoTitle) ?>">
  <meta name="twitter:description" content="<?= h($metaDesc) ?>">
  <script type="application/ld+json"><?= $breadcrumbSchema ?></script>
  <script type="application/ld+json"><?= $faqSchema ?></script>
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/main.css')) ?>">
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5506877998492189" crossorigin="anonymous"></script>
<style>
/* ════ Exchange Page Styles ════════════════════════════ */

/* ── SYP Hero ────────────────────────────────────────── */
.ex-hero {
  background: linear-gradient(135deg, #003d1f 0%, #007A3D 45%, #00a854 75%, #00c965 100%);
  position: relative; overflow: hidden; padding: 0;
}
.ex-hero::before {
  content: ''; position: absolute; inset: 0;
  background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.ex-hero-inner {
  position: relative; z-index: 1;
  max-width: 1100px; margin: 0 auto; padding: 36px 20px 28px;
}
.ex-breadcrumb {
  font-size: 12px; color: rgba(255,255,255,.6);
  display: flex; align-items: center; gap: 6px; margin-bottom: 20px;
  flex-wrap: wrap;
}
.ex-breadcrumb a { color: rgba(255,255,255,.6); transition: color .15s }
.ex-breadcrumb a:hover { color: #fff }
.ex-breadcrumb svg { width: 12px; height: 12px; stroke: rgba(255,255,255,.4); fill: none; stroke-width: 2 }

/* SYP big card */
.syp-main-card {
  display: flex; align-items: center; gap: 20px; flex-wrap: wrap;
  background: rgba(255,255,255,.1); backdrop-filter: blur(12px);
  border: 1px solid rgba(255,255,255,.2); border-radius: 20px;
  padding: 24px 28px; margin-bottom: 20px;
}
.syp-flag-big { font-size: 56px; line-height: 1; flex-shrink: 0 }
.syp-main-info { flex: 1 }
.syp-main-label { font-size: 13px; color: rgba(255,255,255,.7); font-weight: 600; letter-spacing: .5px; margin-bottom: 4px; text-transform: uppercase }
.syp-main-rate {
  font-size: clamp(2.2rem, 7vw, 3.8rem); font-weight: 900; color: #fff;
  letter-spacing: -1px; line-height: 1; margin-bottom: 6px;
  font-variant-numeric: tabular-nums;
}
.syp-main-unit { font-size: .55em; font-weight: 600; color: rgba(255,255,255,.75); margin-right: 6px }
.syp-main-sub { font-size: 13px; color: rgba(255,255,255,.65) }
.syp-main-meta { text-align: center; flex-shrink: 0; min-width: 100px }
.syp-live-dot {
  display: inline-block; width: 10px; height: 10px; border-radius: 50%;
  background: #4ade80; box-shadow: 0 0 0 4px rgba(74,222,128,.25);
  animation: syp-pulse 2s infinite; margin-left: 6px;
}
@keyframes syp-pulse { 0%,100%{box-shadow:0 0 0 4px rgba(74,222,128,.25)} 50%{box-shadow:0 0 0 8px rgba(74,222,128,.08)} }
.ex-hero-title {
  font-size: clamp(1.1rem, 2.5vw, 1.5rem); font-weight: 800; color: #fff;
  margin: 0 0 6px;
}
.ex-hero-sub { font-size: 14px; color: rgba(255,255,255,.72); margin: 0 }

/* ── White stripe (Syrian flag bottom) ───────────────── */
.ex-hero-stripe { height: 5px; background: #fff }

/* ── Page body ───────────────────────────────────────── */
.ex-body { max-width: 1100px; margin: 0 auto; padding: 28px 20px 60px }

/* ── Cards ───────────────────────────────────────────── */
.ex-card {
  background: var(--surface); border: 1px solid var(--border-c);
  border-radius: 16px; padding: 24px; margin-bottom: 20px;
  box-shadow: 0 2px 12px rgba(0,0,0,.05);
}
.ex-card-title {
  font-size: 17px; font-weight: 800; color: var(--white); margin-bottom: 20px;
  display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
}
.ex-card-title svg { flex-shrink: 0 }

/* ── SYP Converter ───────────────────────────────────── */
.syp-conv-grid {
  display: grid; grid-template-columns: 1fr auto 1fr; gap: 12px;
  align-items: end;
}
.syp-conv-field label {
  display: block; font-size: 12px; font-weight: 700; color: var(--muted);
  margin-bottom: 6px; letter-spacing: .3px; text-transform: uppercase;
}
.syp-conv-input {
  width: 100%; padding: 14px 16px;
  background: var(--surface-2); border: 2px solid var(--border-c);
  border-radius: 12px; font-size: 20px; font-weight: 700;
  color: var(--white); outline: none; transition: border-color .18s;
  font-variant-numeric: tabular-nums; direction: ltr; text-align: right;
}
.syp-conv-input:focus { border-color: #007A3D; box-shadow: 0 0 0 4px rgba(0,122,61,.1) }
.syp-conv-badge {
  display: inline-flex; align-items: center; gap: 6px;
  background: var(--surface-2); border: 1px solid var(--border-c);
  border-radius: 8px; padding: 8px 12px; font-size: 13px; font-weight: 700;
  color: var(--white); margin-top: 6px;
}
.syp-swap-btn {
  display: flex; align-items: center; justify-content: center;
  width: 44px; height: 44px; border-radius: 50%;
  background: #007A3D; color: #fff; border: none; cursor: pointer;
  font-size: 20px; transition: all .2s; flex-shrink: 0; margin-bottom: 0;
}
.syp-swap-btn:hover { background: #005a2e; transform: rotate(180deg) }
.syp-conv-result {
  margin-top: 18px; padding: 18px 20px;
  background: linear-gradient(135deg, rgba(0,122,61,.06) 0%, rgba(0,200,100,.04) 100%);
  border: 1.5px solid rgba(0,122,61,.2); border-radius: 14px;
  display: none;
}
.syp-conv-result.show { display: block }
.syp-result-big { font-size: 2.2rem; font-weight: 900; color: #007A3D; font-variant-numeric: tabular-nums }
.syp-result-label { font-size: 14px; color: var(--muted); margin-top: 4px }
.syp-result-rate { font-size: 12px; color: var(--muted); margin-top: 6px; opacity: .8 }

/* ── Arab Currencies Grid ────────────────────────────── */
.arab-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 12px;
}
.arab-card {
  background: var(--surface-2); border: 1px solid var(--border-c);
  border-radius: 14px; padding: 16px; display: flex;
  align-items: center; gap: 12px; transition: all .2s; cursor: default;
}
.arab-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.1); border-color: rgba(0,122,61,.3) }
.arab-card.syp-highlight {
  background: linear-gradient(135deg, rgba(0,122,61,.08), rgba(0,200,100,.04));
  border-color: rgba(0,122,61,.35);
}
.arab-card-flag { font-size: 28px; flex-shrink: 0 }
.arab-card-body { min-width: 0 }
.arab-card-name { font-size: 12px; color: var(--muted); margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis }
.arab-card-rate {
  font-size: 18px; font-weight: 800; color: var(--white);
  font-variant-numeric: tabular-nums; transition: color .3s;
}
.arab-card.syp-highlight .arab-card-rate { color: #007A3D }
.arab-card-code { font-size: 11px; font-weight: 700; color: var(--muted); margin-top: 1px; letter-spacing: .5px }
.rate-loading { color: #94a3b8; font-size: 1.1rem }

/* ── Full Converter ──────────────────────────────────── */
.full-conv-grid {
  display: grid; grid-template-columns: 1fr auto 1fr; gap: 10px; align-items: end;
}
.conv-field label {
  display: block; font-size: 12px; font-weight: 600; color: var(--muted);
  margin-bottom: 5px;
}
.conv-field input {
  width: 100%; padding: 12px 14px;
  background: var(--surface-2); border: 1.5px solid var(--border-c);
  border-radius: 10px; font-size: 16px; color: var(--white);
  outline: none; transition: border-color .18s; font-variant-numeric: tabular-nums;
  direction: ltr; text-align: right;
}
.conv-field input:focus { border-color: var(--cyan) }
.conv-field select {
  width: 100%; padding: 12px 14px;
  background: var(--surface-2); border: 1.5px solid var(--border-c);
  border-radius: 10px; font-size: 14px; color: var(--white);
  outline: none; transition: border-color .18s; cursor: pointer;
}
.conv-field select:focus { border-color: var(--cyan) }
.conv-swap-btn {
  display: flex; align-items: center; justify-content: center;
  width: 42px; height: 42px; border-radius: 50%;
  background: var(--surface-3); border: 1.5px solid var(--border-c);
  cursor: pointer; transition: all .2s; color: var(--muted); font-size: 18px; flex-shrink: 0;
}
.conv-swap-btn:hover { background: var(--cyan); color: #fff; border-color: var(--cyan) }
.conv-result-box {
  margin-top: 16px; padding: 16px 20px;
  background: rgba(14,165,233,.07); border: 1.5px solid rgba(14,165,233,.2);
  border-radius: 12px; display: none;
}
.conv-result-box.show { display: block }
.conv-result-big { font-size: 1.8rem; font-weight: 900; color: var(--cyan); font-variant-numeric: tabular-nums }
.conv-result-label { font-size: 13px; color: var(--muted); margin-top: 4px }
.conv-result-rate { font-size: 12px; color: var(--muted); opacity: .75; margin-top: 3px }

/* ── Rates Table ─────────────────────────────────────── */
.rates-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch }
.rates-table { width: 100%; border-collapse: collapse; font-size: 14px }
.rates-table th {
  padding: 10px 14px; text-align: right;
  background: var(--surface-2); color: var(--muted);
  font-size: 11px; font-weight: 700; letter-spacing: .5px;
  border-bottom: 2px solid var(--border-c); white-space: nowrap;
}
.rates-table td { padding: 11px 14px; border-bottom: 1px solid var(--border-c); vertical-align: middle }
.rates-table tr:last-child td { border-bottom: none }
.rates-table tr.syp-row { background: rgba(0,122,61,.05) }
.rates-table tr:hover td { background: var(--surface-2) }
.rates-table .td-flag { font-size: 22px; padding: 11px 10px }
.rates-table .td-name { font-weight: 600; color: var(--white) }
.rates-table .td-code { font-size: 12px; font-weight: 700; color: var(--muted); direction: ltr }
.rates-table .td-rate { font-size: 15px; font-weight: 700; font-variant-numeric: tabular-nums; direction: ltr; text-align: left; color: var(--white) }
.rates-table .td-100 { font-size: 12px; color: var(--muted); direction: ltr; text-align: left; white-space: nowrap }
.rates-table .syp-badge {
  display: inline-flex; align-items: center;
  background: rgba(0,122,61,.12); color: #007A3D;
  font-size: 10px; font-weight: 700; padding: 1px 7px; border-radius: 10px;
  margin-right: 6px; border: 1px solid rgba(0,122,61,.25);
}

/* ── SEO content ─────────────────────────────────────── */
.seo-prose h3 { font-size: 16px; font-weight: 800; color: var(--white); margin: 22px 0 10px; display: flex; align-items: center; gap: 8px }
.seo-prose p { font-size: 14px; color: var(--muted); line-height: 1.8; margin-bottom: 12px }
.seo-prose ul, .seo-prose ol { margin: 8px 20px 14px; display: flex; flex-direction: column; gap: 6px }
.seo-prose li { font-size: 14px; color: var(--muted); line-height: 1.65 }
.seo-prose strong { color: var(--white) }
.faq-item { border: 1px solid var(--border-c); border-radius: 12px; margin-bottom: 8px; overflow: hidden }
.faq-summary {
  padding: 14px 18px; font-size: 14px; font-weight: 700; color: var(--white);
  cursor: pointer; list-style: none; display: flex; justify-content: space-between; align-items: center;
  background: var(--surface); transition: background .15s;
}
.faq-summary:hover { background: var(--surface-2) }
.faq-summary::after { content: '＋'; font-size: 18px; color: var(--muted); transition: transform .2s; flex-shrink: 0 }
details[open] .faq-summary::after { content: '－' }
.faq-body { padding: 0 18px 14px; font-size: 14px; color: var(--muted); line-height: 1.75 }

/* ── Live badge ──────────────────────────────────────── */
.live-badge {
  display: inline-flex; align-items: center; gap: 5px;
  background: rgba(74,222,128,.15); color: #16a34a;
  border: 1px solid rgba(74,222,128,.3); border-radius: 20px;
  font-size: 11px; font-weight: 700; padding: 3px 10px;
}

/* ── Source bar ──────────────────────────────────────── */
.source-bar {
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 8px;
  font-size: 11px; color: var(--muted); margin-top: 12px;
}
.source-bar a { color: var(--cyan) }

/* ── Responsive ──────────────────────────────────────── */
@media (max-width: 680px) {
  .syp-conv-grid { grid-template-columns: 1fr; }
  .syp-swap-btn { width: 100%; height: 38px; border-radius: 10px; margin: 0 }
  .full-conv-grid { grid-template-columns: 1fr }
  .conv-swap-btn { width: 100%; height: 36px; border-radius: 10px }
  .syp-main-card { padding: 18px }
  .syp-flag-big { font-size: 40px }
  .syp-main-rate { font-size: 2rem }
  .ex-body { padding: 18px 14px 50px }
  .ex-card { padding: 18px }
}
@media (max-width: 420px) {
  .arab-grid { grid-template-columns: 1fr 1fr }
}
</style>
</head>
<body>
<?php render_site_header('', 'exchange'); ?>

<!-- ── Hero ──────────────────────────────────────────── -->
<section class="ex-hero">
  <div class="ex-hero-inner">
    <nav class="ex-breadcrumb" aria-label="مسار التنقل">
      <a href="<?= h(url('')) ?>">الرئيسية</a>
      <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
      <span>أسعار الصرف</span>
    </nav>

    <!-- SYP Spotlight card -->
    <div class="syp-main-card">
      <div class="syp-flag-big" aria-hidden="true">🇸🇾</div>
      <div class="syp-main-info">
        <div class="syp-main-label">سعر الدولار مقابل الليرة السورية</div>
        <div class="syp-main-rate" id="syp-hero-rate">
          <?= number_format($sypRate, 0) ?><span class="syp-main-unit">ل.س</span>
        </div>
        <div class="syp-main-sub">
          <span class="syp-live-dot"></span>
          سعر السوق اليوم — <?= h($today) ?>
        </div>
      </div>
      <div class="syp-main-meta">
        <div style="font-size:11px;color:rgba(255,255,255,.5);margin-bottom:6px;font-weight:600;letter-spacing:.5px">USD / SYP</div>
        <div style="font-size:20px;color:#fff;font-weight:900;direction:ltr" id="syp-usd-per-k">
          <?= number_format(1000 / $sypRate, 5) ?><br>
          <span style="font-size:11px;font-weight:500;color:rgba(255,255,255,.55)">دولار / 1000 ل.س</span>
        </div>
      </div>
    </div>

    <h1 class="ex-hero-title">
      أسعار الصرف اليوم <span class="live-badge">مباشر</span>
    </h1>
    <p class="ex-hero-sub">سعر الدولار والعملات العربية والعالمية — محوّل فوري لجميع العملات</p>
  </div>
</section>
<div class="ex-hero-stripe" aria-hidden="true"></div>

<div class="ex-body">

  <!-- ── SYP Quick Converter ────────────────────────── -->
  <section class="ex-card" style="border-right: 4px solid #007A3D">
    <h2 class="ex-card-title">
      <span style="font-size:1.5rem">🇸🇾</span>
      محوّل الليرة السورية — دولار / ليرة
    </h2>

    <div class="syp-conv-grid">
      <div class="syp-conv-field">
        <label for="syp-usd-in">
          <span style="margin-left:6px">🇺🇸</span>
          بالدولار الأمريكي (USD)
        </label>
        <input type="number" id="syp-usd-in" class="syp-conv-input"
               value="1" min="0" step="any" placeholder="0"
               oninput="sypConvFromUsd()">
        <div class="syp-conv-badge">
          <span>🇺🇸</span> دولار أمريكي
        </div>
      </div>

      <button class="syp-swap-btn" onclick="sypSwap()" aria-label="تبديل" title="تبديل اتجاه التحويل">
        ⇄
      </button>

      <div class="syp-conv-field">
        <label for="syp-lira-in">
          <span style="margin-left:6px">🇸🇾</span>
          بالليرة السورية (SYP)
        </label>
        <input type="number" id="syp-lira-in" class="syp-conv-input"
               min="0" step="any" placeholder="0"
               oninput="sypConvFromSyp()">
        <div class="syp-conv-badge" style="background:rgba(0,122,61,.1);border-color:rgba(0,122,61,.25)">
          <span>🇸🇾</span> ليرة سورية
        </div>
      </div>
    </div>

    <div class="syp-conv-result show" id="syp-result">
      <div class="syp-result-big" id="syp-result-val">
        <?= number_format($sypRate, 0) ?> ل.س
      </div>
      <div class="syp-result-label" id="syp-result-label">
        1 دولار أمريكي = <?= number_format($sypRate, 0) ?> ليرة سورية
      </div>
      <div class="syp-result-rate" id="syp-result-rate">
        1,000 ليرة سورية = <?= number_format(1000 / $sypRate, 4) ?> دولار أمريكي
      </div>
    </div>

    <p style="margin-top:12px;font-size:12px;color:var(--muted)">
      ⚡ السعر المستخدم: <strong id="syp-rate-display"><?= number_format($sypRate, 0) ?></strong> ل.س/دولار
      · <span id="syp-rate-source">سعر مرجعي</span>
      · <a href="<?= h(url('gold')) ?>" style="color:var(--cyan)">سعر الذهب ←</a>
    </p>
  </section>

  <!-- ── Arab Currencies Grid ─────────────────────── -->
  <section class="ex-card">
    <h2 class="ex-card-title">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 010 20"/></svg>
      العملات العربية — مقابل الدولار الأمريكي
    </h2>
    <div class="arab-grid" id="arab-grid">
      <!-- Cards filled by JS -->
      <div class="arab-card syp-highlight">
        <div class="arab-card-flag">🇸🇾</div>
        <div class="arab-card-body">
          <div class="arab-card-name">الليرة السورية</div>
          <div class="arab-card-rate" id="arab-SYP"><?= number_format($sypRate, 0) ?></div>
          <div class="arab-card-code">SYP</div>
        </div>
      </div>
      <?php
      $arabList = [
        'SAR' => ['الريال السعودي','🇸🇦'],
        'AED' => ['الدرهم الإماراتي','🇦🇪'],
        'EGP' => ['الجنيه المصري','🇪🇬'],
        'KWD' => ['الدينار الكويتي','🇰🇼'],
        'QAR' => ['الريال القطري','🇶🇦'],
        'BHD' => ['الدينار البحريني','🇧🇭'],
        'OMR' => ['الريال العُماني','🇴🇲'],
        'JOD' => ['الدينار الأردني','🇯🇴'],
        'IQD' => ['الدينار العراقي','🇮🇶'],
        'LBP' => ['الليرة اللبنانية','🇱🇧'],
        'MAD' => ['الدرهم المغربي','🇲🇦'],
        'TND' => ['الدينار التونسي','🇹🇳'],
        'DZD' => ['الدينار الجزائري','🇩🇿'],
        'SDG' => ['الجنيه السوداني','🇸🇩'],
        'YER' => ['الريال اليمني','🇾🇪'],
        'LYD' => ['الدينار الليبي','🇱🇾'],
      ];
      foreach ($arabList as $code => [$name, $flag]):
      ?>
      <div class="arab-card">
        <div class="arab-card-flag"><?= $flag ?></div>
        <div class="arab-card-body">
          <div class="arab-card-name"><?= h($name) ?></div>
          <div class="arab-card-rate rate-loading" id="arab-<?= h($code) ?>">···</div>
          <div class="arab-card-code"><?= h($code) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="source-bar">
      <span>المصدر: fawazahmed0 Exchange API · محدَّثة يومياً</span>
      <span id="arab-updated">جارٍ التحميل…</span>
    </div>
  </section>

  <!-- ── Full Currency Converter ───────────────────── -->
  <section class="ex-card">
    <h2 class="ex-card-title">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--cyan)" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
      محوّل العملات الشامل
    </h2>

    <div class="full-conv-grid">
      <div class="conv-field">
        <label>المبلغ</label>
        <input type="number" id="cv-amt" value="1" min="0" step="any"
               oninput="fullConvert()" placeholder="أدخل المبلغ">
      </div>
      <button class="conv-swap-btn" onclick="fullSwap()" aria-label="تبديل">⇄</button>
      <div class="conv-field">
        <label>النتيجة</label>
        <input type="number" id="cv-result-input" min="0" step="any"
               oninput="fullConvertReverse()" placeholder="النتيجة">
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px">
      <div class="conv-field">
        <label>من عملة</label>
        <select id="cv-from" onchange="fullConvert()">
          <option value="USD" selected>🇺🇸 USD — دولار أمريكي</option>
          <option value="SYP">🇸🇾 SYP — ليرة سورية</option>
          <option value="SAR">🇸🇦 SAR — ريال سعودي</option>
          <option value="AED">🇦🇪 AED — درهم إماراتي</option>
          <option value="EGP">🇪🇬 EGP — جنيه مصري</option>
          <option value="KWD">🇰🇼 KWD — دينار كويتي</option>
          <option value="EUR">🇪🇺 EUR — يورو</option>
          <option value="GBP">🇬🇧 GBP — جنيه إسترليني</option>
          <option value="QAR">🇶🇦 QAR — ريال قطري</option>
          <option value="BHD">🇧🇭 BHD — دينار بحريني</option>
          <option value="OMR">🇴🇲 OMR — ريال عُماني</option>
          <option value="JOD">🇯🇴 JOD — دينار أردني</option>
          <option value="TRY">🇹🇷 TRY — ليرة تركية</option>
          <option value="JPY">🇯🇵 JPY — ين ياباني</option>
          <option value="CNY">🇨🇳 CNY — يوان صيني</option>
          <option value="INR">🇮🇳 INR — روبية هندية</option>
          <option value="CHF">🇨🇭 CHF — فرنك سويسري</option>
          <option value="CAD">🇨🇦 CAD — دولار كندي</option>
          <option value="AUD">🇦🇺 AUD — دولار أسترالي</option>
        </select>
      </div>
      <div class="conv-field">
        <label>إلى عملة</label>
        <select id="cv-to" onchange="fullConvert()">
          <option value="SYP" selected>🇸🇾 SYP — ليرة سورية</option>
          <option value="USD">🇺🇸 USD — دولار أمريكي</option>
          <option value="SAR">🇸🇦 SAR — ريال سعودي</option>
          <option value="AED">🇦🇪 AED — درهم إماراتي</option>
          <option value="EGP">🇪🇬 EGP — جنيه مصري</option>
          <option value="KWD">🇰🇼 KWD — دينار كويتي</option>
          <option value="EUR">🇪🇺 EUR — يورو</option>
          <option value="GBP">🇬🇧 GBP — جنيه إسترليني</option>
          <option value="QAR">🇶🇦 QAR — ريال قطري</option>
          <option value="BHD">🇧🇭 BHD — دينار بحريني</option>
          <option value="OMR">🇴🇲 OMR — ريال عُماني</option>
          <option value="JOD">🇯🇴 JOD — دينار أردني</option>
          <option value="TRY">🇹🇷 TRY — ليرة تركية</option>
          <option value="JPY">🇯🇵 JPY — ين ياباني</option>
          <option value="CNY">🇨🇳 CNY — يوان صيني</option>
          <option value="INR">🇮🇳 INR — روبية هندية</option>
          <option value="CHF">🇨🇭 CHF — فرنك سويسري</option>
          <option value="CAD">🇨🇦 CAD — دولار كندي</option>
          <option value="AUD">🇦🇺 AUD — دولار أسترالي</option>
        </select>
      </div>
    </div>

    <div class="conv-result-box show" id="cv-result-box">
      <div class="conv-result-big" id="cv-result-big">···</div>
      <div class="conv-result-label" id="cv-result-label"></div>
      <div class="conv-result-rate" id="cv-result-rate"></div>
    </div>

    <div id="cv-loading" style="font-size:13px;color:var(--muted);margin-top:12px;padding:10px 0">
      ⏳ جارٍ تحميل أسعار الصرف من fawazahmed0…
    </div>
  </section>

  <!-- ── Full Rates Table ──────────────────────────── -->
  <section class="ex-card">
    <h2 class="ex-card-title">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18"/></svg>
      جدول الأسعار الكامل — 1 دولار = ؟
    </h2>
    <div class="rates-table-wrap">
      <table class="rates-table" id="main-rates-table">
        <thead>
          <tr>
            <th></th>
            <th>العملة</th>
            <th>الرمز</th>
            <th>السعر مقابل 1$</th>
            <th>100$ يساوي</th>
          </tr>
        </thead>
        <tbody id="main-rates-tbody">
          <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--muted)">⏳ جارٍ تحميل الأسعار…</td></tr>
        </tbody>
      </table>
    </div>
    <div class="source-bar">
      <span>المصدر: fawazahmed0 Exchange API + سعر SYP المُحدَّث يدوياً</span>
      <a href="<?= h(url('gold')) ?>">سعر الذهب ←</a>
    </div>
  </section>

  <!-- ── SEO Content ───────────────────────────────── -->
  <section class="ex-card seo-prose">
    <h2 class="ex-card-title">
      <span style="font-size:1.3rem">📖</span>
      دليل شامل — الليرة السورية وأسعار الصرف
    </h2>

    <h3>🇸🇾 سعر الدولار مقابل الليرة السورية اليوم</h3>
    <p>يعكس السعر الحالي <strong><?= number_format($sypRate, 0) ?> ليرة سورية لكل دولار</strong> معدل السوق الحر. شهدت الليرة السورية (SYP) تقلبات حادة منذ عام 2011، وبعد الأحداث السياسية عام 2024 بدأت تستعيد شيئاً من استقرارها النسبي. السعر المعروض هنا هو سعر مرجعي تقريبي، وقد يختلف في مكاتب الصرافة المحلية والبنوك السورية.</p>

    <h3>📊 كيف يُحسب سعر الليرة السورية؟</h3>
    <p>لا تتوفر بيانات SYP من المصادر المالية الدولية الكبرى (مثل Bloomberg أو Frankfurter) بشكل منتظم، نظراً لخصوصية الاقتصاد السوري. على yassota يُحدَّث السعر يدوياً بناءً على:</p>
    <ul>
      <li>معدل السوق الحر في المراكز الرئيسية (دمشق، حلب)</li>
      <li>أسعار منصات التداول الإلكترونية المتخصصة بالعملات السورية</li>
      <li>متوسط أسعار شبكات حوالات المغتربين السوريين</li>
    </ul>

    <h3>💱 أبرز العملات العربية مقابل الدولار</h3>
    <p>تتفاوت العملات العربية بين مربوطة بالدولار (مثل الريال السعودي 3.75 والدرهم الإماراتي 3.67) ومرنة تتأثر بالأسواق (مثل الجنيه المصري). أما الليرة السورية فهي من أكثر العملات التي شهدت تقلبات خلال العقد الماضي.</p>

    <h3>💸 كيف ترسل الأموال إلى سوريا؟</h3>
    <ul>
      <li><strong>Western Union وMoneyGram:</strong> متاحتان في بعض الدول بخدمة إرسال إلى سوريا</li>
      <li><strong>الحوالة البنكية:</strong> عبر المصارف التجارية إلى المصارف السورية المتاحة</li>
      <li><strong>شبكات الحوالة غير الرسمية:</strong> تُستخدم على نطاق واسع لكن تحمل مخاطر — تأكد من الترخيص</li>
      <li><strong>Wise وRemitly:</strong> تتحقق من توافر الخدمة لسوريا حسب بلدك وتاريخ الإرسال</li>
    </ul>

    <h3>❓ أسئلة شائعة</h3>
    <details class="faq-item">
      <summary class="faq-summary">كم سعر الدولار مقابل الليرة السورية اليوم؟</summary>
      <div class="faq-body">السعر المرجعي اليوم هو تقريباً <strong><?= number_format($sypRate, 0) ?> ليرة سورية لكل دولار</strong>. قد يختلف السعر الفعلي في الصرافات المحلية ببضع مئات من الليرات حسب منطقتك.</div>
    </details>
    <details class="faq-item">
      <summary class="faq-summary">لماذا لا يوجد سعر رسمي ثابت للليرة السورية؟</summary>
      <div class="faq-body">يعمل سوق الصرف في سوريا بنظام ازدواجي بين السعر الرسمي للمصرف المركزي وسعر السوق الحر. في ظروف الأزمات وشُح الاحتياطيات يكون الفارق بينهما كبيراً. الأسعار المعروضة هنا تعكس السوق الحر.</div>
    </details>
    <details class="faq-item">
      <summary class="faq-summary">ما العوامل التي تؤثر في سعر الليرة السورية؟</summary>
      <div class="faq-body">أبرز العوامل: احتياطي النقد الأجنبي، مستوى الاستيراد، حجم تحويلات المغتربين، الأوضاع السياسية والأمنية، ونشاط الاقتصاد غير الرسمي. بعد التغيرات السياسية في نهاية 2024 بدأ الوضع يتجه نحو استقرار نسبي.</div>
    </details>
    <details class="faq-item">
      <summary class="faq-summary">هل يمكن الثقة بأسعار الصرف المعروضة هنا؟</summary>
      <div class="faq-body">الأسعار تُحدَّث بانتظام وتعكس متوسط السوق. للمعاملات المالية الكبيرة نوصي دائماً بالتحقق من صرافة موثوقة محلياً أو خدمة تحويل مرخصة للحصول على السعر الدقيق لحظة التحويل.</div>
    </details>
  </section>

</div><!-- /ex-body -->

<?php render_site_footer(); ?>

<script>
/* ═══ Exchange Page JS ════════════════════════════════ */
var FX = { USD: 1, SYP: <?= (float)$sypRate ?> };   // seed with server SYP rate
var FX_LOADED = false;
var SYP_RATE  = <?= (float)$sypRate ?>;

/* Currency names & flags for the table */
var CNAMES = {
  SYP:['الليرة السورية','🇸🇾'],  SAR:['الريال السعودي','🇸🇦'],
  AED:['الدرهم الإماراتي','🇦🇪'], EGP:['الجنيه المصري','🇪🇬'],
  KWD:['الدينار الكويتي','🇰🇼'],  QAR:['الريال القطري','🇶🇦'],
  BHD:['الدينار البحريني','🇧🇭'], OMR:['الريال العُماني','🇴🇲'],
  JOD:['الدينار الأردني','🇯🇴'],  IQD:['الدينار العراقي','🇮🇶'],
  LBP:['الليرة اللبنانية','🇱🇧'], MAD:['الدرهم المغربي','🇲🇦'],
  TND:['الدينار التونسي','🇹🇳'],  DZD:['الدينار الجزائري','🇩🇿'],
  SDG:['الجنيه السوداني','🇸🇩'],  YER:['الريال اليمني','🇾🇪'],
  LYD:['الدينار الليبي','🇱🇧'],   EUR:['اليورو','🇪🇺'],
  GBP:['الجنيه الإسترليني','🇬🇧'],JPY:['الين الياباني','🇯🇵'],
  TRY:['الليرة التركية','🇹🇷'],   CNY:['اليوان الصيني','🇨🇳'],
  INR:['الروبية الهندية','🇮🇳'],  CHF:['الفرنك السويسري','🇨🇭'],
  CAD:['الدولار الكندي','🇨🇦'],   AUD:['الدولار الأسترالي','🇦🇺'],
  RUB:['الروبل الروسي','🇷🇺'],    KRW:['الوون الكوري','🇰🇷'],
  BRL:['الريال البرازيلي','🇧🇷'], MXN:['البيسو المكسيكي','🇲🇽'],
  THB:['البات التايلاندي','🇹🇭'],  IDR:['الروبية الإندونيسية','🇮🇩'],
  PKR:['الروبية الباكستانية','🇵🇰'],NGN:['النيرة النيجيرية','🇳🇬'],
  SGD:['الدولار السنغافوري','🇸🇬'],HKD:['الدولار الهونغ كونغي','🇭🇰'],
  NOK:['الكرونة النرويجية','🇳🇴'], SEK:['الكرونة السويدية','🇸🇪'],
  DKK:['الكرونة الدنماركية','🇩🇰'],PLN:['الزلوتي البولندي','🇵🇱'],
  CZK:['الكرونة التشيكية','🇨🇿'],
};

/* ── Load from fawazahmed0 (free, 170+ currencies, includes SYP) ── */
function loadFX() {
  var apiUrl = 'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/usd.json';
  var fallback= 'https://latest.currency-api.pages.dev/v1/currencies/usd.json';

  function process(d) {
    var raw = d.usd || d.USD || {};
    Object.keys(raw).forEach(function(k){ FX[k.toUpperCase()] = raw[k]; });
    FX['USD'] = 1;
    /* Use server SYP rate (more accurate) unless API has clearly different one */
    if (!FX['SYP'] || Math.abs(FX['SYP'] - SYP_RATE) / SYP_RATE > 0.5) {
      FX['SYP'] = SYP_RATE;
    }
    FX_LOADED = true;
    onFxReady();
  }

  fetch(apiUrl)
    .then(function(r){ return r.ok ? r.json() : Promise.reject(r.status); })
    .then(process)
    .catch(function(){
      fetch(fallback)
        .then(function(r){ return r.ok ? r.json() : Promise.reject(r.status); })
        .then(process)
        .catch(function(e){
          document.getElementById('cv-loading').textContent = '⚠️ تعذّر تحميل الأسعار — يُرجى تحديث الصفحة';
          console.warn('FX load failed', e);
        });
    });
}

function onFxReady() {
  document.getElementById('cv-loading').style.display = 'none';

  /* Update arab-grid cards */
  Object.keys(CNAMES).forEach(function(code){
    var el = document.getElementById('arab-' + code);
    if (!el) return;
    var rate = FX[code];
    if (!rate) return;
    var dec = ['KWD','BHD','OMR','JOD'].indexOf(code) >= 0 ? 4 : (['SYP','IQD','LBP','SDG','YER','IDR','PKR','NGN'].indexOf(code) >= 0 ? 0 : 2);
    el.textContent = fmt(rate, dec);
    el.classList.remove('rate-loading');
  });
  document.getElementById('arab-updated').textContent = 'محدَّث: ' + (new Date()).toLocaleDateString('ar-SA');

  /* Update hero rate if API gave SYP */
  if (FX['SYP'] && FX['SYP'] !== SYP_RATE) {
    document.getElementById('arab-SYP').textContent = fmt(FX['SYP'], 0);
  }

  /* Build full rates table */
  buildTable();

  /* Initial converter calc */
  fullConvert();
  sypConvFromUsd();
}

/* ── SYP Converter ───────────────────────────────────── */
function sypConvFromUsd() {
  var usd = parseFloat(document.getElementById('syp-usd-in').value) || 0;
  var rate = FX['SYP'] || SYP_RATE;
  var syp = usd * rate;
  document.getElementById('syp-result-val').textContent   = fmtSyp(syp) + ' ل.س';
  document.getElementById('syp-result-label').textContent = fmtN(usd,2) + ' دولار = ' + fmtSyp(syp) + ' ليرة سورية';
  document.getElementById('syp-result-rate').textContent  = '1,000 ليرة سورية = ' + (1000/rate).toFixed(5) + ' دولار أمريكي';
  document.getElementById('syp-result').classList.add('show');
  /* sync lira field without triggering its event */
  var liraIn = document.getElementById('syp-lira-in');
  if (document.activeElement !== liraIn) liraIn.value = syp > 0 ? Math.round(syp) : '';
  updateRateDisplay(rate);
}
function sypConvFromSyp() {
  var syp  = parseFloat(document.getElementById('syp-lira-in').value) || 0;
  var rate = FX['SYP'] || SYP_RATE;
  var usd  = syp / rate;
  document.getElementById('syp-result-val').textContent   = fmtN(usd, 4) + ' دولار';
  document.getElementById('syp-result-label').textContent = fmtSyp(syp) + ' ليرة سورية = ' + fmtN(usd, 4) + ' دولار أمريكي';
  document.getElementById('syp-result-rate').textContent  = '1 USD = ' + fmtSyp(rate) + ' SYP';
  document.getElementById('syp-result').classList.add('show');
  var usdIn = document.getElementById('syp-usd-in');
  if (document.activeElement !== usdIn) usdIn.value = usd > 0 ? usd.toFixed(4) : '';
}
function sypSwap() {
  var usd  = document.getElementById('syp-usd-in').value;
  var syp  = document.getElementById('syp-lira-in').value;
  document.getElementById('syp-usd-in').value  = syp ? (parseFloat(syp) / (FX['SYP']||SYP_RATE)).toFixed(4) : '';
  document.getElementById('syp-lira-in').value = usd ? Math.round(parseFloat(usd) * (FX['SYP']||SYP_RATE)) : '';
  sypConvFromUsd();
}
function updateRateDisplay(rate) {
  document.getElementById('syp-rate-display').textContent = fmtSyp(rate);
  document.getElementById('syp-rate-source').textContent  = FX_LOADED ? 'fawazahmed0 API' : 'سعر مرجعي';
  document.getElementById('syp-hero-rate').innerHTML      = fmtSyp(rate) + '<span class="syp-main-unit">ل.س</span>';
  document.getElementById('syp-usd-per-k').innerHTML      = (1000/rate).toFixed(5) + '<br><span style="font-size:11px;font-weight:500;color:rgba(255,255,255,.55)">دولار / 1000 ل.س</span>';
}

/* ── Full Converter ───────────────────────────────────── */
function fullConvert() {
  if (!FX_LOADED) { document.getElementById('cv-result-big').textContent = '···'; return; }
  var amt  = parseFloat(document.getElementById('cv-amt').value) || 0;
  var from = document.getElementById('cv-from').value;
  var to   = document.getElementById('cv-to').value;
  var usd  = amt / (FX[from] || 1);
  var out  = usd * (FX[to] || 1);
  var rate = (FX[to] || 1) / (FX[from] || 1);
  var revR = (FX[from] || 1) / (FX[to] || 1);
  var dec  = decFor(to);
  var decF = decFor(from);
  document.getElementById('cv-result-big').textContent   = fmt(out, dec) + ' ' + to;
  document.getElementById('cv-result-label').textContent = fmt(amt, decF) + ' ' + from + ' = ' + fmt(out, dec) + ' ' + to;
  document.getElementById('cv-result-rate').textContent  = '1 ' + from + ' = ' + fmt(rate, dec) + ' ' + to + '  ·  1 ' + to + ' = ' + fmt(revR, decF) + ' ' + from;
  document.getElementById('cv-result-box').classList.add('show');
  var ri = document.getElementById('cv-result-input');
  if (document.activeElement !== ri) ri.value = out.toFixed(Math.min(dec, 6));
}
function fullConvertReverse() {
  if (!FX_LOADED) return;
  var out  = parseFloat(document.getElementById('cv-result-input').value) || 0;
  var from = document.getElementById('cv-from').value;
  var to   = document.getElementById('cv-to').value;
  var usd  = out / (FX[to] || 1);
  var amt  = usd * (FX[from] || 1);
  var amtIn = document.getElementById('cv-amt');
  if (document.activeElement !== amtIn) amtIn.value = amt.toFixed(decFor(from));
}
function fullSwap() {
  var f = document.getElementById('cv-from'), t = document.getElementById('cv-to'), tmp = f.value;
  f.value = t.value; t.value = tmp; fullConvert();
}

/* ── Rates Table ─────────────────────────────────────── */
function buildTable() {
  /* Priority order: SYP first, then Arab, then major, then rest */
  var priority = ['SYP','SAR','AED','EGP','KWD','QAR','BHD','OMR','JOD',
                  'IQD','LBP','MAD','TND','DZD','SDG','YER','LYD',
                  'EUR','GBP','JPY','TRY','CNY','INR','CHF','CAD','AUD',
                  'RUB','KRW','BRL','MXN','THB','IDR','PKR','NGN','SGD','HKD',
                  'NOK','SEK','DKK','PLN','CZK'];
  var extra = Object.keys(FX).filter(function(c){ return priority.indexOf(c) < 0 && c !== 'USD'; }).sort();
  var order = priority.filter(function(c){ return FX[c]; }).concat(extra);

  var tbody = document.getElementById('main-rates-tbody');
  tbody.innerHTML = '';
  order.forEach(function(code) {
    var rate = FX[code];
    if (!rate || code === 'USD') return;
    var info = CNAMES[code] || [code, '💱'];
    var dec  = decFor(code);
    var isSyp = code === 'SYP';
    var syBadge = isSyp ? '<span class="syp-badge">مميز</span>' : '';
    tbody.innerHTML += '<tr class="' + (isSyp ? 'syp-row' : '') + '">' +
      '<td class="td-flag">' + info[1] + '</td>' +
      '<td class="td-name">' + syBadge + info[0] + '</td>' +
      '<td class="td-code">' + code + '</td>' +
      '<td class="td-rate">' + fmt(rate, dec) + '</td>' +
      '<td class="td-100">' + fmt(rate * 100, dec) + ' ' + code + '</td>' +
      '</tr>';
  });
}

/* ── Helpers ─────────────────────────────────────────── */
function decFor(c) {
  if (['JPY','KRW','IDR','IQD','YER','SDG','LBP','SYP','DZD','NGN'].indexOf(c) >= 0) return 0;
  if (['KWD','BHD','OMR','JOD'].indexOf(c) >= 0) return 4;
  return 2;
}
function fmt(n, dec) {
  if (!isFinite(n)) return '—';
  dec = dec === undefined ? 2 : dec;
  return n.toLocaleString('ar-SA', { minimumFractionDigits: dec, maximumFractionDigits: dec });
}
function fmtN(n, dec) { return n.toLocaleString('ar-SA', { minimumFractionDigits: dec, maximumFractionDigits: dec }); }
function fmtSyp(n) { return Math.round(n).toLocaleString('ar-SA'); }

/* ── Boot ────────────────────────────────────────────── */
loadFX();
/* Seed defaults immediately so page isn't blank */
sypConvFromUsd();
</script>
</body>
</html>
