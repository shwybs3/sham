<?php
/* ═══════════════════════════════════════════════════════════════
   robots.txt — ديناميكي مع اكتشاف تلقائي لجميع المسارات
   يُجدَّد تلقائياً عند إضافة صفحات أو أدوات جديدة.
   ═══════════════════════════════════════════════════════════════ */
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');

$base = __DIR__;

/* ── اكتشاف تلقائي: ملفات PHP العامة في الجذر ── */
$rootPhpFiles = glob($base . '/*.php') ?: [];
$PRIVATE = ['admin.php','config.php','config.local.php','comment-form.php',
            'track-view.php','search-suggest.php','partials.php','robots.php'];

$allowedRoots = [];
foreach ($rootPhpFiles as $f) {
    $name = basename($f);
    if (in_array($name, $PRIVATE)) continue;
    // map filename to clean URL
    if ($name === 'index.php') { $allowedRoots[] = '/'; continue; }
    $clean = '/' . preg_replace('/\.php$/', '', $name);
    $allowedRoots[] = $clean;
}
sort($allowedRoots);

/* ── اكتشاف تلقائي: مجلدات الأدوات ── */
$toolDirs = glob($base . '/tools/*', GLOB_ONLYDIR) ?: [];
$toolAllows = ['/tools'];
foreach ($toolDirs as $d) {
    $slug = basename($d);
    $toolAllows[] = '/tools/' . $slug;
}

/* ── اكتشاف تلقائي: صفحات HTML الثابتة ── */
$htmlPages = glob($base . '/pages/*.html') ?: [];
$htmlAllows = [];
foreach ($htmlPages as $f) {
    $slug = preg_replace('/\.html$/', '', basename($f));
    $htmlAllows[] = '/' . $slug;        // مسار نظيف عبر .htaccess
    $htmlAllows[] = '/pages/' . $slug;  // المسار الفعلي احتياطاً
}

/* ── DB: مسارات التطبيقات المنشورة ── */
$appSlugs = [];
try {
    $appSlugs = $pdo->query(
        "SELECT slug FROM apps WHERE status='published' ORDER BY id DESC LIMIT 500"
    )->fetchAll(PDO::FETCH_COLUMN);
} catch (\Throwable $e) { $appSlugs = []; }

/* ── DB: تصنيفات ── */
$catSlugs = [];
try {
    $catSlugs = $pdo->query(
        "SELECT DISTINCT c.slug FROM categories c
         INNER JOIN apps a ON a.category_slug=c.slug AND a.status='published'
         WHERE c.slug IS NOT NULL AND c.slug <> '' LIMIT 200"
    )->fetchAll(PDO::FETCH_COLUMN);
} catch (\Throwable $e) { $catSlugs = []; }

/* ── DB: مطورون ── */
$devNames = [];
try {
    $devNames = $pdo->query(
        "SELECT DISTINCT developer FROM apps
         WHERE status='published' AND developer IS NOT NULL AND developer <> '' LIMIT 100"
    )->fetchAll(PDO::FETCH_COLUMN);
} catch (\Throwable $e) { $devNames = []; }

/* ── DB: مقالات المدونة ── */
$blogSlugs = [];
try {
    $blogSlugs = $pdo->query(
        "SELECT slug FROM blog_posts WHERE status='published' LIMIT 300"
    )->fetchAll(PDO::FETCH_COLUMN);
} catch (\Throwable $e) { $blogSlugs = []; }

?>
# ── yassota robots.txt ──────────────────────────────────────────────────────
# تم التوليد تلقائياً — <?= date('Y-m-d H:i:s') ?>

# جميع المسارات الجديدة تُضاف تلقائياً عند إضافة ملفات PHP أو أدوات أو صفحات.
# ──────────────────────────────────────────────────────────────────────────────

# ── القواعد العامة ───────────────────────────────────────────────────────────
User-agent: *

# السماح الصريح بجميع الصفحات الرئيسية (مكتشفة تلقائياً)
<?php foreach ($allowedRoots as $p): ?>
Allow: <?= $p ?>

<?php endforeach; ?>

# السماح بصفحات التطبيقات
<?php foreach ($appSlugs as $slug): ?>
Allow: /<?= rawurlencode($slug) ?>

Allow: /<?= rawurlencode($slug) ?>/download
<?php endforeach; ?>

# السماح بالتصنيفات
<?php foreach ($catSlugs as $slug): ?>
Allow: /category/<?= rawurlencode($slug) ?>

<?php endforeach; ?>

# السماح بالمطورين
<?php foreach ($devNames as $dev): ?>
Allow: /developer/<?= rawurlencode($dev) ?>

<?php endforeach; ?>

# السماح بمقالات المدونة
<?php foreach ($blogSlugs as $slug): ?>
Allow: /blog/<?= rawurlencode($slug) ?>

<?php endforeach; ?>

# السماح بالأدوات
<?php foreach ($toolAllows as $p): ?>
Allow: <?= $p ?>

<?php endforeach; ?>

# السماح بالصفحات الثابتة
<?php foreach ($htmlAllows as $p): ?>
Allow: <?= $p ?>

<?php endforeach; ?>

# ── مسارات خاصة تُمنع من الزحف ────────────────────────────────────────────
Disallow: /admin.php
Disallow: /config.php
Disallow: /install/
Disallow: /uploads/.cache/
Disallow: /search-suggest.php
Disallow: /comment-form.php
Disallow: /track-view.php
Disallow: /?q=
Disallow: /*?q=
Disallow: /?page=
Disallow: /index.php
Disallow: /app.php

# ── AdsBot — Google يتطلب قاعدة منفصلة ──────────────────────────────────────
User-agent: AdsBot-Google
Allow: /
Disallow: /admin.php
Disallow: /install/

# ── Sitemap ───────────────────────────────────────────────────────────────────
Sitemap: <?= SITE_URL ?>/sitemap.xml
