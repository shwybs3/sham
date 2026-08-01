<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'تحويل الأرقام إلى كتابة بالعربي','url'=>TOOLS_BASE_URL.'/tools/number-to-words/',
    'description'=>'حوّل أي رقم إلى كتابته الصحيحة بالحروف العربية فوراً — مثالية للشيكات والفواتير والعقود.',
    'applicationCategory'=>'UtilitiesApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('✍️ تحويل الأرقام إلى كتابة بالعربي | أداة كتابة المبلغ بالحروف للشيكات | yassota','حوّل أي رقم إلى كتابته الصحيحة بالحروف العربية فوراً ومجاناً — مثالية لكتابة الشيكات والفواتير والعقود الرسمية بدقة قواعدية.',$schema,'#b45309');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#fef3c7">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#b45309" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
    </div>
    <h1>تحويل الأرقام إلى كتابة بالعربي</h1>
    <p>حوّل أي رقم إلى كتابته الصحيحة بالحروف العربية — مثالية للشيكات والفواتير والعقود.</p>
  </div>

  <div class="t-card" style="max-width:560px;margin:0 auto 24px">
    <label class="t-label">أدخل الرقم</label>
    <input type="text" id="num-input" class="t-input" placeholder="مثال: 125430" style="width:100%;font-size:18px;text-align:center" inputmode="numeric">
    <div style="display:flex;gap:10px;margin-top:12px">
      <select id="currency-select" class="t-input" style="flex:1">
        <option value="">بدون عملة (رقم فقط)</option>
        <option value="ريال سعودي">ريال سعودي</option>
        <option value="درهم إماراتي">درهم إماراتي</option>
        <option value="دينار كويتي">دينار كويتي</option>
        <option value="جنيه مصري">جنيه مصري</option>
        <option value="ليرة سورية">ليرة سورية</option>
        <option value="دولار أمريكي">دولار أمريكي</option>
        <option value="يورو">يورو</option>
      </select>
    </div>
    <div id="result" style="display:none;margin-top:20px"></div>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card">
    <h2>لماذا نكتب المبالغ بالحروف؟</h2>
    <p style="color:#64748b;line-height:1.9">كتابة الرقم بالحروف بجانب كتابته بالأرقام في الشيكات المصرفية والعقود والفواتير الرسمية ليست مجرد شكليات — إنها إجراء قانوني ومحاسبي أساسي يمنع التلاعب أو التزوير. فالأرقام يسهل تعديلها (مثلاً تحويل 100 إلى 1000 بإضافة صفر)، بينما كتابة "مئة" بالحروف يصعب تحريفها دون أن يظهر التلاعب بوضوح. لهذا تشترط البنوك والمؤسسات المالية في جميع الدول العربية كتابة قيمة الشيك بالحروف كإجراء إلزامي.</p>
    <p style="color:#64748b;line-height:1.9;margin-top:12px">اللغة العربية تتميز بقواعد نحوية دقيقة في صياغة الأعداد، حيث تختلف صيغة العدد حسب الجنس (مذكر/مؤنث) والعدد (مفرد/مثنى/جمع) — وهو ما يجعل كتابة الأرقام بالحروف يدوياً عرضة للأخطاء، خصوصاً في الأرقام الكبيرة التي تحتوي ملايين أو مليارات.</p>

    <h3 style="margin-top:20px">أين تُستخدم هذه الأداة؟</h3>
    <ul style="color:#64748b;line-height:2;padding-right:20px">
      <li>كتابة قيمة الشيكات المصرفية بالحروف بدقة قانونية</li>
      <li>إعداد الفواتير التجارية والعقود الرسمية</li>
      <li>كتابة قيم العطاءات والمناقصات الحكومية</li>
      <li>تحرير محاضر الاجتماعات والوثائق الرسمية التي تتطلب توثيق أرقام بالحروف</li>
      <li>مساعدة الطلاب في تعلم قواعد كتابة الأعداد بالعربية</li>
    </ul>

    <h3 style="margin-top:20px">أمثلة على التحويل</h3>
    <p style="color:#64748b;line-height:1.9">الرقم 125 يُكتب "مئة وخمسة وعشرون"، والرقم 1000 يُكتب "ألف"، بينما 2500000 يُكتب "مليونان وخمسمئة ألف". الأداة تتعامل تلقائياً مع كل هذه الحالات وفق القواعد النحوية العربية الصحيحة.</p>

    <h3 style="margin-top:20px">الخصوصية والأمان</h3>
    <p style="color:#64748b;line-height:1.9">التحويل يتم بالكامل داخل متصفحك عبر JavaScript فوراً بدون إرسال أي رقم تُدخله إلى أي خادم خارجي — خصوصية تامة حتى عند التعامل مع مبالغ مالية حساسة.</p>
  </div>

  <div class="t-card" style="margin-top:16px">
    <h2>أسئلة شائعة</h2>
    <div style="display:flex;flex-direction:column;gap:12px;margin-top:8px">
      <details style="background:#f8fafc;border-radius:10px;padding:14px">
        <summary style="cursor:pointer;font-weight:700;color:#0f172a">ما أكبر رقم يمكن تحويله؟</summary>
        <p style="margin:8px 0 0;color:#64748b;line-height:1.7">تدعم الأداة الأرقام حتى تريليون واحد بدقة كاملة.</p>
      </details>
      <details style="background:#f8fafc;border-radius:10px;padding:14px">
        <summary style="cursor:pointer;font-weight:700;color:#0f172a">هل يمكن تحويل أرقام عشرية (فيها كسور)؟</summary>
        <p style="margin:8px 0 0;color:#64748b;line-height:1.7">نعم، الأداة تكتب الجزء الصحيح ثم "فاصلة" أو تفصل الهللات/القروش إذا اخترت عملة.</p>
      </details>
      <details style="background:#f8fafc;border-radius:10px;padding:14px">
        <summary style="cursor:pointer;font-weight:700;color:#0f172a">هل يمكن إضافة أرقام سالبة؟</summary>
        <p style="margin:8px 0 0;color:#64748b;line-height:1.7">نعم، ستُكتب "سالب" أو "ناقص" قبل الرقم بالحروف.</p>
      </details>
    </div>
  </div>
</main>

<script>
const ones = ['', 'واحد', 'اثنان', 'ثلاثة', 'أربعة', 'خمسة', 'ستة', 'سبعة', 'ثمانية', 'تسعة'];
const onesF = ['', 'إحدى', 'اثنتا', 'ثلاث', 'أربع', 'خمس', 'ست', 'سبع', 'ثمان', 'تسع'];
const teens = ['عشرة', 'أحد عشر', 'اثنا عشر', 'ثلاثة عشر', 'أربعة عشر', 'خمسة عشر', 'ستة عشر', 'سبعة عشر', 'ثمانية عشر', 'تسعة عشر'];
const tens = ['', '', 'عشرون', 'ثلاثون', 'أربعون', 'خمسون', 'ستون', 'سبعون', 'ثمانون', 'تسعون'];
const hundreds = ['', 'مئة', 'مئتان', 'ثلاثمئة', 'أربعمئة', 'خمسمئة', 'ستمئة', 'سبعمئة', 'ثمانمئة', 'تسعمئة'];
const scales = [
  {v: 1000000000000, s: 'تريليون', d: 'تريليونان', p: 'تريليونات'},
  {v: 1000000000, s: 'مليار', d: 'ملياران', p: 'مليارات'},
  {v: 1000000, s: 'مليون', d: 'مليونان', p: 'ملايين'},
  {v: 1000, s: 'ألف', d: 'ألفان', p: 'آلاف'},
];

function threeDigits(n) {
  n = Math.floor(n);
  if (n === 0) return '';
  const h = Math.floor(n / 100);
  const rem = n % 100;
  const parts = [];
  if (h > 0) parts.push(hundreds[h]);
  if (rem > 0) {
    if (rem < 10) parts.push(ones[rem]);
    else if (rem < 20) parts.push(teens[rem - 10]);
    else {
      const t = Math.floor(rem / 10);
      const o = rem % 10;
      if (o > 0) parts.push(ones[o] + ' و' + tens[t]);
      else parts.push(tens[t]);
    }
  }
  return parts.join(' و');
}

function numberToArabic(num) {
  num = Math.floor(Math.abs(num));
  if (num === 0) return 'صفر';
  let result = [];
  let remaining = num;

  for (const scale of scales) {
    const count = Math.floor(remaining / scale.v);
    if (count > 0) {
      remaining -= count * scale.v;
      if (count === 1) result.push(scale.s);
      else if (count === 2) result.push(scale.d);
      else if (count >= 3 && count <= 10) result.push(threeDigits(count) + ' ' + scale.p);
      else result.push(threeDigits(count) + ' ' + scale.s);
    }
  }
  if (remaining > 0) result.push(threeDigits(remaining));

  return result.join(' و');
}

const input = document.getElementById('num-input');
const currencySelect = document.getElementById('currency-select');
const result = document.getElementById('result');

function convert() {
  const raw = input.value.replace(/[^\d.-]/g, '');
  if (!raw) { result.style.display = 'none'; return; }
  const num = parseFloat(raw);
  if (isNaN(num)) { result.style.display = 'none'; return; }

  const isNeg = num < 0;
  const intPart = Math.floor(Math.abs(num));
  const decPart = Math.round((Math.abs(num) - intPart) * 100);

  let words = numberToArabic(intPart);
  if (isNeg) words = 'سالب ' + words;

  const currency = currencySelect.value;
  if (currency) {
    words += ' ' + currency;
    if (decPart > 0) {
      words += ' و' + threeDigits(decPart) + (currency.includes('ريال') || currency.includes('درهم') || currency.includes('دولار') || currency.includes('يورو') ? ' هللة' : ' قرش') + (decPart > 2 ? '' : '');
    }
    words += ' فقط لا غير';
  } else if (decPart > 0) {
    words += ' فاصلة ' + threeDigits(decPart);
  }

  result.style.display = 'block';
  result.innerHTML = `
    <div style="background:linear-gradient(135deg,#b45309,#d97706);border-radius:14px;padding:22px;text-align:center;color:#fff">
      <div style="font-size:19px;font-weight:800;line-height:1.6">${words}</div>
    </div>
    <button id="copy-btn" style="margin-top:12px;width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;background:#fff;cursor:pointer;font-size:13px;font-family:inherit">نسخ النص</button>`;
  document.getElementById('copy-btn').onclick = () => {
    navigator.clipboard.writeText(words);
    const b = document.getElementById('copy-btn');
    b.textContent = 'تم النسخ ✓';
    setTimeout(() => b.textContent = 'نسخ النص', 1500);
  };
}

input.addEventListener('input', convert);
currencySelect.addEventListener('change', convert);
</script>
<?php tool_footer(); ?>
