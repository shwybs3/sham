<?php
require_once __DIR__ . '/../config.php';
requireAdminLogin();

$currency = $settings['currency'] ?? 'ل.س';
$error = '';
$ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck()) {
        $error = 'انتهت صلاحية الجلسة، أعد المحاولة.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'add') {
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            if ($name === '') {
                $error = 'اسم الزبون مطلوب.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO customers (name, phone, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$name, $phone !== '' ? $phone : null]);
                $ok = 'تمت إضافة الزبون بنجاح.';
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
            $stmt->execute([$id]);
            $ok = 'تم حذف الزبون وكل عملياته.';
        }
    }
}

$search = trim($_GET['q'] ?? '');
$customers = getCustomersWithBalances($pdo, $search, 'balance_desc');

$pageTitle = 'الزبائن';
$activeNav = 'customers';
require __DIR__ . '/includes/admin-header.php';
?>
<style>
  .add-form-row{display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;}
  .add-form-row .field{flex:1; min-width:160px; margin-bottom:0;}
  .search-inline{max-width:320px; margin-bottom:18px;}
  .modal-overlay{position:fixed; inset:0; background:rgba(0,0,0,.6); display:none; align-items:center; justify-content:center; z-index:100;}
  .modal-overlay.show{display:flex;}
  .modal-box{background:var(--panel); border:1px solid var(--line); border-radius:16px; padding:26px; max-width:360px; width:90%;}
  .modal-box h3{margin:0 0 10px; font-family:var(--disp);}
  .modal-box p{color:var(--ink-dim); font-size:14px; margin-bottom:20px;}
  .modal-actions{display:flex; gap:10px;}
</style>

<?php if ($ok): ?><div class="notice ok"><?= icon('check-circle', 16) ?> <?= h($ok) ?></div><?php endif; ?>
<?php if ($error): ?><div class="notice err"><?= icon('alert', 16) ?> <?= h($error) ?></div><?php endif; ?>

<div class="panel">
  <h2><?= icon('plus-circle', 18) ?> إضافة زبون جديد</h2>
  <form method="post" class="add-form-row">
    <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
    <input type="hidden" name="action" value="add">
    <div class="field"><label>الاسم</label><input type="text" name="name" required placeholder="اسم الزبون"></div>
    <div class="field"><label>رقم الهاتف (اختياري)</label><input type="tel" name="phone" placeholder="09xxxxxxxx"></div>
    <button class="btn btn-primary" type="submit"><?= icon('plus', 16) ?> إضافة</button>
  </form>
</div>

<div class="panel">
  <h2><?= icon('users', 18) ?> كل الزبائن (<?= count($customers) ?>)</h2>
  <form method="get" class="search-inline">
    <div class="search-box" style="padding:4px 6px 4px 14px;">
      <?= icon('search', 17) ?>
      <input type="text" name="q" value="<?= h($search) ?>" placeholder="بحث بالاسم..." onchange="this.form.submit()">
    </div>
  </form>
  <div class="table-wrap">
    <table>
      <thead><tr><th>الاسم</th><th>الهاتف</th><th>الرصيد</th><th>عمليات</th></tr></thead>
      <tbody>
      <?php foreach ($customers as $c): $bal = (float)$c['balance']; ?>
        <tr>
          <td><a href="customer.php?id=<?= (int)$c['id'] ?>" style="color:var(--ink); font-weight:600;"><?= h($c['name']) ?></a></td>
          <td style="color:var(--ink-dim);"><?= h($c['phone'] ?: '—') ?></td>
          <td><span class="badge <?= $bal > 0 ? 'badge-debt' : 'badge-ok' ?>"><?= money($bal) ?> <?= h($currency) ?></span></td>
          <td>
            <div class="row-actions">
              <a class="icon-btn" href="customer.php?id=<?= (int)$c['id'] ?>" title="التفاصيل"><?= icon('receipt', 15) ?></a>
              <button class="icon-btn danger" title="حذف" onclick="confirmDelete(<?= (int)$c['id'] ?>, '<?= h(addslashes($c['name'])) ?>')"><?= icon('trash', 15) ?></button>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$customers): ?><tr><td colspan="4" style="text-align:center; color:var(--ink-faint); padding:30px;">لا يوجد زبائن.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="delete-modal">
  <div class="modal-box">
    <h3><?= icon('alert', 18) ?> تأكيد الحذف</h3>
    <p id="delete-modal-text"></p>
    <form method="post" id="delete-form">
      <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" id="delete-id">
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost btn-block" onclick="closeModal()">إلغاء</button>
        <button type="submit" class="btn btn-danger btn-block"><?= icon('trash', 16) ?> حذف نهائيًا</button>
      </div>
    </form>
  </div>
</div>

<script>
function confirmDelete(id, name){
  document.getElementById('delete-id').value = id;
  document.getElementById('delete-modal-text').textContent = 'سيتم حذف "' + name + '" وكل سجل ديونه ودفعاته نهائيًا. هذا الإجراء لا يمكن التراجع عنه.';
  document.getElementById('delete-modal').classList.add('show');
}
function closeModal(){ document.getElementById('delete-modal').classList.remove('show'); }
</script>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
