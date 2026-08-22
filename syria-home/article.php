<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("SELECT a.*, c.name AS category_name, c.slug AS category_slug FROM articles a
    LEFT JOIN categories c ON c.id = a.category_id WHERE a.slug = ? AND a.status='published' LIMIT 1");
$stmt->execute([$slug]);
$article = $stmt->fetch();

if (!$article) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$pdo->prepare("UPDATE articles SET views = views + 1 WHERE id = ?")->execute([$article['id']]);

$related = $pdo->prepare("SELECT a.*, c.name AS category_name FROM articles a LEFT JOIN categories c ON c.id=a.category_id
    WHERE a.status='published' AND a.id != ? AND (a.category_id <=> ? OR a.content_type = ?)
    ORDER BY a.published_at DESC LIMIT 3");
$related->execute([$article['id'], $article['category_id'], $article['content_type']]);
$related = $related->fetchAll();

$isLocked = !empty($article['is_premium']) && !is_content_unlocked('article', (int)$article['id']);

$tags = array_filter(array_map('trim', explode(',', $article['tags'])));
$metaTitle = $article['meta_title'] ?: $article['title'];
$metaDesc = $article['meta_description'] ?: $article['excerpt'];

$jsonld = [
    '@context' => 'https://schema.org',
    '@type' => $article['content_type'] === 'news' ? 'NewsArticle' : 'Article',
    'headline' => $article['title'],
    'description' => $metaDesc,
    'image' => [site_url('assets/img/og-default.svg')],
    'author' => ['@type' => 'Organization', 'name' => $article['author']],
    'publisher' => ['@type' => 'Organization', 'name' => setting('site_name'), 'logo' => ['@type' => 'ImageObject', 'url' => site_url('assets/img/favicon.svg')]],
    'datePublished' => date('c', strtotime($article['published_at'])),
    'dateModified' => date('c', strtotime($article['updated_at'])),
    'mainEntityOfPage' => site_url('article.php?slug=' . $article['slug']),
];

$extraSchema = $pdo->prepare("SELECT payload_json FROM article_schema_blocks WHERE article_id = ?");
$extraSchema->execute([$article['id']]);
$extraSchema = $extraSchema->fetchAll(PDO::FETCH_COLUMN);
?><!doctype html><html lang="en"><head>
<?php seo_head([
    'title' => $metaTitle . ' | ' . setting('site_name'),
    'description' => $metaDesc,
    'keywords' => $article['meta_keywords'],
    'canonical' => site_url('article.php?slug=' . $article['slug']),
    'type' => 'article',
    'jsonld' => $jsonld,
]); ?>
<?php foreach ($extraSchema as $blockJson): ?>
<script type="application/ld+json"><?= str_replace('</script', '<\/script', $blockJson) ?></script>
<?php endforeach; ?>
</head><body>
<?php site_header(); ?>

<div class="container article-hero">
  <div class="breadcrumb"><a href="<?= site_url('') ?>">Home</a> / <a href="<?= site_url('articles.php') ?>">Articles</a> <?php if ($article['category_name']): ?> / <a href="<?= site_url('category.php?slug=' . urlencode($article['category_slug'])) ?>"><?= e($article['category_name']) ?></a><?php endif; ?></div>
  <?php if (!empty($article['hero_image_path'])): ?>
    <div class="article-hero-photo">
      <img src="<?= site_url($article['hero_image_path']) ?>" alt="<?= e($article['title']) ?>" loading="eager">
      <span class="art-icon art-icon-overlay" style="<?= hero_style_css($article['hero_gradient']) ?>"><i class="fa-solid <?= e($article['hero_icon']) ?>"></i></span>
    </div>
  <?php else: ?>
    <div class="art-icon" style="<?= hero_style_css($article['hero_gradient']) ?>"><i class="fa-solid <?= e($article['hero_icon']) ?>"></i></div>
  <?php endif; ?>
  <span class="badge-trending" style="background:#eef1ff;color:var(--brand1)"><?= e(ucfirst($article['content_type'])) ?></span>
  <?php if ($article['trending']): ?> <span class="badge-trending"><i class="fa-solid fa-fire"></i> Trending</span><?php endif; ?>
  <h1><?= e($article['title']) ?></h1>
  <p class="lead"><?= e($article['excerpt']) ?></p>
  <div class="article-meta">
    <span><i class="fa-regular fa-user"></i> <?= e($article['author']) ?></span>
    <span><i class="fa-regular fa-calendar"></i> <?= date('M j, Y', strtotime($article['published_at'])) ?></span>
    <span><i class="fa-regular fa-clock"></i> <?= (int)$article['reading_time'] ?> min read</span>
    <span><i class="fa-regular fa-eye"></i> <?= number_format((int)$article['views']) ?> views</span>
  </div>
</div>

<div class="container">
  <?php ad_zone('article_top'); ?>

  <?php if ($isLocked): ?>
    <div class="article-body">
      <p><?= e($article['excerpt']) ?></p>
      <p style="filter:blur(3px);user-select:none;pointer-events:none"><?= e(mb_substr(strip_tags($article['body']), 0, 400)) ?>…</p>
    </div>
    <div class="guarantee-box" style="background:#eef1ff;border-color:#c7d2fe">
      <div class="icon-badge" style="background:var(--grad-brand)"><i class="fa-solid fa-lock"></i></div>
      <div>
        <h3 style="color:var(--brand1)">This article is premium</h3>
        <p style="color:var(--ink-soft)">Unlock the full article for <?= money((float)$article['premium_price'], 'USD') ?> — a one-time crypto payment, no account needed.</p>
        <?php if (NOWPayments::isConfigured()): ?>
          <a class="btn-buy" style="display:inline-flex;width:auto;margin-top:10px" href="<?= site_url('checkout.php?type=unlock_article&id=' . (int)$article['id']) ?>"><i class="fa-solid fa-wallet"></i> Unlock for <?= money((float)$article['premium_price'], 'USD') ?></a>
        <?php else: ?>
          <p class="hint">Payments aren't configured on this site yet.</p>
        <?php endif; ?>
      </div>
    </div>
  <?php else: ?>
    <div class="article-body"><?= $article['body'] ?></div>
  <?php endif; ?>

  <?php if ($tags): ?>
  <div class="tag-row container" style="padding:0">
    <?php foreach ($tags as $tag): ?><span class="tag">#<?= e($tag) ?></span><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (!$isLocked) tip_widget('Article: ' . $article['title']); ?>

  <?php ad_zone('article_bottom'); ?>

  <?php if ($related): ?>
  <div class="section-head"><h2><i class="fa-solid fa-layer-group" style="color:var(--brand1)"></i> Related reads</h2></div>
  <div class="grid">
    <?php foreach ($related as $r) article_card($r); ?>
  </div>
  <?php endif; ?>
</div>

<?php site_footer(); ?>
</body></html>
