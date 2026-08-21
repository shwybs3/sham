<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'تحويل تنسيق الصور مجاناً','url'=>TOOLS_BASE_URL.'/tools/img-convert/',
    'description'=>'حوّل صورك بين JPG وPNG وWebP وICO مجاناً في المتصفح',
    'applicationCategory'=>'UtilitiesApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('تحويل تنسيق الصور — JPG إلى PNG إلى WebP مجاناً | yassota','حوّل صورك بين تنسيقات JPG وPNG وWebP وICO مجاناً مباشرة في متصفحك',$schema,'#059669');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#d1fae5">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
    </div>
    <h1>تحويل تنسيق الصور مجاناً</h1>
    <p>حوّل صورك بين تنسيقات JPG وPNG وWebP وBMP مجاناً — كل شيء يعمل في متصفحك بدون رفع ملفاتك.</p>
  </div>

  <div class="t-card">
    <div id="drop-area" style="border:2.5px dashed #cbd5e1;border-radius:14px;padding:32px 20px;text-align:center;cursor:pointer;background:#f8fafc"
         ondragover="event.preventDefault();this.style.borderColor='#059669'" ondragleave="this.style.borderColor='#cbd5e1'"
         ondrop="handleDrop(event)" onclick="document.getElementById('img-in').click()">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin:0 auto 10px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
      <p style="font-size:15px;font-weight:600;color:#334155;margin-bottom:4px">اسحب الصورة هنا أو انقر للاختيار</p>
      <p style="font-size:12px;color:#94a3b8">JPG، PNG، WebP، GIF، BMP</p>
      <input type="file" id="img-in" accept="image/*" multiple style="display:none" onchange="loadImages(this.files)">
    </div>

    <div id="controls" style="display:none;margin-top:18px">
      <div class="t-row" style="margin-bottom:14px;align-items:flex-end">
        <div class="t-col">
          <label class="t-label">تحويل إلى</label>
          <select id="to-fmt" class="t-input">
            <option value="image/webp">WebP (أفضل ضغط)</option>
            <option value="image/jpeg">JPG</option>
            <option value="image/png">PNG (شفاف)</option>
          </select>
        </div>
        <div class="t-col">
          <label class="t-label">الجودة <span id="q-val">90%</span></label>
          <input type="range" id="quality" min="10" max="100" value="90" oninput="document.getElementById('q-val').textContent=this.value+'%'" style="width:100%;accent-color:#059669">
        </div>
        <div class="t-col">
          <button class="t-btn" style="background:#059669;width:100%" onclick="convertAll()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
            تحويل الكل
          </button>
        </div>
      </div>
      <div id="file-list" style="display:flex;flex-direction:column;gap:8px"></div>
      <div id="results" style="margin-top:14px;display:flex;flex-wrap:wrap;gap:8px"></div>
    </div>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card">
    <h2>لماذا WebP؟</h2>
    <p style="color:#64748b;font-size:14px;line-height:1.8">تنسيق WebP من Google يوفر ما بين 25-35% في حجم الملف مقارنةً بـ JPG مع جودة مشابهة، وما بين 50-70% مقارنةً بـ PNG مع دعم للشفافية. مثالي لتحسين سرعة المواقع الإلكترونية.</p>
  </div>
</main>

<script>
var loadedImages = [];

function handleDrop(ev) {
  ev.preventDefault();
  document.querySelector('#drop-area').style.borderColor='#cbd5e1';
  loadImages(ev.dataTransfer.files);
}

function loadImages(files) {
  loadedImages = [...files].filter(function(f){ return f.type.startsWith('image/'); });
  if (!loadedImages.length) return;
  var list = document.getElementById('file-list');
  list.innerHTML = '';
  loadedImages.forEach(function(f, i) {
    list.innerHTML += '<div style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0">' +
      '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>' +
      '<div style="flex:1;font-size:13px;font-weight:600">' + f.name + '</div>' +
      '<div style="font-size:11px;color:#94a3b8">' + (f.size/1024).toFixed(1) + ' KB</div>' +
      '<span id="st-' + i + '" style="font-size:12px;color:#94a3b8">—</span></div>';
  });
  document.getElementById('controls').style.display = 'block';
  document.getElementById('results').innerHTML = '';
}

function convertAll() {
  var fmt = document.getElementById('to-fmt').value;
  var q = parseInt(document.getElementById('quality').value) / 100;
  var ext = fmt.split('/')[1] === 'jpeg' ? 'jpg' : fmt.split('/')[1];
  var res = document.getElementById('results');
  res.innerHTML = '';
  loadedImages.forEach(function(f, i) {
    var reader = new FileReader();
    reader.onload = function(e) {
      var st = document.getElementById('st-' + i);
      if (st) { st.textContent = '⏳'; st.style.color='#f59e0b'; }
      var img = new Image();
      img.onload = function() {
        var canvas = document.createElement('canvas');
        canvas.width = img.width; canvas.height = img.height;
        var ctx = canvas.getContext('2d');
        if (fmt === 'image/png' || fmt === 'image/webp') {
          ctx.clearRect(0,0,canvas.width,canvas.height);
        } else {
          ctx.fillStyle = '#ffffff';
          ctx.fillRect(0,0,canvas.width,canvas.height);
        }
        ctx.drawImage(img,0,0);
        canvas.toBlob(function(blob) {
          var url = URL.createObjectURL(blob);
          var baseName = f.name.replace(/\.[^.]+$/, '');
          res.innerHTML += '<a href="' + url + '" download="' + baseName + '.' + ext + '" class="t-btn t-btn-success" style="font-size:13px;padding:8px 16px">↓ ' + baseName + '.' + ext + '</a>';
          if (st) { st.textContent = '✓'; st.style.color='#059669'; }
        }, fmt, q);
      };
      img.src = e.target.result;
    };
    reader.readAsDataURL(f);
  });
}
</script>
<?php tool_footer(); ?>
