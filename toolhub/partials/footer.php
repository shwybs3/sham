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

<!-- Mobile bottom nav — same 5 tabs as main site -->
<nav class="bottom-nav" id="bottom-nav" aria-label="التنقل السريع">
  <a href="/" class="bnav-item" id="bn-home">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12l9-9 9 9M5 10v9a1 1 0 001 1h4v-4h4v4h4a1 1 0 001-1v-9"/></svg>
    الرئيسية
  </a>
  <a href="/top?by=downloads" class="bnav-item" id="bn-top">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 6l-9.5 9.5-5-5L2 18"/><path d="M16 6h6v6"/></svg>
    الأكثر تحميلاً
  </a>
  <a href="/updates" class="bnav-item" id="bn-updates">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
    التحديثات
  </a>
  <a href="/tools/" class="bnav-item active" id="bn-tools">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>
    أدوات
  </a>
  <button class="bnav-item" onclick="document.getElementById('bn-sheet').classList.toggle('open')" id="bn-more">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="5" r="1.5" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.5" fill="currentColor" stroke="none"/><circle cx="12" cy="19" r="1.5" fill="currentColor" stroke="none"/></svg>
    المزيد
  </button>
</nav>

<!-- المزيد bottom sheet -->
<div class="bn-more-sheet" id="bn-sheet">
  <div style="text-align:center;font-size:11px;color:rgba(226,232,240,.35);margin-bottom:12px;font-weight:600;letter-spacing:.5px">قسم المزيد</div>
  <div class="bn-more-grid">
    <a href="/exchange" class="bn-more-item">
      <svg viewBox="0 0 24 24"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
      الصرف
    </a>
    <a href="/gold" class="bn-more-item">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
      الذهب
    </a>
    <a href="/calculators" class="bn-more-item">
      <svg viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M8 7h8M8 12h4M8 17h2"/></svg>
      حاسبات
    </a>
    <a href="/pdf" class="bn-more-item">
      <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      PDF
    </a>
    <a href="/solar" class="bn-more-item">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
      شمسية
    </a>
    <a href="/blog" class="bn-more-item">
      <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 8h10M7 12h10M7 16h6"/></svg>
      المدونة
    </a>
    <a href="/about" class="bn-more-item">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/></svg>
      من نحن
    </a>
    <a href="/contact" class="bn-more-item">
      <svg viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><polyline points="22,4 12,13 2,4"/></svg>
      تواصل
    </a>
  </div>
</div>
<script>
(function(){
  var sheet = document.getElementById('bn-sheet');
  document.addEventListener('click', function(e){
    if (sheet && sheet.classList.contains('open') && !e.target.closest('#bn-more') && !e.target.closest('#bn-sheet'))
      sheet.classList.remove('open');
  });
})();
</script>
