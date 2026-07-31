<header class="site-header">
  <div class="container header-inner">
    <a href="/" class="logo" aria-label="yassota" style="display:flex;flex-direction:column;line-height:1;gap:2px">
      <span style="font-size:1.2rem;font-weight:900"><span class="logo-yas">yas</span><span class="logo-sota">sota</span></span>
      <span class="logo-tagline">دليل تطبيقات أندرويد</span>
    </a>

    <nav class="desktop-nav" aria-label="التنقل الرئيسي">
      <a href="/exchange" class="nav-link<?= active('exchange',$page) ?>">💱 الصرف</a>
      <a href="/gold"     class="nav-link<?= active('gold',$page) ?>">🥇 الذهب</a>
      <a href="/fuel"     class="nav-link<?= active('fuel',$page) ?>">⛽ المحروقات</a>
      <a href="/phones"   class="nav-link<?= active('phones',$page) ?>">📱 الهواتف</a>
      <a href="/pdf"      class="nav-link<?= active('pdf',$page) ?>">📄 PDF</a>
      <a href="/images"   class="nav-link<?= active('images',$page) ?>">🖼️ صور</a>
      <a href="/calculators" class="nav-link<?= active('calculators',$page) ?>">🏗️ البناء</a>
      <a href="/solar"    class="nav-link<?= active('solar',$page) ?>">☀️ شمسية</a>
      <a href="/ai"       class="nav-link<?= active('ai',$page) ?>">🤖 AI</a>
      <a href="/articles" class="nav-link<?= active('articles',$page) ?>">📰 مقالات</a>
    </nav>

    <div class="header-actions">
      <button class="search-btn" onclick="toggleSearch()" aria-label="بحث">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      </button>
      <button class="hamburger" onclick="toggleMobileNav()" aria-label="القائمة" aria-expanded="false" id="hamburger">
        <svg width="18" height="18" viewBox="0 0 18 18" fill="currentColor" aria-hidden="true">
          <circle cx="9" cy="3" r="2.2"/>
          <circle cx="9" cy="9" r="2.2"/>
          <circle cx="9" cy="15" r="2.2"/>
        </svg>
      </button>
    </div>
  </div>

  <!-- Mobile search dropdown -->
  <div class="mobile-search" id="mobileSearch" hidden>
    <div class="container">
      <form action="/articles" method="get" role="search">
        <input type="search" name="q" placeholder="ابحث في الموقع..." autofocus>
        <button type="submit">بحث</button>
      </form>
    </div>
  </div>
</header>

<!-- Mobile nav drawer -->
<nav class="mobile-nav" id="mobileNav" aria-label="القائمة المتنقلة" hidden>
  <div class="mobile-nav-header">
    <span><span class="logo-yas">yas</span><span class="logo-sota">sota</span></span>
    <button onclick="toggleMobileNav()" aria-label="إغلاق">✕</button>
  </div>
  <a href="/"           class="mnav-link">🏠 الرئيسية</a>
  <div class="mnav-divider">الأسعار</div>
  <a href="/exchange"   class="mnav-link">💱 أسعار الصرف</a>
  <a href="/gold"       class="mnav-link">🥇 أسعار الذهب</a>
  <a href="/fuel"       class="mnav-link">⛽ أسعار المحروقات</a>
  <a href="/food"       class="mnav-link">🥦 أسعار الغذاء</a>
  <a href="/phones"     class="mnav-link">📱 أسعار الهواتف</a>
  <div class="mnav-divider">الأدوات</div>
  <a href="/pdf"        class="mnav-link">📄 أدوات PDF</a>
  <a href="/images"     class="mnav-link">🖼️ أدوات الصور</a>
  <a href="/ai"         class="mnav-link">🤖 أدوات AI</a>
  <div class="mnav-divider">الحاسبات</div>
  <a href="/calculators" class="mnav-link">🏗️ حاسبات البناء</a>
  <a href="/solar"      class="mnav-link">☀️ الطاقة الشمسية</a>
  <a href="/articles"   class="mnav-link">📰 المقالات</a>
  <div class="mnav-divider">المواقع الفرعية</div>
  <a href="https://shorts.<?= h(parse_url(SITE_URL,PHP_URL_HOST)) ?>" class="mnav-link" target="_blank">▶️ يوتيوب شورت</a>
  <a href="https://tiktok.<?= h(parse_url(SITE_URL,PHP_URL_HOST)) ?>" class="mnav-link" target="_blank">🎵 تيك توك</a>
  <a href="https://ocr.<?= h(parse_url(SITE_URL,PHP_URL_HOST)) ?>"   class="mnav-link" target="_blank">📷 استخراج النصوص</a>
</nav>
<div class="mobile-nav-overlay" id="navOverlay" onclick="toggleMobileNav()"></div>
