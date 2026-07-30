<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'محوّل الأرقام (Binary / Hex / Octal)','url'=>TOOLS_BASE_URL.'/tools/base-conv/',
    'description'=>'حوّل الأرقام بين عشري وثنائي وست عشري وثماني. محوّل قواعد الأرقام للمطورين والطلاب.',
    'applicationCategory'=>'DeveloperApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('محوّل الأرقام Binary Hex Octal Decimal — تحويل قواعد الأرقام | yassota','حوّل الأرقام بين النظام العشري والثنائي والست عشري والثماني فوراً. أداة للمطورين وطلاب علوم الحاسوب.',$schema,'#0f172a');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#f1f5f9">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 3H8a2 2 0 00-2 2v2h12V5a2 2 0 00-2-2z"/><path d="M6 12h.01M10 12h.01M14 12h.01M18 12h.01M6 16h.01M10 16h.01M14 16h.01M18 16h.01"/></svg>
    </div>
    <h1>محوّل قواعد الأرقام</h1>
    <p>حوّل فورياً بين النظام العشري (Decimal) والثنائي (Binary) والست عشري (Hex) والثماني (Octal).</p>
  </div>

  <div class="t-card" style="max-width:600px;margin:0 auto 24px">
    <div style="display:grid;gap:14px">
      <?php $systems = [
        ['dec','العشري (Decimal)','10','#0f172a','#f1f5f9','0-9'],
        ['bin','الثنائي (Binary)','2','#2563eb','#dbeafe','0-1'],
        ['oct','الثماني (Octal)','8','#059669','#d1fae5','0-7'],
        ['hex','الست عشري (Hexadecimal)','16','#7c3aed','#ede9fe','0-9, A-F'],
      ]; foreach ($systems as [$id,$label,$base,$col,$bg,$hint]): ?>
      <div>
        <label class="t-label" style="display:flex;justify-content:space-between">
          <span><?= $label ?> (قاعدة <?= $base ?>)</span>
          <span style="font-size:11px;color:#94a3b8">الأرقام المسموحة: <?= $hint ?></span>
        </label>
        <div style="display:flex;gap:8px">
          <input type="text" id="<?= $id ?>" class="t-input" dir="ltr" style="flex:1;font-family:monospace;font-size:15px;border-color:<?= $col ?>33" placeholder="أدخل الرقم هنا...">
          <button onclick="convertFrom('<?= $id ?>')" style="background:<?= $bg ?>;color:<?= $col ?>;border:1px solid <?= $col ?>33;border-radius:8px;padding:0 14px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap">تحويل</button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div id="error" style="display:none;color:#ef4444;font-size:13px;margin-top:10px"></div>
  </div>

  <div class="t-card" style="margin-bottom:24px">
    <h2>جدول تحويل سريع (0–15)</h2>
    <div style="overflow-x:auto">
      <table style="width:100%;border-collapse:collapse;font-size:13px;font-family:monospace;direction:ltr;text-align:left">
        <thead><tr style="background:#f1f5f9">
          <th style="padding:8px 12px">DEC</th><th style="padding:8px 12px">BIN</th><th style="padding:8px 12px">OCT</th><th style="padding:8px 12px">HEX</th>
        </tr></thead>
        <tbody>
          <?php for ($i=0;$i<16;$i++): ?>
          <tr style="border-bottom:1px solid #f1f5f9;<?= $i%2?'background:#f8fafc':'' ?>">
            <td style="padding:7px 12px;font-weight:700"><?= $i ?></td>
            <td style="padding:7px 12px;color:#2563eb"><?= decbin($i) ?></td>
            <td style="padding:7px 12px;color:#059669"><?= decoct($i) ?></td>
            <td style="padding:7px 12px;color:#7c3aed"><?= strtoupper(dechex($i)) ?></td>
          </tr>
          <?php endfor; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card">
    <h2>شرح قواعد الأرقام</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;margin-top:10px">
      <div style="background:#dbeafe;border-radius:12px;padding:14px">
        <div style="font-weight:700;color:#1d4ed8;margin-bottom:6px">النظام العشري (Base-10)</div>
        <p style="font-size:12px;color:#1e40af;line-height:1.6">النظام الذي نستخدمه يومياً. يستخدم الأرقام 0-9. كل موضع يساوي 10 أضعاف الموضع الذي يليه.</p>
      </div>
      <div style="background:#dcfce7;border-radius:12px;padding:14px">
        <div style="font-weight:700;color:#166534;margin-bottom:6px">النظام الثنائي (Base-2)</div>
        <p style="font-size:12px;color:#14532d;line-height:1.6">لغة الحاسوب الأساسية. يستخدم رقمين فقط (0 و1). كل موضع يساوي ضعف الذي يليه.</p>
      </div>
      <div style="background:#ede9fe;border-radius:12px;padding:14px">
        <div style="font-weight:700;color:#5b21b6;margin-bottom:6px">النظام الست عشري (Base-16)</div>
        <p style="font-size:12px;color:#4c1d95;line-height:1.6">يستخدم في البرمجة وألوان CSS والعناوين. يستخدم 0-9 والأحرف A-F.</p>
      </div>
      <div style="background:#d1fae5;border-radius:12px;padding:14px">
        <div style="font-weight:700;color:#065f46;margin-bottom:6px">النظام الثماني (Base-8)</div>
        <p style="font-size:12px;color:#064e3b;line-height:1.6">يستخدم في أنظمة Unix/Linux لأذونات الملفات. يستخدم الأرقام 0-7 فقط.</p>
      </div>
    </div>
  </div>
</main>

<script>
const validators = { dec:/^-?\d+$/, bin:/^-?[01]+$/, oct:/^-?[0-7]+$/, hex:/^-?[0-9a-fA-F]+$/ };
const bases = { dec:10, bin:2, oct:8, hex:16 };

function convertFrom(src) {
  const val = document.getElementById(src).value.trim();
  const errEl = document.getElementById('error');
  if (!val) { errEl.style.display='none'; return; }
  if (!validators[src].test(val)) {
    errEl.style.display='block';
    errEl.textContent = 'القيمة تحتوي على أرقام غير صالحة لـ ' + src;
    return;
  }
  errEl.style.display = 'none';
  const dec = parseInt(val, bases[src]);
  if (isNaN(dec)) { errEl.style.display='block'; errEl.textContent='رقم غير صالح'; return; }
  if (src!=='dec') document.getElementById('dec').value = dec;
  if (src!=='bin') document.getElementById('bin').value = (dec>>>0).toString(2).toUpperCase();
  if (src!=='oct') document.getElementById('oct').value = (dec>>>0).toString(8);
  if (src!=='hex') document.getElementById('hex').value = (dec>>>0).toString(16).toUpperCase();
}

// Auto-convert on input
['dec','bin','oct','hex'].forEach(id => {
  document.getElementById(id).addEventListener('input', () => convertFrom(id));
});
</script>
<?php tool_footer(); ?>
