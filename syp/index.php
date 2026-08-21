<?php
/**
 * syp/index.php — Currency exchange rates landing page for syp.yassota.com
 * Standalone — no main site DB required.
 */
define('SYP_DATA', true);
$pairs = require __DIR__ . '/data.php';
$h     = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

/* ── Category grouping ──────────────────────────────────────────── */
$cats = [
  'سوري'      => ['syp-usd','syp-eur','syp-sar','syp-aed','syp-gbp'],
  'خليجي'     => ['sar-usd','sar-eur','sar-aed','sar-egp','sar-kwd','aed-usd','aed-eur','aed-egp','qar-usd','qar-sar','qar-aed','omr-usd','omr-sar','bhd-usd','bhd-sar','kwd-usd','kwd-sar','kwd-aed','kwd-egp'],
  'عربي'      => ['egp-usd','egp-sar','egp-aed','iqd-usd','iqd-sar','iqd-aed','jod-usd','jod-sar','jod-aed','lbp-usd','lbp-sar','yer-usd','yer-sar'],
  'عالمي'     => ['usd-eur','usd-gbp','usd-cny','usd-jpy','eur-gbp','eur-sar','gbp-usd','gbp-sar','gbp-aed','cny-sar','cny-aed','jpy-usd','jpy-sar','try-usd','try-sar','inr-sar'],
];
?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>أسعار العملات اليوم — سعر الدولار والريال والدرهم | syp.yassota.com</title>
<meta name="description" content="أسعار صرف العملات العربية والعالمية — الليرة السورية، الريال السعودي، الدرهم الإماراتي، الجنيه المصري والمزيد. أسعار مرجعية للاطلاع.">
<meta property="og:title" content="أسعار العملات اليوم">
<meta property="og:description" content="أسعار مرجعية لأكثر من 55 زوج عملات عربي وعالمي">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💱</text></svg>">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --primary:#0ea5e9;--primary2:#0284c7;--primary3:#e0f2fe;
  --bg:#f8fafc;--bg2:#fff;--bg3:#f1f5f9;
  --border:#e2e8f0;--text:#0f172a;--muted:#64748b;
  --red:#ef4444;--green:#10b981;
  --ff:'Segoe UI','Helvetica Neue',Arial,sans-serif;
}
@media(prefers-color-scheme:dark){
  :root{--bg:#0f172a;--bg2:#1e293b;--bg3:#1e293b;--border:#334155;--text:#f1f5f9;--muted:#94a3b8;--primary3:#0c4a6e}
}
html{scroll-behavior:smooth}
body{font-family:var(--ff);background:var(--bg);color:var(--text);min-height:100vh;line-height:1.6}
a{color:var(--primary2);text-decoration:none}
a:hover{text-decoration:underline}

.sz-nav{
  background:var(--bg2);border-bottom:1px solid var(--border);
  padding:14px 20px;display:flex;align-items:center;
  justify-content:space-between;position:sticky;top:0;z-index:50;
}
.sz-logo{font-size:20px;font-weight:800;color:var(--text);display:flex;align-items:center;gap:8px}
.sz-logo span{color:var(--primary2)}
.sz-nav-right{display:flex;align-items:center;gap:16px;font-size:13px;color:var(--muted)}
.nav-btn{background:var(--primary2);color:#fff;padding:7px 16px;border-radius:8px;font-size:13px;font-weight:600}
.nav-btn:hover{background:var(--primary);text-decoration:none;color:#fff}

.hero{
  background:linear-gradient(135deg,var(--primary2),#0369a1);
  color:#fff;padding:48px 20px 40px;text-align:center;
}
.hero h1{font-size:clamp(22px,5vw,36px);font-weight:800;margin-bottom:10px}
.hero p{font-size:15px;opacity:.88;max-width:560px;margin:0 auto 24px;line-height:1.75}
.search-box{
  display:flex;gap:0;max-width:480px;margin:0 auto;border-radius:12px;overflow:hidden;
  box-shadow:0 4px 20px rgba(0,0,0,.2);
}
.search-input{
  flex:1;padding:13px 18px;font-size:15px;border:none;outline:none;
  font-family:var(--ff);background:#fff;color:#0f172a;
}
.search-btn{
  background:var(--primary2);color:#fff;border:none;padding:13px 20px;
  font-size:15px;cursor:pointer;white-space:nowrap;
}

.disclaimer{
  background:#fef3c7;border:1px solid #fbbf24;border-radius:8px;
  padding:10px 18px;font-size:12px;color:#92400e;text-align:center;
  max-width:800px;margin:16px auto 0;
}

.wrap{max-width:1100px;margin:0 auto;padding:32px 16px}

.cat-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:24px}
.cat-tab{
  padding:7px 18px;border-radius:20px;font-size:13px;font-weight:600;
  border:1.5px solid var(--border);background:transparent;color:var(--muted);
  cursor:pointer;transition:all .15s;
}
.cat-tab.active,.cat-tab:hover{background:var(--primary2);border-color:var(--primary2);color:#fff}

.pairs-grid{
  display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;
}
.pair-card{
  background:var(--bg2);border:1px solid var(--border);border-radius:14px;
  padding:18px 16px;display:flex;flex-direction:column;gap:8px;
  transition:transform .15s,box-shadow .15s;
}
.pair-card:hover{transform:translateY(-2px);box-shadow:0 4px 16px rgba(0,0,0,.08);text-decoration:none}
.pair-flags{font-size:22px;line-height:1}
.pair-code{font-size:12px;font-weight:700;color:var(--muted);letter-spacing:.05em}
.pair-rate{font-size:22px;font-weight:800;color:var(--text);font-variant-numeric:tabular-nums}
.pair-inv{font-size:12px;color:var(--muted)}
.pair-names{font-size:12px;color:var(--muted)}
.pair-link{
  font-size:12px;color:var(--primary2);font-weight:600;
  margin-top:4px;display:flex;align-items:center;gap:4px;
}

/* SEO content */
.seo-section{background:var(--bg2);border-radius:14px;border:1px solid var(--border);padding:28px 24px;margin-top:32px}
.seo-section h2{font-size:20px;font-weight:700;margin-bottom:12px}
.seo-section p{font-size:14px;color:var(--muted);line-height:1.85;margin-bottom:12px}
.seo-section h3{font-size:16px;font-weight:700;color:var(--text);margin:18px 0 8px}
.faq-list{display:flex;flex-direction:column;gap:8px;margin-top:16px}
details.sfaq{border:1px solid var(--border);border-radius:10px}
details.sfaq summary{padding:13px 16px;font-size:14px;font-weight:600;cursor:pointer;list-style:none;display:flex;justify-content:space-between}
details.sfaq summary::after{content:'＋';color:var(--primary2)}
details.sfaq[open] summary::after{content:'－'}
details.sfaq summary::-webkit-details-marker{display:none}
.sfaq-ans{padding:0 16px 13px;font-size:13px;color:var(--muted);line-height:1.8}

.sz-footer{background:var(--bg2);border-top:1px solid var(--border);padding:28px 20px;text-align:center;font-size:13px;color:var(--muted)}
.footer-links{display:flex;gap:20px;justify-content:center;flex-wrap:wrap;margin-bottom:12px}
.footer-links a{color:var(--muted)}
.footer-links a:hover{color:var(--text);text-decoration:none}

@media(max-width:480px){
  .pairs-grid{grid-template-columns:1fr 1fr}
  .pair-rate{font-size:18px}
}
</style>
</head>
<body>

<nav class="sz-nav">
  <div class="sz-logo">💱 <span>syp</span>.yassota.com</div>
  <div class="sz-nav-right">
    <span><?= date('d/m/Y') ?></span>
    <a href="https://yassota.com" class="nav-btn">yassota.com</a>
  </div>
</nav>

<div class="hero">
  <h1>💱 أسعار العملات اليوم</h1>
  <p>أسعار صرف مرجعية لأكثر من 55 زوج عملات عربي وعالمي — مُحدَّثة يومياً للاطلاع العام.</p>
  <div class="search-box">
    <input class="search-input" type="text" id="s-input" placeholder="ابحث عن عملة... مثال: دولار، ريال، ليرة">
    <button class="search-btn" onclick="doSearch()">🔍</button>
  </div>
  <div class="disclaimer">
    ⚠️ الأسعار مرجعية للاطلاع فقط — ليست أسعار تداول فعلية ولا تصلح للقرارات المالية. تحقق من بنكك أو شركة الصرافة للسعر الدقيق.
  </div>
</div>

<div class="wrap">

  <!-- Category tabs -->
  <div class="cat-tabs">
    <button class="cat-tab active" onclick="filterCat('all',this)">🌍 الكل</button>
    <button class="cat-tab" onclick="filterCat('سوري',this)">🇸🇾 سوري</button>
    <button class="cat-tab" onclick="filterCat('خليجي',this)">🇸🇦 خليجي</button>
    <button class="cat-tab" onclick="filterCat('عربي',this)">🌙 عربي</button>
    <button class="cat-tab" onclick="filterCat('عالمي',this)">💹 عالمي</button>
  </div>

  <!-- Pairs grid -->
  <div class="pairs-grid" id="pairs-grid">
    <?php foreach ($pairs as $slug => $p): ?>
    <a class="pair-card" href="/<?= $h($slug) ?>/"
       data-cat="<?php
         foreach ($cats as $cat => $items) {
           if (in_array($slug, $items)) { echo $h($cat); break; }
         }
       ?>"
       data-search="<?= $h(strtolower($p['from_ar'].' '.$p['to_ar'].' '.$p['from'].' '.$p['to'])) ?>">
      <div class="pair-flags"><?= $h($p['flag_from']) ?> <?= $h($p['flag_to']) ?></div>
      <div class="pair-code"><?= $h($p['from'].'/'.($p['to']??'')) ?></div>
      <div class="pair-rate">
        <?php
        $r = (float)($p['rate_inv'] ?? 0);
        echo $r >= 1000 ? number_format($r,0,'.',',') : ($r >= 10 ? number_format($r,2) : number_format($r,4));
        ?>
      </div>
      <div class="pair-names"><?= $h($p['from_ar']) ?> → <?= $h($p['to_ar']) ?></div>
      <div class="pair-inv">1 <?= $h($p['from']) ?> = <?php
        $ri = (float)($p['rate'] ?? 0);
        echo $ri < 0.001 ? number_format($ri,6) : ($ri < 1 ? number_format($ri,4) : number_format($ri,2));
      ?> <?= $h($p['to']??'') ?></div>
      <div class="pair-link">تفاصيل وتحويل →</div>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- SEO Content -->
  <div class="seo-section">
    <h2>أسعار الصرف العربية والعالمية — دليلك الشامل</h2>
    <p>
      توفر هذه الصفحة أسعاراً مرجعية لأبرز أزواج العملات العربية والعالمية، تشمل الليرة السورية،
      الريال السعودي، الدرهم الإماراتي، الجنيه المصري، الدينار الكويتي، الدينار العراقي،
      الدينار الأردني، الدينار البحريني، الريال العُماني، الريال القطري، الدولار الأمريكي،
      اليورو الأوروبي، الجنيه الإسترليني، واليوان الصيني.
    </p>
    <p>
      سوق الفوركس هو أكبر الأسواق المالية في العالم بحجم تداول يومي يتجاوز 7 تريليون دولار.
      تتغير أسعار الصرف باستمرار تبعاً للعوامل الاقتصادية والسياسية وقرارات البنوك المركزية.
    </p>

    <h3>عملات الخليج العربي</h3>
    <p>
      معظم عملات دول مجلس التعاون الخليجي مرتبطة بالدولار الأمريكي بأسعار شبه ثابتة:
      الريال السعودي (3.75)، الدرهم الإماراتي (3.67)، الريال القطري (3.64)،
      الريال البحريني (0.376)، الريال العُماني (0.385). الدينار الكويتي الوحيد المرتبط بسلة عملات.
    </p>

    <h3>عملات عربية أخرى</h3>
    <p>
      الجنيه المصري شهد تحريراً لسعر صرفه في السنوات الأخيرة. الليرة اللبنانية تعرضت لأزمة حادة
      منذ 2019. الدينار العراقي مستقر نسبياً بدعم من عائدات النفط.
    </p>

    <div class="faq-list">
      <?php
      $faqs = [
        ['كيف أقرأ أسعار الصرف؟','السعر يعني كم وحدة من العملة الثانية تحصل مقابل وحدة واحدة من العملة الأولى. مثال: USD/SAR = 3.75 يعني الدولار الواحد = 3.75 ريال سعودي.'],
        ['لماذا تختلف الأسعار بين المواقع؟','الأسعار في السوق تتغير كل ثانية. كل موقع يعرض سعراً مختلفاً حسب وقت التحديث ومصدر البيانات. أسعارنا مرجعية تقريبية.'],
        ['ما الفرق بين سعر الشراء والبيع؟','شركات الصرافة تشتري العملة بسعر أقل وتبيعها بسعر أعلى — الفرق هو هامش ربحها (الـ spread).'],
        ['كيف أتحقق من السعر الرسمي؟','راجع الموقع الرسمي للبنك المركزي في بلدك أو تطبيق بنكك للحصول على السعر المعتمد.'],
        ['هل يمكنني الاعتماد على هذه الأسعار للصرف الفعلي؟','لا، هذه أسعار استرشادية فقط. للصرف الفعلي راجع بنكك أو شركة الصرافة المعتمدة.'],
      ];
      foreach ($faqs as [$q,$a]): ?>
      <details class="sfaq">
        <summary><?= $h($q) ?></summary>
        <div class="sfaq-ans"><?= $h($a) ?></div>
      </details>
      <?php endforeach; ?>
    </div>
  </div>

</div><!-- /wrap -->

<footer class="sz-footer">
  <div class="footer-links">
    <a href="/">الرئيسية</a>
    <a href="/syp-usd/">دولار/ليرة سورية</a>
    <a href="/sar-usd/">ريال/دولار</a>
    <a href="/aed-usd/">درهم/دولار</a>
    <a href="/usd-eur/">دولار/يورو</a>
    <a href="https://yassota.com">yassota.com</a>
  </div>
  <div>
    © <?= date('Y') ?> syp.yassota.com — أسعار مرجعية للاطلاع فقط، لا تصلح للقرارات المالية.
  </div>
</footer>

<script>
/* Filter by category */
function filterCat(cat, btn) {
  document.querySelectorAll('.cat-tab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.pair-card').forEach(c => {
    c.style.display = (cat === 'all' || c.dataset.cat === cat) ? '' : 'none';
  });
}

/* Search */
function doSearch() {
  var q = document.getElementById('s-input').value.trim().toLowerCase();
  document.querySelectorAll('.pair-card').forEach(c => {
    c.style.display = (!q || c.dataset.search.includes(q)) ? '' : 'none';
  });
  document.querySelectorAll('.cat-tab').forEach(b => b.classList.remove('active'));
  document.querySelector('.cat-tab').classList.add('active');
}
document.getElementById('s-input').addEventListener('keydown', function(e) {
  if (e.key === 'Enter') doSearch();
});
document.getElementById('s-input').addEventListener('input', function() {
  if (!this.value) filterCat('all', document.querySelector('.cat-tab'));
});
</script>
</body>
</html>
