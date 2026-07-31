<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') { header('Location: ' . url('blog.php')); exit; }

$stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE slug=? AND status='published'");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="' . (defined('UI_LANG') ? UI_LANG : 'ar') . '" dir="' . (defined('UI_DIR') ? UI_DIR : 'rtl') . '"><head><meta charset="UTF-8"><title>404</title>
    <link rel="stylesheet" href="assets/css/main.css"></head><body style="display:flex;align-items:center;justify-content:center;height:100vh;flex-direction:column;gap:16px">
    <p style="font-size:64px;font-family:var(--f-mono);color:var(--cyan)">404</p>
    <p style="color:var(--muted)">المقال غير موجود</p>
    <a href="/" style="color:var(--cyan)">العودة للرئيسية</a></body></html>';
    exit;
}
if (page_cache_start($pdo, $_SERVER['REQUEST_URI'])) exit;

// Detect code-page type — body is JSON with language sections
$isCodePage  = $post['type'] === 'code-page';
$cpSections  = $isCodePage ? decode_code_page($post['body'] ?? '') : [];
// Priority order for code-page sections
$cpLangOrder = ['html','css','js','php','python','java','kotlin','cpp'];
// Body is stored as HTML from the WYSIWYG editor (admin-only authoring,
// so raw HTML output is intentional and safe — no public user input here).
$body = $isCodePage ? '' : trim($post['body'] ?? '');
$cpDescription = $isCodePage ? (json_decode($post['body'] ?? '{}', true)['description'] ?? '') : '';
$seoTitle = $post['seo_title'] ?: $post['title'];
$metaDesc = $post['meta_description'] ?: ($post['excerpt'] ?: mb_substr(trim(strip_tags($body)), 0, 160));

// A few apps mentioned by name in the post title/excerpt, for a natural
// "related apps" block linking back to download pages — same internal-
// linking pattern as app_articles.
$related = $pdo->prepare("SELECT id,name,slug,icon_path,rating FROM apps WHERE status='published' ORDER BY downloads DESC LIMIT 6");
$related->execute();
$relatedApps = $related->fetchAll();

$otherStmt = $pdo->prepare("SELECT id,title,slug,cover_image FROM blog_posts WHERE type=? AND id!=? AND status='published' ORDER BY created_at DESC LIMIT 4");
$otherStmt->execute([$post['type'], $post['id']]);
$otherPosts = $otherStmt->fetchAll();

$breadcrumbSchema = json_encode([
    "@context" => "https://schema.org", "@type" => "BreadcrumbList",
    "itemListElement" => [
        ["@type" => "ListItem", "position" => 1, "name" => "الرئيسية", "item" => url('')],
        ["@type" => "ListItem", "position" => 2, "name" => blog_type_label($post['type']), "item" => blog_type_url($post['type'])],
        ["@type" => "ListItem", "position" => 3, "name" => $post['title'], "item" => blog_post_url($post['slug'])],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$articleSchema = json_encode(array_filter([
    "@context" => "https://schema.org", "@type" => "Article",
    "headline" => $post['title'],
    "description" => $metaDesc,
    "image" => $post['cover_image'] ? url($post['cover_image']) : null,
    "datePublished" => date('c', strtotime($post['created_at'])),
    "dateModified" => date('c', strtotime($post['updated_at'])),
    "author" => ["@type" => "Organization", "name" => "yassota"],
    "publisher" => ["@type" => "Organization", "name" => "yassota"],
    "mainEntityOfPage" => blog_post_url($post['slug']),
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<html lang="<?= defined('UI_LANG') ? UI_LANG : 'ar' ?>" dir="<?= defined('UI_DIR') ? UI_DIR : 'rtl' ?>">
<head>
  <?= nav_guard_script() ?>
  <meta charset="UTF-8">
  <?= head_extras($pdo) ?>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title><?= h($seoTitle) ?></title>
  <meta name="description" content="<?= h($metaDesc) ?>">
  <?php if ($post['keywords']): ?><meta name="keywords" content="<?= h($post['keywords']) ?>"><?php endif; ?>
  <link rel="canonical" href="<?= h(blog_post_url($post['slug'])) ?>">
  <meta property="og:type" content="article">
  <meta property="og:title" content="<?= h($seoTitle) ?>">
  <meta property="og:description" content="<?= h($metaDesc) ?>">
  <?php if ($post['cover_image']): ?><meta property="og:image" content="<?= h(url($post['cover_image'])) ?>"><?php endif; ?>
  <meta name="twitter:card" content="summary_large_image">
  <script type="application/ld+json"><?= $breadcrumbSchema ?></script>
  <script type="application/ld+json"><?= $articleSchema ?></script>
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/main.css')) ?>">
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5506877998492189"
     crossorigin="anonymous"></script>
</head>
<body>

<?php render_site_header(); ?>

<div class="page-wrap fw">

<main class="main-content">

  <nav style="font-size:12px;color:var(--muted);margin-bottom:16px;display:flex;gap:6px;align-items:center;flex-wrap:wrap">
    <a href="/" style="color:var(--cyan)">الرئيسية</a>
    <span>/</span>
    <a href="<?= h(blog_type_url($post['type'])) ?>" style="color:var(--cyan)"><?= h(blog_type_label($post['type'])) ?></a>
    <span>/</span>
    <span><?= h($post['title']) ?></span>
  </nav>

  <?php if ($isCodePage): ?>
  <!-- ── Code-page hero ── -->
  <div class="cp-page-hero reveal">
    <div class="cp-page-hero-title"><?= h($post['title']) ?></div>
    <?php if ($cpDescription): ?><div class="cp-page-hero-desc"><?= h($cpDescription) ?></div><?php endif; ?>
  </div>

  <!-- Language navigation pills -->
  <?php $cpFilled = array_filter($cpLangOrder, fn($l) => !empty($cpSections[$l])); ?>
  <?php if ($cpFilled): ?>
  <nav class="cp-page-nav reveal" style="justify-content:space-between;align-items:center">
    <div style="display:flex;flex-wrap:wrap;gap:8px">
      <?php foreach ($cpFilled as $lang):
          $meta = CODE_PAGE_LANGS[$lang];
      ?>
      <a href="#cp-<?= $lang ?>" class="cp-nav-pill" style="--pill-color:<?= h($meta['color']) ?>">
        <?= $meta['icon'] ?> <?= h($meta['label']) ?>
      </a>
      <?php endforeach; ?>
    </div>
    <button type="button" class="cp-copy-btn" onclick="cpDownloadAll()" style="margin-inline-start:auto;white-space:nowrap">
      ⬇️ تحميل الكل
    </button>
  </nav>
  <?php endif; ?>

  <!-- Code sections -->
  <?php foreach ($cpLangOrder as $lang):
      if (empty($cpSections[$lang])) continue;
      $meta  = CODE_PAGE_LANGS[$lang];
      $code  = $cpSections[$lang];
      $lines = explode("\n", $code);
      $lineCount = count($lines);
  ?>
  <div class="cp-block reveal" id="cp-<?= $lang ?>">
    <div class="cp-block-header" onclick="cpPubToggle('<?= $lang ?>')">
      <span class="cp-block-badge" style="--lang-color:<?= h($meta['color']) ?>"><?= $meta['icon'] ?> <?= h($meta['label']) ?></span>
      <div class="cp-block-meta">
        <span><?= $lineCount ?> سطر</span>
        <button type="button" class="cp-copy-btn" id="cpb-copy-<?= $lang ?>"
                onclick="event.stopPropagation();cpPubCopy('<?= $lang ?>')">📋 نسخ</button>
        <button type="button" class="cp-copy-btn"
                onclick="event.stopPropagation();cpDownload('<?= $lang ?>','<?= h($meta['ext']) ?>')"
                title="تحميل كملف">⬇️ تحميل</button>
        <button type="button" class="cp-block-toggle open" id="cpb-tog-<?= $lang ?>">▼</button>
      </div>
    </div>
    <div class="cp-code-wrap" id="cpb-wrap-<?= $lang ?>">
      <div class="cp-code-inner">
        <div class="cp-line-nums" aria-hidden="true"><?php
          for ($n = 1; $n <= $lineCount; $n++) echo $n . "\n";
        ?></div>
        <pre class="cp-code-content" id="cpb-code-<?= $lang ?>"><?= h($code) ?></pre>
      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- Hidden raw code stores for copy -->
  <?php foreach ($cpFilled as $lang): ?>
  <textarea id="cpraw-<?= $lang ?>" style="display:none" aria-hidden="true"><?= h($cpSections[$lang]) ?></textarea>
  <?php endforeach; ?>

  <?php else: ?>
  <!-- ── Regular article (WYSIWYG HTML body) ── -->
  <?php if ($post['cover_image']): ?>
  <img src="<?= h(url($post['cover_image'])) ?>" alt="<?= h($post['title']) ?>" style="width:100%;max-height:340px;object-fit:cover;border-radius:var(--radius-lg);margin-bottom:20px">
  <?php endif; ?>

  <div class="section-head reveal">
    <span class="section-title"><?= h($post['title']) ?></span>
  </div>

  <div class="section-box" style="margin-bottom:20px">
    <div class="blog-body"><?= render_blog_body($body) ?></div>
  </div>
  <?php endif; ?>

  <?php if ($relatedApps): ?>
  <div class="section-head reveal"><span class="section-title">تطبيقات وألعاب قد تعجبك</span></div>
  <div class="apps-grid" style="margin-bottom:20px">
    <?php foreach ($relatedApps as $r): render_app_card($r); endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($otherPosts): ?>
  <div class="section-head reveal"><span class="section-title">مقالات أخرى في <?= h(blog_type_label($post['type'])) ?></span></div>
  <div class="blog-grid" style="margin-bottom:20px">
    <?php foreach ($otherPosts as $op): ?>
    <a href="<?= h(blog_post_url($op['slug'])) ?>" class="blog-card reveal" data-hardnav="1">
      <?php if ($op['cover_image']): ?>
        <img src="<?= h(url($op['cover_image'])) ?>" alt="<?= h($op['title']) ?>" class="blog-card-img" loading="lazy">
      <?php else: ?>
        <div class="blog-card-img blog-card-img-placeholder"><?= partial_icon('article') ?></div>
      <?php endif; ?>
      <div class="blog-card-body"><div class="blog-card-title"><?= h($op['title']) ?></div></div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</main>
</div>

<?php render_site_footer(); ?>
<script src="<?= h(asset_url('assets/js/main.js')) ?>"></script>
<?php if ($isCodePage): ?>
<script>
function cpPubToggle(lang) {
  const wrap = document.getElementById('cpb-wrap-' + lang);
  const tog  = document.getElementById('cpb-tog-' + lang);
  const blk  = document.getElementById('cp-' + lang);
  if (!wrap) return;
  const open = wrap.style.display !== 'none';
  wrap.style.display = open ? 'none' : '';
  if (tog) tog.classList.toggle('open', !open);
  if (blk) blk.classList.toggle('cp-collapsed', open);
}
function cpPubCopy(lang) {
  const raw = document.getElementById('cpraw-' + lang);
  const btn = document.getElementById('cpb-copy-' + lang);
  if (!raw) return;
  navigator.clipboard.writeText(raw.value).then(() => {
    if (btn) { btn.textContent = '✓ تم النسخ'; btn.classList.add('copied');
      setTimeout(() => { btn.textContent = '📋 نسخ'; btn.classList.remove('copied'); }, 2000); }
  });
}
// Smooth scroll for nav pills
document.querySelectorAll('.cp-nav-pill').forEach(a => {
  a.addEventListener('click', e => {
    e.preventDefault();
    const target = document.querySelector(a.getAttribute('href'));
    if (target) { target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    document.querySelectorAll('.cp-nav-pill').forEach(p => p.classList.remove('active'));
    a.classList.add('active');
  });
});
// Highlight active pill on scroll
const cpBlocks = document.querySelectorAll('.cp-block[id]');
const cpPills  = document.querySelectorAll('.cp-nav-pill');
const cpObs = new IntersectionObserver(entries => {
  entries.forEach(en => {
    if (en.isIntersecting) {
      const id = en.target.id;
      cpPills.forEach(p => p.classList.toggle('active', p.getAttribute('href') === '#' + id));
    }
  });
}, { threshold: 0.4 });
cpBlocks.forEach(b => cpObs.observe(b));

// Download a single code section as a file
function cpDownload(lang, ext) {
  const raw = document.getElementById('cpraw-' + lang);
  if (!raw || !raw.value) return;
  const blob = new Blob([raw.value], { type: 'text/plain;charset=utf-8' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href = url; a.download = lang + ext; a.click();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}

// Basic syntax highlighting with placeholder approach to prevent tag conflicts
(function applySyntaxHL() {
  const e = s => s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  const tokens = [];
  const save   = (color, raw) => { const k = `\x02${tokens.length}\x03`; tokens.push(`<span style="color:${color}">${e(raw)}</span>`); return k; };

  document.querySelectorAll('.cp-code-content').forEach(pre => {
    tokens.length = 0;
    let code = pre.textContent;
    // 1) block comments
    code = code.replace(/\/\*[\s\S]*?\*\//g, m => save('#475569', m));
    // 2) single-line comments (// and #)
    code = code.replace(/\/\/[^\n]*/g, m => save('#64748b', m));
    code = code.replace(/#(?![0-9a-fA-F]{3,6}\b)[^\n]*/g, m => save('#64748b', m));
    // 3) strings (double, single, backtick)
    code = code.replace(/(["'`])(?:(?!\1)[^\\]|\\.)*?\1/g, m => save('#86efac', m));
    // 4) escape remaining for HTML output
    code = e(code);
    // 5) numbers
    code = code.replace(/\b(\d+(?:\.\d+)?(?:[eE][+-]?\d+)?)\b/g, '<span style="color:#fb923c">$1</span>');
    // 6) keywords
    const kw = /\b(function|return|const|let|var|if|else|for|while|do|switch|case|break|continue|class|import|export|default|from|new|this|try|catch|finally|throw|async|await|typeof|instanceof|in|of|delete|void|def|print|lambda|elif|True|False|None|with|yield|pass|raise|assert|global|nonlocal|public|private|protected|static|void|int|long|double|float|char|boolean|string|bool|echo|require|require_once|include|include_once|foreach|fn|use|namespace|extends|implements|abstract|interface|trait|final|parent|self|null|true|false|undefined|fun|val|var|when|data|sealed|object|open|companion|override|constructor|init|typealias|is|as|in|out|get|set|by)\b/g;
    code = code.replace(kw, '<span style="color:#60a5fa">$1</span>');
    // 7) restore saved tokens
    code = code.replace(/\x02(\d+)\x03/g, (_, i) => tokens[i]);
    pre.innerHTML = code;
  });
})();

// Download all filled sections as separate files sequentially
function cpDownloadAll() {
  const langs = <?= json_encode(array_values($cpFilled)) ?>;
  const exts  = <?= json_encode(array_combine(
      array_values($cpFilled),
      array_map(fn($l) => CODE_PAGE_LANGS[$l]['ext'], array_values($cpFilled))
  )) ?>;
  let i = 0;
  function next() {
    if (i >= langs.length) return;
    const lang = langs[i++];
    cpDownload(lang, exts[lang]);
    setTimeout(next, 400);
  }
  next();
}
</script>
<?php endif; ?>
</body>
</html>
<?php page_cache_end($pdo, $_SERVER['REQUEST_URI']); ?>
