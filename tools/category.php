<?php
require_once __DIR__ . '/_base.php';
require_once dirname(__DIR__) . '/config.php';

$cat = preg_replace('/[^a-z0-9-]/', '', strtolower(trim($_GET['cat'] ?? '')));

/* ── Category registry ─────────────────────────────────────── */
$categories = [
    'ai' => [
        'name'  => 'أدوات الذكاء الاصطناعي',
        'name_en' => 'AI Tools',
        'desc'  => 'أدوات مدعومة بالذكاء الاصطناعي لكتابة المحتوى والترجمة والتلخيص وتوليد الأفكار — مجاناً بالعربية',
        'color' => '#7c3aed',
        'bg'    => '#ede9fe',
        'icon'  => '<path d="M12 2a2 2 0 012 2v2h3a1 1 0 011 1v10a1 1 0 01-1 1h-3v2a2 2 0 01-4 0v-2H7a1 1 0 01-1-1V7a1 1 0 011-1h3V4a2 2 0 012-2z"/>',
        'slugs' => ['ai-translator','ai-summarizer','ai-rewriter','ai-grammar','ai-code','ai-title','ai-seo',
                    'ai-email','ai-social','ai-ad-copy','ai-product-desc','ai-story','ai-poem','ai-speech',
                    'ai-cv','ai-meeting-notes','ai-lesson-plan','ai-quiz','ai-flashcards','ai-meal-plan',
                    'ai-explain','ai-debate','ai-contract','ai-image-desc','ai-hashtag-gen','ai-color-palette',
                    'ai-baby-names','ai-logo-ideas','ai-business-name','ai-text-to-sql'],
        'intro' => '<p>أدوات الذكاء الاصطناعي من yassota تستخدم أحدث نماذج اللغة الكبيرة (LLMs) عبر OpenRouter لتقديم مساعدة احترافية مجانية بالكامل. من الترجمة الفورية إلى كتابة المقالات والأكواد البرمجية، كل أداة مصممة خصيصاً لتلبي حاجة محددة بدقة واحترافية عالية.</p>
<p>سواء كنت كاتباً أو مطوراً أو رائد أعمال أو طالباً، ستجد هنا أداة تناسب احتياجاتك تماماً — بدون تسجيل، بدون دفع، وبدون حدود.</p>',
        'faq' => [
            ['q' => 'هل أدوات الذكاء الاصطناعي في yassota مجانية تماماً؟', 'a' => 'نعم، جميع أدوات الذكاء الاصطناعي في yassota مجانية 100% ولا تتطلب تسجيلاً أو بطاقة ائتمانية.'],
            ['q' => 'ما النماذج المستخدمة في أدوات الذكاء الاصطناعي؟', 'a' => 'نستخدم أفضل النماذج المجانية المتاحة عبر OpenRouter، وتتحول الأداة تلقائياً بين النماذج للحصول على أفضل نتيجة.'],
            ['q' => 'هل تدعم الأدوات اللغة العربية؟', 'a' => 'نعم، جميع أدوات الذكاء الاصطناعي مُحسَّنة للغة العربية وتقدم نتائج عالية الجودة باللغة العربية.'],
            ['q' => 'كيف تختلف هذه الأدوات عن ChatGPT؟', 'a' => 'أدواتنا متخصصة — كل أداة مُحسَّنة لمهمة واحدة بدقة، مما يجعل النتائج أكثر احترافية ومباشرة مقارنة بالمساعد العام.'],
            ['q' => 'هل يمكنني استخدام المحتوى المُنشأ تجارياً؟', 'a' => 'نعم، المحتوى الذي تولّده هو ملكك الكاملة ويمكنك استخدامه لأي غرض تجاري أو شخصي.'],
        ],
        'long' => '<h2>أدوات الذكاء الاصطناعي العربية — الدليل الشامل</h2>
<p>في عالم يتسارع فيه التحول الرقمي، أصبحت أدوات الذكاء الاصطناعي ضرورة لا رفاهية. yassota تقدم أكبر مجموعة من أدوات الذكاء الاصطناعي المجانية باللغة العربية في مكان واحد.</p>
<h3>أدوات الكتابة والمحتوى</h3>
<p>من أداة الترجمة الفورية التي تدعم أكثر من 50 لغة، إلى أداة إعادة الصياغة التي تحوّل النصوص العادية إلى محتوى احترافي — مجموعتنا تغطي كل احتياجات الكتابة الرقمية.</p>
<h3>أدوات المطورين والتقنية</h3>
<p>مولّد الأكواد البرمجية يكتب الكود بناءً على وصفك بالعربية أو الإنجليزية، فيما تحوّل أداة النص إلى SQL طلباتك اللغوية إلى استعلامات قاعدة بيانات دقيقة.</p>
<h3>أدوات الأعمال والتسويق</h3>
<p>من كتابة وصف المنتج الاحترافي للمتاجر الإلكترونية، إلى توليد الهاشتاقات الأكثر انتشاراً للسوشيال ميديا — أدوات yassota تختصر ساعات العمل في ثوانٍ.</p>',
    ],
    'pdf' => [
        'name'  => 'أدوات PDF',
        'name_en' => 'PDF Tools',
        'desc'  => 'دمج وتقسيم وضغط ملفات PDF وتحويلها — أدوات PDF مجانية تعمل في المتصفح بدون رفع',
        'color' => '#dc2626',
        'bg'    => '#fee2e2',
        'icon'  => '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/>',
        'slugs' => ['pdf-merge','pdf-split','pdf-compress','pdf-to-word','word-to-pdf','pdf-to-jpg','jpg-to-pdf','pdf-unlock'],
        'intro' => '<p>أدوات PDF من yassota تعمل مباشرة في متصفحك — ملفاتك لا تُرفع إلى أي سيرفر خارجي، ما يضمن خصوصية تامة وأماناً كاملاً لمستنداتك الحساسة.</p>
<p>سواء أردت دمج عدة ملفات PDF في ملف واحد، أو تقسيم ملف كبير إلى أجزاء، أو ضغطه لتقليل حجمه دون فقدان الجودة — كل ذلك في ثوانٍ ومجاناً.</p>',
        'faq' => [
            ['q' => 'هل أدوات PDF تعمل بدون رفع الملفات للإنترنت؟', 'a' => 'نعم، أدوات PDF في yassota تعمل محلياً في متصفحك باستخدام JavaScript. ملفاتك لا تغادر جهازك أبداً.'],
            ['q' => 'ما الحجم الأقصى لملفات PDF المدعومة؟', 'a' => 'تدعم الأدوات ملفات PDF حتى 100 ميغابايت في معظم المتصفحات الحديثة.'],
            ['q' => 'هل يمكن معالجة ملفات PDF المحمية بكلمة مرور؟', 'a' => 'أداة إلغاء قفل PDF تتيح رفع الملفات المحمية وإدخال كلمة المرور لمعالجتها.'],
            ['q' => 'هل تحافظ الأدوات على جودة النص والصور في PDF؟', 'a' => 'نعم، جميع العمليات تحافظ على جودة المحتوى الأصلي مع خيارات لضبط مستوى الضغط عند الحاجة.'],
        ],
        'long' => '<h2>أدوات PDF المجانية — كل ما تحتاجه في مكان واحد</h2>
<p>PDF هو الصيغة الأكثر استخداماً للمستندات الرسمية والتقارير والكتب الإلكترونية. تعامل معها باحترافية عبر أدوات yassota المجانية.</p>
<h3>دمج ملفات PDF</h3>
<p>أداة دمج PDF تتيح لك ضم ملفين أو أكثر في ملف واحد بترتيب الصفحات الذي تختاره، مثالية لتجميع تقارير أو عروض تقديمية.</p>
<h3>تقسيم ملفات PDF</h3>
<p>قسّم ملف PDF الكبير إلى أجزاء بحسب عدد الصفحات، أو استخرج صفحات محددة منه بسهولة تامة.</p>
<h3>ضغط ملفات PDF</h3>
<p>قلّل حجم ملفات PDF بنسبة تصل إلى 80% مع الحفاظ على وضوح النص والصور، مثالية للمشاركة عبر البريد الإلكتروني.</p>',
    ],
    'network' => [
        'name'  => 'أدوات الشبكات',
        'name_en' => 'Network Tools',
        'desc'  => 'أدوات فحص الشبكات والـ IP والدومين والـ DNS — معلومات فورية لكل مواقع ويب',
        'color' => '#0891b2',
        'bg'    => '#e0f2fe',
        'icon'  => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>',
        'slugs' => ['ip-lookup','whois','dns-lookup','ip-subnet','speed-test','ping','ssl-checker','port-scanner'],
        'intro' => '<p>أدوات شبكات yassota توفر معلومات فورية ودقيقة عن أي عنوان IP أو نطاق ويب — من الموقع الجغرافي إلى بيانات WHOIS وسجلات DNS.</p>
<p>سواء كنت مديراً لشبكات، مطوراً ويب، أو تبحث عن معلومات أمان الشبكة — هذه الأدوات توفر بياناتك الدقيقة في ثوانٍ.</p>',
        'faq' => [
            ['q' => 'ما الفرق بين IP Lookup وWHOIS؟', 'a' => 'IP Lookup يكشف الموقع الجغرافي ومزود الخدمة لعنوان IP، بينما WHOIS يعطي معلومات تسجيل النطاق كالمالك وتاريخ الانتهاء.'],
            ['q' => 'هل يمكن معرفة موقع أي IP في العالم؟', 'a' => 'نعم، أداة IP Lookup تحدد الموقع الجغرافي التقريبي لأي عنوان IP مع مزود الخدمة ومعلومات الشبكة.'],
            ['q' => 'كيف يمكن فحص سجلات DNS لنطاق ما؟', 'a' => 'أداة DNS Lookup تستعلم عن جميع سجلات DNS للنطاق (A, AAAA, MX, CNAME, TXT, NS) في ثوانٍ.'],
            ['q' => 'هل أداة فحص سرعة الإنترنت دقيقة؟', 'a' => 'نعم، تستخدم خوادم متعددة لقياس سرعة التحميل والرفع وزمن الاستجابة ping بدقة عالية.'],
        ],
        'long' => '<h2>أدوات الشبكات المتقدمة — للمحترفين والمبتدئين</h2>
<p>إدارة الشبكات وفهم البنية التحتية للإنترنت لم تكن أسهل من أي وقت مضى بفضل أدوات yassota المجانية.</p>
<h3>فحص عناوين IP</h3>
<p>أداة IP Lookup تكشف الموقع الجغرافي الدقيق، مزود خدمة الإنترنت (ISP)، نوع الاتصال، وحالة القائمة السوداء لأي عنوان IP.</p>
<h3>WHOIS واستعلام النطاقات</h3>
<p>تعرّف على مالك أي نطاق ويب، تاريخ التسجيل والانتهاء، خوادم DNS المرتبطة، وجهة الاتصال التقنية.</p>
<h3>DNS Lookup متقدم</h3>
<p>استعلم عن جميع سجلات DNS لأي نطاق مع إمكانية اختيار خادم DNS محدد للاستعلام (Google, Cloudflare, OpenDNS).</p>
<h3>حساب شبكات IP (Subnet Calculator)</h3>
<p>احسب تفاصيل شبكة IP الفرعية من CIDR notation — عدد العناوين، نطاقها، عنوان البث، القناع.</p>',
    ],
    'image' => [
        'name'  => 'أدوات الصور',
        'name_en' => 'Image Tools',
        'desc'  => 'ضغط وتحويل وتغيير حجم الصور — أدوات صور مجانية تعمل في المتصفح بدون رفع',
        'color' => '#059669',
        'bg'    => '#d1fae5',
        'icon'  => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>',
        'slugs' => ['compress','resize','qr-code','image-to-base64','svg-optimizer','favicon-gen','color-picker','color-contrast','pixel-ruler'],
        'intro' => '<p>أدوات الصور في yassota تعمل محلياً في متصفحك — لا رفع، لا انتظار، لا خصوصية مُخترَقة. ملفاتك تبقى على جهازك بينما تُعالَج بالكامل في المتصفح.</p>',
        'faq' => [
            ['q' => 'هل أدوات الصور تدعم WebP؟', 'a' => 'نعم، أداة ضغط الصور تحول تلقائياً إلى صيغة WebP لأفضل ضغط مع أعلى جودة.'],
            ['q' => 'ما الصيغ المدعومة لأدوات الصور؟', 'a' => 'ندعم JPG, PNG, WebP, GIF, SVG, ICO وصيغ أخرى حسب كل أداة.'],
            ['q' => 'هل توجد حدود لحجم الصورة؟', 'a' => 'تدعم معظم الأدوات صوراً حتى 20 ميغابايت. لا قيود على الأبعاد.'],
        ],
        'long' => '<h2>أدوات معالجة الصور المجانية</h2>
<p>من ضغط صور المتجر الإلكتروني لتسريع الموقع، إلى توليد رموز QR احترافية، يجد مصمم الجرافيك والمطور وصاحب المتجر في yassota كل ما يحتاجه.</p>
<h3>ضغط الصور</h3>
<p>قلّل حجم صورك بنسبة تصل إلى 90% مع الحفاظ على الجودة البصرية — مثالي لتسريع مواقع الويب وتحسين Core Web Vitals.</p>
<h3>تغيير حجم الصور</h3>
<p>غيّر أبعاد أي صورة بدقة مع الحفاظ على نسبة العرض إلى الارتفاع، أو حدد الأبعاد يدوياً.</p>
<h3>مولّد QR Code</h3>
<p>أنشئ رموز QR لأي رابط أو نص أو رقم هاتف — مع خيارات تخصيص الألوان والحجم والتنزيل بصيغ متعددة.</p>',
    ],
    'developer' => [
        'name'  => 'أدوات المطورين',
        'name_en' => 'Developer Tools',
        'desc'  => 'أدوات برمجية للمطورين — JSON وCSS وJS وSQL وCRON وHTAccess وJWT وأكثر',
        'color' => '#d97706',
        'bg'    => '#fef3c7',
        'icon'  => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
        'slugs' => ['json-fmt','css-minifier','js-minifier','html-to-md','csv-to-json','json-to-csv','sql-fmt',
                    'epoch-converter','cron-gen','jwt-decode','htaccess-gen','robots-gen','sitemap-gen','xml-fmt','url-shortener'],
        'intro' => '<p>مجموعة أدوات المطورين في yassota توفر أكثر الأدوات البرمجية استخداماً في مكان واحد — من تنسيق JSON وضغط CSS وJS، إلى فك تشفير JWT وتوليد ملفات .htaccess.</p>',
        'faq' => [
            ['q' => 'هل أدوات المطورين تعمل بدون إنترنت؟', 'a' => 'معظم أدوات المطورين تعمل محلياً في المتصفح باستخدام JavaScript ولا تحتاج لإرسال بيانات للخادم.'],
            ['q' => 'هل أداة فك تشفير JWT آمنة؟', 'a' => 'نعم، تعمل محلياً 100% — لا يُرسَل الـ token لأي خادم. فك التشفير يتم في متصفحك مباشرة.'],
            ['q' => 'هل يمكن ضغط ملفات CSS وJS كبيرة؟', 'a' => 'نعم، الأدوات تدعم ملفات بأي حجم — الحد العملي هو ذاكرة المتصفح.'],
        ],
        'long' => '<h2>أدوات المطورين الأساسية — أسرع بدون Postman أو VS Code</h2>
<p>للمطور السريع الذي يريد نتيجة فورية بدون فتح IDE أو تثبيت إضافات — yassota توفر أشهر الأدوات البرمجية مباشرة في المتصفح.</p>
<h3>أدوات JSON</h3>
<p>نسّق JSON المضغوط إلى شكل قابل للقراءة، أو اضغطه لتقليل الحجم، أو حوّله إلى CSV — كل ذلك مع التحقق التلقائي من صحة البنية.</p>
<h3>ضغط CSS وJavaScript</h3>
<p>احصل على ملفات CSS وJS مضغوطة في ثوانٍ — يقلل الضغط حجم الملفات بنسبة 30-60%، مما يسرّع تحميل صفحاتك ويحسن Core Web Vitals.</p>
<h3>أدوات HTAccess وRobots وSitemap</h3>
<p>أنشئ ملفات .htaccess احترافية بضغطة واحدة، أو اكتب robots.txt مثالياً، أو أنشئ sitemap.xml لموقعك.</p>
<h3>Epoch Time Converter</h3>
<p>حوّل Unix Timestamp إلى تاريخ ووقت مقروء والعكس — مع دعم المناطق الزمنية المختلفة.</p>',
    ],
    'text' => [
        'name'  => 'أدوات النصوص',
        'name_en' => 'Text Tools',
        'desc'  => 'أدوات معالجة النصوص — عدّ الكلمات والمقارنة وتحويل الحالة والمزيد',
        'color' => '#6d28d9',
        'bg'    => '#ede9fe',
        'icon'  => '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>',
        'slugs' => ['word-count','text-case','lorem','text-diff-merge','markdown-to-html','html-to-md','char-map',
                    'speech-to-text','text-to-speech'],
        'intro' => '<p>أدوات النصوص في yassota تسرّع معالجة النصوص اليومية — من عدّ الكلمات والأحرف، إلى مقارنة نسختين من نص وإيجاد الفروقات، إلى تحويل النصوص بين صيغ مختلفة.</p>',
        'faq' => [
            ['q' => 'هل أداة عدّ الكلمات تدعم العربية؟', 'a' => 'نعم، تعدّ الكلمات والأحرف والجمل والفقرات بدقة للنصوص العربية والإنجليزية وجميع اللغات.'],
            ['q' => 'هل أداة مقارنة النصوص تظهر الفروقات بوضوح؟', 'a' => 'نعم، تظهر الإضافات باللون الأخضر والحذوفات باللون الأحمر بشكل مرئي واضح.'],
        ],
        'long' => '<h2>أدوات معالجة النصوص الأساسية</h2>
<p>من كتّاب المحتوى إلى المترجمين والمحررين والمطورين — أدوات النصوص في yassota تختصر الوقت وتزيد الدقة.</p>
<h3>عداد الكلمات والأحرف</h3>
<p>احصل فوراً على عدد الكلمات والأحرف والجمل والفقرات والوقت المقدر للقراءة — مثالي للكتّاب الذين يعملون بحدود محددة.</p>
<h3>مقارنة النصوص</h3>
<p>قارن نسختين من أي نص وشاهد الفروقات بشكل مرئي ملوّن — مثالي للمراجعة والتدقيق اللغوي.</p>
<h3>تحويل حالة الأحرف</h3>
<p>حوّل النص بين حالات متعددة: كبير كل الأحرف، صغير كل الأحرف، بداية كل كلمة بحرف كبير، وغيرها.</p>',
    ],
    'security' => [
        'name'  => 'أدوات الأمان',
        'name_en' => 'Security Tools',
        'desc'  => 'مولّد كلمات المرور وفحص قوتها وهاش التشفير وفحص SSL — أمان رقمي مجاني',
        'color' => '#16a34a',
        'bg'    => '#dcfce7',
        'icon'  => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'slugs' => ['password-gen','password-strength','hash-gen','ssl-checker'],
        'intro' => '<p>الأمان الرقمي ضرورة في عالم اليوم. أدوات الأمان في yassota تساعدك على إنشاء كلمات مرور قوية، فحص مستوى أمان كلمات مرورك الحالية، وتشفير البيانات بأحدث الخوارزميات.</p>',
        'faq' => [
            ['q' => 'هل مولّد كلمات المرور آمن؟', 'a' => 'نعم، يعمل محلياً في متصفحك ويستخدم Web Crypto API للتوليد العشوائي الآمن. لا كلمة مرور تُرسَل لأي سيرفر.'],
            ['q' => 'ما خوارزميات التشفير المدعومة في Hash Generator؟', 'a' => 'ندعم MD5, SHA-1, SHA-256, SHA-512, SHA-3 وBCrypt.'],
            ['q' => 'كيف يعمل فحص SSL؟', 'a' => 'يتحقق من صحة شهادة SSL للنطاق، تاريخ الانتهاء، سلسلة الثقة، والبروتوكولات المدعومة.'],
        ],
        'long' => '<h2>أدوات الأمان الرقمي المجانية</h2>
<p>حماية بياناتك ومعلوماتك الرقمية تبدأ بخطوات بسيطة — كلمات مرور قوية وفريدة لكل حساب، ومعرفة مستوى تشفير مواقعك.</p>
<h3>مولّد كلمات المرور</h3>
<p>أنشئ كلمات مرور عشوائية وقوية بطول وتعقيد تختاره أنت — مع خيار تضمين حروف كبيرة وصغيرة وأرقام ورموز خاصة.</p>
<h3>فاحص قوة كلمة المرور</h3>
<p>اختبر مدى قوة كلمة مرورك الحالية — يقيّم الطول والتعقيد والأنماط الشائعة ويعطيك توصيات للتحسين.</p>
<h3>مولّد Hash</h3>
<p>حوّل أي نص إلى hash مشفّر بخوارزميات متعددة — مثالي للمطورين لتخزين كلمات المرور والتحقق من سلامة الملفات.</p>',
    ],
    'seo' => [
        'name'  => 'أدوات السيو',
        'name_en' => 'SEO Tools',
        'desc'  => 'أدوات تحسين محركات البحث — Meta Tags وSitemap وRobots وكثافة الكلمات المفتاحية',
        'color' => '#0284c7',
        'bg'    => '#e0f2fe',
        'icon'  => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        'slugs' => ['meta-gen','keyword-density','domain-age','sitemap-gen','robots-gen','ai-seo','ai-title'],
        'intro' => '<p>أدوات السيو في yassota تساعدك على تحسين ظهور موقعك في محركات البحث — من توليد Meta Tags احترافية، إلى تحليل كثافة الكلمات المفتاحية، إلى إنشاء Sitemap وRobots.txt مثالي.</p>',
        'faq' => [
            ['q' => 'كيف تساعد Meta Tags في ظهور الموقع؟', 'a' => 'Meta Title وMeta Description هما أول ما يراه المستخدم في نتائج البحث — صياغتهما جيداً تزيد نسبة النقر (CTR).'],
            ['q' => 'ما أداة تحليل كثافة الكلمات المفتاحية؟', 'a' => 'تحلل النص وتحسب نسبة تكرار كل كلمة مفتاحية — النسبة المثلى 1-3% لكل كلمة مفتاحية رئيسية.'],
        ],
        'long' => '<h2>أدوات السيو الأساسية لتصدر نتائج البحث</h2>
<p>تحسين محركات البحث (SEO) عملية متكاملة تبدأ بأساسيات صحيحة. yassota يوفر الأدوات التقنية لضمان تهيئة موقعك بشكل مثالي.</p>
<h3>مولّد Meta Tags</h3>
<p>أنشئ Meta Title وMeta Description وOpen Graph tags وTwitter Card tags بالصيغة الصحيحة لكل صفحة من صفحات موقعك.</p>
<h3>كثافة الكلمات المفتاحية</h3>
<p>حلّل أي نص وتعرّف على نسبة تكرار كل كلمة مفتاحية — تجنب حشو الكلمات المفتاحية الذي يُعاقب عليه Google.</p>
<h3>مولّد Sitemap وRobots</h3>
<p>أنشئ ملف sitemap.xml لموقعك بصيغة XML صحيحة، وملف robots.txt يتحكم بزحف محركات البحث بدقة.</p>',
    ],
];

if (!$cat || !isset($categories[$cat])) {
    // Show all categories listing
    $pageTitle = 'تصفح أدوات yassota حسب الفئة | أدوات ويب وذكاء اصطناعي مجانية';
    $pageDesc  = 'استعرض مجموعة أدوات yassota مرتبة حسب الفئة: أدوات الذكاء الاصطناعي، PDF، الشبكات، الصور، المطورين، السيو، الأمان والنصوص — مجانية 100%';
    $schema = json_encode(['@context'=>'https://schema.org','@type'=>'WebPage','name'=>$pageTitle,'url'=>TOOLS_BASE_URL.'/tools/category/','description'=>$pageDesc], JSON_UNESCAPED_UNICODE);
    tool_head($pageTitle, $pageDesc, $schema);
    tool_header();
    ?>
    <main class="t-main">
      <div class="t-hero">
        <h1>تصفح الأدوات حسب الفئة</h1>
        <p>أكثر من 65 أداة مجانية مرتبة حسب الفئة — اختر ما يناسب احتياجاتك</p>
      </div>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;margin-bottom:24px">
        <?php foreach ($categories as $slug => $c): ?>
        <a href="<?= TOOLS_BASE_URL ?>/tools/category/<?= $slug ?>/" style="text-decoration:none">
          <div class="t-card" style="margin:0;height:100%;cursor:pointer;transition:transform .18s,box-shadow .18s"
               onmouseenter="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.1)'"
               onmouseleave="this.style.transform='';this.style.boxShadow=''">
            <div style="width:48px;height:48px;border-radius:12px;background:<?= $c['bg'] ?>;display:flex;align-items:center;justify-content:center;margin-bottom:12px">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="<?= $c['color'] ?>" stroke-width="2"><?= $c['icon'] ?></svg>
            </div>
            <div style="font-weight:700;font-size:15px;color:#0f172a;margin-bottom:6px"><?= htmlspecialchars($c['name']) ?></div>
            <div style="font-size:12px;color:#64748b;line-height:1.6"><?= htmlspecialchars($c['desc']) ?></div>
            <div style="margin-top:12px;font-size:12px;font-weight:700;color:<?= $c['color'] ?>"><?= count($c['slugs']) ?> أداة ←</div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php tool_ad(); ?>
    </main>
    <?php tool_footer(); return;
}

/* ── Fetch tools for this category ───────────────────────────── */
$c = $categories[$cat];
$placeholders = implode(',', array_fill(0, count($c['slugs']), '?'));
try {
    $stmt = $pdo->prepare("SELECT slug, name, short_description, icon_svg, icon_color, icon_bg, views
                           FROM web_tools WHERE slug IN ($placeholders) AND status='published'
                           ORDER BY FIELD(slug, " . $placeholders . ")");
    $stmt->execute(array_merge($c['slugs'], $c['slugs']));
    $tools = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {
    $tools = [];
}

// Fetch 4 popular apps for cross-promotion
try {
    $appStmt = $pdo->prepare("SELECT name, slug, icon_url FROM apps WHERE status='published' ORDER BY views DESC LIMIT 4");
    $appStmt->execute();
    $relatedApps = $appStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {
    $relatedApps = [];
}

/* ── SEO ──────────────────────────────────────────────────────── */
$canonical  = TOOLS_BASE_URL . '/tools/category/' . $cat . '/';
$pageTitle  = $c['name'] . ' المجانية | ' . count($tools) . ' أداة مجانية بدون تسجيل — yassota';
$pageDesc   = $c['desc'] . ' — ' . count($tools) . ' أداة مجانية تعمل في المتصفح بدون تسجيل.';

$faqItems = $c['faq'] ?? [];
$schemaLD = [
    [
        '@context' => 'https://schema.org',
        '@type'    => 'CollectionPage',
        'name'     => $c['name'],
        'url'      => $canonical,
        'description' => $pageDesc,
        'inLanguage' => 'ar',
    ],
    [
        '@context' => 'https://schema.org',
        '@type'    => 'BreadcrumbList',
        'itemListElement' => [
            ['@type'=>'ListItem','position'=>1,'name'=>'yassota','item'=>MAIN_SITE_URL],
            ['@type'=>'ListItem','position'=>2,'name'=>'الأدوات','item'=>TOOLS_BASE_URL.'/tools/'],
            ['@type'=>'ListItem','position'=>3,'name'=>$c['name'],'item'=>$canonical],
        ],
    ],
];
if ($faqItems) {
    $schemaLD[] = [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => array_map(fn($f) => [
            '@type' => 'Question',
            'name'  => $f['q'],
            'acceptedAnswer' => ['@type'=>'Answer','text'=>$f['a']],
        ], $faqItems),
    ];
}
$schemaJson = json_encode($schemaLD, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

tool_head($pageTitle, $pageDesc, $schemaJson, $c['color']);
tool_header();
?>
<main class="t-main">

  <!-- breadcrumb -->
  <nav aria-label="breadcrumb" style="font-size:12px;color:#64748b;margin-bottom:12px">
    <a href="<?= MAIN_SITE_URL ?>">yassota</a> ›
    <a href="<?= TOOLS_BASE_URL ?>/tools/">الأدوات</a> ›
    <span><?= htmlspecialchars($c['name']) ?></span>
  </nav>

  <div class="t-hero">
    <div class="t-hero-icon" style="background:<?= $c['bg'] ?>">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="<?= $c['color'] ?>" stroke-width="2"><?= $c['icon'] ?></svg>
    </div>
    <h1><?= htmlspecialchars($c['name']) ?></h1>
    <p><?= htmlspecialchars($c['desc']) ?></p>
    <div style="margin-top:8px;font-size:13px;color:#64748b"><?= count($tools) ?> أداة مجانية متاحة الآن</div>
  </div>

  <?php if ($c['intro'] ?? ''): ?>
  <div class="t-card" style="font-size:15px;line-height:1.9;color:#374151">
    <?= $c['intro'] ?>
  </div>
  <?php endif; ?>

  <!-- Tools grid -->
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;margin-bottom:24px">
    <?php if ($tools): foreach ($tools as $t):
      $toolUrl = TOOLS_BASE_URL . '/tools/' . $t['slug'] . '/';
    ?>
    <a href="<?= htmlspecialchars($toolUrl) ?>" style="text-decoration:none;display:block">
      <div class="t-card" style="margin:0;height:100%;cursor:pointer;transition:transform .18s,box-shadow .18s"
           onmouseenter="this.style.transform='translateY(-3px)';this.style.boxShadow='0 6px 20px rgba(0,0,0,.1)'"
           onmouseleave="this.style.transform='';this.style.boxShadow=''">
        <div style="width:40px;height:40px;border-radius:10px;background:<?= htmlspecialchars($t['icon_bg']??'#f1f5f9') ?>;display:flex;align-items:center;justify-content:center;margin-bottom:10px">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="<?= htmlspecialchars($t['icon_color']??'#64748b') ?>" stroke-width="2"><?= $t['icon_svg'] ?></svg>
        </div>
        <div style="font-size:14px;font-weight:700;color:#0f172a;margin-bottom:5px"><?= htmlspecialchars($t['name']) ?></div>
        <div style="font-size:12px;color:#64748b;line-height:1.6"><?= htmlspecialchars($t['short_description']??'') ?></div>
        <div style="margin-top:10px;font-size:11px;font-weight:700;color:<?= $c['color'] ?>">جرّب الآن ←</div>
      </div>
    </a>
    <?php endforeach; else: ?>
    <div class="t-card" style="grid-column:1/-1;text-align:center;color:#64748b">
      <p>قريباً — جاري إضافة أدوات هذه الفئة</p>
    </div>
    <?php endif; ?>
  </div>

  <?php tool_ad(); ?>

  <!-- Long description & FAQ -->
  <?php if ($c['long'] ?? ''): ?>
  <div class="t-card" style="font-size:15px;line-height:1.9;color:#374151">
    <?= $c['long'] ?>
  </div>
  <?php endif; ?>

  <?php if ($faqItems): ?>
  <div class="t-card">
    <h2 style="margin-top:0;font-size:18px">الأسئلة الشائعة حول <?= htmlspecialchars($c['name']) ?></h2>
    <?php foreach ($faqItems as $i => $fq): ?>
    <details <?= $i === 0 ? 'open' : '' ?> style="border-bottom:1px solid #f1f5f9;padding:12px 0">
      <summary style="font-weight:700;cursor:pointer;list-style:none;display:flex;justify-content:space-between;gap:8px">
        <span><?= htmlspecialchars($fq['q']) ?></span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </summary>
      <p style="margin:8px 0 0;color:#64748b;line-height:1.7"><?= htmlspecialchars($fq['a']) ?></p>
    </details>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Related apps -->
  <?php if ($relatedApps): ?>
  <div class="t-card">
    <h3 style="margin-top:0">تطبيقات أندرويد شائعة</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px">
      <?php foreach ($relatedApps as $a): ?>
      <a href="<?= MAIN_SITE_URL ?>/<?= htmlspecialchars($a['slug']) ?>" style="text-decoration:none;display:flex;align-items:center;gap:8px;padding:10px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0">
        <?php if ($a['icon_url']): ?>
        <img src="<?= htmlspecialchars($a['icon_url']) ?>" alt="<?= htmlspecialchars($a['name']) ?>" width="32" height="32" style="border-radius:8px;object-fit:cover" loading="lazy">
        <?php endif; ?>
        <span style="font-size:12px;font-weight:600;color:#0f172a"><?= htmlspecialchars($a['name']) ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Other categories -->
  <div class="t-card">
    <h3 style="margin-top:0">استعرض فئات أخرى</h3>
    <div style="display:flex;flex-wrap:wrap;gap:8px">
      <?php foreach ($categories as $slug => $oc):
        if ($slug === $cat) continue; ?>
      <a href="<?= TOOLS_BASE_URL ?>/tools/category/<?= $slug ?>/"
         style="text-decoration:none;padding:7px 14px;background:<?= $oc['bg'] ?>;color:<?= $oc['color'] ?>;border-radius:20px;font-size:13px;font-weight:600">
        <?= htmlspecialchars($oc['name']) ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

</main>
<?php tool_footer(); ?>
