<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'حاسبة النسب المئوية','url'=>TOOLS_BASE_URL.'/tools/percentage/',
    'description'=>'احسب النسب المئوية بكل أنواعها: نسبة زيادة أو نقصان، نسبة رقم من آخر، واحسب X% من Y. حاسبة مجانية.',
    'applicationCategory'=>'UtilitiesApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('حاسبة النسب المئوية — احسب النسبة المئوية بكل أنواعها | yassota','احسب نسبة التغيير، نسبة رقم من رقم آخر، X% من Y، أو ما هو الرقم الأصلي. حاسبة نسب مئوية شاملة ومجانية.',$schema,'#ea580c');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#ffedd5">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
    </div>
    <h1>حاسبة النسب المئوية</h1>
    <p>ستة أنواع مختلفة من حسابات النسب المئوية — كل ما تحتاجه في مكان واحد.</p>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-bottom:24px">

    <!-- Type 1: X% of Y -->
    <div class="t-card">
      <h3 style="color:#ea580c;margin-bottom:12px">كم يساوي X% من Y؟</h3>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:10px">
        <input type="number" id="p1-pct" class="t-input" placeholder="25" style="width:90px"> %
        من
        <input type="number" id="p1-num" class="t-input" placeholder="200" style="width:90px">
        <button onclick="calc1()" class="t-btn" style="background:#ea580c;padding:8px 14px;font-size:13px">احسب</button>
      </div>
      <div id="r1" style="font-size:18px;font-weight:700;color:#ea580c;min-height:28px"></div>
    </div>

    <!-- Type 2: X is what % of Y -->
    <div class="t-card">
      <h3 style="color:#7c3aed;margin-bottom:12px">ما نسبة X من Y؟</h3>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:10px">
        <input type="number" id="p2-x" class="t-input" placeholder="50" style="width:90px">
        من
        <input type="number" id="p2-y" class="t-input" placeholder="200" style="width:90px">
        <button onclick="calc2()" class="t-btn" style="background:#7c3aed;padding:8px 14px;font-size:13px">احسب</button>
      </div>
      <div id="r2" style="font-size:18px;font-weight:700;color:#7c3aed;min-height:28px"></div>
    </div>

    <!-- Type 3: % change -->
    <div class="t-card">
      <h3 style="color:#059669;margin-bottom:12px">نسبة التغيير (زيادة/نقصان)</h3>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:10px">
        من
        <input type="number" id="p3-from" class="t-input" placeholder="100" style="width:90px">
        إلى
        <input type="number" id="p3-to" class="t-input" placeholder="150" style="width:90px">
        <button onclick="calc3()" class="t-btn" style="background:#059669;padding:8px 14px;font-size:13px">احسب</button>
      </div>
      <div id="r3" style="font-size:18px;font-weight:700;min-height:28px"></div>
    </div>

    <!-- Type 4: original value -->
    <div class="t-card">
      <h3 style="color:#2563eb;margin-bottom:12px">ما الرقم الأصلي قبل الخصم؟</h3>
      <p style="font-size:12px;color:#94a3b8;margin-bottom:10px">مثال: السعر بعد خصم 20% هو 80، ما السعر الأصلي؟</p>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:10px">
        القيمة
        <input type="number" id="p4-val" class="t-input" placeholder="80" style="width:90px">
        بعد خصم
        <input type="number" id="p4-pct" class="t-input" placeholder="20" style="width:80px"> %
        <button onclick="calc4()" class="t-btn" style="background:#2563eb;padding:8px 14px;font-size:13px">احسب</button>
      </div>
      <div id="r4" style="font-size:18px;font-weight:700;color:#2563eb;min-height:28px"></div>
    </div>

    <!-- Type 5: add percent -->
    <div class="t-card">
      <h3 style="color:#d97706;margin-bottom:12px">أضف نسبة مئوية (زيادة/ضريبة)</h3>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:10px">
        <input type="number" id="p5-base" class="t-input" placeholder="1000" style="width:90px">
        +
        <input type="number" id="p5-add" class="t-input" placeholder="15" style="width:80px"> %
        <button onclick="calc5()" class="t-btn" style="background:#d97706;padding:8px 14px;font-size:13px">احسب</button>
      </div>
      <div id="r5" style="font-size:18px;font-weight:700;color:#d97706;min-height:28px"></div>
    </div>

    <!-- Type 6: discount -->
    <div class="t-card">
      <h3 style="color:#dc2626;margin-bottom:12px">سعر ما بعد الخصم</h3>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:10px">
        السعر
        <input type="number" id="p6-price" class="t-input" placeholder="500" style="width:90px">
        خصم
        <input type="number" id="p6-disc" class="t-input" placeholder="30" style="width:80px"> %
        <button onclick="calc6()" class="t-btn" style="background:#dc2626;padding:8px 14px;font-size:13px">احسب</button>
      </div>
      <div id="r6" style="font-size:18px;font-weight:700;color:#dc2626;min-height:28px"></div>
    </div>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card">
    <h2>استخدامات النسب المئوية في الحياة اليومية</h2>
    <p style="color:#64748b;line-height:1.8">النسب المئوية من أكثر الأدوات الرياضية استخداماً في الحياة اليومية. نواجهها في كل مكان: نسب الخصومات في المحلات، معدلات الفائدة البنكية، نسب الضريبة (VAT)، التقييمات والدرجات، الأوزان في التغذية، وإحصاءات الرياضة والأعمال.</p>
    <h3 style="margin-top:16px">أمثلة عملية</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;margin-top:8px">
      <?php $examples = [
        ['🛍️','خصم 30% على سلعة بـ500 ريال','= 350 ريال'],
        ['📊','نسبة الزيادة من 4000 إلى 5000','= 25%'],
        ['🏦','ضريبة 15% على فاتورة 1000','= 1150 ريال'],
        ['📝','درجة 45 من 60','= 75%'],
      ]; foreach ($examples as [$ic,$txt,$res]): ?>
      <div style="background:#f8fafc;border-radius:10px;padding:12px">
        <div style="font-size:20px;margin-bottom:6px"><?= $ic ?></div>
        <div style="font-size:12px;color:#475569;margin-bottom:4px"><?= $txt ?></div>
        <div style="font-size:13px;font-weight:700;color:#0f172a"><?= $res ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</main>
<script>
function fmt(n) { return isFinite(n) ? parseFloat(n.toFixed(4)).toLocaleString('ar') : 'خطأ'; }
function calc1(){const p=+document.getElementById('p1-pct').value,y=+document.getElementById('p1-num').value;document.getElementById('r1').textContent=p&&y?fmt(p/100*y):'—';}
function calc2(){const x=+document.getElementById('p2-x').value,y=+document.getElementById('p2-y').value;document.getElementById('r2').textContent=x&&y?fmt(x/y*100)+'%':'—';}
function calc3(){
  const f=+document.getElementById('p3-from').value,t=+document.getElementById('p3-to').value;
  if(!f||!t){document.getElementById('r3').textContent='—';return;}
  const pct=(t-f)/Math.abs(f)*100;
  const r3=document.getElementById('r3');
  r3.textContent=fmt(Math.abs(pct))+'% '+(pct>=0?'زيادة':'نقصان');
  r3.style.color=pct>=0?'#059669':'#dc2626';
}
function calc4(){const v=+document.getElementById('p4-val').value,p=+document.getElementById('p4-pct').value;document.getElementById('r4').textContent=v&&p?fmt(v/(1-p/100)):'—';}
function calc5(){const b=+document.getElementById('p5-base').value,p=+document.getElementById('p5-add').value;document.getElementById('r5').textContent=b&&p!=null?fmt(b*(1+p/100)):'—';}
function calc6(){const pr=+document.getElementById('p6-price').value,d=+document.getElementById('p6-disc').value;
  if(pr&&d!=null){const after=pr*(1-d/100);document.getElementById('r6').innerHTML=`${fmt(after)} <span style="font-size:13px;color:#94a3b8">(وفرت ${fmt(pr-after)})</span>`;}
  else document.getElementById('r6').textContent='—';
}
</script>
<?php tool_footer(); ?>
