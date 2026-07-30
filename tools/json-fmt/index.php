<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'منسّق JSON مجاناً','url'=>TOOLS_BASE_URL.'/tools/json-fmt/',
    'description'=>'نسّق وتحقق من صحة ملفات JSON مجاناً في المتصفح',
    'applicationCategory'=>'DeveloperApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('منسّق JSON مجاناً — تحقق وجمّل ملف JSON | yassota','نسّق ملف JSON وتحقق من صحته وحوّله إلى جدول مجاناً — يعمل في المتصفح بالكامل',$schema,'#6366f1');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#eef2ff">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><path d="M8 3H5a2 2 0 00-2 2v3m18 0V5a2 2 0 00-2-2h-3m0 18h3a2 2 0 002-2v-3M3 16v3a2 2 0 002 2h3"/></svg>
    </div>
    <h1>منسّق JSON وMinifire</h1>
    <p>نسّق JSON ليصبح قابلاً للقراءة، أو اضغطه في سطر واحد، أو تحقق من صحته — مجاناً بالكامل في متصفحك.</p>
  </div>

  <div class="t-card">
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
      <button class="t-btn" onclick="format()" style="background:#6366f1">تنسيق (Prettify)</button>
      <button class="t-btn t-btn-secondary" onclick="minify()">ضغط (Minify)</button>
      <button class="t-btn t-btn-secondary" onclick="validate()">تحقق من الصحة</button>
      <button class="t-btn t-btn-secondary" onclick="clearAll()">مسح</button>
      <button class="t-btn t-btn-secondary" onclick="copyOut()">نسخ</button>
    </div>
    <div class="t-row">
      <div class="t-col">
        <label class="t-label">أدخل JSON</label>
        <textarea id="json-in" class="t-textarea" style="min-height:280px;font-family:monospace;font-size:13px;direction:ltr" placeholder='{"name":"yassota","type":"tools"}'></textarea>
      </div>
      <div class="t-col">
        <label class="t-label">النتيجة</label>
        <textarea id="json-out" class="t-textarea" style="min-height:280px;font-family:monospace;font-size:13px;direction:ltr;background:#f8fafc" readonly></textarea>
      </div>
    </div>
    <div id="msg" style="margin-top:10px;font-size:13px"></div>
  </div>

  <div class="t-card">
    <h2 style="margin-bottom:10px">أدوات JSON إضافية</h2>
    <div class="t-row">
      <div class="t-col">
        <label class="t-label">استخراج قيمة (JSONPath بسيط)</label>
        <input type="text" id="jpath" class="t-input" placeholder="مثال: .name أو .users[0].email">
        <button class="t-btn t-btn-secondary" style="margin-top:8px;font-size:13px;padding:8px 16px" onclick="extractPath()">استخراج</button>
        <div id="path-result" style="margin-top:8px;font-size:13px;font-family:monospace;color:#6366f1"></div>
      </div>
      <div class="t-col">
        <label class="t-label">معلومات</label>
        <div id="info" style="padding:12px;background:#f8fafc;border-radius:8px;font-size:13px;color:#64748b;line-height:2">—</div>
      </div>
    </div>
  </div>

  <?php tool_ad(); ?>
</main>

<script>
function getJSON() { return document.getElementById('json-in').value.trim(); }
function setOut(v) { document.getElementById('json-out').value = v; }
function setMsg(v, ok) {
  var el = document.getElementById('msg');
  el.textContent = v;
  el.style.color = ok ? '#16a34a' : '#dc2626';
}

function format() {
  try {
    var obj = JSON.parse(getJSON());
    setOut(JSON.stringify(obj, null, 2));
    setMsg('✓ JSON صحيح وتم تنسيقه', true);
    showInfo(obj);
  } catch(e) { setMsg('✗ خطأ: ' + e.message, false); }
}

function minify() {
  try {
    var obj = JSON.parse(getJSON());
    setOut(JSON.stringify(obj));
    setMsg('✓ تم ضغط JSON', true);
  } catch(e) { setMsg('✗ خطأ: ' + e.message, false); }
}

function validate() {
  try {
    JSON.parse(getJSON());
    setMsg('✓ JSON صحيح بلا أخطاء', true);
  } catch(e) { setMsg('✗ JSON يحتوي أخطاء: ' + e.message, false); }
}

function clearAll() {
  document.getElementById('json-in').value='';
  setOut('');
  setMsg('','');
  document.getElementById('info').textContent='—';
}

function copyOut() {
  var v = document.getElementById('json-out').value;
  if (v) navigator.clipboard.writeText(v).then(function(){ setMsg('تم النسخ!', true); });
}

function showInfo(obj) {
  var keys = typeof obj === 'object' && obj !== null ? Object.keys(obj).length : 0;
  var type = Array.isArray(obj) ? 'مصفوفة (Array)' : typeof obj === 'object' ? 'كائن (Object)' : typeof obj;
  var size = new Blob([JSON.stringify(obj)]).size;
  document.getElementById('info').innerHTML =
    'النوع: <strong>' + type + '</strong><br>' +
    (Array.isArray(obj) ? 'عدد العناصر: <strong>' + obj.length + '</strong><br>' : 'عدد المفاتيح: <strong>' + keys + '</strong><br>') +
    'الحجم: <strong>' + (size > 1024 ? (size/1024).toFixed(1)+' KB' : size+' B') + '</strong>';
}

function extractPath() {
  var path = document.getElementById('jpath').value.trim();
  try {
    var obj = JSON.parse(getJSON());
    var result = obj;
    path.replace(/^\./, '').split(/[\.\[\]]+/).filter(Boolean).forEach(function(k) {
      result = result[isNaN(k) ? k : parseInt(k)];
    });
    document.getElementById('path-result').textContent = JSON.stringify(result, null, 2);
  } catch(e) {
    document.getElementById('path-result').textContent = 'خطأ: ' + e.message;
  }
}

document.getElementById('json-in').addEventListener('input', function() {
  try { var obj = JSON.parse(this.value.trim()); showInfo(obj); setMsg('JSON صحيح', true); } catch(e) { setMsg('', ''); }
});
</script>
<?php tool_footer(); ?>
