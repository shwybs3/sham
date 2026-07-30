<section class="page-hero small">
  <div class="container">
    <nav class="breadcrumb"><a href="/">الرئيسية</a> <span>›</span> <span>أدوات الصور</span></nav>
    <h1>🖼️ أدوات الصور المجانية</h1>
    <p>ضغط وتغيير حجم وتحويل صيغة الصور — كل العمليات في المتصفح، لا رفع خادم</p>
  </div>
</section>

<div class="container page-body">

  <!-- Tabs -->
  <section class="card" aria-labelledby="h-imgtool">
    <h2 id="h-imgtool">🛠️ اختر الأداة</h2>
    <div class="tool-tabs" data-group="img">
      <button class="tool-tab active" onclick="switchImgTab('compress',this)">🗜️ ضغط</button>
      <button class="tool-tab" onclick="switchImgTab('resize',this)">📐 تغيير الحجم</button>
      <button class="tool-tab" onclick="switchImgTab('convert',this)">🔄 تحويل الصيغة</button>
      <button class="tool-tab" onclick="switchImgTab('crop',this)">✂️ قص</button>
    </div>

    <!-- Compress -->
    <div class="tool-panel active" id="img-compress">
      <p style="color:var(--c-text2);font-size:14px;margin-bottom:1rem">اضغط صورتك حتى 90٪ مع الحفاظ على الجودة — يعمل بالكامل في المتصفح</p>
      <div class="drop-zone" onclick="document.getElementById('compressInput').click()" id="compressZone">
        <span class="dz-icon">🗜️</span>
        <div>اسحب الصورة هنا أو اضغط للاختيار</div>
        <div class="dz-sub">JPG · PNG · WebP · GIF — حتى 50MB</div>
        <input type="file" id="compressInput" accept="image/*" onchange="previewCompress(this)">
      </div>
      <div id="compressPreviewWrap" hidden style="margin:1rem 0">
        <img id="compressPreview" style="max-height:200px;border-radius:8px;border:1px solid var(--c-border)">
      </div>
      <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;margin:1rem 0">
        <label style="display:flex;align-items:center;gap:.5rem;font-size:14px">
          جودة الضغط:
          <input type="range" id="compressQuality" min="10" max="95" value="75" style="width:140px;background:transparent;border:none;padding:0"
            oninput="document.getElementById('qualityVal').textContent=this.value+'%'">
          <span id="qualityVal" style="min-width:35px;font-weight:700;color:var(--c-gold)">75%</span>
        </label>
        <button onclick="doCompress()" class="btn-primary">🗜️ اضغط الصورة</button>
      </div>
      <div id="compressInfo" hidden style="margin:.5rem 0;font-size:13px;color:var(--c-emerald)"></div>
      <div id="compressOutputWrap" hidden style="margin:1rem 0">
        <img id="compressOutput" style="max-height:250px;border-radius:8px;border:1px solid var(--c-border)">
        <br>
        <button onclick="downloadCompressed()" class="btn-outline" style="margin-top:.75rem">⬇️ تحميل الصورة المضغوطة</button>
      </div>
    </div>

    <!-- Resize -->
    <div class="tool-panel" id="img-resize">
      <div class="drop-zone" onclick="document.getElementById('resizeInput').click()" style="margin-bottom:1rem">
        <span class="dz-icon">📐</span>
        <div>اختر الصورة لتغيير حجمها</div>
        <input type="file" id="resizeInput" accept="image/*" onchange="initResize(this)">
      </div>
      <div id="resizeOrigInfo" hidden style="font-size:13px;color:var(--c-text2);margin-bottom:.75rem"></div>
      <div class="form-grid" style="max-width:400px">
        <label>العرض (بكسل) <input type="number" id="resizeW" value="800" min="1"></label>
        <label>الارتفاع (بكسل) <input type="number" id="resizeH" value="600" min="1"></label>
      </div>
      <label style="display:flex;align-items:center;gap:.5rem;font-size:13px;margin-bottom:.75rem;cursor:pointer">
        <input type="checkbox" id="resizeLock" checked style="width:auto"> الحفاظ على النسبة
      </label>
      <button onclick="doResize()" class="btn-primary">تغيير الحجم</button>
      <canvas id="resizeCanvas" hidden style="max-width:100%;margin-top:1rem;border-radius:8px;border:1px solid var(--c-border)"></canvas>
      <div id="resizeActions" hidden style="margin-top:.75rem">
        <button onclick="downloadCanvas('resizeCanvas','resized.jpg')" class="btn-outline">⬇️ تحميل</button>
      </div>
    </div>

    <!-- Convert -->
    <div class="tool-panel" id="img-convert">
      <div class="drop-zone" onclick="document.getElementById('convertInput').click()" style="margin-bottom:1rem">
        <span class="dz-icon">🔄</span>
        <div>اختر الصورة لتحويل صيغتها</div>
        <input type="file" id="convertInput" accept="image/*" onchange="initConvert(this)">
      </div>
      <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem">
        <label style="margin:0">تحويل إلى:
          <select id="convertFormat" style="width:auto;display:inline-block;margin-right:.5rem">
            <option value="image/jpeg">JPG</option>
            <option value="image/png">PNG</option>
            <option value="image/webp" selected>WebP</option>
          </select>
        </label>
        <button onclick="doConvert()" class="btn-primary">تحويل</button>
      </div>
      <canvas id="convertCanvas" hidden style="max-width:100%;border-radius:8px;border:1px solid var(--c-border);margin-top:.5rem"></canvas>
      <div id="convertActions" hidden style="margin-top:.75rem">
        <button onclick="downloadConverted()" class="btn-outline">⬇️ تحميل الصورة المحولة</button>
      </div>
    </div>

    <!-- Crop -->
    <div class="tool-panel" id="img-crop">
      <p style="color:var(--c-text2);font-size:14px;margin-bottom:1rem">اختر صورة ثم أدخل إحداثيات القص يدوياً</p>
      <div class="drop-zone" onclick="document.getElementById('cropInput').click()" style="margin-bottom:1rem">
        <span class="dz-icon">✂️</span>
        <div>اختر الصورة</div>
        <input type="file" id="cropInput" accept="image/*" onchange="initCrop(this)">
      </div>
      <img id="cropPreview" hidden style="max-width:100%;margin:1rem 0;border-radius:8px;border:1px solid var(--c-border)">
      <div class="form-grid" id="cropControls" hidden style="max-width:480px">
        <label>X البداية <input type="number" id="cropX" value="0" min="0"></label>
        <label>Y البداية <input type="number" id="cropY" value="0" min="0"></label>
        <label>العرض <input type="number" id="cropW" value="400" min="1"></label>
        <label>الارتفاع <input type="number" id="cropH" value="300" min="1"></label>
      </div>
      <div id="cropBtn" hidden style="margin-top:.75rem">
        <button onclick="doCrop()" class="btn-primary">✂️ قص الصورة</button>
      </div>
      <canvas id="cropCanvas" hidden style="max-width:100%;margin-top:1rem;border-radius:8px;border:1px solid var(--c-border)"></canvas>
      <div id="cropActions" hidden style="margin-top:.75rem">
        <button onclick="downloadCanvas('cropCanvas','cropped.jpg')" class="btn-outline">⬇️ تحميل</button>
      </div>
    </div>
  </section>

  <!-- Info -->
  <section class="seo-content card">
    <h2>📌 مميزات أدوات الصور</h2>
    <div class="prose">
      <ul>
        <li><strong>خصوصية كاملة:</strong> جميع العمليات تتم في المتصفح مباشرةً — لا ترفع صورك إلى أي خادم</li>
        <li><strong>مجانية 100%:</strong> لا حساب مطلوب، لا إعلانات مزعجة</li>
        <li><strong>سريعة:</strong> المعالجة فورية حتى لو لم يكن لديك اتصال بالإنترنت</li>
        <li><strong>متعددة الصيغ:</strong> تدعم JPG وPNG وWebP وGIF</li>
      </ul>
    </div>
  </section>
</div>

<script>
let _compressFile = null, _compressBlob = null;
let _resizeImg = null;
let _convertImg = null, _convertBlob = null;
let _cropImg = null;

function switchImgTab(id, btn) {
  document.querySelectorAll('.tool-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.tool-tab').forEach(b => b.classList.remove('active'));
  document.getElementById('img-'+id)?.classList.add('active');
  btn?.classList.add('active');
}

// ── Compress ──
function previewCompress(input) {
  _compressFile = input.files[0];
  if (!_compressFile) return;
  const wrap = document.getElementById('compressPreviewWrap');
  const img  = document.getElementById('compressPreview');
  img.src = URL.createObjectURL(_compressFile);
  wrap.removeAttribute('hidden');
}
function doCompress() {
  if (!_compressFile) { alert('اختر صورة أولاً'); return; }
  const q = parseInt(document.getElementById('compressQuality').value) / 100;
  const img = new Image();
  img.onload = () => {
    const c = document.createElement('canvas');
    c.width = img.naturalWidth; c.height = img.naturalHeight;
    c.getContext('2d').drawImage(img, 0, 0);
    c.toBlob(blob => {
      _compressBlob = blob;
      const out  = document.getElementById('compressOutput');
      const wrap = document.getElementById('compressOutputWrap');
      const info = document.getElementById('compressInfo');
      out.src = URL.createObjectURL(blob);
      wrap.removeAttribute('hidden');
      const ratio = ((1 - blob.size / _compressFile.size) * 100).toFixed(1);
      info.textContent = `${(_compressFile.size/1024).toFixed(0)} KB → ${(blob.size/1024).toFixed(0)} KB (توفير ${ratio}%)`;
      info.removeAttribute('hidden');
    }, _compressFile.type === 'image/png' ? 'image/png' : 'image/jpeg', q);
  };
  img.src = URL.createObjectURL(_compressFile);
}
function downloadCompressed() {
  if (!_compressBlob) return;
  const a = document.createElement('a');
  const ext = _compressBlob.type.includes('png') ? 'png' : 'jpg';
  a.download = 'compressed.' + ext;
  a.href = URL.createObjectURL(_compressBlob);
  a.click();
}

// ── Resize ──
function initResize(input) {
  _resizeImg = new Image();
  _resizeImg.onload = () => {
    document.getElementById('resizeW').value = _resizeImg.naturalWidth;
    document.getElementById('resizeH').value = _resizeImg.naturalHeight;
    const info = document.getElementById('resizeOrigInfo');
    info.textContent = `الحجم الأصلي: ${_resizeImg.naturalWidth} × ${_resizeImg.naturalHeight} بكسل`;
    info.removeAttribute('hidden');
  };
  _resizeImg.src = URL.createObjectURL(input.files[0]);
}
document.addEventListener('DOMContentLoaded', () => {
  const wIn = document.getElementById('resizeW');
  const hIn = document.getElementById('resizeH');
  if (wIn) wIn.addEventListener('input', () => {
    if (document.getElementById('resizeLock')?.checked && _resizeImg) {
      hIn.value = Math.round(parseInt(wIn.value) * _resizeImg.naturalHeight / _resizeImg.naturalWidth);
    }
  });
  if (hIn) hIn.addEventListener('input', () => {
    if (document.getElementById('resizeLock')?.checked && _resizeImg) {
      wIn.value = Math.round(parseInt(hIn.value) * _resizeImg.naturalWidth / _resizeImg.naturalHeight);
    }
  });
});
function doResize() {
  if (!_resizeImg) { alert('اختر صورة أولاً'); return; }
  const tw = parseInt(document.getElementById('resizeW').value);
  const th = parseInt(document.getElementById('resizeH').value);
  const c  = document.getElementById('resizeCanvas');
  c.width  = tw; c.height = th;
  c.getContext('2d').drawImage(_resizeImg, 0, 0, tw, th);
  c.removeAttribute('hidden');
  document.getElementById('resizeActions').removeAttribute('hidden');
}

// ── Convert ──
function initConvert(input) {
  _convertImg = new Image();
  _convertImg.src = URL.createObjectURL(input.files[0]);
}
function doConvert() {
  if (!_convertImg) { alert('اختر صورة أولاً'); return; }
  const fmt = document.getElementById('convertFormat').value;
  const c = document.getElementById('convertCanvas');
  c.width  = _convertImg.naturalWidth  || 800;
  c.height = _convertImg.naturalHeight || 600;
  c.getContext('2d').drawImage(_convertImg, 0, 0);
  c.toBlob(blob => {
    _convertBlob = blob;
    c.removeAttribute('hidden');
    document.getElementById('convertActions').removeAttribute('hidden');
  }, fmt, 0.92);
}
function downloadConverted() {
  if (!_convertBlob) return;
  const ext = _convertBlob.type.split('/')[1];
  const a = document.createElement('a');
  a.download = 'converted.' + ext;
  a.href = URL.createObjectURL(_convertBlob);
  a.click();
}

// ── Crop ──
function initCrop(input) {
  _cropImg = new Image();
  _cropImg.onload = () => {
    const prev = document.getElementById('cropPreview');
    prev.src = _cropImg.src;
    prev.removeAttribute('hidden');
    document.getElementById('cropW').value = Math.min(400, _cropImg.naturalWidth);
    document.getElementById('cropH').value = Math.min(300, _cropImg.naturalHeight);
    document.getElementById('cropControls').removeAttribute('hidden');
    document.getElementById('cropBtn').removeAttribute('hidden');
  };
  _cropImg.src = URL.createObjectURL(input.files[0]);
}
function doCrop() {
  if (!_cropImg) return;
  const x = parseInt(document.getElementById('cropX').value) || 0;
  const y = parseInt(document.getElementById('cropY').value) || 0;
  const w = parseInt(document.getElementById('cropW').value) || 400;
  const h = parseInt(document.getElementById('cropH').value) || 300;
  const c = document.getElementById('cropCanvas');
  c.width = w; c.height = h;
  c.getContext('2d').drawImage(_cropImg, x, y, w, h, 0, 0, w, h);
  c.removeAttribute('hidden');
  document.getElementById('cropActions').removeAttribute('hidden');
}

// Drag-over styling
document.querySelectorAll('.drop-zone').forEach(zone => {
  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('over'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('over'));
  zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('over');
    const file = e.dataTransfer?.files[0];
    if (!file) return;
    const inp = zone.querySelector('input[type=file]');
    if (!inp) return;
    const dt = new DataTransfer();
    dt.items.add(file);
    inp.files = dt.files;
    inp.dispatchEvent(new Event('change'));
  });
});
</script>
