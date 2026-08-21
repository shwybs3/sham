<?php
require_once __DIR__ . '/../config.php';
requireAdminLogin();

$currency = $settings['currency'] ?? 'ل.س';
$totalDebt = getTotalDebt($pdo);
$customerCount = (int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$todayCount = (int)$pdo->query("SELECT COUNT(*) FROM transactions WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$topDebtors = $pdo->query(
    "SELECT c.id, c.name, COALESCE(SUM(CASE WHEN t.type='purchase' THEN t.amount ELSE -t.amount END),0) AS balance
     FROM customers c LEFT JOIN transactions t ON t.customer_id = c.id
     GROUP BY c.id HAVING balance > 0 ORDER BY balance DESC LIMIT 6"
)->fetchAll();

$pageTitle = 'نظرة عامة';
$activeNav = 'home';
require __DIR__ . '/includes/admin-header.php';
?>
<div class="stat-strip" style="margin-top:0;">
  <div class="stat-card warn"><div class="s-icon"><?= icon('wallet', 20) ?></div><div><div class="stat-value"><?= money($totalDebt) ?> <?= h($currency) ?></div><div class="stat-label">إجمالي الديون المستحقة</div></div></div>
  <div class="stat-card"><div class="s-icon"><?= icon('users', 20) ?></div><div><div class="stat-value"><?= $customerCount ?></div><div class="stat-label">عدد الزبائن</div></div></div>
  <div class="stat-card"><div class="s-icon"><?= icon('receipt', 20) ?></div><div><div class="stat-value"><?= $todayCount ?></div><div class="stat-label">عمليات اليوم</div></div></div>
</div>

<div class="panel">
  <h2><?= icon('trend-up', 18) ?> أكبر الديون</h2>
  <?php if (!$topDebtors): ?>
    <p style="color:var(--ink-faint); font-size:14px; display:flex; align-items:center; gap:8px;"><?= icon('check-circle', 16) ?> لا يوجد ديون مستحقة حاليًا.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>الزبون</th><th>الرصيد</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($topDebtors as $d): ?>
        <tr>
          <td><?= h($d['name']) ?></td>
          <td><span class="badge badge-debt"><?= icon('wallet', 13) ?> <?= money($d['balance']) ?> <?= h($currency) ?></span></td>
          <td><a class="btn btn-ghost btn-sm" href="customer.php?id=<?= (int)$d['id'] ?>">التفاصيل</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<div class="panel">
  <h2><?= icon('plus-circle', 18) ?> تسجيل عملية جديدة</h2>
  <p style="color:var(--ink-dim); font-size:14px; margin-bottom:16px;">استخدم شاشة التسجيل السريع لإضافة دين أو دفعة خلال ثوانٍ.</p>
  <a class="btn btn-primary" href="quick-entry.php"><?= icon('plus-circle', 18) ?> فتح شاشة التسجيل السريع</a>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
