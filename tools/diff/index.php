<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'مقارنة النصوص — أداة Diff','url'=>TOOLS_BASE_URL.'/tools/diff/',
    'description'=>'قارن بين نصّين وابحث عن الاختلافات بينهما. أداة مقارنة نصوص مجانية تعمل في المتصفح بدون رفع ملفات.',
    'applicationCategory'=>'DeveloperApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('مقارنة النصوص Diff — ابحث عن الاختلافات بين نصّين | yassota','قارن بين نصّين أو ملفين بشكل مرئي سهل. أداة Diff عربية مجانية تُبرز الإضافات والحذف والتغييرات.',$schema,'#6366f1');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#eef2ff">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M9 11h6M9 15h6M9 7h3"/></svg>
    </div>
    <h1>مقارنة النصوص (Diff)</h1>
    <p>ضع نصّين واعثر على الفروقات بينهما — الإضافات باللون الأخضر والحذف باللون الأحمر.</p>
  </div>

  <div class="t-card" style="margin-bottom:16px">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
      <div>
        <label class="t-label">النص الأصلي (قبل)</label>
        <textarea id="text-a" class="t-input" rows="12" style="width:100%;font-family:monospace;font-size:13px;resize:vertical" placeholder="النص الأصلي هنا..."></textarea>
      </div>
      <div>
        <label class="t-label">النص الجديد (بعد)</label>
        <textarea id="text-b" class="t-input" rows="12" style="width:100%;font-family:monospace;font-size:13px;resize:vertical" placeholder="النص المعدَّل هنا..."></textarea>
      </div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
      <button id="diff-btn" class="t-btn" style="background:#6366f1">مقارنة الآن</button>
      <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
        <input type="checkbox" id="ignore-case"> تجاهل حالة الأحرف
      </label>
      <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
        <input type="checkbox" id="ignore-ws"> تجاهل المسافات الزائدة
      </label>
      <button id="clear-btn" style="margin-right:auto;background:none;border:1px solid #e2e8f0;border-radius:8px;padding:8px 14px;font-size:13px;cursor:pointer;color:#64748b">مسح</button>
    </div>
    <div id="stats" style="display:none;margin-top:12px;font-size:12px;color:#64748b;display:flex;gap:16px;flex-wrap:wrap"></div>
  </div>

  <div id="diff-result" style="display:none" class="t-card">
    <h3 style="margin-bottom:12px;display:flex;gap:16px">
      <span>نتيجة المقارنة</span>
      <span style="font-size:12px;font-weight:400;display:flex;gap:10px;align-items:center">
        <span style="background:#dcfce7;padding:2px 8px;border-radius:4px;color:#166534">+ إضافة</span>
        <span style="background:#fee2e2;padding:2px 8px;border-radius:4px;color:#991b1b">− حذف</span>
        <span style="background:#fef3c7;padding:2px 8px;border-radius:4px;color:#92400e">~ تعديل</span>
      </span>
    </h3>
    <div id="diff-output" style="font-family:monospace;font-size:13px;line-height:1.7;overflow-x:auto"></div>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card" style="margin-top:16px">
    <h2>متى تستخدم أداة مقارنة النصوص؟</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;margin-top:12px">
      <?php $uses = [
        ['👨‍💻','مقارنة الكود البرمجي','اكتشف التغييرات بين نسختين من ملف كود'],
        ['📝','مراجعة المقالات','قارن المسودة الأولى بالنسخة المحررة'],
        ['📜','العقود والوثائق','تتبع التعديلات على العقود القانونية'],
        ['🔍','اكتشاف الانتحال','قارن نصين لمعرفة نسبة التشابه'],
      ]; foreach ($uses as [$ic,$t,$d]): ?>
      <div style="background:#f8fafc;border-radius:10px;padding:12px">
        <div style="font-size:24px;margin-bottom:6px"><?= $ic ?></div>
        <div style="font-size:13px;font-weight:700;margin-bottom:4px"><?= $t ?></div>
        <div style="font-size:12px;color:#64748b"><?= $d ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</main>

<script>
document.getElementById('diff-btn').addEventListener('click', runDiff);
document.getElementById('clear-btn').addEventListener('click', () => {
  document.getElementById('text-a').value = '';
  document.getElementById('text-b').value = '';
  document.getElementById('diff-result').style.display = 'none';
  document.getElementById('stats').style.display = 'none';
});

function runDiff() {
  let a = document.getElementById('text-a').value;
  let b = document.getElementById('text-b').value;
  const ignCase = document.getElementById('ignore-case').checked;
  const ignWs   = document.getElementById('ignore-ws').checked;
  if (ignWs) { a = a.replace(/[^\S\n]+/g,' ').trim(); b = b.replace(/[^\S\n]+/g,' ').trim(); }
  const aLines = a.split('\n');
  const bLines = b.split('\n');
  const aCmp = aLines.map(l => ignCase ? l.toLowerCase() : l);
  const bCmp = bLines.map(l => ignCase ? l.toLowerCase() : l);

  // Simple LCS-based diff
  const lcs = computeLCS(aCmp, bCmp);
  const output = [];
  let ai=0, bi=0, li=0;
  while (ai < aLines.length || bi < bLines.length) {
    if (ai < aLines.length && bi < bLines.length && li < lcs.length && aCmp[ai] === lcs[li] && bCmp[bi] === lcs[li]) {
      output.push({type:'same', line:aLines[ai]});
      ai++; bi++; li++;
    } else if (bi < bLines.length && (li >= lcs.length || bCmp[bi] !== lcs[li])) {
      output.push({type:'add', line:bLines[bi]});
      bi++;
    } else {
      output.push({type:'del', line:aLines[ai]});
      ai++;
    }
  }

  let adds=0, dels=0, sames=0;
  output.forEach(o => { if(o.type==='add') adds++; else if(o.type==='del') dels++; else sames++; });

  const html = output.map((o,i) => {
    const esc = o.line.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    if (o.type==='add') return `<div style="background:#dcfce7;color:#166534;padding:2px 8px;border-right:3px solid #16a34a"><span style="opacity:.5;margin-left:8px">+</span>${esc}</div>`;
    if (o.type==='del') return `<div style="background:#fee2e2;color:#991b1b;padding:2px 8px;border-right:3px solid #dc2626"><span style="opacity:.5;margin-left:8px">−</span>${esc}</div>`;
    return `<div style="padding:2px 8px;color:#475569"><span style="opacity:.3;margin-left:8px"> </span>${esc}</div>`;
  }).join('');

  document.getElementById('diff-output').innerHTML = html || '<div style="color:#94a3b8">النصّان متطابقان</div>';
  document.getElementById('diff-result').style.display = 'block';
  const stats = document.getElementById('stats');
  stats.style.display = 'flex';
  stats.innerHTML = `<span style="color:#166534">✚ ${adds} سطر مضاف</span> | <span style="color:#991b1b">✕ ${dels} سطر محذوف</span> | <span>= ${sames} سطر مشترك</span>`;
}

function computeLCS(a, b) {
  const m=a.length, n=b.length;
  const dp = Array.from({length:m+1}, ()=>new Array(n+1).fill(0));
  for(let i=1;i<=m;i++) for(let j=1;j<=n;j++) dp[i][j]=a[i-1]===b[j-1]?dp[i-1][j-1]+1:Math.max(dp[i-1][j],dp[i][j-1]);
  const lcs=[];
  let i=m,j=n;
  while(i>0&&j>0){if(a[i-1]===b[j-1]){lcs.unshift(a[i-1]);i--;j--;}else if(dp[i-1][j]>dp[i][j-1])i--;else j--;}
  return lcs;
}
</script>
<?php tool_footer(); ?>
