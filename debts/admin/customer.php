<?php
require_once __DIR__ . '/../config.php';
requireAdminLogin();

$id = (int)($_GET['id'] ?? 0);
$customer = getCustomer($pdo, $id);
if (!$customer) { header('Location: customers.php'); exit; }

$currency = $settings['currency'] ?? 'ل.س';
$error = '';
$ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck()) {
        $error = 'انتهت صلاحية الجلسة، أعد المحاولة.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_info') {
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $notes = trim($_POST['notes'] ?? '');
            if ($name === '') {
                $error = 'الاسم مطلوب.';
            } else {
                $stmt = $pdo->prepare("UPDATE customers SET name=?, phone=?, notes=? WHERE id=?");
                $stmt->execute([$name, $phone ?: null, $notes ?: null, $id]);
                $customer['name'] = $name; $customer['phone'] = $phone; $customer['notes'] = $notes;
                $ok = 'تم تحديث بيانات الزبون.';
            }
        }

        elseif ($action === 'add_tx') {
            $type = $_POST['type'] ?? '';
            $amount = (float)($_POST['amount'] ?? 0);
            $desc = trim($_POST['description'] ?? '');
            if (!in_array($type, ['purchase', 'payment'], true) || $amount <= 0) {
                $error = 'أدخل نوعًا ومبلغًا صحيحين.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO transactions (customer_id, type, amount, description, created_at) VALUES (?,?,?,?,NOW())");
                $stmt->execute([$id, $type, $amount, $desc ?: null]);
                $ok = 'تمت إضافة العملية.';
            }
        }

        elseif ($action === 'edit_tx') {
            $txId = (int)($_POST['tx_id'] ?? 0);
            $type = $_POST['type'] ?? '';
            $amount = (float)($_POST['amount'] ?? 0);
            $desc = trim($_POST['description'] ?? '');
            if (!in_array($type, ['purchase', 'payment'], true) || $amount <= 0) {
                $error = 'أدخل نوعًا ومبلغًا صحيحين.';
            } else {
                $stmt = $pdo->prepare("UPDATE transactions SET type=?, amount=?, description=? WHERE id=? AND customer_id=?");
                $stmt->execute([$type, $amount, $desc ?: null, $txId, $id]);
                $ok = 'تم تعديل العملية.';
            }
        }

        elseif ($action === 'delete_tx') {
            $txId = (int)($_POST['tx_id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM transactions WHERE id=? AND customer_id=?");
            $stmt->execute([$txId, $id]);
            $ok = 'تم حذف العملية.';
        }

        elseif ($action === 'mark_paid') {
            $bal = getCustomerBalance($pdo, $id);
            if ($bal > 0) {
                $stmt = $pdo->prepare("INSERT INTO transactions (customer_id, type, amount, description, created_at) VALUES (?, 'payment', ?, 'تسديد كامل الرصيد', NOW())");
                $stmt->execute([$id, $bal]);
                $ok = 'تم تسجيل تسديد كامل الرصيد.';
            }
        }

        elseif ($action === 'delete_customer') {
            $stmt = $pdo->prepare("DELETE FROM customers WHERE id=?");
            $stmt->execute([$id]);
            header('Location: customers.php');
            exit;
        }
    }
}

$transactions = getCustomerTransactions($pdo, $id);
$balance = getCustomerBalance($pdo, $id);

$pageTitle = $customer['name'];
$activeNav = 'customers';
require __DIR__ . '/includes/admin-header.php';
?>
<style>
  .cust-grid{display:grid; grid-template-columns:340px 1fr; gap:22px; align-items:start;}
  @media(max-width:900px){ .cust-grid{grid-template-columns:1fr;} }
  .tx-edit-row{display:none; gap:8px; margin-top:10px; padding-top:10px; border-top:1px dashed var(--line); flex-wrap:wrap;}
  .tx-edit-row.show{display:flex;}
  .tx-edit-row .field{flex:1; min-width:110px; margin-bottom:0;}
  .type-pill{display:inline-flex; gap:6px; align-items:center;}
  .type-pill button{padding:8px 12px; border-radius:8px; border:1px solid var(--line); background:var(--panel-2); color:var(--ink-dim); cursor:pointer; font-size:13px; font-weight:600;}
  .type-pill button.active[data-t="purchase"]{background:rgba(239,106,95,.12); border-color:var(--debt); color:var(--debt);}
  .type-pill button.active[data-t="payment"]{background:rgba(34,197,142,.12); border-color:var(--brand); color:var(--brand);}
</style>

<a href="customers.php" class="btn btn-ghost btn-sm" style="margin-bottom:18px;"><?= icon('arrow-right', 16) ?> كل الزبائن</a>

<?php if ($ok): ?><div class="notice ok"><?= icon('check-circle', 16) ?> <?= h($ok) ?></div><?php endif; ?>
<?php if ($error): ?><div class="notice err"><?= icon('alert', 16) ?> <?= h($error) ?></div><?php endif; ?>

<div class="cust-grid">
  <div>
    <div class="panel">
      <h2><?= icon('user', 18) ?> بيانات الزبون</h2>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
        <input type="hidden" name="action" value="update_info">
        <div class="field"><label>الاسم</label><input type="text" name="name" value="<?= h($customer['name']) ?>" required></div>
        <div class="field"><label>الهاتف</label><input type="tel" name="phone" value="<?= h($customer['phone'] ?? '') ?>"></div>
        <div class="field"><label>ملاحظات</label><textarea name="notes" rows="2"><?= h($customer['notes'] ?? '') ?></textarea></div>
        <button class="btn btn-primary btn-block" type="submit"><?= icon('check', 16) ?> حفظ البيانات</button>
      </form>
    </div>

    <div class="panel" style="text-align:center;">
      <div style="font-size:12.5px; color:var(--ink-dim); margin-bottom:6px;">الرصيد الحالي</div>
      <div style="font-family:var(--mono); font-size:28px; font-weight:800; color:<?= $balance > 0 ? 'var(--debt)' : 'var(--brand)' ?>; margin-bottom:16px;">
        <?= money($balance) ?> <?= h($currency) ?>
      </div>
      <?php if ($balance > 0): ?>
      <form method="post" onsubmit="return confirm('تسجيل تسديد كامل الرصيد؟');">
        <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
        <input type="hidden" name="action" value="mark_paid">
        <button class="btn btn-primary btn-block" type="submit"><?= icon('check-circle', 16) ?> تسديد كامل الرصيد</button>
      </form>
      <?php else: ?>
        <div class="badge badge-ok" style="display:inline-flex;"><?= icon('check-circle', 14) ?> لا يوجد دين مستحق</div>
      <?php endif; ?>
    </div>

    <div class="panel" style="border-color:rgba(239,106,95,.3);">
      <h2 style="color:var(--debt);"><?= icon('trash', 18) ?> حذف الزبون</h2>
      <p style="color:var(--ink-dim); font-size:13.5px; margin-bottom:14px;">سيؤدي هذا لحذف كل سجل عمليات هذا الزبون نهائيًا.</p>
      <form method="post" onsubmit="return confirm('تأكيد حذف هذا الزبون وكل عملياته نهائيًا؟');">
        <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
        <input type="hidden" name="action" value="delete_customer">
        <button class="btn btn-danger btn-block" type="submit"><?= icon('trash', 16) ?> حذف نهائيًا</button>
      </form>
    </div>
  </div>

  <div>
    <div class="panel">
      <h2><?= icon('plus-circle', 18) ?> إضافة عملية</h2>
      <form method="post" id="add-tx-form">
        <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
        <input type="hidden" name="action" value="add_tx">
        <input type="hidden" name="type" id="add-type" value="purchase">
        <div class="type-pill" style="margin-bottom:14px;">
          <button type="button" data-t="purchase" class="active" onclick="setAddType('purchase', this)"><?= icon('plus', 14) ?> مشتريات</button>
          <button type="button" data-t="payment" onclick="setAddType('payment', this)"><?= icon('check', 14) ?> دفعة</button>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
          <div class="field" style="flex:1; min-width:140px;"><label>المبلغ</label><input type="number" name="amount" min="0" step="1" required></div>
          <div class="field" style="flex:2; min-width:200px;"><label>الوصف (اختياري)</label><input type="text" name="description" placeholder="مثال: رز وسكر"></div>
        </div>
        <button class="btn btn-primary" type="submit"><?= icon('check', 16) ?> إضافة</button>
      </form>
    </div>

    <div class="panel">
      <h2><?= icon('receipt', 18) ?> سجل العمليات (<?= count($transactions) ?>)</h2>
      <div class="timeline">
        <?php if (!$transactions): ?>
          <div class="cc-empty-state" style="padding:30px;"><?= icon('receipt', 30) ?><p>لا توجد عمليات بعد.</p></div>
        <?php endif; ?>
        <?php foreach ($transactions as $t): $isP = $t['type'] === 'purchase'; ?>
        <div>
          <div class="tx-row <?= $isP ? 'purchase' : 'payment' ?>">
            <div class="tx-icon"><?= icon($isP ? 'plus' : 'check', 16) ?></div>
            <div class="tx-body">
              <div class="tx-desc"><?= h($t['description'] ?: ($isP ? 'مشتريات' : 'دفعة')) ?></div>
              <div class="tx-date"><?= icon('calendar', 12) ?> <?= date('Y-m-d — H:i', strtotime($t['created_at'])) ?></div>
            </div>
            <div class="tx-amount"><?= $isP ? '+' : '−' ?><?= money($t['amount']) ?> <?= h($currency) ?></div>
            <div class="row-actions">
              <button class="icon-btn" title="تعديل" onclick="toggleEdit(<?= (int)$t['id'] ?>)"><?= icon('edit', 14) ?></button>
              <form method="post" style="display:inline;" onsubmit="return confirm('حذف هذه العملية؟');">
                <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
                <input type="hidden" name="action" value="delete_tx">
                <input type="hidden" name="tx_id" value="<?= (int)$t['id'] ?>">
                <button class="icon-btn danger" title="حذف"><?= icon('trash', 14) ?></button>
              </form>
            </div>
          </div>
          <form method="post" class="tx-edit-row" id="edit-row-<?= (int)$t['id'] ?>">
            <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
            <input type="hidden" name="action" value="edit_tx">
            <input type="hidden" name="tx_id" value="<?= (int)$t['id'] ?>">
            <select name="type" class="field" style="max-width:130px;">
              <option value="purchase" <?= $isP ? 'selected' : '' ?>>مشتريات</option>
              <option value="payment" <?= !$isP ? 'selected' : '' ?>>دفعة</option>
            </select>
            <input type="number" name="amount" value="<?= h($t['amount']) ?>" min="0" step="1" required>
            <input type="text" name="description" value="<?= h($t['description'] ?? '') ?>" placeholder="الوصف">
            <button class="btn btn-primary btn-sm" type="submit"><?= icon('check', 14) ?> حفظ</button>
          </form>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<script>
function setAddType(t, btn){
  document.getElementById('add-type').value = t;
  [...btn.parentElement.children].forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}
function toggleEdit(id){
  document.getElementById('edit-row-' + id).classList.toggle('show');
}
</script>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
