<?php
/* ═══════════════════════════════════════════════════════════
   lang.php — UI Language System
   Auto-detects device language from Accept-Language / cookie.
   Provides __() translate function used in all templates.
   Content in DB stays in Arabic; only UI strings change.
   ═══════════════════════════════════════════════════════════ */

$_TRANSLATIONS = [

/* ── Arabic (default, RTL) ─────────────────────────────── */
'ar' => [
  /* Navigation */
  'home'              => 'الرئيسية',
  'apps'              => 'تطبيقات',
  'games'             => 'ألعاب',
  'top_downloads'     => 'الأكثر تحميلاً',
  'top_views'         => 'الأكثر زيارة',
  'updates'           => 'التحديثات',
  'tools'             => 'أدوات',
  'more'              => 'المزيد',
  'blog'              => 'المدونة',
  'prices'            => 'الأسعار',
  'calculators'       => 'حاسبات',
  'solar'             => 'الطاقة الشمسية',
  'exchange'          => 'أسعار الصرف',
  'gold'              => 'أسعار الذهب',
  'about'             => 'من نحن',
  'contact'           => 'اتصل بنا',
  'privacy'           => 'الخصوصية',
  'faq'               => 'الأسئلة الشائعة',
  'menu'              => 'القائمة',
  'close'             => 'إغلاق',
  /* Search */
  'search_placeholder'=> 'ابحث عن تطبيق أو لعبة...',
  'search'            => 'بحث',
  'search_results'    => 'نتائج البحث',
  'search_hint'       => 'ابحث بالاسم أو اسم المطوّر',
  'no_results'        => 'لا توجد نتائج',
  'no_results_for'    => 'لا توجد نتائج لـ',
  /* Download */
  'download'          => 'تحميل',
  'download_apk'      => 'تحميل APK',
  'download_start'    => 'سيبدأ التحميل خلال لحظات',
  'google_play'       => 'Google Play',
  'mirror'            => 'مرآة',
  /* Sections */
  'latest_apps'       => 'أحدث التطبيقات',
  'latest_updates'    => 'آخر التحديثات',
  'related_apps'      => 'تطبيقات مشابهة',
  'show_more'         => 'عرض المزيد',
  'show_less'         => 'عرض أقل',
  'see_all'           => 'عرض الكل',
  'all'               => 'الكل',
  /* App meta */
  'app'               => 'تطبيق',
  'apps_label'        => 'تطبيقات',
  'version'           => 'الإصدار',
  'size'              => 'الحجم',
  'rating'            => 'التقييم',
  'developer'         => 'المطوّر',
  'category'          => 'التصنيف',
  'free'              => 'مجاني',
  'android'           => 'أندرويد',
  'latest'            => 'أحدث',
  /* Common UI */
  'language'          => 'اللغة',
  'all_rights'        => 'جميع الحقوق محفوظة',
  'share'             => 'مشاركة',
  'copy_link'         => 'نسخ الرابط',
  'report'            => 'إبلاغ',
  'verified'          => 'تم التحقق',
],

/* ── English ───────────────────────────────────────────── */
'en' => [
  'home'              => 'Home',
  'apps'              => 'Apps',
  'games'             => 'Games',
  'top_downloads'     => 'Top Downloads',
  'top_views'         => 'Most Viewed',
  'updates'           => 'Updates',
  'tools'             => 'Tools',
  'more'              => 'More',
  'blog'              => 'Blog',
  'prices'            => 'Prices',
  'calculators'       => 'Calculators',
  'solar'             => 'Solar Energy',
  'exchange'          => 'Exchange Rates',
  'gold'              => 'Gold Prices',
  'about'             => 'About Us',
  'contact'           => 'Contact',
  'privacy'           => 'Privacy Policy',
  'faq'               => 'FAQ',
  'menu'              => 'Menu',
  'close'             => 'Close',
  'search_placeholder'=> 'Search for an app or game...',
  'search'            => 'Search',
  'search_results'    => 'Search Results',
  'search_hint'       => 'Search by name or developer',
  'no_results'        => 'No results found',
  'no_results_for'    => 'No results for',
  'download'          => 'Download',
  'download_apk'      => 'Download APK',
  'download_start'    => 'Your download will start shortly',
  'google_play'       => 'Google Play',
  'mirror'            => 'Mirror',
  'latest_apps'       => 'Latest Apps',
  'latest_updates'    => 'Latest Updates',
  'related_apps'      => 'Similar Apps',
  'show_more'         => 'Show More',
  'show_less'         => 'Show Less',
  'see_all'           => 'See All',
  'all'               => 'All',
  'app'               => 'App',
  'apps_label'        => 'apps',
  'version'           => 'Version',
  'size'              => 'Size',
  'rating'            => 'Rating',
  'developer'         => 'Developer',
  'category'          => 'Category',
  'free'              => 'Free',
  'android'           => 'Android',
  'latest'            => 'Latest',
  'language'          => 'Language',
  'all_rights'        => 'All rights reserved',
  'share'             => 'Share',
  'copy_link'         => 'Copy Link',
  'report'            => 'Report',
  'verified'          => 'Verified',
],

/* ── French ────────────────────────────────────────────── */
'fr' => [
  'home'=>'Accueil','apps'=>'Applications','games'=>'Jeux',
  'top_downloads'=>'Plus téléchargés','updates'=>'Mises à jour',
  'tools'=>'Outils','more'=>'Plus','about'=>'À propos','contact'=>'Contact',
  'search_placeholder'=>'Rechercher une application...','search'=>'Rechercher',
  'download'=>'Télécharger','download_apk'=>'Télécharger APK',
  'show_more'=>'Voir plus','show_less'=>'Voir moins',
  'latest_apps'=>'Dernières applications','see_all'=>'Voir tout',
  'all'=>'Tout','close'=>'Fermer','menu'=>'Menu','language'=>'Langue',
  'all_rights'=>'Tous droits réservés','free'=>'Gratuit',
],

/* ── Turkish ───────────────────────────────────────────── */
'tr' => [
  'home'=>'Anasayfa','apps'=>'Uygulamalar','games'=>'Oyunlar',
  'top_downloads'=>'En Çok İndirilenler','updates'=>'Güncellemeler',
  'tools'=>'Araçlar','more'=>'Daha Fazla','about'=>'Hakkımızda','contact'=>'İletişim',
  'search_placeholder'=>'Uygulama veya oyun ara...','search'=>'Ara',
  'download'=>'İndir','download_apk'=>'APK İndir',
  'show_more'=>'Daha Fazla Göster','show_less'=>'Daha Az Göster',
  'latest_apps'=>'Son Uygulamalar','see_all'=>'Tümünü Gör',
  'all'=>'Tümü','close'=>'Kapat','menu'=>'Menü','language'=>'Dil',
  'all_rights'=>'Tüm hakları saklıdır','free'=>'Ücretsiz',
],

/* ── German ────────────────────────────────────────────── */
'de' => [
  'home'=>'Startseite','apps'=>'Apps','games'=>'Spiele',
  'top_downloads'=>'Top Downloads','updates'=>'Aktualisierungen',
  'tools'=>'Tools','more'=>'Mehr','about'=>'Über uns','contact'=>'Kontakt',
  'search_placeholder'=>'App oder Spiel suchen...','search'=>'Suchen',
  'download'=>'Herunterladen','download_apk'=>'APK Herunterladen',
  'show_more'=>'Mehr anzeigen','show_less'=>'Weniger anzeigen',
  'latest_apps'=>'Neueste Apps','see_all'=>'Alle anzeigen',
  'all'=>'Alle','close'=>'Schließen','menu'=>'Menü','language'=>'Sprache',
  'all_rights'=>'Alle Rechte vorbehalten','free'=>'Kostenlos',
],

/* ── Spanish ───────────────────────────────────────────── */
'es' => [
  'home'=>'Inicio','apps'=>'Aplicaciones','games'=>'Juegos',
  'top_downloads'=>'Más descargados','updates'=>'Actualizaciones',
  'tools'=>'Herramientas','more'=>'Más','about'=>'Acerca de','contact'=>'Contacto',
  'search_placeholder'=>'Buscar aplicación o juego...','search'=>'Buscar',
  'download'=>'Descargar','download_apk'=>'Descargar APK',
  'show_more'=>'Mostrar más','show_less'=>'Mostrar menos',
  'latest_apps'=>'Últimas aplicaciones','see_all'=>'Ver todo',
  'all'=>'Todo','close'=>'Cerrar','menu'=>'Menú','language'=>'Idioma',
  'all_rights'=>'Todos los derechos reservados','free'=>'Gratis',
],

/* ── Portuguese ────────────────────────────────────────── */
'pt' => [
  'home'=>'Início','apps'=>'Aplicativos','games'=>'Jogos',
  'top_downloads'=>'Mais baixados','updates'=>'Atualizações',
  'tools'=>'Ferramentas','more'=>'Mais','about'=>'Sobre nós','contact'=>'Contato',
  'search_placeholder'=>'Buscar aplicativo ou jogo...','search'=>'Buscar',
  'download'=>'Baixar','download_apk'=>'Baixar APK',
  'show_more'=>'Ver mais','show_less'=>'Ver menos',
  'latest_apps'=>'Últimos aplicativos','see_all'=>'Ver tudo',
  'all'=>'Todos','close'=>'Fechar','menu'=>'Menu','language'=>'Idioma',
  'all_rights'=>'Todos os direitos reservados','free'=>'Grátis',
],

/* ── Russian ───────────────────────────────────────────── */
'ru' => [
  'home'=>'Главная','apps'=>'Приложения','games'=>'Игры',
  'top_downloads'=>'Топ загрузок','updates'=>'Обновления',
  'tools'=>'Инструменты','more'=>'Ещё','about'=>'О нас','contact'=>'Контакты',
  'search_placeholder'=>'Поиск приложения или игры...','search'=>'Найти',
  'download'=>'Скачать','download_apk'=>'Скачать APK',
  'show_more'=>'Показать больше','show_less'=>'Показать меньше',
  'latest_apps'=>'Последние приложения','see_all'=>'Смотреть все',
  'all'=>'Все','close'=>'Закрыть','menu'=>'Меню','language'=>'Язык',
  'all_rights'=>'Все права защищены','free'=>'Бесплатно',
],

/* ── Persian / Farsi (RTL) ─────────────────────────────── */
'fa' => [
  'home'=>'خانه','apps'=>'برنامه‌ها','games'=>'بازی‌ها',
  'top_downloads'=>'پرتریج‌ترین','updates'=>'به‌روزرسانی‌ها',
  'tools'=>'ابزارها','more'=>'بیشتر','about'=>'درباره ما','contact'=>'تماس',
  'search_placeholder'=>'جستجوی برنامه یا بازی...','search'=>'جستجو',
  'download'=>'دانلود','download_apk'=>'دانلود APK',
  'show_more'=>'نمایش بیشتر','show_less'=>'نمایش کمتر',
  'latest_apps'=>'آخرین برنامه‌ها','see_all'=>'مشاهده همه',
  'all'=>'همه','close'=>'بستن','menu'=>'منو','language'=>'زبان',
  'all_rights'=>'تمامی حقوق محفوظ است','free'=>'رایگان',
],

/* ── Urdu (RTL) ────────────────────────────────────────── */
'ur' => [
  'home'=>'گھر','apps'=>'ایپس','games'=>'گیمز',
  'top_downloads'=>'سب سے زیادہ ڈاؤنلوڈ','updates'=>'اپڈیٹس',
  'tools'=>'ٹولز','more'=>'مزید','about'=>'ہمارے بارے میں','contact'=>'رابطہ',
  'search_placeholder'=>'ایپ یا گیم تلاش کریں...','search'=>'تلاش',
  'download'=>'ڈاؤنلوڈ','download_apk'=>'APK ڈاؤنلوڈ',
  'show_more'=>'مزید دکھائیں','show_less'=>'کم دکھائیں',
  'latest_apps'=>'تازہ ترین ایپس','see_all'=>'سب دیکھیں',
  'all'=>'سب','close'=>'بند کریں','menu'=>'مینو','language'=>'زبان',
  'all_rights'=>'جملہ حقوق محفوظ ہیں','free'=>'مفت',
],

/* ── Indonesian ────────────────────────────────────────── */
'id' => [
  'home'=>'Beranda','apps'=>'Aplikasi','games'=>'Game',
  'top_downloads'=>'Paling Banyak Diunduh','updates'=>'Pembaruan',
  'tools'=>'Alat','more'=>'Lainnya','about'=>'Tentang Kami','contact'=>'Kontak',
  'search_placeholder'=>'Cari aplikasi atau game...','search'=>'Cari',
  'download'=>'Unduh','download_apk'=>'Unduh APK',
  'show_more'=>'Tampilkan Lebih Banyak','show_less'=>'Tampilkan Lebih Sedikit',
  'latest_apps'=>'Aplikasi Terbaru','see_all'=>'Lihat Semua',
  'all'=>'Semua','close'=>'Tutup','menu'=>'Menu','language'=>'Bahasa',
  'all_rights'=>'Hak cipta dilindungi','free'=>'Gratis',
],

/* ── Chinese ───────────────────────────────────────────── */
'zh' => [
  'home'=>'首页','apps'=>'应用','games'=>'游戏',
  'top_downloads'=>'最多下载','updates'=>'更新',
  'tools'=>'工具','more'=>'更多','about'=>'关于我们','contact'=>'联系',
  'search_placeholder'=>'搜索应用或游戏...','search'=>'搜索',
  'download'=>'下载','download_apk'=>'下载 APK',
  'show_more'=>'显示更多','show_less'=>'显示较少',
  'latest_apps'=>'最新应用','see_all'=>'查看全部',
  'all'=>'全部','close'=>'关闭','menu'=>'菜单','language'=>'语言',
  'all_rights'=>'版权所有','free'=>'免费',
],

/* ── Japanese ──────────────────────────────────────────── */
'ja' => [
  'home'=>'ホーム','apps'=>'アプリ','games'=>'ゲーム',
  'top_downloads'=>'ダウンロード数トップ','updates'=>'更新',
  'tools'=>'ツール','more'=>'もっと見る','about'=>'私たちについて','contact'=>'連絡先',
  'search_placeholder'=>'アプリやゲームを検索...','search'=>'検索',
  'download'=>'ダウンロード','download_apk'=>'APK ダウンロード',
  'show_more'=>'もっと見る','show_less'=>'閉じる',
  'latest_apps'=>'最新アプリ','see_all'=>'すべて見る',
  'all'=>'すべて','close'=>'閉じる','menu'=>'メニュー','language'=>'言語',
  'all_rights'=>'全著作権所有','free'=>'無料',
],

/* ── Korean ────────────────────────────────────────────── */
'ko' => [
  'home'=>'홈','apps'=>'앱','games'=>'게임',
  'top_downloads'=>'인기 다운로드','updates'=>'업데이트',
  'tools'=>'도구','more'=>'더보기','about'=>'소개','contact'=>'문의',
  'search_placeholder'=>'앱 또는 게임 검색...','search'=>'검색',
  'download'=>'다운로드','download_apk'=>'APK 다운로드',
  'show_more'=>'더 보기','show_less'=>'접기',
  'latest_apps'=>'최신 앱','see_all'=>'전체 보기',
  'all'=>'전체','close'=>'닫기','menu'=>'메뉴','language'=>'언어',
  'all_rights'=>'모든 권리 보유','free'=>'무료',
],

/* ── Hindi ─────────────────────────────────────────────── */
'hi' => [
  'home'=>'होम','apps'=>'ऐप्स','games'=>'गेम्स',
  'top_downloads'=>'सबसे ज्यादा डाउनलोड','updates'=>'अपडेट',
  'tools'=>'टूल्स','more'=>'और','about'=>'हमारे बारे में','contact'=>'संपर्क',
  'search_placeholder'=>'ऐप या गेम खोजें...','search'=>'खोजें',
  'download'=>'डाउनलोड','download_apk'=>'APK डाउनलोड',
  'show_more'=>'और दिखाएं','show_less'=>'कम दिखाएं',
  'latest_apps'=>'नवीनतम ऐप्स','see_all'=>'सभी देखें',
  'all'=>'सभी','close'=>'बंद करें','menu'=>'मेनू','language'=>'भाषा',
  'all_rights'=>'सर्वाधिकार सुरक्षित','free'=>'मुफ्त',
],

/* ── Italian ───────────────────────────────────────────── */
'it' => [
  'home'=>'Home','apps'=>'Applicazioni','games'=>'Giochi',
  'top_downloads'=>'Più scaricati','updates'=>'Aggiornamenti',
  'tools'=>'Strumenti','more'=>'Altro','about'=>'Chi siamo','contact'=>'Contatti',
  'search_placeholder'=>'Cerca app o gioco...','search'=>'Cerca',
  'download'=>'Scarica','download_apk'=>'Scarica APK',
  'show_more'=>'Mostra di più','show_less'=>'Mostra meno',
  'latest_apps'=>'Ultime app','see_all'=>'Vedi tutto',
  'all'=>'Tutti','close'=>'Chiudi','menu'=>'Menu','language'=>'Lingua',
  'all_rights'=>'Tutti i diritti riservati','free'=>'Gratuito',
],

/* ── Dutch ─────────────────────────────────────────────── */
'nl' => [
  'home'=>'Home','apps'=>'Apps','games'=>'Games',
  'top_downloads'=>'Meest gedownload','updates'=>'Updates',
  'tools'=>'Tools','more'=>'Meer','about'=>'Over ons','contact'=>'Contact',
  'search_placeholder'=>'Zoek app of spel...','search'=>'Zoeken',
  'download'=>'Downloaden','download_apk'=>'APK Downloaden',
  'show_more'=>'Meer weergeven','show_less'=>'Minder weergeven',
  'latest_apps'=>'Nieuwste apps','see_all'=>'Alles weergeven',
  'all'=>'Alles','close'=>'Sluiten','menu'=>'Menu','language'=>'Taal',
  'all_rights'=>'Alle rechten voorbehouden','free'=>'Gratis',
],

];

/* ─────────────────────────────────────────────────────────
   RTL Languages
──────────────────────────────────────────────────────────── */
$_RTL_LANGS = ['ar', 'fa', 'ur', 'he', 'yi'];

/* ─────────────────────────────────────────────────────────
   Language display names
──────────────────────────────────────────────────────────── */
$_LANG_NAMES = [
  'ar'=>'العربية','en'=>'English','fr'=>'Français','tr'=>'Türkçe',
  'de'=>'Deutsch','es'=>'Español','pt'=>'Português','ru'=>'Русский',
  'fa'=>'فارسی','ur'=>'اردو','id'=>'Indonesia','zh'=>'中文',
  'ja'=>'日本語','ko'=>'한국어','hi'=>'हिन्दी','it'=>'Italiano','nl'=>'Nederlands',
];

/* ─────────────────────────────────────────────────────────
   Handle ?lang= switching — must run before any output
──────────────────────────────────────────────────────────── */
if (!empty($_GET['lang']) && !headers_sent()) {
    $__r = preg_replace('/[^a-z]/', '', strtolower($_GET['lang']));
    if (array_key_exists($__r, $_TRANSLATIONS)) {
        setcookie('ui_lang', $__r, time() + 365*24*3600, '/', '', false, false);
        $_COOKIE['ui_lang'] = $__r;
        // Redirect to clean URL (remove ?lang=)
        $__uri = strtok($_SERVER['REQUEST_URI'], '?');
        $__q   = $_GET;
        unset($__q['lang']);
        if ($__q) $__uri .= '?' . http_build_query($__q, '', '&');
        header('Location: ' . $__uri, true, 302);
        exit;
    }
    unset($__r, $__uri, $__q);
}

/* ─────────────────────────────────────────────────────────
   detect_ui_lang()
──────────────────────────────────────────────────────────── */
function detect_ui_lang(): string {
    global $_TRANSLATIONS;

    // 1. Cookie preference (user manually selected)
    if (!empty($_COOKIE['ui_lang'])) {
        $c = preg_replace('/[^a-z]/', '', strtolower($_COOKIE['ui_lang']));
        if (isset($_TRANSLATIONS[$c])) return $c;
    }

    // 2. HTTP Accept-Language header
    $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    if ($accept) {
        // e.g. "ar-SA,ar;q=0.9,en-US;q=0.8,en;q=0.7"
        preg_match_all('/([a-z]{2,3})(?:-[A-Z]{2,3})?(?:;q=([\d.]+))?/i', $accept, $m);
        $found = [];
        foreach ($m[1] as $i => $code) {
            $code = strtolower(substr($code, 0, 2));
            $q    = isset($m[2][$i]) && $m[2][$i] !== '' ? (float)$m[2][$i] : 1.0;
            $found[$code] = max($found[$code] ?? 0, $q);
        }
        arsort($found);
        foreach (array_keys($found) as $code) {
            if (isset($_TRANSLATIONS[$code])) return $code;
        }
    }

    return 'ar';
}

/* ─────────────────────────────────────────────────────────
   is_rtl_lang($lang?)
──────────────────────────────────────────────────────────── */
function is_rtl_lang(string $lang = ''): bool {
    global $_RTL_LANGS;
    if (!$lang) $lang = $GLOBALS['UI_LANG'] ?? 'ar';
    return in_array($lang, $_RTL_LANGS, true);
}

/* ─────────────────────────────────────────────────────────
   __($key, $fallback = '')
──────────────────────────────────────────────────────────── */
function __(string $key, string $fallback = ''): string {
    global $_TRANSLATIONS;
    $lang = $GLOBALS['UI_LANG'] ?? 'ar';
    return $_TRANSLATIONS[$lang][$key]
        ?? $_TRANSLATIONS['en'][$key]
        ?? $_TRANSLATIONS['ar'][$key]
        ?? ($fallback ?: $key);
}

/* ─────────────────────────────────────────────────────────
   lang_name($lang)
──────────────────────────────────────────────────────────── */
function lang_name(string $lang): string {
    global $_LANG_NAMES;
    return $_LANG_NAMES[$lang] ?? strtoupper($lang);
}
