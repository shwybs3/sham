<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode(['@context'=>'https://schema.org','@type'=>'WebApplication','name'=>'حاسبة وقت القراءة','url'=>TOOLS_BASE_URL.'/tools/reading-time/','description'=>'احسب وقت قراءة أي نص أو مقال بالدقائق، مع إحصائيات الكلمات والجمل والفقرات.','applicationCategory'=>'UtilitiesApplication','operatingSystem'=>'All','offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD']]);
tool_head('حاسبة وقت القراءة — كم تستغرق لقراءة مقالتك؟ | yassota','احسب وقت قراءة مقالتك أو موضوعك بالدقائق — مناسبة للكتّاب والمدوّنين ومحرري المحتوى. تُحدِّث تلقائياً أثناء الكتابة.',$schema,'#059669');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#d1fae5"><span style="font-size:24px">📖</span></div>
    <h1>حاسبة وقت القراءة</h1>
    <p>الصقْ نصك واعرف كم تستغرق قراءته — مع إحصائيات الكلمات والجمل والمستوى.</p>
  </div>
  <div class="t-card" style="margin-bottom:16px">
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px">
      <label class="t-label" style="margin:0">سرعة القراءة:</label>
      <select id="speed" class="t-input" style="width:220px" onchange="calc()">
        <option value="200">بطيء (200 كلمة/دقيقة)</option>
        <option value="238" selected>متوسط (238 كلمة/دقيقة)</option>
        <option value="300">سريع (300 كلمة/دقيقة)</option>
        <option value="450">قارئ متقدم (450 كلمة/دقيقة)</option>
      </select>
    </div>
    <textarea id="text-in" class="t-input" rows="10" style="width:100%;font-size:14px;line-height:1.8;resize:vertical" placeholder="الصق نصك أو مقالتك هنا…" oninput="calc()"></textarea>
  </div>
  <div id="results" style="display:none;margin-bottom:24px">
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px">
      <?php $stats=[['read-time','وقت القراءة','#059669','#d1fae5'],['words','كلمة','#2563eb','#dbeafe'],['chars','حرف','#7c3aed','#ede9fe'],['sentences','جملة','#d97706','#fef3c7'],['paragraphs','فقرة','#0891b2','#e0f2fe']]; foreach($stats as [$id,$lbl,$c,$bg]): ?>
      <div style="background:<?= $bg ?>;border-radius:12px;padding:16px;text-align:center">
        <div style="font-size:26px;font-weight:900;color:<?= $c ?>" id="<?= $id ?>">—</div>
        <div style="font-size:12px;color:<?= $c ?>;margin-top:4px"><?= $lbl ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <div id="level-badge" style="margin-top:12px;padding:10px 14px;border-radius:10px;font-size:13px;font-weight:700"></div>
    <div id="top-words" style="margin-top:12px"></div>
  </div>
  <?php tool_ad(); ?>
  <div class="t-card">
    <h2>معايير وقت القراءة</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;margin-top:10px">
      <?php foreach([['تغريدة','280 حرف','~ 7 ثواني'],['منشور مدونة قصير','500 كلمة','~ 2 دقيقة'],['مقالة متوسطة','1500 كلمة','~ 6 دقائق'],['مقالة طويلة','3000 كلمة','~ 12 دقيقة'],['قصة قصيرة','5000 كلمة','~ 21 دقيقة'],['كتاب متوسط','80000 كلمة','~ 5-6 ساعات']] as [$t,$len,$time]): ?>
      <div style="background:#f8fafc;border-radius:8px;padding:10px">
        <div style="font-weight:700;font-size:13px;color:#0f172a"><?= $t ?></div>
        <div style="font-size:12px;color:#64748b;margin-top:2px"><?= $len ?> — <?= $time ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</main>
<script>
function calc(){
  const text=document.getElementById('text-in').value;
  if(!text.trim()){document.getElementById('results').style.display='none';return;}
  const speed=+document.getElementById('speed').value;
  const words=text.trim().split(/\s+/).filter(w=>w.length>0).length;
  const chars=text.length;
  const sentences=(text.match(/[.!?؟।]+/g)||[]).length || 1;
  const paragraphs=(text.trim().split(/\n\s*\n/).filter(p=>p.trim()).length) || 1;
  const mins=words/speed;
  let timeStr;
  if(mins<1) timeStr=Math.round(mins*60)+' ثانية';
  else if(mins<60) timeStr=mins.toFixed(1)+' دقيقة';
  else timeStr=Math.floor(mins/60)+' ساعة و'+Math.round(mins%60)+' دقيقة';
  document.getElementById('read-time').textContent=timeStr;
  document.getElementById('words').textContent=words.toLocaleString('ar');
  document.getElementById('chars').textContent=chars.toLocaleString('ar');
  document.getElementById('sentences').textContent=sentences.toLocaleString('ar');
  document.getElementById('paragraphs').textContent=paragraphs.toLocaleString('ar');
  // Reading level
  const avgWordLen=chars/Math.max(words,1);
  const avgSentLen=words/Math.max(sentences,1);
  let level,lcolor,lbg;
  if(avgSentLen<=10&&avgWordLen<=4){level='سهل جداً — مناسب للأطفال';lcolor='#16a34a';lbg='#dcfce7';}
  else if(avgSentLen<=15){level='سهل — مناسب للعامة';lcolor='#2563eb';lbg='#dbeafe';}
  else if(avgSentLen<=20){level='متوسط — مستوى متوسط التعليم';lcolor='#d97706';lbg='#fef3c7';}
  else{level='صعب — محتوى أكاديمي أو تقني';lcolor='#dc2626';lbg='#fee2e2';}
  const lb=document.getElementById('level-badge');
  lb.style.background=lbg;lb.style.color=lcolor;lb.textContent='مستوى القراءة: '+level;
  // Top words
  const freq={};
  text.toLowerCase().split(/\s+/).forEach(w=>{const c=w.replace(/[^a-zأ-ي]/g,'');if(c.length>2)freq[c]=(freq[c]||0)+1;});
  const top=Object.entries(freq).sort((a,b)=>b[1]-a[1]).slice(0,8);
  if(top.length){
    document.getElementById('top-words').innerHTML='<div style="font-size:12px;color:#64748b;margin-bottom:6px">الكلمات الأكثر تكراراً:</div><div style="display:flex;flex-wrap:wrap;gap:6px">'+
    top.map(([w,c])=>`<span style="background:#f1f5f9;padding:3px 10px;border-radius:20px;font-size:12px;color:#475569">${w} <b>${c}</b></span>`).join('')+'</div>';
  }
  document.getElementById('results').style.display='block';
}
</script>
<?php tool_footer(); ?>
