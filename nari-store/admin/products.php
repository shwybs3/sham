<?php
define('NARI_ADMIN', true);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/nav.php';
nari_require_login();

$products = nari_read_json(PRODUCTS_FILE);
$services = nari_read_json(SERVICES_FILE);
$csrf = nari_csrf_token();
$saved = $_GET['saved'] ?? '';

function v($val, $default = '') {
    return htmlspecialchars((string)($val ?? $default), ENT_QUOTES, 'UTF-8');
}

$serviceName = [];
foreach ($services as $s) {
    $serviceName[$s['id']] = $s['name'];
}

$grouped = [];
foreach ($products as $p) {
    $grouped[$p['service_id']][] = $p;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>المنتجات والأسعار | لوحة تحكم ناري ستور</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="../assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="admin.css">
</head>
<body>

<div class="top-bar">
  <div class="wrap">
    <h1><svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="none" aria-hidden="true"><path d="M12 2c1 3-2 5-2 8 0 2 1.5 3 1.5 3s-4-1-4-5c0-1 .2-2 .2-2S4 9 4 13c0 5 3.5 8 8 8s8-3 8-8c0-4-3-6-4-9-.3 2-1 3.5-2.5 4 .5-2.5-.5-4.5-2.5-6z"/></svg> المنتجات والأسعار</h1>
    <div style="display:flex; align-items:center; gap:14px;">
      <a href="index.php" class="btn ghost small">لوحة التحكم الرئيسية</a>
      <a href="../price-list.html" target="_blank" class="btn ghost small">عرض قائمة الأسعار ↗</a>
      <a href="logout.php" class="btn ghost small">تسجيل الخروج</a>
    </div>
  </div>
</div>
<?php nari_admin_nav('products'); ?>

<div class="wrap">

  <?php if ($saved === '1'): ?>
    <div class="success-box">تم حفظ الأسعار والحالة بنجاح. التغييرات تظهر خلال ثوانٍ على كل صفحات المنتجات وقائمة الأسعار.</div>
  <?php endif; ?>

  <div class="error-box" style="background:rgba(255,209,102,.12); border-color:rgba(255,209,102,.4); color:#ffd166;">
    ⚠️ <strong>تنبيه مهم:</strong> الأسعار المعبّأة حالياً هي أسعار استرشادية تجريبية فقط لتوضيح شكل قائمة الأسعار،
    وليست أسعارك الحقيقية. راجع كل سعر أدناه وحدّثه ليطابق تكلفتك الفعلية قبل نشر الموقع للجمهور.
  </div>

  <div class="panel">
    <h2>كل المنتجات (<?= count($products) ?> منتج)</h2>
    <p class="hint">
      كل منتج له صفحة مستقلة على الموقع (رابط "عرض" أدناه). السعر المعروض هنا يظهر فوراً على صفحة المنتج
      وفي قائمة الأسعار الكاملة. أوقف أي منتج مؤقتاً دون حذف صفحته من الموقع أو من نتائج البحث.
    </p>
    <form method="post" action="save.php">
      <input type="hidden" name="csrf" value="<?= v($csrf) ?>">
      <input type="hidden" name="action" value="save_products">

      <?php foreach ($grouped as $sid => $items): ?>
        <h3 style="font-size:.95rem; color:var(--gold); margin:24px 0 10px;"><?= v($serviceName[$sid] ?? $sid) ?></h3>
        <div style="overflow-x:auto;">
        <table class="svc-table">
          <thead>
            <tr><th>المنتج</th><th>السعر (ل.س)</th><th>الحالة</th><th>ملاحظة</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($items as $p): $id = $p['id']; ?>
            <tr>
              <td><?= v($p['name']) ?><?php if (!empty($p['badge'])): ?> <span class="badge on"><?= v($p['badge']) ?></span><?php endif; ?></td>
              <td>
                <input type="text" inputmode="numeric" name="products[<?= v($id) ?>][price_syp]" value="<?= v($p['price_syp'] ?? 0) ?>" style="max-width:130px;">
              </td>
              <td>
                <label style="display:flex; align-items:center; gap:8px; white-space:nowrap;">
                  <input type="checkbox" name="products[<?= v($id) ?>][active]" <?= !empty($p['active']) ? 'checked' : '' ?>>
                  <span class="badge <?= !empty($p['active']) ? 'on' : 'off' ?>"><?= !empty($p['active']) ? 'مُفعّل' : 'متوقف' ?></span>
                </label>
              </td>
              <td><input type="text" name="products[<?= v($id) ?>][note]" value="<?= v($p['note'] ?? '') ?>" placeholder="اختياري"></td>
              <td><a href="../<?= v($p['slug']) ?>" target="_blank" style="color:var(--muted); font-size:.85rem;">عرض ↗</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      <?php endforeach; ?>

      <div style="margin-top:22px;">
        <button type="submit" class="btn" style="width:auto; padding:12px 28px;">حفظ كل الأسعار والتغييرات</button>
      </div>
    </form>
  </div>
</div>

<footer class="admin-foot">لوحة تحكم ناري ستور — للاستخدام الداخلي فقط، غير مفهرسة في محركات البحث.</footer>
</body>
</html>
