<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
  '@context'=>'https://schema.org','@type'=>'WebApplication',
  'name'=>'مصغّر HTML مجاناً','url'=>TOOLS_BASE_URL.'/tools/html-minifier/',
  'description'=>'صغّر كود HTML وقلّل حجم الملف بإزالة التعليقات والمسافات الزائدة — مجاناً في المتصفح',
  'applicationCategory'=>'DeveloperApplication','operatingSystem'=>'All',
  'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('مصغّر HTML مجاناً — ضغط كود HTML | yassota','صغّر كود HTML بإزالة التعليقات والمسافات الزائدة وأحرف السطر الجديد. شاهد نسبة الضغط فوراً — مجاني وآمن في متصفحك',$schema,'#dc2626');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#fee2e2">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg>
    </div>
    <h1>مصغّر HTML</h1>
    <p>ضغط كود HTML بإزالة التعليقات والمسافات الزائدة مع عرض نسبة الضغط الفعلية — بدون إرسال أي بيانات للخادم.</p>
  </div>

  <div class="t-card">
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
      <button class="t-btn" onclick="minifyHTML()" style="background:#dc2626">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg>
        تصغير HTML
      </button>
      <button class="t-btn t-btn-secondary" onclick="beautifyHTML()">تجميل HTML</button>
      <button class="t-btn t-btn-secondary" onclick="copyOutput()">نسخ الناتج</button>
      <button class="t-btn t-btn-secondary" onclick="clearAll()">مسح الكل</button>
      <button class="t-btn t-btn-secondary" onclick="swapIO()">↕ عكس</button>
    </div>

    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px;padding:12px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0">
      <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
        <input type="checkbox" id="opt-comments" checked style="accent-color:#dc2626"> إزالة التعليقات
      </label>
      <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
        <input type="checkbox" id="opt-whitespace" checked style="accent-color:#dc2626"> ضغط المسافات
      </label>
      <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
        <input type="checkbox" id="opt-newlines" checked style="accent-color:#dc2626"> إزالة أسطر فارغة
      </label>
      <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
        <input type="checkbox" id="opt-attrquotes" style="accent-color:#dc2626"> إزالة إقتباسات attrs اختيارية
      </label>
    </div>

    <div class="t-row">
      <div class="t-col">
        <label class="t-label">كود HTML الأصلي</label>
        <textarea id="html-in" class="t-textarea" style="min-height:280px;font-family:monospace;font-size:12px;direction:ltr;background:#f8fafc" placeholder="&lt;!DOCTYPE html&gt;&#10;&lt;html&gt;&#10;  &lt;head&gt;&#10;    &lt;!-- تعليق --&gt;&#10;    &lt;title&gt;مثال&lt;/title&gt;&#10;  &lt;/head&gt;&#10;&lt;/html&gt;"></textarea>
      </div>
      <div class="t-col">
        <label class="t-label">الناتج المضغوط</label>
        <textarea id="html-out" class="t-textarea" style="min-height:280px;font-family:monospace;font-size:12px;direction:ltr;background:#f8fafc" readonly placeholder="سيظهر الكود المضغوط هنا..."></textarea>
      </div>
    </div>

    <div id="stats-bar" style="display:none;margin-top:14px;padding:14px;background:#f0fdf4;border:1.5px solid #86efac;border-radius:10px">
      <div style="display:flex;gap:20px;flex-wrap:wrap;font-size:13px">
        <div><span style="color:#64748b">الحجم الأصلي: </span><strong id="stat-before" style="color:#0f172a"></strong></div>
        <div><span style="color:#64748b">الحجم بعد الضغط: </span><strong id="stat-after" style="color:#0f172a"></strong></div>
        <div><span style="color:#64748b">نسبة الضغط: </span><strong id="stat-ratio" style="color:#16a34a"></strong></div>
        <div><span style="color:#64748b">وفّرت: </span><strong id="stat-saved" style="color:#dc2626"></strong></div>
      </div>
      <div style="margin-top:8px">
        <div class="t-progress" style="height:10px">
          <div id="stat-bar" class="t-progress-bar" style="background:#16a34a;height:10px;border-radius:5px;width:0%;transition:width .5s"></div>
        </div>
      </div>
    </div>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card" style="line-height:1.9">
    <h2 style="font-size:22px;font-weight:800;color:#7f1d1d;margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid #fecaca">ما هو تصغير HTML وما أهميته؟ — شرح كامل</h2>

    <p style="color:#374151;margin-bottom:16px">تصغير HTML (HTML Minification) هو عملية إزالة جميع الأحرف غير الضرورية من كود HTML دون تغيير وظيفته أو سلوكه في المتصفح. هذه الأحرف غير الضرورية تشمل المسافات الزائدة، وأسطر الفصل الفارغة، والتعليقات البرمجية، والمسافات بين علامات HTML. الناتج النهائي هو كود أصغر حجماً وأسرع في التحميل وأقل استهلاكاً للنطاق الترددي. في مواقع الويب الكبيرة التي تخدم ملايين الزوار يومياً، يمكن لتصغير HTML وحده أن يوفر مئات الغيغابايتات من النطاق الترددي شهرياً.</p>

    <p style="color:#374151;margin-bottom:16px">لفهم حجم التأثير: صفحة HTML نموذجية تحتوي على كود HTML بحجم 50 كيلوبايت قد تنكمش إلى 35 كيلوبايت بعد التصغير، وهو توفير بنسبة 30%. بالنسبة للمستخدمين على شبكات بطيئة أو بيانات محدودة — وهم ما زالوا يمثلون شريحة واسعة في كثير من الدول العربية — هذا الفارق ملموس حقيقياً في سرعة تحميل الصفحة. كما أن محركات البحث مثل Google تضع سرعة تحميل الصفحة كعامل مهم في ترتيب نتائج البحث (SEO)، مما يجعل تصغير الكود استثماراً مباشراً في رؤية موقعك في نتائج البحث.</p>

    <p style="color:#374151;margin-bottom:16px">ميزة مهمة تميز مصغّر HTML عن ضغط الملفات بصيغة GZip أو Brotli: التصغير يعمل على مستوى الكود نفسه ويبقى الكود قابلاً للقراءة (نسبياً) ومفهوماً للمتصفح مباشرة دون الحاجة لفك الضغط. أما ضغط GZip فيعمل على مستوى الملف الثنائي ويتطلب من الخادم والمتصفح خطوة فك الضغط. الحل الأمثل هو الجمع بين الاثنين: صغّر الكود أولاً ثم أضف ضغط GZip من الخادم.</p>

    <h2 style="font-size:20px;font-weight:800;color:#7f1d1d;margin:28px 0 16px;padding-bottom:10px;border-bottom:2px solid #fecaca">كيفية استخدام الأداة خطوة بخطوة</h2>

    <div style="background:#fff7f7;border-radius:12px;padding:16px;margin-bottom:20px;border:1px solid #fecaca">
      <ol style="color:#374151;font-size:14px;padding-right:20px;line-height:2.2">
        <li><strong>الصق كود HTML</strong> في حقل الإدخال الأيسر. يمكنك لصق صفحة كاملة أو مقطع كود جزئي.</li>
        <li><strong>اختر خيارات التصغير</strong> المناسبة: إزالة التعليقات مستحسنة دائماً في الإنتاج؛ ضغط المسافات آمن تماماً؛ إزالة إقتباسات attributes اختيارية قد تُسبب مشاكل مع بعض المعالجات.</li>
        <li><strong>اضغط "تصغير HTML"</strong> لتشغيل عملية التصغير. سيظهر الناتج فوراً في حقل الإخراج.</li>
        <li><strong>راجع إحصائيات الضغط</strong> أسفل الحقلين: الحجم قبل وبعد، ونسبة التوفير.</li>
        <li><strong>انسخ الناتج</strong> واستخدمه في مشروعك أو ادفعه مباشرة للخادم.</li>
      </ol>
      <p style="color:#dc2626;font-size:13px;margin-top:10px;font-weight:600">تنبيه: احتفظ دائماً بالنسخة الأصلية غير المصغّرة للتطوير والتعديل. ضع فقط النسخة المصغّرة على الخادم الإنتاجي.</p>
    </div>

    <h2 style="font-size:20px;font-weight:800;color:#7f1d1d;margin:28px 0 16px;padding-bottom:10px;border-bottom:2px solid #fecaca">ما الذي يُزيله مصغّر HTML؟</h2>

    <p style="color:#374151;margin-bottom:16px"><strong style="color:#7f1d1d">التعليقات HTML:</strong> التعليقات المحاطة بعلامتَي &lt;!-- و --&gt; مفيدة للمطور لكنها حمل زائد على الزوار. المصغّر يحذفها جميعاً. استثناء: تعليقات شرطية Internet Explorer مثل &lt;!--[if IE]&gt; يجب الحفاظ عليها لأنها وظيفية وليست توضيحية، وأداتنا تُبقي عليها.</p>

    <p style="color:#374151;margin-bottom:16px"><strong style="color:#7f1d1d">المسافات المتعددة المتتالية:</strong> حين يكتب المطور كوداً منظماً بمسافات بادئة (Indentation) لتسهيل القراءة، تُحوَّل كل سلسلة مسافات متتالية إلى مسافة واحدة فقط. المتصفح لا يُميّز بين مسافة واحدة وعشر مسافات داخل محتوى HTML.</p>

    <p style="color:#374151;margin-bottom:16px"><strong style="color:#7f1d1d">أسطر الفصل والفراغة:</strong> الأسطر الجديدة التي يضيفها المطور لتنظيم الكود ليست ضرورية للمتصفح. حذفها يُقلل حجم الملف بشكل ملحوظ خاصة في ملفات HTML الكبيرة التي تحتوي مئات العناصر المتداخلة.</p>

    <p style="color:#374151;margin-bottom:16px"><strong style="color:#7f1d1d">إقتباسات الخصائص غير الضرورية:</strong> معيار HTML5 يسمح بحذف إقتباسات قيم attributes البسيطة التي لا تحتوي مسافات أو رموزاً خاصة. مثلاً &lt;div class="header"&gt; يمكن كتابتها &lt;div class=header&gt;. لكن انتبه: هذا التحسين الأخير قد يُسبب مشاكل مع بعض المعالجات، فاختبر دائماً.</p>

    <h2 style="font-size:20px;font-weight:800;color:#7f1d1d;margin:28px 0 16px;padding-bottom:10px;border-bottom:2px solid #fecaca">الفرق بين تصغير HTML وضغط GZip وضغط Brotli</h2>

    <p style="color:#374151;margin-bottom:16px">يُمثّل هذا السؤال ارتباكاً شائعاً لدى كثير من مطوري الويب. الأساليب الثلاثة مختلفة تماماً وتعمل على مستويات مختلفة. تصغير HTML يُعدّل الكود نفسه ويجعله أصغر قبل أي معالجة أخرى. ضغط GZip أو Brotli يعمل على مستوى نقل الملف عبر الشبكة، فالخادم يضغط الملف ثنائياً ويرسله للمتصفح الذي يفكه تلقائياً. الأفضل هو تطبيق كلا الأسلوبين معاً: صغّر الكود ثم دعِ الخادم يضغطه بـ GZip. هذا الجمع يُعطي أقصى قدر من التوفير في الحجم.</p>

    <p style="color:#374151;margin-bottom:16px">مثال عملي: صفحة HTML بحجم 100 كيلوبايت قد تنخفض إلى 70 كيلوبايت بعد التصغير (30% توفير)، ثم ينخفض الملف المضغوط بـ GZip إلى 15 كيلوبايت فقط (85% توفير إجمالي). الفارق بين التصغير وحده والتصغير مع GZip ضخم لأن GZip يستفيد من التكرار في النصوص، وكود HTML يحتوي كثيراً من الأنماط المتكررة مثل العلامات والأسماء.</p>

    <h2 style="font-size:20px;font-weight:800;color:#7f1d1d;margin:28px 0 16px;padding-bottom:10px;border-bottom:2px solid #fecaca">أسئلة شائعة</h2>

    <div style="display:flex;flex-direction:column;gap:16px">
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#7f1d1d;cursor:pointer;font-size:14px">هل التصغير آمن وهل يؤثر على وظائف الموقع؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">نعم، التصغير آمن تماماً للكود البنيوي لأن المتصفحات تتجاهل المسافات الزائدة والتعليقات أصلاً. الحالة الوحيدة التي قد تُسبب مشكلة هي المسافات داخل العناصر التي تحافظ على المسافات مثل &lt;pre&gt; و&lt;textarea&gt;. الأداة الجيدة تتجاهل هذه العناصر وتحافظ على محتواها كما هو، وهذا ما تفعله أداتنا.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#7f1d1d;cursor:pointer;font-size:14px">هل يجب تصغير JavaScript وCSS الموجودَين داخل HTML أيضاً؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">نعم بالتأكيد، لكن بأدوات متخصصة. JavaScript الداخلية (Inline Scripts) و CSS الداخلية (Inline Styles) تستحق تصغيرها بأدوات متخصصة مثل Terser لـ JS و cssnano لـ CSS. أداتنا تركّز على بنية HTML وتُزيل التعليقات والمسافات البنيوية، لكن لتصغير شامل للكود الداخلي استخدم أدوات متخصصة أو بيئة بناء متكاملة مثل Webpack أو Vite.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#7f1d1d;cursor:pointer;font-size:14px">كيف أُطبّق التصغير التلقائي في مشروعي؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">للمشاريع الاحترافية، استخدم أدوات البناء التلقائية. في Node.js استخدم مكتبة html-minifier-terser مع Webpack أو Gulp. في PHP استخدم مكتبات مثل Minify أو HtmlMinifier. في إطارات عمل مثل Laravel أو Django يمكنك دمج التصغير في خط أنابيب الأصول (Asset Pipeline). تفعيل التصغير التلقائي يعني أنك تكتب كوداً نظيفاً منظماً في التطوير والخادم يحصل على نسخة محسّنة في الإنتاج.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#7f1d1d;cursor:pointer;font-size:14px">هل التصغير يؤثر على SEO؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">التصغير يُحسّن SEO بشكل غير مباشر من خلال تحسين سرعة تحميل الصفحة، وهو عامل مرتبة مؤكد في خوارزمية Google. Core Web Vitals التي تقيسها Google تتأثر بحجم الموارد وسرعة تحميلها. صفحة أسرع = تجربة مستخدم أفضل = ترتيب أعلى في نتائج البحث. لا يوجد أي تأثير سلبي على SEO من التصغير طالما لم تحذف محتوى نصياً حقيقياً.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#7f1d1d;cursor:pointer;font-size:14px">ما الفرق بين تصغير HTML للمبتدئين وللمحترفين؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">للمبتدئين: أداة ويب مثل أداتنا تكفي تماماً. الصق الكود، اضغط تصغير، انسخ الناتج. للمحترفين الذين يديرون مواقع ضخمة: استخدم أدوات البناء الآلية في Pipeline CI/CD، وادمج التصغير مع ضغط GZip/Brotli على مستوى الخادم، وراقب Web Vitals بانتظام. المشاريع الضخمة تحتاج أيضاً لتصغير CSS وJavaScript بجانب HTML وتحسين الصور وتقنية Lazy Loading.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#7f1d1d;cursor:pointer;font-size:14px">هل التصغير يجعل الكود محمياً من السرقة؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">لا، التصغير ليس تشفيراً ولا تعتيماً. الكود المصغّر لا يزال قابلاً للقراءة والفهم عبر أدوات Developer Tools في المتصفح، أو عبر أدوات تجميل الكود (Beautify). إذا كنت تريد حماية كود JavaScript من الفهم السهل، استخدم أدوات Obfuscation المتخصصة مثل JavaScript Obfuscator، لكن تذكر أن HTML والـ CSS لا يمكن حمايتهما فعلياً لأن المتصفح يجب أن يقرأهما.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#7f1d1d;cursor:pointer;font-size:14px">كم حجم التوفير المتوقع من التصغير؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">يعتمد ذلك على كمية المسافات والتعليقات في الكود الأصلي. الكود المكتوب بمسافات بادئة واضحة وتعليقات كثيرة قد يُوفّر 20-40% من حجمه بعد التصغير. الكود المُولَّد آلياً بدون تعليقات كثيرة قد لا يُوفّر أكثر من 5-10%. متوسط التوفير الواقعي لمواقع الويب العادية يتراوح بين 15-25%.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#7f1d1d;cursor:pointer;font-size:14px">هل تعمل الأداة على الجوال؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">نعم، الأداة متجاوبة تماماً مع الأجهزة المحمولة. على الجوال، يتحول التخطيط الجانبي إلى تخطيط عمودي لسهولة الاستخدام. يمكنك لصق الكود من أي تطبيق على هاتفك وتصغيره ونسخ الناتج للصقه في محرر الكود أو مشاركته مع زميل.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#7f1d1d;cursor:pointer;font-size:14px">هل يمكنني عكس التصغير واسترداد الكود الأصلي؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">لا يمكن استرداد الكود الأصلي تماماً من الكود المصغّر. يمكن "تجميل" الكود المصغّر (Beautify) بإضافة مسافات وأسطر جديدة لتسهيل القراءة، وأداتنا تدعم ذلك بزر "تجميل HTML". لكن التعليقات المحذوفة وأسماء المتغيرات المختصرة لا تُستعاد. لذلك دائماً احتفظ بالكود المصدر الأصلي في نظام التحكم بالإصدارات مثل Git.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#7f1d1d;cursor:pointer;font-size:14px">ما الفرق بين التصغير الكامل والجزئي؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">التصغير الجزئي (Conservative Minification) يُزيل فقط المسافات الزائدة والتعليقات الآمنة. التصغير الكامل (Aggressive Minification) يذهب أبعد: يُزيل علامات الإغلاق الاختيارية مثل &lt;/li&gt; و&lt;/td&gt;، ويُزيل علامات اقتباس الخصائص، ويُدمج التصريحات المتكررة. التصغير الكامل يُحقق وفورات أكبر لكنه يحتاج اختباراً أكثر للتأكد من عدم كسر أي وظيفة.</p>
      </details>
    </div>

    <h2 style="font-size:20px;font-weight:800;color:#7f1d1d;margin:28px 0 16px;padding-bottom:10px;border-bottom:2px solid #fecaca">الخلاصة</h2>
    <p style="color:#374151;margin-bottom:16px">تصغير HTML خطوة بسيطة ذات تأثير كبير. في مواقع الويب المهنية، تصغير ملفات HTML يُجسّد احتراماً لوقت المستخدم وعرضه، ويُحسّن ترتيب الموقع في محركات البحث، ويُقلل تكاليف النطاق الترددي. أداتنا تُتيح هذا التحسين بنقرة واحدة لأي مطور أو صاحب موقع دون الحاجة لأدوات معقدة أو معرفة تقنية متعمقة. ابدأ اليوم بتصغير صفحاتك وشاهد الفرق الفعلي في حجم الملفات وسرعة التحميل.</p>
  </div>
</main>

<script>
function minifyHTML(){
  var inp=document.getElementById('html-in').value;
  if(!inp.trim()) return;
  var res=inp;

  // Remove HTML comments (but keep conditional comments)
  if(document.getElementById('opt-comments').checked){
    res=res.replace(/<!--(?!\[if )[\s\S]*?-->/g,'');
  }

  // Collapse whitespace between tags
  if(document.getElementById('opt-whitespace').checked){
    // Preserve content inside pre, textarea, script, style
    var preserved=[];
    res=res.replace(/<(pre|textarea|script|style)([\s\S]*?)>([\s\S]*?)<\/\1>/gi,function(m){
      preserved.push(m);
      return '\x00PRESERVED'+(preserved.length-1)+'\x00';
    });
    res=res.replace(/\s+/g,' ');
    res=res.replace(/>\s+</g,'><');
    // Restore preserved blocks
    res=res.replace(/\x00PRESERVED(\d+)\x00/g,function(m,i){return preserved[parseInt(i)];});
  }

  // Remove empty lines
  if(document.getElementById('opt-newlines').checked){
    res=res.replace(/^\s*[\r\n]/gm,'');
  }

  // Remove optional quotes
  if(document.getElementById('opt-attrquotes').checked){
    res=res.replace(/="([^"'\s<>`=]+)"/g,'=$1');
  }

  res=res.trim();
  document.getElementById('html-out').value=res;
  showStats(inp,res);
}

function beautifyHTML(){
  var inp=document.getElementById('html-out').value||document.getElementById('html-in').value;
  if(!inp.trim()) return;
  var indent=0;
  var res=inp
    .replace(/></g,'>\n<')
    .replace(/<\/[^>]+>/g,function(m){indent=Math.max(0,indent-1);return '  '.repeat(indent)+m;})
    .replace(/<[^\/!][^>]*[^\/]>/g,function(m){var r='  '.repeat(indent)+m;indent++;return r;})
    .replace(/<[^>]+\/>/g,function(m){return '  '.repeat(indent)+m;});
  document.getElementById('html-out').value=res.trim();
}

function showStats(before,after){
  var b=new TextEncoder().encode(before).length;
  var a=new TextEncoder().encode(after).length;
  var ratio=b>0?((1-a/b)*100).toFixed(1):0;
  var saved=b-a;
  document.getElementById('stat-before').textContent=fmt(b);
  document.getElementById('stat-after').textContent=fmt(a);
  document.getElementById('stat-ratio').textContent=ratio+'%';
  document.getElementById('stat-saved').textContent=fmt(saved);
  document.getElementById('stat-bar').style.width=Math.min(100,ratio)+'%';
  document.getElementById('stats-bar').style.display='block';
}

function fmt(bytes){
  if(bytes<1024) return bytes+' B';
  return (bytes/1024).toFixed(1)+' KB';
}

function copyOutput(){
  var v=document.getElementById('html-out').value;
  if(v) navigator.clipboard.writeText(v);
}

function swapIO(){
  var a=document.getElementById('html-in').value;
  var b=document.getElementById('html-out').value;
  document.getElementById('html-in').value=b;
  document.getElementById('html-out').value=a;
}

function clearAll(){
  document.getElementById('html-in').value='';
  document.getElementById('html-out').value='';
  document.getElementById('stats-bar').style.display='none';
}
</script>
<?php tool_footer(); ?>
