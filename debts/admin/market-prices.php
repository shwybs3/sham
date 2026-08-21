<?php
require_once __DIR__ . '/../config.php';
requireAdminLogin();

$pageTitle = 'أسعار السوق المحلي';
$activeNav = 'market';
require __DIR__ . '/includes/admin-header.php';

// إجراءات الموافقة / الرفض / الحذف
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfCheck()) {
    $action = $_POST['action'] ?? '';
    $ids    = array_map('intval', (array)($_POST['ids'] ?? []));

    if ($action === 'approve' && $ids) {
        // نقل السعر الموافق عليه إلى price_list
        $rows = $pdo->query("SELECT * FROM market_prices_raw WHERE id IN (" . implode(',', $ids) . ")")->fetchAll();
        $ins  = $pdo->prepare("INSERT INTO price_list (name, category, price, currency, unit) VALUES (?,?,?,?,?)
                                ON DUPLICATE KEY UPDATE price=VALUES(price), updated_at=NOW()");
        $upd  = $pdo->prepare("UPDATE market_prices_raw SET status='approved', reviewed_at=NOW() WHERE id=?");
        foreach ($rows as $r) {
            $ins->execute([$r['product_name'], 'سوق محلي', $r['price'], $r['currency'], $r['unit']]);
            $upd->execute([$r['id']]);
        }
        header('Location: market-prices.php?ok=approved'); exit;
    }
    if ($action === 'reject' && $ids) {
        $pdo->query("UPDATE market_prices_raw SET status='rejected', reviewed_at=NOW() WHERE id IN (" . implode(',', $ids) . ")");
        header('Location: market-prices.php?ok=rejected'); exit;
    }
    if ($action === 'delete_all') {
        $pdo->query("DELETE FROM market_prices_raw WHERE status IN ('approved','rejected')");
        header('Location: market-prices.php?ok=cleaned'); exit;
    }
}

$filter  = $_GET['status'] ?? 'pending';
$allowed = ['pending','approved','rejected'];
$filter  = in_array($filter, $allowed) ? $filter : 'pending';

$rows    = $pdo->prepare("SELECT * FROM market_prices_raw WHERE status=? ORDER BY created_at DESC LIMIT 200");
$rows->execute([$filter]);
$items   = $rows->fetchAll();
$counts  = $pdo->query("SELECT status, COUNT(*) AS n FROM market_prices_raw GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="admin-content">
  <div class="admin-page-header">
    <h1><?= icon('trending-up', 22) ?> أسعار السوق المحلي</h1>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
      <a href="market-prices.php?status=pending"  class="btn btn-sm <?= $filter==='pending'  ?'btn-primary':'btn-ghost' ?>">
        انتظار (<?= (int)($counts['pending'] ?? 0) ?>)
      </a>
      <a href="market-prices.php?status=approved" class="btn btn-sm <?= $filter==='approved' ?'btn-primary':'btn-ghost' ?>">
        مقبول (<?= (int)($counts['approved'] ?? 0) ?>)
      </a>
      <a href="market-prices.php?status=rejected" class="btn btn-sm <?= $filter==='rejected' ?'btn-primary':'btn-ghost' ?>">
        مرفوض (<?= (int)($counts['rejected'] ?? 0) ?>)
      </a>
    </div>
  </div>

  <?php if (isset($_GET['ok'])): ?>
  <div class="notice ok"><?= icon('check-circle',15) ?>
    <?= ['approved'=>'تمت الموافقة ونقل الأسعار لقائمة المحل','rejected'=>'تم الرفض','cleaned'=>'تم حذف المعالجة'][$_GET['ok']] ?? 'تم' ?>
  </div>
  <?php endif; ?>

  <!-- إعداد Webhook -->
  <div class="panel" style="margin-bottom:20px; border-right:3px solid var(--brand);">
    <h2 style="font-size:14px; margin:0 0 12px;"><?= icon('send',16) ?> إعداد Webhook تيليجرام</h2>
    <div style="font-size:13px; color:var(--ink-dim); line-height:1.8;">
      <p>1. أضف البوت لمجموعة السوق وامنحه صلاحية قراءة الرسائل</p>
      <p>2. سجّل الـ Webhook بهذا الأمر:</p>
      <code style="display:block; background:var(--surface-2); padding:10px; border-radius:8px; font-size:11px; direction:ltr; margin:8px 0; overflow-x:auto;">
        curl "https://api.telegram.org/bot<b>TOKEN</b>/setWebhook?url=<?= h(SITE_URL) ?>/api/tg-market-webhook.php&secret_token=<b><?= h($settings['tg_market_webhook_secret'] ?? 'SECRET') ?></b>"
      </code>
      <p>3. الأسعار ستصل تلقائياً وتظهر هنا للمراجعة قبل النشر</p>
    </div>
  </div>

  <?php if (empty($items)): ?>
    <div class="cc-empty-state" style="padding:40px 0;">
      <?= icon('trending-up', 40) ?>
      <p>لا توجد <?= $filter === 'pending' ? 'أسعار في الانتظار' : 'بيانات' ?>.</p>
    </div>
  <?php else: ?>

  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
    <div style="display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap;">
      <?php if ($filter === 'pending'): ?>
      <button name="action" value="approve" class="btn btn-primary btn-sm"><?= icon('check',14) ?> قبول المحدد</button>
      <button name="action" value="reject"  class="btn btn-ghost btn-sm"><?= icon('x',14) ?> رفض المحدد</button>
      <?php endif; ?>
      <?php if ($filter !== 'pending'): ?>
      <button name="action" value="delete_all" class="btn btn-ghost btn-sm" style="color:var(--debt);" onclick="return confirm('حذف جميع المعالجة؟')">
        <?= icon('trash',14) ?> حذف المعالجة
      </button>
      <?php endif; ?>
      <label style="display:flex; align-items:center; gap:6px; font-size:13px; cursor:pointer; margin-inline-start:auto;">
        <input type="checkbox" id="chk-all" onchange="document.querySelectorAll('.row-chk').forEach(c=>c.checked=this.checked)">
        تحديد الكل
      </label>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th style="width:28px;"></th>
            <th>المنتج</th>
            <th>السعر</th>
            <th>الوحدة</th>
            <th>المجموعة</th>
            <th>الرسالة الأصلية</th>
            <th>الوقت</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $r): ?>
          <tr>
            <td><input type="checkbox" class="row-chk" name="ids[]" value="<?= $r['id'] ?>"></td>
            <td style="font-weight:600;"><?= h($r['product_name']) ?></td>
            <td style="font-family:var(--mono);">
              <?= number_format((float)$r['price'], 0) ?> <?= h(currencySymbol($r['currency'])) ?>
            </td>
            <td><?= h($r['unit']) ?></td>
            <td style="font-size:12px; color:var(--ink-faint);"><?= h($r['source_group']) ?></td>
            <td style="font-size:11px; color:var(--ink-faint); max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
                title="<?= h($r['raw_text']) ?>">
              <?= h(mb_substr($r['raw_text'], 0, 60)) ?>…
            </td>
            <td style="font-size:12px; white-space:nowrap;"><?= date('m/d H:i', strtotime($r['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </form>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
