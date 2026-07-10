/* ════════════════════════════════════════════
   YASSOTA — admin.js
   ════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', () => {

  /* ── Dynamic list builder (features, pros, cons, steps, faq) ── */
  window.initDynamicList = (containerId, fieldName, placeholder, secondField) => {
    const container = document.getElementById(containerId);
    if (!container) return;

    const addBtn = container.nextElementSibling;

    const addItem = (val = '', val2 = '') => {
      const item = document.createElement('div');
      item.className = 'dynamic-item';

      if (secondField) {
        item.innerHTML = `
          <div style="flex:1;display:flex;flex-direction:column;gap:6px">
            <input type="text" class="form-input" name="${fieldName}[q][]" placeholder="السؤال" value="${escHtml(val)}">
            <input type="text" class="form-input" name="${fieldName}[a][]" placeholder="الجواب" value="${escHtml(val2)}">
          </div>
          <button type="button" class="del-btn" onclick="this.parentElement.remove()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
          </button>`;
      } else {
        item.innerHTML = `
          <input type="text" class="form-input" name="${fieldName}[]" placeholder="${placeholder}" value="${escHtml(val)}" style="flex:1">
          <button type="button" class="del-btn" onclick="this.parentElement.remove()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
          </button>`;
      }
      container.appendChild(item);
    };

    if (addBtn) addBtn.addEventListener('click', () => addItem());
    return addItem;
  };

  /* ── Pre-fill existing data ── */
  if (window.EXISTING_DATA) {
    const d = window.EXISTING_DATA;
    const fillList = (id, name, items, secondField) => {
      const add = window.initDynamicList(id, name, '', secondField);
      if (add && items) items.forEach(v => secondField ? add(v.q, v.a) : add(v));
    };
    fillList('feat-list', 'features', d.features);
    fillList('pros-list', 'pros', d.pros);
    fillList('cons-list', 'cons', d.cons);
    fillList('steps-list', 'install_steps', d.install_steps);
    fillList('faq-list', 'faq', d.faq, true);
  } else {
    window.initDynamicList('feat-list', 'features', 'ميزة');
    window.initDynamicList('pros-list', 'pros', 'إيجابية');
    window.initDynamicList('cons-list', 'cons', 'سلبية');
    window.initDynamicList('steps-list', 'install_steps', 'خطوة');
    window.initDynamicList('faq-list', 'faq', '', true);
  }

  /* ── AI Generate ── */
  const aiBtn = document.getElementById('btn-ai');
  const aiNameInput = document.getElementById('ai-name');
  const aiStatus = document.getElementById('ai-status');

  if (aiBtn) {
    aiBtn.addEventListener('click', async () => {
      const name = aiNameInput?.value.trim();
      if (!name) { aiStatus.textContent = 'اكتب اسم التطبيق أولاً'; return; }
      aiStatus.textContent = '⏳ جاري التوليد...';
      aiBtn.disabled = true;

      try {
        const res = await fetch('admin.php?ajax=generate', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ name, csrf: document.querySelector('[name=_csrf]')?.value })
        });
        const data = await res.json();
        if (!data.success) { aiStatus.textContent = '❌ ' + data.error; return; }

        const set = (id, v) => { const el = document.getElementById(id); if (el && v != null) el.value = v; };
        set('f-name', data.name); set('f-seo-title', data.seo_title);
        set('f-meta-desc', data.meta_description); set('f-keywords', data.keywords);
        set('f-short-desc', data.short_description); set('f-long-desc', data.long_description);
        set('f-developer', data.developer); set('f-version', data.version);
        set('f-android', data.android_version); set('f-size', data.size_mb);
        set('f-license', data.license); set('f-pkg', data.package_name);
        set('f-rating', data.rating); set('f-whats-new', data.whats_new);

        // Refill dynamic lists
        ['feat-list', 'pros-list', 'cons-list', 'steps-list', 'faq-list'].forEach(id => {
          const el = document.getElementById(id); if (el) el.innerHTML = '';
        });
        const fill = (id, name, items, second) => {
          const add = window.initDynamicList(id, name, '', second);
          if (add && items) items.forEach(v => second ? add(v.q, v.a) : add(v));
        };
        fill('feat-list', 'features', data.features);
        fill('pros-list', 'pros', data.pros);
        fill('cons-list', 'cons', data.cons);
        fill('steps-list', 'install_steps', data.install_steps);
        fill('faq-list', 'faq', data.faq, true);

        aiStatus.textContent = '✅ تم التوليد بنجاح — راجع وعدّل ثم احفظ';
      } catch { aiStatus.textContent = '❌ خطأ في الاتصال'; }
      aiBtn.disabled = false;
    });
  }

  /* ── Image preview ── */
  document.querySelectorAll('input[type=file]').forEach(input => {
    input.addEventListener('change', function () {
      const preview = document.getElementById(this.dataset.preview);
      if (!preview || !this.files[0]) return;
      preview.src = URL.createObjectURL(this.files[0]);
      preview.style.display = 'block';
    });
  });

  /* ── Confirm delete ── */
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => { if (!confirm(el.dataset.confirm)) e.preventDefault(); });
  });

  /* ── Render diagnostic checklist ── */
  function renderChecks(container, checks) {
    container.innerHTML = '';
    checks.forEach(c => {
      const row = document.createElement('div');
      row.style.cssText = 'display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border-radius:10px;background:' +
        (c.ok ? 'rgba(0,230,118,.08)' : 'rgba(255,68,102,.08)') + ';border:1px solid ' +
        (c.ok ? 'rgba(0,230,118,.25)' : 'rgba(255,68,102,.25)');
      row.innerHTML = `
        <span style="font-size:16px;line-height:1;flex-shrink:0">${c.ok ? '✅' : '❌'}</span>
        <div style="flex:1">
          <div style="font-weight:700;font-size:13px;color:${c.ok ? 'var(--success)' : 'var(--danger)'}">${c.label}</div>
          <div style="font-size:12px;color:var(--muted);margin-top:3px">${c.detail || ''}</div>
        </div>`;
      container.appendChild(row);
      if (c.trace && c.trace.length) {
        const details = document.createElement('details');
        details.style.cssText = 'margin-right:26px;margin-bottom:6px;font-size:11px;color:var(--muted)';
        details.innerHTML = `<summary style="cursor:pointer;color:var(--cyan)">عرض تفاصيل كل محاولة (${c.trace.length})</summary>` +
          c.trace.map(t => `<div style="padding:4px 0;border-bottom:1px solid var(--border-c)">
            ${t.ok ? '✅' : '❌'} ${t.model} — مفتاح ينتهي بـ ${t.key_tail} — ${t.ok ? 'نجح' : (t.error || 'خطأ')}
          </div>`).join('');
        container.appendChild(details);
      }
    });
  }

  async function runConnectionTest(resultEl, btn) {
    if (btn) { btn.disabled = true; btn.style.opacity = .6; }
    resultEl.innerHTML = '<div style="color:var(--muted);font-size:13px">⏳ جاري الفحص...</div>';
    try {
      const res = await fetch('admin.php?ajax=test_connection');
      const data = await res.json();
      if (data.success) renderChecks(resultEl, data.checks);
      else resultEl.innerHTML = '<div style="color:var(--danger)">فشل الفحص</div>';
    } catch {
      resultEl.innerHTML = '<div style="color:var(--danger)">تعذر الوصول لخادم الفحص</div>';
    }
    if (btn) { btn.disabled = false; btn.style.opacity = 1; }
  }

  const btnTest = document.getElementById('btn-test-connection');
  if (btnTest) btnTest.addEventListener('click', () => runConnectionTest(document.getElementById('connection-results'), btnTest));

  const btnTestInline = document.getElementById('btn-test-connection-inline');
  if (btnTestInline) btnTestInline.addEventListener('click', () => runConnectionTest(document.getElementById('inline-test-result'), btnTestInline));

  /* ── Fix DB button ── */
  const btnFixDb = document.getElementById('btn-fix-db');
  if (btnFixDb) {
    btnFixDb.addEventListener('click', async () => {
      const out = document.getElementById('db-fix-result');
      btnFixDb.disabled = true;
      out.innerHTML = '<div style="color:var(--muted);font-size:13px">⏳ جاري الفحص والإصلاح...</div>';
      try {
        const res = await fetch('admin.php?ajax=fix_db');
        const data = await res.json();
        out.innerHTML = data.success
          ? `<div style="color:var(--success);font-size:13px">✅ ${data.message} (${data.tables.join(', ')}) — أعد تحميل الصفحة لرؤية الأعداد المحدّثة</div>`
          : `<div style="color:var(--danger);font-size:13px">❌ ${data.error}</div>`;
      } catch { out.innerHTML = '<div style="color:var(--danger)">تعذر الاتصال</div>'; }
      btnFixDb.disabled = false;
    });
  }

  /* ── Populate free-model selects on settings page ── */
  const selPrimary = document.getElementById('sel-primary-model');
  const selFallback = document.getElementById('sel-fallback-model');
  if (selPrimary && selFallback) {
    fetch('admin.php?ajax=list_models').then(r => r.json()).then(data => {
      const hint = document.getElementById('models-hint');
      if (!data.success || !data.models.length) {
        if (hint) hint.textContent = 'تعذر تحميل القائمة — يمكنك كتابة اسم الموديل يدوياً';
        return;
      }
      const currentPrimary = selPrimary.value;
      const currentFallback = selFallback.value;
      [selPrimary, selFallback].forEach(sel => {
        const keep = sel.value;
        sel.innerHTML = '';
        data.models.forEach(m => {
          const opt = document.createElement('option');
          opt.value = m.id;
          opt.textContent = m.name + ' (مجاني)';
          sel.appendChild(opt);
        });
        // Ensure the previously-saved value is present even if not in the fetched list
        if (![...sel.options].some(o => o.value === keep)) {
          const opt = document.createElement('option');
          opt.value = keep; opt.textContent = keep;
          sel.insertBefore(opt, sel.firstChild);
        }
        sel.value = keep;
      });
      selPrimary.value = currentPrimary;
      selFallback.value = currentFallback;
      if (hint) hint.textContent = `تم تحميل ${data.models.length} موديل مجاني من OpenRouter`;
    }).catch(() => {
      const hint = document.getElementById('models-hint');
      if (hint) hint.textContent = 'تعذر تحميل القائمة — يمكنك كتابة اسم الموديل يدوياً';
    });
  }
  /* ── Import from Google Play ── */
  const btnImportPS = document.getElementById('btn-import-playstore');
  if (btnImportPS) {
    btnImportPS.addEventListener('click', async () => {
      const urlInput = document.getElementById('playstore-import-url');
      const status = document.getElementById('playstore-import-status');
      const url = urlInput?.value.trim();
      if (!url) { status.textContent = 'الصق رابط صفحة التطبيق من Google Play أولاً'; return; }
      status.textContent = '⏳ جاري الاستيراد...';
      btnImportPS.disabled = true;
      try {
        const res = await fetch('admin.php?ajax=fetch_playstore', {
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ url })
        });
        const data = await res.json();
        if (!data.success) { status.textContent = '❌ ' + data.error; btnImportPS.disabled = false; return; }
        const set = (id, v) => { const el = document.getElementById(id); if (el && v) el.value = v; };
        set('f-name', data.name); set('ai-name', data.name);
        set('f-short-desc', data.short_description);
        set('f-long-desc', data.long_description);
        set('f-pkg', data.package_name);
        const playstoreField = document.querySelector('[name=playstore_url]');
        if (playstoreField) playstoreField.value = data.playstore_url || url;
        if (data.icon_url) {
          const preview = document.getElementById('icon-preview');
          if (preview) { preview.src = data.icon_url; preview.style.display = 'block'; }
          const hidden = document.querySelector('[name=icon_url_import]');
          if (!hidden) {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = 'icon_url_import'; inp.value = data.icon_url;
            document.querySelector('form').appendChild(inp);
          } else { hidden.value = data.icon_url; }
        }
        status.textContent = '✅ ' + (data.note || 'تم الاستيراد — راجع الحقول ثم أكمل الباقي بالذكاء الاصطناعي');
      } catch { status.textContent = '❌ خطأ في الاتصال'; }
      btnImportPS.disabled = false;
    });
  }

  /* ── Continue long description ── */
  const btnContinue = document.getElementById('btn-continue-desc');
  const descField = document.getElementById('f-long-desc');
  const wordCountEl = document.getElementById('desc-word-count');
  function updateWordCount() {
    if (!descField || !wordCountEl) return;
    const words = descField.value.trim().split(/\s+/).filter(Boolean).length;
    wordCountEl.textContent = words ? `(${words} كلمة)` : '';
  }
  if (descField) { updateWordCount(); descField.addEventListener('input', updateWordCount); }
  if (btnContinue && descField) {
    btnContinue.addEventListener('click', async () => {
      const name = document.getElementById('f-name')?.value.trim();
      const status = document.getElementById('continue-desc-status');
      if (!name) { status.textContent = 'اكتب اسم التطبيق أولاً'; return; }
      btnContinue.disabled = true;
      status.textContent = '⏳ جاري كتابة الجزء التالي...';
      try {
        const res = await fetch('admin.php?ajax=continue_content', {
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ name, current: descField.value })
        });
        const data = await res.json();
        if (!data.success) { status.textContent = '❌ ' + data.error; btnContinue.disabled = false; return; }
        descField.value = (descField.value.trim() + '\n\n' + data.addition).trim();
        updateWordCount();
        status.textContent = `✅ تمت إضافة ~${data.added_words} كلمة — يمكنك الضغط مجدداً للمتابعة`;
      } catch { status.textContent = '❌ خطأ في الاتصال'; }
      btnContinue.disabled = false;
    });
  }

  /* ── Generate legal/offers content ── */
  document.querySelectorAll('.btn-gen-legal').forEach(btn => {
    btn.addEventListener('click', async () => {
      const type = btn.dataset.type;
      const name = document.getElementById('f-name')?.value.trim();
      const status = document.getElementById('legal-gen-status');
      const targetId = type === 'privacy' ? 'f-privacy' : (type === 'terms' ? 'f-terms' : 'f-offers');
      const field = document.getElementById(targetId);
      if (!name) { status.textContent = 'اكتب اسم التطبيق أولاً'; return; }
      btn.disabled = true;
      status.textContent = '⏳ جاري التوليد...';
      try {
        const res = await fetch('admin.php?ajax=generate_legal', {
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ name, type })
        });
        const data = await res.json();
        if (!data.success) { status.textContent = '❌ ' + data.error; btn.disabled = false; return; }
        if (field) field.value = data.content;
        status.textContent = '✅ تم التوليد — راجع النص ثم احفظ';
      } catch { status.textContent = '❌ خطأ في الاتصال'; }
      btn.disabled = false;
    });
  });

  /* ── Bulk SEO regeneration (dashboard) ── */
  const btnBulkSeo = document.getElementById('btn-bulk-seo');
  if (btnBulkSeo) {
    btnBulkSeo.addEventListener('click', async () => {
      const progress = document.getElementById('bulk-seo-progress');
      const bar = document.getElementById('bulk-seo-bar');
      const status = document.getElementById('bulk-seo-status');
      btnBulkSeo.disabled = true;
      progress.style.display = 'block';
      status.textContent = 'جاري جلب قائمة التطبيقات...';
      try {
        const listRes = await fetch('admin.php?ajax=bulk_seo_list');
        const listData = await listRes.json();
        const ids = listData.ids || [];
        if (!ids.length) { status.textContent = 'لا توجد تطبيقات منشورة'; btnBulkSeo.disabled = false; return; }
        let done = 0, failed = 0;
        for (const id of ids) {
          status.textContent = `جاري التحديث... (${done + failed + 1}/${ids.length})`;
          try {
            const r = await fetch(`admin.php?ajax=regen_seo&id=${id}`);
            const d = await r.json();
            d.success ? done++ : failed++;
          } catch { failed++; }
          bar.style.width = Math.round(((done + failed) / ids.length) * 100) + '%';
        }
        status.textContent = `✅ اكتمل: ${done} تم تحديثهم${failed ? `، فشل ${failed}` : ''}`;
      } catch { status.textContent = '❌ خطأ في الاتصال'; }
      btnBulkSeo.disabled = false;
    });
  }
});

function escHtml(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

/* ── Mobile sidebar toggle (off-canvas drawer) ── */
document.addEventListener('DOMContentLoaded', () => {
  const toggle  = document.getElementById('admin-menu-toggle');
  const sidebar = document.getElementById('admin-sidebar');
  const overlay = document.getElementById('admin-sidebar-overlay');
  if (!toggle || !sidebar || !overlay) return;

  function openMenu() {
    sidebar.classList.add('open');
    overlay.classList.add('open');
    toggle.setAttribute('aria-expanded', 'true');
  }
  function closeMenu() {
    sidebar.classList.remove('open');
    overlay.classList.remove('open');
    toggle.setAttribute('aria-expanded', 'false');
  }

  toggle.addEventListener('click', () => {
    sidebar.classList.contains('open') ? closeMenu() : openMenu();
  });
  overlay.addEventListener('click', closeMenu);
  sidebar.querySelectorAll('a').forEach(a => a.addEventListener('click', closeMenu));
  window.addEventListener('resize', () => { if (window.innerWidth > 900) closeMenu(); });
});
