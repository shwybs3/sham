<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'دمج ملفات PDF مجاناً','url'=>TOOLS_BASE_URL.'/tools/pdf-merge/',
    'description'=>'ادمج ملفات PDF متعددة في ملف واحد مجاناً داخل متصفحك مع ترتيب الصفحات',
    'applicationCategory'=>'UtilitiesApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('دمج PDF مجاناً — ادمج ملفات PDF في ملف واحد | yassota','ادمج ملفات PDF متعددة في ملف واحد مجاناً مباشرة في متصفحك بدون رفع أي ملف للإنترنت',$schema,'#7c3aed');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#ede9fe">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="12" y2="18"/><line x1="15" y1="15" x2="12" y2="18"/></svg>
    </div>
    <h1>دمج ملفات PDF مجاناً</h1>
    <p>ادمج ملفين أو أكثر في ملف PDF واحد مباشرة في متصفحك — بدون رفع ملفات للإنترنت، بدون تسجيل، 100% مجاني.</p>
  </div>

  <div class="t-card">
    <div id="drop-area" style="border:2.5px dashed #cbd5e1;border-radius:14px;padding:32px 20px;text-align:center;cursor:pointer;transition:all .2s;background:#f8fafc"
         ondragover="ev.preventDefault();this.style.borderColor='#7c3aed';this.style.background='#f5f3ff'"
         ondragleave="this.style.borderColor='#cbd5e1';this.style.background='#f8fafc'"
         ondrop="handleDrop(event)" onclick="document.getElementById('pdf-in').click()">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin:0 auto 10px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
      <p style="font-size:15px;font-weight:600;color:#334155;margin-bottom:4px">اسحب ملفات PDF هنا</p>
      <p style="font-size:12px;color:#94a3b8">أو انقر للاختيار — يمكنك اختيار عدة ملفات</p>
      <input type="file" id="pdf-in" accept=".pdf,application/pdf" multiple style="display:none" onchange="addFiles(this.files)">
    </div>

    <div id="file-list" style="margin-top:16px;display:none">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
        <div style="font-size:13px;font-weight:600;color:#334155">الملفات المحددة <span id="file-count" style="color:#7c3aed"></span></div>
        <button class="t-btn t-btn-secondary" style="padding:6px 14px;font-size:12px" onclick="clearFiles()">مسح الكل</button>
      </div>
      <div id="file-items" style="display:flex;flex-direction:column;gap:8px"></div>
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px">
      <button class="t-btn" id="merge-btn" onclick="mergePDFs()" style="display:none">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16"/><path d="M20 2H8a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/></svg>
        دمج الملفات
      </button>
    </div>

    <div id="progress-wrap" style="margin-top:16px;display:none">
      <div style="font-size:13px;color:#334155;margin-bottom:6px" id="progress-msg">جارٍ المعالجة...</div>
      <div class="t-progress"><div class="t-progress-bar" id="prog-bar" style="width:0%"></div></div>
    </div>
    <div id="result" style="margin-top:14px;display:none">
      <a id="dl-link" class="t-btn t-btn-success" href="#" download="merged.pdf">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
        تحميل PDF المدموج
      </a>
    </div>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card">
    <h2>مميزات أداة الدمج</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;font-size:13px">
      <div style="padding:12px;background:#f5f3ff;border-radius:10px"><strong style="color:#7c3aed">🔒 خصوصية تامة</strong><p style="color:#6b7280;margin-top:4px">تعمل الأداة في متصفحك فقط دون رفع أي ملف</p></div>
      <div style="padding:12px;background:#f5f3ff;border-radius:10px"><strong style="color:#7c3aed">⚡ سريعة</strong><p style="color:#6b7280;margin-top:4px">النتيجة فورية بدون انتظار سيرفر</p></div>
      <div style="padding:12px;background:#f5f3ff;border-radius:10px"><strong style="color:#7c3aed">🔀 ترتيب حر</strong><p style="color:#6b7280;margin-top:4px">رتّب الملفات بالترتيب الذي تريده</p></div>
      <div style="padding:12px;background:#f5f3ff;border-radius:10px"><strong style="color:#7c3aed">📄 عدة ملفات</strong><p style="color:#6b7280;margin-top:4px">ادمج عشرات الملفات في وقت واحد</p></div>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
<script>
var files = [];

function handleDrop(ev) {
  ev.preventDefault();
  document.getElementById('drop-area').style.borderColor='#cbd5e1';
  document.getElementById('drop-area').style.background='#f8fafc';
  addFiles(ev.dataTransfer.files);
}

function addFiles(newFiles) {
  for (var f of newFiles) {
    if (f.type === 'application/pdf' || f.name.endsWith('.pdf')) files.push(f);
  }
  renderList();
}

function clearFiles() { files = []; renderList(); }

function renderList() {
  var el = document.getElementById('file-list');
  var items = document.getElementById('file-items');
  var count = document.getElementById('file-count');
  var btn = document.getElementById('merge-btn');
  if (!files.length) { el.style.display='none'; btn.style.display='none'; return; }
  el.style.display='block'; btn.style.display='inline-flex';
  count.textContent = '(' + files.length + ' ملفات)';
  items.innerHTML = '';
  files.forEach(function(f, i) {
    var kb = (f.size/1024).toFixed(1);
    items.innerHTML += '<div style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0">' +
      '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>' +
      '<div style="flex:1;min-width:0"><div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + f.name + '</div>' +
      '<div style="font-size:11px;color:#94a3b8">' + kb + ' KB</div></div>' +
      '<button onclick="removeFile('+i+')" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px" title="حذف">' +
      '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg></button></div>';
  });
}

function removeFile(i) { files.splice(i,1); renderList(); }

async function mergePDFs() {
  if (files.length < 1) return;
  var btn = document.getElementById('merge-btn');
  var wrap = document.getElementById('progress-wrap');
  var msg = document.getElementById('progress-msg');
  var bar = document.getElementById('prog-bar');
  var res = document.getElementById('result');
  btn.disabled = true; wrap.style.display='block'; res.style.display='none';
  try {
    var merged = await PDFLib.PDFDocument.create();
    for (var i=0; i<files.length; i++) {
      msg.textContent = 'جارٍ معالجة: ' + files[i].name + ' (' + (i+1) + '/' + files.length + ')';
      bar.style.width = Math.round((i/files.length)*80) + '%';
      var buf = await files[i].arrayBuffer();
      var doc = await PDFLib.PDFDocument.load(buf);
      var pages = await merged.copyPages(doc, doc.getPageIndices());
      pages.forEach(function(p){ merged.addPage(p); });
    }
    msg.textContent = 'جارٍ إنشاء الملف النهائي...';
    bar.style.width = '90%';
    var pdfBytes = await merged.save();
    bar.style.width = '100%';
    var blob = new Blob([pdfBytes], {type:'application/pdf'});
    var url = URL.createObjectURL(blob);
    document.getElementById('dl-link').href = url;
    res.style.display='block';
    msg.textContent = 'تم بنجاح! ' + merged.getPageCount() + ' صفحة في الملف المدموج.';
  } catch(e) {
    msg.textContent = 'خطأ: ' + e.message;
    msg.style.color = '#dc2626';
  }
  btn.disabled = false;
}
</script>
<?php tool_footer(); ?>
