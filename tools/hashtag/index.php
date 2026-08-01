<?php
require_once dirname(__DIR__) . '/_base.php';
require_once dirname(dirname(__DIR__)) . '/config.php';

$result  = [];
$error   = '';
$rawText = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $topic    = trim($_POST['topic'] ?? '');
    $platform = in_array($_POST['platform'] ?? '', ['instagram','twitter','tiktok','linkedin','youtube']) ? ($_POST['platform'] ?? 'instagram') : 'instagram';
    $lang     = in_array($_POST['lang'] ?? '', ['ar','en','both']) ? ($_POST['lang'] ?? 'ar') : 'ar';
    $count    = max(5, min(30, (int)($_POST['count'] ?? 15)));

    if (mb_strlen($topic) < 2) {
        $error = 'أدخل موضوع المنشور أولاً.';
    } else {
        $langMap = ['ar'=>'اللغة العربية فقط','en'=>'English only','both'=>'مزيج عربي وإنجليزي'];
        $prompt = "أنت خبير تسويق رقمي. أنشئ {$count} هاشتاق مناسبة لمنصة {$platform} عن الموضوع: \"{$topic}\"\n{$langMap[$lang]}\n\nالقواعد:\n- لا تضع مسافات داخل الهاشتاق\n- الهاشتاقات بدون # (ستضاف تلقائياً)\n- متنوعة: عامة وخاصة وبالإنجليزية إن طُلب\n- أخرجها كقائمة نقطية، كلمة واحدة في كل سطر بدون شرح";

        $aiResult = ai_text($pdo, $prompt);
        if (isset($aiResult['content'])) {
            $rawText = $aiResult['content'];
            $lines = preg_split('/\r?\n/', $rawText);
            foreach ($lines as $line) {
                $line = preg_replace('/^[-*•·\d.\s]+/', '', trim($line));
                $line = ltrim($line, '#');
                $line = preg_replace('/\s+/', '', $line);
                if (mb_strlen($line) > 1 && mb_strlen($line) < 50) {
                    $result[] = $line;
                }
            }
            $result = array_unique(array_values($result));
        } else {
            $error = $aiResult['error'] ?? 'تعذّر توليد الهاشتاقات، حاول مرة أخرى.';
        }
    }
}

$schema = json_encode(['@context'=>'https://schema.org','@type'=>'WebApplication','name'=>'مولّد الهاشتاق بالذكاء الاصطناعي','url'=>TOOLS_BASE_URL.'/tools/hashtag/','description'=>'أنشئ هاشتاقات احترافية لإنستغرام وتيك توك وتويتر بالذكاء الاصطناعي مجاناً','applicationCategory'=>'MarketingApplication','operatingSystem'=>'All','offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD']]);
tool_head('مولّد الهاشتاق بالذكاء الاصطناعي — إنستغرام وتيك توك | yassota أدوات','أنشئ هاشتاقات احترافية ومتنوعة لمنصاتك بالذكاء الاصطناعي — مجاناً لإنستغرام وتيك توك وتويتر',$schema,'#e11d48');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#ffe4e6">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#e11d48" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
    </div>
    <h1>مولّد الهاشتاق بالذكاء الاصطناعي</h1>
    <p>أنشئ هاشتاقات مُحسَّنة لمنصاتك بنقرة واحدة — تصل إلى جمهورك الأوسع على إنستغرام وتيك توك وتويتر ويوتيوب.</p>
  </div>

<?php if ($error): ?>
  <div class="t-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

  <div class="t-card">
    <form method="post">
      <div style="margin-bottom:14px">
        <label class="t-label">موضوع منشورك أو وصف قصير</label>
        <input type="text" name="topic" class="t-input" placeholder="مثال: وصفة كيك شوكولاتة، رحلة دبي، تطبيق لياقة..." value="<?= htmlspecialchars($_POST['topic'] ?? '') ?>" required>
      </div>
      <div class="t-row">
        <div class="t-col">
          <label class="t-label">المنصة</label>
          <select name="platform" class="t-input">
            <option value="instagram" <?= ($_POST['platform']??'instagram')==='instagram'?'selected':'' ?>>إنستغرام</option>
            <option value="tiktok" <?= ($_POST['platform']??'')==='tiktok'?'selected':'' ?>>تيك توك</option>
            <option value="twitter" <?= ($_POST['platform']??'')==='twitter'?'selected':'' ?>>تويتر / X</option>
            <option value="youtube" <?= ($_POST['platform']??'')==='youtube'?'selected':'' ?>>يوتيوب</option>
            <option value="linkedin" <?= ($_POST['platform']??'')==='linkedin'?'selected':'' ?>>لينكد إن</option>
          </select>
        </div>
        <div class="t-col">
          <label class="t-label">لغة الهاشتاق</label>
          <select name="lang" class="t-input">
            <option value="ar" <?= ($_POST['lang']??'ar')==='ar'?'selected':'' ?>>عربية فقط</option>
            <option value="en" <?= ($_POST['lang']??'')==='en'?'selected':'' ?>>إنجليزية فقط</option>
            <option value="both" <?= ($_POST['lang']??'')==='both'?'selected':'' ?>>عربية وإنجليزية</option>
          </select>
        </div>
        <div class="t-col">
          <label class="t-label">عدد الهاشتاقات</label>
          <input type="number" name="count" min="5" max="30" value="<?= (int)($_POST['count'] ?? 15) ?>" class="t-input">
        </div>
      </div>
      <div style="margin-top:16px">
        <button type="submit" class="t-btn" style="background:#e11d48">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 3l14 9-14 9V3z"/></svg>
          توليد الهاشتاقات
        </button>
      </div>
    </form>
  </div>

<?php if (!empty($result)): ?>
  <div class="t-card" style="border-color:#fda4af">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:8px">
      <h2 style="margin:0"><?= count($result) ?> هاشتاق جاهزة</h2>
      <button class="t-btn t-btn-secondary" onclick="copyAll()" id="copy-all-btn" style="padding:8px 16px">نسخ الكل</button>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px" id="tag-list">
      <?php foreach ($result as $tag): ?>
        <span class="t-tag" style="cursor:pointer;font-size:14px" onclick="copyTag(this)" title="انقر للنسخ">#<?= htmlspecialchars($tag) ?></span>
      <?php endforeach; ?>
    </div>
    <div id="tag-raw" style="font-family:monospace;font-size:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;word-break:break-all;color:#475569;direction:ltr;text-align:left">
      <?= htmlspecialchars(implode(' ', array_map(function($t){ return '#'.$t; }, $result))) ?>
    </div>
  </div>
  <script>
  function copyAll(){
    var v=document.getElementById('tag-raw').textContent.trim();
    if(navigator.clipboard){navigator.clipboard.writeText(v).then(function(){document.getElementById('copy-all-btn').textContent='✓ تم النسخ';setTimeout(function(){document.getElementById('copy-all-btn').textContent='نسخ الكل';},2000);});}
    else{var t=document.createElement('textarea');t.value=v;document.body.appendChild(t);t.select();document.execCommand('copy');document.body.removeChild(t);}
  }
  function copyTag(el){
    var v=el.textContent.trim();
    if(navigator.clipboard){navigator.clipboard.writeText(v).then(function(){var orig=el.style.background;el.style.background='#86efac';setTimeout(function(){el.style.background=orig;},800);});}
  }
  </script>
<?php endif; ?>

  <?php tool_ad(); ?>

  <div class="t-card">
    <h2>نصائح للهاشتاقات الناجحة</h2>
    <ul style="list-style:none;display:flex;flex-direction:column;gap:8px;font-size:14px;color:#475569">
      <li>✓ امزج الهاشتاقات الكبيرة (مليون+) مع الصغيرة (10K-100K) لوصول متوازن</li>
      <li>✓ إنستغرام: استخدم 5-10 هاشتاقات متخصصة لا 30 عاماً</li>
      <li>✓ تيك توك: ركّز على 3-5 هاشتاقات مع FYP# أو fyp#</li>
      <li>✓ تويتر: هاشتاق أو اثنين فقط لتجنب التشتيت</li>
      <li>✓ تجنب الهاشتاقات المحظورة التي تقلل الوصول تلقائياً</li>
    </ul>
  </div>
</main>
<?php tool_footer(); ?>
