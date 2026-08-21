<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'شروط الاستخدام';
$shopName  = $settings['shop_name'] ?? 'دفتر الدكان';
require __DIR__ . '/includes/page-header.php';
?>
<main class="page narrow" style="padding-top:32px;">
  <div class="panel">
    <h1 style="font-family:var(--disp); font-size:24px; margin:0 0 6px;"><?= icon('receipt', 22) ?> شروط الاستخدام</h1>
    <p style="color:var(--ink-faint); font-size:13px; margin:0 0 24px;">آخر تحديث: <?= date('Y/m/d') ?></p>

    <section>
      <h2 style="font-size:18px;">1. القبول بالشروط</h2>
      <p>
        باستخدام موقع <strong><?= h($shopName) ?></strong> أو تسجيل الدخول إليه، فإنك توافق على هذه الشروط.
        إن لم توافق، أوقف استخدام الموقع.
      </p>
    </section>

    <section>
      <h2 style="font-size:18px;">2. الغرض من الموقع</h2>
      <p>
        الموقع أداة لتسجيل ديون الزبائن ومتابعة المدفوعات، ويوفر مجموعة دردشة عامة للمستخدمين المسجلين.
        استخدامه خارج هذا الغرض مسؤولية المستخدم وحده.
      </p>
    </section>

    <section>
      <h2 style="font-size:18px;">3. الحساب وتسجيل الدخول</h2>
      <ul>
        <li>تسجيل الدخول يتم حصراً عبر Google.</li>
        <li>مسؤوليتك أن تحتفظ بأمان حسابك.</li>
        <li>يحق لنا تعليق الحساب أو حذفه عند انتهاك الشروط.</li>
      </ul>
    </section>

    <section>
      <h2 style="font-size:18px;">4. المجموعة العامة</h2>
      <ul>
        <li>يُحظر نشر محتوى مسيء، عنصري، تحريضي، أو مخالف للقانون.</li>
        <li>يُحظر الإعلانات والسبام والتشهير بالأشخاص.</li>
        <li>الرسائل المنشورة مرئية لجميع مستخدمي المجموعة.</li>
        <li>للإدارة الحق في حذف أي رسالة أو حظر أي مستخدم.</li>
      </ul>
    </section>

    <section>
      <h2 style="font-size:18px;">5. الملكية الفكرية</h2>
      <p>
        كل المحتوى الذي تنشره يظل ملكاً لك. بنشره في المجموعة تمنح الموقع حق عرضه للمستخدمين الآخرين.
      </p>
    </section>

    <section>
      <h2 style="font-size:18px;">6. إخلاء المسؤولية</h2>
      <p>
        الموقع يُقدَّم "كما هو" دون ضمانات. لا نتحمل مسؤولية أي خسارة ناتجة عن استخدامه أو
        عدم دقة البيانات المدخلة من قِبل المستخدم.
      </p>
    </section>

    <section>
      <h2 style="font-size:18px;">7. التعديلات</h2>
      <p>
        يحق لنا تعديل هذه الشروط في أي وقت. استمرارك في استخدام الموقع بعد التعديل يُعدّ قبولاً للشروط الجديدة.
      </p>
    </section>

    <section>
      <h2 style="font-size:18px;">8. التواصل</h2>
      <p>للاستفسار أو الشكاوى، استخدم زر الدعم في الموقع.</p>
    </section>
  </div>
</main>

<?php require __DIR__ . '/includes/page-footer.php'; ?>
