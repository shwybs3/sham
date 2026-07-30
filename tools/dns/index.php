<?php
require_once dirname(__DIR__) . '/_base.php';

if (isset($_GET['action']) && $_GET['action'] === 'lookup') {
    header('Content-Type: application/json; charset=utf-8');
    $domain = trim($_GET['domain'] ?? '');
    $type   = strtoupper(trim($_GET['type'] ?? 'A'));
    $allowed_types = ['A','AAAA','MX','NS','TXT','CNAME','SOA','PTR','CAA'];
    if (!$domain || !in_array($type, $allowed_types)) {
        echo json_encode(['error' => 'طلب غير صالح']);
        exit;
    }
    $domain = preg_replace('/[^a-zA-Z0-9.\-]/', '', $domain);
    if (!$domain) { echo json_encode(['error' => 'اسم النطاق غير صالح']); exit; }
    $url = 'https://dns.google/resolve?name=' . urlencode($domain) . '&type=' . urlencode($type);
    $ctx = stream_context_create(['http'=>['timeout'=>8,'user_agent'=>'Mozilla/5.0']]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>1, CURLOPT_TIMEOUT=>8, CURLOPT_USERAGENT=>'Mozilla/5.0']);
        $raw = curl_exec($ch); curl_close($ch);
    }
    if (!$raw) { echo json_encode(['error' => 'فشل الاتصال بخادم DNS']); exit; }
    echo $raw;
    exit;
}

$schema = json_encode([
  '@context'=>'https://schema.org','@type'=>'WebApplication',
  'name'=>'بحث DNS سجلات النطاق','url'=>TOOLS_BASE_URL.'/tools/dns/',
  'description'=>'ابحث في سجلات DNS لأي نطاق مجاناً — A وAAAA وMX وNS وTXT وCNAME وSOA فوراً',
  'applicationCategory'=>'DeveloperApplication','operatingSystem'=>'All',
  'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],'inLanguage'=>'ar'
]);
tool_head('بحث DNS — سجلات النطاق A MX NS TXT فوري | yassota','ابحث في سجلات DNS لأي نطاق مجاناً. عرض سجلات A وAAAA وMX وNS وTXT وCNAME وSOA مع TTL فوري وبدون تسجيل.',$schema,'#92400e');
tool_header();
?>
<main class="t-main">
<div class="t-hero">
  <div class="t-hero-icon" style="background:#fef3c7">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#92400e" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
  </div>
  <h1>بحث سجلات DNS</h1>
  <p>استعلم عن أي سجل DNS لأي نطاق بثوانٍ</p>
</div>

<div class="t-card">
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <input type="text" id="dns-domain" class="t-input" placeholder="example.com" style="flex:1;min-width:200px;direction:ltr;text-align:left" onkeydown="if(event.key==='Enter')lookupDns()">
    <select id="dns-type" class="t-input" style="width:auto">
      <?php foreach(['A','AAAA','MX','NS','TXT','CNAME','SOA','PTR','CAA'] as $t): ?>
      <option value="<?= $t ?>"><?= $t ?></option>
      <?php endforeach; ?>
    </select>
    <button class="t-btn" onclick="lookupDns()">بحث</button>
  </div>
  <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">
    <span style="font-size:12px;color:#92400e;font-weight:600">بحث سريع:</span>
    <?php foreach(['A','MX','NS','TXT','ALL'] as $q): ?>
    <button onclick="quickLookup('<?= $q ?>')" style="font-size:12px;padding:4px 10px;border:1px solid #92400e;background:transparent;color:#92400e;border-radius:6px;cursor:pointer"><?= $q ?></button>
    <?php endforeach; ?>
  </div>
  <div id="dns-loading" style="display:none;text-align:center;padding:20px;color:#92400e">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
    <div style="margin-top:8px">جارٍ البحث...</div>
  </div>
  <div id="dns-result" style="margin-top:16px;display:none"></div>
  <div id="dns-error" class="t-error" style="display:none"></div>
</div>

<?php tool_ad(); ?>

<div class="t-card" style="line-height:1.9">
  <h2 class="t-article-h2">ما هو DNS وكيف يعمل؟</h2>
  <p>DNS اختصار لـ Domain Name System — نظام أسماء النطاقات. يُشبّهه الخبراء بـ"دليل الهاتف للإنترنت": فبدلاً من حفظ عناوين IP الرقمية لكل موقع (مثل 142.250.185.78 لموقع Google)، نكتب اسماً سهل التذكر كـ google.com ويتولى DNS ترجمته إلى العنوان الرقمي الذي يفهمه الحاسوب.</p>
  <p>عملية حل اسم النطاق (DNS Resolution) تمر بعدة خطوات خفية تحدث في أجزاء من الثانية: يبحث الحاسوب أولاً في ذاكرة التخزين المؤقت المحلية (Local Cache)، ثم يسأل خادم DNS المُحدد في إعدادات الشبكة (عادةً خادم DNS مزود الإنترنت أو خادم عام كـ 8.8.8.8 من Google)، فيتوجه بدوره إلى خوادم DNS الجذرية (Root Nameservers) التي تُحيله لخادم DNS المسؤول عن المستوى الأعلى (.com، .net)، ثم لخادم DNS المسؤول عن النطاق المحدد (Authoritative Nameserver)، الذي يُعيد أخيراً العنوان المطلوب.</p>
  <p>كل هذا يحدث عادةً في أقل من 50 ميلي ثانية — وهذا هو السبب في أنك لا تلاحظه. لكن عند مشاكل DNS، كل شيء يتعطل: لا يمكنك فتح أي موقع حتى لو كان الإنترنت يعمل بشكل طبيعي.</p>

  <h2 class="t-article-h2">أنواع سجلات DNS وما وظيفة كل منها</h2>
  <p><strong>سجل A (Address Record):</strong> يربط اسم النطاق بعنوان IPv4 رقمي (مثل 93.184.216.34). هذا أساس كل موقع ويب — بدونه لا يمكن الوصول للخادم. يمكن لنطاق واحد أن يمتلك عدة سجلات A للتوزيع بين خوادم متعددة (Load Balancing).</p>
  <p><strong>سجل AAAA (IPv6):</strong> نسخة IPv6 من سجل A. يحتوي على عنوان IPv6 المكوّن من 128 بت مكتوباً بالتنسيق السداسي عشري. مع نفاد عناوين IPv4 واتجاه الإنترنت نحو IPv6، أصبح هذا السجل ضرورياً لكل موقع حديث.</p>
  <p><strong>سجل MX (Mail Exchange):</strong> يُحدد خوادم البريد الإلكتروني المسؤولة عن استقبال رسائل النطاق. يحتوي على أولوية (Priority) — كلما انخفض الرقم كان الخادم أولوية أعلى. نطاق يستخدم Google Workspace سيحتوي على سجلات MX تشير لخوادم Google.</p>
  <p><strong>سجل NS (Name Server):</strong> يُحدد خوادم الأسماء الموثوقة (Authoritative Nameservers) لهذا النطاق. هؤلاء الخوادم يملكون السجل الرسمي لجميع سجلات DNS للنطاق. تغيير NS يعني نقل إدارة DNS لمزود آخر.</p>
  <p><strong>سجل TXT (Text):</strong> يُخزّن نصاً حراً. يُستخدم لأغراض متعددة: التحقق من ملكية النطاق (Google Search Console, domain verification)، إعدادات SPF لمنع انتحال البريد، DMARC وDKIM لمصادقة البريد، وإعلانات سياسات الأمان.</p>
  <p><strong>سجل CNAME (Canonical Name):</strong> يُنشئ اسماً بديلاً لاسم آخر. مثلاً www.example.com → example.com. يُستخدم لتوجيه النطاقات الفرعية لنطاق رئيسي دون تكرار جميع السجلات. ملاحظة: CNAME لا يمكن استخدامه على النطاق الجذري (root domain).</p>
  <p><strong>سجل SOA (Start of Authority):</strong> معلومات تقنية عن منطقة DNS. يحتوي على: الخادم الرئيسي، بريد مدير النطاق، رقم تسلسلي للإصدار، ومواعيد التحديث. الخوادم الثانوية تستخدم هذه المعلومات لمزامنة سجلاتها.</p>
  <p><strong>سجل CAA (Certification Authority Authorization):</strong> يُحدد جهات إصدار شهادات SSL/TLS المُصرَّح لها بإصدار شهادات لهذا النطاق. إجراء أمني يمنع إصدار شهادات غير مُخوَّلة من جهات تصديق مختلفة.</p>

  <h2 class="t-article-h2">كيفية استخدام أداة بحث DNS</h2>
  <p>أدخل اسم النطاق المراد الاستعلام عنه (مثلاً: google.com أو mail.example.com) ثم اختر نوع السجل المطلوب من القائمة. انقر "بحث" أو اضغط Enter. ستظهر النتائج فوراً من خادم Google DNS العام (8.8.8.8). يمكنك أيضاً استخدام أزرار البحث السريع للاستعلام عن سجلات متعددة بسرعة.</p>
  <p>استخدم زر "ALL" للبحث عن أشهر أنواع السجلات دفعة واحدة وهو مفيد لفحص إعداد نطاق جديد.</p>

  <h2 class="t-article-h2">التشخيص الشائع بسجلات DNS</h2>
  <p><strong>تحقق من إعداد البريد الإلكتروني:</strong> ابحث عن سجل MX للتأكد من أن البريد مُوجَّه للخادم الصحيح. إذا لم يصل البريد، تحقق من وجود سجل SPF في TXT ومن صحة سجلات MX.</p>
  <p><strong>تحقق من نقل النطاق:</strong> بعد نقل الاستضافة، ابحث عن سجلات A للتأكد من أنها تشير للخادم الجديد. قد يستغرق انتشار التغييرات من دقائق لـ48 ساعة بحسب TTL.</p>
  <p><strong>التحقق من شهادة SSL:</strong> سجل CAA يُخبرك بجهات التصديق المُصرَّح لها بإصدار شهادات لهذا النطاق. مفيد لتشخيص فشل إصدار Let's Encrypt أو CloudFlare.</p>

  <h2 class="t-article-h2">أسئلة شائعة حول DNS</h2>
  <div class="t-faq-item">
    <div class="t-faq-q">ما هو TTL في سجلات DNS ولماذا يهمني؟</div>
    <div class="t-faq-a">TTL اختصار لـ Time To Live وهو المدة الزمنية (بالثواني) التي يحتفظ فيها خادم DNS بالسجل في ذاكرته المؤقتة قبل إعادة الاستعلام. TTL = 300 يعني إعادة الاستعلام كل 5 دقائق. TTL = 86400 = يوم كامل. قبل تغيير سجلات DNS، اخفض TTL لـ300 ثانية قبل يوم. هذا يُقلص وقت انتشار التغيير عند تنفيذه.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">لماذا تختلف نتائج DNS من بلد لآخر؟</div>
    <div class="t-faq-a">Geolocation DNS وGeoDNS تُعيد عناوين مختلفة بحسب موقع المُستعلِم. تُستخدم من قِبَل CDN الكبرى (Cloudflare, AWS CloudFront, Akamai) لتوجيه المستخدم لأقرب خادم جغرافياً. لذا استعلام DNS من السعودية قد يُعيد IP خادم في دبي، بينما نفس الاستعلام من أمريكا يُعيد IP خادم في فيرجينيا.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">ما هو DNS over HTTPS وهل يُحسن الخصوصية؟</div>
    <div class="t-faq-a">DNS التقليدي يُرسل الاستعلامات بشكل غير مشفر (UDP Port 53)، أي أن مزود الإنترنت أو أي وسيط يمكنه معرفة المواقع التي تزورها. DNS over HTTPS يُشفّر الاستعلامات ضمن اتصال HTTPS عادي (Port 443)، مما يمنع التجسس على استعلاماتك. Firefox وChrome وWindows 11 يدعمونه. يُحسّن الخصوصية لكنه لا يُخفي وجهتك عن ISP بالكامل (يعرف عنوان IP الوجهة رغم عدم معرفة اسم النطاق).</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">ما الفرق بين DNS العام مثل 8.8.8.8 و1.1.1.1 ودي إن إس المزود؟</div>
    <div class="t-faq-a">خوادم DNS المزود عادةً أسرع للمستخدمين القريبين منها لكنها قد تُراقب استعلاماتك وتُوجّه بعضها. 8.8.8.8 (Google) و1.1.1.1 (Cloudflare) أبطأ قليلاً في بعض المناطق لكنها لا تُفيد من بيانات استعلاماتك للإعلانات وتتميز بسياسات خصوصية أفضل. 9.9.9.9 (Quad9) يُضيف حماية إضافية بحجب النطاقات الضارة.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">كيف أُنشئ سجل SPF لنطاقي؟</div>
    <div class="t-faq-a">SPF يُضاف كسجل TXT. مثال للنطاق الذي يُرسل بريداً فقط عبر Google Workspace: v=spf1 include:_spf.google.com ~all. وعبر SendGrid: v=spf1 include:sendgrid.net ~all. يمكن الجمع: v=spf1 include:_spf.google.com include:sendgrid.net ~all. الـ~all تعني "soft fail" (أقل صرامة من -all).</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">ما هو DNSSEC ولماذا يهم؟</div>
    <div class="t-faq-a">DNSSEC هو امتداد أمني يُضيف توقيعات رقمية لسجلات DNS للتحقق من صحتها. يمنع هجمات DNS Spoofing/Poisoning حيث يُقوم مهاجم بتلويث ذاكرة خادم DNS لتوجيه زوار موقعك لخادم مزيف. تفعيل DNSSEC يتطلب دعماً من كل من مسجّل النطاق ومزود استضافة DNS.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">كم يستغرق تحديث سجلات DNS؟</div>
    <div class="t-faq-a">يعتمد على TTL السجل السابق. إذا كان TTL = 86400 (يوم)، قد تستغرق التغييرات يوماً كاملاً للانتشار. إذا خفّضته لـ300 ثانية قبل التغيير، يبدأ الانتشار في دقائق. المصطلح "DNS Propagation" يعني وقت وصول التغيير لكل خوادم DNS حول العالم.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">ما الفرق بين النطاق الجذري والنطاق الفرعي؟</div>
    <div class="t-faq-a">example.com هو النطاق الجذري (root/apex domain). www.example.com وmail.example.com وapi.example.com هي نطاقات فرعية (subdomains). النطاق الجذري لا يمكن إسناده لـ CNAME (محظور تقنياً) لذا يُستخدم سجل A أو ALIAS (تقنية خاصة ببعض مزودي DNS). النطاقات الفرعية تقبل CNAME بحرية.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">كيف أتحقق من DMARC لنطاقي؟</div>
    <div class="t-faq-a">ابحث عن سجل TXT للنطاق الفرعي _dmarc.example.com (استبدل example.com بنطاقك). سجل DMARC صالح يبدأ بـ: v=DMARC1; p=... حيث p=none (مراقبة فقط)، p=quarantine (الرسائل المشبوهة للبريد العشوائي)، p=reject (رفض الرسائل المشبوهة كلياً). إضافة DMARC يُقلل احتمال استخدام نطاقك في انتحال الهوية (Email Spoofing).</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">لماذا يفشل الاتصال بموقع ما رغم عمل الإنترنت؟</div>
    <div class="t-faq-a">أسباب محتملة: خادم DNS محجوب أو معطل (جرّب تغيير DNS لـ8.8.8.8)، سجل DNS منتهي الصلاحية أو خاطئ (ابحث هنا عن سجلات A)، انتهاء صلاحية النطاق (تحقق في WHOIS)، حجب الموقع على مستوى DNS من مزود الإنترنت (استخدم VPN)، أو مشكلة في ملف hosts المحلي (C:\Windows\System32\drivers\etc\hosts في Windows).</div>
  </div>

  <h2 class="t-article-h2">الخلاصة</h2>
  <p>سجلات DNS هي الهيكل العظمي للإنترنت — غير مرئية للمستخدم العادي لكنها الأساس الذي تقوم عليه كل عملية اتصال على الشبكة. فهمها يُمكّنك من تشخيص مشاكل الاتصال، إعداد البريد الإلكتروني، نقل الاستضافة بثقة، وتطبيق معايير الأمان. استخدم هذه الأداة لاستعلام فوري عن أي سجل DNS لأي نطاق، مدعومة بخوادم Google DNS العامة الموثوقة.</p>
</div>
</main>

<style>@keyframes spin{to{transform:rotate(360deg)}}</style>
<script>
async function lookupDns(){
  const domain=document.getElementById('dns-domain').value.trim();
  const type=document.getElementById('dns-type').value;
  if(!domain)return;
  document.getElementById('dns-loading').style.display='block';
  document.getElementById('dns-result').style.display='none';
  document.getElementById('dns-error').style.display='none';
  try{
    const res=await fetch('?action=lookup&domain='+encodeURIComponent(domain)+'&type='+encodeURIComponent(type));
    const data=await res.json();
    document.getElementById('dns-loading').style.display='none';
    if(data.error){
      document.getElementById('dns-error').style.display='block';
      document.getElementById('dns-error').textContent=data.error;
      return;
    }
    renderResult(data,domain,type);
  }catch(e){
    document.getElementById('dns-loading').style.display='none';
    document.getElementById('dns-error').style.display='block';
    document.getElementById('dns-error').textContent='حدث خطأ في الاتصال: '+e.message;
  }
}
async function quickLookup(type){
  const domain=document.getElementById('dns-domain').value.trim();
  if(!domain){alert('أدخل اسم النطاق أولاً');return;}
  if(type==='ALL'){
    document.getElementById('dns-loading').style.display='block';
    document.getElementById('dns-result').style.display='none';
    document.getElementById('dns-error').style.display='none';
    const types=['A','MX','NS','TXT','CNAME'];
    const results=await Promise.all(types.map(t=>fetch('?action=lookup&domain='+encodeURIComponent(domain)+'&type='+t).then(r=>r.json()).catch(()=>null)));
    document.getElementById('dns-loading').style.display='none';
    let html='<h3 style="font-weight:800;margin-bottom:12px">نتائج شاملة لـ '+h(domain)+'</h3>';
    types.forEach((t,i)=>{
      const d=results[i];
      if(d&&d.Answer&&d.Answer.length>0){
        html+=`<div style="margin-bottom:12px"><strong style="color:#92400e">${t}</strong>`;
        html+='<table style="width:100%;border-collapse:collapse;font-size:13px;margin-top:4px">';
        d.Answer.forEach(rec=>{html+=`<tr><td style="padding:4px 8px;border:1px solid #e5e7eb;font-family:monospace;direction:ltr;text-align:left">${h(rec.data)}</td><td style="padding:4px 8px;border:1px solid #e5e7eb;color:#6b7280;width:80px">TTL: ${rec.TTL}s</td></tr>`;});
        html+='</table></div>';
      }
    });
    document.getElementById('dns-result').innerHTML=html;
    document.getElementById('dns-result').style.display='block';
    return;
  }
  document.getElementById('dns-type').value=type;
  lookupDns();
}
function renderResult(data,domain,type){
  let html=`<div style="margin-bottom:10px;font-size:13px;color:#6b7280">${h(domain)} — سجلات ${h(type)}</div>`;
  if(!data.Answer||data.Answer.length===0){
    html+='<div style="padding:16px;background:#fef3c7;border-radius:8px;color:#92400e">لا توجد سجلات '+type+' لهذا النطاق</div>';
  }else{
    html+='<div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse;font-size:14px">';
    html+='<thead><tr style="background:var(--tool-light)"><th style="padding:8px;border:1px solid var(--tool-border);text-align:right">اسم</th><th style="padding:8px;border:1px solid var(--tool-border)">النوع</th><th style="padding:8px;border:1px solid var(--tool-border)">البيانات</th><th style="padding:8px;border:1px solid var(--tool-border)">TTL</th></tr></thead><tbody>';
    data.Answer.forEach(rec=>{
      html+=`<tr><td style="padding:8px;border:1px solid #e5e7eb;font-family:monospace;direction:ltr;text-align:left;font-size:12px">${h(rec.name)}</td>
        <td style="padding:8px;border:1px solid #e5e7eb;text-align:center"><span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:4px;font-weight:700;font-size:12px">${getTypeName(rec.type)}</span></td>
        <td style="padding:8px;border:1px solid #e5e7eb;font-family:monospace;direction:ltr;text-align:left;word-break:break-all">${h(rec.data)}</td>
        <td style="padding:8px;border:1px solid #e5e7eb;text-align:center;color:#6b7280;font-size:12px">${rec.TTL}s</td></tr>`;
    });
    html+='</tbody></table></div>';
  }
  if(data.Authority&&data.Authority.length>0){
    html+=`<div style="margin-top:12px;font-size:13px;color:#6b7280">خوادم NS المرجعية: ${data.Authority.map(a=>h(a.data)).join(' | ')}</div>`;
  }
  document.getElementById('dns-result').innerHTML=html;
  document.getElementById('dns-result').style.display='block';
}
const DNS_TYPES={1:'A',2:'NS',5:'CNAME',6:'SOA',12:'PTR',15:'MX',16:'TXT',28:'AAAA',257:'CAA'};
function getTypeName(n){return DNS_TYPES[n]||'TYPE'+n;}
function h(s){const d=document.createElement('div');d.textContent=s;return d.innerHTML;}
</script>
<?php tool_footer(); ?>
