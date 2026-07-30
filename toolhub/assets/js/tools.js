/* =====================================================
   بوابة الأدوات — Shared JavaScript
   ===================================================== */

/* ── Mobile nav ────────────────────────────────────────── */
function toggleMobileNav() {
  const nav = document.getElementById('mobileNav');
  const overlay = document.getElementById('navOverlay');
  const btn = document.getElementById('hamburger');
  const open = nav.getAttribute('hidden') !== null;
  if (open) {
    nav.removeAttribute('hidden');
    overlay.classList.add('visible');
    btn.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  } else {
    nav.setAttribute('hidden', '');
    overlay.classList.remove('visible');
    btn.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }
}

function toggleSearch() {
  const s = document.getElementById('mobileSearch');
  if (s.hasAttribute('hidden')) {
    s.removeAttribute('hidden');
    s.querySelector('input')?.focus();
  } else {
    s.setAttribute('hidden', '');
  }
}

/* Close mobile nav on ESC */
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    const nav = document.getElementById('mobileNav');
    if (nav && !nav.hasAttribute('hidden')) toggleMobileNav();
  }
});

/* ── Currency Converter ─────────────────────────────────── */
function convertCurrency() {
  const rates = window._fxRates || {};
  const amt  = parseFloat(document.getElementById('convAmt')?.value || 0);
  const from = document.getElementById('convFrom')?.value;
  const to   = document.getElementById('convTo')?.value;
  const out  = document.getElementById('convResult');
  if (!out) return;

  if (!amt || !from || !to) { out.textContent = 'أدخل قيمة صحيحة'; out.removeAttribute('hidden'); return; }

  // Rates are USD-based; convert via USD
  const fromRate = from === 'USD' ? 1 : (rates[from] || 1);
  const toRate   = to   === 'USD' ? 1 : (rates[to]   || 1);
  const result   = (amt / fromRate) * toRate;

  const dec = ['JPY','INR','TRY'].includes(to) ? 1 : (['KWD','BHD','OMR','JOD'].includes(to) ? 4 : 2);
  out.innerHTML = `<span>${fmt(amt, 2)} ${from}</span> = <strong style="font-size:1.3em;color:var(--c-gold)">${fmt(result, dec)} ${to}</strong>`;
  out.removeAttribute('hidden');
}

function swapConv() {
  const f = document.getElementById('convFrom');
  const t = document.getElementById('convTo');
  if (!f || !t) return;
  [f.value, t.value] = [t.value, f.value];
  convertCurrency();
}

/* ── Gold Calculator ─────────────────────────────────────── */
function calcGold() {
  const grams   = parseFloat(document.getElementById('goldGrams')?.value || 0);
  const purity  = parseFloat(document.getElementById('goldPurity')?.value || 1);
  const currency= document.getElementById('goldCurrency')?.value || 'SAR';
  const out     = document.getElementById('goldResult');
  if (!out) return;

  const sarG  = (window._goldSarG || 0) * purity;
  const aedG  = (window._goldAedG || 0) * purity;
  const usdG  = (window._goldUsdG || 0) * purity;
  const egpG  = (window._goldEgpG || 0) * purity;

  const totSar = sarG * grams;
  const totAed = aedG * grams;
  const totUsd = usdG * grams;
  const totEgp = egpG * grams;

  out.innerHTML = `
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:.5rem;text-align:center">
      <div><div style="font-size:1.4rem;font-weight:900;color:var(--c-gold)">${fmt(totSar,2)}</div><div style="font-size:12px;color:var(--c-text2)">ريال سعودي</div></div>
      <div><div style="font-size:1.4rem;font-weight:900;color:var(--c-cyan)">${fmt(totAed,2)}</div><div style="font-size:12px;color:var(--c-text2)">درهم إماراتي</div></div>
      <div><div style="font-size:1.4rem;font-weight:900;color:var(--c-emerald)">${fmt(totUsd,2)}</div><div style="font-size:12px;color:var(--c-text2)">دولار أمريكي</div></div>
      <div><div style="font-size:1.4rem;font-weight:900;color:var(--c-orange)">${fmt(totEgp,2)}</div><div style="font-size:12px;color:var(--c-text2)">جنيه مصري</div></div>
    </div>`;
  out.removeAttribute('hidden');
}

/* ── Building Calculators ────────────────────────────────── */
function calcConcrete() {
  const l   = parseFloat(document.getElementById('c_len')?.value || 0);
  const w   = parseFloat(document.getElementById('c_wid')?.value || 0);
  const thk = parseFloat(document.getElementById('c_thk')?.value || 0) / 100; // cm → m
  const out = document.getElementById('concreteResult');
  if (!out) return;

  const vol     = l * w * thk;
  const cement  = Math.ceil(vol * 6.5);   // bags per m³ (1:2:4 mix)
  const sand    = +(vol * 0.44).toFixed(2);   // m³
  const gravel  = +(vol * 0.88).toFixed(2);   // m³

  out.innerHTML = `
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
      <div>📦 حجم الخرسانة</div><strong>${vol.toFixed(2)} م³</strong>
      <div>🏗️ أكياس الأسمنت</div><strong>${cement} كيس (50كجم)</strong>
      <div>🪨 الرمل</div><strong>${sand} م³</strong>
      <div>🪨 الحصى</div><strong>${gravel} م³</strong>
    </div>`;
  out.removeAttribute('hidden');
}

function calcTiles() {
  const area  = parseFloat(document.getElementById('t_area')?.value  || 0);
  const size  = parseFloat(document.getElementById('t_size')?.value  || 60);
  const waste = parseFloat(document.getElementById('t_waste')?.value || 10);
  const out   = document.getElementById('tilesResult');
  if (!out) return;

  const tileArea   = (size / 100) ** 2;           // m² per tile
  const tileCount  = Math.ceil((area / tileArea) * (1 + waste / 100));
  const boxes      = Math.ceil(tileCount / 4);     // ~4 tiles per box common estimate

  out.innerHTML = `
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
      <div>🟫 عدد البلاطات</div><strong>${tileCount} بلاطة</strong>
      <div>📦 عدد الكراتين</div><strong>~${boxes} كرتون</strong>
      <div>📐 مساحة البلاطة</div><strong>${size}×${size} سم</strong>
      <div>♻️ نسبة الهدر</div><strong>${waste}%</strong>
    </div>`;
  out.removeAttribute('hidden');
}

function calcBricks() {
  const len = parseFloat(document.getElementById('b_len')?.value || 0);
  const hgt = parseFloat(document.getElementById('b_hgt')?.value || 0);
  const thk = parseFloat(document.getElementById('b_thk')?.value || 0.20);
  const out = document.getElementById('bricksResult');
  if (!out) return;

  const area   = len * hgt;            // wall face area m²
  const brkM2  = thk <= 0.12 ? 50 : (thk <= 0.20 ? 95 : 115); // bricks per m²
  const bricks = Math.ceil(area * brkM2 * 1.05); // 5% waste
  const mortar = +(area * thk * 0.3).toFixed(2); // m³ of mortar

  out.innerHTML = `
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
      <div>🧱 عدد الطوب</div><strong>${bricks.toLocaleString()} طوبة</strong>
      <div>📐 مساحة الجدار</div><strong>${area.toFixed(1)} م²</strong>
      <div>🪣 الملاط اللازم</div><strong>${mortar} م³</strong>
      <div>📏 سماكة الجدار</div><strong>${thk*100} سم</strong>
    </div>`;
  out.removeAttribute('hidden');
}

/* ── Solar Preview Calculator ────────────────────────────── */
// (Quick homepage version; full version is in solar.php)
const solarCfgHome = {
  sa: {rate:0.18, sun:8.5, currency:'ريال'},
  ae: {rate:0.23, sun:7.5, currency:'درهم'},
  eg: {rate:0.10, sun:8.0, currency:'جنيه'},
  kw: {rate:0.02, sun:8.0, currency:'دينار'},
};

function calcSolar() {
  const bill    = parseFloat(document.getElementById('solarBill')?.value    || 500);
  const cKey    = document.getElementById('solarCountry')?.value || 'sa';
  const cfg     = solarCfgHome[cKey] || solarCfgHome.sa;
  const out     = document.getElementById('solarResult');
  if (!out) return;

  const kwhMonth  = bill / cfg.rate;
  const kwhDay    = kwhMonth / 30;
  const systemKw  = kwhDay / (cfg.sun * 0.8);
  const numPanels = Math.ceil(systemKw * 1000 / 400);
  const actualKw  = (numPanels * 400) / 1000;
  const cost      = Math.ceil(numPanels * 1500 + actualKw * 1200 + numPanels * 200 + numPanels * 1500 * 0.15);
  const yearlySave= bill * 12;
  const payback   = Math.ceil(cost / yearlySave);

  out.innerHTML = `
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:.75rem;text-align:center;padding:.75rem 0">
      <div><div style="font-size:2rem;font-weight:900;color:var(--c-gold)">${numPanels}</div><div style="font-size:12px;color:var(--c-text2)">لوح شمسي</div></div>
      <div><div style="font-size:2rem;font-weight:900;color:var(--c-emerald)">${actualKw.toFixed(1)}</div><div style="font-size:12px;color:var(--c-text2)">كيلوواط</div></div>
      <div><div style="font-size:2rem;font-weight:900;color:var(--c-primary)">${payback}</div><div style="font-size:12px;color:var(--c-text2)">سنوات استرداد</div></div>
      <div><div style="font-size:2rem;font-weight:900;color:var(--c-purple)">${cost.toLocaleString()}</div><div style="font-size:12px;color:var(--c-text2)">${cfg.currency} تقريباً</div></div>
    </div>
    <div style="text-align:center"><a href="/solar" style="color:var(--c-primary);font-size:13px">احسب بتفاصيل أكثر ←</a></div>`;
  out.removeAttribute('hidden');
}

/* ── Ticker duplicate for seamless loop ──────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  const track = document.getElementById('tickerTrack');
  if (track && track.children.length > 0) {
    const clone = track.cloneNode(true);
    clone.id = '';
    clone.setAttribute('aria-hidden', 'true');
    track.parentNode.appendChild(clone);
    // Adjust animation duration based on content width
    const fullW = track.scrollWidth;
    const speed = Math.max(20, fullW / 40);
    track.style.animationDuration = speed + 's';
    if (clone.style !== undefined) clone.style.animationDuration = speed + 's';
  }
});

/* ── Calculator Tab Switcher ─────────────────────────────── */
function switchTab(group, id, btn) {
  // Hide all panels in group
  document.querySelectorAll(`[data-group="${group}"].calc-panel, [data-group="${group}"].tool-panel`).forEach(p => p.classList.remove('active'));
  document.querySelectorAll(`[data-group="${group}"] .calc-tab, [data-group="${group}"] .tool-tab`).forEach(b => b.classList.remove('active'));
  // Show target
  const panel = document.getElementById(id);
  if (panel) panel.classList.add('active');
  if (btn) btn.classList.add('active');
}

/* ── Number formatter ─────────────────────────────────────── */
function fmt(n, dec = 2) {
  if (!isFinite(n)) return '—';
  return n.toLocaleString('ar-SA', { minimumFractionDigits: dec, maximumFractionDigits: dec });
}

/* ── Image tools helpers ─────────────────────────────────── */
function readImageFile(input, imgId) {
  const file = input.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    const img = document.getElementById(imgId);
    if (img) { img.src = e.target.result; img.removeAttribute('hidden'); }
  };
  reader.readAsDataURL(file);
}

function compressImage(inputId, quality, outputId, infoId) {
  const input = document.getElementById(inputId);
  if (!input?.files[0]) { alert('اختر صورة أولاً'); return; }
  const file = input.files[0];
  const img = new Image();
  const url = URL.createObjectURL(file);
  img.onload = () => {
    URL.revokeObjectURL(url);
    const canvas = document.createElement('canvas');
    canvas.width  = img.naturalWidth;
    canvas.height = img.naturalHeight;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(img, 0, 0);
    canvas.toBlob(blob => {
      if (!blob) return;
      const outImg = document.getElementById(outputId);
      if (outImg) { outImg.src = URL.createObjectURL(blob); outImg.removeAttribute('hidden'); }
      const info = document.getElementById(infoId);
      if (info) {
        const ratio = ((1 - blob.size / file.size) * 100).toFixed(1);
        info.textContent = `الحجم الأصلي: ${(file.size/1024).toFixed(0)} KB → ${(blob.size/1024).toFixed(0)} KB (توفير ${ratio}%)`;
        info.removeAttribute('hidden');
      }
    }, file.type === 'image/png' ? 'image/png' : 'image/jpeg', quality / 100);
  };
  img.src = url;
}

function resizeImage(inputId, w, h, outputId) {
  const input = document.getElementById(inputId);
  if (!input?.files[0]) { alert('اختر صورة أولاً'); return; }
  const img = new Image();
  img.onload = () => {
    const tw = parseInt(w) || img.naturalWidth;
    const th = parseInt(h) || img.naturalHeight;
    const canvas = document.createElement('canvas');
    canvas.width = tw; canvas.height = th;
    canvas.getContext('2d').drawImage(img, 0, 0, tw, th);
    canvas.toBlob(blob => {
      const out = document.getElementById(outputId);
      if (out && blob) { out.src = URL.createObjectURL(blob); out.removeAttribute('hidden'); }
    }, 'image/jpeg', 0.92);
  };
  img.src = URL.createObjectURL(input.files[0]);
}

function downloadCanvas(canvasId, filename) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;
  const a = document.createElement('a');
  a.download = filename || 'image.png';
  a.href = canvas.toDataURL('image/png');
  a.click();
}
