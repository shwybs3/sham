<?php
/**
 * Dynamic XML sitemap.
 *
 * Every URL here is built through the same helpers the site links with
 * (article_url(), product_url(), …) and the same canonical base URL the
 * <link rel="canonical"> tags use — so what we submit, what we link, and what
 * we declare canonical can never drift apart. That drift is the usual reason
 * pages end up "Discovered – currently not indexed".
 */
require_once __DIR__ . '/config.php';
// Guarded so the admin panel can buffer this file to write a static sitemap.xml.
if (!headers_sent()) header('Content-Type: application/xml; charset=utf-8');

/** @var array<int,array{loc:string,lastmod?:string,changefreq?:string,priority:string}> $urls */
$urls = [];
$add = function (string $loc, string $priority, ?string $lastmod = null, string $changefreq = 'weekly') use (&$urls) {
    $urls[] = array_filter([
        'loc' => $loc,
        'lastmod' => $lastmod ? date('c', is_numeric($lastmod) ? (int)$lastmod : (int)strtotime($lastmod)) : null,
        'changefreq' => $changefreq,
        'priority' => $priority,
    ]);
};

/* ── Core pages ── */
$add(site_url(''), '1.0', null, 'daily');
$add(site_url('products.php'), '0.9', null, 'daily');
$add(site_url('articles.php'), '0.8', null, 'daily');
$add(site_url('tools.php'), '0.8', null, 'weekly');
$add(site_url('about.php'), '0.4', null, 'monthly');
$add(site_url('contact.php'), '0.4', null, 'monthly');

/* ── Platform landing pages — real, distinct storefronts worth ranking,
      but only while they actually hold packages. ── */
foreach (['instagram', 'facebook', 'youtube', 'telegram', 'security'] as $platform) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE status='published' AND platform = ?");
    $stmt->execute([$platform]);
    if ((int)$stmt->fetchColumn() > 0) {
        $add(site_url('products.php?platform=' . $platform), '0.8', null, 'weekly');
    }
}

/* ── Blog type views ── */
foreach (['news', 'tutorial', 'comparison', 'review'] as $type) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE status='published' AND content_type = ?");
    $stmt->execute([$type]);
    if ((int)$stmt->fetchColumn() > 0) {
        $add(site_url('articles.php?type=' . $type), '0.6', null, 'weekly');
    }
}

/* ── Legal / policy pages ── */
foreach (['privacy-policy.php', 'terms.php', 'refund-policy.php', 'cookie-policy.php',
          'editorial-policy.php', 'license.php'] as $legal) {
    $add(site_url($legal), '0.2', null, 'yearly');
}

/* ── Content ── */
foreach ($pdo->query("SELECT slug, updated_at FROM products WHERE status='published'") as $p) {
    $add(product_url($p['slug']), '0.9', $p['updated_at'] ?? null, 'weekly');
}
foreach ($pdo->query("SELECT slug, updated_at FROM articles WHERE status='published'") as $a) {
    $add(article_url($a['slug']), '0.7', $a['updated_at'] ?? null, 'monthly');
}
foreach ($pdo->query("SELECT slug, created_at FROM tools WHERE status='published'") as $t) {
    $add(tool_url($t['slug']), '0.7', $t['created_at'] ?? null, 'monthly');
}
foreach ($pdo->query("SELECT slug, updated_at FROM pages WHERE status='published'") as $pg) {
    $add(page_url($pg['slug']), '0.4', $pg['updated_at'] ?? null, 'monthly');
}

/* ── Categories, skipping empty ones: a listing page with nothing on it is
      exactly the thin content crawlers decline to index. ── */
foreach ($pdo->query("SELECT id, slug, type FROM categories") as $c) {
    $table = $c['type'] === 'tool' ? 'tools' : 'articles';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE category_id = ? AND status='published'");
    $stmt->execute([$c['id']]);
    if ((int)$stmt->fetchColumn() > 0) {
        $add(category_url($c['slug']), '0.5', null, 'weekly');
    }
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo '  <url><loc>' . htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>';
    if (!empty($u['lastmod'])) echo '<lastmod>' . $u['lastmod'] . '</lastmod>';
    if (!empty($u['changefreq'])) echo '<changefreq>' . $u['changefreq'] . '</changefreq>';
    echo '<priority>' . $u['priority'] . '</priority></url>' . "\n";
}
echo '</urlset>';
