<?php $gld = get_gold_prices(); ?>

<section class="page-hero small gold-hero">
  <div class="container">
    <nav class="breadcrumb"><a href="/">الرئيسية</a> <span>›</span> <span>أسعار الذهب</span></nav>
    <h1>أسعار الذهب اليوم <span class="live-badge">مباشر</span></h1>
    <p>سعر الذهب عيار 24 22 21 18 بالجرام بالريال السعودي والدرهم الإماراتي والجنيه المصري — <?= h($gld['date']) ?></p>
  </div>
</section>

<div class="container page-body">

  <!-- Gold price cards -->
  <section class="card" aria-labelledby="h-goldtable">
    <h2 id="h-goldtable">💰 جدول أسعار الذهب بالجرام</h2>
    <div class="table-wrap">
      <table class="rates-table gold-table" aria-label="أسعار الذهب">
        <thead>
          <tr>
            <th>العيار</th>
            <th>سعر الجرام بالريال 🇸🇦</th>
            <th>سعر الجرام بالدرهم 🇦🇪</th>
            <th>سعر الجرام بالجنيه 🇪🇬</th>
            <th>سعر الجرام بالدولار 🇺🇸</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $purities = ['24'=>['عيار 24 (خالص)','#F59E0B'],'22'=>['عيار 22','#FBBF24'],'21'=>['عيار 21','#FCD34D'],'18'=>['عيار 18','#FDE68A'],'14'=>['عيار 14','#FEF3C7'],'10'=>['عيار 10','#FFFBEB']];
          foreach ($purities as $k => [$label, $color]):
            $factor = $gld['purities'][$k] ?? 1;
            $sarG = round(($gld['sar_g'] ?? 0) * $factor, 2);
            $aedG = round(($gld['aed_g'] ?? 0) * $factor, 2);
            $egpG = round(($gld['egp_g'] ?? 0) * $factor, 2);
            $usdG = round(($gld['usd_g'] ?? 0) * $factor, 2);
          ?>
          <tr>
            <td><strong style="color:<?= h($color) ?>"><?= h($label) ?></strong></td>
            <td><strong><?= n($sarG) ?></strong> ر.س</td>
            <td><?= n($aedG) ?> د.إ</td>
            <td><?= n($egpG) ?> ج.م</td>
            <td><?= n($usdG) ?> $</td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="muted small">سعر الذهب عالمياً: <?= n($gld['usd_oz'] ?? 0) ?> دولار للأوقية · محدث: <?= h($gld['date']) ?></p>
  </section>

  <!-- Weight Calculator -->
  <section class="card" aria-labelledby="h-goldcalc">
    <h2 id="h-goldcalc">⚖️ حاسبة الذهب</h2>
    <p>احسب قيمة مقدار محدد من الذهب بأي عيار وأي عملة</p>
    <div class="converter-form">
      <div class="conv-field">
        <label>الوزن (جرام)</label>
        <input type="number" id="goldGrams" value="10" min="0.1" step="0.1">
      </div>
      <div class="conv-field">
        <label>العيار</label>
        <select id="goldPurity">
          <option value="1">عيار 24 (خالص)</option>
          <option value="0.9167">عيار 22</option>
          <option value="0.875" selected>عيار 21</option>
          <option value="0.75">عيار 18</option>
          <option value="0.5833">عيار 14</option>
          <option value="0.4167">عيار 10</option>
        </select>
      </div>
      <div class="conv-field">
        <label>العملة</label>
        <select id="goldCurrency">
          <option value="sar" selected>ريال سعودي</option>
          <option value="aed">درهم إماراتي</option>
          <option value="egp">جنيه مصري</option>
          <option value="usd">دولار أمريكي</option>
        </select>
      </div>
      <button onclick="calcGold()" class="btn-primary">احسب القيمة</button>
    </div>
    <div id="goldResult" class="conv-result" hidden></div>
  </section>

  <!-- Gold price chart placeholder -->
  <section class="card" aria-labelledby="h-goldinfo">
    <h2 id="h-goldinfo">📈 معلومات سعر الذهب</h2>
    <div class="info-boxes">
      <div class="info-box">
        <div class="ib-label">سعر الأوقية (دولار)</div>
        <div class="ib-val"><?= n($gld['usd_oz'] ?? 0) ?></div>
      </div>
      <div class="info-box">
        <div class="ib-label">جرام عيار 21 (ريال)</div>
        <div class="ib-val"><?= n(($gld['sar_g'] ?? 0) * 0.875) ?></div>
      </div>
      <div class="info-box">
        <div class="ib-label">جرام عيار 21 (درهم)</div>
        <div class="ib-val"><?= n(($gld['aed_g'] ?? 0) * 0.875) ?></div>
      </div>
      <div class="info-box">
        <div class="ib-label">جرام عيار 21 (جنيه)</div>
        <div class="ib-val"><?= n(($gld['egp_g'] ?? 0) * 0.875) ?></div>
      </div>
    </div>
  </section>

  <section class="seo-content card">
    <h2>📌 الدليل الشامل لأسعار الذهب وكيفية الاستثمار فيه</h2>
    <div class="prose">
      <h3>فهم عيارات الذهب</h3>
      <p>عيار الذهب يُعبّر عن نسبة الذهب الخالص في السبيكة أو القطعة:</p>
      <ul>
        <li><strong>عيار 24:</strong> 99.9% ذهب خالص — مثالي للاستثمار والسبائك، لكنه طري ولا يُستخدم في المجوهرات عادةً</li>
        <li><strong>عيار 22:</strong> 91.7% ذهب — متوازن بين الصلابة والقيمة، شائع في العملات الذهبية</li>
        <li><strong>عيار 21:</strong> 87.5% ذهب — الأكثر شيوعاً في المجوهرات العربية والخليجية</li>
        <li><strong>عيار 18:</strong> 75% ذهب — الأكثر استخداماً عالمياً في المجوهرات الأوروبية وخواتم الخطوبة</li>
        <li><strong>عيار 14:</strong> 58.3% ذهب — شائع في الأسواق الأمريكية، أكثر متانةً وأقل سعراً</li>
      </ul>

      <h3>العوامل التي تحرك سعر الذهب</h3>
      <ul>
        <li><strong>قرارات الفيدرالي الأمريكي:</strong> رفع الفائدة يضعف الذهب، وخفضها يقويه</li>
        <li><strong>قوة الدولار:</strong> علاقة عكسية — الدولار القوي يخفض سعر الذهب بالدولار</li>
        <li><strong>التضخم:</strong> الذهب ملاذ آمن ضد التضخم، يرتفع معه عادةً</li>
        <li><strong>الأزمات الجيوسياسية:</strong> الحروب والتوترات ترفع الطلب على الذهب كأصل آمن</li>
        <li><strong>طلب المجوهرات:</strong> الهند والصين أكبر مستهلكَي ذهب المجوهرات في العالم</li>
        <li><strong>احتياطيات البنوك المركزية:</strong> شراء البنوك للذهب يرفع السعر</li>
      </ul>

      <h3>الاستثمار في الذهب — البدائل</h3>
      <ul>
        <li><strong>الذهب المادي:</strong> سبائك ومسكوكات — الأكثر أماناً لكن يحتاج تخزيناً آمناً</li>
        <li><strong>صناديق الذهب (ETF):</strong> تتبع سعر الذهب دون الحاجة للتخزين المادي</li>
        <li><strong>حسابات الذهب البنكية:</strong> تتيحها بعض البنوك الخليجية بيسر وسيولة عالية</li>
        <li><strong>المجوهرات:</strong> قيمتها تشمل أجر الصياغة (10-30%) الذي لا يُسترد عند البيع</li>
      </ul>

      <h3>أسئلة شائعة عن أسعار الذهب</h3>
      <details class="faq-item" style="margin:.5rem 0">
        <summary>كيف أتحقق من عيار الذهب الذي اشتريته؟</summary>
        <p>ابحث عن الطابع المحفور على القطعة: 750 تعني عيار 18 (75٪)، 875 تعني عيار 21، 916 تعني عيار 22، 999 تعني عيار 24. يمكن أيضاً الذهاب لصاحب ذهب معتمد لإجراء فحص الحمض.</p>
      </details>
      <details class="faq-item" style="margin:.5rem 0">
        <summary>هل يُنصح بشراء الذهب الآن كاستثمار؟</summary>
        <p>الذهب أداة للحفاظ على القيمة وتنويع المحفظة الاستثمارية، وليس للمضاربة قصيرة المدى. ينصح معظم المستشارين الماليين بتخصيص 5-15% من المحفظة للذهب وفق أهدافك وقدرتك على المخاطرة.</p>
      </details>
    </div>
  </section>
</div>

<script>
window._goldSarG = <?= $gld['sar_g'] ?? 0 ?>;
window._goldAedG = <?= $gld['aed_g'] ?? 0 ?>;
window._goldEgpG = <?= $gld['egp_g'] ?? 0 ?>;
window._goldUsdG = <?= $gld['usd_g'] ?? 0 ?>;
</script>
