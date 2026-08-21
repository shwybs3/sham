<?php
require_once dirname(__DIR__) . '/_base.php';
require_once dirname(dirname(__DIR__)) . '/config.php';

$result = '';
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $topic   = trim($_POST['topic'] ?? '');
    $style   = in_array($_POST['style'] ?? '', ['article','email','social','product','seo']) ? ($_POST['style'] ?? 'article') : 'article';
    $length  = in_array($_POST['length'] ?? '', ['short','medium','long']) ? ($_POST['length'] ?? 'medium') : 'medium';
    $extra   = trim($_POST['extra'] ?? '');

    if (mb_strlen($topic) < 3) {
        $error = 'الرجاء كتابة موضوع الكتابة.';
    } else {
        $styles = [
            'article' => 'مقال إعلامي شامل',
            'email'   => 'بريد إلكتروني احترافي',
            'social'  => 'منشور وسائل تواصل اجتماعي جذاب',
            'product' => 'وصف منتج احترافي لمتجر إلكتروني',
            'seo'     => 'محتوى محسّن لمحركات البحث SEO',
        ];
        $lengths = ['short'=>'قصير (150-300 كلمة)','medium'=>'متوسط (400-700 كلمة)','long'=>'طويل (800-1500 كلمة)'];
        $extraLine = $extra ? "\nملاحظات إضافية: $extra" : '';
        $prompt = "أنت كاتب محترف باللغة العربية. اكتب {$styles[$style]} عن الموضوع التالي:\n\n{$topic}{$extraLine}\n\nالطول المطلوب: {$lengths[$length]}\n\nاكتب النص مباشرة بدون مقدمة توضيحية.";

        $aiResult = ai_text($pdo, $prompt);
        if (isset($aiResult['content'])) {
            $result = $aiResult['content'];
        } else {
            $error = $aiResult['error'] ?? 'تعذّر توليد المحتوى، حاول مرة أخرى.';
        }
    }
}

$schema = json_encode(['@context'=>'https://schema.org','@type'=>'WebApplication','name'=>'كاتب المحتوى العربي بالذكاء الاصطناعي','url'=>TOOLS_BASE_URL.'/tools/write/','description'=>'أنشئ محتوى عربي احترافي بالذكاء الاصطناعي — مقالات وأوصاف منتجات وبريد إلكتروني مجاناً','applicationCategory'=>'WritingApplication','operatingSystem'=>'All','offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD']]);
tool_head('كاتب المحتوى العربي بالذكاء الاصطناعي | yassota أدوات','أنشئ مقالات ومنشورات وأوصاف منتجات باللغة العربية بالذكاء الاصطناعي — مجاناً وبجودة احترافية',$schema,'#0891b2');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#e0f2fe">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0891b2" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
    </div>
    <h1>كاتب المحتوى العربي بالذكاء الاصطناعي</h1>
    <p>أنشئ محتوى عربياً احترافياً في ثوانٍ — مقالات، منشورات سوشيال ميديا، أوصاف منتجات، ورسائل بريد إلكتروني.</p>
  </div>

<?php if ($error): ?>
  <div class="t-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

  <div class="t-card">
    <form method="post">
      <div style="margin-bottom:14px">
        <label class="t-label">موضوع الكتابة</label>
        <input type="text" name="topic" class="t-input" placeholder="مثال: فوائد القهوة العربية، أفضل تطبيقات الأندرويد 2025..." value="<?= htmlspecialchars($_POST['topic'] ?? '') ?>" required>
      </div>
      <div class="t-row">
        <div class="t-col">
          <label class="t-label">نوع المحتوى</label>
          <select name="style" class="t-input">
            <option value="article" <?= ($_POST['style']??'article')==='article'?'selected':'' ?>>مقال إعلامي</option>
            <option value="social" <?= ($_POST['style']??'')==='social'?'selected':'' ?>>منشور سوشيال ميديا</option>
            <option value="product" <?= ($_POST['style']??'')==='product'?'selected':'' ?>>وصف منتج</option>
            <option value="email" <?= ($_POST['style']??'')==='email'?'selected':'' ?>>بريد إلكتروني</option>
            <option value="seo" <?= ($_POST['style']??'')==='seo'?'selected':'' ?>>محتوى SEO</option>
          </select>
        </div>
        <div class="t-col">
          <label class="t-label">طول المحتوى</label>
          <select name="length" class="t-input">
            <option value="short" <?= ($_POST['length']??'')==='short'?'selected':'' ?>>قصير (150-300 كلمة)</option>
            <option value="medium" <?= ($_POST['length']??'medium')==='medium'?'selected':'' ?>>متوسط (400-700 كلمة)</option>
            <option value="long" <?= ($_POST['length']??'')==='long'?'selected':'' ?>>طويل (800-1500 كلمة)</option>
          </select>
        </div>
      </div>
      <div style="margin-top:12px">
        <label class="t-label">تعليمات إضافية (اختياري)</label>
        <input type="text" name="extra" class="t-input" placeholder="مثال: أسلوب رسمي، اذكر المصادر، استهدف سوق الخليج..." value="<?= htmlspecialchars($_POST['extra'] ?? '') ?>">
      </div>
      <div style="margin-top:16px">
        <button type="submit" class="t-btn">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
          اكتب المحتوى الآن
        </button>
      </div>
    </form>
  </div>

<?php if ($result): ?>
  <div class="t-card" style="border-color:#7dd3fc">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px">
      <h2 style="margin:0">المحتوى المُنشأ</h2>
      <button class="t-btn t-btn-secondary" onclick="copyResult()" id="copy-res-btn" style="padding:8px 16px">نسخ</button>
    </div>
    <div id="result-text" style="white-space:pre-wrap;line-height:2;font-size:15px;color:#0f172a"><?= htmlspecialchars($result) ?></div>
  </div>
  <script>
  function copyResult(){
    var v=document.getElementById('result-text').textContent;
    if(navigator.clipboard){navigator.clipboard.writeText(v).then(function(){document.getElementById('copy-res-btn').textContent='✓ تم النسخ';setTimeout(function(){document.getElementById('copy-res-btn').textContent='نسخ';},2000);});}
    else{var t=document.createElement('textarea');t.value=v;document.body.appendChild(t);t.select();document.execCommand('copy');document.body.removeChild(t);}
  }
  </script>
<?php endif; ?>

  <?php tool_ad(); ?>
</main>
<?php tool_footer(); ?>
