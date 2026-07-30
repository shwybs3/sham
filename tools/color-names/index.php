<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode(['@context'=>'https://schema.org','@type'=>'WebApplication','name'=>'قاموس أسماء الألوان بالعربية','url'=>TOOLS_BASE_URL.'/tools/color-names/','description'=>'ابحث عن أي لون بالعربي أو HEX وتعرف على اسمه الإنجليزي والعربي ورمز CSS.','applicationCategory'=>'DeveloperApplication','operatingSystem'=>'All','offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD']]);
tool_head('أسماء الألوان بالعربية — HEX وCSS وRGB | yassota','ابحث عن أسماء الألوان بالعربية والإنجليزية مع رمز HEX وقيم RGB — مرجع كامل لمصممي الويب والجرافيك.',$schema,'#be185d');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:linear-gradient(135deg,#fce7f3,#dbeafe)"><span style="font-size:22px">🎨</span></div>
    <h1>أسماء الألوان بالعربية</h1>
    <p>ابحث عن أي لون بالعربي أو HEX وتعرّف على اسمه الكامل مع قيم CSS وRGB.</p>
  </div>
  <div class="t-card" style="max-width:640px;margin:0 auto 24px">
    <div style="display:flex;gap:10px;margin-bottom:16px">
      <input type="text" id="search" class="t-input" style="flex:1" placeholder="ابحث: أحمر، blue، #ff0000…" oninput="search()">
      <input type="color" id="color-picker" style="width:44px;height:44px;border-radius:8px;border:1px solid #e2e8f0;cursor:pointer;padding:2px" onchange="pickColor(this.value)">
    </div>
    <div id="results-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:8px"></div>
  </div>
  <?php tool_ad(); ?>
  <div class="t-card">
    <h2>أشهر ألوان CSS</h2>
    <div id="all-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:8px;margin-top:12px"></div>
  </div>
</main>
<script>
const colors=[
  ['#FF0000','أحمر','Red'],['#00FF00','أخضر ساطع','Lime'],['#0000FF','أزرق','Blue'],
  ['#FFFF00','أصفر','Yellow'],['#FF00FF','زهري ساطع','Fuchsia'],['#00FFFF','سماوي','Cyan'],
  ['#FFFFFF','أبيض','White'],['#000000','أسود','Black'],['#808080','رمادي','Gray'],
  ['#C0C0C0','فضي','Silver'],['#800000','كستنائي','Maroon'],['#808000','زيتوني','Olive'],
  ['#008000','أخضر','Green'],['#800080','بنفسجي','Purple'],['#008080','أزرق مخضر','Teal'],
  ['#000080','كحلي','Navy'],['#FFA500','برتقالي','Orange'],['#FFC0CB','وردي','Pink'],
  ['#A52A2A','بني','Brown'],['#D2691E','شوكولاتي','Chocolate'],['#FFD700','ذهبي','Gold'],
  ['#ADFF2F','أخضر-أصفر','GreenYellow'],['#4169E1','أزرق ملكي','RoyalBlue'],['#DC143C','كرمزي','Crimson'],
  ['#FF6347','طماطمي','Tomato'],['#FF7F50','مرجاني','Coral'],['#20B2AA','أخضر بحري','LightSeaGreen'],
  ['#9370DB','بنفسجي متوسط','MediumPurple'],['#3CB371','أخضر متوسط','MediumSeaGreen'],['#F4A460','رملي','SandyBrown'],
  ['#DEB887','بيج','Burlywood'],['#87CEEB','أزرق سماوي','SkyBlue'],['#F0E68C','كاكي','Khaki'],
  ['#E6E6FA','خزامى','Lavender'],['#7B68EE','أرجواني متوسط','MediumSlateBlue'],['#00CED1','أزرق داكن','DarkTurquoise'],
  ['#FF1493','وردي عميق','DeepPink'],['#1E90FF','أزرق دودجر','DodgerBlue'],['#FFDAB9','خوخي','PeachPuff'],
];
function hexToRgb(h){const r=parseInt(h.slice(1,3),16),g=parseInt(h.slice(3,5),16),b=parseInt(h.slice(5,7),16);return{r,g,b};}
function textColor(hex){const{r,g,b}=hexToRgb(hex);return(r*0.299+g*0.587+b*0.114)>128?'#1e293b':'#ffffff';}
function card(c){const{r,g,b}=hexToRgb(c[0]);const tc=textColor(c[0]);return`
  <div style="border-radius:10px;overflow:hidden;cursor:pointer;border:1px solid #e2e8f0" onclick="navigator.clipboard.writeText('${c[0]}')">
    <div style="height:80px;background:${c[0]};display:flex;align-items:center;justify-content:center">
      <span style="color:${tc};font-family:monospace;font-size:12px;font-weight:700">${c[0]}</span>
    </div>
    <div style="padding:8px;background:#fff">
      <div style="font-size:13px;font-weight:700;color:#0f172a">${c[1]}</div>
      <div style="font-size:11px;color:#64748b">${c[2]}</div>
      <div style="font-size:10px;color:#94a3b8;font-family:monospace;direction:ltr">rgb(${r},${g},${b})</div>
    </div>
  </div>`;}
function render(arr){document.getElementById('results-grid').innerHTML=arr.map(card).join('');}
function search(){
  const q=document.getElementById('search').value.toLowerCase();
  if(!q){document.getElementById('results-grid').innerHTML='';return;}
  const res=colors.filter(c=>c[0].toLowerCase().includes(q)||c[1].includes(q)||c[2].toLowerCase().includes(q));
  render(res.length?res:[]);
}
function pickColor(hex){
  document.getElementById('search').value=hex;
  const res=colors.filter(c=>c[0].toLowerCase()===hex.toLowerCase());
  render(res.length?res:[[hex,'لون مخصص','Custom Color']]);
}
document.getElementById('all-grid').innerHTML=colors.map(card).join('');
</script>
<?php tool_footer(); ?>
