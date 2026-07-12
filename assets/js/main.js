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

  /* ── Comment form: fetched fresh per visitor instead of being baked into
     app.php's shared page cache, so the CSRF token always matches the
     actual visitor's own session. ── */
  const commentSlot = document.getElementById('comment-form-slot');
  if (commentSlot) {
    fetch('comment-form.php?slug=' + encodeURIComponent(commentSlot.dataset.appSlug))
      .then(r => r.ok ? r.text() : Promise.reject())
      .then(html => { commentSlot.innerHTML = html; })
      .catch(() => { commentSlot.innerHTML = '<p style="color:var(--danger);font-size:13px">تعذر تحميل نموذج التقييم، أعد تحميل الصفحة.</p>'; });
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
    reveals.forEach(el => io.observe(el));
  } else {
    reveals.forEach(el => el.classList.add('visible'));
  }

  /* ── Sidebar Toggle (mobile) ── */
  const navToggle = document.querySelector('.nav-toggle');
  const sidebar = document.querySelector('.sidebar');
  if (navToggle && sidebar) {
    navToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      sidebar.classList.toggle('open');
    });
    document.addEventListener('click', (e) => {
      // .closest() (not strict e.target !== navToggle) — a click on the
      // icon SVG/path inside the button has e.target set to that child
      // element, not the button, which made this immediately re-close the
      // sidebar the instant it opened.
      if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && !e.target.closest('.nav-toggle'))
        sidebar.classList.remove('open');
    });
  }

  /* ── Mobile search toggle (header-search is hidden by default on small screens) ── */
  const searchToggle = document.querySelector('.mobile-search-toggle');
  const searchForm = document.querySelector('.header-search');
  if (searchToggle && searchForm) {
    searchToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = searchForm.classList.toggle('mobile-open');
      if (isOpen) searchForm.querySelector('input')?.focus();
    });
    document.addEventListener('click', (e) => {
      if (searchForm.classList.contains('mobile-open') && !searchForm.contains(e.target) && !e.target.closest('.mobile-search-toggle'))
        searchForm.classList.remove('mobile-open');
    });
  }

  /* ── Search autocomplete ── */
  const searchInput = document.getElementById('search-input');
  const suggestBox = document.getElementById('search-suggestions');
  if (searchInput && suggestBox) {
    let debounceTimer = null;
    let activeIndex = -1;
    let items = [];

    function renderSuggestions(results) {
      items = results;
      activeIndex = -1;
      if (!results.length) { suggestBox.hidden = true; suggestBox.innerHTML = ''; return; }
      suggestBox.innerHTML = results.map(r =>
        `<a href="${r.url}" data-hardnav="1">${r.icon ? `<img src="${r.icon}" alt="">` : ''}<span>${r.name}</span></a>`
      ).join('');
      suggestBox.hidden = false;
    }

    searchInput.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      const q = searchInput.value.trim();
      if (q.length < 2) { suggestBox.hidden = true; return; }
      debounceTimer = setTimeout(() => {
        fetch('search-suggest.php?q=' + encodeURIComponent(q))
          .then(r => r.ok ? r.json() : [])
          .then(renderSuggestions)
          .catch(() => {});
      }, 200);
    });

    searchInput.addEventListener('keydown', (e) => {
      const links = [...suggestBox.querySelectorAll('a')];
      if (!links.length || suggestBox.hidden) return;
      if (e.key === 'ArrowDown') { e.preventDefault(); activeIndex = Math.min(activeIndex + 1, links.length - 1); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); activeIndex = Math.max(activeIndex - 1, 0); }
      else if (e.key === 'Enter' && activeIndex >= 0) { e.preventDefault(); links[activeIndex].click(); return; }
      else if (e.key === 'Escape') { suggestBox.hidden = true; return; }
      else return;
      links.forEach((a, i) => a.classList.toggle('active', i === activeIndex));
    });

    document.addEventListener('click', (e) => {
      if (!suggestBox.contains(e.target) && e.target !== searchInput) suggestBox.hidden = true;
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

  /* ── Sticky Download Bar (mobile, on scroll) ── */
  const stickyDl = document.querySelector('.sticky-dl');
  if (stickyDl) {
    const hero = document.querySelector('.app-hero') || document.querySelector('.hero-banner');
    const io2 = new IntersectionObserver(
      (entries) => stickyDl.classList.toggle('visible', !entries[0].isIntersecting),
      { threshold: 0 }
    );
    if (hero) io2.observe(hero);
  }

  /* ── Smooth card hover ripple ── */
  document.querySelectorAll('.app-card, .btn-download-hero, .btn-primary').forEach(el => {
    el.addEventListener('click', function (e) {
      const ripple = document.createElement('span');
      const rect = this.getBoundingClientRect();
      ripple.style.cssText = `
        position:absolute;border-radius:50%;
        width:100px;height:100px;
        top:${e.clientY - rect.top - 50}px;
        left:${e.clientX - rect.left - 50}px;
        background:rgba(37,99,235,.12);
        transform:scale(0);
        animation:ripple .5s ease forwards;
        pointer-events:none;
      `;
      if (getComputedStyle(this).position === 'static') this.style.position = 'relative';
      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 500);
    });
  });
});

/* Global ripple keyframe */
const style = document.createElement('style');
style.textContent = '@keyframes ripple{to{transform:scale(4);opacity:0}}';
document.head.appendChild(style);
