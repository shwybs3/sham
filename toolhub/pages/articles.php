<?php
$q    = trim($_GET['q'] ?? '');
$cat  = trim($_GET['cat'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$per  = 12;

$pdo  = db();
$arts = [];
$total = 0;
$cats  = [];

if ($pdo) {
  // Get categories
  $cats = $pdo->query("SELECT DISTINCT category FROM articles WHERE status='published' AND category IS NOT NULL AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

  // Build query
  $where = ["status='published'"];
  $params = [];
  if ($q) { $where[] = "(title LIKE :q OR excerpt LIKE :q2)"; $params[':q'] = "%$q%"; $params[':q2'] = "%$q%"; }
  if ($cat) { $where[] = "category = :cat"; $params[':cat'] = $cat; }
  $whereSQL = implode(' AND ', $where);

  $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE $whereSQL");
  $cntStmt->execute($params);
  $total = (int)$cntStmt->fetchColumn();

  $offset  = ($page - 1) * $per;
  $artStmt = $pdo->prepare("SELECT id,slug,title,excerpt,category,thumb,created_at FROM articles WHERE $whereSQL ORDER BY created_at DESC LIMIT $per OFFSET $offset");
  $artStmt->execute($params);
  $arts = $artStmt->fetchAll();
}

$pages = ceil($total / $per);
?>

<section class="page-hero small">
  <div class="container">
    <nav class="breadcrumb"><a href="/">الرئيسية</a> <span>›</span> <span>المقالات</span></nav>
    <h1>📰 المقالات والدليل</h1>
    <p>أدلة شاملة وشروحات حول أسعار الصرف والذهب والطاقة الشمسية والأدوات الرقمية</p>
  </div>
</section>

<div class="container page-body">

  <!-- Search & Filter -->
  <div class="card" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center">
    <form action="/articles" method="get" style="display:flex;gap:.5rem;flex:1;min-width:200px">
      <?php if ($cat): ?><input type="hidden" name="cat" value="<?= h($cat) ?>"><?php endif; ?>
      <input type="search" name="q" value="<?= h($q) ?>" placeholder="ابحث في المقالات..." style="flex:1">
      <button type="submit" class="btn-primary">بحث</button>
    </form>
    <?php if ($cats): ?>
    <div style="display:flex;gap:.4rem;flex-wrap:wrap">
      <a href="/articles" class="filter-btn<?= !$cat?' active':'' ?>">الكل</a>
      <?php foreach ($cats as $c): ?>
      <a href="/articles?cat=<?= urlencode($c) ?>" class="filter-btn<?= $cat===$c?' active':'' ?>"><?= h($c) ?></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <?php if ($q || $cat): ?>
  <div class="alert-info">
    نتائج <?php if ($q): ?>البحث عن "<strong><?= h($q) ?></strong>"<?php endif; ?>
    <?php if ($cat): ?>في تصنيف "<strong><?= h($cat) ?></strong>"<?php endif; ?>:
    <strong><?= $total ?></strong> مقالة
    <?php if ($q || $cat): ?><a href="/articles" style="margin-right:.5rem;font-size:12px">مسح الفلتر</a><?php endif; ?>
  </div>
  <?php endif; ?>

  <?php if ($arts): ?>
  <div class="articles-grid">
    <?php foreach ($arts as $a): ?>
    <a href="/article/<?= h($a['slug']) ?>" class="article-card">
      <?php if ($a['thumb']): ?>
      <img src="<?= h($a['thumb']) ?>" alt="<?= h($a['title']) ?>" loading="lazy">
      <?php endif; ?>
      <div class="ac-body">
        <?php if ($a['category']): ?><span class="ac-cat"><?= h($a['category']) ?></span><?php endif; ?>
        <h3><?= h($a['title']) ?></h3>
        <p><?= h(mb_substr(strip_tags($a['excerpt'] ?? ''), 0, 120)) ?>…</p>
        <span class="ac-date"><?= h(date('d/m/Y', strtotime($a['created_at']))) ?></span>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($pages > 1): ?>
  <div style="display:flex;gap:.4rem;justify-content:center;flex-wrap:wrap">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
    <?php $params2 = array_filter(['q'=>$q,'cat'=>$cat,'p'=>$i>1?$i:null]); ?>
    <a href="/articles?<?= http_build_query($params2) ?>"
       class="filter-btn<?= $i===$page?' active':'' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>

  <?php else: ?>
  <div class="empty-state">
    <p style="font-size:3rem;margin-bottom:1rem">📭</p>
    <?php if ($q): ?>
    <p>لا توجد مقالات تطابق "<strong><?= h($q) ?></strong>". جرّب كلمات مختلفة.</p>
    <?php else: ?>
    <p>لا توجد مقالات حتى الآن. ترقّب المحتوى القادم!</p>
    <?php endif; ?>
  </div>
  <?php endif; ?>

</div>
