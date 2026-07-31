<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

/* ── SYP rate (server-side) ─────────────────────────── */
$sypFallback = 13500;
$sypRate     = (float)(get_cfg($pdo, 'syp_rate_usd', '') ?: $sypFallback);

/* ── Auto Telegram post: every 6h OR immediate if Δ>200 ─ */
if (get_cfg($pdo, 'telegram_enabled', '0') === '1') {
    $lastPost       = (int)get_cfg($pdo, 'syp_last_auto_post', '0');
    $lastPostedRate = (float)get_cfg($pdo, 'syp_last_posted_rate', '0');
    $sincePost      = time() - $lastPost;
    $rateDelta      = $lastPostedRate > 0 ? abs($sypRate - $lastPostedRate) : 0;
    $isUrgent       = $rateDelta >= 200;
    $isScheduled    = $sincePost >= 21600; // 6 hours

    if ($isUrgent || $isScheduled) {
        /* Atomic claim: only one concurrent request wins */
        if ($lastPost === 0) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO settings(`key`,`value`) VALUES('syp_last_auto_post',?)");
            $stmt->execute([(string)time()]);
            $claimed = $stmt->rowCount() > 0;
        } else {
            $stmt = $pdo->prepare("UPDATE settings SET `value`=? WHERE `key`='syp_last_auto_post' AND `value`=?");
            $stmt->execute([(string)time(), (string)$lastPost]);
            $claimed = $stmt->rowCount() > 0;
        }

        if ($claimed) {
            $changeText = '';
            if ($lastPostedRate > 0 && $rateDelta > 0) {
                $dir  = $sypRate > $lastPostedRate ? '📈 ارتفع' : '📉 انخفض';
                $pct  = round($rateDelta / $lastPostedRate * 100, 2);
                $changeText = "\n{$dir} <b>" . number_format($rateDelta, 0) . " ل.س</b> ({$pct}%)\n"
                            . "السعر السابق: " . number_format($lastPostedRate, 0) . " ل.س";
            }
            $header  = $isUrgent
                ? "🚨 <b>تنبيه عاجل — تغيّر مفاجئ في سعر الصرف</b>"
                : "📢 <b>تحديث سعر الصرف | يassota</b>";
            $msgText = $header . "\n\n"
                . "🇸🇾 <b>سعر الدولار الآن: " . number_format($sypRate, 0) . " ل.س</b>"
                . $changeText . "\n\n"
                . "📅 " . date('d/m/Y — H:i') . " (دمشق)";

            $siteBase   = rtrim(SITE_URL, '/');
            $exchangeUrl = $siteBase . '/exchange';

            telegram_api($pdo, 'sendMessage', [
                'text'                     => $msgText,
                'parse_mode'               => 'HTML',
                'disable_web_page_preview' => true,
                'reply_markup'             => json_encode([
                    'inline_keyboard' => [[
                        ['text' => '🌐 yassota.com',        'url' => $siteBase],
                        ['text' => '💱 سعر الصرف الكامل', 'url' => $exchangeUrl],
                    ]],
                ]),
            ]);

            $pdo->prepare("INSERT INTO settings(`key`,`value`) VALUES(?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)")
                ->execute(['syp_last_posted_rate', (string)$sypRate]);
        }
    }
}

/* ── SEO ─────────────────────────────────────────────── */
$seoTitle = 'سعر الدولار مقابل الليرة السورية اليوم — أسعار الصرف | yassota';
$metaDesc = 'سعر الدولار الأمريكي مقابل الليرة السورية لحظة بلحظة، مع محوّل عملات شامل لجميع العملات العربية والعالمية.';
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
         'acceptedAnswer'=>['@type'=>'Answer','text'=>'سعر الدولار مقابل الليرة السورية اليوم يبلغ تقريباً '.number_format($sypRate,0).' ليرة سورية.']],
        ['@type'=>'Question','name'=>'ما أفضل طريقة لتحويل الأموال إلى سوريا؟',
         'acceptedAnswer'=>['@type'=>'Answer','text'=>'أكثر الطرق شيوعاً شركات الصرافة المرخصة والحوالات عبر شبكات المغتربين السوريين.']],
        ['@type'=>'Question','name'=>'لماذا تتغير قيمة الليرة السورية باستمرار؟',
         'acceptedAnswer'=>['@type'=>'Answer','text'=>'تتأثر الليرة السورية بالأوضاع الاقتصادية والسياسية وشُح احتياطي النقد الأجنبي.']],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<html lang="<?= defined('UI_LANG') ? UI_LANG : 'ar' ?>" dir="<?= defined('UI_DIR') ? UI_DIR : 'rtl' ?>">
<head>
  <?= nav_guard_script() ?>
  <meta charset="UTF-8">
  <?= head_extras($pdo) ?>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($seoTitle) ?></title>
  <meta name="description" content="<?= h($metaDesc) ?>">
  <link rel="canonical" href="<?= h($canonical) ?>">
  <meta property="og:type"        content="website">
  <meta property="og:title"       content="<?= h($seoTitle) ?>">
  <meta property="og:description" content="<?= h($metaDesc) ?>">
  <meta property="og:url"         content="<?= h($canonical) ?>">
  <meta name="twitter:card"        content="summary_large_image">
  <meta name="twitter:title"       content="<?= h($seoTitle) ?>">
  <meta name="twitter:description" content="<?= h($metaDesc) ?>">
  <script type="application/ld+json"><?= $breadcrumbSchema ?></script>
  <script type="application/ld+json"><?= $faqSchema ?></script>
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/main.css')) ?>">
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5506877998492189" crossorigin="anonymous"></script>
<style>
/* ════ Exchange Page — Modern Redesign ═══════════════════ */

/* ── Custom font for numbers ─────────────────────────── */
@import url('data:text/css,');
.num-display { font-variant-numeric: tabular-nums; font-feature-settings: "tnum" }

/* ── Hero ─────────────────────────────────────────────── */
.ex-hero {
  background: linear-gradient(160deg, #002a15 0%, #005c2a 35%, #007A3D 65%, #00a854 100%);
  position: relative; overflow: hidden; padding: 0;
}
.ex-hero::before {
  content: ''; position: absolute; inset: 0; pointer-events: none;
  background:
    radial-gradient(ellipse 70% 60% at 20% 50%, rgba(0,200,100,.12) 0%, transparent 70%),
    radial-gradient(ellipse 50% 80% at 80% 30%, rgba(255,255,255,.04) 0%, transparent 60%);
}
/* animated shimmer */
.ex-hero::after {
  content: ''; position: absolute; inset: 0; pointer-events: none;
  background: linear-gradient(105deg, transparent 40%, rgba(255,255,255,.04) 50%, transparent 60%);
  animation: hero-shimmer 4s ease-in-out infinite;
}
@keyframes hero-shimmer {
  0%,100%{transform:translateX(-100%)} 50%{transform:translateX(200%)}
}
.ex-hero-inner {
  position: relative; z-index: 1;
  max-width: 1100px; margin: 0 auto; padding: 32px 20px 26px;
}
.ex-breadcrumb {
  font-size: 12px; color: rgba(255,255,255,.55);
  display: flex; align-items: center; gap: 6px; margin-bottom: 22px; flex-wrap: wrap;
}
.ex-breadcrumb a { color: rgba(255,255,255,.55); transition: color .15s }
.ex-breadcrumb a:hover { color: #fff }
.ex-breadcrumb svg { width: 12px; height: 12px; stroke: rgba(255,255,255,.35); fill: none; stroke-width: 2 }

/* ── Syrian Flag SVG wrapper ──────────────────────────── */
.syria-flag-wrap {
  display: inline-flex; border-radius: 10px; overflow: hidden;
  box-shadow: 0 4px 20px rgba(0,0,0,.35), 0 0 0 2px rgba(255,255,255,.15);
  flex-shrink: 0;
  width: 90px; height: 60px;
}
.syria-flag-wrap svg { width: 100%; height: 100%; display: block }

/* ── SYP Spotlight card ────────────────────────────────── */
.syp-main-card {
  display: flex; align-items: center; gap: 22px; flex-wrap: wrap;
  background: rgba(255,255,255,.1); backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(255,255,255,.18); border-radius: 22px;
  padding: 26px 28px; margin-bottom: 22px;
  box-shadow: 0 8px 32px rgba(0,0,0,.2);
}
.syp-main-info { flex: 1; min-width: 160px }
.syp-main-label {
  font-size: 12px; color: rgba(255,255,255,.65); font-weight: 700;
  letter-spacing: 1px; margin-bottom: 6px; text-transform: uppercase;
}
.syp-main-rate {
  font-size: clamp(2.4rem, 8vw, 4rem); font-weight: 900; color: #fff;
  letter-spacing: -2px; line-height: 1; margin-bottom: 8px;
  font-variant-numeric: tabular-nums;
  text-shadow: 0 2px 20px rgba(0,0,0,.2);
}
.syp-main-unit { font-size: .45em; font-weight: 700; color: rgba(255,255,255,.7); margin-right: 6px }
.syp-main-sub { font-size: 13px; color: rgba(255,255,255,.6); display: flex; align-items: center; gap: 6px }
.syp-main-meta { text-align: center; flex-shrink: 0 }
.syp-live-dot {
  display: inline-block; width: 9px; height: 9px; border-radius: 50%;
  background: #4ade80; box-shadow: 0 0 0 3px rgba(74,222,128,.3);
  animation: pulse-dot 2s ease-in-out infinite;
}
@keyframes pulse-dot {
  0%,100%{box-shadow:0 0 0 3px rgba(74,222,128,.3)}
  50%{box-shadow:0 0 0 7px rgba(74,222,128,.08)}
}

/* mini stat */
.syp-mini-stat {
  background: rgba(0,0,0,.2); border-radius: 14px; padding: 12px 18px;
  text-align: center; min-width: 110px;
}
.syp-mini-val { font-size: 18px; font-weight: 900; color: #fff; font-variant-numeric: tabular-nums }
.syp-mini-lbl { font-size: 10px; color: rgba(255,255,255,.5); margin-top: 3px; font-weight: 600; letter-spacing: .5px }

.ex-hero-title {
  font-size: clamp(1.1rem, 3vw, 1.6rem); font-weight: 800; color: #fff; margin: 0 0 6px;
}
.ex-hero-sub { font-size: 14px; color: rgba(255,255,255,.68); margin: 0 }
.ex-hero-stripe { height: 4px; background: linear-gradient(90deg, #007A3D, #fff 50%, #000) }

/* ── Live badge ──────────────────────────────────────── */
.live-badge {
  display: inline-flex; align-items: center; gap: 5px;
  background: rgba(74,222,128,.18); color: #16a34a;
  border: 1px solid rgba(74,222,128,.35); border-radius: 20px;
  font-size: 11px; font-weight: 700; padding: 3px 10px;
}

/* ── Page body ───────────────────────────────────────── */
.ex-body { max-width: 1100px; margin: 0 auto; padding: 28px 20px 60px }
.ex-card {
  background: var(--surface); border: 1px solid var(--border-c);
  border-radius: 18px; padding: 24px; margin-bottom: 20px;
  box-shadow: 0 2px 16px rgba(0,0,0,.04);
  transition: box-shadow .2s;
}
.ex-card:hover { box-shadow: 0 4px 24px rgba(0,0,0,.07) }
.ex-card-title {
  font-size: 17px; font-weight: 800; color: var(--white); margin-bottom: 20px;
  display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
}

/* ── Modern Result Card ───────────────────────────────── */
.result-card {
  position: relative; margin-top: 20px;
  border-radius: 18px; overflow: hidden;
  display: none; transition: opacity .25s, transform .25s;
  opacity: 0; transform: translateY(6px);
}
.result-card.show { display: block; animation: rc-in .3s ease forwards }
@keyframes rc-in {
  from{opacity:0;transform:translateY(8px)}
  to{opacity:1;transform:translateY(0)}
}
.result-card.syp-card {
  background: linear-gradient(145deg, rgba(0,122,61,.06), rgba(0,200,100,.03));
  border: 1.5px solid rgba(0,122,61,.22);
}
.result-card.full-card {
  background: linear-gradient(145deg, rgba(14,165,233,.06), rgba(99,102,241,.03));
  border: 1.5px solid rgba(14,165,233,.2);
}
.rc-top-stripe { height: 4px; }
.syp-card .rc-top-stripe { background: linear-gradient(90deg, #007A3D, #22c55e, #4ade80) }
.full-card .rc-top-stripe { background: linear-gradient(90deg, #0ea5e9, #6366f1, #8b5cf6) }
.rc-inner { padding: 20px 22px 18px }
.rc-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; gap: 10px }
.rc-pair {
  display: flex; align-items: center; gap: 10px;
  background: var(--surface); border: 1px solid var(--border-c);
  border-radius: 30px; padding: 6px 14px 6px 6px;
}
.rc-pair-flags { display: flex; align-items: center; gap: 4px; font-size: 20px }
.rc-pair-sep {
  display: flex; align-items: center; color: var(--muted);
  margin: 0 4px;
}
.rc-pair-sep svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2.5 }
.rc-pair-label { font-size: 12px; font-weight: 700; color: var(--muted); margin-right: 4px }

/* Copy button */
.rc-copy-btn {
  display: inline-flex; align-items: center; gap: 6px;
  background: var(--surface); border: 1.5px solid var(--border-c);
  border-radius: 10px; padding: 7px 14px;
  font-size: 12px; font-weight: 700; color: var(--muted);
  cursor: pointer; transition: all .18s; font-family: inherit;
  white-space: nowrap;
}
.rc-copy-btn:hover { border-color: #007A3D; color: #007A3D; background: rgba(0,122,61,.06) }
.rc-copy-btn.copied { border-color: #16a34a; color: #16a34a; background: rgba(22,163,74,.08) }
.full-card .rc-copy-btn:hover { border-color: var(--cyan); color: var(--cyan); background: rgba(14,165,233,.06) }

/* Amount */
.rc-amount-row { display: flex; align-items: baseline; gap: 8px; margin-bottom: 8px }
.rc-amount {
  font-size: clamp(2.2rem, 8vw, 3.4rem); font-weight: 900;
  letter-spacing: -2px; line-height: 1;
  font-variant-numeric: tabular-nums;
}
.syp-card .rc-amount {
  background: linear-gradient(135deg, #005a2e 0%, #00a854 60%, #22c55e 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.full-card .rc-amount {
  background: linear-gradient(135deg, #0369a1, #0ea5e9, #6366f1);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.rc-unit {
  font-size: 1.1rem; font-weight: 800; color: var(--muted);
  padding-bottom: 4px;
}
.rc-equation { font-size: 13px; color: var(--muted); margin-bottom: 14px; line-height: 1.6 }

/* Chips */
.rc-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px }
.rc-chip {
  display: inline-flex; align-items: center; gap: 5px;
  background: var(--surface); border: 1px solid var(--border-c);
  border-radius: 20px; padding: 5px 13px;
  font-size: 12px; font-weight: 600; color: var(--muted);
  font-variant-numeric: tabular-nums;
}
.syp-card .rc-chip  { border-color: rgba(0,122,61,.2) }
.full-card .rc-chip { border-color: rgba(14,165,233,.2) }

/* Footer row */
.rc-footer {
  display: flex; align-items: center; justify-content: space-between;
  gap: 8px; padding-top: 12px; border-top: 1px solid var(--border-c);
  font-size: 11px; color: var(--muted); flex-wrap: wrap;
}
.rc-footer a { color: var(--cyan) }

/* ── SYP Converter inputs ─────────────────────────────── */
.syp-conv-grid {
  display: grid; grid-template-columns: 1fr auto 1fr; gap: 14px; align-items: end;
}
.syp-conv-field label {
  display: flex; align-items: center; gap: 6px;
  font-size: 12px; font-weight: 700; color: var(--muted);
  margin-bottom: 8px; letter-spacing: .5px; text-transform: uppercase;
}
.syp-conv-input {
  width: 100%; padding: 15px 16px;
  background: var(--surface-2); border: 2px solid var(--border-c);
  border-radius: 14px; font-size: 22px; font-weight: 700;
  color: var(--white); outline: none; transition: border-color .18s, box-shadow .18s;
  font-variant-numeric: tabular-nums; direction: ltr; text-align: right;
  font-family: inherit;
}
.syp-conv-input:focus { border-color: #007A3D; box-shadow: 0 0 0 4px rgba(0,122,61,.1) }
.syp-conv-badge {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 7px 12px; border-radius: 8px; font-size: 13px; font-weight: 700;
  color: var(--muted); margin-top: 7px;
  background: var(--surface-2); border: 1px solid var(--border-c);
}
.syp-swap-btn {
  display: flex; align-items: center; justify-content: center;
  width: 46px; height: 46px; border-radius: 50%;
  background: linear-gradient(135deg, #007A3D, #00a854);
  color: #fff; border: none; cursor: pointer; font-size: 18px;
  transition: all .25s; flex-shrink: 0; margin-bottom: 2px;
  box-shadow: 0 4px 14px rgba(0,122,61,.35);
}
.syp-swap-btn:hover { transform: rotate(180deg); box-shadow: 0 6px 20px rgba(0,122,61,.4) }
.syp-swap-btn:active { transform: rotate(180deg) scale(.95) }

/* ── Arab Currencies Grid ────────────────────────────── */
.arab-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
  gap: 12px;
}
.arab-card {
  background: var(--surface-2); border: 1px solid var(--border-c);
  border-radius: 16px; padding: 16px 14px; display: flex;
  align-items: center; gap: 12px;
  transition: all .22s cubic-bezier(.4,0,.2,1); cursor: default;
}
.arab-card:hover {
  transform: translateY(-3px) scale(1.02);
  box-shadow: 0 8px 24px rgba(0,0,0,.1);
  border-color: rgba(0,122,61,.35);
}
.arab-card.syp-highlight {
  background: linear-gradient(145deg, rgba(0,122,61,.1), rgba(0,200,100,.05));
  border-color: rgba(0,122,61,.4);
  box-shadow: 0 4px 16px rgba(0,122,61,.12);
}
.arab-card-flag { font-size: 30px; flex-shrink: 0 }
.arab-card-body { min-width: 0; flex: 1 }
.arab-card-name { font-size: 11px; color: var(--muted); margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis }
.arab-card-rate {
  font-size: 19px; font-weight: 900; color: var(--white);
  font-variant-numeric: tabular-nums; transition: all .4s;
}
.arab-card.syp-highlight .arab-card-rate {
  background: linear-gradient(135deg, #007A3D, #22c55e);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  font-size: 21px;
}
.arab-card-code { font-size: 10px; font-weight: 700; color: var(--muted); margin-top: 2px; letter-spacing: .8px }
.rate-loading { color: var(--muted) !important; -webkit-text-fill-color: var(--muted) !important }

/* ── Full Converter ──────────────────────────────────── */
.full-conv-grid {
  display: grid; grid-template-columns: 1fr auto 1fr; gap: 10px; align-items: end;
}
.conv-field label {
  display: block; font-size: 12px; font-weight: 600; color: var(--muted); margin-bottom: 6px;
}
.conv-field input, .conv-field select {
  width: 100%; padding: 13px 14px;
  background: var(--surface-2); border: 1.5px solid var(--border-c);
  border-radius: 12px; font-size: 15px; color: var(--white);
  outline: none; transition: border-color .18s; font-variant-numeric: tabular-nums;
  font-family: inherit;
}
.conv-field input { direction: ltr; text-align: right }
.conv-field input:focus, .conv-field select:focus { border-color: var(--cyan) }
.conv-field select { cursor: pointer }
.conv-swap-btn {
  display: flex; align-items: center; justify-content: center;
  width: 42px; height: 42px; border-radius: 50%;
  background: var(--surface-2); border: 1.5px solid var(--border-c);
  cursor: pointer; transition: all .22s; color: var(--muted); flex-shrink: 0;
}
.conv-swap-btn:hover { background: var(--cyan); color: #fff; border-color: var(--cyan); transform: rotate(180deg) }

/* ── Rates Table ─────────────────────────────────────── */
.rates-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 12px }
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
.syp-badge {
  display: inline-flex; align-items: center;
  background: rgba(0,122,61,.12); color: #007A3D;
  font-size: 10px; font-weight: 700; padding: 1px 8px; border-radius: 10px;
  margin-left: 6px; border: 1px solid rgba(0,122,61,.25);
}

/* ── Source / meta bar ───────────────────────────────── */
.source-bar {
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 8px; font-size: 11px; color: var(--muted); margin-top: 14px;
}
.source-bar a { color: var(--cyan) }

/* ── SEO / FAQ ───────────────────────────────────────── */
.seo-prose h3 { font-size: 16px; font-weight: 800; color: var(--white); margin: 22px 0 10px; display: flex; align-items: center; gap: 8px }
.seo-prose p  { font-size: 14px; color: var(--muted); line-height: 1.85; margin-bottom: 12px }
.seo-prose ul, .seo-prose ol { margin: 8px 20px 14px; display: flex; flex-direction: column; gap: 7px }
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
.faq-body { padding: 0 18px 16px; font-size: 14px; color: var(--muted); line-height: 1.75 }

/* ── Responsive ──────────────────────────────────────── */
@media (max-width: 680px) {
  .syp-conv-grid { grid-template-columns: 1fr }
  .syp-swap-btn { width: 100%; height: 40px; border-radius: 12px; margin: 0 }
  .full-conv-grid { grid-template-columns: 1fr }
  .conv-swap-btn { width: 100%; height: 38px; border-radius: 10px }
  .syp-main-card { padding: 18px 16px; gap: 14px }
  .syp-main-rate { font-size: 2.2rem }
  .ex-body { padding: 18px 14px 50px }
  .ex-card { padding: 18px 16px }
  .rc-inner { padding: 16px 16px 14px }
  .rc-amount { font-size: 2rem }
}
@media (max-width: 420px) {
  .arab-grid { grid-template-columns: 1fr 1fr }
  .rc-header { flex-direction: column; align-items: flex-start }
}
</style>
</head>
<body>
<?php render_site_header('', 'exchange'); ?>

<!-- ── Hero ─────────────────────────────────────────── -->
<section class="ex-hero">
  <div class="ex-hero-inner">
    <nav class="ex-breadcrumb" aria-label="مسار التنقل">
      <a href="<?= h(url('')) ?>">الرئيسية</a>
      <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
      <span>أسعار الصرف</span>
    </nav>

    <div class="syp-main-card">
      <!-- Syrian flag — green/white/black post-2024 revolutionary flag -->
      <div class="syria-flag-wrap" title="الجمهورية العربية السورية" aria-label="العلم السوري">
        <svg viewBox="0 0 300 200" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
          <rect width="300" height="66" fill="#007A3D"/>
          <rect y="66" width="300" height="68" fill="#FFFFFF"/>
          <rect y="134" width="300" height="66" fill="#000000"/>
          <!-- 3 red 5-pointed stars in white stripe -->
          <polygon fill="#CE1126" transform="translate(75,100)"
            points="0,-14 3.13,-4.31 13.30,-4.31 5.07,1.65 8.19,11.96 0,6 -8.19,11.96 -5.07,1.65 -13.30,-4.31 -3.13,-4.31"/>
          <polygon fill="#CE1126" transform="translate(150,100)"
            points="0,-14 3.13,-4.31 13.30,-4.31 5.07,1.65 8.19,11.96 0,6 -8.19,11.96 -5.07,1.65 -13.30,-4.31 -3.13,-4.31"/>
          <polygon fill="#CE1126" transform="translate(225,100)"
            points="0,-14 3.13,-4.31 13.30,-4.31 5.07,1.65 8.19,11.96 0,6 -8.19,11.96 -5.07,1.65 -13.30,-4.31 -3.13,-4.31"/>
        </svg>
      </div>

      <div class="syp-main-info">
        <div class="syp-main-label">الدولار الأمريكي / الليرة السورية</div>
        <div class="syp-main-rate num-display" id="syp-hero-rate">
          <?= number_format($sypRate, 0) ?><span class="syp-main-unit">ل.س</span>
        </div>
        <div class="syp-main-sub">
          <span class="syp-live-dot"></span>
          سعر السوق · <?= h($today) ?>
        </div>
      </div>

      <div class="syp-mini-stat">
        <div class="syp-mini-val num-display" id="syp-usd-per-k">
          <?= number_format(1000 / $sypRate, 5) ?>
        </div>
        <div class="syp-mini-lbl">دولار / 1,000 ل.س</div>
      </div>
    </div>

    <h1 class="ex-hero-title">
      أسعار الصرف اليوم <span class="live-badge">
        <span class="syp-live-dot" style="width:7px;height:7px;box-shadow:none"></span>
        مباشر
      </span>
    </h1>
    <p class="ex-hero-sub">الليرة السورية والعملات العربية والعالمية — محوّل فوري بأكثر من 170 عملة</p>
  </div>
</section>
<div class="ex-hero-stripe" aria-hidden="true"></div>

<div class="ex-body">

  <!-- ── SYP Quick Converter ─────────────────────────── -->
  <section class="ex-card" style="border-right:4px solid #007A3D">
    <h2 class="ex-card-title">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#007A3D" stroke-width="2.5"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/><path d="M8 12h8M12 8l4 4-4 4"/></svg>
      محوّل الليرة السورية — دولار / ليرة
    </h2>

    <div class="syp-conv-grid">
      <div class="syp-conv-field">
        <label for="syp-usd-in">
          <span style="font-size:18px">🇺🇸</span>
          USD — دولار أمريكي
        </label>
        <input type="number" id="syp-usd-in" class="syp-conv-input"
               value="1" min="0" step="any" placeholder="0"
               oninput="sypConvFromUsd()">
        <div class="syp-conv-badge"><span>🇺🇸</span> دولار أمريكي</div>
      </div>

      <button class="syp-swap-btn" onclick="sypSwap()" aria-label="تبديل الاتجاه">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <path d="M7 16V4m0 0L3 8m4-4l4 4"/><path d="M17 8v12m0 0l4-4m-4 4l-4-4"/>
        </svg>
      </button>

      <div class="syp-conv-field">
        <label for="syp-lira-in">
          <span style="font-size:18px">🇸🇾</span>
          SYP — ليرة سورية
        </label>
        <input type="number" id="syp-lira-in" class="syp-conv-input"
               min="0" step="any" placeholder="0"
               oninput="sypConvFromSyp()">
        <div class="syp-conv-badge" style="background:rgba(0,122,61,.08);border-color:rgba(0,122,61,.25)">
          <span>🇸🇾</span> ليرة سورية
        </div>
      </div>
    </div>

    <!-- Modern result card -->
    <div class="result-card syp-card show" id="syp-result">
      <div class="rc-top-stripe"></div>
      <div class="rc-inner">
        <div class="rc-header">
          <div class="rc-pair">
            <div class="rc-pair-flags">
              <span id="rc-syp-from-flag">🇺🇸</span>
              <div class="rc-pair-sep">
                <svg viewBox="0 0 24 24"><polyline points="5 12 19 12 14 7"/><polyline points="14 17 19 12"/></svg>
              </div>
              <span id="rc-syp-to-flag">🇸🇾</span>
            </div>
            <span class="rc-pair-label" id="rc-syp-pair-lbl">USD → SYP</span>
          </div>
          <button class="rc-copy-btn" id="syp-copy-btn" onclick="copyRc('syp-result-raw','syp-copy-btn')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
            نسخ
          </button>
        </div>

        <div class="rc-amount-row">
          <div class="rc-amount num-display" id="syp-result-val"><?= number_format($sypRate, 0) ?></div>
          <div class="rc-unit" id="rc-syp-unit">ل.س</div>
        </div>
        <input type="hidden" id="syp-result-raw" value="<?= $sypRate ?>">

        <div class="rc-equation" id="syp-result-label">
          1 دولار أمريكي = <?= number_format($sypRate, 0) ?> ليرة سورية
        </div>

        <div class="rc-chips">
          <span class="rc-chip" id="syp-chip1">
            <span>💵</span> 1 USD = <?= number_format($sypRate, 0) ?> SYP
          </span>
          <span class="rc-chip" id="syp-chip2">
            <span>💴</span> 1,000 SYP = <?= number_format(1000 / $sypRate, 4) ?> USD
          </span>
          <span class="rc-chip" id="syp-chip3">
            <span>💶</span> 100 USD = <?= number_format($sypRate * 100, 0) ?> SYP
          </span>
        </div>

        <div class="rc-footer">
          <span>
            ⚡ سعر يُستخدم: <strong class="num-display" id="syp-rate-display"><?= number_format($sypRate, 0) ?></strong> ل.س/دولار
            · <span id="syp-rate-source">سعر مرجعي</span>
          </span>
          <a href="<?= h(url('gold')) ?>" style="color:var(--cyan);font-size:11px">سعر الذهب ←</a>
        </div>
      </div>
    </div>
  </section>

  <!-- ── Arab Currencies Grid ─────────────────────────── -->
  <section class="ex-card">
    <h2 class="ex-card-title">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 010 20"/></svg>
      العملات العربية — مقابل الدولار الأمريكي
    </h2>
    <div class="arab-grid" id="arab-grid">
      <!-- SYP card (hardcoded, updated by JS) -->
      <div class="arab-card syp-highlight">
        <div class="arab-card-flag">
          <!-- tiny flag inline SVG -->
          <svg viewBox="0 0 30 20" width="34" height="22" xmlns="http://www.w3.org/2000/svg" style="border-radius:3px;box-shadow:0 1px 4px rgba(0,0,0,.3)">
            <rect width="30" height="6.7" fill="#007A3D"/>
            <rect y="6.7" width="30" height="6.6" fill="#fff"/>
            <rect y="13.3" width="30" height="6.7" fill="#000"/>
            <polygon fill="#CE1126" transform="translate(7.5,10)" points="0,-3 .7,-1 2.85,-1 1.15,.4 1.85,2.4 0,1.15 -1.85,2.4 -1.15,.4 -2.85,-1 -.7,-1"/>
            <polygon fill="#CE1126" transform="translate(15,10)" points="0,-3 .7,-1 2.85,-1 1.15,.4 1.85,2.4 0,1.15 -1.85,2.4 -1.15,.4 -2.85,-1 -.7,-1"/>
            <polygon fill="#CE1126" transform="translate(22.5,10)" points="0,-3 .7,-1 2.85,-1 1.15,.4 1.85,2.4 0,1.15 -1.85,2.4 -1.15,.4 -2.85,-1 -.7,-1"/>
          </svg>
        </div>
        <div class="arab-card-body">
          <div class="arab-card-name">الليرة السورية</div>
          <div class="arab-card-rate num-display" id="arab-SYP"><?= number_format($sypRate, 0) ?></div>
          <div class="arab-card-code">SYP</div>
        </div>
      </div>
      <?php
      $arabList = [
        'SAR'=>['الريال السعودي','🇸🇦'],
        'AED'=>['الدرهم الإماراتي','🇦🇪'],
        'EGP'=>['الجنيه المصري','🇪🇬'],
        'KWD'=>['الدينار الكويتي','🇰🇼'],
        'QAR'=>['الريال القطري','🇶🇦'],
        'BHD'=>['الدينار البحريني','🇧🇭'],
        'OMR'=>['الريال العُماني','🇴🇲'],
        'JOD'=>['الدينار الأردني','🇯🇴'],
        'IQD'=>['الدينار العراقي','🇮🇶'],
        'LBP'=>['الليرة اللبنانية','🇱🇧'],
        'MAD'=>['الدرهم المغربي','🇲🇦'],
        'TND'=>['الدينار التونسي','🇹🇳'],
        'DZD'=>['الدينار الجزائري','🇩🇿'],
        'SDG'=>['الجنيه السوداني','🇸🇩'],
        'YER'=>['الريال اليمني','🇾🇪'],
        'LYD'=>['الدينار الليبي','🇱🇾'],
      ];
      foreach ($arabList as $code => [$name, $flag]):
      ?>
      <div class="arab-card">
        <div class="arab-card-flag"><?= $flag ?></div>
        <div class="arab-card-body">
          <div class="arab-card-name"><?= h($name) ?></div>
          <div class="arab-card-rate rate-loading num-display" id="arab-<?= h($code) ?>">···</div>
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

  <!-- ── Full Currency Converter ──────────────────────── -->
  <section class="ex-card">
    <h2 class="ex-card-title">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--cyan)" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
      محوّل العملات الشامل — 170+ عملة
    </h2>

    <div class="full-conv-grid">
      <div class="conv-field">
        <label>المبلغ</label>
        <input type="number" id="cv-amt" value="1" min="0" step="any"
               oninput="fullConvert()" placeholder="أدخل المبلغ">
      </div>
      <button class="conv-swap-btn" onclick="fullSwap()" aria-label="تبديل">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <path d="M7 16V4m0 0L3 8m4-4l4 4"/><path d="M17 8v12m0 0l4-4m-4 4l-4-4"/>
        </svg>
      </button>
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
          <option value="IQD">🇮🇶 IQD — دينار عراقي</option>
          <option value="LBP">🇱🇧 LBP — ليرة لبنانية</option>
          <option value="TRY">🇹🇷 TRY — ليرة تركية</option>
          <option value="JPY">🇯🇵 JPY — ين ياباني</option>
          <option value="CNY">🇨🇳 CNY — يوان صيني</option>
          <option value="INR">🇮🇳 INR — روبية هندية</option>
          <option value="CHF">🇨🇭 CHF — فرنك سويسري</option>
          <option value="CAD">🇨🇦 CAD — دولار كندي</option>
          <option value="AUD">🇦🇺 AUD — دولار أسترالي</option>
          <option value="RUB">🇷🇺 RUB — روبل روسي</option>
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
          <option value="IQD">🇮🇶 IQD — دينار عراقي</option>
          <option value="LBP">🇱🇧 LBP — ليرة لبنانية</option>
          <option value="TRY">🇹🇷 TRY — ليرة تركية</option>
          <option value="JPY">🇯🇵 JPY — ين ياباني</option>
          <option value="CNY">🇨🇳 CNY — يوان صيني</option>
          <option value="INR">🇮🇳 INR — روبية هندية</option>
          <option value="CHF">🇨🇭 CHF — فرنك سويسري</option>
          <option value="CAD">🇨🇦 CAD — دولار كندي</option>
          <option value="AUD">🇦🇺 AUD — دولار أسترالي</option>
          <option value="RUB">🇷🇺 RUB — روبل روسي</option>
        </select>
      </div>
    </div>

    <!-- Modern full-converter result card -->
    <div class="result-card full-card show" id="cv-result-box">
      <div class="rc-top-stripe"></div>
      <div class="rc-inner">
        <div class="rc-header">
          <div class="rc-pair">
            <div class="rc-pair-flags">
              <span id="rc-cv-from-flag">🇺🇸</span>
              <div class="rc-pair-sep">
                <svg viewBox="0 0 24 24"><polyline points="5 12 19 12 14 7"/><polyline points="14 17 19 12"/></svg>
              </div>
              <span id="rc-cv-to-flag">🇸🇾</span>
            </div>
            <span class="rc-pair-label" id="rc-cv-pair-lbl">USD → SYP</span>
          </div>
          <button class="rc-copy-btn" id="cv-copy-btn" onclick="copyRc('cv-result-raw','cv-copy-btn')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
            نسخ
          </button>
        </div>

        <div class="rc-amount-row">
          <div class="rc-amount num-display" id="cv-result-big">···</div>
          <div class="rc-unit" id="rc-cv-unit">SYP</div>
        </div>
        <input type="hidden" id="cv-result-raw" value="">

        <div class="rc-equation" id="cv-result-label"></div>
        <div class="rc-chips">
          <span class="rc-chip" id="cv-chip1"></span>
          <span class="rc-chip" id="cv-chip2"></span>
        </div>

        <div class="rc-footer">
          <span id="cv-result-rate" style="font-size:11px;color:var(--muted)"></span>
          <span id="cv-loading" style="font-size:11px;color:var(--muted)">⏳ جارٍ تحميل الأسعار…</span>
        </div>
      </div>
    </div>
  </section>

  <!-- ── Full Rates Table ─────────────────────────────── -->
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
          <tr><td colspan="5" style="text-align:center;padding:28px;color:var(--muted)">⏳ جارٍ تحميل الأسعار…</td></tr>
        </tbody>
      </table>
    </div>
    <div class="source-bar">
      <span>المصدر: fawazahmed0 Exchange API + سعر SYP المُحدَّث يدوياً</span>
      <a href="<?= h(url('gold')) ?>">سعر الذهب ←</a>
    </div>
  </section>

  <!-- ── SEO Content ───────────────────────────────────── -->
  <section class="ex-card seo-prose">
    <h2 class="ex-card-title">
      <span style="font-size:1.3rem">📖</span>
      دليل شامل — الليرة السورية وأسعار الصرف
    </h2>

    <h3>🇸🇾 سعر الدولار مقابل الليرة السورية اليوم</h3>
    <p>يعكس السعر الحالي <strong><?= number_format($sypRate, 0) ?> ليرة سورية لكل دولار</strong> معدل السوق الحر. شهدت الليرة السورية (SYP) تقلبات حادة منذ عام 2011، وبعد التغيرات السياسية في نهاية 2024 بدأت تستعيد شيئاً من استقرارها النسبي. السعر المعروض هو سعر مرجعي تقريبي وقد يختلف في مكاتب الصرافة.</p>

    <h3>📊 كيف يُحسب سعر الليرة السورية؟</h3>
    <p>لا تتوفر بيانات SYP من المصادر المالية الدولية بشكل منتظم. على yassota يُحدَّث السعر بانتظام بناءً على:</p>
    <ul>
      <li>معدل السوق الحر في المراكز الرئيسية (دمشق، حلب)</li>
      <li>أسعار منصات التداول الإلكترونية المتخصصة بالعملات السورية</li>
      <li>متوسط أسعار شبكات حوالات المغتربين السوريين</li>
    </ul>

    <h3>💱 أبرز العملات العربية مقابل الدولار</h3>
    <p>تتفاوت العملات العربية بين مربوطة بالدولار (الريال السعودي 3.75 والدرهم الإماراتي 3.67) ومرنة تتأثر بالأسواق (الجنيه المصري). الليرة السورية من أكثر العملات التي شهدت تقلبات خلال العقد الماضي.</p>

    <h3>💸 كيف ترسل الأموال إلى سوريا؟</h3>
    <ul>
      <li><strong>Western Union وMoneyGram:</strong> متاحتان في بعض الدول بخدمة إرسال إلى سوريا</li>
      <li><strong>الحوالة البنكية:</strong> عبر المصارف التجارية إلى المصارف السورية المتاحة</li>
      <li><strong>شبكات الحوالة:</strong> تُستخدم على نطاق واسع — تأكد دائماً من الترخيص القانوني</li>
      <li><strong>Wise وRemitly:</strong> تحقق من توافر الخدمة لسوريا حسب بلدك</li>
    </ul>

    <h3>❓ أسئلة شائعة</h3>
    <details class="faq-item">
      <summary class="faq-summary">كم سعر الدولار مقابل الليرة السورية اليوم؟</summary>
      <div class="faq-body">السعر المرجعي اليوم هو تقريباً <strong><?= number_format($sypRate, 0) ?> ليرة سورية لكل دولار</strong>. قد يختلف السعر الفعلي في الصرافات المحلية ببضع مئات من الليرات.</div>
    </details>
    <details class="faq-item">
      <summary class="faq-summary">لماذا لا يوجد سعر رسمي ثابت للليرة السورية؟</summary>
      <div class="faq-body">يعمل سوق الصرف في سوريا بنظام ازدواجي بين السعر الرسمي للمصرف المركزي وسعر السوق الحر. في ظروف الأزمات وشُح الاحتياطيات يكون الفارق بينهما كبيراً.</div>
    </details>
    <details class="faq-item">
      <summary class="faq-summary">ما العوامل التي تؤثر في سعر الليرة السورية؟</summary>
      <div class="faq-body">أبرز العوامل: احتياطي النقد الأجنبي، مستوى الاستيراد، حجم تحويلات المغتربين، الأوضاع السياسية والأمنية، ونشاط الاقتصاد غير الرسمي.</div>
    </details>
    <details class="faq-item">
      <summary class="faq-summary">هل يمكن الثقة بأسعار الصرف المعروضة هنا؟</summary>
      <div class="faq-body">الأسعار تُحدَّث بانتظام وتعكس متوسط السوق. للمعاملات المالية الكبيرة نوصي دائماً بالتحقق من صرافة موثوقة محلياً.</div>
    </details>
  </section>

</div><!-- /ex-body -->

<?php render_site_footer(); ?>

<script>
/* ═══════════════════════════════════════════════════════
   Exchange Page — Modern JS
══════════════════════════════════════════════════════ */
var FX       = { USD: 1, SYP: <?= (float)$sypRate ?> };
var FX_LOADED = false;
var SYP_RATE  = <?= (float)$sypRate ?>;

/* Currency meta: [name, flag-emoji] */
var CMETA = {
  SYP:['الليرة السورية','🇸🇾'],  SAR:['الريال السعودي','🇸🇦'],
  AED:['الدرهم الإماراتي','🇦🇪'], EGP:['الجنيه المصري','🇪🇬'],
  KWD:['الدينار الكويتي','🇰🇼'],  QAR:['الريال القطري','🇶🇦'],
  BHD:['الدينار البحريني','🇧🇭'], OMR:['الريال العُماني','🇴🇲'],
  JOD:['الدينار الأردني','🇯🇴'],  IQD:['الدينار العراقي','🇮🇶'],
  LBP:['الليرة اللبنانية','🇱🇧'], MAD:['الدرهم المغربي','🇲🇦'],
  TND:['الدينار التونسي','🇹🇳'],  DZD:['الدينار الجزائري','🇩🇿'],
  SDG:['الجنيه السوداني','🇸🇩'],  YER:['الريال اليمني','🇾🇪'],
  LYD:['الدينار الليبي','🇱🇾'],   EUR:['اليورو','🇪🇺'],
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
  CZK:['الكرونة التشيكية','🇨🇿'],  USD:['الدولار الأمريكي','🇺🇸'],
};

/* ── Animated counter ─────────────────────────────── */
function animCount(el, target, dur, dec) {
  dur = dur || 500;
  dec = dec === undefined ? 0 : dec;
  var t0 = null, from = parseFloat(el.dataset.countFrom || 0) || 0;
  el.dataset.countFrom = target;
  function tick(ts) {
    if (!t0) t0 = ts;
    var p = Math.min((ts - t0) / dur, 1);
    var e = 1 - Math.pow(1 - p, 3); /* ease-out cubic */
    var cur = from + (target - from) * e;
    el.textContent = fmt(cur, dec);
    if (p < 1) requestAnimationFrame(tick);
    else el.textContent = fmt(target, dec);
  }
  requestAnimationFrame(tick);
}

/* ── Copy to clipboard ────────────────────────────── */
function copyRc(rawId, btnId) {
  var raw = document.getElementById(rawId).value;
  var btn = document.getElementById(btnId);
  var num = parseFloat(raw) || 0;
  var text = num.toLocaleString('en-US');
  function done() {
    var orig = btn.innerHTML;
    btn.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> تم النسخ';
    btn.classList.add('copied');
    setTimeout(function(){ btn.innerHTML = orig; btn.classList.remove('copied'); }, 2200);
  }
  if (navigator.clipboard) {
    navigator.clipboard.writeText(text).then(done).catch(function(){ fbCopy(text); done(); });
  } else { fbCopy(text); done(); }
}
function fbCopy(t) {
  var ta = document.createElement('textarea');
  ta.value = t; ta.style.position = 'fixed'; ta.style.opacity = '0';
  document.body.appendChild(ta); ta.focus(); ta.select(); document.execCommand('copy');
  document.body.removeChild(ta);
}

/* ── fawazahmed0 API ─────────────────────────────── */
function loadFX() {
  var primary  = 'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/usd.json';
  var fallback = 'https://latest.currency-api.pages.dev/v1/currencies/usd.json';
  function process(d) {
    var raw = d.usd || d.USD || {};
    Object.keys(raw).forEach(function(k){ FX[k.toUpperCase()] = raw[k]; });
    FX['USD'] = 1;
    /* prefer server SYP rate unless API is wildly different */
    if (!FX['SYP'] || Math.abs(FX['SYP'] - SYP_RATE) / SYP_RATE > 0.5) FX['SYP'] = SYP_RATE;
    FX_LOADED = true;
    onFxReady();
  }
  fetch(primary)
    .then(function(r){ return r.ok ? r.json() : Promise.reject() })
    .then(process)
    .catch(function(){
      fetch(fallback)
        .then(function(r){ return r.ok ? r.json() : Promise.reject() })
        .then(process)
        .catch(function(){
          var cl = document.getElementById('cv-loading');
          if (cl) cl.textContent = '⚠️ تعذّر تحميل الأسعار — أعد تحميل الصفحة';
        });
    });
}

function onFxReady() {
  var cl = document.getElementById('cv-loading');
  if (cl) cl.style.display = 'none';

  /* Update arab cards */
  Object.keys(CMETA).forEach(function(code){
    var el = document.getElementById('arab-' + code);
    if (!el) return;
    var rate = FX[code]; if (!rate) return;
    var dec = decFor(code);
    if (FX_LOADED && code === 'SYP') {
      animCount(el, FX['SYP'], 600, dec);
    } else {
      el.textContent = fmt(rate, dec);
    }
    el.classList.remove('rate-loading');
  });
  var upd = document.getElementById('arab-updated');
  if (upd) upd.textContent = 'محدَّث: ' + (new Date()).toLocaleDateString('ar-SA', {year:'numeric',month:'long',day:'numeric'});

  /* Sync hero */
  updateHero(FX['SYP'] || SYP_RATE);

  buildTable();
  fullConvert();
  sypConvFromUsd();
}

/* ── SYP Converter ───────────────────────────────── */
function sypConvFromUsd() {
  var usd  = parseFloat(document.getElementById('syp-usd-in').value) || 0;
  var rate = FX['SYP'] || SYP_RATE;
  var syp  = usd * rate;
  updateSypResult(usd, syp, rate, 'usd');
  var li = document.getElementById('syp-lira-in');
  if (document.activeElement !== li) li.value = syp > 0 ? Math.round(syp) : '';
}
function sypConvFromSyp() {
  var syp  = parseFloat(document.getElementById('syp-lira-in').value) || 0;
  var rate = FX['SYP'] || SYP_RATE;
  var usd  = syp / rate;
  updateSypResult(usd, syp, rate, 'syp');
  var ui = document.getElementById('syp-usd-in');
  if (document.activeElement !== ui) ui.value = usd > 0 ? usd.toFixed(4) : '';
}
function sypSwap() {
  var ui = document.getElementById('syp-usd-in'), li = document.getElementById('syp-lira-in');
  var rate = FX['SYP'] || SYP_RATE;
  var tmpUsd = parseFloat(ui.value)||0, tmpSyp = parseFloat(li.value)||0;
  ui.value = tmpSyp ? (tmpSyp / rate).toFixed(4) : '';
  li.value = tmpUsd ? Math.round(tmpUsd * rate) : '';
  sypConvFromUsd();
}

function updateSypResult(usd, syp, rate, dir) {
  var res = document.getElementById('syp-result');
  var amtEl = document.getElementById('syp-result-val');
  var raw   = document.getElementById('syp-result-raw');

  if (dir === 'usd') {
    /* USD → SYP */
    document.getElementById('rc-syp-from-flag').textContent = '🇺🇸';
    document.getElementById('rc-syp-to-flag').textContent   = '🇸🇾';
    document.getElementById('rc-syp-pair-lbl').textContent  = 'USD ← SYP';
    document.getElementById('rc-syp-unit').textContent      = 'ل.س';
    var target = Math.round(syp);
    raw.value = target;
    animCount(amtEl, target, 420, 0);
    document.getElementById('syp-result-label').textContent =
      fmt(usd, usd < 100 ? 2 : 0) + ' دولار = ' + fmt(syp, 0) + ' ليرة سورية';
    document.getElementById('syp-chip1').innerHTML = '💵 1 USD = ' + fmt(rate, 0) + ' SYP';
    document.getElementById('syp-chip2').innerHTML = '💴 1,000 SYP = ' + (1000/rate).toFixed(5) + ' USD';
    document.getElementById('syp-chip3').innerHTML = '💶 100 USD = ' + fmt(rate*100, 0) + ' SYP';
  } else {
    /* SYP → USD */
    document.getElementById('rc-syp-from-flag').textContent = '🇸🇾';
    document.getElementById('rc-syp-to-flag').textContent   = '🇺🇸';
    document.getElementById('rc-syp-pair-lbl').textContent  = 'SYP ← USD';
    document.getElementById('rc-syp-unit').textContent      = 'USD';
    var target2 = usd;
    raw.value = target2;
    /* small decimal — no counter anim */
    amtEl.textContent = fmt(target2, 4);
    amtEl.dataset.countFrom = target2;
    document.getElementById('syp-result-label').textContent =
      fmt(syp, 0) + ' ليرة سورية = ' + fmt(usd, 4) + ' دولار أمريكي';
    document.getElementById('syp-chip1').innerHTML = '💵 1 USD = ' + fmt(rate, 0) + ' SYP';
    document.getElementById('syp-chip2').innerHTML = '💴 1,000 SYP = ' + (1000/rate).toFixed(5) + ' USD';
    document.getElementById('syp-chip3').innerHTML = '💶 10,000 SYP = ' + fmt(10000/rate, 3) + ' USD';
  }

  document.getElementById('syp-rate-display').textContent = fmt(rate, 0);
  document.getElementById('syp-rate-source').textContent  = FX_LOADED ? 'fawazahmed0 API' : 'سعر مرجعي';
  res.classList.add('show');
  updateHero(rate);
}

function updateHero(rate) {
  var el = document.getElementById('syp-hero-rate');
  if (el) el.innerHTML = fmt(rate, 0) + '<span class="syp-main-unit">ل.س</span>';
  var kEl = document.getElementById('syp-usd-per-k');
  if (kEl) kEl.textContent = (1000/rate).toFixed(5);
}

/* ── Full Converter ──────────────────────────────── */
function fullConvert() {
  if (!FX_LOADED) return;
  var amt  = parseFloat(document.getElementById('cv-amt').value) || 0;
  var from = document.getElementById('cv-from').value;
  var to   = document.getElementById('cv-to').value;
  var usd  = amt / (FX[from] || 1);
  var out  = usd * (FX[to] || 1);
  var rate = (FX[to]||1) / (FX[from]||1);
  var revR = (FX[from]||1) / (FX[to]||1);
  var dec  = decFor(to), decF = decFor(from);

  var ri = document.getElementById('cv-result-input');
  if (document.activeElement !== ri) ri.value = out.toFixed(Math.min(dec, 8));

  var amtEl = document.getElementById('cv-result-big');
  document.getElementById('cv-result-raw').value = out;

  if (dec === 0 && out > 1) {
    animCount(amtEl, Math.round(out), 380, 0);
  } else {
    amtEl.textContent = fmt(out, dec);
    amtEl.dataset.countFrom = out;
  }
  document.getElementById('rc-cv-unit').textContent = to;
  document.getElementById('rc-cv-pair-lbl').textContent = from + ' ← ' + to;
  /* flags */
  var mf = CMETA[from], mt = CMETA[to];
  document.getElementById('rc-cv-from-flag').textContent = mf ? mf[1] : '💱';
  document.getElementById('rc-cv-to-flag').textContent   = mt ? mt[1] : '💱';

  document.getElementById('cv-result-label').textContent =
    fmt(amt, decF) + ' ' + from + ' = ' + fmt(out, dec) + ' ' + to;
  document.getElementById('cv-chip1').innerHTML =
    '📌 1 ' + from + ' = ' + fmt(rate, dec) + ' ' + to;
  document.getElementById('cv-chip2').innerHTML =
    '🔄 1 ' + to + ' = ' + fmt(revR, decF) + ' ' + from;
  document.getElementById('cv-result-rate').textContent = '';
  document.getElementById('cv-result-box').classList.add('show');
}
function fullConvertReverse() {
  if (!FX_LOADED) return;
  var out  = parseFloat(document.getElementById('cv-result-input').value) || 0;
  var from = document.getElementById('cv-from').value, to = document.getElementById('cv-to').value;
  var usd  = out / (FX[to] || 1);
  var amt  = usd * (FX[from] || 1);
  var ai   = document.getElementById('cv-amt');
  if (document.activeElement !== ai) ai.value = amt.toFixed(decFor(from));
}
function fullSwap() {
  var f = document.getElementById('cv-from'), t = document.getElementById('cv-to'), tmp = f.value;
  f.value = t.value; t.value = tmp; fullConvert();
}

/* ── Rates Table ─────────────────────────────────── */
function buildTable() {
  var priority = ['SYP','SAR','AED','EGP','KWD','QAR','BHD','OMR','JOD',
                  'IQD','LBP','MAD','TND','DZD','SDG','YER','LYD',
                  'EUR','GBP','JPY','TRY','CNY','INR','CHF','CAD','AUD',
                  'RUB','KRW','BRL','MXN','THB','IDR','PKR','NGN','SGD','HKD',
                  'NOK','SEK','DKK','PLN','CZK'];
  var extra = Object.keys(FX).filter(function(c){ return priority.indexOf(c)<0 && c!=='USD'; }).sort();
  var order = priority.filter(function(c){ return FX[c]; }).concat(extra);
  var tbody = document.getElementById('main-rates-tbody');
  tbody.innerHTML = '';
  order.forEach(function(code) {
    var rate = FX[code]; if (!rate || code === 'USD') return;
    var m    = CMETA[code] || [code, '💱'];
    var dec  = decFor(code);
    var isSyp = code === 'SYP';
    tbody.innerHTML += '<tr class="' + (isSyp?'syp-row':'') + '">' +
      '<td class="td-flag">' + m[1] + '</td>' +
      '<td class="td-name">' + (isSyp?'<span class="syp-badge">SYP</span>':'') + m[0] + '</td>' +
      '<td class="td-code">' + code + '</td>' +
      '<td class="td-rate">' + fmt(rate, dec) + '</td>' +
      '<td class="td-100">' + fmt(rate*100, dec) + ' ' + code + '</td>' +
      '</tr>';
  });
}

/* ── Helpers ─────────────────────────────────────── */
function decFor(c) {
  if (['JPY','KRW','IDR','IQD','YER','SDG','LBP','SYP','DZD','NGN','PKR'].indexOf(c) >= 0) return 0;
  if (['KWD','BHD','OMR','JOD'].indexOf(c) >= 0) return 4;
  return 2;
}
function fmt(n, dec) {
  if (!isFinite(n)) return '—';
  return n.toLocaleString('ar-SA', { minimumFractionDigits: dec||0, maximumFractionDigits: dec||0 });
}

/* ── Boot ────────────────────────────────────────── */
sypConvFromUsd(); /* instant seed from PHP-seeded SYP_RATE */
loadFX();         /* async enrich from API */
</script>
</body>
</html>
