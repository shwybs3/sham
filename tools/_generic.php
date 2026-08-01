<?php
/**
 * Generic tool handler — AI-powered or utility tool page with full SEO.
 * Each tool's index.php sets $toolSlug and requires this file.
 */
require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/_base.php';

if (empty($toolSlug)) die('toolSlug not set');

$toolRow = $pdo->prepare("SELECT * FROM web_tools WHERE slug = ? AND status = 'published' LIMIT 1");
$toolRow->execute([$toolSlug]);
$tool = $toolRow->fetch(PDO::FETCH_ASSOC);
if (!$tool) { http_response_code(404); die('Tool not found'); }

$toolName  = $tool['name'];
$toolDesc  = $tool['short_description'];
$toolColor = $tool['icon_color'] ?: '#2563eb';
$toolBg    = $tool['icon_bg'] ?: '#dbeafe';
$toolIcon  = $tool['icon_svg'] ?: '';
$longDesc  = $tool['long_description'] ?? '';
$faqData   = $tool['faq'] ? json_decode($tool['faq'], true) : [];
$features  = $tool['features'] ? json_decode($tool['features'], true) : [];
$tutorials = $tool['tutorials'] ?? '';
$seoTitle  = $tool['seo_title'] ?: ($toolName . ($toolSlug && strpos($toolSlug,'ai-') === 0 ? ' — أداة ذكاء اصطناعي مجانية' : ' — أداة ويب مجانية') . ' | yassota');
$metaDesc  = $tool['meta_description'] ?: $toolDesc;
$canonical = TOOLS_BASE_URL . '/tools/' . $toolSlug . '/';

// AI tool slugs
$aiSlugs = [
    'ai-translator','ai-summarizer','ai-rewriter','ai-grammar','ai-email','ai-code',
    'ai-image-desc','ai-social','ai-seo','ai-story','ai-cv','ai-product-desc',
    'ai-title','ai-quiz','ai-poem','ai-logo-ideas','ai-ad-copy','ai-meal-plan',
    'ai-baby-names','ai-explain','ai-debate','ai-lesson-plan','ai-contract',
    'ai-speech','ai-flashcards','ai-business-name','ai-color-palette',
    'ai-hashtag-gen','ai-text-to-sql','ai-meeting-notes',
    'write','hashtag',
];
$isAI = in_array($toolSlug, $aiSlugs);

// Tool-specific configs
$toolConfigs = [
    'ai-translator'    => ['placeholder'=>'الصق النص المراد ترجمته...','fields'=>['target_lang'=>['label'=>'اللغة المستهدفة','type'=>'select','options'=>['en'=>'English','ar'=>'العربية','fr'=>'Français','de'=>'Deutsch','es'=>'Español','tr'=>'Türkçe','it'=>'Italiano','pt'=>'Português','ru'=>'Русский','zh'=>'中文','ja'=>'日本語','ko'=>'한국어']]],'prompt'=>'ترجم النص التالي إلى {target_lang} بدقة عالية مع الحفاظ على السياق والأسلوب:\n\n{input}'],
    'ai-summarizer'    => ['placeholder'=>'الصق النص الطويل هنا...','prompt'=>'لخّص النص التالي بشكل واضح ومختصر في نقاط رئيسية مع الحفاظ على أهم المعلومات:\n\n{input}'],
    'ai-rewriter'      => ['placeholder'=>'الصق النص لإعادة صياغته...','fields'=>['style'=>['label'=>'الأسلوب','type'=>'select','options'=>['academic'=>'أكاديمي','journalistic'=>'صحفي','marketing'=>'تسويقي','simplified'=>'مبسّط','formal'=>'رسمي','creative'=>'إبداعي']]],'prompt'=>'أعد صياغة النص التالي بأسلوب {style} مع الحفاظ على المعنى:\n\n{input}'],
    'ai-grammar'       => ['placeholder'=>'الصق النص للتدقيق النحوي...','prompt'=>'دقّق النص التالي نحوياً وإملائياً، وأظهر الأخطاء مع تصحيحها وشرح مختصر لكل تصحيح:\n\n{input}'],
    'ai-email'         => ['placeholder'=>'اكتب موضوع أو محتوى البريد...','fields'=>['tone'=>['label'=>'النبرة','type'=>'select','options'=>['formal'=>'رسمي','friendly'=>'ودّي','marketing'=>'تسويقي','follow-up'=>'متابعة','complaint'=>'شكوى']]],'prompt'=>'اكتب بريداً إلكترونياً احترافياً بنبرة {tone} عن:\n\n{input}'],
    'ai-code'          => ['placeholder'=>'اكتب سؤالك البرمجي أو الصق الكود...','fields'=>['action'=>['label'=>'الإجراء','type'=>'select','options'=>['explain'=>'شرح الكود','debug'=>'إصلاح الأخطاء','convert'=>'تحويل بين اللغات','optimize'=>'تحسين الأداء','write'=>'كتابة كود جديد']]],'prompt'=>'أنت مبرمج خبير. {action}:\n\n{input}'],
    'ai-image-desc'    => ['placeholder'=>'صف الصورة أو المشهد الذي تريد وصفه...','prompt'=>'اكتب وصفاً تفصيلياً دقيقاً بالعربية مناسباً للسيو وTAlt Text:\n\n{input}'],
    'ai-social'        => ['placeholder'=>'اكتب موضوع المنشور...','fields'=>['platform'=>['label'=>'المنصة','type'=>'select','options'=>['instagram'=>'Instagram','facebook'=>'Facebook','twitter'=>'Twitter/X','linkedin'=>'LinkedIn','tiktok'=>'TikTok']]],'prompt'=>'اكتب منشوراً احترافياً لمنصة {platform} عن:\n\n{input}\n\nأضف هاشتاقات مناسبة.'],
    'ai-seo'           => ['placeholder'=>'الصق محتوى صفحتك أو عنوانها...','prompt'=>'أنت خبير SEO. حلّل المحتوى وقدّم توصيات مفصّلة: عنوان محسّن، meta description، كلمات مفتاحية، وتوصيات هيكلية:\n\n{input}'],
    'ai-story'         => ['placeholder'=>'اكتب فكرة القصة...','fields'=>['genre'=>['label'=>'النوع','type'=>'select','options'=>['short'=>'قصة قصيرة','children'=>'أطفال','scifi'=>'خيال علمي','horror'=>'رعب','romance'=>'رومانسي','adventure'=>'مغامرات']]],'prompt'=>'اكتب قصة {genre} بالعربية عن:\n\n{input}'],
    'ai-cv'            => ['placeholder'=>'اكتب معلوماتك الشخصية وخبراتك...','fields'=>['lang'=>['label'=>'اللغة','type'=>'select','options'=>['ar'=>'عربي','en'=>'English']]],'prompt'=>'أنشئ سيرة ذاتية احترافية باللغة {lang} من:\n\n{input}'],
    'ai-product-desc'  => ['placeholder'=>'اكتب اسم المنتج ومميزاته...','prompt'=>'اكتب وصفاً تسويقياً مقنعاً للمنتج مناسباً للمتجر الإلكتروني:\n\n{input}'],
    'ai-title'         => ['placeholder'=>'اكتب موضوع المقالة...','prompt'=>'أنشئ 10 عناوين جذابة ومحسّنة للسيو لمقالة عن:\n\n{input}'],
    'ai-quiz'          => ['placeholder'=>'اكتب الموضوع أو المادة...','fields'=>['count'=>['label'=>'عدد الأسئلة','type'=>'select','options'=>['5'=>'5 أسئلة','10'=>'10 أسئلة','15'=>'15 سؤال']]],'prompt'=>'أنشئ {count} أسئلة اختبار متعددة الخيارات مع الإجابات الصحيحة عن:\n\n{input}'],
    'ai-poem'          => ['placeholder'=>'اكتب موضوع القصيدة...','fields'=>['style'=>['label'=>'النمط','type'=>'select','options'=>['classic'=>'فصحى كلاسيكية','modern'=>'فصحى حديثة','nabati'=>'نبطي']]],'prompt'=>'اكتب قصيدة بأسلوب {style} عن:\n\n{input}'],
    'ai-logo-ideas'    => ['placeholder'=>'اكتب اسم الشركة ومجالها...','prompt'=>'اقترح 5 أفكار تصميمية مفصّلة لشعار هذا المشروع:\n\n{input}'],
    'ai-ad-copy'       => ['placeholder'=>'اكتب اسم المنتج/الخدمة...','fields'=>['platform'=>['label'=>'المنصة','type'=>'select','options'=>['google'=>'Google Ads','facebook'=>'Facebook Ads','instagram'=>'Instagram','general'=>'عام']]],'prompt'=>'اكتب نص إعلاني مقنع لـ{platform}:\n\n{input}'],
    'ai-meal-plan'     => ['placeholder'=>'اكتب نظامك الغذائي وأهدافك...','prompt'=>'أنشئ خطة وجبات أسبوعية (7 أيام) مع السعرات:\n\n{input}'],
    'ai-baby-names'    => ['placeholder'=>'اكتب تفضيلاتك...','fields'=>['gender'=>['label'=>'الجنس','type'=>'select','options'=>['male'=>'ذكر','female'=>'أنثى','both'=>'كلاهما']]],'prompt'=>'اقترح 20 اسم مولود {gender} عربي جميل مع المعنى والأصل:\n\n{input}'],
    'ai-explain'       => ['placeholder'=>'اكتب المفهوم...','fields'=>['level'=>['label'=>'المستوى','type'=>'select','options'=>['child'=>'طفل','beginner'=>'مبتدئ','intermediate'=>'متوسط','expert'=>'متخصص']]],'prompt'=>'اشرح المفهوم التالي لمستوى {level} مع أمثلة:\n\n{input}'],
    'ai-debate'        => ['placeholder'=>'اكتب الموضوع...','prompt'=>'قدّم 5 حجج مؤيدة و5 معارضة مدعومة بأدلة:\n\n{input}'],
    'ai-lesson-plan'   => ['placeholder'=>'اكتب عنوان الدرس والمرحلة...','prompt'=>'أنشئ خطة درس تعليمية متكاملة:\n\n{input}'],
    'ai-contract'      => ['placeholder'=>'اكتب نوع العقد وتفاصيل الأطراف...','prompt'=>'أنشئ مسودة عقد أولية بالعربية (تنويه: للمراجعة القانونية):\n\n{input}'],
    'ai-speech'        => ['placeholder'=>'اكتب موضوع الخطاب والمناسبة...','fields'=>['duration'=>['label'=>'المدة','type'=>'select','options'=>['3min'=>'3 دقائق','5min'=>'5 دقائق','10min'=>'10 دقائق']]],'prompt'=>'اكتب خطاباً مدته {duration} عن:\n\n{input}'],
    'ai-flashcards'    => ['placeholder'=>'اكتب الموضوع...','fields'=>['count'=>['label'=>'العدد','type'=>'select','options'=>['10'=>'10 بطاقات','20'=>'20 بطاقة','30'=>'30 بطاقة']]],'prompt'=>'أنشئ {count} بطاقة مراجعة (سؤال/إجابة) عن:\n\n{input}'],
    'ai-business-name' => ['placeholder'=>'اكتب مجال عملك ورؤيتك...','prompt'=>'اقترح 15 اسماً إبداعياً لشركة في هذا المجال مع معنى كل اسم:\n\n{input}'],
    'ai-color-palette' => ['placeholder'=>'اكتب الوصف أو المزاج المطلوب...','prompt'=>'أنشئ 3 لوحات ألوان (5 ألوان لكل لوحة) مع قيم HEX ووصف الاستخدام:\n\n{input}'],
    'ai-hashtag-gen'   => ['placeholder'=>'اكتب موضوع المنشور...','fields'=>['platform'=>['label'=>'المنصة','type'=>'select','options'=>['instagram'=>'Instagram','tiktok'=>'TikTok','twitter'=>'Twitter/X','linkedin'=>'LinkedIn']]],'prompt'=>'أنشئ 30 هاشتاق ذكي لـ{platform} مصنفة حسب الشعبية:\n\n{input}'],
    'ai-text-to-sql'   => ['placeholder'=>'اكتب سؤالك بالعربية (مثال: جميع المستخدمين الذين اشتركوا هذا الشهر)...','prompt'=>'حوّل هذا السؤال إلى استعلام SQL مع شرح كل جزء:\n\n{input}'],
    'ai-meeting-notes' => ['placeholder'=>'الصق محضر الاجتماع...','prompt'=>'لخّص محضر الاجتماع: النقاط الرئيسية، القرارات، المهام الموكلة مع المسؤولين والمواعيد:\n\n{input}'],
    'write'            => ['placeholder'=>'اكتب موضوع المقالة أو المنشور...','fields'=>['style'=>['label'=>'نوع المحتوى','type'=>'select','options'=>['article'=>'مقال','email'=>'بريد إلكتروني','social'=>'منشور سوشيال','product'=>'وصف منتج','seo'=>'محتوى SEO']]],'prompt'=>'أنت كاتب محترف بالعربية. اكتب {style} عن:\n\n{input}'],
    'hashtag'          => ['placeholder'=>'اكتب موضوع أو محتوى منشورك...','prompt'=>'أنشئ هاشتاقات احترافية لإنستغرام وتيك توك وتويتر:\n\n{input}'],
];

$defaultConfig = ['placeholder'=>'أدخل البيانات...','prompt'=>''];
$cfg = $toolConfigs[$toolSlug] ?? $defaultConfig;

$result = '';
$error  = '';

// Handle AI tool POST
if ($isAI && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = trim($_POST['input'] ?? '');
    if (mb_strlen($input) < 2) {
        $error = 'الرجاء إدخال نص.';
    } else {
        $prompt = str_replace('{input}', $input, $cfg['prompt']);
        if (!empty($cfg['fields'])) {
            foreach ($cfg['fields'] as $fKey => $fDef) {
                $val   = $_POST[$fKey] ?? '';
                if (!empty($fDef['options']) && !isset($fDef['options'][$val])) $val = array_key_first($fDef['options']);
                $label = $fDef['options'][$val] ?? $val;
                $prompt = str_replace('{'.$fKey.'}', $label, $prompt);
            }
        }
        $aiResult = ai_text($prompt, 2000);
        if (isset($aiResult['content'])) $result = $aiResult['content'];
        else $error = $aiResult['error'] ?? 'تعذّرت المعالجة، حاول مرة أخرى.';
    }
}

// Increment views (non-blocking)
try { $pdo->prepare("UPDATE web_tools SET views = views + 1 WHERE slug = ?")->execute([$toolSlug]); } catch(Throwable $e){}

// ── Fetch related tools for internal linking ─────────────────────────────
$relatedTools = [];
try {
    $relatedTools = $pdo->query(
        "SELECT slug, name, icon_svg, icon_color, icon_bg, short_description FROM web_tools
         WHERE status='published' AND slug != " . $pdo->quote($toolSlug) . "
         ORDER BY RAND() LIMIT 4"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e){}

// ── Fetch recent apps for internal linking ───────────────────────────────
$relatedApps = [];
try {
    $relatedApps = $pdo->query(
        "SELECT slug, name, icon_url, short_description FROM apps WHERE status='published' ORDER BY views DESC LIMIT 4"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e){}

// ── Build default FAQ if none in DB ──────────────────────────────────────
if (empty($faqData)) {
    $faqData = [
        ['q'=>'هل الأداة مجانية بالكامل؟', 'a'=>'نعم، '.$toolName.' مجانية 100% بدون تسجيل أو اشتراك. نموّل الخدمة من خلال الإعلانات فقط.'],
        ['q'=>'هل بياناتي آمنة عند استخدام الأداة؟', 'a'=>'نعم. جميع البيانات تُعالج عبر اتصال مشفر (HTTPS/TLS 1.3). الأدوات التي تعمل على المتصفح لا ترسل بياناتك لخوادمنا أصلاً.'],
        ['q'=>'هل تعمل الأداة على الهاتف المحمول؟', 'a'=>'نعم. الأداة مصممة بتقنية Responsive Design وتعمل بشكل ممتاز على الهواتف الذكية والأجهزة اللوحية.'],
        ['q'=>'هل أحتاج لتثبيت أي برنامج؟', 'a'=>'لا. الأداة تعمل مباشرة في متصفح الإنترنت بدون تثبيت أي برنامج أو إضافة. فقط افتح الرابط واستخدم الأداة.'],
        ['q'=>'كم مرة يمكنني استخدام الأداة؟', 'a'=>'يمكنك استخدام الأداة بشكل غير محدود. لا توجد قيود على عدد الاستخدامات اليومية.'],
    ];
}

// ── Schema.org markup ─────────────────────────────────────────────────────
$schemaFaq = [];
foreach ($faqData as $qa) {
    if (!empty($qa['q'])) {
        $schemaFaq[] = [
            '@type'          => 'Question',
            'name'           => $qa['q'],
            'acceptedAnswer' => ['@type'=>'Answer','text'=>$qa['a']??''],
        ];
    }
}

$schemaLD = [
    [
        '@context'           => 'https://schema.org',
        '@type'              => $isAI ? ['WebApplication','SoftwareApplication'] : 'WebApplication',
        'name'               => $toolName,
        'url'                => $canonical,
        'description'        => $metaDesc,
        'applicationCategory'=> $isAI ? 'AIApplication' : 'WebApplication',
        'operatingSystem'    => 'All',
        'inLanguage'         => ['ar','en'],
        'offers'             => ['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
        'provider'           => ['@type'=>'Organization','name'=>'yassota','url'=>SITE_URL],
        'featureList'        => $toolDesc,
    ],
];

if (!empty($schemaFaq)) {
    $schemaLD[] = [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $schemaFaq,
    ];
}

$schemaLD[] = [
    '@context'        => 'https://schema.org',
    '@type'           => 'BreadcrumbList',
    'itemListElement' => [
        ['@type'=>'ListItem','position'=>1,'name'=>'الرئيسية','item'=>SITE_URL.'/'],
        ['@type'=>'ListItem','position'=>2,'name'=>'أدوات ويب','item'=>TOOLS_BASE_URL.'/tools/'],
        ['@type'=>'ListItem','position'=>3,'name'=>$toolName,'item'=>$canonical],
    ],
];

tool_head($seoTitle, $metaDesc, json_encode($schemaLD, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), $toolColor, $canonical);
tool_header();
?>
<main class="t-main">

  <!-- Breadcrumb -->
  <nav style="font-size:12px;color:#94a3b8;margin-bottom:16px;display:flex;gap:6px;align-items:center;flex-wrap:wrap">
    <a href="<?= SITE_URL ?>/" style="color:#06b6d4;text-decoration:none">الرئيسية</a>
    <span>›</span>
    <a href="<?= TOOLS_BASE_URL ?>/tools/" style="color:#06b6d4;text-decoration:none">الأدوات</a>
    <span>›</span>
    <span><?= htmlspecialchars($toolName) ?></span>
  </nav>

  <!-- Hero -->
  <div class="t-hero">
    <div class="t-hero-icon" style="background:<?= htmlspecialchars($toolBg) ?>">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="<?= htmlspecialchars($toolColor) ?>" stroke-width="2"><?= $toolIcon ?></svg>
    </div>
    <h1><?= htmlspecialchars($toolName) ?></h1>
    <p><?= htmlspecialchars($toolDesc) ?></p>
    <?php if ($isAI): ?>
    <div style="display:inline-flex;align-items:center;gap:6px;background:rgba(99,102,241,.1);border:1px solid rgba(99,102,241,.25);border-radius:20px;padding:5px 14px;font-size:12px;color:#6366f1;margin-top:10px">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
      مدعومة بالذكاء الاصطناعي
    </div>
    <?php endif; ?>
  </div>

  <?php if ($isAI): ?>
  <!-- ── AI TOOL INTERFACE ───────────────────────────────────────────── -->
  <?php if ($error): ?>
  <div class="t-card" style="border-right:4px solid #ef4444;background:#fef2f2;color:#991b1b;margin-bottom:16px;padding:14px 18px;font-size:14px"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="t-card">
    <form method="POST" id="ai-form">
      <?php if (!empty($cfg['fields'])): ?>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;margin-bottom:16px">
        <?php foreach ($cfg['fields'] as $fKey => $fDef): ?>
        <div>
          <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;color:#374151"><?= htmlspecialchars($fDef['label']) ?></label>
          <?php if ($fDef['type'] === 'select'): ?>
          <select name="<?= htmlspecialchars($fKey) ?>" style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:10px;font-size:14px;background:#fff;color:#1f2937;direction:rtl">
            <?php foreach ($fDef['options'] as $oKey => $oLabel): ?>
            <option value="<?= htmlspecialchars($oKey) ?>" <?= ($_POST[$fKey] ?? '') === $oKey ? 'selected' : '' ?>><?= htmlspecialchars($oLabel) ?></option>
            <?php endforeach; ?>
          </select>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <textarea name="input" rows="6" placeholder="<?= htmlspecialchars($cfg['placeholder']) ?>"
        style="width:100%;padding:14px 16px;border:1.5px solid #e2e8f0;border-radius:12px;font-size:15px;font-family:inherit;resize:vertical;box-sizing:border-box;line-height:1.8;color:#1f2937;background:#f9fafb;transition:border-color .2s;direction:rtl"
        onfocus="this.style.borderColor='<?= htmlspecialchars($toolColor) ?>'" onblur="this.style.borderColor='#e2e8f0'"
        required><?= htmlspecialchars($_POST['input'] ?? '') ?></textarea>

      <button type="submit" id="ai-btn" style="margin-top:14px;width:100%;padding:14px;border:none;border-radius:12px;background:<?= htmlspecialchars($toolColor) ?>;color:#fff;font-size:16px;font-weight:700;cursor:pointer;font-family:inherit;transition:opacity .2s;display:flex;align-items:center;justify-content:center;gap:8px">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
        <span id="ai-btn-text">معالجة بالذكاء الاصطناعي</span>
      </button>
    </form>
  </div>

  <?php if ($result): ?>
  <div class="t-card" style="margin-top:16px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">
      <h2 style="margin:0;font-size:16px;font-weight:700;color:#0f172a">النتيجة</h2>
      <div style="display:flex;gap:8px">
        <button onclick="copyResult()" id="copy-btn" style="padding:8px 18px;border:1px solid #d1d5db;border-radius:8px;background:#fff;color:#374151;font-size:13px;cursor:pointer;font-family:inherit;transition:all .2s">نسخ النتيجة</button>
      </div>
    </div>
    <div id="ai-result" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:20px;font-size:14px;line-height:2;white-space:pre-wrap;color:#1e293b;direction:rtl;min-height:80px"><?= nl2br(htmlspecialchars($result)) ?></div>
  </div>
  <script>
  function copyResult(){
    const txt = document.getElementById('ai-result').innerText;
    navigator.clipboard.writeText(txt).then(()=>{
      const btn = document.getElementById('copy-btn');
      btn.textContent = 'تم النسخ ✓';
      btn.style.background='#d1fae5';btn.style.color='#065f46';
      setTimeout(()=>{btn.textContent='نسخ النتيجة';btn.style.background='';btn.style.color='';},2000);
    });
  }
  document.getElementById('ai-form').addEventListener('submit',function(){
    const btn=document.getElementById('ai-btn');
    const txt=document.getElementById('ai-btn-text');
    btn.disabled=true; btn.style.opacity='0.7';
    txt.textContent='جارٍ المعالجة...';
  });
  </script>
  <?php endif; ?>

  <?php else: ?>
  <!-- ── NON-AI TOOL COMING SOON ─────────────────────────────────────── -->
  <div class="t-card" style="text-align:center;padding:48px 24px">
    <div style="width:80px;height:80px;border-radius:20px;background:<?= htmlspecialchars($toolBg) ?>;display:inline-flex;align-items:center;justify-content:center;margin-bottom:20px;box-shadow:0 4px 20px rgba(0,0,0,.08)">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="<?= htmlspecialchars($toolColor) ?>" stroke-width="2"><?= $toolIcon ?></svg>
    </div>
    <h2 style="font-size:22px;color:#0f172a;margin-bottom:8px"><?= htmlspecialchars($toolName) ?></h2>
    <p style="color:#64748b;font-size:15px;margin-bottom:28px;max-width:480px;margin-left:auto;margin-right:auto"><?= htmlspecialchars($toolDesc) ?></p>
    <div style="background:linear-gradient(135deg,rgba(<?= implode(',',sscanf(ltrim($toolColor,'#'),'%02x%02x%02x')) ?>,0.08),rgba(<?= implode(',',sscanf(ltrim($toolColor,'#'),'%02x%02x%02x')) ?>,0.04));border:1px solid rgba(<?= implode(',',sscanf(ltrim($toolColor,'#'),'%02x%02x%02x')) ?>,0.2);border-radius:14px;padding:22px 28px;display:inline-block;max-width:440px">
      <div style="font-size:24px;margin-bottom:8px">🚀</div>
      <p style="color:#1e293b;font-size:15px;font-weight:600;margin:0 0 6px">قيد التطوير النهائي</p>
      <p style="color:#64748b;font-size:13px;margin:0">ستكون هذه الأداة متاحة قريباً جداً</p>
    </div>
  </div>
  <?php endif; ?>

  <?php tool_ad(); ?>

  <!-- ── LONG DESCRIPTION / SEO CONTENT ─────────────────────────────── -->
  <?php if ($longDesc): ?>
  <div class="t-card t-article-content" style="margin-top:24px;font-size:14px;line-height:1.9;color:#475569">
    <?= $longDesc ?>
  </div>
  <?php endif; ?>

  <!-- ── FEATURES ────────────────────────────────────────────────────── -->
  <?php if ($features): ?>
  <div class="t-card" style="margin-top:16px">
    <h2 style="font-size:18px;font-weight:700;color:#0f172a;margin:0 0 16px">مميزات <?= htmlspecialchars($toolName) ?></h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px">
      <?php foreach ($features as $feat): ?>
      <div style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="<?= htmlspecialchars($toolColor) ?>" stroke-width="2.5" style="flex-shrink:0;margin-top:2px"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <span style="font-size:13px;color:#374151;line-height:1.7"><?= htmlspecialchars(is_array($feat) ? ($feat['text'] ?? (string)($feat[0] ?? '')) : (string)$feat) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── FAQ ─────────────────────────────────────────────────────────── -->
  <?php if ($faqData): ?>
  <div class="t-card" style="margin-top:16px">
    <h2 style="font-size:18px;font-weight:700;color:#0f172a;margin:0 0 16px">الأسئلة الشائعة</h2>
    <div id="faq-list">
      <?php foreach ($faqData as $i => $qa): ?>
      <?php if (empty($qa['q'])) continue; ?>
      <details style="margin-bottom:10px;border:1.5px solid #e2e8f0;border-radius:12px;overflow:hidden" <?= $i===0?'open':'' ?>>
        <summary style="padding:15px 20px;cursor:pointer;font-weight:700;font-size:14px;color:#1e293b;background:#f8fafc;list-style:none;display:flex;justify-content:space-between;align-items:center">
          <?= htmlspecialchars($qa['q']) ?>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0;transition:transform .2s"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div style="padding:16px 20px;font-size:13px;color:#475569;line-height:1.9;border-top:1px solid #e2e8f0"><?= nl2br(htmlspecialchars($qa['a'] ?? '')) ?></div>
      </details>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── TUTORIALS ────────────────────────────────────────────────────── -->
  <?php if ($tutorials): ?>
  <div class="t-card t-article-content" style="margin-top:16px">
    <h2 style="font-size:18px;font-weight:700;color:#0f172a;margin:0 0 16px">كيفية الاستخدام</h2>
    <?= $tutorials ?>
  </div>
  <?php endif; ?>

  <!-- ── RELATED TOOLS ────────────────────────────────────────────────── -->
  <?php if ($relatedTools): ?>
  <div class="t-card" style="margin-top:16px">
    <h2 style="font-size:16px;font-weight:700;color:#0f172a;margin:0 0 14px">أدوات ذات صلة</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px">
      <?php foreach ($relatedTools as $rt): ?>
      <a href="<?= TOOLS_BASE_URL ?>/tools/<?= htmlspecialchars($rt['slug']) ?>/" style="text-decoration:none">
        <div style="padding:14px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;transition:all .18s;display:flex;align-items:center;gap:12px" onmouseenter="this.style.borderColor='<?= htmlspecialchars($rt['icon_color'] ?? '#2563eb') ?>';this.style.background='#f1f5f9'" onmouseleave="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc'">
          <div style="width:36px;height:36px;border-radius:9px;background:<?= htmlspecialchars($rt['icon_bg'] ?? '#dbeafe') ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="<?= htmlspecialchars($rt['icon_color'] ?? '#2563eb') ?>" stroke-width="2"><?= $rt['icon_svg'] ?></svg>
          </div>
          <div>
            <div style="font-size:13px;font-weight:700;color:#0f172a"><?= htmlspecialchars($rt['name']) ?></div>
            <div style="font-size:11px;color:#64748b;margin-top:2px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical"><?= htmlspecialchars($rt['short_description']) ?></div>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── RELATED APPS ─────────────────────────────────────────────────── -->
  <?php if ($relatedApps): ?>
  <div class="t-card" style="margin-top:16px">
    <h2 style="font-size:16px;font-weight:700;color:#0f172a;margin:0 0 14px">تطبيقات مقترحة</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px">
      <?php foreach ($relatedApps as $app): ?>
      <a href="<?= SITE_URL ?>/app/<?= htmlspecialchars($app['slug']) ?>/" style="text-decoration:none">
        <div style="padding:12px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;transition:all .18s;display:flex;align-items:center;gap:10px" onmouseenter="this.style.background='#f1f5f9';this.style.borderColor='#06b6d4'" onmouseleave="this.style.background='#f8fafc';this.style.borderColor='#e2e8f0'">
          <?php if ($app['icon_url']): ?>
          <img src="<?= htmlspecialchars($app['icon_url']) ?>" alt="<?= htmlspecialchars($app['name']) ?>" width="36" height="36" style="border-radius:8px;object-fit:cover;flex-shrink:0" loading="lazy">
          <?php else: ?>
          <div style="width:36px;height:36px;border-radius:8px;background:#dbeafe;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:18px">📱</div>
          <?php endif; ?>
          <div>
            <div style="font-size:13px;font-weight:700;color:#0f172a;overflow:hidden;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical"><?= htmlspecialchars($app['name']) ?></div>
            <div style="font-size:11px;color:#64748b;margin-top:2px">تطبيق أندرويد</div>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</main>
<?php tool_footer(); ?>
