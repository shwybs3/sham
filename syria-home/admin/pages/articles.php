<?php
$cats = $pdo->query("SELECT * FROM categories WHERE type='article' ORDER BY name")->fetchAll();
$enArticles = $pdo->query("SELECT id, title FROM articles WHERE lang='en' ORDER BY title")->fetchAll();
$msg = null;

/* ── Delete ── */
if (isset($_GET['delete']) && csrf_check_get()) {
    $pdo->prepare("DELETE FROM articles WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: ?page=articles'); exit;
}

/* ── One-click migration: shorten any slug longer than 60 chars (e.g. from
   an older version of the seed data) using the current, shorter slugify(). ── */
if (isset($_GET['shorten_slugs']) && csrf_check_get()) {
    $shortened = 0;
    foreach ($pdo->query("SELECT id, title, slug FROM articles WHERE CHAR_LENGTH(slug) > 60") as $a) {
        $newSlug = slugify($a['title']);
        $check = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE slug = ? AND id != ?");
        $check->execute([$newSlug, $a['id']]);
        if ((int)$check->fetchColumn() > 0) $newSlug .= '-' . $a['id'];
        $pdo->prepare("UPDATE articles SET slug = ? WHERE id = ?")->execute([$newSlug, $a['id']]);
        $shortened++;
    }
    header('Location: ?page=articles&shortened=' . $shortened); exit;
}

/* ── One-click: add the "Welcome to..." + script-marketing draft articles ── */
if (isset($_GET['add_bonus']) && csrf_check_get()) {
    require_once __DIR__ . '/../../seed/seed_bonus_articles.php';
    $added = seed_bonus_articles($pdo);
    header('Location: ?page=articles&bonus_added=' . $added); exit;
}

/* ── One-click: add the second wave of original long-form articles ── */
if (isset($_GET['add_wave2']) && csrf_check_get()) {
    require_once __DIR__ . '/../../seed/seed_articles_batch2.php';
    $before = (int)$pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn();
    seed_articles_batch2($pdo);
    $wave2Added = (int)$pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn() - $before;
    header('Location: ?page=articles&wave2_added=' . $wave2Added); exit;
}

/* ── One-click: add the third wave of original long-form articles ── */
if (isset($_GET['add_wave3']) && csrf_check_get()) {
    require_once __DIR__ . '/../../seed/seed_articles_batch3.php';
    $before = (int)$pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn();
    seed_articles_batch3($pdo);
    $wave3Added = (int)$pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn() - $before;
    header('Location: ?page=articles&wave3_added=' . $wave3Added); exit;
}

/* ── One-click: add the fourth wave of original long-form articles ── */
if (isset($_GET['add_wave4']) && csrf_check_get()) {
    require_once __DIR__ . '/../../seed/seed_articles_batch4.php';
    $before = (int)$pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn();
    seed_articles_batch4($pdo);
    $wave4Added = (int)$pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn() - $before;
    header('Location: ?page=articles&wave4_added=' . $wave4Added); exit;
}

/* ── One-click: add the standalone "Gemini AI image generation" Arabic article ── */
if (isset($_GET['add_gemini_article']) && csrf_check_get()) {
    require_once __DIR__ . '/../../seed/seed_article_gemini_images_ar.php';
    $before = (int)$pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn();
    seed_article_gemini_images_ar($pdo);
    $geminiAdded = (int)$pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn() - $before;
    header('Location: ?page=articles&gemini_added=' . $geminiAdded); exit;
}

/* ── Schema Generator: add/delete extra JSON-LD blocks on an article ── */
if (isset($_GET['delete_schema']) && csrf_check_get()) {
    $pdo->prepare("DELETE FROM article_schema_blocks WHERE id = ?")->execute([(int)$_GET['delete_schema']]);
    header('Location: ?page=articles&edit=' . (int)($_GET['back'] ?? 0)); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_schema' && csrf_check()) {
    $articleId = (int)($_POST['article_id'] ?? 0);
    $type = $_POST['schema_type'] ?? '';

    if ($type === 'FAQPage') {
        $questions = array_map('trim', $_POST['faq_q'] ?? []);
        $answers = array_map('trim', $_POST['faq_a'] ?? []);
        $items = [];
        foreach ($questions as $idx => $q) {
            $ans = $answers[$idx] ?? '';
            if ($q === '' || $ans === '') continue;
            $items[] = ['@type' => 'Question', 'name' => $q, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $ans]];
        }
        $payload = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $items];
    } elseif ($type === 'HowTo') {
        $steps = array_filter(array_map('trim', $_POST['howto_step'] ?? []));
        $payload = [
            '@context' => 'https://schema.org', '@type' => 'HowTo',
            'name' => trim($_POST['howto_name'] ?? ''),
            'step' => array_values(array_map(fn($s) => ['@type' => 'HowToStep', 'text' => $s], $steps)),
        ];
    } elseif ($type === 'Review') {
        $payload = [
            '@context' => 'https://schema.org', '@type' => 'Review',
            'itemReviewed' => ['@type' => 'Thing', 'name' => trim($_POST['review_item'] ?? '')],
            'reviewRating' => ['@type' => 'Rating', 'ratingValue' => (string)(float)($_POST['review_rating'] ?? 5), 'bestRating' => '5'],
            'author' => ['@type' => 'Organization', 'name' => setting('site_name')],
            'reviewBody' => trim($_POST['review_body'] ?? ''),
        ];
    } else {
        $payload = null;
    }

    if ($articleId && $payload) {
        $pdo->prepare("INSERT INTO article_schema_blocks (article_id, schema_type, payload_json) VALUES (?,?,?)")
            ->execute([$articleId, $type, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
    }
    header('Location: ?page=articles&edit=' . $articleId); exit;
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
    if (isset($_POST['auto_link'])) $body = auto_link_body($pdo, $body, $id);

    if (isset($_POST['localize_images'])) {
        $body = sh_localize_body_images($body, 'articles');
    }

    $heroImagePath = trim($_POST['hero_image_path'] ?? ''); // hidden field carrying over the already-stored path
    $heroImageUrl = trim($_POST['hero_image_url'] ?? '');
    if ($heroImageUrl !== '') {
        $imgResult = sh_fetch_and_store_image($heroImageUrl, 'articles');
        if ($imgResult['ok']) {
            $heroImagePath = $imgResult['path'];
        } else {
            $msg = ['err', 'Hero image: ' . $imgResult['error']];
        }
    }

    $fields = [
        'title' => $title,
        'slug' => $slug,
        'category_id' => $_POST['category_id'] ?: null,
        'content_type' => $_POST['content_type'] ?? 'article',
        'excerpt' => trim($_POST['excerpt'] ?? '') ?: excerpt_from_html($body),
        'body' => $body,
        'hero_icon' => trim($_POST['hero_icon'] ?? 'fa-newspaper'),
        'hero_gradient' => $_POST['hero_gradient'] ?? 'g1',
        'hero_image_path' => $heroImagePath,
        'meta_title' => trim($_POST['meta_title'] ?? ''),
        'meta_description' => trim($_POST['meta_description'] ?? ''),
        'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
        'tags' => trim($_POST['tags'] ?? ''),
        'sources' => trim($_POST['sources'] ?? ''),
        'author' => trim($_POST['author'] ?? '') ?: 'Editorial Team',
        'status' => $_POST['status'] ?? 'draft',
        'trending' => isset($_POST['trending']) ? 1 : 0,
        'is_premium' => isset($_POST['is_premium']) ? 1 : 0,
        'premium_price' => (float)($_POST['premium_price'] ?? 3),
        'auto_link' => isset($_POST['auto_link']) ? 1 : 0,
        'reading_time' => reading_time_from_html($body),
        'lang' => in_array($_POST['lang'] ?? 'en', ['en', 'ar'], true) ? $_POST['lang'] : 'en',
        'translation_of' => ($_POST['translation_of'] ?? '') !== '' ? (int)$_POST['translation_of'] : null,
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
        if (($fields['status'] ?? '') === 'published') {
            indexnow_ping(site_url('article/' . ($fields['slug'] ?? '')));
        }
        $redirect = '?page=articles&saved=1';
        if (isset($msg) && $msg[0] === 'err') $redirect .= '&img_error=' . urlencode($msg[1]);
        header('Location: ' . $redirect); exit;
    }
}

if (isset($_GET['img_error'])) $msg = ['err', $_GET['img_error']];

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}
$showForm = isset($_GET['new']) || $editing;
$schemaBlocks = [];
if ($editing) {
    $sb = $pdo->prepare("SELECT * FROM article_schema_blocks WHERE article_id = ? ORDER BY created_at DESC");
    $sb->execute([$editing['id']]);
    $schemaBlocks = $sb->fetchAll();
}
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
<?php if (isset($_GET['shortened'])): flash('ok', (int)$_GET['shortened'] . ' slug(s) shortened.'); endif; ?>
<?php if (isset($_GET['bonus_added'])): flash('ok', (int)$_GET['bonus_added'] > 0 ? (int)$_GET['bonus_added'] . ' starter article(s) added.' : 'Already added — nothing new to add.'); endif; ?>
<?php if (isset($_GET['wave2_added'])): flash('ok', (int)$_GET['wave2_added'] > 0 ? (int)$_GET['wave2_added'] . ' new article(s) added.' : 'Already added — nothing new to add.'); endif; ?>
<?php if (isset($_GET['wave3_added'])): flash('ok', (int)$_GET['wave3_added'] > 0 ? (int)$_GET['wave3_added'] . ' new article(s) added.' : 'Already added — nothing new to add.'); endif; ?>
<?php if (isset($_GET['wave4_added'])): flash('ok', (int)$_GET['wave4_added'] > 0 ? (int)$_GET['wave4_added'] . ' new article(s) added.' : 'Already added — nothing new to add.'); endif; ?>
<?php if (isset($_GET['gemini_added'])): flash('ok', (int)$_GET['gemini_added'] > 0 ? 'Gemini article added.' : 'Already added — nothing new to add.'); endif; ?>
<?php if ($msg): flash($msg[0] === 'ok' ? 'ok' : 'err', $msg[1]); endif; ?>

<?php
$longSlugCount = (int)$pdo->query("SELECT COUNT(*) FROM articles WHERE CHAR_LENGTH(slug) > 60")->fetchColumn();
?>
<?php if ($longSlugCount > 0 && !$showForm): ?>
  <div class="flash warn" style="background:#fffbeb;border:1px solid #fde68a;color:#854d0e"><i class="fa-solid fa-link"></i> <?= $longSlugCount ?> article(s) have an overly long URL slug. <a href="?page=articles&shorten_slugs=1&csrf=<?= csrf_token() ?>" style="font-weight:800">Shorten them now →</a></div>
<?php endif; ?>

<?php if (!$showForm): ?>
  <div class="card">
    <div class="toolbar">
      <h3 style="margin:0">All articles</h3>
      <div style="display:flex;gap:8px">
        <a class="btn gray sm" href="?page=articles&add_bonus=1&csrf=<?= csrf_token() ?>"><i class="fa-solid fa-star"></i> Add starter marketing articles</a>
        <a class="btn gray sm" href="?page=articles&add_wave2=1&csrf=<?= csrf_token() ?>"><i class="fa-solid fa-newspaper"></i> Add more articles</a>
        <a class="btn gray sm" href="?page=articles&add_wave3=1&csrf=<?= csrf_token() ?>"><i class="fa-solid fa-newspaper"></i> Add more articles 2</a>
        <a class="btn gray sm" href="?page=articles&add_wave4=1&csrf=<?= csrf_token() ?>"><i class="fa-solid fa-newspaper"></i> Add more articles 3</a>
        <a class="btn gray sm" href="?page=articles&add_gemini_article=1&csrf=<?= csrf_token() ?>"><i class="fa-solid fa-wand-magic-sparkles"></i> Add Gemini AI images article (AR)</a>
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
      <tr><th>Title</th><th>Lang</th><th>Type</th><th>Status</th><th>Trending</th><th>Views</th><th>Date</th><th></th></tr>
      <?php
      try {
          $allArticles = $pdo->query("SELECT id,title,slug,content_type,status,trending,views,published_at,lang FROM articles ORDER BY created_at DESC")->fetchAll();
      } catch (PDOException $e) { $allArticles = []; echo '<tr><td colspan="8" style="color:#dc2626">DB error: ' . e($e->getMessage()) . '</td></tr>'; }
      foreach ($allArticles as $a): ?>
      <tr>
        <td><?= e($a['title']) ?></td>
        <td><?= ($a['lang'] ?? 'en') === 'ar' ? '<span class="badge warn">AR</span>' : '<span class="badge off">EN</span>' ?></td>
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
        <div><label>Title</label><input type="text" name="title" id="seoTitle" value="<?= e($editing['title'] ?? '') ?>" required></div>
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
            <?php foreach ($enArticles as $ea): if ($ea['id'] == ($editing['id'] ?? 0)) continue; ?>
              <option value="<?= $ea['id'] ?>" <?= (int)($editing['translation_of'] ?? 0) === (int)$ea['id'] ? 'selected' : '' ?>><?= e($ea['title']) ?></option>
            <?php endforeach; ?>
          </select>
          <p class="hint">Links this row as the Arabic (or other) translation of an existing English article — powers the language switcher and hreflang tags on the public page.</p>
        </div>
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
      <textarea name="excerpt" id="seoExcerpt" style="min-height:60px"><?= e($editing['excerpt'] ?? '') ?></textarea>

      <label>Body (HTML — use &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt; etc.)</label>
      <textarea name="body" id="seoBody" style="min-height:320px;font-family:'JetBrains Mono',monospace;font-size:13px"><?= e($editing['body'] ?? '') ?></textarea>

      <div class="row2">
        <div><label>Hero icon (Font Awesome class, e.g. fa-microchip)</label><input type="text" name="hero_icon" value="<?= e($editing['hero_icon'] ?? 'fa-newspaper') ?>"></div>
        <div><label>Hero color (used until a real image is set below)</label>
          <select name="hero_gradient">
            <?php foreach (array_keys(HERO_GRADIENTS) as $g): ?><option value="<?= $g ?>" <?= ($editing['hero_gradient'] ?? 'g1') === $g ? 'selected' : '' ?>><?= strtoupper($g) ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>

      <h3>Images</h3>
      <input type="hidden" name="hero_image_path" value="<?= e($editing['hero_image_path'] ?? '') ?>">
      <?php if (!empty($editing['hero_image_path'])): ?>
        <img src="<?= site_url($editing['hero_image_path']) ?>" style="max-width:260px;border-radius:12px;border:1px solid var(--line);margin-bottom:10px">
        <p class="hint">Current stored hero image. Paste a new URL below to replace it.</p>
      <?php endif; ?>
      <label>Hero image URL (fetched, compressed &amp; stored on your server when you save — never hotlinked)</label>
      <input type="text" name="hero_image_url" placeholder="https://example.com/image.jpg">

      <label style="display:flex;align-items:center;gap:8px;font-weight:600;margin-top:14px">
        <input type="checkbox" name="localize_images" style="width:auto" checked>
        Also fetch &amp; store any external images already inside the body (rewrites their &lt;img src&gt; to the local, compressed copy)
      </label>

      <h3>SEO</h3>
      <label>Meta title</label><input type="text" name="meta_title" id="seoMetaTitle" value="<?= e($editing['meta_title'] ?? '') ?>">
      <label>Meta description</label><textarea name="meta_description" id="seoMetaDesc" style="min-height:60px"><?= e($editing['meta_description'] ?? '') ?></textarea>
      <label>Meta keywords (comma separated)</label><input type="text" name="meta_keywords" id="seoKeywords" value="<?= e($editing['meta_keywords'] ?? '') ?>">
      <label>Tags (comma separated)</label><input type="text" name="tags" value="<?= e($editing['tags'] ?? '') ?>">
      <label>Sources (one per line: "Title | https://url" — shown as a real "Sources" section on the article; leave blank if none)</label>
      <textarea name="sources" rows="4" placeholder="Example: World Health Organization | https://who.int/..."><?= e($editing['sources'] ?? '') ?></textarea>

      <div class="card" style="background:#f8f9ff;margin-top:16px">
        <div style="display:flex;align-items:center;gap:14px">
          <div id="seoScoreCircle" style="width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:15px;background:#e2e8f0;color:#334155;flex-shrink:0">–</div>
          <div><b>SEO Score</b><div class="hint" style="margin:2px 0 0">Updates live as you edit — computed in your browser, nothing sent anywhere.</div></div>
        </div>
        <ul id="seoChecklist" style="list-style:none;padding:0;margin:14px 0 0;font-size:13px"></ul>
      </div>

      <label style="display:flex;align-items:center;gap:8px;font-weight:600;margin-top:16px"><input type="checkbox" name="auto_link" style="width:auto" <?= (($editing['auto_link'] ?? 1) ? 'checked' : '') ?>> Auto-link matching keywords to other articles/tools on save</label>

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

  <?php if ($editing && $editing['id']): ?>
  <div class="card">
    <h3 style="margin-top:0"><i class="fa-solid fa-share-nodes"></i> Share Kit</h3>
    <p class="hint">Ready-to-paste posts about this article, in its own language — copy one straight into social media, another site's comment section, or anywhere else you want to talk about it and drop the link.</p>
    <?php
    $shareUrl = site_url((($editing['lang'] ?? 'en') === 'ar' ? 'ar/article/' : 'article/') . $editing['slug']);
    foreach (share_kit_variants($editing, $shareUrl, $editing['lang'] ?? 'en') as $i => $variant):
    ?>
      <div style="margin-bottom:14px">
        <label style="margin-bottom:4px"><?= e($variant['label']) ?></label>
        <textarea readonly id="shareKit<?= $i ?>" style="min-height:70px;font-size:13px"><?= e($variant['text']) ?></textarea>
        <button type="button" class="btn gray sm" style="margin-top:6px" onclick="shCopyShareKit(<?= $i ?>, this)"><i class="fa-regular fa-copy"></i> Copy</button>
      </div>
    <?php endforeach; ?>
  </div>
  <script>
  function shCopyShareKit(i, btn) {
    var ta = document.getElementById('shareKit' + i);
    navigator.clipboard.writeText(ta.value).then(function () {
      var old = btn.innerHTML; btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
      setTimeout(function () { btn.innerHTML = old; }, 1500);
    });
  }
  </script>

  <div class="card">
    <h3 style="margin-top:0"><i class="fa-solid fa-diagram-project"></i> Schema Generator</h3>
    <p class="hint">Add extra structured data to this article — shown alongside its automatic Article schema, and eligible for rich results in Google (FAQ accordions, how-to steps, star ratings).</p>

    <?php if ($schemaBlocks): ?>
      <table style="margin-bottom:16px">
        <tr><th>Type</th><th>Added</th><th></th></tr>
        <?php foreach ($schemaBlocks as $sb): ?>
        <tr>
          <td><?= e($sb['schema_type']) ?></td>
          <td><?= date('M j, Y', strtotime($sb['created_at'])) ?></td>
          <td><a class="btn red sm" href="?page=articles&delete_schema=<?= (int)$sb['id'] ?>&back=<?= (int)$editing['id'] ?>&csrf=<?= csrf_token() ?>" onclick="return confirm('Remove this schema block?')">Delete</a></td>
        </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>

    <div class="tabs" id="schemaTabs">
      <a class="active" data-t="FAQPage" onclick="return shSwitchSchema(this)">FAQ</a>
      <a data-t="HowTo" onclick="return shSwitchSchema(this)">How-To</a>
      <a data-t="Review" onclick="return shSwitchSchema(this)">Review</a>
    </div>

    <form method="post">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" value="add_schema">
      <input type="hidden" name="article_id" value="<?= (int)$editing['id'] ?>">
      <input type="hidden" name="schema_type" id="schemaTypeField" value="FAQPage">

      <div id="schemaFAQ">
        <?php for ($i = 0; $i < 4; $i++): ?>
          <div class="row2">
            <input type="text" name="faq_q[]" placeholder="Question <?= $i + 1 ?>">
            <input type="text" name="faq_a[]" placeholder="Answer">
          </div>
        <?php endfor; ?>
      </div>

      <div id="schemaHowTo" style="display:none">
        <label>How-to title</label><input type="text" name="howto_name">
        <?php for ($i = 0; $i < 5; $i++): ?>
          <label>Step <?= $i + 1 ?></label><input type="text" name="howto_step[]">
        <?php endfor; ?>
      </div>

      <div id="schemaReview" style="display:none">
        <div class="row2">
          <div><label>Item reviewed</label><input type="text" name="review_item"></div>
          <div><label>Rating (1–5)</label><input type="text" name="review_rating" value="5"></div>
        </div>
        <label>Review summary</label><textarea name="review_body" style="min-height:80px"></textarea>
      </div>

      <button class="btn" style="margin-top:14px" type="submit"><i class="fa-solid fa-plus"></i> Add schema block</button>
    </form>
  </div>
  <script>
  function shSwitchSchema(el) {
    document.querySelectorAll('#schemaTabs a').forEach(a => a.classList.remove('active'));
    el.classList.add('active');
    ['FAQ', 'HowTo', 'Review'].forEach(t => document.getElementById('schema' + t).style.display = 'none');
    const type = el.dataset.t;
    document.getElementById('schema' + (type === 'FAQPage' ? 'FAQ' : type)).style.display = 'block';
    document.getElementById('schemaTypeField').value = type;
    return false;
  }
  </script>
  <?php endif; ?>
<?php endif; ?>

<script>
(function () {
  const $ = id => document.getElementById(id);
  const titleEl = $('seoTitle'), bodyEl = $('seoBody'), excerptEl = $('seoExcerpt');
  const mTitleEl = $('seoMetaTitle'), mDescEl = $('seoMetaDesc'), kwEl = $('seoKeywords');
  if (!titleEl || !bodyEl) return;

  function wordCount(html) { return (html.replace(/<[^>]*>/g, ' ').match(/\S+/g) || []).length; }

  function score() {
    const title = titleEl.value.trim();
    const body = bodyEl.value;
    const excerpt = excerptEl.value.trim();
    const mTitle = mTitleEl.value.trim() || title;
    const mDesc = mDescEl.value.trim();
    const kw = (kwEl.value.split(',')[0] || '').trim().toLowerCase();
    const words = wordCount(body);
    const headings = (body.match(/<h[23][^>]*>/gi) || []).length;
    const links = (body.match(/<a\s/gi) || []).length;
    const plainBody = body.replace(/<[^>]*>/g, ' ').toLowerCase();

    const checks = [
      { label: 'Title length (30–60 characters)', pass: title.length >= 30 && title.length <= 60 },
      { label: 'Meta title set (≤ 60 characters)', pass: mTitle.length > 0 && mTitle.length <= 60 },
      { label: 'Meta description (120–160 characters)', pass: mDesc.length >= 120 && mDesc.length <= 160 },
      { label: 'Excerpt written', pass: excerpt.length >= 50 },
      { label: 'Content length (≥ 400 words)', pass: words >= 400 },
      { label: 'Has at least 2 subheadings (H2/H3)', pass: headings >= 2 },
      { label: 'Has at least 1 internal/outbound link', pass: links >= 1 },
      { label: 'Primary keyword appears in title', pass: kw.length > 0 && title.toLowerCase().includes(kw) },
      { label: 'Primary keyword appears in body', pass: kw.length > 0 && plainBody.includes(kw) },
    ];

    const passed = checks.filter(c => c.pass).length;
    const pct = Math.round((passed / checks.length) * 100);
    const circle = $('seoScoreCircle');
    circle.textContent = pct;
    circle.style.background = pct >= 80 ? '#dcfce7' : pct >= 50 ? '#fef9c3' : '#fee2e2';
    circle.style.color = pct >= 80 ? '#166534' : pct >= 50 ? '#854d0e' : '#991b1b';

    $('seoChecklist').innerHTML = checks.map(c =>
      '<li style="padding:4px 0"><i class="fa-solid ' + (c.pass ? 'fa-circle-check" style="color:#16a34a"' : 'fa-circle-xmark" style="color:#dc2626"') + '></i> ' + c.label + '</li>'
    ).join('');
  }

  [titleEl, bodyEl, excerptEl, mTitleEl, mDescEl, kwEl].forEach(el => el.addEventListener('input', score));
  score();
})();
</script>
