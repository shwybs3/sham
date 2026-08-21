<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'ساعة إيقاف ومؤقت عداد تنازلي','url'=>TOOLS_BASE_URL.'/tools/timer/',
    'description'=>'ساعة إيقاف دقيقة للمليثانية، ومؤقت عداد تنازلي مع تنبيه صوتي. أداة مجانية تعمل في المتصفح.',
    'applicationCategory'=>'UtilitiesApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('ساعة إيقاف ومؤقت عداد تنازلي مجاني — يعمل في المتصفح | yassota','ساعة إيقاف دقيقة بالمليثانية ومؤقت عداد تنازلي مع تنبيه صوتي. مثالية للتمارين والطبخ والدراسة.',$schema,'#e11d48');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#ffe4e6">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#e11d48" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    </div>
    <h1>ساعة إيقاف ومؤقت</h1>
    <p>ساعة إيقاف بدقة المليثانية، ومؤقت عداد تنازلي — كل ما تحتاجه لقياس الوقت.</p>
  </div>

  <!-- Tabs -->
  <div style="display:flex;gap:0;border-radius:12px;overflow:hidden;border:1px solid #f1f5f9;max-width:500px;margin:0 auto 24px">
    <button id="tab-sw" onclick="switchTab('sw')" style="flex:1;padding:12px;background:#e11d48;color:#fff;border:none;font-size:14px;font-weight:700;cursor:pointer">⏱ ساعة إيقاف</button>
    <button id="tab-ct" onclick="switchTab('ct')" style="flex:1;padding:12px;background:#f1f5f9;color:#475569;border:none;font-size:14px;font-weight:700;cursor:pointer">⏳ عداد تنازلي</button>
  </div>

  <!-- Stopwatch -->
  <div id="panel-sw" class="t-card" style="max-width:500px;margin:0 auto 24px;text-align:center">
    <div id="sw-display" style="font-size:56px;font-weight:900;font-variant-numeric:tabular-nums;letter-spacing:-2px;color:#0f172a;margin-bottom:20px;font-family:monospace">00:00.000</div>
    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
      <button id="sw-start" onclick="swStart()" class="t-btn" style="background:#e11d48;min-width:120px">ابدأ</button>
      <button id="sw-lap" onclick="swLap()" class="t-btn" style="background:#f1f5f9;color:#475569;min-width:100px" disabled>دورة</button>
      <button onclick="swReset()" class="t-btn" style="background:#f1f5f9;color:#475569;min-width:100px">إعادة</button>
    </div>
    <div id="sw-laps" style="margin-top:20px;max-height:200px;overflow-y:auto;display:none"></div>
  </div>

  <!-- Countdown -->
  <div id="panel-ct" class="t-card" style="display:none;max-width:500px;margin:0 auto 24px;text-align:center">
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:20px">
      <div>
        <label class="t-label">ساعات</label>
        <input type="number" id="ct-h" min="0" max="99" value="0" class="t-input" style="text-align:center;font-size:20px;font-weight:700">
      </div>
      <div>
        <label class="t-label">دقائق</label>
        <input type="number" id="ct-m" min="0" max="59" value="5" class="t-input" style="text-align:center;font-size:20px;font-weight:700">
      </div>
      <div>
        <label class="t-label">ثواني</label>
        <input type="number" id="ct-s" min="0" max="59" value="0" class="t-input" style="text-align:center;font-size:20px;font-weight:700">
      </div>
    </div>
    <div id="ct-display" style="font-size:56px;font-weight:900;font-variant-numeric:tabular-nums;letter-spacing:-2px;color:#0f172a;margin-bottom:20px;font-family:monospace">05:00</div>
    <div id="ct-done" style="display:none;font-size:18px;font-weight:700;color:#e11d48;margin-bottom:14px">⏰ انتهى الوقت!</div>
    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
      <button id="ct-start" onclick="ctStart()" class="t-btn" style="background:#e11d48;min-width:120px">ابدأ</button>
      <button onclick="ctReset()" class="t-btn" style="background:#f1f5f9;color:#475569;min-width:100px">إعادة</button>
    </div>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card">
    <h2>استخدامات ساعة الإيقاف والمؤقت</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;margin-top:12px">
      <?php $uses = [
        ['🏃','الرياضة','قس وقت التمارين والجلسات'],
        ['📚','الدراسة','تقنية Pomodoro (25 دقيقة تركيز)'],
        ['🍳','الطبخ','لا تحرق طعامك بعد الآن'],
        ['🎮','الألعاب','قس أوقات الجلسات'],
        ['💼','الإنتاجية','تتبع وقت كل مهمة'],
        ['🧘','التأمل','جلسات التأمل الموقوتة'],
      ]; foreach ($uses as [$ic,$t,$d]): ?>
      <div style="background:#f8fafc;border-radius:10px;padding:12px;text-align:center">
        <div style="font-size:28px;margin-bottom:6px"><?= $ic ?></div>
        <div style="font-size:13px;font-weight:700;margin-bottom:4px"><?= $t ?></div>
        <div style="font-size:11px;color:#64748b"><?= $d ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</main>

<script>
// Stopwatch
let swRunning=false, swStart=0, swElapsed=0, swTimer=null, lapCount=0;
function swPad(n,d=2){return String(Math.floor(n)).padStart(d,'0');}
function swFmt(ms){const m=ms/60000,s=(ms%60000)/1000,msec=ms%1000;return swPad(m)+':'+swPad(s)+'.'+swPad(msec,3);}
function swTick(){swElapsed=Date.now()-swStart;document.getElementById('sw-display').textContent=swFmt(swElapsed);}
function swStart(){
  if(swRunning){clearInterval(swTimer);swRunning=false;document.getElementById('sw-start').textContent='استمر';document.getElementById('sw-lap').disabled=true;}
  else{swStart=Date.now()-swElapsed;swTimer=setInterval(swTick,10);swRunning=true;document.getElementById('sw-start').textContent='إيقاف مؤقت';document.getElementById('sw-lap').disabled=false;}
}
function swLap(){
  lapCount++;
  const laps=document.getElementById('sw-laps');
  laps.style.display='block';
  const row=document.createElement('div');
  row.style.cssText='display:flex;justify-content:space-between;padding:7px 10px;border-bottom:1px solid #f1f5f9;font-size:13px';
  row.innerHTML=`<span style="color:#94a3b8">دورة ${lapCount}</span><span style="font-family:monospace;font-weight:700">${swFmt(swElapsed)}</span>`;
  laps.prepend(row);
}
function swReset(){clearInterval(swTimer);swRunning=false;swElapsed=0;lapCount=0;document.getElementById('sw-display').textContent='00:00.000';document.getElementById('sw-start').textContent='ابدأ';document.getElementById('sw-lap').disabled=true;document.getElementById('sw-laps').innerHTML='';document.getElementById('sw-laps').style.display='none';}

// Countdown
let ctRunning=false, ctRemaining=300, ctTimer=null;
function ctFmt(s){const h=Math.floor(s/3600),m=Math.floor((s%3600)/60),sec=s%60;return (h>0?swPad(h)+':':'')+swPad(m)+':'+swPad(sec);}
function ctStart(){
  if(ctRunning){clearInterval(ctTimer);ctRunning=false;document.getElementById('ct-start').textContent='استمر';}
  else{
    if(ctRemaining===0||!document.getElementById('ct-done').style.display==='none'){
      const h=+document.getElementById('ct-h').value||0,m=+document.getElementById('ct-m').value||0,s=+document.getElementById('ct-s').value||0;
      ctRemaining=h*3600+m*60+s;
    }
    if(ctRemaining<=0){alert('أدخل وقتاً صالحاً');return;}
    document.getElementById('ct-done').style.display='none';
    ctTimer=setInterval(()=>{
      ctRemaining--;
      document.getElementById('ct-display').textContent=ctFmt(ctRemaining);
      if(ctRemaining<=0){clearInterval(ctTimer);ctRunning=false;document.getElementById('ct-start').textContent='ابدأ';document.getElementById('ct-done').style.display='block';beep();}
    },1000);
    ctRunning=true;
    document.getElementById('ct-start').textContent='إيقاف مؤقت';
  }
}
function ctReset(){clearInterval(ctTimer);ctRunning=false;const h=+document.getElementById('ct-h').value||0,m=+document.getElementById('ct-m').value||0,s=+document.getElementById('ct-s').value||0;ctRemaining=h*3600+m*60+s;document.getElementById('ct-display').textContent=ctFmt(ctRemaining);document.getElementById('ct-done').style.display='none';document.getElementById('ct-start').textContent='ابدأ';}
['ct-h','ct-m','ct-s'].forEach(id=>document.getElementById(id).addEventListener('input',()=>{if(!ctRunning)ctReset();}));

function beep(){try{const ctx=new(window.AudioContext||window.webkitAudioContext)();const o=ctx.createOscillator();const g=ctx.createGain();o.connect(g);g.connect(ctx.destination);o.frequency.value=880;g.gain.value=0.3;o.start();g.gain.exponentialRampToValueAtTime(0.001,ctx.currentTime+1);o.stop(ctx.currentTime+1);}catch(e){}}

function switchTab(tab){
  document.getElementById('panel-sw').style.display=tab==='sw'?'block':'none';
  document.getElementById('panel-ct').style.display=tab==='ct'?'block':'none';
  document.getElementById('tab-sw').style.background=tab==='sw'?'#e11d48':'#f1f5f9';
  document.getElementById('tab-sw').style.color=tab==='sw'?'#fff':'#475569';
  document.getElementById('tab-ct').style.background=tab==='ct'?'#e11d48':'#f1f5f9';
  document.getElementById('tab-ct').style.color=tab==='ct'?'#fff':'#475569';
}
ctReset();
</script>
<?php tool_footer(); ?>
