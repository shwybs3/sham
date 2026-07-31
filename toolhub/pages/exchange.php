<?php
$fx    = get_exchange_rates('USD');
$rates = $fx['rates'] ?? [];
$date  = $fx['date']  ?? date('Y-m-d');
$sypRate = $rates['SYP'] ?? get_syp_rate();

// Trigger SYP change notification (max once/hour, non-blocking)
check_syp_price_change();

$currencyData = [
  'SYP'=>['الليرة السورية','🇸🇾','syp'],
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

$arabCurrencies = ['SYP','SAR','AED','EGP','KWD','QAR','BHD','OMR','JOD'];
?>

<!-- ── Syrian Colors Hero ─────────────────────────────── -->
<section class="page-hero syp-hero">
  <div class="container">
    <nav class="breadcrumb" aria-label="مسار التنقل">
      <a href="/">الرئيسية</a> <span>›</span> <span>أسعار الصرف</span>
    </nav>

    <!-- SYP Spotlight -->
    <div class="syp-spotlight">
      <div class="syp-flag">🇸🇾</div>
      <div class="syp-spot-info">
        <h2>الليرة السورية — SYP</h2>
        <div class="syp-spot-rate">
          <?= n($sypRate, 0) ?> <span class="syp-unit">ل.س / دولار</span>
        </div>
        <div class="syp-spot-meta">
          سعر السوق · <span class="live-badge">مباشر</span>
        </div>
      </div>
      <div style="text-align:center;flex-shrink:0">
        <div style="font-size:11px;color:rgba(255,255,255,.55);margin-bottom:.5rem">تحديث</div>
        <div style="font-size:13px;font-weight:700;color:rgba(255,255,255,.85)"><?= h($date) ?></div>
      </div>
    </div>

    <h1 style="font-size:clamp(1.3rem,3vw,2rem);font-weight:900;color:#fff;margin:.5rem 0 .3rem">
      أسعار الصرف اليوم <span class="live-badge">مباشر</span>
    </h1>
    <p style="color:rgba(255,255,255,.75);font-size:.95rem">
      سعر الدولار مقابل الليرة السورية والريال والدرهم وجميع العملات العربية والعالمية
    </p>
  </div>
</section>

<div class="container page-body">

  <!-- ── SYP Quick Converter ──────────────────────────── -->
  <section class="card" style="border-right:4px solid #007A3D" aria-labelledby="h-syp-conv">
    <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap">
      <span style="font-size:1.8rem">🇸🇾</span>
      <div>
        <h2 id="h-syp-conv" style="margin:0;font-size:1.1rem">محوّل الليرة السورية</h2>
        <p style="margin:0;font-size:12px;color:var(--c-text2)">تحويل سريع بين الدولار الأمريكي والليرة السورية</p>
      </div>
    </div>
    <div class="converter-form">
      <div class="conv-field">
        <label>المبلغ بالدولار</label>
        <input type="number" id="sypAmt" value="1" min="0" step="any"
               style="border-color:rgba(0,122,61,.5)">
      </div>
      <button onclick="calcSyp()" class="btn-primary" style="background:linear-gradient(135deg,#007A3D,#22c55e);align-self:flex-end;padding:.6rem 1.4rem">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v20M2 12l10 10 10-10"/></svg>
        احسب
      </button>
    </div>
    <div id="sypResult" class="conv-result" hidden></div>
    <p style="margin-top:.85rem;font-size:12px;color:var(--c-text3)">
      * سعر الليرة السورية معدل السوق — يمكن للأدمن تحديثه من لوحة الإعدادات
    </p>
  </section>

  <!-- ── Currency Converter ───────────────────────────── -->
  <section class="card converter-card" aria-labelledby="h-conv">
    <h2 id="h-conv">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline-block;vertical-align:middle;color:var(--c-primary)"><path d="M8 3H5a2 2 0 00-2 2v3m18 0V5a2 2 0 00-2-2h-3m0 18h3a2 2 0 002-2v-3M3 16v3a2 2 0 002 2h3"/></svg>
      محوّل العملات الشامل
    </h2>
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
      <button onclick="swapConv()" class="swap-btn" title="تبديل" aria-label="تبديل العملتين">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M7 16V4m0 0L3 8m4-4l4 4"/><path d="M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
      </button>
      <div class="conv-field">
        <label>إلى</label>
        <select id="convTo">
          <option value="SYP" selected>🇸🇾 SYP — الليرة السورية</option>
          <option value="SAR">🇸🇦 SAR — ريال سعودي</option>
          <?php foreach ($currencyData as $code => [$name, $flag, $slug]):
            if ($code === 'SAR' || $code === 'SYP') continue; ?>
          <option value="<?= h($code) ?>"><?= h($flag) ?> <?= h($code) ?> — <?= h($name) ?></option>
          <?php endforeach; ?>
          <option value="USD">🇺🇸 USD — دولار أمريكي</option>
        </select>
      </div>
      <button onclick="convertCurrency()" class="btn-primary" style="align-self:flex-end">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        تحويل
      </button>
    </div>
    <div id="convResult" class="conv-result" hidden></div>
  </section>

  <!-- ── Arab Currencies Grid ─────────────────────────── -->
  <section class="card" aria-labelledby="h-arab">
    <h2 id="h-arab">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline-block;vertical-align:middle;color:var(--c-gold)"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 010 20M12 2a15.3 15.3 0 000 20"/></svg>
      العملات العربية — مقابل الدولار
    </h2>
    <div class="arab-currencies-grid">
      <?php foreach ($arabCurrencies as $code):
        [$name, $flag] = $currencyData[$code];
        $rate = $rates[$code] ?? ($code === 'SYP' ? $sypRate : null);
        if (!$rate) continue;
        $dec = in_array($code, ['KWD','BHD','OMR','JOD']) ? 4 : ($code === 'SYP' ? 0 : 2);
        $isSyp = $code === 'SYP';
      ?>
      <div class="arab-curr-card<?= $isSyp ? ' syp-card' : '' ?>">
        <div class="acc-flag"><?= $flag ?></div>
        <div class="acc-info">
          <div class="acc-name"><?= h($name) ?></div>
          <div class="acc-rate<?= $isSyp ? ' syp' : '' ?>"><?= n($rate, $dec) ?></div>
          <div class="acc-code"><?= h($code) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ── Global Currencies Table ──────────────────────── -->
  <section class="card" aria-labelledby="h-rates">
    <div class="section-head">
      <h2 id="h-rates">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline-block;vertical-align:middle;color:var(--c-primary)"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18"/></svg>
        جدول أسعار الصرف — 1 دولار أمريكي
      </h2>
      <span class="muted small">المصدر: Frankfurter/ECB · <?= h($date) ?></span>
    </div>
    <div class="table-wrap">
      <table class="rates-table" aria-label="أسعار الصرف">
        <thead>
          <tr>
            <th></th>
            <th>العملة</th>
            <th>الرمز</th>
            <th>السعر مقابل 1$</th>
            <th>100$ يساوي</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($currencyData as $code => [$name, $flag, $slug]):
            $rate = $rates[$code] ?? ($code === 'SYP' ? $sypRate : null);
            if (!$rate) continue;
            $dec  = in_array($code, ['JPY','INR','TRY','SYP']) ? 0 : (in_array($code, ['KWD','BHD','OMR','JOD']) ? 4 : 2);
          ?>
          <tr<?= $code === 'SYP' ? ' style="background:rgba(0,122,61,.06)"' : '' ?>>
            <td class="flag-cell"><?= $flag ?></td>
            <td class="name-cell"><?= h($name) ?><?= $code === 'SYP' ? ' <span style="font-size:10px;color:#22c55e;font-weight:700;padding:1px 6px;background:rgba(0,122,61,.15);border-radius:2rem">جديد</span>' : '' ?></td>
            <td class="code-cell"><?= h($code) ?></td>
            <td class="rate-cell"><strong><?= n($rate, $dec) ?></strong></td>
            <td class="calc-cell"><?= n($rate * 100, $dec) ?> <?= h($code) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- ── SEO Content ───────────────────────────────────── -->
  <section class="seo-content card">
    <h2>📌 دليل شامل لأسعار الصرف وتحويل العملات</h2>
    <div class="prose">

      <h3>🇸🇾 الليرة السورية — نظرة شاملة</h3>
      <p>شهدت الليرة السورية (SYP) تقلبات حادة في السنوات الأخيرة بسبب الأوضاع الاقتصادية والسياسية. يعكس السعر الحالي معدل السوق والتحويلات غير الرسمية. بعد التغيرات السياسية عام 2024، بدأت الليرة تشهد بعض الاستقرار النسبي. السعر المعروض هنا هو تقريبي ومؤشري، وقد يختلف في مكاتب الصرافة المحلية.</p>

      <h3>كيف تستخدم محوّل العملات؟</h3>
      <ol>
        <li>أدخل المبلغ الذي تريد تحويله في حقل "المبلغ"</li>
        <li>اختر العملة المصدر من القائمة (مثال: دولار أمريكي)</li>
        <li>اختر العملة الهدف (مثال: ليرة سورية أو ريال سعودي)</li>
        <li>اضغط "تحويل" للحصول على النتيجة الفورية</li>
        <li>استخدم زر التبديل ⇄ لعكس اتجاه التحويل فوراً</li>
      </ol>

      <h3>ما مصدر أسعار الصرف؟</h3>
      <p>نعتمد على <strong>Frankfurter API</strong> المتصلة بالبنك المركزي الأوروبي (ECB) للعملات الدولية. أما الليرة السورية فسعرها مُحدَّث يدوياً بناءً على معدل السوق، إذ لا تتوفر بيانات SYP من المصادر الرسمية الدولية بشكل مستمر.</p>

      <h3>أسعار الصرف في دول الخليج العربي</h3>
      <p>تتميز دول الخليج بعملات مرتبطة بالدولار الأمريكي (pegged) وذلك لاستقرار اقتصاداتها النفطية:</p>
      <ul>
        <li><strong>ريال سعودي (SAR):</strong> مربوط بالدولار عند 3.75 ريال منذ عام 1986 — ثبات تام</li>
        <li><strong>درهم إماراتي (AED):</strong> مربوط عند 3.6725 درهم — ثبات تام</li>
        <li><strong>دينار كويتي (KWD):</strong> مربوط بسلة عملات (لا بالدولار وحده) — أقل العملات الخليجية تقلباً</li>
        <li><strong>ريال قطري (QAR):</strong> مربوط عند 3.64 ريال — ثبات شبه تام</li>
        <li><strong>دينار بحريني (BHD):</strong> مربوط عند 0.376 دينار</li>
        <li><strong>ريال عُماني (OMR):</strong> مربوط عند 0.385 ريال</li>
      </ul>

      <h3>أسئلة شائعة عن أسعار الصرف</h3>
      <details class="faq-item" style="margin:.5rem 0">
        <summary>كيف يمكن تحويل الأموال بأقل رسوم إلى سوريا؟</summary>
        <p>أكثر وسائل التحويل استخداماً: حوالات المغتربين عبر شركات الصرافة المرخصة (Western Union، MoneyGram، Wise). بعض حوالات الجاليات السورية في الخليج تتم عبر شبكات غير رسمية (حوالات) قد تقدم أسعاراً أفضل لكن بمخاطر أعلى. دائماً تحقق من الترخيص القانوني للجهة قبل الإرسال.</p>
      </details>
      <details class="faq-item" style="margin:.5rem 0">
        <summary>لماذا يختلف سعر الصرف في البنوك عن السوق؟</summary>
        <p>الأسعار المعروضة هي أسعار مرجعية دولية (Interbank Rate). البنوك تضيف هامشاً 1-3% فوق هذا السعر. بالنسبة للسوريين، الفارق أكبر بسبب القيود على التحويل وشُح السيولة؛ يعكس سعر السوق الحقيقي ندرة الدولار في الاقتصاد المحلي.</p>
      </details>
      <details class="faq-item" style="margin:.5rem 0">
        <summary>ما العوامل التي تحرك أسعار الصرف؟</summary>
        <p>تتأثر العملات بقرارات البنوك المركزية (رفع/خفض الفائدة)، بيانات التضخم، الميزان التجاري، والأحداث الجيوسياسية. للدول النامية كمصر وسوريا، احتياطي النقد الأجنبي ومستوى الدين الخارجي وثقة المستثمرين عوامل محددة رئيسية.</p>
      </details>

      <h3>نصائح لتوفير المال عند تحويل العملات</h3>
      <ul>
        <li>قارن أسعار عدة جهات قبل التحويل (بنك، صرافة، خدمات إلكترونية)</li>
        <li>خدمات مثل <strong>Wise</strong> و<strong>Revolut</strong> تقدم أسعاراً أقرب للسعر المرجعي</li>
        <li>تجنب التحويل في المطارات — أسعارها دائماً الأسوأ</li>
        <li>لمبالغ كبيرة، تفاوض مع البنك — قد تحصل على سعر أفضل</li>
        <li>راقب اتجاه السعر: إذا كان ترند الدولار صاعداً فأسرع في التحويل، وإذا كان هابطاً فانتظر</li>
      </ul>
    </div>
  </section>
</div>

<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"FinancialService",
  "name":"محوّل العملات — <?= h(SITE_NAME) ?>",
  "description":"تحويل العملات لحظة بلحظة: الدولار وسعر الليرة السورية والريال السعودي والدرهم الإماراتي وكل العملات العربية",
  "url":"<?= h(base_url('exchange')) ?>",
  "inLanguage":"ar",
  "areaServed":"Arab World",
  "currenciesAccepted":"USD,EUR,GBP,SAR,AED,EGP,KWD,QAR,SYP,JOD,BHD,OMR"
}
</script>
<script>
window._fxRates = <?= json_encode($rates, JSON_UNESCAPED_UNICODE) ?>;
window._sypRate = <?= (float)$sypRate ?>;

function calcSyp() {
  const amt = parseFloat(document.getElementById('sypAmt')?.value || 1);
  const out = document.getElementById('sypResult');
  if (!out || !amt) return;
  const syp = amt * window._sypRate;
  out.innerHTML = `
    <div class="cr-result-big" style="color:#22c55e">${syp.toLocaleString('ar', {maximumFractionDigits:0})}</div>
    <div class="cr-label">${amt.toLocaleString('ar', {maximumFractionDigits:2})} دولار = <strong>${syp.toLocaleString('ar', {maximumFractionDigits:0})} ليرة سورية</strong></div>
    <div class="cr-rate-info">1 USD = ${window._sypRate.toLocaleString('ar', {maximumFractionDigits:0})} SYP · 1000 SYP = ${(1000/window._sypRate).toFixed(4)} USD</div>`;
  out.removeAttribute('hidden');
}
</script>
