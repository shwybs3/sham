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
  'free_download'     => 'تحميل مجاني',
  'google_play'       => 'Google Play',
  'mirror'            => 'مرآة',
  'mirror_2'          => 'مرآة 2',
  'mirror_3'          => 'مرآة 3',
  'captcha_q'         => 'أجب للتأكد من أنك لست روبوتاً',
  'captcha_verify'    => 'تحقق وحمّل',
  'captcha_wrong'     => 'إجابة خاطئة، حاول مرة أخرى',
  /* Sections */
  'latest_apps'       => 'أحدث التطبيقات',
  'latest_updates'    => 'آخر التحديثات',
  'related_apps'      => 'تطبيقات مشابهة',
  'alternatives'      => 'بدائل مقترحة',
  'similar_apps'      => 'تطبيقات مشابهة',
  'screenshots'       => 'لقطات الشاشة',
  'whats_new'         => 'ما الجديد',
  'app_info'          => 'معلومات التطبيق',
  'reviews'           => 'التقييمات والتعليقات',
  'versions'          => 'سجل الإصدارات',
  'show_more'         => 'عرض المزيد',
  'show_less'         => 'عرض أقل',
  'see_all'           => 'عرض الكل',
  'all'               => 'الكل',
  /* Badges */
  'badge_new'         => 'جديد',
  'badge_updated'     => 'محدّث',
  'badge_hot'         => 'رائج',
  'badge_choice'      => 'اختيار المحرر',
  'hosted_apk'        => 'APK مستضاف',
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
  /* App sections */
  'description'       => 'الوصف',
  'features'          => 'المميزات',
  'pros_cons'         => 'الإيجابيات والسلبيات',
  'install_steps'     => 'طريقة التثبيت',
  'terms'             => 'شروط الاستخدام',
  'related_articles'  => 'مقالات ذات صلة',
  'version_history'   => 'سجل التحديثات',
  'ratings_reviews'   => 'التعليقات والتقييمات',
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
  'free_download'     => 'Free Download',
  'google_play'       => 'Google Play',
  'mirror'            => 'Mirror',
  'mirror_2'          => 'Mirror 2',
  'mirror_3'          => 'Mirror 3',
  'captcha_q'         => 'Solve to verify you\'re not a robot',
  'captcha_verify'    => 'Verify & Download',
  'captcha_wrong'     => 'Wrong answer, please try again',
  'latest_apps'       => 'Latest Apps',
  'latest_updates'    => 'Latest Updates',
  'related_apps'      => 'Similar Apps',
  'alternatives'      => 'Suggested Alternatives',
  'similar_apps'      => 'Similar Apps',
  'screenshots'       => 'Screenshots',
  'whats_new'         => 'What\'s New',
  'app_info'          => 'App Info',
  'reviews'           => 'Ratings & Reviews',
  'versions'          => 'Version History',
  'show_more'         => 'Show More',
  'show_less'         => 'Show Less',
  'see_all'           => 'See All',
  'all'               => 'All',
  'badge_new'         => 'New',
  'badge_updated'     => 'Updated',
  'badge_hot'         => 'Trending',
  'badge_choice'      => 'Editor\'s Choice',
  'hosted_apk'        => 'Hosted APK',
  'description'       => 'Description',
  'features'          => 'Features',
  'pros_cons'         => 'Pros & Cons',
  'install_steps'     => 'How to Install',
  'terms'             => 'Terms of Use',
  'related_articles'  => 'Related Articles',
  'version_history'   => 'Version History',
  'ratings_reviews'   => 'Ratings & Reviews',
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
  'free_download'=>'Téléchargement gratuit','mirror_2'=>'Miroir 2','mirror_3'=>'Miroir 3',
  'captcha_q'=>'Résoudre pour vérifier','captcha_verify'=>'Vérifier & Télécharger',
  'captcha_wrong'=>'Réponse incorrecte, réessayez',
  'alternatives'=>'Alternatives suggérées','similar_apps'=>'Applications similaires',
  'screenshots'=>'Captures d\'écran','whats_new'=>'Nouveautés',
  'app_info'=>'Infos app','reviews'=>'Avis & Notes','versions'=>'Historique des versions',
  'badge_new'=>'Nouveau','badge_updated'=>'Mis à jour','badge_hot'=>'Tendance',
  'badge_choice'=>'Choix de l\'éditeur','hosted_apk'=>'APK hébergé',
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
  'free_download'=>'Ücretsiz İndir','mirror_2'=>'Ayna 2','mirror_3'=>'Ayna 3',
  'captcha_q'=>'Robot olmadığını doğrula','captcha_verify'=>'Doğrula & İndir',
  'captcha_wrong'=>'Yanlış cevap, tekrar dene',
  'alternatives'=>'Önerilen Alternatifler','similar_apps'=>'Benzer Uygulamalar',
  'screenshots'=>'Ekran Görüntüleri','whats_new'=>'Yenilikler',
  'app_info'=>'Uygulama Bilgisi','reviews'=>'Değerlendirmeler','versions'=>'Sürüm Geçmişi',
  'badge_new'=>'Yeni','badge_updated'=>'Güncellendi','badge_hot'=>'Trend',
  'badge_choice'=>'Editörün Seçimi','hosted_apk'=>'Barındırılan APK',
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
  'free_download'=>'Kostenlos herunterladen','mirror_2'=>'Spiegel 2','mirror_3'=>'Spiegel 3',
  'captcha_q'=>'Lösen um zu bestätigen','captcha_verify'=>'Bestätigen & Herunterladen',
  'captcha_wrong'=>'Falsche Antwort, erneut versuchen',
  'alternatives'=>'Empfohlene Alternativen','similar_apps'=>'Ähnliche Apps',
  'screenshots'=>'Screenshots','whats_new'=>'Was ist neu',
  'app_info'=>'App-Info','reviews'=>'Bewertungen','versions'=>'Versionsverlauf',
  'badge_new'=>'Neu','badge_updated'=>'Aktualisiert','badge_hot'=>'Trending',
  'badge_choice'=>'Editors Wahl','hosted_apk'=>'Gehostete APK',
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
  'free_download'=>'Descargar gratis','mirror_2'=>'Espejo 2','mirror_3'=>'Espejo 3',
  'captcha_q'=>'Resuelve para verificar','captcha_verify'=>'Verificar y descargar',
  'captcha_wrong'=>'Respuesta incorrecta, inténtalo de nuevo',
  'alternatives'=>'Alternativas sugeridas','similar_apps'=>'Aplicaciones similares',
  'screenshots'=>'Capturas de pantalla','whats_new'=>'Novedades',
  'app_info'=>'Info de la app','reviews'=>'Reseñas','versions'=>'Historial de versiones',
  'badge_new'=>'Nuevo','badge_updated'=>'Actualizado','badge_hot'=>'Tendencia',
  'badge_choice'=>'Elección del editor','hosted_apk'=>'APK alojado',
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
  'free_download'=>'Download gratuito','mirror_2'=>'Espelho 2','mirror_3'=>'Espelho 3',
  'captcha_q'=>'Resolva para verificar','captcha_verify'=>'Verificar e baixar',
  'captcha_wrong'=>'Resposta errada, tente novamente',
  'alternatives'=>'Alternativas sugeridas','similar_apps'=>'Aplicativos similares',
  'screenshots'=>'Capturas de tela','whats_new'=>'Novidades',
  'app_info'=>'Info do app','reviews'=>'Avaliações','versions'=>'Histórico de versões',
  'badge_new'=>'Novo','badge_updated'=>'Atualizado','badge_hot'=>'Em alta',
  'badge_choice'=>'Escolha do editor','hosted_apk'=>'APK hospedado',
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
  'free_download'=>'Скачать бесплатно','mirror_2'=>'Зеркало 2','mirror_3'=>'Зеркало 3',
  'captcha_q'=>'Решите для подтверждения','captcha_verify'=>'Подтвердить и скачать',
  'captcha_wrong'=>'Неверный ответ, попробуйте снова',
  'alternatives'=>'Предлагаемые альтернативы','similar_apps'=>'Похожие приложения',
  'screenshots'=>'Скриншоты','whats_new'=>'Что нового',
  'app_info'=>'Об приложении','reviews'=>'Отзывы и оценки','versions'=>'История версий',
  'badge_new'=>'Новое','badge_updated'=>'Обновлено','badge_hot'=>'В тренде',
  'badge_choice'=>'Выбор редактора','hosted_apk'=>'Размещённый APK',
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
  'free_download'=>'دانلود رایگان','mirror_2'=>'آینه ۲','mirror_3'=>'آینه ۳',
  'captcha_q'=>'برای تأیید حل کنید','captcha_verify'=>'تأیید و دانلود',
  'captcha_wrong'=>'پاسخ اشتباه است، دوباره امتحان کنید',
  'alternatives'=>'جایگزین‌های پیشنهادی','similar_apps'=>'برنامه‌های مشابه',
  'screenshots'=>'تصاویر','whats_new'=>'تازه‌ها',
  'app_info'=>'اطلاعات برنامه','reviews'=>'نظرات و امتیازات','versions'=>'تاریخچه نسخه',
  'badge_new'=>'جدید','badge_updated'=>'به‌روز شده','badge_hot'=>'پرطرفدار',
  'badge_choice'=>'انتخاب سردبیر','hosted_apk'=>'APK میزبانی شده',
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
  'free_download'=>'مفت ڈاؤنلوڈ','mirror_2'=>'آئینہ ۲','mirror_3'=>'آئینہ ۳',
  'captcha_q'=>'تصدیق کے لیے حل کریں','captcha_verify'=>'تصدیق کریں اور ڈاؤنلوڈ کریں',
  'captcha_wrong'=>'غلط جواب، دوبارہ کوشش کریں',
  'alternatives'=>'تجویز کردہ متبادل','similar_apps'=>'ملتی جلتی ایپس',
  'screenshots'=>'اسکرین شاٹس','whats_new'=>'نیا کیا ہے',
  'app_info'=>'ایپ کی معلومات','reviews'=>'جائزے','versions'=>'ورژن کی تاریخ',
  'badge_new'=>'نیا','badge_updated'=>'اپ ڈیٹ','badge_hot'=>'ٹرینڈنگ',
  'badge_choice'=>'ایڈیٹر کی پسند','hosted_apk'=>'میزبان APK',
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
  'free_download'=>'Unduh Gratis','mirror_2'=>'Cermin 2','mirror_3'=>'Cermin 3',
  'captcha_q'=>'Selesaikan untuk verifikasi','captcha_verify'=>'Verifikasi & Unduh',
  'captcha_wrong'=>'Jawaban salah, coba lagi',
  'alternatives'=>'Alternatif yang Disarankan','similar_apps'=>'Aplikasi Serupa',
  'screenshots'=>'Tangkapan Layar','whats_new'=>'Yang Baru',
  'app_info'=>'Info Aplikasi','reviews'=>'Ulasan','versions'=>'Riwayat Versi',
  'badge_new'=>'Baru','badge_updated'=>'Diperbarui','badge_hot'=>'Trending',
  'badge_choice'=>'Pilihan Editor','hosted_apk'=>'APK yang Dihosting',
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
  'free_download'=>'免费下载','mirror_2'=>'镜像 2','mirror_3'=>'镜像 3',
  'captcha_q'=>'解题以验证','captcha_verify'=>'验证并下载',
  'captcha_wrong'=>'答案错误，请重试',
  'alternatives'=>'推荐替代品','similar_apps'=>'类似应用',
  'screenshots'=>'截图','whats_new'=>'新功能',
  'app_info'=>'应用信息','reviews'=>'评价','versions'=>'版本历史',
  'badge_new'=>'新','badge_updated'=>'已更新','badge_hot'=>'热门',
  'badge_choice'=>'编辑推荐','hosted_apk'=>'托管 APK',
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
  'free_download'=>'無料ダウンロード','mirror_2'=>'ミラー 2','mirror_3'=>'ミラー 3',
  'captcha_q'=>'確認のため解いてください','captcha_verify'=>'確認してダウンロード',
  'captcha_wrong'=>'間違えています、もう一度お試しください',
  'alternatives'=>'おすすめの代替アプリ','similar_apps'=>'類似アプリ',
  'screenshots'=>'スクリーンショット','whats_new'=>'新機能',
  'app_info'=>'アプリ情報','reviews'=>'レビュー','versions'=>'バージョン履歴',
  'badge_new'=>'新着','badge_updated'=>'更新済み','badge_hot'=>'人気',
  'badge_choice'=>'編集部のおすすめ','hosted_apk'=>'ホスト型 APK',
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
  'free_download'=>'무료 다운로드','mirror_2'=>'미러 2','mirror_3'=>'미러 3',
  'captcha_q'=>'확인을 위해 풀어주세요','captcha_verify'=>'확인 후 다운로드',
  'captcha_wrong'=>'틀렸습니다, 다시 시도하세요',
  'alternatives'=>'추천 대안','similar_apps'=>'유사 앱',
  'screenshots'=>'스크린샷','whats_new'=>'새 기능',
  'app_info'=>'앱 정보','reviews'=>'리뷰','versions'=>'버전 기록',
  'badge_new'=>'신규','badge_updated'=>'업데이트됨','badge_hot'=>'인기',
  'badge_choice'=>'에디터 추천','hosted_apk'=>'호스팅 APK',
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
  'free_download'=>'मुफ्त डाउनलोड','mirror_2'=>'मिरर 2','mirror_3'=>'मिरर 3',
  'captcha_q'=>'सत्यापन के लिए हल करें','captcha_verify'=>'सत्यापित करें और डाउनलोड करें',
  'captcha_wrong'=>'गलत उत्तर, पुनः प्रयास करें',
  'alternatives'=>'सुझाए गए विकल्प','similar_apps'=>'समान ऐप्स',
  'screenshots'=>'स्क्रीनशॉट','whats_new'=>'नया क्या है',
  'app_info'=>'ऐप जानकारी','reviews'=>'समीक्षाएं','versions'=>'वर्शन इतिहास',
  'badge_new'=>'नया','badge_updated'=>'अपडेट','badge_hot'=>'ट्रेंडिंग',
  'badge_choice'=>'संपादक की पसंद','hosted_apk'=>'होस्टेड APK',
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
  'free_download'=>'Scarica gratis','mirror_2'=>'Specchio 2','mirror_3'=>'Specchio 3',
  'captcha_q'=>'Risolvi per verificare','captcha_verify'=>'Verifica e scarica',
  'captcha_wrong'=>'Risposta errata, riprova',
  'alternatives'=>'Alternative consigliate','similar_apps'=>'App simili',
  'screenshots'=>'Schermate','whats_new'=>'Novità',
  'app_info'=>'Info app','reviews'=>'Recensioni','versions'=>'Cronologia versioni',
  'badge_new'=>'Nuovo','badge_updated'=>'Aggiornato','badge_hot'=>'Tendenza',
  'badge_choice'=>'Scelta della redazione','hosted_apk'=>'APK ospitato',
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
  'free_download'=>'Gratis downloaden','mirror_2'=>'Spiegel 2','mirror_3'=>'Spiegel 3',
  'captcha_q'=>'Los op om te verifiëren','captcha_verify'=>'Verifiëren en downloaden',
  'captcha_wrong'=>'Verkeerd antwoord, probeer opnieuw',
  'alternatives'=>'Aanbevolen alternatieven','similar_apps'=>'Vergelijkbare apps',
  'screenshots'=>'Schermafbeeldingen','whats_new'=>'Nieuw',
  'app_info'=>'App-info','reviews'=>'Beoordelingen','versions'=>'Versiegeschiedenis',
  'badge_new'=>'Nieuw','badge_updated'=>'Bijgewerkt','badge_hot'=>'Trending',
  'badge_choice'=>'Redactiekeuze','hosted_apk'=>'Gehoste APK',
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
