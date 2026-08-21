/* ════════════════════════════════════════════
   YASSOTA — main.js
   ════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', () => {

  /* ── Cookie consent banner ── */
  const cookieBar = document.getElementById('cookie-consent');
  if (cookieBar) {
    if (!localStorage.getItem('cookie_consent')) cookieBar.hidden = false;
    document.getElementById('cookie-consent-accept')?.addEventListener('click', () => {
      localStorage.setItem('cookie_consent', '1');
      cookieBar.hidden = true;
    });
  }

  /* ── Comment form: fetched fresh per visitor (CSRF token must be per-session) ── */
  var commentSlot = document.getElementById('comment-form-slot');
  if (commentSlot) {
    if (typeof fetch !== 'undefined') {
      fetch('/comment-form.php?slug=' + encodeURIComponent(commentSlot.dataset.appSlug || ''))
        .then(function(r){ return r.ok ? r.text() : Promise.reject(); })
        .then(function(html){ commentSlot.innerHTML = html; })
        .catch(function(){ commentSlot.innerHTML = '<p style="color:#ef4444;font-size:13px">تعذر تحميل نموذج التقييم، أعد تحميل الصفحة.</p>'; });
    } else {
      // Fallback for very old browsers without fetch
      var xhr = new XMLHttpRequest();
      xhr.open('GET', '/comment-form.php?slug=' + encodeURIComponent(commentSlot.dataset.appSlug || ''));
      xhr.onload = function(){ if (xhr.status === 200) commentSlot.innerHTML = xhr.responseText; };
      xhr.onerror = function(){ commentSlot.innerHTML = '<p style="color:#ef4444;font-size:13px">تعذر تحميل نموذج التقييم.</p>'; };
      xhr.send();
    }
  }

  /* ── Ad zones: stay hidden (zero space) until AdSense actually fills the
     slot. Google marks each <ins> with data-ad-status="filled"/"unfilled"
     once it resolves the request, so watch for that attribute instead of
     guessing from a timeout. ── */
  document.querySelectorAll('.ad-zone').forEach(zone => {
    const ins = zone.querySelector('ins.adsbygoogle');
    if (!ins) return;
    const sync = () => {
      zone.classList.toggle('ad-filled', ins.getAttribute('data-ad-status') === 'filled');
    };
    new MutationObserver(sync).observe(ins, { attributes: true, attributeFilter: ['data-ad-status'] });
    sync();
  });

  /* ── Reveal on Scroll ── */
  const reveals = document.querySelectorAll('.reveal');
  if (reveals.length && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); } });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
    reveals.forEach(el => {
      const rect = el.getBoundingClientRect();
      if (rect.top < window.innerHeight && rect.bottom > 0) {
        /* Already in viewport on load — show immediately, no blank flash */
        el.style.transition = 'none';
        el.classList.add('visible');
        el.getBoundingClientRect(); /* force reflow so transition suppression applies */
        el.style.transition = '';
      } else {
        io.observe(el);
      }
    });
  } else {
    reveals.forEach(el => el.classList.add('visible'));
  }

  /* ── Mobile nav drawer — right-side RTL panel ── */
  const navToggle   = document.querySelector('#nav-toggle');
  const mobileNavDrawer = document.querySelector('#mobile-nav-drawer');
  const mobileNavOverlay = document.querySelector('#mobile-nav-overlay');
  const mobileNavClose   = document.querySelector('#mobile-nav-close');

  function openDrawer() {
    mobileNavDrawer.classList.add('open');
    mobileNavOverlay && mobileNavOverlay.classList.add('open');
    navToggle && navToggle.setAttribute('aria-expanded', 'true');
    mobileNavDrawer.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }
  function closeDrawer() {
    mobileNavDrawer.classList.remove('open');
    mobileNavOverlay && mobileNavOverlay.classList.remove('open');
    navToggle && navToggle.setAttribute('aria-expanded', 'false');
    mobileNavDrawer.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  if (navToggle && mobileNavDrawer) {
    navToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      mobileNavDrawer.classList.contains('open') ? closeDrawer() : openDrawer();
    });
    mobileNavClose && mobileNavClose.addEventListener('click', closeDrawer);
    mobileNavOverlay && mobileNavOverlay.addEventListener('click', closeDrawer);
    mobileNavDrawer.querySelectorAll('a').forEach(a => a.addEventListener('click', closeDrawer));
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && mobileNavDrawer.classList.contains('open')) closeDrawer();
    });
  }

  /* ── Full-screen search modal ── */
  const searchModal      = document.getElementById('search-modal');
  const searchModalInput = document.getElementById('search-modal-input');
  const searchModalClose = document.getElementById('search-modal-close');
  const searchModalRes   = document.getElementById('search-modal-results');
  const searchToggle     = document.querySelector('.mobile-search-toggle');
  const desktopSearchInput = document.getElementById('search-input');

  function openSearchModal() {
    if (!searchModal) return;
    searchModal.classList.add('open');
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(() => searchModalInput?.focus());
  }
  function closeSearchModal() {
    if (!searchModal) return;
    searchModal.classList.remove('open');
    document.body.style.overflow = '';
    if (searchModalRes) { searchModalRes.hidden = true; searchModalRes.innerHTML = ''; }
  }

  searchToggle?.addEventListener('click', (e) => { e.stopPropagation(); openSearchModal(); });
  searchModalClose?.addEventListener('click', closeSearchModal);
  searchModal?.addEventListener('click', (e) => { if (e.target === searchModal) closeSearchModal(); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && searchModal?.classList.contains('open')) closeSearchModal(); });

  /* ── Search autocomplete (works in modal + desktop) ── */
  function setupAutocomplete(inputEl, resultsEl) {
    if (!inputEl || !resultsEl) return;
    let timer = null;
    inputEl.addEventListener('input', () => {
      clearTimeout(timer);
      const q = inputEl.value.trim();
      if (q.length < 2) { resultsEl.hidden = true; resultsEl.innerHTML = ''; return; }
      timer = setTimeout(() => {
        fetch('/search-suggest.php?q=' + encodeURIComponent(q))
          .then(r => r.ok ? r.json() : [])
          .then(data => {
            if (!data.length) { resultsEl.hidden = true; return; }
            resultsEl.innerHTML = data.map(r =>
              `<a href="${r.url}" data-hardnav="1">${r.icon ? `<img src="${r.icon}" alt="" loading="lazy">` : ''}<span>${r.name}</span></a>`
            ).join('');
            resultsEl.hidden = false;
          })
          .catch(() => {});
      }, 220);
    });
    document.addEventListener('click', (e) => {
      if (!resultsEl.contains(e.target) && e.target !== inputEl) resultsEl.hidden = true;
    });
    inputEl.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') { resultsEl.hidden = true; }
    });
  }
  setupAutocomplete(searchModalInput, searchModalRes);
  setupAutocomplete(desktopSearchInput, document.getElementById('search-suggestions'));

  /* ── Text clamp — "show more" for any .text-clamp element ── */
  document.querySelectorAll('.text-clamp').forEach(el => {
    // Only add button if content is actually clamped
    if (el.scrollHeight <= el.clientHeight + 4) return;
    const btn = document.createElement('button');
    btn.className = 'show-more-btn';
    const isRtl = document.documentElement.dir === 'rtl';
    btn.textContent = isRtl ? 'عرض المزيد ▾' : 'Show more ▾';
    btn.addEventListener('click', () => {
      const expanded = el.classList.toggle('expanded');
      btn.textContent = expanded
        ? (isRtl ? 'عرض أقل ▴' : 'Show less ▴')
        : (isRtl ? 'عرض المزيد ▾' : 'Show more ▾');
    });
    el.insertAdjacentElement('afterend', btn);
  });

  /* ── Language switcher dropdown ── */
  const langBtn  = document.getElementById('lang-btn');
  const langDrop = document.getElementById('lang-dropdown');
  if (langBtn && langDrop) {
    langBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = langDrop.hidden;
      langDrop.hidden = !isOpen;
    });
    document.addEventListener('click', (e) => {
      if (!e.target.closest('#lang-switcher')) langDrop.hidden = true;
    });
  }

  /* ── Category Chips Filter ── */
  document.querySelectorAll('.cat-chip[data-cat]').forEach(chip => {
    chip.addEventListener('click', () => {
      const url = new URL(location.href);
      const val = chip.dataset.cat;
      if (val === 'all') url.searchParams.delete('cat'); else url.searchParams.set('cat', val);
      url.searchParams.delete('page');
      location.href = url.toString();
    });
  });

  /* ── Lightbox ── */
  const lightbox = document.querySelector('.lightbox');
  if (lightbox) {
    const lbImg = lightbox.querySelector('.lightbox-img');
    const shots = [...document.querySelectorAll('.screenshot-thumb img')];
    let cur = 0;
    const open = (i) => { cur = i; lbImg.src = shots[i].src; lightbox.classList.add('open'); };
    const close = () => lightbox.classList.remove('open');
    const prev = () => open((cur - 1 + shots.length) % shots.length);
    const next = () => open((cur + 1) % shots.length);

    document.querySelectorAll('.screenshot-thumb').forEach((el, i) => el.addEventListener('click', () => open(i)));
    lightbox.querySelector('.lightbox-close')?.addEventListener('click', close);
    lightbox.querySelector('.lightbox-prev')?.addEventListener('click', prev);
    lightbox.querySelector('.lightbox-next')?.addEventListener('click', next);
    lightbox.addEventListener('click', (e) => { if (e.target === lightbox) close(); });
    document.addEventListener('keydown', (e) => {
      if (!lightbox.classList.contains('open')) return;
      if (e.key === 'Escape') close();
      if (e.key === 'ArrowRight') prev();
      if (e.key === 'ArrowLeft') next();
    });
  }

  /* ── FAQ Accordion ── */
  document.querySelectorAll('.faq-item').forEach(item => {
    item.querySelector('.faq-q')?.addEventListener('click', () => {
      const isOpen = item.classList.contains('open');
      document.querySelectorAll('.faq-item.open').forEach(o => o.classList.remove('open'));
      if (!isOpen) item.classList.add('open');
    });
  });

/* ── Smooth card hover ripple ── */
  document.querySelectorAll('.app-card, .btn-download-hero, .btn-primary').forEach(function(el) {
    el.addEventListener('click', function (e) {
      var ripple = document.createElement('span');
      var rect = this.getBoundingClientRect();
      ripple.style.cssText = 'position:absolute;border-radius:50%;width:100px;height:100px;top:' + (e.clientY - rect.top - 50) + 'px;left:' + (e.clientX - rect.left - 50) + 'px;background:rgba(37,99,235,.12);transform:scale(0);animation:ripple .5s ease forwards;pointer-events:none';
      if (getComputedStyle(this).position === 'static') this.style.position = 'relative';
      this.appendChild(ripple);
      setTimeout(function(){ if (ripple.parentNode) ripple.parentNode.removeChild(ripple); }, 500);
    });
  });

  /* Ripple keyframe */
  var rippleStyle = document.createElement('style');
  rippleStyle.textContent = '@keyframes ripple{to{transform:scale(4);opacity:0}}';
  if (document.head) document.head.appendChild(rippleStyle);
});
