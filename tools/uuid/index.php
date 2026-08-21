<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode(['@context'=>'https://schema.org','@type'=>'WebApplication','name'=>'مولّد UUID و GUID','url'=>TOOLS_BASE_URL.'/tools/uuid/','description'=>'أنشئ معرّفات UUID (v4) وGUID فريدة عشوائية — مجانية وتعمل في المتصفح.','applicationCategory'=>'DeveloperApplication','operatingSystem'=>'All','offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD']]);
tool_head('مولّد UUID وGUID — أنشئ معرّفات فريدة مجانياً | yassota','أنشئ معرّفات UUID v4 وGUID عشوائية فريدة للمشاريع البرمجية — مجاني، فوري، يعمل في المتصفح بدون إرسال بيانات.',$schema,'#7c3aed');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#ede9fe">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M9 9h6M9 12h4M9 15h2"/></svg>
    </div>
    <h1>مولّد UUID / GUID</h1>
    <p>أنشئ معرّفات UUID v4 عشوائية فريدة — للمشاريع البرمجية وقواعد البيانات وAPI.</p>
  </div>
  <div class="t-card" style="max-width:640px;margin:0 auto 24px">
    <div style="display:flex;gap:10px;align-items:center;margin-bottom:16px;flex-wrap:wrap">
      <label class="t-label" style="margin:0">عدد المعرّفات</label>
      <input type="number" id="count" class="t-input" value="1" min="1" max="100" style="width:80px;text-align:center">
      <select id="fmt" class="t-input" style="width:200px">
        <option value="uuid">UUID معياري (lowercase)</option>
        <option value="upper">UUID بحروف كبيرة</option>
        <option value="braces">GUID بأقواس {UUID}</option>
        <option value="nohyphens">بدون شرطات</option>
      </select>
      <button onclick="generate()" class="t-btn" style="background:#7c3aed">توليد</button>
    </div>
    <div style="position:relative">
      <textarea id="output" class="t-input" rows="8" dir="ltr" style="width:100%;font-family:monospace;font-size:13px;line-height:1.9;background:#f8fafc" readonly></textarea>
      <button onclick="copyAll()" style="position:absolute;top:8px;left:8px;background:#ede9fe;color:#7c3aed;border:none;border-radius:6px;padding:5px 10px;font-size:12px;cursor:pointer">نسخ الكل</button>
    </div>
    <div id="copy-msg" style="display:none;color:#059669;font-size:13px;margin-top:6px">✅ تم النسخ!</div>
  </div>
  <div class="t-card" style="margin-bottom:24px">
    <h2>ما هو UUID؟</h2>
    <p style="color:#64748b;line-height:1.8">UUID (Universally Unique Identifier) هو معرّف فريد عالمياً مكوّن من 128 بت (32 رقم سداسي عشري مع شرطات). نسخة v4 تُنشأ عشوائياً تماماً مما يجعل احتمال التكرار ضئيلاً للغاية. تُستخدم في قواعد البيانات، API، والبرمجة كمفاتيح أساسية آمنة.</p>
    <div style="background:#f8fafc;border-radius:8px;padding:12px;font-family:monospace;font-size:13px;direction:ltr;color:#475569;margin-top:10px">
      مثال: 550e8400-e29b-41d4-a716-446655440000<br>
      البنية: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;margin-top:14px">
      <?php foreach([['قواعد البيانات','مفاتيح أساسية في MySQL/PostgreSQL'],['REST API','معرّفات الموارد في endpoints'],['الملفات','تسمية ملفات رفع المستخدمين'],['الجلسات','Session IDs آمنة']] as [$t,$d]): ?>
      <div style="background:#f0fdf4;border-radius:8px;padding:10px">
        <div style="font-size:13px;font-weight:700;color:#065f46;margin-bottom:4px"><?= $t ?></div>
        <div style="font-size:12px;color:#64748b"><?= $d ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php tool_ad(); ?>
</main>
<script>
function uuidv4(){
  return ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g,c=>(c^crypto.getRandomValues(new Uint8Array(1))[0]&15>>c/4).toString(16));
}
function generate(){
  const n=Math.min(100,Math.max(1,+document.getElementById('count').value||1));
  const fmt=document.getElementById('fmt').value;
  const ids=[];
  for(let i=0;i<n;i++){
    let id=uuidv4();
    if(fmt==='upper') id=id.toUpperCase();
    else if(fmt==='braces') id='{'+id.toUpperCase()+'}';
    else if(fmt==='nohyphens') id=id.replace(/-/g,'');
    ids.push(id);
  }
  document.getElementById('output').value=ids.join('\n');
}
function copyAll(){
  navigator.clipboard.writeText(document.getElementById('output').value);
  const m=document.getElementById('copy-msg'); m.style.display='block';
  setTimeout(()=>m.style.display='none',2000);
}
generate();
</script>
<?php tool_footer(); ?>
