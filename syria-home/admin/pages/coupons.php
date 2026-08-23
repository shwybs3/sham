<?php
$msg = null;
$products = $pdo->query("SELECT id, name, price FROM products WHERE status='published' ORDER BY name")->fetchAll();

if (isset($_GET['delete']) && csrf_check_get()) {
    $pdo->prepare("DELETE FROM coupons WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: ?page=coupons'); exit;
}

if (isset($_GET['toggle']) && csrf_check_get()) {
    $pdo->prepare("UPDATE coupons SET status = IF(status='active','disabled','active') WHERE id = ?")->execute([(int)$_GET['toggle']]);
    header('Location: ?page=coupons'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $code = strtoupper(trim($_POST['code'] ?? '')) ?: coupon_random_code();
    $fields = [
        'code'           => $code,
        'discount_type'  => in_array($_POST['discount_type'] ?? '', ['percent', 'fixed'], true) ? $_POST['discount_type'] : 'percent',
        'discount_value' => (float)($_POST['discount_value'] ?? 10),
        'product_id'     => ($_POST['product_id'] ?? '') !== '' ? (int)$_POST['product_id'] : null,
        'max_uses'       => trim((string)($_POST['max_uses'] ?? '')) !== '' ? (int)$_POST['max_uses'] : null,
        'expires_at'     => trim((string)($_POST['expires_at'] ?? '')) !== '' ? $_POST['expires_at'] . ' 23:59:59' : null,
    ];
    $sql = "INSERT INTO coupons (" . implode(',', array_keys($fields)) . ") VALUES (" . implode(',', array_map(fn($k) => ":$k", array_keys($fields))) . ")";
    try {
        $pdo->prepare($sql)->execute($fields);
        $msg = ['ok', "Promo code $code created."];
    } catch (PDOException $e) {
        $msg = ['err', str_contains($e->getMessage(), 'Duplicate') ? 'That code already exists — try another.' : $e->getMessage()];
    }
}

$coupons = $pdo->query("SELECT c.*, p.name AS product_name FROM coupons c LEFT JOIN products p ON p.id = c.product_id ORDER BY c.created_at DESC")->fetchAll();
$suggested = coupon_random_code();
?>
<?php if ($msg): flash($msg[0], $msg[1]); endif; ?>

<div class="row2">
  <div class="card">
    <h3 style="margin-top:0"><i class="fa-solid fa-ticket"></i> Create a promo code</h3>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <label>Code (leave blank to auto-generate)</label>
      <input type="text" name="code" placeholder="<?= e($suggested) ?>" style="text-transform:uppercase;font-family:'JetBrains Mono',monospace">
      <div class="row2">
        <div><label>Discount type</label>
          <select name="discount_type">
            <option value="percent">Percent off</option>
            <option value="fixed">Fixed amount off (USD)</option>
          </select>
        </div>
        <div><label>Discount value</label>
          <input type="number" name="discount_value" value="10" min="0" step="0.01">
        </div>
      </div>
      <label>Applies to</label>
      <select name="product_id">
        <option value="">All products</option>
        <?php foreach ($products as $p): ?>
          <option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?> ($<?= number_format((float)$p['price'], 2) ?>)</option>
        <?php endforeach; ?>
      </select>
      <div class="row2">
        <div><label>Max uses (blank = unlimited)</label><input type="number" name="max_uses" min="1"></div>
        <div><label>Expires (blank = never)</label><input type="date" name="expires_at"></div>
      </div>
      <button class="btn" style="margin-top:14px" type="submit"><i class="fa-solid fa-plus"></i> Create code</button>
    </form>
  </div>

  <div class="card" style="background:#f0f9ff;border-color:#bae6fd">
    <h3 style="margin-top:0"><i class="fa-solid fa-circle-info" style="color:#0284c7"></i> How this works</h3>
    <p class="hint" style="font-size:13.5px;line-height:1.7">
      A code applied at checkout deducts from the product's real price before the crypto invoice is created — the discount is genuine, not a display trick. Product cards already show a strikethrough "compare at" price when you set one in the product's own edit form; a promo code here is a separate, stackable discount on top of that.
    </p>
    <p class="hint" style="font-size:13.5px;line-height:1.7">
      Leave "Applies to" on <b>All products</b> for a storewide code, or pick one product for a targeted discount. Set a max-use count for a limited flash sale, or leave it unlimited for an evergreen code you print on marketing pages.
    </p>
  </div>
</div>

<div class="card">
  <h3 style="margin-top:0">All promo codes</h3>
  <table>
    <tr><th>Code</th><th>Discount</th><th>Applies to</th><th>Used</th><th>Expires</th><th>Status</th><th></th></tr>
    <?php foreach ($coupons as $c): ?>
    <tr>
      <td><code style="font-weight:800"><?= e($c['code']) ?></code></td>
      <td><?= $c['discount_type'] === 'percent' ? (int)$c['discount_value'] . '%' : '$' . number_format((float)$c['discount_value'], 2) ?> off</td>
      <td><?= $c['product_id'] ? e($c['product_name'] ?? '(deleted product)') : 'All products' ?></td>
      <td><?= (int)$c['used_count'] ?><?= $c['max_uses'] !== null ? ' / ' . (int)$c['max_uses'] : '' ?></td>
      <td><?= $c['expires_at'] ? e(date('M j, Y', strtotime($c['expires_at']))) : '—' ?></td>
      <td><?= $c['status'] === 'active' ? '<span class="badge ok">Active</span>' : '<span class="badge off">Disabled</span>' ?></td>
      <td style="white-space:nowrap">
        <a class="btn gray sm" href="?page=coupons&toggle=<?= (int)$c['id'] ?>&csrf=<?= csrf_token() ?>"><?= $c['status'] === 'active' ? 'Disable' : 'Enable' ?></a>
        <a class="btn red sm" href="?page=coupons&delete=<?= (int)$c['id'] ?>&csrf=<?= csrf_token() ?>" onclick="return confirm('Delete this code?')">Delete</a>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$coupons): ?><tr><td colspan="7" style="color:#94a3b8;text-align:center;padding:30px">No promo codes yet.</td></tr><?php endif; ?>
  </table>
</div>
