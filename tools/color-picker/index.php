<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
  '@context'=>'https://schema.org','@type'=>'WebApplication',
  'name'=>'أداة اختيار الألوان — Color Picker','url'=>TOOLS_BASE_URL.'/tools/color-picker/',
  'description'=>'اختر أي لون وشاهد قيمته بصيغ HEX وRGB وHSL وCMYK مع لوحة ألوان متناسقة ومتدرجة',
  'applicationCategory'=>'DesignApplication','operatingSystem'=>'All',
  'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('أداة اختيار الألوان — HEX RGB HSL CMYK | yassota','اختر لوناً من أداة اختيار احترافية واحصل على قيمه بصيغ HEX وRGB وHSL وCMYK فوراً. ألوان تناسقية وتدرج وسجل الألوان المستخدمة.',$schema,'#0d9488');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#ccfbf1">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0d9488" stroke-width="2"><circle cx="13.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="10.5" r="2.5"/><circle cx="8.5" cy="7.5" r="2.5"/><circle cx="6.5" cy="12.5" r="2.5"/><path d="M12 22a7 7 0 010-14" stroke-width="2"/></svg>
    </div>
    <h1>أداة اختيار الألوان</h1>
    <p>انقر على أي نقطة في لوحة الألوان للحصول على قيمه بجميع الصيغ — HEX وRGB وHSL وCMYK — مع لوحات الانسجام اللوني.</p>
  </div>

  <div class="t-card">
    <div class="t-row" style="gap:20px">
      <div style="flex:1 1 280px;min-width:0">
        <!-- SV Picker Canvas -->
        <div style="position:relative;margin-bottom:10px">
          <canvas id="sv-canvas" width="300" height="200" style="border-radius:10px;cursor:crosshair;width:100%;display:block;touch-action:none"></canvas>
          <div id="sv-cursor" style="position:absolute;width:16px;height:16px;border:2px solid #fff;border-radius:50%;box-shadow:0 0 0 1.5px rgba(0,0,0,.4);transform:translate(-50%,-50%);pointer-events:none;top:10%;left:90%"></div>
        </div>
        <!-- Hue Slider -->
        <div style="position:relative;margin-bottom:10px">
          <canvas id="hue-canvas" height="20" style="border-radius:6px;cursor:pointer;width:100%;display:block;touch-action:none"></canvas>
          <div id="hue-cursor" style="position:absolute;top:0;width:4px;height:100%;background:#fff;border-radius:2px;box-shadow:0 0 0 1.5px rgba(0,0,0,.4);transform:translateX(-50%);pointer-events:none;left:0%"></div>
        </div>
        <!-- Alpha Slider -->
        <div style="position:relative;margin-bottom:14px">
          <canvas id="alpha-canvas" height="20" style="border-radius:6px;cursor:pointer;width:100%;display:block;touch-action:none"></canvas>
          <div id="alpha-cursor" style="position:absolute;top:0;width:4px;height:100%;background:#fff;border-radius:2px;box-shadow:0 0 0 1.5px rgba(0,0,0,.4);transform:translateX(-50%);pointer-events:none;left:100%"></div>
        </div>
        <!-- Color Preview -->
        <div style="display:flex;gap:10px;align-items:center">
          <div id="color-preview" style="width:56px;height:56px;border-radius:12px;border:2px solid rgba(0,0,0,.1);background:#0d9488;flex-shrink:0"></div>
          <div style="flex:1">
            <input type="color" id="color-native" value="#0d9488" onchange="fromHex(this.value)" style="width:100%;height:36px;border:1.5px solid #cbd5e1;border-radius:8px;cursor:pointer;padding:2px 4px">
            <p style="font-size:11px;color:#94a3b8;margin-top:4px">أو اختر من منتقي النظام</p>
          </div>
        </div>
      </div>

      <div style="flex:1 1 220px;min-width:0">
        <!-- Color Values -->
        <div style="display:flex;flex-direction:column;gap:8px">
          <?php foreach([
            ['HEX','hex-val','#0d9488'],
            ['RGB','rgb-val','rgb(13, 148, 136)'],
            ['HSL','hsl-val','hsl(177, 84%, 32%)'],
            ['CMYK','cmyk-val','C:91 M:0 Y:8 K:42'],
          ] as [$lbl,$id,$def]): ?>
          <div style="display:flex;align-items:center;gap:8px;background:#f8fafc;border-radius:8px;padding:8px 10px;border:1px solid #e2e8f0">
            <span style="font-size:11px;font-weight:700;color:#0d9488;min-width:40px"><?= $lbl ?></span>
            <span id="<?= $id ?>" style="flex:1;font-family:monospace;font-size:13px;direction:ltr;word-break:break-all"><?= $def ?></span>
            <button onclick="copyVal('<?= $id ?>')" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:2px">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
            </button>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Color Harmonies -->
        <div style="margin-top:14px">
          <p style="font-size:12px;font-weight:700;color:#64748b;margin-bottom:8px">الانسجام اللوني</p>
          <div class="chip-group" style="margin-bottom:8px">
            <button class="chip active" onclick="setHarmony('complementary',this)">متكاملة</button>
            <button class="chip" onclick="setHarmony('triadic',this)">ثلاثية</button>
            <button class="chip" onclick="setHarmony('analogous',this)">متجاورة</button>
          </div>
          <div id="harmony-swatches" style="display:flex;gap:6px;flex-wrap:wrap"></div>
        </div>

        <!-- Gradient Preview -->
        <div style="margin-top:14px">
          <p style="font-size:12px;font-weight:700;color:#64748b;margin-bottom:6px">تدرج لوني</p>
          <div id="gradient-bar" style="height:32px;border-radius:8px;background:linear-gradient(to right,#0d9488,#ffffff)"></div>
          <p id="gradient-code" style="font-size:11px;font-family:monospace;color:#64748b;margin-top:4px;direction:ltr;word-break:break-all"></p>
        </div>

        <!-- Color History -->
        <div style="margin-top:14px">
          <p style="font-size:12px;font-weight:700;color:#64748b;margin-bottom:6px">سجل الألوان (آخر 10)</p>
          <div id="history-swatches" style="display:flex;gap:6px;flex-wrap:wrap"></div>
        </div>
      </div>
    </div>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card" style="line-height:1.9">
    <h2 style="font-size:22px;font-weight:800;color:#134e4a;margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid #99f6e4">ما هي أداة اختيار الألوان؟ — شرح كامل</h2>

    <p style="color:#374151;margin-bottom:16px">أداة اختيار الألوان (Color Picker) هي واجهة تفاعلية تُتيح للمصممين والمطورين اختيار ألوان بدقة وتحويل قيمها بين الصيغ المختلفة. كل لون يمكن التعبير عنه بطرق متعددة رياضياً تامة وصحيحة في نفس الوقت: HEX للويب، RGB للشاشات، HSL للتلاعب البرمجي، CMYK للطباعة. أداتنا توفر الأربعة دفعة واحدة، مع لوحة اختيار بصرية للفضاء اللوني كاملاً، وشريط تدرج الصبغة (Hue)، ومعاينة التناسق اللوني الذي يُساعد في بناء لوحات ألوان متوازنة.</p>

    <p style="color:#374151;margin-bottom:16px">الأداة تعمل بالكامل عبر Canvas API في المتصفح. لوحة اختيار التشبع/القيمة (SV Picker) تعرض بُعدَين مهمَّين في مساحة لون HSV: محور X يتحكم في التشبع (Saturation) من الأبيض الخالص يساراً إلى اللون المشبع يميناً، ومحور Y يتحكم في القيمة (Value/Brightness) من اللون الكامل السطوع في الأعلى إلى الأسود الخالص في الأسفل. هذا التمثيل يجعل اختيار الألوان بديهياً وحدسياً، وهو نفس المنطق الذي تستخدمه برامج التصميم الاحترافية كـ Photoshop وFigma وIllustrator.</p>

    <h2 style="font-size:20px;font-weight:800;color:#134e4a;margin:28px 0 16px;padding-bottom:10px;border-bottom:2px solid #99f6e4">كيفية استخدام الأداة خطوة بخطوة</h2>

    <div style="background:#f0fdfa;border-radius:12px;padding:16px;margin-bottom:20px;border:1px solid #99f6e4">
      <ol style="color:#374151;font-size:14px;padding-right:20px;line-height:2.2">
        <li><strong>اختر الصبغة (Hue)</strong> من شريط الألوان الأفقي أسفل اللوحة الرئيسية — هذا يحدد اللون الأساسي (أحمر، أخضر، أزرق، أصفر، إلخ)</li>
        <li><strong>اضبط التشبع والسطوع</strong> بالنقر أو السحب داخل اللوحة الرئيسية. سحب يميناً يزيد التشبع؛ سحب للأعلى يزيد السطوع</li>
        <li><strong>اضبط الشفافية (Alpha)</strong> إذا أردت لوناً شبه شفاف من الشريط السفلي</li>
        <li><strong>انسخ القيمة</strong> بالصيغة التي تريدها (HEX للويب، RGB للـ CSS، HSL للتحريك، CMYK للطباعة)</li>
        <li><strong>استكشف الانسجام اللوني</strong> من الأزرار أدناه لبناء لوحة ألوان متوازنة لمشروعك</li>
      </ol>
    </div>

    <h2 style="font-size:20px;font-weight:800;color:#134e4a;margin:28px 0 16px;padding-bottom:10px;border-bottom:2px solid #99f6e4">صيغ الألوان وأين تُستخدم كل منها</h2>

    <p style="color:#374151;margin-bottom:16px"><strong style="color:#134e4a">صيغة HEX (#RRGGBB):</strong> الصيغة الأكثر شيوعاً في تطوير الويب. تُعبّر عن اللون برمز سداسي عشري مكوّن من 6 أرقام (أحياناً 8 مع الشفافية في CSS4). مثلاً #FF5733 يعني أحمر بقيمة 255، أخضر بقيمة 87، أزرق بقيمة 51. استخدمها في CSS، HTML inline styles، ومتغيرات Tailwind. قصيرة وسهلة النسخ ولصق.</p>

    <p style="color:#374151;margin-bottom:16px"><strong style="color:#134e4a">صيغة RGB (R, G, B):</strong> تُعبّر عن اللون بثلاث قيم عددية من 0 إلى 255 لقنوات الأحمر (Red) والأخضر (Green) والأزرق (Blue). هي الطريقة الأساسية التي تتعامل بها الشاشات مع الألوان، إذ تخلط ثلاثة مصادر ضوئية لينتج اللون المطلوب. في CSS تكتبها كـ rgb(255, 87, 51). صيغة rgba() تضيف قناة شفافية (Alpha) من 0 إلى 1.</p>

    <p style="color:#374151;margin-bottom:16px"><strong style="color:#134e4a">صيغة HSL (H, S%, L%):</strong> تُعبّر عن اللون بالصبغة (Hue من 0 إلى 360 درجة)، والتشبع (Saturation من 0% إلى 100%)، والإضاءة (Lightness من 0% إلى 100%). هذه الصيغة أقرب لطريقة فهم الإنسان للألوان: "أريد الأزرق مشبعاً 80% وإضاءة 50%". تُفضَّل في CSS للأنيميشن وحسابات الألوان الديناميكية لأن تعديل Hue وحده يُنتج ألوان مختلفة من نفس العائلة.</p>

    <p style="color:#374151;margin-bottom:16px"><strong style="color:#134e4a">صيغة CMYK (C%, M%, Y%, K%):</strong> تُستخدم حصراً في الطباعة. تُعبّر عن اللون بنسب الأحبار الأربعة: السماوي (Cyan)، الأرجواني (Magenta)، الأصفر (Yellow)، والأسود (Key/Black). الشاشات تخلط الضوء (RGB) لذا تجمع الألوان لتُنتج الأبيض؛ أما الطباعة تخلط الأحبار التي تمتص الضوء فتجمعها لتُنتج الأسود. إذا كنت تُعدّ تصميماً للطباعة الاحترافية، لا بد من التحويل للـ CMYK.</p>

    <h2 style="font-size:20px;font-weight:800;color:#134e4a;margin:28px 0 16px;padding-bottom:10px;border-bottom:2px solid #99f6e4">الانسجام اللوني — كيف تبني لوحة ألوان متوازنة</h2>

    <p style="color:#374151;margin-bottom:16px">الانسجام اللوني (Color Harmony) مبدأ في نظرية الألوان يُحدد تركيبات الألوان التي تبدو متناسقة ومريحة بصرياً. أداتنا تحسب ثلاثة أنواع تلقائياً:</p>

    <p style="color:#374151;margin-bottom:16px"><strong style="color:#134e4a">الألوان المتكاملة (Complementary):</strong> لونان في موضعَين متقابلَين تماماً على دائرة الألوان (زاوية 180°). يُحدثان تبايناً قوياً وجاذباً بصرياً، مثال: الأحمر والأخضر، الأزرق والبرتقالي. مثالي للتصميمات التي تريد لفت الانتباه وإبراز الدعوة للتصرف (Call to Action).</p>

    <p style="color:#374151;margin-bottom:16px"><strong style="color:#134e4a">الألوان الثلاثية (Triadic):</strong> ثلاثة ألوان موزعة بالتساوي على دائرة الألوان (زوايا 120° بين كل منها). تُوفر تنوعاً لونياً مع توازن بصري جيد. تُستخدم في التصميمات الإبداعية التي تريد حيوية ودفئاً دون الوقوع في التشتت البصري.</p>

    <p style="color:#374151;margin-bottom:16px"><strong style="color:#134e4a">الألوان المتجاورة (Analogous):</strong> ثلاثة ألوان متقاربة على دائرة الألوان (±30° من اللون الأصلي). تُعطي شعوراً بالهدوء والتماسك والانسجام الطبيعي. تُفضَّل في التصميمات الاحترافية الهادئة مثل الواجهات الرسمية والمواقع المؤسسية.</p>

    <h2 style="font-size:20px;font-weight:800;color:#134e4a;margin:28px 0 16px;padding-bottom:10px;border-bottom:2px solid #99f6e4">أسئلة شائعة</h2>

    <div style="display:flex;flex-direction:column;gap:16px">
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#134e4a;cursor:pointer;font-size:14px">كيف أُدخل لوناً محدداً بدلاً من اختياره من اللوحة؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">انقر على حقل اللون الأصلي (Native Color Picker) واكتب قيمة HEX مباشرة، أو انقر على مربع اللون الصغير واختر بدقة. كذلك يمكنك النقر على أي قيمة HEX / RGB / HSL في الجدول وتعديلها يدوياً. الأداة ستحدث كل الصيغ الأخرى تلقائياً.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#134e4a;cursor:pointer;font-size:14px">لماذا تختلف الألوان بين الشاشة والطباعة؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">الشاشات تنبعث منها ضوء RGB (إضافي: الألوان تجمع لتُنتج الأبيض)، بينما الطباعة تعتمد على امتصاص الضوء CMYK (طرحي: الأحبار تجمع لتُنتج الأسود). بعض الألوان الزاهية على الشاشة مثل الأخضر الفلوري أو الأزرق الكهربائي لا يمكن إعادة إنتاجها بدقة بالأحبار. لذا تصاميم الطباعة دائماً تُراجع في "Color Proof" قبل الطباعة النهائية.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#134e4a;cursor:pointer;font-size:14px">ما الفرق بين HSL وHSV؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">كلاهما يُعبّر عن الألوان بالصبغة والتشبع لكن البُعد الثالث مختلف: HSL يستخدم الإضاءة (Lightness) حيث 50% هو اللون الكامل؛ HSV يستخدم القيمة/السطوع (Value/Brightness) حيث 100% هو اللون الكامل. HSL أكثر بديهية للمصممين لأن تعديل الإضاءة يُعطي نتائج متوقعة. لوحة الاختيار البصرية تستخدم HSV داخلياً لأنها أسهل في التمثيل البصري ثم تحولها.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#134e4a;cursor:pointer;font-size:14px">ما معنى قيمة Alpha في الألوان؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">Alpha هي قناة الشفافية. قيمة 1 (أو 255 في RGB) تعني اللون معتماً كلياً؛ قيمة 0 تعني شفافاً تاماً؛ والقيم البينية تُنتج مستويات من الشفافية. في CSS تستخدمها كـ rgba(13, 148, 136, 0.5) للحصول على لون بنصف شفافية. مفيد لطبقات التراكب (Overlays)، والتوهجات (Glows)، وخلفيات النوافذ المنبثقة.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#134e4a;cursor:pointer;font-size:14px">كيف أختار ألوان متناسبة لموقع ويب احترافي؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">ابدأ بلون رئيسي واحد يعكس هوية علامتك التجارية. ثم استخدم نظام الانسجام: الألوان المتجاورة للتصاميم الهادئة، المتكاملة لعناصر الدعوة للتصرف. أضف درجات فاتحة وغامقة من نفس الصبغة للنصوص والخلفيات. حدد لوناً محايداً (رمادي بدرجة خفيفة من اللون الرئيسي) للخلفيات. مجموعة 5-6 ألوان تكفي لأي واجهة: رئيسي، ثانوي، تأكيد، نجاح، تحذير، محايد.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#134e4a;cursor:pointer;font-size:14px">كيف أتحقق من إمكانية الوصول لتباين الألوان؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">معيار WCAG (Web Content Accessibility Guidelines) يشترط نسبة تباين بين النص وخلفيته لا تقل عن 4.5:1 للنص العادي و3:1 للنص الكبير. يمكنك التحقق من ذلك بحساب نسبة التباين باستخدام صيغة Relative Luminance. أدوات مثل WebAIM Contrast Checker تحسبها تلقائياً إذا أدخلت القيمتَين. أداتنا ستضيف هذه الميزة قريباً.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#134e4a;cursor:pointer;font-size:14px">ما هي مساحات الألوان الأخرى التي يجب معرفتها؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">بجانب RGB وHSL وCMYK، ثمة مساحات ألوان متخصصة: LAB (يصف الألوان كما يراها العين البشرية وهو متوازن إدراكياً)، OKLCH (نسخة محسّنة من LAB شائعة في CSS Level 4)، P3 (مساحة ألوان أوسع من sRGB تدعمها شاشات iPhone الحديثة). CSS Level 4 الحديث يدعم هذه المساحات مما يفتح إمكانيات ألوان أكثر ثراءً للويب الحديث.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#134e4a;cursor:pointer;font-size:14px">هل يمكنني استخدام الأداة على الجوال بسهولة؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">نعم، الأداة تدعم اللمس (Touch Events) بالكامل. يمكنك سحب اللوحة والأشرطة بإصبعك على الجوال بنفس سهولة الماوس على الكمبيوتر. التصميم المتجاوب يُعدّل التخطيط ليناسب شاشة الهاتف. اختيار اللون الأصلي النظام يُتيح لك فتح منتقي ألوان نظام iOS أو Android مع خاصية قطّارة اللون لالتقاط أي لون من الشاشة.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#134e4a;cursor:pointer;font-size:14px">كيف أحفظ الألوان التي أعجبتني؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">الأداة تحفظ تلقائياً آخر 10 ألوان اخترتها في سجل الألوان (Color History) ضمن نفس الجلسة. يمكنك النقر على أي لون من السجل لاسترجاعه. لحفظ الألوان بشكل دائم، انسخ قيمة HEX وألصقها في ملف تصميم أو أداة إدارة لوحات الألوان مثل Coolors أو Adobe Color.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#134e4a;cursor:pointer;font-size:14px">ما هو قطّارة الألوان (Color Dropper) وهل تدعمه الأداة؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">قطّارة الألوان أداة تسمح بالنقر على أي نقطة في الشاشة لمعرفة لونها. المتصفحات الحديثة تدعمها عبر EyeDropper API (Chrome 95+ وEdge 95+). منتقي الألوان الأصلي للنظام (Native Color Input) في iOS وAndroid يتضمن قطّارة مدمجة. أداتنا تدعم EyeDropper API تلقائياً في المتصفحات التي توفره عبر حقل اختيار النظام.</p>
      </details>
    </div>

    <h2 style="font-size:20px;font-weight:800;color:#134e4a;margin:28px 0 16px;padding-bottom:10px;border-bottom:2px solid #99f6e4">الخلاصة</h2>
    <p style="color:#374151;margin-bottom:16px">الألوان لغة بصرية لها قواعدها وقوانينها. فهم صيغ الألوان المختلفة ومعرفة متى تستخدم كل منها يُعطيك سيطرة كاملة على الهوية البصرية لمشاريعك سواء أكانت مواقع ويب أم تطبيقات أم تصاميم مطبوعة. أداة اختيار الألوان تُجسّد هذا الفهم في واجهة تفاعلية مباشرة — انقر، تجربة، انسخ القيمة. مع ميزات الانسجام اللوني وسجل الألوان والتدرجات، تُصبح عملية بناء لوحات ألوان متوازنة ومتناسقة أمراً ممتعاً وسريعاً. ابدأ باختيار لون رئيسي يُعبّر عن هويتك واترك الأداة تبني معك البقية.</p>
  </div>
</main>

<script>
var H=177,S=0.84,V=0.58,A=1;
var harmony='complementary';
var colorHistory=[];
var svDragging=false,hueDragging=false,alphaDragging=false;
var svCanvas=document.getElementById('sv-canvas');
var hueCanvas=document.getElementById('hue-canvas');
var alphaCanvas=document.getElementById('alpha-canvas');
var svCtx=svCanvas.getContext('2d');
var hueCtx=hueCanvas.getContext('2d');
var alphaCtx=alphaCanvas.getContext('2d');

function hsvToRgb(h,s,v){
  var r,g,b;
  var i=Math.floor(h/60)%6;
  var f=(h/60)-Math.floor(h/60);
  var p=v*(1-s),q=v*(1-f*s),t=v*(1-(1-f)*s);
  if(i===0){r=v;g=t;b=p;}
  else if(i===1){r=q;g=v;b=p;}
  else if(i===2){r=p;g=v;b=t;}
  else if(i===3){r=p;g=q;b=v;}
  else if(i===4){r=t;g=p;b=v;}
  else{r=v;g=p;b=q;}
  return [Math.round(r*255),Math.round(g*255),Math.round(b*255)];
}

function rgbToHsl(r,g,b){
  r/=255;g/=255;b/=255;
  var max=Math.max(r,g,b),min=Math.min(r,g,b);
  var h=0,s,l=(max+min)/2;
  if(max!==min){
    var d=max-min;
    s=l>0.5?d/(2-max-min):d/(max+min);
    if(max===r) h=((g-b)/d+(g<b?6:0))/6;
    else if(max===g) h=((b-r)/d+2)/6;
    else h=((r-g)/d+4)/6;
  } else s=0;
  return [Math.round(h*360),Math.round(s*100),Math.round(l*100)];
}

function rgbToCmyk(r,g,b){
  r/=255;g/=255;b/=255;
  var k=1-Math.max(r,g,b);
  if(k===1) return [0,0,0,100];
  return [Math.round((1-r-k)/(1-k)*100),Math.round((1-g-k)/(1-k)*100),Math.round((1-b-k)/(1-k)*100),Math.round(k*100)];
}

function toHex(r,g,b){
  return '#'+(r<16?'0':'')+r.toString(16)+(g<16?'0':'')+g.toString(16)+(b<16?'0':'')+b.toString(16);
}

function drawSV(){
  var W=svCanvas.width,H2=svCanvas.height;
  var hueColor='hsl('+H+',100%,50%)';
  var gradH=svCtx.createLinearGradient(0,0,W,0);
  gradH.addColorStop(0,'#fff');
  gradH.addColorStop(1,hueColor);
  svCtx.fillStyle=gradH;
  svCtx.fillRect(0,0,W,H2);
  var gradV=svCtx.createLinearGradient(0,0,0,H2);
  gradV.addColorStop(0,'transparent');
  gradV.addColorStop(1,'#000');
  svCtx.fillStyle=gradV;
  svCtx.fillRect(0,0,W,H2);
  // cursor
  var cx=S*W,cy=(1-V)*H2;
  svCtx.beginPath();svCtx.arc(cx,cy,7,0,Math.PI*2);
  svCtx.strokeStyle='#fff';svCtx.lineWidth=2;svCtx.stroke();
  svCtx.beginPath();svCtx.arc(cx,cy,8,0,Math.PI*2);
  svCtx.strokeStyle='rgba(0,0,0,.3)';svCtx.lineWidth=1;svCtx.stroke();
}

function drawHue(){
  var W=hueCanvas.width,H2=hueCanvas.height;
  var g=hueCtx.createLinearGradient(0,0,W,0);
  for(var i=0;i<=360;i+=30) g.addColorStop(i/360,'hsl('+i+',100%,50%)');
  hueCtx.fillStyle=g;
  hueCtx.fillRect(0,0,W,H2);
}

function drawAlpha(){
  var W=alphaCanvas.width,H2=alphaCanvas.height;
  var rgb=hsvToRgb(H,S,V);
  // checker
  for(var x=0;x<W;x+=10){
    for(var y=0;y<H2;y+=10){
      alphaCtx.fillStyle=((Math.floor(x/10)+Math.floor(y/10))%2===0)?'#ccc':'#fff';
      alphaCtx.fillRect(x,y,10,10);
    }
  }
  var g=alphaCtx.createLinearGradient(0,0,W,0);
  g.addColorStop(0,'rgba('+rgb[0]+','+rgb[1]+','+rgb[2]+',0)');
  g.addColorStop(1,'rgba('+rgb[0]+','+rgb[1]+','+rgb[2]+',1)');
  alphaCtx.fillStyle=g;
  alphaCtx.fillRect(0,0,W,H2);
}

function updateAll(){
  drawSV();drawHue();drawAlpha();
  var rgb=hsvToRgb(H,S,V);
  var r=rgb[0],g=rgb[1],b=rgb[2];
  var hex=toHex(r,g,b);
  var hsl=rgbToHsl(r,g,b);
  var cmyk=rgbToCmyk(r,g,b);
  document.getElementById('hex-val').textContent=hex+(A<1?(Math.round(A*255)<16?'0':'')+Math.round(A*255).toString(16):'');
  document.getElementById('rgb-val').textContent=(A<1?'rgba':'rgb')+'('+r+', '+g+', '+b+(A<1?', '+A.toFixed(2):'')+')';
  document.getElementById('hsl-val').textContent='hsl('+hsl[0]+', '+hsl[1]+'%, '+hsl[2]+'%)';
  document.getElementById('cmyk-val').textContent='C:'+cmyk[0]+' M:'+cmyk[1]+' Y:'+cmyk[2]+' K:'+cmyk[3];
  document.getElementById('color-preview').style.background='rgba('+r+','+g+','+b+','+A+')';
  document.getElementById('color-native').value=hex;
  // hue cursor
  document.getElementById('hue-cursor').style.left=(H/360*100)+'%';
  // alpha cursor
  document.getElementById('alpha-cursor').style.left=(A*100)+'%';
  updateHarmony();
  updateGradient(r,g,b);
}

function updateHarmony(){
  var swatches=[];
  if(harmony==='complementary') swatches=[(H+180)%360];
  else if(harmony==='triadic') swatches=[(H+120)%360,(H+240)%360];
  else swatches=[(H+30)%360,(H-30+360)%360,(H+60)%360];
  var c=document.getElementById('harmony-swatches');
  c.innerHTML='<div style="width:36px;height:36px;border-radius:8px;border:2px solid rgba(0,0,0,.1);background:hsl('+H+','+Math.round(S*100)+'%,'+Math.round((0.5+V*0.25)*100*0.6)+'%);cursor:pointer" onclick="applyHarmony('+H+')" title="الأصلي"></div>';
  swatches.forEach(function(h){
    c.innerHTML+='<div style="width:36px;height:36px;border-radius:8px;border:2px solid rgba(0,0,0,.1);background:hsl('+h+','+Math.round(S*100)+'%,50%);cursor:pointer" onclick="applyHarmony('+h+')" title="'+h+'°"></div>';
  });
}

function applyHarmony(h){
  H=h;updateAll();addHistory();
}

function updateGradient(r,g,b){
  var grad='linear-gradient(to right, rgb('+r+','+g+','+b+'), #ffffff)';
  document.getElementById('gradient-bar').style.background=grad;
  document.getElementById('gradient-code').textContent='background: '+grad+';';
}

function setHarmony(h,btn){
  harmony=h;
  document.querySelectorAll('.chip').forEach(function(c){c.classList.remove('active');});
  btn.classList.add('active');
  updateHarmony();
}

function addHistory(){
  var rgb=hsvToRgb(H,S,V);
  var hex=toHex(rgb[0],rgb[1],rgb[2]);
  colorHistory=colorHistory.filter(function(c){return c!==hex;});
  colorHistory.unshift(hex);
  if(colorHistory.length>10) colorHistory.pop();
  var c=document.getElementById('history-swatches');
  c.innerHTML='';
  colorHistory.forEach(function(h){
    c.innerHTML+='<div style="width:28px;height:28px;border-radius:6px;border:1.5px solid rgba(0,0,0,.1);background:'+h+';cursor:pointer" onclick="fromHex(\''+h+'\')" title="'+h+'"></div>';
  });
}

function fromHex(hex){
  var r=parseInt(hex.slice(1,3),16),g=parseInt(hex.slice(3,5),16),b=parseInt(hex.slice(5,7),16);
  // Convert RGB to HSV
  r/=255;g/=255;b/=255;
  var max=Math.max(r,g,b),min=Math.min(r,g,b),d=max-min;
  V=max;S=max===0?0:d/max;
  if(d===0) H=0;
  else if(max===r) H=((g-b)/d*60+360)%360;
  else if(max===g) H=(b-r)/d*60+120;
  else H=(r-g)/d*60+240;
  updateAll();addHistory();
}

function copyVal(id){
  var v=document.getElementById(id).textContent;
  navigator.clipboard.writeText(v);
}

function getSVPos(e,canvas){
  var r=canvas.getBoundingClientRect();
  var x=((e.clientX||e.touches[0].clientX)-r.left)/r.width;
  var y=((e.clientY||e.touches[0].clientY)-r.top)/r.height;
  return {x:Math.max(0,Math.min(1,x)),y:Math.max(0,Math.min(1,y))};
}

svCanvas.addEventListener('mousedown',function(e){svDragging=true;var p=getSVPos(e,svCanvas);S=p.x;V=1-p.y;updateAll();});
svCanvas.addEventListener('mousemove',function(e){if(!svDragging)return;var p=getSVPos(e,svCanvas);S=p.x;V=1-p.y;updateAll();});
document.addEventListener('mouseup',function(){if(svDragging){svDragging=false;addHistory();}});
svCanvas.addEventListener('touchstart',function(e){e.preventDefault();svDragging=true;var p=getSVPos(e,svCanvas);S=p.x;V=1-p.y;updateAll();},{passive:false});
svCanvas.addEventListener('touchmove',function(e){e.preventDefault();if(!svDragging)return;var p=getSVPos(e,svCanvas);S=p.x;V=1-p.y;updateAll();},{passive:false});
svCanvas.addEventListener('touchend',function(){if(svDragging){svDragging=false;addHistory();}});

hueCanvas.addEventListener('mousedown',function(e){hueDragging=true;var r=hueCanvas.getBoundingClientRect();H=((e.clientX-r.left)/r.width)*360;H=Math.max(0,Math.min(359,H));updateAll();});
hueCanvas.addEventListener('mousemove',function(e){if(!hueDragging)return;var r=hueCanvas.getBoundingClientRect();H=((e.clientX-r.left)/r.width)*360;H=Math.max(0,Math.min(359,H));updateAll();});
document.addEventListener('mouseup',function(){if(hueDragging){hueDragging=false;addHistory();}});
hueCanvas.addEventListener('touchstart',function(e){e.preventDefault();hueDragging=true;var r=hueCanvas.getBoundingClientRect();H=((e.touches[0].clientX-r.left)/r.width)*360;H=Math.max(0,Math.min(359,H));updateAll();},{passive:false});
hueCanvas.addEventListener('touchmove',function(e){e.preventDefault();if(!hueDragging)return;var r=hueCanvas.getBoundingClientRect();H=((e.touches[0].clientX-r.left)/r.width)*360;H=Math.max(0,Math.min(359,H));updateAll();},{passive:false});
hueCanvas.addEventListener('touchend',function(){if(hueDragging){hueDragging=false;addHistory();}});

alphaCanvas.addEventListener('mousedown',function(e){alphaDragging=true;var r=alphaCanvas.getBoundingClientRect();A=Math.max(0,Math.min(1,(e.clientX-r.left)/r.width));updateAll();});
alphaCanvas.addEventListener('mousemove',function(e){if(!alphaDragging)return;var r=alphaCanvas.getBoundingClientRect();A=Math.max(0,Math.min(1,(e.clientX-r.left)/r.width));updateAll();});
document.addEventListener('mouseup',function(){alphaDragging=false;});

// Init canvas sizes
function resizeCanvases(){
  var pw=svCanvas.parentElement.offsetWidth;
  svCanvas.width=Math.max(200,pw);
  hueCanvas.width=svCanvas.width;
  alphaCanvas.width=svCanvas.width;
  hueCanvas.height=20;
  alphaCanvas.height=20;
  drawSV();drawHue();drawAlpha();
}
resizeCanvases();
updateAll();
addHistory();
window.addEventListener('resize',function(){resizeCanvases();updateAll();});
</script>
<?php tool_footer(); ?>
