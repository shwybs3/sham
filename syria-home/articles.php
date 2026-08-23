<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$lang = is_ar_request() ? 'ar' : 'en';
$dir = $lang === 'ar' ? 'rtl' : 'ltr';

$type = $_GET['type'] ?? '';
$validTypes = ['article', 'news', 'tutorial', 'comparison', 'review'];
if (!in_array($type, $validTypes, true)) $type = '';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$where = "status='published' AND lang = :lang" . ($type ? " AND content_type = :type" : '');
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE $where");
$countParams = ['lang' => $lang]; if ($type) $countParams['type'] = $type;
$countStmt->execute($countParams);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$sql = "SELECT a.*, c.name AS category_name FROM articles a LEFT JOIN categories c ON c.id=a.category_id
        WHERE $where ORDER BY a.published_at DESC LIMIT :lim OFFSET :off";
$stmt = $pdo->prepare($sql);
$stmt->bindValue('lang', $lang);
if ($type) $stmt->bindValue('type', $type);
$stmt->bindValue('lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue('off', $offset, PDO::PARAM_INT);
$stmt->execute();
$articles = $stmt->fetchAll();

$titleMap = $lang === 'ar'
    ? ['' => 'كل المقالات', 'article' => 'المقالات', 'news' => 'الأخبار', 'tutorial' => 'الدروس', 'comparison' => 'المقارنات', 'review' => 'المراجعات']
    : ['' => 'All Articles', 'article' => 'Articles', 'news' => 'News', 'tutorial' => 'Tutorials', 'comparison' => 'Comparisons', 'review' => 'Reviews'];
$pageTitle = $titleMap[$type];
$qs = $lang === 'ar' ? '&lang=ar' : '';
?><!doctype html><html lang="<?= e($lang) ?>" dir="<?= $dir ?>"><head>
<?php seo_head([
    'title' => $pageTitle . ' | ' . setting('site_name'),
    'description' => t('Browse ', 'تصفح ', $lang) . strtolower($pageTitle) . t(' on ', ' على ', $lang) . setting('site_name') . ': ' . setting('site_description'),
    'canonical' => site_url('articles.php' . ($type ? '?type=' . $type : '') . ($lang === 'ar' ? ($type ? '&lang=ar' : '?lang=ar') : '')),
    'lang' => $lang,
]); ?>
</head><body>
<?php site_header(t('Articles', 'المقالات', $lang), $lang); ?>

<div class="page-hero container">
  <span class="eyebrow"><i class="fa-solid fa-newspaper"></i> <?= number_format($total) ?> <?= e(t('published', 'منشور', $lang)) ?></span>
  <h1><?= e($pageTitle) ?></h1>
  <div class="chip-row">
    <a class="chip <?= $type === '' ? 'active' : '' ?>" href="<?= site_url($lang === 'ar' ? 'articles.php?lang=ar' : 'articles.php') ?>"><?= e(t('All', 'الكل', $lang)) ?></a>
    <?php foreach ($titleMap as $k => $label): if ($k === '') continue; ?>
      <a class="chip <?= $type === $k ? 'active' : '' ?>" href="<?= site_url('articles.php?type=' . $k . $qs) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="container">
  <?php if ($articles): ?>
  <div class="grid">
    <?php foreach ($articles as $a) article_card($a); ?>
  </div>
  <?php else: ?>
    <div class="empty-state"><i class="fa-regular fa-folder-open" style="font-size:32px"></i><p><?= e(t('No articles here yet.', 'لا توجد مقالات هنا بعد.', $lang)) ?></p></div>
  <?php endif; ?>

  <?php if ($totalPages > 1): ?>
  <div class="pagination">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
      <a class="<?= $p === $page ? 'active' : '' ?>" href="<?= site_url('articles.php?' . http_build_query(array_filter(['type'=>$type,'page'=>$p,'lang'=>$lang==='ar'?'ar':'']))) ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<?php site_footer($lang); ?>
</body></html>
