<section class="page-hero small">
  <div class="container">
    <nav class="breadcrumb"><a href="/">الرئيسية</a> <span>›</span> <span>من نحن</span></nav>
    <h1>🌐 من نحن</h1>
    <p>بوابة الأدوات الشاملة للمستخدم العربي</p>
  </div>
</section>

<div class="container page-body" style="max-width:800px">
  <section class="card prose" style="line-height:2">
    <h2>عن <?= h(SITE_NAME) ?></h2>
    <p><?= h(SITE_NAME) ?> هي بوابة رقمية شاملة تقدم أسعار الصرف والذهب والمحروقات والمواد الغذائية لحظة بلحظة، إلى جانب مجموعة أدوات مجانية للاستخدام اليومي.</p>

    <h3>ما الذي نقدمه؟</h3>
    <ul>
      <li><strong>أسعار الصرف:</strong> تحديث كل ساعة من البنك المركزي الأوروبي</li>
      <li><strong>أسعار الذهب:</strong> عيار 24، 22، 21، 18 بالريال والدرهم والجنيه والدولار</li>
      <li><strong>أسعار المحروقات:</strong> البنزين والديزل في دول الخليج ومصر والأردن</li>
      <li><strong>أدوات PDF:</strong> دمج وضغط وتحويل الملفات في المتصفح مباشرةً</li>
      <li><strong>أدوات الصور:</strong> ضغط وتغيير حجم وتحويل صيغة الصور</li>
      <li><strong>حاسبات البناء:</strong> خرسانة وطوب وبلاط ودهان وصلب</li>
      <li><strong>حاسبة الطاقة الشمسية:</strong> احسب عدد الألواح والتكلفة وفترة الاسترداد</li>
      <li><strong>مواقع فرعية:</strong> تحميل يوتيوب وتيك توك واستخراج النصوص من الصور</li>
    </ul>

    <h3>خصوصيتك تهمنا</h3>
    <p>معظم أدواتنا تعمل بالكامل في متصفحك — لا ترفع ملفاتك أو صورك إلى أي خادم. الأسعار تُجلب من مصادر عامة موثوقة ولا يتم تخزين أي بيانات شخصية.</p>

    <h3>مصادر البيانات</h3>
    <ul>
      <li>أسعار الصرف: <strong>Frankfurter API</strong> (البنك المركزي الأوروبي)</li>
      <li>أسعار الذهب: حساب مشتق من سعر الأوقية بالدولار</li>
      <li>أسعار المحروقات: مُحدَّثة يدوياً من المصادر الرسمية</li>
    </ul>
  </section>

  <section class="card">
    <h2>📞 تواصل معنا</h2>
    <p style="color:var(--c-text2)">للاقتراحات أو الملاحظات أو الإبلاغ عن خطأ، راسلنا عبر <a href="/contact">صفحة التواصل</a>.</p>
  </section>
</div>

<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"Organization",
  "name":"<?= h(SITE_NAME) ?>",
  "url":"<?= h(SITE_URL) ?>",
  "description":"<?= h(SITE_TAGLINE) ?>",
  "inLanguage":"ar"
}
</script>
