<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'محوّل Markdown إلى HTML','url'=>TOOLS_BASE_URL.'/tools/markdown/',
    'description'=>'حوّل نص Markdown إلى HTML مباشرةً في المتصفح. أداة مجانية مع معاينة مباشرة وتنزيل الناتج.',
    'applicationCategory'=>'DeveloperApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('محوّل Markdown إلى HTML — معاينة مباشرة مجانية | yassota','حوّل كتاباتك من Markdown إلى HTML جاهز للنشر. معاينة مباشرة، نسخ الكود، تنزيل الملف — بدون خوادم.',$schema,'#0369a1');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#e0f2fe">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0369a1" stroke-width="2"><path d="M17 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V5a2 2 0 00-2-2z"/><path d="M9 7l2 2-2 2M13 11h2"/></svg>
    </div>
    <h1>محوّل Markdown إلى HTML</h1>
    <p>اكتب Markdown وشاهد النتيجة HTML مباشرةً — أداة مطوري الويب والكتّاب.</p>
  </div>

  <div class="t-card" style="margin-bottom:16px">
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
      <button onclick="setView('split')"  id="btn-split"  style="background:#0369a1;color:#fff;border:none;border-radius:8px;padding:8px 14px;font-size:13px;cursor:pointer">عمودان</button>
      <button onclick="setView('editor')" id="btn-editor" style="background:#f1f5f9;color:#475569;border:none;border-radius:8px;padding:8px 14px;font-size:13px;cursor:pointer">المحرر</button>
      <button onclick="setView('preview')"id="btn-preview"style="background:#f1f5f9;color:#475569;border:none;border-radius:8px;padding:8px 14px;font-size:13px;cursor:pointer">المعاينة</button>
      <div style="margin-right:auto;display:flex;gap:8px">
        <button onclick="copyHtml()" style="background:#e0f2fe;color:#0369a1;border:none;border-radius:8px;padding:8px 14px;font-size:13px;cursor:pointer">نسخ HTML</button>
        <button onclick="downloadHtml()" style="background:#e0f2fe;color:#0369a1;border:none;border-radius:8px;padding:8px 14px;font-size:13px;cursor:pointer">⬇ تنزيل</button>
        <button onclick="clearAll()" style="background:#fee2e2;color:#dc2626;border:none;border-radius:8px;padding:8px 14px;font-size:13px;cursor:pointer">مسح</button>
      </div>
    </div>

    <div id="editor-area" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div>
        <label class="t-label">Markdown</label>
        <textarea id="md-input" class="t-input" rows="20" style="width:100%;font-family:monospace;font-size:13px;line-height:1.7;resize:vertical;direction:ltr" placeholder="اكتب Markdown هنا..."></textarea>
      </div>
      <div>
        <label class="t-label">معاينة HTML</label>
        <div id="preview-pane" style="min-height:440px;border:1px solid #e2e8f0;border-radius:10px;padding:16px;overflow-y:auto;line-height:1.8;font-size:14px"></div>
      </div>
    </div>

    <div id="html-area" style="display:none;margin-top:12px">
      <label class="t-label">كود HTML الناتج</label>
      <textarea id="html-out" class="t-input" rows="10" dir="ltr" style="width:100%;font-family:monospace;font-size:12px;background:#f8fafc" readonly></textarea>
    </div>
    <div id="copy-msg" style="display:none;color:#059669;font-size:13px;margin-top:6px">✅ تم النسخ!</div>
  </div>

  <!-- Toolbar shortcuts -->
  <div class="t-card" style="margin-bottom:24px">
    <h2>أدوات Markdown السريعة</h2>
    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:10px">
      <?php $shortcuts=[
        ['**نص**','عريض (Bold)','**','**'],
        ['*نص*','مائل (Italic)','*','*'],
        ['~~نص~~','شطب','~~','~~'],
        ['`كود`','كود مضمّن','`','`'],
        ['# عنوان','H1','# ',''],
        ['## عنوان','H2','## ',''],
        ['### عنوان','H3','### ',''],
        ['[رابط](URL)','رابط','[','](https://)'],
        ['![صورة](URL)','صورة','![','](https://)'],
        ['> اقتباس','اقتباس','> ',''],
        ['- عنصر','قائمة','- ',''],
        ['---','خط فاصل','---',''],
      ]; foreach($shortcuts as [$lbl,$title,$pre,$suf]): ?>
      <button onclick="insertMd('<?= addslashes($pre) ?>','<?= addslashes($suf) ?>')"
        title="<?= $title ?>"
        style="background:#f0f9ff;color:#0369a1;border:1px solid #bae6fd;border-radius:6px;padding:6px 10px;font-size:12px;font-family:monospace;cursor:pointer">
        <?= htmlspecialchars($lbl) ?>
      </button>
      <?php endforeach; ?>
    </div>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card">
    <h2>مرجع سريع — صيغة Markdown</h2>
    <div style="overflow-x:auto">
      <table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead><tr style="background:#f0f9ff">
          <th style="padding:9px 12px;text-align:right">العنصر</th>
          <th style="padding:9px 12px;text-align:left">صيغة Markdown</th>
          <th style="padding:9px 12px;text-align:left">الناتج</th>
        </tr></thead>
        <tbody>
          <?php $ref=[
            ['عنوان رئيسي','# عنوان H1','<h1>'],
            ['عنوان فرعي','## عنوان H2','<h2>'],
            ['نص عريض','**نص عريض**','<strong>'],
            ['نص مائل','*نص مائل*','<em>'],
            ['كود مضمّن','`print("hello")`','<code>'],
            ['كتلة كود','``` py ... ```','<pre><code>'],
            ['رابط','[نص](https://url.com)','<a href>'],
            ['صورة','![وصف](image.jpg)','<img>'],
            ['قائمة غير مرتبة','- عنصر أو * عنصر','<ul><li>'],
            ['قائمة مرتبة','1. أول\n2. ثاني','<ol><li>'],
            ['اقتباس','> نص الاقتباس','<blockquote>'],
            ['خط فاصل','--- أو ***','<hr>'],
            ['جدول','| عمود1 | عمود2 |','<table>'],
          ]; foreach($ref as [$el,$md,$html]): ?>
          <tr style="border-bottom:1px solid #f1f5f9">
            <td style="padding:8px 12px;font-weight:600"><?= $el ?></td>
            <td style="padding:8px 12px;font-family:monospace;color:#0369a1;direction:ltr"><?= htmlspecialchars($md) ?></td>
            <td style="padding:8px 12px;font-family:monospace;color:#64748b;direction:ltr"><?= htmlspecialchars($html) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<script>
const sample = `# مرحباً بـ Markdown!

هذا **نص عريض** و*هذا نص مائل* و~~هذا مشطوب~~.

## الميزات الرئيسية

- سهل القراءة والكتابة
- يتحول إلى HTML نظيف
- مدعوم في GitHub وNotion وغيرهم

### مثال على الكود

\`\`\`javascript
function hello(name) {
  console.log('مرحبا ' + name);
}
\`\`\`

> "الكتابة الجيدة هي التفكير الواضح." — William Zinsser

### جدول بسيط

| العنصر | الوصف |
|--------|-------|
| Heading | عناوين H1–H6 |
| Bold | نص **عريض** |
| List | قائمة نقطية |

[رابط تجريبي](https://example.com) و![صورة تجريبية](https://via.placeholder.com/20)
`;

document.getElementById('md-input').value = sample;

function parseMarkdown(md) {
  let h = md
    // Escape HTML
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    // Code blocks (triple backtick)
    .replace(/```(\w*)\n([\s\S]*?)```/g, (_,lang,code)=>`<pre><code class="language-${lang}">${code.trim()}</code></pre>`)
    // Inline code
    .replace(/`([^`]+)`/g,'<code>$1</code>')
    // Headings
    .replace(/^######\s(.+)$/gm,'<h6>$1</h6>')
    .replace(/^#####\s(.+)$/gm,'<h5>$1</h5>')
    .replace(/^####\s(.+)$/gm,'<h4>$1</h4>')
    .replace(/^###\s(.+)$/gm,'<h3>$1</h3>')
    .replace(/^##\s(.+)$/gm,'<h2>$1</h2>')
    .replace(/^#\s(.+)$/gm,'<h1>$1</h1>')
    // Bold + italic
    .replace(/\*\*\*(.+?)\*\*\*/g,'<strong><em>$1</em></strong>')
    .replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>')
    .replace(/\*(.+?)\*/g,'<em>$1</em>')
    .replace(/~~(.+?)~~/g,'<del>$1</del>')
    // Blockquote
    .replace(/^>\s(.+)$/gm,'<blockquote>$1</blockquote>')
    // HR
    .replace(/^(---|\*\*\*|___)$/gm,'<hr>')
    // Images before links
    .replace(/!\[([^\]]*)\]\(([^)]+)\)/g,'<img src="$2" alt="$1" style="max-width:100%">')
    // Links
    .replace(/\[([^\]]+)\]\(([^)]+)\)/g,'<a href="$2" target="_blank" rel="noopener">$1</a>');

  // Tables
  h = h.replace(/(\|.+\|\n\|[-| :]+\|\n(?:\|.+\|\n?)*)/g, tableBlock => {
    const rows = tableBlock.trim().split('\n');
    const headers = rows[0].split('|').filter(c=>c.trim()).map(c=>`<th style="padding:8px 12px;border:1px solid #e2e8f0">${c.trim()}</th>`).join('');
    const body = rows.slice(2).map(r=>{
      const cells = r.split('|').filter(c=>c.trim()).map(c=>`<td style="padding:8px 12px;border:1px solid #e2e8f0">${c.trim()}</td>`).join('');
      return `<tr>${cells}</tr>`;
    }).join('');
    return `<table style="border-collapse:collapse;width:100%;margin:12px 0"><thead><tr style="background:#f1f5f9">${headers}</tr></thead><tbody>${body}</tbody></table>`;
  });

  // Unordered lists
  h = h.replace(/((?:^[*\-+]\s.+\n?)+)/gm, block => {
    const items = block.trim().split('\n').map(l=>`<li>${l.replace(/^[*\-+]\s/,'')}</li>`).join('');
    return `<ul style="padding-right:20px;line-height:2">${items}</ul>`;
  });
  // Ordered lists
  h = h.replace(/((?:^\d+\.\s.+\n?)+)/gm, block => {
    const items = block.trim().split('\n').map(l=>`<li>${l.replace(/^\d+\.\s/,'')}</li>`).join('');
    return `<ol style="padding-right:20px;line-height:2">${items}</ol>`;
  });

  // Paragraphs
  h = h.replace(/\n\n([^<\n].+)/g,'\n\n<p>$1</p>');
  h = h.replace(/^([^<\n].+)/gm,'<p>$1</p>');
  h = h.replace(/<\/p>\n<p>/g,'</p>\n<p>');

  // Unwrap wrong p tags around block elements
  h = h.replace(/<p>(<(?:h[1-6]|ul|ol|table|pre|blockquote|hr)[^>]*>)/g,'$1');
  h = h.replace(/(<\/(?:h[1-6]|ul|ol|table|pre|blockquote|hr)>)<\/p>/g,'$1');

  return h;
}

const style = `<style>
body{font-family:sans-serif;line-height:1.7;color:#1e293b}
h1,h2,h3,h4,h5,h6{color:#0f172a;margin:16px 0 8px}
h1{font-size:1.8em;border-bottom:2px solid #e2e8f0;padding-bottom:8px}
h2{font-size:1.4em;border-bottom:1px solid #f1f5f9;padding-bottom:6px}
code{background:#f1f5f9;padding:2px 6px;border-radius:4px;font-size:.9em;direction:ltr;display:inline-block}
pre{background:#1e293b;color:#e2e8f0;border-radius:10px;padding:16px;overflow-x:auto;direction:ltr}
pre code{background:none;color:inherit;padding:0}
blockquote{border-right:4px solid #0369a1;background:#f0f9ff;padding:12px 16px;margin:12px 0;border-radius:0 8px 8px 0;color:#0369a1}
hr{border:none;border-top:2px solid #f1f5f9;margin:16px 0}
a{color:#0369a1}
img{border-radius:8px;margin:8px 0}
</style>`;

function render() {
  const md = document.getElementById('md-input').value;
  const html = parseMarkdown(md);
  document.getElementById('preview-pane').innerHTML = style + html;
  document.getElementById('html-out').value = html;
}

document.getElementById('md-input').addEventListener('input', render);

function setView(v) {
  const area = document.getElementById('editor-area');
  const htmlArea = document.getElementById('html-area');
  ['split','editor','preview'].forEach(x=>{
    const b=document.getElementById('btn-'+x);
    b.style.background=x===v?'#0369a1':'#f1f5f9';
    b.style.color=x===v?'#fff':'#475569';
  });
  if(v==='split'){
    area.style.gridTemplateColumns='1fr 1fr';
    area.children[0].style.display='block';
    area.children[1].style.display='block';
    htmlArea.style.display='none';
  } else if(v==='editor'){
    area.style.gridTemplateColumns='1fr';
    area.children[0].style.display='block';
    area.children[1].style.display='none';
    htmlArea.style.display='block';
  } else {
    area.style.gridTemplateColumns='1fr';
    area.children[0].style.display='none';
    area.children[1].style.display='block';
    htmlArea.style.display='none';
  }
}

function insertMd(pre, suf) {
  const ta = document.getElementById('md-input');
  const start = ta.selectionStart, end = ta.selectionEnd;
  const sel = ta.value.substring(start, end) || 'نص';
  ta.value = ta.value.substring(0,start) + pre + sel + suf + ta.value.substring(end);
  ta.focus();
  ta.selectionStart = start + pre.length;
  ta.selectionEnd = start + pre.length + sel.length;
  render();
}

function copyHtml() {
  navigator.clipboard.writeText(document.getElementById('html-out').value);
  const m=document.getElementById('copy-msg'); m.style.display='block';
  setTimeout(()=>m.style.display='none',2000);
}

function downloadHtml() {
  const full = '<!DOCTYPE html>\n<html lang="ar" dir="rtl">\n<head><meta charset="UTF-8"><title>Markdown Output</title>'+style+'</head>\n<body>\n'+document.getElementById('html-out').value+'\n</body>\n</html>';
  const a=document.createElement('a'); a.href=URL.createObjectURL(new Blob([full],{type:'text/html'}));
  a.download='markdown-output.html'; a.click();
}

function clearAll() {
  document.getElementById('md-input').value='';
  document.getElementById('preview-pane').innerHTML='';
  document.getElementById('html-out').value='';
}

render();
</script>
<?php tool_footer(); ?>
