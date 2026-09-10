<?php
require_once __DIR__ . '/partials.php';

$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower((string)($_GET['slug'] ?? '')));
$pages = nari_read_json_file(__DIR__ . '/data/pages.json');
$page = null;
foreach ($pages as $p) {
    if (($p['slug'] ?? '') === $slug) { $page = $p; break; }
}

if (!$page || empty($page['published'])) {
    http_response_code(404);
    $notFound = @file_get_contents(__DIR__ . '/404.html');
    echo $notFound !== false ? $notFound : 'الصفحة غير موجودة.';
    exit;
}

$jsonld = [json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => $page['title'],
    'url' => nari_site_url() . '/p/' . $slug,
    'description' => $page['meta_description'] ?? '',
    'inLanguage' => 'ar',
    'isPartOf' => ['@type' => 'WebSite', 'name' => 'ناري ستور', 'url' => nari_site_url() . '/'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];

nari_head([
    'title' => $page['title'] . ' | ناري ستور',
    'description' => $page['meta_description'] ?? '',
    'keywords' => $page['keywords'] ?? '',
    'path' => 'p/' . $slug,
    'crumbs' => [['label' => $page['title']]],
    'jsonld' => $jsonld,
]);
?>
<div class="legal">
  <h1><?= nari_esc($page['title']) ?></h1>
  <?php if (!empty($page['updated_at'])): ?>
    <p class="updated"><svg class="icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><line x1="12" y1="7" x2="12" y2="12"/><line x1="12" y1="12" x2="16" y2="14"/></svg> آخر تحديث: <?= nari_esc($page['updated_at']) ?></p>
  <?php endif; ?>
  <?= $page['content_html'] ?>
</div>
<?php nari_footer(); ?>
