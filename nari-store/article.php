<?php
require_once __DIR__ . '/partials.php';

$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower((string)($_GET['slug'] ?? '')));
$articles = nari_read_json_file(__DIR__ . '/data/articles.json');
$article = null;
foreach ($articles as $a) {
    if (($a['slug'] ?? '') === $slug) { $article = $a; break; }
}

if (!$article || empty($article['published'])) {
    http_response_code(404);
    $notFound = @file_get_contents(__DIR__ . '/404.html');
    echo $notFound !== false ? $notFound : 'المقال غير موجود.';
    exit;
}

$jsonld = [json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $article['title'],
    'description' => $article['meta_description'] ?? ($article['excerpt'] ?? ''),
    'url' => nari_site_url() . '/article/' . $slug,
    'datePublished' => $article['published_at'] ?? '',
    'dateModified' => $article['updated_at'] ?? ($article['published_at'] ?? ''),
    'inLanguage' => 'ar',
    'author' => ['@type' => 'Organization', 'name' => 'ناري ستور'],
    'publisher' => ['@type' => 'Organization', 'name' => 'ناري ستور', 'logo' => ['@type' => 'ImageObject', 'url' => nari_site_url() . '/assets/logo.svg']],
    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => nari_site_url() . '/article/' . $slug],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];

nari_head([
    'title' => $article['title'] . ' | مقالات ناري ستور',
    'description' => $article['meta_description'] ?? ($article['excerpt'] ?? ''),
    'keywords' => $article['keywords'] ?? '',
    'path' => 'article/' . $slug,
    'active_nav' => 'blog',
    'crumbs' => [['label' => 'المقالات والأخبار', 'url' => 'blog.php'], ['label' => $article['title']]],
    'jsonld' => $jsonld,
]);
?>
<div class="legal">
  <?php if (!empty($article['category'])): ?>
    <p class="updated"><span class="tag-pill" style="margin-inline-start:0;"><?= nari_esc($article['category']) ?></span></p>
  <?php endif; ?>
  <h1><?= nari_esc($article['title']) ?></h1>
  <p class="updated">
    <svg class="icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><line x1="12" y1="7" x2="12" y2="12"/><line x1="12" y1="12" x2="16" y2="14"/></svg>
    نُشر: <?= nari_esc($article['published_at'] ?? '') ?>
    <?php if (!empty($article['updated_at']) && $article['updated_at'] !== ($article['published_at'] ?? '')): ?> — آخر تحديث: <?= nari_esc($article['updated_at']) ?><?php endif; ?>
  </p>
  <?= $article['content_html'] ?>
  <p style="margin-top:40px;"><a class="btn btn-ghost" href="blog.php">← كل المقالات والأخبار</a></p>
</div>
<?php nari_footer(); ?>
