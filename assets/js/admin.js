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

  /* ── Live slug preview on the add/edit-app form ── */
  const slugInput = document.getElementById('f-slug');
  const slugPreview = document.getElementById('slug-preview');
  const nameInputForSlug = document.getElementById('f-name');
  if (slugInput && slugPreview) {
    const clientSlugify = (s) => (s || '').trim()
      .replace(/\s+/g, '-')
      .replace(/[^؀-ۿa-zA-Z0-9\-]/g, '')
      .replace(/-+/g, '-')
      .replace(/^-|-$/g, '')
      .toLowerCase() || 'app-name';
    const updatePreview = () => {
      slugPreview.textContent = clientSlugify(slugInput.value || nameInputForSlug?.value || '');
    };
    slugInput.addEventListener('input', updatePreview);
    nameInputForSlug?.addEventListener('input', () => { if (!slugInput.value) updatePreview(); });
  }

  /* ── Generate a new blog post (tutorial/news/comparison/best-of/article) via AI ── */
  const btnGenBlog = document.getElementById('btn-generate-blog');
  if (btnGenBlog) {
    btnGenBlog.addEventListener('click', async () => {
      const type = document.getElementById('blog-gen-type')?.value || 'article';
      const topic = document.getElementById('blog-gen-topic')?.value || '';
      const out = document.getElementById('blog-gen-result');
      btnGenBlog.disabled = true;
      if (out) out.innerHTML = '<span style="color:var(--muted)">⏳ جاري توليد المقال بالذكاء الاصطناعي (قد يستغرق دقيقة)...</span>';
      try {
        const res = await fetch(`admin.php?ajax=generate_blog_post&type=${encodeURIComponent(type)}&topic=${encodeURIComponent(topic)}`);
        const data = await res.json();
        if (data.success) {
          if (out) out.innerHTML = `<span style="color:var(--success)">✅ تم إنشاء المقال "${data.title}" كمسودة.</span> <a href="admin.php?page=blog-edit&id=${data.id}" class="btn-edit" style="margin-inline-start:8px">راجعه الآن ←</a>`;
        } else {
          if (out) out.innerHTML = `<span style="color:var(--danger)">❌ ${data.error || 'فشل التوليد'}</span>`;
        }
      } catch { if (out) out.innerHTML = '<span style="color:var(--danger)">تعذر الاتصال</span>'; }
      btnGenBlog.disabled = false;
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
        // Note: name field is intentionally NOT filled — user manages the title themselves
        set('f-short-desc', data.short_description);
        set('f-long-desc', data.long_description);
        set('f-pkg', data.package_name);
        if (data.developer) set('f-developer', data.developer);
        const vField = document.querySelector('[name="version"]');
        if (data.version && vField && !vField.value) vField.value = data.version;
        const avField = document.querySelector('[name="android_version"]');
        if (data.android_version && avField && !avField.value) avField.value = data.android_version;
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
        // Inject screenshot URLs as hidden inputs + show previews
        const psInputsBox = document.getElementById('ps-screenshot-inputs');
        const psPreviewBox = document.getElementById('ps-screenshot-preview');
        if (psInputsBox) {
          psInputsBox.innerHTML = '';
          if (psPreviewBox) psPreviewBox.innerHTML = '';
          (data.screenshot_urls || []).forEach(su => {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = 'screenshot_urls_import[]'; inp.value = su;
            psInputsBox.appendChild(inp);
            if (psPreviewBox) {
              const img = document.createElement('img');
              img.src = su; img.style.cssText = 'width:60px;height:100px;object-fit:cover;border-radius:6px;border:1px solid var(--border-c)';
              psPreviewBox.appendChild(img);
            }
          });
          if ((data.screenshot_urls || []).length > 0) {
            const note = document.createElement('p');
            note.style.cssText = 'font-size:11px;color:var(--muted);margin:4px 0 0;width:100%';
            note.textContent = `سيتم استيراد ${data.screenshot_urls.length} لقطة شاشة من Play Store عند الحفظ`;
            psPreviewBox && psPreviewBox.appendChild(note);
          }
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

    document.getElementById('btn-bg-manual')?.addEventListener('click', () => {
      const raw = document.getElementById('bg-manual-names').value;
      const names = raw.split('\n').map(s => s.trim()).filter(Boolean);
      if (!names.length) { suggestStatus.textContent = '❌ أدخل اسماً واحداً على الأقل'; return; }
      namesList.innerHTML = '';
      names.forEach(name => {
        const row = document.createElement('label');
        row.style.cssText = 'display:flex;align-items:center;gap:10px;font-size:13px;cursor:pointer';
        row.innerHTML = `<input type="checkbox" checked value="${escHtml(name)}"><span>${escHtml(name)}</span>`;
        namesList.appendChild(row);
      });
      namesPanel.style.display = 'block';
      bgResults.innerHTML = '';
      suggestStatus.textContent = `✅ تم تحميل ${names.length} اسم يدوياً`;
      namesPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
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

  /* ── XHR form submit with progress overlay ── */
  const appForm = document.getElementById('app-form');
  if (appForm) {
    const overlay  = document.getElementById('app-save-overlay');
    const bar      = document.getElementById('app-save-bar');
    const statTxt  = document.getElementById('app-save-status');
    appForm.addEventListener('submit', function(e) {
      e.preventDefault();
      if (overlay) { overlay.style.display = 'flex'; }
      if (bar)     { bar.style.width = '0%'; }
      if (statTxt) { statTxt.textContent = 'رفع البيانات...'; }
      const fd = new FormData(appForm);
      const xhr = new XMLHttpRequest();
      xhr.open('POST', appForm.action || window.location.href);
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
      xhr.upload.onprogress = function(ev) {
        if (ev.lengthComputable && bar) {
          bar.style.width = Math.min(90, Math.round(ev.loaded / ev.total * 90)) + '%';
          if (statTxt) statTxt.textContent = 'رفع البيانات... ' + Math.round(ev.loaded / ev.total * 90) + '%';
        }
      };
      xhr.onload = function() {
        if (bar) bar.style.width = '100%';
        let data;
        try { data = JSON.parse(xhr.responseText); } catch(err) { data = null; }
        if (data && data.ok) {
          if (statTxt) statTxt.textContent = '✅ تم الحفظ — جاري التحويل...';
          setTimeout(() => { window.location.href = data.redirect; }, 400);
        } else {
          if (overlay) overlay.style.display = 'none';
          const msg = (data && data.error) ? data.error : 'حدث خطأ، حاول مجدداً';
          alert(msg);
        }
      };
      xhr.onerror = function() {
        if (overlay) overlay.style.display = 'none';
        alert('فشل الاتصال، حاول مجدداً');
      };
      xhr.send(fd);
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

/* ════ WYSIWYG Editor ════ */
(function () {
  const editor    = document.getElementById('wysiwyg-editor');
  const hidden    = document.getElementById('blog-body-hidden');
  const source    = document.getElementById('blog-body-source');
  const toolbar   = document.getElementById('wysiwyg-toolbar');
  const form      = document.getElementById('blog-edit-form');
  const blockSel  = document.getElementById('ws-block');
  const tplSel    = document.getElementById('blog-template-select');
  const toggleSrc = document.getElementById('blog-toggle-source');
  const fullBtn   = document.getElementById('ws-fullscreen');
  const colorIn   = document.getElementById('ws-color');

  if (!editor || !form) return;

  let sourceMode = false;

  // Sync hidden field on submit
  form.addEventListener('submit', () => {
    if (sourceMode) {
      hidden.value = source.value;
    } else {
      hidden.value = editor.innerHTML;
    }
  });

  // Format block (heading / blockquote / pre / p)
  if (blockSel) {
    blockSel.addEventListener('change', () => {
      const v = blockSel.value;
      if (!v) return;
      if (v === 'pre') {
        const lang = prompt('لغة البرمجة (js, php, python, html, css, sql, bash...):', 'js') || 'text';
        document.execCommand('insertHTML', false,
          `<pre><code class="language-${lang}">// الكود هنا\n</code></pre><p><br></p>`);
      } else {
        document.execCommand('formatBlock', false, v);
      }
      editor.focus();
      blockSel.value = '';
    });
  }

  // Toolbar buttons with data-cmd
  toolbar && toolbar.querySelectorAll('[data-cmd]').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      const cmd = btn.dataset.cmd;
      const val = btn.dataset.val || null;
      document.execCommand(cmd, false, val);
      editor.focus();
    });
  });

  // Text color picker
  if (colorIn) {
    colorIn.addEventListener('input', () => {
      document.execCommand('foreColor', false, colorIn.value);
      editor.focus();
    });
  }

  // Link
  const linkBtn = document.getElementById('ws-link');
  if (linkBtn) {
    linkBtn.addEventListener('click', () => {
      const url = prompt('أدخل الرابط (URL):', 'https://');
      if (url) document.execCommand('createLink', false, url);
      editor.focus();
    });
  }

  // Image by URL
  const imgBtn = document.getElementById('ws-image');
  if (imgBtn) {
    imgBtn.addEventListener('click', () => {
      const url = prompt('رابط الصورة (URL):', 'https://');
      if (url) document.execCommand('insertImage', false, url);
      editor.focus();
    });
  }

  // Code block
  const codeBtn = document.getElementById('ws-code');
  if (codeBtn) {
    codeBtn.addEventListener('click', () => {
      const lang = prompt('لغة البرمجة (js, php, python, html, css, sql, bash, ...):', 'js') || 'text';
      document.execCommand('insertHTML', false,
        `<pre><code class="language-${lang}">// الكود هنا\n</code></pre><p><br></p>`);
      editor.focus();
    });
  }

  // Toggle HTML source
  if (toggleSrc) {
    toggleSrc.addEventListener('click', () => {
      if (!sourceMode) {
        source.value = editor.innerHTML;
        editor.style.display = 'none';
        toolbar && (toolbar.style.opacity = '.4');
        toolbar && (toolbar.style.pointerEvents = 'none');
        source.style.display = '';
        toggleSrc.textContent = '👁 معاينة';
        sourceMode = true;
      } else {
        editor.innerHTML = source.value;
        source.style.display = 'none';
        editor.style.display = '';
        toolbar && (toolbar.style.opacity = '');
        toolbar && (toolbar.style.pointerEvents = '');
        toggleSrc.textContent = '</> HTML';
        sourceMode = false;
      }
    });
  }

  // Fullscreen toggle
  if (fullBtn) {
    fullBtn.addEventListener('click', () => {
      const wrap = editor.closest('.form-group');
      wrap && wrap.classList.toggle('wysiwyg-fullscreen');
      editor.focus();
    });
  }

  // Templates
  const TEMPLATES = {
    news: `<h2>عنوان الخبر</h2>
<p>أعلنت [الجهة] اليوم عن [الخبر]. يأتي هذا الإعلان في سياق [السياق].</p>
<h3>أبرز التفاصيل</h3>
<ul><li>تفصيل أول</li><li>تفصيل ثانٍ</li><li>تفصيل ثالث</li></ul>
<h3>ماذا يعني هذا للمستخدمين؟</h3>
<p>يُتيح هذا التحديث للمستخدمين [الفائدة]. ويُتوقع أن [التوقع].</p>
<p>متابعة المستجدات على yassota.</p>`,

    tutorial: `<h2>كيف تفعل [المهمة] خطوة بخطوة</h2>
<p>في هذا الشرح سنتعلم كيف نُنجز [المهمة] بسهولة.</p>
<h3>المتطلبات</h3>
<ul><li>متطلب أول</li><li>متطلب ثانٍ</li></ul>
<h3>الخطوة الأولى: [اسم الخطوة]</h3>
<p>شرح تفصيلي للخطوة الأولى...</p>
<pre><code class="language-bash">// مثال على أمر أو كود</code></pre>
<h3>الخطوة الثانية: [اسم الخطوة]</h3>
<p>شرح تفصيلي للخطوة الثانية...</p>
<h3>النتيجة النهائية</h3>
<p>الآن أصبح بإمكانك [النتيجة]. شارك تجربتك في التعليقات!</p>`,

    comparison: `<h2>[التطبيق الأول] مقابل [التطبيق الثاني]: أيهما أفضل؟</h2>
<p>نقارن اليوم بين [الأول] و[الثاني] لمساعدتك في اختيار الأنسب.</p>
<h3>جدول المقارنة</h3>
<table><tr><th>الميزة</th><th>${'[الأول]'}</th><th>${'[الثاني]'}</th></tr>
<tr><td>السعر</td><td>مجاني</td><td>مجاني</td></tr>
<tr><td>الحجم</td><td>—</td><td>—</td></tr>
<tr><td>التقييم</td><td>—</td><td>—</td></tr></table>
<h3>مزايا [الأول]</h3>
<ul><li>ميزة</li></ul>
<h3>مزايا [الثاني]</h3>
<ul><li>ميزة</li></ul>
<h3>الخلاصة</h3>
<p>إذا كنت [نوع المستخدم] فـ[التوصية]. يمكنك تحميل كليهما مجاناً من yassota.</p>`,

    'top-list': `<h2>أفضل [العدد] تطبيقات [المجال] لعام ${new Date().getFullYear()}</h2>
<p>جمعنا لك قائمة بأفضل تطبيقات [المجال] المتاحة الآن على أندرويد.</p>
<h3>1. [اسم التطبيق]</h3>
<p>وصف قصير لما يميز هذا التطبيق ولماذا يستحق مكانه الأول في القائمة.</p>
<h3>2. [اسم التطبيق]</h3>
<p>وصف قصير...</p>
<h3>3. [اسم التطبيق]</h3>
<p>وصف قصير...</p>
<h3>الخلاصة</h3>
<p>هذه أفضل خياراتنا لتطبيقات [المجال]. جميعها متاحة للتحميل المجاني على yassota.</p>`,

    review: `<h2>مراجعة [اسم التطبيق]: هل يستحق التحميل؟</h2>
<p>[اسم التطبيق] هو تطبيق [الفئة] يُتيح للمستخدمين [الوظيفة الرئيسية].</p>
<h3>واجهة المستخدم</h3>
<p>تتميز الواجهة بـ[الوصف]. سهل التنقل وبديهي للمستخدم الجديد.</p>
<h3>المزايا ✅</h3>
<ul><li>ميزة رئيسية</li><li>ميزة ثانية</li><li>مجاني بالكامل</li></ul>
<h3>العيوب ❌</h3>
<ul><li>عيب محتمل</li></ul>
<h3>التقييم الإجمالي</h3>
<p>⭐⭐⭐⭐ (4/5) — [جملة ختامية]. <a href="#">حمّل ${('[اسم التطبيق]')} مجاناً الآن</a>.</p>`,
  };

  if (tplSel) {
    tplSel.addEventListener('change', () => {
      const key = tplSel.value;
      if (!key || !TEMPLATES[key]) return;
      if (editor.innerHTML.trim() && !confirm('سيُستبدل المحتوى الحالي بالقالب. متأكد؟')) {
        tplSel.value = ''; return;
      }
      editor.innerHTML = TEMPLATES[key];
      editor.focus();
      tplSel.value = '';
    });
  }
})();

/* ── Blog slug preview (same pattern as app slug preview) ── */
(function () {
  const slugInput  = document.getElementById('blog-slug');
  const slugPreview= document.getElementById('blog-slug-preview');
  const titleInput = document.getElementById('blog-title');
  if (!slugInput || !slugPreview) return;

  const clientSlugify = s => (s || '').trim()
    .replace(/\s+/g, '-')
    .replace(/[^؀-ۿa-zA-Z0-9\-]/g, '')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '')
    .toLowerCase() || 'post-slug';

  const updatePreview = () => {
    slugPreview.textContent = clientSlugify(slugInput.value || titleInput?.value || '');
  };
  slugInput.addEventListener('input', updatePreview);
  titleInput?.addEventListener('input', () => { if (!slugInput.value) updatePreview(); });
  updatePreview();
})();

/* ── Internal security verification (replaces VirusTotal) ── */
(function () {
  const btn = document.getElementById('btn-verify-link');
  if (!btn) return;
  btn.addEventListener('click', async () => {
    const appId  = btn.dataset.appId;
    const action = btn.dataset.action || 'verify';
    btn.disabled = true;
    btn.textContent = '...';
    try {
      const fd = new FormData();
      fd.append('app_id', appId);
      fd.append('action', action);
      const r = await fetch('admin.php?ajax=verify_link', { method: 'POST', body: fd });
      const d = await r.json();
      const statusEl = document.getElementById('verify-status');
      const resultEl = document.getElementById('verify-result');
      if (d.success) {
        if (statusEl) {
          statusEl.innerHTML = d.verified
            ? '<span class="status-badge status-published">✓ تم التحقق من سلامة الرابط</span>'
            : '<span class="status-badge status-draft">لم يتم التحقق من الرابط بعد</span>';
        }
        btn.dataset.action = d.verified ? 'unverify' : 'verify';
        btn.textContent = d.verified ? 'إلغاء التحقق' : '✓ تحقق الفريق — الرابط آمن';
        btn.style.background = d.verified ? 'rgba(220,38,38,.1)' : '';
        btn.style.color      = d.verified ? 'var(--danger)' : '';
        if (resultEl) resultEl.innerHTML = d.verified
          ? '<span style="color:var(--success);font-size:13px">✓ تمت إضافة شارة التحقق على صفحة التطبيق وصفحة التحميل</span>'
          : '<span style="color:var(--muted);font-size:13px">تم إزالة الشارة</span>';
      } else {
        if (resultEl) resultEl.innerHTML = `<span style="color:var(--danger)">${d.error}</span>`;
      }
    } catch (e) {
      alert('خطأ في الاتصال');
    } finally {
      btn.disabled = false;
    }
  });
})();

/* ── Settings page: AI provider toggle, multi-key manager, free model presets, Telegram test ── */
(function () {
  // AI provider radio toggle — dims OpenRouter panel when aifreeforever selected
  document.querySelectorAll('input[name="ai_provider"]').forEach(radio => {
    radio.addEventListener('change', () => {
      const isFree = radio.value === 'aifreeforever' && radio.checked;
      const orPanel = document.getElementById('openrouter-panel');
      const freeWrap = document.getElementById('test-aifree-wrap');
      if (orPanel) { orPanel.style.opacity = isFree ? '.45' : '1'; orPanel.style.pointerEvents = isFree ? 'none' : ''; }
      if (freeWrap) freeWrap.style.display = isFree ? '' : 'none';
      // Update card border highlight
      document.querySelectorAll('.ai-provider-card').forEach(c => {
        const inp = c.querySelector('input[type=radio]');
        c.style.borderColor = inp && inp.checked ? 'var(--cyan)' : 'var(--border-c)';
      });
    });
  });

  // aifreeforever test button
  const btnFree = document.getElementById('btn-test-aifree');
  if (btnFree) {
    btnFree.addEventListener('click', async () => {
      const resultEl = document.getElementById('aifree-test-result');
      btnFree.disabled = true; if (resultEl) resultEl.textContent = 'جاري الاختبار...';
      try {
        // Quick smoke test — POST to our test_connection endpoint which now checks the current provider
        const fd = new FormData();
        const r = await fetch('admin.php?ajax=test_connection', { method: 'POST', body: fd });
        const d = await r.json();
        const freeCheck = d.checks && d.checks.find(c => c.label && c.label.includes('aifreeforever'));
        if (freeCheck) {
          if (resultEl) resultEl.innerHTML = freeCheck.ok
            ? '<span style="color:var(--success)">✓ الاتصال ناجح</span>'
            : `<span style="color:var(--danger)">✗ ${freeCheck.detail}</span>`;
        } else {
          if (resultEl) resultEl.innerHTML = '<span style="color:var(--muted)">احفظ الإعدادات أولاً ثم اختبر مجدداً</span>';
        }
      } catch (e) { if (resultEl) resultEl.textContent = 'خطأ في الاتصال'; }
      finally { btnFree.disabled = false; }
    });
  }

  // Add API key row
  const btnAddKey = document.getElementById('btn-add-key');
  if (btnAddKey) {
    btnAddKey.addEventListener('click', () => {
      const wrap = document.getElementById('openrouter-keys-wrap');
      if (!wrap) return;
      const row = document.createElement('div');
      row.className = 'key-row';
      row.style.cssText = 'display:flex;gap:8px;margin-bottom:8px';
      row.innerHTML = `<input class="form-input or-key-input" type="text" name="openrouter_key_multi[]" value="" placeholder="sk-or-v1-..." dir="ltr" style="flex:1;font-family:var(--f-mono);font-size:12px">
        <button type="button" class="btn-remove-key" style="padding:8px 12px;background:rgba(255,68,102,.15);border:1px solid rgba(255,68,102,.3);border-radius:8px;color:var(--danger);font-size:18px;line-height:1;cursor:pointer" title="حذف" onclick="this.closest('.key-row').remove()">×</button>`;
      wrap.appendChild(row);
      row.querySelector('input').focus();
    });
  }

  // Preset model buttons
  document.querySelectorAll('.preset-model-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const model = btn.dataset.model;
      const primarySel = document.getElementById('sel-primary-model');
      if (!primarySel) return;
      // Add option if not already present
      if (![...primarySel.options].some(o => o.value === model)) {
        const opt = new Option(model, model);
        primarySel.appendChild(opt);
      }
      primarySel.value = model;
      btn.style.background = 'rgba(6,182,212,.25)';
      btn.style.borderColor = 'var(--cyan)';
      document.querySelectorAll('.preset-model-btn').forEach(b => { if (b !== btn) { b.style.background = ''; b.style.borderColor = ''; } });
    });
  });

  // On form submit: merge multi-key inputs + base64-encode ad code to bypass mod_security
  const settingsForm = document.querySelector('form[action*="page=settings"]');
  if (settingsForm) {
    settingsForm.addEventListener('submit', () => {
      const inputs = settingsForm.querySelectorAll('.or-key-input');
      const hidden = document.getElementById('openrouter-key-hidden');
      if (hidden) hidden.value = [...inputs].map(i => i.value.trim()).filter(Boolean).join('\n');

      // Base64-encode the ad code textarea so mod_security doesn't block <script> tags
      const adTa = settingsForm.querySelector('[name="download_custom_ad_code"]');
      if (adTa && adTa.value.trim()) {
        try {
          const encoded = btoa(unescape(encodeURIComponent(adTa.value)));
          let b64inp = settingsForm.querySelector('[name="download_custom_ad_code_b64"]');
          if (!b64inp) {
            b64inp = document.createElement('input');
            b64inp.type = 'hidden';
            b64inp.name = 'download_custom_ad_code_b64';
            settingsForm.appendChild(b64inp);
          }
          b64inp.value = encoded;
          adTa.name = '_download_custom_ad_code_raw'; // disable raw submission
        } catch(e) { /* btoa failed — let raw value through */ }
      }
    });
  }

  // Telegram test
  const btnTg = document.getElementById('btn-telegram-test');
  if (btnTg) {
    btnTg.addEventListener('click', async () => {
      const resEl = document.getElementById('telegram-test-result');
      btnTg.disabled = true; if (resEl) resEl.textContent = 'جاري الإرسال...';
      try {
        const r = await fetch('admin.php?ajax=telegram_test', { method: 'POST' });
        const d = await r.json();
        if (resEl) resEl.innerHTML = d.success
          ? '<span style="color:var(--success)">✓ تم الإرسال بنجاح — تحقق من قناتك</span>'
          : `<span style="color:var(--danger)">✗ ${d.error || 'فشل الإرسال'}</span>`;
      } catch (e) { if (resEl) resEl.textContent = 'خطأ'; }
      finally { btnTg.disabled = false; }
    });
  }
})();

/* ══════════════════════════════════════════
   Blog body: "Fix HTML" button — converts plain-text content to HTML paragraphs
   ══════════════════════════════════════════ */
(function () {
  const btn = document.getElementById('btn-fix-body-html');
  if (!btn) return;
  btn.addEventListener('click', () => {
    const editor = document.getElementById('wysiwyg-editor');
    if (!editor) return;
    let text = editor.innerText || editor.textContent || '';
    if (!text.trim()) return;
    // Split by double newlines → wrap each block in <p>
    const paras = text.split(/\n{2,}/).map(s => s.trim()).filter(Boolean);
    const html  = paras.map(p => `<p>${p.replace(/\n/g, '<br>')}</p>`).join('\n');
    editor.innerHTML = html;
    btn.textContent = '✓ تم التحويل';
    setTimeout(() => btn.textContent = '🔧 تحويل لـ HTML', 2500);
  });
})();

/* ══════════════════════════════════════════
   Code-page editor — blog-edit form
   ══════════════════════════════════════════ */
(function () {
  const typeSelect  = document.querySelector('select[name="type"]');
  const wysiwygWrap = document.getElementById('blog-wysiwyg-wrap');
  const cpWrap      = document.getElementById('blog-codepage-wrap');
  if (!typeSelect || !cpWrap) return;

  function syncEditorMode() {
    const isCodePage = typeSelect.value === 'code-page';
    if (wysiwygWrap) wysiwygWrap.style.display = isCodePage ? 'none' : '';
    cpWrap.style.display = isCodePage ? '' : 'none';
  }
  typeSelect.addEventListener('change', syncEditorMode);
  syncEditorMode(); // run once on load

  // Line counter per textarea
  function updateLines(lang) {
    const ta = document.getElementById('cp-textarea-' + lang);
    const el = document.getElementById('cp-lines-' + lang);
    if (!ta || !el) return;
    const lines = ta.value ? ta.value.split('\n').length : 0;
    el.textContent = lines ? lines + ' سطر' : 'فارغ';
  }

  document.querySelectorAll('.cp-textarea').forEach(ta => {
    const lang = ta.dataset.lang;
    updateLines(lang);
    ta.addEventListener('input', () => updateLines(lang));
    // Tab key inserts spaces instead of changing focus
    ta.addEventListener('keydown', e => {
      if (e.key === 'Tab') {
        e.preventDefault();
        const s = ta.selectionStart, end = ta.selectionEnd;
        ta.value = ta.value.substring(0, s) + '  ' + ta.value.substring(end);
        ta.selectionStart = ta.selectionEnd = s + 2;
        updateLines(lang);
      }
    });
  });

  // Ensure sections with existing code start open with toggle button correct
  document.querySelectorAll('.cp-section').forEach(sec => {
    const lang   = sec.dataset.lang;
    const body   = document.getElementById('cp-body-' + lang);
    const toggle = document.getElementById('cp-toggle-' + lang);
    if (body && body.style.display !== 'none' && toggle) toggle.classList.add('open');
  });
})();

/* ══════════════════════════════════════════
   Smart paste / auto-detect for code-page
   ══════════════════════════════════════════ */
(function () {
  const CP_DEFS = {
    html:   { label:'HTML',   icon:'🌐', color:'#e34f26', exts:['html','htm','xhtml'] },
    css:    { label:'CSS',    icon:'🎨', color:'#1572b6', exts:['css','scss','less','sass'] },
    js:     { label:'JS',     icon:'⚡', color:'#f7df1e', exts:['js','javascript','ts','typescript','mjs'] },
    php:    { label:'PHP',    icon:'🐘', color:'#777bb4', exts:['php','php7','php8'] },
    python: { label:'Python', icon:'🐍', color:'#3776ab', exts:['python','py','py3'] },
    java:   { label:'Java',   icon:'☕', color:'#f89820', exts:['java'] },
    kotlin: { label:'Kotlin', icon:'🎯', color:'#7f52ff', exts:['kotlin','kt','kts'] },
    cpp:    { label:'C++',    icon:'⚙️', color:'#00599c', exts:['cpp','c++','cxx','cc','c','h','hpp'] },
  };
  window.CP_DEFS = CP_DEFS;

  function normLang(name) {
    const n = (name || '').toLowerCase().replace(/\s/g,'');
    if (CP_DEFS[n]) return n;
    for (const [k, d] of Object.entries(CP_DEFS)) {
      if (d.exts.includes(n)) return k;
    }
    return null;
  }

  function autoDetect(code) {
    const s = { html:0, css:0, js:0, php:0, python:0, java:0, kotlin:0, cpp:0 };
    if (/\<\?php/.test(code))                            s.php    += 25;
    if (/\$[a-zA-Z_]\w*\s*[=;(,\-]/.test(code))         s.php    += 10;
    if (/#include\s*[<"]/.test(code))                    s.cpp    += 25;
    if (/\bint\s+main\s*\(/.test(code))                  s.cpp    += 15;
    if (/\bcout\s*<</.test(code))                        s.cpp    += 15;
    if (/\bstd::/.test(code))                            s.cpp    += 10;
    if (/\bpublic\s+class\b/.test(code))                 s.java   += 15;
    if (/\bSystem\.out\.print/.test(code))               s.java   += 15;
    if (/\bimport\s+java\./.test(code))                  s.java   += 12;
    if (/\bvoid\s+\w+\s*\(/.test(code) && !/fun /.test(code)) s.java += 5;
    if (/\bfun\s+\w+\s*[\(<]/.test(code))               s.kotlin += 22;
    if (/\bval\s+\w+\s*[=:]/.test(code))                s.kotlin += 10;
    if (/\bprintln\s*\(/.test(code))                    s.kotlin += 10;
    if (/^\s*def\s+\w+\s*\(/m.test(code))               s.python += 18;
    if (/^\s*import\s+[\w.]+$/m.test(code))             s.python += 8;
    if (/\bprint\s*\(/.test(code))                      s.python += 7;
    if (/^\s*class\s+\w+.*:/m.test(code))               s.python += 8;
    if (/<(!DOCTYPE|html|head|body|div|span|p|a|h[1-6])\b/i.test(code)) s.html += 18;
    if (/<\/\w+>/.test(code))                           s.html   += 8;
    if (/\{[^}]*[\w-]+\s*:[^}]+\}/.test(code) && !/</.test(code)) s.css += 18;
    if (/@(media|keyframes|import|root)\b/.test(code)) s.css    += 15;
    if (/:root\b/.test(code))                           s.css    += 10;
    if (/\b(const|let|var)\s+\w+\s*=/.test(code))      s.js     += 12;
    if (/\b(function|async|await|=>)\b/.test(code))     s.js     += 10;
    if (/\bdocument\.\w+/.test(code))                   s.js     += 12;
    if (/\bconsole\./.test(code))                       s.js     += 8;
    let best='html', top=0;
    for (const [k,v] of Object.entries(s)) if (v>top){top=v;best=k;}
    return best;
  }

  function parseInput(text) {
    const result = {};

    // 1. Markdown fences: ```lang ... ```
    const fenceRe = /```([\w+.\-]+)\s*\n([\s\S]*?)```/g;
    let m, gotFences = false;
    while ((m = fenceRe.exec(text)) !== null) {
      const lang = normLang(m[1]);
      if (lang) { gotFences=true; result[lang]=(result[lang]?result[lang]+'\n':'')+m[2].trimEnd(); }
    }
    if (gotFences) return result;

    // 2. === lang === or --- lang --- separators (whole line)
    const sepRe  = /^[ \t]*(?:={3,}|-{3,})[ \t]*([\w+]+)[ \t]*(?:={3,}|-{3,})[ \t]*$/gim;
    const sepAll = [...text.matchAll(sepRe)];
    if (sepAll.length > 0) {
      const parts = text.split(/^[ \t]*(?:={3,}|-{3,})[ \t]*[\w+]+[ \t]*(?:={3,}|-{3,})[ \t]*$/im);
      sepAll.forEach((match, i) => {
        const lang = normLang(match[1]);
        const code = parts[i+1];
        if (lang && code?.trim()) result[lang] = code.trim();
      });
      if (Object.keys(result).length) return result;
    }

    // 3. Single-block auto-detect
    const lang = autoDetect(text);
    result[lang] = text;
    return result;
  }

  function fillSection(lang, code) {
    const ta     = document.getElementById('cp-textarea-'+lang);
    const body   = document.getElementById('cp-body-'+lang);
    const toggle = document.getElementById('cp-toggle-'+lang);
    const lines  = document.getElementById('cp-lines-'+lang);
    if (!ta) return false;
    ta.value = code;
    ta.dispatchEvent(new Event('input'));
    if (body)   body.style.display = '';
    if (toggle) toggle.classList.add('open');
    if (lines)  lines.textContent = code ? (code.split('\n').length+' سطر') : 'فارغ';
    return true;
  }

  window.cpSmartDetect = function() {
    const raw = (document.getElementById('cp-smart-input')?.value||'').trim();
    const res = document.getElementById('cp-smart-result');
    if (!raw) return;

    const sections = parseInput(raw);
    const keys = Object.keys(sections);
    const filled = keys.filter(k => fillSection(k, sections[k]));

    if (!res) return;
    if (!filled.length) {
      res.style.display=''; res.innerHTML='<span style="color:#f87171">⚠️ لم يتم الكشف عن أي لغة</span>';
      return;
    }
    res.style.display='';
    res.innerHTML = '<span class="cp-smart-ok">✓ تمت المزامنة:</span> '
      + filled.map(lang => {
          const d = CP_DEFS[lang]||{label:lang,icon:'',color:'#64748b'};
          return `<span class="cp-smart-chip" style="--lc:${d.color}">${d.icon} ${d.label}</span>`;
        }).join('');

    // scroll to first section
    const firstSec = document.getElementById('cp-section-'+filled[0]);
    if (firstSec) firstSec.scrollIntoView({behavior:'smooth',block:'start'});
  };

  window.cpSmartClear = function() {
    const inp = document.getElementById('cp-smart-input');
    const res = document.getElementById('cp-smart-result');
    if (inp) inp.value='';
    if (res) { res.style.display='none'; res.innerHTML=''; }
  };

  // Auto-detect on paste into the smart textarea (100ms delay to read pasted value)
  document.addEventListener('DOMContentLoaded', function() {
    const inp = document.getElementById('cp-smart-input');
    if (!inp) return;
    inp.addEventListener('paste', function() { setTimeout(window.cpSmartDetect, 120); });
    inp.addEventListener('keydown', function(e) {
      if (e.key==='Tab') { e.preventDefault(); const s=inp.selectionStart,end=inp.selectionEnd; inp.value=inp.value.substring(0,s)+'  '+inp.value.substring(end); inp.selectionStart=inp.selectionEnd=s+2; }
    });
  });
})();

/* Global helpers called from onclick attributes in the code-page editor */
function cpToggle(lang) {
  const body   = document.getElementById('cp-body-' + lang);
  const toggle = document.getElementById('cp-toggle-' + lang);
  if (!body) return;
  const open = body.style.display !== 'none';
  body.style.display = open ? 'none' : '';
  if (toggle) toggle.classList.toggle('open', !open);
}

function cpClear(lang) {
  const ta = document.getElementById('cp-textarea-' + lang);
  if (ta && confirm('مسح كل محتوى قسم ' + lang + '؟')) {
    ta.value = '';
    const el = document.getElementById('cp-lines-' + lang);
    if (el) el.textContent = 'فارغ';
  }
}

function cpCopy(lang) {
  const ta = document.getElementById('cp-textarea-' + lang);
  if (!ta || !ta.value) return;
  navigator.clipboard.writeText(ta.value).then(() => {
    const btn = document.querySelector(`[onclick="cpCopy('${lang}')"]`);
    if (btn) { const orig = btn.textContent; btn.textContent = '✓ تم النسخ'; setTimeout(() => btn.textContent = orig, 1800); }
  });
}

function cpExpand(lang) {
  const ta = document.getElementById('cp-textarea-' + lang);
  if (ta) ta.classList.toggle('cp-expanded');
}
