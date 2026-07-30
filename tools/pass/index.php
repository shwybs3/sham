<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode(['@context'=>'https://schema.org','@type'=>'WebApplication','name'=>'مولّد كلمات المرور القوية','url'=>TOOLS_BASE_URL.'/tools/pass/','description'=>'أنشئ كلمة مرور قوية وآمنة مجاناً باختياراتك','applicationCategory'=>'SecurityApplication','operatingSystem'=>'All','offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD']]);
tool_head('مولّد كلمات المرور القوية | yassota أدوات','أنشئ كلمة مرور قوية وعشوائية في ثانية واحدة — مجاناً وبأمان تام في متصفحك',$schema,'#dc2626');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#fee2e2">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
    </div>
    <h1>مولّد كلمات المرور القوية</h1>
    <p>أنشئ كلمات مرور عشوائية وآمنة حسب متطلباتك — كل شيء يتم داخل متصفحك ولا تُرسل أي بيانات للسيرفر.</p>
  </div>

  <div class="t-card">
    <div style="margin-bottom:16px">
      <label class="t-label">طول كلمة المرور</label>
      <div style="display:flex;align-items:center;gap:12px">
        <input type="range" id="len" min="6" max="64" value="16" oninput="document.getElementById('len-val').textContent=this.value;generate()" style="flex:1;accent-color:#dc2626">
        <span id="len-val" style="font-size:18px;font-weight:800;color:#dc2626;min-width:30px;text-align:center">16</span>
      </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:18px">
      <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer"><input type="checkbox" id="upper" checked onchange="generate()"> أحرف كبيرة (A-Z)</label>
      <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer"><input type="checkbox" id="lower" checked onchange="generate()"> أحرف صغيرة (a-z)</label>
      <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer"><input type="checkbox" id="nums" checked onchange="generate()"> أرقام (0-9)</label>
      <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer"><input type="checkbox" id="syms" checked onchange="generate()"> رموز (!@#$%)</label>
    </div>

    <div style="position:relative;margin-bottom:16px">
      <input type="text" id="pass-out" readonly class="t-input" style="font-family:monospace;font-size:16px;letter-spacing:1px;padding-left:50px" placeholder="اضغط توليد...">
      <button onclick="copyPass()" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#64748b;padding:6px" title="نسخ">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
      </button>
    </div>

    <div id="strength-bar" style="margin-bottom:14px;display:none">
      <div style="display:flex;justify-content:space-between;margin-bottom:4px">
        <span style="font-size:12px;color:#64748b">قوة كلمة المرور</span>
        <span id="strength-label" style="font-size:12px;font-weight:700"></span>
      </div>
      <div class="t-progress"><div id="strength-fill" class="t-progress-bar"></div></div>
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <button class="t-btn" style="background:#dc2626" onclick="generate()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 4v6h-6M1 20v-6h6"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
        توليد جديد
      </button>
      <button class="t-btn t-btn-success" onclick="copyPass()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
        <span id="copy-label">نسخ</span>
      </button>
    </div>
  </div>

  <div id="bulk-card" class="t-card" style="margin-top:0">
    <h2>توليد عدة كلمات مرور دفعة واحدة</h2>
    <div style="display:flex;gap:10px;align-items:center;margin-bottom:10px;flex-wrap:wrap">
      <input type="number" id="bulk-count" value="10" min="1" max="50" class="t-input" style="max-width:80px">
      <button class="t-btn t-btn-secondary" onclick="generateBulk()">توليد</button>
    </div>
    <div id="bulk-out" style="font-family:monospace;font-size:13px;background:#f8fafc;border-radius:8px;padding:12px;display:none;white-space:pre-wrap;word-break:break-all;line-height:2"></div>
  </div>

  <?php tool_ad(); ?>
</main>
<script>
var CHARS={upper:'ABCDEFGHIJKLMNOPQRSTUVWXYZ',lower:'abcdefghijklmnopqrstuvwxyz',nums:'0123456789',syms:'!@#$%^&*()-_=+[]{}|;:,.<>?'};
function getPool(){var p='';if(document.getElementById('upper').checked)p+=CHARS.upper;if(document.getElementById('lower').checked)p+=CHARS.lower;if(document.getElementById('nums').checked)p+=CHARS.nums;if(document.getElementById('syms').checked)p+=CHARS.syms;return p||CHARS.lower+CHARS.nums;}
function randPass(len){var pool=getPool(),r='';var arr=new Uint32Array(len);window.crypto.getRandomValues(arr);for(var i=0;i<len;i++)r+=pool[arr[i]%pool.length];return r;}
function calcStrength(p){var s=0;if(/[A-Z]/.test(p))s++;if(/[a-z]/.test(p))s++;if(/[0-9]/.test(p))s++;if(/[^A-Za-z0-9]/.test(p))s++;if(p.length>=12)s++;if(p.length>=20)s++;return s;}
function generate(){
  var len=parseInt(document.getElementById('len').value)||16;
  var pass=randPass(len);
  document.getElementById('pass-out').value=pass;
  var s=calcStrength(pass);
  var labels=['','ضعيفة','مقبولة','متوسطة','قوية','ممتازة','مثالية'];
  var colors=['','#ef4444','#f97316','#eab308','#22c55e','#0ea5e9','#7c3aed'];
  var el=document.getElementById('strength-bar');el.style.display='block';
  document.getElementById('strength-fill').style.width=(s/6*100)+'%';
  document.getElementById('strength-fill').style.background=colors[s]||'#22c55e';
  document.getElementById('strength-label').textContent=labels[s]||'';
  document.getElementById('strength-label').style.color=colors[s]||'#22c55e';
  document.getElementById('copy-label').textContent='نسخ';
}
function copyPass(){
  var v=document.getElementById('pass-out').value;if(!v)return;
  if(navigator.clipboard){navigator.clipboard.writeText(v).then(function(){document.getElementById('copy-label').textContent='✓ تم النسخ';setTimeout(function(){document.getElementById('copy-label').textContent='نسخ';},2000)});}
  else{var ta=document.createElement('textarea');ta.value=v;document.body.appendChild(ta);ta.select();document.execCommand('copy');document.body.removeChild(ta);document.getElementById('copy-label').textContent='✓ تم النسخ';}
}
function generateBulk(){
  var n=Math.min(50,Math.max(1,parseInt(document.getElementById('bulk-count').value)||10));
  var len=parseInt(document.getElementById('len').value)||16;
  var lines=[];for(var i=0;i<n;i++)lines.push((i+1)+'. '+randPass(len));
  var el=document.getElementById('bulk-out');el.textContent=lines.join('\n');el.style.display='block';
}
generate();
</script>
<?php tool_footer(); ?>
