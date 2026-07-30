<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode(['@context'=>'https://schema.org','@type'=>'WebApplication','name'=>'حاسبة نسبة الأبعاد','url'=>TOOLS_BASE_URL.'/tools/aspect-ratio/','description'=>'احسب نسبة الأبعاد للصور والفيديو (16:9، 4:3، 1:1) وحوّل بين أبعاد مختلفة مع الحفاظ على النسبة.','applicationCategory'=>'DeveloperApplication','operatingSystem'=>'All','offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD']]);
tool_head('حاسبة نسبة الأبعاد — 16:9 و4:3 وأبعاد مخصصة | yassota','احسب الأبعاد المثالية للصور والفيديو مع الحفاظ على نسبة الأبعاد — مناسبة للمصورين والمصممين ومطوري الويب.',$schema,'#2563eb');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#dbeafe">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18M7 6v12"/></svg>
    </div>
    <h1>حاسبة نسبة الأبعاد</h1>
    <p>احسب الأبعاد الصحيحة للصور والفيديو بأي نسبة — مع الحفاظ على النسبة تلقائياً.</p>
  </div>
  <!-- Common ratios -->
  <div class="t-card" style="max-width:600px;margin:0 auto 16px">
    <h2 style="font-size:15px;margin-bottom:12px">نسب أبعاد شائعة</h2>
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px">
      <?php $ratios=[['16:9','فيديو HD'],['4:3','شاشات قديمة'],['1:1','مربع / إنستغرام'],['9:16','ستوري / تيك توك'],['21:9','سينمائي'],['3:2','DSLR'],['5:4','مطبوعات'],['2.39:1','سينما أمريكية']];
      foreach($ratios as [$r,$n]): [$w,$h]=explode(':',$r); ?>
      <button onclick="setRatio(<?= $w ?>,<?= $h ?>)" style="background:#dbeafe;color:#1d4ed8;border:none;border-radius:8px;padding:7px 12px;font-size:12px;cursor:pointer;text-align:center">
        <div style="font-weight:700"><?= $r ?></div><div style="font-size:10px;opacity:.8"><?= $n ?></div>
      </button>
      <?php endforeach; ?>
    </div>
    <div style="display:grid;grid-template-columns:1fr auto 1fr;gap:10px;align-items:end;margin-bottom:14px">
      <div>
        <label class="t-label">العرض (Width)</label>
        <input type="number" id="w1" class="t-input" value="1920" min="1" oninput="calcH()" dir="ltr" style="width:100%">
      </div>
      <div style="text-align:center;color:#64748b;font-size:18px;font-weight:700;padding-bottom:8px">×</div>
      <div>
        <label class="t-label">الارتفاع (Height)</label>
        <input type="number" id="h1" class="t-input" value="1080" min="1" oninput="calcW()" dir="ltr" style="width:100%">
      </div>
    </div>
    <div id="ratio-result" style="text-align:center;padding:16px;background:#eff6ff;border-radius:12px">
      <div style="font-size:32px;font-weight:900;color:#2563eb" id="ratio-display">16:9</div>
      <div style="font-size:13px;color:#64748b;margin-top:4px" id="ratio-desc"></div>
    </div>
  </div>
  <!-- Scale calculator -->
  <div class="t-card" style="max-width:600px;margin:0 auto 24px">
    <h2 style="font-size:15px;margin-bottom:12px">تحويل الأبعاد مع الحفاظ على النسبة</h2>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
      <div>
        <label class="t-label">العرض الجديد</label>
        <input type="number" id="new-w" class="t-input" value="1280" min="1" oninput="scaleFromW()" dir="ltr" style="width:100%">
      </div>
      <div>
        <label class="t-label">الارتفاع الجديد</label>
        <input type="number" id="new-h" class="t-input" value="720" min="1" oninput="scaleFromH()" dir="ltr" style="width:100%">
      </div>
    </div>
    <div id="scale-sizes" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:8px;margin-top:4px"></div>
  </div>
  <?php tool_ad(); ?>
  <div class="t-card">
    <h2>جدول الأبعاد الشائعة</h2>
    <div style="overflow-x:auto;margin-top:10px">
      <table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead><tr style="background:#eff6ff"><th style="padding:8px 12px;text-align:right">الاستخدام</th><th style="padding:8px 12px;text-align:right">النسبة</th><th style="padding:8px 12px;text-align:left;direction:ltr">الدقة الشائعة</th></tr></thead>
        <tbody>
        <?php $table=[
          ['يوتيوب / فيديو HD','16:9','1920×1080, 1280×720'],
          ['صورة إنستغرام مربعة','1:1','1080×1080'],
          ['ستوري إنستغرام','9:16','1080×1920'],
          ['تغريدة تويتر','16:9','1200×675'],
          ['غلاف فيسبوك','2.7:1','820×312'],
          ['صورة مقالة','3:2','1200×800'],
          ['بانر موقع','3:1','900×300'],
          ['أيقونة تطبيق','1:1','512×512'],
        ]; foreach($table as [$use,$rat,$res]): ?>
        <tr style="border-bottom:1px solid #f1f5f9">
          <td style="padding:8px 12px;font-weight:600"><?= $use ?></td>
          <td style="padding:8px 12px;color:#2563eb;font-weight:700"><?= $rat ?></td>
          <td style="padding:8px 12px;font-family:monospace;direction:ltr"><?= $res ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
<script>
function gcd(a,b){return b?gcd(b,a%b):a;}
function simplify(w,h){const g=gcd(Math.round(w),Math.round(h));return[Math.round(w)/g,Math.round(h)/g];}
function updateRatioDisplay(){
  const w=+document.getElementById('w1').value||1;
  const h=+document.getElementById('h1').value||1;
  const[rw,rh]=simplify(w,h);
  document.getElementById('ratio-display').textContent=rw+':'+rh;
  document.getElementById('ratio-desc').textContent=`${w}×${h} — نسبة ${(w/h).toFixed(4)}`;
  generateScales(w,h);
}
function calcH(){
  const w=+document.getElementById('w1').value;
  const ratio=+document.getElementById('new-w').value / (+document.getElementById('new-h').value||1);
  updateRatioDisplay();
}
function calcW(){updateRatioDisplay();}
function setRatio(rw,rh){
  const w=+document.getElementById('w1').value||1920;
  const h=Math.round(w*rh/rw);
  document.getElementById('h1').value=h;
  document.getElementById('new-h').value=Math.round(+document.getElementById('new-w').value*rh/rw);
  updateRatioDisplay();
}
function scaleFromW(){
  const nw=+document.getElementById('new-w').value;
  const w=+document.getElementById('w1').value||1;
  const h=+document.getElementById('h1').value||1;
  document.getElementById('new-h').value=Math.round(nw*h/w);
  updateRatioDisplay();
}
function scaleFromH(){
  const nh=+document.getElementById('new-h').value;
  const w=+document.getElementById('w1').value||1;
  const h=+document.getElementById('h1').value||1;
  document.getElementById('new-w').value=Math.round(nh*w/h);
  updateRatioDisplay();
}
function generateScales(w,h){
  const base=[240,360,480,720,1080,1440,1920,2560,3840].map(bw=>({w:bw,h:Math.round(bw*h/w)}));
  document.getElementById('scale-sizes').innerHTML=base.map(s=>`
    <div style="background:#f8fafc;border-radius:8px;padding:8px;text-align:center;cursor:pointer" onclick="document.getElementById('new-w').value=${s.w};document.getElementById('new-h').value=${s.h}">
      <div style="font-size:12px;font-weight:700;color:#2563eb;direction:ltr">${s.w}×${s.h}</div>
    </div>`).join('');
}
updateRatioDisplay();
</script>
<?php tool_footer(); ?>
