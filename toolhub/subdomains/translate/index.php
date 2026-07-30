<?php define('MAIN_SITE', 'https://your-domain.com'); ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>مترجم فوري عربي — ترجمة مجانية</title>
<meta name="description" content="ترجمة فورية مجانية بين العربية والإنجليزية والفرنسية والألمانية — بدون تسجيل">
<meta name="robots" content="index,follow">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800;900&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{direction:rtl}
body{font-family:'Cairo',sans-serif;background:#0f172a;color:#e2e8f0;min-height:100vh}
.wrap{max-width:800px;margin:0 auto;padding:2rem 1rem}
.logo{text-align:center;margin-bottom:2rem}
.logo h1{font-size:2rem;font-weight:900;color:#fff}
.logo p{color:#94a3b8;font-size:.9rem;margin-top:.35rem}
.card{background:#1e293b;border:1px solid #334155;border-radius:16px;padding:1.5rem;margin-bottom:1.25rem}
.langs{display:flex;gap:.5rem;align-items:center;margin-bottom:.75rem;flex-wrap:wrap}
.langs select{flex:1;background:#0f172a;border:1px solid #334155;border-radius:8px;padding:.6rem .85rem;color:#e2e8f0;font-size:14px;font-family:inherit;min-width:120px}
.swap-btn{background:#334155;border:none;border-radius:50%;width:36px;height:36px;font-size:1.1rem;color:#e2e8f0;cursor:pointer;flex-shrink:0}
.swap-btn:hover{background:#0ea5e9;color:#fff}
.areas{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}
textarea{width:100%;background:#0f172a;border:1px solid #334155;border-radius:8px;padding:.85rem;color:#e2e8f0;font-size:14px;font-family:'Cairo',sans-serif;resize:vertical;min-height:180px;line-height:1.7}
textarea:focus{outline:none;border-color:#0ea5e9}
.btn{background:linear-gradient(135deg,#0ea5e9,#6366f1);color:#fff;border:none;border-radius:8px;padding:.7rem 2rem;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;margin-top:.75rem}
.btn:hover{opacity:.9}
.chars{font-size:12px;color:#475569;margin-top:.25rem;text-align:left}
.nav-back{display:block;text-align:center;color:#64748b;font-size:13px;margin-top:1.5rem;text-decoration:none}
.nav-back:hover{color:#0ea5e9}
@media(max-width:600px){.areas{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="wrap">
  <div class="logo">
    <h1>🌐 مترجم فوري</h1>
    <p>ترجمة مجانية بين العربية والإنجليزية والفرنسية والألمانية وأكثر</p>
  </div>

  <div class="card">
    <div class="langs">
      <select id="fromLang">
        <option value="ar" selected>العربية</option>
        <option value="en">الإنجليزية</option>
        <option value="fr">الفرنسية</option>
        <option value="de">الألمانية</option>
        <option value="tr">التركية</option>
        <option value="es">الإسبانية</option>
        <option value="zh">الصينية</option>
      </select>
      <button class="swap-btn" onclick="swapLangs()">⇄</button>
      <select id="toLang">
        <option value="en" selected>الإنجليزية</option>
        <option value="ar">العربية</option>
        <option value="fr">الفرنسية</option>
        <option value="de">الألمانية</option>
        <option value="tr">التركية</option>
        <option value="es">الإسبانية</option>
        <option value="zh">الصينية</option>
      </select>
    </div>
    <div class="areas">
      <div>
        <textarea id="srcText" placeholder="اكتب النص للترجمة..." oninput="updateChars()"></textarea>
        <div class="chars" id="charCount">0 حرف</div>
      </div>
      <div>
        <textarea id="dstText" placeholder="الترجمة تظهر هنا..." readonly style="background:#0d1a2e;color:#93c5fd"></textarea>
      </div>
    </div>
    <div style="text-align:center">
      <button class="btn" onclick="translate()">🌐 ترجمة</button>
    </div>
  </div>

  <div class="card" style="font-size:13px;color:#64748b;text-align:center">
    مدعوم بـ MyMemory Translation API المجانية •
    <a href="<?= htmlspecialchars(MAIN_SITE) ?>" style="color:#0ea5e9">الموقع الرئيسي</a>
  </div>

  <a href="<?= htmlspecialchars(MAIN_SITE) ?>" class="nav-back">← العودة للموقع الرئيسي</a>
</div>
<script>
function swapLangs() {
  const f = document.getElementById('fromLang');
  const t = document.getElementById('toLang');
  const src = document.getElementById('srcText');
  const dst = document.getElementById('dstText');
  [f.value, t.value] = [t.value, f.value];
  [src.value, dst.value] = [dst.value, src.value];
  updateChars();
}
function updateChars() {
  document.getElementById('charCount').textContent = document.getElementById('srcText').value.length + ' حرف';
}
async function translate() {
  const text = document.getElementById('srcText').value.trim();
  const from = document.getElementById('fromLang').value;
  const to   = document.getElementById('toLang').value;
  const dst  = document.getElementById('dstText');
  if (!text) { alert('أدخل نصاً للترجمة'); return; }
  dst.value = '⏳ جاري الترجمة...';
  try {
    const r = await fetch(`https://api.mymemory.translated.net/get?q=${encodeURIComponent(text)}&langpair=${from}|${to}`);
    const d = await r.json();
    dst.value = d.responseData?.translatedText || 'تعذّرت الترجمة';
  } catch {
    dst.value = 'خطأ في الاتصال. تحقق من الإنترنت.';
  }
}
document.getElementById('srcText').addEventListener('keydown', e => {
  if (e.ctrlKey && e.key === 'Enter') translate();
});
</script>
</body>
</html>
