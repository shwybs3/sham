/* ==========================================================
   Hamado Market — دفتر ديون الدكان
   يعمل بالكامل دون إنترنت. كل البيانات داخل الجهاز (IndexedDB).
   ========================================================== */
(function () {
'use strict';

const APP_VERSION = '1.0.0';
const $  = (s, r) => (r || document).querySelector(s);
const $$ = (s, r) => Array.from((r || document).querySelectorAll(s));

/* ─────────────────── الأيقونات (SVG أصلية، بلا إيموجي) ─────────────────── */
const ICONS = {
  home:     '<path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-4v-6H9v6H5a1 1 0 0 1-1-1z"/>',
  users:    '<path d="M16 20v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 18.5V20"/><circle cx="10" cy="8" r="3.2"/><path d="M20 20v-1.4a3.4 3.4 0 0 0-2.6-3.3M15.5 5.2a3.2 3.2 0 0 1 0 5.9"/>',
  bread:    '<path d="M5 11c0-2.8 3.1-5 7-5s7 2.2 7 5c0 1-.6 1.7-1.5 1.9V17a2 2 0 0 1-2 2H8.5a2 2 0 0 1-2-2v-4.1C5.6 12.7 5 12 5 11z"/><path d="M9.5 9.2v3.4M12 9v3.6M14.5 9.2v3.4"/>',
  shield:   '<path d="M12 3.2 19 6v5.4c0 4.2-2.8 7.4-7 9.4-4.2-2-7-5.2-7-9.4V6z"/><path d="M9.3 12.2l1.9 1.9 3.6-3.9"/>',
  plus:     '<path d="M12 5.5v13M5.5 12h13"/>',
  minus:    '<path d="M5.5 12h13"/>',
  'plus-c': '<circle cx="12" cy="12" r="8.5"/><path d="M12 8.6v6.8M8.6 12h6.8"/>',
  'minus-c':'<circle cx="12" cy="12" r="8.5"/><path d="M8.6 12h6.8"/>',
  search:   '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/>',
  x:        '<path d="M17.5 6.5 6.5 17.5M6.5 6.5l11 11"/>',
  check:    '<path d="M5 12.5 9.5 17 19 7.5"/>',
  save:     '<path d="M5 5.8A.8.8 0 0 1 5.8 5h9.6L19 8.6v9.6a.8.8 0 0 1-.8.8H5.8a.8.8 0 0 1-.8-.8z"/><path d="M8.5 5v5h7V6.5M8.5 19v-5h7v5"/>',
  cash:     '<rect x="3" y="6.5" width="18" height="11" rx="2"/><circle cx="12" cy="12" r="2.6"/><path d="M6.5 10v4M17.5 10v4"/>',
  wallet:   '<path d="M4 8.5A2 2 0 0 1 6 6.5h11a2 2 0 0 1 2 2V17a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/><path d="M4 9.5h13.5a1.5 1.5 0 0 1 1.5 1.5v2.4a1.5 1.5 0 0 1-1.5 1.5H4"/><circle cx="16.2" cy="12.2" r=".9" fill="currentColor" stroke="none"/>',
  alert:    '<path d="M12 4.5 21 19.5H3z"/><path d="M12 10v4"/><circle cx="12" cy="16.8" r=".9" fill="currentColor" stroke="none"/>',
  bolt:     '<path d="M13.5 3 5.5 13.5H11l-.5 7.5 8-10.5H13z"/>',
  list:     '<path d="M9 6.5h11M9 12h11M9 17.5h11"/><circle cx="4.8" cy="6.5" r="1.2" fill="currentColor" stroke="none"/><circle cx="4.8" cy="12" r="1.2" fill="currentColor" stroke="none"/><circle cx="4.8" cy="17.5" r="1.2" fill="currentColor" stroke="none"/>',
  history:  '<path d="M4.5 12a7.5 7.5 0 1 0 2.2-5.3"/><path d="M4.5 5.5V9h3.5"/><path d="M12 8.4V12l2.6 1.6"/>',
  inbox:    '<path d="M4 13.5 6.2 6.2A1.5 1.5 0 0 1 7.6 5h8.8a1.5 1.5 0 0 1 1.4 1.2L20 13.5V18a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1z"/><path d="M4 13.5h4l1 2.5h6l1-2.5h4"/>',
  back:     '<path d="M14.5 5.5 8 12l6.5 6.5"/>',
  arrow:    '<path d="M14.5 5.5 8 12l6.5 6.5"/>',
  more:     '<circle cx="12" cy="5.5" r="1.4" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.4" fill="currentColor" stroke="none"/><circle cx="12" cy="18.5" r="1.4" fill="currentColor" stroke="none"/>',
  edit:     '<path d="M15.5 4.8 19.2 8.5 8.7 19H5v-3.7z"/><path d="M13.4 6.9 17.1 10.6"/>',
  trash:    '<path d="M5.5 7h13M9.5 7V5.4a.9.9 0 0 1 .9-.9h3.2a.9.9 0 0 1 .9.9V7"/><path d="M7 7l.8 11.2a1 1 0 0 0 1 .9h6.4a1 1 0 0 0 1-.9L17 7"/><path d="M10.4 10.5v5.2M13.6 10.5v5.2"/>',
  phone:    '<path d="M7.2 4.5h2.4l1.5 3.6-2 1.4a10.5 10.5 0 0 0 5.4 5.4l1.4-2 3.6 1.5v2.4a1.8 1.8 0 0 1-2 1.8A14.6 14.6 0 0 1 5.4 6.5a1.8 1.8 0 0 1 1.8-2z"/>',
  chat:     '<path d="M4.5 6.2A1.7 1.7 0 0 1 6.2 4.5h11.6a1.7 1.7 0 0 1 1.7 1.7v7.6a1.7 1.7 0 0 1-1.7 1.7H9.5L5 19.3z"/>',
  store:    '<path d="M4.4 9.5h15.2V19a1 1 0 0 1-1 1H5.4a1 1 0 0 1-1-1z"/><path d="M4 9.5 5.6 5a.9.9 0 0 1 .85-.6h11.1a.9.9 0 0 1 .85.6L20 9.5"/><path d="M9.8 20v-5.2h4.4V20"/>',
  lock:     '<rect x="5" y="10.2" width="14" height="9.3" rx="2"/><path d="M8.2 10.2V7.8a3.8 3.8 0 0 1 7.6 0v2.4"/><circle cx="12" cy="14.6" r="1.3" fill="currentColor" stroke="none"/>',
  megaphone:'<path d="M4.5 10.2v3.6a1.4 1.4 0 0 0 1.4 1.4H8l6.5 4V5.2L8 9.2H5.9a1.4 1.4 0 0 0-1.4 1z"/><path d="M17.8 9a4.2 4.2 0 0 1 0 6M8 15.2V19a1 1 0 0 0 1 1h1.4"/>',
  cloud:    '<path d="M7.2 18a3.7 3.7 0 0 1-.4-7.4A5.2 5.2 0 0 1 17 9.8a3.6 3.6 0 0 1-.4 8.2z"/><path d="M12 11.8v5.4M9.8 14l2.2-2.2 2.2 2.2"/>',
  download: '<path d="M12 4.5v10"/><path d="M8.2 11 12 14.8 15.8 11"/><path d="M5 16.5v2a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2"/>',
  upload:   '<path d="M12 15V4.8"/><path d="M8.2 8.4 12 4.6l3.8 3.8"/><path d="M5 16.5v2a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2"/>',
  share:    '<circle cx="17.5" cy="6.5" r="2.5"/><circle cx="6.5" cy="12" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/><path d="m8.7 10.8 6.6-3.1M8.7 13.2l6.6 3.1"/>',
  send:     '<path d="M20 4 3.8 10.4l6.1 2.3 2.3 6.1z"/><path d="M20 4 9.9 12.7"/>',
  refresh:  '<path d="M19 12a7 7 0 1 1-2-4.9"/><path d="M19.2 4.6v3.8h-3.8"/>',
  calendar: '<rect x="4" y="5.8" width="16" height="14" rx="2"/><path d="M4 10h16M8.5 4v3.4M15.5 4v3.4"/>',
  book:     '<path d="M5 5.4A1.4 1.4 0 0 1 6.4 4H18v14.5H6.4A1.4 1.4 0 0 0 5 20z"/><path d="M5 18.6A1.4 1.4 0 0 1 6.4 17.2H18"/>',
  tag:      '<path d="M4.5 11.3V5.6a1 1 0 0 1 1-1h5.7a1 1 0 0 1 .7.3l7 7a1 1 0 0 1 0 1.4l-5.7 5.7a1 1 0 0 1-1.4 0l-7-7a1 1 0 0 1-.3-.7z"/><circle cx="8.4" cy="8.4" r="1.3"/>',
  clock:    '<circle cx="12" cy="12" r="7.8"/><path d="M12 7.6V12l2.9 1.8"/>',
};
function icon(name, cls) {
  const p = ICONS[name] || ICONS.tag;
  return '<svg class="ic ' + (cls || '') + '" viewBox="0 0 24 24" aria-hidden="true">' + p + '</svg>';
}
function paintIcons(root) {
  $$('[data-ic]', root || document).forEach(el => {
    if (el.dataset.painted) return;
    el.innerHTML = icon(el.dataset.ic);
    el.dataset.painted = '1';
  });
}

/* ─────────────────── قاعدة البيانات المحلية ─────────────────── */
const DB = (function () {
  let dbp = null;
  function open() {
    if (dbp) return dbp;
    dbp = new Promise((res, rej) => {
      const rq = indexedDB.open('hamado_market', 1);
      rq.onupgradeneeded = e => {
        const db = e.target.result;
        if (!db.objectStoreNames.contains('customers')) {
          const s = db.createObjectStore('customers', { keyPath: 'id', autoIncrement: true });
          s.createIndex('norm', 'norm', { unique: false });
        }
        if (!db.objectStoreNames.contains('transactions')) {
          const s = db.createObjectStore('transactions', { keyPath: 'id', autoIncrement: true });
          s.createIndex('customerId', 'customerId', { unique: false });
        }
        if (!db.objectStoreNames.contains('bread')) {
          const s = db.createObjectStore('bread', { keyPath: 'id', autoIncrement: true });
          s.createIndex('date', 'date', { unique: false });
        }
        if (!db.objectStoreNames.contains('settings')) {
          db.createObjectStore('settings', { keyPath: 'key' });
        }
      };
      rq.onsuccess = () => res(rq.result);
      rq.onerror = () => rej(rq.error);
    });
    return dbp;
  }
  function tx(store, mode) { return open().then(db => db.transaction(store, mode).objectStore(store)); }
  function wrap(rq) { return new Promise((res, rej) => { rq.onsuccess = () => res(rq.result); rq.onerror = () => rej(rq.error); }); }
  return {
    all:  s => tx(s, 'readonly').then(o => wrap(o.getAll())),
    get:  (s, k) => tx(s, 'readonly').then(o => wrap(o.get(k))),
    put:  (s, v) => tx(s, 'readwrite').then(o => wrap(o.put(v))),
    add:  (s, v) => tx(s, 'readwrite').then(o => wrap(o.add(v))),
    del:  (s, k) => tx(s, 'readwrite').then(o => wrap(o.delete(k))),
    clear:s => tx(s, 'readwrite').then(o => wrap(o.clear())),
    byIndex: (s, idx, val) => tx(s, 'readonly').then(o => wrap(o.index(idx).getAll(val))),
  };
})();

/* ─────────────────── الحالة ─────────────────── */
const S = {
  customers: [],
  tx: [],
  bread: [],
  balances: {},
  settings: {
    shopName: 'Hamado Market',
    currency: 'ل.س',
    breadPrice: 0,
    pin: hash('1234'),
    ticker: '',
    tickerOn: false,
    banners: [],
    tgToken: '',
    tgChat: '',
    tgAuto: false,
    lastAutoBackup: '',
  },
  view: 'home',
  tab: 'all',
  listQuery: '',
  quick: { customer: null, type: 'debt', isNew: false, newName: '' },
  breadSel: { customer: null, isNew: false, newName: '', count: 1, paid: true, date: today() },
  currentCustomer: null,
  adminOpen: false,
  pinBuf: '',
};

function hash(s) {
  let h = 5381;
  s = 'hm~' + String(s);
  for (let i = 0; i < s.length; i++) h = ((h * 33) ^ s.charCodeAt(i)) >>> 0;
  return 'h' + h.toString(36);
}

/* ─────────────────── أدوات ─────────────────── */
function today() {
  const d = new Date(), p = n => String(n).padStart(2, '0');
  return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate());
}
function nowISO() { return new Date().toISOString(); }
function esc(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
function money(n) {
  n = Math.round((Number(n) || 0) * 100) / 100;
  return n.toLocaleString('en-US', { maximumFractionDigits: 2 }) + ' ' + S.settings.currency;
}
function moneyBare(n) {
  n = Math.round((Number(n) || 0) * 100) / 100;
  return n.toLocaleString('en-US', { maximumFractionDigits: 2 });
}
function parseAmount(v) {
  const n = parseFloat(String(v).replace(/[^\d.]/g, ''));
  return isFinite(n) && n > 0 ? n : 0;
}
// تطبيع عربي: يوحّد الهمزات والتاء المربوطة والألف المقصورة ويزيل التشكيل
function arNorm(s) {
  return String(s || '')
    .replace(/[ً-ْٰـ]/g, '')
    .replace(/[أإآٱ]/g, 'ا')
    .replace(/ة/g, 'ه')
    .replace(/ى/g, 'ي')
    .replace(/ؤ/g, 'و')
    .replace(/ئ/g, 'ي')
    .replace(/\s+/g, ' ')
    .trim()
    .toLowerCase();
}
const AV_COLORS = [
  ['#12D9A0', '#0A7C5E'], ['#FF5C7A', '#A8143A'], ['#3FC5FF', '#1A6E9E'],
  ['#FFB020', '#A86A05'], ['#A78BFA', '#5B34C4'], ['#2EE07A', '#0E7A41'],
  ['#FF8A5B', '#B0411B'], ['#5AC8FA', '#1E6FA8'],
];
function avatar(name, cls, id) {
  const clean = String(name || '؟').trim();
  const parts = clean.split(/\s+/);
  // الأحرف العربية تتصل ببعضها، فحرفان يظهران كرباط غير مقروء — نكتفي بحرف واحد للعربية
  const isArabic = /[؀-ۿ]/.test(clean);
  const ini = isArabic
    ? clean[0]
    : (parts.length > 1 ? (parts[0][0] || '') + (parts[1][0] || '') : clean.slice(0, 2));
  let h = 0;
  for (let i = 0; i < clean.length; i++) h = (h * 31 + clean.charCodeAt(i)) >>> 0;
  const c = AV_COLORS[h % AV_COLORS.length];
  return '<span class="avatar ' + (cls || '') + '"' + (id ? ' id="' + id + '"' : '') +
    ' style="background:linear-gradient(145deg,' + c[0] + ',' + c[1] + ')">' + esc(ini) + '</span>';
}
function timeAgo(iso) {
  const d = new Date(iso), diff = (Date.now() - d.getTime()) / 1000;
  if (diff < 60) return 'الآن';
  if (diff < 3600) return 'قبل ' + Math.floor(diff / 60) + ' د';
  if (diff < 86400) return 'قبل ' + Math.floor(diff / 3600) + ' س';
  if (diff < 172800) return 'أمس';
  if (diff < 604800) return 'قبل ' + Math.floor(diff / 86400) + ' أيام';
  return d.toLocaleDateString('ar-EG', { day: 'numeric', month: 'short', year: 'numeric' });
}
function dayLabel(iso) {
  const d = new Date(iso), t = today();
  const key = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  if (key === t) return 'اليوم';
  const y = new Date(Date.now() - 86400000);
  const yk = y.getFullYear() + '-' + String(y.getMonth() + 1).padStart(2, '0') + '-' + String(y.getDate()).padStart(2, '0');
  if (key === yk) return 'أمس';
  return d.toLocaleDateString('ar-EG', { weekday: 'long', day: 'numeric', month: 'long' });
}

let toastTimer;
function toast(msg, kind) {
  const t = $('#toast');
  t.textContent = msg;
  t.className = 'toast ' + (kind || '');
  t.hidden = false;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => { t.hidden = true; }, 2600);
}
function confirmBox(title, text, danger) {
  return new Promise(res => {
    $('#confirmTitle').textContent = title;
    $('#confirmText').textContent = text;
    const yes = $('#confirmYes'), no = $('#confirmNo'), wrap = $('#confirmWrap');
    yes.className = 'btn ' + (danger === false ? 'btn-primary' : 'btn-danger');
    wrap.hidden = false;
    const done = v => { wrap.hidden = true; yes.onclick = null; no.onclick = null; res(v); };
    yes.onclick = () => done(true);
    no.onclick = () => done(false);
  });
}
function openSheet(title, html) {
  $('#sheetTitle').textContent = title;
  $('#sheetBody').innerHTML = html;
  $('#sheetWrap').hidden = false;
  paintIcons($('#sheetBody'));
}
function closeSheet() { $('#sheetWrap').hidden = true; }

/* تموّج الأزرار */
document.addEventListener('pointerdown', e => {
  const b = e.target.closest('.btn');
  if (!b) return;
  const r = b.getBoundingClientRect();
  const s = Math.max(r.width, r.height);
  const el = document.createElement('span');
  el.className = 'ripple';
  el.style.cssText = 'width:' + s + 'px;height:' + s + 'px;left:' + (e.clientX - r.left - s / 2) + 'px;top:' + (e.clientY - r.top - s / 2) + 'px';
  b.appendChild(el);
  setTimeout(() => el.remove(), 560);
});

/* ─────────────────── الجسر مع أندرويد ─────────────────── */
const Native = {
  get on() { return typeof AndroidBridge !== 'undefined'; },
  saveFile(name, content) {
    if (this.on) { try { return AndroidBridge.saveFile(name, content); } catch (e) { return 'ERR:' + e; } }
    const b = new Blob([content], { type: 'application/json' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(b); a.download = name; a.click();
    setTimeout(() => URL.revokeObjectURL(a.href), 4000);
    return 'OK';
  },
  shareFile(name, content) {
    if (this.on) { try { return AndroidBridge.shareFile(name, content); } catch (e) { return 'ERR:' + e; } }
    return this.saveFile(name, content);
  },
  sendTelegram(token, chat, name, content) {
    if (this.on) {
      return Promise.resolve().then(() => AndroidBridge.sendTelegram(token, chat, name, content));
    }
    const fd = new FormData();
    fd.append('chat_id', chat);
    fd.append('caption', 'نسخة احتياطية — ' + S.settings.shopName + ' — ' + new Date().toLocaleString('ar-EG'));
    fd.append('document', new Blob([content], { type: 'application/json' }), name);
    return fetch('https://api.telegram.org/bot' + token + '/sendDocument', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(j => (j && j.ok) ? 'OK' : 'ERR:' + JSON.stringify(j && j.description || j));
  },
};

/* ─────────────────── تحميل البيانات ─────────────────── */
async function loadAll() {
  const [cs, txs, br, st] = await Promise.all([
    DB.all('customers'), DB.all('transactions'), DB.all('bread'), DB.all('settings'),
  ]);
  S.customers = cs;
  S.tx = txs;
  S.bread = br;
  st.forEach(row => { S.settings[row.key] = row.value; });
  recomputeBalances();
}
function recomputeBalances() {
  const b = {};
  S.customers.forEach(c => { b[c.id] = 0; });
  S.tx.forEach(t => {
    if (!(t.customerId in b)) b[t.customerId] = 0;
    b[t.customerId] += (t.type === 'debt' ? 1 : -1) * Number(t.amount || 0);
  });
  S.balances = b;
}
function saveSetting(key, value) {
  S.settings[key] = value;
  return DB.put('settings', { key: key, value: value });
}
function balOf(id) { return Math.round((S.balances[id] || 0) * 100) / 100; }

async function findOrCreateCustomer(name) {
  const n = arNorm(name);
  const found = S.customers.find(c => c.norm === n);
  if (found) return found;
  const rec = { name: String(name).trim(), norm: n, phone: '', note: '', archived: 0, createdAt: nowISO() };
  rec.id = await DB.add('customers', rec);
  S.customers.push(rec);
  S.balances[rec.id] = 0;
  return rec;
}
async function addTx(customerId, type, amount, note, extra) {
  const rec = Object.assign({
    customerId: customerId, type: type, amount: Number(amount),
    note: note || '', createdAt: nowISO(), source: 'manual',
  }, extra || {});
  rec.id = await DB.add('transactions', rec);
  S.tx.push(rec);
  recomputeBalances();
  return rec;
}
async function removeTx(id) {
  await DB.del('transactions', id);
  S.tx = S.tx.filter(t => t.id !== id);
  recomputeBalances();
}

/* ─────────────────── التنقّل ─────────────────── */
function go(view) {
  if (view === 'customers') { view = 'home'; setTimeout(() => { const el = $('#customerCards'); if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 60); }
  S.view = view;
  $$('.view').forEach(v => v.classList.remove('is-active'));
  const el = $('#view-' + view);
  if (el) el.classList.add('is-active');
  $$('.tabbar-btn').forEach(b => b.classList.toggle('is-on', b.dataset.go === view));
  if (view === 'home')   renderHome();
  if (view === 'bread')  renderBread();
  if (view === 'admin')  renderAdmin();
}

/* ─────────────────── الرئيسية ─────────────────── */
function renderStats() {
  const ids = S.customers.filter(c => !c.archived).map(c => c.id);
  let total = 0, debtors = 0, clear = 0;
  ids.forEach(id => {
    const b = balOf(id);
    if (b > 0) { total += b; debtors++; } else clear++;
  });
  $('#statTotal').textContent = moneyBare(total);
  $('#statCustomers').textContent = ids.length;
  $('#statDebtors').textContent = debtors;
  $('#statClear').textContent = clear;
  $('#qCur').textContent = S.settings.currency;
  $('#shopName').textContent = S.settings.shopName || 'Hamado Market';
}
function renderBanners() {
  const box = $('#banners');
  const list = S.settings.banners || [];
  if (!list.length) { box.hidden = true; return; }
  box.hidden = false;
  box.innerHTML = list.map(b =>
    '<div class="banner"><b>' + esc(b.text) + '</b>' + (b.sub ? '<small>' + esc(b.sub) + '</small>' : '') + '</div>'
  ).join('');
}
function renderTicker() {
  const t = $('#ticker');
  if (S.settings.tickerOn && S.settings.ticker) {
    $('#tickerTrack').textContent = S.settings.ticker + '   •   ' + S.settings.ticker;
    t.hidden = false;
    document.body.classList.add('has-ticker');
  } else {
    t.hidden = true;
    document.body.classList.remove('has-ticker');
  }
}
function customerRow(c) {
  const b = balOf(c.id);
  const cls = b > 0 ? 'bal-debt' : (b < 0 ? 'bal-ok' : 'bal-zero');
  const label = b > 0 ? 'عليه' : (b < 0 ? 'له' : 'مسدَّد');
  const last = S.tx.filter(t => t.customerId === c.id).sort((x, y) => y.createdAt.localeCompare(x.createdAt))[0];
  return '<button class="ccard ' + cls + '" data-cust="' + c.id + '">' +
    avatar(c.name) +
    '<span class="ccard-info"><b>' + esc(c.name) + '</b>' +
      '<small>' + icon('clock') + (last ? timeAgo(last.createdAt) : 'لا حركات') + '</small></span>' +
    '<span class="ccard-bal"><b>' + moneyBare(Math.abs(b)) + '</b><small>' + label + '</small></span>' +
    '<span class="ccard-arrow">' + icon('arrow') + '</span>' +
  '</button>';
}
function renderHome() {
  renderStats(); renderBanners(); renderTicker();
  const q = arNorm(S.listQuery);
  let list = S.customers.filter(c => !c.archived);
  if (S.tab === 'debt')  list = list.filter(c => balOf(c.id) > 0);
  if (S.tab === 'clear') list = list.filter(c => balOf(c.id) <= 0);
  if (q) list = list.filter(c => c.norm.indexOf(q) !== -1);
  list.sort((a, b) => {
    const d = balOf(b.id) - balOf(a.id);
    return d !== 0 ? d : a.name.localeCompare(b.name, 'ar');
  });
  const box = $('#customerCards');
  box.innerHTML = list.map(customerRow).join('');
  $$('.ccard', box).forEach((el, i) => { el.style.animationDelay = Math.min(i * 26, 420) + 'ms'; });
  paintIcons(box);
  $('#homeEmpty').hidden = list.length > 0;
  renderQuickAmounts();
}
function renderQuickAmounts() {
  const base = [500, 1000, 2000, 5000, 10000, 25000];
  $('#qQuickAmounts').innerHTML = base.map(v =>
    '<button class="chip" data-amt="' + v + '">' + v.toLocaleString('en-US') + '</button>'
  ).join('');
}

/* الاقتراحات */
function suggestHTML(q, items, allowNew) {
  let h = items.map(c => {
    const b = balOf(c.id);
    const cls = b > 0 ? 'bal-debt' : (b < 0 ? 'bal-ok' : 'bal-zero');
    const idx = c.norm.indexOf(arNorm(q));
    let nm = esc(c.name);
    if (idx >= 0 && q) {
      const raw = c.name;
      nm = esc(raw.slice(0, idx)) + '<mark>' + esc(raw.slice(idx, idx + q.length)) + '</mark>' + esc(raw.slice(idx + q.length));
    }
    return '<button class="sg" data-pick="' + c.id + '">' + avatar(c.name, 'sm') +
      '<span class="sg-name">' + nm + '</span>' +
      '<span class="sg-bal ' + cls + '" style="color:' + (b > 0 ? 'var(--debt)' : b < 0 ? 'var(--paid)' : 'var(--dim)') + '">' + moneyBare(Math.abs(b)) + '</span></button>';
  }).join('');
  if (allowNew && q.trim()) {
    h += '<button class="sg" data-new="1">' + icon('plus-c') +
      '<span class="sg-name sg-new">إضافة «' + esc(q.trim()) + '» كعميل جديد</span></button>';
  }
  return h;
}
function searchCustomers(q) {
  const n = arNorm(q);
  if (n.length < 2) return [];
  const starts = [], contains = [];
  S.customers.forEach(c => {
    if (c.archived) return;
    const i = c.norm.indexOf(n);
    if (i === 0) starts.push(c);
    else if (i > 0) contains.push(c);
  });
  return starts.concat(contains).slice(0, 8);
}

/* ─────────────────── التسجيل السريع ─────────────────── */
function setQuickCustomer(c, isNew, name) {
  S.quick.customer = c;
  S.quick.isNew = !!isNew;
  S.quick.newName = name || '';
  const chip = $('#selectedChip');
  if (!c && !isNew) { chip.hidden = true; return; }
  chip.hidden = false;
  const nm = c ? c.name : name;
  $('#chipAvatar').outerHTML = avatar(nm, '', 'chipAvatar');
  $('#chipName').textContent = nm;
  $('#chipBal').textContent = c ? ('الرصيد: ' + money(balOf(c.id))) : 'عميل جديد — سيُنشأ عند الحفظ';
  $('#suggestBox').hidden = true;
  $('#qAmount').focus();
}
function resetQuick() {
  S.quick = { customer: null, type: S.quick.type, isNew: false, newName: '' };
  $('#qName').value = '';
  $('#qAmount').value = '';
  $('#qNote').value = '';
  $('#selectedChip').hidden = true;
  $('#suggestBox').hidden = true;
  $('#qClear').hidden = true;
  $$('#qQuickAmounts .chip').forEach(c => c.classList.remove('is-on'));
}
async function quickSave() {
  const amount = parseAmount($('#qAmount').value);
  if (!S.quick.customer && !S.quick.isNew) { toast('اختر العميل أولاً', 'err'); $('#qName').focus(); return; }
  if (!amount) { toast('أدخل مبلغاً صحيحاً', 'err'); $('#qAmount').focus(); return; }
  const c = S.quick.customer || await findOrCreateCustomer(S.quick.newName);
  await addTx(c.id, S.quick.type, amount, $('#qNote').value.trim());
  toast((S.quick.type === 'debt' ? 'سُجّل دين ' : 'سُجّلت دفعة ') + money(amount) + ' — ' + c.name, 'ok');
  resetQuick();
  renderHome();
  maybeAutoBackup();
}

/* ─────────────────── صفحة العميل ─────────────────── */
function openCustomer(id) {
  const c = S.customers.find(x => x.id === id);
  if (!c) return;
  S.currentCustomer = c;
  go('customer');
  renderCustomer();
}
function renderCustomer() {
  const c = S.currentCustomer;
  if (!c) return;
  const b = balOf(c.id);
  $('#custTitle').textContent = c.name;
  $('#custSub').textContent = c.phone ? c.phone : (c.note || 'عميل');
  $('#custAvatar').outerHTML = avatar(c.name, 'lg bh-avatar', 'custAvatar');
  $('#custBalance').textContent = money(Math.abs(b));
  const tag = $('#custTag');
  tag.className = 'bh-tag ' + (b > 0 ? 't-debt' : 't-ok');
  tag.innerHTML = b > 0 ? icon('alert') + ' مستحق عليه' : (b < 0 ? icon('check') + ' له رصيد لديك' : icon('check') + ' الحساب مسدَّد');

  const mine = S.tx.filter(t => t.customerId === c.id).sort((x, y) => y.createdAt.localeCompare(x.createdAt));
  const totDebt = mine.filter(t => t.type === 'debt').reduce((s, t) => s + Number(t.amount), 0);
  const totPay  = mine.filter(t => t.type === 'payment').reduce((s, t) => s + Number(t.amount), 0);
  $('#custMini').innerHTML =
    '<div class="ms"><b style="color:var(--debt)">' + moneyBare(totDebt) + '</b><small>مجموع الديون</small></div>' +
    '<div class="ms"><b style="color:var(--paid)">' + moneyBare(totPay) + '</b><small>مجموع المدفوع</small></div>' +
    '<div class="ms"><b>' + mine.length + '</b><small>حركة</small></div>';

  $('#custLinks').innerHTML = c.phone
    ? '<a class="btn btn-ghost" href="tel:' + esc(c.phone) + '">' + icon('phone') + ' اتصال</a>' +
      '<a class="btn btn-ghost" href="https://wa.me/' + esc(String(c.phone).replace(/\D/g, '')) + '" target="_blank" rel="noopener">' + icon('chat') + ' واتساب</a>'
    : '';

  $('#custTxCount').textContent = mine.length;
  const box = $('#custTx');
  let html = '', lastDay = '';
  mine.forEach((t, i) => {
    const d = dayLabel(t.createdAt);
    if (d !== lastDay) { html += '<div class="day-sep">' + d + '</div>'; lastDay = d; }
    const isBread = t.source === 'bread';
    const cls = isBread ? 'tx-bread' : (t.type === 'debt' ? 'tx-debt' : 'tx-pay');
    const ic = isBread ? 'bread' : (t.type === 'debt' ? 'minus-c' : 'plus-c');
    html += '<div class="tx ' + cls + '" style="animation-delay:' + Math.min(i * 22, 380) + 'ms">' +
      '<span class="tx-ic">' + icon(ic) + '</span>' +
      '<span class="tx-info"><b>' + (t.type === 'debt' ? '+' : '−') + ' ' + moneyBare(t.amount) + '</b>' +
        '<small>' + esc(t.note || (t.type === 'debt' ? 'دين' : 'دفعة')) + ' · ' + timeAgo(t.createdAt) + '</small></span>' +
      '<span class="tx-acts">' +
        '<button class="tx-act" data-edit-tx="' + t.id + '" aria-label="تعديل">' + icon('edit') + '</button>' +
        '<button class="tx-act danger" data-del-tx="' + t.id + '" aria-label="حذف">' + icon('trash') + '</button>' +
      '</span></div>';
  });
  box.innerHTML = html;
  $('#custEmpty').hidden = mine.length > 0;
}
function txSheet(type) {
  const c = S.currentCustomer;
  const isDebt = type === 'debt';
  openSheet(isDebt ? 'إضافة دين على ' + c.name : 'تسجيل دفعة من ' + c.name,
    '<div class="field-wrap"><span class="field-ic" data-ic="cash"></span>' +
    '<input id="shAmount" class="field field-num" inputmode="decimal" placeholder="المبلغ">' +
    '<span class="field-suffix">' + esc(S.settings.currency) + '</span></div>' +
    '<div class="chips" id="shChips">' + [500, 1000, 2000, 5000, 10000].map(v => '<button class="chip" data-amt="' + v + '">' + v.toLocaleString('en-US') + '</button>').join('') + '</div>' +
    '<input id="shNote" class="field field-note" placeholder="ملاحظة (اختياري)">' +
    '<button class="btn ' + (isDebt ? 'btn-danger' : 'btn-ok') + ' btn-lg" id="shSave">' +
    icon(isDebt ? 'minus-c' : 'plus-c') + ' ' + (isDebt ? 'تسجيل الدين' : 'تسجيل الدفعة') + '</button>');
  setTimeout(() => $('#shAmount').focus(), 120);
  $('#shChips').onclick = e => {
    const b = e.target.closest('[data-amt]'); if (!b) return;
    $('#shAmount').value = (parseAmount($('#shAmount').value) || 0) + Number(b.dataset.amt);
  };
  $('#shSave').onclick = async () => {
    const a = parseAmount($('#shAmount').value);
    if (!a) { toast('أدخل مبلغاً صحيحاً', 'err'); return; }
    await addTx(c.id, type, a, $('#shNote').value.trim());
    closeSheet(); renderCustomer(); toast('تم الحفظ', 'ok'); maybeAutoBackup();
  };
}
function editTxSheet(id) {
  const t = S.tx.find(x => x.id === id);
  if (!t) return;
  openSheet('تعديل الحركة',
    '<div class="seg" id="etType">' +
      '<button class="seg-btn ' + (t.type === 'debt' ? 'is-on' : '') + '" data-type="debt">' + icon('minus-c') + ' دين</button>' +
      '<button class="seg-btn ' + (t.type === 'payment' ? 'is-on' : '') + '" data-type="payment">' + icon('plus-c') + ' دفعة</button>' +
    '</div>' +
    '<div class="field-wrap"><span class="field-ic" data-ic="cash"></span>' +
    '<input id="etAmount" class="field field-num" inputmode="decimal" value="' + esc(t.amount) + '">' +
    '<span class="field-suffix">' + esc(S.settings.currency) + '</span></div>' +
    '<input id="etNote" class="field field-note" placeholder="ملاحظة" value="' + esc(t.note || '') + '">' +
    '<button class="btn btn-primary btn-lg" id="etSave">' + icon('save') + ' حفظ التعديل</button>');
  let type = t.type;
  $('#etType').onclick = e => {
    const b = e.target.closest('[data-type]'); if (!b) return;
    type = b.dataset.type;
    $$('#etType .seg-btn').forEach(x => x.classList.toggle('is-on', x === b));
  };
  $('#etSave').onclick = async () => {
    const a = parseAmount($('#etAmount').value);
    if (!a) { toast('مبلغ غير صحيح', 'err'); return; }
    t.amount = a; t.type = type; t.note = $('#etNote').value.trim();
    await DB.put('transactions', t);
    recomputeBalances(); closeSheet(); renderCustomer(); toast('تم تعديل الحركة', 'ok');
  };
}
async function settleAll() {
  const c = S.currentCustomer, b = balOf(c.id);
  if (b <= 0) { toast('لا يوجد دين مستحق', 'err'); return; }
  const ok = await confirmBox('تسديد كامل الحساب', 'سيتم تسجيل دفعة بقيمة ' + money(b) + ' لحساب ' + c.name + '، ليصبح الرصيد صفراً.', false);
  if (!ok) return;
  await addTx(c.id, 'payment', b, 'تسديد كامل الحساب');
  renderCustomer(); toast('تم تسديد الحساب بالكامل', 'ok'); maybeAutoBackup();
}
function customerMenu() {
  const c = S.currentCustomer;
  openSheet('خيارات ' + c.name,
    '<label class="lbl">الاسم</label><input class="field" id="cmName" value="' + esc(c.name) + '">' +
    '<label class="lbl">رقم الهاتف</label><input class="field ltr" id="cmPhone" inputmode="tel" value="' + esc(c.phone || '') + '" placeholder="09xxxxxxxx">' +
    '<label class="lbl">ملاحظة</label><input class="field" id="cmNote" value="' + esc(c.note || '') + '">' +
    '<button class="btn btn-primary" id="cmSave">' + icon('save') + ' حفظ البيانات</button>' +
    '<button class="btn btn-ghost" id="cmArchive">' + icon('inbox') + ' ' + (c.archived ? 'إلغاء الأرشفة' : 'أرشفة العميل') + '</button>' +
    '<button class="btn btn-danger" id="cmDelete">' + icon('trash') + ' حذف العميل وكل حركاته</button>');
  $('#cmSave').onclick = async () => {
    const nm = $('#cmName').value.trim();
    if (!nm) { toast('الاسم مطلوب', 'err'); return; }
    c.name = nm; c.norm = arNorm(nm);
    c.phone = $('#cmPhone').value.trim(); c.note = $('#cmNote').value.trim();
    await DB.put('customers', c);
    closeSheet(); renderCustomer(); toast('تم الحفظ', 'ok');
  };
  $('#cmArchive').onclick = async () => {
    c.archived = c.archived ? 0 : 1;
    await DB.put('customers', c);
    closeSheet(); toast(c.archived ? 'تمت الأرشفة' : 'أُلغيت الأرشفة', 'ok'); go('home');
  };
  $('#cmDelete').onclick = async () => {
    const ok = await confirmBox('حذف العميل', 'سيُحذف ' + c.name + ' مع كل حركاته وسجلات الخبز الخاصة به نهائياً.');
    if (!ok) return;
    for (const t of S.tx.filter(t => t.customerId === c.id)) await DB.del('transactions', t.id);
    for (const b of S.bread.filter(b => b.customerId === c.id)) await DB.del('bread', b.id);
    await DB.del('customers', c.id);
    S.tx = S.tx.filter(t => t.customerId !== c.id);
    S.bread = S.bread.filter(b => b.customerId !== c.id);
    S.customers = S.customers.filter(x => x.id !== c.id);
    recomputeBalances(); closeSheet(); toast('تم حذف العميل', 'ok'); go('home');
  };
}

/* ─────────────────── الخبز ─────────────────── */
function setBreadCustomer(c, isNew, name) {
  S.breadSel.customer = c;
  S.breadSel.isNew = !!isNew;
  S.breadSel.newName = name || '';
  $('#bName').value = c ? c.name : (name || '');
  $('#bSuggest').hidden = true;
  $('#bClear').hidden = !$('#bName').value;
}
function breadTotal() {
  return (Number(S.settings.breadPrice) || 0) * S.breadSel.count;
}
function renderBreadForm() {
  $('#bCount').textContent = S.breadSel.count;
  $('#bTotal').textContent = moneyBare(breadTotal());
}
function renderBread() {
  renderTicker();
  const d = S.breadSel.date;
  $('#breadDate').value = d;
  $('#breadDateLabel').textContent = d === today() ? 'اليوم' : new Date(d).toLocaleDateString('ar-EG', { weekday: 'long', day: 'numeric', month: 'long' });
  renderBreadForm();

  const rows = S.bread.filter(b => b.date === d).sort((a, b) => (b.createdAt || '').localeCompare(a.createdAt || ''));
  const count = rows.reduce((s, r) => s + Number(r.count || 0), 0);
  const paid  = rows.filter(r => r.paid).reduce((s, r) => s + Number(r.total || 0), 0);
  const due   = rows.filter(r => !r.paid).reduce((s, r) => s + Number(r.total || 0), 0);
  $('#bStatCount').textContent = count;
  $('#bStatPaid').textContent  = moneyBare(paid);
  $('#bStatDue').textContent   = moneyBare(due);
  $('#bStatTotal').textContent = moneyBare(paid + due);
  $('#bLogCount').textContent  = rows.length;

  $('#breadLog').innerHTML = rows.map((r, i) =>
    '<div class="tx ' + (r.paid ? 'tx-pay' : 'tx-bread') + '" style="animation-delay:' + Math.min(i * 22, 380) + 'ms">' +
      '<span class="tx-ic">' + icon('bread') + '</span>' +
      '<span class="tx-info"><b>' + esc(r.customerName) + ' · ' + r.count + ' ربطة</b>' +
        '<small>' + moneyBare(r.total) + ' ' + esc(S.settings.currency) + ' · ' + (r.paid ? 'مدفوع' : 'على الحساب') + '</small></span>' +
      '<span class="tx-acts">' +
        '<button class="tx-act" data-bread-toggle="' + r.id + '" aria-label="تبديل الدفع">' + icon(r.paid ? 'book' : 'check') + '</button>' +
        '<button class="tx-act danger" data-bread-del="' + r.id + '" aria-label="حذف">' + icon('trash') + '</button>' +
      '</span></div>'
  ).join('');
  $('#breadEmpty').hidden = rows.length > 0;
}
async function saveBread() {
  const price = Number(S.settings.breadPrice) || 0;
  if (!price) { toast('حدّد سعر ربطة الخبز من لوحة الإدارة أولاً', 'err'); return; }
  const nameTyped = $('#bName').value.trim();
  if (!S.breadSel.customer && !nameTyped) { toast('اكتب اسم الزبون', 'err'); $('#bName').focus(); return; }
  const c = S.breadSel.customer || await findOrCreateCustomer(nameTyped);
  const total = price * S.breadSel.count;
  const rec = {
    customerId: c.id, customerName: c.name, count: S.breadSel.count,
    unitPrice: price, total: total, paid: S.breadSel.paid ? 1 : 0,
    date: S.breadSel.date, createdAt: nowISO(), txId: null,
  };
  if (!rec.paid) {
    const t = await addTx(c.id, 'debt', total, 'خبز × ' + rec.count, { source: 'bread' });
    rec.txId = t.id;
  }
  rec.id = await DB.add('bread', rec);
  if (rec.txId) { const t = S.tx.find(x => x.id === rec.txId); if (t) { t.breadId = rec.id; await DB.put('transactions', t); } }
  S.bread.push(rec);
  setBreadCustomer(null, false, '');
  S.breadSel.count = 1;
  renderBread();
  toast(rec.paid ? 'سُجّل الخبز (مدفوع)' : 'سُجّل الخبز على حساب ' + c.name, 'ok');
  maybeAutoBackup();
}
async function toggleBreadPaid(id) {
  const r = S.bread.find(b => b.id === id);
  if (!r) return;
  if (r.paid) {
    const t = await addTx(r.customerId, 'debt', r.total, 'خبز × ' + r.count, { source: 'bread', breadId: r.id });
    r.paid = 0; r.txId = t.id;
    toast('حُوّل إلى حساب ' + r.customerName, 'ok');
  } else {
    if (r.txId) await removeTx(r.txId);
    r.paid = 1; r.txId = null;
    toast('سُجّل كمدفوع', 'ok');
  }
  await DB.put('bread', r);
  renderBread();
}
async function deleteBread(id) {
  const r = S.bread.find(b => b.id === id);
  if (!r) return;
  const ok = await confirmBox('حذف السجل', 'حذف تسجيل ' + r.customerName + ' (' + r.count + ' ربطة)؟' + (r.txId ? ' سيُحذف الدين المرتبط به أيضاً.' : ''));
  if (!ok) return;
  if (r.txId) await removeTx(r.txId);
  await DB.del('bread', id);
  S.bread = S.bread.filter(b => b.id !== id);
  renderBread(); toast('تم الحذف', 'ok');
}

/* ─────────────────── لوحة الإدارة ─────────────────── */
function renderKeypad() {
  const keys = ['1', '2', '3', '4', '5', '6', '7', '8', '9', 'clr', '0', 'del'];
  $('#keypad').innerHTML = keys.map(k => {
    if (k === 'del') return '<button class="key key-fn" data-k="del">' + icon('back') + '</button>';
    if (k === 'clr') return '<button class="key key-fn" data-k="clr">مسح</button>';
    return '<button class="key" data-k="' + k + '">' + k + '</button>';
  }).join('');
}
function renderPinDots() {
  $$('#pinDots i').forEach((d, i) => d.classList.toggle('on', i < S.pinBuf.length));
}
function pinPress(k) {
  if (k === 'del') S.pinBuf = S.pinBuf.slice(0, -1);
  else if (k === 'clr') S.pinBuf = '';
  else if (S.pinBuf.length < 4) S.pinBuf += k;
  renderPinDots();
  $('#lockErr').hidden = true;
  if (S.pinBuf.length === 4) {
    setTimeout(() => {
      if (hash(S.pinBuf) === S.settings.pin) { S.adminOpen = true; S.pinBuf = ''; renderPinDots(); renderAdmin(); }
      else { $('#lockErr').hidden = false; S.pinBuf = ''; renderPinDots(); }
    }, 160);
  }
}
function renderAdmin() {
  renderTicker();
  $('#lockGate').hidden = S.adminOpen;
  $('#adminBody').hidden = !S.adminOpen;
  $('#adminLock').hidden = !S.adminOpen;
  $('#adminSub').textContent = S.adminOpen ? 'التحكم الكامل' : 'محمية برمز';
  if (!S.adminOpen) { renderKeypad(); renderPinDots(); paintIcons($('#lockGate')); return; }

  const due = S.customers.filter(c => !c.archived).reduce((s, c) => s + Math.max(0, balOf(c.id)), 0);
  $('#aStatCust').textContent  = S.customers.length;
  $('#aStatTx').textContent    = S.tx.length;
  $('#aStatBread').textContent = S.bread.length;
  $('#aStatDue').textContent   = moneyBare(due);
  $('#appVer').textContent     = APP_VERSION;

  $('#setShopName').value   = S.settings.shopName || '';
  $('#setCurrency').value   = S.settings.currency || '';
  $('#setBreadPrice').value = S.settings.breadPrice || '';
  $('#setTicker').value     = S.settings.ticker || '';
  $('#setTickerOn').checked = !!S.settings.tickerOn;
  $('#setTgToken').value    = S.settings.tgToken || '';
  $('#setTgChat').value     = S.settings.tgChat || '';
  $('#setTgAuto').checked   = !!S.settings.tgAuto;

  $('#curChips').innerHTML = ['ل.س', '₺', '$', '€'].map(c =>
    '<button class="chip ' + (c === S.settings.currency ? 'is-on' : '') + '" data-cur="' + c + '">' + c + '</button>').join('');

  renderBannerAdmin();
  renderAdminCustomers();
  paintIcons($('#adminBody'));
}
function renderBannerAdmin() {
  const list = S.settings.banners || [];
  $('#bannerList').innerHTML = list.length ? list.map((b, i) =>
    '<div class="bn-row"><div><b>' + esc(b.text) + '</b>' + (b.sub ? '<small>' + esc(b.sub) + '</small>' : '') + '</div>' +
    '<button class="tx-act danger" data-bn-del="' + i + '">' + icon('trash') + '</button></div>'
  ).join('') : '<p class="hint">لا توجد بنرات بعد.</p>';
}
function renderAdminCustomers() {
  const q = arNorm($('#adminCustSearch') ? $('#adminCustSearch').value : '');
  let list = S.customers.slice();
  if (q) list = list.filter(c => c.norm.indexOf(q) !== -1);
  list.sort((a, b) => balOf(b.id) - balOf(a.id));
  $('#adminCustList').innerHTML = list.slice(0, 60).map(c => {
    const b = balOf(c.id);
    return '<div class="bn-row"><div><b>' + esc(c.name) + (c.archived ? ' (مؤرشف)' : '') + '</b>' +
      '<small style="color:' + (b > 0 ? 'var(--debt)' : 'var(--paid)') + '">' + moneyBare(Math.abs(b)) + ' ' + esc(S.settings.currency) + ' ' + (b > 0 ? 'عليه' : b < 0 ? 'له' : '') + '</small></div>' +
      '<button class="tx-act" data-admin-open="' + c.id + '">' + icon('arrow') + '</button></div>';
  }).join('') || '<p class="hint">لا نتائج.</p>';
  paintIcons($('#adminCustList'));
}

/* ─────────────────── النسخ الاحتياطي ─────────────────── */
function backupJSON() {
  return JSON.stringify({
    app: 'hamado-market', version: APP_VERSION, exportedAt: nowISO(),
    settings: S.settings, customers: S.customers, transactions: S.tx, bread: S.bread,
  }, null, 1);
}
function backupName() {
  return 'hamado-backup-' + today() + '.json';
}
function doDownload() {
  const r = Native.saveFile(backupName(), backupJSON());
  toast(String(r).indexOf('ERR') === 0 ? 'تعذّر الحفظ: ' + r : 'تم حفظ النسخة في الجهاز', String(r).indexOf('ERR') === 0 ? 'err' : 'ok');
}
function doShare() {
  const r = Native.shareFile(backupName(), backupJSON());
  if (String(r).indexOf('ERR') === 0) toast('تعذّرت المشاركة: ' + r, 'err');
}
async function doTelegram(silent) {
  const tk = (S.settings.tgToken || '').trim(), ch = (S.settings.tgChat || '').trim();
  if (!tk || !ch) { if (!silent) toast('أدخل توكن البوت ومعرّف الدردشة أولاً', 'err'); return false; }
  if (!silent) toast('جارٍ الإرسال…');
  try {
    const r = await Native.sendTelegram(tk, ch, backupName(), backupJSON());
    const ok = String(r) === 'OK';
    if (!silent) toast(ok ? 'وصلت النسخة إلى تيليجرام' : 'فشل الإرسال: ' + r, ok ? 'ok' : 'err');
    return ok;
  } catch (e) {
    if (!silent) toast('فشل الإرسال: ' + e, 'err');
    return false;
  }
}
async function maybeAutoBackup() {
  if (!S.settings.tgAuto) return;
  if (S.settings.lastAutoBackup === today()) return;
  const ok = await doTelegram(true);
  if (ok) await saveSetting('lastAutoBackup', today());
}
async function doRestore(file) {
  let data;
  try { data = JSON.parse(await file.text()); }
  catch (e) { toast('الملف غير صالح', 'err'); return; }
  if (!data || data.app !== 'hamado-market') { toast('هذا ليس ملف نسخة احتياطية للتطبيق', 'err'); return; }
  const ok = await confirmBox('استعادة نسخة احتياطية',
    'سيتم استبدال كل البيانات الحالية ببيانات الملف (' + (data.customers || []).length + ' عميل، ' + (data.transactions || []).length + ' حركة).');
  if (!ok) return;
  await Promise.all([DB.clear('customers'), DB.clear('transactions'), DB.clear('bread'), DB.clear('settings')]);
  for (const c of (data.customers || [])) await DB.put('customers', c);
  for (const t of (data.transactions || [])) await DB.put('transactions', t);
  for (const b of (data.bread || [])) await DB.put('bread', b);
  const st = data.settings || {};
  for (const k of Object.keys(st)) await DB.put('settings', { key: k, value: st[k] });
  await loadAll();
  toast('تمت الاستعادة بنجاح', 'ok');
  go('home');
}

/* ─────────────────── ربط الأحداث ─────────────────── */
function bind() {
  /* التنقل */
  $('#tabbar').addEventListener('click', e => {
    const b = e.target.closest('[data-go]');
    if (b) go(b.dataset.go);
  });
  $('#fabAdd').onclick = () => {
    go('home');
    setTimeout(() => { $('#quickCard').scrollIntoView({ behavior: 'smooth', block: 'center' }); $('#qName').focus(); }, 120);
  };
  $('#btnRefresh').onclick = async () => { await loadAll(); renderHome(); toast('تم التحديث', 'ok'); };
  $('#custBack').onclick = () => go('home');
  $('#custMenu').onclick = customerMenu;

  /* التسجيل السريع */
  const qName = $('#qName');
  qName.addEventListener('input', () => {
    const v = qName.value;
    $('#qClear').hidden = !v;
    if (arNorm(v).length < 2) { $('#suggestBox').hidden = true; return; }
    const res = searchCustomers(v);
    $('#suggestBox').innerHTML = suggestHTML(v, res, true);
    $('#suggestBox').hidden = false;
    paintIcons($('#suggestBox'));
  });
  $('#qClear').onclick = () => { qName.value = ''; $('#qClear').hidden = true; $('#suggestBox').hidden = true; qName.focus(); };
  $('#suggestBox').addEventListener('click', e => {
    const pick = e.target.closest('[data-pick]');
    const nw = e.target.closest('[data-new]');
    if (pick) { const c = S.customers.find(x => x.id === Number(pick.dataset.pick)); setQuickCustomer(c, false); qName.value = c.name; }
    else if (nw) { setQuickCustomer(null, true, qName.value.trim()); }
  });
  $('#chipClear').onclick = () => { setQuickCustomer(null, false); qName.value = ''; $('#qClear').hidden = true; };
  $('#qType').addEventListener('click', e => {
    const b = e.target.closest('[data-type]'); if (!b) return;
    S.quick.type = b.dataset.type;
    $$('#qType .seg-btn').forEach(x => x.classList.toggle('is-on', x === b));
  });
  $('#qQuickAmounts').addEventListener('click', e => {
    const b = e.target.closest('[data-amt]'); if (!b) return;
    $('#qAmount').value = (parseAmount($('#qAmount').value) || 0) + Number(b.dataset.amt);
    b.classList.add('is-on');
    setTimeout(() => b.classList.remove('is-on'), 400);
  });
  $('#qSave').onclick = quickSave;
  $('#qAmount').addEventListener('keydown', e => { if (e.key === 'Enter') quickSave(); });
  $('#qNote').addEventListener('keydown', e => { if (e.key === 'Enter') quickSave(); });

  /* قائمة العملاء */
  $('#homeTabs').addEventListener('click', e => {
    const b = e.target.closest('[data-tab]'); if (!b) return;
    S.tab = b.dataset.tab;
    $$('#homeTabs .tab').forEach(x => x.classList.toggle('is-on', x === b));
    renderHome();
  });
  $('#listSearch').addEventListener('input', e => {
    S.listQuery = e.target.value;
    $('#listSearchClear').hidden = !e.target.value;
    renderHome();
  });
  $('#listSearchClear').onclick = () => { $('#listSearch').value = ''; S.listQuery = ''; $('#listSearchClear').hidden = true; renderHome(); };
  $('#customerCards').addEventListener('click', e => {
    const b = e.target.closest('[data-cust]'); if (!b) return;
    openCustomer(Number(b.dataset.cust));
  });

  /* صفحة العميل */
  $('#custAddDebt').onclick = () => txSheet('debt');
  $('#custAddPay').onclick  = () => txSheet('payment');
  $('#custSettle').onclick  = settleAll;
  $('#custTx').addEventListener('click', async e => {
    const ed = e.target.closest('[data-edit-tx]');
    const dl = e.target.closest('[data-del-tx]');
    if (ed) editTxSheet(Number(ed.dataset.editTx));
    else if (dl) {
      const ok = await confirmBox('حذف الحركة', 'سيُحذف هذا السجل ويتغيّر رصيد العميل تبعاً لذلك.');
      if (!ok) return;
      const id = Number(dl.dataset.delTx);
      const t = S.tx.find(x => x.id === id);
      if (t && t.breadId) {
        const b = S.bread.find(x => x.id === t.breadId);
        if (b) { b.paid = 1; b.txId = null; await DB.put('bread', b); }
      }
      await removeTx(id);
      renderCustomer(); toast('تم حذف الحركة', 'ok');
    }
  });

  /* الخبز */
  $('#breadDate').addEventListener('change', e => { S.breadSel.date = e.target.value || today(); renderBread(); });
  $('#breadCal').onclick = () => { const d = $('#breadDate'); if (d.showPicker) d.showPicker(); else d.focus(); };
  const bName = $('#bName');
  bName.addEventListener('input', () => {
    const v = bName.value;
    $('#bClear').hidden = !v;
    S.breadSel.customer = null;
    if (arNorm(v).length < 2) { $('#bSuggest').hidden = true; return; }
    $('#bSuggest').innerHTML = suggestHTML(v, searchCustomers(v), false);
    $('#bSuggest').hidden = false;
    paintIcons($('#bSuggest'));
  });
  $('#bClear').onclick = () => { setBreadCustomer(null, false, ''); $('#bClear').hidden = true; bName.focus(); };
  $('#bSuggest').addEventListener('click', e => {
    const p = e.target.closest('[data-pick]'); if (!p) return;
    setBreadCustomer(S.customers.find(x => x.id === Number(p.dataset.pick)), false);
  });
  $('#bMinus').onclick = () => { S.breadSel.count = Math.max(1, S.breadSel.count - 1); renderBreadForm(); };
  $('#bPlus').onclick  = () => { S.breadSel.count = Math.min(999, S.breadSel.count + 1); renderBreadForm(); };
  $('#bPaidSeg').addEventListener('click', e => {
    const b = e.target.closest('[data-paid]'); if (!b) return;
    S.breadSel.paid = b.dataset.paid === '1';
    $$('#bPaidSeg .seg-btn').forEach(x => x.classList.toggle('is-on', x === b));
  });
  $('#bSave').onclick = saveBread;
  $('#breadLog').addEventListener('click', e => {
    const t = e.target.closest('[data-bread-toggle]');
    const d = e.target.closest('[data-bread-del]');
    if (t) toggleBreadPaid(Number(t.dataset.breadToggle));
    else if (d) deleteBread(Number(d.dataset.breadDel));
  });

  /* الإدارة */
  $('#keypad').addEventListener('click', e => {
    const k = e.target.closest('[data-k]'); if (!k) return;
    pinPress(k.dataset.k);
  });
  $('#adminLock').onclick = () => { S.adminOpen = false; renderAdmin(); toast('تم قفل اللوحة'); };
  $('#adminScroll').addEventListener('click', e => {
    const h = e.target.closest('.acc-head'); if (!h) return;
    h.parentElement.classList.toggle('is-open');
  });
  $('#curChips').addEventListener('click', e => {
    const b = e.target.closest('[data-cur]'); if (!b) return;
    $('#setCurrency').value = b.dataset.cur;
    $$('#curChips .chip').forEach(x => x.classList.toggle('is-on', x === b));
  });
  $('#saveShop').onclick = async () => {
    await saveSetting('shopName', $('#setShopName').value.trim() || 'Hamado Market');
    await saveSetting('currency', $('#setCurrency').value.trim() || 'ل.س');
    await saveSetting('breadPrice', parseAmount($('#setBreadPrice').value) || 0);
    renderAdmin(); toast('حُفظت إعدادات المتجر', 'ok');
  };
  $('#savePin').onclick = async () => {
    const p = $('#setPin').value.trim();
    if (!/^\d{4}$/.test(p)) { toast('الرمز يجب أن يكون 4 أرقام', 'err'); return; }
    await saveSetting('pin', hash(p));
    $('#setPin').value = '';
    toast('تم تغيير رمز الحماية', 'ok');
  };
  $('#bnAdd').onclick = () => {
    const t = $('#bnText').value.trim();
    if (!t) { toast('اكتب نص البنر', 'err'); return; }
    S.settings.banners = (S.settings.banners || []).concat([{ text: t, sub: $('#bnSub').value.trim() }]);
    $('#bnText').value = ''; $('#bnSub').value = '';
    renderBannerAdmin(); paintIcons($('#bannerList'));
  };
  $('#bannerList').addEventListener('click', e => {
    const d = e.target.closest('[data-bn-del]'); if (!d) return;
    S.settings.banners.splice(Number(d.dataset.bnDel), 1);
    renderBannerAdmin(); paintIcons($('#bannerList'));
  });
  $('#saveAds').onclick = async () => {
    await saveSetting('banners', S.settings.banners || []);
    await saveSetting('ticker', $('#setTicker').value.trim());
    await saveSetting('tickerOn', $('#setTickerOn').checked);
    renderTicker(); toast('حُفظت الإعلانات', 'ok');
  };
  $('#adminCustSearch').addEventListener('input', renderAdminCustomers);
  $('#adminCustList').addEventListener('click', e => {
    const b = e.target.closest('[data-admin-open]'); if (!b) return;
    openCustomer(Number(b.dataset.adminOpen));
  });
  $('#bkDownload').onclick = doDownload;
  $('#bkShare').onclick = doShare;
  $('#saveTg').onclick = async () => {
    await saveSetting('tgToken', $('#setTgToken').value.trim());
    await saveSetting('tgChat', $('#setTgChat').value.trim());
    await saveSetting('tgAuto', $('#setTgAuto').checked);
    toast('حُفظت إعدادات تيليجرام', 'ok');
  };
  $('#bkTelegram').onclick = () => doTelegram(false);
  $('#bkRestore').onclick = () => $('#bkFile').click();
  $('#bkFile').addEventListener('change', e => { if (e.target.files[0]) doRestore(e.target.files[0]); e.target.value = ''; });
  $('#wipeBread').onclick = async () => {
    const ok = await confirmBox('حذف سجلات الخبز', 'سيُحذف كل سجل الخبز. الديون المسجّلة على العملاء تبقى كما هي.');
    if (!ok) return;
    await DB.clear('bread'); S.bread = [];
    renderAdmin(); toast('حُذفت سجلات الخبز', 'ok');
  };
  $('#wipeAll').onclick = async () => {
    const ok = await confirmBox('حذف كل البيانات', 'سيُحذف كل العملاء والحركات وسجلات الخبز نهائياً ولا يمكن التراجع.');
    if (!ok) return;
    const ok2 = await confirmBox('تأكيد أخير', 'هل أخذت نسخة احتياطية؟ بعد هذه الخطوة لا يمكن استرجاع البيانات إلا من ملف نسخة احتياطية.');
    if (!ok2) return;
    await Promise.all([DB.clear('customers'), DB.clear('transactions'), DB.clear('bread')]);
    S.customers = []; S.tx = []; S.bread = []; recomputeBalances();
    renderAdmin(); toast('حُذفت كل البيانات', 'ok');
  };

  /* الأوراق المنبثقة */
  $('#sheetBackdrop').onclick = closeSheet;
  document.addEventListener('click', e => {
    if (!e.target.closest('#suggestBox') && !e.target.closest('#qName')) $('#suggestBox').hidden = true;
    if (!e.target.closest('#bSuggest') && !e.target.closest('#bName')) $('#bSuggest').hidden = true;
  });
  /* زر الرجوع في أندرويد */
  window.addEventListener('hm-back', () => {
    if (!$('#sheetWrap').hidden) { closeSheet(); return; }
    if (!$('#confirmWrap').hidden) { $('#confirmNo').click(); return; }
    if (S.view !== 'home') { go('home'); return; }
    if (typeof AndroidBridge !== 'undefined' && AndroidBridge.exitApp) AndroidBridge.exitApp();
  });
}

/* ─────────────────── الإقلاع ─────────────────── */
(async function init() {
  paintIcons();
  $('#brandMark').innerHTML = icon('store');
  $('#btnRefresh').innerHTML = icon('refresh');
  $('#custBack').innerHTML = icon('back');
  $('#custMenu').innerHTML = icon('more');
  $('#breadCal').innerHTML = icon('calendar');
  $('#adminLock').innerHTML = icon('lock');
  $('#qClear').innerHTML = icon('x');
  $('#listSearchClear').innerHTML = icon('x');
  $('#bClear').innerHTML = icon('x');
  $('#chipClear').innerHTML = icon('x');
  $('#bMinus').innerHTML = icon('minus');
  $('#bPlus').innerHTML = icon('plus');
  try {
    await loadAll();
  } catch (e) {
    toast('تعذّر فتح قاعدة البيانات المحلية', 'err');
  }
  bind();
  $('#breadDate').value = S.breadSel.date;
  go('home');
  maybeAutoBackup();
})();

})();
