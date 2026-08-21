<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$contactEmail = get_cfg($pdo, 'contact_email') ?: 'contact@' . parse_url(SITE_URL, PHP_URL_HOST);
$siteHost     = parse_url(SITE_URL, PHP_URL_HOST) ?: 'yassota.com';
$seoTitle     = 'الإفصاح الإعلاني وإخلاء المسؤولية — yassota';
$metaDesc     = 'إفصاح كامل عن طبيعة الإعلانات في موقع yassota وإخلاء المسؤولية عن التطبيقات المنشورة. تعرّف على أنظمة الحماية والأمان المتقدمة التي نستخدمها لضمان سلامة المستخدمين وبياناتهم.';
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
  <link rel="canonical" href="<?= h(url('disclosure')) ?>">
  <meta property="og:title" content="<?= h($seoTitle) ?>">
  <meta property="og:description" content="<?= h($metaDesc) ?>">
  <meta property="og:type" content="website">
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/main.css')) ?>">
  <script type="application/ld+json"><?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => $seoTitle,
    'description' => $metaDesc,
    'publisher' => ['@type'=>'Organization','name'=>'yassota','url'=>SITE_URL],
    'breadcrumb' => ['@type'=>'BreadcrumbList','itemListElement'=>[
      ['@type'=>'ListItem','position'=>1,'name'=>'الرئيسية','item'=>SITE_URL.'/'],
      ['@type'=>'ListItem','position'=>2,'name'=>'الإفصاح وإخلاء المسؤولية'],
    ]],
  ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?></script>
</head>
<body>

<?php render_site_header(); ?>

<div class="page-wrap fw">
<main class="main-content">

  <nav style="font-size:12px;color:var(--muted);margin-bottom:16px;display:flex;gap:6px;align-items:center;flex-wrap:wrap">
    <a href="<?= h(url('')) ?>" style="color:var(--cyan)">الرئيسية</a><span>/</span><span>الإفصاح وإخلاء المسؤولية</span>
  </nav>

  <div class="section-head reveal"><span class="section-title">الإفصاح الإعلاني وإخلاء المسؤولية</span></div>

  <!-- ═══════════════════════════════════════════════════════════════
       PART 1: إخلاء المسؤولية عن التطبيقات وأنظمة الحماية
       ═══════════════════════════════════════════════════════════════ -->

  <div class="section-box reveal" style="color:var(--muted);font-size:14px;line-height:1.9">

    <div style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);border-radius:12px;padding:18px 22px;margin-bottom:24px">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        <strong style="color:var(--white);font-size:15px">إخلاء المسؤولية — يُرجى القراءة بعناية</strong>
      </div>
      <p style="margin:0;font-size:13px">
        فريق yassota <strong style="color:var(--white)">ليس مسؤولاً بشكل مباشر عن التطبيقات المنشورة</strong> على المنصة من حيث المحتوى الداخلي أو الوظائف أو السياسات الخاصة بكل تطبيق.
        لكننا <strong style="color:var(--white)">مسؤولون بالكامل عن أمان وسلامة</strong> جميع التطبيقات المستضافة عبر سيرفراتنا الخاصة، ونضمن خلوّها من البرمجيات الخبيثة والتهديدات الأمنية.
        <strong style="color:var(--white)">أمنك هو مسؤوليتنا.</strong>
      </p>
    </div>

    <h2 style="color:var(--white);font-size:18px;font-weight:800;margin:0 0 16px;padding-bottom:10px;border-bottom:2px solid var(--border-c)">إخلاء المسؤولية عن التطبيقات المنشورة</h2>

    <h3 style="color:var(--white);font-size:15px;font-weight:700;margin:20px 0 8px">نطاق مسؤولية yassota</h3>
    <p>
      يقوم موقع yassota بتوفير منصة لعرض ومراجعة تطبيقات الأندرويد وتسهيل وصول المستخدمين إليها. التطبيقات المعروضة على المنصة تم تطويرها
      بواسطة مطوّرين مستقلين أو شركات تقنية خارجية، ويتحمل كل مطوّر المسؤولية الكاملة عن:
    </p>
    <ul style="padding-right:20px;margin:10px 0 18px">
      <li>المحتوى الداخلي للتطبيق وجميع الوظائف والميزات التي يقدمها</li>
      <li>سياسة الخصوصية الخاصة بالتطبيق وآلية جمع بيانات المستخدمين</li>
      <li>الإعلانات الداخلية ضمن التطبيق وعمليات الشراء داخله</li>
      <li>التحديثات والتعديلات اللاحقة على التطبيق بعد النشر</li>
      <li>الامتثال للقوانين المحلية والدولية المتعلقة بالبرمجيات</li>
      <li>حقوق الملكية الفكرية والعلامات التجارية المستخدمة</li>
    </ul>

    <h3 style="color:var(--white);font-size:15px;font-weight:700;margin:20px 0 8px">ما يقع ضمن مسؤوليتنا</h3>
    <p>
      بينما لا نتحمل مسؤولية محتوى التطبيقات ذاتها، فإن فريق yassota يتحمل المسؤولية الكاملة عن:
    </p>
    <ul style="padding-right:20px;margin:10px 0 18px">
      <li><strong style="color:var(--white)">أمان ملفات التحميل:</strong> كل ملف APK مستضاف على سيرفراتنا يخضع لفحص أمني شامل قبل النشر</li>
      <li><strong style="color:var(--white)">سلامة عملية التحميل:</strong> ضمان وصول المستخدم للملف الأصلي دون تعديل أو حقن أكواد خبيثة</li>
      <li><strong style="color:var(--white)">حماية بيانات المستخدمين:</strong> جميع البيانات المتعلقة بتفاعل المستخدمين مع المنصة محمية بتشفير عالي المستوى</li>
      <li><strong style="color:var(--white)">دقة المعلومات المعروضة:</strong> نسعى لتقديم معلومات دقيقة ومحدثة عن كل تطبيق</li>
      <li><strong style="color:var(--white)">استمرارية الخدمة:</strong> ضمان توفر المنصة والملفات المستضافة على مدار الساعة</li>
    </ul>

    <h2 style="color:var(--white);font-size:18px;font-weight:800;margin:32px 0 16px;padding-bottom:10px;border-bottom:2px solid var(--border-c)">أنظمة الحماية والأمان في yassota</h2>
    <p>
      يعتمد موقع yassota على بنية أمنية متعددة الطبقات صُمّمت لحماية المستخدمين وبياناتهم وضمان سلامة جميع الملفات المستضافة.
      فيما يلي شرح تفصيلي لكل نظام حماية نستخدمه:
    </p>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin:20px 0 28px">

      <div style="background:rgba(6,182,212,.06);border:1px solid rgba(6,182,212,.18);border-radius:14px;padding:20px">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
          <div style="width:38px;height:38px;border-radius:10px;background:rgba(6,182,212,.12);display:flex;align-items:center;justify-content:center">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#06b6d4" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          </div>
          <strong style="color:var(--white);font-size:14px">Evil Guard WAF</strong>
        </div>
        <p style="font-size:13px;margin:0">
          جدار حماية تطبيقات الويب (Web Application Firewall) المطوّر خصيصاً لمنصة yassota.
          يعمل على تحليل كل طلب HTTP/HTTPS وارد في الوقت الفعلي، ويكشف ويحظر محاولات الاختراق
          مثل حقن SQL، هجمات XSS، محاولات تجاوز الصلاحيات، وهجمات CSRF قبل وصولها لخوادمنا.
          يتم تحديث قواعد الحماية بشكل دوري لمواجهة أحدث أساليب الهجوم.
        </p>
      </div>

      <div style="background:rgba(168,85,247,.06);border:1px solid rgba(168,85,247,.18);border-radius:14px;padding:20px">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
          <div style="width:38px;height:38px;border-radius:10px;background:rgba(168,85,247,.12);display:flex;align-items:center;justify-content:center">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#a855f7" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </div>
          <strong style="color:var(--white);font-size:14px">VirusTotal Multi-Engine Scanning</strong>
        </div>
        <p style="font-size:13px;margin:0">
          كل ملف APK يُرفع على المنصة يُرسل تلقائياً إلى خدمة VirusTotal التي تفحصه عبر أكثر من 70 محرك مضاد فيروسات
          عالمي في وقت واحد، بما فيها Kaspersky وBitdefender وMalwarebytes وESET وAvast.
          لا يُنشر أي ملف حتى يحصل على نتيجة فحص نظيفة. يتم تخزين نتيجة الفحص ورابط التقرير الكامل
          وإتاحته للمستخدم في صفحة التحميل كشارة أمان شفافة.
        </p>
      </div>

      <div style="background:rgba(34,197,94,.06);border:1px solid rgba(34,197,94,.18);border-radius:14px;padding:20px">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
          <div style="width:38px;height:38px;border-radius:10px;background:rgba(34,197,94,.12);display:flex;align-items:center;justify-content:center">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
          </div>
          <strong style="color:var(--white);font-size:14px">SSL/TLS End-to-End Encryption</strong>
        </div>
        <p style="font-size:13px;margin:0">
          جميع الاتصالات بين المستخدم والسيرفر مشفرة بتقنية TLS 1.3 (أحدث بروتوكول تشفير متاح)
          مع شهادة SSL صادرة من جهة موثوقة ومجددة تلقائياً. هذا يعني أن أي بيانات يرسلها المستخدم
          — سواء تعليقات أو تقييمات أو عمليات بحث — لا يمكن اعتراضها أو قراءتها من قبل أي طرف ثالث.
          نحصل على تصنيف A+ في اختبارات SSL Labs.
        </p>
      </div>

      <div style="background:rgba(249,115,22,.06);border:1px solid rgba(249,115,22,.18);border-radius:14px;padding:20px">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
          <div style="width:38px;height:38px;border-radius:10px;background:rgba(249,115,22,.12);display:flex;align-items:center;justify-content:center">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
          </div>
          <strong style="color:var(--white);font-size:14px">VPN & Proxy Detection</strong>
        </div>
        <p style="font-size:13px;margin:0">
          نظام كشف متقدم يعمل على تحديد الاتصالات القادمة عبر شبكات VPN أو خوادم Proxy أو شبكات Tor
          والتي قد تُستخدم لإخفاء هوية المهاجمين. يساعد هذا النظام في منع محاولات التلاعب بالتقييمات،
          إنشاء حسابات وهمية، أو تنفيذ هجمات مؤتمتة على المنصة. يعمل بالتكامل مع قواعد بيانات
          عناوين IP المشبوهة المحدثة يومياً.
        </p>
      </div>

      <div style="background:rgba(236,72,153,.06);border:1px solid rgba(236,72,153,.18);border-radius:14px;padding:20px">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
          <div style="width:38px;height:38px;border-radius:10px;background:rgba(236,72,153,.12);display:flex;align-items:center;justify-content:center">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ec4899" stroke-width="2"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6m3-3h-6"/></svg>
          </div>
          <strong style="color:var(--white);font-size:14px">IP Allowlist & Admin Hardening</strong>
        </div>
        <p style="font-size:13px;margin:0">
          لوحة الإدارة محمية بنظام قائمة IP المسموحة (IP Allowlist) الذي يمنع الوصول إليها
          من أي عنوان IP غير مصرّح به مسبقاً. بالإضافة إلى ذلك، يتم تطبيق سياسة كلمات مرور قوية
          مع تشفير bcrypt بعامل تكلفة عالي، وتسجيل كل عملية دخول ومحاولة دخول فاشلة مع عنوان IP
          والمتصفح المستخدم. يتم إغلاق الحساب تلقائياً بعد عدد محدد من المحاولات الفاشلة.
        </p>
      </div>

      <div style="background:rgba(59,130,246,.06);border:1px solid rgba(59,130,246,.18);border-radius:14px;padding:20px">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
          <div style="width:38px;height:38px;border-radius:10px;background:rgba(59,130,246,.12);display:flex;align-items:center;justify-content:center">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"><path d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.66 0 3-4.03 3-9s-1.34-9-3-9m0 18c-1.66 0-3-4.03-3-9s1.34-9 3-9"/></svg>
          </div>
          <strong style="color:var(--white);font-size:14px">Cloudflare Turnstile CAPTCHA</strong>
        </div>
        <p style="font-size:13px;margin:0">
          نستخدم نظام التحقق البشري Cloudflare Turnstile (الجيل الجديد من CAPTCHA) لحماية عمليات التحميل
          من الروبوتات والبرامج المؤتمتة. على عكس reCAPTCHA التقليدي، يعمل Turnstile بشكل غير مرئي تقريباً
          دون إزعاج المستخدم بألغاز بصرية، ويعتمد على تحليل سلوكي ذكي للتمييز بين البشر والروبوتات.
          هذا يحمي من تحميلات الروبوتات الضارة واستنزاف موارد السيرفر.
        </p>
      </div>

    </div>

    <h3 style="color:var(--white);font-size:15px;font-weight:700;margin:24px 0 10px">أنظمة حماية إضافية</h3>

    <div style="margin-bottom:24px">
      <div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:14px">
        <div style="min-width:6px;height:6px;border-radius:50%;background:#06b6d4;margin-top:8px"></div>
        <div>
          <strong style="color:var(--white);font-size:13px">Rate Limiting (تحديد معدل الطلبات):</strong>
          <span style="font-size:13px"> نظام ذكي يحدد عدد الطلبات المسموحة لكل مستخدم في فترة زمنية محددة. يمنع هجمات DDoS البسيطة،
          محاولات التخمين العنيف لكلمات المرور (Brute Force)، والإفراط في استخدام واجهات API. يتكيف ديناميكياً مع حجم الحركة الطبيعية.</span>
        </div>
      </div>
      <div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:14px">
        <div style="min-width:6px;height:6px;border-radius:50%;background:#a855f7;margin-top:8px"></div>
        <div>
          <strong style="color:var(--white);font-size:13px">Content Security Policy (CSP):</strong>
          <span style="font-size:13px"> رؤوس أمان HTTP صارمة تمنع حقن أكواد JavaScript خارجية أو تحميل موارد من نطاقات غير مصرّح بها.
          هذا يحمي المستخدمين من هجمات Cross-Site Scripting (XSS) ويضمن أن المتصفح لا يُنفذ إلا الأكواد الموثوقة.</span>
        </div>
      </div>
      <div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:14px">
        <div style="min-width:6px;height:6px;border-radius:50%;background:#22c55e;margin-top:8px"></div>
        <div>
          <strong style="color:var(--white);font-size:13px">File Integrity Monitoring:</strong>
          <span style="font-size:13px"> نظام مراقبة سلامة الملفات يتحقق من أن ملفات APK المستضافة لم يتم تعديلها بعد الرفع. يُحسب hash SHA-256
          لكل ملف عند الرفع ويُقارن دورياً للتأكد من عدم التلاعب. أي تغيير غير مصرّح به يُطلق تنبيهاً فورياً.</span>
        </div>
      </div>
      <div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:14px">
        <div style="min-width:6px;height:6px;border-radius:50%;background:#f97316;margin-top:8px"></div>
        <div>
          <strong style="color:var(--white);font-size:13px">Database Encryption & Backup:</strong>
          <span style="font-size:13px"> قاعدة البيانات محمية بتشفير AES-256 في حالة السكون (at-rest) مع نسخ احتياطية مشفرة يومية تُخزن في مواقع جغرافية منفصلة.
          هذا يضمن استمرارية البيانات وحمايتها حتى في حالة الكوارث.</span>
        </div>
      </div>
      <div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:14px">
        <div style="min-width:6px;height:6px;border-radius:50%;background:#ec4899;margin-top:8px"></div>
        <div>
          <strong style="color:var(--white);font-size:13px">Honeypot Anti-Spam:</strong>
          <span style="font-size:13px"> حقول مخفية في نماذج التعليقات والتقييمات تكشف عن الروبوتات التي تملأها تلقائياً. هذه التقنية تعمل بشفافية تامة
          دون أي تأثير على تجربة المستخدم الحقيقي، وتمنع التعليقات المزعجة (Spam) من الظهور على المنصة.</span>
        </div>
      </div>
      <div style="display:flex;gap:10px;align-items:flex-start">
        <div style="min-width:6px;height:6px;border-radius:50%;background:#3b82f6;margin-top:8px"></div>
        <div>
          <strong style="color:var(--white);font-size:13px">Access Logging & Audit Trail:</strong>
          <span style="font-size:13px"> تسجيل شامل لجميع عمليات الوصول إلى لوحة الإدارة مع عنوان IP، المتصفح، الوقت، والإجراء المتخذ.
          يتم الاحتفاظ بسجلات التدقيق لمدة 90 يوماً ومراجعتها دورياً لكشف أي نشاط غير اعتيادي.</span>
        </div>
      </div>
    </div>

    <h3 style="color:var(--white);font-size:15px;font-weight:700;margin:24px 0 10px">التزامنا تجاه المستخدمين</h3>
    <p>
      نلتزم في yassota بأعلى معايير الأمان والشفافية. نحن نؤمن بأن ثقة المستخدم هي أساس عملنا،
      ولهذا نستثمر بشكل مستمر في تطوير وتحديث أنظمة الحماية لمواجهة التهديدات المتطورة.
      إذا اكتشفت أي ثغرة أمنية أو سلوك مشبوه على المنصة، نرجو إبلاغنا فوراً عبر البريد الإلكتروني
      <a href="mailto:<?= h($contactEmail) ?>" style="color:var(--cyan)"><?= h($contactEmail) ?></a>
      وسنتعامل مع البلاغ بأقصى سرعة وسرية.
    </p>
  </div>

  <!-- ═══════════════════════════════════════════════════════════════
       PART 2: الإفصاح الإعلاني
       ═══════════════════════════════════════════════════════════════ -->

  <div class="section-box reveal" style="color:var(--muted);font-size:14px;line-height:1.9;margin-top:24px">

    <h2 style="color:var(--white);font-size:18px;font-weight:800;margin:0 0 16px;padding-bottom:10px;border-bottom:2px solid var(--border-c)">الإفصاح الإعلاني</h2>

    <div style="background:rgba(249,115,22,.08);border:1px solid rgba(249,115,22,.25);border-radius:12px;padding:18px 22px;margin-bottom:24px">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
        <strong style="color:var(--white);font-size:15px">إشعار مهم للقرّاء</strong>
      </div>
      <p style="margin:0;font-size:13px">
        يحتوي موقع yassota على <strong style="color:var(--white)">إعلانات مدفوعة</strong> من Google AdSense.
        هذه الإعلانات تظهر بوضوح وتُميَّز عن المحتوى التحريري. نلتزم بالإفصاح الكامل والشفافية وفق متطلبات
        لجنة التجارة الفيدرالية الأمريكية (FTC) ومعايير الإعلانات الرقمية الدولية.
      </p>
    </div>

    <h3 style="color:var(--white);font-size:15px;font-weight:700;margin:0 0 10px">١. الإعلانات عبر Google AdSense</h3>
    <p>
      يستخدم yassota شبكة <strong style="color:var(--white)">Google AdSense</strong> (معرّف الناشر: ca-pub-5506877998492189)
      لعرض الإعلانات. هذه الإعلانات:
    </p>
    <ul style="padding-right:20px;margin:10px 0 18px">
      <li>قد تكون <strong style="color:var(--white)">مخصصة</strong> بناءً على تاريخ تصفحك واهتماماتك (إعلانات قائمة على الاهتمامات).</li>
      <li>يختارها خوارزمية Google تلقائياً — نحن لا نتدخل في اختيار محتوى الإعلان المحدد.</li>
      <li>تُدار من لوحة Google AdSense الخاصة بنا؛ لا نتحكم في المعلنين الفرديين.</li>
      <li>تعمل Google على جمع بيانات معينة لأغراض عرض الإعلانات (راجع <a href="https://policies.google.com/privacy" target="_blank" rel="nofollow noopener" style="color:var(--cyan)">سياسة خصوصية Google</a>).</li>
    </ul>
    <p>
      <strong style="color:var(--white)">يمكنك إيقاف الإعلانات المخصصة:</strong>
      من خلال <a href="https://adssettings.google.com" target="_blank" rel="nofollow noopener" style="color:var(--cyan)">إعدادات إعلانات Google</a>
      أو عبر <a href="https://www.aboutads.info/choices/" target="_blank" rel="nofollow noopener" style="color:var(--cyan)">aboutads.info</a>.
    </p>

    <h3 style="color:var(--white);font-size:15px;font-weight:700;margin:24px 0 10px">٢. الروابط الخارجية وGoogle Play</h3>
    <p>
      روابط التحميل الموجودة على الموقع تُوجَّه في الغالب نحو <strong style="color:var(--white)">Google Play</strong>
      أو المواقع الرسمية للمطوّرين. yassota <strong style="color:var(--white)">لا يشارك في برامج التابعين لأي شركة</strong>
      ولا يحصل على عمولة أو مكافأة مادية من روابط التحميل هذه — هدفها الوحيد إيصالك للمصدر الرسمي للتطبيق.
    </p>

    <h3 style="color:var(--white);font-size:15px;font-weight:700;margin:24px 0 10px">٣. استقلالية المحتوى التحريري</h3>
    <p>
      <strong style="color:var(--white)">الإعلانات لا تؤثر بأي شكل على محتوانا التحريري.</strong>
      اختيار التطبيقات لمراجعتها، والتقييمات المعطاة، والآراء الواردة في المقالات — جميعها تصدر بناءً على
      معايير تحريرية مستقلة دون أي تأثير من المعلنين. المعلن لا يحصل على معاملة تفضيلية في محتوانا مقابل إعلانه.
    </p>

    <h3 style="color:var(--white);font-size:15px;font-weight:700;margin:24px 0 10px">٤. كيف نُميّز الإعلانات عن المحتوى</h3>
    <p>الإعلانات في موقع yassota مُميّزة بوضوح بالطرق التالية:</p>
    <ul style="padding-right:20px;margin:10px 0">
      <li>شارة "إعلان" تظهر فوق وحدات الإعلانات أو حولها.</li>
      <li>تصميم مرئي مختلف عن المحتوى التحريري.</li>
      <li>وضع الإعلانات في مناطق مخصصة لا تتداخل مع المحتوى الرئيسي.</li>
    </ul>
    <p>إذا كان لديك أي شك حول ما إذا كان قسم ما إعلاناً أم محتوىً تحريرياً، تواصل معنا للاستيضاح.</p>

    <h3 style="color:var(--white);font-size:15px;font-weight:700;margin:24px 0 10px">٥. تمويل الموقع</h3>
    <p>
      عائدات الإعلانات هي المصدر الرئيسي لتمويل yassota، وتُتيح لنا تقديم المحتوى مجاناً للقرّاء.
      هذه العائدات تُستخدم لتغطية تكاليف الاستضافة، التطوير، وإنتاج المحتوى.
      نؤمن بأن الشفافية حول تمويل المحتوى جزء أساسي من الثقة مع قرّائنا.
    </p>

    <h3 style="color:var(--white);font-size:15px;font-weight:700;margin:24px 0 10px">٦. تواصل معنا</h3>
    <p>
      لأي استفسار حول الإعلانات أو المحتوى الممول أو سياسة الإفصاح لدينا:
    </p>
    <ul style="padding-right:20px;margin:10px 0">
      <li>البريد الإلكتروني: <a href="mailto:<?= h($contactEmail) ?>" style="color:var(--cyan)"><?= h($contactEmail) ?></a></li>
      <li>نموذج التواصل: <a href="<?= h(url('contact')) ?>" style="color:var(--cyan)">صفحة اتصل بنا</a></li>
    </ul>
  </div>

  <div style="background:rgba(6,182,212,.06);border:1px solid rgba(6,182,212,.18);border-radius:12px;padding:16px 20px;margin-top:20px" class="reveal">
    <div style="font-size:12px;color:var(--muted);line-height:1.8">
      راجع أيضاً:
      <a href="<?= h(url('about')) ?>" style="color:var(--cyan);margin:0 8px">من نحن</a> |
      <a href="<?= h(url('privacy-policy')) ?>" style="color:var(--cyan);margin:0 8px">سياسة الخصوصية</a> |
      <a href="<?= h(url('terms')) ?>" style="color:var(--cyan);margin:0 8px">شروط الاستخدام</a> |
      <a href="<?= h(url('cookie-policy')) ?>" style="color:var(--cyan);margin:0 8px">سياسة الكوكيز</a>
    </div>
  </div>

</main>
</div>

<?php render_site_footer(); ?>
<script src="<?= h(asset_url('assets/js/main.js')) ?>"></script>
</body>
</html>
