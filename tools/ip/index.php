<?php
require_once dirname(__DIR__) . '/_base.php';

if (isset($_GET['action']) && $_GET['action'] === 'myip') {
    header('Content-Type: application/json');
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP']
       ?? $_SERVER['HTTP_X_FORWARDED_FOR']
       ?? $_SERVER['HTTP_X_REAL_IP']
       ?? $_SERVER['REMOTE_ADDR']
       ?? 'unknown';
    $ip = trim(explode(',', $ip)[0]);
    echo json_encode(['ip' => $ip]);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'lookup') {
    header('Content-Type: application/json');
    $ip = filter_var($_GET['ip'] ?? '', FILTER_VALIDATE_IP);
    if (!$ip) { echo json_encode(['error' => 'IP غير صالح']); exit; }
    $ctx = stream_context_create(['http' => ['timeout' => 8, 'user_agent' => 'yassota-tools/1.0']]);
    $raw = @file_get_contents("https://ipapi.co/{$ip}/json/", false, $ctx);
    if ($raw) { echo $raw; } else { echo json_encode(['error' => 'تعذّر جلب البيانات']); }
    exit;
}

$schema = json_encode(['@context'=>'https://schema.org','@type'=>'WebApplication','name'=>'معلومات عنوان IP','url'=>TOOLS_BASE_URL.'/tools/ip/','description'=>'اعرف عنوان IP الخاص بك والموقع الجغرافي والمزود والبلد فوراً. ابحث عن أي IP في العالم.','applicationCategory'=>'UtilitiesApplication','operatingSystem'=>'All','offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD']]);
tool_head('ما هو عنوان IP الخاص بي؟ — بحث وموقع IP | yassota','اكتشف عنوان IP الخاص بك وموقعك الجغرافي وشركة الإنترنت فوراً. أداة بحث عن أي IP مجانية.',$schema,'#4f46e5');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#eef2ff">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#4f46e5" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 010 20M12 2a15.3 15.3 0 000 20"/></svg>
    </div>
    <h1>عنوان IP الخاص بي</h1>
    <p>اعرف IP الخاص بك وموقعك الجغرافي — أو ابحث عن أي عنوان IP في العالم.</p>
  </div>
  <!-- My IP -->
  <div class="t-card" style="max-width:600px;margin:0 auto 24px;text-align:center">
    <div style="font-size:13px;color:#64748b;margin-bottom:8px">عنوان IP الخاص بك</div>
    <div id="my-ip" style="font-size:38px;font-weight:900;color:#4f46e5;font-family:monospace;direction:ltr;letter-spacing:1px">جارٍ التحميل…</div>
    <button onclick="copyIp()" style="margin-top:10px;background:#eef2ff;color:#4f46e5;border:none;border-radius:8px;padding:8px 20px;font-size:13px;cursor:pointer">نسخ IP</button>
    <div id="ip-copy-msg" style="display:none;color:#059669;font-size:12px;margin-top:4px">✅ تم النسخ</div>
  </div>
  <!-- Details -->
  <div class="t-card" style="max-width:600px;margin:0 auto 24px" id="ip-details" style="display:none">
    <h2 style="font-size:15px;margin-bottom:12px">تفاصيل الاتصال</h2>
    <div id="details-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:10px"></div>
  </div>
  <!-- Lookup -->
  <div class="t-card" style="max-width:600px;margin:0 auto 24px">
    <h2 style="font-size:15px;margin-bottom:12px">ابحث عن أي عنوان IP</h2>
    <div style="display:flex;gap:8px">
      <input type="text" id="lookup-ip" class="t-input" style="flex:1" dir="ltr" placeholder="مثال: 8.8.8.8">
      <button onclick="lookupIp()" class="t-btn" style="background:#4f46e5">بحث</button>
    </div>
    <div id="lookup-result" style="margin-top:14px"></div>
  </div>
  <?php tool_ad(); ?>
  <div class="t-card">
    <h2>ما هو عنوان IP؟</h2>
    <p style="color:#64748b;line-height:1.8">عنوان IP (Internet Protocol) هو رقم فريد يُعرِّف جهازك على الإنترنت، مثل العنوان البريدي للمنزل. هناك نوعان: IPv4 (مثل 192.168.1.1) وIPv6 (أطول وأكثر تعقيداً). يُستخدم IP لتحديد الموقع الجغرافي التقريبي، توجيه البيانات، وتقييد الوصول.</p>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:12px">
      <div style="background:#eef2ff;border-radius:10px;padding:12px"><div style="font-weight:700;color:#4f46e5;margin-bottom:4px">IPv4</div><div style="font-size:12px;color:#64748b">4 أرقام مفصولة بنقاط (0-255.0-255) — نحو 4 مليار عنوان</div></div>
      <div style="background:#f0fdf4;border-radius:10px;padding:12px"><div style="font-weight:700;color:#16a34a;margin-bottom:4px">IPv6</div><div style="font-size:12px;color:#64748b">128 بت سداسي عشري — عناوين تقريباً لا تنتهي</div></div>
    </div>
  </div>
</main>
<script>
let myIpVal = '';
fetch('?action=myip').then(r=>r.json()).then(d=>{
  myIpVal = d.ip || 'غير متاح';
  document.getElementById('my-ip').textContent = myIpVal;
  if(myIpVal !== 'غير متاح') fetchDetails(myIpVal, 'details-grid');
}).catch(()=>{ document.getElementById('my-ip').textContent='غير متاح'; });

function fetchDetails(ip, containerId) {
  fetch('?action=lookup&ip='+encodeURIComponent(ip)).then(r=>r.json()).then(d=>{
    if(d.error){document.getElementById(containerId).innerHTML='<div style="color:#ef4444">'+d.error+'</div>';return;}
    const fields=[
      ['البلد',d.country_name+' '+(d.country||'')],
      ['المدينة',d.city||'—'],
      ['المنطقة',d.region||'—'],
      ['المزود (ISP)',(d.org||d.asn||'—')],
      ['نوع الشبكة',d.network||'—'],
      ['المنطقة الزمنية',d.timezone||'—'],
      ['خط العرض',d.latitude||'—'],
      ['خط الطول',d.longitude||'—'],
    ];
    document.getElementById(containerId).innerHTML=fields.map(([k,v])=>`
      <div style="background:#f8fafc;border-radius:8px;padding:10px">
        <div style="font-size:11px;color:#94a3b8;margin-bottom:2px">${k}</div>
        <div style="font-size:13px;font-weight:700;color:#0f172a;direction:ltr">${v||'—'}</div>
      </div>`).join('');
    document.getElementById('ip-details').style.display='block';
  }).catch(()=>{});
}

function lookupIp(){
  const ip=document.getElementById('lookup-ip').value.trim();
  if(!ip){return;}
  const res=document.getElementById('lookup-result');
  res.innerHTML='<div style="color:#64748b">⏳ جارٍ البحث…</div>';
  fetch('?action=lookup&ip='+encodeURIComponent(ip)).then(r=>r.json()).then(d=>{
    if(d.error){res.innerHTML='<div style="color:#ef4444">'+d.error+'</div>';return;}
    const fields=[['البلد',d.country_name+' '+(d.country||'')],['المدينة',d.city||'—'],['المنطقة',d.region||'—'],['المزود',(d.org||'—')],['المنطقة الزمنية',d.timezone||'—']];
    res.innerHTML='<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">'+fields.map(([k,v])=>`<div style="background:#f8fafc;border-radius:8px;padding:10px"><div style="font-size:11px;color:#94a3b8">${k}</div><div style="font-size:13px;font-weight:700">${v}</div></div>`).join('')+'</div>';
  }).catch(()=>{res.innerHTML='<div style="color:#ef4444">تعذّر الاتصال</div>';});
}
function copyIp(){
  navigator.clipboard.writeText(myIpVal);
  const m=document.getElementById('ip-copy-msg');m.style.display='block';
  setTimeout(()=>m.style.display='none',2000);
}
</script>
<?php tool_footer(); ?>
