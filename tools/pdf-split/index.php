<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'تقسيم PDF مجاناً','url'=>TOOLS_BASE_URL.'/tools/pdf-split/',
    'description'=>'قسّم ملف PDF إلى صفحات منفصلة أو نطاق محدد مجاناً بالمتصفح',
    'applicationCategory'=>'UtilitiesApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('تقسيم PDF مجاناً — استخرج صفحات من PDF | yassota','قسّم ملف PDF أو استخرج صفحات محددة مجاناً داخل متصفحك بدون رفع أي بيانات',$schema,'#dc2626');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#fee2e2">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
    </div>
    <h1>تقسيم ملف PDF مجاناً</h1>
    <p>استخرج صفحات محددة من ملف PDF أو قسّمه إلى ملفات منفصلة — يعمل كلياً في متصفحك بدون أي رفع للإنترنت.</p>
  </div>

  <div class="t-card">
    <div id="drop-area" style="border:2.5px dashed #cbd5e1;border-radius:14px;padding:32px 20px;text-align:center;cursor:pointer;transition:all .2s;background:#f8fafc"
         onclick="document.getElementById('pdf-in').click()">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin:0 auto 10px"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      <p style="font-size:15px;font-weight:600;color:#334155;margin-bottom:4px">انقر لاختيار ملف PDF</p>
      <p style="font-size:12px;color:#94a3b8" id="file-name">لم يُختر أي ملف</p>
      <input type="file" id="pdf-in" accept=".pdf,application/pdf" style="display:none" onchange="loadPDF(this.files[0])">
    </div>

    <div id="options" style="margin-top:18px;display:none">
      <div style="font-size:13px;font-weight:600;color:#334155;margin-bottom:10px">الملف: <span id="pdf-info" style="color:#dc2626"></span></div>
      <div class="t-row" style="margin-bottom:14px">
        <div class="t-col">
          <label class="t-label">طريقة التقسيم</label>
          <select id="mode" class="t-input" onchange="toggleMode()">
            <option value="all">كل صفحة في ملف منفصل</option>
            <option value="range">نطاق محدد (مثال: 1,3,5-8)</option>
            <option value="split">تقسيم إلى جزأين</option>
          </select>
        </div>
        <div class="t-col" id="range-col" style="display:none">
          <label class="t-label">الصفحات المطلوبة</label>
          <input type="text" id="range-input" class="t-input" placeholder="مثال: 1,3,5-8">
        </div>
      </div>
      <button class="t-btn" style="background:#dc2626" onclick="splitPDF()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16"/><path d="M20 2H8a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/></svg>
        تقسيم PDF
      </button>
    </div>

    <div id="progress-wrap" style="margin-top:16px;display:none">
      <div style="font-size:13px;color:#334155;margin-bottom:6px" id="progress-msg">جارٍ المعالجة...</div>
      <div class="t-progress"><div class="t-progress-bar" id="prog-bar" style="width:0%;background:#dc2626"></div></div>
    </div>
    <div id="downloads" style="margin-top:14px;display:flex;flex-wrap:wrap;gap:8px"></div>
  </div>

  <?php tool_ad(); ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
<script>
var loadedBuf = null, totalPages = 0;

async function loadPDF(file) {
  if (!file) return;
  document.getElementById('file-name').textContent = file.name;
  loadedBuf = await file.arrayBuffer();
  var doc = await PDFLib.PDFDocument.load(loadedBuf);
  totalPages = doc.getPageCount();
  document.getElementById('pdf-info').textContent = file.name + ' — ' + totalPages + ' صفحة';
  document.getElementById('options').style.display = 'block';
}

function toggleMode() {
  var m = document.getElementById('mode').value;
  document.getElementById('range-col').style.display = m === 'range' ? 'block' : 'none';
}

function parseRange(str, max) {
  var pages = new Set();
  str.split(',').forEach(function(part) {
    part = part.trim();
    if (part.includes('-')) {
      var [a,b] = part.split('-').map(Number);
      for (var i=a; i<=Math.min(b,max); i++) if (i>=1) pages.add(i-1);
    } else {
      var n = parseInt(part);
      if (n>=1 && n<=max) pages.add(n-1);
    }
  });
  return [...pages].sort((a,b)=>a-b);
}

async function splitPDF() {
  if (!loadedBuf) return;
  var mode = document.getElementById('mode').value;
  var wrap = document.getElementById('progress-wrap');
  var msg = document.getElementById('progress-msg');
  var bar = document.getElementById('prog-bar');
  var dl = document.getElementById('downloads');
  wrap.style.display='block'; dl.innerHTML='';
  var srcDoc = await PDFLib.PDFDocument.load(loadedBuf);

  async function extractPages(indices, filename) {
    var newDoc = await PDFLib.PDFDocument.create();
    var copied = await newDoc.copyPages(srcDoc, indices);
    copied.forEach(function(p){ newDoc.addPage(p); });
    var bytes = await newDoc.save();
    var url = URL.createObjectURL(new Blob([bytes],{type:'application/pdf'}));
    dl.innerHTML += '<a href="'+url+'" download="'+filename+'" class="t-btn t-btn-success" style="font-size:13px;padding:8px 16px">↓ '+filename+'</a>';
  }

  if (mode === 'all') {
    for (var i=0; i<totalPages; i++) {
      bar.style.width = Math.round(((i+1)/totalPages)*100)+'%';
      msg.textContent = 'معالجة الصفحة ' + (i+1) + ' من ' + totalPages;
      await extractPages([i], 'page-'+(i+1)+'.pdf');
    }
    msg.textContent = 'تم! ' + totalPages + ' ملف جاهز للتحميل';
  } else if (mode === 'range') {
    var indices = parseRange(document.getElementById('range-input').value, totalPages);
    if (!indices.length) { msg.textContent = 'يرجى تحديد نطاق صالح'; return; }
    bar.style.width='60%'; msg.textContent='جارٍ الاستخراج...';
    await extractPages(indices, 'extracted.pdf');
    bar.style.width='100%'; msg.textContent='تم! ' + indices.length + ' صفحة مستخرجة';
  } else if (mode === 'split') {
    var half = Math.floor(totalPages/2);
    bar.style.width='40%'; await extractPages([...Array(half).keys()], 'part1.pdf');
    bar.style.width='80%'; await extractPages([...Array(totalPages-half).keys()].map(function(i){return i+half;}), 'part2.pdf');
    bar.style.width='100%'; msg.textContent='تم! تم التقسيم إلى جزأين';
  }
}
</script>
<?php tool_footer(); ?>
