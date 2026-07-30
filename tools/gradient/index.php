<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'مولّد تدرجات CSS','url'=>TOOLS_BASE_URL.'/tools/gradient/',
    'description'=>'أنشئ تدرجات CSS جميلة (linear وradial) وانسخ الكود جاهزاً. أداة مجانية لمصممي الويب.',
    'applicationCategory'=>'DeveloperApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('مولّد تدرجات CSS — أنشئ Gradient جميل وانسخ الكود | yassota','أنشئ تدرجات CSS خطية ودائرية بألوان مخصصة، وانسخ الكود جاهزاً للاستخدام في مشاريعك. مجاني ولا يحتاج تسجيل.',$schema,'#8b5cf6');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:linear-gradient(135deg,#ede9fe,#fce7f3)">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#8b5cf6"/><stop offset="100%" stop-color="#ec4899"/></linearGradient></defs><rect x="2" y="3" width="20" height="18" rx="3" fill="url(#g)" stroke="none"/></svg>
    </div>
    <h1>مولّد تدرجات CSS</h1>
    <p>صمّم تدرجات ألوان CSS جميلة واحصل على الكود جاهزاً للنسخ — بدون أي خبرة برمجية.</p>
  </div>

  <div class="t-card" style="max-width:700px;margin:0 auto 24px">
    <!-- Preview -->
    <div id="preview" style="width:100%;height:200px;border-radius:12px;margin-bottom:20px;transition:all .3s"></div>

    <!-- Type selector -->
    <div style="display:flex;gap:0;border-radius:10px;overflow:hidden;border:1px solid #e2e8f0;margin-bottom:16px">
      <button id="btn-linear" onclick="setType('linear')" style="flex:1;padding:10px;background:#8b5cf6;color:#fff;border:none;font-size:13px;font-weight:700;cursor:pointer">خطي (Linear)</button>
      <button id="btn-radial" onclick="setType('radial')" style="flex:1;padding:10px;background:#f1f5f9;color:#475569;border:none;font-size:13px;font-weight:700;cursor:pointer">دائري (Radial)</button>
      <button id="btn-conic"  onclick="setType('conic')"  style="flex:1;padding:10px;background:#f1f5f9;color:#475569;border:none;font-size:13px;font-weight:700;cursor:pointer">مخروطي (Conic)</button>
    </div>

    <!-- Direction (linear only) -->
    <div id="dir-panel" style="margin-bottom:16px">
      <label class="t-label">الاتجاه</label>
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px">
        <?php $dirs=[['to right','← يمين'],['to left','يسار →'],['to bottom','↓ أسفل'],['to top','↑ أعلى'],['to bottom right','↘'],['to bottom left','↙'],['to top right','↗'],['to top left','↖']]; foreach($dirs as [$val,$lbl]): ?>
        <button class="dir-btn" data-dir="<?= $val ?>" onclick="setDir('<?= $val ?>')" style="padding:8px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;cursor:pointer"><?= $lbl ?></button>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Angle (linear) -->
    <div id="angle-panel" style="margin-bottom:16px">
      <label class="t-label">زاوية مخصصة: <span id="angle-val">135°</span></label>
      <input type="range" id="angle" min="0" max="360" value="135" oninput="document.getElementById('angle-val').textContent=this.value+'°';useAngle=true;render()" style="width:100%;accent-color:#8b5cf6">
    </div>

    <!-- Color stops -->
    <div style="margin-bottom:16px">
      <label class="t-label" style="display:flex;justify-content:space-between;align-items:center">
        <span>نقاط الألوان</span>
        <button onclick="addStop()" style="background:#ede9fe;color:#7c3aed;border:none;border-radius:6px;padding:4px 10px;font-size:12px;font-weight:700;cursor:pointer">+ إضافة لون</button>
      </label>
      <div id="stops" style="display:flex;flex-direction:column;gap:8px;margin-top:8px"></div>
    </div>

    <!-- Opacity -->
    <div style="margin-bottom:16px">
      <label class="t-label">الشفافية: <span id="opacity-val">100%</span></label>
      <input type="range" id="opacity" min="0" max="100" value="100" oninput="document.getElementById('opacity-val').textContent=this.value+'%';render()" style="width:100%;accent-color:#8b5cf6">
    </div>

    <!-- Output -->
    <label class="t-label">كود CSS</label>
    <div style="display:flex;gap:8px;margin-bottom:12px">
      <input type="text" id="css-out" class="t-input" dir="ltr" style="flex:1;font-family:monospace;font-size:12px;background:#f8fafc" readonly>
      <button onclick="copyCss()" class="t-btn" style="padding:0 16px;white-space:nowrap">نسخ</button>
    </div>
    <div id="copy-msg" style="display:none;color:#059669;font-size:13px">✅ تم النسخ!</div>
  </div>

  <!-- Presets -->
  <div class="t-card" style="margin-bottom:24px">
    <h2>تدرجات جاهزة</h2>
    <div id="presets" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:10px;margin-top:12px"></div>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card">
    <h2>دليل تدرجات CSS</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;margin-top:12px">
      <div style="background:#ede9fe;border-radius:12px;padding:14px">
        <div style="font-weight:700;color:#6d28d9;margin-bottom:6px">linear-gradient()</div>
        <p style="font-size:12px;color:#5b21b6;line-height:1.6">تدرج خطي من نقطة إلى أخرى. يمكن تحديد الاتجاه بالكلمات (to right) أو الزاوية (135deg).</p>
        <code style="font-size:11px;color:#4c1d95;display:block;margin-top:6px;direction:ltr">linear-gradient(135deg, #purple, #pink)</code>
      </div>
      <div style="background:#fce7f3;border-radius:12px;padding:14px">
        <div style="font-weight:700;color:#be185d;margin-bottom:6px">radial-gradient()</div>
        <p style="font-size:12px;color:#9d174d;line-height:1.6">تدرج دائري ينتشر من المركز للخارج. مثالي لتأثيرات الضوء والبريق.</p>
        <code style="font-size:11px;color:#831843;display:block;margin-top:6px;direction:ltr">radial-gradient(circle, #pink, #purple)</code>
      </div>
      <div style="background:#d1fae5;border-radius:12px;padding:14px">
        <div style="font-weight:700;color:#065f46;margin-bottom:6px">conic-gradient()</div>
        <p style="font-size:12px;color:#064e3b;line-height:1.6">تدرج مخروطي يدور حول نقطة مركزية. مثالي لرسم الرسوم البيانية الدائرية (Pie charts).</p>
        <code style="font-size:11px;color:#064e3b;display:block;margin-top:6px;direction:ltr">conic-gradient(#green 180deg, #blue)</code>
      </div>
      <div style="background:#dbeafe;border-radius:12px;padding:14px">
        <div style="font-weight:700;color:#1e40af;margin-bottom:6px">نقاط متعددة</div>
        <p style="font-size:12px;color:#1e3a8a;line-height:1.6">يمكن إضافة أي عدد من الألوان مع تحديد موضع كل لون بالنسبة المئوية.</p>
        <code style="font-size:11px;color:#1e3a8a;display:block;margin-top:6px;direction:ltr">linear-gradient(red 0%, blue 50%, green 100%)</code>
      </div>
    </div>
    <h3 style="margin-top:20px">استخدامات تدرجات CSS:</h3>
    <ul style="color:#64748b;line-height:2;padding-right:20px">
      <li>خلفيات الأقسام والبطاقات في الصفحات</li>
      <li>أزرار بتأثيرات بصرية جذابة</li>
      <li>شرائط التقدم والإحصائيات</li>
      <li>تأثيرات الظل والإضاءة على الصور</li>
      <li>الرسوم البيانية الدائرية بـ conic-gradient</li>
      <li>خلفيات بطاقات الإحصاء في لوحات التحكم</li>
    </ul>
  </div>
</main>

<script>
let gType = 'linear', gDir = 'to right', useAngle = false;
let stops = [
  {color:'#8b5cf6', pos:0},
  {color:'#ec4899', pos:100},
];

const presets = [
  {name:'غروب',stops:[{color:'#f97316',pos:0},{color:'#ec4899',pos:100}]},
  {name:'أرجواني',stops:[{color:'#8b5cf6',pos:0},{color:'#3b82f6',pos:100}]},
  {name:'أخضر',stops:[{color:'#10b981',pos:0},{color:'#3b82f6',pos:100}]},
  {name:'ذهبي',stops:[{color:'#f59e0b',pos:0},{color:'#ef4444',pos:100}]},
  {name:'فيروزي',stops:[{color:'#06b6d4',pos:0},{color:'#8b5cf6',pos:100}]},
  {name:'وردي',stops:[{color:'#f472b6',pos:0},{color:'#a855f7',pos:100}]},
  {name:'شروق',stops:[{color:'#fbbf24',pos:0},{color:'#f97316',pos:50},{color:'#ef4444',pos:100}]},
  {name:'ليل',stops:[{color:'#1e293b',pos:0},{color:'#334155',pos:100}]},
  {name:'محيط',stops:[{color:'#0ea5e9',pos:0},{color:'#0284c7',pos:100}]},
  {name:'ربيع',stops:[{color:'#86efac',pos:0},{color:'#6ee7b7',pos:50},{color:'#5eead4',pos:100}]},
  {name:'لافندر',stops:[{color:'#c4b5fd',pos:0},{color:'#8b5cf6',pos:100}]},
  {name:'نار',stops:[{color:'#fef08a',pos:0},{color:'#f97316',pos:50},{color:'#dc2626',pos:100}]},
];

function renderPresets(){
  document.getElementById('presets').innerHTML = presets.map((p,i)=>{
    const css = buildCss(p.stops,'linear','to right',false,135,100);
    return `<div onclick="applyPreset(${i})" style="cursor:pointer;border-radius:10px;overflow:hidden;border:2px solid transparent;transition:.2s" onmouseover="this.style.borderColor='#8b5cf6'" onmouseout="this.style.borderColor='transparent'">
      <div style="height:70px;background:${css}"></div>
      <div style="font-size:11px;padding:5px 6px;text-align:center;color:#475569">${p.name}</div>
    </div>`;
  }).join('');
}
function applyPreset(i){
  stops = presets[i].stops.map(s=>({...s}));
  gType='linear'; gDir='to right'; useAngle=false;
  document.getElementById('angle').value=135;
  document.getElementById('angle-val').textContent='135°';
  setType('linear');
  renderStops();
  render();
}

function buildCss(st, type, dir, angle, deg, opacity){
  const stStr = st.map(s=>`${s.color} ${s.pos}%`).join(', ');
  const op = opacity/100;
  let fn;
  if(type==='radial') fn=`radial-gradient(circle, ${stStr})`;
  else if(type==='conic') fn=`conic-gradient(${stStr})`;
  else fn=`linear-gradient(${angle?deg+'deg':dir}, ${stStr})`;
  if(op<1){
    // wrap with alpha stops
    return fn;
  }
  return fn;
}

function render(){
  const op = +document.getElementById('opacity').value;
  const deg = +document.getElementById('angle').value;
  const css = buildCss(stops, gType, gDir, useAngle, deg, op);
  const fullCss = `background: ${css};` + (op<100?` opacity: ${op/100};`:'');
  document.getElementById('preview').style.background = css;
  document.getElementById('preview').style.opacity = op/100;
  document.getElementById('css-out').value = `background: ${css};`;
}

function renderStops(){
  const cont = document.getElementById('stops');
  cont.innerHTML = stops.map((s,i)=>`
    <div style="display:flex;align-items:center;gap:10px">
      <input type="color" value="${s.color}" oninput="stops[${i}].color=this.value;render()" style="width:44px;height:38px;border-radius:8px;border:1px solid #e2e8f0;cursor:pointer;padding:2px">
      <input type="text" value="${s.color}" oninput="stops[${i}].color=this.value;render()" class="t-input" dir="ltr" style="width:100px;font-family:monospace;font-size:13px">
      <span style="font-size:12px;color:#64748b">الموضع:</span>
      <input type="number" value="${s.pos}" min="0" max="100" oninput="stops[${i}].pos=+this.value;render()" class="t-input" style="width:70px;text-align:center">%
      ${stops.length>2?`<button onclick="removeStop(${i})" style="background:#fee2e2;color:#dc2626;border:none;border-radius:6px;padding:6px 10px;cursor:pointer;font-size:12px">✕</button>`:''}
    </div>`).join('');
}

function addStop(){
  const mid = stops.length>0 ? Math.round((stops[stops.length-1].pos + 100)/2) : 50;
  stops.push({color:'#ffffff', pos: Math.min(mid,100)});
  renderStops(); render();
}
function removeStop(i){ stops.splice(i,1); renderStops(); render(); }

function setType(t){
  gType=t;
  ['linear','radial','conic'].forEach(x=>{
    document.getElementById('btn-'+x).style.background = x===t?'#8b5cf6':'#f1f5f9';
    document.getElementById('btn-'+x).style.color = x===t?'#fff':'#475569';
  });
  document.getElementById('dir-panel').style.display = t==='linear'?'block':'none';
  document.getElementById('angle-panel').style.display = t==='linear'?'block':'none';
  render();
}
function setDir(d){
  gDir=d; useAngle=false;
  document.querySelectorAll('.dir-btn').forEach(b=>{
    b.style.background = b.dataset.dir===d?'#ede9fe':'#f8fafc';
    b.style.borderColor = b.dataset.dir===d?'#8b5cf6':'#e2e8f0';
    b.style.color = b.dataset.dir===d?'#6d28d9':'#475569';
  });
  render();
}
function copyCss(){
  navigator.clipboard.writeText(document.getElementById('css-out').value);
  const m=document.getElementById('copy-msg'); m.style.display='block';
  setTimeout(()=>m.style.display='none',2000);
}

renderStops(); render(); renderPresets(); setDir('to right');
</script>
<?php tool_footer(); ?>
