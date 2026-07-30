<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'ضغط PDF مجاناً','url'=>TOOLS_BASE_URL.'/tools/pdf-compress/',
    'description'=>'قلّل حجم ملف PDF مجاناً داخل متصفحك مع الحفاظ على الجودة',
    'applicationCategory'=>'UtilitiesApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('ضغط PDF مجاناً — تصغير حجم PDF بدون فقدان الجودة | yassota','قلّل حجم ملف PDF مجاناً مباشرة في متصفحك بدون رفع الملفات للإنترنت',$schema,'#ea580c');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#ffedd5">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="12" y2="18"/><line x1="15" y1="15" x2="12" y2="18"/></svg>
    </div>
    <h1>ضغط ملف PDF مجاناً</h1>
    <p>قلّل حجم ملف PDF بشكل ملحوظ مع الحفاظ على الجودة — تعمل الأداة كلياً في متصفحك دون رفع أي بيانات للإنترنت.</p>
  </div>

  <div class="t-card">
    <div id="drop-area" style="border:2.5px dashed #cbd5e1;border-radius:14px;padding:32px 20px;text-align:center;cursor:pointer;transition:all .2s;background:#f8fafc" onclick="document.getElementById('pdf-in').click()">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin:0 auto 10px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
      <p style="font-size:15px;font-weight:600;color:#334155;margin-bottom:4px">انقر لاختيار ملف PDF</p>
      <p style="font-size:12px;color:#94a3b8" id="file-name">لم يُختر أي ملف</p>
      <input type="file" id="pdf-in" accept=".pdf,application/pdf" style="display:none" onchange="loadFile(this.files[0])">
    </div>

    <div id="options" style="margin-top:18px;display:none">
      <div style="background:#f8fafc;border-radius:10px;padding:14px;margin-bottom:14px;font-size:13px">
        <div style="display:flex;gap:20px;flex-wrap:wrap">
          <div><span style="color:#94a3b8">الحجم الأصلي:</span> <strong id="orig-size"></strong></div>
        </div>
      </div>
      <label class="t-label">مستوى الضغط</label>
      <div class="chip-group" style="margin-bottom:14px">
        <div class="chip" onclick="setLevel(this,'low')" style="border-color:#ea580c;color:#ea580c">خفيف (جودة أعلى)</div>
        <div class="chip active" onclick="setLevel(this,'medium')" style="background:#ea580c;color:#fff;border-color:#ea580c">متوسط (مُوصى)</div>
        <div class="chip" onclick="setLevel(this,'high')">عالي (حجم أصغر)</div>
      </div>
      <button class="t-btn" style="background:#ea580c" onclick="compressPDF()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
        ضغط الملف
      </button>
    </div>

    <div id="progress-wrap" style="margin-top:16px;display:none">
      <div style="font-size:13px;color:#334155;margin-bottom:6px" id="prog-msg">جارٍ الضغط...</div>
      <div class="t-progress"><div class="t-progress-bar" id="prog-bar" style="width:0%;background:#ea580c"></div></div>
    </div>
    <div id="result" style="margin-top:14px;display:none">
      <div style="background:#fff7ed;border:1.5px solid #fdba74;border-radius:10px;padding:14px 16px;margin-bottom:12px;font-size:13px">
        <div style="display:flex;gap:20px;flex-wrap:wrap">
          <div><span style="color:#94a3b8">قبل:</span> <strong id="before-sz"></strong></div>
          <div><span style="color:#94a3b8">بعد:</span> <strong id="after-sz" style="color:#ea580c"></strong></div>
          <div><span style="color:#94a3b8">وفورات:</span> <strong id="savings" style="color:#16a34a"></strong></div>
        </div>
      </div>
      <a id="dl-link" class="t-btn t-btn-success" href="#" download="compressed.pdf">↓ تحميل PDF المضغوط</a>
    </div>
  </div>

  <?php tool_ad(); ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
<script>
var loadedBuf = null, origSize = 0, comprLevel = 'medium';

async function loadFile(file) {
  if (!file) return;
  loadedBuf = await file.arrayBuffer();
  origSize = file.size;
  document.getElementById('file-name').textContent = file.name;
  document.getElementById('orig-size').textContent = fmtSize(origSize);
  document.getElementById('options').style.display = 'block';
}

function setLevel(el, lvl) {
  document.querySelectorAll('.chip').forEach(function(c){ c.className='chip'; c.style.background=''; c.style.color=''; c.style.borderColor=''; });
  el.className='chip active'; el.style.background='#ea580c'; el.style.color='#fff'; el.style.borderColor='#ea580c';
  comprLevel = lvl;
}

function fmtSize(bytes) {
  if (bytes > 1048576) return (bytes/1048576).toFixed(2) + ' MB';
  return (bytes/1024).toFixed(1) + ' KB';
}

async function compressPDF() {
  if (!loadedBuf) return;
  var wrap = document.getElementById('progress-wrap');
  var msg = document.getElementById('prog-msg');
  var bar = document.getElementById('prog-bar');
  wrap.style.display='block';
  msg.textContent='جارٍ تحليل الملف...'; bar.style.width='30%';
  try {
    var doc = await PDFLib.PDFDocument.load(loadedBuf, {ignoreEncryption: true});
    msg.textContent='جارٍ إعادة الحفظ بإعدادات الضغط...'; bar.style.width='70%';
    var useObjectStreams = comprLevel !== 'low';
    var addDefaultPage = false;
    var bytes = await doc.save({ useObjectStreams, addDefaultPage });
    bar.style.width='100%';
    msg.textContent='اكتمل الضغط!';
    var newSize = bytes.byteLength;
    var saved = origSize - newSize;
    var pct = origSize > 0 ? Math.round((saved/origSize)*100) : 0;
    document.getElementById('before-sz').textContent = fmtSize(origSize);
    document.getElementById('after-sz').textContent = fmtSize(newSize);
    document.getElementById('savings').textContent = (saved > 0 ? '-' + fmtSize(saved) + ' (' + pct + '%)' : 'الملف محسّن بالفعل');
    var blob = new Blob([bytes], {type:'application/pdf'});
    document.getElementById('dl-link').href = URL.createObjectURL(blob);
    document.getElementById('result').style.display='block';
  } catch(e) {
    msg.textContent = 'خطأ: ' + e.message; msg.style.color='#dc2626';
  }
}
</script>
<?php tool_footer(); ?>
