<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'استخراج النص من الصور OCR','url'=>TOOLS_BASE_URL.'/tools/ocr/',
    'description'=>'استخرج النص من أي صورة مجاناً باستخدام تقنية OCR تدعم العربية والإنجليزية',
    'applicationCategory'=>'UtilitiesApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('استخراج النص من الصور مجاناً — OCR عربي وإنجليزي | yassota','استخرج النص من أي صورة أو لقطة شاشة مجاناً داخل متصفحك — يدعم العربية والإنجليزية',$schema,'#0891b2');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#e0f2fe">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0891b2" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><path d="M9 21V9"/><path d="M7 13h2M7 17h4M13 13h4M13 17h2"/></svg>
    </div>
    <h1>استخراج النص من الصور (OCR)</h1>
    <p>حوّل أي صورة تحتوي على نص إلى نص قابل للتحرير — يدعم العربية والإنجليزية ويعمل كلياً في متصفحك.</p>
  </div>

  <div class="t-card">
    <div id="drop-area" style="border:2.5px dashed #cbd5e1;border-radius:14px;padding:32px 20px;text-align:center;cursor:pointer;background:#f8fafc"
         ondragover="event.preventDefault();this.style.borderColor='#0891b2'" ondragleave="this.style.borderColor='#cbd5e1'"
         ondrop="handleDrop(event)" onclick="document.getElementById('img-in').click()">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin:0 auto 10px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
      <p style="font-size:15px;font-weight:600;color:#334155;margin-bottom:4px">اسحب صورة هنا أو انقر للاختيار</p>
      <p style="font-size:12px;color:#94a3b8">PNG، JPG، WebP، BMP، GIF</p>
      <input type="file" id="img-in" accept="image/*" style="display:none" onchange="startOCR(this.files[0])">
    </div>

    <div id="lang-row" style="margin-top:14px;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
      <label class="t-label" style="margin:0">اللغة:</label>
      <div class="chip active" onclick="setLang(this,'ara+eng')">عربي + إنجليزي</div>
      <div class="chip" onclick="setLang(this,'ara')">عربي فقط</div>
      <div class="chip" onclick="setLang(this,'eng')">إنجليزي فقط</div>
    </div>

    <div id="preview-wrap" style="display:none;margin-top:14px">
      <img id="preview" style="max-width:100%;border-radius:10px;border:1px solid #e2e8f0;max-height:300px;object-fit:contain">
    </div>

    <div id="progress-wrap" style="margin-top:16px;display:none">
      <div style="font-size:13px;color:#334155;margin-bottom:6px" id="prog-msg">جارٍ التحليل — قد يستغرق بضع ثوانٍ...</div>
      <div class="t-progress"><div class="t-progress-bar" id="prog-bar" style="width:0%;background:#0891b2"></div></div>
    </div>

    <div id="result-wrap" style="margin-top:14px;display:none">
      <div style="font-size:13px;font-weight:600;color:#334155;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center">
        <span>النص المستخرج</span>
        <button class="t-btn t-btn-secondary" style="padding:5px 12px;font-size:12px" onclick="copyText()">نسخ</button>
      </div>
      <textarea id="ocr-result" class="t-textarea" style="min-height:200px;direction:auto" readonly></textarea>
      <div id="conf" style="font-size:11px;color:#94a3b8;margin-top:6px"></div>
    </div>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card">
    <h2>نصائح لأفضل نتائج</h2>
    <ul style="color:#64748b;font-size:14px;line-height:2;padding-right:18px">
      <li>استخدم صوراً عالية الدقة (200+ DPI) للنتائج الأفضل</li>
      <li>تأكد من أن النص واضح وغير مائل</li>
      <li>خلفية بيضاء أو فاتحة تعطي نتائج أدق</li>
      <li>اختر اللغة الصحيحة لنتائج أفضل</li>
    </ul>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
<script>
var lang = 'ara+eng';

function setLang(el, l) {
  document.querySelectorAll('.chip').forEach(function(c){ c.className='chip'; });
  el.className='chip active'; lang = l;
}

function handleDrop(ev) {
  ev.preventDefault();
  document.getElementById('drop-area').style.borderColor='#cbd5e1';
  var file = ev.dataTransfer.files[0];
  if (file && file.type.startsWith('image/')) startOCR(file);
}

function startOCR(file) {
  if (!file) return;
  var reader = new FileReader();
  reader.onload = function(e) {
    document.getElementById('preview').src = e.target.result;
    document.getElementById('preview-wrap').style.display='block';
    document.getElementById('result-wrap').style.display='none';
    document.getElementById('progress-wrap').style.display='block';
    document.getElementById('prog-bar').style.width='5%';
    document.getElementById('prog-msg').textContent='جارٍ تحميل محرك OCR...';

    Tesseract.recognize(e.target.result, lang, {
      logger: function(m) {
        if (m.status === 'recognizing text') {
          var pct = Math.round(m.progress * 100);
          document.getElementById('prog-bar').style.width = pct + '%';
          document.getElementById('prog-msg').textContent = 'جارٍ التعرف على النص... ' + pct + '%';
        }
      }
    }).then(function(r) {
      document.getElementById('prog-bar').style.width='100%';
      document.getElementById('prog-msg').textContent='اكتمل التحليل!';
      document.getElementById('ocr-result').value = r.data.text.trim();
      var conf = Math.round(r.data.confidence);
      document.getElementById('conf').textContent = 'مستوى الثقة: ' + conf + '%' + (conf < 50 ? ' — جودة الصورة منخفضة، حاول بصورة أوضح' : '');
      document.getElementById('result-wrap').style.display='block';
    }).catch(function(e) {
      document.getElementById('prog-msg').textContent='خطأ: ' + e.message;
      document.getElementById('prog-msg').style.color='#dc2626';
    });
  };
  reader.readAsDataURL(file);
}

function copyText() {
  var t = document.getElementById('ocr-result').value;
  navigator.clipboard.writeText(t).then(function(){ alert('تم النسخ!'); });
}
</script>
<?php tool_footer(); ?>
