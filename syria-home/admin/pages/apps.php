<?php
$msg = null;

if (isset($_GET['delete']) && csrf_check_get()) {
    $pdo->prepare("DELETE FROM apps WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: ?page=apps'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save' && csrf_check()) {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $playUrl = trim($_POST['play_store_url'] ?? '');
    $shortDesc = trim($_POST['short_description'] ?? '');
    $developer = trim($_POST['developer'] ?? '');
    $iconPath = trim($_POST['icon_path'] ?? '');
    $screenshots = trim($_POST['screenshots'] ?? '[]');

    if ($playUrl !== '' && isset($_POST['fetch_from_play'])) {
        $info = fetch_play_store_app_info($playUrl);
        if ($info['ok']) {
            if ($name === '') $name = $info['name'];
            if ($shortDesc === '') $shortDesc = mb_substr($info['description'], 0, 380);
            if ($developer === '') $developer = $info['developer'];

            if (!empty($info['icon_url'])) {
                $iconResult = sh_fetch_and_store_image($info['icon_url'], 'apps', 512);
                if ($iconResult['ok']) $iconPath = $iconResult['path'];
                else $msg = ['err', 'Icon fetch failed: ' . $iconResult['error']];
            }

            $shotPaths = [];
            foreach ($info['screenshot_urls'] as $shotUrl) {
                $r = sh_fetch_and_store_image($shotUrl, 'apps', 1000);
                if ($r['ok']) $shotPaths[] = $r['path'];
            }
            if ($shotPaths) $screenshots = json_encode($shotPaths);
        } else {
            $msg = ['err', 'Play Store fetch failed: ' . $info['error']];
        }
    }

    if ($name === '' || $playUrl === '') {
        $msg = ['err', 'App name and Play Store URL are required.'];
    } else {
        $slug = trim($_POST['slug'] ?? '') ?: slugify($name);
        $fields = [
            'name' => $name, 'slug' => $slug, 'play_store_url' => $playUrl,
            'developer' => $developer, 'category' => trim($_POST['category'] ?? ''),
            'icon_path' => $iconPath, 'short_description' => $shortDesc,
            'full_description' => $_POST['full_description'] ?? '', 'screenshots' => $screenshots,
            'meta_title' => trim($_POST['meta_title'] ?? ''), 'meta_description' => trim($_POST['meta_description'] ?? ''),
            'status' => $_POST['status'] ?? 'published',
        ];
        if ($id) {
            $sql = "UPDATE apps SET " . implode(',', array_map(fn($k) => "$k = :$k", array_keys($fields))) . " WHERE id = :id";
            $fields['id'] = $id;
            $pdo->prepare($sql)->execute($fields);
        } else {
            $sql = "INSERT INTO apps (" . implode(',', array_keys($fields)) . ") VALUES (" . implode(',', array_map(fn($k) => ":$k", array_keys($fields))) . ")";
            try { $pdo->prepare($sql)->execute($fields); }
            catch (PDOException $e) { $fields['slug'] .= '-' . substr(md5((string)microtime(true)), 0, 5); $pdo->prepare($sql)->execute($fields); }
        }
        header('Location: ?page=apps&saved=1'); exit;
    }
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM apps WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}
$showForm = isset($_GET['new']) || $editing;
?>
<?php if (isset($_GET['saved'])): flash('ok', 'App saved.'); endif; ?>
<?php if ($msg): flash($msg[0] === 'ok' ? 'ok' : 'err', $msg[1]); endif; ?>

<?php if (!$showForm): ?>
  <div class="card">
    <div class="toolbar">
      <h3 style="margin:0">All apps</h3>
      <a class="btn sm" href="?page=apps&new=1"><i class="fa-solid fa-plus"></i> New app</a>
    </div>
    <p class="hint" style="margin-bottom:14px">Paste a Google Play listing URL when adding an app and the icon + screenshots are fetched once and stored locally forever — nothing hotlinks Google's servers afterward.</p>
    <table>
      <tr><th></th><th>Name</th><th>Developer</th><th>Status</th><th>Views</th><th></th></tr>
      <?php foreach ($pdo->query("SELECT * FROM apps ORDER BY id DESC") as $a): ?>
      <tr>
        <td><?php if ($a['icon_path']): ?><img src="<?= site_url($a['icon_path']) ?>" style="width:32px;height:32px;border-radius:8px;object-fit:cover"><?php endif; ?></td>
        <td><?= e($a['name']) ?></td>
        <td><?= e($a['developer']) ?></td>
        <td><?= $a['status'] === 'published' ? '<span class="badge ok">Live</span>' : '<span class="badge off">Draft</span>' ?></td>
        <td><?= number_format((int)$a['views']) ?></td>
        <td style="white-space:nowrap">
          <a class="btn gray sm" href="?page=apps&edit=<?= (int)$a['id'] ?>">Edit</a>
          <a class="btn red sm" href="?page=apps&delete=<?= (int)$a['id'] ?>&csrf=<?= csrf_token() ?>" onclick="return confirm('Delete this app?')">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$pdo->query("SELECT COUNT(*) FROM apps")->fetchColumn()): ?><tr><td colspan="6" style="color:#94a3b8;text-align:center;padding:20px">No apps added yet.</td></tr><?php endif; ?>
    </table>
  </div>
<?php else: ?>
  <div class="card">
    <form method="post">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
      <input type="hidden" name="icon_path" value="<?= e($editing['icon_path'] ?? '') ?>">
      <input type="hidden" name="screenshots" value="<?= e($editing['screenshots'] ?? '[]') ?>">

      <label>Google Play listing URL</label>
      <input type="text" name="play_store_url" value="<?= e($editing['play_store_url'] ?? '') ?>" placeholder="https://play.google.com/store/apps/details?id=com.example.app" required>
      <label style="display:flex;align-items:center;gap:8px;font-weight:600;margin-top:10px">
        <input type="checkbox" name="fetch_from_play" style="width:auto" <?= $editing ? '' : 'checked' ?>>
        Fetch icon, screenshots<?= $editing ? ' (re-fetch and overwrite current ones)' : '' ?> and blank fields from this link on save
      </label>

      <div class="row2" style="margin-top:14px">
        <div><label>App name (leave blank to use the fetched name)</label><input type="text" name="name" value="<?= e($editing['name'] ?? '') ?>"></div>
        <div><label>Slug (leave blank to auto-generate)</label><input type="text" name="slug" value="<?= e($editing['slug'] ?? '') ?>"></div>
      </div>
      <div class="row2">
        <div><label>Developer (leave blank to use fetched value)</label><input type="text" name="developer" value="<?= e($editing['developer'] ?? '') ?>"></div>
        <div><label>Category (free text, e.g. "Productivity")</label><input type="text" name="category" value="<?= e($editing['category'] ?? '') ?>"></div>
      </div>

      <?php if (!empty($editing['icon_path'])): ?>
        <img src="<?= site_url($editing['icon_path']) ?>" style="width:64px;height:64px;border-radius:14px;margin:10px 0">
      <?php endif; ?>
      <?php $shots = json_decode($editing['screenshots'] ?? '[]', true) ?: []; if ($shots): ?>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px">
          <?php foreach ($shots as $s): ?><img src="<?= site_url($s) ?>" style="height:120px;border-radius:8px;border:1px solid var(--line)"><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <label>Short description (leave blank to use the fetched one, trimmed)</label>
      <textarea name="short_description" style="min-height:60px"><?= e($editing['short_description'] ?? '') ?></textarea>

      <label>Full description (HTML allowed — your own write-up, not just the Play Store blurb)</label>
      <textarea name="full_description" style="min-height:160px;font-family:'JetBrains Mono',monospace;font-size:13px"><?= e($editing['full_description'] ?? '') ?></textarea>

      <h3>SEO</h3>
      <label>Meta title</label><input type="text" name="meta_title" value="<?= e($editing['meta_title'] ?? '') ?>">
      <label>Meta description</label><textarea name="meta_description" style="min-height:60px"><?= e($editing['meta_description'] ?? '') ?></textarea>

      <label style="display:flex;align-items:center;gap:8px;font-weight:600;margin-top:16px">Status
        <select name="status" style="width:auto">
          <option value="published" <?= ($editing['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Published</option>
          <option value="draft" <?= ($editing['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
        </select>
      </label>

      <div style="margin-top:20px;display:flex;gap:10px">
        <button class="btn" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save app</button>
        <a class="btn gray" href="?page=apps">Cancel</a>
      </div>
    </form>
  </div>
<?php endif; ?>
