<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode(['@context'=>'https://schema.org','@type'=>'WebApplication','name'=>'محرر واختبار التعابير النمطية Regex','url'=>TOOLS_BASE_URL.'/tools/regex/','description'=>'اختبر تعابيرك النمطية Regex مباشرةً مع تظليل التطابقات وشرح النمط خطوة خطوة.','applicationCategory'=>'DeveloperApplication','operatingSystem'=>'All','offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD']]);
tool_head('محرر Regex — اختبار التعابير النمطية بالعربية | yassota','اختبر Regular Expressions مباشرةً مع تظليل التطابقات — أداة مطورين مجانية تعمل في المتصفح بدون إرسال أي بيانات.',$schema,'#0891b2');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#e0f2fe">
      <span style="font-size:22px;font-weight:900;color:#0891b2;font-family:monospace">.*</span>
    </div>
    <h1>محرر واختبار Regex</h1>
    <p>اكتب تعبيرك النمطي وشاهد التطابقات مباشرةً مع تفاصيل كل مجموعة.</p>
  </div>
  <div class="t-card" style="margin-bottom:16px">
    <div style="margin-bottom:12px">
      <label class="t-label" style="display:flex;align-items:center;gap:8px">
        <span>النمط (Pattern)</span>
        <span style="font-size:11px;color:#64748b">/<span id="pat-disp"></span>/</span>
      </label>
      <div style="display:flex;gap:8px">
        <span style="font-size:16px;color:#94a3b8;line-height:40px">/</span>
        <input type="text" id="pattern" class="t-input" dir="ltr" style="flex:1;font-family:monospace;font-size:15px" placeholder="\b\w+@\w+\.\w+\b" oninput="runRegex()">
        <span style="font-size:16px;color:#94a3b8;line-height:40px">/</span>
        <div style="display:flex;gap:4px">
          <?php foreach(['g'=>'g (global)','i'=>'i (case insensitive)','m'=>'m (multiline)'] as $f=>$lbl): ?>
          <label title="<?= $lbl ?>" style="display:flex;align-items:center;gap:2px;font-size:12px;cursor:pointer;background:#f1f5f9;border-radius:6px;padding:4px 8px">
            <input type="checkbox" class="flag" value="<?= $f ?>" <?= $f==='g'?'checked':'' ?> onchange="runRegex()"> <?= $f ?>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div style="margin-bottom:12px">
      <label class="t-label">النص المراد اختباره</label>
      <textarea id="test-text" class="t-input" rows="6" dir="ltr" style="width:100%;font-family:monospace;font-size:13px;line-height:1.7;resize:vertical" oninput="runRegex()" placeholder="انسخ أي نص هنا…"></textarea>
    </div>
    <div id="highlighted" style="display:none;background:#0f172a;border-radius:10px;padding:14px;font-family:monospace;font-size:13px;line-height:1.9;direction:ltr;color:#e2e8f0;white-space:pre-wrap;word-break:break-all;max-height:300px;overflow-y:auto"></div>
    <div id="error-msg" style="display:none;color:#ef4444;font-size:13px;margin-top:8px;padding:8px;background:#fee2e2;border-radius:6px"></div>
    <div id="stats" style="font-size:12px;color:#64748b;margin-top:8px"></div>
  </div>
  <div class="t-card" style="margin-bottom:24px">
    <h2>مطابقات مفصّلة</h2>
    <div id="matches-detail" style="font-size:13px;color:#64748b;margin-top:8px">أدخل نمطاً للبدء…</div>
  </div>
  <div class="t-card" style="margin-bottom:24px">
    <h2>أنماط Regex جاهزة</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:8px;margin-top:10px">
      <?php $presets=[
        ['البريد الإلكتروني','[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}'],
        ['رقم الهاتف الدولي','\+?[0-9]{8,15}'],
        ['رابط URL','https?://[^\s/$.?#].[^\s]*'],
        ['IPv4 Address','\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b'],
        ['تاريخ (YYYY-MM-DD)','\d{4}-\d{2}-\d{2}'],
        ['كلمة مرور قوية','(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}'],
        ['أرقام عربية','[٠-٩]+'],
        ['نص عربي','[؀-ۿ\s]+'],
        ['HTML tag','<[^>]+>'],
        ['اسم ملف','[\w\-.]+\.\w{2,5}'],
      ]; foreach($presets as [$name,$pat]): ?>
      <button onclick="document.getElementById('pattern').value='<?= addslashes($pat) ?>';runRegex()"
        style="text-align:right;background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:9px 12px;cursor:pointer;font-size:12px">
        <div style="font-weight:700;color:#0891b2;margin-bottom:2px"><?= $name ?></div>
        <div style="font-family:monospace;color:#64748b;font-size:10px;direction:ltr;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($pat) ?></div>
      </button>
      <?php endforeach; ?>
    </div>
  </div>
  <?php tool_ad(); ?>
</main>
<script>
function runRegex(){
  const pat=document.getElementById('pattern').value;
  const text=document.getElementById('test-text').value;
  const errEl=document.getElementById('error-msg');
  const hi=document.getElementById('highlighted');
  const statsEl=document.getElementById('stats');
  const detailEl=document.getElementById('matches-detail');
  document.getElementById('pat-disp').textContent=pat;
  if(!pat){errEl.style.display='none';hi.style.display='none';statsEl.textContent='';detailEl.textContent='أدخل نمطاً للبدء…';return;}
  const flags=[...document.querySelectorAll('.flag:checked')].map(e=>e.value).join('');
  let re;
  try{re=new RegExp(pat,flags);}catch(e){errEl.style.display='block';errEl.textContent='خطأ: '+e.message;hi.style.display='none';statsEl.textContent='';return;}
  errEl.style.display='none';
  if(!text){hi.style.display='none';statsEl.textContent='';detailEl.textContent='أدخل نصاً للاختبار…';return;}
  const matches=[...text.matchAll(flags.includes('g')?re:new RegExp(pat, flags.includes('g')?flags:flags+'g'))];
  statsEl.textContent=matches.length? `✅ ${matches.length} تطابق`:' ∅ لا توجد تطابقات';
  // Highlight
  let hiHtml=text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  const gre=new RegExp(pat,flags.includes('g')?flags:flags+'g');
  hiHtml=hiHtml.replace(gre,m=>`<mark style="background:#fde047;color:#0f172a;border-radius:2px">${m.replace(/&/g,'&amp;').replace(/</g,'&lt;')}</mark>`);
  hi.innerHTML=hiHtml;
  hi.style.display='block';
  // Detail
  if(matches.length){
    detailEl.innerHTML=matches.slice(0,20).map((m,i)=>`
      <div style="padding:8px 10px;border-bottom:1px solid #f1f5f9;direction:ltr">
        <span style="color:#0891b2;font-weight:700">تطابق ${i+1}</span>
        <code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;margin:0 8px">${m[0].replace(/</g,'&lt;')}</code>
        <span style="color:#94a3b8;font-size:11px">pos ${m.index}</span>
        ${m.length>1?'<div style="font-size:11px;color:#64748b;margin-top:4px">مجموعات: '+m.slice(1).map((g,j)=>`(${j+1}): <code>${g??'undefined'}</code>`).join(' | ')+'</div>':''}
      </div>`).join('') + (matches.length>20?`<div style="padding:8px;color:#94a3b8;font-size:12px">... و${matches.length-20} تطابق إضافي</div>`:'');
  } else {
    detailEl.textContent='لا توجد تطابقات';
  }
}
document.getElementById('test-text').value='مرحباً، بريدي هو example@gmail.com وموقعي https://example.com ورقمي +963912345678';
runRegex();
</script>
<?php tool_footer(); ?>
