'use strict';
/* ShamHost — واجهة لوحة التحكم */

const $ = (s, r) => (r || document).querySelector(s);
const h = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));

/* ── نداءات الواجهة الخلفية ── */
async function api(path, opts = {}) {
  const init = { method: opts.method || 'GET', headers: { 'X-ShamHost': '1' }, credentials: 'same-origin' };
  if (opts.body !== undefined) {
    init.headers['Content-Type'] = 'application/json';
    init.body = JSON.stringify(opts.body);
  }
  const res = await fetch(path, init);
  let data = {};
  try { data = await res.json(); } catch (_) {}
  if (res.status === 401) { showLogin(); throw new Error('انتهت الجلسة، سجّل الدخول من جديد'); }
  if (!res.ok || data.ok === false) throw new Error(data.error || ('خطأ ' + res.status));
  return data;
}

function toast(msg, kind) {
  const box = document.createElement('div');
  if (kind) box.className = kind;
  box.textContent = msg;
  $('#toast').appendChild(box);
  setTimeout(() => box.remove(), kind === 'bad' ? 9000 : 4500);
}
const okToast = m => toast(m, 'ok');
const errToast = e => toast(typeof e === 'string' ? e : (e.message || 'خطأ غير متوقع'), 'bad');

function modal(title, html, actions) {
  const box = $('#modalBox');
  box.innerHTML = '<h3>' + h(title) + '</h3>' + html +
    '<div class="acts">' + (actions || []).map((a, i) =>
      '<button class="btn ' + (a.kind || 'ghost') + '" data-act="' + i + '">' + h(a.label) + '</button>').join('') + '</div>';
  $('#modal').classList.add('on');
  box.querySelectorAll('[data-act]').forEach(b => {
    b.onclick = async () => {
      const a = actions[+b.dataset.act];
      if (!a.run) return closeModal();
      b.disabled = true; b.innerHTML = '<span class="spin"></span>';
      try { await a.run(box); closeModal(); } catch (e) { errToast(e); b.disabled = false; b.textContent = a.label; }
    };
  });
}
const closeModal = () => $('#modal').classList.remove('on');
$('#modal').onclick = (e) => { if (e.target.id === 'modal') closeModal(); };

const confirmBox = (msg, onYes) => modal('تأكيد', '<p>' + h(msg) + '</p>',
  [{ label: 'إلغاء' }, { label: 'تأكيد', kind: 'bad', run: onYes }]);

/* ── الصفحات ── */
const PAGES = [
  { id: 'dash',     icon: '📊', title: 'لوحة القيادة' },
  { id: 'wizard',   icon: '🪄', title: 'النشر الآلي' },
  { id: 'sites',    icon: '🌐', title: 'المواقع' },
  { id: 'domains',  icon: '🔗', title: 'الدومينات و DNS' },
  { id: 'tunnels',  icon: '🚇', title: 'الأنفاق' },
  { id: 'ssl',      icon: '🔒', title: 'شهادات SSL' },
  { id: 'db',       icon: '🗃️', title: 'قواعد البيانات' },
  { id: 'files',    icon: '📁', title: 'مدير الملفات' },
  { id: 'cron',     icon: '⏰', title: 'المهام المجدولة' },
  { id: 'backups',  icon: '💾', title: 'النسخ الاحتياطي' },
  { id: 'terminal', icon: '⌨️', title: 'الطرفية' },
  { id: 'logs',     icon: '📜', title: 'السجلات' },
  { id: 'settings', icon: '⚙️', title: 'الإعدادات' },
];

let current = 'dash';

function buildNav() {
  $('#nav').innerHTML = PAGES.map(p =>
    '<button data-page="' + p.id + '"><span class="ic">' + p.icon + '</span>' + h(p.title) + '</button>').join('');
  $('#nav').querySelectorAll('button').forEach(b => {
    b.onclick = () => { go(b.dataset.page); closeSide(); };
  });
}

async function go(id) {
  current = id;
  const page = PAGES.find(p => p.id === id) || PAGES[0];
  $('#pageTitle').textContent = page.title;
  $('#nav').querySelectorAll('button').forEach(b => b.classList.toggle('on', b.dataset.page === id));
  location.hash = id;
  $('#view').innerHTML = '<div class="card"><span class="spin"></span> جارٍ التحميل…</div>';
  try { await RENDER[id](); }
  catch (e) { $('#view').innerHTML = '<div class="card"><p class="muted">تعذّر التحميل</p><pre class="out">' + h(e.message) + '</pre></div>'; }
}

const RENDER = {};

/* ═══════════ لوحة القيادة ═══════════ */
RENDER.dash = async () => {
  const { summary: s } = await api('/api/system/summary');
  const svc = s.services.map(x =>
    '<span class="pill ' + (x.running ? 'ok' : 'bad') + '">' + h(x.name) + '</span>').join(' ');
  const bat = s.battery;
  const up = (sec) => {
    const d = Math.floor(sec / 86400), hh = Math.floor(sec % 86400 / 3600), mm = Math.floor(sec % 3600 / 60);
    return (d ? d + ' يوم ' : '') + (hh ? hh + ' ساعة ' : '') + mm + ' دقيقة';
  };
  $('#view').innerHTML = `
  <div class="grid g3" style="margin-bottom:1rem">
    <div class="stat"><div class="k">المواقع</div><div class="v">${s.counts.sites}</div><div class="s">منشورة على هذا الجهاز</div></div>
    <div class="stat"><div class="k">الأنفاق النشطة</div><div class="v">${s.counts.tunnels}</div><div class="s">منافذ إلى الإنترنت</div></div>
    <div class="stat"><div class="k">الذاكرة</div><div class="v">${s.memory.percent}%</div>
      <div class="s">${h(s.memory.usedText)} من ${h(s.memory.totalText)}</div>
      <div class="bar"><i style="width:${s.memory.percent}%"></i></div></div>
    <div class="stat"><div class="k">التخزين</div><div class="v">${s.disk ? s.disk.percent : '?'}%</div>
      <div class="s">${s.disk ? h(s.disk.availText) + ' متاحة' : ''}</div>
      <div class="bar"><i style="width:${s.disk ? s.disk.percent : 0}%"></i></div></div>
    <div class="stat"><div class="k">البطارية</div><div class="v">${bat ? bat.percentage + '%' : '—'}</div>
      <div class="s">${bat ? h(bat.status === 'CHARGING' ? 'قيد الشحن' : bat.status) : 'termux-api غير مثبّت'}</div></div>
    <div class="stat"><div class="k">مدة التشغيل</div><div class="v" style="font-size:1rem">${h(up(s.uptime))}</div>
      <div class="s">اللوحة: ${h(up(s.panelUptime))}</div></div>
  </div>

  <div class="card">
    <h3>حالة الخدمات</h3>
    <p>${svc}</p>
    <div class="row" style="margin-top:.6rem">
      ${['nginx', 'php-fpm', 'mariadb', 'crond'].map(n =>
        `<button class="btn ghost sm" data-svc="${n}">↻ ${n}</button>`).join('')}
    </div>
  </div>

  <div class="card">
    <h3>عناوين الوصول</h3>
    <table><tbody>
      <tr><td>الشبكة المحلية</td><td><code class="k">http://${h(s.lanIp)}:${s.ports.http}</code></td></tr>
      <tr><td>لوحة التحكم</td><td><code class="k">http://${h(s.lanIp)}:${s.ports.panel}</code></td></tr>
      <tr><td>HTTPS محلي</td><td><code class="k">https://${h(s.lanIp)}:${s.ports.https}</code></td></tr>
    </tbody></table>
    <div class="hint">المنافذ أقل من 1024 تحتاج صلاحية روت، لذلك يعمل الخادم على 8080/8443.
    لنشر الموقع على المنفذ 80/443 القياسي استخدم <b>نفق Cloudflare</b> أو أعد توجيه المنافذ من الراوتر.</div>
  </div>

  <div class="card">
    <h3>إصدارات البرمجيات</h3>
    <table><tbody>
      ${Object.entries(s.versions).map(([k, v]) =>
        `<tr><td>${h(k)}</td><td><code class="k">${h(v || 'غير مثبّت')}</code></td></tr>`).join('')}
      <tr><td>المعمارية</td><td><code class="k">${h(s.arch)} · ${s.cpus} نواة</code></td></tr>
    </tbody></table>
  </div>`;

  $('#view').querySelectorAll('[data-svc]').forEach(b => {
    b.onclick = async () => {
      b.disabled = true;
      try { await api('/api/system/service', { method: 'POST', body: { name: b.dataset.svc, action: 'restart' } }); okToast('أُعيد تشغيل ' + b.dataset.svc); go('dash'); }
      catch (e) { errToast(e); b.disabled = false; }
    };
  });
};

/* ═══════════ معالج النشر الآلي ═══════════ */
RENDER.wizard = async () => {
  $('#view').innerHTML = `
  <div class="card">
    <h3>🪄 نشر موقع كامل بخطوة واحدة</h3>
    <p class="muted small">يقوم المعالج بكل شيء تلقائياً: إنشاء الموقع، قاعدة البيانات، فتح المنفذ للإنترنت، ربط الدومين، وإصدار شهادة SSL.</p>

    <label>اسم الدومين</label>
    <input id="wDomain" placeholder="example.com" dir="ltr">

    <div class="row">
      <div><label>نوع الموقع</label>
        <select id="wType">
          <option value="php">PHP (ووردبريس، لارافيل، سكربتات)</option>
          <option value="static">ملفات ثابتة (HTML / React مبني)</option>
          <option value="proxy">تطبيق Node يعمل على منفذ</option>
        </select></div>
      <div><label>منفذ التطبيق (لنوع Node فقط)</label><input id="wPort" type="number" value="3000" dir="ltr"></div>
    </div>

    <div class="check"><input type="checkbox" id="wDb" checked><label for="wDb" style="margin:0">إنشاء قاعدة بيانات ومستخدم خاص بالموقع</label></div>

    <label>طريقة النشر على الإنترنت</label>
    <select id="wExpose">
      <option value="quick">نفق Cloudflare مؤقت — رابط فوري للتجربة، بدون حساب</option>
      <option value="named">نفق Cloudflare دائم — يحتاج Tunnel Token (الأفضل للدومين الحقيقي)</option>
      <option value="dns">ربط مباشر بـ IP العام — يتطلب توجيه منافذ من الراوتر</option>
      <option value="none">لا شيء الآن — شبكة محلية فقط</option>
    </select>
    <div id="wExposeExtra"></div>

    <label>شهادة SSL</label>
    <select id="wSsl">
      <option value="none">بدون (النفق يوفّر HTTPS تلقائياً)</option>
      <option value="letsencrypt">Let's Encrypt عبر Namecheap DNS</option>
      <option value="self">شهادة موقّعة ذاتياً (اختبار محلي)</option>
    </select>

    <button class="btn" id="wRun" style="margin-top:1rem;width:100%">🚀 ابدأ النشر الآلي</button>
  </div>
  <div class="card" id="wLogCard" style="display:none">
    <h3>سجل التنفيذ</h3>
    <pre class="out" id="wLog"></pre>
  </div>`;

  const extra = $('#wExposeExtra');
  const paint = () => {
    const v = $('#wExpose').value;
    if (v === 'named') {
      extra.innerHTML = `<label>Tunnel Token من Cloudflare Zero Trust</label>
        <input id="wToken" placeholder="eyJhIjoi..." dir="ltr">
        <div class="hint">من <b>one.dash.cloudflare.com</b> ← Networks ← Tunnels ← Create a tunnel ← اختر Cloudflared ← انسخ الرمز.
        ثم أضف Public Hostname يربط دومينك بـ <code class="k">http://localhost:8080</code>.</div>`;
    } else if (v === 'dns') {
      extra.innerHTML = `<div class="hint warn">سيُضاف سجل A يشير إلى عنوان IP العام لهذا الجهاز عبر Namecheap API.
        يعمل فقط إذا كان لديك IP عام ثابت وأمكنك توجيه المنفذ 80 إلى 8080 من إعدادات الراوتر.
        معظم شبكات الجيل الرابع/الخامس خلف CGNAT ولن تعمل معها هذه الطريقة.</div>`;
    } else if (v === 'quick') {
      extra.innerHTML = `<div class="hint">سيصدر رابط <code class="k">*.trycloudflare.com</code> فوراً. الرابط مؤقت ويتغير عند إعادة التشغيل، مناسب للتجربة فقط.</div>`;
    } else extra.innerHTML = '';
  };
  $('#wExpose').onchange = paint; paint();

  $('#wRun').onclick = async () => {
    const btn = $('#wRun');
    const logCard = $('#wLogCard'), logEl = $('#wLog');
    logCard.style.display = 'block'; logEl.textContent = '';
    const log = (m) => { logEl.textContent += m + '\n'; logEl.scrollTop = logEl.scrollHeight; };

    const domain = $('#wDomain').value.trim().toLowerCase();
    if (!domain) return errToast('أدخل اسم الدومين');
    btn.disabled = true; btn.innerHTML = '<span class="spin"></span> جارٍ التنفيذ…';

    try {
      log('▶ إنشاء الموقع ' + domain + ' …');
      const created = await api('/api/sites', { method: 'POST', body: {
        domain, type: $('#wType').value, proxy_port: $('#wPort').value, create_db: $('#wDb').checked,
      } });
      log('✔ الموقع أُنشئ في: ' + created.site.root);
      if (created.dbCredentials) {
        const d = created.dbCredentials;
        log('✔ قاعدة البيانات: ' + d.database);
        log('   المستخدم: ' + d.user);
        log('   كلمة المرور: ' + d.password);
        log('   المضيف: 127.0.0.1  المنفذ: 3306');
        log('   ⚠ انسخ هذه البيانات الآن، لن تُعرض مرة أخرى.');
      } else if (created.dbWarning) {
        log('⚠ تعذّر إنشاء قاعدة البيانات: ' + created.dbWarning);
      }

      const expose = $('#wExpose').value;
      if (expose === 'quick' || expose === 'named') {
        log('▶ تشغيل نفق Cloudflare …');
        const t = await api('/api/tunnels', { method: 'POST', body: {
          type: expose, site: created.site.id, hostname: expose === 'named' ? domain : null,
          token: expose === 'named' ? ($('#wToken') || {}).value : undefined,
        } });
        log('✔ النفق يعمل' + (t.tunnel.url ? ' → ' + t.tunnel.url : ''));
      } else if (expose === 'dns') {
        log('▶ جلب عنوان IP العام …');
        const { ip } = await api('/api/system/publicip');
        if (!ip) throw new Error('تعذّر تحديد عنوان IP العام');
        log('   IP = ' + ip);
        log('▶ تحديث سجلات DNS في Namecheap …');
        const r = await api('/api/dns/point', { method: 'POST', body: { domain, target: ip, www: true } });
        log('✔ أُضيفت ' + r.applied.length + ' سجلات (@ و www)');
        log('   ملاحظة: انتشار DNS يستغرق من دقائق إلى ساعات.');
      } else {
        log('• تم تخطي النشر الخارجي.');
      }

      const sslMode = $('#wSsl').value;
      if (sslMode !== 'none') {
        log('▶ إصدار شهادة SSL (' + sslMode + ') …');
        await api('/api/ssl/issue', { method: 'POST', body: { domain, method: sslMode === 'self' ? 'self-signed' : 'letsencrypt' } });
        log('✔ الشهادة مثبّتة وأُعيد تحميل nginx');
      }

      log('');
      log('🎉 اكتمل النشر.');
      okToast('اكتمل النشر بنجاح');
    } catch (e) {
      log('✖ توقف: ' + e.message);
      errToast(e);
    } finally {
      btn.disabled = false; btn.textContent = '🚀 ابدأ النشر الآلي';
    }
  };
};

/* ═══════════ المواقع ═══════════ */
RENDER.sites = async () => {
  const { sites } = await api('/api/sites');
  const fmt = (n) => n > 1048576 ? (n / 1048576).toFixed(1) + ' م.ب' : (n / 1024).toFixed(0) + ' ك.ب';
  $('#view').innerHTML = `
  <div class="card">
    <h3>إضافة موقع</h3>
    <div class="row">
      <div style="flex:2 1 240px"><label>الدومين</label><input id="sDomain" placeholder="site.example.com" dir="ltr"></div>
      <div><label>النوع</label><select id="sType">
        <option value="php">PHP</option><option value="static">ملفات ثابتة</option><option value="proxy">تمرير إلى منفذ</option>
      </select></div>
      <div><label>منفذ التمرير</label><input id="sPort" type="number" value="3000" dir="ltr"></div>
      <div style="flex:0 0 auto"><button class="btn" id="sAdd">إضافة</button></div>
    </div>
    <div class="check"><input type="checkbox" id="sDb"><label for="sDb" style="margin:0">أنشئ قاعدة بيانات مع الموقع</label></div>
  </div>

  <div class="card">
    <h3>المواقع المستضافة (${sites.length})</h3>
    ${sites.length ? `<div class="tw"><table>
      <thead><tr><th>الدومين</th><th>النوع</th><th>الحجم</th><th>SSL</th><th>الحالة</th><th></th></tr></thead>
      <tbody>${sites.map(s => `<tr>
        <td><b dir="ltr">${h(s.domain)}</b><div class="small muted" dir="ltr">${h(s.root)}</div></td>
        <td><span class="pill">${h(s.type)}</span>${s.type === 'proxy' ? '<div class="small muted">:' + s.proxy_port + '</div>' : ''}</td>
        <td class="small">${fmt(s.size || 0)}</td>
        <td>${s.ssl ? '<span class="pill ok">مفعّل</span>' : '<span class="pill">لا</span>'}</td>
        <td>${s.enabled === false ? '<span class="pill bad">معطّل</span>' : '<span class="pill ok">يعمل</span>'}</td>
        <td style="white-space:nowrap">
          <button class="btn ghost sm" data-open="${h(s.id)}">فتح</button>
          <button class="btn ghost sm" data-toggle="${h(s.id)}">${s.enabled === false ? 'تفعيل' : 'تعطيل'}</button>
          <button class="btn bad sm" data-del="${h(s.id)}">حذف</button>
        </td></tr>`).join('')}</tbody></table></div>`
      : '<p class="muted">لا توجد مواقع بعد. أضف أول موقع من الأعلى أو استخدم <b>النشر الآلي</b>.</p>'}
  </div>`;

  $('#sAdd').onclick = async () => {
    const btn = $('#sAdd'); btn.disabled = true;
    try {
      const r = await api('/api/sites', { method: 'POST', body: {
        domain: $('#sDomain').value.trim(), type: $('#sType').value,
        proxy_port: $('#sPort').value, create_db: $('#sDb').checked,
      } });
      if (r.dbCredentials) {
        const d = r.dbCredentials;
        modal('بيانات قاعدة البيانات', `<div class="hint warn">انسخها الآن — لن تُعرض مرة أخرى.</div>
          <table><tbody>
          <tr><td>القاعدة</td><td><code class="k">${h(d.database)}</code></td></tr>
          <tr><td>المستخدم</td><td><code class="k">${h(d.user)}</code></td></tr>
          <tr><td>كلمة المرور</td><td><code class="k">${h(d.password)}</code></td></tr>
          <tr><td>المضيف</td><td><code class="k">127.0.0.1:3306</code></td></tr>
          </tbody></table>`, [{ label: 'تم', kind: 'ok' }]);
      } else okToast('أُضيف الموقع');
      if (r.dbWarning) errToast('الموقع أُنشئ لكن قاعدة البيانات فشلت: ' + r.dbWarning);
      RENDER.sites();
    } catch (e) { errToast(e); } finally { btn.disabled = false; }
  };

  $('#view').querySelectorAll('[data-del]').forEach(b => b.onclick = () =>
    confirmBox('حذف الموقع ' + b.dataset.del + '؟ سيُحذف مجلد الملفات أيضاً.', async () => {
      await api('/api/sites/' + encodeURIComponent(b.dataset.del) + '?files=1', { method: 'DELETE' });
      okToast('حُذف الموقع'); RENDER.sites();
    }));

  $('#view').querySelectorAll('[data-toggle]').forEach(b => b.onclick = async () => {
    const s = sites.find(x => x.id === b.dataset.toggle);
    try { await api('/api/sites/' + encodeURIComponent(s.id), { method: 'POST', body: { enabled: s.enabled === false } }); RENDER.sites(); }
    catch (e) { errToast(e); }
  });

  $('#view').querySelectorAll('[data-open]').forEach(b => b.onclick = () => {
    const s = sites.find(x => x.id === b.dataset.open);
    modal('إعدادات ' + s.domain, `
      <label>نطاقات إضافية (مفصولة بمسافة)</label>
      <input id="mAliases" dir="ltr" value="${h((s.aliases || []).join(' '))}">
      <label>حد حجم الرفع (ميغابايت)</label><input id="mBody" type="number" dir="ltr" value="${s.max_body || 256}">
      <div class="check"><input type="checkbox" id="mSpa" ${s.spa ? 'checked' : ''}><label for="mSpa" style="margin:0">وضع SPA (توجيه كل المسارات إلى index.html)</label></div>
      <div class="check"><input type="checkbox" id="mHttps" ${s.force_https ? 'checked' : ''}><label for="mHttps" style="margin:0">إجبار HTTPS عند توفر شهادة</label></div>
      <div class="hint">مجلد الملفات: <code class="k">${h(s.root)}</code></div>`,
      [{ label: 'إلغاء' }, { label: 'حفظ', kind: 'ok', run: async (box) => {
        await api('/api/sites/' + encodeURIComponent(s.id), { method: 'POST', body: {
          aliases: $('#mAliases', box).value, max_body: $('#mBody', box).value,
          spa: $('#mSpa', box).checked, force_https: $('#mHttps', box).checked,
        } });
        okToast('حُفظ'); RENDER.sites();
      } }]);
  });
};

/* ═══════════ الدومينات و DNS ═══════════ */
RENDER.domains = async () => {
  const st = await api('/api/dns/status');
  const { sites } = await api('/api/sites');
  if (!st.configured) {
    $('#view').innerHTML = `
    <div class="card">
      <h3>🔗 ربط دومين Namecheap</h3>
      <div class="hint warn">لم تُضبط بيانات Namecheap API بعد.</div>
      <ol class="steps">
        <li>ادخل إلى namecheap.com ← Profile ← Tools ← <b>Namecheap API Access</b> وفعّل الواجهة.</li>
        <li>أضف عنوان IP الحالي لهذا الجهاز في <b>Whitelisted IPs</b>.</li>
        <li>انسخ <b>API Key</b> واسم المستخدم.</li>
        <li>أدخلها في صفحة <b>الإعدادات</b> ثم ارجع إلى هنا.</li>
      </ol>
      <div class="hint">لا تملك وصولاً لواجهة API؟ يمكنك بدلاً منها استخدام <b>DNS الديناميكي</b> أدناه، وهو لا يحتاج سوى كلمة مرور DDNS من صفحة Advanced DNS في حسابك.</div>
      <button class="btn" id="goSettings">فتح الإعدادات</button>
    </div>
    ${ddnsCard()}`;
    $('#goSettings').onclick = () => go('settings');
    wireDdns();
    return;
  }

  $('#view').innerHTML = `
  <div class="card">
    <h3>ربط سريع للدومين</h3>
    <div class="row">
      <div style="flex:2 1 220px"><label>الدومين</label><input id="dDomain" placeholder="example.com" dir="ltr"></div>
      <div style="flex:2 1 220px"><label>الوجهة (IP أو اسم مضيف)</label><input id="dTarget" placeholder="1.2.3.4" dir="ltr"></div>
      <div style="flex:0 0 auto"><button class="btn ghost" id="dMyIp">استخدم IP جهازي</button></div>
      <div style="flex:0 0 auto"><button class="btn" id="dPoint">ربط</button></div>
    </div>
    <div class="check"><input type="checkbox" id="dWww" checked><label for="dWww" style="margin:0">أضف www أيضاً</label></div>
    <div class="hint">عنوان IP الخارجي المسجّل حالياً: <code class="k">${h(st.ip || 'غير معروف')}</code>.
    إن تغيّر عنوانك فحدّثه من الإعدادات، لأن Namecheap ترفض الطلبات من عناوين غير مُدرجة.</div>
  </div>

  <div class="card">
    <h3>سجلات DNS</h3>
    <div class="row">
      <div style="flex:2 1 220px"><label>اعرض سجلات دومين</label><input id="rDomain" placeholder="example.com" dir="ltr"></div>
      <div style="flex:0 0 auto"><button class="btn ghost" id="rLoad">تحميل</button></div>
      <div style="flex:0 0 auto"><button class="btn ghost" id="rDomains">دوميناتي</button></div>
    </div>
    <div id="recBox"></div>
  </div>

  ${ddnsCard()}

  <div class="card">
    <h3>خوادم الأسماء</h3>
    <div class="row">
      <div style="flex:2 1 220px"><label>الدومين</label><input id="nsDomain" placeholder="example.com" dir="ltr"></div>
      <div style="flex:3 1 260px"><label>خوادم مخصّصة (مفصولة بفواصل)</label><input id="nsList" placeholder="ana.ns.cloudflare.com,bob.ns.cloudflare.com" dir="ltr"></div>
      <div style="flex:0 0 auto"><button class="btn" id="nsSet">تعيين</button></div>
      <div style="flex:0 0 auto"><button class="btn ghost" id="nsReset">إعادة لـ Namecheap</button></div>
    </div>
    <div class="hint">لاستخدام نفق Cloudflare دائم مع دومينك، يجب نقل خوادم الأسماء إلى Cloudflare أولاً.</div>
  </div>`;

  $('#dMyIp').onclick = async () => {
    try { const { ip } = await api('/api/system/publicip'); $('#dTarget').value = ip || ''; okToast('IP = ' + ip); }
    catch (e) { errToast(e); }
  };
  $('#dPoint').onclick = async () => {
    const b = $('#dPoint'); b.disabled = true;
    try {
      const r = await api('/api/dns/point', { method: 'POST', body: {
        domain: $('#dDomain').value.trim(), target: $('#dTarget').value.trim(), www: $('#dWww').checked } });
      okToast('حُدّثت ' + r.applied.length + ' سجلات');
    } catch (e) { errToast(e); } finally { b.disabled = false; }
  };
  $('#rDomains').onclick = async () => {
    try {
      const { domains } = await api('/api/dns/domains');
      modal('دوميناتك في Namecheap', '<div class="tw"><table><thead><tr><th>الدومين</th><th>ينتهي</th><th>تجديد تلقائي</th></tr></thead><tbody>' +
        domains.map(d => `<tr><td dir="ltr">${h(d.name)}</td><td class="small">${h(d.expires)}</td><td>${d.autoRenew ? '✔' : '—'}</td></tr>`).join('') +
        '</tbody></table></div>', [{ label: 'إغلاق' }]);
    } catch (e) { errToast(e); }
  };
  $('#rLoad').onclick = async () => {
    const dom = $('#rDomain').value.trim();
    const box = $('#recBox'); box.innerHTML = '<span class="spin"></span>';
    try {
      const { hosts } = await api('/api/dns/hosts?domain=' + encodeURIComponent(dom));
      box.innerHTML = `<div class="tw"><table><thead><tr><th>الاسم</th><th>النوع</th><th>القيمة</th><th>TTL</th><th></th></tr></thead>
        <tbody>${hosts.map(r => `<tr>
          <td dir="ltr">${h(r.name)}</td><td><span class="pill">${h(r.type)}</span></td>
          <td dir="ltr" class="small" style="word-break:break-all">${h(r.address)}</td><td class="small">${h(r.ttl)}</td>
          <td><button class="btn bad sm" data-rm="${h(r.name)}|${h(r.type)}">حذف</button></td></tr>`).join('')}</tbody></table></div>
        <div class="row" style="margin-top:.7rem">
          <div><label>اسم</label><input id="nName" value="@" dir="ltr"></div>
          <div><label>نوع</label><select id="nType">${['A','AAAA','CNAME','ALIAS','TXT','MX','NS','URL301'].map(t => `<option>${t}</option>`).join('')}</select></div>
          <div style="flex:2 1 200px"><label>القيمة</label><input id="nAddr" dir="ltr"></div>
          <div><label>TTL</label><input id="nTtl" type="number" value="1799" dir="ltr"></div>
          <div style="flex:0 0 auto"><button class="btn" id="nAdd">إضافة / تحديث</button></div>
        </div>`;
      box.querySelectorAll('[data-rm]').forEach(b => b.onclick = () => {
        const [name, type] = b.dataset.rm.split('|');
        confirmBox('حذف السجل ' + name + ' ' + type + '؟', async () => {
          await api('/api/dns/hosts', { method: 'POST', body: { domain: dom, upserts: [], removals: [{ name, type }] } });
          okToast('حُذف'); $('#rLoad').click();
        });
      });
      $('#nAdd', box).onclick = async () => {
        try {
          await api('/api/dns/hosts', { method: 'POST', body: { domain: dom, upserts: [{
            name: $('#nName', box).value.trim(), type: $('#nType', box).value,
            address: $('#nAddr', box).value.trim(), ttl: $('#nTtl', box).value }] } });
          okToast('حُفظ السجل'); $('#rLoad').click();
        } catch (e) { errToast(e); }
      };
    } catch (e) { box.innerHTML = '<pre class="out">' + h(e.message) + '</pre>'; }
  };
  $('#nsSet').onclick = async () => {
    try {
      await api('/api/dns/nameservers', { method: 'POST', body: {
        domain: $('#nsDomain').value.trim(), nameservers: $('#nsList').value.split(',').map(s => s.trim()).filter(Boolean) } });
      okToast('عُيّنت خوادم الأسماء (قد تستغرق ساعات للانتشار)');
    } catch (e) { errToast(e); }
  };
  $('#nsReset').onclick = () => confirmBox('إعادة الدومين لخوادم Namecheap الافتراضية؟', async () => {
    await api('/api/dns/nameservers', { method: 'POST', body: { domain: $('#nsDomain').value.trim(), reset: true } });
    okToast('أُعيدت للافتراضي');
  });

  wireDdns();
};

function ddnsCard() {
  return `<div class="card">
    <h3>DNS ديناميكي (يتتبع تغيّر IP)</h3>
    <p class="muted small">لا يحتاج API key. فعّل Dynamic DNS من صفحة Advanced DNS في Namecheap وانسخ كلمة المرور.</p>
    <div class="row">
      <div><label>المضيف</label><input id="ddHost" value="@" dir="ltr"></div>
      <div style="flex:2 1 200px"><label>الدومين</label><input id="ddDomain" placeholder="example.com" dir="ltr"></div>
      <div style="flex:2 1 200px"><label>كلمة مرور DDNS</label><input id="ddPass" type="password" dir="ltr"></div>
      <div style="flex:0 0 auto"><button class="btn" id="ddAdd">حفظ وتحديث</button></div>
    </div>
    <div id="ddList" style="margin-top:.6rem"></div>
    <button class="btn ghost sm" id="ddSync" style="margin-top:.5rem">↻ مزامنة كل السجلات الآن</button>
    <div class="hint">أضف مهمة مجدولة كل 15 دقيقة لتحديث تلقائي:
      <code class="k">*/15 * * * * curl -s -X POST -H "X-ShamHost: 1" http://127.0.0.1:8088/api/dns/ddns/sync</code>
      (يتطلب جلسة؛ الأبسط استخدام هذا الزر أو نفق Cloudflare الذي يغنيك عن IP ثابت أصلاً.)</div>
  </div>`;
}

async function wireDdns() {
  const refresh = async () => {
    try {
      const { entries } = await api('/api/dns/ddns');
      $('#ddList').innerHTML = entries.length ? `<div class="tw"><table><thead><tr><th>المضيف</th><th>الدومين</th><th>آخر IP</th><th></th></tr></thead>
        <tbody>${entries.map(e => `<tr><td dir="ltr">${h(e.host)}</td><td dir="ltr">${h(e.domain)}</td>
        <td dir="ltr" class="small">${h(e.last_ip || '—')}</td>
        <td><button class="btn bad sm" data-ddel="${h(e.host + '.' + e.domain)}">حذف</button></td></tr>`).join('')}</tbody></table></div>`
        : '<p class="muted small">لا توجد سجلات ديناميكية.</p>';
      $('#ddList').querySelectorAll('[data-ddel]').forEach(b => b.onclick = async () => {
        await api('/api/dns/ddns/' + encodeURIComponent(b.dataset.ddel), { method: 'DELETE' });
        refresh();
      });
    } catch (_) {}
  };
  if (!$('#ddAdd')) return;
  $('#ddAdd').onclick = async () => {
    const b = $('#ddAdd'); b.disabled = true;
    try {
      const r = await api('/api/dns/ddns', { method: 'POST', body: {
        host: $('#ddHost').value.trim() || '@', domain: $('#ddDomain').value.trim(), password: $('#ddPass').value } });
      okToast('حُدّث السجل إلى ' + r.ip); $('#ddPass').value = ''; refresh();
    } catch (e) { errToast(e); } finally { b.disabled = false; }
  };
  $('#ddSync').onclick = async () => {
    try { const r = await api('/api/dns/ddns/sync', { method: 'POST', body: {} }); okToast('تمت المزامنة على ' + r.ip); refresh(); }
    catch (e) { errToast(e); }
  };
  refresh();
}

/* ═══════════ الأنفاق ═══════════ */
RENDER.tunnels = async () => {
  const { tunnels, installed } = await api('/api/tunnels');
  const { sites } = await api('/api/sites');
  $('#view').innerHTML = `
  ${installed ? '' : '<div class="card"><div class="hint warn">cloudflared غير مثبّت. نفّذ في Termux: <code class="k">pkg install cloudflared</code></div></div>'}
  <div class="card">
    <h3>🚇 نفق جديد</h3>
    <p class="muted small">النفق يجعل موقعك متاحاً على الإنترنت بـ HTTPS دون فتح منافذ في الراوتر ودون IP ثابت — الحل الأنسب للجوال.</p>
    <div class="row">
      <div><label>النوع</label><select id="tType">
        <option value="quick">مؤقت (trycloudflare.com)</option>
        <option value="named">دائم (Tunnel Token)</option>
      </select></div>
      <div><label>المنفذ المحلي</label><input id="tPort" type="number" value="8080" dir="ltr"></div>
      <div style="flex:2 1 220px"><label>الموقع المرتبط (اختياري)</label>
        <select id="tSite"><option value="">—</option>${sites.map(s => `<option value="${h(s.id)}">${h(s.domain)}</option>`).join('')}</select></div>
      <div style="flex:0 0 auto"><button class="btn" id="tAdd">تشغيل</button></div>
    </div>
    <div id="tExtra"></div>
  </div>

  <div class="card">
    <h3>الأنفاق (${tunnels.length})</h3>
    ${tunnels.length ? `<div class="tw"><table><thead><tr><th>المعرّف</th><th>النوع</th><th>الرابط</th><th>الحالة</th><th></th></tr></thead>
      <tbody>${tunnels.map(t => `<tr>
        <td dir="ltr" class="small">${h(t.id)}</td><td><span class="pill">${h(t.type)}</span></td>
        <td dir="ltr" class="small" style="word-break:break-all">${t.url ? `<a href="${h(t.url)}" target="_blank" rel="noopener">${h(t.url)}</a>` : '—'}</td>
        <td>${t.running ? '<span class="pill ok">يعمل</span>' : '<span class="pill bad">متوقف</span>'}</td>
        <td style="white-space:nowrap">
          <button class="btn ghost sm" data-tlog="${h(t.id)}">سجل</button>
          <button class="btn bad sm" data-tdel="${h(t.id)}">إيقاف</button></td></tr>`).join('')}</tbody></table></div>`
      : '<p class="muted">لا توجد أنفاق نشطة.</p>'}
  </div>`;

  const paint = () => {
    $('#tExtra').innerHTML = $('#tType').value === 'named'
      ? `<label>Tunnel Token</label><input id="tToken" dir="ltr" placeholder="eyJhIjoi...">
         <label>اسم المضيف (اختياري، للعرض)</label><input id="tHost" dir="ltr" placeholder="example.com">`
      : '';
  };
  $('#tType').onchange = paint; paint();

  $('#tAdd').onclick = async () => {
    const b = $('#tAdd'); b.disabled = true; b.innerHTML = '<span class="spin"></span>';
    try {
      const r = await api('/api/tunnels', { method: 'POST', body: {
        type: $('#tType').value, port: $('#tPort').value, site: $('#tSite').value || null,
        token: ($('#tToken') || {}).value, hostname: ($('#tHost') || {}).value } });
      okToast('النفق يعمل' + (r.tunnel.url ? ': ' + r.tunnel.url : ''));
      RENDER.tunnels();
    } catch (e) { errToast(e); b.disabled = false; b.textContent = 'تشغيل'; }
  };
  $('#view').querySelectorAll('[data-tdel]').forEach(b => b.onclick = async () => {
    try { await api('/api/tunnels/' + b.dataset.tdel, { method: 'DELETE' }); okToast('أُوقف النفق'); RENDER.tunnels(); }
    catch (e) { errToast(e); }
  });
  $('#view').querySelectorAll('[data-tlog]').forEach(b => b.onclick = async () => {
    const r = await api('/api/tunnels/' + b.dataset.tlog + '/logs?lines=150');
    modal('سجل النفق', '<pre class="out">' + h(r.content || '(فارغ)') + '</pre>', [{ label: 'إغلاق' }]);
  });
};

/* ═══════════ SSL ═══════════ */
RENDER.ssl = async () => {
  const { certificates, acme } = await api('/api/ssl');
  const { sites } = await api('/api/sites');
  $('#view').innerHTML = `
  <div class="card">
    <h3>🔒 إصدار شهادة</h3>
    ${acme ? '' : '<div class="hint warn">acme.sh غير مثبّت — سيُثبَّت تلقائياً عند أول إصدار (يحتاج إنترنت).</div>'}
    <div class="row">
      <div style="flex:2 1 220px"><label>الدومين</label>
        <input id="cDomain" list="cDomains" placeholder="example.com" dir="ltr">
        <datalist id="cDomains">${sites.map(s => `<option value="${h(s.domain)}">`).join('')}</datalist></div>
      <div><label>الطريقة</label><select id="cMethod">
        <option value="letsencrypt">Let's Encrypt (تحدي DNS عبر Namecheap)</option>
        <option value="self-signed">موقّعة ذاتياً (اختبار)</option>
      </select></div>
      <div style="flex:0 0 auto"><button class="btn" id="cIssue">إصدار</button></div>
    </div>
    <div class="check"><input type="checkbox" id="cWild"><label for="cWild" style="margin:0">شهادة شاملة للنطاقات الفرعية (*.domain)</label></div>
    <div class="hint">تحدي DNS لا يحتاج المنفذ 80، لذلك يعمل على الجوال بدون روت.
    يتطلب ضبط مفتاح Namecheap API في الإعدادات. عند استخدام نفق Cloudflare يكون HTTPS متوفراً أصلاً ولا تحتاج هذه الخطوة.</div>
  </div>

  <div class="card">
    <h3>الشهادات المثبّتة (${certificates.length})</h3>
    ${certificates.length ? `<div class="tw"><table><thead><tr><th>الدومين</th><th>تنتهي</th><th>ثُبّتت</th><th></th></tr></thead>
      <tbody>${certificates.map(c => `<tr><td dir="ltr">${h(c.domain)}</td>
        <td class="small" dir="ltr">${h(c.expires || '—')}</td>
        <td class="small" dir="ltr">${h((c.installed_at || '').slice(0, 10))}</td>
        <td><button class="btn bad sm" data-cdel="${h(c.domain)}">حذف</button></td></tr>`).join('')}</tbody></table></div>
      <button class="btn ghost sm" id="cRenew" style="margin-top:.6rem">↻ تجديد كل الشهادات</button>`
      : '<p class="muted">لا توجد شهادات.</p>'}
  </div>`;

  $('#cIssue').onclick = async () => {
    const b = $('#cIssue'); b.disabled = true; b.innerHTML = '<span class="spin"></span>';
    try {
      await api('/api/ssl/issue', { method: 'POST', body: {
        domain: $('#cDomain').value.trim(), method: $('#cMethod').value, wildcard: $('#cWild').checked } });
      okToast('صدرت الشهادة وثُبّتت'); RENDER.ssl();
    } catch (e) {
      modal('فشل إصدار الشهادة', '<pre class="out">' + h(e.message) + '</pre>', [{ label: 'إغلاق' }]);
      b.disabled = false; b.textContent = 'إصدار';
    }
  };
  $('#view').querySelectorAll('[data-cdel]').forEach(b => b.onclick = () =>
    confirmBox('حذف شهادة ' + b.dataset.cdel + '؟', async () => {
      await api('/api/ssl/' + encodeURIComponent(b.dataset.cdel), { method: 'DELETE' });
      okToast('حُذفت'); RENDER.ssl();
    }));
  if ($('#cRenew')) $('#cRenew').onclick = async () => {
    const b = $('#cRenew'); b.disabled = true;
    try { const r = await api('/api/ssl/renew', { method: 'POST', body: {} }); modal('نتيجة التجديد', '<pre class="out">' + h(r.log || 'تم') + '</pre>', [{ label: 'إغلاق' }]); }
    catch (e) { errToast(e); } finally { b.disabled = false; }
  };
};

/* ═══════════ قواعد البيانات ═══════════ */
RENDER.db = async () => {
  let status, dbs = [], users = [];
  try { status = (await api('/api/db/status')).status; } catch (e) { status = { ok: false, error: e.message }; }
  if (status.ok) {
    try { dbs = (await api('/api/db/databases')).databases; } catch (_) {}
    try { users = (await api('/api/db/users')).users; } catch (_) {}
  }
  const fmt = (n) => n > 1048576 ? (n / 1048576).toFixed(1) + ' م.ب' : (n / 1024).toFixed(0) + ' ك.ب';
  $('#view').innerHTML = `
  <div class="card">
    <h3>حالة MariaDB</h3>
    ${status.ok
      ? `<span class="pill ok">متصل</span> <code class="k">${h(status.version)}</code>`
      : `<span class="pill bad">غير متصل</span><pre class="out">${h(status.error)}</pre>
         <div class="hint">شغّل الخدمة من لوحة القيادة، أو نفّذ <code class="k">shamhost service mariadb start</code>.</div>`}
  </div>

  <div class="card">
    <h3>قواعد البيانات (${dbs.length})</h3>
    <div class="row">
      <div style="flex:2 1 200px"><label>اسم قاعدة جديدة</label><input id="dbName" dir="ltr" placeholder="myapp_db"></div>
      <div style="flex:0 0 auto"><button class="btn" id="dbAdd">إنشاء</button></div>
      <div style="flex:0 0 auto"><button class="btn ghost" id="dbProv">إنشاء حزمة كاملة (قاعدة+مستخدم)</button></div>
    </div>
    ${dbs.length ? `<div class="tw" style="margin-top:.7rem"><table><thead><tr><th>الاسم</th><th>الجداول</th><th>الحجم</th><th></th></tr></thead>
      <tbody>${dbs.map(d => `<tr><td dir="ltr">${h(d.name)}</td><td>${d.tables}</td><td class="small">${fmt(d.bytes)}</td>
      <td><button class="btn bad sm" data-dbdel="${h(d.name)}">حذف</button></td></tr>`).join('')}</tbody></table></div>` : ''}
  </div>

  <div class="card">
    <h3>المستخدمون (${users.length})</h3>
    <div class="row">
      <div><label>اسم المستخدم</label><input id="uName" dir="ltr"></div>
      <div><label>كلمة المرور</label><input id="uPass" type="password" dir="ltr"></div>
      <div style="flex:0 0 auto"><button class="btn" id="uAdd">إنشاء</button></div>
    </div>
    <div class="row" style="margin-top:.4rem">
      <div><label>منح صلاحيات: المستخدم</label>
        <select id="gUser">${users.map(u => `<option>${h(u.user)}</option>`).join('')}</select></div>
      <div><label>على قاعدة</label>
        <select id="gDb">${dbs.map(d => `<option>${h(d.name)}</option>`).join('')}</select></div>
      <div style="flex:0 0 auto"><button class="btn ghost" id="gDo">منح كل الصلاحيات</button></div>
    </div>
    ${users.length ? `<div class="tw" style="margin-top:.7rem"><table><tbody>
      ${users.map(u => `<tr><td dir="ltr">${h(u.user)}</td><td class="small muted" dir="ltr">${h(u.host)}</td>
      <td style="text-align:end"><button class="btn bad sm" data-udel="${h(u.user)}">حذف</button></td></tr>`).join('')}
      </tbody></table></div>` : ''}
  </div>

  <div class="card">
    <h3>تنفيذ SQL</h3>
    <div class="row"><div><label>القاعدة</label>
      <select id="qDb"><option value="">—</option>${dbs.map(d => `<option>${h(d.name)}</option>`).join('')}</select></div></div>
    <label>الاستعلام</label>
    <textarea id="qSql" placeholder="SHOW TABLES;" style="min-height:100px"></textarea>
    <button class="btn" id="qRun" style="margin-top:.6rem">تنفيذ</button>
    <div id="qOut" style="margin-top:.7rem"></div>
  </div>`;

  $('#dbAdd').onclick = async () => {
    try { await api('/api/db/databases', { method: 'POST', body: { name: $('#dbName').value.trim() } }); okToast('أُنشئت'); RENDER.db(); }
    catch (e) { errToast(e); }
  };
  $('#dbProv').onclick = async () => {
    try {
      const r = await api('/api/db/provision', { method: 'POST', body: { base: $('#dbName').value.trim() || 'site' } });
      modal('بيانات الاتصال', `<div class="hint warn">انسخها الآن.</div><table><tbody>
        <tr><td>القاعدة</td><td><code class="k">${h(r.database)}</code></td></tr>
        <tr><td>المستخدم</td><td><code class="k">${h(r.user)}</code></td></tr>
        <tr><td>كلمة المرور</td><td><code class="k">${h(r.password)}</code></td></tr>
        <tr><td>المضيف</td><td><code class="k">127.0.0.1:3306</code></td></tr></tbody></table>`,
        [{ label: 'تم', kind: 'ok', run: async () => RENDER.db() }]);
    } catch (e) { errToast(e); }
  };
  $('#uAdd').onclick = async () => {
    try { await api('/api/db/users', { method: 'POST', body: { name: $('#uName').value.trim(), password: $('#uPass').value } }); okToast('أُنشئ المستخدم'); RENDER.db(); }
    catch (e) { errToast(e); }
  };
  $('#gDo').onclick = async () => {
    try { await api('/api/db/grant', { method: 'POST', body: { user: $('#gUser').value, database: $('#gDb').value } }); okToast('مُنحت الصلاحيات'); }
    catch (e) { errToast(e); }
  };
  $('#view').querySelectorAll('[data-dbdel]').forEach(b => b.onclick = () =>
    confirmBox('حذف قاعدة ' + b.dataset.dbdel + ' نهائياً؟ لا يمكن التراجع.', async () => {
      await api('/api/db/databases/' + encodeURIComponent(b.dataset.dbdel), { method: 'DELETE' }); okToast('حُذفت'); RENDER.db();
    }));
  $('#view').querySelectorAll('[data-udel]').forEach(b => b.onclick = () =>
    confirmBox('حذف المستخدم ' + b.dataset.udel + '؟', async () => {
      await api('/api/db/users/' + encodeURIComponent(b.dataset.udel), { method: 'DELETE' }); okToast('حُذف'); RENDER.db();
    }));
  $('#qRun').onclick = async () => {
    const out = $('#qOut'); out.innerHTML = '<span class="spin"></span>';
    try {
      const r = await api('/api/db/query', { method: 'POST', body: { sql: $('#qSql').value, database: $('#qDb').value || undefined } });
      out.innerHTML = r.columns.length
        ? `<div class="tw"><table><thead><tr>${r.columns.map(c => `<th>${h(c)}</th>`).join('')}</tr></thead>
           <tbody>${r.rows.map(row => `<tr>${row.map(v => `<td class="small" dir="ltr">${h(v)}</td>`).join('')}</tr>`).join('')}</tbody></table></div>
           <p class="small muted">${r.rows.length} صف</p>`
        : '<p class="muted small">نُفّذ بنجاح، بلا نتائج.</p>';
    } catch (e) { out.innerHTML = '<pre class="out">' + h(e.message) + '</pre>'; }
  };
};

/* ═══════════ مدير الملفات ═══════════ */
let fmState = { root: 'sites', path: '' };
RENDER.files = async () => {
  const { roots } = await api('/api/files/roots');
  const { listing } = await api('/api/files/list?root=' + encodeURIComponent(fmState.root) + '&path=' + encodeURIComponent(fmState.path));
  const crumbs = ['<button data-cd="">' + h(listing.rootLabel) + '</button>'];
  let acc = '';
  for (const seg of (listing.path ? listing.path.split('/') : [])) {
    acc = acc ? acc + '/' + seg : seg;
    crumbs.push('<span class="muted">/</span><button data-cd="' + h(acc) + '">' + h(seg) + '</button>');
  }
  $('#view').innerHTML = `
  <div class="card">
    <div class="row" style="margin-bottom:.5rem">
      <div><label>المجلد الجذر</label><select id="fRoot">
        ${roots.map(r => `<option value="${h(r.key)}" ${r.key === fmState.root ? 'selected' : ''}>${h(r.label)}</option>`).join('')}
      </select></div>
      <div style="flex:0 0 auto"><label>&nbsp;</label><button class="btn ghost" id="fMkdir">📁 مجلد جديد</button></div>
      <div style="flex:0 0 auto"><label>&nbsp;</label><button class="btn ghost" id="fNew">📄 ملف جديد</button></div>
      <div style="flex:0 0 auto"><label>&nbsp;</label><button class="btn" id="fUpBtn">⬆ رفع ملف</button>
        <input type="file" id="fUp" style="display:none"></div>
    </div>
    <div class="crumbs">${crumbs.join('')}</div>
    <div class="small muted" dir="ltr" style="margin-bottom:.5rem;word-break:break-all">${h(listing.abs)}</div>
    <div class="tw"><table><thead><tr><th>الاسم</th><th>الحجم</th><th>الصلاحيات</th><th>آخر تعديل</th><th></th></tr></thead><tbody>
      ${listing.parent !== null ? `<tr class="clickable" data-cd="${h(listing.parent)}"><td colspan="5">⬅ المجلد الأعلى</td></tr>` : ''}
      ${listing.entries.map(e => `<tr>
        <td>${e.dir ? `<button class="btn ghost sm" data-cd="${h(join(listing.path, e.name))}">📁 ${h(e.name)}</button>`
                     : `<span>${e.text ? '📝' : '📦'} ${h(e.name)}</span>`}</td>
        <td class="small">${h(e.sizeText)}</td>
        <td class="small" dir="ltr">${h(e.mode)}</td>
        <td class="small" dir="ltr">${h((e.mtime || '').slice(0, 16).replace('T', ' '))}</td>
        <td style="white-space:nowrap">
          ${!e.dir && e.text ? `<button class="btn ghost sm" data-edit="${h(join(listing.path, e.name))}">تحرير</button>` : ''}
          ${!e.dir ? `<a class="btn ghost sm" href="/api/files/download?root=${encodeURIComponent(fmState.root)}&path=${encodeURIComponent(join(listing.path, e.name))}">تنزيل</a>` : ''}
          ${/\.(zip|tar|tar\.gz|tgz)$/i.test(e.name) ? `<button class="btn ghost sm" data-x="${h(join(listing.path, e.name))}">فك</button>` : ''}
          <button class="btn ghost sm" data-ren="${h(join(listing.path, e.name))}">تسمية</button>
          <button class="btn bad sm" data-fdel="${h(join(listing.path, e.name))}">حذف</button>
        </td></tr>`).join('')}
    </tbody></table></div>
    ${listing.entries.length ? '' : '<p class="muted small">المجلد فارغ.</p>'}
  </div>`;

  const cd = (p) => { fmState.path = p; RENDER.files(); };
  $('#view').querySelectorAll('[data-cd]').forEach(el => el.onclick = () => cd(el.dataset.cd));
  $('#fRoot').onchange = () => { fmState.root = $('#fRoot').value; fmState.path = ''; RENDER.files(); };

  $('#fMkdir').onclick = () => modal('مجلد جديد', '<label>الاسم</label><input id="mkName" dir="ltr">',
    [{ label: 'إلغاء' }, { label: 'إنشاء', kind: 'ok', run: async (box) => {
      await api('/api/files/mkdir', { method: 'POST', body: { root: fmState.root, path: join(listing.path, $('#mkName', box).value.trim()) } });
      okToast('أُنشئ'); RENDER.files();
    } }]);

  $('#fNew').onclick = () => modal('ملف جديد', '<label>الاسم</label><input id="nfName" dir="ltr" value="index.html">',
    [{ label: 'إلغاء' }, { label: 'إنشاء', kind: 'ok', run: async (box) => {
      await api('/api/files/write', { method: 'POST', body: { root: fmState.root, path: join(listing.path, $('#nfName', box).value.trim()), content: '' } });
      okToast('أُنشئ'); RENDER.files();
    } }]);

  $('#fUpBtn').onclick = () => $('#fUp').click();
  $('#fUp').onchange = async () => {
    const f = $('#fUp').files[0];
    if (!f) return;
    const fd = new FormData();
    fd.append('root', fmState.root);
    fd.append('path', listing.path);
    fd.append('file', f);
    try {
      const res = await fetch('/api/files/upload', {
        method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-ShamHost': '1' },
      });
      const d = await res.json();
      if (!res.ok || d.ok === false) throw new Error(d.error || 'فشل الرفع');
      okToast('رُفع ' + d.file.name); RENDER.files();
    } catch (e) { errToast(e); }
  };

  $('#view').querySelectorAll('[data-edit]').forEach(b => b.onclick = async () => {
    try {
      const r = await api('/api/files/read?root=' + encodeURIComponent(fmState.root) + '&path=' + encodeURIComponent(b.dataset.edit));
      modal('تحرير: ' + b.dataset.edit,
        '<textarea id="edC" style="min-height:52vh">' + h(r.content) + '</textarea>',
        [{ label: 'إغلاق' }, { label: 'حفظ', kind: 'ok', run: async (box) => {
          await api('/api/files/write', { method: 'POST', body: { root: fmState.root, path: b.dataset.edit, content: $('#edC', box).value } });
          okToast('حُفظ الملف');
        } }]);
    } catch (e) { errToast(e); }
  });
  $('#view').querySelectorAll('[data-fdel]').forEach(b => b.onclick = () =>
    confirmBox('حذف ' + b.dataset.fdel + '؟', async () => {
      await api('/api/files?root=' + encodeURIComponent(fmState.root) + '&path=' + encodeURIComponent(b.dataset.fdel), { method: 'DELETE' });
      okToast('حُذف'); RENDER.files();
    }));
  $('#view').querySelectorAll('[data-ren]').forEach(b => b.onclick = () =>
    modal('إعادة تسمية', '<label>المسار الجديد</label><input id="rnTo" dir="ltr" value="' + h(b.dataset.ren) + '">',
      [{ label: 'إلغاء' }, { label: 'حفظ', kind: 'ok', run: async (box) => {
        await api('/api/files/rename', { method: 'POST', body: { root: fmState.root, path: b.dataset.ren, to: $('#rnTo', box).value.trim() } });
        okToast('تمت التسمية'); RENDER.files();
      } }]));
  $('#view').querySelectorAll('[data-x]').forEach(b => b.onclick = async () => {
    b.disabled = true;
    try { await api('/api/files/extract', { method: 'POST', body: { root: fmState.root, path: b.dataset.x } }); okToast('فُك الأرشيف'); RENDER.files(); }
    catch (e) { errToast(e); b.disabled = false; }
  });
};
const join = (a, b) => (a ? a + '/' + b : b);

/* ═══════════ المهام المجدولة ═══════════ */
RENDER.cron = async () => {
  const { jobs } = await api('/api/cron');
  $('#view').innerHTML = `
  <div class="card">
    <h3>⏰ مهمة جديدة</h3>
    <div class="row">
      <div><label>الجدولة</label><input id="cSched" value="0 3 * * *" dir="ltr"></div>
      <div style="flex:3 1 260px"><label>الأمر</label><input id="cCmd" dir="ltr" placeholder="php $HOME/.shamhost/sites/mysite/cron.php"></div>
      <div style="flex:0 0 auto"><button class="btn" id="cAdd">إضافة</button></div>
    </div>
    <div class="hint">أمثلة: <code class="k">*/15 * * * *</code> كل ربع ساعة ·
      <code class="k">0 4 * * *</code> يومياً 4 فجراً ·
      <code class="k">@reboot</code> عند الإقلاع.
      تأكد أن خدمة <b>crond</b> تعمل من لوحة القيادة.</div>
  </div>
  <div class="card">
    <h3>المهام (${jobs.length})</h3>
    ${jobs.length ? `<div class="tw"><table><thead><tr><th>الجدولة</th><th>الأمر</th><th></th></tr></thead>
      <tbody>${jobs.map(j => `<tr><td dir="ltr" class="small"><code class="k">${h(j.schedule)}</code></td>
      <td dir="ltr" class="small" style="word-break:break-all">${h(j.command)}</td>
      <td><button class="btn bad sm" data-jdel="${j.id}">حذف</button></td></tr>`).join('')}</tbody></table></div>`
      : '<p class="muted">لا توجد مهام.</p>'}
  </div>`;
  $('#cAdd').onclick = async () => {
    try { await api('/api/cron', { method: 'POST', body: { schedule: $('#cSched').value, command: $('#cCmd').value } }); okToast('أُضيفت'); RENDER.cron(); }
    catch (e) { errToast(e); }
  };
  $('#view').querySelectorAll('[data-jdel]').forEach(b => b.onclick = () =>
    confirmBox('حذف المهمة؟', async () => { await api('/api/cron/' + b.dataset.jdel, { method: 'DELETE' }); okToast('حُذفت'); RENDER.cron(); }));
};

/* ═══════════ النسخ الاحتياطي ═══════════ */
RENDER.backups = async () => {
  const { backups } = await api('/api/backups');
  const { sites } = await api('/api/sites');
  $('#view').innerHTML = `
  <div class="card">
    <h3>💾 نسخة جديدة</h3>
    <div class="row">
      <div style="flex:2 1 200px"><label>الموقع</label>
        <select id="bSite">${sites.map(s => `<option value="${h(s.id)}">${h(s.domain)}</option>`).join('')}</select></div>
      <div style="flex:2 1 200px"><label>قاعدة بيانات (اختياري)</label><input id="bDb" dir="ltr" placeholder="myapp_db"></div>
      <div style="flex:0 0 auto"><button class="btn" id="bAdd">إنشاء نسخة</button></div>
    </div>
    <div class="hint">تشمل النسخة ملفات الموقع وإعداداته وقاعدة البيانات إن حُدّدت، في أرشيف tar.gz واحد.</div>
  </div>
  <div class="card">
    <h3>النسخ المحفوظة (${backups.length})</h3>
    ${backups.length ? `<div class="tw"><table><thead><tr><th>الاسم</th><th>الحجم</th><th>التاريخ</th><th></th></tr></thead>
      <tbody>${backups.map(b => `<tr><td dir="ltr" class="small" style="word-break:break-all">${h(b.name)}</td>
        <td class="small">${h(b.sizeText)}</td><td class="small" dir="ltr">${h(b.mtime.slice(0, 16).replace('T', ' '))}</td>
        <td style="white-space:nowrap">
          <a class="btn ghost sm" href="/api/files/download?root=backups&path=${encodeURIComponent(b.name)}">تنزيل</a>
          <button class="btn ghost sm" data-brs="${h(b.name)}">استرجاع</button>
          <button class="btn bad sm" data-bdel="${h(b.name)}">حذف</button></td></tr>`).join('')}</tbody></table></div>
      <button class="btn ghost sm" id="bPrune" style="margin-top:.6rem">🧹 احتفظ بآخر 5 نسخ لكل موقع</button>`
      : '<p class="muted">لا توجد نسخ.</p>'}
  </div>`;
  $('#bAdd').onclick = async () => {
    const b = $('#bAdd'); b.disabled = true; b.innerHTML = '<span class="spin"></span>';
    try { const r = await api('/api/backups', { method: 'POST', body: { site: $('#bSite').value, database: $('#bDb').value.trim() || undefined } });
      okToast('أُنشئت النسخة (' + r.sizeText + ')'); RENDER.backups(); }
    catch (e) { errToast(e); b.disabled = false; b.textContent = 'إنشاء نسخة'; }
  };
  $('#view').querySelectorAll('[data-bdel]').forEach(b => b.onclick = () =>
    confirmBox('حذف النسخة؟', async () => { await api('/api/backups/' + encodeURIComponent(b.dataset.bdel), { method: 'DELETE' }); okToast('حُذفت'); RENDER.backups(); }));
  $('#view').querySelectorAll('[data-brs]').forEach(b => b.onclick = () =>
    modal('استرجاع نسخة', '<p class="small muted">سيُعاد كتابة ملفات الموقع من النسخة.</p><label>اسم قاعدة البيانات للاسترجاع (اختياري)</label><input id="rsDb" dir="ltr">',
      [{ label: 'إلغاء' }, { label: 'استرجاع', kind: 'bad', run: async (box) => {
        await api('/api/backups/restore', { method: 'POST', body: { name: b.dataset.brs, database: $('#rsDb', box).value.trim() || undefined } });
        okToast('تم الاسترجاع');
      } }]));
  if ($('#bPrune')) $('#bPrune').onclick = async () => {
    const r = await api('/api/backups/prune', { method: 'POST', body: { keep: 5 } });
    okToast('حُذفت ' + r.deleted + ' نسخة قديمة'); RENDER.backups();
  };
};

/* ═══════════ الطرفية ═══════════ */
RENDER.terminal = async () => {
  $('#view').innerHTML = `
  <div class="card">
    <h3>⌨️ تنفيذ أوامر</h3>
    <div class="hint warn">الأوامر تُنفَّذ بصلاحيات مستخدم Termux على جهازك. تعامل بحذر.</div>
    <div class="row">
      <div style="flex:4 1 260px"><label>الأمر</label><input id="tCmd" dir="ltr" placeholder="ls -la ~/.shamhost/sites"></div>
      <div style="flex:0 0 auto"><button class="btn" id="tRun">تنفيذ</button></div>
    </div>
    <div class="row" style="margin-top:.4rem">
      ${['shamhost status', 'shamhost doctor', 'df -h', 'free -h', 'php -v', 'nginx -t -c $HOME/.shamhost/conf/nginx.conf']
        .map(c => `<button class="btn ghost sm" data-quick="${h(c)}" style="flex:0 0 auto">${h(c)}</button>`).join('')}
    </div>
    <pre class="out" id="tOut" style="margin-top:.8rem">$ …</pre>
  </div>`;
  const runCmd = async (cmd) => {
    const out = $('#tOut');
    out.textContent = '$ ' + cmd + '\n\n… جارٍ التنفيذ';
    try {
      const r = await api('/api/system/exec', { method: 'POST', body: { command: cmd } });
      out.textContent = '$ ' + cmd + '\n\n' + (r.stdout || '') + (r.stderr ? '\n[stderr]\n' + r.stderr : '') + '\n\n[exit ' + r.code + ']';
    } catch (e) { out.textContent = '$ ' + cmd + '\n\n' + e.message; }
  };
  $('#tRun').onclick = () => runCmd($('#tCmd').value);
  $('#tCmd').onkeydown = (e) => { if (e.key === 'Enter') runCmd($('#tCmd').value); };
  $('#view').querySelectorAll('[data-quick]').forEach(b => b.onclick = () => { $('#tCmd').value = b.dataset.quick; runCmd(b.dataset.quick); });
};

/* ═══════════ السجلات ═══════════ */
RENDER.logs = async () => {
  const { names } = await api('/api/system/log-names');
  const { sites } = await api('/api/sites');
  $('#view').innerHTML = `
  <div class="card">
    <div class="row">
      <div><label>سجل النظام</label><select id="lName">${names.map(n => `<option>${h(n)}</option>`).join('')}</select></div>
      <div><label>سجل موقع</label><select id="lSite"><option value="">—</option>
        ${sites.map(s => `<option value="${h(s.id)}">${h(s.domain)}</option>`).join('')}</select></div>
      <div><label>النوع</label><select id="lKind"><option value="error">أخطاء</option><option value="access">وصول</option></select></div>
      <div><label>عدد الأسطر</label><input id="lLines" type="number" value="200" dir="ltr"></div>
      <div style="flex:0 0 auto"><label>&nbsp;</label><button class="btn" id="lLoad">عرض</button></div>
    </div>
    <pre class="out" id="lOut" style="margin-top:.8rem">اختر سجلاً ثم اضغط عرض.</pre>
  </div>`;
  $('#lLoad').onclick = async () => {
    const out = $('#lOut'); out.textContent = '…';
    try {
      const site = $('#lSite').value;
      const r = site
        ? await api('/api/sites/' + encodeURIComponent(site) + '/logs?kind=' + $('#lKind').value + '&lines=' + $('#lLines').value)
        : await api('/api/system/logs?name=' + encodeURIComponent($('#lName').value) + '&lines=' + $('#lLines').value);
      out.textContent = r.content || '(فارغ)';
      out.scrollTop = out.scrollHeight;
    } catch (e) { out.textContent = e.message; }
  };
  $('#lLoad').click();
};

/* ═══════════ الإعدادات ═══════════ */
RENDER.settings = async () => {
  const { settings: s } = await api('/api/settings');
  $('#view').innerHTML = `
  <div class="card">
    <h3>🔗 Namecheap API</h3>
    <div class="row">
      <div><label>ApiUser</label><input id="ncUser" dir="ltr" value="${h(s.namecheap.api_user)}"></div>
      <div><label>UserName</label><input id="ncName" dir="ltr" value="${h(s.namecheap.username)}"></div>
    </div>
    <label>API Key ${s.namecheap.has_key ? '<span class="pill ok">محفوظ</span>' : ''}</label>
    <input id="ncKey" type="password" dir="ltr" placeholder="${s.namecheap.has_key ? 'اتركه فارغاً للإبقاء على الحالي' : ''}">
    <div class="row">
      <div><label>IP المُدرج في القائمة البيضاء</label><input id="ncIp" dir="ltr" value="${h(s.namecheap.client_ip)}"></div>
      <div style="flex:0 0 auto"><label>&nbsp;</label><button class="btn ghost" id="ncDetect">اكتشف تلقائياً</button></div>
    </div>
    <div class="check"><input type="checkbox" id="ncSandbox" ${s.namecheap.sandbox ? 'checked' : ''}>
      <label for="ncSandbox" style="margin:0">استخدام بيئة الاختبار (sandbox)</label></div>
    <button class="btn" id="ncSave" style="margin-top:.8rem">حفظ</button>
    <div class="hint">ApiUser و UserName عادةً متطابقان (اسم حسابك في Namecheap).
      المفتاح من: Profile ← Tools ← Namecheap API Access. ولا تنسَ إدراج IP جهازك في Whitelisted IPs.</div>
  </div>

  <div class="card">
    <h3>🗃️ اتصال MariaDB</h3>
    <div class="row">
      <div><label>المستخدم الإداري</label><input id="dbUser" dir="ltr" value="${h(s.db.admin_user)}"></div>
      <div><label>كلمة المرور ${s.db.has_password ? '<span class="pill ok">محفوظة</span>' : ''}</label><input id="dbPass" type="password" dir="ltr"></div>
      <div><label>المضيف</label><input id="dbHost" dir="ltr" value="${h(s.db.host)}"></div>
      <div><label>المنفذ</label><input id="dbPort" type="number" dir="ltr" value="${s.db.port}"></div>
    </div>
    <button class="btn" id="dbSave" style="margin-top:.8rem">حفظ</button>
    <div class="hint">في Termux يكون المستخدم الإداري الافتراضي هو اسم مستخدم النظام بلا كلمة مرور.</div>
  </div>

  <div class="card">
    <h3>🔑 كلمة مرور اللوحة</h3>
    <label>كلمة مرور جديدة (8 أحرف فأكثر)</label>
    <input id="pwNew" type="password" dir="ltr">
    <button class="btn" id="pwSave" style="margin-top:.8rem">تغيير</button>
    <div class="hint">سيتم تسجيل خروج كل الجلسات بعد التغيير.</div>
  </div>

  <div class="card">
    <h3>🔌 واجهة متوافقة مع cPanel</h3>
    <p class="small muted">تطبيقاتك التي تتحدث مع cPanel UAPI يمكنها العمل مع هذا الجهاز بلا تعديل: استخدم المضيف أدناه مع ترويسة
      <code class="k">Authorization: cpanel USER:TOKEN</code>.</p>
    <div id="tokBox"></div>
    <button class="btn ghost" id="tokShow">إظهار الرمز</button>
    <button class="btn ghost" id="tokNew">توليد رمز جديد</button>
    <div class="hint">الدوال المدعومة: SubDomain.addsubdomain / delsubdomain / list_subdomains ·
      Mysql.create_database / create_user / set_privileges_on_user / list_databases · DomainInfo.list_domains.</div>
  </div>

  <div class="card">
    <h3>🧰 صيانة</h3>
    <div class="row">
      <button class="btn ghost" id="mRebuild">إعادة بناء إعدادات nginx</button>
      <button class="btn ghost" id="mTest">فحص إعداد nginx</button>
    </div>
    <p class="small muted" style="margin-top:.7rem">الإصدار: ${h(s.version)}</p>
  </div>`;

  $('#ncDetect').onclick = async () => {
    try { const { ip } = await api('/api/system/publicip'); $('#ncIp').value = ip || ''; okToast('IP = ' + ip); } catch (e) { errToast(e); }
  };
  $('#ncSave').onclick = async () => {
    try {
      await api('/api/settings', { method: 'POST', body: { namecheap: {
        api_user: $('#ncUser').value.trim(), username: $('#ncName').value.trim(),
        api_key: $('#ncKey').value.trim() || undefined, client_ip: $('#ncIp').value.trim(),
        sandbox: $('#ncSandbox').checked } } });
      okToast('حُفظت بيانات Namecheap'); $('#ncKey').value = '';
    } catch (e) { errToast(e); }
  };
  $('#dbSave').onclick = async () => {
    try {
      await api('/api/settings', { method: 'POST', body: { db: {
        admin_user: $('#dbUser').value.trim(), admin_pass: $('#dbPass').value,
        host: $('#dbHost').value.trim(), port: $('#dbPort').value } } });
      okToast('حُفظ اتصال قاعدة البيانات');
    } catch (e) { errToast(e); }
  };
  $('#pwSave').onclick = async () => {
    try { await api('/api/settings/password', { method: 'POST', body: { password: $('#pwNew').value } }); okToast('تغيّرت كلمة المرور، سجّل الدخول من جديد'); setTimeout(showLogin, 1200); }
    catch (e) { errToast(e); }
  };
  $('#tokShow').onclick = async () => {
    const r = await api('/api/settings/api-token');
    const host = location.host;
    $('#tokBox').innerHTML = `<table><tbody>
      <tr><td>المضيف</td><td><code class="k">${h(host)}</code></td></tr>
      <tr><td>المستخدم</td><td><code class="k">${h(r.username)}</code></td></tr>
      <tr><td>الرمز</td><td><code class="k" style="word-break:break-all">${h(r.token)}</code></td></tr>
      </tbody></table>`;
  };
  $('#tokNew').onclick = () => confirmBox('توليد رمز جديد سيبطل الرمز الحالي. متابعة؟', async () => {
    const r = await api('/api/settings/api-token', { method: 'POST', body: {} });
    okToast('رمز جديد: ' + r.token); $('#tokShow').click();
  });
  $('#mRebuild').onclick = async () => {
    const b = $('#mRebuild'); b.disabled = true;
    try { await api('/api/nginx/rebuild', { method: 'POST', body: {} }); okToast('أُعيد البناء والتحميل'); }
    catch (e) { errToast(e); } finally { b.disabled = false; }
  };
  $('#mTest').onclick = async () => {
    const r = await api('/api/nginx/test');
    modal(r.valid ? 'الإعداد صالح' : 'الإعداد غير صالح', '<pre class="out">' + h(r.output) + '</pre>', [{ label: 'إغلاق' }]);
  };
};

/* ═══════════ الإقلاع ═══════════ */
function showLogin() {
  $('#app').classList.remove('on');
  $('#loginView').style.display = 'grid';
}
function showApp() {
  $('#loginView').style.display = 'none';
  $('#app').classList.add('on');
}
const closeSide = () => { $('#side').classList.remove('open'); $('#scrim').classList.remove('on'); };

$('#loginForm').onsubmit = async (e) => {
  e.preventDefault();
  const b = $('#loginBtn'); b.disabled = true; b.innerHTML = '<span class="spin"></span>';
  try {
    await api('/api/login', { method: 'POST', body: { username: $('#lu').value, password: $('#lp').value } });
    showApp(); buildNav(); go(location.hash.slice(1) || 'dash');
  } catch (err) { errToast(err); }
  finally { b.disabled = false; b.textContent = 'دخول'; }
};

$('#logoutBtn').onclick = async () => { try { await api('/api/logout', { method: 'POST', body: {} }); } catch (_) {} showLogin(); };
$('#refreshBtn').onclick = () => go(current);
$('#menuBtn').onclick = () => { $('#side').classList.toggle('open'); $('#scrim').classList.toggle('on'); };
$('#scrim').onclick = closeSide;
$('#themeBtn').onclick = () => {
  const now = document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
  document.documentElement.setAttribute('data-theme', now);
  try { localStorage.setItem('sh_theme', now); } catch (_) {}
};
try { const t = localStorage.getItem('sh_theme'); if (t) document.documentElement.setAttribute('data-theme', t); } catch (_) {}

(async () => {
  try {
    await api('/api/me');
    showApp(); buildNav(); go(location.hash.slice(1) || 'dash');
  } catch (_) { showLogin(); }
})();
