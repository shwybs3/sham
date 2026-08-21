<?php
/**
 * syp/pair.php — Single currency pair detail page.
 * URL: /usd-syp/ → pair.php?pair=usd-syp
 */
define('SYP_DATA', true);
$allPairs = require __DIR__ . '/data.php';
$h        = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

$slug = preg_replace('/[^a-z\-]/', '', strtolower(trim($_GET['pair'] ?? '')));
if (!$slug || !isset($allPairs[$slug])) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>صفحة غير موجودة</title></head>';
    echo '<body><p style="text-align:center;padding:60px">الزوج غير موجود. <a href="/">العودة للرئيسية</a></p></body></html>';
    exit;
}

$p = $allPairs[$slug];

/* ── Converter input ─────────────────────────────────────────── */
$amount    = max(0, (float)($_GET['amount'] ?? 1));
$converted = round($amount * (float)($p['rate'] ?? 0), 4);

/* ── Related pairs (same base) ───────────────────────────────── */
$base    = strtolower($p['from'] ?? '');
$related = [];
foreach ($allPairs as $k => $v) {
    if ($k !== $slug && (strtolower($v['from']??'') === $base || strtolower($v['to']??'') === $base)) {
        $related[] = $k;
        if (count($related) >= 6) break;
    }
}

$pageTitle = ($p['seo_title'] ?? ($h($p['from_ar']).' مقابل '.$h($p['to_ar'])));
$pageDesc  = $p['description'] ?? '';
?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $h(strip_tags($pageTitle)) ?> | syp.yassota.com</title>
<meta name="description" content="<?= $h($pageDesc) ?>">
<link rel="canonical" href="/<?= $h($slug) ?>/">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💱</text></svg>">
<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"WebPage",
  "name":"<?= addslashes(strip_tags($pageTitle)) ?>",
  "description":"<?= addslashes($pageDesc) ?>",
  "url":"https://syp.yassota.com/<?= $h($slug) ?>/"
}
</script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --primary:#0ea5e9;--primary2:#0284c7;--bg:#f8fafc;--bg2:#fff;
  --border:#e2e8f0;--text:#0f172a;--muted:#64748b;
  --ff:'Segoe UI','Helvetica Neue',Arial,sans-serif;
}
@media(prefers-color-scheme:dark){
  :root{--bg:#0f172a;--bg2:#1e293b;--border:#334155;--text:#f1f5f9;--muted:#94a3b8}
}
html{scroll-behavior:smooth}
body{font-family:var(--ff);background:var(--bg);color:var(--text);min-height:100vh}
a{color:var(--primary2);text-decoration:none}
a:hover{text-decoration:underline}

.sz-nav{
  background:var(--bg2);border-bottom:1px solid var(--border);
  padding:13px 20px;display:flex;align-items:center;
  justify-content:space-between;position:sticky;top:0;z-index:50;
}
.sz-logo{font-size:18px;font-weight:800;color:var(--text)}
.sz-logo span{color:var(--primary2)}

.hero-pair{
  background:linear-gradient(135deg,var(--primary2),#0369a1);
  color:#fff;padding:40px 20px;text-align:center;
}
.pair-flags-big{font-size:48px;margin-bottom:12px}
.pair-title{font-size:clamp(18px,4vw,28px);font-weight:800;margin-bottom:6px}
.pair-subtitle{font-size:14px;opacity:.85}
.rate-big{font-size:clamp(36px,8vw,60px);font-weight:900;margin:16px 0 6px;font-variant-numeric:tabular-nums}
.rate-inv-small{font-size:14px;opacity:.8}
.pair-date{font-size:12px;opacity:.7;margin-top:8px}

.wrap{max-width:900px;margin:0 auto;padding:28px 16px}
.panel{background:var(--bg2);border:1px solid var(--border);border-radius:14px;padding:24px;margin-bottom:20px}
.panel h2{font-size:17px;font-weight:700;margin-bottom:16px}

/* Converter */
.conv-form{display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap}
.conv-group{display:flex;flex-direction:column;gap:4px;flex:1;min-width:140px}
.conv-label{font-size:12px;color:var(--muted);font-weight:600}
.conv-input{
  padding:12px 14px;border:1px solid var(--border);border-radius:8px;
  font-size:18px;font-weight:700;color:var(--text);background:var(--bg);
  outline:none;width:100%;
}
.conv-input:focus{border-color:var(--primary2)}
.conv-btn{
  background:var(--primary2);color:#fff;border:none;border-radius:8px;
  padding:12px 22px;font-size:15px;font-weight:600;cursor:pointer;white-space:nowrap;
}
.conv-swap{
  background:var(--bg3,#f1f5f9);border:1px solid var(--border);border-radius:8px;
  padding:12px 14px;cursor:pointer;font-size:18px;flex-shrink:0;
}
.conv-result{
  margin-top:14px;padding:14px 18px;background:var(--primary3,#e0f2fe);
  border-radius:10px;font-size:16px;color:var(--primary2);font-weight:600;
}

/* Chart sparkline */
.chart-placeholder{height:80px;background:var(--bg);border-radius:8px;overflow:hidden}
canvas#sparkline{width:100%;height:80px}

/* Rate table */
.rate-table{width:100%;border-collapse:collapse;font-size:14px}
.rate-table th{text-align:start;padding:8px 12px;border-bottom:2px solid var(--border);font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.04em}
.rate-table td{padding:10px 12px;border-bottom:1px solid var(--border)}
.rate-table tr:last-child td{border-bottom:none}

/* Content */
.content p{font-size:14px;color:var(--muted);line-height:1.85;margin-bottom:12px}
.content h3{font-size:15px;font-weight:700;color:var(--text);margin:16px 0 8px}

/* FAQ */
.faq-list{display:flex;flex-direction:column;gap:8px}
details.sfaq{border:1px solid var(--border);border-radius:10px}
details.sfaq summary{padding:13px 16px;font-size:14px;font-weight:600;cursor:pointer;list-style:none;display:flex;justify-content:space-between;align-items:center}
details.sfaq summary::after{content:'＋';color:var(--primary2)}
details.sfaq[open] summary::after{content:'－'}
details.sfaq summary::-webkit-details-marker{display:none}
.sfaq-ans{padding:0 16px 13px;font-size:13px;color:var(--muted);line-height:1.8}

/* Related */
.related-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px}
.related-card{
  background:var(--bg);border:1px solid var(--border);border-radius:10px;
  padding:12px;text-align:center;
}
.related-card .flags{font-size:18px}
.related-card .code{font-size:11px;font-weight:700;color:var(--muted);margin:4px 0}
.related-card .rate{font-size:14px;font-weight:700;color:var(--text)}

.sz-footer{background:var(--bg2);border-top:1px solid var(--border);padding:20px;text-align:center;font-size:12px;color:var(--muted)}
.disclaimer{font-size:12px;color:#92400e;background:#fef3c7;border-radius:8px;padding:10px 14px;margin-bottom:20px;line-height:1.7}
@media(max-width:520px){.conv-form{flex-direction:column}.conv-btn,.conv-swap{width:100%}}
</style>
</head>
<body>

<nav class="sz-nav">
  <a href="/" style="color:inherit;display:flex;align-items:center;gap:6px;font-size:18px;font-weight:800">
    💱 <span style="color:var(--primary2)">syp</span>.yassota.com
  </a>
  <a href="/" style="font-size:13px;color:var(--muted)">← جميع العملات</a>
</nav>

<!-- Hero rate display -->
<div class="hero-pair">
  <div class="pair-flags-big"><?= $h($p['flag_from']??'🏳') ?> <?= $h($p['flag_to']??'🏳') ?></div>
  <div class="pair-title"><?= strip_tags($pageTitle) ?></div>
  <div class="pair-subtitle"><?= $h($p['from_ar']) ?> مقابل <?= $h($p['to_ar']) ?></div>
  <?php
  $rInv = (float)($p['rate_inv'] ?? 0);
  $rDisp = $rInv >= 1000 ? number_format($rInv,0,'.',',') : ($rInv >= 10 ? number_format($rInv,2) : number_format($rInv,4));
  ?>
  <div class="rate-big">
    1 <?= $h($p['from']) ?> = <?= $rDisp ?> <?= $h($p['to']??'') ?>
  </div>
  <?php
  $rFwd = (float)($p['rate'] ?? 0);
  $rFwdDisp = $rFwd < 0.001 ? number_format($rFwd,6) : ($rFwd < 1 ? number_format($rFwd,4) : number_format($rFwd,2));
  ?>
  <div class="rate-inv-small">1 <?= $h($p['to']??'') ?> = <?= $rFwdDisp ?> <?= $h($p['from']) ?></div>
  <div class="pair-date">آخر تحديث: <?= date('d/m/Y') ?> — أسعار مرجعية</div>
</div>

<div class="wrap">

  <div class="disclaimer">
    ⚠️ الأسعار مرجعية للاطلاع فقط — ليست أسعار تداول فعلية. تحقق من بنكك أو شركة الصرافة للسعر الدقيق وللقرارات المالية.
  </div>

  <!-- Converter -->
  <div class="panel">
    <h2>🔢 حاسبة تحويل العملات</h2>
    <form method="GET" action="">
      <input type="hidden" name="pair" value="<?= $h($slug) ?>">
      <div class="conv-form">
        <div class="conv-group">
          <span class="conv-label"><?= $h($p['from_ar']) ?> (<?= $h($p['from']) ?>)</span>
          <input class="conv-input" type="number" name="amount" min="0" step="any"
                 value="<?= $h($amount) ?>" id="conv-from" oninput="liveConvert()">
        </div>
        <button type="button" class="conv-swap" onclick="swapConvert()" title="عكس">⇄</button>
        <div class="conv-group">
          <span class="conv-label"><?= $h($p['to_ar']) ?> (<?= $h($p['to']??'') ?>)</span>
          <input class="conv-input" type="number" id="conv-to" value="<?= $h($converted) ?>" readonly
                 style="background:var(--bg3,#f1f5f9)">
        </div>
        <button type="submit" class="conv-btn">حوّل</button>
      </div>
    </form>
    <?php if ($amount && $amount != 1): ?>
    <div class="conv-result">
      <?= $h(number_format($amount,2)) ?> <?= $h($p['from_ar']) ?> =
      <strong><?= $h(number_format($converted,4)) ?> <?= $h($p['to_ar']) ?></strong>
    </div>
    <?php endif; ?>
  </div>

  <!-- Sparkline chart -->
  <div class="panel">
    <h2>📈 اتجاه السعر (مرجعي)</h2>
    <div class="chart-placeholder">
      <canvas id="sparkline"></canvas>
    </div>
    <p style="font-size:12px;color:var(--muted);margin-top:8px">
      مخطط توضيحي — البيانات التاريخية الحقيقية تتطلب مصدر بيانات لحظي.
    </p>
  </div>

  <!-- Rate table -->
  <div class="panel">
    <h2>📊 جدول أسعار سريع</h2>
    <table class="rate-table">
      <thead><tr><th>المبلغ (<?= $h($p['from']) ?>)</th><th>= (<?= $h($p['to']??'') ?>)</th></tr></thead>
      <tbody>
        <?php
        $amounts = [1,5,10,50,100,500,1000,5000];
        foreach ($amounts as $a):
          $res = round($a * (float)($p['rate'] ?? 0), 4);
          $resDisp = $res >= 1000 ? number_format($res,0,'.',',') : number_format($res,4);
        ?>
        <tr>
          <td><?= number_format($a) ?> <?= $h($p['from']) ?></td>
          <td style="font-weight:600"><?= $resDisp ?> <?= $h($p['to']??'') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Content -->
  <?php if (!empty($p['content'])): ?>
  <div class="panel content">
    <h2>معلومات عن <?= $h($p['from_ar']) ?> مقابل <?= $h($p['to_ar']) ?></h2>
    <p><?= $h($p['content']) ?></p>
  </div>
  <?php endif; ?>

  <!-- FAQ -->
  <?php if (!empty($p['faq'])): ?>
  <div class="panel">
    <h2>❓ أسئلة شائعة</h2>
    <div class="faq-list">
      <?php foreach ($p['faq'] as [$q,$a]): ?>
      <details class="sfaq">
        <summary><?= $h($q) ?></summary>
        <div class="sfaq-ans"><?= $h($a) ?></div>
      </details>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Related pairs -->
  <?php if ($related): ?>
  <div class="panel">
    <h2>🔗 أزواج ذات صلة</h2>
    <div class="related-grid">
      <?php foreach ($related as $rk):
        $rp = $allPairs[$rk];
        $rRate = (float)($rp['rate_inv'] ?? 0);
        $rDisp2 = $rRate >= 1000 ? number_format($rRate,0,'.',',') : ($rRate >= 10 ? number_format($rRate,2) : number_format($rRate,4));
      ?>
      <a class="related-card" href="/<?= $h($rk) ?>/">
        <div class="flags"><?= $h($rp['flag_from']??'') ?> <?= $h($rp['flag_to']??'') ?></div>
        <div class="code"><?= $h($rp['from'].'/'.$rp['to']) ?></div>
        <div class="rate"><?= $rDisp2 ?></div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</div><!-- /wrap -->

<footer class="sz-footer">
  <p>
    <a href="/">← جميع العملات</a> &nbsp;|&nbsp;
    <a href="https://yassota.com">yassota.com</a> &nbsp;|&nbsp;
    © <?= date('Y') ?> syp.yassota.com
  </p>
  <p style="margin-top:6px">الأسعار مرجعية للاطلاع فقط — لا تصلح للقرارات المالية.</p>
</footer>

<script>
var RATE = <?= json_encode((float)($p['rate'] ?? 0)) ?>;
var RATE_INV = <?= json_encode((float)($p['rate_inv'] ?? 0)) ?>;

function liveConvert() {
  var from = parseFloat(document.getElementById('conv-from').value) || 0;
  document.getElementById('conv-to').value = (from * RATE).toFixed(4);
}

var swapped = false;
function swapConvert() {
  swapped = !swapped;
  var fromLbl = document.querySelector('.conv-group:first-child .conv-label');
  var toLbl   = document.querySelector('.conv-group:last-child .conv-label');
  if (swapped) {
    fromLbl.textContent = '<?= addslashes($p['to_ar']) ?> (<?= $p['to'] ?>)';
    toLbl.textContent   = '<?= addslashes($p['from_ar']) ?> (<?= $p['from'] ?>)';
    RATE = RATE_INV;
  } else {
    fromLbl.textContent = '<?= addslashes($p['from_ar']) ?> (<?= $p['from'] ?>)';
    toLbl.textContent   = '<?= addslashes($p['to_ar']) ?> (<?= $p['to'] ?>)';
    RATE = <?= json_encode((float)($p['rate'] ?? 0)) ?>;
  }
  document.getElementById('conv-from').value = 1;
  liveConvert();
}

/* Sparkline — fake but realistic-looking trend */
(function(){
  var cv = document.getElementById('sparkline');
  if(!cv) return;
  cv.width  = cv.offsetWidth;
  cv.height = 80;
  var ctx   = cv.getContext('2d');
  var W = cv.width, H = cv.height;
  /* generate slightly random trend around base */
  var pts = [];
  var base = RATE_INV || RATE || 1;
  var v = base;
  for(var i=0;i<40;i++){
    v += (Math.random()-.5)*(base*.005);
    pts.push(v);
  }
  var mn = Math.min(...pts), mx = Math.max(...pts);
  var scaleY = mn === mx ? 1 : H / (mx - mn);
  ctx.beginPath();
  pts.forEach(function(p,i){
    var x = (i/(pts.length-1))*W;
    var y = H - (p-mn)*scaleY;
    i===0 ? ctx.moveTo(x,y) : ctx.lineTo(x,y);
  });
  ctx.strokeStyle = '#0284c7';
  ctx.lineWidth   = 2;
  ctx.stroke();
  /* fill area */
  ctx.lineTo(W,H); ctx.lineTo(0,H); ctx.closePath();
  ctx.fillStyle = 'rgba(2,132,199,.08)';
  ctx.fill();
})();
</script>
</body>
</html>
