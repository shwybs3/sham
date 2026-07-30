<?php define('MAIN_SITE', 'https://your-domain.com'); ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>استخراج النصوص من الصور — OCR مجاني عربي وإنجليزي</title>
<meta name="description" content="استخرج النصوص من الصور مجاناً — يدعم العربية والإنجليزية — يعمل في المتصفح بدون رفع صور">
<meta name="robots" content="index,follow">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800;900&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{direction:rtl}
body{font-family:'Cairo',sans-serif;background:#0f172a;color:#e2e8f0;min-height:100vh}
.wrap{max-width:800px;margin:0 auto;padding:2rem 1rem}
.logo{text-align:center;margin-bottom:2rem}
.logo h1{font-size:2rem;font-weight:900;color:#fff}
.logo p{color:#94a3b8;margin-top:.35rem;font-size:.9rem}
.card{background:#1e293b;border:1px solid #334155;border-radius:16px;padding:1.5rem;margin-bottom:1.25rem}
.drop-zone{border:2px dashed #475569;border-radius:12px;padding:2.5rem;text-align:center;cursor:pointer;transition:.2s}
.drop-zone:hover,.drop-zone.over{border-color:#7c3aed;background:rgba(124,58,237,.05)}
.drop-zone input{display:none}
.drop-zone .icon{font-size:2.5rem;margin-bottom:.5rem}
.preview-img{max-width:100%;border-radius:10px;margin-top:1rem;max-height:300px;object-fit:contain}
.controls{display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;margin-top:1rem}
.select-lang{background:#0f172a;border:1px solid #334155;border-radius:8px;padding:.55rem .85rem;color:#e2e8f0;font-size:14px;font-family:inherit}
.btn{background:#7c3aed;color:#fff;border:none;border-radius:8px;padding:.65rem 1.5rem;font-weight:700;cursor:pointer;font-size:15px;font-family:inherit}
.btn:hover{background:#6d28d9}
.progress-wrap{margin-top:1rem}
.progress-bar{background:#334155;border-radius:4px;overflow:hidden;height:8px}
.progress-fill{background:linear-gradient(90deg,#7c3aed,#a78bfa);height:100%;width:0;transition:width .3s}
.progress-label{font-size:13px;color:#94a3b8;margin-top:.35rem;text-align:center}
.output{background:#0f172a;border:1px solid #334155;border-radius:10px;padding:1rem;font-size:14px;line-height:1.9;min-height:120px;white-space:pre-wrap;direction:auto}
.copy-btn{background:#16a34a;color:#fff;border:none;border-radius:6px;padding:.45rem .9rem;font-size:13px;font-weight:700;cursor:pointer;margin-top:.5rem;font-family:inherit}
.nav-back{display:block;text-align:center;color:#64748b;font-size:13px;margin-top:1.5rem;text-decoration:none}
.nav-back:hover{color:#7c3aed}
#status{font-size:13px;color:#a78bfa;margin-top:.35rem;text-align:center}
</style>
</head>
<body>
<div class="wrap">
  <div class="logo">
    <h1>📷 استخراج النصوص</h1>
    <p>OCR مجاني — يدعم العربية والإنجليزية — يعمل في المتصفح</p>
  </div>

  <div class="card">
    <div class="drop-zone" id="dropZone" onclick="document.getElementById('imgInput').click()">
      <div class="icon">📷</div>
      <div>اسحب الصورة هنا أو اضغط للاختيار</div>
      <div style="font-size:12px;color:#64748b;margin-top:.3rem">JPG · PNG · WEBP · BMP</div>
      <input type="file" id="imgInput" accept="image/*" onchange="handleFile(this)">
    </div>
    <img id="preview" class="preview-img" hidden alt="معاينة">

    <div class="controls">
      <label style="font-size:14px;color:#94a3b8;display:flex;align-items:center;gap:.5rem">
        اللغة:
        <select id="ocrLang" class="select-lang">
          <option value="ara">العربية</option>
          <option value="eng">الإنجليزية</option>
          <option value="ara+eng" selected>العربية + الإنجليزية</option>
        </select>
      </label>
      <button onclick="startOCR()" class="btn" id="ocrBtn">📖 استخرج النص</button>
    </div>

    <div id="progressWrap" hidden class="progress-wrap">
      <div class="progress-bar"><div class="progress-fill" id="progressFill"></div></div>
      <div class="progress-label" id="progressLabel">جاري التهيئة...</div>
    </div>
    <div id="status"></div>
  </div>

  <div class="card" id="outputCard" hidden>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem">
      <strong>النص المستخرج</strong>
      <button class="copy-btn" onclick="copyText()">📋 نسخ</button>
    </div>
    <div class="output" id="outputText"></div>
  </div>

  <div class="card" style="font-size:13px;color:#64748b;text-align:center">
    جميع العمليات تتم في متصفحك — لا ترفع صورك إلى أي خادم •
    مدعوم بـ <strong style="color:#a78bfa">Tesseract.js</strong>
  </div>

  <a href="<?= htmlspecialchars(MAIN_SITE) ?>" class="nav-back">← العودة للموقع الرئيسي</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
<script>
const dropZone = document.getElementById('dropZone');
let _file = null;

dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('over'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('over'));
dropZone.addEventListener('drop', e => {
  e.preventDefault(); dropZone.classList.remove('over');
  const f = e.dataTransfer?.files[0];
  if (f && f.type.startsWith('image/')) loadFile(f);
});

function handleFile(input) {
  const f = input.files[0];
  if (f) loadFile(f);
}
function loadFile(f) {
  _file = f;
  const prev = document.getElementById('preview');
  prev.src = URL.createObjectURL(f);
  prev.removeAttribute('hidden');
  document.getElementById('outputCard').setAttribute('hidden', '');
  document.getElementById('status').textContent = `✅ ${f.name} (${(f.size/1024).toFixed(0)} KB)`;
}

async function startOCR() {
  if (!_file) { alert('اختر صورة أولاً'); return; }
  const lang  = document.getElementById('ocrLang').value;
  const btn   = document.getElementById('ocrBtn');
  const pwrap = document.getElementById('progressWrap');
  const pfill = document.getElementById('progressFill');
  const plbl  = document.getElementById('progressLabel');

  btn.disabled = true;
  btn.textContent = '⏳ جاري المعالجة...';
  pwrap.removeAttribute('hidden');

  try {
    const worker = await Tesseract.createWorker(lang, 1, {
      logger: m => {
        if (m.status === 'recognizing text') {
          const pct = Math.round((m.progress || 0) * 100);
          pfill.style.width = pct + '%';
          plbl.textContent = `جاري التعرف على النص... ${pct}%`;
        } else if (m.status) {
          plbl.textContent = m.status;
        }
      }
    });
    const { data: { text } } = await worker.recognize(_file);
    await worker.terminate();

    document.getElementById('outputText').textContent = text || 'لم يُعثر على نص في الصورة.';
    document.getElementById('outputCard').removeAttribute('hidden');
    pfill.style.width = '100%';
    plbl.textContent = '✅ اكتمل!';
  } catch (err) {
    plbl.textContent = '❌ حدث خطأ: ' + (err.message || 'غير معروف');
    console.error(err);
  } finally {
    btn.disabled = false;
    btn.textContent = '📖 استخرج النص';
  }
}

function copyText() {
  const t = document.getElementById('outputText').textContent;
  navigator.clipboard.writeText(t).then(() => {
    const btn = document.querySelector('.copy-btn');
    btn.textContent = '✅ تم النسخ!';
    setTimeout(() => btn.textContent = '📋 نسخ', 2000);
  });
}
</script>
</body>
</html>
