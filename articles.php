<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/cache.php';

if (cache_serve('articles')) exit;
cache_start('articles');

$lang      = getLang();
$articles  = allArticles();
$cat       = clean($_GET['cat'] ?? '');
$cats      = array_unique(array_column($articles, 'category'));
sort($cats);

if ($cat) {
    $articles = array_filter($articles, fn($a) => $a['category'] === $cat);
}

$pageTitle = t('المقالات والشروحات', 'Articles & Tutorials');
require_once __DIR__.'/header.php';
?>

<main>
  <!-- Breadcrumb -->
  <div class="container" style="padding-top:18px;font-size:13px;color:var(--dim);display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
    <a href="index.php"><?= t('الرئيسية','Home') ?></a>
    <i class="fa-solid fa-chevron-<?= $lang==='ar'?'left':'right' ?>" style="font-size:11px;"></i>
    <span><?= t('المقالات','Articles') ?></span>
  </div>

  <section class="container" style="padding:28px 0 60px;">
    <div style="margin-bottom:32px;">
      <h1 style="font-size:28px;font-weight:900;margin-bottom:8px;">
        <i class="fa-solid fa-newspaper" style="color:var(--cyan);"></i>
        <?= t('المقالات والشروحات','Articles & Tutorials') ?>
      </h1>
      <p style="color:var(--dim);font-size:15px;"><?= t('شروحات وأدلة تقنية احترافية','Professional technical guides and tutorials') ?></p>
    </div>

    <!-- Category filter -->
    <?php if(!empty($cats)): ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:28px;">
      <a href="articles.php" class="cat-btn<?= $cat===''?' active':'' ?>" style="border:1px solid var(--border);padding:6px 16px;border-radius:20px;font-size:13px;color:<?= $cat===''?'var(--cyan)':'var(--dim)' ?>;<?= $cat===''?'border-color:var(--cyan);background:rgba(0,240,255,.08);':'' ?>"><?= t('الكل','All') ?></a>
      <?php foreach($cats as $c): ?>
      <a href="articles.php?cat=<?= urlencode($c) ?>" class="cat-btn<?= $cat===$c?' active':'' ?>" style="border:1px solid var(--border);padding:6px 16px;border-radius:20px;font-size:13px;color:<?= $cat===$c?'var(--cyan)':'var(--dim)' ?>;<?= $cat===$c?'border-color:var(--cyan);background:rgba(0,240,255,.08);':'' ?>"><?= clean($c) ?></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if(empty($articles)): ?>
    <div style="text-align:center;padding:60px 20px;color:var(--dim);">
      <i class="fa-solid fa-newspaper" style="font-size:48px;opacity:.3;margin-bottom:16px;display:block;"></i>
      <p><?= t('لا توجد مقالات بعد — تابعنا للمزيد قريباً','No articles yet — stay tuned for more soon') ?></p>
    </div>
    <?php else: ?>
    <div class="articles-grid">
      <?php foreach($articles as $a): ?>
      <a href="a/<?= urlencode($a['slug']) ?>" class="article-card">
        <?php if($a['image']): ?>
        <div class="article-img" style="background-image:url('<?= clean($a['image']) ?>')"></div>
        <?php else: ?>
        <div class="article-img article-img-placeholder">
          <i class="fa-solid fa-newspaper"></i>
        </div>
        <?php endif; ?>
        <div class="article-body">
          <div class="article-cat"><?= clean($a['category']) ?></div>
          <h2 class="article-title"><?= t(clean($a['title_ar']),clean($a['title_en'])) ?></h2>
          <p class="article-summary"><?= t(clean(mb_substr($a['summary_ar']??'',0,120)),clean(mb_substr($a['summary_en']??'',0,120))) ?><?= mb_strlen($a['summary_ar']??'')>120?'…':'' ?></p>
          <div class="article-meta">
            <span><i class="fa-solid fa-calendar-days"></i> <?= substr($a['created_at'],0,10) ?></span>
            <span><i class="fa-solid fa-eye"></i> <?= number_format($a['views']) ?></span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>
</main>

<?php require_once __DIR__.'/footer.php'; ?>
