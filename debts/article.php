<?php
require_once __DIR__ . '/config.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') { header('Location: blog.php'); exit; }

try {
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE slug=? AND status='published'");
    $stmt->execute([$slug]);
    $article = $stmt->fetch();
} catch (PDOException $e) {
    $article = null;
}

if (!$article) {
    http_response_code(404);
    $pageTitle = 'المقال غير موجود';
    require __DIR__ . '/includes/page-header.php';
    echo '<div style="text-align:center; padding:80px 20px;">';
    echo icon('alert', 40);
    echo '<p style="margin-top:20px; font-size:16px; color:var(--ink-dim);">هذا المقال غير موجود أو تمت إزالته.</p>';
    echo '<a href="blog.php" class="btn btn-ghost" style="margin-top:16px;">العودة للمدونة</a>';
    echo '</div>';
    require __DIR__ . '/includes/page-footer.php';
    exit;
}

$pageTitle = $article['title'];
$tags = array_filter(array_map('trim', explode(',', $article['tags'] ?? '')));

$siteUrl = rtrim($settings['site_url'] ?? (
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\')
), '/');
$canonicalUrl = $siteUrl . '/article.php?slug=' . rawurlencode($slug);

require __DIR__ . '/includes/page-header.php';
?>

<style>
.article-wrap{
  max-width:740px; margin:0 auto; padding-bottom:60px;
}
.article-back{
  margin-bottom:28px;
}
.article-title{
  font-family:var(--disp); font-size:clamp(22px,4vw,34px);
  line-height:1.35; margin:0 0 14px; color:var(--ink);
}
.article-meta{
  display:flex; align-items:center; flex-wrap:wrap; gap:12px;
  font-size:13px; color:var(--ink-faint); margin-bottom:20px;
  padding-bottom:20px; border-bottom:1px solid var(--line);
}
.article-tags{display:flex; flex-wrap:wrap; gap:6px; margin-bottom:28px;}
.tag{
  padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600;
  background:rgba(34,197,142,.1); color:var(--brand); border:1px solid rgba(34,197,142,.2);
  text-decoration:none;
}
.tag:hover{background:rgba(34,197,142,.2);}
.article-body{
  font-size:15.5px; line-height:1.85; color:var(--ink);
}
.article-body h1,.article-body h2,.article-body h3{
  font-family:var(--disp); margin-top:1.8em; margin-bottom:.6em; color:var(--ink);
}
.article-body h1{font-size:1.6em;}
.article-body h2{font-size:1.35em; border-bottom:1px solid var(--line); padding-bottom:8px;}
.article-body h3{font-size:1.15em;}
.article-body p{margin:0 0 1.2em;}
.article-body ul,.article-body ol{padding-inline-start:1.6em; margin:0 0 1.2em;}
.article-body li{margin-bottom:.4em;}
.article-body blockquote{
  border-inline-start:4px solid var(--brand); padding:.6em 1.2em;
  margin:1.4em 0; background:rgba(34,197,142,.06); border-radius:0 8px 8px 0;
  color:var(--ink-dim);
}
.article-body code{
  font-family:var(--mono); font-size:.88em; background:var(--panel-2);
  padding:2px 7px; border-radius:5px; border:1px solid var(--line);
}
.article-body pre{
  background:var(--panel-2); border:1px solid var(--line); border-radius:10px;
  padding:16px; overflow-x:auto; margin:1.2em 0;
}
.article-body pre code{background:none; border:none; padding:0; font-size:.87em;}
.article-body img{max-width:100%; border-radius:10px; margin:1em 0;}
.article-body a{color:var(--brand); text-decoration:underline;}
.article-body table{width:100%; border-collapse:collapse; margin:1.2em 0; font-size:14px;}
.article-body th{background:var(--panel-2); padding:10px 12px; border:1px solid var(--line); text-align:right;}
.article-body td{padding:10px 12px; border:1px solid var(--line);}
.article-body hr{border:none; border-top:1px solid var(--line); margin:2em 0;}
.article-share{
  margin-top:40px; padding-top:24px; border-top:1px solid var(--line);
  display:flex; align-items:center; gap:12px; flex-wrap:wrap;
}
.share-btn{
  display:inline-flex; align-items:center; gap:7px; padding:9px 16px;
  border-radius:10px; font-size:13.5px; font-weight:600; cursor:pointer;
  border:1px solid var(--line); background:var(--panel-2); color:var(--ink-dim);
  transition:.15s; text-decoration:none;
}
.share-btn:hover{border-color:var(--brand); color:var(--brand);}
</style>

<div class="article-wrap">
  <div class="article-back">
    <a href="blog.php" class="btn btn-ghost btn-sm"><?= icon('arrow-right', 15) ?> المدونة</a>
  </div>

  <h1 class="article-title"><?= h($article['title']) ?></h1>

  <div class="article-meta">
    <span><?= icon('calendar', 13) ?> <?= date('d / m / Y', strtotime($article['created_at'])) ?></span>
    <?php if ($article['updated_at'] && $article['updated_at'] !== $article['created_at']): ?>
    <span><?= icon('edit', 13) ?> آخر تعديل: <?= date('d / m / Y', strtotime($article['updated_at'])) ?></span>
    <?php endif; ?>
  </div>

  <?php if ($tags): ?>
  <div class="article-tags">
    <?php foreach ($tags as $tag): ?>
    <a href="blog.php" class="tag"><?= h($tag) ?></a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="article-body">
    <?= $article['content'] ?>
  </div>

  <div class="article-share">
    <span style="font-size:13px; color:var(--ink-dim); font-weight:600;">مشاركة:</span>
    <button class="share-btn" onclick="copyLink()"><?= icon('external', 15) ?> نسخ الرابط</button>
  </div>
</div>

<script>
function copyLink(){
  navigator.clipboard.writeText(<?= json_encode($canonicalUrl) ?>).then(function(){
    const btn = document.querySelector('.share-btn');
    btn.textContent = '✓ تم النسخ';
    setTimeout(() => { btn.innerHTML = '<?= icon('external', 15) ?> نسخ الرابط'; }, 2000);
  });
}
</script>

<?php require __DIR__ . '/includes/page-footer.php'; ?>
