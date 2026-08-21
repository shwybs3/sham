<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$contactEmail = get_cfg($pdo, 'contact_email') ?: 'contact@' . (parse_url(SITE_URL, PHP_URL_HOST) ?: 'yassota.com');
$siteHost     = parse_url(SITE_URL, PHP_URL_HOST) ?: 'yassota.com';
$updated      = get_cfg($pdo, 'privacy_updated_date', '1 يناير 2025');
$seoTitle     = 'سياسة الخصوصية — yassota';
$metaDesc     = 'سياسة الخصوصية الكاملة لموقع yassota: ما نجمعه من بيانات، كيف نستخدمها، Google AdSense، ملفات تعريف الارتباط، حقوقك وفق GDPR.';
?>
<!DOCTYPE html>
<html lang="<?= defined('UI_LANG') ? UI_LANG : 'ar' ?>" dir="<?= defined('UI_DIR') ? UI_DIR : 'rtl' ?>">
<head>
  <?= nav_guard_script() ?>
  <meta charset="UTF-8">
  <?= head_extras($pdo) ?>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title><?= h($seoTitle) ?></title>
  <meta name="description" content="<?= h($metaDesc) ?>">
  <meta name="robots" content="index,follow">
  <link rel="canonical" href="<?= h(url('privacy-policy')) ?>">
  <meta property="og:title" content="<?= h($seoTitle) ?>">
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/main.css')) ?>">
</head>
<body>

<?php render_site_header(); ?>

<div class="page-wrap fw">

<main class="main-content">
  <nav style="font-size:12px;color:var(--muted);margin-bottom:16px;display:flex;gap:6px;align-items:center;flex-wrap:wrap">
    <a href="<?= h(url('')) ?>" style="color:var(--cyan)">الرئيسية</a><span>/</span><span>سياسة الخصوصية</span>
  </nav>

  <div class="section-head reveal"><span class="section-title">سياسة الخصوصية</span></div>

  <div class="section-box reveal" style="color:var(--muted);font-size:14px;line-height:1.9">

    <p style="background:rgba(6,182,212,.07);border:1px solid rgba(6,182,212,.2);border-radius:10px;padding:14px 18px;margin-bottom:22px;font-size:13px">
      <strong style="color:var(--white)">آخر تحديث:</strong> <?= $updated ?> |
      <strong style="color:var(--white)">تسري على:</strong> <?= h($siteHost) ?>
    </p>

    <p>تحترم منصة yassota خصوصية زوارها احتراماً كاملاً. توضح هذه السياسة بشكل مفصّل ما نجمعه من بيانات أثناء زيارتك للموقع، كيف نستخدمها وحمايتها، ومن يشاركنا فيها. باستمرارك في استخدام الموقع، فإنك تقرّ بأنك اطلعت على هذه السياسة ووافقت على أحكامها.</p>

    <!-- ── Section 1 ── -->
    <h2 style="color:var(--white);font-size:16px;font-weight:800;margin:24px 0 10px;padding-bottom:8px;border-bottom:1px solid var(--border-c)">١. من نحن وكيف نتواصل</h2>
    <p>
      yassota هو الموقع الإلكتروني الذي تزوره (<strong style="color:var(--white)"><?= h($siteHost) ?></strong>)، وهو دليل تحريري مستقل متخصص في مراجعة تطبيقات وألعاب أندرويد. للتواصل بشأن الخصوصية:
    </p>
    <ul style="margin:10px 0 0 0;padding-right:20px">
      <li>البريد الإلكتروني: <a href="mailto:<?= h($contactEmail) ?>" style="color:var(--cyan)"><?= h($contactEmail) ?></a></li>
      <li>نموذج التواصل: <a href="<?= h(url('contact')) ?>" style="color:var(--cyan)"><?= h(url('contact')) ?></a></li>
    </ul>

    <!-- ── Section 2 ── -->
    <h2 style="color:var(--white);font-size:16px;font-weight:800;margin:24px 0 10px;padding-bottom:8px;border-bottom:1px solid var(--border-c)">٢. البيانات التي نجمعها</h2>
    <p><strong style="color:var(--white)">أ. بيانات تُقدّمها أنت:</strong></p>
    <ul style="margin:0 0 14px 0;padding-right:20px">
      <li>إذا أرسلت رسالة عبر نموذج التواصل: اسمك وبريدك الإلكتروني ومحتوى رسالتك.</li>
      <li>إذا تركت تعليقاً أو تقييماً على صفحة تطبيق: الاسم المُدخَل ومحتوى التعليق.</li>
    </ul>
    <p><strong style="color:var(--white)">ب. بيانات تُجمَع تلقائياً:</strong></p>
    <ul style="margin:0;padding-right:20px">
      <li>عنوان IP (مجهول جزئياً بعد المعالجة)</li>
      <li>نوع المتصفح وإصداره، ونظام التشغيل</li>
      <li>الصفحات المزارة ومدة الجلسة</li>
      <li>الصفحة المُحيلة (المصدر الذي أتيت منه)</li>
      <li>دقة الشاشة واللغة المفضّلة</li>
    </ul>
    <p style="margin-top:10px"><strong style="color:var(--white)">لا</strong> نجمع: أرقام بطاقات ائتمان، أرقام هاتف، بيانات موقع جغرافي دقيق، أو بيانات حسابات شبكات التواصل الاجتماعي.</p>

    <!-- ── Section 3 ── -->
    <h2 style="color:var(--white);font-size:16px;font-weight:800;margin:24px 0 10px;padding-bottom:8px;border-bottom:1px solid var(--border-c)">٣. كيف نستخدم البيانات</h2>
    <ul style="margin:0;padding-right:20px">
      <li>تشغيل الموقع وضمان عمله بشكل صحيح</li>
      <li>الرد على استفساراتك عبر نموذج التواصل</li>
      <li>مراجعة التعليقات قبل نشرها (نظام الإشراف)</li>
      <li>تحسين تجربة المستخدم بناءً على بيانات الزيارة الإجمالية (إحصائيات مجمّعة)</li>
      <li>عرض إعلانات ذات صلة عبر Google AdSense (انظر القسم ٥)</li>
      <li>الامتثال للالتزامات القانونية عند الاقتضاء</li>
    </ul>

    <!-- ── Section 4 ── -->
    <h2 style="color:var(--white);font-size:16px;font-weight:800;margin:24px 0 10px;padding-bottom:8px;border-bottom:1px solid var(--border-c)">٤. ملفات تعريف الارتباط (Cookies)</h2>
    <p>نستخدم أنواعاً متعددة من ملفات تعريف الارتباط:</p>

    <div style="overflow-x:auto;margin:14px 0">
      <table style="width:100%;border-collapse:collapse;font-size:12px;min-width:480px">
        <thead>
          <tr style="background:rgba(6,182,212,.1);border-bottom:2px solid rgba(6,182,212,.3)">
            <th style="padding:10px 12px;text-align:right;color:var(--white);font-weight:700">النوع</th>
            <th style="padding:10px 12px;text-align:right;color:var(--white);font-weight:700">المصدر</th>
            <th style="padding:10px 12px;text-align:right;color:var(--white);font-weight:700">الغرض</th>
            <th style="padding:10px 12px;text-align:right;color:var(--white);font-weight:700">المدة</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ([
            ['ضروري','yassota','حفظ الجلسة وإعدادات الموافقة على الكوكيز','جلسة / 1 سنة'],
            ['تحليلي','Google Analytics (إن فُعّل)','إحصاءات عدد الزوار والصفحات','حتى 2 سنة'],
            ['إعلاني','Google AdSense','عرض إعلانات مخصصة وقياس أدائها','حتى 13 شهراً'],
            ['تفضيلات','yassota','حفظ إعداد الوضع المظلم / الفاتح','1 سنة'],
          ] as [$type,$src,$purpose,$dur]): ?>
          <tr style="border-bottom:1px solid var(--border-c)">
            <td style="padding:10px 12px;color:var(--white);font-weight:600"><?= h($type) ?></td>
            <td style="padding:10px 12px"><?= h($src) ?></td>
            <td style="padding:10px 12px"><?= h($purpose) ?></td>
            <td style="padding:10px 12px;font-family:var(--f-mono);font-size:11px"><?= h($dur) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p>يمكنك إدارة ملفات تعريف الارتباط من إعدادات متصفحك. راجع أيضاً <a href="<?= h(url('cookie-policy')) ?>" style="color:var(--cyan)">سياسة ملفات تعريف الارتباط</a> للمزيد.</p>

    <!-- ── Section 5 — AdSense (critical for approval) ── -->
    <h2 style="color:var(--white);font-size:16px;font-weight:800;margin:24px 0 10px;padding-bottom:8px;border-bottom:1px solid var(--border-c)">٥. إعلانات Google AdSense</h2>
    <div style="background:rgba(6,182,212,.06);border:1px solid rgba(6,182,212,.18);border-radius:10px;padding:16px 18px;margin-bottom:14px">
      <p style="margin:0;font-size:13px">
        يستخدم موقع yassota شبكة <strong style="color:var(--white)">Google AdSense</strong> لعرض الإعلانات، وهي خدمة إعلانية تديرها شركة Google LLC (1600 Amphitheatre Parkway, Mountain View, CA 94043, USA).
      </p>
    </div>
    <p>فيما يتعلق بـ Google AdSense:</p>
    <ul style="margin:0 0 14px 0;padding-right:20px">
      <li>تستخدم Google ملفات تعريف الارتباط (مثل <code style="font-size:11px;background:rgba(0,0,0,.2);padding:1px 5px;border-radius:4px">DSID</code>، <code style="font-size:11px;background:rgba(0,0,0,.2);padding:1px 5px;border-radius:4px">IDE</code>) لعرض إعلانات مبنية على اهتماماتك واستخدامك السابق لمواقع الإنترنت.</li>
      <li>قد تظهر لك إعلانات مخصصة بناءً على تاريخ تصفحك للموقع والمواقع الأخرى (Remarketing / Interest-based Advertising).</li>
      <li>يُجمَع عنوان IP الخاص بك وبيانات التفاعل مع الإعلان وتُرسَل لـ Google لأغراض الفوترة وقياس الأداء.</li>
      <li>Google معتمدة في إطار Privacy Shield الأوروبي-الأمريكي، وتخضع لسياسة خصوصية Google.</li>
    </ul>
    <p><strong style="color:var(--white)">التحكم بإعلانات Google:</strong></p>
    <ul style="margin:0;padding-right:20px">
      <li>يمكنك إيقاف الإعلانات المخصصة من <a href="https://adssettings.google.com" target="_blank" rel="nofollow noopener" style="color:var(--cyan)">إعدادات إعلانات Google</a></li>
      <li>أو من خلال <a href="https://www.aboutads.info/choices/" target="_blank" rel="nofollow noopener" style="color:var(--cyan)">optout.aboutads.info</a></li>
      <li>يمكنك أيضاً تثبيت إضافة <a href="https://support.google.com/ads/answer/7395996" target="_blank" rel="nofollow noopener" style="color:var(--cyan)">Google Analytics Opt-out</a> على متصفحك</li>
    </ul>
    <p style="margin-top:12px">
      اطّلع على <a href="https://policies.google.com/privacy" target="_blank" rel="nofollow noopener" style="color:var(--cyan)">سياسة خصوصية Google</a> و<a href="https://policies.google.com/technologies/ads" target="_blank" rel="nofollow noopener" style="color:var(--cyan)">سياسة إعلانات Google</a> للمزيد.
    </p>

    <!-- ── Section 6 ── -->
    <h2 style="color:var(--white);font-size:16px;font-weight:800;margin:24px 0 10px;padding-bottom:8px;border-bottom:1px solid var(--border-c)">٦. مشاركة البيانات مع أطراف ثالثة</h2>
    <p>لا نبيع بياناتك الشخصية ولا نتداولها. نشارك البيانات فقط مع:</p>
    <ul style="margin:0;padding-right:20px">
      <li><strong style="color:var(--white)">Google AdSense / Google Analytics</strong> — لأغراض الإعلان والإحصاء المذكورة أعلاه.</li>
      <li><strong style="color:var(--white)">مزوّد الاستضافة</strong> — الذي يعالج بيانات الطلبات التقنية (عناوين IP، سجلات الخادم) لأغراض التشغيل.</li>
      <li><strong style="color:var(--white)">الجهات القانونية</strong> — إذا طلب ذلك قانون نافذ أو أمر قضائي.</li>
    </ul>
    <p style="margin-top:12px">روابط التحميل الخارجية (Google Play وغيرها) تخضع لسياسات خصوصية تلك الجهات المستقلة عنا تماماً.</p>

    <!-- ── Section 7 ── -->
    <h2 style="color:var(--white);font-size:16px;font-weight:800;margin:24px 0 10px;padding-bottom:8px;border-bottom:1px solid var(--border-c)">٧. حقوقك (GDPR وما يعادله)</h2>
    <p>إذا كنت مقيماً في المنطقة الاقتصادية الأوروبية أو تخضع لقوانين حماية البيانات المشابهة، تحق لك الحقوق التالية:</p>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:12px 0">
      <?php foreach ([
        ['حق الوصول','الاطلاع على ما نحتفظ به من بياناتك'],
        ['حق التصحيح','تصحيح أي بيانات غير دقيقة'],
        ['حق الحذف','طلب مسح بياناتك ("الحق في النسيان")'],
        ['حق التقييد','تقييد معالجة بياناتك في حالات معيّنة'],
        ['حق الاعتراض','الاعتراض على معالجة بياناتك للتسويق'],
        ['حق النقل','الحصول على بياناتك بصيغة قابلة للقراءة الآلية'],
      ] as [$right,$desc]): ?>
      <div style="background:var(--navy-600);border:1px solid var(--border-c);border-radius:8px;padding:12px 14px">
        <div style="font-weight:700;color:var(--white);font-size:12px;margin-bottom:4px"><?= h($right) ?></div>
        <p style="font-size:12px;margin:0"><?= h($desc) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <p>لممارسة أي من هذه الحقوق، راسلنا على: <a href="mailto:<?= h($contactEmail) ?>" style="color:var(--cyan)"><?= h($contactEmail) ?></a></p>

    <!-- ── Section 8 ── -->
    <h2 style="color:var(--white);font-size:16px;font-weight:800;margin:24px 0 10px;padding-bottom:8px;border-bottom:1px solid var(--border-c)">٨. خصوصية الأطفال</h2>
    <p>
      موقع yassota غير موجّه للأطفال دون سن 13 عاماً. لا نجمع بيانات شخصية من الأطفال عن قصد. إذا علمنا بجمع مثل هذه البيانات بدون موافقة ولي الأمر، سنحذفها فوراً.
      <br>إذا اعتقدت أن طفلك قدّم بيانات شخصية لنا، تواصل معنا عبر: <a href="mailto:<?= h($contactEmail) ?>" style="color:var(--cyan)"><?= h($contactEmail) ?></a>
    </p>

    <!-- ── Section 9 ── -->
    <h2 style="color:var(--white);font-size:16px;font-weight:800;margin:24px 0 10px;padding-bottom:8px;border-bottom:1px solid var(--border-c)">٩. مدة الاحتفاظ بالبيانات</h2>
    <ul style="margin:0;padding-right:20px">
      <li>بيانات نماذج التواصل: تُحتفظ بها لمدة لا تتجاوز 12 شهراً بعد انتهاء المراسلة.</li>
      <li>التعليقات المنشورة: تبقى حتى تطلب حذفها.</li>
      <li>بيانات السجلات التقنية (logs): تُحذف بعد 30 يوماً تلقائياً.</li>
      <li>بيانات ملفات تعريف الارتباط: تتبع مددها المذكورة في جدول القسم الرابع.</li>
    </ul>

    <!-- ── Section 10 ── -->
    <h2 style="color:var(--white);font-size:16px;font-weight:800;margin:24px 0 10px;padding-bottom:8px;border-bottom:1px solid var(--border-c)">١٠. أمان البيانات</h2>
    <p>
      نتخذ إجراءات تقنية وتنظيمية معقولة لحماية بياناتك: الاتصال عبر HTTPS مشفّر، تقييد الوصول للبيانات الحساسة، ومراجعة دورية لممارسات الأمان.
      مع ذلك، لا يمكن ضمان أمان مطلق لأي نقل بيانات عبر الإنترنت.
    </p>

    <!-- ── Section 11 ── -->
    <h2 style="color:var(--white);font-size:16px;font-weight:800;margin:24px 0 10px;padding-bottom:8px;border-bottom:1px solid var(--border-c)">١١. التعديلات على هذه السياسة</h2>
    <p>
      قد نُحدّث هذه السياسة بشكل دوري. عند التحديث، نغيّر تاريخ "آخر تحديث" أعلى الصفحة. في حال التعديلات الجوهرية، قد نُبلّغك عبر إشعار على الصفحة الرئيسية.
      استمرارك في استخدام الموقع بعد التحديث يُعدّ موافقةً على النسخة الجديدة.
    </p>

    <!-- ── Section 12 ── -->
    <h2 style="color:var(--white);font-size:16px;font-weight:800;margin:24px 0 10px;padding-bottom:8px;border-bottom:1px solid var(--border-c)">١٢. التواصل وتقديم الشكاوى</h2>
    <p>
      لأي استفسار، طلب، أو شكوى بخصوص خصوصيتك:
    </p>
    <ul style="margin:0;padding-right:20px">
      <li>البريد الإلكتروني: <a href="mailto:<?= h($contactEmail) ?>" style="color:var(--cyan)"><?= h($contactEmail) ?></a></li>
      <li>النموذج المباشر: <a href="<?= h(url('contact')) ?>" style="color:var(--cyan)">صفحة اتصل بنا</a></li>
    </ul>
    <p style="margin-top:12px">
      إذا كنت تقيم في الاتحاد الأوروبي ولم تُرضِك استجابتنا، يحق لك تقديم شكوى لهيئة حماية البيانات المحلية في بلدك.
    </p>

  </div>
</main>
</div>

<?php render_site_footer(); ?>
<script src="<?= h(asset_url('assets/js/main.js')) ?>"></script>
</body>
</html>
