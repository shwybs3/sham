<?php
// Fetch live data server-side for initial render (JS will update)
$fx  = get_exchange_rates('USD');
$gld = get_gold_prices();
$fxRates = $fx['rates'] ?? [];
$fxDate  = $fx['date'] ?? date('Y-m-d');

// Currency display names
$currencies = [
  'SAR'=>['الريال السعودي','🇸🇦'],
  'AED'=>['الدرهم الإماراتي','🇦🇪'],
  'EGP'=>['الجنيه المصري','🇪🇬'],
  'KWD'=>['الدينار الكويتي','🇰🇼'],
  'QAR'=>['الريال القطري','🇶🇦'],
  'EUR'=>['اليورو','🇪🇺'],
  'GBP'=>['الجنيه الإسترليني','🇬🇧'],
  'TRY'=>['الليرة التركية','🇹🇷'],
];
?>

<!-- HERO -->
<section class="hero" aria-label="رئيسية">
  <div class="hero-bg"></div>
  <div class="container hero-content">
    <div class="hero-badge">🌐 موقع الأدوات الشامل بالعربية</div>
    <h1 class="hero-title">أسعار · أدوات · حاسبات<br><span class="text-gold">كل ما تحتاجه في مكان واحد</span></h1>
    <p class="hero-sub">أسعار الصرف والذهب لحظة بلحظة — أدوات PDF والصور — حاسبات البناء والطاقة الشمسية</p>
    <div class="hero-search">
      <form action="/articles" method="get" role="search">
        <input type="search" name="q" placeholder="ابحث عن أداة أو سعر...">
        <button type="submit">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          بحث
        </button>
      </form>
    </div>
    <div class="hero-quicklinks">
      <a href="/exchange">💱 صرف</a>
      <a href="/gold">🥇 ذهب</a>
      <a href="/pdf">📄 PDF</a>
      <a href="/calculators">🏗️ بناء</a>
      <a href="/solar">☀️ شمسية</a>
      <a href="/images">🖼️ صور</a>
    </div>
  </div>
</section>

<!-- LIVE TICKER -->
<div class="ticker-bar" role="marquee" aria-label="أسعار العملات الآنية">
  <div class="ticker-label">💱 أسعار الصرف الآن</div>
  <div class="ticker-track" id="tickerTrack">
    <?php foreach ($currencies as $code => [$name, $flag]):
      $rate = $fxRates[$code] ?? null; if (!$rate) continue; ?>
    <span class="ticker-item">
      <?= $flag ?> <?= h($name) ?> <strong><?= n($rate, $code==='KWD'?4:2) ?></strong>
    </span>
    <?php endforeach; ?>
    <span class="ticker-item muted">محدث: <?= h($fxDate) ?></span>
  </div>
</div>

<!-- SECTION: Exchange Rates -->
<section class="section" aria-labelledby="h-fx">
  <div class="container">
    <div class="section-head">
      <h2 id="h-fx" class="section-title">
        <span class="section-icon">💱</span> أسعار الصرف اليوم
        <span class="live-badge">مباشر</span>
      </h2>
      <a href="/exchange" class="see-all-link">عرض الكل ←</a>
    </div>
    <div class="rates-grid" id="ratesGrid">
      <?php foreach ($currencies as $code => [$name, $flag]):
        $rate = $fxRates[$code] ?? null; if (!$rate) continue; ?>
      <div class="rate-card">
        <div class="rate-flag"><?= $flag ?></div>
        <div class="rate-info">
          <div class="rate-name"><?= h($name) ?></div>
          <div class="rate-code"><?= h($code) ?></div>
        </div>
        <div class="rate-value"><?= n($rate, $code==='KWD'?4:2) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <!-- Mini converter -->
    <div class="mini-converter card">
      <h3>🔄 محوّل العملات السريع</h3>
      <div class="conv-row">
        <input type="number" id="convAmt" value="100" min="0" step="any">
        <select id="convFrom">
          <option value="USD" selected>USD — دولار أمريكي</option>
          <option value="EUR">EUR — يورو</option>
          <option value="GBP">GBP — جنيه إسترليني</option>
          <option value="SAR">SAR — ريال سعودي</option>
          <option value="AED">AED — درهم إماراتي</option>
          <option value="EGP">EGP — جنيه مصري</option>
        </select>
        <span>→</span>
        <select id="convTo">
          <option value="SAR" selected>SAR — ريال سعودي</option>
          <option value="AED">AED — درهم إماراتي</option>
          <option value="EGP">EGP — جنيه مصري</option>
          <option value="USD">USD — دولار أمريكي</option>
          <option value="EUR">EUR — يورو</option>
        </select>
        <button onclick="convertCurrency()" class="btn-primary">تحويل</button>
      </div>
      <div class="conv-result" id="convResult"></div>
    </div>
  </div>
</section>

<!-- SECTION: Gold Prices -->
<section class="section gold-section" aria-labelledby="h-gold">
  <div class="container">
    <div class="section-head">
      <h2 id="h-gold" class="section-title">
        <span class="section-icon">🥇</span> أسعار الذهب اليوم
        <span class="live-badge">مباشر</span>
      </h2>
      <a href="/gold" class="see-all-link">تفاصيل أكثر ←</a>
    </div>
    <div class="gold-cards">
      <?php
      $purities = ['24'=>'عيار 24','22'=>'عيار 22','21'=>'عيار 21','18'=>'عيار 18'];
      foreach ($purities as $k => $label):
        $factor = $gld['purities'][$k] ?? 1;
        $sarG = round(($gld['sar_g'] ?? 0) * $factor, 2);
        $aedG = round(($gld['aed_g'] ?? 0) * $factor, 2);
        $usdG = round(($gld['usd_g'] ?? 0) * $factor, 2);
      ?>
      <div class="gold-card">
        <div class="gold-purity"><?= h($label) ?></div>
        <div class="gold-price-sar"><?= n($sarG) ?> <span>ريال/جرام</span></div>
        <div class="gold-price-sub">
          <?= n($aedG) ?> درهم &nbsp;·&nbsp; <?= n($usdG) ?> دولار
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <!-- Gold weight calculator -->
    <div class="card gold-calc">
      <h3>⚖️ حاسبة الذهب السريعة</h3>
      <div class="conv-row">
        <input type="number" id="goldGrams" value="10" min="0.1" step="0.1">
        <span>جرام من</span>
        <select id="goldPurity">
          <option value="1">عيار 24</option>
          <option value="0.9167">عيار 22</option>
          <option value="0.875" selected>عيار 21</option>
          <option value="0.75">عيار 18</option>
          <option value="0.5833">عيار 14</option>
        </select>
        <button onclick="calcGold()" class="btn-primary">احسب</button>
      </div>
      <div id="goldResult" class="conv-result"></div>
    </div>
  </div>
</section>

<!-- SECTION: Tools categories grid -->
<section class="section" aria-labelledby="h-tools">
  <div class="container">
    <h2 id="h-tools" class="section-title"><span class="section-icon">🛠️</span> جميع الأدوات والخدمات</h2>
    <div class="tools-grid">
      <a href="/exchange" class="tool-card cat-prices">
        <div class="tc-icon">💱</div>
        <div class="tc-body">
          <h3>أسعار الصرف</h3>
          <p>تحويل العملات لحظة بلحظة</p>
        </div>
        <span class="tc-tag">مباشر</span>
      </a>
      <a href="/gold" class="tool-card cat-prices">
        <div class="tc-icon">🥇</div>
        <div class="tc-body">
          <h3>أسعار الذهب</h3>
          <p>جميع العيارات بجميع العملات</p>
        </div>
        <span class="tc-tag">مباشر</span>
      </a>
      <a href="/fuel" class="tool-card cat-prices">
        <div class="tc-icon">⛽</div>
        <div class="tc-body">
          <h3>أسعار المحروقات</h3>
          <p>بنزين وديزل في دول الخليج</p>
        </div>
        <span class="tc-tag">أسعار</span>
      </a>
      <a href="/food" class="tool-card cat-prices">
        <div class="tc-icon">🥦</div>
        <div class="tc-body">
          <h3>أسعار الغذاء</h3>
          <p>خضار ولحوم وحبوب</p>
        </div>
        <span class="tc-tag">أسعار</span>
      </a>
      <a href="/phones" class="tool-card cat-prices">
        <div class="tc-icon">📱</div>
        <div class="tc-body">
          <h3>أسعار الهواتف</h3>
          <p>مقارنة آيفون وسامسونج</p>
        </div>
        <span class="tc-tag">مقارنة</span>
      </a>
      <a href="/pdf" class="tool-card cat-tools">
        <div class="tc-icon">📄</div>
        <div class="tc-body">
          <h3>أدوات PDF</h3>
          <p>دمج وضغط وتحويل</p>
        </div>
        <span class="tc-tag">مجاني</span>
      </a>
      <a href="/images" class="tool-card cat-tools">
        <div class="tc-icon">🖼️</div>
        <div class="tc-body">
          <h3>أدوات الصور</h3>
          <p>ضغط وتحويل وتعديل</p>
        </div>
        <span class="tc-tag">مجاني</span>
      </a>
      <a href="/ai" class="tool-card cat-tools">
        <div class="tc-icon">🤖</div>
        <div class="tc-body">
          <h3>أدوات AI</h3>
          <p>ذكاء اصطناعي مجاني</p>
        </div>
        <span class="tc-tag">مجاني</span>
      </a>
      <a href="/calculators" class="tool-card cat-calc">
        <div class="tc-icon">🏗️</div>
        <div class="tc-body">
          <h3>حاسبات البناء</h3>
          <p>خرسانة وطوب وبلاط</p>
        </div>
        <span class="tc-tag">دقيق</span>
      </a>
      <a href="/solar" class="tool-card cat-calc">
        <div class="tc-icon">☀️</div>
        <div class="tc-body">
          <h3>الطاقة الشمسية</h3>
          <p>ألواح وتكلفة وتوفير</p>
        </div>
        <span class="tc-tag">دقيق</span>
      </a>
    </div>
  </div>
</section>

<!-- SECTION: Subdomains directory -->
<section class="section subdomains-section" aria-labelledby="h-subs">
  <div class="container">
    <h2 id="h-subs" class="section-title"><span class="section-icon">🌐</span> المواقع الفرعية المتخصصة</h2>
    <p class="section-sub">كل خدمة على نطاق فرعي مخصص لأفضل تجربة وظهور في محركات البحث</p>
    <?php $host = parse_url(SITE_URL, PHP_URL_HOST) ?? 'your-domain.com'; ?>
    <div class="subs-grid">
      <a href="https://shorts.<?= h($host) ?>" target="_blank" class="sub-card">
        <div class="sub-icon">▶️</div>
        <div class="sub-info">
          <strong>تحميل يوتيوب شورت</strong>
          <span>shorts.<?= h($host) ?></span>
        </div>
      </a>
      <a href="https://tiktok.<?= h($host) ?>" target="_blank" class="sub-card">
        <div class="sub-icon">🎵</div>
        <div class="sub-info">
          <strong>تحميل تيك توك</strong>
          <span>tiktok.<?= h($host) ?></span>
        </div>
      </a>
      <a href="https://ocr.<?= h($host) ?>" target="_blank" class="sub-card">
        <div class="sub-icon">📷</div>
        <div class="sub-info">
          <strong>استخراج النصوص من الصور</strong>
          <span>ocr.<?= h($host) ?></span>
        </div>
      </a>
      <a href="https://pdf.<?= h($host) ?>" target="_blank" class="sub-card">
        <div class="sub-icon">📄</div>
        <div class="sub-info">
          <strong>تحويل PDF</strong>
          <span>pdf.<?= h($host) ?></span>
        </div>
      </a>
      <a href="https://translate.<?= h($host) ?>" target="_blank" class="sub-card">
        <div class="sub-icon">🌐</div>
        <div class="sub-info">
          <strong>مترجم فوري</strong>
          <span>translate.<?= h($host) ?></span>
        </div>
      </a>
      <a href="https://images.<?= h($host) ?>" target="_blank" class="sub-card">
        <div class="sub-icon">🖼️</div>
        <div class="sub-info">
          <strong>تحرير الصور</strong>
          <span>images.<?= h($host) ?></span>
        </div>
      </a>
      <a href="https://exchange.<?= h($host) ?>" target="_blank" class="sub-card">
        <div class="sub-icon">💱</div>
        <div class="sub-info">
          <strong>تحويل العملات</strong>
          <span>exchange.<?= h($host) ?></span>
        </div>
      </a>
      <a href="https://gold.<?= h($host) ?>" target="_blank" class="sub-card">
        <div class="sub-icon">🥇</div>
        <div class="sub-info">
          <strong>أسعار الذهب</strong>
          <span>gold.<?= h($host) ?></span>
        </div>
      </a>
      <a href="https://calc.<?= h($host) ?>" target="_blank" class="sub-card">
        <div class="sub-icon">🔢</div>
        <div class="sub-info">
          <strong>الحاسبات</strong>
          <span>calc.<?= h($host) ?></span>
        </div>
      </a>
    </div>
  </div>
</section>

<!-- SECTION: Quick Calculators preview -->
<section class="section" aria-labelledby="h-calc">
  <div class="container">
    <div class="section-head">
      <h2 id="h-calc" class="section-title"><span class="section-icon">🏗️</span> حاسبات البناء السريعة</h2>
      <a href="/calculators" class="see-all-link">جميع الحاسبات ←</a>
    </div>
    <div class="calc-preview-grid">
      <!-- Concrete Calculator -->
      <div class="card calc-widget">
        <h3>🏛️ حاسبة الخرسانة</h3>
        <div class="form-grid">
          <label>الطول (م) <input type="number" id="c_len" value="5" min="0.1" step="0.1"></label>
          <label>العرض (م) <input type="number" id="c_wid" value="4" min="0.1" step="0.1"></label>
          <label>السماكة (سم) <input type="number" id="c_thk" value="20" min="1" step="1"></label>
        </div>
        <button onclick="calcConcrete()" class="btn-calc">احسب الكميات</button>
        <div id="concreteResult" class="calc-result" hidden></div>
      </div>
      <!-- Tiles Calculator -->
      <div class="card calc-widget">
        <h3>🟫 حاسبة البلاط</h3>
        <div class="form-grid">
          <label>مساحة الغرفة م² <input type="number" id="t_area" value="20" min="1" step="0.5"></label>
          <label>حجم البلاطة سم <select id="t_size">
            <option value="30">30×30</option>
            <option value="40">40×40</option>
            <option value="60" selected>60×60</option>
            <option value="80">80×80</option>
            <option value="100">100×100</option>
          </select></label>
          <label>هدر بالنسبة ٪ <input type="number" id="t_waste" value="10" min="0" max="30"></label>
        </div>
        <button onclick="calcTiles()" class="btn-calc">احسب</button>
        <div id="tilesResult" class="calc-result" hidden></div>
      </div>
      <!-- Bricks Calculator -->
      <div class="card calc-widget">
        <h3>🧱 حاسبة الطوب</h3>
        <div class="form-grid">
          <label>الطول (م) <input type="number" id="b_len" value="10" min="0.1" step="0.1"></label>
          <label>الارتفاع (م) <input type="number" id="b_hgt" value="3" min="0.1" step="0.1"></label>
          <label>سماكة الجدار <select id="b_thk">
            <option value="0.12">12 سم</option>
            <option value="0.20" selected>20 سم</option>
            <option value="0.25">25 سم</option>
          </select></label>
        </div>
        <button onclick="calcBricks()" class="btn-calc">احسب</button>
        <div id="bricksResult" class="calc-result" hidden></div>
      </div>
    </div>
  </div>
</section>

<!-- SECTION: Solar preview -->
<section class="section solar-section" aria-labelledby="h-solar">
  <div class="container">
    <div class="section-head">
      <h2 id="h-solar" class="section-title"><span class="section-icon">☀️</span> حاسبة الطاقة الشمسية</h2>
      <a href="/solar" class="see-all-link">تفاصيل أكثر ←</a>
    </div>
    <div class="solar-preview card">
      <div class="solar-intro">
        <p>أدخل فاتورة الكهرباء الشهرية واحصل على عدد الألواح الشمسية المطلوبة والتكلفة التقديرية ومدة الاسترداد</p>
      </div>
      <div class="conv-row">
        <label>الفاتورة الشهرية</label>
        <input type="number" id="solarBill" value="500" min="50">
        <select id="solarCountry">
          <option value="sa">السعودية (هللة/كيلو)</option>
          <option value="ae">الإمارات (فلس/كيلو)</option>
          <option value="eg">مصر (قرش/كيلو)</option>
          <option value="kw">الكويت (فلس/كيلو)</option>
        </select>
        <button onclick="calcSolar()" class="btn-primary">احسب</button>
      </div>
      <div id="solarResult" class="solar-result" hidden></div>
    </div>
  </div>
</section>

<!-- SECTION: AI Tools -->
<section class="section" aria-labelledby="h-ai">
  <div class="container">
    <div class="section-head">
      <h2 id="h-ai" class="section-title"><span class="section-icon">🤖</span> أدوات الذكاء الاصطناعي</h2>
      <a href="/ai" class="see-all-link">استكشف الكل ←</a>
    </div>
    <div class="tools-grid">
      <a href="/ai#chatgpt" class="tool-card cat-ai">
        <div class="tc-icon">💬</div>
        <div class="tc-body"><h3>ChatGPT عربي</h3><p>محادثة وكتابة المحتوى</p></div>
        <span class="tc-tag">مجاني</span>
      </a>
      <a href="/ai#image" class="tool-card cat-ai">
        <div class="tc-icon">🎨</div>
        <div class="tc-body"><h3>توليد الصور بـ AI</h3><p>صور احترافية بالوصف</p></div>
        <span class="tc-tag">مجاني</span>
      </a>
      <a href="/ai#translate" class="tool-card cat-ai">
        <div class="tc-icon">🌐</div>
        <div class="tc-body"><h3>الترجمة الفورية</h3><p>ترجمة دقيقة بالذكاء الاصطناعي</p></div>
        <span class="tc-tag">مجاني</span>
      </a>
      <a href="/ai#summary" class="tool-card cat-ai">
        <div class="tc-icon">📝</div>
        <div class="tc-body"><h3>التلخيص التلقائي</h3><p>لخص أي نص في ثوانٍ</p></div>
        <span class="tc-tag">مجاني</span>
      </a>
    </div>
  </div>
</section>

<!-- SECTION: Articles -->
<section class="section" aria-labelledby="h-articles">
  <div class="container">
    <div class="section-head">
      <h2 id="h-articles" class="section-title"><span class="section-icon">📰</span> أحدث المقالات</h2>
      <a href="/articles" class="see-all-link">جميع المقالات ←</a>
    </div>
    <?php
    $pdo = db();
    $arts = [];
    if ($pdo) {
      $arts = $pdo->query("SELECT id,slug,title,excerpt,category,thumb,created_at FROM articles WHERE status='published' ORDER BY created_at DESC LIMIT 6")->fetchAll();
    }
    ?>
    <?php if ($arts): ?>
    <div class="articles-grid">
      <?php foreach ($arts as $a): ?>
      <a href="/article/<?= h($a['slug']) ?>" class="article-card">
        <?php if ($a['thumb']): ?><img src="<?= h($a['thumb']) ?>" alt="<?= h($a['title']) ?>" loading="lazy"><?php endif; ?>
        <div class="ac-body">
          <span class="ac-cat"><?= h($a['category']) ?></span>
          <h3><?= h($a['title']) ?></h3>
          <p><?= h(mb_substr(strip_tags($a['excerpt'] ?? ''), 0, 100)) ?>…</p>
          <span class="ac-date"><?= h(date('d/m/Y', strtotime($a['created_at']))) ?></span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
      <p>لا توجد مقالات حتى الآن. ترقّب أحدث المحتوى قريباً!</p>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- Schema.org WebPage JSON-LD for homepage tools -->
<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"ItemList",
  "name":"أدوات وخدمات <?= h(SITE_NAME) ?>",
  "description":"<?= h(SITE_TAGLINE) ?>",
  "url":"<?= h(SITE_URL) ?>",
  "numberOfItems":10,
  "itemListElement":[
    {"@type":"ListItem","position":1,"name":"أسعار الصرف","url":"<?= h(base_url('exchange')) ?>"},
    {"@type":"ListItem","position":2,"name":"أسعار الذهب","url":"<?= h(base_url('gold')) ?>"},
    {"@type":"ListItem","position":3,"name":"أسعار المحروقات","url":"<?= h(base_url('fuel')) ?>"},
    {"@type":"ListItem","position":4,"name":"أدوات PDF","url":"<?= h(base_url('pdf')) ?>"},
    {"@type":"ListItem","position":5,"name":"أدوات الصور","url":"<?= h(base_url('images')) ?>"},
    {"@type":"ListItem","position":6,"name":"أدوات AI","url":"<?= h(base_url('ai')) ?>"},
    {"@type":"ListItem","position":7,"name":"حاسبات البناء","url":"<?= h(base_url('calculators')) ?>"},
    {"@type":"ListItem","position":8,"name":"الطاقة الشمسية","url":"<?= h(base_url('solar')) ?>"}
  ]
}
</script>
<script>
// Pass server-side rates to JS for converter
window._fxRates = <?= json_encode($fxRates, JSON_UNESCAPED_UNICODE) ?>;
window._goldSarG = <?= $gld['sar_g'] ?? 0 ?>;
window._goldAedG = <?= $gld['aed_g'] ?? 0 ?>;
window._goldUsdG = <?= $gld['usd_g'] ?? 0 ?>;
</script>
