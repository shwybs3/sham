<?php
$slug = $extra ?? '';
$art  = null;
$pdo  = db();

if ($pdo && $slug) {
  $stmt = $pdo->prepare("SELECT * FROM articles WHERE slug=? AND status='published' LIMIT 1");
  $stmt->execute([$slug]);
  $art = $stmt->fetch();
}

if (!$art) {
  http_response_code(404);
  echo '<div class="container" style="padding:4rem 1rem;text-align:center"><h1>404 — المقالة غير موجودة</h1><p><a href="/articles">العودة للمقالات</a></p></div>';
  return;
}

// Update page meta from article data
$page_title = h($art['title']) . ' — ' . SITE_NAME;
$page_desc  = h(mb_substr(strip_tags($art['excerpt'] ?? ''), 0, 160));
$page_canonical = base_url('article/' . $art['slug']);

// Related articles
$related = [];
if ($pdo && $art['category']) {
  $stmt2 = $pdo->prepare("SELECT id,slug,title,thumb,created_at FROM articles WHERE status='published' AND category=? AND id!=? ORDER BY RAND() LIMIT 3");
  $stmt2->execute([$art['category'], $art['id']]);
  $related = $stmt2->fetchAll();
}
?>

<section class="page-hero small">
  <div class="container">
    <nav class="breadcrumb">
      <a href="/">الرئيسية</a> <span>›</span>
      <a href="/articles">المقالات</a> <span>›</span>
      <?php if ($art['category']): ?><a href="/articles?cat=<?= urlencode($art['category']) ?>"><?= h($art['category']) ?></a> <span>›</span><?php endif; ?>
      <span><?= h(mb_substr($art['title'], 0, 40)) ?></span>
    </nav>
    <h1><?= h($art['title']) ?></h1>
    <p style="color:rgba(255,255,255,.65);font-size:13px"><?= h(date('d MMMM Y', strtotime($art['created_at']))) ?></p>
  </div>
</section>

<div class="container page-body" style="max-width:860px">

  <article class="card" aria-label="<?= h($art['title']) ?>">
    <?php if ($art['thumb']): ?>
    <img src="<?= h($art['thumb']) ?>" alt="<?= h($art['title']) ?>" style="width:100%;border-radius:var(--radius-lg);margin-bottom:1.5rem;max-height:400px;object-fit:cover" loading="eager">
    <?php endif; ?>

    <?php if ($art['excerpt']): ?>
    <p style="font-size:1.05rem;color:var(--c-text2);border-right:3px solid var(--c-gold);padding-right:1rem;margin-bottom:1.5rem;line-height:1.7"><?= h($art['excerpt']) ?></p>
    <?php endif; ?>

    <div class="prose article-body" style="line-height:2">
      <?= $art['content'] ?? '' ?>
    </div>

    <div style="margin-top:2rem;padding-top:1.25rem;border-top:1px solid var(--c-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem">
      <?php if ($art['category']): ?>
      <a href="/articles?cat=<?= urlencode($art['category']) ?>" class="filter-btn active"><?= h($art['category']) ?></a>
      <?php endif; ?>
      <span style="font-size:12px;color:var(--c-text3)">📅 <?= h(date('d/m/Y', strtotime($art['created_at']))) ?></span>
    </div>
  </article>

  <?php if ($related): ?>
  <section aria-labelledby="h-rel">
    <h2 id="h-rel" class="section-title" style="margin-bottom:1rem"><span class="section-icon">📰</span> مقالات ذات صلة</h2>
    <div class="articles-grid" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr))">
      <?php foreach ($related as $r): ?>
      <a href="/article/<?= h($r['slug']) ?>" class="article-card">
        <?php if ($r['thumb']): ?><img src="<?= h($r['thumb']) ?>" alt="<?= h($r['title']) ?>" loading="lazy"><?php endif; ?>
        <div class="ac-body">
          <h3><?= h($r['title']) ?></h3>
          <span class="ac-date"><?= h(date('d/m/Y', strtotime($r['created_at']))) ?></span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

</div>

<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"Article",
  "headline":"<?= h($art['title']) ?>",
  "description":"<?= $page_desc ?>",
  "url":"<?= h($page_canonical) ?>",
  "inLanguage":"ar",
  "datePublished":"<?= h(date('c', strtotime($art['created_at']))) ?>",
  "dateModified":"<?= h(date('c', strtotime($art['updated_at'] ?? $art['created_at']))) ?>",
  "publisher":{"@type":"Organization","name":"<?= h(SITE_NAME) ?>","url":"<?= h(SITE_URL) ?>"}
  <?php if ($art['thumb']): ?>,"image":"<?= h($art['thumb']) ?>"<?php endif; ?>
}
</script>
