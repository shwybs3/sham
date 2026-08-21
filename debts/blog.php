<?php
require_once __DIR__ . '/config.php';

$pageTitle = 'المدونة';

try {
    $articles = $pdo->query(
        "SELECT id, title, slug, description, tags, created_at FROM articles WHERE status='published' ORDER BY created_at DESC"
    )->fetchAll();
} catch (PDOException $e) {
    $articles = [];
}

require __DIR__ . '/includes/page-header.php';
?>

<style>
.blog-grid{
  max-width:820px; margin:0 auto;
}
.blog-header{
  text-align:center; padding:40px 0 36px;
  border-bottom:1px solid var(--line); margin-bottom:36px;
}
.blog-header h1{
  font-family:var(--disp); font-size:clamp(26px,4vw,38px);
  margin:0 0 10px; background:linear-gradient(135deg,var(--ink),var(--brand));
  -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;
}
.blog-header p{color:var(--ink-dim); font-size:15px; margin:0;}
.blog-search{
  max-width:420px; margin:0 auto 36px; position:relative;
}
.blog-search input{
  width:100%; box-sizing:border-box; padding:12px 44px 12px 14px;
  border-radius:14px; font-size:14.5px;
}
.blog-search .icon{ position:absolute; inset-inline-end:14px; top:50%; transform:translateY(-50%); color:var(--ink-faint); pointer-events:none; }
.article-card{
  background:var(--panel); border:1px solid var(--line); border-radius:var(--radius);
  padding:24px; margin-bottom:16px; text-decoration:none; display:block;
  transition:border-color .2s, transform .15s;
}
.article-card:hover{border-color:var(--brand); transform:translateY(-2px);}
.article-card h2{
  font-family:var(--disp); font-size:18px; margin:0 0 8px; color:var(--ink);
  line-height:1.4;
}
.article-card p{
  color:var(--ink-dim); font-size:14px; margin:0 0 14px; line-height:1.65;
  display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
}
.article-meta{
  display:flex; align-items:center; gap:14px; flex-wrap:wrap;
  font-size:12.5px; color:var(--ink-faint);
}
.article-meta .date{display:flex; align-items:center; gap:5px;}
.tag-list{display:flex; flex-wrap:wrap; gap:6px;}
.tag{
  padding:3px 10px; border-radius:20px; font-size:11.5px; font-weight:600;
  background:rgba(34,197,142,.1); color:var(--brand); border:1px solid rgba(34,197,142,.2);
}
.no-articles{
  text-align:center; padding:60px 0; color:var(--ink-faint);
}
</style>

<div class="blog-grid">
  <div class="blog-header">
    <h1><?= icon('receipt', 26) ?> المدونة</h1>
    <p>مقالات ونصائح مفيدة لأصحاب الدكاكين</p>
  </div>

  <?php if ($articles): ?>
  <div class="blog-search">
    <input type="text" id="blog-search" placeholder="ابحث في المقالات..." oninput="filterArticles(this.value)">
    <span class="icon"><?= icon('search', 16) ?></span>
  </div>
  <?php endif; ?>

  <div id="articles-list">
    <?php if (!$articles): ?>
      <div class="no-articles">
        <?= icon('receipt', 40) ?>
        <p style="margin-top:16px; font-size:15px;">لا توجد مقالات منشورة بعد.</p>
      </div>
    <?php else: ?>
      <?php foreach ($articles as $a):
        $tags = array_filter(array_map('trim', explode(',', $a['tags'] ?? '')));
      ?>
      <a class="article-card" href="article.php?slug=<?= urlencode($a['slug']) ?>" data-title="<?= h(mb_strtolower($a['title'],'UTF-8')) ?>" data-tags="<?= h(mb_strtolower($a['tags'] ?? '','UTF-8')) ?>">
        <h2><?= h($a['title']) ?></h2>
        <?php if ($a['description']): ?>
        <p><?= h($a['description']) ?></p>
        <?php endif; ?>
        <div class="article-meta">
          <span class="date"><?= icon('calendar', 12) ?> <?= date('d / m / Y', strtotime($a['created_at'])) ?></span>
          <?php if ($tags): ?>
          <div class="tag-list">
            <?php foreach (array_slice($tags, 0, 4) as $tag): ?>
            <span class="tag"><?= h($tag) ?></span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<script>
function filterArticles(q){
  q = q.toLowerCase().trim();
  document.querySelectorAll('.article-card').forEach(el => {
    const match = !q || el.dataset.title.includes(q) || el.dataset.tags.includes(q);
    el.style.display = match ? '' : 'none';
  });
}
</script>

<?php require __DIR__ . '/includes/page-footer.php'; ?>
