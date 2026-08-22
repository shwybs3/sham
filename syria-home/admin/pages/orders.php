<?php
$validStatuses = ['new', 'contacted', 'paid', 'delivered', 'cancelled'];

if (isset($_GET['delete']) && csrf_check_get()) {
    $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: ?page=orders'); exit;
}

if (isset($_GET['delete_payment']) && csrf_check_get()) {
    $pdo->prepare("DELETE FROM payments WHERE id = ?")->execute([(int)$_GET['delete_payment']]);
    header('Location: ?page=orders'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_status' && csrf_check()) {
    $status = $_POST['status'] ?? 'new';
    if (in_array($status, $validStatuses, true)) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$status, (int)($_POST['id'] ?? 0)]);
    }
    header('Location: ?page=orders&saved=1'); exit;
}

$counts = [];
foreach ($pdo->query("SELECT status, COUNT(*) c FROM orders GROUP BY status") as $r) $counts[$r['status']] = (int)$r['c'];
$orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 200")->fetchAll();
$payments = $pdo->query("SELECT * FROM payments ORDER BY created_at DESC LIMIT 200")->fetchAll();
$messages = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 50")->fetchAll();

$refTypeLabels = ['product' => 'Product', 'tip' => 'Tip', 'unlock_article' => 'Article unlock', 'unlock_tool' => 'Tool unlock'];
$payStatusBadge = function (string $s): string {
    if (in_array($s, ['finished', 'confirmed'], true)) return '<span class="badge ok">' . ucfirst($s) . '</span>';
    if (in_array($s, ['failed', 'expired', 'refunded'], true)) return '<span class="badge off">' . ucfirst($s) . '</span>';
    return '<span class="badge warn">' . ucfirst($s ?: 'pending') . '</span>';
};
?>
<?php if (isset($_GET['saved'])): flash('ok', 'Order updated.'); endif; ?>

<div class="grid-stats">
  <div class="stat"><i class="fa-solid fa-inbox"></i><div class="n"><?= $counts['new'] ?? 0 ?></div><div class="l">New</div></div>
  <div class="stat"><i class="fa-solid fa-comments"></i><div class="n"><?= $counts['contacted'] ?? 0 ?></div><div class="l">Contacted</div></div>
  <div class="stat"><i class="fa-solid fa-credit-card"></i><div class="n"><?= $counts['paid'] ?? 0 ?></div><div class="l">Paid</div></div>
  <div class="stat"><i class="fa-solid fa-box-open"></i><div class="n"><?= $counts['delivered'] ?? 0 ?></div><div class="l">Delivered</div></div>
  <div class="stat"><i class="fa-solid fa-wallet"></i><div class="n"><?= count(array_filter($payments, fn($p) => in_array($p['status'], ['finished','confirmed'], true))) ?></div><div class="l">Crypto payments confirmed</div></div>
</div>

<div class="card">
  <h3 style="margin-top:0">Product requests</h3>
  <?php if (!$orders): ?>
    <p class="hint">No requests yet. They'll appear here when someone submits the form on a product page.</p>
  <?php else: ?>
  <table>
    <tr><th>Date</th><th>Product</th><th>Customer</th><th>Amount</th><th>Status</th><th></th></tr>
    <?php foreach ($orders as $o): ?>
    <tr>
      <td style="white-space:nowrap"><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
      <td><?= e($o['product_name']) ?></td>
      <td>
        <b><?= e($o['name']) ?></b><br>
        <a href="mailto:<?= e($o['email']) ?>" style="font-size:12px;color:var(--brand1)"><?= e($o['email']) ?></a>
        <?php if (trim((string)$o['note']) !== ''): ?><div class="hint" style="margin-top:4px"><?= e($o['note']) ?></div><?php endif; ?>
      </td>
      <td style="white-space:nowrap"><?= e($o['currency']) ?> <?= number_format((float)$o['amount'], 2) ?></td>
      <td>
        <form method="post" style="display:flex;gap:6px;align-items:center">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="action" value="set_status">
          <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
          <select name="status" onchange="this.form.submit()" style="font-size:12px;padding:5px 8px">
            <?php foreach ($validStatuses as $s): ?>
              <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </td>
      <td><a class="btn red sm" href="?page=orders&delete=<?= (int)$o['id'] ?>&csrf=<?= csrf_token() ?>" onclick="return confirm('Delete this order record?')">Delete</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>

<div class="card">
  <h3 style="margin-top:0">Crypto payments (NOWPayments)</h3>
  <?php if (!NOWPayments::isConfigured()): ?>
    <p class="hint">Not set up yet — add an API key in <a href="?page=settings&tab=payments">Settings &gt; Payments</a> to accept real crypto checkout.</p>
  <?php elseif (!$payments): ?>
    <p class="hint">No crypto payments yet. They'll appear here as soon as someone starts a checkout.</p>
  <?php else: ?>
  <table>
    <tr><th>Date</th><th>Type</th><th>For</th><th>Amount</th><th>Currency</th><th>Status</th><th></th></tr>
    <?php foreach ($payments as $p): ?>
    <tr>
      <td style="white-space:nowrap"><?= date('M j, Y H:i', strtotime($p['created_at'])) ?></td>
      <td><?= e($refTypeLabels[$p['reference_type']] ?? $p['reference_type']) ?></td>
      <td><?= e($p['reference_label']) ?><?php if ($p['customer_email']): ?><br><a href="mailto:<?= e($p['customer_email']) ?>" style="font-size:12px;color:var(--brand1)"><?= e($p['customer_email']) ?></a><?php endif; ?></td>
      <td style="white-space:nowrap">$<?= number_format((float)$p['price_usd'], 2) ?></td>
      <td style="white-space:nowrap"><?= e(strtoupper($p['pay_currency'])) ?></td>
      <td><?= $payStatusBadge($p['status']) ?></td>
      <td><a class="btn red sm" href="?page=orders&delete_payment=<?= (int)$p['id'] ?>&csrf=<?= csrf_token() ?>" onclick="return confirm('Delete this payment record?')">Delete</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>

<div class="card">
  <h3 style="margin-top:0">Contact messages</h3>
  <?php if (!$messages): ?>
    <p class="hint">No messages yet.</p>
  <?php else: ?>
  <table>
    <tr><th>Date</th><th>From</th><th>Message</th></tr>
    <?php foreach ($messages as $m): ?>
    <tr>
      <td style="white-space:nowrap"><?= date('M j, Y', strtotime($m['created_at'])) ?></td>
      <td><b><?= e($m['name']) ?></b><br><a href="mailto:<?= e($m['email']) ?>" style="font-size:12px;color:var(--brand1)"><?= e($m['email']) ?></a></td>
      <td style="font-size:13px"><?= e($m['message']) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>
