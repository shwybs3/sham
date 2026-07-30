<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
  '@context'=>'https://schema.org','@type'=>'WebApplication',
  'name'=>'مولد كلمات المرور القوية','url'=>TOOLS_BASE_URL.'/tools/password/',
  'description'=>'أنشئ كلمات مرور قوية وعشوائية مع خيارات متقدمة للطول والأحرف والرموز — مجاني وآمن بالكامل في متصفحك',
  'applicationCategory'=>'SecurityApplication','operatingSystem'=>'All',
  'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
  'featureList'=>['توليد كلمات مرور عشوائية','مقياس قوة كلمة المرور','توليد دفعي حتى 50 كلمة مرور','دعم الرموز الخاصة']
]);
tool_head('مولد كلمات المرور القوية — عشوائية وآمنة | yassota','أنشئ كلمات مرور قوية وعشوائية بضغطة واحدة. اختر الطول والأحرف والرموز. مقياس القوة الفوري. مجاني 100% وخاص تماماً.',$schema,'#7c3aed');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#ede9fe">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/><circle cx="12" cy="16" r="1" fill="#7c3aed"/></svg>
    </div>
    <h1>مولد كلمات المرور القوية</h1>
    <p>أنشئ كلمات مرور عشوائية وقوية لا يمكن اختراقها. يعمل بالكامل داخل متصفحك — لا يُرسَل شيء لأي خادم.</p>
  </div>

  <div class="t-card">
    <div style="margin-bottom:20px">
      <label class="t-label">طول كلمة المرور: <strong id="len-label" style="color:#7c3aed">16</strong> حرفاً</label>
      <input type="range" id="len-range" min="8" max="128" value="16" oninput="document.getElementById('len-label').textContent=this.value;generate()" style="width:100%;accent-color:#7c3aed;cursor:pointer;margin-top:4px">
      <div style="display:flex;justify-content:space-between;font-size:11px;color:#94a3b8;margin-top:2px"><span>8</span><span>128</span></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px">
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:10px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px">
        <input type="checkbox" id="opt-upper" checked onchange="generate()" style="accent-color:#7c3aed;width:16px;height:16px">
        <span>أحرف كبيرة (A-Z)</span>
      </label>
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:10px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px">
        <input type="checkbox" id="opt-lower" checked onchange="generate()" style="accent-color:#7c3aed;width:16px;height:16px">
        <span>أحرف صغيرة (a-z)</span>
      </label>
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:10px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px">
        <input type="checkbox" id="opt-nums" checked onchange="generate()" style="accent-color:#7c3aed;width:16px;height:16px">
        <span>أرقام (0-9)</span>
      </label>
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:10px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px">
        <input type="checkbox" id="opt-syms" checked onchange="generate()" style="accent-color:#7c3aed;width:16px;height:16px">
        <span>رموز (!@#$%)</span>
      </label>
    </div>

    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:20px;font-size:14px">
      <input type="checkbox" id="opt-nosim" onchange="generate()" style="accent-color:#7c3aed;width:16px;height:16px">
      <span>استبعاد الأحرف المتشابهة (0، O، 1، l، I)</span>
    </label>

    <div style="background:#f5f3ff;border-radius:12px;padding:16px;margin-bottom:14px;direction:ltr">
      <div style="display:flex;align-items:center;gap:10px">
        <span id="pw-out" style="flex:1;font-family:monospace;font-size:16px;font-weight:700;letter-spacing:1px;word-break:break-all;color:#1e1b4b">اضغط توليد</span>
        <button onclick="copyPw()" title="نسخ" style="background:#7c3aed;border:none;border-radius:8px;padding:8px;cursor:pointer;color:#fff;display:flex">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
        </button>
      </div>
      <div style="margin-top:10px">
        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px">
          <span id="strength-label" style="font-weight:600;color:#64748b">—</span>
          <span id="entropy-label" style="color:#94a3b8"></span>
        </div>
        <div class="t-progress"><div id="strength-bar" class="t-progress-bar" style="width:0%;background:#7c3aed;height:8px;border-radius:4px"></div></div>
      </div>
    </div>

    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px">
      <button class="t-btn" onclick="generate()" style="background:#7c3aed">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
        توليد كلمة مرور
      </button>
      <button class="t-btn t-btn-secondary" onclick="copyPw()">نسخ</button>
    </div>

    <div style="border-top:1px solid #e2e8f0;padding-top:18px">
      <label class="t-label">التوليد الدفعي</label>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <input type="number" id="bulk-count" min="2" max="50" value="10" class="t-input" style="width:100px">
        <span style="font-size:13px;color:#64748b">كلمة مرور (2-50)</span>
        <button class="t-btn" onclick="generateBulk()" style="background:#7c3aed">توليد دفعي</button>
        <button class="t-btn t-btn-secondary" onclick="copyAllPw()">نسخ الكل</button>
      </div>
      <div id="bulk-out" style="margin-top:12px;direction:ltr;display:none">
        <div style="max-height:220px;overflow-y:auto;border:1.5px solid #e2e8f0;border-radius:10px;background:#f8fafc"></div>
      </div>
    </div>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card" style="line-height:1.9">
    <h2 style="font-size:22px;font-weight:800;color:#1e1b4b;margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid #ede9fe">ما هو مولد كلمات المرور؟ — شرح كامل</h2>

    <p style="color:#374151;margin-bottom:16px">مولد كلمات المرور هو أداة برمجية تعمل على إنشاء كلمات مرور عشوائية وقوية بشكل تلقائي، بدلاً من اضطرار المستخدم إلى التفكير بكلمة مرور يدوياً. تعتمد هذه الأدوات على خوارزميات توليد عشوائي حقيقية تستخدم مصادر عشوائية على مستوى نظام التشغيل أو المعالج، مما يجعل الكلمات المُولَّدة أكثر أماناً بمراحل من أي كلمة يبتكرها الإنسان. في عصر تتزايد فيه الاختراقات الإلكترونية بشكل مخيف، أصبح امتلاك كلمات مرور قوية ضرورة لا رفاهية، وهنا تأتي أهمية مولدات كلمات المرور.</p>

    <p style="color:#374151;margin-bottom:16px">تعتمد أداتنا على واجهة برمجية خاصة بالمتصفح تُعرف بـ <strong>Web Crypto API</strong>، وتحديداً دالة <code style="background:#f3f4f6;padding:2px 6px;border-radius:4px">crypto.getRandomValues()</code>، وهي تُولِّد أرقاماً عشوائية حقيقية (CSPRNG) تعتمد على مصادر خارجية غير متوقعة مثل تحركات الماوس وضغطات لوحة المفاتيح وزمن المقاطعات. هذا يجعل الكلمات المُولَّدة غير قابلة للتنبؤ من الناحية الرياضية، على عكس الدوال العشوائية العادية التي تُعطي نفس التسلسل إذا عرفت البذرة الأولى.</p>

    <p style="color:#374151;margin-bottom:16px">تقيس قوة كلمة المرور رياضياً بمفهوم يُسمى <strong>الأنتروبيا</strong> وتُحسب بوحدة البت. الأنتروبيا = طول الكلمة × log₂(حجم مجموعة الأحرف). فمثلاً: كلمة مرور مكوّنة من 16 حرفاً من مجموعة تضم 72 رمزاً (أحرف كبيرة + صغيرة + أرقام + رموز) تمتلك أنتروبيا تساوي 16 × log₂(72) ≈ 97 بت. هذا المستوى يعني أن أقوى الحواسيب في العالم ستحتاج إلى آلاف السنين لاختراقها بهجوم القوة الغاشمة، حتى لو كانت تجرب مليارات الاحتمالات في الثانية.</p>

    <h2 style="font-size:20px;font-weight:800;color:#1e1b4b;margin:28px 0 16px;padding-bottom:10px;border-bottom:2px solid #ede9fe">كيفية استخدام الأداة خطوة بخطوة</h2>

    <p style="color:#374151;margin-bottom:14px">صُممت الأداة لتكون بسيطة للمبتدئين وقوية للمحترفين في آنٍ واحد. إليك الخطوات الكاملة للحصول على كلمة مرور مثالية:</p>

    <div style="background:#f5f3ff;border-radius:12px;padding:16px;margin-bottom:20px">
      <div style="display:flex;flex-direction:column;gap:12px">
        <div style="display:flex;gap:12px;align-items:flex-start">
          <div style="min-width:28px;height:28px;background:#7c3aed;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff">1</div>
          <div><strong style="color:#1e1b4b">حدّد الطول المطلوب</strong><br><span style="font-size:13px;color:#64748b">حرّك شريط التمرير لتحديد طول كلمة المرور. الحد الأدنى 8 أحرف للحسابات غير الحساسة، لكن يُنصح بـ 16 حرفاً أو أكثر للبريد الإلكتروني والحسابات المصرفية.</span></div>
        </div>
        <div style="display:flex;gap:12px;align-items:flex-start">
          <div style="min-width:28px;height:28px;background:#7c3aed;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff">2</div>
          <div><strong style="color:#1e1b4b">اختر مجموعات الأحرف</strong><br><span style="font-size:13px;color:#64748b">حدد ما تريد تضمينه: أحرف كبيرة، صغيرة، أرقام، رموز خاصة. كلما أضفت مجموعات أكثر، ارتفعت قوة كلمة المرور بشكل كبير.</span></div>
        </div>
        <div style="display:flex;gap:12px;align-items:flex-start">
          <div style="min-width:28px;height:28px;background:#7c3aed;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff">3</div>
          <div><strong style="color:#1e1b4b">استبعاد الأحرف المتشابهة (اختياري)</strong><br><span style="font-size:13px;color:#64748b">إذا كنت بحاجة لنسخ كلمة المرور يدوياً أحياناً، فعّل هذا الخيار لاستبعاد الأحرف التي تتشابه بصرياً مثل O وصفر، وl وأحرف I الكبيرة.</span></div>
        </div>
        <div style="display:flex;gap:12px;align-items:flex-start">
          <div style="min-width:28px;height:28px;background:#7c3aed;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff">4</div>
          <div><strong style="color:#1e1b4b">اضغط "توليد كلمة مرور"</strong><br><span style="font-size:13px;color:#64748b">ستظهر كلمة المرور فوراً مع مقياس القوة الملوّن. يمكنك الضغط مجدداً لتوليد كلمة مختلفة.</span></div>
        </div>
        <div style="display:flex;gap:12px;align-items:flex-start">
          <div style="min-width:28px;height:28px;background:#7c3aed;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff">5</div>
          <div><strong style="color:#1e1b4b">انسخ واحفظ في مدير كلمات المرور</strong><br><span style="font-size:13px;color:#64748b">اضغط زر النسخ، ثم احفظها فوراً في تطبيق مثل Bitwarden أو 1Password أو KeePassXC. لا تتركها في ملف نصي عادي.</span></div>
        </div>
      </div>
    </div>

    <h2 style="font-size:20px;font-weight:800;color:#1e1b4b;margin:28px 0 16px;padding-bottom:10px;border-bottom:2px solid #ede9fe">استخدامات مولد كلمات المرور في حياتك اليومية</h2>

    <p style="color:#374151;margin-bottom:16px">في عالم يعتمد فيه كل شيء على الإنترنت، يُعدّ وجود كلمة مرور قوية لكل حساب أمراً حيوياً. وفيما يلي أبرز المجالات التي تحتاج فيها إلى كلمات مرور قوية:</p>

    <p style="color:#374151;margin-bottom:16px"><strong style="color:#1e1b4b">الحسابات الشخصية على الإنترنت:</strong> يمتلك المستخدم العادي أكثر من 100 حساب رقمي، بدءاً من البريد الإلكتروني ووسائل التواصل الاجتماعي، وانتهاءً بمنصات التسوق والترفيه. استخدام كلمة مرور ضعيفة أو إعادة استخدام نفس الكلمة يعني أن اختراق حساب واحد يمهّد للسيطرة على الجميع. مولد كلمات المرور يحل هذه المشكلة بتوفير كلمة فريدة لكل حساب.</p>

    <p style="color:#374151;margin-bottom:16px"><strong style="color:#1e1b4b">الحسابات المصرفية والمالية:</strong> البنوك وتطبيقات الدفع مثل PayPal وSadad وفوري تحتاج إلى أعلى مستويات الحماية. كلمة مرور من 20 حرفاً تجمع الأنواع الأربعة توفر حماية تجعل اختراق الحساب شبه مستحيل، حتى في مواجهة أكثر الهجمات تطوراً.</p>

    <p style="color:#374151;margin-bottom:16px"><strong style="color:#1e1b4b">بيئات العمل والشركات:</strong> في الفرق التقنية والشركات، تُستخدم كلمات المرور للوصول إلى الخوادم وقواعد البيانات وأنظمة إدارة المحتوى. توليد كلمات مرور دفعية يُسهّل على مسؤولي الأنظمة إنشاء بيانات اعتماد لفريق كامل دفعة واحدة.</p>

    <p style="color:#374151;margin-bottom:16px"><strong style="color:#1e1b4b">حماية الشبكات اللاسلكية:</strong> كلمة مرور WiFi ضعيفة تعني أن جيرانك أو المارة يمكنهم الاتصال بشبكتك. أنشئ كلمة مرور من 20+ حرفاً للراوتر المنزلي — لن تحتاج لكتابتها يدوياً لأنك ستدخلها مرة واحدة فقط في كل جهاز.</p>

    <h2 style="font-size:20px;font-weight:800;color:#1e1b4b;margin:28px 0 16px;padding-bottom:10px;border-bottom:2px solid #ede9fe">الفرق بين كلمة المرور العشوائية وكلمة المرور التي تختارها يدوياً</h2>

    <p style="color:#374151;margin-bottom:16px">يعتقد كثيرون أنهم يمكنهم اختيار كلمات مرور قوية بأنفسهم، لكن الدراسات تُثبت خلاف ذلك. حين يختار البشر كلمات مرور بشكل يدوي، يميلون إلى استخدام أنماط متوقعة: تواريخ الميلاد، أسماء أحباء مع أرقام، كلمات من قاموس مع استبدال بسيط مثل "@" بدل "a". يعرف المخترقون هذه الأنماط تماماً ويبدأون بها في هجماتهم.</p>

    <p style="color:#374151;margin-bottom:16px">في المقابل، الكلمات المُولَّدة آلياً لا تتبع أي نمط بشري. كل حرف فيها مستقل تماماً عن الحرف الذي قبله، مما يجعل تحليل الأنماط عديم الفائدة للمهاجم. حتى لو كان المهاجم يعرف طول الكلمة ومجموعة الأحرف المستخدمة، فعليه تجربة جميع الاحتمالات الممكنة واحداً واحداً.</p>

    <p style="color:#374151;margin-bottom:16px">ثمة حالة استثنائية حيث يُفضَّل أسلوب "عبارة المرور" (Passphrase): وهي اختيار 4-5 كلمات عشوائية من القاموس وتسلسلها، مثل "شجرة-بحر-ملح-كتاب-سريع". هذا الأسلوب يوفر أنتروبيا عالية مع سهولة الحفظ. لكن للحسابات الرقمية التي يُديرها مدير كلمات المرور، فالكلمة العشوائية من مولّدنا هي الأمثل دائماً.</p>

    <h2 style="font-size:20px;font-weight:800;color:#1e1b4b;margin:28px 0 16px;padding-bottom:10px;border-bottom:2px solid #ede9fe">أسئلة شائعة</h2>

    <div style="display:flex;flex-direction:column;gap:16px">
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#1e1b4b;cursor:pointer;font-size:14px">هل كلمات المرور التي يولّدها الموقع آمنة تماماً؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">نعم، لأن التوليد يتم بالكامل داخل متصفحك باستخدام دالة <code>crypto.getRandomValues()</code> المبنية في المتصفح. لا تُرسَل كلمة المرور إلى أي خادم، ولا نحتفظ بأي سجل لها. ما يُنشأ في متصفحك يبقى في متصفحك تماماً.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#1e1b4b;cursor:pointer;font-size:14px">ما الطول المثالي لكلمة المرور؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">يعتمد ذلك على الغرض: 8 أحرف كحدٍّ أدنى مطلق للحسابات غير الحساسة، 12-16 حرفاً لمعظم الحسابات العامة، 20+ حرفاً للحسابات المصرفية والبريد الإلكتروني الرئيسي ومديري كلمات المرور. مع زيادة قدرات الحواسيب سنوياً، يُنصح دائماً باختيار الأطول.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#1e1b4b;cursor:pointer;font-size:14px">هل أحتاج إلى تغيير كلمات المرور بانتظام؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">أوصت الهيئات الأمنية القديمة بتغييرها كل 90 يوماً، لكن الإرشادات الحديثة من NIST (المعهد الأمريكي للمعايير والتقنية) تُوضح أن التغيير الدوري القسري يضرّ أكثر مما ينفع لأنه يدفع المستخدمين لاختيار كلمات أضعف. غيّر كلمة المرور فقط عند: الاشتباه في اختراق، تسريب بيانات الموقع، مشاركتها مع شخص آخر.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#1e1b4b;cursor:pointer;font-size:14px">أين أحفظ كلمات المرور الطويلة التي لا أستطيع حفظها في الذاكرة؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">استخدم مدير كلمات مرور موثوقاً مثل Bitwarden (مجاني ومفتوح المصدر)، أو 1Password، أو KeePassXC للتخزين المحلي. هذه التطبيقات تشفّر قاعدة البيانات بكلمة مرور رئيسية واحدة تحفظها أنت، وتتولى حفظ الباقي. لا تضع كلمات المرور في ملف Word أو Excel غير مشفّر.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#1e1b4b;cursor:pointer;font-size:14px">بعض المواقع لا تقبل الرموز الخاصة — ماذا أفعل؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">إذا كان الموقع يرفض الرموز، ألغِ تحديد خانة "رموز" في الأداة وولّد كلمة تعتمد على الأحرف والأرقام فقط. لتعويض غياب الرموز وإبقاء الأمان عالياً، زِد طول الكلمة إلى 20 حرفاً أو أكثر. المواقع التي تمنع الرموز في كلمات المرور تُعدّ ذات ممارسات أمنية ضعيفة، لذا انتبه لمعلوماتك على هذه المواقع.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#1e1b4b;cursor:pointer;font-size:14px">ما معنى "استبعاد الأحرف المتشابهة"؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">بعض الأحرف تبدو متطابقة تقريباً في الخطوط الرقمية: الحرف O (أوه) والرقم 0 (صفر)، الحرف l (إل الصغير) والحرف I (آي الكبير) والرقم 1 (واحد)، الحرف B والرقم 8. عند تفعيل هذا الخيار تُستبعد هذه الأحرف من مجموعة التوليد، مما يُسهّل نسخ كلمة المرور يدوياً أو قراءتها بوضوح.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#1e1b4b;cursor:pointer;font-size:14px">هل يمكن استخدام الأداة من الجوال؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">نعم، الأداة متجاوبة تماماً مع الهاتف المحمول. يمكنك توليد كلمات المرور من أي هاتف أو جهاز لوحي ثم نسخها مباشرة. تدعم الأداة أيضاً متصفحات الجوال الشائعة مثل Chrome وSafari وFirefox للأندرويد وiOS.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#1e1b4b;cursor:pointer;font-size:14px">ما هو مقياس قوة كلمة المرور وكيف يُحسب؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">مقياس القوة يعتمد على حساب الأنتروبيا (بالبت) وهي مقياس للعشوائية. تُحسب بضرب طول الكلمة في لوغاريتم ثنائي لحجم مجموعة الأحرف. درجات القوة: أقل من 40 بت = ضعيفة، 40-60 = مقبولة، 60-80 = قوية، أكثر من 80 = قوية جداً. الأداة تحسب هذا تلقائياً وتعرضه مع اللون المناسب.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#1e1b4b;cursor:pointer;font-size:14px">هل أحتاج اتصالاً بالإنترنت لاستخدام الأداة؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">تحتاج الإنترنت لتحميل الصفحة لأول مرة فقط. بعد ذلك، جميع عمليات التوليد تتم بالكامل في المتصفح بدون أي اتصال بالخادم. إذا حفظت الصفحة على جهازك، يمكنك استخدامها حتى بدون إنترنت.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#1e1b4b;cursor:pointer;font-size:14px">ما التوليد الدفعي وكيف أستفيد منه؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">التوليد الدفعي يُتيح إنشاء عدة كلمات مرور دفعة واحدة، تصل حتى 50 كلمة. يُفيد ذلك مديري الأنظمة حين يحتاجون إنشاء بيانات اعتماد لفريق كامل، أو المطورين الذين يريدون ملء قاعدة بيانات اختبار ببيانات مستخدمين وهمية، أو أي حالة تحتاج كلمات مرور متعددة في وقت واحد.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#1e1b4b;cursor:pointer;font-size:14px">هل المصادقة الثنائية تُغني عن كلمة المرور القوية؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">لا، كلاهما مكمّل للآخر وليس بديلاً. المصادقة الثنائية (2FA) توفر طبقة حماية إضافية، لكن إذا كانت كلمة مرورك ضعيفة وسرقها أحد قبل تطلّب كود 2FA، فقد تُعترض الأكواد بهجمات التصيد. الحل المثالي: كلمة مرور قوية فريدة + 2FA.</p>
      </details>
    </div>

    <h2 style="font-size:20px;font-weight:800;color:#1e1b4b;margin:28px 0 16px;padding-bottom:10px;border-bottom:2px solid #ede9fe">الخلاصة</h2>
    <p style="color:#374151;margin-bottom:16px">في عالم تتضاعف فيه التهديدات الإلكترونية يوماً بعد يوم، يُعدّ استخدام مولد كلمات مرور موثوق خطوةً أساسية لحماية حضورك الرقمي. أداتنا توفر لك كل ما تحتاجه: توليد عشوائي حقيقي، خيارات مرنة تناسب مختلف المتطلبات، مقياس قوة فوري، وتوليد دفعي للمحترفين. ما يجعلها مختلفة أنها تعمل بالكامل في متصفحك — لا خوادم، لا تسجيل، لا تتبع. ابدأ اليوم بتأمين حساباتك: ولّد كلمة مرور قوية، احفظها في مدير كلمات مرور، وفعّل المصادقة الثنائية كلما أمكن ذلك. أمانك الرقمي يستحق هذا الاستثمار البسيط.</p>
  </div>
</main>

<script>
var UPPER='ABCDEFGHIJKLMNOPQRSTUVWXYZ';
var LOWER='abcdefghijklmnopqrstuvwxyz';
var NUMS='0123456789';
var SYMS='!@#$%^&*()_+-=[]{}|;:,.<>?';
var SIMILAR=/[0O1lI]/g;
var bulkPasswords=[];

function getCharset(){
  var c='';
  if(document.getElementById('opt-upper').checked) c+=UPPER;
  if(document.getElementById('opt-lower').checked) c+=LOWER;
  if(document.getElementById('opt-nums').checked) c+=NUMS;
  if(document.getElementById('opt-syms').checked) c+=SYMS;
  if(document.getElementById('opt-nosim').checked) c=c.replace(SIMILAR,'');
  return c;
}

function genOne(len,chars){
  if(!chars||!len) return '';
  var arr=new Uint32Array(len);
  crypto.getRandomValues(arr);
  var s='';
  for(var i=0;i<len;i++) s+=chars[arr[i]%chars.length];
  return s;
}

function calcStrength(pw,chars){
  var entropy=pw.length*(Math.log(chars.length)/Math.log(2));
  return entropy;
}

function strengthLabel(e){
  if(e<40) return {label:'ضعيفة جداً',color:'#ef4444',pct:15};
  if(e<60) return {label:'ضعيفة',color:'#f97316',pct:35};
  if(e<75) return {label:'مقبولة',color:'#eab308',pct:55};
  if(e<90) return {label:'قوية',color:'#22c55e',pct:75};
  return {label:'قوية جداً',color:'#7c3aed',pct:100};
}

function generate(){
  var len=parseInt(document.getElementById('len-range').value);
  var chars=getCharset();
  if(!chars){
    document.getElementById('pw-out').textContent='اختر نوع الأحرف على الأقل';
    return;
  }
  var pw=genOne(len,chars);
  document.getElementById('pw-out').textContent=pw;
  var e=calcStrength(pw,chars);
  var s=strengthLabel(e);
  document.getElementById('strength-label').textContent='القوة: '+s.label;
  document.getElementById('strength-label').style.color=s.color;
  document.getElementById('entropy-label').textContent=Math.round(e)+' بت';
  var bar=document.getElementById('strength-bar');
  bar.style.width=s.pct+'%';
  bar.style.background=s.color;
}

function copyPw(){
  var t=document.getElementById('pw-out').textContent;
  if(!t||t==='اضغط توليد'||t==='اختر نوع الأحرف على الأقل') return;
  navigator.clipboard.writeText(t).then(function(){
    var btn=document.querySelector('[onclick="copyPw()"]');
    if(btn) btn.style.background='#16a34a';
    setTimeout(function(){if(btn) btn.style.background='#7c3aed';},1200);
  });
}

function generateBulk(){
  var len=parseInt(document.getElementById('len-range').value);
  var chars=getCharset();
  var count=Math.min(50,Math.max(2,parseInt(document.getElementById('bulk-count').value)||10));
  if(!chars) return;
  bulkPasswords=[];
  for(var i=0;i<count;i++) bulkPasswords.push(genOne(len,chars));
  var container=document.getElementById('bulk-out');
  var inner=container.querySelector('div');
  inner.innerHTML='';
  bulkPasswords.forEach(function(pw,idx){
    var row=document.createElement('div');
    row.style.cssText='display:flex;align-items:center;justify-content:space-between;padding:8px 12px;border-bottom:1px solid #e2e8f0;gap:8px';
    row.innerHTML='<span style="font-family:monospace;font-size:13px;direction:ltr;flex:1;word-break:break-all">'+pw+'</span>'
      +'<button onclick="copySingle('+idx+')" style="background:#7c3aed;border:none;border-radius:6px;padding:4px 8px;cursor:pointer;color:#fff;font-size:11px;white-space:nowrap">نسخ</button>';
    inner.appendChild(row);
  });
  container.style.display='block';
}

function copySingle(idx){
  if(bulkPasswords[idx]) navigator.clipboard.writeText(bulkPasswords[idx]);
}

function copyAllPw(){
  if(!bulkPasswords.length) return;
  navigator.clipboard.writeText(bulkPasswords.join('\n'));
}

// Generate one on load
generate();
</script>
<?php tool_footer(); ?>
