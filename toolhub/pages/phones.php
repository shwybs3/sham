<?php
$phones = [
  ['Apple','iPhone 16 Pro Max','samsung-logo.png',5499,1469,'8GB','256GB','6.9"','48MP','4685mAh',2025,'flagship'],
  ['Apple','iPhone 16 Pro','',4799,1279,'8GB','128GB','6.3"','48MP','3582mAh',2025,'flagship'],
  ['Apple','iPhone 16','',3499,929,'8GB','128GB','6.1"','48MP','3561mAh',2025,'mid'],
  ['Apple','iPhone 15','',2799,749,'6GB','128GB','6.1"','48MP','3877mAh',2024,'mid'],
  ['Samsung','Galaxy S25 Ultra','',5299,1399,'12GB','256GB','6.9"','200MP','5000mAh',2025,'flagship'],
  ['Samsung','Galaxy S25+','',4499,1199,'12GB','256GB','6.7"','50MP','4900mAh',2025,'flagship'],
  ['Samsung','Galaxy S25','',3399,899,'12GB','128GB','6.2"','50MP','4000mAh',2025,'mid'],
  ['Samsung','Galaxy A55','',1599,429,'8GB','128GB','6.6"','50MP','5000mAh',2024,'budget'],
  ['Samsung','Galaxy A35','',1199,319,'6GB','128GB','6.6"','50MP','5000mAh',2024,'budget'],
  ['Xiaomi','14 Ultra','',4299,1149,'16GB','512GB','6.73"','50MP','5000mAh',2024,'flagship'],
  ['Xiaomi','14T Pro','',2599,699,'12GB','256GB','6.67"','50MP','5000mAh',2024,'mid'],
  ['Xiaomi','Redmi Note 14 Pro','',1299,349,'8GB','256GB','6.67"','200MP','5500mAh',2024,'budget'],
  ['Google','Pixel 9 Pro','',4999,1329,'16GB','128GB','6.3"','50MP','4700mAh',2024,'flagship'],
  ['OnePlus','13','',3299,879,'12GB','256GB','6.82"','50MP','6000mAh',2025,'mid'],
  ['Huawei','Pura 70 Pro','',4199,1099,'12GB','512GB','6.8"','50MP','5050mAh',2024,'flagship'],
  ['OPPO','Find X8 Pro','',4499,1199,'16GB','512GB','6.78"','50MP','5910mAh',2024,'flagship'],
  ['Vivo','X200 Pro','',4199,1099,'16GB','512GB','6.78"','50MP','5800mAh',2024,'flagship'],
];
?>

<section class="page-hero small">
  <div class="container">
    <nav class="breadcrumb"><a href="/">الرئيسية</a> <span>›</span> <span>أسعار الهواتف</span></nav>
    <h1>📱 أسعار الهواتف الذكية 2025</h1>
    <p>مقارنة أحدث أسعار الهواتف الذكية: آيفون، سامسونج، شاومي، جوجل بيكسل والمزيد</p>
  </div>
</section>

<div class="container page-body">
  <!-- Filters -->
  <div class="filters-bar card">
    <button class="filter-btn active" data-filter="all">الكل</button>
    <button class="filter-btn" data-filter="flagship">فلاجشيب</button>
    <button class="filter-btn" data-filter="mid">متوسط</button>
    <button class="filter-btn" data-filter="budget">اقتصادي</button>
    <button class="filter-btn" data-filter="Apple">آيفون</button>
    <button class="filter-btn" data-filter="Samsung">سامسونج</button>
    <button class="filter-btn" data-filter="Xiaomi">شاومي</button>
  </div>

  <!-- Phones table -->
  <section class="card" aria-labelledby="h-phones">
    <h2 id="h-phones">📋 جدول أسعار الهواتف</h2>
    <div class="table-wrap">
      <table class="rates-table phones-table" id="phonesTable">
        <thead>
          <tr>
            <th>الهاتف</th>
            <th>الذاكرة / التخزين</th>
            <th>الشاشة</th>
            <th>الكاميرا</th>
            <th>البطارية</th>
            <th>السعر (ريال)</th>
            <th>السعر (دولار)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($phones as [$brand, $model, $img, $prSar, $prUsd, $ram, $stor, $scr, $cam, $bat, $yr, $tier]): ?>
          <tr class="phone-row" data-brand="<?= h(strtolower($brand)) ?>" data-tier="<?= h($tier) ?>">
            <td>
              <div class="phone-name-cell">
                <strong><?= h($brand) ?> <?= h($model) ?></strong>
                <span class="badge-tier badge-<?= h($tier) ?>"><?= $tier==='flagship'?'فلاجشيب':($tier==='mid'?'متوسط':'اقتصادي') ?></span>
              </div>
            </td>
            <td><?= h($ram) ?> / <?= h($stor) ?></td>
            <td><?= h($scr) ?></td>
            <td><?= h($cam) ?></td>
            <td><?= h($bat) ?></td>
            <td><strong><?= n($prSar, 0) ?></strong> ر.س</td>
            <td><?= n($prUsd, 0) ?> $</td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="seo-content card">
    <h2>📌 الدليل الشامل لاختيار الهاتف الذكي المناسب 2025</h2>
    <div class="prose">
      <p>مع تزايد الخيارات في سوق الهواتف الذكية، أصبح اختيار الهاتف المناسب أمراً يستحق التمحيص. سواء كانت ميزانيتك محدودة أو كنت تبحث عن أفضل ما في الأسواق، هذا الدليل يساعدك على اتخاذ القرار الصحيح.</p>

      <h3>معايير اختيار الهاتف الذكي</h3>
      <ul>
        <li><strong>الميزانية:</strong> حدد سقفاً للإنفاق قبل البدء — الفئة الاقتصادية (أقل من 1500 ر.س)، المتوسطة (1500-3500)، والفلاجشيب (فوق 3500)</li>
        <li><strong>نظام التشغيل:</strong> iOS (آيفون) يتميز بتحديثات طويلة الأمد وخصوصية أفضل. Android يوفر تنوعاً أوسع وأسعاراً لكل الفئات</li>
        <li><strong>المعالج:</strong> A18 Pro في آيفون 16 Pro وSnapdragon 8 Elite هما الأسرع حالياً. للفئة المتوسطة: Snapdragon 7s أو Dimensity 9200</li>
        <li><strong>البطارية:</strong> لا تقل عن 4500mAh للاستخدام المتوسط. فوق 5000mAh للاستخدام المكثف</li>
        <li><strong>سرعة الشحن:</strong> الشحن السريع 65W+ يوفر شحناً كاملاً في أقل من ساعة</li>
        <li><strong>الكاميرا:</strong> عدد الميغابيكسل ليس المعيار الوحيد — حجم الحساس وكيفية المعالجة تؤثران أكثر</li>
      </ul>

      <h3>مقارنة iOS مقابل Android في 2025</h3>
      <table style="width:100%;border-collapse:collapse;margin-top:.75rem;font-size:13px">
        <tr style="background:var(--c-bg2)"><th style="padding:8px;text-align:right;border:1px solid var(--c-border)">الميزة</th><th style="padding:8px;border:1px solid var(--c-border)">iOS (آيفون)</th><th style="padding:8px;border:1px solid var(--c-border)">Android</th></tr>
        <tr><td style="padding:8px;border:1px solid var(--c-border)">مدة دعم التحديثات</td><td style="padding:8px;border:1px solid var(--c-border);text-align:center">6-7 سنوات ✅</td><td style="padding:8px;border:1px solid var(--c-border);text-align:center">2-7 سنوات (حسب العلامة)</td></tr>
        <tr style="background:var(--c-bg2)"><td style="padding:8px;border:1px solid var(--c-border)">تنوع الأسعار</td><td style="padding:8px;border:1px solid var(--c-border);text-align:center">محدود</td><td style="padding:8px;border:1px solid var(--c-border);text-align:center">واسع جداً ✅</td></tr>
        <tr><td style="padding:8px;border:1px solid var(--c-border)">الخصوصية والأمان</td><td style="padding:8px;border:1px solid var(--c-border);text-align:center">أعلى ✅</td><td style="padding:8px;border:1px solid var(--c-border);text-align:center">متوسط</td></tr>
        <tr style="background:var(--c-bg2)"><td style="padding:8px;border:1px solid var(--c-border)">تخصيص النظام</td><td style="padding:8px;border:1px solid var(--c-border);text-align:center">محدود</td><td style="padding:8px;border:1px solid var(--c-border);text-align:center">كامل ✅</td></tr>
      </table>

      <h3>أسئلة شائعة قبل شراء الهاتف</h3>
      <details class="faq-item" style="margin:.5rem 0">
        <summary>هل يستحق الفلاجشيب ضعف سعر الهاتف المتوسط؟</summary>
        <p>للاستخدام العادي (تصفح، واتساب، سناب، كاميرا عادية) لا. الهواتف المتوسطة مثل Samsung A55 أو Redmi Note 14 Pro تلبي 90% من الاحتياجات بنصف السعر. الفلاجشيب يستحق إذا كنت مصوراً محترفاً أو تحتاج أعلى أداء للألعاب أو الإنتاجية المكثفة.</p>
      </details>
      <details class="faq-item" style="margin:.5rem 0">
        <summary>ما أفضل وقت لشراء الهاتف بسعر أرخص؟</summary>
        <p>موسم الجمعة السوداء (نوفمبر) وموسم رمضان والعيد يشهدان أكبر التخفيضات في السوق السعودية والخليجي. كذلك عند إطلاق الجيل الجديد تنخفض أسعار الجيل السابق بشكل ملحوظ (20-40%).</p>
      </details>
    </div>
  </section>
</div>

<script>
document.querySelectorAll('.filter-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    const f = this.dataset.filter;
    document.querySelectorAll('.phone-row').forEach(row => {
      const show = f === 'all' || row.dataset.brand === f.toLowerCase() || row.dataset.tier === f;
      row.style.display = show ? '' : 'none';
    });
  });
});
</script>
