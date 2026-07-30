<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'مولّد الـ Slug لعناوين URL','url'=>TOOLS_BASE_URL.'/tools/slug/',
    'description'=>'حوّل أي عنوان عربي أو إنجليزي إلى Slug صالح لعناوين URL. أداة مجانية لإنشاء روابط SEO-friendly.',
    'applicationCategory'=>'DeveloperApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('مولّد Slug URL من العنوان العربي — روابط SEO-Friendly | yassota','حوّل عنوان موضوعك أو مقالتك إلى Slug نظيف صالح لعناوين URL، بالعربية والإنجليزية. مجاني وفوري.',$schema,'#dc2626');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#fee2e2">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
    </div>
    <h1>مولّد الـ Slug لعناوين URL</h1>
    <p>حوّل أي عنوان إلى Slug نظيف صالح لعناوين URL ومحسّن لمحركات البحث.</p>
  </div>

  <div class="t-card" style="max-width:640px;margin:0 auto 24px">
    <label class="t-label">العنوان (عربي أو إنجليزي)</label>
    <input type="text" id="title-input" class="t-input" style="width:100%;font-size:16px;margin-bottom:14px" placeholder="مثال: أفضل تطبيقات الهاتف المحمول 2024">

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px">
      <div>
        <label class="t-label">الفاصل</label>
        <select id="sep" class="t-input" style="width:100%">
          <option value="-">شرطة - (Hyphen)</option>
          <option value="_">شرطة سفلية _ (Underscore)</option>
        </select>
      </div>
      <div>
        <label class="t-label">حالة الأحرف</label>
        <select id="case" class="t-input" style="width:100%">
          <option value="lower">صغيرة (lowercase)</option>
          <option value="upper">كبيرة (UPPERCASE)</option>
        </select>
      </div>
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
      <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer"><input type="checkbox" id="keep-arabic" checked> الحفاظ على الأحرف العربية</label>
      <label style="display:flex;align-items:center;gap=6px;font-size:13px;cursor:pointer"><input type="checkbox" id="transliterate"> نقحرة الأحرف العربية (latin)</label>
    </div>

    <button id="gen-btn" class="t-btn" style="background:#dc2626;margin-bottom:16px">توليد الـ Slug</button>

    <div id="results" style="display:none">
      <label class="t-label">الـ Slug الناتج</label>
      <div style="display:flex;gap:8px">
        <input type="text" id="slug-output" class="t-input" dir="ltr" style="flex:1;font-family:monospace;font-size:15px;background:#f8fafc" readonly>
        <button onclick="copySlug()" class="t-btn" style="padding:0 16px;white-space:nowrap">نسخ</button>
      </div>
      <div style="margin-top:8px;font-size:12px;color:#94a3b8" dir="ltr" id="preview-url"></div>
      <div id="copy-msg" style="display:none;color:#059669;font-size:13px;margin-top:6px">✅ تم النسخ!</div>
    </div>
  </div>

  <div class="t-card" style="margin-bottom:24px">
    <h2>أمثلة على تحويل العناوين</h2>
    <table style="width:100%;border-collapse:collapse;font-size:13px">
      <thead><tr style="background:#f1f5f9">
        <th style="padding:9px 12px;text-align:right">العنوان الأصلي</th>
        <th style="padding:9px 12px;text-align:left">الـ Slug الناتج</th>
      </tr></thead>
      <tbody>
        <?php $examples = [
          ['أفضل تطبيقات أندرويد 2024','أفضل-تطبيقات-أندرويد-2024'],
          ['Best Android Apps 2024','best-android-apps-2024'],
          ['كيف تحقق النجاح في 30 يوماً؟','كيف-تحقق-النجاح-في-30-يوماً'],
          ['Hello World! This is a Test.','hello-world-this-is-a-test'],
        ]; foreach ($examples as [$title,$slug]): ?>
        <tr style="border-bottom:1px solid #f1f5f9" onclick="document.getElementById('title-input').value='<?= addslashes($title) ?>';generate();" style="cursor:pointer">
          <td style="padding:9px 12px"><?= htmlspecialchars($title) ?></td>
          <td style="padding:9px 12px;font-family:monospace;color:#dc2626;direction:ltr"><?= htmlspecialchars($slug) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card">
    <h2>ما هو الـ Slug وأهميته في SEO؟</h2>
    <p style="color:#64748b;line-height:1.8">الـ Slug هو الجزء الأخير من عنوان URL الذي يعرّف الصفحة، مثل <code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;direction:ltr">/blog/أفضل-تطبيقات-2024</code>. يُعدّ Slug جيد من أهم عناصر تحسين محركات البحث (SEO) لأن Google يقرأه ويستخدمه لفهم موضوع الصفحة.</p>
    <h3 style="margin-top:16px">خصائص Slug جيد:</h3>
    <ul style="color:#64748b;line-height:2;padding-right:20px">
      <li>قصير وواضح (أقل من 50 حرف مثالياً)</li>
      <li>يحتوي على الكلمة المفتاحية الرئيسية</li>
      <li>يستخدم الشرطة (-) للفصل بدلاً من المسافة أو الشرطة السفلية</li>
      <li>لا يحتوي على أحرف خاصة أو علامات ترقيم</li>
      <li>بحروف صغيرة (للإنجليزي)</li>
      <li>لا يتغير بعد نشر الصفحة (تغييره يفقد الروابط الخلفية)</li>
    </ul>
  </div>
</main>

<script>
const translitMap = {
  'ا':'a','أ':'a','إ':'i','آ':'aa','ب':'b','ت':'t','ث':'th','ج':'j','ح':'h','خ':'kh',
  'د':'d','ذ':'dh','ر':'r','ز':'z','س':'s','ش':'sh','ص':'s','ض':'d','ط':'t','ظ':'z',
  'ع':'a','غ':'gh','ف':'f','ق':'q','ك':'k','ل':'l','م':'m','ن':'n','ه':'h','و':'w',
  'ي':'y','ى':'a','ة':'a','ء':'','ئ':'y','ؤ':'w','لا':'la','لأ':'la','لإ':'li','لآ':'laa'
};
function transliterate(str) {
  return str.replace(/لا|لأ|لإ|لآ/g,m=>translitMap[m]||m).replace(/[؀-ۿ]/g,c=>translitMap[c]||c);
}
function generate() {
  let text = document.getElementById('title-input').value;
  if (!text.trim()) return;
  const sep = document.getElementById('sep').value;
  const caseMode = document.getElementById('case').value;
  const keepAr = document.getElementById('keep-arabic').checked;
  const trans  = document.getElementById('transliterate').checked;
  if (trans) text = transliterate(text);
  // Remove diacritics
  text = text.replace(/[ً-ٟ]/g,'');
  // Remove non-Arabic, non-Latin, non-digit chars
  if (!keepAr || trans) text = text.replace(/[^\w\s-]/g,'');
  else text = text.replace(/[^؀-ۿ\w\s-]/g,'');
  text = text.replace(/[\s_-]+/g, sep);
  text = text.replace(new RegExp('^'+sep+'|'+sep+'$','g'),'');
  if (caseMode === 'lower') text = text.toLowerCase();
  else text = text.toUpperCase();
  document.getElementById('slug-output').value = text;
  document.getElementById('preview-url').textContent = 'https://yoursite.com/' + text;
  document.getElementById('results').style.display = 'block';
}
document.getElementById('gen-btn').addEventListener('click', generate);
document.getElementById('title-input').addEventListener('input', generate);
document.getElementById('sep').addEventListener('change', generate);
document.getElementById('case').addEventListener('change', generate);
document.getElementById('keep-arabic').addEventListener('change', generate);
document.getElementById('transliterate').addEventListener('change', function(){
  if(this.checked) document.getElementById('keep-arabic').checked = false;
  generate();
});
function copySlug() {
  navigator.clipboard.writeText(document.getElementById('slug-output').value);
  const m = document.getElementById('copy-msg');
  m.style.display = 'block';
  setTimeout(() => m.style.display='none', 2000);
}
</script>
<?php tool_footer(); ?>
