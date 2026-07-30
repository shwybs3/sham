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
    <h2>📌 دليل شراء الهواتف الذكية</h2>
    <div class="prose">
      <h3>كيف تختار الهاتف المناسب؟</h3>
      <ul>
        <li><strong>الميزانية:</strong> حدد سقفاً للإنفاق مسبقاً</li>
        <li><strong>نظام التشغيل:</strong> iOS (آيفون) أو Android (سامسونج، شاومي...)</li>
        <li><strong>الكاميرا:</strong> للتصوير الاحترافي انظر للميغابكسل ومواصفات الكاميرا</li>
        <li><strong>البطارية:</strong> كلما زادت عن 5000mAh كلما كانت أفضل</li>
        <li><strong>المعالج:</strong> A18 Pro (آيفون) وSnapdragon 8 Elite (أندرويد) هما الأقوى</li>
      </ul>
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
