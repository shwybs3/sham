<?php
/**
 * legal-static.php — الصفحات القانونية المستقلة (نسخة الطوارئ)
 *
 * WHY THIS FILE EXISTS
 * --------------------
 * Every page on the site normally goes through config.php, which opens a PDO
 * connection. When MySQL is unreachable, config.php dies with a maintenance
 * screen carrying HTTP 503 + <meta name="robots" content="noindex,nofollow">.
 * That means that during any database hiccup — including a rotated password —
 * Googlebot sees the About and Privacy pages as unavailable and non-indexable.
 * AdSense reviewers check for exactly those pages, and a 503 reads as "missing".
 *
 * A privacy policy is static text. It has no business depending on MySQL.
 * This file renders the complete legal set with zero dependencies: no config,
 * no database, no session, no partials. It always answers HTTP 200 with
 * index,follow, so the policies are permanently reachable and indexable.
 *
 * Used two ways:
 *   1. config.php falls back to it when the PDO connection throws.
 *   2. Directly, as /legal-static.php?p=privacy — a stable canary URL.
 *
 * The database-backed pages (about.php, privacy-policy.php, …) remain the
 * canonical versions; this is the safety net beneath them.
 */

if (!function_exists('legal_static_render')) {

/** Full legal content. Kept in one place so both callers stay in sync. */
function legal_static_pages(): array {
    $host  = $_SERVER['HTTP_HOST'] ?? 'yassota.com';
    $brand = 'yassota';
    $mail  = 'contact@yassota.com';
    $today = date('Y-m-d');

    return [
        'about' => [
            'nav'   => 'من نحن',
            'title' => 'من نحن — ' . $brand,
            'desc'  => 'تعرّف على منصة ' . $brand . ': فريقنا، رسالتنا، ومعايير المحتوى التي نلتزم بها في مراجعة التطبيقات ونشر الأدلة التقنية.',
            'body'  => '
<p><strong>' . $brand . '</strong> منصة عربية متخصصة في مراجعة التطبيقات، نشر الأدلة التقنية، وتوفير أدوات ويب مجانية تعمل مباشرة من المتصفح دون تثبيت.</p>

<h2>رسالتنا</h2>
<p>المحتوى التقني العربي يعاني من مشكلتين: الترجمة الآلية الركيكة، والمعلومات المنسوخة دون تحقّق. نكتب كل مراجعة وكل دليل على المنصة بالعربية أصالةً — لا ترجمة — وبعد تجربة فعلية للتطبيق أو الأداة محل الحديث.</p>

<h2>ماذا نقدّم</h2>
<ul>
  <li><strong>مراجعات التطبيقات:</strong> بطاقة تعريفية كاملة لكل تطبيق تشمل المطوّر، الإصدار، الحجم، متطلبات النظام، والمزايا والعيوب — مع رابط مباشر لصفحة التطبيق الرسمية على Google Play.</li>
  <li><strong>الأدلة والشروحات:</strong> مقالات خطوة بخطوة مدعومة بلقطات شاشة توضيحية.</li>
  <li><strong>أدوات الويب:</strong> أكثر من 100 أداة مجانية تعمل داخل المتصفح — تحويل الصيغ، الحسابات، معالجة النصوص وغيرها.</li>
  <li><strong>أدوات الذكاء الاصطناعي:</strong> مساعدات عربية للمحادثة والبحث والتحليل.</li>
</ul>

<h2>معايير المحتوى</h2>
<p>نلتزم بالمبادئ التالية في كل ما يُنشر على المنصة:</p>
<ul>
  <li><strong>لا استضافة لملفات مقرصنة.</strong> لا نستضيف نسخاً معدّلة أو مكركة من أي تطبيق. روابط التحميل تشير إلى المتاجر الرسمية أو مواقع المطوّرين.</li>
  <li><strong>فصل واضح بين المحتوى والإعلان.</strong> الإعلانات موسومة بوضوح ولا تُقدَّم في هيئة محتوى تحريري.</li>
  <li><strong>تصحيح الأخطاء علناً.</strong> عند اكتشاف خطأ في مقال منشور نصحّحه ونوثّق تاريخ التعديل.</li>
  <li><strong>استقلالية التقييم.</strong> لا نقبل مقابلاً مادياً لرفع تقييم تطبيق أو حذف مراجعة سلبية.</li>
</ul>

<h2>تواصل معنا</h2>
<p>لأي استفسار أو تصحيح أو طلب إزالة محتوى، راسلنا على <a href="mailto:' . $mail . '">' . $mail . '</a>. نردّ عادةً خلال 48 ساعة عمل.</p>',
        ],

        'privacy' => [
            'nav'   => 'سياسة الخصوصية',
            'title' => 'سياسة الخصوصية — ' . $brand,
            'desc'  => 'سياسة الخصوصية الكاملة لمنصة ' . $brand . ': البيانات التي نجمعها، كيفية استخدامها، ملفات تعريف الارتباط، إعلانات جوجل، وحقوقك.',
            'body'  => '
<p class="updated">آخر تحديث: ' . $today . '</p>

<p>توضّح هذه السياسة أنواع البيانات التي تُجمع عند استخدامك <strong>' . $brand . '</strong>، وكيف تُستخدم، ومن يمكنه الوصول إليها، والخيارات المتاحة لك للتحكم بها.</p>

<h2>1. البيانات التي نجمعها</h2>
<h3>بيانات تُقدّمها أنت</h3>
<ul>
  <li><strong>بيانات الحساب:</strong> عند التسجيل الاختياري نحفظ البريد الإلكتروني والاسم المعروض. كلمات المرور تُخزَّن مُجزَّأة (hashed) ولا يمكن استرجاعها كنص صريح — ولا نطّلع عليها.</li>
  <li><strong>رسائل التواصل:</strong> ما ترسله عبر نموذج التواصل يُحفظ لغرض الرد فقط.</li>
  <li><strong>محادثات أدوات الذكاء الاصطناعي:</strong> تُحفظ فقط عند تسجيل الدخول، ولعرض سجلّك الشخصي. الاستخدام دون تسجيل لا يُحفظ.</li>
</ul>

<h3>بيانات تُجمع تلقائياً</h3>
<ul>
  <li>عنوان IP، نوع المتصفح ونظام التشغيل، الصفحات المُزارة وأوقات الزيارة، والصفحة المُحيلة.</li>
  <li>تُستخدم هذه البيانات لأغراض إحصائية وأمنية (منع الإساءة وهجمات الحرمان من الخدمة).</li>
</ul>

<h2>2. أساس المعالجة والغرض منها</h2>
<p>نعالج بياناتك من أجل: تشغيل الموقع وتأمينه، تحسين المحتوى بناءً على الصفحات الأكثر زيارة، الرد على استفساراتك، وعرض إعلانات تموّل استمرار الخدمة مجاناً. لا نبيع بياناتك الشخصية لأي طرف ثالث.</p>

<h2>3. ملفات تعريف الارتباط (Cookies)</h2>
<p>نستخدم ثلاثة أنواع:</p>
<ul>
  <li><strong>ضرورية:</strong> لحفظ جلسة الدخول وتفضيل اللغة والوضع الليلي. لا يعمل الموقع بدونها.</li>
  <li><strong>تحليلية:</strong> لقياس عدد الزوار والصفحات الأكثر قراءة، بصيغة مجمّعة لا تحدّد هويتك.</li>
  <li><strong>إعلانية:</strong> تضعها شبكات الإعلان لعرض إعلانات أكثر ملاءمة وقياس أدائها.</li>
</ul>
<p>يمكنك حذف الكوكيز أو حظرها من إعدادات متصفحك في أي وقت. حظر الكوكيز الضرورية قد يعطّل تسجيل الدخول.</p>

<h2>4. إعلانات جوجل و AdSense</h2>
<p>قد نعرض إعلانات عبر شبكة <strong>Google AdSense</strong>. في هذه الحالة:</p>
<ul>
  <li>تستخدم جوجل — كطرف ثالث — ملفات تعريف ارتباط لعرض إعلانات مبنية على زياراتك السابقة لهذا الموقع ومواقع أخرى.</li>
  <li>يتيح استخدام <strong>ملف تعريف ارتباط DoubleClick DART</strong> لجوجل وشركائها عرض إعلانات مخصّصة لمستخدمي الموقع.</li>
  <li>يمكنك تعطيل الإعلانات المخصّصة من
      <a href="https://www.google.com/settings/ads" rel="nofollow noopener" target="_blank">إعدادات إعلانات جوجل</a>،
      أو الانسحاب من شبكات أخرى عبر
      <a href="https://www.aboutads.info/choices/" rel="nofollow noopener" target="_blank">aboutads.info</a>.</li>
  <li>لمعرفة كيفية تعامل جوجل مع بياناتك راجع
      <a href="https://policies.google.com/technologies/partner-sites" rel="nofollow noopener" target="_blank">سياسة جوجل للمواقع الشريكة</a>.</li>
</ul>

<h2>5. خدمات الطرف الثالث</h2>
<p>قد نستعين بمزوّدين خارجيين لتشغيل بعض الميزات (استضافة، تحليلات، مزوّدو نماذج الذكاء الاصطناعي). يصل هؤلاء إلى الحد الأدنى من البيانات اللازم لأداء وظيفتهم فقط، ويلتزمون بسياسات الخصوصية الخاصة بهم.</p>

<h2>6. مدة الاحتفاظ</h2>
<p>سجلات الزيارة تُحفظ حتى 12 شهراً ثم تُحذف أو تُجمَّع. بيانات الحساب تبقى ما دام الحساب قائماً، وتُحذف خلال 30 يوماً من طلب حذف الحساب.</p>

<h2>7. خصوصية الأطفال</h2>
<p>الموقع غير موجَّه لمن هم دون 13 عاماً، ولا نجمع عن قصد بيانات منهم. إذا علمت بوجود بيانات كهذه راسلنا وسنحذفها فوراً.</p>

<h2>8. حقوقك</h2>
<p>لك الحق في: الاطلاع على بياناتك، تصحيحها، طلب حذفها، الاعتراض على معالجتها، وسحب موافقتك على الكوكيز غير الضرورية. لممارسة أي منها راسلنا على <a href="mailto:' . $mail . '">' . $mail . '</a>.</p>

<h2>9. الأمان</h2>
<p>نستخدم اتصالاً مشفّراً (HTTPS) على كامل الموقع، ونُجزّئ كلمات المرور. ومع ذلك لا يمكن ضمان أمان مطلق لأي نقل عبر الإنترنت.</p>

<h2>10. تعديلات على هذه السياسة</h2>
<p>قد نحدّث هذه السياسة، ويُحدَّث تاريخ "آخر تحديث" أعلاه عند كل تعديل جوهري. استمرارك في استخدام الموقع بعد التعديل يُعدّ موافقة عليه.</p>',
        ],

        'terms' => [
            'nav'   => 'شروط الاستخدام',
            'title' => 'شروط الاستخدام — ' . $brand,
            'desc'  => 'الشروط والأحكام التي تحكم استخدامك لمنصة ' . $brand . ' ومحتواها وأدواتها.',
            'body'  => '
<p class="updated">آخر تحديث: ' . $today . '</p>
<p>باستخدامك <strong>' . $brand . '</strong> فإنك توافق على الشروط التالية. إن لم توافق عليها، يُرجى عدم استخدام الموقع.</p>

<h2>1. طبيعة الخدمة</h2>
<p>يقدّم الموقع محتوى معلوماتياً ومراجعات وأدوات ويب مجانية. المحتوى لأغراض تعليمية وإعلامية عامة، ولا يُعدّ استشارة مهنية متخصصة (قانونية أو مالية أو طبية).</p>

<h2>2. الاستخدام المقبول</h2>
<p>تلتزم بعدم:</p>
<ul>
  <li>استخدام الموقع في أي غرض غير قانوني أو للإضرار بالآخرين.</li>
  <li>محاولة اختراق الموقع أو تعطيله أو تجاوز حدود الاستخدام تقنياً.</li>
  <li>السحب الآلي المكثّف (scraping) للمحتوى دون إذن كتابي.</li>
  <li>إعادة نشر محتوى الموقع تجارياً دون إذن مسبق.</li>
</ul>

<h2>3. الملكية الفكرية</h2>
<p>المحتوى الأصلي المنشور على الموقع — النصوص والتصاميم — مملوك لـ ' . $brand . '. أسماء التطبيقات وشعاراتها وعلاماتها التجارية ملك لأصحابها، وتُستخدم على الموقع لغرض التعريف والمراجعة فقط ضمن حدود الاستخدام العادل.</p>

<h2>4. روابط التحميل والمواقع الخارجية</h2>
<p>لا نستضيف ملفات التطبيقات التجارية على خوادمنا. روابط التحميل تشير إلى <strong>Google Play</strong> أو مواقع المطوّرين الرسمية. لا نتحمّل مسؤولية محتوى المواقع الخارجية أو سياساتها.</p>

<h2>5. حسابات المستخدمين</h2>
<p>أنت مسؤول عن سرية بيانات دخولك وعن كل نشاط يجري عبر حسابك. يحق لنا تعليق أي حساب يخالف هذه الشروط.</p>

<h2>6. أدوات الذكاء الاصطناعي</h2>
<p>تُقدَّم مخرجات أدوات الذكاء الاصطناعي "كما هي". النماذج اللغوية قد تُنتج معلومات غير دقيقة، ويقع على المستخدم التحقّق منها قبل الاعتماد عليها في أي قرار.</p>

<h2>7. إخلاء المسؤولية</h2>
<p>يُقدَّم الموقع "كما هو" ودون ضمانات من أي نوع. لا نضمن خلوّه من الانقطاع أو الأخطاء، ولا نتحمّل مسؤولية أي أضرار مباشرة أو غير مباشرة ناتجة عن الاستخدام.</p>

<h2>8. تعديل الشروط</h2>
<p>قد نعدّل هذه الشروط في أي وقت مع تحديث تاريخ "آخر تحديث". استمرار الاستخدام بعد التعديل يُعدّ قبولاً به.</p>',
        ],

        'contact' => [
            'nav'   => 'تواصل معنا',
            'title' => 'تواصل معنا — ' . $brand,
            'desc'  => 'طرق التواصل مع فريق ' . $brand . ' للاستفسارات، التصحيحات، وطلبات إزالة المحتوى.',
            'body'  => '
<p>نرحّب بأسئلتك وملاحظاتك وتصحيحاتك. اختر القناة المناسبة لطلبك:</p>

<h2>البريد الإلكتروني</h2>
<p><a href="mailto:' . $mail . '">' . $mail . '</a> — القناة الرئيسية لجميع الاستفسارات. نردّ عادةً خلال 48 ساعة عمل.</p>

<h2>حسب نوع الطلب</h2>
<ul>
  <li><strong>تصحيح معلومة في مقال:</strong> أرسل رابط الصفحة، الفقرة المعنية، والمعلومة الصحيحة مع مصدرها إن أمكن.</li>
  <li><strong>طلب إزالة محتوى أو انتهاك حقوق نشر:</strong> راجع صفحة <a href="?p=dmca">إشعار DMCA</a> وأرسل البيانات المطلوبة كاملة.</li>
  <li><strong>خصوصية البيانات:</strong> لطلب نسخة من بياناتك أو حذفها، أرسل الطلب من البريد المسجّل في حسابك.</li>
  <li><strong>الإبلاغ عن مشكلة تقنية:</strong> صف المشكلة مع رابط الصفحة ونوع المتصفح والجهاز.</li>
  <li><strong>اقتراح تطبيق أو أداة:</strong> نرحّب بالاقتراحات، أرسل اسم التطبيق وسبب ترشيحه.</li>
</ul>

<h2>ما لا نقدّمه</h2>
<p>لا نوفّر دعماً فنياً للتطبيقات نفسها — لذلك تواصل مع مطوّر التطبيق مباشرة. ولا نقبل طلبات رفع التقييم أو حذف المراجعات مقابل مادي.</p>',
        ],

        'dmca' => [
            'nav'   => 'إشعار DMCA',
            'title' => 'سياسة حقوق النشر و DMCA — ' . $brand,
            'desc'  => 'إجراءات الإبلاغ عن انتهاك حقوق النشر على منصة ' . $brand . ' وآلية الاعتراض.',
            'body'  => '
<p class="updated">آخر تحديث: ' . $today . '</p>
<p>نحترم حقوق الملكية الفكرية ونستجيب للإشعارات الموثّقة وفق قانون الألفية الرقمية (DMCA).</p>

<h2>موقفنا من الملفات</h2>
<p><strong>لا نستضيف ملفات التطبيقات التجارية على خوادمنا.</strong> صفحات التطبيقات على الموقع صفحات تعريفية تتضمّن وصفاً ومراجعة، وروابط التحميل فيها تشير إلى Google Play أو الموقع الرسمي للمطوّر.</p>

<h2>كيفية إرسال إشعار انتهاك</h2>
<p>أرسل إلى <a href="mailto:' . $mail . '">' . $mail . '</a> رسالة تتضمّن العناصر التالية كاملةً:</p>
<ol>
  <li>توقيعك (إلكتروني أو مادي) بصفتك المالك أو المفوّض بالتصرف نيابة عنه.</li>
  <li>تحديد دقيق للعمل المحمي محل الانتهاك.</li>
  <li>الرابط الكامل (URL) للصفحة المخالفة على موقعنا.</li>
  <li>بيانات التواصل معك: الاسم، العنوان، البريد الإلكتروني، رقم الهاتف.</li>
  <li>إقرار بحسن النية بأن الاستخدام غير مصرّح به من المالك أو وكيله أو القانون.</li>
  <li>إقرار بأن المعلومات الواردة في الإشعار صحيحة، وبأنك مفوّض بالتصرف، تحت طائلة عقوبة الحنث باليمين.</li>
</ol>

<h2>إجراؤنا</h2>
<p>نراجع الإشعار خلال <strong>72 ساعة عمل</strong>. عند اكتماله واستيفائه للشروط، نزيل المحتوى أو نعطّل الوصول إليه ونُخطر ناشره.</p>

<h2>الاعتراض المضاد</h2>
<p>إن كنت ترى أن محتواك أُزيل خطأً، أرسل اعتراضاً مضاداً يتضمّن: توقيعك، تحديد المحتوى المُزال وموقعه قبل الإزالة، وإقرارك تحت طائلة عقوبة الحنث باليمين بأن الإزالة تمّت نتيجة خطأ أو سوء تحديد.</p>

<h2>المخالفات المتكرّرة</h2>
<p>نغلق حسابات المستخدمين الذين يثبت تكرار انتهاكهم لحقوق النشر.</p>',
        ],

        'cookies' => [
            'nav'   => 'سياسة الكوكيز',
            'title' => 'سياسة ملفات تعريف الارتباط — ' . $brand,
            'desc'  => 'شرح تفصيلي لملفات تعريف الارتباط المستخدمة على ' . $brand . ' وكيفية التحكم بها.',
            'body'  => '
<p class="updated">آخر تحديث: ' . $today . '</p>
<p>ملفات تعريف الارتباط ملفات نصية صغيرة يحفظها متصفحك عند زيارة موقع ما، وتُستخدم لتذكّر تفضيلاتك وقياس أداء الموقع.</p>

<h2>الأنواع المستخدمة على هذا الموقع</h2>

<h3>1. كوكيز ضرورية</h3>
<p>تحفظ جلسة تسجيل الدخول، اللغة المختارة، وتفضيل الوضع الليلي، إضافة إلى رمز الحماية من تزوير الطلبات (CSRF). لا يمكن تعطيلها لأن الموقع لا يعمل بدونها.</p>

<h3>2. كوكيز تحليلية</h3>
<p>تقيس عدد الزوار ومصادر الزيارة والصفحات الأكثر قراءة، بصيغة مجمّعة لا تُحدّد هوية الزائر. تساعدنا في معرفة أي المحتوى يستحق التوسّع.</p>

<h3>3. كوكيز إعلانية</h3>
<p>تضعها شبكات الإعلان — ومنها Google AdSense — لعرض إعلانات أكثر ملاءمة، ولمنع تكرار الإعلان نفسه، ولقياس فعاليته. تُدار وفق سياسات مزوّديها.</p>

<h2>كيف تتحكّم بها</h2>
<ul>
  <li><strong>من المتصفح:</strong> يتيح كل متصفح حديث حذف الكوكيز أو حظرها كلياً أو حظر كوكيز الطرف الثالث فقط، من الإعدادات ← الخصوصية.</li>
  <li><strong>الإعلانات المخصّصة:</strong> عطّلها من <a href="https://www.google.com/settings/ads" rel="nofollow noopener" target="_blank">إعدادات إعلانات جوجل</a> أو <a href="https://www.aboutads.info/choices/" rel="nofollow noopener" target="_blank">aboutads.info</a>.</li>
  <li><strong>وضع التصفح الخفي:</strong> يحذف الكوكيز تلقائياً عند إغلاق النافذة.</li>
</ul>
<p>ملاحظة: حظر الكوكيز الضرورية يمنع تسجيل الدخول وحفظ التفضيلات.</p>',
        ],

        'disclosure' => [
            'nav'   => 'الإفصاح الإعلاني',
            'title' => 'الإفصاح الإعلاني — ' . $brand,
            'desc'  => 'إفصاح شفّاف عن مصادر تمويل منصة ' . $brand . ' وعلاقتها بالمحتوى التحريري.',
            'body'  => '
<p class="updated">آخر تحديث: ' . $today . '</p>
<p>الشفافية شرط للثقة. هذه الصفحة توضّح كيف يُموَّل الموقع وأثر ذلك — أو انعدام أثره — على ما نكتب.</p>

<h2>مصادر الدخل</h2>
<ul>
  <li><strong>الإعلانات:</strong> نعرض إعلانات عبر شبكات إعلانية مثل Google AdSense. اختيار الإعلان يتم آلياً من الشبكة ولا نتحكّم بمحتواه إعلاناً بإعلان.</li>
  <li><strong>الاشتراكات المدفوعة:</strong> نوفّر اشتراكات اختيارية ترفع حدود استخدام أدوات الذكاء الاصطناعي. الاشتراك لا يغيّر المحتوى التحريري.</li>
</ul>

<h2>استقلالية المحتوى التحريري</h2>
<p>نلتزم بالتالي دون استثناء:</p>
<ul>
  <li>لا نقبل مقابلاً مادياً لرفع تقييم تطبيق أو لحذف مراجعة سلبية.</li>
  <li>المعلنون لا يطّلعون على المقالات قبل نشرها ولا يملكون حق الاعتراض عليها.</li>
  <li>أي محتوى مموّل — إن وُجد — يُوسَم صراحةً بعبارة "محتوى مموّل" في أعلى الصفحة.</li>
  <li>ترتيب التطبيقات في القوائم يعتمد على معايير معلنة (التقييم، عدد التحميلات، حداثة التحديث) لا على الدفع.</li>
</ul>

<h2>روابط الإحالة</h2>
<p>قد تحتوي بعض الصفحات على روابط إحالة نحصل منها على عمولة عند الشراء، دون أي تكلفة إضافية عليك. لا يؤثّر وجود رابط إحالة على تقييمنا للمنتج، ونُشير إلى ذلك عند وجوده.</p>

<h2>تعارض المصالح</h2>
<p>عند مراجعة منتج تربطنا به علاقة تجارية، نُفصح عن ذلك في مقدّمة المراجعة نفسها.</p>',
        ],
    ];
}

/**
 * Render one legal page as a complete standalone HTML document.
 * Emits HTTP 200 + index,follow — deliberately, even when the DB is down.
 */
function legal_static_render(string $key): void {
    $pages = legal_static_pages();
    $key   = array_key_exists($key, $pages) ? $key : 'about';
    $p     = $pages[$key];
    $esc   = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'yassota.com';
    $self   = $scheme . '://' . $host . '/legal-static.php?p=' . $key;

    if (!headers_sent()) {
        http_response_code(200);
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: public, max-age=1800');
        header('X-Robots-Tag: index, follow', true);
    }

    $schema = json_encode([
        '@context'      => 'https://schema.org',
        '@type'         => 'WebPage',
        'name'          => $p['title'],
        'description'   => $p['desc'],
        'url'           => $self,
        'inLanguage'    => 'ar',
        'isPartOf'      => ['@type' => 'WebSite', 'name' => 'yassota', 'url' => $scheme . '://' . $host . '/'],
        'dateModified'  => date('Y-m-d'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    echo '<!doctype html>', "\n";
    ?>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title><?= $esc($p['title']) ?></title>
<meta name="description" content="<?= $esc($p['desc']) ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="index,follow">
<link rel="canonical" href="<?= $esc($self) ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= $esc($p['title']) ?>">
<meta property="og:description" content="<?= $esc($p['desc']) ?>">
<meta property="og:url" content="<?= $esc($self) ?>">
<meta property="og:site_name" content="yassota">
<script type="application/ld+json"><?= $schema ?></script>
<style>
  :root{
    --bg:#fbfcfe; --surface:#fff; --line:#e4e9f2;
    --ink:#0f1a2e; --ink-soft:#3d4d68; --muted:#64748b;
    --accent:#1d4ed8; --accent-soft:rgba(29,78,216,.07);
  }
  @media (prefers-color-scheme:dark){
    :root:not([data-theme="light"]){
      --bg:#0a1020; --surface:#111a2e; --line:#213052;
      --ink:#eaf0fa; --ink-soft:#c2cee2; --muted:#8395b3;
      --accent:#60a5fa; --accent-soft:rgba(96,165,250,.1);
    }
  }
  :root[data-theme="dark"]{
    --bg:#0a1020; --surface:#111a2e; --line:#213052;
    --ink:#eaf0fa; --ink-soft:#c2cee2; --muted:#8395b3;
    --accent:#60a5fa; --accent-soft:rgba(96,165,250,.1);
  }
  *{box-sizing:border-box;margin:0;padding:0}
  html{scroll-behavior:smooth}
  body{
    background:var(--bg); color:var(--ink); direction:rtl;
    font-family:'Cairo','Tajawal',system-ui,-apple-system,'Segoe UI',Tahoma,Arial,sans-serif;
    line-height:1.85; -webkit-font-smoothing:antialiased;
  }
  header{
    background:var(--surface); border-bottom:1px solid var(--line);
    position:sticky; top:0; z-index:20;
  }
  .bar{
    max-width:860px; margin:0 auto; padding:13px 20px;
    display:flex; align-items:center; justify-content:space-between; gap:14px;
  }
  .brand{font-size:17px;font-weight:800;color:var(--ink);text-decoration:none;letter-spacing:-.3px}
  .brand span{color:var(--accent)}
  .home{font-size:13px;font-weight:600;color:var(--accent);text-decoration:none}
  .home:hover{text-decoration:underline}
  nav.tabs{border-bottom:1px solid var(--line);background:var(--surface)}
  .tabs-inner{
    max-width:860px;margin:0 auto;padding:0 20px 11px;
    display:flex;gap:7px;overflow-x:auto;-webkit-overflow-scrolling:touch;
  }
  .tabs-inner::-webkit-scrollbar{height:0}
  .tabs-inner a{
    flex-shrink:0;font-size:12.5px;font-weight:600;text-decoration:none;
    color:var(--muted);padding:6px 13px;border-radius:20px;
    border:1px solid transparent;transition:background .15s,color .15s;
  }
  .tabs-inner a:hover{color:var(--accent);background:var(--accent-soft)}
  .tabs-inner a[aria-current="page"]{
    color:var(--accent);background:var(--accent-soft);border-color:var(--line);
  }
  main{max-width:760px;margin:0 auto;padding:38px 20px 70px}
  h1{
    font-size:clamp(23px,4.5vw,31px);font-weight:800;line-height:1.35;
    letter-spacing:-.6px;margin-bottom:22px;text-wrap:balance;
  }
  h2{
    font-size:17px;font-weight:800;margin:34px 0 12px;
    padding-top:20px;border-top:1px solid var(--line);text-wrap:balance;
  }
  h2:first-of-type{border-top:none;padding-top:0;margin-top:26px}
  h3{font-size:14.5px;font-weight:700;margin:20px 0 8px;color:var(--ink-soft)}
  p{margin-bottom:14px;color:var(--ink-soft);font-size:15px}
  ul,ol{margin:0 0 16px;padding-inline-start:22px;color:var(--ink-soft);font-size:15px}
  li{margin-bottom:9px}
  li strong,p strong{color:var(--ink);font-weight:700}
  a{color:var(--accent)}
  .updated{
    display:inline-block;font-size:12px;font-weight:700;color:var(--muted);
    background:var(--accent-soft);padding:5px 13px;border-radius:20px;margin-bottom:22px;
  }
  footer{
    border-top:1px solid var(--line);background:var(--surface);
    padding:26px 20px;text-align:center;
  }
  .flinks{
    display:flex;flex-wrap:wrap;gap:8px 18px;justify-content:center;margin-bottom:14px;
  }
  .flinks a{font-size:12.5px;color:var(--muted);text-decoration:none;font-weight:600}
  .flinks a:hover{color:var(--accent)}
  .copy{font-size:11.5px;color:var(--muted)}
  @media (max-width:520px){ main{padding:28px 18px 56px} }
</style>
</head>
<body>

<header>
  <div class="bar">
    <a href="/" class="brand">yas<span>sota</span></a>
    <a href="/" class="home">← الموقع الرئيسي</a>
  </div>
</header>

<nav class="tabs" aria-label="الصفحات القانونية">
  <div class="tabs-inner">
    <?php foreach (legal_static_pages() as $k => $pg): ?>
      <a href="?p=<?= $esc($k) ?>"<?= $k === $key ? ' aria-current="page"' : '' ?>><?= $esc($pg['nav']) ?></a>
    <?php endforeach; ?>
  </div>
</nav>

<main>
  <h1><?= $esc($p['title']) ?></h1>
  <?= $p['body'] ?>
</main>

<footer>
  <div class="flinks">
    <?php foreach (legal_static_pages() as $k => $pg): ?>
      <a href="?p=<?= $esc($k) ?>"><?= $esc($pg['nav']) ?></a>
    <?php endforeach; ?>
  </div>
  <p class="copy">&copy; 2024–<?= date('Y') ?> yassota — جميع الحقوق محفوظة</p>
</footer>

</body>
</html>
<?php
}

} // end function_exists guard

/* ── Direct access: /legal-static.php?p=privacy ─────────────────── */
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'legal-static.php') {
    legal_static_render((string)($_GET['p'] ?? 'about'));
    exit;
}
