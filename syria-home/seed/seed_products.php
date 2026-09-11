<?php
/**
 * Store packages: social-growth services (Instagram/Facebook/YouTube/Telegram
 * followers, likes, views, premium) + digital security tools.
 *
 * Copywriting note: growth numbers describe delivery targets, not a promise
 * that a platform's own algorithm will keep them static forever — that is
 * each platform's decision, not ours. What we do promise is what we
 * actually control: activation after payment confirmation, no password
 * requests, and a clear refund policy (see refund-policy.php).
 */
function seed_products(PDO $pdo): void {
    $P = [];

    $P[] = [
        'name' => 'متابعين إنستقرام حقيقيين', 'platform' => 'instagram',
        'type' => 'متابعين', 'icon' => 'fa-brands fa-instagram', 'art' => 'p1',
        'price' => 4.50, 'compare' => 7.00, 'badge' => 'الأكثر طلباً', 'featured' => 1,
        'tagline' => '1000 متابع حقيقي، تسليم تدريجي وبدون كلمة مرور حسابك.',
        'short' => 'متابعين حقيقيين لحساب إنستقرام، تسليم تدريجي طبيعي يحافظ على أمان حسابك، بدون طلب كلمة المرور أبداً.',
        'features' => "تسليم تدريجي طبيعي (ليس دفعة واحدة)\nحسابات نشطة وليست فارغة\nلا حاجة لكلمة مرور حسابك — فقط اسم المستخدم\nدعم عبر تيليجرام أو المساعد الذكي\nإمكانية زيادة الكمية لاحقاً بسعر أفضل",
        'includes' => "1000 متابع\nتفعيل خلال 24 ساعة من تأكيد الدفع\nدعم بعد الطلب\nسياسة استرجاع عند عدم التسليم",
        'meta_desc' => 'متابعين إنستقرام حقيقيين، تسليم تدريجي، دفع فوري بالعملات الرقمية.',
        'meta_kw' => 'متابعين انستقرام, زيادة متابعين انستقرام, متابعين حقيقيين',
    ];
    $P[] = [
        'name' => 'إعجابات إنستقرام فورية', 'platform' => 'instagram',
        'type' => 'إعجابات', 'icon' => 'fa-brands fa-instagram', 'art' => 'p2',
        'price' => 2.50, 'compare' => 4.00, 'badge' => '', 'featured' => 0,
        'tagline' => '1000 إعجاب فوري على آخر منشور لك.',
        'short' => 'إعجابات فورية على منشورك، مناسبة لرفع نسبة التفاعل بسرعة بعد النشر.',
        'features' => "تسليم سريع خلال ساعات\nمن حسابات نشطة\nيرفع نسبة التفاعل على المنشور\nيدعم أي منشور عام",
        'includes' => "1000 إعجاب\nتفعيل خلال 12 ساعة\nدعم بعد الطلب",
        'meta_desc' => 'إعجابات إنستقرام فورية وحقيقية، تفعيل سريع بعد الدفع.',
        'meta_kw' => 'اعجابات انستقرام, زيادة تفاعل انستقرام',
    ];
    $P[] = [
        'name' => 'مشاهدات ريلز إنستقرام', 'platform' => 'instagram',
        'type' => 'مشاهدات', 'icon' => 'fa-brands fa-instagram', 'art' => 'p3',
        'price' => 3.00, 'compare' => 5.00, 'badge' => '', 'featured' => 0,
        'tagline' => '5000 مشاهدة لفيديو Reels لتحسين ظهوره في الاستكشاف.',
        'short' => 'مشاهدات لفيديو الريلز تساعد على تحسين ظهوره في صفحة الاستكشاف والتوصيات.',
        'features' => "5000 مشاهدة تدريجية\nيدعم أي فيديو ريلز عام\nيحسّن فرصة الظهور في الاستكشاف\nتسليم يبدأ فوراً بعد الدفع",
        'includes' => "5000 مشاهدة\nبدء التسليم فوراً\nدعم بعد الطلب",
        'meta_desc' => 'مشاهدات ريلز إنستقرام لتحسين الظهور في صفحة الاستكشاف.',
        'meta_kw' => 'مشاهدات ريلز, مشاهدات انستقرام',
    ];

    $P[] = [
        'name' => 'متابعين صفحة فيسبوك', 'platform' => 'facebook',
        'type' => 'متابعين', 'icon' => 'fa-brands fa-facebook', 'art' => 'p4',
        'price' => 5.00, 'compare' => 8.00, 'badge' => '', 'featured' => 1,
        'tagline' => '1000 متابع لصفحتك التجارية أو الشخصية على فيسبوك.',
        'short' => 'متابعين لصفحة فيسبوك التجارية أو الشخصية، مفيد لبناء ثقة أولية لصفحتك.',
        'features' => "1000 متابع تدريجي\nيدعم الصفحات التجارية والشخصية\nلا حاجة لصلاحيات إدارة — فقط رابط الصفحة\nدعم بعد الطلب",
        'includes' => "1000 متابع\nتفعيل خلال 24-48 ساعة\nدعم بعد الطلب",
        'meta_desc' => 'متابعين فيسبوك حقيقيين لصفحتك التجارية أو الشخصية.',
        'meta_kw' => 'متابعين فيسبوك, زيادة متابعين صفحة فيسبوك',
    ];
    $P[] = [
        'name' => 'إعجابات منشور فيسبوك', 'platform' => 'facebook',
        'type' => 'إعجابات', 'icon' => 'fa-brands fa-facebook', 'art' => 'p5',
        'price' => 2.00, 'compare' => 3.50, 'badge' => '', 'featured' => 0,
        'tagline' => '500 إعجاب على منشور واحد من اختيارك.',
        'short' => 'إعجابات على منشور محدد لرفع تفاعله الأولي.',
        'features' => "500 إعجاب\nتسليم سريع\nيدعم أي منشور عام\nيرفع نسبة التفاعل الأولي",
        'includes' => "500 إعجاب\nتفعيل خلال 24 ساعة\nدعم بعد الطلب",
        'meta_desc' => 'إعجابات فيسبوك سريعة وحقيقية على منشور محدد.',
        'meta_kw' => 'اعجابات فيسبوك, زيادة تفاعل فيسبوك',
    ];

    $P[] = [
        'name' => 'مشتركين يوتيوب', 'platform' => 'youtube',
        'type' => 'مشتركين', 'icon' => 'fa-brands fa-youtube', 'art' => 'p6',
        'price' => 9.00, 'compare' => 14.00, 'badge' => 'الأفضل قيمة', 'featured' => 1,
        'tagline' => '500 مشترك حقيقي مع ثبات جيد بمرور الوقت.',
        'short' => 'مشتركين لقناتك على يوتيوب، تسليم تدريجي يحافظ على معدل ثبات جيد.',
        'features' => "500 مشترك تدريجي\nثبات جيد بمرور الوقت\nلا حاجة لصلاحيات القناة — فقط رابطها\nدعم بعد الطلب",
        'includes' => "500 مشترك\nتفعيل خلال 24-72 ساعة\nدعم بعد الطلب\nسياسة استرجاع عند عدم التسليم",
        'meta_desc' => 'مشتركين يوتيوب حقيقيين بثبات جيد، دفع فوري بالعملات الرقمية.',
        'meta_kw' => 'مشتركين يوتيوب, زيادة مشتركين يوتيوب',
    ];
    $P[] = [
        'name' => 'مشاهدات يوتيوب طويلة المدة', 'platform' => 'youtube',
        'type' => 'مشاهدات', 'icon' => 'fa-brands fa-youtube', 'art' => 'p7',
        'price' => 6.00, 'compare' => 10.00, 'badge' => '', 'featured' => 0,
        'tagline' => '5000 مشاهدة طويلة المدة تساعد على تحسين ترتيب الفيديو.',
        'short' => 'مشاهدات لفيديو يوتيوب من اختيارك، مصممة لتحسين وقت المشاهدة وترتيب الفيديو في نتائج البحث.',
        'features' => "5000 مشاهدة تدريجية\nتحسّن وقت المشاهدة الكلي للفيديو\nيدعم أي فيديو عام\nبدء التسليم خلال ساعات",
        'includes' => "5000 مشاهدة\nبدء التسليم خلال 24 ساعة\nدعم بعد الطلب",
        'meta_desc' => 'مشاهدات يوتيوب طويلة المدة لتحسين ترتيب الفيديو في نتائج البحث.',
        'meta_kw' => 'مشاهدات يوتيوب, زيادة مشاهدات يوتيوب',
    ];

    $P[] = [
        'name' => 'أعضاء قناة تيليجرام', 'platform' => 'telegram',
        'type' => 'أعضاء', 'icon' => 'fa-brands fa-telegram', 'art' => 'p8',
        'price' => 4.00, 'compare' => 6.50, 'badge' => '', 'featured' => 1,
        'tagline' => '1000 عضو حقيقي لقناتك أو مجموعتك على تيليجرام.',
        'short' => 'أعضاء لقناتك أو مجموعتك على تيليجرام، تسليم تدريجي وآمن.',
        'features' => "1000 عضو تدريجي\nيدعم القنوات والمجموعات العامة\nلا حاجة لصلاحيات إدارية — فقط رابط الدعوة\nدعم بعد الطلب",
        'includes' => "1000 عضو\nتفعيل خلال 24-48 ساعة\nدعم بعد الطلب",
        'meta_desc' => 'أعضاء تيليجرام حقيقيون لقناتك أو مجموعتك، دفع فوري بالعملات الرقمية.',
        'meta_kw' => 'اعضاء تيليجرام, زيادة اعضاء قناة تيليجرام',
    ];
    $P[] = [
        'name' => 'تيليجرام بريميوم 3 أشهر', 'platform' => 'telegram',
        'type' => 'اشتراك مميز', 'icon' => 'fa-brands fa-telegram', 'art' => 'p9',
        'price' => 15.00, 'compare' => 22.00, 'badge' => 'جديد', 'featured' => 1,
        'tagline' => 'اشتراك Telegram Premium الرسمي لمدة 3 أشهر.',
        'short' => 'اشتراك Telegram Premium الرسمي — رفع سرعات التحميل، ملصقات وتعبيرات حصرية، وميزات إضافية لمدة 3 أشهر كاملة.',
        'features' => "تفعيل مباشر على حسابك عبر رمز الهدية الرسمي\nسرعات تحميل ورفع أعلى\nملصقات وتعبيرات (Emoji) حصرية\nإزالة الإعلانات من القنوات العامة\n3 أشهر كاملة من التفعيل",
        'includes' => "3 أشهر Telegram Premium\nتفعيل خلال 24 ساعة من تأكيد الدفع\nدعم بعد الطلب",
        'meta_desc' => 'اشتراك Telegram Premium الرسمي لمدة 3 أشهر، دفع فوري بالعملات الرقمية.',
        'meta_kw' => 'تيليجرام بريميوم, telegram premium, اشتراك تيليجرام مميز',
    ];

    $P[] = [
        'name' => 'اشتراك VPN سنوي', 'platform' => 'security',
        'type' => 'أداة حماية', 'icon' => 'fa-solid fa-shield-halved', 'art' => 'p10',
        'price' => 25.00, 'compare' => 40.00, 'badge' => '', 'featured' => 1,
        'tagline' => 'تصفح آمن ومشفّر على كل أجهزتك لمدة سنة كاملة.',
        'short' => 'اشتراك VPN موثوق لتشفير اتصالك بالإنترنت وحماية بياناتك على الشبكات العامة، لمدة سنة كاملة على عدة أجهزة.',
        'features' => "تشفير كامل لاتصال الإنترنت\nيدعم عدة أجهزة (موبايل وكمبيوتر)\nخوادم في عدة دول\nسرعة عالية بدون حدود بيانات\nدعم فني عند التفعيل",
        'includes' => "سنة كاملة من التفعيل\nتعليمات تفعيل خطوة بخطوة\nدعم بعد الطلب",
        'meta_desc' => 'اشتراك VPN سنوي لتصفح آمن ومشفّر على جميع أجهزتك.',
        'meta_kw' => 'vpn, اشتراك في بي ان, حماية رقمية, تصفح آمن',
    ];
    $P[] = [
        'name' => 'مدير كلمات مرور مشفّر', 'platform' => 'security',
        'type' => 'أداة حماية', 'icon' => 'fa-solid fa-key', 'art' => 'p1',
        'price' => 18.00, 'compare' => 30.00, 'badge' => '', 'featured' => 0,
        'tagline' => 'حفظ ومزامنة كلمات المرور بتشفير كامل لسنة كاملة.',
        'short' => 'أداة موثوقة لحفظ كلمات المرور ومزامنتها بين أجهزتك بتشفير من طرف إلى طرف، مع مولّد كلمات مرور قوية.',
        'features' => "تشفير من طرف إلى طرف\nمزامنة بين الموبايل والكمبيوتر\nمولّد كلمات مرور قوية تلقائياً\nتنبيه عند تسريب كلمة مرور\nسنة كاملة من التفعيل",
        'includes' => "سنة كاملة من التفعيل\nتعليمات تفعيل خطوة بخطوة\nدعم بعد الطلب",
        'meta_desc' => 'مدير كلمات مرور مشفّر لحماية حساباتك، دفع فوري بالعملات الرقمية.',
        'meta_kw' => 'مدير كلمات مرور, حماية حسابات, تشفير كلمات المرور',
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO products
        (name, slug, tagline, product_type, platform, icon_class, art_key, price, compare_at_price, currency, badge,
         short_description, full_description, features, includes_list, meta_title, meta_description, meta_keywords,
         status, featured, sort_order)
        VALUES (:name,:slug,:tagline,:product_type,:platform,:icon_class,:art_key,:price,:compare_at_price,'USD',:badge,
         :short_description,:full_description,:features,:includes_list,:meta_title,:meta_description,:meta_keywords,
         'published',:featured,:sort_order)");

    $order = 0;
    foreach ($P as $p) {
        $full = '<h2>ما تحصل عليه</h2><p>' . htmlspecialchars($p['short'], ENT_QUOTES, 'UTF-8') . '</p>'
              . '<h2>الدعم والضمان</h2>'
              . '<p>كل طلب يشمل تفعيلاً بعد تأكيد الدفع، دعماً مباشراً عبر تيليجرام أو المساعد الذكي على الموقع، '
              . 'وسياسة استرجاع واضحة إذا لم تصل الخدمة كما هو موصوف.</p>'
              . '<p><strong>ملاحظة صادقة:</strong> باقات المتابعين والمشاهدات والأعضاء تعتمد على نمو تدريجي حسب سياسات '
              . 'كل منصة اجتماعية بذاتها — نحن لا نتحكم بتلك السياسات ولا نعِد بنتائج تتجاوزها، ونصف كل باقة بدقة '
              . 'حتى تعرف بالضبط ما تطلبه.</p>';

        $stmt->execute([
            'name' => $p['name'],
            'slug' => slugify($p['name']),
            'tagline' => $p['tagline'],
            'product_type' => $p['type'],
            'platform' => $p['platform'],
            'icon_class' => $p['icon'],
            'art_key' => $p['art'],
            'price' => $p['price'],
            'compare_at_price' => $p['compare'],
            'badge' => $p['badge'],
            'short_description' => $p['short'],
            'full_description' => $full,
            'features' => $p['features'],
            'includes_list' => $p['includes'],
            'meta_title' => $p['name'] . ' — طلب فوري',
            'meta_description' => $p['meta_desc'],
            'meta_keywords' => $p['meta_kw'],
            'featured' => $p['featured'],
            'sort_order' => $order++,
        ]);
    }
}
