<?php
require_once __DIR__ . '/../config.php';
requireAdminLogin();
require __DIR__ . '/includes/admin-header.php';

$msg = '';

// حذف
if (isset($_GET['delete']) && csrfCheck()) {
    $pdo->prepare("DELETE FROM price_list WHERE id=?")->execute([(int)$_GET['delete']]);
    header('Location: prices.php?ok=deleted'); exit;
}

// حفظ / تعديل
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfCheck()) {
    $id       = (int)($_POST['id'] ?? 0);
    $name     = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? 'عام');
    $price    = (float)($_POST['price'] ?? 0);
    $currency = $_POST['currency'] ?? $settings['currency'] ?? 'SYP';
    $unit     = trim($_POST['unit'] ?? 'قطعة');

    if ($name && $price >= 0) {
        if ($id) {
            $pdo->prepare("UPDATE price_list SET name=?,category=?,price=?,currency=?,unit=?,updated_at=NOW() WHERE id=?")
                ->execute([$name, $category, $price, $currency, $unit, $id]);
        } else {
            $pdo->prepare("INSERT INTO price_list (name,category,price,currency,unit) VALUES (?,?,?,?,?)")
                ->execute([$name, $category, $price, $currency, $unit]);
        }
        header('Location: prices.php?ok=saved'); exit;
    }
    $msg = 'يرجى إدخال الاسم والسعر.';
}

$items = $pdo->query("SELECT * FROM price_list ORDER BY category, name")->fetchAll();
$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM price_list WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editItem = $stmt->fetch() ?: null;
}

$currencies = getCurrencies();
$shopCat    = array_keys(getProductTypes());
?>

<div class="admin-content">
  <div class="admin-page-header">
    <h1><?= icon('tag', 22) ?> إدارة قائمة الأسعار</h1>
    <a href="../prices.php" class="btn btn-ghost btn-sm" target="_blank">عرض عام</a>
  </div>

  <?php if (isset($_GET['ok'])): ?>
  <div class="alert alert-success">تم الحفظ بنجاح.</div>
  <?php endif; ?>
  <?php if ($msg): ?>
  <div class="alert alert-error"><?= h($msg) ?></div>
  <?php endif; ?>

  <!-- نموذج الإضافة/التعديل -->
  <div class="panel" style="margin-bottom:24px;">
    <h2 style="font-size:15px; margin:0 0 16px;"><?= $editItem ? 'تعديل: ' . h($editItem['name']) : 'إضافة صنف جديد' ?></h2>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
      <?php if ($editItem): ?>
      <input type="hidden" name="id" value="<?= (int)$editItem['id'] ?>">
      <?php endif; ?>
      <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-bottom:12px;">
        <div>
          <label class="field-label">اسم الصنف</label>
          <input class="input" name="name" required value="<?= h($editItem['name'] ?? '') ?>" placeholder="مثال: خبز">
        </div>
        <div>
          <label class="field-label">الفئة</label>
          <input class="input" name="category" list="cat-list" value="<?= h($editItem['category'] ?? 'بقالة') ?>">
          <datalist id="cat-list">
            <?php foreach ($shopCat as $c): ?><option value="<?= h($c) ?>"><?php endforeach; ?>
          </datalist>
        </div>
        <div>
          <label class="field-label">الوحدة</label>
          <input class="input" name="unit" value="<?= h($editItem['unit'] ?? 'قطعة') ?>" placeholder="كغ، لتر، قطعة...">
        </div>
      </div>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px;">
        <div>
          <label class="field-label">السعر</label>
          <input class="input" type="number" name="price" min="0" step="any" required value="<?= h($editItem['price'] ?? '') ?>" placeholder="0">
        </div>
        <div>
          <label class="field-label">العملة</label>
          <select class="input" name="currency">
            <?php foreach ($currencies as $code => $c): ?>
            <option value="<?= h($code) ?>" <?= ($editItem['currency'] ?? $settings['currency'] ?? 'SYP') === $code ? 'selected' : '' ?>>
              <?= h($c['symbol']) ?> — <?= h($c['name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div style="display:flex; gap:8px;">
        <button class="btn btn-primary" type="submit"><?= icon('save', 16) ?> حفظ</button>
        <?php if ($editItem): ?>
        <a href="prices.php" class="btn btn-ghost">إلغاء</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- قائمة الأصناف -->
  <div class="panel">
    <h2 style="font-size:15px; margin:0 0 16px;">الأصناف (<?= count($items) ?>)</h2>
    <?php if (empty($items)): ?>
      <div class="cc-empty-state" style="padding:30px 0;"><?= icon('tag', 36) ?><p>لا يوجد أصناف بعد.</p></div>
    <?php else: ?>
    <div style="overflow-x:auto;">
      <table style="width:100%; border-collapse:collapse; font-size:14px;">
        <thead>
          <tr style="border-bottom:2px solid var(--line);">
            <th style="padding:10px 0; text-align:start;">الاسم</th>
            <th style="padding:10px 0; text-align:start;">الفئة</th>
            <th style="padding:10px 0; text-align:start;">السعر</th>
            <th style="padding:10px 0; text-align:start;">الوحدة</th>
            <th style="padding:10px 0;"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): ?>
          <tr style="border-bottom:1px solid var(--line);">
            <td style="padding:10px 0; font-weight:500;"><?= h($item['name']) ?></td>
            <td style="padding:10px 0;"><span class="tx-product-badge"><?= h($item['category']) ?></span></td>
            <td style="padding:10px 0; font-family:var(--mono);">
              <?= number_format((float)$item['price'], 0) ?> <?= h(currencySymbol($item['currency'])) ?>
            </td>
            <td style="padding:10px 0; color:var(--ink-faint);"><?= h($item['unit']) ?></td>
            <td style="padding:10px 0; text-align:end;">
              <a href="prices.php?edit=<?= $item['id'] ?>" class="btn btn-ghost btn-sm"><?= icon('edit', 14) ?></a>
              <a href="prices.php?delete=<?= $item['id'] ?>&csrf=<?= csrfToken() ?>"
                 class="btn btn-ghost btn-sm" style="color:var(--red);"
                 onclick="return confirm('حذف <?= h($item['name']) ?>?')">
                <?= icon('trash', 14) ?>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
