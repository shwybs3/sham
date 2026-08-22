<?php
$cats = $pdo->query("SELECT * FROM categories WHERE type='tool' ORDER BY name")->fetchAll();
$msg = null;

if (isset($_GET['delete']) && csrf_check_get()) {
    $pdo->prepare("DELETE FROM tools WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: ?page=tools'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save' && csrf_check()) {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '') ?: slugify($name);
    $toolKey = $_POST['tool_key'] ?? '';
    if ($name === '' || !array_key_exists($toolKey, TOOL_REGISTRY)) {
        $msg = ['err', 'Name and a valid engine (Tool Key) are required.'];
    } else {
        $fields = [
            'name' => $name, 'slug' => $slug,
            'category_id' => $_POST['category_id'] ?: null,
            'icon_class' => trim($_POST['icon_class'] ?? '') ?: TOOL_REGISTRY[$toolKey]['icon'],
            'tool_key' => $toolKey,
            'short_description' => trim($_POST['short_description'] ?? ''),
            'full_description' => $_POST['full_description'] ?? '',
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
            'status' => isset($_POST['enabled']) ? 'published' : 'draft',
        ];
        if ($id) {
            $sql = "UPDATE tools SET " . implode(',', array_map(fn($k) => "$k = :$k", array_keys($fields))) . " WHERE id = :id";
            $fields['id'] = $id;
            $pdo->prepare($sql)->execute($fields);
        } else {
            $sql = "INSERT INTO tools (" . implode(',', array_keys($fields)) . ") VALUES (" . implode(',', array_map(fn($k) => ":$k", array_keys($fields))) . ")";
            try { $pdo->prepare($sql)->execute($fields); }
            catch (PDOException $e) { $fields['slug'] .= '-' . substr(md5((string)microtime(true)), 0, 5); $pdo->prepare($sql)->execute($fields); }
        }
        header('Location: ?page=tools&saved=1'); exit;
    }
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM tools WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}
$showForm = isset($_GET['new']) || $editing;
?>
<?php if (isset($_GET['saved'])): flash('ok', 'Tool saved.'); endif; ?>
<?php if ($msg): flash('err', $msg[1]); endif; ?>

<?php if (!$showForm): ?>
  <div class="card">
    <div class="toolbar"><h3 style="margin:0">All tools</h3><a class="btn sm" href="?page=tools&new=1"><i class="fa-solid fa-plus"></i> New tool</a></div>
    <table>
      <tr><th>Name</th><th>Engine</th><th>Slug</th><th>Status</th><th>Uses</th><th></th></tr>
      <?php foreach ($pdo->query("SELECT * FROM tools ORDER BY id DESC") as $t): ?>
      <tr>
        <td><i class="fa-solid <?= e($t['icon_class']) ?>"></i> <?= e($t['name']) ?></td>
        <td><?= e(TOOL_REGISTRY[$t['tool_key']]['name'] ?? $t['tool_key']) ?></td>
        <td><code><?= e($t['slug']) ?></code></td>
        <td><?= $t['status'] === 'published' ? '<span class="badge ok">Live</span>' : '<span class="badge off">Draft</span>' ?></td>
        <td><?= number_format((int)$t['uses_count']) ?></td>
        <td style="white-space:nowrap">
          <a class="btn gray sm" href="?page=tools&edit=<?= (int)$t['id'] ?>">Edit</a>
          <a class="btn red sm" href="?page=tools&delete=<?= (int)$t['id'] ?>&csrf=<?= csrf_token() ?>" onclick="return confirm('Delete this tool?')">Delete</a>
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
        <div><label>Tool name</label><input type="text" name="name" value="<?= e($editing['name'] ?? '') ?>" required></div>
        <div><label>Slug (leave blank to auto-generate)</label><input type="text" name="slug" value="<?= e($editing['slug'] ?? '') ?>"></div>
      </div>

      <div class="row2">
        <div><label>Engine (Tool Key) — must match a built-in tool</label>
          <select name="tool_key" required>
            <option value="">— choose —</option>
            <?php foreach (TOOL_REGISTRY as $k => $info): ?>
              <option value="<?= $k ?>" <?= ($editing['tool_key'] ?? '') === $k ? 'selected' : '' ?>><?= e($info['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <p class="hint">This picks which JS engine powers the tool page. Add new engines in assets/js/tools.js + includes/tool_registry.php.</p>
        </div>
        <div><label>Category</label>
          <select name="category_id">
            <option value="">— None —</option>
            <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= (($editing['category_id'] ?? null) == $c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>

      <label>Icon class (Font Awesome, e.g. fa-file-image)</label>
      <input type="text" name="icon_class" value="<?= e($editing['icon_class'] ?? '') ?>">

      <label>Short description (shown on cards, max ~150 chars)</label>
      <textarea name="short_description" style="min-height:60px"><?= e($editing['short_description'] ?? '') ?></textarea>

      <label>Full description (shown below the tool, HTML allowed — good for SEO)</label>
      <textarea name="full_description" style="min-height:160px;font-family:'JetBrains Mono',monospace;font-size:13px"><?= e($editing['full_description'] ?? '') ?></textarea>

      <h3>SEO</h3>
      <label>Meta title</label><input type="text" name="meta_title" value="<?= e($editing['meta_title'] ?? '') ?>">
      <label>Meta description</label><textarea name="meta_description" style="min-height:60px"><?= e($editing['meta_description'] ?? '') ?></textarea>
      <label>Meta keywords</label><input type="text" name="meta_keywords" value="<?= e($editing['meta_keywords'] ?? '') ?>">

      <label style="display:flex;align-items:center;gap:8px;font-weight:600;margin-top:16px"><input type="checkbox" name="enabled" style="width:auto" <?= (($editing['status'] ?? 'published') === 'published') ? 'checked' : '' ?>> Enable tool (visible on site)</label>

      <div style="margin-top:20px;display:flex;gap:10px">
        <button class="btn" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save tool</button>
        <a class="btn gray" href="?page=tools">Cancel</a>
      </div>
    </form>
  </div>
<?php endif; ?>
