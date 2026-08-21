<?php
/**
 * includes/page-footer.php — تذييل الصفحة مع دعم الـ FAB وPWA
 */
$supportEnabled = ($settings['support_enabled'] ?? '1') === '1';
$pwaEnabled     = ($settings['pwa_enabled']    ?? '1') === '1';
?>
<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-links">
      <a href="index.php">الرئيسية</a>
      <a href="community.php">المجموعة</a>
      <a href="privacy-policy.php">سياسة الخصوصية</a>
      <a href="terms.php">شروط الاستخدام</a>
      <a href="sitemap.php">خريطة الموقع</a>
      <a href="rss.php">RSS</a>
    </div>
    <div class="footer-copy">© <?= date('Y') ?> <?= h($shopName) ?> · جميع الحقوق محفوظة</div>
  </div>
</footer>

<?php if ($supportEnabled): ?>
<!-- زر الدعم العائم -->
<div class="support-panel hidden" id="support-panel">
  <div class="support-head">
    <span><?= icon('chat', 16) ?> ابلاغ عن مشكلة</span>
    <button class="support-close" onclick="toggleSupport()" aria-label="إغلاق">✕</button>
  </div>
  <div class="support-body" id="support-body">
    <p>اكتب رسالتك وسنرد خلال دقائق</p>
    <input type="text" id="support-name" placeholder="اسمك (اختياري)">
    <input type="text" id="support-msg" placeholder="ما المشكلة؟" maxlength="300">
    <button class="btn btn-primary btn-block" id="support-send"><?= icon('check', 16) ?> إرسال</button>
  </div>
</div>
<button class="support-fab" id="support-fab" onclick="toggleSupport()" title="تواصل مع الدعم" aria-label="دعم">
  <?= icon('chat', 22) ?>
</button>

<script>
(function(){
  const panel = document.getElementById('support-panel');
  const fab   = document.getElementById('support-fab');
  let open = false;
  window.toggleSupport = function(){
    open = !open;
    panel.classList.toggle('hidden', !open);
  };
  document.getElementById('support-send').addEventListener('click', function(){
    const name = document.getElementById('support-name').value.trim();
    const msg  = document.getElementById('support-msg').value.trim();
    if (!msg){ document.getElementById('support-msg').focus(); return; }
    this.disabled = true;
    fetch('api/support.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({name, message: msg})
    }).then(r=>r.json()).then(()=>{
      document.getElementById('support-body').innerHTML = `
        <div class="support-sent">
          <div class="check">✅</div>
          <strong>تمت استقبال رسالتك</strong>
          <p>سيتم الرد في غضون دقائق قليلة</p>
        </div>`;
    }).catch(()=>{
      this.disabled = false;
      alert('تعذر الإرسال، حاول مجدداً');
    });
  });
})();
</script>
<?php endif; ?>

<?php if ($pwaEnabled): ?>
<!-- PWA Install Banner -->
<div class="pwa-banner hidden" id="pwa-banner">
  <div>
    <p><?= icon('store', 16) ?> ثبّت التطبيق</p>
    <small>استخدمه بدون إنترنت وأسرع</small>
  </div>
  <div class="pwa-banner-btns">
    <button class="btn btn-primary btn-sm" id="pwa-install-btn">تثبيت</button>
    <button class="btn btn-ghost btn-sm" id="pwa-dismiss-btn">لاحقاً</button>
  </div>
</div>

<script>
(function(){
  let deferredPrompt;
  const banner = document.getElementById('pwa-banner');
  const installBtn = document.getElementById('pwa-install-btn');
  const dismissBtn = document.getElementById('pwa-dismiss-btn');

  if (localStorage.getItem('pwa-dismissed')) return;

  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    setTimeout(() => banner.classList.remove('hidden'), 3000);
  });

  installBtn && installBtn.addEventListener('click', async () => {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;
    deferredPrompt = null;
    banner.classList.add('hidden');
  });

  dismissBtn && dismissBtn.addEventListener('click', () => {
    banner.classList.add('hidden');
    localStorage.setItem('pwa-dismissed', '1');
  });
})();
</script>
<?php endif; ?>

</body>
</html>
