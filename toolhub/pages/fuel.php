<?php
// Fuel prices (manually curated, updated periodically)
$fuelData = [
  'السعودية' => ['🇸🇦','91'=>'0.67','95'=>'0.90','ديزل'=>'0.23','وحدة'=>'ر.س/لتر'],
  'الإمارات' => ['🇦🇪','E-Plus'=>'2.44','Special'=>'2.57','ديزل'=>'2.66','وحدة'=>'د.إ/لتر'],
  'الكويت'   => ['🇰🇼','عادي'=>'0.065','ممتاز'=>'0.075','ديزل'=>'0.200','وحدة'=>'د.ك/لتر'],
  'قطر'       => ['🇶🇦','91'=>'1.85','95'=>'1.95','ديزل'=>'1.80','وحدة'=>'ر.ق/لتر'],
  'البحرين'   => ['🇧🇭','91'=>'0.19','95'=>'0.21','ديزل'=>'0.13','وحدة'=>'د.ب/لتر'],
  'عُمان'     => ['🇴🇲','91'=>'0.190','95'=>'0.220','ديزل'=>'0.290','وحدة'=>'ر.ع/لتر'],
  'مصر'       => ['🇪🇬','92'=>'13.75','95'=>'16.25','ديزل'=>'10.25','وحدة'=>'ج.م/لتر'],
  'الأردن'    => ['🇯🇴','90'=>'0.810','95'=>'0.930','ديزل'=>'0.750','وحدة'=>'د.أ/لتر'],
];
$updated = 'يوليو 2025';
?>

<section class="page-hero small">
  <div class="container">
    <nav class="breadcrumb"><a href="/">الرئيسية</a> <span>›</span> <span>أسعار المحروقات</span></nav>
    <h1>⛽ أسعار المحروقات اليوم</h1>
    <p>أسعار البنزين والديزل في السعودية والإمارات والكويت وقطر ومصر وباقي دول المنطقة — <?= h($updated) ?></p>
  </div>
</section>

<div class="container page-body">
  <div class="alert-info">
    📢 آخر تحديث لأسعار المحروقات: <strong><?= h($updated) ?></strong>. الأسعار قابلة للتغيير وفق قرارات الحكومات.
  </div>

  <?php foreach ($fuelData as $country => $data):
    $flag = $data[0];
    $unit = $data['وحدة'];
    unset($data[0], $data['وحدة']);
  ?>
  <section class="card fuel-card" aria-label="أسعار المحروقات في <?= h($country) ?>">
    <h2><?= $flag ?> <?= h($country) ?></h2>
    <div class="fuel-types">
      <?php foreach ($data as $type => $price): ?>
      <div class="fuel-item">
        <div class="fuel-type"><?= h($type) ?></div>
        <div class="fuel-price"><?= h($price) ?></div>
        <div class="fuel-unit"><?= h($unit) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endforeach; ?>

  <section class="seo-content card">
    <h2>📌 معلومات عن أسعار الوقود في الخليج</h2>
    <div class="prose">
      <p>تتميز دول الخليج العربي بأسعار وقود منخفضة نسبياً مقارنة بالمعدلات العالمية، نظراً للدعم الحكومي ولكون هذه الدول من أكبر منتجي النفط في العالم. الأسعار مقومة بالعملات المحلية لكل دولة.</p>
      <h3>مقارنة الأسعار</h3>
      <p>تُعدّ الكويت صاحبة أرخص أسعار البنزين في منطقة الخليج، تليها السعودية ثم البحرين. أما الإمارات فتعتمد نظام تسعير متحرك مرتبط بأسعار النفط العالمية.</p>
    </div>
  </section>
</div>

<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"Dataset",
  "name":"أسعار المحروقات في دول الخليج — <?= h(SITE_NAME) ?>",
  "description":"أسعار البنزين والديزل في السعودية والإمارات والكويت وقطر والبحرين وعمان ومصر",
  "url":"<?= h(base_url('fuel')) ?>",
  "inLanguage":"ar",
  "dateModified":"<?= date('Y-m-d') ?>"
}
</script>
