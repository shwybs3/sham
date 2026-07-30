<footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-brand">
      <a href="/" class="logo">
        <span class="logo-icon"><?= SITE_LOGO ?></span>
        <span class="logo-text"><?= h(SITE_NAME) ?></span>
      </a>
      <p class="footer-tagline"><?= h(SITE_TAGLINE) ?></p>
      <p class="footer-note">جميع الأسعار للاسترشاد فقط · يُرجى التحقق من المصادر الرسمية</p>
    </div>
    <div class="footer-col">
      <h3>الأسعار</h3>
      <a href="/exchange">أسعار الصرف</a>
      <a href="/gold">أسعار الذهب</a>
      <a href="/fuel">أسعار المحروقات</a>
      <a href="/food">أسعار الغذاء</a>
      <a href="/phones">أسعار الهواتف</a>
    </div>
    <div class="footer-col">
      <h3>الأدوات</h3>
      <a href="/pdf">أدوات PDF</a>
      <a href="/images">أدوات الصور</a>
      <a href="/ai">أدوات AI</a>
      <a href="/calculators">حاسبات البناء</a>
      <a href="/solar">الطاقة الشمسية</a>
    </div>
    <div class="footer-col">
      <h3>المواقع الفرعية</h3>
      <?php $host = parse_url(SITE_URL, PHP_URL_HOST) ?? 'your-domain.com'; ?>
      <a href="https://shorts.<?= h($host) ?>" target="_blank" rel="noopener">▶️ يوتيوب شورت</a>
      <a href="https://tiktok.<?= h($host) ?>"  target="_blank" rel="noopener">🎵 تيك توك</a>
      <a href="https://ocr.<?= h($host) ?>"    target="_blank" rel="noopener">📷 استخراج النصوص</a>
      <a href="https://pdf.<?= h($host) ?>"    target="_blank" rel="noopener">📄 PDF</a>
      <a href="https://translate.<?= h($host) ?>" target="_blank" rel="noopener">🌐 ترجمة</a>
    </div>
    <div class="footer-col">
      <h3>روابط</h3>
      <a href="/articles">المقالات</a>
      <a href="/about">من نحن</a>
      <a href="/contact">اتصل بنا</a>
      <a href="/privacy">الخصوصية</a>
      <a href="/sitemap.xml">خريطة الموقع</a>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="container">
      <span>© <?= date('Y') ?> <?= h(SITE_NAME) ?> · جميع الحقوق محفوظة</span>
      <span>صُنع بـ ❤️ للمستخدم العربي</span>
    </div>
  </div>
</footer>

<!-- Mobile-bottom nav bar -->
<nav class="bottom-nav" aria-label="تنقل سريع">
  <a href="/" class="bnav-item<?= $page==='home'?' active':'' ?>">
    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
    <span>رئيسية</span>
  </a>
  <a href="/exchange" class="bnav-item<?= $page==='exchange'?' active':'' ?>">
    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/><path d="M15 9.354a4 4 0 100 5.292M15 12H9"/></svg>
    <span>صرف</span>
  </a>
  <a href="/gold" class="bnav-item<?= $page==='gold'?' active':'' ?>">
    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
    <span>ذهب</span>
  </a>
  <a href="/calculators" class="bnav-item<?= $page==='calculators'?' active':'' ?>">
    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M8 6h8M8 10h8M8 14h4"/></svg>
    <span>حاسبة</span>
  </a>
  <a href="/pdf" class="bnav-item<?= $page==='pdf'?' active':'' ?>">
    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    <span>PDF</span>
  </a>
</nav>
