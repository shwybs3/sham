<?php
$msg = null;

if (isset($_GET['delete']) && csrf_check_get()) {
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: ?page=products'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save' && csrf_check()) {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        $msg = ['err', 'Product name is required.'];
    } else {
        $fields = [
            'name' => $name,
            'slug' => trim($_POST['slug'] ?? '') ?: slugify($name),
            'tagline' => trim($_POST['tagline'] ?? ''),
            'product_type' => trim($_POST['product_type'] ?? 'Script'),
            'icon_class' => trim($_POST['icon_class'] ?? '') ?: 'fa-cube',
            'art_key' => $_POST['art_key'] ?? 'p1',
            'price' => (float)($_POST['price'] ?? 0),
            'compare_at_price' => ($_POST['compare_at_price'] ?? '') === '' ? null : (float)$_POST['compare_at_price'],
            'currency' => trim($_POST['currency'] ?? 'USD') ?: 'USD',
            'badge' => trim($_POST['badge'] ?? ''),
            'short_description' => trim($_POST['short_description'] ?? ''),
            'full_description' => $_POST['full_description'] ?? '',
            'features' => trim($_POST['features'] ?? ''),
            'includes_list' => trim($_POST['includes_list'] ?? ''),
            'demo_url' => trim($_POST['demo_url'] ?? ''),
            'payment_url' => trim($_POST['payment_url'] ?? ''),
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
            'status' => isset($_POST['enabled']) ? 'published' : 'draft',
            'featured' => isset($_POST['featured']) ? 1 : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ];
        if ($id) {
            $sql = "UPDATE products SET " . implode(',', array_map(fn($k) => "$k = :$k", array_keys($fields))) . " WHERE id = :id";
            $fields['id'] = $id;
            $pdo->prepare($sql)->execute($fields);
        } else {
            $sql = "INSERT INTO products (" . implode(',', array_keys($fields)) . ") VALUES (" . implode(',', array_map(fn($k) => ":$k", array_keys($fields))) . ")";
            try { $pdo->prepare($sql)->execute($fields); }
            catch (PDOException $e) { $fields['slug'] .= '-' . substr(md5((string)microtime(true)), 0, 5); $pdo->prepare($sql)->execute($fields); }
        }
        header('Location: ?page=products&saved=1'); exit;
    }
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}
$showForm = isset($_GET['new']) || $editing;
?>
<?php if (isset($_GET['saved'])): flash('ok', 'Product saved.'); endif; ?>
<?php if ($msg): flash('err', $msg[1]); endif; ?>

<?php if (!$showForm): ?>
  <div class="card">
    <div class="toolbar"><h3 style="margin:0">Store products</h3><a class="btn sm" href="?page=products&new=1"><i class="fa-solid fa-plus"></i> New product</a></div>
    <p class="hint" style="margin-top:0">Leave <b>Payment URL</b> empty and the product page shows a "Request to buy" form that saves to your Orders inbox. Paste a checkout link (Gumroad, Paddle, PayPal, Stripe Payment Link…) to send buyers straight there instead.</p>
    <table>
      <tr><th>Product</th><th>Type</th><th>Price</th><th>Status</th><th>Featured</th><th>Views</th><th></th></tr>
      <?php foreach ($pdo->query("SELECT * FROM products ORDER BY sort_order, id") as $p): ?>
      <tr>
        <td><i class="fa-solid <?= e($p['icon_class']) ?>"></i> <?= e($p['name']) ?></td>
        <td><?= e($p['product_type']) ?></td>
        <td><?= e($p['currency']) ?> <?= number_format((float)$p['price'], 2) ?></td>
        <td><?= $p['status'] === 'published' ? '<span class="badge ok">Live</span>' : '<span class="badge off">Draft</span>' ?></td>
        <td><?= $p['featured'] ? '<i class="fa-solid fa-star" style="color:#eab308"></i>' : '—' ?></td>
        <td><?= number_format((int)$p['views']) ?></td>
        <td style="white-space:nowrap">
          <a class="btn gray sm" href="?page=products&edit=<?= (int)$p['id'] ?>">Edit</a>
          <a class="btn red sm" href="?page=products&delete=<?= (int)$p['id'] ?>&csrf=<?= csrf_token() ?>" onclick="return confirm('Delete this product?')">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
<?php else: ?>
  <div class="card">
    <form method="post">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">

      <div class="row2">
        <div><label>Product name</label><input type="text" name="name" value="<?= e($editing['name'] ?? '') ?>" required></div>
        <div><label>Slug (blank = auto)</label><input type="text" name="slug" value="<?= e($editing['slug'] ?? '') ?>"></div>
      </div>

      <label>Tagline (one line, shown on cards)</label>
      <input type="text" name="tagline" value="<?= e($editing['tagline'] ?? '') ?>">

      <div class="row2">
        <div><label>Product type (e.g. PHP Script, Template)</label><input type="text" name="product_type" value="<?= e($editing['product_type'] ?? 'PHP Script') ?>"></div>
        <div><label>Badge (e.g. Best Seller — blank for none)</label><input type="text" name="badge" value="<?= e($editing['badge'] ?? '') ?>"></div>
      </div>

      <div class="row2">
        <div><label>Icon class (Font Awesome)</label><input type="text" name="icon_class" value="<?= e($editing['icon_class'] ?? 'fa-cube') ?>"></div>
        <div><label>Artwork palette</label>
          <select name="art_key">
            <?php foreach (array_keys(ART_PALETTES) as $k): ?>
              <option value="<?= $k ?>" <?= ($editing['art_key'] ?? 'p1') === $k ? 'selected' : '' ?>><?= strtoupper($k) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="row2">
        <div><label>Price</label><input type="text" name="price" value="<?= e((string)($editing['price'] ?? '0')) ?>"></div>
        <div><label>Compare-at price (optional, shows a discount)</label><input type="text" name="compare_at_price" value="<?= e((string)($editing['compare_at_price'] ?? '')) ?>"></div>
      </div>
      <div class="row2">
        <div><label>Currency</label><input type="text" name="currency" value="<?= e($editing['currency'] ?? 'USD') ?>"></div>
        <div><label>Sort order (lower shows first)</label><input type="text" name="sort_order" value="<?= e((string)($editing['sort_order'] ?? 0)) ?>"></div>
      </div>

      <label>Short description (card + SEO fallback)</label>
      <textarea name="short_description" style="min-height:70px"><?= e($editing['short_description'] ?? '') ?></textarea>

      <label>Features — one per line</label>
      <textarea name="features" style="min-height:130px"><?= e($editing['features'] ?? '') ?></textarea>

      <label>What's included — one per line (shown in the buy box)</label>
      <textarea name="includes_list" style="min-height:100px"><?= e($editing['includes_list'] ?? '') ?></textarea>

      <label>Full description (HTML allowed)</label>
      <textarea name="full_description" style="min-height:180px;font-family:'JetBrains Mono',monospace;font-size:13px"><?= e($editing['full_description'] ?? '') ?></textarea>

      <div class="row2">
        <div><label>Live demo URL (optional)</label><input type="text" name="demo_url" value="<?= e($editing['demo_url'] ?? '') ?>"></div>
        <div><label>Payment / checkout URL (optional)</label><input type="text" name="payment_url" value="<?= e($editing['payment_url'] ?? '') ?>" placeholder="https://gumroad.com/l/...">
          <p class="hint">Leave empty to use the built-in request form instead.</p>
        </div>
      </div>

      <h3>SEO</h3>
      <label>Meta title</label><input type="text" name="meta_title" value="<?= e($editing['meta_title'] ?? '') ?>">
      <label>Meta description</label><textarea name="meta_description" style="min-height:60px"><?= e($editing['meta_description'] ?? '') ?></textarea>
      <label>Meta keywords</label><input type="text" name="meta_keywords" value="<?= e($editing['meta_keywords'] ?? '') ?>">

      <div style="display:flex;gap:20px;margin-top:16px;flex-wrap:wrap">
        <label style="display:flex;align-items:center;gap:8px;font-weight:600;margin:0"><input type="checkbox" name="enabled" style="width:auto" <?= (($editing['status'] ?? 'published') === 'published') ? 'checked' : '' ?>> Published</label>
        <label style="display:flex;align-items:center;gap:8px;font-weight:600;margin:0"><input type="checkbox" name="featured" style="width:auto" <?= !empty($editing['featured']) ? 'checked' : '' ?>> Featured</label>
      </div>

      <div style="margin-top:20px;display:flex;gap:10px">
        <button class="btn" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save product</button>
        <a class="btn gray" href="?page=products">Cancel</a>
      </div>
    </form>
  </div>
<?php endif; ?>
