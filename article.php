<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/cache.php';

$slug = clean($_GET['slug'] ?? '');
$a    = $slug ? articleBySlug($slug) : null;
if (!$a) { header('Location: articles.php'); exit; }

$lang = getLang();

// increment views
db()->prepare("UPDATE articles SET views=views+1 WHERE slug=?")->execute([$slug]);

// related articles (same category, limit 3)
$relStmt = db()->prepare("SELECT * FROM articles WHERE category=? AND slug!=? AND active=1 ORDER BY id DESC LIMIT 3");
$relStmt->execute([$a['category'], $slug]);
$related = $relStmt->fetchAll();

$content = $lang === 'ar' ? $a['content_ar'] : $a['content_en'];

$pageTitle = t(clean($a['title_ar']), clean($a['title_en']));

if (cache_serve('article', $slug)) exit;
cache_start('article', $slug);
require_once __DIR__.'/header.php';
?>

<main>
  <div class="container" style="max-width:860px;padding:24px 20px 80px;">

    <!-- Breadcrumb -->
    <div style="font-size:13px;color:var(--dim);margin-bottom:22px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
      <a href="index.php"><?= t('الرئيسية','Home') ?></a>
      <i class="fa-solid fa-chevron-<?= $lang==='ar'?'left':'right' ?>" style="font-size:11px;"></i>
      <a href="articles.php"><?= t('المقالات','Articles') ?></a>
      <i class="fa-solid fa-chevron-<?= $lang==='ar'?'left':'right' ?>" style="font-size:11px;"></i>
      <span style="color:var(--text);"><?= t(clean($a['title_ar']),clean($a['title_en'])) ?></span>
    </div>

    <!-- Category badge -->
    <div style="margin-bottom:14px;">
      <a href="articles.php?cat=<?= urlencode($a['category']) ?>" style="font-size:13px;padding:4px 14px;border-radius:20px;border:1px solid var(--cyan);color:var(--cyan);background:rgba(0,240,255,.08);">
        <?= clean($a['category']) ?>
      </a>
    </div>

    <!-- Title -->
    <h1 style="font-size:clamp(22px,4vw,34px);font-weight:900;line-height:1.4;margin-bottom:16px;">
      <?= t(clean($a['title_ar']),clean($a['title_en'])) ?>
    </h1>

    <!-- Meta -->
    <div style="display:flex;align-items:center;gap:18px;color:var(--dim);font-size:13px;margin-bottom:26px;flex-wrap:wrap;">
      <span><i class="fa-solid fa-calendar-days"></i> <?= substr($a['created_at'],0,10) ?></span>
      <span><i class="fa-solid fa-eye"></i> <?= number_format($a['views']) ?> <?= t('مشاهدة','views') ?></span>
    </div>

    <!-- Featured image -->
    <?php if($a['image']): ?>
    <div style="margin-bottom:28px;border-radius:16px;overflow:hidden;max-height:460px;">
      <img src="<?= clean($a['image']) ?>" alt="<?= t(clean($a['title_ar']),clean($a['title_en'])) ?>"
           style="width:100%;height:auto;object-fit:cover;display:block;" loading="lazy">
    </div>
    <?php endif; ?>

    <!-- Summary -->
    <?php $summary = t($a['summary_ar']??'',$a['summary_en']??''); if($summary): ?>
    <div style="font-size:16px;color:#c0c0c0;background:rgba(0,240,255,.04);border-<?= $lang==='ar'?'right':'left' ?>:3px solid var(--cyan);padding:14px 18px;border-radius:6px;line-height:1.85;margin-bottom:28px;">
      <?= nl2br(clean($summary)) ?>
    </div>
    <?php endif; ?>

    <!-- Article body (admin-authored HTML) -->
    <div class="article-content">
      <?= $content ?>
    </div>

    <!-- Share -->
    <div style="margin-top:36px;padding-top:20px;border-top:1px solid var(--border);display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
      <span style="font-size:14px;color:var(--dim);font-weight:600;"><?= t('شارك:','Share:') ?></span>
      <?php $shareUrl = urlencode(siteUrl('a/'.$a['slug'])); $shareTitle = urlencode(t($a['title_ar'],$a['title_en'])); ?>
      <a href="https://wa.me/?text=<?= $shareTitle ?>%20<?= $shareUrl ?>" target="_blank" rel="noopener"
         style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:20px;background:#25D36622;color:#25D366;font-size:13px;border:1px solid #25D36633;">
        <i class="fa-brands fa-whatsapp"></i> WhatsApp
      </a>
      <a href="https://t.me/share/url?url=<?= $shareUrl ?>&text=<?= $shareTitle ?>" target="_blank" rel="noopener"
         style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:20px;background:rgba(0,136,204,.15);color:#08c;font-size:13px;border:1px solid rgba(0,136,204,.25);">
        <i class="fa-brands fa-telegram"></i> Telegram
      </a>
      <button onclick="navigator.clipboard.writeText(location.href);this.textContent='✅ تم النسخ';setTimeout(()=>this.textContent='نسخ الرابط',2000);"
         style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:20px;background:rgba(0,240,255,.08);color:var(--cyan);font-size:13px;border:1px solid rgba(0,240,255,.2);cursor:pointer;">
        <i class="fa-solid fa-copy"></i> <?= t('نسخ الرابط','Copy Link') ?>
      </button>
    </div>

    <!-- Related articles -->
    <?php if(!empty($related)): ?>
    <div style="margin-top:48px;">
      <h3 style="font-size:18px;font-weight:800;margin-bottom:18px;display:flex;align-items:center;gap:8px;">
        <i class="fa-solid fa-layer-group" style="color:var(--cyan);"></i>
        <?= t('مقالات ذات صلة','Related Articles') ?>
      </h3>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;">
        <?php foreach($related as $r): ?>
        <a href="a/<?= urlencode($r['slug']) ?>" class="article-card" style="margin:0;">
          <?php if($r['image']): ?>
          <div class="article-img" style="background-image:url('<?= clean($r['image']) ?>');height:140px;"></div>
          <?php else: ?>
          <div class="article-img article-img-placeholder" style="height:140px;"><i class="fa-solid fa-newspaper"></i></div>
          <?php endif; ?>
          <div class="article-body" style="padding:14px;">
            <div class="article-cat"><?= clean($r['category']) ?></div>
            <h4 class="article-title" style="font-size:14px;"><?= t(clean($r['title_ar']),clean($r['title_en'])) ?></h4>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
</main>

<?php require_once __DIR__.'/footer.php'; ?>
