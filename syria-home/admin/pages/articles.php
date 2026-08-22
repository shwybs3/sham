<?php
$cats = $pdo->query("SELECT * FROM categories WHERE type='article' ORDER BY name")->fetchAll();
$msg = null;

/* ── Delete ── */
if (isset($_GET['delete']) && csrf_check_get()) {
    $pdo->prepare("DELETE FROM articles WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: ?page=articles'); exit;
}

/* ── AI generate (fills the form via session draft, doesn't save) ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ai_generate' && csrf_check()) {
    $topic = trim($_POST['ai_topic'] ?? '');
    $type = $_POST['ai_type'] ?? 'article';
    $system = "You are a senior tech editor writing for an English-language news/tools website. "
        . "Write ONLY valid minified JSON (no markdown fences) with keys: title, excerpt (<=160 chars), "
        . "body_html (well-structured HTML using <h2>/<h3>/<p>/<ul> — 700-1000 words, no <html>/<body> wrapper), "
        . "meta_title, meta_description (<=160 chars), meta_keywords (comma separated), tags (comma separated, 4-6 tags).";
    $prompt = "Write a {$type} about: {$topic}. Make it specific, factual-sounding, useful, and optimized for search intent.";
    $res = AIRouter::generate($prompt, $system);
    if ($res['ok']) {
        $json = json_decode(trim(preg_replace('~^```json|```$~m', '', trim($res['text']))), true);
        if ($json) {
            $_SESSION['ai_draft'] = $json;
            log_ai_activity('generate_article', 'article', null, 'Drafted "' . ($json['title'] ?? $topic) . '" via ' . $res['provider']);
            header('Location: ?page=articles&new=1&ai=1'); exit;
        }
        $msg = ['err', 'AI responded but not in the expected format. Try again or a more specific topic.'];
    } else {
        $msg = ['err', $res['error']];
    }
}

/* ── Save (create/update) ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save' && csrf_check()) {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '') ?: slugify($title);
    $body = $_POST['body'] ?? '';
    $fields = [
        'title' => $title,
        'slug' => $slug,
        'category_id' => $_POST['category_id'] ?: null,
        'content_type' => $_POST['content_type'] ?? 'article',
        'excerpt' => trim($_POST['excerpt'] ?? '') ?: excerpt_from_html($body),
        'body' => $body,
        'hero_icon' => trim($_POST['hero_icon'] ?? 'fa-newspaper'),
        'hero_gradient' => $_POST['hero_gradient'] ?? 'g1',
        'meta_title' => trim($_POST['meta_title'] ?? ''),
        'meta_description' => trim($_POST['meta_description'] ?? ''),
        'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
        'tags' => trim($_POST['tags'] ?? ''),
        'author' => trim($_POST['author'] ?? '') ?: 'Editorial Team',
        'status' => $_POST['status'] ?? 'draft',
        'trending' => isset($_POST['trending']) ? 1 : 0,
        'is_premium' => isset($_POST['is_premium']) ? 1 : 0,
        'premium_price' => (float)($_POST['premium_price'] ?? 3),
        'reading_time' => reading_time_from_html($body),
    ];
    if ($title === '') {
        $msg = ['err', 'Title is required.'];
    } else {
        if ($id) {
            $sql = "UPDATE articles SET " . implode(',', array_map(fn($k) => "$k = :$k", array_keys($fields))) . ", updated_at = NOW() WHERE id = :id";
            $fields['id'] = $id;
            $pdo->prepare($sql)->execute($fields);
        } else {
            $fields['published_at'] = date('Y-m-d H:i:s');
            $sql = "INSERT INTO articles (" . implode(',', array_keys($fields)) . ") VALUES (" . implode(',', array_map(fn($k) => ":$k", array_keys($fields))) . ")";
            try {
                $pdo->prepare($sql)->execute($fields);
                $id = (int)$pdo->lastInsertId();
            } catch (PDOException $e) {
                $fields['slug'] .= '-' . substr(md5((string)microtime(true)), 0, 5);
                $pdo->prepare($sql)->execute($fields);
                $id = (int)$pdo->lastInsertId();
            }
        }
        unset($_SESSION['ai_draft']);
        header('Location: ?page=articles&saved=1'); exit;
    }
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}
$showForm = isset($_GET['new']) || $editing;
$draft = $_SESSION['ai_draft'] ?? null;
if ($draft && !$editing) {
    $editing = [
        'id' => 0, 'title' => $draft['title'] ?? '', 'slug' => slugify($draft['title'] ?? ''),
        'category_id' => null, 'content_type' => $_POST['ai_type'] ?? 'article',
        'excerpt' => $draft['excerpt'] ?? '', 'body' => $draft['body_html'] ?? '',
        'hero_icon' => 'fa-newspaper', 'hero_gradient' => 'g' . random_int(1, 8),
        'meta_title' => $draft['meta_title'] ?? '', 'meta_description' => $draft['meta_description'] ?? '',
        'meta_keywords' => $draft['meta_keywords'] ?? '', 'tags' => $draft['tags'] ?? '',
        'author' => 'Editorial Team', 'status' => 'draft', 'trending' => 0,
    ];
}
?>

<?php if (isset($_GET['saved'])): flash('ok', 'Article saved.'); endif; ?>
<?php if ($msg): flash($msg[0] === 'ok' ? 'ok' : 'err', $msg[1]); endif; ?>

<?php if (!$showForm): ?>
  <div class="card">
    <div class="toolbar">
      <h3 style="margin:0">All articles</h3>
      <div style="display:flex;gap:8px">
        <a class="btn gray sm" href="#ai-generate" onclick="document.getElementById('aiBox').style.display='block'"><i class="fa-solid fa-wand-magic-sparkles"></i> Generate with AI</a>
        <a class="btn sm" href="?page=articles&new=1"><i class="fa-solid fa-plus"></i> New article</a>
      </div>
    </div>

    <div id="aiBox" class="card" style="display:none;background:#f8f9ff;border-style:dashed">
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="ai_generate">
        <label>Topic / working title</label>
        <input type="text" name="ai_topic" placeholder="e.g. Why Wi-Fi 7 routers are suddenly worth buying" required>
        <label>Type</label>
        <select name="ai_type">
          <option value="article">Article</option>
          <option value="news">News</option>
          <option value="tutorial">Tutorial</option>
          <option value="comparison">Comparison</option>
          <option value="review">Review</option>
        </select>
        <button class="btn" style="margin-top:14px" type="submit"><i class="fa-solid fa-wand-magic-sparkles"></i> Generate draft</button>
        <p class="hint">Uses Gemini if configured, otherwise falls back to OpenRouter's free models. You'll review everything before publishing.</p>
      </form>
    </div>

    <table>
      <tr><th>Title</th><th>Type</th><th>Status</th><th>Trending</th><th>Views</th><th>Date</th><th></th></tr>
      <?php foreach ($pdo->query("SELECT * FROM articles ORDER BY created_at DESC") as $a): ?>
      <tr>
        <td><?= e($a['title']) ?></td>
        <td><?= e(ucfirst($a['content_type'])) ?></td>
        <td><?= $a['status'] === 'published' ? '<span class="badge ok">Published</span>' : '<span class="badge off">Draft</span>' ?></td>
        <td><?= $a['trending'] ? '<i class="fa-solid fa-fire" style="color:#f43f5e"></i>' : '—' ?></td>
        <td><?= number_format((int)$a['views']) ?></td>
        <td><?= date('M j, Y', strtotime($a['published_at'])) ?></td>
        <td style="white-space:nowrap">
          <a class="btn gray sm" href="?page=articles&edit=<?= (int)$a['id'] ?>">Edit</a>
          <a class="btn red sm" href="?page=articles&delete=<?= (int)$a['id'] ?>&csrf=<?= csrf_token() ?>" onclick="return confirm('Delete this article?')">Delete</a>
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
        <div><label>Title</label><input type="text" name="title" value="<?= e($editing['title'] ?? '') ?>" required></div>
        <div><label>Slug (leave blank to auto-generate)</label><input type="text" name="slug" value="<?= e($editing['slug'] ?? '') ?>"></div>
      </div>

      <div class="row2">
        <div><label>Category</label>
          <select name="category_id">
            <option value="">— None —</option>
            <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= (($editing['category_id'] ?? null) == $c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div><label>Content type</label>
          <select name="content_type">
            <?php foreach (['article'=>'Article','news'=>'News','tutorial'=>'Tutorial','comparison'=>'Comparison','review'=>'Review'] as $k=>$l): ?>
              <option value="<?= $k ?>" <?= ($editing['content_type'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <label>Excerpt</label>
      <textarea name="excerpt" style="min-height:60px"><?= e($editing['excerpt'] ?? '') ?></textarea>

      <label>Body (HTML — use &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt; etc.)</label>
      <textarea name="body" style="min-height:320px;font-family:'JetBrains Mono',monospace;font-size:13px"><?= e($editing['body'] ?? '') ?></textarea>

      <div class="row2">
        <div><label>Hero icon (Font Awesome class, e.g. fa-microchip)</label><input type="text" name="hero_icon" value="<?= e($editing['hero_icon'] ?? 'fa-newspaper') ?>"></div>
        <div><label>Hero color</label>
          <select name="hero_gradient">
            <?php foreach (array_keys(HERO_GRADIENTS) as $g): ?><option value="<?= $g ?>" <?= ($editing['hero_gradient'] ?? 'g1') === $g ? 'selected' : '' ?>><?= strtoupper($g) ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>

      <h3>SEO</h3>
      <label>Meta title</label><input type="text" name="meta_title" value="<?= e($editing['meta_title'] ?? '') ?>">
      <label>Meta description</label><textarea name="meta_description" style="min-height:60px"><?= e($editing['meta_description'] ?? '') ?></textarea>
      <label>Meta keywords (comma separated)</label><input type="text" name="meta_keywords" value="<?= e($editing['meta_keywords'] ?? '') ?>">
      <label>Tags (comma separated)</label><input type="text" name="tags" value="<?= e($editing['tags'] ?? '') ?>">

      <div class="row2">
        <div><label>Author</label><input type="text" name="author" value="<?= e($editing['author'] ?? 'Editorial Team') ?>"></div>
        <div><label>Status</label>
          <select name="status">
            <option value="draft" <?= ($editing['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
            <option value="published" <?= ($editing['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
          </select>
        </div>
      </div>
      <label style="display:flex;align-items:center;gap:8px;font-weight:600"><input type="checkbox" name="trending" style="width:auto" <?= !empty($editing['trending']) ? 'checked' : '' ?>> Mark as trending</label>

      <h3>Premium content (crypto paywall)</h3>
      <label style="display:flex;align-items:center;gap:8px;font-weight:600"><input type="checkbox" name="is_premium" style="width:auto" <?= !empty($editing['is_premium']) ? 'checked' : '' ?>> Require payment to read the full article</label>
      <label>Unlock price (USD)</label>
      <input type="text" name="premium_price" value="<?= e((string)($editing['premium_price'] ?? '3.00')) ?>" style="max-width:160px">
      <p class="hint">When enabled, visitors see the excerpt and a "Pay with crypto" unlock button instead of the full body. Requires NOWPayments to be configured in Settings &gt; Payments.</p>

      <div style="margin-top:20px;display:flex;gap:10px">
        <button class="btn" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save article</button>
        <a class="btn gray" href="?page=articles">Cancel</a>
      </div>
    </form>
  </div>
<?php endif; ?>
