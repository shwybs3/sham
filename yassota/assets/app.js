/* ═══ YASSOTA — سلوك الواجهة ═══ */
(function () {
  'use strict';
  var YA = window.YA || {};

  function $(s, r) { return (r || document).querySelector(s); }
  function $all(s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); }
  function toast(msg, kind) {
    var w = $('#toasts'); if (!w) return;
    var t = document.createElement('div');
    t.className = 'toast' + (kind ? ' ' + kind : '');
    t.textContent = msg; w.appendChild(t);
    setTimeout(function () { t.style.opacity = '0'; t.style.transition = '.3s'; setTimeout(function () { t.remove(); }, 300); }, 2600);
  }
  window.yaToast = toast;

  function api(action, data) {
    var body = new URLSearchParams(data || {});
    body.set('action', action);
    return fetch(YA.api, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF': YA.csrf || '' },
      credentials: 'same-origin', body: body
    }).then(function (r) { return r.json(); });
  }
  window.yaApi = api;

  function needLogin() {
    if (YA.me) return false;
    location.href = YA.base + 'login?next=' + encodeURIComponent(location.pathname + location.search);
    return true;
  }

  var themeBtn = $('#themeToggle');
  function curTheme() {
    var t = document.documentElement.getAttribute('data-theme');
    if (t) return t;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }
  function setTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    try { localStorage.setItem('ya-theme', t); } catch (e) {}
    if (themeBtn) themeBtn.innerHTML = t === 'dark'
      ? '<svg class="ic" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1.5 1.5M17.5 17.5 19 19M5 19l1.5-1.5M17.5 6.5 19 5"/></svg>'
      : '<svg class="ic" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 14a8 8 0 1 1-9-11 6 6 0 0 0 9 11z"/></svg>';
  }
  if (themeBtn) { setTheme(curTheme()); themeBtn.addEventListener('click', function () { setTheme(curTheme() === 'dark' ? 'light' : 'dark'); }); }

  document.addEventListener('click', function (ev) {
    var el = ev.target.closest('[data-act]');
    if (el) {
      var act = el.getAttribute('data-act');
      if (act === 'like') { ev.preventDefault(); doLike(el); }
      else if (act === 'save') { ev.preventDefault(); doSave(el); }
      else if (act === 'share') { ev.preventDefault(); doShare(el); }
      else if (act === 'follow') { ev.preventDefault(); doFollow(el); }
      return;
    }
    var menu = ev.target.closest('[data-post-menu]');
    if (menu) { ev.preventDefault(); postMenu(menu.getAttribute('data-post-menu')); }
    if (ev.target.closest('[data-close-modal]') || ev.target.id === 'modalRoot') closeModal();
  });

  function doLike(btn) {
    if (needLogin()) return;
    var id = btn.getAttribute('data-id'), on = btn.classList.contains('on');
    var c = btn.querySelector('.c');
    btn.classList.toggle('on'); setIcon(btn, !on ? 'heart-fill' : 'heart');
    if (c) c.textContent = fmt(delta(c.textContent, on ? -1 : 1));
    api('like', { id: id }).then(function (r) {
      if (!r.ok) { btn.classList.toggle('on'); return; }
      btn.classList.toggle('on', r.liked); setIcon(btn, r.liked ? 'heart-fill' : 'heart');
      if (c) c.textContent = fmt(r.count);
    });
  }
  function doSave(btn) {
    if (needLogin()) return;
    var id = btn.getAttribute('data-id'), on = btn.classList.contains('on');
    btn.classList.toggle('on'); setIcon(btn, !on ? 'bookmark-fill' : 'bookmark');
    api('save', { id: id }).then(function (r) {
      if (!r.ok) { btn.classList.toggle('on'); return; }
      btn.classList.toggle('on', r.saved); setIcon(btn, r.saved ? 'bookmark-fill' : 'bookmark');
      toast(r.saved ? 'تم الحفظ' : 'أُزيل من المحفوظات');
    });
  }
  function doFollow(btn) {
    if (needLogin()) return;
    var id = btn.getAttribute('data-id');
    var on = btn.classList.contains('on');
    btn.classList.toggle('on'); btn.textContent = on ? 'متابعة' : 'إلغاء المتابعة';
    api('follow', { id: id }).then(function (r) {
      if (!r.ok) { btn.classList.toggle('on'); return; }
      btn.classList.toggle('on', r.following);
      btn.textContent = r.following ? 'إلغاء المتابعة' : 'متابعة';
    });
  }
  function doShare(btn) {
    var url = btn.getAttribute('data-url'), title = btn.getAttribute('data-title') || 'YASSOTA';
    if (navigator.share) { navigator.share({ title: title, url: url }).catch(function () {}); }
    else { navigator.clipboard.writeText(url).then(function () { toast('تم نسخ الرابط', 'ok'); }); }
    api('share', { url: url });
  }
  function setIcon(btn, name) {
    var svg = btn.querySelector('svg'); if (!svg) return;
    var paths = { 'heart': '<path d="M12 20s-7-4.3-9.3-8.6C1 8 3 4.8 6.2 4.8c2 0 3.2 1.2 3.8 2.2.6-1 1.8-2.2 3.8-2.2C21 4.8 23 8 21.3 11.4 19 15.7 12 20 12 20z"/>',
      'heart-fill': '<path d="M12 20s-7-4.3-9.3-8.6C1 8 3 4.8 6.2 4.8c2 0 3.2 1.2 3.8 2.2.6-1 1.8-2.2 3.8-2.2C21 4.8 23 8 21.3 11.4 19 15.7 12 20 12 20z" fill="currentColor" stroke="none"/>',
      'bookmark': '<path d="M6 3h12v18l-6-4-6 4z"/>', 'bookmark-fill': '<path d="M6 3h12v18l-6-4-6 4z" fill="currentColor" stroke="none"/>' };
    if (paths[name]) svg.innerHTML = paths[name];
  }
  function delta(txt, d) { var n = parseCount(txt); return n + d; }
  function parseCount(t) { t = (t || '0').trim(); if (/K$/.test(t)) return Math.round(parseFloat(t) * 1000); if (/M$/.test(t)) return Math.round(parseFloat(t) * 1e6); return parseInt(t) || 0; }
  function fmt(n) { n = +n; if (n >= 1e6) return (n / 1e6).toFixed(1).replace(/\.0$/, '') + 'M'; if (n >= 1e3) return (n / 1e3).toFixed(1).replace(/\.0$/, '') + 'K'; return '' + n; }
  window.yaFmt = fmt;

  function openModal(html) { var r = $('#modalRoot'); if (!r) return; r.innerHTML = '<div class="modal" role="dialog">' + html + '</div>'; r.hidden = false; }
  function closeModal() { var r = $('#modalRoot'); if (r) { r.hidden = true; r.innerHTML = ''; } }
  window.yaModal = openModal; window.yaCloseModal = closeModal;

  function postMenu(id) {
    openModal(
      '<div class="modal-h"><h3>خيارات المنشور</h3><button class="ic-btn" data-close-modal>✕</button></div>' +
      '<div class="modal-b">' +
      '<button class="menu-item" onclick="yaCopyPost(\'' + id + '\')"><span>نسخ الرابط</span></button>' +
      '<button class="menu-item danger" onclick="yaReport(\'post\',' + id + ')"><span>إبلاغ</span></button>' +
      '</div>');
  }
  window.yaCopyPost = function (id) {
    var card = document.querySelector('.post[data-id="' + id + '"] .p-title');
    var link = card ? card.href : location.href;
    navigator.clipboard.writeText(link).then(function () { toast('تم نسخ الرابط', 'ok'); });
    closeModal();
  };
  window.yaReport = function (type, id) {
    if (needLogin()) return;
    openModal(
      '<div class="modal-h"><h3>إبلاغ</h3><button class="ic-btn" data-close-modal>✕</button></div>' +
      '<div class="modal-b"><p style="color:var(--muted);font-size:13.5px;margin-top:0">اختر سبب الإبلاغ:</p>' +
      ['spam,سبام', 'harassment,تحرّش', 'violence,عنف', 'adult,محتوى غير لائق', 'copyright,حقوق نشر', 'scam,احتيال', 'other,أخرى']
        .map(function (r) { var p = r.split(','); return '<button class="menu-item" onclick="yaSendReport(\'' + type + '\',' + id + ',\'' + p[0] + '\')">' + p[1] + '</button>'; }).join('') +
      '</div>');
  };
  window.yaSendReport = function (type, id, reason) {
    api('report', { target_type: type, target_id: id, reason: reason }).then(function (r) {
      closeModal(); toast(r.ok ? 'شكراً، تم استلام البلاغ' : (r.error || 'تعذّر الإرسال'), r.ok ? 'ok' : 'err');
    });
  };

  var feed = $('[data-feed]');
  if (feed) {
    var loading = false, done = false, page = 1;
    var mode = feed.getAttribute('data-feed'), extra = feed.getAttribute('data-arg') || '', layout = feed.getAttribute('data-layout') || 'cards';
    var sentinel = document.createElement('div'); sentinel.style.height = '1px'; feed.after(sentinel);
    var io = new IntersectionObserver(function (ents) {
      if (ents[0].isIntersecting && !loading && !done) loadMore();
    }, { rootMargin: '600px' });
    io.observe(sentinel);
    function loadMore() {
      loading = true; page++;
      var sk = document.createElement('div'); sk.className = 'skel skel-card'; feed.appendChild(sk);
      api('feed', { mode: mode, arg: extra, page: page, layout: layout }).then(function (r) {
        sk.remove(); loading = false;
        if (!r.ok || !r.html || !r.html.trim()) { done = true; return; }
        feed.insertAdjacentHTML('beforeend', r.html);
        if (r.count < r.per) done = true;
      }).catch(function () { sk.remove(); loading = false; });
    }
  }

  var gs = $('#globalSearch'), pop = $('#searchPop'), tmr;
  if (gs && pop) {
    gs.addEventListener('input', function () {
      clearTimeout(tmr); var q = gs.value.trim();
      if (q.length < 2) { pop.hidden = true; return; }
      tmr = setTimeout(function () {
        api('suggest', { q: q }).then(function (r) {
          if (!r.ok || !r.html) { pop.hidden = true; return; }
          pop.innerHTML = r.html; pop.hidden = false;
        });
      }, 220);
    });
    document.addEventListener('click', function (e) { if (!e.target.closest('.tb-search')) pop.hidden = true; });
  }

  if (YA.me) {
    api('notif_count', {}).then(function (r) {
      if (r.ok && r.count > 0) { var b = $('#notifBadge'); if (b) { b.textContent = r.count > 99 ? '99+' : r.count; b.hidden = false; } }
    });
  }

  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register(YA.base + 'sw.js').catch(function () {});
    });
  }

  window.yaComment = function (form) {
    if (needLogin()) return false;
    var ta = form.querySelector('textarea'), body = ta.value.trim();
    if (!body) return false;
    var pid = form.getAttribute('data-post'), parent = form.getAttribute('data-parent') || '';
    api('comment', { post_id: pid, parent_id: parent, body: body }).then(function (r) {
      if (!r.ok) { toast(r.error || 'تعذّر النشر', 'err'); return; }
      ta.value = '';
      var list = $('#commentList');
      if (list && r.html) { if (parent) { var box = document.querySelector('[data-replies="' + parent + '"]'); if (box) box.insertAdjacentHTML('beforeend', r.html); } else list.insertAdjacentHTML('afterbegin', r.html); }
      var cc = $('#commentCount'); if (cc) cc.textContent = r.total;
      toast('تم نشر تعليقك', 'ok');
    });
    return false;
  };
})();
