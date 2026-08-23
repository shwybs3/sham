<?php
$cats = $pdo->query("SELECT * FROM categories WHERE type='tool' ORDER BY name")->fetchAll();
$enTools = $pdo->query("SELECT id, name FROM tools WHERE lang='en' ORDER BY name")->fetchAll();
$msg = null;

if (isset($_GET['delete']) && csrf_check_get()) {
    $pdo->prepare("DELETE FROM tools WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: ?page=tools'); exit;
}

/* Installs created before the pro tool set shipped can pull it in here.
   The seeder uses INSERT IGNORE, so re-running never disturbs existing rows. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'seed_pro' && csrf_check()) {
    require_once __DIR__ . '/../../seed/seed_pro_tools.php';
    $before = (int)$pdo->query("SELECT COUNT(*) FROM tools")->fetchColumn();
    seed_pro_tools($pdo);
    $added = (int)$pdo->query("SELECT COUNT(*) FROM tools")->fetchColumn() - $before;
    $msg = ['ok', $added > 0 ? "$added pro tools added." : 'All pro tools were already installed.'];
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
            'is_premium' => isset($_POST['is_premium']) ? 1 : 0,
            'premium_price' => (float)($_POST['premium_price'] ?? 3),
            'affiliate_url' => trim($_POST['affiliate_url'] ?? ''),
            'tool_code'     => $_POST['tool_code'] ?? '',
            'replaces'      => trim($_POST['replaces'] ?? ''),
            'lang' => in_array($_POST['lang'] ?? 'en', ['en', 'ar'], true) ? $_POST['lang'] : 'en',
            'translation_of' => ($_POST['translation_of'] ?? '') !== '' ? (int)$_POST['translation_of'] : null,
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
        if (($fields['status'] ?? '') === 'published') indexnow_ping(site_url('tool/' . $slug));
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
    <div class="toolbar">
      <h3 style="margin:0">All tools</h3>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <form method="post" style="margin:0">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="seed_pro">
          <button class="btn gray sm" type="submit"><i class="fa-solid fa-download"></i> Install pro tool set</button>
        </form>
        <a class="btn sm" href="?page=tools&new=1"><i class="fa-solid fa-plus"></i> New tool</a>
      </div>
    </div>
    <p class="hint" style="margin-bottom:14px">The pro set adds 20 tools covering what paid services normally charge for — background removal, PDF building, transcription, SEO analysis and more. Every one is an original implementation that runs in the visitor's browser.</p>
    <table>
      <tr><th>Name</th><th>Lang</th><th>Engine</th><th>Slug</th><th>Status</th><th>Uses</th><th></th></tr>
      <?php foreach ($pdo->query("SELECT * FROM tools ORDER BY id DESC") as $t): ?>
      <tr>
        <td><i class="fa-solid <?= e($t['icon_class']) ?>"></i> <?= e($t['name']) ?></td>
        <td><?= ($t['lang'] ?? 'en') === 'ar' ? '<span class="badge warn">AR</span>' : '<span class="badge off">EN</span>' ?></td>
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
        <div><label>Language</label>
          <select name="lang">
            <option value="en" <?= ($editing['lang'] ?? 'en') === 'en' ? 'selected' : '' ?>>English</option>
            <option value="ar" <?= ($editing['lang'] ?? 'en') === 'ar' ? 'selected' : '' ?>>العربية (Arabic)</option>
          </select>
        </div>
        <div><label>Translation of (for an Arabic version — pick the English original)</label>
          <select name="translation_of">
            <option value="">— None / this is the original —</option>
            <?php foreach ($enTools as $et): if ($et['id'] == ($editing['id'] ?? 0)) continue; ?>
              <option value="<?= $et['id'] ?>" <?= (int)($editing['translation_of'] ?? 0) === (int)$et['id'] ? 'selected' : '' ?>><?= e($et['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <p class="hint">Links this row as the Arabic translation of an existing tool page — powers the language switcher and hreflang tags.</p>
        </div>
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

      <label>Replaces (paid workflow this tool covers for free)</label>
      <input type="text" name="replaces" value="<?= e($editing['replaces'] ?? '') ?>" placeholder="e.g. Paid per-image background removal services">
      <p class="hint">Shown as a badge on the public page. Describe the <em>category</em> of paid service, not a specific company — naming a competitor in your own marketing invites a trademark complaint.</p>

      <h3>Source Code</h3>
      <label>Tool source code (shown to visitors as "View Source" — JavaScript)</label>
      <textarea name="tool_code" style="min-height:200px;font-family:'JetBrains Mono',monospace;font-size:12px"><?= e($editing['tool_code'] ?? '') ?></textarea>
      <p class="hint">Paste the JavaScript function/class that powers this tool. Shown in a syntax-highlighted block on the public tool page with a Copy button. Purely informational — the actual running code comes from assets/js/tools.js.</p>

      <h3>Affiliate</h3>
      <label>Affiliate / external tool URL (optional)</label>
      <input type="text" name="affiliate_url" value="<?= e($editing['affiliate_url'] ?? '') ?>" placeholder="https://external-tool.com/?ref=yourcode">
      <p class="hint">If set, the "Visit Tool" button on the public page opens this URL instead of the built-in tool. Use for affiliate links or third-party embeds.</p>

      <h3>Premium tool (crypto paywall)</h3>
      <label style="display:flex;align-items:center;gap:8px;font-weight:600"><input type="checkbox" name="is_premium" style="width:auto" <?= !empty($editing['is_premium']) ? 'checked' : '' ?>> Require payment to use this tool</label>
      <label>Unlock price (USD)</label>
      <input type="text" name="premium_price" value="<?= e((string)($editing['premium_price'] ?? '3.00')) ?>" style="max-width:160px">
      <p class="hint">Requires NOWPayments to be configured in Settings &gt; Payments.</p>

      <div style="margin-top:20px;display:flex;gap:10px">
        <button class="btn" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save tool</button>
        <a class="btn gray" href="?page=tools">Cancel</a>
      </div>
    </form>
  </div>
<?php endif; ?>
