/* ════════════════════════════════════════════
   YASSOTA — admin.js
   ════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', () => {

  /* ── VirusTotal scan / check ── */
  const vtOut = document.getElementById('vt-scan-result');
  async function vtRun(action, btn) {
    const appId = btn.dataset.appId;
    btn.disabled = true;
    if (vtOut) vtOut.innerHTML = '<span style="color:var(--muted)">⏳ جاري الاتصال بـ VirusTotal...</span>';
    try {
      const res = await fetch(`admin.php?ajax=${action}&app_id=${appId}`);
      const data = await res.json();
      if (data.success) {
        const msg = data.status === 'pending' ? 'تم إرسال الرابط للفحص — يستغرق حتى دقيقتين، اضغط "تحقق من النتيجة" لاحقاً.'
          : data.status === 'clean' ? '✅ الرابط آمن حسب VirusTotal.'
          : data.status === 'flagged' ? '⚠️ تنبيه: بعض محركات الفحص أشارت لهذا الرابط.'
          : 'تم التحديث.';
        if (vtOut) vtOut.innerHTML = `<span style="color:var(--success)">${msg}</span> <span style="color:var(--muted);font-size:12px">أعد تحميل الصفحة لرؤية الشارة المحدّثة</span>`;
      } else {
        if (vtOut) vtOut.innerHTML = `<span style="color:var(--danger)">❌ ${data.error || 'فشل الفحص'}</span>`;
      }
    } catch { if (vtOut) vtOut.innerHTML = '<span style="color:var(--danger)">تعذر الاتصال</span>'; }
    btn.disabled = false;
  }
  document.getElementById('btn-vt-scan')?.addEventListener('click', (e) => vtRun('vt_scan', e.currentTarget));
  document.getElementById('btn-vt-check')?.addEventListener('click', (e) => vtRun('vt_check', e.currentTarget));

  /* ── AI-generate a category SEO description ── */
  document.querySelectorAll('.btn-gen-cat-desc').forEach(btn => {
    btn.addEventListener('click', async () => {
      const form = btn.closest('form');
      const name = form?.dataset.catName;
      const textarea = form?.querySelector('textarea[name=description]');
      if (!name || !textarea) return;
      btn.disabled = true;
      const original = btn.textContent;
      btn.textContent = '⏳ ...';
      try {
        const res = await fetch('admin.php?ajax=generate_cat_description', {
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ name })
        });
        const data = await res.json();
        if (data.success) textarea.value = data.content;
        else alert(data.error || 'فشل التوليد');
      } catch { alert('خطأ في الاتصال'); }
      btn.textContent = original;
      btn.disabled = false;
    });
  });

  /* ── Generate 3 related articles for an app ── */
  const btnGenArticles = document.getElementById('btn-generate-articles');
  if (btnGenArticles) {
    btnGenArticles.addEventListener('click', async () => {
      const appId = btnGenArticles.dataset.appId;
      const out = document.getElementById('generate-articles-status');
      btnGenArticles.disabled = true;
      out.innerHTML = '<span style="color:var(--muted)">⏳ جاري توليد 3 مقالات...</span>';
      try {
        const res = await fetch(`admin.php?ajax=generate_articles&app_id=${appId}`);
        const data = await res.json();
        if (data.success) {
          out.innerHTML = `<span style="color:var(--success)">✅ تم إنشاء ${data.created} مقالات — أعد تحميل الصفحة لرؤيتها</span>`;
          setTimeout(() => location.reload(), 1200);
        } else {
          out.innerHTML = `<span style="color:var(--danger)">❌ ${data.error || 'فشل التوليد'}</span>`;
        }
      } catch { out.innerHTML = '<span style="color:var(--danger)">تعذر الاتصال</span>'; }
      btnGenArticles.disabled = false;
    });
  }

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

  /* ── Repair legacy text encoding button ── */
  const btnRepairEnc = document.getElementById('btn-repair-encoding');
  if (btnRepairEnc) {
    btnRepairEnc.addEventListener('click', async () => {
      const out = document.getElementById('repair-encoding-result');
      btnRepairEnc.disabled = true;
      out.innerHTML = '<div style="color:var(--muted);font-size:13px">⏳ جاري الفحص والإصلاح...</div>';
      try {
        const res = await fetch('admin.php?ajax=repair_encoding');
        const data = await res.json();
        out.innerHTML = data.success
          ? `<div style="color:var(--success);font-size:13px">✅ ${data.message}</div>`
          : `<div style="color:var(--danger);font-size:13px">❌ ${data.error}</div>`;
      } catch { out.innerHTML = '<div style="color:var(--danger)">تعذر الاتصال</div>'; }
      btnRepairEnc.disabled = false;
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

  /* ── AI-generate icon ── */
  const btnGenIcon = document.getElementById('btn-gen-icon-ai');
  if (btnGenIcon) {
    btnGenIcon.addEventListener('click', async () => {
      const name = document.getElementById('f-name')?.value.trim();
      const desc = document.getElementById('f-short-desc')?.value.trim();
      const status = document.getElementById('icon-ai-status');
      if (!name) { status.textContent = 'اكتب اسم التطبيق أولاً'; return; }
      btnGenIcon.disabled = true;
      status.textContent = '⏳ جاري توليد الأيقونة...';
      try {
        const res = await fetch('admin.php?ajax=generate_icon_ai', {
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ name, description: desc })
        });
        const data = await res.json();
        if (!data.success) { status.textContent = '❌ ' + data.error; btnGenIcon.disabled = false; return; }
        const preview = document.getElementById('icon-preview');
        if (preview) { preview.src = data.url; preview.style.display = 'block'; }
        const hidden = document.getElementById('f-ai-icon-path');
        if (hidden) hidden.value = data.path;
        status.textContent = '✅ تم التوليد — راجع الأيقونة ثم احفظ التطبيق';
      } catch { status.textContent = '❌ خطأ في الاتصال'; }
      btnGenIcon.disabled = false;
    });
  }

  /* ── AI-generate screenshot (repeatable) ── */
  const btnGenShot = document.getElementById('btn-gen-shot-ai');
  if (btnGenShot) {
    btnGenShot.addEventListener('click', async () => {
      const name = document.getElementById('f-name')?.value.trim();
      const desc = document.getElementById('f-short-desc')?.value.trim();
      const status = document.getElementById('shot-ai-status');
      const preview = document.getElementById('ai-shots-preview');
      if (!name) { status.textContent = 'اكتب اسم التطبيق أولاً'; return; }
      btnGenShot.disabled = true;
      status.textContent = '⏳ جاري توليد صورة...';
      try {
        const res = await fetch('admin.php?ajax=generate_screenshot_ai', {
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ name, description: desc })
        });
        const data = await res.json();
        if (!data.success) { status.textContent = '❌ ' + data.error; btnGenShot.disabled = false; return; }
        const img = document.createElement('img');
        img.src = data.url;
        img.style.cssText = 'width:70px;height:120px;object-fit:cover;border-radius:8px;border:1px solid var(--border-c)';
        preview.appendChild(img);
        const hidden = document.createElement('input');
        hidden.type = 'hidden'; hidden.name = 'ai_screenshot_paths[]'; hidden.value = data.path;
        preview.appendChild(hidden);
        status.textContent = '✅ تمت إضافة صورة — يمكنك التوليد مجدداً لصور إضافية';
      } catch { status.textContent = '❌ خطأ في الاتصال'; }
      btnGenShot.disabled = false;
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

  /* ── AI admin assistant (whitelisted-actions console) ── */
  const assistantInput = document.getElementById('assistant-input');
  const assistantBtn = document.getElementById('btn-assistant-send');
  const assistantLog = document.getElementById('assistant-log');
  function assistantAppend(role, text) {
    if (!assistantLog) return;
    const bubble = document.createElement('div');
    const isUser = role === 'user';
    bubble.style.cssText = `align-self:${isUser ? 'flex-end' : 'flex-start'};max-width:85%;padding:10px 14px;border-radius:12px;font-size:13px;line-height:1.7;white-space:pre-wrap;` +
      (isUser ? 'background:rgba(37,99,235,.08);border:1px solid rgba(37,99,235,.22);color:var(--white)'
              : 'background:var(--navy-600);border:1px solid var(--border-c);color:var(--white)');
    bubble.textContent = text;
    assistantLog.appendChild(bubble);
    assistantLog.scrollTop = assistantLog.scrollHeight;
  }
  async function sendAssistantMessage() {
    const msg = assistantInput?.value.trim();
    if (!msg) return;
    assistantAppend('user', msg);
    assistantInput.value = '';
    assistantBtn.disabled = true;
    assistantAppend('assistant', '⏳ ...');
    try {
      const res = await fetch('admin.php?ajax=assistant', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: msg })
      });
      const data = await res.json();
      assistantLog.lastChild.remove();
      if (!data.success) { assistantAppend('assistant', '❌ ' + data.error); assistantBtn.disabled = false; return; }
      let text = data.reply || '';
      if (data.result) text += (text ? '\n\n' : '') + data.result;
      assistantAppend('assistant', text || '(تم التنفيذ)');
    } catch {
      assistantLog.lastChild.remove();
      assistantAppend('assistant', '❌ خطأ في الاتصال');
    }
    assistantBtn.disabled = false;
  }
  if (assistantBtn) {
    assistantBtn.addEventListener('click', sendAssistantMessage);
    assistantInput.addEventListener('keydown', e => { if (e.key === 'Enter') sendAssistantMessage(); });
  }

  /* ── Bulk trending app/game generator ── */
  const btnBgSuggest = document.getElementById('btn-bg-suggest');
  if (btnBgSuggest) {
    const namesPanel = document.getElementById('bg-names-panel');
    const namesList = document.getElementById('bg-names-list');
    const suggestStatus = document.getElementById('bg-suggest-status');
    const btnBgCreate = document.getElementById('btn-bg-create');
    const bgProgress = document.getElementById('bg-progress');
    const bgBar = document.getElementById('bg-bar');
    const bgStatus = document.getElementById('bg-status');
    const bgResults = document.getElementById('bg-results');

    btnBgSuggest.addEventListener('click', async () => {
      const count = parseInt(document.getElementById('bg-count').value, 10) || 10;
      const type = document.getElementById('bg-type').value;
      const hint = document.getElementById('bg-hint').value.trim();
      btnBgSuggest.disabled = true;
      suggestStatus.textContent = '⏳ جاري اقتراح الأسماء...';
      try {
        const res = await fetch('admin.php?ajax=suggest_trending', {
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ count, type, hint })
        });
        const data = await res.json();
        if (!data.success) { suggestStatus.textContent = '❌ ' + data.error; btnBgSuggest.disabled = false; return; }
        namesList.innerHTML = '';
        data.names.forEach(name => {
          const row = document.createElement('label');
          row.style.cssText = 'display:flex;align-items:center;gap:10px;font-size:13px;cursor:pointer';
          row.innerHTML = `<input type="checkbox" checked value="${escHtml(name)}"><span>${escHtml(name)}</span>`;
          namesList.appendChild(row);
        });
        namesPanel.style.display = 'block';
        bgResults.innerHTML = '';
        suggestStatus.textContent = `✅ تم اقتراح ${data.names.length} اسم`;
      } catch { suggestStatus.textContent = '❌ خطأ في الاتصال'; }
      btnBgSuggest.disabled = false;
    });

    btnBgCreate.addEventListener('click', async () => {
      const type = document.getElementById('bg-type').value === 'games' ? 'games' : 'apps';
      const checked = [...namesList.querySelectorAll('input[type=checkbox]:checked')].map(c => c.value);
      if (!checked.length) { bgStatus.textContent = 'لم يتم تحديد أي اسم'; return; }
      btnBgCreate.disabled = true;
      bgProgress.style.display = 'block';
      bgResults.innerHTML = '';
      let done = 0, failed = 0;
      for (const name of checked) {
        bgStatus.textContent = `جاري الإنشاء... (${done + failed + 1}/${checked.length}) — ${name}`;
        try {
          const res = await fetch('admin.php?ajax=bulk_create_one', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, type })
          });
          const data = await res.json();
          const row = document.createElement('div');
          row.style.cssText = 'display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 14px;border-radius:10px;font-size:13px;' +
            (data.success ? 'background:rgba(0,230,118,.08);border:1px solid rgba(0,230,118,.2)' : 'background:rgba(255,68,102,.08);border:1px solid rgba(255,68,102,.2)');
          if (data.success) {
            done++;
            row.innerHTML = `<span>✅ ${escHtml(data.name)} — ${data.has_playstore ? 'تم ربطها بـ Play Store' : 'بدون رابط Play Store'}${data.has_icon ? '' : '، بدون أيقونة'}</span>
              <a href="${data.edit_url}" class="btn-edit" target="_blank">فتح للتعديل</a>`;
          } else {
            failed++;
            row.innerHTML = `<span>❌ ${escHtml(name)} — ${escHtml(data.error || 'فشل غير معروف')}</span>`;
          }
          bgResults.appendChild(row);
        } catch {
          failed++;
          const row = document.createElement('div');
          row.textContent = `❌ ${name} — خطأ في الاتصال`;
          bgResults.appendChild(row);
        }
        bgBar.style.width = Math.round(((done + failed) / checked.length) * 100) + '%';
      }
      bgStatus.textContent = `✅ اكتمل: ${done} تم إنشاؤهم${failed ? `، فشل ${failed}` : ''}`;
      btnBgCreate.disabled = false;
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
