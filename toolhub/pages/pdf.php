<section class="page-hero small">
  <div class="container">
    <nav class="breadcrumb"><a href="/">الرئيسية</a> <span>›</span> <span>أدوات PDF</span></nav>
    <h1>📄 أدوات PDF المجانية</h1>
    <p>دمج وضغط وتقسيم وتحويل ملفات PDF مباشرة في المتصفح — بدون رفع الملفات لخوادم خارجية</p>
  </div>
</section>

<div class="container page-body">
  <div class="tools-tabs" id="pdfTabs">
    <button class="ptab active" data-tool="merge">🔗 دمج PDF</button>
    <button class="ptab" data-tool="split">✂️ تقسيم PDF</button>
    <button class="ptab" data-tool="img2pdf">🖼️ صور إلى PDF</button>
    <button class="ptab" data-tool="pdf2img">📸 PDF إلى صور</button>
    <button class="ptab" data-tool="compress">🗜️ ضغط PDF</button>
    <button class="ptab" data-tool="protect">🔒 حماية PDF</button>
  </div>

  <!-- Merge PDF -->
  <div class="tool-panel active" id="tool-merge">
    <div class="card tool-card-main">
      <h2>🔗 دمج ملفات PDF</h2>
      <p>ادمج عدة ملفات PDF في ملف واحد. الترتيب يُحدَّد حسب تسلسل الإضافة.</p>
      <div class="drop-zone" id="mergeDrop" ondragover="event.preventDefault()" ondrop="handleDrop(event,'merge')">
        <div class="dz-icon">📂</div>
        <p>اسحب وأفلت ملفات PDF هنا</p>
        <p class="dz-sub">أو انقر للاختيار من جهازك</p>
        <input type="file" id="mergeInput" multiple accept=".pdf" onchange="addFiles(this.files,'merge')" style="display:none">
        <button onclick="document.getElementById('mergeInput').click()" class="btn-outline">اختر ملفات PDF</button>
      </div>
      <div id="mergeList" class="file-list"></div>
      <div class="tool-actions">
        <button onclick="mergePDF()" class="btn-primary" id="mergBtn" disabled>🔗 دمج الملفات</button>
        <button onclick="clearFiles('merge')" class="btn-outline">مسح القائمة</button>
      </div>
      <div id="mergeStatus" class="tool-status" hidden></div>
    </div>
  </div>

  <!-- Split PDF -->
  <div class="tool-panel" id="tool-split">
    <div class="card tool-card-main">
      <h2>✂️ تقسيم ملف PDF</h2>
      <p>استخرج صفحات محددة أو قسّم ملف PDF إلى عدة ملفات</p>
      <div class="drop-zone" ondragover="event.preventDefault()" ondrop="handleDrop(event,'split')">
        <div class="dz-icon">📂</div>
        <p>اختر ملف PDF للتقسيم</p>
        <input type="file" id="splitInput" accept=".pdf" onchange="loadSplitPDF(this.files[0])" style="display:none">
        <button onclick="document.getElementById('splitInput').click()" class="btn-outline">اختر ملف PDF</button>
      </div>
      <div id="splitInfo" class="split-info" hidden>
        <p id="splitPageCount"></p>
        <div class="form-row">
          <label>نطاق الصفحات (مثال: 1-3,5,7-9)
            <input type="text" id="splitRange" placeholder="1-3,5,7-9">
          </label>
        </div>
        <button onclick="splitPDF()" class="btn-primary">✂️ تقسيم</button>
      </div>
      <div id="splitStatus" class="tool-status" hidden></div>
    </div>
  </div>

  <!-- Images to PDF -->
  <div class="tool-panel" id="tool-img2pdf">
    <div class="card tool-card-main">
      <h2>🖼️ تحويل الصور إلى PDF</h2>
      <p>حوّل صور JPG وPNG وWebP إلى ملف PDF واحد</p>
      <div class="drop-zone" ondragover="event.preventDefault()" ondrop="handleImgDrop(event)">
        <div class="dz-icon">🖼️</div>
        <p>اسحب الصور هنا (JPG, PNG, WebP)</p>
        <input type="file" id="imgInput" multiple accept="image/*" onchange="addImages(this.files)" style="display:none">
        <button onclick="document.getElementById('imgInput').click()" class="btn-outline">اختر الصور</button>
      </div>
      <div id="imgList" class="img-preview-list"></div>
      <div class="form-row" id="imgToPdfOptions" hidden>
        <label>حجم الصفحة
          <select id="pdfPageSize">
            <option value="A4" selected>A4</option>
            <option value="A3">A3</option>
            <option value="Letter">Letter</option>
          </select>
        </label>
        <label>الاتجاه
          <select id="pdfOrientation">
            <option value="portrait">طولي</option>
            <option value="landscape">عرضي</option>
          </select>
        </label>
      </div>
      <button onclick="convertImgsToPDF()" class="btn-primary" id="img2pdfBtn" disabled>📄 تحويل إلى PDF</button>
      <div id="img2pdfStatus" class="tool-status" hidden></div>
    </div>
  </div>

  <!-- PDF to Images -->
  <div class="tool-panel" id="tool-pdf2img">
    <div class="card tool-card-main">
      <h2>📸 تحويل PDF إلى صور</h2>
      <p>حوّل كل صفحة في ملف PDF إلى صورة JPG أو PNG</p>
      <div class="drop-zone" ondragover="event.preventDefault()">
        <div class="dz-icon">📄</div>
        <p>اختر ملف PDF لتحويله</p>
        <input type="file" id="pdf2imgInput" accept=".pdf" onchange="convertPDFToImages(this.files[0])" style="display:none">
        <button onclick="document.getElementById('pdf2imgInput').click()" class="btn-outline">اختر ملف PDF</button>
      </div>
      <div class="form-row">
        <label>جودة الصورة
          <select id="imgQuality">
            <option value="0.8">عالية</option>
            <option value="0.6" selected>متوسطة</option>
            <option value="0.4">منخفضة</option>
          </select>
        </label>
        <label>الصيغة
          <select id="imgFormat">
            <option value="image/jpeg">JPG</option>
            <option value="image/png">PNG</option>
          </select>
        </label>
      </div>
      <div id="pdf2imgResult" class="img-preview-list"></div>
      <div id="pdf2imgStatus" class="tool-status" hidden></div>
    </div>
  </div>

  <!-- Compress PDF (note: true compression requires server-side) -->
  <div class="tool-panel" id="tool-compress">
    <div class="card tool-card-main">
      <h2>🗜️ ضغط ملف PDF</h2>
      <p>قلّل حجم ملف PDF مع الحفاظ على الجودة</p>
      <div class="alert-info">
        💡 لضغط PDF بفاعلية أعلى، نوصي باستخدام أداة PDF المتكاملة على نطاق <strong>pdf.<?= h(parse_url(SITE_URL,PHP_URL_HOST)) ?></strong>
      </div>
      <div class="drop-zone" ondragover="event.preventDefault()">
        <div class="dz-icon">📂</div>
        <p>اختر ملف PDF للضغط</p>
        <input type="file" id="compressInput" accept=".pdf" onchange="compressPDF(this.files[0])" style="display:none">
        <button onclick="document.getElementById('compressInput').click()" class="btn-outline">اختر ملف PDF</button>
      </div>
      <div id="compressStatus" class="tool-status" hidden></div>
    </div>
  </div>

  <!-- Protect PDF -->
  <div class="tool-panel" id="tool-protect">
    <div class="card tool-card-main">
      <h2>🔒 حماية PDF بكلمة مرور</h2>
      <p>أضف كلمة مرور لملف PDF لحمايته من القراءة غير المصرح بها</p>
      <div class="drop-zone">
        <div class="dz-icon">🔒</div>
        <p>اختر ملف PDF للحماية</p>
        <input type="file" id="protectInput" accept=".pdf" onchange="protectPDF(this.files[0])" style="display:none">
        <button onclick="document.getElementById('protectInput').click()" class="btn-outline">اختر ملف PDF</button>
      </div>
      <div id="protectOptions" hidden>
        <div class="form-row">
          <label>كلمة المرور <input type="password" id="pdfPwd" placeholder="أدخل كلمة المرور"></label>
          <label>تأكيد كلمة المرور <input type="password" id="pdfPwd2" placeholder="أعد الإدخال"></label>
        </div>
        <button onclick="applyPDFProtection()" class="btn-primary">🔒 تطبيق الحماية</button>
      </div>
      <div id="protectStatus" class="tool-status" hidden></div>
    </div>
  </div>

  <section class="seo-content card">
    <h2>📌 دليل أدوات PDF</h2>
    <div class="prose">
      <p>أدواتنا تعمل <strong>مباشرة في المتصفح</strong> بدون رفع ملفاتك لأي خادم خارجي، مما يضمن خصوصيتك الكاملة.</p>
      <ul>
        <li><strong>دمج PDF:</strong> ادمج ملفين أو أكثر في ملف واحد بترتيب تختاره</li>
        <li><strong>تقسيم PDF:</strong> استخرج صفحات محددة من ملف PDF كبير</li>
        <li><strong>صور إلى PDF:</strong> حوّل مجموعة صور إلى وثيقة PDF</li>
        <li><strong>PDF إلى صور:</strong> استخرج الصفحات كصور JPG أو PNG</li>
      </ul>
    </div>
  </section>
</div>

<!-- Load PDF.js and pdf-lib.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
<script>
// PDF tool tabs
document.querySelectorAll('.ptab').forEach(tab => {
  tab.addEventListener('click', function() {
    document.querySelectorAll('.ptab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tool-panel').forEach(p => p.classList.remove('active'));
    this.classList.add('active');
    document.getElementById('tool-' + this.dataset.tool).classList.add('active');
  });
});

const mergeFiles = [];
const imgFiles  = [];

function handleDrop(e, type) {
  e.preventDefault();
  const files = [...e.dataTransfer.files].filter(f => f.type === 'application/pdf');
  if (type === 'merge') addFiles(files, 'merge');
}

function addFiles(files, type) {
  [...files].forEach(f => {
    mergeFiles.push(f);
    const li = document.createElement('div');
    li.className = 'file-item';
    li.innerHTML = `<span>📄 ${f.name}</span><span class="muted">${(f.size/1024/1024).toFixed(2)} MB</span>`;
    document.getElementById('mergeList').appendChild(li);
  });
  document.getElementById('mergBtn').disabled = mergeFiles.length < 2;
}

function clearFiles(type) {
  mergeFiles.length = 0;
  document.getElementById('mergeList').innerHTML = '';
  document.getElementById('mergBtn').disabled = true;
}

async function mergePDF() {
  if (mergeFiles.length < 2) return;
  const st = document.getElementById('mergeStatus');
  st.hidden = false; st.innerHTML = '<div class="loading">⏳ جاري الدمج…</div>';
  try {
    const { PDFDocument } = PDFLib;
    const merged = await PDFDocument.create();
    for (const file of mergeFiles) {
      const bytes = await file.arrayBuffer();
      const doc   = await PDFDocument.load(bytes);
      const pages = await merged.copyPages(doc, doc.getPageIndices());
      pages.forEach(p => merged.addPage(p));
    }
    const pdfBytes = await merged.save();
    downloadBlob(pdfBytes, 'merged.pdf', 'application/pdf');
    st.innerHTML = '<div class="success">✅ تم الدمج بنجاح! جاري التنزيل…</div>';
  } catch(e) { st.innerHTML = `<div class="error">❌ خطأ: ${e.message}</div>`; }
}

async function loadSplitPDF(file) {
  if (!file) return;
  const bytes = await file.arrayBuffer();
  const { PDFDocument } = PDFLib;
  const doc = await PDFDocument.load(bytes);
  window._splitDoc = doc;
  window._splitBytes = bytes;
  document.getElementById('splitInfo').hidden = false;
  document.getElementById('splitPageCount').textContent = `عدد الصفحات: ${doc.getPageCount()}`;
}

async function splitPDF() {
  if (!window._splitDoc) return;
  const range = document.getElementById('splitRange').value;
  const pages = parsePageRange(range, window._splitDoc.getPageCount());
  const st = document.getElementById('splitStatus');
  st.hidden = false; st.innerHTML = '<div class="loading">⏳ جاري التقسيم…</div>';
  try {
    const { PDFDocument } = PDFLib;
    const newDoc = await PDFDocument.create();
    const srcDoc = await PDFDocument.load(window._splitBytes);
    const copied = await newDoc.copyPages(srcDoc, pages.map(p => p-1));
    copied.forEach(p => newDoc.addPage(p));
    const pdfBytes = await newDoc.save();
    downloadBlob(pdfBytes, 'split.pdf', 'application/pdf');
    st.innerHTML = '<div class="success">✅ تم التقسيم!</div>';
  } catch(e) { st.innerHTML = `<div class="error">❌ ${e.message}</div>`; }
}

function parsePageRange(str, max) {
  const pages = new Set();
  str.split(',').forEach(part => {
    const m = part.trim().match(/^(\d+)(?:-(\d+))?$/);
    if (!m) return;
    const from = +m[1], to = m[2] ? +m[2] : from;
    for (let i = from; i <= Math.min(to, max); i++) pages.add(i);
  });
  return [...pages].sort((a,b) => a-b);
}

function addImages(files) {
  [...files].forEach(f => {
    imgFiles.push(f);
    const url = URL.createObjectURL(f);
    const div = document.createElement('div');
    div.className = 'img-thumb';
    div.innerHTML = `<img src="${url}" alt="${f.name}"><span>${f.name}</span>`;
    document.getElementById('imgList').appendChild(div);
  });
  document.getElementById('imgToPdfOptions').hidden = false;
  document.getElementById('img2pdfBtn').disabled = false;
}

function handleImgDrop(e) {
  e.preventDefault();
  addImages([...e.dataTransfer.files].filter(f => f.type.startsWith('image/')));
}

async function convertImgsToPDF() {
  if (!imgFiles.length) return;
  const { PDFDocument, PageSizes } = PDFLib;
  const doc = await PDFDocument.create();
  const sz  = document.getElementById('pdfPageSize').value;
  const ori = document.getElementById('pdfOrientation').value;
  const st  = document.getElementById('img2pdfStatus');
  st.hidden = false; st.innerHTML = '<div class="loading">⏳ جاري التحويل…</div>';
  try {
    for (const f of imgFiles) {
      const imgBytes = await f.arrayBuffer();
      let img;
      if (f.type === 'image/png') img = await doc.embedPng(imgBytes);
      else img = await doc.embedJpg(imgBytes);
      let [w, h] = PageSizes[sz] || PageSizes.A4;
      if (ori === 'landscape') [w, h] = [h, w];
      const page = doc.addPage([w, h]);
      const scale = Math.min(w / img.width, h / img.height);
      const iw = img.width * scale, ih = img.height * scale;
      page.drawImage(img, {x:(w-iw)/2, y:(h-ih)/2, width:iw, height:ih});
    }
    const bytes = await doc.save();
    downloadBlob(bytes, 'images.pdf', 'application/pdf');
    st.innerHTML = '<div class="success">✅ تم التحويل!</div>';
  } catch(e) { st.innerHTML = `<div class="error">❌ ${e.message}</div>`; }
}

async function convertPDFToImages(file) {
  if (!file) return;
  const bytes = await file.arrayBuffer();
  const pdfjsLib = window['pdfjs-dist/build/pdf'];
  if (pdfjsLib) pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
  const st = document.getElementById('pdf2imgStatus');
  const result = document.getElementById('pdf2imgResult');
  st.hidden = false; st.innerHTML = '<div class="loading">⏳ جاري المعالجة…</div>';
  try {
    const loadTask = pdfjsLib.getDocument({data: bytes});
    const pdf = await loadTask.promise;
    result.innerHTML = '';
    for (let i = 1; i <= pdf.numPages; i++) {
      const page = await pdf.getPage(i);
      const vp   = page.getViewport({scale: 1.5});
      const canvas = document.createElement('canvas');
      canvas.width = vp.width; canvas.height = vp.height;
      await page.render({canvasContext: canvas.getContext('2d'), viewport: vp}).promise;
      const div = document.createElement('div');
      div.className = 'img-thumb';
      const a = document.createElement('a');
      a.href = canvas.toDataURL('image/jpeg', 0.8);
      a.download = `page-${i}.jpg`;
      a.textContent = `⬇ تحميل صفحة ${i}`;
      result.appendChild(canvas); result.appendChild(a);
    }
    st.innerHTML = `<div class="success">✅ تم تحويل ${pdf.numPages} صفحة</div>`;
  } catch(e) { st.innerHTML = `<div class="error">❌ ${e.message}</div>`; }
}

async function compressPDF(file) {
  if (!file) return;
  const st = document.getElementById('compressStatus');
  st.hidden = false;
  st.innerHTML = '<div class="loading">⏳ جاري الضغط...</div>';
  try {
    const { PDFDocument } = PDFLib;
    const bytes = await file.arrayBuffer();
    const doc = await PDFDocument.load(bytes, {ignoreEncryption:true});
    const out  = await doc.save({useObjectStreams:true});
    const ratio = ((1 - out.byteLength/bytes.byteLength)*100).toFixed(1);
    downloadBlob(out, 'compressed.pdf', 'application/pdf');
    st.innerHTML = `<div class="success">✅ تم! نسبة الضغط: ${ratio}٪</div>`;
  } catch(e) { st.innerHTML = `<div class="error">❌ ${e.message}</div>`; }
}

async function protectPDF(file) {
  if (!file) return;
  document.getElementById('protectOptions').hidden = false;
  window._protectFile = file;
}

async function applyPDFProtection() {
  const pwd  = document.getElementById('pdfPwd').value;
  const pwd2 = document.getElementById('pdfPwd2').value;
  const st   = document.getElementById('protectStatus');
  if (!pwd) return;
  if (pwd !== pwd2) { alert('كلمات المرور غير متطابقة'); return; }
  st.hidden = false; st.innerHTML = '<div class="loading">⏳...</div>';
  const { PDFDocument } = PDFLib;
  const bytes = await window._protectFile.arrayBuffer();
  const doc = await PDFDocument.load(bytes);
  const out  = await doc.save({userPassword: pwd, ownerPassword: pwd+'owner'});
  downloadBlob(out, 'protected.pdf', 'application/pdf');
  st.innerHTML = '<div class="success">✅ تم!</div>';
}

function downloadBlob(data, name, mime) {
  const blob = new Blob([data], {type: mime});
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href = url; a.download = name; a.click();
  setTimeout(() => URL.revokeObjectURL(url), 5000);
}
</script>
