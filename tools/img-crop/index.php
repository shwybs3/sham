<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'قصّ الصور مجاناً','url'=>TOOLS_BASE_URL.'/tools/img-crop/',
    'description'=>'قصّ أي صورة وحدّد الأبعاد بدقة مجاناً داخل متصفحك',
    'applicationCategory'=>'UtilitiesApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('قصّ الصور مجاناً — أداة قص صور احترافية | yassota','قصّ أي صورة بدقة عالية مجاناً داخل متصفحك — بدون تسجيل ولا رفع بيانات',$schema,'#0891b2');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#e0f2fe">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0891b2" stroke-width="2"><path d="M6 2v14a2 2 0 002 2h14"/><path d="M18 22V8a2 2 0 00-2-2H2"/></svg>
    </div>
    <h1>قصّ الصور مجاناً</h1>
    <p>اقطع صورتك بالأبعاد التي تريدها — اختر نسبة جاهزة أو أدخل أبعاداً مخصصة، كل شيء يعمل في متصفحك.</p>
  </div>

  <div class="t-card">
    <div id="upload-area" style="border:2.5px dashed #cbd5e1;border-radius:14px;padding:32px 20px;text-align:center;cursor:pointer;background:#f8fafc" onclick="document.getElementById('img-in').click()">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin:0 auto 10px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
      <p style="font-size:15px;font-weight:600;color:#334155;margin-bottom:4px">انقر لاختيار صورة</p>
      <p style="font-size:12px;color:#94a3b8">PNG، JPG، WebP، GIF</p>
      <input type="file" id="img-in" accept="image/*" style="display:none" onchange="loadImage(this.files[0])">
    </div>

    <div id="editor" style="display:none;margin-top:18px">
      <label class="t-label" style="margin-bottom:8px">نسبة القص</label>
      <div class="chip-group" style="margin-bottom:14px">
        <div class="chip active" onclick="setRatio(this,'free')">حر</div>
        <div class="chip" onclick="setRatio(this,'1:1')">1:1 (مربع)</div>
        <div class="chip" onclick="setRatio(this,'16:9')">16:9 (أفقي)</div>
        <div class="chip" onclick="setRatio(this,'4:3')">4:3</div>
        <div class="chip" onclick="setRatio(this,'9:16')">9:16 (عمودي)</div>
      </div>
      <div class="t-row" style="margin-bottom:14px">
        <div class="t-col"><label class="t-label">X (بكسل)</label><input type="number" id="cx" class="t-input" value="0" min="0" oninput="updatePreview()"></div>
        <div class="t-col"><label class="t-label">Y (بكسل)</label><input type="number" id="cy" class="t-input" value="0" min="0" oninput="updatePreview()"></div>
        <div class="t-col"><label class="t-label">العرض</label><input type="number" id="cw" class="t-input" value="0" min="1" oninput="updatePreview()"></div>
        <div class="t-col"><label class="t-label">الارتفاع</label><input type="number" id="ch" class="t-input" value="0" min="1" oninput="updatePreview()"></div>
      </div>
      <div style="overflow:auto;margin-bottom:14px;border:1px solid #e2e8f0;border-radius:10px">
        <canvas id="preview" style="display:block;max-width:100%"></canvas>
      </div>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <button class="t-btn" style="background:#0891b2" onclick="cropImage()">قص الصورة</button>
        <select id="fmt" class="t-input" style="width:auto;padding:11px 14px">
          <option value="image/jpeg">JPG</option>
          <option value="image/png">PNG</option>
          <option value="image/webp">WebP</option>
        </select>
      </div>
      <div id="result" style="margin-top:14px;display:none">
        <canvas id="result-canvas" style="max-width:100%;border-radius:8px;border:1px solid #e2e8f0;display:block;margin-bottom:10px"></canvas>
        <a id="dl-link" class="t-btn t-btn-success" href="#" download="cropped">↓ تحميل الصورة المقصوصة</a>
      </div>
    </div>
  </div>

  <?php tool_ad(); ?>
</main>

<script>
var img = new Image(), ratio = 'free', natW = 0, natH = 0;

function loadImage(file) {
  if (!file) return;
  var reader = new FileReader();
  reader.onload = function(e) {
    img.onload = function() {
      natW = img.naturalWidth; natH = img.naturalHeight;
      document.getElementById('cx').value = 0;
      document.getElementById('cy').value = 0;
      document.getElementById('cw').value = natW;
      document.getElementById('ch').value = natH;
      document.getElementById('editor').style.display = 'block';
      updatePreview();
    };
    img.src = e.target.result;
  };
  reader.readAsDataURL(file);
}

function setRatio(el, r) {
  document.querySelectorAll('.chip').forEach(function(c){ c.className='chip'; });
  el.className='chip active'; ratio = r;
  if (r !== 'free') {
    var parts = r.split(':').map(Number);
    var rw = parts[0], rh = parts[1];
    var cw = parseInt(document.getElementById('cw').value) || natW;
    document.getElementById('ch').value = Math.round(cw * rh / rw);
  }
  updatePreview();
}

function updatePreview() {
  var canvas = document.getElementById('preview');
  var ctx = canvas.getContext('2d');
  var scale = Math.min(820 / natW, 400 / natH, 1);
  canvas.width = natW * scale; canvas.height = natH * scale;
  ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
  var x = parseInt(document.getElementById('cx').value)*scale;
  var y = parseInt(document.getElementById('cy').value)*scale;
  var w = parseInt(document.getElementById('cw').value)*scale;
  var h = parseInt(document.getElementById('ch').value)*scale;
  ctx.fillStyle = 'rgba(0,0,0,0.45)';
  ctx.fillRect(0,0,canvas.width,canvas.height);
  ctx.clearRect(x,y,w,h);
  ctx.strokeStyle='#0891b2'; ctx.lineWidth=2;
  ctx.strokeRect(x,y,w,h);
}

function cropImage() {
  var x = parseInt(document.getElementById('cx').value);
  var y = parseInt(document.getElementById('cy').value);
  var w = Math.min(parseInt(document.getElementById('cw').value), natW-x);
  var h = Math.min(parseInt(document.getElementById('ch').value), natH-y);
  var canvas = document.getElementById('result-canvas');
  canvas.width = w; canvas.height = h;
  canvas.getContext('2d').drawImage(img, x, y, w, h, 0, 0, w, h);
  var fmt = document.getElementById('fmt').value;
  var ext = fmt.split('/')[1];
  var url = canvas.toDataURL(fmt, 0.92);
  var dl = document.getElementById('dl-link');
  dl.href = url; dl.download = 'cropped.' + ext;
  document.getElementById('result').style.display='block';
}
</script>
<?php tool_footer(); ?>
