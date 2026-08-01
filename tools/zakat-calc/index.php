<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'حاسبة الزكاة','url'=>TOOLS_BASE_URL.'/tools/zakat-calc/',
    'description'=>'احسب زكاة مالك ومدخراتك وذهبك بدقة وفق نصاب الزكاة الشرعي — أداة مجانية وفورية.',
    'applicationCategory'=>'FinanceApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('🕌 حاسبة الزكاة 2026 | احسب زكاة مالك والذهب والفضة بدقة | yassota','احسب زكاة أموالك ومدخراتك وذهبك وفضتك وفق النصاب الشرعي بضغطة زر — حاسبة زكاة مجانية دقيقة تراعي أسعار الذهب المحدّثة.',$schema,'#15803d');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#dcfce7">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
    </div>
    <h1>حاسبة الزكاة</h1>
    <p>احسب زكاة أموالك ومدخراتك وذهبك وفضتك بدقة وفق النصاب الشرعي.</p>
  </div>

  <div class="t-card" style="max-width:600px;margin:0 auto 24px">
    <div style="margin-bottom:16px">
      <label class="t-label">النقد والمدخرات (بالعملة التي تريد الحساب بها)</label>
      <input type="number" id="cash" class="t-input" placeholder="0" style="width:100%">
    </div>
    <div style="margin-bottom:16px">
      <label class="t-label">قيمة الذهب الذي تملكه (اختياري)</label>
      <input type="number" id="gold" class="t-input" placeholder="0" style="width:100%">
    </div>
    <div style="margin-bottom:16px">
      <label class="t-label">قيمة الفضة والاستثمارات التجارية الأخرى (اختياري)</label>
      <input type="number" id="silver" class="t-input" placeholder="0" style="width:100%">
    </div>
    <div style="margin-bottom:16px">
      <label class="t-label">الديون المستحقة عليك (تُخصم من الوعاء الزكوي)</label>
      <input type="number" id="debts" class="t-input" placeholder="0" style="width:100%">
    </div>
    <div style="margin-bottom:16px">
      <label class="t-label">نصاب الزكاة التقديري بعملتك (قيمة 85 غراماً ذهباً)</label>
      <input type="number" id="nisab" class="t-input" placeholder="مثال: 25000" style="width:100%">
      <div style="font-size:11px;color:#94a3b8;margin-top:4px">استخدم أداة <a href="<?= TOOLS_BASE_URL ?>/tools/gold-calc/" style="color:#15803d">حاسبة الذهب</a> لمعرفة سعر 85 غراماً بعملتك اليوم</div>
    </div>
    <button id="calc-btn" class="t-btn" style="width:100%;background:#15803d">احسب الزكاة</button>
    <div id="result" style="display:none;margin-top:20px"></div>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card">
    <h2>ما هي الزكاة ولماذا هي مهمة؟</h2>
    <p style="color:#64748b;line-height:1.9">الزكاة هي الركن الثالث من أركان الإسلام الخمسة، فريضة مالية سنوية على المسلم القادر تُصرف لأصناف محددة من مستحقيها (الفقراء والمساكين والعاملين عليها والمؤلفة قلوبهم وفي الرقاب والغارمين وفي سبيل الله وابن السبيل) كما ورد في القرآن الكريم. تبلغ نسبة زكاة المال والذهب والفضة والعروض التجارية <strong>2.5%</strong> من إجمالي الوعاء الزكوي (وهي نسبة "ربع العشر") إذا بلغ المال النصاب الشرعي ومرّ عليه حول قمري كامل (حولان الحول).</p>
    <p style="color:#64748b;line-height:1.9;margin-top:12px">النصاب هو الحد الأدنى من المال الذي يجب أن يملكه المسلم حتى تجب عليه الزكاة، ويُقدَّر تقليدياً بما يعادل قيمة 85 غراماً من الذهب عيار 24 (أو ما يعادل 595 غراماً من الفضة وفق بعض المذاهب، وإن كان الذهب هو المرجع الأكثر استخداماً اليوم). تتغيّر قيمة النصاب بالعملات المحلية يومياً مع تغيّر سعر الذهب، لذا يُنصح بالتحقق من السعر الحالي عند الحساب.</p>

    <h3 style="margin-top:20px">كيف تُحسب زكاة المال؟</h3>
    <p style="color:#64748b;line-height:1.9">تُجمع كل الأموال الزكوية (النقد، المدخرات، الذهب والفضة غير المستخدمين للزينة الشخصية المعتادة عند بعض المذاهب، عروض التجارة)، ثم تُخصم الديون المستحقة عليك، فإذا كان الناتج (الوعاء الزكوي) مساوياً للنصاب أو أكثر، تُحسب الزكاة بضرب هذا المبلغ في 2.5% (أو تقسيمه على 40).</p>

    <h3 style="margin-top:20px">أمثلة توضيحية</h3>
    <ul style="color:#64748b;line-height:2;padding-right:20px">
      <li>إذا كان مجموع أموالك 50,000 (بعد خصم الديون) وبلغت النصاب: الزكاة = 50,000 × 0.025 = 1,250</li>
      <li>إذا كان مجموع أموالك أقل من النصاب: لا تجب عليك الزكاة هذا العام</li>
      <li>الذهب المُعَدّ للاستخدام الشخصي المعتاد (الحُلي) محل خلاف فقهي في وجوب زكاته — استشر مرجعك الشرعي</li>
    </ul>

    <h3 style="margin-top:20px">شروط وجوب الزكاة</h3>
    <p style="color:#64748b;line-height:1.9">تجب الزكاة على المسلم البالغ العاقل الحر المالك لنصاب كامل ملكاً تاماً، بشرط مرور حول قمري كامل (354 يوماً تقريباً) على بلوغ المال النصاب. هذه الأداة تحسب المبلغ المستحق بافتراض تحقق هذه الشروط — للفتوى الدقيقة في حالتك الخاصة يُنصح بمراجعة عالم شرعي أو دار إفتاء معتمدة.</p>

    <h3 style="margin-top:20px">الخصوصية والأمان</h3>
    <p style="color:#64748b;line-height:1.9">جميع الحسابات تتم محلياً داخل متصفحك — أرقامك المالية لا تُرسَل أو تُخزَّن على أي خادم، خصوصية تامة لمعلوماتك المالية الحساسة.</p>
  </div>

  <div class="t-card" style="margin-top:16px">
    <h2>أسئلة شائعة عن الزكاة</h2>
    <div style="display:flex;flex-direction:column;gap:12px;margin-top:8px">
      <details style="background:#f8fafc;border-radius:10px;padding:14px">
        <summary style="cursor:pointer;font-weight:700;color:#0f172a">هل تجب الزكاة على راتبي الشهري مباشرة؟</summary>
        <p style="margin:8px 0 0;color:#64748b;line-height:1.7">لا، الزكاة تجب على المدخرات المتبقية التي بلغت النصاب ومرّ عليها حول كامل، وليس على الدخل الشهري مباشرة عند استلامه.</p>
      </details>
      <details style="background:#f8fafc;border-radius:10px;padding:14px">
        <summary style="cursor:pointer;font-weight:700;color:#0f172a">ماذا لو اختلف تاريخ بلوغ كل مبلغ للنصاب؟</summary>
        <p style="margin:8px 0 0;color:#64748b;line-height:1.7">عملياً يعتمد أغلب الناس تاريخاً سنوياً ثابتاً لحساب زكاة كل أموالهم مجتمعة تيسيراً، وهذا رأي فقهي معتبر لدى كثير من أهل العلم.</p>
      </details>
      <details style="background:#f8fafc;border-radius:10px;padding:14px">
        <summary style="cursor:pointer;font-weight:700;color:#0f172a">هل حُلي الذهب للاستخدام الشخصي عليه زكاة؟</summary>
        <p style="margin:8px 0 0;color:#64748b;line-height:1.7">مسألة خلافية بين الفقهاء؛ يرى بعضهم عدم وجوب الزكاة في الحُلي المُعَدّ للُبس المعتاد، بينما يرى آخرون وجوبها. يُنصح بمراجعة مرجعك الشرعي.</p>
      </details>
    </div>
  </div>
</main>

<script>
const calcBtn = document.getElementById('calc-btn');
const result = document.getElementById('result');

calcBtn.addEventListener('click', () => {
  const cash = parseFloat(document.getElementById('cash').value) || 0;
  const gold = parseFloat(document.getElementById('gold').value) || 0;
  const silver = parseFloat(document.getElementById('silver').value) || 0;
  const debts = parseFloat(document.getElementById('debts').value) || 0;
  const nisab = parseFloat(document.getElementById('nisab').value) || 0;

  const total = cash + gold + silver - debts;
  result.style.display = 'block';

  if (nisab <= 0) {
    result.innerHTML = `<div style="background:#fef3c7;border-radius:12px;padding:16px;color:#92400e;font-size:13px">الرجاء إدخال قيمة النصاب بعملتك لإتمام الحساب بدقة.</div>`;
    return;
  }

  if (total < nisab) {
    result.innerHTML = `
      <div style="background:#f1f5f9;border-radius:14px;padding:20px;text-align:center">
        <div style="font-size:15px;color:#475569;font-weight:700">لا تجب عليك الزكاة هذا العام</div>
        <div style="font-size:12px;color:#94a3b8;margin-top:6px">إجمالي وعائك الزكوي (${total.toLocaleString('ar')}) أقل من النصاب (${nisab.toLocaleString('ar')})</div>
      </div>`;
    return;
  }

  const zakat = total * 0.025;
  result.innerHTML = `
    <div style="background:linear-gradient(135deg,#15803d,#16a34a);border-radius:14px;padding:22px;text-align:center;color:#fff">
      <div style="font-size:13px;opacity:.9;margin-bottom:6px">مبلغ الزكاة المستحق (2.5%)</div>
      <div style="font-size:32px;font-weight:900">${zakat.toLocaleString('ar', {maximumFractionDigits:2})}</div>
    </div>
    <div style="background:#f8fafc;border-radius:12px;padding:14px;margin-top:12px;font-size:13px;color:#475569;line-height:2">
      <div>💰 إجمالي الوعاء الزكوي: <strong style="color:#0f172a">${total.toLocaleString('ar')}</strong></div>
      <div>📊 النصاب المُستخدم: <strong style="color:#0f172a">${nisab.toLocaleString('ar')}</strong></div>
      <div>✅ الحالة: <strong style="color:#15803d">تجب عليك الزكاة</strong></div>
    </div>`;
});
</script>
<?php tool_footer(); ?>
