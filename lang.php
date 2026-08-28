<?php
/**
 * lang.php — minimal UI i18n layer for the public/admin pages.
 * Provides: detect_ui_lang(), is_rtl_lang(), __().
 * Supported UI languages: ar (default, RTL), en.
 */

const UI_SUPPORTED_LANGS = ['ar', 'en'];
const UI_RTL_LANGS = ['ar', 'he', 'fa', 'ur'];

/** Detect the visitor's UI language from ?lang=, cookie, then browser header. */
function detect_ui_lang(): string {
    $pick = function (string $lang): string {
        return in_array($lang, UI_SUPPORTED_LANGS, true) ? $lang : 'ar';
    };

    if (!empty($_GET['lang'])) {
        $lang = $pick(strtolower(trim((string)$_GET['lang'])));
        if (!headers_sent()) {
            setcookie('ui_lang', $lang, time() + 60 * 60 * 24 * 365, '/', '', !empty($_SERVER['HTTPS']), true);
        }
        return $lang;
    }

    if (!empty($_COOKIE['ui_lang'])) {
        return $pick(strtolower(trim((string)$_COOKIE['ui_lang'])));
    }

    $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    if ($accept && preg_match('/^([a-zA-Z]{2})/', $accept, $m)) {
        return $pick(strtolower($m[1]));
    }

    return 'ar';
}

/** Is the given (or current UI_LANG) language right-to-left? */
function is_rtl_lang(?string $lang = null): bool {
    $lang = $lang ?? (defined('UI_LANG') ? UI_LANG : 'ar');
    return in_array($lang, UI_RTL_LANGS, true);
}

/** Translate a UI string key for the current UI_LANG (falls back to ar, then the key itself). */
function __(string $key): string {
    static $dict = null;
    if ($dict === null) {
        $dict = ui_translations();
    }
    $lang = defined('UI_LANG') ? UI_LANG : 'ar';
    if (isset($dict[$lang][$key])) return $dict[$lang][$key];
    if (isset($dict['ar'][$key])) return $dict['ar'][$key];
    return ucfirst(str_replace('_', ' ', $key));
}

/** All UI translation strings, keyed by [lang][key]. */
function ui_translations(): array {
    return [
        'ar' => [
            'about' => 'من نحن',
            'accept_all' => 'قبول الكل',
            'action' => 'إجراء',
            'add_tool_new' => 'إضافة أداة جديدة',
            'added_success' => 'تمت الإضافة بنجاح',
            'all' => 'الكل',
            'all_rights' => 'جميع الحقوق محفوظة',
            'all_tools_label' => 'كل الأدوات',
            'alternatives' => 'بدائل مشابهة',
            'and_word' => 'و',
            'android' => 'أندرويد',
            'app' => 'تطبيق',
            'app_not_found' => 'التطبيق غير موجود',
            'apps' => 'تطبيقات',
            'available_languages' => 'اللغات المتاحة',
            'back' => 'رجوع',
            'back_to_home' => 'العودة للرئيسية',
            'badge_choice' => 'الاختيار الأفضل',
            'badge_hot' => 'الأكثر طلبًا',
            'badge_new' => 'جديد',
            'badge_updated' => 'محدَّث',
            'blog' => 'المدونة',
            'calculators' => 'حاسبات',
            'changes_col' => 'التغييرات',
            'close' => 'إغلاق',
            'contact' => 'تواصل معنا',
            'content_label' => 'المحتوى',
            'content_page_label' => 'صفحة المحتوى',
            'cookie_banner_text' => 'نستخدم ملفات تعريف الارتباط لتحسين تجربتك على الموقع. بالمتابعة أنت توافق على سياسة الكوكيز الخاصة بنا.',
            'cookie_banner_title' => 'نحترم خصوصيتك',
            'cookie_policy' => 'سياسة الكوكيز',
            'cons' => 'السلبيات',
            'current_version' => 'الإصدار الحالي',
            'date' => 'التاريخ',
            'date_col' => 'التاريخ',
            'delete' => 'حذف',
            'delete_tool' => 'حذف الأداة',
            'deleted_success' => 'تم الحذف بنجاح',
            'description' => 'الوصف',
            'developer' => 'المطوّر',
            'disclosure_link' => 'إفصاح الإعلانات',
            'discover' => 'اكتشف',
            'dmca_link' => 'حقوق النشر (DMCA)',
            'download' => 'تحميل',
            'download_this_version' => 'تحميل هذا الإصدار',
            'edit' => 'تعديل',
            'editorial_oversight' => 'مراجَع من فريق التحرير',
            'essential_only' => 'الأساسية فقط',
            'exchange' => 'تبادل',
            'faq' => 'الأسئلة الشائعة',
            'features' => 'المميزات',
            'footer_disclaimer' => 'جميع أسماء وعلامات التطبيقات المذكورة ملك لأصحابها، ونعرضها لأغراض تعريفية فقط.',
            'footer_tagline' => 'منصتك لتحميل أفضل التطبيقات والألعاب بأمان.',
            'free' => 'مجاني',
            'free_download' => 'تحميل مجاني',
            'games' => 'ألعاب',
            'gold' => 'ذهبي',
            'home' => 'الرئيسية',
            'hosted_apk' => 'ملف APK مستضاف لدينا',
            'install_steps' => 'خطوات التثبيت',
            'last_update' => 'آخر تحديث',
            'latest_updates' => 'أحدث التحديثات',
            'manage_tools' => 'إدارة الأدوات',
            'menu' => 'القائمة',
            'mirror_2' => 'رابط بديل 2',
            'mirror_3' => 'رابط بديل 3',
            'more' => 'المزيد',
            'name' => 'الاسم',
            'no_results' => 'لا توجد نتائج',
            'no_reviews_yet' => 'لا توجد تقييمات بعد — كن أول من يقيّم',
            'no_tools' => 'لا توجد أدوات بعد',
            'permission_count' => 'عدد الأذونات',
            'permissions_warning' => 'قد يطلب هذا التطبيق أذونات على جهازك — راجعها قبل التثبيت.',
            'press_esc_close' => 'اضغط Esc للإغلاق',
            'prev_versions' => 'إصدارات سابقة',
            'prices' => 'الأسعار',
            'privacy' => 'الخصوصية',
            'privacy_notice' => 'إشعار الخصوصية',
            'pros' => 'الإيجابيات',
            'pros_cons' => 'الإيجابيات والسلبيات',
            'ratings_reviews' => 'التقييمات والمراجعات',
            'read_more' => 'اقرأ المزيد',
            'related_articles' => 'مقالات ذات صلة',
            'required' => 'مطلوب',
            'required_permissions' => 'الأذونات المطلوبة',
            'review_thanks' => 'شكرًا لتقييمك',
            'review_word' => 'تقييم',
            'reviewed_by_team' => 'راجعه فريقنا',
            'save' => 'حفظ',
            'screenshots' => 'لقطات الشاشة',
            'search' => 'بحث',
            'search_placeholder' => 'ابحث عن تطبيق أو أداة…',
            'sections_label' => 'الأقسام',
            'show_less' => 'عرض أقل',
            'show_more' => 'عرض المزيد',
            'similar_apps' => 'تطبيقات مشابهة',
            'size' => 'الحجم',
            'slug' => 'الرابط المختصر',
            'solar' => 'فضي',
            'status' => 'الحالة',
            'subscribe_telegram' => 'اشترك في قناتنا على تيليجرام',
            'terms' => 'شروط الاستخدام',
            'tool_delete_confirm' => 'هل أنت متأكد من حذف هذه الأداة؟ لا يمكن التراجع عن هذا الإجراء.',
            'tool_description' => 'وصف الأداة',
            'tool_draft' => 'مسودة',
            'tool_features' => 'مميزات الأداة',
            'tool_long_desc' => 'الوصف التفصيلي',
            'tool_name' => 'اسم الأداة',
            'tool_published' => 'منشورة',
            'tool_slug' => 'رابط الأداة',
            'tools' => 'الأدوات',
            'top_downloads' => 'الأكثر تحميلًا',
            'top_views' => 'الأكثر مشاهدة',
            'updated_success' => 'تم التحديث بنجاح',
            'updates' => 'التحديثات',
            'useful_tools' => 'أدوات مفيدة',
            'version' => 'الإصدار',
            'version_col' => 'الإصدار',
            'version_history' => 'سجل الإصدارات',
            'views' => 'المشاهدات',
            'what_does_offer' => 'ماذا يقدّم',
            'whats_new' => 'ما الجديد',
            'yassota_section' => 'قسم ياسوتا',
        ],
        'en' => [
            'about' => 'About',
            'accept_all' => 'Accept all',
            'action' => 'Action',
            'add_tool_new' => 'Add new tool',
            'added_success' => 'Added successfully',
            'all' => 'All',
            'all_rights' => 'All rights reserved',
            'all_tools_label' => 'All tools',
            'alternatives' => 'Alternatives',
            'and_word' => 'and',
            'android' => 'Android',
            'app' => 'App',
            'app_not_found' => 'App not found',
            'apps' => 'Apps',
            'available_languages' => 'Available languages',
            'back' => 'Back',
            'back_to_home' => 'Back to home',
            'badge_choice' => 'Editor\'s choice',
            'badge_hot' => 'Hot',
            'badge_new' => 'New',
            'badge_updated' => 'Updated',
            'blog' => 'Blog',
            'calculators' => 'Calculators',
            'changes_col' => 'Changes',
            'close' => 'Close',
            'contact' => 'Contact',
            'content_label' => 'Content',
            'content_page_label' => 'Content page',
            'cookie_banner_text' => 'We use cookies to improve your experience on this site. By continuing, you agree to our cookie policy.',
            'cookie_banner_title' => 'We respect your privacy',
            'cookie_policy' => 'Cookie policy',
            'cons' => 'Cons',
            'current_version' => 'Current version',
            'date' => 'Date',
            'date_col' => 'Date',
            'delete' => 'Delete',
            'delete_tool' => 'Delete tool',
            'deleted_success' => 'Deleted successfully',
            'description' => 'Description',
            'developer' => 'Developer',
            'disclosure_link' => 'Ad disclosure',
            'discover' => 'Discover',
            'dmca_link' => 'DMCA',
            'download' => 'Download',
            'download_this_version' => 'Download this version',
            'edit' => 'Edit',
            'editorial_oversight' => 'Reviewed by our editorial team',
            'essential_only' => 'Essential only',
            'exchange' => 'Exchange',
            'faq' => 'FAQ',
            'features' => 'Features',
            'footer_disclaimer' => 'All app names and trademarks mentioned belong to their respective owners and are used for identification purposes only.',
            'footer_tagline' => 'Your destination for safe app and game downloads.',
            'free' => 'Free',
            'free_download' => 'Free download',
            'games' => 'Games',
            'gold' => 'Gold',
            'home' => 'Home',
            'hosted_apk' => 'APK hosted by us',
            'install_steps' => 'Installation steps',
            'last_update' => 'Last update',
            'latest_updates' => 'Latest updates',
            'manage_tools' => 'Manage tools',
            'menu' => 'Menu',
            'mirror_2' => 'Mirror 2',
            'mirror_3' => 'Mirror 3',
            'more' => 'More',
            'name' => 'Name',
            'no_results' => 'No results found',
            'no_reviews_yet' => 'No reviews yet — be the first to review',
            'no_tools' => 'No tools yet',
            'permission_count' => 'Permission count',
            'permissions_warning' => 'This app may request device permissions — review them before installing.',
            'press_esc_close' => 'Press Esc to close',
            'prev_versions' => 'Previous versions',
            'prices' => 'Prices',
            'privacy' => 'Privacy',
            'privacy_notice' => 'Privacy notice',
            'pros' => 'Pros',
            'pros_cons' => 'Pros & cons',
            'ratings_reviews' => 'Ratings & reviews',
            'read_more' => 'Read more',
            'related_articles' => 'Related articles',
            'required' => 'Required',
            'required_permissions' => 'Required permissions',
            'review_thanks' => 'Thanks for your review',
            'review_word' => 'Review',
            'reviewed_by_team' => 'Reviewed by our team',
            'save' => 'Save',
            'screenshots' => 'Screenshots',
            'search' => 'Search',
            'search_placeholder' => 'Search for an app or tool…',
            'sections_label' => 'Sections',
            'show_less' => 'Show less',
            'show_more' => 'Show more',
            'similar_apps' => 'Similar apps',
            'size' => 'Size',
            'slug' => 'Slug',
            'solar' => 'Silver',
            'status' => 'Status',
            'subscribe_telegram' => 'Subscribe to our Telegram channel',
            'terms' => 'Terms of use',
            'tool_delete_confirm' => 'Are you sure you want to delete this tool? This cannot be undone.',
            'tool_description' => 'Tool description',
            'tool_draft' => 'Draft',
            'tool_features' => 'Tool features',
            'tool_long_desc' => 'Long description',
            'tool_name' => 'Tool name',
            'tool_published' => 'Published',
            'tool_slug' => 'Tool slug',
            'tools' => 'Tools',
            'top_downloads' => 'Top downloads',
            'top_views' => 'Most viewed',
            'updated_success' => 'Updated successfully',
            'updates' => 'Updates',
            'useful_tools' => 'Useful tools',
            'version' => 'Version',
            'version_col' => 'Version',
            'version_history' => 'Version history',
            'views' => 'Views',
            'what_does_offer' => 'What does',
            'whats_new' => "What's new",
            'yassota_section' => 'Yassota section',
        ],
    ];
}
