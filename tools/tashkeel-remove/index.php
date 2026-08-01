<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'إزالة التشكيل من النص العربي','url'=>TOOLS_BASE_URL.'/tools/tashkeel-remove/',
    'description'=>'احذف الحركات والتشكيل من أي نص عربي فوراً — أداة مجانية دقيقة تحافظ على النص الأصلي كاملاً.',
    'applicationCategory'=>'UtilitiesApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('🔤 إزالة التشكيل من النص العربي فوراً | حذف الحركات مجاناً | yassota','احذف الفتحة والضمة والكسرة والشدة والسكون من أي نص عربي بضغطة واحدة — أداة مجانية دقيقة 100% تعمل داخل متصفحك مباشرة.',$schema,'#7c3aed');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#ede9fe">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/></svg>
    </div>
    <h1>إزالة التشكيل من النص العربي</h1>
    <p>احذف الحركات (الفتحة، الضمة، الكسرة، الشدة، السكون، التنوين) من أي نص عربي فوراً.</p>
  </div>

  <div class="t-card" style="max-width:720px;margin:0 auto 24px">
    <label class="t-label">الصق النص المُشكَّل هنا</label>
    <textarea id="input-text" rows="6" class="t-input" style="width:100%;font-size:16px;line-height:2" placeholder="مثال: بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ"></textarea>
    <div style="display:flex;gap:10px;margin-top:12px;flex-wrap:wrap">
      <button id="remove-btn" class="t-btn" style="flex:1;min-width:160px">إزالة التشكيل</button>
      <button id="clear-btn" class="t-btn t-btn-secondary" style="flex:0 0 auto">مسح</button>
    </div>
    <div id="result-wrap" style="display:none;margin-top:20px">
      <label class="t-label">النص بعد إزالة التشكيل</label>
      <div id="result-text" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px;font-size:16px;line-height:2;color:#1e293b;min-height:60px;white-space:pre-wrap"></div>
      <button id="copy-btn" style="margin-top:10px;width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;background:#fff;cursor:pointer;font-size:13px;font-family:inherit">نسخ النص</button>
    </div>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card">
    <h2>ما هو التشكيل ولماذا نحتاج إزالته؟</h2>
    <p style="color:#64748b;line-height:1.9">التشكيل (أو الحركات) هي رموز صغيرة تُضاف فوق أو تحت الحروف العربية لتوضيح النطق الصحيح: الفتحة (ــَـ)، الضمة (ــُـ)، الكسرة (ــِـ)، السكون (ــْـ)، الشدة (ــّـ)، وأشكال التنوين (ــًـ ــٍـ ــٌـ). تظهر هذه العلامات بكثرة في النصوص الدينية كالقرآن الكريم والحديث الشريف، وفي الكتب التعليمية الموجّهة للأطفال ومتعلمي اللغة العربية كلغة ثانية، لضمان النطق الصحيح دون لبس.</p>
    <p style="color:#64748b;line-height:1.9;margin-top:12px">لكن في كثير من السياقات التقنية والبرمجية، وجود التشكيل يسبب مشاكل حقيقية: محركات البحث ومحركات المقارنة النصية (String Matching) قد تتعامل مع "الكِتاب" و"الكتاب" كنصين مختلفين تماماً بسبب حرف الكسرة الإضافي، مما يُفشل عمليات البحث والفهرسة وقواعد البيانات. لهذا يحتاج المطورون والباحثون وصنّاع المحتوى أداة سريعة لتطبيع (Normalize) النص العربي بإزالة كل علامات التشكيل دفعة واحدة.</p>

    <h3 style="margin-top:20px">استخدامات أداة إزالة التشكيل</h3>
    <ul style="color:#64748b;line-height:2;padding-right:20px">
      <li>تنظيف النصوص القرآنية أو الدينية للاستخدام في تطبيقات لا تحتاج تشكيلاً</li>
      <li>تحضير بيانات نصية عربية لتدريب نماذج الذكاء الاصطناعي أو محركات البحث</li>
      <li>توحيد النصوص قبل تخزينها في قواعد البيانات لتفادي تكرار السجلات المتشابهة</li>
      <li>تسهيل المقارنة النصية والبحث الدقيق ضمن أنظمة إدارة المحتوى</li>
      <li>تبسيط النصوص التعليمية لمستوى القراءة المتقدم الذي لا يحتاج تشكيلاً توضيحياً</li>
    </ul>

    <h3 style="margin-top:20px">هل تُحذف الشدة أيضاً؟</h3>
    <p style="color:#64748b;line-height:1.9">نعم، الأداة تُزيل جميع علامات التشكيل الثمانية القياسية في نظام يونيكود العربي (Unicode Arabic Diacritics): الفتحة، الضمة، الكسرة، الفتحتان (تنوين النصب)، الضمتان (تنوين الرفع)، الكسرتان (تنوين الجر)، الشدة، والسكون — مع الحفاظ الكامل على الحروف الأصلية وعلامات الترقيم دون أي تغيير.</p>

    <h3 style="margin-top:20px">الخصوصية والأمان</h3>
    <p style="color:#64748b;line-height:1.9">تعمل هذه الأداة بالكامل داخل متصفحك عبر معالجة نصية محلية (JavaScript Regular Expression) — النص الذي تُدخله لا يُرسَل إلى أي خادم خارجي إطلاقاً، حتى لو كان نصاً حساساً أو خاصاً.</p>
  </div>

  <div class="t-card" style="margin-top:16px">
    <h2>أسئلة شائعة</h2>
    <div style="display:flex;flex-direction:column;gap:12px;margin-top:8px">
      <details style="background:#f8fafc;border-radius:10px;padding:14px">
        <summary style="cursor:pointer;font-weight:700;color:#0f172a">هل تحذف الأداة علامات الترقيم أيضاً؟</summary>
        <p style="margin:8px 0 0;color:#64748b;line-height:1.7">لا، تُزال فقط رموز التشكيل الصوتية. علامات الترقيم والفراغات وترتيب النص تبقى كما هي تماماً.</p>
      </details>
      <details style="background:#f8fafc;border-radius:10px;padding:14px">
        <summary style="cursor:pointer;font-weight:700;color:#0f172a">هل يوجد حد لطول النص؟</summary>
        <p style="margin:8px 0 0;color:#64748b;line-height:1.7">لا يوجد حد عملي — يمكنك معالجة نصوص طويلة جداً مثل فصول كاملة من كتاب فوراً.</p>
      </details>
      <details style="background:#f8fafc;border-radius:10px;padding:14px">
        <summary style="cursor:pointer;font-weight:700;color:#0f172a">هل تُزال همزة القطع أو مد الألف؟</summary>
        <p style="margin:8px 0 0;color:#64748b;line-height:1.7">لا، الهمزات ومدود الألف حروف أصلية وليست تشكيلاً، وتبقى دون تغيير.</p>
      </details>
    </div>
  </div>
</main>

<script>
const inputText = document.getElementById('input-text');
const resultWrap = document.getElementById('result-wrap');
const resultText = document.getElementById('result-text');

document.getElementById('remove-btn').addEventListener('click', () => {
  const text = inputText.value;
  if (!text.trim()) return;
  const cleaned = text.replace(/[\u064B-\u0670\u06D6-\u06ED]/g, '');
  resultText.textContent = cleaned;
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
  setTimeout(() => b.textContent = 'نسخ النص', 1500);
});
</script>
<?php tool_footer(); ?>
