<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'مولّد نص اللوريم إيبسوم','url'=>TOOLS_BASE_URL.'/tools/lorem/',
    'description'=>'أنشئ نصوص Lorem Ipsum أو نصوصاً تعبئة عربية للتصميم والنماذج الأولية. مجاني وفوري.',
    'applicationCategory'=>'DeveloperApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('مولّد Lorem Ipsum عربي وإنجليزي — نص تعبئة للتصميم | yassota','أنشئ نصوص Lorem Ipsum أو نصوصاً عربية عشوائية لتعبئة النماذج والتصاميم. اختر العدد والتنسيق وانسخ الآن.',$schema,'#0f766e');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#ccfbf1">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0f766e" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
    </div>
    <h1>مولّد نص Lorem Ipsum</h1>
    <p>أنشئ نصوص تعبئة للتصميم — عربي أو Lorem Ipsum الكلاسيكي — بأي عدد وتنسيق تريد.</p>
  </div>

  <div class="t-card" style="max-width:700px;margin:0 auto 24px">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
      <div>
        <label class="t-label">نوع النص</label>
        <select id="lang" class="t-input" style="width:100%">
          <option value="lorem">Lorem Ipsum (لاتيني)</option>
          <option value="arabic">نص عربي عشوائي</option>
          <option value="mixed">مزيج عربي/إنجليزي</option>
        </select>
      </div>
      <div>
        <label class="t-label">وحدة القياس</label>
        <select id="unit" class="t-input" style="width:100%">
          <option value="paragraph">فقرات</option>
          <option value="sentence">جمل</option>
          <option value="word">كلمات</option>
        </select>
      </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
      <div>
        <label class="t-label">العدد</label>
        <input type="number" id="count" class="t-input" value="3" min="1" max="50" style="width:100%">
      </div>
      <div>
        <label class="t-label">التنسيق</label>
        <select id="format" class="t-input" style="width:100%">
          <option value="plain">نص عادي</option>
          <option value="html">&lt;p&gt; HTML</option>
          <option value="markdown">Markdown</option>
        </select>
      </div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
      <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
        <input type="checkbox" id="startLorem" checked> ابدأ بـ "Lorem ipsum dolor sit amet"
      </label>
    </div>
    <button onclick="generate()" class="t-btn" style="background:#0f766e;width:100%;margin-bottom:16px">توليد النص</button>

    <div id="result-area" style="display:none">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
        <label class="t-label" style="margin:0">النص الناتج</label>
        <div style="display:flex;gap:8px">
          <span id="word-count" style="font-size:12px;color:#94a3b8"></span>
          <button onclick="copyText()" style="background:#ccfbf1;color:#0f766e;border:none;border-radius:6px;padding:5px 12px;font-size:12px;font-weight:700;cursor:pointer">نسخ</button>
        </div>
      </div>
      <textarea id="output" class="t-input" rows="10" style="width:100%;font-size:13px;line-height:1.8;resize:vertical" readonly></textarea>
      <div id="copy-msg" style="display:none;color:#059669;font-size:13px;margin-top:6px">✅ تم النسخ!</div>
    </div>
  </div>

  <!-- Quick presets -->
  <div class="t-card" style="margin-bottom:24px">
    <h2>إعدادات سريعة</h2>
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px">
      <?php $presets=[
        ['بطاقة واحدة','1','paragraph','lorem'],
        ['3 فقرات','3','paragraph','lorem'],
        ['5 فقرات','5','paragraph','arabic'],
        ['10 جمل','10','sentence','lorem'],
        ['50 كلمة','50','word','lorem'],
        ['100 كلمة عربي','100','word','arabic'],
      ]; foreach($presets as [$lbl,$c,$u,$l]): ?>
      <button onclick="quickSet(<?= $c ?>,'<?= $u ?>','<?= $l ?>')" style="background:#f0fdfa;color:#0f766e;border:1px solid #99f6e4;border-radius:8px;padding:8px 14px;font-size:13px;cursor:pointer"><?= $lbl ?></button>
      <?php endforeach; ?>
    </div>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card">
    <h2>ما هو Lorem Ipsum ولماذا يستخدمه المصممون؟</h2>
    <p style="color:#64748b;line-height:1.8">Lorem Ipsum هو نص لاتيني مشوَّه (مشتق من كتاب "De Finibus Bonorum et Malorum" لشيشرون، 45 ق.م) يستخدمه المصممون والمطورون منذ القرن السادس عشر كنص عشوائي لملء التصاميم. الهدف منه أن تُبدي العين انتباهها للتصميم والتخطيط لا لمحتوى النص.</p>
    <h3 style="margin-top:16px">متى تستخدم نص التعبئة؟</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;margin-top:10px">
      <?php $uses=[
        ['🎨','التصميم الجرافيكي','اختبر تخطيطات الصفحات قبل توفر المحتوى الحقيقي'],
        ['💻','تطوير الويب','اختبر الـ CSS والـ Grid بمحتوى واقعي'],
        ['📱','تصميم التطبيقات','عرض نماذج Wireframe للعملاء'],
        ['📄','القوالب','أنشئ قوالب Word وInDesign قابلة للبيع'],
        ['🖨️','المطبوعات','اختبر الخطوط وحجم النص قبل الطباعة'],
        ['🎯','العروض التقديمية','عروض للعملاء لتوضيح هيكل المحتوى'],
      ]; foreach($uses as [$ic,$t,$d]): ?>
      <div style="background:#f0fdfa;border-radius:10px;padding:12px">
        <div style="font-size:24px;margin-bottom:6px"><?= $ic ?></div>
        <div style="font-size:13px;font-weight:700;margin-bottom:4px;color:#0f766e"><?= $t ?></div>
        <div style="font-size:12px;color:#64748b"><?= $d ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</main>

<script>
const loremWords = "Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua Ut enim ad minim veniam quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur Excepteur sint occaecat cupidatat non proident sunt in culpa qui officia deserunt mollit anim id est laborum Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium totam rem aperiam eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt Neque porro quisquam est qui dolorem ipsum quia dolor sit amet consectetur adipisci velit sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem".split(' ');

const arabicWords = "في هذا النص المؤقت تجد كلمات عربية عشوائية تساعدك على رؤية شكل النص في تصميمك بشكل واقعي دون الحاجة لمحتوى حقيقي يمكنك استخدام هذا النص لاختبار الخطوط والألوان والمسافات والتخطيط العام للصفحة أو التطبيق الذي تصممه التصميم الجيد يحتاج إلى محتوى واقعي لتقييمه بدقة ومن هنا تأتي أهمية نص التعبئة العربي الذي يعطيك شعوراً حقيقياً بكيفية ظهور النص النهائي على الصفحة أو الشاشة مما يساعدك على اتخاذ قرارات تصميمية أفضل وأكثر دقة قبل بدء مرحلة المحتوى الفعلي".split(' ');

function randomWords(arr, n) {
  const out = [];
  for(let i=0;i<n;i++) out.push(arr[Math.floor(Math.random()*arr.length)]);
  return out;
}

function makeSentence(words, n=8) {
  const w = randomWords(words, n + Math.floor(Math.random()*4));
  w[0] = w[0].charAt(0).toUpperCase() + w[0].slice(1);
  return w.join(' ') + '.';
}

function makeParagraph(words, sentences=5) {
  const sents = [];
  for(let i=0;i<sentences+Math.floor(Math.random()*3);i++)
    sents.push(makeSentence(words, 6+Math.floor(Math.random()*6)));
  return sents.join(' ');
}

function generate() {
  const lang = document.getElementById('lang').value;
  const unit = document.getElementById('unit').value;
  const count = Math.max(1, Math.min(50, +document.getElementById('count').value || 3));
  const format = document.getElementById('format').value;
  const startLorem = document.getElementById('startLorem').checked;

  let words = lang==='arabic' ? arabicWords : lang==='mixed' ? [...loremWords,...arabicWords] : loremWords;
  const parts = [];

  if(unit==='paragraph') {
    for(let i=0;i<count;i++) parts.push(makeParagraph(words));
    if(startLorem && lang!=='arabic') parts[0] = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. ' + parts[0];
  } else if(unit==='sentence') {
    for(let i=0;i<count;i++) parts.push(makeSentence(words,8));
    if(startLorem && lang!=='arabic') parts[0] = 'Lorem ipsum dolor sit amet. ' + parts[0];
  } else {
    const w = randomWords(words, count);
    if(startLorem && lang!=='arabic') { w.unshift('Lorem','ipsum','dolor','sit','amet'); }
    parts.push(w.join(' '));
  }

  let out;
  if(format==='plain') {
    out = parts.join(unit==='paragraph' ? '\n\n' : ' ');
  } else if(format==='html') {
    out = parts.map(p=>`<p>${p}</p>`).join('\n');
  } else {
    out = parts.map(p=>p).join('\n\n');
  }

  document.getElementById('output').value = out;
  const wc = out.split(/\s+/).filter(Boolean).length;
  document.getElementById('word-count').textContent = wc + ' كلمة';
  document.getElementById('result-area').style.display = 'block';
}

function quickSet(count, unit, lang) {
  document.getElementById('count').value = count;
  document.getElementById('unit').value = unit;
  document.getElementById('lang').value = lang;
  generate();
}

function copyText() {
  navigator.clipboard.writeText(document.getElementById('output').value);
  const m = document.getElementById('copy-msg'); m.style.display='block';
  setTimeout(()=>m.style.display='none',2000);
}

generate();
</script>
<?php tool_footer(); ?>
