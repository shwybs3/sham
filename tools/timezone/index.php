<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'محوّل المناطق الزمنية','url'=>TOOLS_BASE_URL.'/tools/timezone/',
    'description'=>'حوّل الوقت بين مختلف المناطق الزمنية حول العالم. تحويل فوري للمدن العربية والعالمية.',
    'applicationCategory'=>'UtilitiesApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('محوّل المناطق الزمنية — حوّل الوقت بين مدن العالم | yassota','حوّل الوقت بين دمشق وبيروت والرياض ودبي وبغداد وأي مدينة عالمية. محوّل مناطق زمنية عربي مجاني.',$schema,'#0891b2');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#e0f2fe">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0891b2" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    </div>
    <h1>محوّل المناطق الزمنية</h1>
    <p>حوّل الوقت بين أي مدينتين في العالم فورياً — مع التوقيت الصيفي وجميع المناطق الزمنية.</p>
  </div>

  <div class="t-card" style="max-width:620px;margin:0 auto 24px">
    <div style="display:grid;grid-template-columns:1fr auto 1fr;gap:12px;align-items:end;margin-bottom:16px">
      <div>
        <label class="t-label">المنطقة الزمنية الأصلية</label>
        <select id="tz-from" class="t-input" style="width:100%"></select>
      </div>
      <button onclick="swapZones()" class="t-btn" style="padding:10px 14px;margin-bottom:0">⇄</button>
      <div>
        <label class="t-label">المنطقة الزمنية المستهدفة</label>
        <select id="tz-to" class="t-input" style="width:100%"></select>
      </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
      <div>
        <label class="t-label">التاريخ</label>
        <input type="date" id="tz-date" class="t-input" style="width:100%">
      </div>
      <div>
        <label class="t-label">الوقت</label>
        <input type="time" id="tz-time" class="t-input" style="width:100%">
      </div>
    </div>
    <button id="convert-btn" class="t-btn" style="width:100%;background:#0891b2">تحويل الوقت</button>
    <div id="result" style="display:none;margin-top:20px"></div>
  </div>

  <div class="t-card" style="margin-bottom:24px">
    <h2>الأوقات الحالية في المدن العربية والعالمية</h2>
    <div id="world-times" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;margin-top:12px"></div>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card">
    <h2>مناطق زمنية الدول العربية</h2>
    <div style="overflow-x:auto">
      <table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead><tr style="background:#f1f5f9"><th style="padding:9px 12px;text-align:right">الدولة/المدينة</th><th style="padding:9px 12px;text-align:right">المنطقة الزمنية</th><th style="padding:9px 12px;text-align:right">الفرق عن GMT</th></tr></thead>
        <tbody>
          <?php $arabCities = [
            ['المغرب','Africa/Casablanca','GMT+1'],
            ['الجزائر وتونس وليبيا','Africa/Algiers','GMT+1'],
            ['مصر','Africa/Cairo','GMT+2'],
            ['السودان','Africa/Khartoum','GMT+3'],
            ['سوريا ولبنان','Asia/Damascus','GMT+3'],
            ['الأردن والفلسطين','Asia/Amman','GMT+3'],
            ['العراق','Asia/Baghdad','GMT+3'],
            ['السعودية واليمن','Asia/Riyadh','GMT+3'],
            ['الكويت والبحرين وقطر','Asia/Kuwait','GMT+3'],
            ['الإمارات وعُمان','Asia/Dubai','GMT+4'],
          ]; foreach ($arabCities as [$c,$tz,$gmt]): ?>
          <tr style="border-bottom:1px solid #f1f5f9">
            <td style="padding:8px 12px;font-weight:600"><?= $c ?></td>
            <td style="padding:8px 12px;color:#64748b;font-family:monospace;direction:ltr;text-align:left"><?= $tz ?></td>
            <td style="padding:8px 12px;color:#0891b2;font-weight:700"><?= $gmt ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<script>
const timezones = [
  {label:'دمشق (Syria)',tz:'Asia/Damascus'},
  {label:'بيروت (Lebanon)',tz:'Asia/Beirut'},
  {label:'عمّان (Jordan)',tz:'Asia/Amman'},
  {label:'القاهرة (Egypt)',tz:'Africa/Cairo'},
  {label:'الرياض (Saudi Arabia)',tz:'Asia/Riyadh'},
  {label:'دبي (UAE)',tz:'Asia/Dubai'},
  {label:'بغداد (Iraq)',tz:'Asia/Baghdad'},
  {label:'الكويت (Kuwait)',tz:'Asia/Kuwait'},
  {label:'مسقط (Oman)',tz:'Asia/Muscat'},
  {label:'الدوحة (Qatar)',tz:'Asia/Qatar'},
  {label:'أبوظبي (Abu Dhabi)',tz:'Asia/Dubai'},
  {label:'طرابلس (Libya)',tz:'Africa/Tripoli'},
  {label:'تونس (Tunisia)',tz:'Africa/Tunis'},
  {label:'الجزائر (Algeria)',tz:'Africa/Algiers'},
  {label:'الدار البيضاء (Morocco)',tz:'Africa/Casablanca'},
  {label:'الخرطوم (Sudan)',tz:'Africa/Khartoum'},
  {label:'أنقرة (Turkey)',tz:'Europe/Istanbul'},
  {label:'لندن (UK)',tz:'Europe/London'},
  {label:'باريس (France)',tz:'Europe/Paris'},
  {label:'برلين (Germany)',tz:'Europe/Berlin'},
  {label:'موسكو (Russia)',tz:'Europe/Moscow'},
  {label:'نيويورك (USA East)',tz:'America/New_York'},
  {label:'لوس أنجلوس (USA West)',tz:'America/Los_Angeles'},
  {label:'طوكيو (Japan)',tz:'Asia/Tokyo'},
  {label:'بكين (China)',tz:'Asia/Shanghai'},
  {label:'مومباي (India)',tz:'Asia/Kolkata'},
  {label:'سنغافورة',tz:'Asia/Singapore'},
  {label:'سيدني (Australia)',tz:'Australia/Sydney'},
];

const fromSel = document.getElementById('tz-from');
const toSel   = document.getElementById('tz-to');
timezones.forEach(({label,tz},i) => {
  const o1 = document.createElement('option'); o1.value=tz; o1.textContent=label; fromSel.appendChild(o1);
  const o2 = document.createElement('option'); o2.value=tz; o2.textContent=label; toSel.appendChild(o2);
});
fromSel.value = 'Asia/Damascus';
toSel.value   = 'America/New_York';

const now = new Date();
document.getElementById('tz-date').value = now.toISOString().slice(0,10);
document.getElementById('tz-time').value = now.toTimeString().slice(0,5);

function swapZones() {
  const tmp = fromSel.value; fromSel.value = toSel.value; toSel.value = tmp;
  doConvert();
}

function doConvert() {
  const dateStr = document.getElementById('tz-date').value;
  const timeStr = document.getElementById('tz-time').value;
  if (!dateStr || !timeStr) return;
  const fromTz = fromSel.value, toTz = toSel.value;
  const dtStr = dateStr + 'T' + timeStr + ':00';
  try {
    const parts = new Intl.DateTimeFormat('en-US', {timeZone:fromTz,year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:false}).formatToParts(new Date(dtStr));
    const obj = Object.fromEntries(parts.map(p=>[p.type,p.value]));
    const utcStr = `${obj.year}-${obj.month}-${obj.day}T${obj.hour==='24'?'00':obj.hour}:${obj.minute}:${obj.second}Z`;
    const offset = (new Date(dtStr)-new Date(utcStr))/6e4;
    const converted = new Date(new Date(dtStr).getTime() - offset*60000);
    const result = new Intl.DateTimeFormat('ar-SY',{timeZone:toTz,dateStyle:'full',timeStyle:'long'}).format(converted);
    const resultEn = new Intl.DateTimeFormat('en',{timeZone:toTz,dateStyle:'long',timeStyle:'short'}).format(converted);
    const res = document.getElementById('result');
    res.style.display = 'block';
    res.innerHTML = `<div style="background:#e0f2fe;border-radius:12px;padding:16px">
      <div style="font-size:13px;color:#0891b2;margin-bottom:6px">الوقت المحوَّل في ${toSel.options[toSel.selectedIndex].text}</div>
      <div style="font-size:22px;font-weight:700;color:#0f172a">${resultEn}</div>
    </div>`;
  } catch(e) {
    document.getElementById('result').innerHTML = '<div style="color:#ef4444">خطأ في التحويل: '+e.message+'</div>';
    document.getElementById('result').style.display = 'block';
  }
}

document.getElementById('convert-btn').addEventListener('click', doConvert);

// World times clock
const worldTimes = document.getElementById('world-times');
const featured = [
  {label:'دمشق',tz:'Asia/Damascus'},
  {label:'الرياض',tz:'Asia/Riyadh'},
  {label:'دبي',tz:'Asia/Dubai'},
  {label:'بغداد',tz:'Asia/Baghdad'},
  {label:'القاهرة',tz:'Africa/Cairo'},
  {label:'لندن',tz:'Europe/London'},
  {label:'نيويورك',tz:'America/New_York'},
  {label:'طوكيو',tz:'Asia/Tokyo'},
];
function updateClocks() {
  const now = new Date();
  worldTimes.innerHTML = featured.map(({label,tz}) => {
    const t = new Intl.DateTimeFormat('ar',{timeZone:tz,hour:'2-digit',minute:'2-digit',hour12:true}).format(now);
    const d = new Intl.DateTimeFormat('ar',{timeZone:tz,weekday:'short'}).format(now);
    return `<div style="background:#f8fafc;border-radius:10px;padding:12px;text-align:center">
      <div style="font-size:11px;color:#94a3b8;margin-bottom:4px">${d}</div>
      <div style="font-size:18px;font-weight:700;color:#0f172a">${t}</div>
      <div style="font-size:12px;color:#0891b2;margin-top:4px">${label}</div>
    </div>`;
  }).join('');
}
updateClocks();
setInterval(updateClocks, 5000);
</script>
<?php tool_footer(); ?>
