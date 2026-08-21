<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'قائمة الأسعار — ' . ($settings['shop_name'] ?? 'دفتر الدكان');
require __DIR__ . '/includes/page-header.php';

$filterCat = trim($_GET['cat'] ?? '');
$items     = getPriceList($pdo, $filterCat);
$cats      = getPriceCategories($pdo);
$currCode  = $settings['currency'] ?? 'SYP';
$currSym   = currencySymbol($currCode);
?>
<main class="page" style="padding-top:32px;">

  <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
    <h1 style="font-family:var(--disp); font-size:22px; margin:0;"><?= icon('tag', 22) ?> قائمة الأسعار</h1>
    <?php if (!empty($cats)): ?>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
      <a href="prices.php" class="btn btn-sm <?= $filterCat === '' ? 'btn-primary' : 'btn-ghost' ?>">الكل</a>
      <?php foreach ($cats as $cat): ?>
      <a href="prices.php?cat=<?= urlencode($cat) ?>"
         class="btn btn-sm <?= $filterCat === $cat ? 'btn-primary' : 'btn-ghost' ?>">
        <?= h($cat) ?>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <?php if (empty($items)): ?>
    <div class="cc-empty-state">
      <?= icon('tag', 40) ?>
      <p>لا توجد أسعار مسجّلة بعد.</p>
    </div>
  <?php else: ?>

  <!-- تجميع حسب الفئة -->
  <?php
  $grouped = [];
  foreach ($items as $item) {
      $grouped[$item['category']][] = $item;
  }
  ?>

  <?php foreach ($grouped as $category => $catItems): ?>
  <div class="panel" style="margin-bottom:20px;">
    <h2 style="font-size:16px; margin:0 0 16px; display:flex; align-items:center; gap:8px;">
      <?= icon('package', 18) ?> <?= h($category) ?>
      <span style="font-size:12px; color:var(--ink-faint); font-weight:400;">(<?= count($catItems) ?> صنف)</span>
    </h2>
    <div class="price-grid">
      <?php foreach ($catItems as $item): ?>
      <div class="price-item">
        <div class="price-name"><?= h($item['name']) ?></div>
        <div class="price-unit"><?= h($item['unit'] ?: 'قطعة') ?></div>
        <div class="price-val">
          <?= number_format((float)$item['price'], 0) ?>
          <span style="font-size:12px;"><?= h(currencySymbol($item['currency'])) ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <p style="font-size:12px; color:var(--ink-faint); text-align:center; margin-top:16px;">
    الأسعار تقريبية وقد تتغير. آخر تحديث: <?= date('Y-m-d') ?>
  </p>

  <?php endif; ?>
</main>

<style>
.price-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 12px;
}
.price-item {
  padding: 14px;
  background: var(--surface-2);
  border-radius: 12px;
  border: 1px solid var(--line);
}
.price-name { font-weight: 600; font-size: 14px; margin-bottom: 4px; }
.price-unit { font-size: 11px; color: var(--ink-faint); margin-bottom: 8px; }
.price-val  { font-size: 20px; font-weight: 700; color: var(--brand); font-family: var(--disp); }
</style>

<?php require __DIR__ . '/includes/page-footer.php'; ?>
