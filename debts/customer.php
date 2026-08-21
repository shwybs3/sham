<?php
require_once __DIR__ . '/config.php';

$id = (int)($_GET['id'] ?? 0);
$customer = getCustomer($pdo, $id);
if (!$customer) {
    http_response_code(404);
    $pageTitle = 'الزبون غير موجود';
    require __DIR__ . '/includes/page-header.php';
    echo '<main class="page narrow"><div class="cc-empty-state">'
        . icon('users', 40)
        . '<p>هذا الزبون غير موجود.</p>'
        . '<a class="btn btn-primary" href="index.php">' . icon('arrow-right', 16) . ' العودة للرئيسية</a>'
        . '</div></main>';
    require __DIR__ . '/includes/page-footer.php';
    exit;
}

$transactions = getCustomerTransactions($pdo, $id);
$balance = getCustomerBalance($pdo, $id);
$currencyCode = $settings['currency'] ?? 'SYP';
$currency = currencySymbol($currencyCode);
$initial = mb_substr($customer['name'], 0, 1, 'UTF-8');

$pageTitle = $customer['name'] . ' — ' . ($settings['shop_name'] ?? 'دفتر الدكان');
require __DIR__ . '/includes/page-header.php';
?>
<main class="page narrow">
  <a href="index.php" class="btn btn-ghost btn-sm" style="margin-bottom:20px;"><?= icon('arrow-right', 16) ?> كل الزبائن</a>

  <div class="customer-hero">
    <div class="ch-avatar"><?= h($initial) ?></div>
    <div class="ch-info">
      <h1><?= h($customer['name']) ?></h1>
      <div class="meta">
        <?php if ($customer['phone']): ?><span><?= icon('phone', 14) ?> <?= h($customer['phone']) ?></span><?php endif; ?>
        <span><?= icon('receipt', 14) ?> <?= count($transactions) ?> عملية</span>
      </div>
    </div>
    <div class="ch-balance">
      <div class="label">الرصيد المستحق</div>
      <div class="val <?= $balance <= 0 ? 'zero' : '' ?>"><?= money($balance) ?> <?= h($currency) ?></div>
    </div>
  </div>

  <div class="timeline">
    <?php if (!$transactions): ?>
      <div class="cc-empty-state"><?= icon('receipt', 36) ?><p>لا توجد أي عمليات مسجّلة بعد.</p></div>
    <?php endif; ?>
    <?php foreach ($transactions as $i => $t):
      $isPurchase = $t['type'] === 'purchase';
    ?>
    <div class="tx-row <?= $isPurchase ? 'purchase' : 'payment' ?>" style="animation-delay:<?= min($i * 0.02, 0.3) ?>s">
      <div class="tx-icon"><?= icon($isPurchase ? 'plus' : 'check', 18) ?></div>
      <div class="tx-body">
        <div class="tx-desc">
          <?= h($t['description'] ?: ($isPurchase ? 'مشتريات' : 'دفعة')) ?>
          <?php if (!empty($t['product_type'])): ?>
            <span class="tx-product-badge"><?= h($t['product_type']) ?></span>
          <?php endif; ?>
        </div>
        <div class="tx-date"><?= icon('calendar', 12) ?> <?= date('Y-m-d — H:i', strtotime($t['created_at'])) ?></div>
      </div>
      <div class="tx-amount"><?= $isPurchase ? '+' : '−' ?><?= money($t['amount']) ?> <?= h($currency) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</main>
<?php require __DIR__ . '/includes/page-footer.php'; ?>
