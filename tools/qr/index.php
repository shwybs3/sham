<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'مولّد QR Code مجاني','url'=>TOOLS_BASE_URL.'/',
    'description'=>'أنشئ رمز QR Code لأي رابط أو نص مجاناً وحمّله كصورة PNG',
    'applicationCategory'=>'UtilitiesApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('مولّد QR Code مجاني | yassota أدوات','أنشئ رمز QR Code لأي رابط أو نص أو بريد إلكتروني مجاناً وحمّله فوراً',$schema,'#7c3aed');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#ede9fe">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h1v1m2 0h1v1m-1 2v1h1m-2 0h-1v-1"/></svg>
    </div>
    <h1>مولّد QR Code المجاني</h1>
    <p>أنشئ رمز QR Code لأي رابط أو نص أو معلومات تواصل مجاناً — يُولَّد مباشرة في متصفحك بدون رفع أي بيانات للسيرفر.</p>
  </div>

  <div class="t-card">
    <div style="margin-bottom:14px">
      <label class="t-label">أدخل الرابط أو النص</label>
      <input type="text" id="qr-input" class="t-input" placeholder="https://yassota.com أو أي نص تريده..." oninput="generateQR()" value="">
    </div>
    <div class="t-row" style="margin-bottom:14px">
      <div class="t-col">
        <label class="t-label">حجم الرمز (بكسل)</label>
        <select id="qr-size" class="t-input" onchange="generateQR()">
          <option value="200">200×200</option>
          <option value="300" selected>300×300</option>
          <option value="400">400×400</option>
          <option value="500">500×500</option>
        </select>
      </div>
      <div class="t-col">
        <label class="t-label">اللون الأمامي</label>
        <input type="color" id="qr-color" value="#000000" class="t-input" style="padding:4px;height:42px;cursor:pointer" onchange="generateQR()">
      </div>
      <div class="t-col">
        <label class="t-label">لون الخلفية</label>
        <input type="color" id="qr-bg" value="#ffffff" class="t-input" style="padding:4px;height:42px;cursor:pointer" onchange="generateQR()">
      </div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px">
      <button class="t-btn" onclick="generateQR()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h1v1m2 0h1v1"/></svg>
        توليد QR
      </button>
      <button class="t-btn t-btn-success" id="dl-btn" onclick="downloadQR()" style="display:none">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
        تحميل PNG
      </button>
    </div>
    <div id="qr-out" style="text-align:center;display:none">
      <canvas id="qr-canvas" style="border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.12);max-width:100%"></canvas>
    </div>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card">
    <h2>استخدامات QR Code</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;font-size:13px;color:#475569">
      <div style="padding:10px;background:#f8fafc;border-radius:8px">🔗 روابط المواقع</div>
      <div style="padding:10px;background:#f8fafc;border-radius:8px">📱 تطبيقات الموبايل</div>
      <div style="padding:10px;background:#f8fafc;border-radius:8px">📧 عناوين البريد</div>
      <div style="padding:10px;background:#f8fafc;border-radius:8px">💬 رسائل واتساب</div>
      <div style="padding:10px;background:#f8fafc;border-radius:8px">📍 مواقع خرائط</div>
      <div style="padding:10px;background:#f8fafc;border-radius:8px">🌐 شبكات WiFi</div>
    </div>
  </div>
</main>

<script>
// Minimal QR Code encoder (Reed-Solomon + Matrix) — no external libraries
// Based on QR Code specification ISO/IEC 18004
(function(){
var QR={};
// Galois Field tables for GF(256)
var logTable=new Uint8Array(256),expTable=new Uint8Array(256);
(function(){var x=1;for(var i=0;i<255;i++){expTable[i]=x;logTable[x]=i;x<<=1;if(x&256)x^=285;}expTable[255]=1;})();
function gfMul(a,b){if(a===0||b===0)return 0;return expTable[(logTable[a]+logTable[b])%255];}
function gfPow(x,p){return expTable[(logTable[x]*p)%255];}
function gfPolyMul(p,q){var r=new Uint8Array(p.length+q.length-1);for(var i=0;i<p.length;i++)for(var j=0;j<q.length;j++)r[i+j]^=gfMul(p[i],q[j]);return r;}
function rsGeneratorPoly(n){var g=new Uint8Array([1]);for(var i=0;i<n;i++){g=gfPolyMul(g,new Uint8Array([1,gfPow(2,i)]));}return g;}
function rsEncode(msg,nEcc){var gen=rsGeneratorPoly(nEcc);var msgex=new Uint8Array(msg.length+nEcc);msgex.set(msg);for(var i=0;i<msg.length;i++){var c=msgex[i];if(c!==0)for(var j=0;j<gen.length;j++)msgex[i+j]^=gfMul(gen[j],c);}return msgex.slice(msg.length);}

// Numeric/Alphanumeric/Byte encoding — we use Byte mode only
function encodeData(str){
  var bytes=[];for(var i=0;i<str.length;i++){var c=str.charCodeAt(i);if(c<128){bytes.push(c);}else{var utf=encodeURIComponent(str[i]).replace(/%/g,'');for(var j=0;j<utf.length;j+=2)bytes.push(parseInt(utf.substr(j,2),16));}}
  return bytes;
}

// Remainder bits per version
var remBits=[0,0,7,7,7,7,7,0,0,0,0,0,0,0,3,3,3,3,3,3,3,4,4,4,4,4,4,4,3,3,3,3,3,3,3,0,0,0,0,0,0];
// Format info — ECC level M, mask 0
var formatInfoBits=[1,1,1,0,1,1,1,1,1,0,0,0,1,0,0];
// Version info not needed for v1-6

// Alignment pattern positions (for v2+)
var alignPos=[[],[],[6,18],[6,22],[6,26],[6,30],[6,34]];

function makeMatrix(ver){
  var n=ver*4+17;
  var mat=[];for(var i=0;i<n;i++){mat.push(new Uint8Array(n).fill(2));} // 2=unset
  return {m:mat,n:n};
}
function setModule(mm,r,c,v){if(r>=0&&r<mm.n&&c>=0&&c<mm.n)mm.m[r][c]=v;}
function addFinder(mm,row,col){
  for(var r=-1;r<=7;r++)for(var c=-1;c<=7;c++)setModule(mm,row+r,col+c,(r>=0&&r<7&&c>=0&&c<7)&&(r===0||r===6||c===0||c===6||(r>=2&&r<=4&&c>=2&&c<=4))?1:0);
}
function addTiming(mm,n){for(var i=8;i<n-8;i++){setModule(mm,6,i,i%2===0?1:0);setModule(mm,i,6,i%2===0?1:0);}}
function addDarkModule(mm,ver){setModule(mm,ver*4+9,8,1);}
function addAlignment(mm,ver){
  var pos=alignPos[ver]||[];
  for(var i=0;i<pos.length;i++)for(var j=0;j<pos.length;j++){
    var r=pos[i],c=pos[j];
    if(mm.m[r][c]!==2)continue;
    for(var dr=-2;dr<=2;dr++)for(var dc=-2;dc<=2;dc++)setModule(mm,r+dr,c+dc,(dr===0&&dc===0)||(Math.abs(dr)===2||Math.abs(dc)===2)?1:0);
  }
}
function addFormatInfo(mm,n){
  var bits=formatInfoBits;
  // Around top-left finder
  var positions=[[8,0],[8,1],[8,2],[8,3],[8,4],[8,5],[8,7],[8,8],[7,8],[5,8],[4,8],[3,8],[2,8],[1,8],[0,8]];
  for(var i=0;i<15;i++){var b=bits[i];setModule(mm,positions[i][0],positions[i][1],b);setModule(mm,n-1-i<8?n-1-i:positions[i][0],n-1-i<8?8:n-1-i,b);}
  // top-right and bottom-left copies handled above
  setModule(mm,8,n-8,1); // dark module
}

function applyMask(mm,n){
  for(var r=0;r<n;r++)for(var c=0;c<n;c++)if(mm.m[r][c]!==2)mm.m[r][c]^=((r+c)%2===0?1:0);
}

// EC codewords count per version (ECC level M)
var eccCount=[0,10,16,26,36,48,64,72];
// Data capacity per version (ECC level M, byte mode)
var dataCap=[0,16,28,44,64,86,108,124];

function qrEncode(str){
  var bytes=encodeData(str);
  var ver=1;
  while(ver<=6&&bytes.length>dataCap[ver])ver++;
  if(ver>6)ver=6; // truncate if too long for simple demo
  var n=ver*4+17;
  var totalCW=Math.floor((n*n-3*64-5*(2*(n-16)+1)-2-remBits[ver])/8);
  var dataLen=totalCW-eccCount[ver];

  // Build data bits: mode indicator (byte=0100) + char count + data + terminator
  var bits=[];
  function addBits(v,len){for(var i=len-1;i>=0;i--)bits.push((v>>i)&1);}
  addBits(4,4); // byte mode
  addBits(bytes.length,8);
  for(var i=0;i<bytes.length;i++)addBits(bytes[i],8);
  // Terminator
  for(var t=0;t<4&&bits.length<dataLen*8;t++)bits.push(0);
  while(bits.length%8!==0)bits.push(0);
  // Pad codewords
  var padCW=[0xEC,0x11];var pi=0;
  while(bits.length<dataLen*8){addBits(padCW[pi%2],8);pi++;}
  // To bytes
  var msgBytes=new Uint8Array(dataLen);
  for(var i=0;i<dataLen;i++){var v=0;for(var b=0;b<8;b++)v=(v<<1)|bits[i*8+b];msgBytes[i]=v;}
  // ECC
  var eccBytes=rsEncode(msgBytes,eccCount[ver]);
  // Full codeword stream
  var full=new Uint8Array(totalCW);
  full.set(msgBytes);full.set(eccBytes,dataLen);
  // All bits
  var allBits=[];for(var i=0;i<totalCW;i++)addBits(full[i],8);
  for(var i=0;i<remBits[ver];i++)allBits.push(0);

  // Build matrix
  var mm=makeMatrix(ver);
  addFinder(mm,0,0);addFinder(mm,0,n-7);addFinder(mm,n-7,0);
  addTiming(mm,n);addDarkModule(mm,ver);
  if(ver>=2)addAlignment(mm,ver);
  addFormatInfo(mm,n);

  // Place data bits
  var bi=0,dir=-1,col=n-1;
  while(col>=0){
    if(col===6)col--;
    var row=dir===-1?n-1:0;
    for(var rr=0;rr<n;rr++){
      for(var cc=0;cc<2;cc++){
        var c2=col-cc;
        if(mm.m[row][c2]===2){mm.m[row][c2]=bi<allBits.length?allBits[bi++]:0;}
      }
      row+=dir;
    }
    dir=-dir;col-=2;
  }
  // Apply mask (pattern 0: (r+c)%2===0)
  for(var r=0;r<n;r++)for(var c=0;c<n;c++){
    if(mm.m[r][c]===0||mm.m[r][c]===1){var fn=isFunctionModule(mm,r,c,n,ver);if(!fn)mm.m[r][c]^=((r+c)%2===0?1:0);}
  }
  return {matrix:mm.m,size:n};
}
function isFunctionModule(mm,r,c,n,ver){
  if(r<9&&c<9)return true; // top-left finder+format
  if(r<9&&c>=n-8)return true; // top-right finder+format
  if(r>=n-8&&c<9)return true; // bottom-left finder
  if(r===6||c===6)return true; // timing
  if(ver>=2){var pos=alignPos[ver]||[];for(var i=0;i<pos.length;i++)for(var j=0;j<pos.length;j++){if(Math.abs(r-pos[i])<=2&&Math.abs(c-pos[j])<=2)return true;}}
  return false;
}

window._qrEncode=qrEncode;
})();

function generateQR(){
  var text=document.getElementById('qr-input').value.trim();
  if(!text){document.getElementById('qr-out').style.display='none';document.getElementById('dl-btn').style.display='none';return;}
  try{
    var qr=window._qrEncode(text);
    var size=parseInt(document.getElementById('qr-size').value)||300;
    var fg=document.getElementById('qr-color').value;
    var bg=document.getElementById('qr-bg').value;
    var canvas=document.getElementById('qr-canvas');
    canvas.width=canvas.height=size;
    var ctx=canvas.getContext('2d');
    var cell=size/qr.size;
    ctx.fillStyle=bg;ctx.fillRect(0,0,size,size);
    ctx.fillStyle=fg;
    for(var r=0;r<qr.size;r++)for(var c=0;c<qr.size;c++)if(qr.matrix[r][c]===1)ctx.fillRect(c*cell,r*cell,cell,cell);
    document.getElementById('qr-out').style.display='block';
    document.getElementById('dl-btn').style.display='inline-flex';
  }catch(e){console.error(e);}
}
function downloadQR(){
  var canvas=document.getElementById('qr-canvas');
  var a=document.createElement('a');a.href=canvas.toDataURL('image/png');a.download='qr-yassota.png';a.click();
}
// Auto-generate on load if query string
var urlParams=new URLSearchParams(window.location.search);
if(urlParams.get('t')){document.getElementById('qr-input').value=urlParams.get('t');generateQR();}
</script>
<?php tool_footer(); ?>
