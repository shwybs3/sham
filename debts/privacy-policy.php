<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'سياسة الخصوصية';
$shopName  = $settings['shop_name'] ?? 'دفتر الدكان';
require __DIR__ . '/includes/page-header.php';
?>
<main class="page narrow" style="padding-top:32px;">
  <div class="panel">
    <h1 style="font-family:var(--disp); font-size:24px; margin:0 0 6px;"><?= icon('shield', 22) ?> سياسة الخصوصية</h1>
    <p style="color:var(--ink-faint); font-size:13px; margin:0 0 24px;">آخر تحديث: <?= date('Y/m/d') ?></p>

    <section>
      <h2 style="font-size:18px;">1. المقدمة</h2>
      <p>
        يلتزم موقع <strong><?= h($shopName) ?></strong> بحماية خصوصيتك. توضح هذه السياسة أنواع البيانات
        التي نجمعها، وكيف نستخدمها، وحقوقك في التحكم بها.
      </p>
    </section>

    <section>
      <h2 style="font-size:18px;">2. البيانات التي نجمعها</h2>
      <h3 style="font-size:15px; color:var(--brand);">أ. بيانات تسجيل الدخول (Google)</h3>
      <p>عند تسجيل الدخول باستخدام حساب Google نجمع:</p>
      <ul>
        <li><strong>المعرّف الفريد (Google ID)</strong> — لتعريف حسابك لدينا</li>
        <li><strong>البريد الإلكتروني</strong> — للتواصل وتعريف الحساب</li>
        <li><strong>الاسم الكامل</strong> — لعرضه في ملفك الشخصي والدردشة</li>
        <li><strong>صورة الملف الشخصي (avatar)</strong> — للعرض في المجموعة العامة</li>
      </ul>
      <p><em>لا نطلب كلمة مرور Google ولا نخزنها.</em></p>

      <h3 style="font-size:15px; color:var(--brand);">ب. بيانات الاستخدام</h3>
      <ul>
        <li>الرسائل التي ترسلها في المجموعة العامة</li>
        <li>تاريخ آخر دخول للموقع</li>
        <li>عنوان IP عند إرسال رسائل الدعم</li>
      </ul>

      <h3 style="font-size:15px; color:var(--brand);">ج. بيانات الدكان (للمالك فقط)</h3>
      <p>
        بيانات الزبائن والديون والمدفوعات مخصصة لصاحب الدكان فقط ولا تُشارك مع أحد.
      </p>
    </section>

    <section>
      <h2 style="font-size:18px;">3. لماذا نجمع هذه البيانات؟</h2>
      <table style="width:100%; border-collapse:collapse; font-size:14px;">
        <thead>
          <tr style="border-bottom:2px solid var(--line);">
            <th style="padding:10px 0; text-align:start;">البيانات</th>
            <th style="padding:10px 0; text-align:start;">الغرض</th>
          </tr>
        </thead>
        <tbody>
          <tr style="border-bottom:1px solid var(--line);">
            <td style="padding:10px 0;">Google ID + البريد</td>
            <td style="padding:10px 0;">تعريف حسابك وتسجيل الدخول بأمان</td>
          </tr>
          <tr style="border-bottom:1px solid var(--line);">
            <td style="padding:10px 0;">الاسم والصورة</td>
            <td style="padding:10px 0;">عرض ملفك في المجموعة العامة</td>
          </tr>
          <tr style="border-bottom:1px solid var(--line);">
            <td style="padding:10px 0;">رسائل الدردشة</td>
            <td style="padding:10px 0;">إتاحة التواصل بين المستخدمين</td>
          </tr>
          <tr>
            <td style="padding:10px 0;">عنوان IP (الدعم)</td>
            <td style="padding:10px 0;">الحماية من الإساءة وسبام رسائل الدعم</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section>
      <h2 style="font-size:18px;">4. مشاركة البيانات</h2>
      <p>
        <strong>لا نبيع بياناتك ولا نشاركها</strong> مع أطراف ثالثة تجارية.
        البيانات الوحيدة المرئية للآخرين هي رسائل الدردشة العامة واسم المستخدم ضمن المجموعة.
      </p>
      <p>
        يستخدم الموقع خدمة <strong>Google Sign-In</strong> لتسجيل الدخول، وتخضع بياناتك
        أيضاً <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">لسياسة خصوصية Google</a>.
      </p>
    </section>

    <section>
      <h2 style="font-size:18px;">5. حقوقك</h2>
      <ul>
        <li>حق الاطلاع على البيانات المخزنة عنك</li>
        <li>حق تعديل اسمك المستعار أو اسم المستخدم من ملفك الشخصي</li>
        <li>حق حذف حسابك وبياناتك بالتواصل عبر زر الدعم</li>
      </ul>
    </section>

    <section>
      <h2 style="font-size:18px;">6. ملفات تعريف الارتباط (Cookies)</h2>
      <p>
        نستخدم <em>session cookie</em> واحداً ضرورياً لتسجيل دخولك.
        لا نستخدم ملفات تتبع إعلانية.
      </p>
    </section>

    <section>
      <h2 style="font-size:18px;">7. التواصل</h2>
      <p>لأي استفسار أو طلب حذف بيانات، تواصل معنا عبر زر الدعم في الموقع.</p>
    </section>
  </div>
</main>

<?php require __DIR__ . '/includes/page-footer.php'; ?>
