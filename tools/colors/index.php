<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode(['@context'=>'https://schema.org','@type'=>'WebApplication','name'=>'منتقي الألوان وتوليد لوحات الألوان','url'=>TOOLS_BASE_URL.'/','description'=>'اختر لوناً وولّد تلقائياً لوحة ألوان متناسقة مجاناً','applicationCategory'=>'DesignApplication','operatingSystem'=>'All','offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD']]);
tool_head('منتقي الألوان — لوحة ألوان متناسقة مجانية | yassota أدوات','اختر أي لون وحوّله فوراً إلى قيم HEX و RGB و HSL ولوحة ألوان كاملة — مجاناً في متصفحك',$schema,'#7c3aed');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#ede9fe">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><circle cx="13.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="10.5" r="2.5"/><circle cx="8.5" cy="7.5" r="2.5"/><circle cx="6.5" cy="12.5" r="2.5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 011.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>
    </div>
    <h1>منتقي الألوان ومولّد لوحات الألوان</h1>
    <p>اختر لوناً أو أدخل قيمته، وستحصل فوراً على لوحة ألوان متناسقة مع قيم HEX وRGB وHSL — كل شيء في متصفحك.</p>
  </div>

  <div class="t-card">
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;flex-wrap:wrap">
      <input type="color" id="main-color" value="#7c3aed" oninput="onColorChange(this.value)" style="width:64px;height:64px;border:2px solid #e2e8f0;border-radius:12px;cursor:pointer;padding:4px;background:none">
      <div style="flex:1;min-width:180px">
        <label class="t-label">قيمة اللون</label>
        <input type="text" id="hex-in" value="#7c3aed" class="t-input" style="font-family:monospace" oninput="tryHex(this.value)" placeholder="#rrggbb">
      </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-bottom:20px" id="values-grid">
      <div class="t-card" style="margin:0;padding:14px">
        <div style="font-size:11px;color:#64748b;margin-bottom:4px">HEX</div>
        <div id="v-hex" style="font-family:monospace;font-size:15px;font-weight:700">#7c3aed</div>
        <button onclick="copyVal('v-hex')" class="t-btn t-btn-secondary" style="padding:4px 10px;font-size:12px;margin-top:8px">نسخ</button>
      </div>
      <div class="t-card" style="margin:0;padding:14px">
        <div style="font-size:11px;color:#64748b;margin-bottom:4px">RGB</div>
        <div id="v-rgb" style="font-family:monospace;font-size:15px;font-weight:700">124, 58, 237</div>
        <button onclick="copyVal('v-rgb')" class="t-btn t-btn-secondary" style="padding:4px 10px;font-size:12px;margin-top:8px">نسخ</button>
      </div>
      <div class="t-card" style="margin:0;padding:14px">
        <div style="font-size:11px;color:#64748b;margin-bottom:4px">HSL</div>
        <div id="v-hsl" style="font-family:monospace;font-size:15px;font-weight:700"></div>
        <button onclick="copyVal('v-hsl')" class="t-btn t-btn-secondary" style="padding:4px 10px;font-size:12px;margin-top:8px">نسخ</button>
      </div>
      <div class="t-card" style="margin:0;padding:14px">
        <div style="font-size:11px;color:#64748b;margin-bottom:4px">CSS</div>
        <div id="v-css" style="font-family:monospace;font-size:13px;font-weight:700"></div>
        <button onclick="copyVal('v-css')" class="t-btn t-btn-secondary" style="padding:4px 10px;font-size:12px;margin-top:8px">نسخ</button>
      </div>
    </div>

    <label class="t-label">معاينة اللون</label>
    <div id="preview-bar" style="height:60px;border-radius:12px;margin-bottom:16px;transition:background .2s;background:#7c3aed"></div>

    <label class="t-label">درجات (Shades)</label>
    <div id="shades-row" style="display:flex;gap:4px;height:50px;border-radius:10px;overflow:hidden;margin-bottom:16px"></div>

    <label class="t-label">لوحة ألوان متناسقة (Palette)</label>
    <div id="palette-row" style="display:flex;flex-wrap:wrap;gap:8px"></div>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card">
    <h2>ألوان يشيوعة جاهزة</h2>
    <div class="chip-group" id="preset-row"></div>
  </div>
</main>
<script>
var PRESETS=['#ef4444','#f97316','#eab308','#22c55e','#14b8a6','#06b6d4','#3b82f6','#6366f1','#7c3aed','#ec4899','#0f172a','#64748b'];

function hexToRgb(h){h=h.replace('#','');if(h.length===3)h=h[0]+h[0]+h[1]+h[1]+h[2]+h[2];var n=parseInt(h,16);return{r:(n>>16)&255,g:(n>>8)&255,b:n&255};}
function rgbToHex(r,g,b){return '#'+[r,g,b].map(function(x){return ('0'+x.toString(16)).slice(-2);}).join('');}
function rgbToHsl(r,g,b){r/=255;g/=255;b/=255;var mx=Math.max(r,g,b),mn=Math.min(r,g,b),h,s,l=(mx+mn)/2;if(mx===mn){h=s=0;}else{var d=mx-mn;s=l>.5?d/(2-mx-mn):d/(mx+mn);switch(mx){case r:h=(g-b)/d+(g<b?6:0);break;case g:h=(b-r)/d+2;break;default:h=(r-g)/d+4;}h/=6;}return{h:Math.round(h*360),s:Math.round(s*100),l:Math.round(l*100)};}
function hslToRgb(h,s,l){s/=100;l/=100;var c=(1-Math.abs(2*l-1))*s,x=c*(1-Math.abs((h/60)%2-1)),m=l-c/2,r=0,g=0,b=0;if(h<60){r=c;g=x;}else if(h<120){r=x;g=c;}else if(h<180){g=c;b=x;}else if(h<240){g=x;b=c;}else if(h<300){r=x;b=c;}else{r=c;b=x;}return{r:Math.round((r+m)*255),g:Math.round((g+m)*255),b:Math.round((b+m)*255)};}
function lighten(h,pct){var rgb=hexToRgb(h),hsl=rgbToHsl(rgb.r,rgb.g,rgb.b);hsl.l=Math.min(100,hsl.l+pct);var r=hslToRgb(hsl.h,hsl.s,hsl.l);return rgbToHex(r.r,r.g,r.b);}
function darken(h,pct){var rgb=hexToRgb(h),hsl=rgbToHsl(rgb.r,rgb.g,rgb.b);hsl.l=Math.max(0,hsl.l-pct);var r=hslToRgb(hsl.h,hsl.s,hsl.l);return rgbToHex(r.r,r.g,r.b);}
function complement(h){var rgb=hexToRgb(h),hsl=rgbToHsl(rgb.r,rgb.g,rgb.b);hsl.h=(hsl.h+180)%360;var r=hslToRgb(hsl.h,hsl.s,hsl.l);return rgbToHex(r.r,r.g,r.b);}
function analogous(h,offset){var rgb=hexToRgb(h),hsl=rgbToHsl(rgb.r,rgb.g,rgb.b);hsl.h=(hsl.h+offset+360)%360;var r=hslToRgb(hsl.h,hsl.s,hsl.l);return rgbToHex(r.r,r.g,r.b);}

function onColorChange(hex){
  var rgb=hexToRgb(hex),hsl=rgbToHsl(rgb.r,rgb.g,rgb.b);
  document.getElementById('hex-in').value=hex;
  document.getElementById('v-hex').textContent=hex;
  document.getElementById('v-rgb').textContent=rgb.r+', '+rgb.g+', '+rgb.b;
  document.getElementById('v-hsl').textContent=hsl.h+'°, '+hsl.s+'%, '+hsl.l+'%';
  document.getElementById('v-css').textContent='color: '+hex+';';
  document.getElementById('preview-bar').style.background=hex;
  buildShades(hex);
  buildPalette(hex);
}
function tryHex(v){v=v.trim();if(/^#?[0-9a-fA-F]{6}$/.test(v)){var h=v.startsWith('#')?v:'#'+v;document.getElementById('main-color').value=h;onColorChange(h);}}

function buildShades(hex){
  var el=document.getElementById('shades-row');el.innerHTML='';
  var steps=[-40,-30,-20,-10,0,10,20,30,40,50];
  for(var i=0;i<steps.length;i++){
    var c=steps[i]<0?darken(hex,-steps[i]):lighten(hex,steps[i]);
    var d=document.createElement('div');
    d.style.cssText='flex:1;background:'+c+';cursor:pointer;';
    d.title=c;
    (function(col){d.onclick=function(){onColorChange(col);document.getElementById('main-color').value=col;};})(c);
    el.appendChild(d);
  }
}
function buildPalette(hex){
  var el=document.getElementById('palette-row');el.innerHTML='';
  var palette=[hex,complement(hex),analogous(hex,30),analogous(hex,-30),analogous(hex,60),analogous(hex,-60)];
  for(var i=0;i<palette.length;i++){
    var c=palette[i];
    var d=document.createElement('div');
    d.style.cssText='width:52px;height:52px;border-radius:10px;background:'+c+';cursor:pointer;border:2px solid rgba(0,0,0,.08);position:relative;';
    d.title=c;
    (function(col){d.onclick=function(){onColorChange(col);document.getElementById('main-color').value=col;};})(c);
    var lbl=document.createElement('div');
    lbl.style.cssText='position:absolute;bottom:-18px;left:0;right:0;text-align:center;font-size:10px;color:#64748b;font-family:monospace';
    lbl.textContent=c;
    d.appendChild(lbl);
    el.appendChild(d);
  }
}

var presetRow=document.getElementById('preset-row');
for(var pi=0;pi<PRESETS.length;pi++){
  (function(c){
    var b=document.createElement('button');
    b.className='chip';
    b.style.background=c;
    b.style.borderColor=c;
    b.style.color='#fff';
    b.style.minWidth='38px';
    b.title=c;
    b.onclick=function(){document.getElementById('main-color').value=c;onColorChange(c);};
    presetRow.appendChild(b);
  })(PRESETS[pi]);
}

function copyVal(id){
  var v=document.getElementById(id).textContent;
  if(navigator.clipboard){navigator.clipboard.writeText(v);}
  else{var t=document.createElement('textarea');t.value=v;document.body.appendChild(t);t.select();document.execCommand('copy');document.body.removeChild(t);}
}
onColorChange('#7c3aed');
</script>
<?php tool_footer(); ?>
