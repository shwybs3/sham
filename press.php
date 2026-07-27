<?php
/* ═══════════════════════════════════════════
   YASSOTA PRESS PRO — /press
   Plugin management system (50 plugins)
   ═══════════════════════════════════════════ */
require_once __DIR__ . '/config.php';
require_admin();

/* ─── Plugin helpers ─────────────────────── */
function pp_get(PDO $pdo, string $slug, string $key, string $default = ''): string {
    return get_cfg($pdo, "press_{$slug}_{$key}", $default);
}
function pp_set(PDO $pdo, string $slug, string $key, string $val): void {
    set_cfg($pdo, "press_{$slug}_{$key}", $val);
}
function pp_active(PDO $pdo, string $slug): bool {
    return get_cfg($pdo, "press_{$slug}_active", '0') === '1';
}
function pp_toggle(PDO $pdo, string $slug, bool $on): void {
    set_cfg($pdo, "press_{$slug}_active", $on ? '1' : '0');
}

/* ─── Plugin registry ────────────────────── */
$PLUGINS = [
  /* ── SEO ── */
  ['slug'=>'yai-seo-pro','name'=>'YAI Rank Match SEO','cat'=>'seo','icon'=>'🎯',
   'desc'=>'محلل SEO شامل لكل تطبيق — نقاط، توصيات، وإصلاح تلقائي بالذكاء الاصطناعي',
   'version'=>'2.1.0','author'=>'Yassota','featured'=>true,
   'settings'=>[
     ['key'=>'auto_meta','label'=>'توليد وصف تلقائي بالذكاء الاصطناعي','type'=>'toggle','default'=>'1'],
     ['key'=>'min_content','label'=>'الحد الأدنى لطول المحتوى (حرف)','type'=>'number','default'=>'800'],
     ['key'=>'keyword_density','label'=>'كثافة الكلمات المفتاحية المستهدفة (%)','type'=>'number','default'=>'2'],
     ['key'=>'score_threshold','label'=>'درجة التحذير (أقل من)','type'=>'number','default'=>'70'],
   ]],
  ['slug'=>'schema-builder','name'=>'Schema Pro Builder','cat'=>'seo','icon'=>'🏗️',
   'desc'=>'JSON-LD متقدم: SoftwareApplication، FAQPage، BreadcrumbList، VideoObject تلقائياً',
   'version'=>'1.5.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'faq_auto','label'=>'توليد FAQ تلقائي من المحتوى','type'=>'toggle','default'=>'1'],
     ['key'=>'breadcrumb','label'=>'تفعيل Breadcrumb Schema','type'=>'toggle','default'=>'1'],
     ['key'=>'video_schema','label'=>'VideoObject لصور الشاشة','type'=>'toggle','default'=>'0'],
   ]],
  ['slug'=>'sitemap-pro','name'=>'XML Sitemap Pro','cat'=>'seo','icon'=>'🗺️',
   'desc'=>'خرائط موقع متقدمة مع دعم الصور والفيديو والأخبار وإرسال تلقائي لـ Google',
   'version'=>'1.8.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'include_images','label'=>'تضمين صور التطبيقات','type'=>'toggle','default'=>'1'],
     ['key'=>'include_categories','label'=>'تضمين صفحات التصنيفات','type'=>'toggle','default'=>'1'],
     ['key'=>'ping_google','label'=>'إرسال تلقائي لـ Google عند التحديث','type'=>'toggle','default'=>'1'],
     ['key'=>'ping_bing','label'=>'إرسال تلقائي لـ Bing','type'=>'toggle','default'=>'0'],
     ['key'=>'changefreq','label'=>'تكرار التغيير الافتراضي','type'=>'select','default'=>'weekly',
      'options'=>['always'=>'دائماً','hourly'=>'كل ساعة','daily'=>'يومياً','weekly'=>'أسبوعياً','monthly'=>'شهرياً']],
   ]],
  ['slug'=>'opengraph-pro','name'=>'Open Graph Pro','cat'=>'seo','icon'=>'📱',
   'desc'=>'إدارة كاملة لـ OG + Twitter Cards + WhatsApp Preview لكل صفحة',
   'version'=>'1.3.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'og_type','label'=>'نوع OG الافتراضي','type'=>'select','default'=>'website',
      'options'=>['website'=>'Website','article'=>'Article']],
     ['key'=>'twitter_card','label'=>'نوع Twitter Card','type'=>'select','default'=>'summary_large_image',
      'options'=>['summary'=>'Summary','summary_large_image'=>'Large Image']],
     ['key'=>'fb_app_id','label'=>'Facebook App ID','type'=>'text','default'=>''],
   ]],
  ['slug'=>'redirect-manager','name'=>'Redirect Manager','cat'=>'seo','icon'=>'↩️',
   'desc'=>'إدارة إعادة التوجيه 301/302 مع استيراد CSV وتتبع النقرات',
   'version'=>'1.2.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'auto_redirect','label'=>'إعادة توجيه تلقائية عند تغيير الـ slug','type'=>'toggle','default'=>'1'],
     ['key'=>'track_hits','label'=>'تتبع عدد النقرات على كل مسار','type'=>'toggle','default'=>'1'],
   ]],
  ['slug'=>'link-checker','name'=>'Broken Link Fixer','cat'=>'seo','icon'=>'🔗',
   'desc'=>'اكتشاف الروابط المعطوبة وإصلاحها تلقائياً أو تحويلها لمسودة',
   'version'=>'1.1.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'check_interval','label'=>'فترة الفحص (ساعات)','type'=>'number','default'=>'24'],
     ['key'=>'auto_draft','label'=>'تحويل التطبيق لمسودة عند فشل رابط التحميل','type'=>'toggle','default'=>'1'],
     ['key'=>'notify_yai','label'=>'إشعار YAI عند اكتشاف رابط معطوب','type'=>'toggle','default'=>'1'],
   ]],
  ['slug'=>'keyword-tracker','name'=>'Keyword Density Analyzer','cat'=>'seo','icon'=>'🔍',
   'desc'=>'تحليل كثافة الكلمات المفتاحية في محتوى التطبيقات مع توصيات AI',
   'version'=>'1.0.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'target_density','label'=>'الكثافة المستهدفة (%)','type'=>'number','default'=>'2'],
     ['key'=>'max_density','label'=>'الحد الأقصى قبل التحذير (%)','type'=>'number','default'=>'5'],
   ]],
  ['slug'=>'meta-ai','name'=>'Meta Tags AI Generator','cat'=>'seo','icon'=>'🤖',
   'desc'=>'توليد عناوين ووصف SEO تلقائي لكل تطبيق باستخدام الذكاء الاصطناعي',
   'version'=>'1.4.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'title_max','label'=>'الحد الأقصى لطول العنوان','type'=>'number','default'=>'60'],
     ['key'=>'desc_max','label'=>'الحد الأقصى لطول الوصف','type'=>'number','default'=>'160'],
     ['key'=>'auto_on_create','label'=>'توليد تلقائي عند إنشاء تطبيق جديد','type'=>'toggle','default'=>'1'],
   ]],
  ['slug'=>'canonical-manager','name'=>'Canonical URL Manager','cat'=>'seo','icon'=>'🔖',
   'desc'=>'إدارة Canonical Tags لمنع تكرار المحتوى وتحسين ترتيب الموقع',
   'version'=>'1.0.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'force_https','label'=>'إجبار HTTPS في الـ canonical','type'=>'toggle','default'=>'1'],
     ['key'=>'no_trailing_slash','label'=>'حذف / من نهاية URLs','type'=>'toggle','default'=>'1'],
   ]],
  ['slug'=>'cwv-monitor','name'=>'Core Web Vitals Monitor','cat'=>'seo','icon'=>'⚡',
   'desc'=>'مراقبة LCP، FID، CLS مع تقارير أسبوعية وتوصيات للتحسين',
   'version'=>'1.0.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'report_email','label'=>'البريد الإلكتروني للتقارير','type'=>'email','default'=>''],
     ['key'=>'report_weekly','label'=>'تقرير أسبوعي تلقائي','type'=>'toggle','default'=>'1'],
   ]],

  /* ── Analytics ── */
  ['slug'=>'google-analytics','name'=>'Google Analytics Pro','cat'=>'analytics','icon'=>'📊',
   'desc'=>'تكامل GA4 مع لوحة تحكم مدمجة: مستخدمون، جلسات، صفحات شائعة، مصادر الزيارات',
   'version'=>'2.0.0','author'=>'Yassota','featured'=>true,
   'settings'=>[
     ['key'=>'measurement_id','label'=>'GA4 Measurement ID (G-XXXXXXXX)','type'=>'text','default'=>''],
     ['key'=>'api_secret','label'=>'GA4 API Secret (للتقارير)','type'=>'password','default'=>''],
     ['key'=>'track_downloads','label'=>'تتبع نقرات التحميل كـ Event','type'=>'toggle','default'=>'1'],
     ['key'=>'track_search','label'=>'تتبع البحث الداخلي','type'=>'toggle','default'=>'1'],
     ['key'=>'anonymize_ip','label'=>'إخفاء هوية IP','type'=>'toggle','default'=>'1'],
   ]],
  ['slug'=>'search-console','name'=>'Search Console Connect','cat'=>'analytics','icon'=>'🔎',
   'desc'=>'ربط Google Search Console — كلمات مفتاحية، نقرات، ظهور، ومتوسط الترتيب',
   'version'=>'1.5.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'property_url','label'=>'URL موقعك في Search Console','type'=>'text','default'=>''],
     ['key'=>'service_account','label'=>'Service Account JSON','type'=>'textarea','default'=>''],
   ]],
  ['slug'=>'adsense-pro','name'=>'AdSense Manager Pro','cat'=>'analytics','icon'=>'💰',
   'desc'=>'Google AdSense OAuth — وحدات إعلانية، إيرادات، ظهور، وتحسين بالذكاء الاصطناعي',
   'version'=>'1.8.0','author'=>'Yassota','featured'=>true,
   'settings'=>[
     ['key'=>'client_id','label'=>'Google OAuth Client ID','type'=>'text','default'=>''],
     ['key'=>'client_secret','label'=>'Google OAuth Client Secret','type'=>'password','default'=>''],
     ['key'=>'publisher_id','label'=>'AdSense Publisher ID (pub-xxxxxxxx)','type'=>'text','default'=>''],
     ['key'=>'access_token','label'=>'Access Token (تلقائي بعد الربط)','type'=>'readonly','default'=>''],
     ['key'=>'refresh_token','label'=>'Refresh Token (تلقائي)','type'=>'readonly','default'=>''],
   ]],
  ['slug'=>'pixel-manager','name'=>'Pixel & Tag Manager','cat'=>'analytics','icon'=>'🎯',
   'desc'=>'إدارة بيكسل Facebook، TikTok، Snapchat، Pinterest من مكان واحد',
   'version'=>'1.2.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'fb_pixel','label'=>'Facebook Pixel ID','type'=>'text','default'=>''],
     ['key'=>'tiktok_pixel','label'=>'TikTok Pixel ID','type'=>'text','default'=>''],
     ['key'=>'snap_pixel','label'=>'Snapchat Pixel ID','type'=>'text','default'=>''],
     ['key'=>'pinterest_tag','label'=>'Pinterest Tag ID','type'=>'text','default'=>''],
     ['key'=>'custom_head','label'=>'كود مخصص في <head>','type'=>'textarea','default'=>''],
   ]],
  ['slug'=>'heatmap-pro','name'=>'Heatmap Tracker','cat'=>'analytics','icon'=>'🔥',
   'desc'=>'خرائط حرارية للنقرات والتمرير — اكتشف ما يجذب زوارك فعلاً',
   'version'=>'1.0.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'track_clicks','label'=>'تتبع النقرات','type'=>'toggle','default'=>'1'],
     ['key'=>'track_scroll','label'=>'تتبع التمرير','type'=>'toggle','default'=>'1'],
     ['key'=>'sample_rate','label'=>'نسبة التتبع (%)','type'=>'number','default'=>'100'],
   ]],
  ['slug'=>'utm-builder','name'=>'UTM Campaign Builder','cat'=>'analytics','icon'=>'🔗',
   'desc'=>'بناء روابط UTM لحملاتك التسويقية وتتبع مصادر الزيارات بدقة',
   'version'=>'1.0.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'default_source','label'=>'المصدر الافتراضي','type'=>'text','default'=>'yassota'],
     ['key'=>'auto_social','label'=>'إضافة UTM تلقائي لروابط السوشيال','type'=>'toggle','default'=>'1'],
   ]],
  ['slug'=>'conversion-tracker','name'=>'Conversion Tracker','cat'=>'analytics','icon'=>'🎪',
   'desc'=>'تتبع أهداف التحويل: تحميلات، نقرات، وقت القراءة، تسجيلات',
   'version'=>'1.1.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'track_download','label'=>'تتبع التحميلات كهدف','type'=>'toggle','default'=>'1'],
     ['key'=>'track_playstore','label'=>'تتبع نقرات Play Store','type'=>'toggle','default'=>'1'],
     ['key'=>'min_read_time','label'=>'وقت القراءة المستهدف (ثانية)','type'=>'number','default'=>'30'],
   ]],
  ['slug'=>'ab-tester','name'=>'A/B Testing Engine','cat'=>'analytics','icon'=>'⚖️',
   'desc'=>'اختبار A/B للعناوين والأوصاف وأزرار التحميل لزيادة معدل التحويل',
   'version'=>'1.0.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'enabled','label'=>'تفعيل الاختبارات','type'=>'toggle','default'=>'0'],
     ['key'=>'split_ratio','label'=>'نسبة المجموعة B (%)','type'=>'number','default'=>'50'],
   ]],

  /* ── Content AI ── */
  ['slug'=>'ai-writer','name'=>'AI Content Pro','cat'=>'content','icon'=>'✍️',
   'desc'=>'كتابة محتوى احترافي بالذكاء الاصطناعي — 1500-3000 كلمة لكل تطبيق',
   'version'=>'2.2.0','author'=>'Yassota','featured'=>true,
   'settings'=>[
     ['key'=>'min_words','label'=>'الحد الأدنى للكلمات','type'=>'number','default'=>'800'],
     ['key'=>'max_words','label'=>'الحد الأقصى للكلمات','type'=>'number','default'=>'2000'],
     ['key'=>'tone','label'=>'أسلوب الكتابة','type'=>'select','default'=>'professional',
      'options'=>['professional'=>'احترافي','casual'=>'عادي','technical'=>'تقني','marketing'=>'تسويقي']],
     ['key'=>'language','label'=>'لغة المحتوى','type'=>'select','default'=>'ar',
      'options'=>['ar'=>'العربية','en'=>'English','ar_en'=>'عربي + إنجليزي']],
     ['key'=>'include_faq','label'=>'إضافة قسم FAQ تلقائياً','type'=>'toggle','default'=>'1'],
   ]],
  ['slug'=>'content-scheduler','name'=>'Content Scheduler','cat'=>'content','icon'=>'📅',
   'desc'=>'جدولة نشر وإلغاء نشر التطبيقات تلقائياً بتاريخ ووقت محدد',
   'version'=>'1.3.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'timezone','label'=>'المنطقة الزمنية','type'=>'text','default'=>'Asia/Riyadh'],
     ['key'=>'notify_before','label'=>'إشعار قبل النشر بـ (دقيقة)','type'=>'number','default'=>'60'],
   ]],
  ['slug'=>'content-templates','name'=>'Content Templates','cat'=>'content','icon'=>'📋',
   'desc'=>'قوالب محتوى قابلة لإعادة الاستخدام لأنواع مختلفة من التطبيقات',
   'version'=>'1.1.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'default_template','label'=>'القالب الافتراضي','type'=>'select','default'=>'app',
      'options'=>['app'=>'تطبيق','game'=>'لعبة','tool'=>'أداة','security'=>'أمان']],
   ]],
  ['slug'=>'translator','name'=>'Auto Translator Pro','cat'=>'content','icon'=>'🌐',
   'desc'=>'ترجمة محتوى التطبيقات تلقائياً بالذكاء الاصطناعي لأكثر من 20 لغة',
   'version'=>'1.0.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'target_langs','label'=>'اللغات المستهدفة (مفصولة بفاصلة)','type'=>'text','default'=>'en'],
     ['key'=>'auto_translate','label'=>'ترجمة تلقائية عند نشر تطبيق جديد','type'=>'toggle','default'=>'0'],
   ]],
  ['slug'=>'content-duplicator','name'=>'Content Duplicator','cat'=>'content','icon'=>'📑',
   'desc'=>'استنساخ التطبيقات مع تعديل المحتوى تلقائياً لتجنب التكرار',
   'version'=>'1.2.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'auto_rewrite','label'=>'إعادة كتابة المحتوى المستنسخ بالذكاء الاصطناعي','type'=>'toggle','default'=>'1'],
     ['key'=>'suffix','label'=>'لاحقة العنوان المستنسخ','type'=>'text','default'=>' (نسخة)'],
   ]],
  ['slug'=>'version-tracker','name'=>'Version History Pro','cat'=>'content','icon'=>'📜',
   'desc'=>'تتبع تاريخ إصدارات التطبيقات مع سجل التغييرات والمقارنة بين الإصدارات',
   'version'=>'1.4.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'max_versions','label'=>'أقصى عدد إصدارات للحفظ','type'=>'number','default'=>'10'],
     ['key'=>'auto_changelog','label'=>'توليد changelog بالذكاء الاصطناعي','type'=>'toggle','default'=>'1'],
   ]],
  ['slug'=>'content-refresher','name'=>'Auto Content Refresher','cat'=>'content','icon'=>'🔄',
   'desc'=>'تحديث المحتوى القديم تلقائياً بالذكاء الاصطناعي لتحسين ترتيب البحث',
   'version'=>'1.0.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'age_days','label'=>'تحديث المحتوى الأقدم من (يوم)','type'=>'number','default'=>'90'],
     ['key'=>'auto_refresh','label'=>'تحديث تلقائي دوري','type'=>'toggle','default'=>'0'],
   ]],
  ['slug'=>'quality-scorer','name'=>'Content Quality Scorer','cat'=>'content','icon'=>'⭐',
   'desc'=>'تقييم جودة المحتوى: قراءة، أصالة، SEO، إشراك — مع تقرير مفصل',
   'version'=>'1.0.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'min_score','label'=>'الحد الأدنى لدرجة النشر','type'=>'number','default'=>'60'],
     ['key'=>'block_low','label'=>'منع نشر المحتوى منخفض الجودة','type'=>'toggle','default'=>'0'],
   ]],

  /* ── Security ── */
  ['slug'=>'firewall-pro','name'=>'Firewall Pro','cat'=>'security','icon'=>'🛡️',
   'desc'=>'جدار ناري متقدم: فلترة طلبات HTTP، حماية SQL Injection، XSS، CSRF',
   'version'=>'2.0.0','author'=>'Yassota','featured'=>true,
   'settings'=>[
     ['key'=>'block_sqli','label'=>'حجب محاولات SQL Injection','type'=>'toggle','default'=>'1'],
     ['key'=>'block_xss','label'=>'حجب محاولات XSS','type'=>'toggle','default'=>'1'],
     ['key'=>'block_scanners','label'=>'حجب السكانرات المعروفة','type'=>'toggle','default'=>'1'],
     ['key'=>'block_vpn','label'=>'حجب VPN/Proxy','type'=>'toggle','default'=>'1'],
     ['key'=>'rate_limit','label'=>'حد الطلبات في الدقيقة','type'=>'number','default'=>'60'],
   ]],
  ['slug'=>'two-factor-auth','name'=>'Two-Factor Authentication','cat'=>'security','icon'=>'🔐',
   'desc'=>'حماية مضاعفة لتسجيل دخول الادمن بـ TOTP (Google Authenticator)',
   'version'=>'1.2.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'enabled','label'=>'تفعيل 2FA','type'=>'toggle','default'=>'0'],
     ['key'=>'issuer','label'=>'اسم الخدمة في التطبيق','type'=>'text','default'=>'Yassota Admin'],
   ]],
  ['slug'=>'login-guard','name'=>'Login Security Pro','cat'=>'security','icon'=>'🔒',
   'desc'=>'حماية تسجيل الدخول: Brute Force، CAPTCHA إضافي، تأخير زمني',
   'version'=>'1.3.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'max_attempts','label'=>'أقصى محاولات فاشلة','type'=>'number','default'=>'5'],
     ['key'=>'lockout_minutes','label'=>'مدة الحجب (دقيقة)','type'=>'number','default'=>'30'],
     ['key'=>'notify_on_fail','label'=>'إشعار YAI عند تجاوز الحد','type'=>'toggle','default'=>'1'],
   ]],
  ['slug'=>'malware-scanner','name'=>'Malware Scanner Pro','cat'=>'security','icon'=>'🦠',
   'desc'=>'فحص ملفات PHP للكشف عن الشفرات الخبيثة وwebshells مع تنبيهات فورية',
   'version'=>'1.1.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'scan_interval','label'=>'فترة الفحص (ساعات)','type'=>'number','default'=>'24'],
     ['key'=>'scan_uploads','label'=>'فحص مجلد uploads','type'=>'toggle','default'=>'1'],
     ['key'=>'quarantine','label'=>'عزل الملفات المشبوهة','type'=>'toggle','default'=>'1'],
   ]],
  ['slug'=>'audit-log','name'=>'Security Audit Log','cat'=>'security','icon'=>'📒',
   'desc'=>'سجل تدقيق شامل لكل إجراءات الادمن: تعديلات، حذف، نشر، إعدادات',
   'version'=>'1.2.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'log_days','label'=>'الاحتفاظ بالسجل (يوم)','type'=>'number','default'=>'90'],
     ['key'=>'log_reads','label'=>'تسجيل عمليات القراءة أيضاً','type'=>'toggle','default'=>'0'],
   ]],
  ['slug'=>'ip-manager','name'=>'IP Manager Pro','cat'=>'security','icon'=>'🌐',
   'desc'=>'إدارة قوائم IP المسموح والمحجوب مع استيراد CSV وتحديث تلقائي من قوائم التهديدات',
   'version'=>'1.4.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'admin_whitelist','label'=>'IPs الادمن المسموح بها (سطر لكل IP)','type'=>'textarea','default'=>''],
     ['key'=>'auto_ban_vpn','label'=>'حجب VPN تلقائياً','type'=>'toggle','default'=>'1'],
     ['key'=>'auto_ban_tor','label'=>'حجب Tor تلقائياً','type'=>'toggle','default'=>'1'],
   ]],
  ['slug'=>'layos-security','name'=>'Layos Security','cat'=>'security','icon'=>'🔍',
   'desc'=>'محرك تقييم جودة المحتوى: يفرز التطبيقات (≥50٪) والمقالات (≥80٪) قبل الفهرسة في sitemap وRSS — مع تقارير، تجاوزات يدوية، وإشعارات YAI',
   'version'=>'1.0.0','author'=>'Yassota','featured'=>true,
   'url'=>'admin.php?page=layos',
   'settings'=>[
     ['key'=>'app_threshold','label'=>'الحد الأدنى لجودة التطبيقات (%)','type'=>'number','default'=>'50'],
     ['key'=>'article_threshold','label'=>'الحد الأدنى لجودة المقالات (%)','type'=>'number','default'=>'80'],
     ['key'=>'notify_apps','label'=>'إشعار YAI عند استبعاد تطبيقات من sitemap','type'=>'toggle','default'=>'1'],
     ['key'=>'notify_articles','label'=>'إشعار YAI عند استبعاد مقالات من RSS','type'=>'toggle','default'=>'1'],
   ]],

  /* ── Performance ── */
  ['slug'=>'cache-pro','name'=>'Cache Manager Pro','cat'=>'performance','icon'=>'⚡',
   'desc'=>'كاش متقدم للصفحات مع تحكم كامل في TTL والإلغاء التلقائي عند التحديث',
   'version'=>'2.1.0','author'=>'Yassota','featured'=>true,
   'settings'=>[
     ['key'=>'page_ttl','label'=>'مدة كاش الصفحات (ثانية)','type'=>'number','default'=>'3600'],
     ['key'=>'exclude_urls','label'=>'مسارات مستثناة من الكاش (سطر لكل مسار)','type'=>'textarea','default'=>''],
     ['key'=>'cache_logged_in','label'=>'كاش للمستخدمين المسجلين','type'=>'toggle','default'=>'0'],
   ]],
  ['slug'=>'image-optimizer','name'=>'Image Optimizer Pro','cat'=>'performance','icon'=>'🖼️',
   'desc'=>'تحويل الصور لـ WebP وضغط لossless/lossy مع lazy loading تلقائي',
   'version'=>'1.6.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'webp_quality','label'=>'جودة WebP (1-100)','type'=>'number','default'=>'85'],
     ['key'=>'max_width','label'=>'أقصى عرض للصور (px)','type'=>'number','default'=>'800'],
     ['key'=>'lazy_load','label'=>'Lazy Loading تلقائي','type'=>'toggle','default'=>'1'],
     ['key'=>'auto_convert','label'=>'تحويل تلقائي عند الرفع','type'=>'toggle','default'=>'1'],
   ]],
  ['slug'=>'cdn-manager','name'=>'CDN Manager','cat'=>'performance','icon'=>'☁️',
   'desc'=>'إعداد CDN لخدمة الصور والملفات الثابتة بسرعة خارقة عبر شبكة عالمية',
   'version'=>'1.0.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'cdn_url','label'=>'CDN Base URL','type'=>'text','default'=>''],
     ['key'=>'cdn_assets','label'=>'تفعيل CDN للـ Assets (CSS/JS)','type'=>'toggle','default'=>'1'],
     ['key'=>'cdn_images','label'=>'تفعيل CDN للصور','type'=>'toggle','default'=>'1'],
   ]],
  ['slug'=>'lazy-load','name'=>'Lazy Load Pro','cat'=>'performance','icon'=>'🐌',
   'desc'=>'تحميل مؤجل متقدم للصور والفيديو والخرائط لتسريع وقت التحميل الأولي',
   'version'=>'1.1.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'threshold','label'=>'المسافة قبل التحميل (px)','type'=>'number','default'=>'200'],
     ['key'=>'placeholder','label'=>'نوع Placeholder','type'=>'select','default'=>'blur',
      'options'=>['blur'=>'ضبابي','skeleton'=>'Skeleton','none'=>'بدون']],
   ]],
  ['slug'=>'minify-pro','name'=>'Minification Pro','cat'=>'performance','icon'=>'🗜️',
   'desc'=>'ضغط CSS وJavaScript وHTML تلقائياً لتقليل حجم الصفحات وزيادة السرعة',
   'version'=>'1.3.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'minify_css','label'=>'ضغط CSS','type'=>'toggle','default'=>'1'],
     ['key'=>'minify_js','label'=>'ضغط JavaScript','type'=>'toggle','default'=>'1'],
     ['key'=>'minify_html','label'=>'ضغط HTML','type'=>'toggle','default'=>'0'],
   ]],

  /* ── Social ── */
  ['slug'=>'social-share','name'=>'Social Share Pro','cat'=>'social','icon'=>'📤',
   'desc'=>'أزرار مشاركة احترافية لـ WhatsApp وTwitter وFacebook وTelegram والبريد',
   'version'=>'1.5.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'platforms','label'=>'المنصات المفعلة (مفصولة بفاصلة)','type'=>'text','default'=>'whatsapp,twitter,facebook,telegram'],
     ['key'=>'position','label'=>'موقع الأزرار','type'=>'select','default'=>'bottom',
      'options'=>['top'=>'أعلى الصفحة','bottom'=>'أسفل الصفحة','both'=>'أعلى وأسفل','floating'=>'عائمة']],
     ['key'=>'show_count','label'=>'إظهار عدد المشاركات','type'=>'toggle','default'=>'1'],
   ]],
  ['slug'=>'social-poster','name'=>'Auto Social Poster','cat'=>'social','icon'=>'📣',
   'desc'=>'نشر تلقائي في Telegram وTwitter عند نشر تطبيق جديد مع صورة وتلخيص AI',
   'version'=>'1.2.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'telegram_bot','label'=>'Telegram Bot Token','type'=>'password','default'=>''],
     ['key'=>'telegram_chat','label'=>'Telegram Chat/Channel ID','type'=>'text','default'=>''],
     ['key'=>'twitter_key','label'=>'Twitter API Key','type'=>'password','default'=>''],
     ['key'=>'auto_post','label'=>'نشر تلقائي عند نشر التطبيق','type'=>'toggle','default'=>'0'],
     ['key'=>'ai_caption','label'=>'كتابة التعليق بالذكاء الاصطناعي','type'=>'toggle','default'=>'1'],
   ]],
  ['slug'=>'comments-pro','name'=>'Comments System Pro','cat'=>'social','icon'=>'💬',
   'desc'=>'نظام تعليقات متقدم مع مكافحة spam، اعتدال، ردود، وإشعارات فورية',
   'version'=>'2.0.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'require_approval','label'=>'اعتماد التعليقات يدوياً','type'=>'toggle','default'=>'1'],
     ['key'=>'spam_keywords','label'=>'كلمات الحجب التلقائي (سطر لكل كلمة)','type'=>'textarea','default'=>''],
     ['key'=>'notify_email','label'=>'إشعار بريد على تعليق جديد','type'=>'toggle','default'=>'1'],
     ['key'=>'max_per_ip','label'=>'أقصى تعليقات لكل IP/يوم','type'=>'number','default'=>'5'],
   ]],
  ['slug'=>'ratings-pro','name'=>'Ratings Analytics Pro','cat'=>'social','icon'=>'⭐',
   'desc'=>'تحليلات التقييمات مع توزيع النجوم والاتجاهات الزمنية ودمج schema.org',
   'version'=>'1.3.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'min_rating_show','label'=>'أدنى تقييم لعرض الـ Schema','type'=>'number','default'=>'3'],
     ['key'=>'min_count_show','label'=>'أدنى عدد تقييمات لعرض الـ Schema','type'=>'number','default'=>'1'],
     ['key'=>'allow_half','label'=>'السماح بتقييم نصف نجمة','type'=>'toggle','default'=>'0'],
   ]],

  /* ── Monetization ── */
  ['slug'=>'ads-manager','name'=>'Direct Ads Manager','cat'=>'monetize','icon'=>'📺',
   'desc'=>'إدارة مناطق إعلانية مخصصة: header، sidebar، بين التطبيقات، footer',
   'version'=>'1.4.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'header_ad','label'=>'كود إعلان الهيدر','type'=>'textarea','default'=>''],
     ['key'=>'sidebar_ad','label'=>'كود إعلان الـ Sidebar','type'=>'textarea','default'=>''],
     ['key'=>'between_apps','label'=>'كود إعلان بين التطبيقات','type'=>'textarea','default'=>''],
     ['key'=>'footer_ad','label'=>'كود إعلان الفوتر','type'=>'textarea','default'=>''],
     ['key'=>'interstitial_ad','label'=>'إعلان بينيي قبل التحميل','type'=>'textarea','default'=>''],
   ]],
  ['slug'=>'affiliate-pro','name'=>'Affiliate Links Pro','cat'=>'monetize','icon'=>'🔗',
   'desc'=>'إضافة معرف أفيليت تلقائياً لروابط Amazon وGoogle Play والمتاجر الأخرى',
   'version'=>'1.1.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'amazon_tag','label'=>'Amazon Associate Tag','type'=>'text','default'=>''],
     ['key'=>'playstore_ref','label'=>'Google Play Referrer Code','type'=>'text','default'=>''],
     ['key'=>'auto_append','label'=>'إضافة تلقائية لكل الروابط','type'=>'toggle','default'=>'1'],
   ]],
  ['slug'=>'download-monetizer','name'=>'Download Monetizer','cat'=>'monetize','icon'=>'⬇️',
   'desc'=>'إعلان بيني قبل تحميل التطبيق مع عداد زمني قابل للتخصيص',
   'version'=>'1.3.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'countdown','label'=>'مدة العداد (ثانية)','type'=>'number','default'=>'5'],
     ['key'=>'skip_premium','label'=>'تخطي الإعلان للزوار المتكررين','type'=>'toggle','default'=>'0'],
     ['key'=>'ad_code','label'=>'كود الإعلان البيني','type'=>'textarea','default'=>''],
   ]],

  /* ── Developer Tools ── */
  ['slug'=>'cron-manager','name'=>'Cron Jobs Manager','cat'=>'dev','icon'=>'⏰',
   'desc'=>'إدارة المهام المجدولة من الادمن — توليد محتوى، تحديث sitemap، نسخ احتياطي',
   'version'=>'1.2.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'sitemap_interval','label'=>'تحديث Sitemap (ساعات)','type'=>'number','default'=>'6'],
     ['key'=>'backup_interval','label'=>'نسخ احتياطي (ساعات)','type'=>'number','default'=>'24'],
     ['key'=>'refresh_interval','label'=>'تحديث المحتوى القديم (ساعات)','type'=>'number','default'=>'168'],
   ]],
  ['slug'=>'webhook-hub','name'=>'Webhook Hub','cat'=>'dev','icon'=>'🔔',
   'desc'=>'إرسال Webhooks لأنظمة خارجية عند أحداث الموقع: نشر، تحميل، تعليق',
   'version'=>'1.0.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'on_publish','label'=>'Webhook عند نشر تطبيق','type'=>'text','default'=>''],
     ['key'=>'on_download','label'=>'Webhook عند التحميل','type'=>'text','default'=>''],
     ['key'=>'on_comment','label'=>'Webhook عند تعليق جديد','type'=>'text','default'=>''],
     ['key'=>'secret','label'=>'Webhook Secret للتحقق','type'=>'password','default'=>''],
   ]],
  ['slug'=>'api-gateway','name'=>'REST API Gateway','cat'=>'dev','icon'=>'🔌',
   'desc'=>'API مفتوح لاستخدام بيانات الموقع: تطبيقات، تصنيفات، بحث — مع مفاتيح API',
   'version'=>'1.0.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'enabled','label'=>'تفعيل الـ API','type'=>'toggle','default'=>'0'],
     ['key'=>'rate_limit','label'=>'حد الطلبات في الدقيقة','type'=>'number','default'=>'60'],
     ['key'=>'require_key','label'=>'اشتراط مفتاح API','type'=>'toggle','default'=>'1'],
   ]],
  ['slug'=>'email-marketing','name'=>'Email Marketing Pro','cat'=>'dev','icon'=>'📧',
   'desc'=>'إرسال نشرات بريدية لزوارك عند نشر تطبيقات جديدة عبر SMTP أو Mailgun',
   'version'=>'1.1.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'provider','label'=>'مزود الإيميل','type'=>'select','default'=>'smtp',
      'options'=>['smtp'=>'SMTP','mailgun'=>'Mailgun','sendgrid'=>'SendGrid']],
     ['key'=>'smtp_host','label'=>'SMTP Host','type'=>'text','default'=>''],
     ['key'=>'smtp_user','label'=>'SMTP Username','type'=>'text','default'=>''],
     ['key'=>'smtp_pass','label'=>'SMTP Password','type'=>'password','default'=>''],
     ['key'=>'from_email','label'=>'بريد المرسل','type'=>'email','default'=>''],
     ['key'=>'from_name','label'=>'اسم المرسل','type'=>'text','default'=>'Yassota'],
   ]],
  ['slug'=>'db-optimizer','name'=>'Database Optimizer','cat'=>'dev','icon'=>'🗄️',
   'desc'=>'تحسين قاعدة البيانات: OPTIMIZE TABLE، تنظيف التعليقات المحذوفة، ضغط الجداول',
   'version'=>'1.0.0','author'=>'Yassota',
   'settings'=>[
     ['key'=>'auto_optimize','label'=>'تحسين تلقائي كل أسبوع','type'=>'toggle','default'=>'1'],
     ['key'=>'clean_spam','label'=>'حذف التعليقات المرفوضة تلقائياً (أيام)','type'=>'number','default'=>'30'],
   ]],
  ['slug'=>'backup-pro','name'=>'Backup Manager Pro','cat'=>'dev','icon'=>'💾',
   'desc'=>'نسخ احتياطي تلقائي لقاعدة البيانات والملفات مع رفع لـ Google Drive أو Dropbox',
   'version'=>'1.5.0','author'=>'Yassota','featured'=>true,
   'settings'=>[
     ['key'=>'auto_backup','label'=>'نسخ احتياطي تلقائي','type'=>'toggle','default'=>'1'],
     ['key'=>'interval_hours','label'=>'كل كم ساعة','type'=>'number','default'=>'24'],
     ['key'=>'keep_days','label'=>'الاحتفاظ بالنسخ (يوم)','type'=>'number','default'=>'7'],
     ['key'=>'gdrive_token','label'=>'Google Drive Token','type'=>'password','default'=>''],
   ]],
];

$PLUGIN_CATS = [
  'seo'         => ['label'=>'تحسين محركات البحث', 'icon'=>'🎯', 'color'=>'#10b981'],
  'analytics'   => ['label'=>'التحليلات والإحصاء',  'icon'=>'📊', 'color'=>'#3b82f6'],
  'content'     => ['label'=>'المحتوى والذكاء الاصطناعي','icon'=>'✍️','color'=>'#8b5cf6'],
  'security'    => ['label'=>'الأمان والحماية',      'icon'=>'🛡️', 'color'=>'#ef4444'],
  'performance' => ['label'=>'الأداء والسرعة',       'icon'=>'⚡', 'color'=>'#f59e0b'],
  'social'      => ['label'=>'التواصل الاجتماعي',   'icon'=>'📱', 'color'=>'#ec4899'],
  'monetize'    => ['label'=>'تحقيق الدخل',          'icon'=>'💰', 'color'=>'#14b8a6'],
  'dev'         => ['label'=>'أدوات المطورين',        'icon'=>'⚙️', 'color'=>'#6b7280'],
];

/* ─── Google OAuth helpers ─────────────────── */
function press_google_auth_url(PDO $pdo): string {
    $clientId = pp_get($pdo, 'adsense-pro', 'client_id');
    if (!$clientId) return '';
    $redirect = SITE_URL . '/press?plugin=adsense-pro&tab=oauth-callback';
    $state    = bin2hex(random_bytes(16));
    $_SESSION['google_oauth_state'] = $state;
    $params = http_build_query([
        'client_id'     => $clientId,
        'redirect_uri'  => $redirect,
        'response_type' => 'code',
        'scope'         => 'https://www.googleapis.com/auth/adsense.readonly https://www.googleapis.com/auth/webmasters.readonly',
        'access_type'   => 'offline',
        'prompt'        => 'consent',
        'state'         => $state,
    ]);
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
}

function press_google_exchange_code(PDO $pdo, string $code): array {
    $clientId     = pp_get($pdo, 'adsense-pro', 'client_id');
    $clientSecret = pp_get($pdo, 'adsense-pro', 'client_secret');
    $redirect     = SITE_URL . '/press?plugin=adsense-pro&tab=oauth-callback';
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 20,
        CURLOPT_POSTFIELDS => http_build_query([
            'code'=>$code,'client_id'=>$clientId,'client_secret'=>$clientSecret,
            'redirect_uri'=>$redirect,'grant_type'=>'authorization_code',
        ]),
    ]);
    $res = json_decode(curl_exec($ch), true) ?: [];
    curl_close($ch);
    return $res;
}

/* ─── AJAX handlers ──────────────────────── */
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $aj = $_GET['ajax'];

    if ($aj === 'toggle_plugin') {
        if (!csrf_check()) { echo json_encode(['ok'=>false,'error'=>'CSRF']); exit; }
        $slug   = preg_replace('/[^a-z0-9\-]/', '', $_POST['slug'] ?? '');
        $active = ($_POST['active'] ?? '') === '1';
        if ($slug) { pp_toggle($pdo, $slug, $active); echo json_encode(['ok'=>true]); }
        else { echo json_encode(['ok'=>false,'error'=>'slug missing']); }
        exit;
    }

    if ($aj === 'save_plugin_settings') {
        if (!csrf_check()) { echo json_encode(['ok'=>false,'error'=>'CSRF']); exit; }
        $slug   = preg_replace('/[^a-z0-9\-]/', '', $_POST['slug'] ?? '');
        $fields = $_POST['fields'] ?? [];
        if ($slug && is_array($fields)) {
            foreach ($fields as $k => $v) {
                $k = preg_replace('/[^a-z0-9_]/', '', $k);
                if ($k) pp_set($pdo, $slug, $k, (string)$v);
            }
            echo json_encode(['ok'=>true,'msg'=>'تم حفظ الإعدادات بنجاح']);
        } else {
            echo json_encode(['ok'=>false,'error'=>'بيانات ناقصة']);
        }
        exit;
    }

    if ($aj === 'plugin_stats') {
        $total    = count($PLUGINS);
        $active   = 0;
        foreach ($PLUGINS as $p) { if (pp_active($pdo, $p['slug'])) $active++; }
        echo json_encode(['ok'=>true,'total'=>$total,'active'=>$active,'inactive'=>$total-$active]);
        exit;
    }

    echo json_encode(['ok'=>false,'error'=>'unknown action']);
    exit;
}

/* ─── Google OAuth callback ─────────────── */
$currentPlugin = $_GET['plugin'] ?? '';
$currentTab    = $_GET['tab'] ?? 'all';

if ($currentPlugin === 'adsense-pro' && $currentTab === 'oauth-callback') {
    $code  = $_GET['code'] ?? '';
    $state = $_GET['state'] ?? '';
    $oauthMsg = '';
    if ($code && $state && isset($_SESSION['google_oauth_state'])
        && hash_equals($_SESSION['google_oauth_state'], $state)) {
        $tokens = press_google_exchange_code($pdo, $code);
        if (!empty($tokens['access_token'])) {
            pp_set($pdo, 'adsense-pro', 'access_token',  $tokens['access_token']);
            pp_set($pdo, 'adsense-pro', 'refresh_token', $tokens['refresh_token'] ?? '');
            $oauthMsg = 'success';
        } else {
            $oauthMsg = 'error:' . ($tokens['error_description'] ?? 'فشل تبادل الرمز');
        }
    } else {
        $oauthMsg = 'error:state_mismatch';
    }
    unset($_SESSION['google_oauth_state']);
    header('Location: ' . SITE_URL . '/press?plugin=adsense-pro&oauth=' . urlencode($oauthMsg));
    exit;
}

/* ─── Build page data ────────────────────── */
$filterCat = $_GET['cat'] ?? 'all';
$searchQ   = trim($_GET['q'] ?? '');

$displayPlugins = $PLUGINS;
if ($filterCat !== 'all') {
    $displayPlugins = array_filter($PLUGINS, fn($p) => $p['cat'] === $filterCat);
}
if ($searchQ) {
    $sq = mb_strtolower($searchQ);
    $displayPlugins = array_filter($displayPlugins, fn($p) =>
        str_contains(mb_strtolower($p['name']), $sq) ||
        str_contains(mb_strtolower($p['desc']), $sq)
    );
}

// Find selected plugin details
$selectedPlugin = null;
if ($currentPlugin) {
    foreach ($PLUGINS as $p) {
        if ($p['slug'] === $currentPlugin) { $selectedPlugin = $p; break; }
    }
}

$activeCount = 0;
foreach ($PLUGINS as $p) { if (pp_active($pdo, $p['slug'])) $activeCount++; }

// Google OAuth URL
$googleAuthUrl = press_google_auth_url($pdo);

?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>YASSOTA PRESS PRO</title>
<style>
:root {
  --bg:       #0a0e1a;
  --bg2:      #0f1526;
  --bg3:      #141b2d;
  --card:     #1a2235;
  --card2:    #1e2840;
  --border:   #2a3454;
  --border2:  #354066;
  --accent:   #6366f1;
  --accent2:  #818cf8;
  --green:    #10b981;
  --red:      #ef4444;
  --yellow:   #f59e0b;
  --blue:     #3b82f6;
  --purple:   #8b5cf6;
  --pink:     #ec4899;
  --teal:     #14b8a6;
  --text:     #e2e8f0;
  --text2:    #94a3b8;
  --text3:    #64748b;
  --radius:   14px;
  --radius-sm:8px;
  --shadow:   0 4px 24px rgba(0,0,0,0.4);
  --glow:     0 0 30px rgba(99,102,241,0.15);
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { font-size: 15px; }
body {
  font-family: -apple-system, 'Segoe UI', Arial, sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  display: flex;
}
a { color: inherit; text-decoration: none; }

/* ── Sidebar ── */
.pp-sidebar {
  width: 260px; min-height: 100vh;
  background: var(--bg2);
  border-left: 1px solid var(--border);
  display: flex; flex-direction: column;
  position: sticky; top: 0; height: 100vh;
  overflow-y: auto; flex-shrink: 0;
}
.pp-logo {
  padding: 24px 20px 20px;
  border-bottom: 1px solid var(--border);
}
.pp-logo-title {
  font-size: 13px; font-weight: 700; letter-spacing: .5px;
  color: var(--accent2); text-transform: uppercase;
}
.pp-logo-sub {
  font-size: 11px; color: var(--text3); margin-top: 2px;
}
.pp-stats-row {
  display: flex; gap: 8px; padding: 12px 20px;
  border-bottom: 1px solid var(--border);
}
.pp-stat { flex: 1; text-align: center; }
.pp-stat-n { font-size: 18px; font-weight: 700; color: var(--text); }
.pp-stat-l { font-size: 10px; color: var(--text3); }
.pp-stat-n.green { color: var(--green); }

.pp-nav { padding: 12px 0; flex: 1; }
.pp-nav-head { font-size: 10px; font-weight: 700; color: var(--text3);
  letter-spacing: .5px; text-transform: uppercase; padding: 8px 20px 4px; }
.pp-nav-link {
  display: flex; align-items: center; gap: 10px;
  padding: 9px 20px; font-size: 13px; color: var(--text2);
  cursor: pointer; transition: all .15s;
  border-right: 3px solid transparent;
  text-decoration: none;
}
.pp-nav-link:hover { color: var(--text); background: var(--bg3); }
.pp-nav-link.active { color: var(--text); background: var(--bg3);
  border-right-color: var(--accent); }
.pp-nav-link .icon { font-size: 16px; width: 20px; text-align: center; }
.pp-nav-link .badge {
  margin-right: auto; background: var(--accent); color: #fff;
  font-size: 10px; font-weight: 700; border-radius: 20px;
  padding: 1px 7px;
}
.pp-nav-link .badge.green { background: var(--green); }

.pp-back {
  padding: 16px 20px; border-top: 1px solid var(--border);
}
.pp-back a {
  display: flex; align-items: center; gap: 8px;
  font-size: 12px; color: var(--text3); transition: color .15s;
}
.pp-back a:hover { color: var(--text); }

/* ── Main content ── */
.pp-main {
  flex: 1; padding: 28px 32px;
  overflow-x: hidden;
}

/* ── Page header ── */
.pp-page-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 28px; gap: 16px; flex-wrap: wrap;
}
.pp-page-title { font-size: 22px; font-weight: 700; }
.pp-page-sub { font-size: 13px; color: var(--text2); margin-top: 3px; }

/* ── Search bar ── */
.pp-search-wrap { display: flex; gap: 10px; align-items: center; }
.pp-search {
  background: var(--card); border: 1px solid var(--border);
  border-radius: var(--radius-sm); padding: 9px 14px;
  color: var(--text); font-size: 13px; width: 260px; outline: none;
  transition: border-color .15s;
}
.pp-search:focus { border-color: var(--accent); }
.pp-search::placeholder { color: var(--text3); }

/* ── Category pills ── */
.pp-cats { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 24px; }
.pp-cat-pill {
  display: flex; align-items: center; gap: 6px;
  padding: 6px 14px; border-radius: 50px; font-size: 12px; font-weight: 600;
  background: var(--card); border: 1px solid var(--border);
  color: var(--text2); cursor: pointer; transition: all .15s;
  text-decoration: none;
}
.pp-cat-pill:hover { border-color: var(--accent); color: var(--text); }
.pp-cat-pill.active { background: var(--accent); border-color: var(--accent); color: #fff; }

/* ── Plugin grid ── */
.pp-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 18px;
}
.pp-card {
  background: var(--card); border: 1px solid var(--border);
  border-radius: var(--radius); padding: 20px;
  transition: all .2s; position: relative; overflow: hidden;
}
.pp-card:hover { border-color: var(--border2); transform: translateY(-2px); box-shadow: var(--shadow); }
.pp-card.active-card { border-color: var(--accent); }
.pp-card.active-card::before {
  content: ''; position: absolute; top: 0; right: 0;
  width: 100%; height: 3px;
  background: linear-gradient(90deg, var(--accent), var(--purple));
}
.pp-card-head { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 12px; }
.pp-card-icon {
  width: 46px; height: 46px; border-radius: 12px;
  background: var(--bg2); display: flex; align-items: center;
  justify-content: center; font-size: 22px; flex-shrink: 0;
}
.pp-card-info { flex: 1; min-width: 0; }
.pp-card-name { font-size: 14px; font-weight: 700; color: var(--text); }
.pp-card-version { font-size: 11px; color: var(--text3); margin-top: 2px; }
.pp-card-desc { font-size: 12px; color: var(--text2); line-height: 1.6; margin-bottom: 14px; }
.pp-card-footer { display: flex; align-items: center; gap: 8px; }
.pp-card-cat { font-size: 11px; padding: 3px 8px; border-radius: 50px; font-weight: 600; }
.pp-card-author { font-size: 11px; color: var(--text3); margin-right: auto; }
.pp-featured-badge {
  position: absolute; top: 12px; left: 12px;
  background: linear-gradient(135deg, #f59e0b, #ef4444);
  color: #fff; font-size: 10px; font-weight: 700;
  padding: 2px 8px; border-radius: 50px; letter-spacing: .3px;
}

/* ── Toggle switch ── */
.pp-toggle-wrap { display: flex; align-items: center; gap: 10px; }
.pp-toggle {
  position: relative; width: 44px; height: 24px;
}
.pp-toggle input { opacity: 0; width: 0; height: 0; }
.pp-toggle-slider {
  position: absolute; cursor: pointer; inset: 0;
  background: var(--border2); border-radius: 50px;
  transition: .3s;
}
.pp-toggle-slider::before {
  content: ''; position: absolute;
  width: 18px; height: 18px; border-radius: 50%;
  background: #fff; bottom: 3px; right: 3px; transition: .3s;
}
.pp-toggle input:checked + .pp-toggle-slider { background: var(--green); }
.pp-toggle input:checked + .pp-toggle-slider::before { transform: translateX(-20px); }
.pp-toggle-label { font-size: 12px; color: var(--text2); }

/* ── Buttons ── */
.pp-btn {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 8px 18px; border-radius: var(--radius-sm);
  font-size: 13px; font-weight: 600; cursor: pointer;
  border: none; transition: all .15s; text-decoration: none;
}
.pp-btn-primary { background: var(--accent); color: #fff; }
.pp-btn-primary:hover { background: var(--accent2); }
.pp-btn-ghost { background: var(--card2); color: var(--text); border: 1px solid var(--border2); }
.pp-btn-ghost:hover { border-color: var(--accent); color: var(--accent); }
.pp-btn-danger { background: rgba(239,68,68,.15); color: var(--red); border: 1px solid rgba(239,68,68,.3); }
.pp-btn-danger:hover { background: rgba(239,68,68,.25); }
.pp-btn-green { background: rgba(16,185,129,.15); color: var(--green); border: 1px solid rgba(16,185,129,.3); }
.pp-btn-green:hover { background: rgba(16,185,129,.25); }
.pp-btn-sm { padding: 5px 12px; font-size: 12px; }

/* ── Plugin detail page ── */
.pp-detail-header {
  background: var(--card); border: 1px solid var(--border);
  border-radius: var(--radius); padding: 28px; margin-bottom: 24px;
  display: flex; gap: 24px; align-items: flex-start;
}
.pp-detail-icon {
  width: 80px; height: 80px; border-radius: 20px;
  background: var(--bg2); display: flex; align-items: center;
  justify-content: center; font-size: 40px; flex-shrink: 0;
}
.pp-detail-info { flex: 1; }
.pp-detail-name { font-size: 24px; font-weight: 700; margin-bottom: 6px; }
.pp-detail-desc { font-size: 14px; color: var(--text2); line-height: 1.7; margin-bottom: 16px; }
.pp-detail-meta { display: flex; gap: 20px; flex-wrap: wrap; }
.pp-detail-meta-item { font-size: 12px; color: var(--text3); }
.pp-detail-meta-item strong { color: var(--text2); }
.pp-detail-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-top: 20px; }

/* ── Settings form ── */
.pp-settings-section {
  background: var(--card); border: 1px solid var(--border);
  border-radius: var(--radius); padding: 24px; margin-bottom: 20px;
}
.pp-settings-title {
  font-size: 15px; font-weight: 700; margin-bottom: 20px;
  padding-bottom: 12px; border-bottom: 1px solid var(--border);
}
.pp-field { margin-bottom: 18px; }
.pp-label { font-size: 13px; color: var(--text2); margin-bottom: 6px; display: block; }
.pp-input, .pp-select, .pp-textarea {
  width: 100%; background: var(--bg2);
  border: 1px solid var(--border); border-radius: var(--radius-sm);
  padding: 9px 14px; color: var(--text); font-size: 13px; outline: none;
  transition: border-color .15s;
}
.pp-input:focus, .pp-select:focus, .pp-textarea:focus { border-color: var(--accent); }
.pp-textarea { resize: vertical; min-height: 90px; font-family: monospace; }
.pp-input[readonly] { color: var(--text3); cursor: default; }

/* ── Status badge ── */
.pp-status {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 11px; font-weight: 700; padding: 3px 10px;
  border-radius: 50px;
}
.pp-status.active { background: rgba(16,185,129,.15); color: var(--green); }
.pp-status.inactive { background: rgba(100,116,139,.15); color: var(--text3); }

/* ── Alert ── */
.pp-alert {
  border-radius: var(--radius-sm); padding: 12px 16px;
  font-size: 13px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
}
.pp-alert.success { background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.3); color: var(--green); }
.pp-alert.error { background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.3); color: var(--red); }
.pp-alert.info { background: rgba(99,102,241,.1); border: 1px solid rgba(99,102,241,.3); color: var(--accent2); }

/* ── OAuth section ── */
.pp-oauth-card {
  background: linear-gradient(135deg, var(--card) 0%, rgba(99,102,241,.05) 100%);
  border: 1px solid var(--border); border-radius: var(--radius); padding: 28px;
  text-align: center; margin-bottom: 20px;
}
.pp-oauth-icon { font-size: 48px; margin-bottom: 16px; }
.pp-oauth-title { font-size: 18px; font-weight: 700; margin-bottom: 8px; }
.pp-oauth-desc { font-size: 13px; color: var(--text2); margin-bottom: 20px; }
.pp-oauth-btn {
  display: inline-flex; align-items: center; gap: 10px;
  background: #fff; color: #333; padding: 12px 24px;
  border-radius: 8px; font-size: 14px; font-weight: 600;
  transition: all .15s; box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}
.pp-oauth-btn:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.3); transform: translateY(-1px); }
.pp-oauth-connected {
  display: inline-flex; align-items: center; gap: 8px;
  background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.3);
  color: var(--green); padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 700;
}

/* ── Toast ── */
#pp-toast {
  position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%) translateY(80px);
  background: var(--card2); border: 1px solid var(--border2); border-radius: var(--radius-sm);
  padding: 12px 24px; font-size: 13px; font-weight: 600; color: var(--text);
  box-shadow: var(--shadow); z-index: 9999; transition: transform .3s cubic-bezier(.34,1.56,.64,1);
  pointer-events: none;
}
#pp-toast.show { transform: translateX(-50%) translateY(0); }
#pp-toast.success { color: var(--green); border-color: rgba(16,185,129,.4); }
#pp-toast.error { color: var(--red); border-color: rgba(239,68,68,.4); }

/* ── Featured strip ── */
.pp-featured-strip {
  background: linear-gradient(135deg, rgba(99,102,241,.1), rgba(139,92,246,.1));
  border: 1px solid rgba(99,102,241,.2); border-radius: var(--radius);
  padding: 20px 24px; margin-bottom: 24px;
}
.pp-featured-title { font-size: 13px; font-weight: 700; color: var(--accent2); margin-bottom: 14px; text-transform: uppercase; letter-spacing: .5px; }
.pp-featured-list { display: flex; gap: 12px; flex-wrap: wrap; }
.pp-featured-item {
  display: flex; align-items: center; gap: 8px;
  background: var(--card); border: 1px solid var(--border);
  border-radius: var(--radius-sm); padding: 8px 14px;
  font-size: 12px; color: var(--text2); transition: all .15s;
  text-decoration: none; cursor: pointer;
}
.pp-featured-item:hover { border-color: var(--accent); color: var(--text); }

/* ── Empty state ── */
.pp-empty { text-align: center; padding: 60px 20px; color: var(--text3); }
.pp-empty-icon { font-size: 48px; margin-bottom: 12px; }
.pp-empty-text { font-size: 14px; }

/* ── Responsive ── */
@media (max-width: 900px) {
  .pp-sidebar { display: none; }
  .pp-main { padding: 20px 16px; }
}
</style>
</head>
<body>

<!-- ═══ SIDEBAR ═══ -->
<aside class="pp-sidebar">
  <div class="pp-logo">
    <div class="pp-logo-title">⚡ YASSOTA PRESS PRO</div>
    <div class="pp-logo-sub">Plugin Management System v3.0</div>
  </div>

  <div class="pp-stats-row">
    <div class="pp-stat"><div class="pp-stat-n"><?= count($PLUGINS) ?></div><div class="pp-stat-l">إجمالي</div></div>
    <div class="pp-stat"><div class="pp-stat-n green"><?= $activeCount ?></div><div class="pp-stat-l">مفعلة</div></div>
    <div class="pp-stat"><div class="pp-stat-n"><?= count($PLUGINS) - $activeCount ?></div><div class="pp-stat-l">معطلة</div></div>
  </div>

  <nav class="pp-nav">
    <div class="pp-nav-head">تصفح</div>
    <a href="press.php?cat=all" class="pp-nav-link <?= $filterCat==='all'&&!$currentPlugin?'active':'' ?>">
      <span class="icon">🏪</span> متجر الإضافات
      <span class="badge"><?= count($PLUGINS) ?></span>
    </a>
    <a href="press.php?cat=all&filter=active" class="pp-nav-link">
      <span class="icon">✅</span> المفعلة
      <span class="badge green"><?= $activeCount ?></span>
    </a>

    <div class="pp-nav-head" style="margin-top:12px">التصنيفات</div>
    <?php foreach ($PLUGIN_CATS as $catKey => $catInfo):
      $catCount = count(array_filter($PLUGINS, fn($p)=>$p['cat']===$catKey));
    ?>
    <a href="press.php?cat=<?= $catKey ?>" class="pp-nav-link <?= $filterCat===$catKey&&!$currentPlugin?'active':'' ?>">
      <span class="icon"><?= $catInfo['icon'] ?></span>
      <?= $catInfo['label'] ?>
      <span class="badge" style="background:<?= $catInfo['color'] ?>"><?= $catCount ?></span>
    </a>
    <?php endforeach; ?>
  </nav>

  <div class="pp-back">
    <a href="admin.php"><span>←</span> العودة للادمن</a>
  </div>
</aside>

<!-- ═══ MAIN ═══ -->
<main class="pp-main">
<div id="pp-toast"></div>

<?php if ($selectedPlugin): ?>
<!-- ════ PLUGIN DETAIL VIEW ════ -->
<?php
  $slug      = $selectedPlugin['slug'];
  $isActive  = pp_active($pdo, $slug);
  $oauthResult = $_GET['oauth'] ?? '';
  $catInfo   = $PLUGIN_CATS[$selectedPlugin['cat']];
?>

<?php if ($oauthResult === 'success'): ?>
<div class="pp-alert success">✅ تم ربط حساب Google بنجاح! يمكنك الآن استخدام AdSense Manager Pro.</div>
<?php elseif (str_starts_with($oauthResult, 'error:')): ?>
<div class="pp-alert error">❌ فشل ربط Google: <?= h(substr($oauthResult, 6)) ?></div>
<?php endif; ?>

<a href="press.php?cat=<?= $selectedPlugin['cat'] ?>" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--text3);margin-bottom:20px;transition:color .15s" onmouseover="this.style.color='var(--text)'" onmouseout="this.style.color='var(--text3)'">
  ← العودة للمتجر
</a>

<div class="pp-detail-header">
  <div class="pp-detail-icon"><?= $selectedPlugin['icon'] ?></div>
  <div class="pp-detail-info">
    <div class="pp-detail-name"><?= h($selectedPlugin['name']) ?></div>
    <div class="pp-detail-desc"><?= h($selectedPlugin['desc']) ?></div>
    <div class="pp-detail-meta">
      <div class="pp-detail-meta-item">الإصدار: <strong><?= h($selectedPlugin['version']) ?></strong></div>
      <div class="pp-detail-meta-item">المطور: <strong><?= h($selectedPlugin['author']) ?></strong></div>
      <div class="pp-detail-meta-item">التصنيف: <strong><?= $catInfo['icon'] ?> <?= $catInfo['label'] ?></strong></div>
    </div>
    <div class="pp-detail-actions">
      <span class="pp-status <?= $isActive?'active':'inactive' ?>" id="plugin-status-badge">
        <?= $isActive ? '● مفعّلة' : '○ معطلة' ?>
      </span>
      <button id="toggle-btn"
        class="pp-btn <?= $isActive?'pp-btn-danger':'pp-btn-green' ?>"
        onclick="togglePlugin('<?= $slug ?>', <?= $isActive?'false':'true' ?>)">
        <?= $isActive ? '⏸ تعطيل الإضافة' : '▶ تفعيل الإضافة' ?>
      </button>
    </div>
  </div>
</div>

<?php if ($slug === 'adsense-pro'): ?>
<!-- Google AdSense OAuth Section -->
<div class="pp-oauth-card">
  <div class="pp-oauth-icon">🔐</div>
  <div class="pp-oauth-title">ربط حساب Google AdSense</div>
  <div class="pp-oauth-desc">
    أدخل بيانات Google OAuth أولاً ثم اضغط "الاتصال" للسماح لـ Yassota بإدارة إعلاناتك وعرض إحصاءاتك
  </div>
  <?php
    $hasToken    = (bool)pp_get($pdo, 'adsense-pro', 'access_token');
    $hasClientId = (bool)pp_get($pdo, 'adsense-pro', 'client_id');
  ?>
  <?php if ($hasToken): ?>
    <div class="pp-oauth-connected">✅ متصل بحساب Google</div>
    <div style="margin-top:12px">
      <button class="pp-btn pp-btn-danger pp-btn-sm" onclick="disconnectGoogle()">قطع الاتصال</button>
    </div>
  <?php elseif ($hasClientId && $googleAuthUrl): ?>
    <a href="<?= h($googleAuthUrl) ?>" class="pp-oauth-btn">
      <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#4285f4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34a853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#fbbc05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#ea4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
      الاتصال بـ Google
    </a>
  <?php else: ?>
    <div class="pp-alert info" style="text-align:right;max-width:480px;margin:0 auto">
      💡 أدخل Google OAuth Client ID و Client Secret في الإعدادات أدناه أولاً
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- Settings Form -->
<?php if (!empty($selectedPlugin['settings'])): ?>
<form id="plugin-settings-form" onsubmit="savePluginSettings(event, '<?= $slug ?>')">
  <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
  <div class="pp-settings-section">
    <div class="pp-settings-title">⚙️ إعدادات الإضافة</div>
    <?php foreach ($selectedPlugin['settings'] as $f):
      $val = pp_get($pdo, $slug, $f['key'], $f['default'] ?? '');
    ?>
    <div class="pp-field">
      <?php if ($f['type'] === 'toggle'): ?>
        <div class="pp-toggle-wrap">
          <label class="pp-toggle">
            <input type="checkbox" name="fields[<?= $f['key'] ?>]" value="1" <?= $val === '1' ? 'checked' : '' ?>>
            <span class="pp-toggle-slider"></span>
          </label>
          <span class="pp-toggle-label"><?= h($f['label']) ?></span>
        </div>
      <?php elseif ($f['type'] === 'textarea'): ?>
        <label class="pp-label"><?= h($f['label']) ?></label>
        <textarea name="fields[<?= $f['key'] ?>]" class="pp-textarea"><?= h($val) ?></textarea>
      <?php elseif ($f['type'] === 'select'): ?>
        <label class="pp-label"><?= h($f['label']) ?></label>
        <select name="fields[<?= $f['key'] ?>]" class="pp-select">
          <?php foreach ($f['options'] as $optK => $optL): ?>
            <option value="<?= h($optK) ?>" <?= $val===$optK?'selected':'' ?>><?= h($optL) ?></option>
          <?php endforeach; ?>
        </select>
      <?php elseif ($f['type'] === 'readonly'): ?>
        <label class="pp-label"><?= h($f['label']) ?></label>
        <input type="text" class="pp-input" value="<?= $val ? '••••••••••••••••' : '(فارغ — يُملأ تلقائياً)' ?>" readonly>
      <?php elseif ($f['type'] === 'password'): ?>
        <label class="pp-label"><?= h($f['label']) ?></label>
        <input type="password" name="fields[<?= $f['key'] ?>]" class="pp-input"
          value="<?= h($val) ?>" autocomplete="new-password">
      <?php else: ?>
        <label class="pp-label"><?= h($f['label']) ?></label>
        <input type="<?= h($f['type']) ?>" name="fields[<?= $f['key'] ?>]" class="pp-input"
          value="<?= h($val) ?>">
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <div style="margin-top:24px;display:flex;gap:10px">
      <button type="submit" class="pp-btn pp-btn-primary">💾 حفظ الإعدادات</button>
      <a href="press.php" class="pp-btn pp-btn-ghost">إلغاء</a>
    </div>
  </div>
</form>
<?php endif; ?>

<?php else: ?>
<!-- ════ MARKETPLACE / PLUGIN GRID VIEW ════ -->

<div class="pp-page-header">
  <div>
    <div class="pp-page-title">⚡ YASSOTA PRESS PRO</div>
    <div class="pp-page-sub">
      <?= count($PLUGINS) ?> إضافة متكاملة —
      <?= $activeCount ?> مفعّلة،
      <?= count($PLUGINS)-$activeCount ?> معطلة
    </div>
  </div>
  <div class="pp-search-wrap">
    <form method="get" style="display:flex;gap:8px">
      <input type="text" name="q" class="pp-search" placeholder="🔍 ابحث في الإضافات..."
        value="<?= h($searchQ) ?>"
        onkeyup="if(this.value==='')this.form.submit()">
      <?php if ($filterCat !== 'all'): ?><input type="hidden" name="cat" value="<?= h($filterCat) ?>"><?php endif; ?>
    </form>
  </div>
</div>

<!-- Featured plugins -->
<?php if ($filterCat === 'all' && !$searchQ):
  $featured = array_filter($PLUGINS, fn($p) => !empty($p['featured']));
?>
<div class="pp-featured-strip">
  <div class="pp-featured-title">⭐ الإضافات الأبرز</div>
  <div class="pp-featured-list">
    <?php foreach ($featured as $fp): ?>
    <a href="press.php?plugin=<?= $fp['slug'] ?>" class="pp-featured-item">
      <?= $fp['icon'] ?> <?= h($fp['name']) ?>
      <?php if (pp_active($pdo, $fp['slug'])): ?>
        <span style="color:var(--green);font-size:10px">●</span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Category pills -->
<div class="pp-cats">
  <a href="press.php" class="pp-cat-pill <?= $filterCat==='all'?'active':'' ?>">🏪 الكل (<?= count($PLUGINS) ?>)</a>
  <?php foreach ($PLUGIN_CATS as $catKey => $catInfo):
    $n = count(array_filter($PLUGINS, fn($p)=>$p['cat']===$catKey));
  ?>
  <a href="press.php?cat=<?= $catKey ?>" class="pp-cat-pill <?= $filterCat===$catKey?'active':'' ?>"
     style="<?= $filterCat===$catKey ? "background:{$catInfo['color']};border-color:{$catInfo['color']};color:#fff" : '' ?>">
    <?= $catInfo['icon'] ?> <?= $catInfo['label'] ?> (<?= $n ?>)
  </a>
  <?php endforeach; ?>
</div>

<!-- Plugin grid -->
<?php if (empty($displayPlugins)): ?>
<div class="pp-empty">
  <div class="pp-empty-icon">🔍</div>
  <div class="pp-empty-text">لا توجد إضافات تطابق البحث</div>
</div>
<?php else: ?>
<div class="pp-grid">
<?php foreach ($displayPlugins as $p):
  $pActive  = pp_active($pdo, $p['slug']);
  $pCatInfo = $PLUGIN_CATS[$p['cat']];
?>
<div class="pp-card <?= $pActive ? 'active-card' : '' ?>" data-slug="<?= $p['slug'] ?>">
  <?php if (!empty($p['featured'])): ?><div class="pp-featured-badge">★ مميز</div><?php endif; ?>
  <div class="pp-card-head">
    <div class="pp-card-icon" style="background:<?= $pCatInfo['color'] ?>22"><?= $p['icon'] ?></div>
    <div class="pp-card-info">
      <div class="pp-card-name"><?= h($p['name']) ?></div>
      <div class="pp-card-version">v<?= h($p['version']) ?> · <?= h($p['author']) ?></div>
    </div>
  </div>
  <div class="pp-card-desc"><?= h($p['desc']) ?></div>
  <div class="pp-card-footer">
    <span class="pp-card-cat" style="background:<?= $pCatInfo['color'] ?>22;color:<?= $pCatInfo['color'] ?>">
      <?= $pCatInfo['icon'] ?> <?= $pCatInfo['label'] ?>
    </span>
    <a href="press.php?plugin=<?= $p['slug'] ?>" class="pp-btn pp-btn-ghost pp-btn-sm">⚙️ إعدادات</a>
    <label class="pp-toggle" title="<?= $pActive?'تعطيل':'تفعيل' ?>" style="margin-right:4px">
      <input type="checkbox" <?= $pActive?'checked':'' ?>
        onchange="togglePlugin('<?= $p['slug'] ?>', this.checked, this)">
      <span class="pp-toggle-slider"></span>
    </label>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php endif; /* end marketplace view */ ?>

</main>

<script>
const CSRF = <?= json_encode(csrf_token()) ?>;

function showToast(msg, type='success') {
  const t = document.getElementById('pp-toast');
  t.textContent = msg;
  t.className = 'show ' + type;
  clearTimeout(t._timer);
  t._timer = setTimeout(() => { t.className = ''; }, 3200);
}

function togglePlugin(slug, activate, toggleEl) {
  const fd = new FormData();
  fd.append('_csrf', CSRF);
  fd.append('slug', slug);
  fd.append('active', activate ? '1' : '0');

  fetch('press.php?ajax=toggle_plugin', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      if (d.ok) {
        showToast(activate ? '✅ تم تفعيل الإضافة' : '⏸ تم تعطيل الإضافة', activate ? 'success' : '');
        // Update card appearance
        const card = document.querySelector(`.pp-card[data-slug="${slug}"]`);
        if (card) card.classList.toggle('active-card', activate);
        // Update detail page button if present
        const btn = document.getElementById('toggle-btn');
        const badge = document.getElementById('plugin-status-badge');
        if (btn && badge) {
          if (activate) {
            btn.textContent = '⏸ تعطيل الإضافة';
            btn.className = 'pp-btn pp-btn-danger';
            btn.setAttribute('onclick', `togglePlugin('${slug}', false)`);
            badge.textContent = '● مفعّلة';
            badge.className = 'pp-status active';
          } else {
            btn.textContent = '▶ تفعيل الإضافة';
            btn.className = 'pp-btn pp-btn-green';
            btn.setAttribute('onclick', `togglePlugin('${slug}', true)`);
            badge.textContent = '○ معطلة';
            badge.className = 'pp-status inactive';
          }
        }
      } else {
        showToast('❌ ' + (d.error || 'خطأ'), 'error');
        if (toggleEl) toggleEl.checked = !activate;
      }
    })
    .catch(() => { showToast('❌ خطأ في الاتصال', 'error'); if (toggleEl) toggleEl.checked = !activate; });
}

function savePluginSettings(e, slug) {
  e.preventDefault();
  const form = e.target;
  const fd = new FormData(form);
  fd.set('slug', slug);
  // Uncheck toggles that aren't checked
  form.querySelectorAll('input[type="checkbox"]').forEach(cb => {
    if (!cb.checked) fd.set(cb.name, '0');
  });
  fetch('press.php?ajax=save_plugin_settings', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      if (d.ok) showToast('💾 ' + (d.msg || 'تم الحفظ'), 'success');
      else showToast('❌ ' + (d.error || 'خطأ'), 'error');
    })
    .catch(() => showToast('❌ خطأ في الاتصال', 'error'));
}

function disconnectGoogle() {
  if (!confirm('هل تريد قطع الاتصال بـ Google؟')) return;
  const fd = new FormData();
  fd.append('_csrf', CSRF);
  fd.append('slug', 'adsense-pro');
  fd.append('fields[access_token]', '');
  fd.append('fields[refresh_token]', '');
  fetch('press.php?ajax=save_plugin_settings', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => { if (d.ok) location.reload(); });
}
</script>
</body>
</html>
