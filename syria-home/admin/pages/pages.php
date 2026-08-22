<?php
$msg = null;

if (isset($_GET['delete']) && csrf_check_get()) {
    $pdo->prepare("DELETE FROM pages WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: ?page=pages'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $id    = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $slug  = trim($_POST['slug'] ?? '') ?: slugify($title);
    $body  = $_POST['body'] ?? '';
    $fields = [
        'slug'             => $slug,
        'title'            => $title,
        'body'             => $body,
        'meta_title'       => trim($_POST['meta_title'] ?? ''),
        'meta_description' => trim($_POST['meta_description'] ?? ''),
        'show_in_footer'   => isset($_POST['show_in_footer']) ? 1 : 0,
        'status'           => $_POST['status'] ?? 'published',
    ];
    if ($title === '') {
        $msg = ['err', 'Title is required.'];
    } else {
        if ($id) {
            $sql = "UPDATE pages SET " . implode(',', array_map(fn($k) => "$k=:$k", array_keys($fields))) . ", updated_at=NOW() WHERE id=:id";
            $fields['id'] = $id;
            $pdo->prepare($sql)->execute($fields);
        } else {
            $sql = "INSERT INTO pages (" . implode(',', array_keys($fields)) . ") VALUES (" . implode(',', array_map(fn($k) => ":$k", array_keys($fields))) . ")";
            try { $pdo->prepare($sql)->execute($fields); }
            catch (PDOException $e) { $fields['slug'] .= '-' . substr(md5((string)microtime(true)), 0, 5); $pdo->prepare($sql)->execute($fields); }
        }
        if (($fields['status'] ?? '') === 'published') indexnow_ping(site_url('p/' . $slug));
        header('Location: ?page=pages&saved=1'); exit;
    }
}

$editing = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM pages WHERE id=?"); $st->execute([(int)$_GET['edit']]); $editing = $st->fetch();
}
$showForm = isset($_GET['new']) || $editing;
?>
<?php if (isset($_GET['saved'])): flash('ok', 'Page saved.'); endif; ?>
<?php if ($msg): flash($msg[0] === 'ok' ? 'ok' : 'err', $msg[1]); endif; ?>

<?php if (!$showForm): ?>
<div class="card">
  <div class="toolbar">
    <h3 style="margin:0">Static Pages</h3>
    <a class="btn sm" href="?page=pages&new=1"><i class="fa-solid fa-plus"></i> New page</a>
  </div>
  <p class="hint" style="margin-bottom:12px">These are standalone informational pages (About, Privacy, Contact, etc.) accessible at <code>/p/{slug}</code> on the public site. Check "Show in footer" to add them to the site footer automatically.</p>
  <table>
    <tr><th>Title</th><th>Slug</th><th>Status</th><th>Footer</th><th>Updated</th><th></th></tr>
    <?php
    try { $allPages = $pdo->query("SELECT * FROM pages ORDER BY created_at ASC")->fetchAll(); } catch (PDOException $e) { $allPages = []; }
    foreach ($allPages as $pg): ?>
    <tr>
      <td><?= e($pg['title']) ?></td>
      <td><code>/p/<?= e($pg['slug']) ?></code></td>
      <td><?= $pg['status'] === 'published' ? '<span class="badge ok">Published</span>' : '<span class="badge off">Draft</span>' ?></td>
      <td><?= $pg['show_in_footer'] ? '<i class="fa-solid fa-check" style="color:#16a34a"></i>' : '—' ?></td>
      <td><?= date('M j, Y', strtotime($pg['updated_at'])) ?></td>
      <td style="white-space:nowrap">
        <a class="btn gray sm" target="_blank" href="<?= e(site_url('p/' . $pg['slug'])) ?>">View</a>
        <a class="btn gray sm" href="?page=pages&edit=<?= (int)$pg['id'] ?>">Edit</a>
        <a class="btn red sm" href="?page=pages&delete=<?= (int)$pg['id'] ?>&csrf=<?= csrf_token() ?>" onclick="return confirm('Delete page?')">Delete</a>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($allPages)): ?><tr><td colspan="6" style="color:#94a3b8;text-align:center;padding:30px">No pages yet — click "New page" to create your first.</td></tr><?php endif; ?>
  </table>
</div>

<?php else: ?>
<div class="card">
  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
    <div class="row2">
      <div>
        <label>Page Title *</label>
        <input type="text" name="title" value="<?= e($editing['title'] ?? '') ?>" required placeholder="e.g. About Us">
      </div>
      <div>
        <label>Slug (URL)</label>
        <input type="text" name="slug" value="<?= e($editing['slug'] ?? '') ?>" placeholder="about-us">
        <p class="hint">Accessible at /p/{slug}. Auto-generated from title if blank.</p>
      </div>
    </div>
    <label>Page Content (HTML allowed)</label>
    <textarea name="body" style="min-height:380px"><?= e($editing['body'] ?? '') ?></textarea>
    <div class="row2">
      <div>
        <label>Meta Title</label>
        <input type="text" name="meta_title" value="<?= e($editing['meta_title'] ?? '') ?>">
      </div>
      <div>
        <label>Meta Description</label>
        <input type="text" name="meta_description" value="<?= e($editing['meta_description'] ?? '') ?>">
      </div>
    </div>
    <div style="display:flex;gap:28px;align-items:center;margin:14px 0">
      <label style="margin:0;display:flex;align-items:center;gap:7px;cursor:pointer">
        <input type="checkbox" name="show_in_footer" <?= ($editing['show_in_footer'] ?? 1) ? 'checked' : '' ?>> Show in site footer
      </label>
      <select name="status" style="width:auto">
        <option value="published" <?= ($editing['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Published</option>
        <option value="draft" <?= ($editing['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
      </select>
    </div>
    <div style="display:flex;gap:10px;margin-top:6px">
      <button class="btn" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save page</button>
      <a class="btn gray" href="?page=pages">Cancel</a>
    </div>
  </form>
</div>
<?php endif; ?>
