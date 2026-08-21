<?php
require_once dirname(__DIR__) . '/_base.php';

if (isset($_GET['action']) && $_GET['action'] === 'whois') {
    header('Content-Type: application/json; charset=utf-8');
    $domain = trim($_GET['domain'] ?? '');
    $domain = preg_replace('/[^a-zA-Z0-9.\-]/', '', $domain);
    if (!$domain || strlen($domain) > 253) {
        echo json_encode(['error' => 'اسم النطاق غير صالح']); exit;
    }
    $parts = explode('.', $domain);
    $tld   = strtolower(end($parts));
    $result = ['domain' => $domain, 'tld' => $tld];

    // Try RDAP first (JSON, modern standard)
    $rdap_url = 'https://rdap.org/domain/' . urlencode($domain);
    $ctx = stream_context_create(['http'=>['timeout'=>8,'user_agent'=>'Mozilla/5.0','ignore_errors'=>true],'ssl'=>['cafile'=>'/root/.ccr/ca-bundle.crt','verify_peer'=>true,'verify_peer_name'=>true]]);
    $raw = @file_get_contents($rdap_url, false, $ctx);
    if ($raw) {
        $data = json_decode($raw, true);
        if ($data && isset($data['ldhName'])) {
            $result['source'] = 'RDAP';
            $result['registrar'] = '';
            $result['created'] = '';
            $result['expires'] = '';
            $result['updated'] = '';
            $result['status'] = [];
            $result['nameservers'] = [];
            if (isset($data['entities'])) {
                foreach ($data['entities'] as $ent) {
                    if (in_array('registrar', $ent['roles'] ?? [])) {
                        $result['registrar'] = $ent['vcardArray'][1][1][3] ?? ($ent['publicIds'][0]['identifier'] ?? '');
                    }
                }
            }
            if (isset($data['events'])) {
                foreach ($data['events'] as $ev) {
                    if ($ev['eventAction'] === 'registration') $result['created'] = substr($ev['eventDate'],0,10);
                    if ($ev['eventAction'] === 'expiration')   $result['expires'] = substr($ev['eventDate'],0,10);
                    if ($ev['eventAction'] === 'last changed') $result['updated'] = substr($ev['eventDate'],0,10);
                }
            }
            if (isset($data['status'])) $result['status'] = (array)$data['status'];
            if (isset($data['nameservers'])) {
                foreach ($data['nameservers'] as $ns) {
                    $result['nameservers'][] = strtolower($ns['ldhName'] ?? '');
                }
            }
            echo json_encode($result); exit;
        }
    }

    // Fallback: whois TCP socket
    $whois_servers = [
        'com'=>'whois.verisign-grs.com','net'=>'whois.verisign-grs.com','org'=>'whois.pir.org',
        'io'=>'whois.nic.io','co'=>'whois.nic.co','sa'=>'whois.nic.net.sa',
        'ae'=>'whois.aeda.net.ae','eg'=>'whois.ripe.net','uk'=>'whois.nic.uk',
        'de'=>'whois.denic.de','fr'=>'whois.afnic.fr','info'=>'whois.afilias.net',
        'biz'=>'whois.neulevel.biz','app'=>'whois.nic.google'
    ];
    $ws = $whois_servers[$tld] ?? 'whois.iana.org';
    $raw = '';
    $fp = @fsockopen($ws, 43, $errno, $errstr, 5);
    if ($fp) {
        fputs($fp, $domain."\r\n");
        while (!feof($fp)) $raw .= fgets($fp, 512);
        fclose($fp);
    }
    if ($raw) {
        $result['source'] = 'WHOIS ('.$ws.')';
        $result['raw'] = htmlspecialchars(mb_substr($raw, 0, 3000));
        foreach (explode("\n", $raw) as $line) {
            $line = trim($line);
            if (!$line || strpos($line,'%')===0 || strpos($line,'>')===0) continue;
            $p = strpos($line,':'); if($p===false)continue;
            $key = strtolower(trim(substr($line,0,$p)));
            $val = trim(substr($line,$p+1));
            if (!$val) continue;
            if (in_array($key,['registrar','sponsoring registrar','registrar name']) && !$result['registrar']) $result['registrar']=$val;
            if (in_array($key,['creation date','registered on','registration time','created']) && !$result['created']) $result['created']=substr($val,0,10);
            if (in_array($key,['registry expiry date','expiry date','expiration date','expires']) && !$result['expires']) $result['expires']=substr($val,0,10);
            if (in_array($key,['updated date','last modified','last updated']) && !$result['updated']) $result['updated']=substr($val,0,10);
            if (in_array($key,['domain status','status'])) $result['status'][]=$val;
            if (in_array($key,['name server','nserver'])) $result['nameservers'][]=strtolower($val);
        }
    } else {
        $result['source'] = 'RDAP (domainsdb)';
        $api = 'https://api.domainsdb.info/v1/domains/search?domain='.urlencode(explode('.',$domain)[0]).'&zone='.urlencode($tld);
        $raw2 = @file_get_contents($api, false, $ctx);
        if ($raw2) { $d2=json_decode($raw2,true); if(isset($d2['domains'][0])) $result['db']=$d2['domains'][0]; }
    }
    echo json_encode($result); exit;
}

$schema = json_encode([
  '@context'=>'https://schema.org','@type'=>'WebApplication',
  'name'=>'WHOIS معلومات النطاق','url'=>TOOLS_BASE_URL.'/tools/whois/',
  'description'=>'ابحث عن معلومات تسجيل أي نطاق مجاناً — المسجّل وتاريخ الانتهاء وخوادم DNS وحالة النطاق فوراً',
  'applicationCategory'=>'DeveloperApplication','operatingSystem'=>'All',
  'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],'inLanguage'=>'ar'
]);
tool_head('WHOIS — معلومات تسجيل النطاق | مجاني وفوري | yassota','ابحث في قاعدة WHOIS عن أي نطاق مجاناً. اعرف المسجّل وتاريخ الإنشاء والانتهاء وخوادم DNS وحالة النطاق.',$schema,'#1e40af');
tool_header();
?>
<main class="t-main">
<div class="t-hero">
  <div class="t-hero-icon" style="background:#dbeafe">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#1e40af" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
  </div>
  <h1>WHOIS — معلومات النطاق</h1>
  <p>اعرف من يملك أي نطاق وتاريخ تسجيله وانتهائه</p>
</div>

<div class="t-card">
  <div style="display:flex;gap:10px">
    <input type="text" id="wi-domain" class="t-input" placeholder="example.com" style="flex:1;direction:ltr;text-align:left" onkeydown="if(event.key==='Enter')lookupWhois()">
    <button class="t-btn" onclick="lookupWhois()">بحث WHOIS</button>
  </div>
  <div style="margin-top:8px;font-size:12px;color:#6b7280">يدعم: .com .net .org .io .sa .ae وكثير غيرها</div>

  <div id="wi-loading" style="display:none;text-align:center;padding:30px;color:#1e40af">
    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
    <div style="margin-top:12px;font-weight:600">جارٍ الاستعلام...</div>
  </div>
  <div id="wi-result" style="display:none;margin-top:16px"></div>
  <div id="wi-error" class="t-error" style="display:none;margin-top:12px"></div>
</div>

<?php tool_ad(); ?>

<div class="t-card" style="line-height:1.9">
  <h2 class="t-article-h2">ما هو WHOIS وما أهميته؟</h2>
  <p>WHOIS (اقرأها "who is" — من هو؟) هو بروتوكول استعلام وقاعدة بيانات تحتوي على معلومات التسجيل لأسماء النطاقات وعناوين IP وأنظمة الأرقام المستقلة (Autonomous Systems). يُتيح لأي شخص معرفة من يملك نطاقاً معيناً ومتى سجّله ومتى ينتهي تسجيله ومن أين يُدار DNS الخاص به.</p>
  <p>اخترع WHOIS المبرمج الأمريكي إليزابيث فينشيتر عام 1982 في الأيام الأولى للإنترنت. في ذلك الوقت كان الإنترنت صغيراً جداً وكانت قاعدة WHOIS تحتوي على معلومات كل مستخدمي الإنترنت في ملف نصي واحد. اليوم تُدير منظمة ICANN (مؤسسة الإنترنت للأسماء والأرقام المخصصة) قاعدة WHOIS العالمية عبر شبكة من المسجّلين المعتمدين.</p>
  <p>في عام 2018، أحدث تطبيق لائحة GDPR الأوروبية تغييراً جوهرياً في WHOIS: أصبح نشر بيانات المسجّلين الشخصية (الاسم والبريد والعنوان) محدوداً بشدة لحماية الخصوصية. لذا ستجد في معظم استعلامات WHOIS اليوم بيانات مخفية أو مُوجَّهة لخدمة خصوصية وسيطة.</p>

  <h2 class="t-article-h2">كيفية استخدام أداة WHOIS</h2>
  <p>أدخل اسم النطاق الكامل في حقل البحث (مثل: google.com أو bbc.co.uk) وانقر "بحث WHOIS". الأداة تستعلم أولاً عبر RDAP (بروتوكول حديث يُعيد بيانات JSON) ثم عبر WHOIS التقليدي (TCP Port 43) كبديل. النتائج تُظهر المعلومات المتاحة عن النطاق.</p>
  <p>إذا كان النطاق يستخدم خدمة حماية الخصوصية، ستجد بيانات الشركة الوسيطة بدلاً من بيانات المالك الفعلي. هذه ممارسة شائعة جداً لأسباب مشروعة (حماية من البريد العشوائي والمضايقة).</p>

  <h2 class="t-article-h2">معلومات يُقدمها WHOIS وما تعنيه</h2>
  <p><strong>المسجّل (Registrar):</strong> الشركة التي سجّل عندها مالك النطاق اسمه. أمثلة: GoDaddy، Namecheap، Google Domains (توقفت 2023)، أو مزودون محليون. المسجّل ليس بالضرورة هو مزود الاستضافة أو مزود DNS.</p>
  <p><strong>تاريخ الإنشاء (Created):</strong> متى سُجّل النطاق لأول مرة. نطاق مسجّل منذ سنوات طويلة عادةً أكثر موثوقية من نطاق جديد. أيضاً مفيد لمعرفة عمر المنافس أو التحقق من قدم موقع تريد شراءه.</p>
  <p><strong>تاريخ الانتهاء (Expires):</strong> متى سينتهي تسجيل النطاق. نطاق على وشك الانتهاء قد يصبح متاحاً للشراء. إذا كنت تعتمد على نطاق لخدمات مهمة، راقب تاريخ انتهائه وجدّده مبكراً.</p>
  <p><strong>خوادم DNS (Nameservers):</strong> الخوادم المسؤولة عن ترجمة اسم النطاق لعناوين IP. تغيير النيم سيرفرز يعني نقل إدارة DNS من مزود لآخر. Cloudflare Nameservers تعني أن المالك يستخدم Cloudflare لـ CDN والحماية.</p>
  <p><strong>حالة النطاق (Status):</strong> clientTransferProhibited = لا يمكن نقل النطاق لمسجّل آخر (حماية شائعة). clientDeleteProhibited = لا يمكن حذفه. serverHold = النطاق موقوف من المسجّل (قد يكون بسبب مخالفة). redemptionPeriod = النطاق منتهي وفي فترة الاسترداد.</p>

  <h2 class="t-article-h2">استخدامات WHOIS العملية</h2>
  <p><strong>شراء النطاقات المنتهية:</strong> المبدعون في تسويق النطاقات (Domain Flipping) يراقبون تواريخ الانتهاء للحصول على نطاقات ذات قيمة عالية بسعر التسجيل عند انتهائها.</p>
  <p><strong>التحقق من مصداقية موقع:</strong> قبل الشراء من موقع مجهول أو إرسال بياناتك، فحص WHOIS يُظهر عمر النطاق. موقع مسجّل منذ أسبوع يطلب معلومات بطاقتك الائتمانية — تحذير أحمر!</p>
  <p><strong>التواصل مع مالك النطاق:</strong> إذا أردت شراء نطاق مُسجَّل مسبقاً، WHOIS يُعطيك (أحياناً) بريد المالك لتقديم عرض.</p>
  <p><strong>الأمن السيبراني:</strong> محللو الأمن يستخدمون WHOIS للتحقيق في نطاقات ضارة — متى سُجّلت (نطاقات الاحتيال غالباً جديدة)، ومن يملكها، وأين يُديرها.</p>
  <p><strong>الصحافة والتحقيق:</strong> الصحفيون يستخدمون WHOIS للكشف عن ملاك المواقع الإعلامية والمدونات المجهولة — رغم أن خصوصية GDPR قلّصت هذه الإمكانية.</p>
  <p><strong>قانوني وحقوق ملكية:</strong> أصحاب العلامات التجارية يستخدمون WHOIS للعثور على ملاك نطاقات تنتهك علاماتهم لاتخاذ إجراءات قانونية (UDRP - Uniform Domain-Name Dispute-Resolution Policy).</p>

  <h2 class="t-article-h2">الفرق بين WHOIS وRDAP</h2>
  <p>WHOIS بروتوكول قديم (RFC 3912) يعمل عبر TCP Port 43 ويُعيد بيانات نصية غير منظمة — لكل مسجّل تنسيق مختلف. هذا يجعل تحليله آلياً صعباً. RDAP (Registration Data Access Protocol) هو البديل الحديث الذي أنتجته IETF عام 2015 ويُعيد بيانات JSON منظمة، يدعم HTTPS، ويُتحكم فيه بشكل أفضل من ناحية الأذونات والخصوصية. هذه الأداة تُجرّب RDAP أولاً للحصول على أفضل نتيجة.</p>

  <h2 class="t-article-h2">أسئلة شائعة حول WHOIS</h2>
  <div class="t-faq-item">
    <div class="t-faq-q">لماذا تظهر معلومات مخفية أو وسيطة في نتائج WHOIS؟</div>
    <div class="t-faq-a">بسبب خدمات حماية الخصوصية (Privacy Protection) التي يُقدّمها معظم المسجّلين مجاناً أو بتكلفة رمزية. عوضاً عن نشر معلومات المالك الحقيقية، تُسجَّل معلومات الشركة الوسيطة. هذا مشروع تماماً ويحمي من البريد العشوائي ومضايقة المسجّلين. ICANN أوجبت هذا الإخفاء الجزئي بعد لائحة GDPR.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">هل يمكنني إخفاء معلوماتي في WHOIS؟</div>
    <div class="t-faq-a">نعم. فعّل خدمة "Privacy Protection" أو "Domain Privacy" عند مسجّلك. معظم المسجّلين يُقدّمونها مجاناً (Namecheap, Cloudflare Registrar) أو بعدة دولارات سنوياً. بعد التفعيل يظهر في WHOIS معلومات الشركة الوسيطة بدل معلوماتك الشخصية.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">كيف أعرف إذا كان نطاق ما متاحاً للشراء؟</div>
    <div class="t-faq-a">إذا لم تُظهر نتائج WHOIS أي بيانات تسجيل (Domain not found)، فالنطاق متاح للتسجيل من أي مسجّل. إذا ظهرت بيانات تسجيل، النطاق مأخوذ. إذا ظهرت حالة "pendingDelete" أو "redemptionPeriod" فالنطاق على وشك الإطلاق.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">ما هي فترة الاسترداد (Redemption Period) في WHOIS؟</div>
    <div class="t-faq-a">بعد انتهاء صلاحية النطاق وفترة الرسوم الإضافية (Grace Period)، يدخل النطاق فترة الاسترداد (عادةً 30 يوماً) حيث يظل بالإمكان تجديده بتكلفة أعلى. بعد الاسترداد يدخل "pendingDelete" (5 أيام) ثم يُطلق للعموم. مواقع متخصصة تُراقب هذه النطاقات وتُعلن عن مواعيد إطلاقها.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">كيف أنقل نطاقاً من مسجّل لآخر؟</div>
    <div class="t-faq-a">احصل على EPP Code (رمز التفويض) من مسجّلك الحالي. تأكد من عدم تفعيل "clientTransferProhibited". اذهب للمسجّل الجديد وابدأ عملية النقل. خلال 5-7 أيام يُرسَل بريد تأكيد. ملاحظة: لا يمكن نقل نطاق سُجّل منذ أقل من 60 يوماً أو جُدِّد منذ أقل من 60 يوماً وفق سياسة ICANN.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">ماذا يعني التاريخ في WHOIS — هل يعني أن الموقع يعمل منذ ذلك التاريخ؟</div>
    <div class="t-faq-a">تاريخ الإنشاء في WHOIS هو متى سُجّل النطاق، ليس متى أُطلق الموقع. قد يُسجّل المرء نطاقاً لسنوات قبل إطلاق الموقع، أو قد تُطلق موقعاً على نطاق موجود منذ سنوات. لمعرفة تاريخ الموقع الفعلي استخدم Wayback Machine من Archive.org.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">لماذا لا تجد معظم نطاقات .sa في قواعد WHOIS العامة؟</div>
    <div class="t-faq-a">الدول التي تُدير نطاقاتها الوطنية (ccTLD) كـ.sa (السعودية) و.ae (الإمارات) و.eg (مصر) لها قواعد WHOIS مستقلة ليست دائماً مُتاحة عبر الأدوات العامة. لمعلومات نطاق .sa اتصل بـ CITC أو زر موقع whois.nic.net.sa مباشرة.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">ما هو UDRP وكيف يُحل نزاع النطاقات؟</div>
    <div class="t-faq-a">UDRP هي سياسة ICANN لحل نزاعات النطاقات بدون تقاضٍ. إذا كنت تملك علامة تجارية مسجّلة وشخص ما سجّل نطاقاً يتطابق معها بنية سيئة (Cybersquatting)، يمكنك تقديم شكوى UDRP أمام هيئات معتمدة (مثل مركز الوساطة WIPO) للحصول على نقل النطاق. يستغرق شهراً إلى شهرين وأقل تكلفة من القضاء.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">كيف أُرصد انتهاء صلاحية نطاقات أنتمي إليها؟</div>
    <div class="t-faq-a">فعّل التجديد التلقائي عند مسجّلك لكل النطاقات المهمة. تلقّ إشعارات بريد إلكتروني قبل 90، 60، 30 يوماً من الانتهاء. استخدم خدمات مراقبة النطاقات مثل DomainMon أو Domain Alert Pro. أيضاً تحقق دورياً من WHOIS للنطاقات الأساسية لأعمالك.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">هل يمكن لأي شخص تسجيل أي نطاق .com؟</div>
    <div class="t-faq-a">نعم. .com (وكذلك .net, .org, .info, .biz) هي نطاقات مفتوحة يمكن لأي شخص في العالم تسجيلها بدون قيود جغرافية أو مؤسسية، طالما أن الاسم متاح وغير مخالف للعلامات التجارية. في المقابل، .gov حكراً على الجهات الحكومية الأمريكية، .edu للجامعات الأمريكية المعتمدة، و.mil للجيش الأمريكي.</div>
  </div>

  <h2 class="t-article-h2">الخلاصة</h2>
  <p>WHOIS أداة أساسية لكل من يعمل في مجال الإنترنت — من المطورين والمسوّقين إلى الصحفيين ومحللي الأمن. يُتيح شفافية ضرورية لبيئة صحية من الثقة على الإنترنت. استخدم هذه الأداة للكشف عن ملاك النطاقات، التحقق من تواريخ الانتهاء، أو ببساطة إشباع فضولك حول من يقف وراء موقع معين.</p>
</div>
</main>

<style>@keyframes spin{to{transform:rotate(360deg)}}</style>
<script>
async function lookupWhois(){
  const domain=document.getElementById('wi-domain').value.trim();
  if(!domain)return;
  document.getElementById('wi-loading').style.display='block';
  document.getElementById('wi-result').style.display='none';
  document.getElementById('wi-error').style.display='none';
  try{
    const res=await fetch('?action=whois&domain='+encodeURIComponent(domain));
    const data=await res.json();
    document.getElementById('wi-loading').style.display='none';
    if(data.error){
      document.getElementById('wi-error').style.display='block';
      document.getElementById('wi-error').textContent=data.error;
      return;
    }
    renderWhois(data);
  }catch(e){
    document.getElementById('wi-loading').style.display='none';
    document.getElementById('wi-error').style.display='block';
    document.getElementById('wi-error').textContent='خطأ في الاتصال: '+e.message;
  }
}
function renderWhois(d){
  const statusColors={'active':'#16a34a','clienttransferprohibited':'#0891b2','serverheld':'#dc2626','serverhold':'#dc2626','pendingdelete':'#f97316','redemptionperiod':'#f97316'};
  let html=`<div style="font-size:12px;color:#6b7280;margin-bottom:12px">المصدر: ${h(d.source||'')}</div>`;
  html+=`<div style="display:grid;gap:10px">`;
  const rows=[
    ['النطاق',d.domain],
    ['المسجّل (Registrar)',d.registrar],
    ['تاريخ التسجيل',d.created],
    ['تاريخ الانتهاء',d.expires],
    ['آخر تحديث',d.updated]
  ];
  rows.forEach(([k,v])=>{if(!v)return;html+=`<div style="display:flex;gap:12px;padding:10px 14px;background:#f8fafc;border-radius:8px;border:1px solid #e5e7eb"><span style="font-weight:700;color:#1e40af;min-width:160px;flex-shrink:0">${h(k)}</span><span style="font-family:monospace;direction:ltr;text-align:left;word-break:break-all">${h(v)}</span></div>`;});
  if(d.status&&d.status.length){
    html+=`<div style="padding:10px 14px;background:#f8fafc;border-radius:8px;border:1px solid #e5e7eb"><span style="font-weight:700;color:#1e40af">الحالة</span><div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:6px">`;
    d.status.forEach(s=>{
      const sl=s.toLowerCase().replace(/\s.*/,'');
      const c=statusColors[sl]||'#6b7280';
      html+=`<span style="padding:2px 10px;border-radius:12px;font-size:12px;border:1px solid ${c};color:${c};font-family:monospace">${h(s.split(' ')[0])}</span>`;
    });
    html+='</div></div>';
  }
  if(d.nameservers&&d.nameservers.length){
    html+=`<div style="padding:10px 14px;background:#f8fafc;border-radius:8px;border:1px solid #e5e7eb"><span style="font-weight:700;color:#1e40af">خوادم DNS</span><div style="margin-top:8px">`;
    d.nameservers.forEach(ns=>{html+=`<div style="font-family:monospace;direction:ltr;text-align:left;font-size:13px;padding:2px 0">${h(ns)}</div>`;});
    html+='</div></div>';
  }
  if(d.raw){
    html+=`<details style="margin-top:4px"><summary style="cursor:pointer;padding:8px;background:#f8fafc;border-radius:8px;font-size:13px;color:#1e40af">البيانات الخام (WHOIS)</summary><pre style="font-size:11px;direction:ltr;text-align:left;overflow-x:auto;padding:12px;background:#0f172a;color:#94a3b8;border-radius:8px;margin-top:8px;max-height:300px;overflow-y:auto">${d.raw}</pre></details>`;
  }
  if(d.db){
    const db=d.db;
    html+=`<div style="padding:10px 14px;background:#eff6ff;border-radius:8px;font-size:13px"><strong>من قاعدة DomainsDB:</strong> تاريخ الإنشاء: ${h(db.create_date||'-')} | آخر تحديث: ${h(db.update_date||'-')}</div>`;
  }
  html+='</div>';
  document.getElementById('wi-result').innerHTML=html;
  document.getElementById('wi-result').style.display='block';
}
function h(s){if(!s)return '';const d=document.createElement('div');d.textContent=String(s);return d.innerHTML;}
</script>
<?php tool_footer(); ?>
