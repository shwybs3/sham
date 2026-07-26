<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode(['@context'=>'https://schema.org','@type'=>'WebApplication','name'=>'عدّاد الكلمات وتحليل النص العربي','url'=>TOOLS_BASE_URL.'/','description'=>'عدّ كلمات النص العربي وحلّله فورياً — الكلمات والأحرف والجمل مجاناً','applicationCategory'=>'UtilitiesApplication','operatingSystem'=>'All','offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD']]);
tool_head('عدّاد الكلمات العربي — تحليل النص مجاناً | yassota أدوات','عدّ كلمات وأحرف وجمل نصك العربي فوراً — مع إحصاءات متقدمة وتحديد الكلمات الأكثر تكراراً',$schema,'#059669');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#d1fae5">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
    </div>
    <h1>عدّاد الكلمات وتحليل النص العربي</h1>
    <p>الصق نصك في الأسفل وستحصل فوراً على إحصاءات تفصيلية: كلمات، أحرف، جمل، فقرات، ووقت القراءة التقديري.</p>
  </div>

  <div class="t-card">
    <label class="t-label">النص</label>
    <textarea id="txt" class="t-textarea" rows="10" placeholder="الصق نصك هنا..." oninput="analyze()" style="min-height:180px"></textarea>
    <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap">
      <button class="t-btn t-btn-secondary" onclick="document.getElementById('txt').value='';analyze()">مسح</button>
      <button class="t-btn t-btn-secondary" onclick="copyTxt()">نسخ النص</button>
    </div>
  </div>

  <div id="stats-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;margin-bottom:16px">
    <?php
    $stats = [
      ['id'=>'s-words','label'=>'كلمات','val'=>'0'],
      ['id'=>'s-chars','label'=>'أحرف (مع مسافات)','val'=>'0'],
      ['id'=>'s-chars-nsp','label'=>'أحرف (بدون مسافات)','val'=>'0'],
      ['id'=>'s-sents','label'=>'جمل','val'=>'0'],
      ['id'=>'s-paras','label'=>'فقرات','val'=>'0'],
      ['id'=>'s-read','label'=>'وقت القراءة','val'=>'0 ث'],
    ];
    foreach ($stats as $s): ?>
    <div class="t-card" style="margin:0;padding:14px;text-align:center">
      <div id="<?= $s['id'] ?>" style="font-size:26px;font-weight:800;color:#059669"><?= $s['val'] ?></div>
      <div style="font-size:11px;color:#64748b;margin-top:4px"><?= $s['label'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="t-card" id="freq-card" style="display:none">
    <h2>الكلمات الأكثر تكراراً</h2>
    <div id="freq-list" style="display:flex;flex-wrap:wrap;gap:8px"></div>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card">
    <h2>لماذا عدّ الكلمات مهم؟</h2>
    <ul style="list-style:none;display:flex;flex-direction:column;gap:8px;font-size:14px;color:#475569">
      <li>✓ المقالات المُحسَّنة لـ SEO تتراوح بين 1500–3000 كلمة</li>
      <li>✓ التغريدات لا تتجاوز 280 حرفاً</li>
      <li>✓ وقت القراءة يُحسب بمتوسط 200 كلمة/دقيقة للعربية</li>
      <li>✓ معرفة كثافة الكلمات المفتاحية يساعد في تحسين ترتيب الموقع</li>
    </ul>
  </div>
</main>
<script>
var STOP=['من','إلى','في','على','عن','مع','هذا','هذه','التي','الذي','وهو','وهي','قد','لا','أن','إن','كان','كانت','هو','هي','نحن','أنا','أنت','لم','لن','لقد','كما','أو','و','ف','ب','ل','ك'];

function analyze(){
  var txt=document.getElementById('txt').value;
  var words=txt.trim()===''?[]:txt.trim().split(/\s+/);
  var wordsCount=words.filter(function(w){return w.length>0;}).length;
  var chars=txt.length;
  var charsNoSp=txt.replace(/\s/g,'').length;
  var sents=(txt.match(/[.!?؟।]+/g)||[]).length||( txt.trim()===''?0:1);
  var paras=(txt.match(/\n\s*\n/g)||[]).length+( txt.trim()===''?0:1);
  var readSecs=Math.round(wordsCount/200*60);
  var readStr=readSecs<60?readSecs+' ث':Math.ceil(readSecs/60)+' د';

  document.getElementById('s-words').textContent=wordsCount.toLocaleString('ar');
  document.getElementById('s-chars').textContent=chars.toLocaleString('ar');
  document.getElementById('s-chars-nsp').textContent=charsNoSp.toLocaleString('ar');
  document.getElementById('s-sents').textContent=sents.toLocaleString('ar');
  document.getElementById('s-paras').textContent=paras.toLocaleString('ar');
  document.getElementById('s-read').textContent=txt.trim()===''?'0 ث':readStr;

  // Word frequency
  var freq={};
  for(var i=0;i<words.length;i++){
    var w=words[i].replace(/[^؀-ۿa-zA-Z0-9]/g,'').toLowerCase();
    if(w.length>2&&STOP.indexOf(w)<0) freq[w]=(freq[w]||0)+1;
  }
  var sorted=Object.keys(freq).sort(function(a,b){return freq[b]-freq[a];}).slice(0,20);
  var fc=document.getElementById('freq-card');
  var fl=document.getElementById('freq-list');
  fl.innerHTML='';
  if(sorted.length>0){
    fc.style.display='block';
    for(var j=0;j<sorted.length;j++){
      var chip=document.createElement('span');
      chip.className='t-tag';
      chip.textContent=sorted[j]+' ('+freq[sorted[j]]+')';
      fl.appendChild(chip);
    }
  } else {
    fc.style.display='none';
  }
}
function copyTxt(){var v=document.getElementById('txt').value;if(navigator.clipboard)navigator.clipboard.writeText(v);}
analyze();
</script>
<?php tool_footer(); ?>
