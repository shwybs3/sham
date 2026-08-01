<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'إصلاح الكتابة الخاطئة بين العربي والإنجليزي','url'=>TOOLS_BASE_URL.'/tools/keyboard-fix/',
    'description'=>'صحّح النص الذي كتبته بلوحة مفاتيح خاطئة فوراً — حوّل بين العربي والإنجليزي تلقائياً.',
    'applicationCategory'=>'UtilitiesApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('⌨️ تصحيح الكتابة الخاطئة (عربي/إنجليزي) فوراً | حوّل كتابتك المعكوسة | yassota','هل كتبت "ندى" فظهرت "kaK"؟ صحّح النص المكتوب بلوحة مفاتيح خاطئة بين العربي والإنجليزي بضغطة زر واحدة — مجاناً 100%.',$schema,'#dc2626');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#fee2e2">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M6 10h.01M10 10h.01M14 10h.01M18 10h.01M6 14h12"/></svg>
    </div>
    <h1>تصحيح الكتابة الخاطئة بين العربي والإنجليزي</h1>
    <p>كتبت بلغة عربية ولوحة المفاتيح كانت على إنجليزي (أو العكس)؟ صحّح النص فوراً.</p>
  </div>

  <div class="t-card" style="max-width:720px;margin:0 auto 24px">
    <label class="t-label">الصق النص المعكوس هنا</label>
    <textarea id="input-text" rows="5" class="t-input" style="width:100%;font-size:16px;line-height:1.8;direction:ltr;text-align:left" placeholder="مثال: hgsghl (نص كُتب بلوحة مفاتيح خاطئة)"></textarea>
    <div style="display:flex;gap:10px;margin-top:12px;flex-wrap:wrap">
      <button id="fix-btn" class="t-btn" style="flex:1;min-width:160px;background:#dc2626">تصحيح تلقائي (كلا الاتجاهين)</button>
      <button id="clear-btn" class="t-btn t-btn-secondary" style="flex:0 0 auto">مسح</button>
    </div>
    <div id="result-wrap" style="display:none;margin-top:20px">
      <label class="t-label">النص المصحح</label>
      <div id="result-text" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px;font-size:17px;line-height:2;color:#1e293b;min-height:60px;white-space:pre-wrap"></div>
      <button id="copy-btn" style="margin-top:10px;width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;background:#fff;cursor:pointer;font-size:13px;font-family:inherit">نسخ النص المصحح</button>
    </div>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card">
    <h2>ما سبب هذه المشكلة الشائعة؟</h2>
    <p style="color:#64748b;line-height:1.9">كل حرف على لوحة المفاتيح الفيزيائية يقابله حرفان مختلفان حسب اللغة المُفعَّلة: عند الكتابة بالعربية، ضغط المفتاح الذي يحمل حرف "k" الإنجليزي ينتج الحرف العربي "ك" (لأن هذا المفتاح تحديداً مخصص لحرف الكاف في تخطيط لوحة المفاتيح العربية القياسية). المشكلة تحدث عندما تنسى تبديل لغة الكتابة قبل أن تبدأ — فتكتب كلمة عربية بذهنك، لكن لوحة المفاتيح لا تزال على وضع الإنجليزية، فتحصل على نص إنجليزي غريب المظهر لا معنى له، والعكس تماماً صحيح أيضاً.</p>
    <p style="color:#64748b;line-height:1.9;margin-top:12px">هذه المشكلة شائعة جداً بين مستخدمي الحواسيب والهواتف الذين يستخدمون أكثر من لغة يومياً، خصوصاً عند الكتابة السريعة دون النظر إلى شاشة تأكيد اللغة. النتيجة نص عشوائي مزعج يضطر المستخدم لحذفه وإعادة كتابته من جديد — أو، وهذا ما تحله هذه الأداة، تحويله تلقائياً إلى النص الصحيح المقصود.</p>

    <h3 style="margin-top:20px">كيف تعمل الأداة؟</h3>
    <p style="color:#64748b;line-height:1.9">تعتمد الأداة على خريطة تطابق دقيقة بين موقع كل مفتاح على لوحة المفاتيح العربية القياسية (تخطيط 101/102) وما يقابله في التخطيط الإنجليزي QWERTY. تحلل الأداة النص المُدخَل تلقائياً وتحدد اتجاه التحويل الأنسب (من عربي إلى إنجليزي أو العكس) بناءً على نوع الأحرف الموجودة، ثم تُبدّل كل حرف بمقابله الصحيح فوراً.</p>

    <h3 style="margin-top:20px">استخدامات شائعة</h3>
    <ul style="color:#64748b;line-height:2;padding-right:20px">
      <li>تصحيح رسائل أو تعليقات كُتبت بالخطأ بلوحة مفاتيح غير مناسبة</li>
      <li>استرجاع كلمات مرور أو بيانات أُدخلت بلغة خاطئة عن طريق النسيان</li>
      <li>تحويل نصوص محفوظة بالخطأ في ملاحظات الهاتف أو المحادثات</li>
      <li>مساعدة مستخدمي لوحات المفاتيح المزدوجة اللغة الذين ينسون تبديل اللغة كثيراً</li>
    </ul>

    <h3 style="margin-top:20px">الخصوصية والأمان</h3>
    <p style="color:#64748b;line-height:1.9">التحويل يتم بالكامل عبر جدول تطابق محلي في متصفحك — لا يُرسَل أي نص تُدخله (بما فيه كلمات المرور المكتوبة بالخطأ) إلى أي خادم خارجي.</p>
  </div>

  <div class="t-card" style="margin-top:16px">
    <h2>أسئلة شائعة</h2>
    <div style="display:flex;flex-direction:column;gap:12px;margin-top:8px">
      <details style="background:#f8fafc;border-radius:10px;padding:14px">
        <summary style="cursor:pointer;font-weight:700;color:#0f172a">كيف تعرف الأداة اتجاه التحويل المطلوب؟</summary>
        <p style="margin:8px 0 0;color:#64748b;line-height:1.7">تحلل الأداة نوع الأحرف السائد في النص المُدخَل (لاتيني أو عربي) وتحوّله تلقائياً للاتجاه المعاكس الصحيح.</p>
      </details>
      <details style="background:#f8fafc;border-radius:10px;padding:14px">
        <summary style="cursor:pointer;font-weight:700;color:#0f172a">هل تعمل مع الأرقام والرموز أيضاً؟</summary>
        <p style="margin:8px 0 0;color:#64748b;line-height:1.7">الأداة تركّز على الحروف الأبجدية. الأرقام والرموز الشائعة تبقى كما هي دون تغيير.</p>
      </details>
      <details style="background:#f8fafc;border-radius:10px;padding:14px">
        <summary style="cursor:pointer;font-weight:700;color:#0f172a">هل يمكن استخدامها لاسترجاع كلمة مرور نسيت تبديل اللغة عند كتابتها؟</summary>
        <p style="margin:8px 0 0;color:#64748b;line-height:1.7">نعم، هذا من أشهر استخدامات هذه الأداة — لكن يُنصح بعدم لصق كلمات مرور حساسة جداً في أي أداة ويب كإجراء أمني عام.</p>
      </details>
    </div>
  </div>
</main>

<script>
// Standard Arabic 101 keyboard layout — unshifted row only (covers the
// overwhelming majority of real wrong-keyboard typos: a full word typed
// without pressing shift). Each QWERTY key maps to its Arabic letter.
const arMap = {
  'ذ':'`','ض':'q','ص':'w','ث':'e','ق':'r','ف':'t','غ':'y','ع':'u','ه':'i','خ':'o','ح':'p','ج':'[','د':']',
  'ش':'a','س':'s','ي':'d','ب':'f','ل':'g','ا':'h','ت':'j','ن':'k','م':'l','ك':';','ط':"'",
  'ئ':'z','ء':'x','ؤ':'c','ر':'v','ى':'n','ة':'m','و':',','ز':'.','ظ':'/',
};
const enMap = {};
for (const k in arMap) { enMap[arMap[k]] = k; }

function transliterate(text) {
  let arCount = 0, enCount = 0;
  for (const ch of text) {
    if (/[؀-ۿ]/.test(ch)) arCount++;
    else if (/[a-zA-Z]/.test(ch)) enCount++;
  }
  const toEnglish = arCount > enCount;
  let out = '';
  for (const ch of text) {
    if (toEnglish && arMap[ch] !== undefined) out += arMap[ch];
    else if (!toEnglish && enMap[ch] !== undefined) out += enMap[ch];
    else out += ch;
  }
  return out;
}

const inputText = document.getElementById('input-text');
const resultWrap = document.getElementById('result-wrap');
const resultText = document.getElementById('result-text');

document.getElementById('fix-btn').addEventListener('click', () => {
  const text = inputText.value;
  if (!text.trim()) return;
  const fixed = transliterate(text);
  resultText.textContent = fixed;
  resultText.style.direction = /[؀-ۿ]/.test(fixed) ? 'rtl' : 'ltr';
  resultText.style.textAlign = /[؀-ۿ]/.test(fixed) ? 'right' : 'left';
  resultWrap.style.display = 'block';
});

document.getElementById('clear-btn').addEventListener('click', () => {
  inputText.value = '';
  resultWrap.style.display = 'none';
});

document.getElementById('copy-btn').addEventListener('click', () => {
  navigator.clipboard.writeText(resultText.textContent);
  const b = document.getElementById('copy-btn');
  b.textContent = 'تم النسخ ✓';
  setTimeout(() => b.textContent = 'نسخ النص المصحح', 1500);
});
</script>
<?php tool_footer(); ?>
