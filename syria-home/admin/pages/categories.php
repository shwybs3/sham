<?php
$msg = null;

if (isset($_GET['delete']) && csrf_check_get()) {
    $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: ?page=categories'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $name = trim($_POST['name'] ?? '');
    if ($name !== '') {
        $slug = trim($_POST['slug'] ?? '') ?: slugify($name);
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, type, icon, color) VALUES (?,?,?,?,?)");
        try {
            $stmt->execute([$name, $slug, $_POST['type'] ?? 'article', trim($_POST['icon'] ?? 'fa-folder') ?: 'fa-folder', $_POST['color'] ?? '#6366f1']);
        } catch (PDOException $e) {
            $msg = ['err', 'A category with that slug already exists.'];
        }
    }
    if (!$msg) { header('Location: ?page=categories&saved=1'); exit; }
}

$categories = $pdo->query("SELECT c.*,
    (SELECT COUNT(*) FROM articles a WHERE a.category_id=c.id) AS article_count,
    (SELECT COUNT(*) FROM tools t WHERE t.category_id=c.id) AS tool_count
    FROM categories c ORDER BY type, name")->fetchAll();
?>
<?php if (isset($_GET['saved'])): flash('ok', 'Category added.'); endif; ?>
<?php if ($msg): flash('err', $msg[1]); endif; ?>

<div class="row2">
  <div class="card">
    <h3 style="margin-top:0">Add category</h3>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <label>Name</label><input type="text" name="name" required>
      <label>Slug (optional)</label><input type="text" name="slug">
      <div class="row2">
        <div><label>Type</label>
          <select name="type"><option value="article">Article</option><option value="tool">Tool</option></select>
        </div>
        <div><label>Icon (Font Awesome class)</label><input type="text" name="icon" value="fa-folder"></div>
      </div>
      <label>Color</label><input type="text" name="color" value="#6366f1">
      <button class="btn" style="margin-top:14px" type="submit"><i class="fa-solid fa-plus"></i> Add</button>
    </form>
  </div>

  <div class="card">
    <h3 style="margin-top:0">All categories</h3>
    <table>
      <tr><th>Name</th><th>Type</th><th>Items</th><th></th></tr>
      <?php foreach ($categories as $c): ?>
      <tr>
        <td><i class="fa-solid <?= e($c['icon']) ?>" style="color:<?= e($c['color']) ?>"></i> <?= e($c['name']) ?></td>
        <td><?= e(ucfirst($c['type'])) ?></td>
        <td><?= (int)($c['article_count'] + $c['tool_count']) ?></td>
        <td><a class="btn red sm" href="?page=categories&delete=<?= (int)$c['id'] ?>&csrf=<?= csrf_token() ?>" onclick="return confirm('Delete category? Items keep their content but lose this category.')">Delete</a></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>
