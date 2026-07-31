<?php if (!defined('SITE_NAME')) require_once __DIR__ . '/config.php'; ?>
<!-- ===== FOOTER ===== -->
<footer>
  <div class="container">
    <div class="footer-inner">

      <div class="footer-brand">
        <span class="logo"><?= clean(setting('site_name_ar', 'DarkStore')) ?></span>
        <p><?= t(setting('tagline_ar','متجر أدوات رقمية احترافية'), setting('tagline_en','Professional Digital Tools Store')) ?></p>
        <div class="footer-social">
          <a href="<?= clean(setting('telegram','#')) ?>" class="soc-btn" target="_blank" rel="noopener" title="Telegram">
            <i class="fa-brands fa-telegram"></i>
          </a>
          <a href="mailto:<?= clean(setting('contact_email','support@example.com')) ?>" class="soc-btn" title="Email">
            <i class="fa-solid fa-envelope"></i>
          </a>
        </div>
      </div>

      <div class="footer-links">
        <h4><?= t('روابط سريعة','Quick Links') ?></h4>
        <a href="index.php"><?= t('الرئيسية','Home') ?></a>
        <a href="index.php#products"><?= t('المنتجات','Products') ?></a>
        <a href="about.php"><?= t('من نحن','About') ?></a>
        <a href="contact.php"><?= t('تواصل معنا','Contact') ?></a>
      </div>

      <div class="footer-links">
        <h4><?= t('السياسات','Policies') ?></h4>
        <a href="privacy-policy.php"><?= t('سياسة الخصوصية','Privacy Policy') ?></a>
        <a href="terms.php"><?= t('شروط الاستخدام','Terms of Service') ?></a>
        <a href="refund-policy.php"><?= t('سياسة الاسترداد','Refund Policy') ?></a>
        <a href="cookie-policy.php"><?= t('سياسة الكوكيز','Cookie Policy') ?></a>
      </div>

      <div class="footer-links">
        <h4><?= t('الدفع','Payment') ?></h4>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="12" fill="#26A17B"/>
            <path d="M13.2 11.9V10.6H15.8V9H8.2V10.6H10.8V11.9C8.5 12.1 6.8 12.7 6.8 13.4C6.8 14.1 8.5 14.7 10.8 14.9V18H13.2V14.9C15.5 14.7 17.2 14.1 17.2 13.4C17.2 12.7 15.5 12.1 13.2 11.9ZM12 14.2C10.1 14.2 8.6 13.9 8.6 13.4C8.6 12.9 10.1 12.6 12 12.6C13.9 12.6 15.4 12.9 15.4 13.4C15.4 13.9 13.9 14.2 12 14.2Z" fill="white"/>
          </svg>
          <span style="color:var(--dim);font-size:14px;">USDT (TRC20 / ERC20)</span>
        </div>
        <p style="font-size:12px;color:var(--dim);line-height:1.6;">
          <?= t('نقبل الدفع بـ USDT فقط. جميع المعاملات آمنة وتخضع للتحقق اليدوي قبل التسليم.','We accept USDT payments only. All transactions are secure and manually verified before delivery.') ?>
        </p>
      </div>
    </div>

    <div class="footer-bottom">
      <?= clean(t(setting('footer_ar','© ' . date('Y') . ' DarkStore'), setting('footer_en','© ' . date('Y') . ' DarkStore'))) ?>
      &nbsp;·&nbsp;
      <a href="privacy-policy.php"><?= t('الخصوصية','Privacy') ?></a>
      &nbsp;·&nbsp;
      <a href="terms.php"><?= t('الشروط','Terms') ?></a>
    </div>
  </div>
</footer>

<script>
function switchLang(e, newLang){
  e.preventDefault();
  const scrollY = window.scrollY;
  fetch('index.php?lang='+newLang+'&ajax=1').then(()=>{
    sessionStorage.setItem('scrollRestore', scrollY);
    window.location.reload();
  });
  return false;
}
window.addEventListener('load', ()=>{
  const y = sessionStorage.getItem('scrollRestore');
  if(y){ window.scrollTo(0, parseInt(y,10)); sessionStorage.removeItem('scrollRestore'); }
});

function toggleSidebar(){
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sbOverlay').classList.toggle('open');
}

function acceptPrivacy(){
  document.cookie = 'pv_ok=1;path=/;max-age=31536000;samesite=Lax';
  const el = document.getElementById('pvOverlay');
  if(el){ el.style.opacity='0'; el.style.transition='opacity .4s'; setTimeout(()=>el.remove(),400); }
}

function copyText(text, el){
  navigator.clipboard.writeText(text).then(()=>{
    const orig = el ? el.innerHTML : '';
    if(el){ el.innerHTML='<i class="fa-solid fa-check"></i> <?= t("تم النسخ","Copied!") ?>'; }
    setTimeout(()=>{ if(el) el.innerHTML=orig; }, 2000);
  });
}

function startCountdown(sec, el){
  if(!el) return;
  let t = sec;
  const iv = setInterval(()=>{
    if(t<=0){ el.textContent='<?= t("انتهى الوقت","Expired") ?>'; clearInterval(iv); return; }
    const m = Math.floor(t/60), s = t%60;
    el.textContent = (m<10?'0':'')+m+':'+(s<10?'0':'')+s;
    t--;
  },1000);
}

function filterCat(cat, btn){
  document.querySelectorAll('.cat-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.product-card').forEach(c=>{
    c.style.display = (cat==='all' || c.dataset.cat===cat) ? '' : 'none';
  });
}

function filterSearch(q){
  q = q.toLowerCase();
  document.querySelectorAll('.product-card').forEach(c=>{
    const title = (c.querySelector('.card-title')?.textContent||'').toLowerCase();
    const desc  = (c.querySelector('.card-desc')?.textContent||'').toLowerCase();
    c.style.display = (title.includes(q)||desc.includes(q)) ? '' : 'none';
  });
}
</script>
</body>
</html>
