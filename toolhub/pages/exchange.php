<?php
$fx = get_exchange_rates('USD');
$rates = $fx['rates'] ?? [];
$date  = $fx['date'] ?? date('Y-m-d');

$currencyData = [
  'SAR'=>['الريال السعودي','🇸🇦','sar'],
  'AED'=>['الدرهم الإماراتي','🇦🇪','aed'],
  'EGP'=>['الجنيه المصري','🇪🇬','egp'],
  'KWD'=>['الدينار الكويتي','🇰🇼','kwd'],
  'QAR'=>['الريال القطري','🇶🇦','qar'],
  'BHD'=>['الدينار البحريني','🇧🇭','bhd'],
  'OMR'=>['الريال العُماني','🇴🇲','omr'],
  'JOD'=>['الدينار الأردني','🇯🇴','jod'],
  'EUR'=>['اليورو','🇪🇺','eur'],
  'GBP'=>['الجنيه الإسترليني','🇬🇧','gbp'],
  'JPY'=>['الين الياباني','🇯🇵','jpy'],
  'TRY'=>['الليرة التركية','🇹🇷','try'],
  'CNY'=>['اليوان الصيني','🇨🇳','cny'],
  'INR'=>['الروبية الهندية','🇮🇳','inr'],
  'CHF'=>['الفرنك السويسري','🇨🇭','chf'],
  'CAD'=>['الدولار الكندي','🇨🇦','cad'],
  'AUD'=>['الدولار الأسترالي','🇦🇺','aud'],
];
?>

<section class="page-hero small">
  <div class="container">
    <nav class="breadcrumb" aria-label="مسار التنقل">
      <a href="/">الرئيسية</a> <span>›</span> <span>أسعار الصرف</span>
    </nav>
    <h1>أسعار الصرف اليوم <span class="live-badge">مباشر</span></h1>
    <p>سعر الدولار الأمريكي مقابل جميع العملات العربية والعالمية الآن — محدث: <?= h($date) ?></p>
  </div>
</section>

<div class="container page-body">
  <!-- Currency Converter -->
  <section class="card converter-card" aria-labelledby="h-conv">
    <h2 id="h-conv">🔄 محوّل العملات</h2>
    <div class="converter-form">
      <div class="conv-field">
        <label>المبلغ</label>
        <input type="number" id="convAmt" value="100" min="0" step="any">
      </div>
      <div class="conv-field">
        <label>من</label>
        <select id="convFrom">
          <option value="USD" selected>🇺🇸 USD — دولار أمريكي</option>
          <?php foreach ($currencyData as $code => [$name, $flag, $slug]): ?>
          <option value="<?= h($code) ?>"><?= h($flag) ?> <?= h($code) ?> — <?= h($name) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button onclick="swapConv()" class="swap-btn" title="تبديل">⇄</button>
      <div class="conv-field">
        <label>إلى</label>
        <select id="convTo">
          <option value="SAR" selected>🇸🇦 SAR — ريال سعودي</option>
          <?php foreach ($currencyData as $code => [$name, $flag, $slug]): if ($code==='SAR') continue; ?>
          <option value="<?= h($code) ?>"><?= h($flag) ?> <?= h($code) ?> — <?= h($name) ?></option>
          <?php endforeach; ?>
          <option value="USD">🇺🇸 USD — دولار أمريكي</option>
        </select>
      </div>
      <button onclick="convertCurrency()" class="btn-primary">تحويل</button>
    </div>
    <div id="convResult" class="conv-result" hidden></div>
  </section>

  <!-- Rates Table -->
  <section class="card" aria-labelledby="h-rates">
    <div class="section-head">
      <h2 id="h-rates">📋 جدول أسعار الصرف — 1 دولار أمريكي</h2>
      <span class="muted small">المصدر: Frankfurter · آخر تحديث: <?= h($date) ?></span>
    </div>
    <div class="table-wrap">
      <table class="rates-table" aria-label="أسعار الصرف">
        <thead>
          <tr>
            <th>العلم</th>
            <th>العملة</th>
            <th>الرمز</th>
            <th>السعر مقابل 1 دولار</th>
            <th>100 دولار بـ</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($currencyData as $code => [$name, $flag, $slug]):
            $rate = $rates[$code] ?? null; if (!$rate) continue;
            $dec  = in_array($code, ['JPY','INR','TRY']) ? 1 : ($code==='KWD'||$code==='BHD'||$code==='OMR'||$code==='JOD' ? 4 : 2);
          ?>
          <tr>
            <td class="flag-cell"><?= $flag ?></td>
            <td class="name-cell"><?= h($name) ?></td>
            <td class="code-cell"><?= h($code) ?></td>
            <td class="rate-cell"><strong><?= n($rate, $dec) ?></strong></td>
            <td class="calc-cell"><?= n($rate * 100, $dec) ?> <?= h($code) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- GCC quick table -->
  <section class="card" aria-labelledby="h-gcc">
    <h2 id="h-gcc">🌙 عملات الخليج العربي</h2>
    <div class="gcc-cards">
      <?php
      $gccCurr = ['SAR','AED','KWD','QAR','BHD','OMR'];
      foreach ($gccCurr as $code):
        [$name, $flag] = $currencyData[$code];
        $rate = $rates[$code] ?? null; if (!$rate) continue;
        $dec = in_array($code, ['KWD','BHD','OMR']) ? 4 : 2;
      ?>
      <div class="gcc-card">
        <div class="gcc-flag"><?= $flag ?></div>
        <div class="gcc-name"><?= h($name) ?></div>
        <div class="gcc-rate"><?= n($rate, $dec) ?></div>
        <div class="gcc-code"><?= h($code) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- SEO content -->
  <section class="seo-content card">
    <h2>📌 دليل شامل لأسعار الصرف وتحويل العملات</h2>
    <div class="prose">
      <p>يوفر موقع <strong><?= h(SITE_NAME) ?></strong> أحدث أسعار صرف الدولار الأمريكي واليورو والجنيه الإسترليني وجميع العملات العالمية مقابل الريال السعودي والدرهم الإماراتي والجنيه المصري والدينار الكويتي وسائر العملات العربية، محدَّثة كل ساعة من المصادر الرسمية.</p>

      <h3>كيف تستخدم محوّل العملات؟</h3>
      <ol>
        <li>أدخل المبلغ الذي تريد تحويله في حقل "المبلغ"</li>
        <li>اختر العملة المصدر من القائمة (مثال: دولار أمريكي)</li>
        <li>اختر العملة الهدف (مثال: ريال سعودي)</li>
        <li>اضغط "تحويل" للحصول على النتيجة الفورية بالسعر الحالي</li>
        <li>يمكنك الضغط على زر التبديل ⇄ لعكس اتجاه التحويل</li>
      </ol>

      <h3>ما مصدر أسعار الصرف؟</h3>
      <p>نعتمد على بيانات من <strong>Frankfurter API</strong> المتصلة بالبنك المركزي الأوروبي (ECB)، وهو من أكثر مصادر أسعار الصرف موثوقيةً ودقةً عالمياً. يتم تحديث الأسعار كل ساعة تلقائياً.</p>

      <h3>أسئلة شائعة عن أسعار الصرف</h3>
      <details class="faq-item" style="margin:.5rem 0">
        <summary>لماذا يختلف سعر الصرف في البنوك عن الموقع؟</summary>
        <p>الأسعار المعروضة هي أسعار الصرف المرجعية الدولية (Interbank Rate). البنوك ومكاتب الصرافة تضيف هامش ربح يتراوح 1-3% فوق السعر الأساسي، وهو المصدر الطبيعي لربحها من هذه المعاملات.</p>
      </details>
      <details class="faq-item" style="margin:.5rem 0">
        <summary>متى تكون أسعار الصرف الأفضل لتحويل الأموال؟</summary>
        <p>لا يوجد وقت "مثالي" ثابت، لكن تجنب أوقات التقلبات الكبرى كإعلانات الفائدة الأمريكية (Fed) أو بيانات التضخم. خدمات مثل Wise وRemitly تقدم أسعاراً أقرب للسعر المرجعي من البنوك التقليدية.</p>
      </details>
      <details class="faq-item" style="margin:.5rem 0">
        <summary>ما العوامل التي تحرك أسعار الصرف؟</summary>
        <p>تتأثر أسعار الصرف بعدة عوامل: قرارات البنوك المركزية (رفع أو خفض الفائدة)، بيانات التضخم، نمو الناتج المحلي، الميزان التجاري، والأحداث الجيوسياسية. ارتفاع الفائدة في دولة ما يقوي عملتها عادةً.</p>
      </details>

      <h3>نصائح لتوفير المال عند تحويل العملات</h3>
      <ul>
        <li>قارن أسعار عدة جهات قبل التحويل (بنك، صرافة، خدمات إلكترونية)</li>
        <li>خدمات مثل <strong>Wise</strong> و<strong>Revolut</strong> تقدم أسعاراً أقرب للسعر المرجعي</li>
        <li>تجنب تحويل العملات في المطارات — أسعارها دائماً الأسوأ</li>
        <li>لمبالغ كبيرة، تفاوض مع البنك على السعر — قد تحصل على سعر أفضل</li>
      </ul>
    </div>
  </section>
</div>

<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"FinancialService",
  "name":"محوّل العملات — <?= h(SITE_NAME) ?>",
  "description":"تحويل العملات لحظة بلحظة: الدولار واليورو والجنيه مقابل الريال والدرهم والجنيه المصري",
  "url":"<?= h(base_url('exchange')) ?>",
  "inLanguage":"ar",
  "areaServed":"Arab World",
  "currenciesAccepted":"USD,EUR,GBP,SAR,AED,EGP,KWD,QAR"
}
</script>
<script>
window._fxRates = <?= json_encode($rates, JSON_UNESCAPED_UNICODE) ?>;
</script>
