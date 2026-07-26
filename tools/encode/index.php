<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode(['@context'=>'https://schema.org','@type'=>'WebApplication','name'=>'مشفّر Base64 وURL وHash','url'=>TOOLS_BASE_URL.'/','description'=>'شفّر وفكّ تشفير النصوص بـ Base64 وURL وMD5 وSHA مجاناً','applicationCategory'=>'DeveloperApplication','operatingSystem'=>'All','offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD']]);
tool_head('مشفّر وفاكّ تشفير Base64 وURL وHash | yassota أدوات','شفّر وفكّ تشفير Base64 وURL وأنشئ MD5 وSHA-1 وSHA-256 — أداة مجانية للمطورين تعمل في المتصفح',$schema,'#0891b2');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#cffafe">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0891b2" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 3H8a2 2 0 00-2 2v2h12V5a2 2 0 00-2-2z"/><path d="M12 12v4M10 14h4"/></svg>
    </div>
    <h1>مشفّر وفاكّ تشفير — Base64 وURL وHash</h1>
    <p>أداة احترافية للمطورين لتشفير وفكّ تشفير النصوص بجميع الصيغ الشائعة — بدون اتصال بالسيرفر.</p>
  </div>

  <div class="t-card">
    <label class="t-label">النص المدخل</label>
    <textarea id="inp" class="t-textarea" placeholder="أدخل النص هنا..." rows="5" oninput="runAll()"></textarea>

    <div class="chip-group" style="margin-bottom:16px">
      <button class="chip active" onclick="setMode('b64e',this)">Base64 تشفير</button>
      <button class="chip" onclick="setMode('b64d',this)">Base64 فكّ</button>
      <button class="chip" onclick="setMode('urle',this)">URL تشفير</button>
      <button class="chip" onclick="setMode('urld',this)">URL فكّ</button>
      <button class="chip" onclick="setMode('html',this)">HTML Escape</button>
    </div>

    <label class="t-label">الناتج</label>
    <textarea id="out" class="t-textarea" rows="5" readonly style="background:#f8fafc;font-family:monospace"></textarea>
    <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap">
      <button class="t-btn" onclick="copyOut()">نسخ الناتج</button>
      <button class="t-btn t-btn-secondary" onclick="swap()">↕ عكس الدخل والناتج</button>
      <button class="t-btn t-btn-secondary" onclick="clear_all()">مسح</button>
    </div>
  </div>

  <div class="t-card">
    <h2>Hash (MD5 / SHA)</h2>
    <p style="font-size:13px;color:#64748b;margin-bottom:12px">يتم حساب الـ Hash من النص المدخل أعلاه تلقائياً — هذه العملية في اتجاه واحد فقط ولا يمكن عكسها.</p>
    <div style="display:flex;flex-direction:column;gap:8px">
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <span style="font-size:12px;font-weight:700;min-width:60px;color:#0891b2">SHA-256</span>
        <code id="h256" style="font-family:monospace;font-size:12px;word-break:break-all;color:#0f172a;flex:1">—</code>
        <button onclick="copyEl('h256')" class="t-btn t-btn-secondary" style="padding:4px 10px;font-size:12px">نسخ</button>
      </div>
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <span style="font-size:12px;font-weight:700;min-width:60px;color:#0891b2">SHA-1</span>
        <code id="h1" style="font-family:monospace;font-size:12px;word-break:break-all;color:#0f172a;flex:1">—</code>
        <button onclick="copyEl('h1')" class="t-btn t-btn-secondary" style="padding:4px 10px;font-size:12px">نسخ</button>
      </div>
    </div>
  </div>

  <?php tool_ad(); ?>
</main>
<script>
var mode='b64e';
function setMode(m,btn){
  mode=m;
  var chips=document.querySelectorAll('.chip-group .chip');
  for(var i=0;i<chips.length;i++) chips[i].classList.remove('active');
  btn.classList.add('active');
  runAll();
}
function runAll(){
  var v=document.getElementById('inp').value;
  var res='';
  try{
    if(mode==='b64e') res=btoa(unescape(encodeURIComponent(v)));
    else if(mode==='b64d') res=decodeURIComponent(escape(atob(v)));
    else if(mode==='urle') res=encodeURIComponent(v);
    else if(mode==='urld') res=decodeURIComponent(v);
    else if(mode==='html') res=v.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }catch(e){res='خطأ: '+e.message;}
  document.getElementById('out').value=res;
  computeHash(v);
}
function computeHash(str){
  if(!window.crypto||!window.crypto.subtle){document.getElementById('h256').textContent='غير مدعوم في هذا المتصفح';return;}
  var buf=new TextEncoder().encode(str);
  crypto.subtle.digest('SHA-256',buf).then(function(ab){document.getElementById('h256').textContent=Array.from(new Uint8Array(ab)).map(function(b){return b.toString(16).padStart(2,'0');}).join('');});
  crypto.subtle.digest('SHA-1',buf).then(function(ab){document.getElementById('h1').textContent=Array.from(new Uint8Array(ab)).map(function(b){return b.toString(16).padStart(2,'0');}).join('');});
}
function copyOut(){var v=document.getElementById('out').value;if(!v)return;if(navigator.clipboard)navigator.clipboard.writeText(v);else{var t=document.createElement('textarea');t.value=v;document.body.appendChild(t);t.select();document.execCommand('copy');document.body.removeChild(t);}}
function copyEl(id){var v=document.getElementById(id).textContent;if(v==='—')return;if(navigator.clipboard)navigator.clipboard.writeText(v);else{var t=document.createElement('textarea');t.value=v;document.body.appendChild(t);t.select();document.execCommand('copy');document.body.removeChild(t);}}
function swap(){var a=document.getElementById('inp').value,b=document.getElementById('out').value;document.getElementById('inp').value=b;runAll();}
function clear_all(){document.getElementById('inp').value='';document.getElementById('out').value='';document.getElementById('h256').textContent='—';document.getElementById('h1').textContent='—';}
</script>
<?php tool_footer(); ?>
