<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
  '@context'=>'https://schema.org','@type'=>'WebApplication',
  'name'=>'محوّل النص إلى ASCII وASCII Art','url'=>TOOLS_BASE_URL.'/tools/ascii/',
  'description'=>'حوّل النص إلى رموز ASCII أو فن ASCII — يدعم العربية والإنجليزية مجاناً',
  'applicationCategory'=>'DeveloperApplication','operatingSystem'=>'All',
  'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('تحويل النص إلى ASCII وASCII Art | yassota','حوّل أي نص إلى رموز ASCII Unicode مع أكوادها العشرية والسداسية والثنائية، أو أنشئ ASCII Art بخط بيكسلي — مجاناً',$schema,'#0891b2');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#e0f2fe">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0891b2" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/><line x1="12" y1="2" x2="12" y2="22" stroke-dasharray="4 2"/></svg>
    </div>
    <h1>محوّل النص إلى ASCII وASCII Art</h1>
    <p>أداة مزدوجة: اعرض الكود الرقمي لكل حرف في نصك، أو حوّل النص إلى لوحة ASCII Art بيكسلية.</p>
  </div>

  <div class="t-card">
    <div class="chip-group" style="margin-bottom:16px">
      <button class="chip active" id="tab-codes" onclick="switchTab('codes',this)">جدول رموز ASCII</button>
      <button class="chip" id="tab-art" onclick="switchTab('art',this)">ASCII Art</button>
    </div>
    <label class="t-label">النص المدخل</label>
    <textarea id="txt-input" class="t-textarea" placeholder="أدخل نصك هنا... مثال: Hello أو مرحبا" rows="3" oninput="process()"></textarea>
    <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap">
      <button class="t-btn" onclick="process()" style="background:#0891b2">تحويل</button>
      <button class="t-btn t-btn-secondary" onclick="copyResult()">نسخ الناتج</button>
      <button class="t-btn t-btn-secondary" onclick="clearAll()">مسح</button>
    </div>
  </div>

  <div id="panel-codes" class="t-card">
    <h2>جدول رموز الأحرف</h2>
    <div style="overflow-x:auto">
      <table id="codes-table" style="width:100%;border-collapse:collapse;font-size:13px">
        <thead>
          <tr style="background:#f0f9ff;border-bottom:2px solid #bae6fd">
            <th style="padding:8px 12px;text-align:right;color:#0369a1;font-weight:700">الحرف</th>
            <th style="padding:8px 12px;text-align:right;color:#0369a1;font-weight:700">عشري (Dec)</th>
            <th style="padding:8px 12px;text-align:right;color:#0369a1;font-weight:700">سداسي (Hex)</th>
            <th style="padding:8px 12px;text-align:right;color:#0369a1;font-weight:700">ثنائي (Bin)</th>
            <th style="padding:8px 12px;text-align:right;color:#0369a1;font-weight:700">HTML Entity</th>
          </tr>
        </thead>
        <tbody id="codes-tbody">
          <tr><td colspan="5" style="padding:20px;text-align:center;color:#94a3b8">أدخل نصاً للبدء</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div id="panel-art" class="t-card" style="display:none">
    <h2>ASCII Art</h2>
    <label style="font-size:13px;display:flex;align-items:center;gap:6px;margin-bottom:12px">
      رمز التعبئة:
      <select id="art-char" class="t-input" style="width:80px;padding:6px 10px" onchange="process()">
        <option value="#">#</option>
        <option value="*">*</option>
        <option value="@">@</option>
        <option value="X">X</option>
      </select>
    </label>
    <pre id="art-out" style="font-family:'Courier New',monospace;font-size:11px;line-height:1.3;background:#0c1e36;color:#38bdf8;padding:16px;border-radius:10px;overflow-x:auto;white-space:pre;min-height:80px;direction:ltr">أدخل نصاً إنجليزياً أو أرقاماً للبدء</pre>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card" style="line-height:1.9">
    <h2 style="font-size:22px;font-weight:800;color:#0c4a6e;margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid #bae6fd">ما هو ASCII وكيف يعمل؟ — شرح كامل</h2>

    <p style="color:#374151;margin-bottom:16px">ASCII اختصار لـ American Standard Code for Information Interchange، وهو نظام ترميز الأحرف الأكثر انتشاراً في تاريخ الحوسبة. نشأ عام 1963 حين كانت الحواسيب في بداياتها الأولى، وكانت الحاجة ملحّة لمعيار موحّد يسمح للأجهزة المختلفة بتبادل النصوص بشكل صحيح. قبل ASCII، كان لكل شركة ترميزها الخاص، مما جعل التواصل بين الأنظمة المختلفة كابوساً تقنياً. وضع ASCII حلاً بسيطاً وأنيقاً: ربط كل رمز مكتوب برقم عشري من 0 إلى 127، مما أتاح للحواسيب المختلفة تبادل النصوص بشكل موحد ومفهوم لجميع الأطراف.</p>

    <p style="color:#374151;margin-bottom:16px">يتضمن جدول ASCII القياسي 128 حرفاً ورمزاً: 33 منها رموز تحكم غير مرئية مثل السطر الجديد (Line Feed رقم 10) وإرجاع المؤشر (Carriage Return رقم 13) ومفتاح الجرس (Bell رقم 7)، و95 منها رموز مرئية قابلة للطباعة تشمل الأرقام من 0 إلى 9، والحروف اللاتينية الكبيرة والصغيرة، وأحرف الترقيم والرموز الخاصة. مثلاً: الحرف A يحمل الرقم 65، والحرف a الصغير هو 97، والمسافة هي 32، وعلامة التعجب ! هي 33. هذا الترتيب المنطقي جعل ASCII معياراً عملياً للغاية.</p>

    <p style="color:#374151;margin-bottom:16px">مع انتشار الحواسيب حول العالم وظهور الحاجة لدعم اللغات غير اللاتينية كالعربية والصينية والعبرية واليابانية، اتضح قصور ASCII الذي يدعم 128 رمزاً فقط. ظهرت امتدادات متعددة كـ ISO-8859 و Windows-1252، لكنها بقيت محدودة ومتعارضة لأن كل نظام اختار رموزاً مختلفة للأرقام 128-255. الحل الجذري والنهائي جاء مع معيار <strong>Unicode</strong> الذي يدعم أكثر من 140,000 رمز من جميع لغات العالم، ويُرمَّز عبر الإنترنت بترميز UTF-8 المتوافق تماماً مع ASCII للرموز الـ128 الأصلية.</p>

    <h2 style="font-size:20px;font-weight:800;color:#0c4a6e;margin:28px 0 16px;padding-bottom:10px;border-bottom:2px solid #bae6fd">كيفية استخدام الأداة خطوة بخطوة</h2>

    <p style="color:#374151;margin-bottom:14px">تقدم الأداة وضعين مختلفين، كلٌّ منهما يخدم غرضاً مختلفاً:</p>

    <div style="background:#f0f9ff;border-radius:12px;padding:16px;margin-bottom:20px">
      <p style="font-weight:700;color:#0369a1;margin-bottom:10px">الوضع الأول — جدول رموز ASCII:</p>
      <ol style="color:#374151;font-size:14px;padding-right:20px;line-height:2.2">
        <li>انقر على تبويب "جدول رموز ASCII" من أعلى الأداة</li>
        <li>اكتب أو الصق أي نص في حقل الإدخال — يدعم العربية والإنجليزية وأي لغة أخرى</li>
        <li>سيظهر الجدول فورياً أثناء الكتابة مع قيمة كل حرف بثلاثة أنظمة أرقام</li>
        <li>انقر "نسخ الناتج" لنسخ الجدول بتنسيق نصي مناسب للصقه في أي مكان</li>
      </ol>
      <p style="font-weight:700;color:#0369a1;margin-top:14px;margin-bottom:10px">الوضع الثاني — ASCII Art:</p>
      <ol style="color:#374151;font-size:14px;padding-right:20px;line-height:2.2">
        <li>انقر على تبويب "ASCII Art"</li>
        <li>اكتب نصاً باللغة الإنجليزية أو أرقاماً (هذا الوضع يدعم الحروف اللاتينية والأرقام)</li>
        <li>اختر رمز التعبئة المناسب لتغيير مظهر الفن (#، *، @، X)</li>
        <li>انسخ النتيجة واستخدمها في README أو تعليقات الكود أو التطبيقات النصية</li>
      </ol>
    </div>

    <h2 style="font-size:20px;font-weight:800;color:#0c4a6e;margin:28px 0 16px;padding-bottom:10px;border-bottom:2px solid #bae6fd">استخدامات ASCII في حياتك اليومية والمهنية</h2>

    <p style="color:#374151;margin-bottom:16px"><strong style="color:#0c4a6e">في البرمجة وتطوير البرمجيات:</strong> يتعامل كل مبرمج يومياً مع رموز ASCII سواء أدرك ذلك أم لا. حين تتحقق من صحة حقل إدخال وتريد التأكد أن المستخدم كتب أحرفاً إنجليزية فقط، تقارن الكود بنطاق 65-90 للحروف الكبيرة و97-122 للصغيرة. وحين تعالج ملفات CSV أو بيانات نصية خاماً من API خارجي، يساعدك فهم ASCII في تشخيص المشاكل وإصلاحها بسرعة.</p>

    <p style="color:#374151;margin-bottom:16px"><strong style="color:#0c4a6e">في تصحيح أخطاء التشفير:</strong> حين تجد نصاً يظهر هراء أو رموزاً غريبة بدل أحرف عادية، فإن عرض قيم Unicode لكل حرف يكشف المشكلة فوراً. ربما يوجد حرف يبدو كمسافة عادية لكنه في الواقع رمز تحكم Unicode غير مرئي مثل U+200B (Zero Width Space)، أو أن الترميز المستخدم مختلف عما يتوقعه البرنامج. أداة جدول الرموز تمنحك الشفافية الكاملة على تركيب النص.</p>

    <p style="color:#374151;margin-bottom:16px"><strong style="color:#0c4a6e">في ASCII Art والتصميم الإبداعي:</strong> ASCII Art فن رقمي أصيل له تاريخ يعود لأيام الطرفيات النصية السبعينيات. يُستخدم اليوم في: شعارات ملفات README على GitHub، شاشات الترحيب في تطبيقات سطر الأوامر، تعليقات الكود الكبيرة لتمييز الأقسام، ورسائل التليجرام والديسكورد في الغرف التقنية.</p>

    <p style="color:#374151;margin-bottom:16px"><strong style="color:#0c4a6e">في HTML Entities وتطوير الويب:</strong> بعض الرموز الخاصة في HTML كعلامتي الأكبر والأصغر والعطف يجب تشفيرها بـ HTML Entities لتجنب تفسيرها كعلامات HTML. عرض Entity لكل حرف يُسهّل عمل مطوري الويب ومحرري المحتوى الذين يتعاملون مع نصوص تحتوي رموزاً خاصة.</p>

    <h2 style="font-size:20px;font-weight:800;color:#0c4a6e;margin:28px 0 16px;padding-bottom:10px;border-bottom:2px solid #bae6fd">الفرق بين ASCII وUnicode وUTF-8</h2>

    <p style="color:#374151;margin-bottom:16px">يخلط كثيرون بين هذه المصطلحات الثلاثة المترابطة. إليك شرحاً مبسطاً دقيقاً: <strong>ASCII</strong> معيار ترميز أصلي يربط 128 رمزاً بأرقام من 0 إلى 127. <strong>Unicode</strong> معيار أشمل يحدد رقماً فريداً لكل حرف في جميع لغات العالم، أكثر من 140,000 رمز حتى الآن وما زال يتوسع. <strong>UTF-8</strong> نظام ترميز (طريقة تمثيل بايتات) يخزن أحرف Unicode في 1 إلى 4 بايتات لكل حرف بطريقة ذكية متوافقة مع ASCII.</p>

    <p style="color:#374151;margin-bottom:16px">العلاقة بينها: ASCII هو مجموعة جزئية من Unicode — الأرقام 0-127 في Unicode هي نفسها أرقام ASCII. وUTF-8 يُخزّن الأحرف الـ128 الأولى (ASCII) في بايت واحد فقط مما يجعله متوافقاً مع ملفات ASCII القديمة. أما الأحرف العربية فتحتاج في UTF-8 إلى 2 بايت لأن قيم Unicode لها تتراوح بين 1536 و1791. هذا يُفسّر لماذا يبدو ملف نصي عربي هراء حين يُفتح ببرنامج يفترض أنه ASCII.</p>

    <h2 style="font-size:20px;font-weight:800;color:#0c4a6e;margin:28px 0 16px;padding-bottom:10px;border-bottom:2px solid #bae6fd">أسئلة شائعة</h2>

    <div style="display:flex;flex-direction:column;gap:16px">
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#0c4a6e;cursor:pointer;font-size:14px">هل يدعم الأداة اللغة العربية في جدول الرموز؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">نعم، يدعم الأداة أي حرف يمكن كتابته بما فيها الحروف العربية والفارسية وغيرها. للأحرف العربية، تعرض الأداة قيمة Unicode (code point) لأن ASCII كمعيار أصلي لا يشمل إلا الأحرف اللاتينية. الحروف العربية الأساسية تقع في نطاق Unicode من U+0600 إلى U+06FF، وهو ما تعرضه الأداة بدقة.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#0c4a6e;cursor:pointer;font-size:14px">لماذا ASCII Art لا يدعم الكتابة العربية؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">ASCII Art يعتمد على فونت بيكسلي مُعرَّف مسبقاً لكل حرف. الحروف العربية أكثر تعقيداً لأنها متصلة وتتغير أشكالها حسب موضعها في الكلمة (بداية، وسط، نهاية، منفردة) — أي أن كل حرف عربي قد يكون له 4 أشكال مختلفة. يمكن نظرياً إنشاء فونت ASCII Art عربي لكنه يتطلب جهداً ضخماً لتعريف كل شكل. المرحلة القادمة من الأداة تسعى لدعم ذلك.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#0c4a6e;cursor:pointer;font-size:14px">ما الفرق بين الرقم العشري والسداسي عشر والثنائي؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">ثلاثة طرق للتعبير عن نفس القيمة بأنظمة عد مختلفة. العشري (Base 10) هو نظامنا اليومي بأرقام 0-9. السداسي عشر (Hexadecimal, Base 16) يستخدم 0-9 وA-F، وهو شائع جداً في البرمجة وترميز الألوان. الثنائي (Binary, Base 2) يستخدم 0 و1 فقط وهو اللغة الأصلية للحاسوب. مثال: الحرف A = عشري 65 = سداسي 0x41 = ثنائي 01000001.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#0c4a6e;cursor:pointer;font-size:14px">ما هي HTML Entities ومتى أحتاجها؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">HTML Entities تمثيل نصي بديل للرموز التي لها معنى خاص في HTML أو التي لا تظهر بسهولة في المحرر. مثلاً عوضاً عن كتابة علامة الأقل من مباشرة في محتوى HTML، تكتب &amp;lt; وتُفسَّر كرمز حرفي وليس كبداية وسم HTML. تحتاجها في: مقتطفات الكود، العبارات الرياضية، المحتوى الذي يحتوي رموزاً متعارضة مع تركيب HTML.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#0c4a6e;cursor:pointer;font-size:14px">ما قيمة ASCII لحروف الأرقام وكيف تختلف عن الأرقام الفعلية؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">قيمة الحرف '0' في ASCII هي 48 وليس صفراً، و'1' هي 49، وهكذا حتى '9' التي قيمتها 57. الأرقام 48-57 مخصصة للحروف الرقمية. هذا يعني أن رمز الرقم يختلف عن قيمة الرقم الفعلية بمقدار ثابت هو 48. عند معالجة ملف نصي يحتوي أرقاماً وتريد تحويل حرف رقمي إلى رقم حقيقي، اطرح 48 من قيمة ASCII. هذا شائع في معالجة السلاسل النصية على المستوى الخام بلغات C وRust.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#0c4a6e;cursor:pointer;font-size:14px">أين يمكنني استخدام ASCII Art الذي أنشأته؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">يمكن استخدام ASCII Art في مواضع كثيرة: ملفات README على GitHub وGitLab لإضافة شعار نصي للمشروع، تعليقات كود البرامج للفصل بين الأقسام الكبيرة، شاشات الترحيب في تطبيقات سطر الأوامر CLI، توقيعات البريد الإلكتروني النصية، ورسائل غرف الدردشة التي تدعم الخطوط المتساوية العرض مثل Slack وDiscord وTelegram.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#0c4a6e;cursor:pointer;font-size:14px">ما معنى رموز التحكم في ASCII وما فائدتها؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">رموز التحكم هي الأرقام من 0 إلى 31 في ASCII، وهي لا تُمثّل أحرفاً مرئية بل إشارات تحكم للأجهزة والبرامج. مثلاً: رقم 10 هو السطر الجديد LF وهو ما ينتج حين تضغط Enter في Unix/Linux، رقم 13 هو إرجاع المؤشر CR وهو جزء من نهاية السطر في Windows (CR+LF)، رقم 9 هو مفتاح Tab، رقم 27 هو Escape. هذه الرموز ما زالت تُستخدم في بروتوكولات الاتصال الشبكي ومعالجة الملفات النصية.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#0c4a6e;cursor:pointer;font-size:14px">كيف يُستخدم ASCII في التشفير والأمن الرقمي؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">ASCII أساسي في كثير من بروتوكولات الأمن: HTTP وSMTP وFTP كلها بروتوكولات نصية تعتمد على ASCII. تشفير Base64 يحوّل البيانات الثنائية إلى 64 رمزاً ASCII آمناً للنقل عبر القنوات النصية. كلمات المرور تُحوَّل لبايتات ASCII ثم تُعالج بدوال Hash. الشهادات الرقمية SSL/TLS تُشفَّر وتُنقل بصيغة PEM وهي ترميز Base64 للبيانات الثنائية.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#0c4a6e;cursor:pointer;font-size:14px">هل يدعم الأداة الإيموجي والرموز الخاصة؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">نعم، الأداة تعرض قيمة Unicode لأي رمز بما فيه الإيموجي. الإيموجي تقع في نطاقات Unicode عالية جداً، مثلاً 😀 (Grinning Face) قيمتها U+1F600. ملاحظة مهمة: بعض الإيموجي مركبة من عدة code points متسلسلة (Emoji Sequences) لذا ستظهر في الجدول كعدة صفوف منفصلة وهو سلوك صحيح يعكس تركيبها الداخلي الفعلي.</p>
      </details>
      <details style="border:1.5px solid #e2e8f0;border-radius:10px;padding:14px">
        <summary style="font-weight:700;color:#0c4a6e;cursor:pointer;font-size:14px">ما الفرق بين ASCII العادي وExtended ASCII؟</summary>
        <p style="color:#374151;margin-top:10px;font-size:14px">ASCII القياسي يشمل 128 رمزاً فقط (أرقام 0-127). Extended ASCII يمتد إلى 256 رمزاً ويضيف 128 رمزاً إضافياً للحروف الأوروبية والرسوميات الخطية. المشكلة أن Extended ASCII ليس معياراً موحداً — لكل نظام تشغيل صفحة ترميز مختلفة مما أدى لتعارضات كثيرة. Unicode جاء ليحل هذه الفوضى بمعيار موحد يشمل جميع لغات العالم دون أي تعارض.</p>
      </details>
    </div>

    <h2 style="font-size:20px;font-weight:800;color:#0c4a6e;margin:28px 0 16px;padding-bottom:10px;border-bottom:2px solid #bae6fd">الخلاصة</h2>
    <p style="color:#374151;margin-bottom:16px">ASCII هو الأساس الذي بُنيت عليه الحوسبة الحديثة والإنترنت كما نعرفه اليوم. فهم تمثيل الأحرف رقمياً يفتح أبواباً واسعة للمطورين في تشخيص مشاكل الترميز، وتطوير خوارزميات معالجة النصوص، وبناء أنظمة اتصال موثوقة. أداة ASCII وأداة ASCII Art معاً توفران نافذة مزدوجة: واحدة للتحليل التقني الدقيق لتركيب النصوص، وأخرى للإبداع والتعبير الفني في عالم الأحرف النصية. سواء كنت مطوراً يبحث عن كود حرف معين، أو مبدعاً يصمم شعاراً نصياً، أو طالباً يتعلم أساسيات الحوسبة، فهذه الأداة تخدمك بكفاءة ودقة.</p>
  </div>
</main>

<script>
var currentTab='codes';

var FONT={
  'A':['01110','10001','11111','10001','10001'],
  'B':['11110','10001','11110','10001','11110'],
  'C':['01111','10000','10000','10000','01111'],
  'D':['11110','10001','10001','10001','11110'],
  'E':['11111','10000','11110','10000','11111'],
  'F':['11111','10000','11110','10000','10000'],
  'G':['01111','10000','10011','10001','01110'],
  'H':['10001','10001','11111','10001','10001'],
  'I':['11111','00100','00100','00100','11111'],
  'J':['01111','00010','00010','10010','01100'],
  'K':['10001','10010','11100','10010','10001'],
  'L':['10000','10000','10000','10000','11111'],
  'M':['10001','11011','10101','10001','10001'],
  'N':['10001','11001','10101','10011','10001'],
  'O':['01110','10001','10001','10001','01110'],
  'P':['11110','10001','11110','10000','10000'],
  'Q':['01110','10001','10001','10011','01111'],
  'R':['11110','10001','11110','10010','10001'],
  'S':['01111','10000','01110','00001','11110'],
  'T':['11111','00100','00100','00100','00100'],
  'U':['10001','10001','10001','10001','01110'],
  'V':['10001','10001','10001','01010','00100'],
  'W':['10001','10001','10101','11011','10001'],
  'X':['10001','01010','00100','01010','10001'],
  'Y':['10001','01010','00100','00100','00100'],
  'Z':['11111','00010','00100','01000','11111'],
  '0':['01110','10011','10101','11001','01110'],
  '1':['00100','01100','00100','00100','01110'],
  '2':['01110','10001','00110','01000','11111'],
  '3':['11110','00001','01110','00001','11110'],
  '4':['10001','10001','11111','00001','00001'],
  '5':['11111','10000','11110','00001','11110'],
  '6':['01110','10000','11110','10001','01110'],
  '7':['11111','00001','00010','00100','00100'],
  '8':['01110','10001','01110','10001','01110'],
  '9':['01110','10001','01111','00001','01110'],
  ' ':['00000','00000','00000','00000','00000'],
  '!':['00100','00100','00100','00000','00100'],
  '?':['01110','10001','00110','00000','00100'],
  '.':['00000','00000','00000','00000','00100'],
  '-':['00000','00000','11111','00000','00000'],
  '+':['00000','00100','01110','00100','00000']
};

function switchTab(tab,btn){
  currentTab=tab;
  document.querySelectorAll('.chip').forEach(function(c){c.classList.remove('active');});
  btn.classList.add('active');
  document.getElementById('panel-codes').style.display=tab==='codes'?'block':'none';
  document.getElementById('panel-art').style.display=tab==='art'?'block':'none';
  process();
}

function process(){
  if(currentTab==='codes') buildCodesTable();
  else buildArt();
}

function buildCodesTable(){
  var text=document.getElementById('txt-input').value;
  var tbody=document.getElementById('codes-tbody');
  if(!text){
    tbody.innerHTML='<tr><td colspan="5" style="padding:20px;text-align:center;color:#94a3b8">أدخل نصاً للبدء</td></tr>';
    return;
  }
  var rows='';
  var chars=Array.from(text);
  chars.forEach(function(ch){
    var cp=ch.codePointAt(0);
    var display;
    if(ch===' ') display='<span style="background:#e2e8f0;padding:0 6px;border-radius:3px;font-size:11px;font-family:monospace">SP</span>';
    else if(cp<32) display='<span style="background:#fde8e8;color:#991b1b;padding:0 4px;border-radius:3px;font-size:11px">CTL</span>';
    else display='<span style="font-family:monospace;font-size:15px">'+escH(ch)+'</span>';
    var hex='0x'+cp.toString(16).toUpperCase().padStart(cp>127?4:2,'0');
    var bin=cp.toString(2).padStart(cp>127?16:8,'0');
    var entity=cp<128?'&#'+cp+';':'U+'+cp.toString(16).toUpperCase().padStart(4,'0');
    rows+='<tr style="border-bottom:1px solid #f1f5f9">'
      +'<td style="padding:8px 12px">'+display+'</td>'
      +'<td style="padding:8px 12px;font-family:monospace;color:#0369a1;direction:ltr">'+cp+'</td>'
      +'<td style="padding:8px 12px;font-family:monospace;color:#7c3aed;direction:ltr">'+hex+'</td>'
      +'<td style="padding:8px 12px;font-family:monospace;color:#059669;font-size:11px;direction:ltr">'+bin+'</td>'
      +'<td style="padding:8px 12px;font-family:monospace;color:#dc2626;font-size:12px;direction:ltr">'+entity+'</td>'
      +'</tr>';
  });
  tbody.innerHTML=rows;
}

function buildArt(){
  var text=document.getElementById('txt-input').value.toUpperCase();
  var fill=document.getElementById('art-char').value;
  if(!text.trim()){
    document.getElementById('art-out').textContent='أدخل نصاً إنجليزياً أو أرقاماً للبدء';
    return;
  }
  var chars=Array.from(text);
  var rows=['','','','',''];
  chars.forEach(function(ch){
    var glyph=FONT[ch]||['00100','00100','00100','00000','00100'];
    for(var r=0;r<5;r++){
      rows[r]+=glyph[r].replace(/1/g,fill).replace(/0/g,' ')+' ';
    }
  });
  document.getElementById('art-out').textContent=rows.join('\n');
}

function escH(s){
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function copyResult(){
  var text='';
  if(currentTab==='codes'){
    var rows=document.querySelectorAll('#codes-tbody tr');
    rows.forEach(function(r){
      var cells=r.querySelectorAll('td');
      if(cells.length>=4) text+=cells[0].textContent.trim()+'\t'+cells[1].textContent+'\t'+cells[2].textContent+'\t'+cells[3].textContent+'\n';
    });
  } else {
    text=document.getElementById('art-out').textContent;
  }
  if(text) navigator.clipboard.writeText(text);
}

function clearAll(){
  document.getElementById('txt-input').value='';
  document.getElementById('codes-tbody').innerHTML='<tr><td colspan="5" style="padding:20px;text-align:center;color:#94a3b8">أدخل نصاً للبدء</td></tr>';
  document.getElementById('art-out').textContent='أدخل نصاً إنجليزياً أو أرقاماً للبدء';
}
</script>
<?php tool_footer(); ?>
