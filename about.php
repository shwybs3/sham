<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$totalApps  = (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published'")->fetchColumn();
$totalCats  = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalBlog  = (int)$pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status='published'")->fetchColumn();
$totalTools = (int)$pdo->query("SELECT COUNT(*) FROM web_tools WHERE status='published'")->fetchColumn();
$contactEmail = get_cfg($pdo, 'contact_email') ?: 'contact@' . (parse_url(SITE_URL, PHP_URL_HOST) ?: 'yassota.com');

$seoTitle = 'من نحن — فريق yassota | 9 سنوات من الخبرة في التقنية والتطوير';
$metaDesc = 'تعرّف على فريق yassota بقيادة المؤسس Saed Hmede: 9 سنوات من الخبرة في تطوير المواقع والتطبيقات، العمل مع آلاف الشركات والمواقع العالمية، وبناء أنظمة حماية متقدمة لضمان أمان المستخدمين.';
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
  <link rel="canonical" href="<?= h(url('about')) ?>">
  <meta property="og:title" content="<?= h($seoTitle) ?>">
  <meta property="og:description" content="<?= h($metaDesc) ?>">
  <meta property="og:type" content="website">
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/main.css')) ?>">
  <script type="application/ld+json"><?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'AboutPage',
    'name' => 'من نحن — yassota',
    'description' => $metaDesc,
    'url' => url('about'),
    'mainEntity' => [
      '@type' => 'Organization',
      'name' => 'yassota',
      'url' => SITE_URL,
      'foundingDate' => '2017',
      'founder' => [
        '@type' => 'Person',
        'name' => 'Saed Hmede',
        'jobTitle' => 'المؤسس والمدير التنفيذي',
      ],
    ],
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
</head>
<body>

<?php render_site_header(); ?>

<div class="page-wrap fw">

<main class="main-content">

  <nav style="font-size:12px;color:var(--muted);margin-bottom:16px;display:flex;gap:6px;align-items:center;flex-wrap:wrap">
    <a href="<?= h(url('')) ?>" style="color:var(--cyan)">الرئيسية</a><span>/</span><span>من نحن</span>
  </nav>

  <div class="section-head reveal"><span class="section-title">من نحن</span></div>

  <!-- Mission -->
  <div class="section-box reveal" style="background:linear-gradient(135deg,rgba(6,182,212,.07),rgba(124,58,237,.05));border-color:rgba(6,182,212,.2);margin-bottom:16px">
    <div style="font-size:11px;font-weight:700;letter-spacing:2px;color:var(--cyan);text-transform:uppercase;margin-bottom:12px">رسالتنا</div>
    <h1 style="font-family:var(--f-head);font-size:22px;font-weight:900;color:var(--white);line-height:1.4;margin-bottom:12px">
      +9 سنوات من الخبرة في بناء المنصات الرقمية والأنظمة الذكية
    </h1>
    <p style="color:var(--muted);font-size:14px;line-height:1.85;margin:0">
      yassota ليست مجرد دليل تطبيقات — هي منصة تقنية متكاملة أسّسها المطوّر <strong style="color:var(--white)">Saed Hmede</strong> عام 2017 بعد سنوات من العمل مع شركات تقنية معروفة وبناء مئات المواقع والأنظمة الرقمية. نجمع بين خبرتنا العميقة في التطوير والأمن السيبراني والذكاء الاصطناعي لنقدّم للمستخدم العربي منصة موثوقة وآمنة وشاملة.
    </p>
  </div>

  <!-- Stats -->
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px" class="reveal">
    <?php foreach ([[$totalApps,'تطبيق مراجَع','smartphone'],[$totalTools,'أداة ذكية','tool'],[$totalBlog,'مقالة','pen'],['9+','سنة خبرة','star']] as [$n,$l,$ic]): ?>
    <div style="background:var(--navy-700);border:1px solid var(--border-c);border-radius:var(--radius-lg);padding:20px;text-align:center">
      <div style="color:var(--cyan);margin-bottom:8px;display:flex;justify-content:center"><?= partial_icon($ic, 24) ?></div>
      <div style="font-family:var(--f-mono);font-size:28px;font-weight:700;color:var(--cyan)"><?= is_int($n) ? number_format($n) : $n ?></div>
      <div style="font-size:13px;color:var(--muted);margin-top:4px"><?= $l ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="section-box reveal" style="color:var(--muted);font-size:14px;line-height:1.9">

    <!-- Founder -->
    <h2 style="color:var(--white);font-size:18px;font-weight:800;margin:0 0 14px">المؤسس: Saed Hmede</h2>
    <div style="background:var(--navy-600);border:1px solid var(--border-c);border-radius:14px;padding:20px;margin-bottom:20px">
      <div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap">
        <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--cyan),var(--purple));display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:900;color:#fff;flex-shrink:0">SH</div>
        <div style="flex:1;min-width:200px">
          <div style="font-size:17px;font-weight:800;color:var(--white);margin-bottom:4px">Saed Hmede</div>
          <div style="font-size:12px;color:var(--cyan);margin-bottom:10px">المؤسس والمدير التنفيذي — +10 سنوات خبرة</div>
          <p style="margin:0;font-size:13px;line-height:1.8">
            مطوّر ويب ومهندس أنظمة بخبرة تتجاوز 10 سنوات في صناعة البرمجيات. بدأ مسيرته المهنية في سن مبكر وعمل مع آلاف المواقع والشركات حول العالم في مجالات تطوير المنصات الرقمية، أنظمة إدارة المحتوى، الذكاء الاصطناعي، والأمن السيبراني. ساهم في بناء وتطوير العديد من المواقع الأشهر حالياً في المنطقة العربية، وقاد فرق تطوير لمشاريع ضخمة تخدم ملايين المستخدمين.
          </p>
        </div>
      </div>
    </div>

    <h2 style="color:var(--white);font-size:17px;font-weight:800;margin:0 0 10px">قصة yassota — كيف بدأنا؟</h2>
    <p>
      بدأت الفكرة عام 2017 عندما لاحظ مؤسسنا Saed Hmede أن المستخدم العربي يفتقر إلى منصة موثوقة وشاملة لاكتشاف التطبيقات ومراجعتها. معظم المواقع العربية كانت إما ترجمات آلية رديئة لمحتوى أجنبي، أو مواقع مليئة بالإعلانات المضللة والروابط المشبوهة. كان الهدف واضحاً: بناء منصة عربية أصيلة ترقى لمستوى ثقة المستخدم.
    </p>
    <p>
      خلال 9 سنوات من العمل المتواصل، تطوّرت yassota من مدوّنة بسيطة إلى منصة تقنية متكاملة تضم دليل تطبيقات شامل، ومجموعة أدوات ويب ذكية مدعومة بالذكاء الاصطناعي، ونظام أمان متقدم يحمي المستخدمين من التطبيقات الضارة، ومساعدة ذكاء اصطناعي عربية (ياسمين) تخدم آلاف المستخدمين يومياً.
    </p>

    <h2 style="color:var(--white);font-size:17px;font-weight:800;margin:22px 0 10px">خبراتنا وشراكاتنا</h2>
    <p>
      على مدار السنوات الماضية، عمل فريق yassota مع شركات ومؤسسات تقنية متنوعة في مجالات:
    </p>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:14px 0">
      <?php foreach ([
        'تطوير منصات إدارة المحتوى (CMS)',
        'بناء أنظمة أمان وحماية متقدمة',
        'تطوير واجهات برمجة تطبيقات (APIs)',
        'حلول الذكاء الاصطناعي والتعلّم الآلي',
        'تحسين محركات البحث (SEO) للمواقع الكبرى',
        'بناء أنظمة التجارة الإلكترونية',
        'تطوير تطبيقات الويب التفاعلية (SPA)',
        'استشارات أمن المعلومات والخصوصية',
      ] as $exp): ?>
      <div style="padding:8px 12px;background:rgba(6,182,212,.06);border:1px solid rgba(6,182,212,.15);border-radius:8px;font-size:12px;display:flex;align-items:center;gap:6px">
        <span style="color:var(--cyan)">✓</span> <?= $exp ?>
      </div>
      <?php endforeach; ?>
    </div>

    <h2 style="color:var(--white);font-size:17px;font-weight:800;margin:22px 0 10px">أنظمة الحماية والأمان في yassota</h2>
    <p>
      أمان المستخدم هو أولويتنا القصوى. طوّرنا مجموعة من الأنظمة والبروتوكولات المتقدمة لضمان حماية بياناتك وسلامة التطبيقات المنشورة على منصتنا:
    </p>

    <div style="display:grid;gap:10px;margin:14px 0">
      <?php foreach ([
        ['🛡️', 'نظام Evil Guard للحماية التلقائية', 'نظام حماية ذكي مطوّر داخلياً يراقب حركة الزوار في الوقت الفعلي ويكشف الأنماط المشبوهة تلقائياً. يتعرّف على هجمات القوة العمياء (Brute Force)، محاولات حقن SQL، هجمات XSS، ومحاولات الوصول غير المشروع. يقوم بحظر عناوين IP المشبوهة تلقائياً مع نظام حظر تصاعدي ذكي.'],
        ['🔍', 'فحص التطبيقات قبل النشر', 'كل تطبيق يُنشر على المنصة يمر بعملية فحص شاملة تشمل: التحقق من مصدر التطبيق (Google Play أو موقع المطوّر الرسمي)، فحص الملف عبر VirusTotal بأكثر من 70 محرك مضاد فيروسات، التحقق من توقيع APK الرقمي، ومقارنة الـ hash مع النسخ الرسمية.'],
        ['🔐', 'تشفير البيانات والاتصالات', 'نستخدم تشفير TLS 1.3 لكل الاتصالات بين المتصفح والسيرفر. بيانات المستخدمين مشفّرة في الانتقال وفي التخزين. لا نجمع بيانات شخصية حساسة ولا نشاركها مع أطراف ثالثة. نلتزم بمعايير GDPR الأوروبية لحماية البيانات.'],
        ['🤖', 'جدار حماية التطبيقات (WAF)', 'جدار حماية متقدم على مستوى التطبيق يفحص كل طلب HTTP ويحظر الطلبات المشبوهة. يحمي من هجمات OWASP Top 10 بما فيها حقن SQL، تزوير الطلبات (CSRF)، تضمين ملفات عن بعد (RFI)، وهجمات تجاوز المسار (Path Traversal).'],
        ['📡', 'كشف VPN/Proxy/Tor', 'نظام متقدم لكشف استخدام VPN والبروكسي وشبكة Tor لمنع إساءة الاستخدام وهجمات الـ DDoS. يستخدم قواعد بيانات متعددة لتصنيف عناوين IP مع التخزين المؤقت الذكي لتقليل زمن الاستجابة.'],
        ['📊', 'مراقبة الأمان في الوقت الفعلي', 'لوحة تحكم أمنية متقدمة تعرض: الزيارات المشبوهة، محاولات الاختراق المحبطة، عناوين IP المحظورة، وتنبيهات أمنية فورية. كل حدث أمني يُسجَّل بالتفصيل مع الطابع الزمني وعنوان IP والتفاصيل الكاملة للمراجعة اللاحقة.'],
      ] as [$icon, $title, $desc]): ?>
      <div style="background:var(--navy-600);border:1px solid var(--border-c);border-radius:12px;padding:16px 18px">
        <div style="font-weight:700;color:var(--white);font-size:14px;margin-bottom:6px;display:flex;align-items:center;gap:8px"><?= $icon ?> <?= $title ?></div>
        <p style="margin:0;font-size:12px;line-height:1.8"><?= $desc ?></p>
      </div>
      <?php endforeach; ?>
    </div>

    <h2 style="color:var(--white);font-size:17px;font-weight:800;margin:22px 0 10px">إخلاء المسؤولية</h2>
    <p>
      فريق yassota مسؤول بشكل كامل عن أمان وسلامة جميع التطبيقات المستضافة عبر سيرفراتنا الخاصة. نضمن أن كل ملف APK مُستضاف على منصتنا قد خضع لفحص أمني شامل ومتعدد المستويات. ومع ذلك، نوضّح النقاط التالية:
    </p>
    <ul style="margin:8px 0;padding-right:18px">
      <li style="margin-bottom:6px">التطبيقات التي تُحمَّل عبر روابط Google Play مسؤولية Google وفق شروط استخدامها</li>
      <li style="margin-bottom:6px">نحن لسنا المطوّرين الأصليين للتطبيقات — نحن منصة مراجعة وتوزيع مرخّصة</li>
      <li style="margin-bottom:6px">أمانك هو مسؤوليتنا: نفحص كل ملف قبل النشر ونحذف فوراً أي تطبيق يُكتشف فيه سلوك ضار</li>
      <li style="margin-bottom:6px">نشجّع المستخدمين على الإبلاغ عن أي تطبيق مشبوه عبر <a href="<?= h(url('contact')) ?>" style="color:var(--cyan)">نموذج التواصل</a> أو <a href="<?= h(url('dmca')) ?>" style="color:var(--cyan)">طلب إزالة DMCA</a></li>
    </ul>

    <h2 style="color:var(--white);font-size:17px;font-weight:800;margin:22px 0 10px">الاستقلالية التحريرية</h2>
    <p>
      yassota منصة مستقلة وليست تابعة لأي شركة تطبيقات أو متجر رقمي. نموذج العمل يعتمد على الإعلانات (Google AdSense) مما يُتيح لنا تقديم كل خدماتنا مجاناً.
      <strong style="color:var(--white)">الإعلانات لا تؤثر على تقييماتنا أو اختياراتنا التحريرية بأي شكل.</strong>
    </p>

    <div style="background:rgba(6,182,212,.07);border:1px solid rgba(6,182,212,.2);border-radius:12px;padding:18px 20px;margin:22px 0 0">
      <div style="font-weight:700;color:var(--white);font-size:14px;margin-bottom:10px">للتواصل معنا</div>
      <div style="display:flex;flex-wrap:wrap;gap:14px">
        <a href="mailto:<?= h($contactEmail) ?>" style="color:var(--cyan);font-size:13px;display:flex;align-items:center;gap:6px">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          <?= h($contactEmail) ?>
        </a>
        <a href="<?= h(url('contact')) ?>" style="color:var(--cyan);font-size:13px;display:flex;align-items:center;gap:6px">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
          نموذج التواصل
        </a>
      </div>
    </div>

    <p style="margin-top:22px">
      لمزيد من الشفافية، راجع أيضاً:
      <a href="<?= h(url('privacy-policy')) ?>" style="color:var(--cyan)">سياسة الخصوصية</a>،
      <a href="<?= h(url('terms')) ?>" style="color:var(--cyan)">شروط الاستخدام</a>،
      <a href="<?= h(url('cookie-policy')) ?>" style="color:var(--cyan)">سياسة الكوكيز</a>، و
      <a href="<?= h(url('dmca')) ?>" style="color:var(--cyan)">سياسة DMCA</a>.
    </p>
  </div>

</main>
</div>

<?php render_site_footer(); ?>
<script src="<?= h(asset_url('assets/js/main.js')) ?>"></script>
</body>
</html>
