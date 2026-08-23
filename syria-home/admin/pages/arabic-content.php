<?php
$msg = null;
$batches = arabic_content_batches();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $key = $_POST['batch'] ?? '';
    if (isset($batches[$key])) {
        [$label, $fn] = $batches[$key];
        $fn($pdo);
        $msg = ['ok', "$label applied."];
    } elseif ($key === 'all') {
        foreach ($batches as [$label, $fn]) $fn($pdo);
        $msg = ['ok', count($batches) . ' batches applied.'];
    }
}

$arArticles = (int)$pdo->query("SELECT COUNT(*) FROM articles WHERE lang='ar'")->fetchColumn();
$enArticles = (int)$pdo->query("SELECT COUNT(*) FROM articles WHERE lang='en'")->fetchColumn();
$arTools = (int)$pdo->query("SELECT COUNT(*) FROM tools WHERE lang='ar'")->fetchColumn();
$enTools = (int)$pdo->query("SELECT COUNT(*) FROM tools WHERE lang='en'")->fetchColumn();
?>
<?php if ($msg): flash($msg[0], $msg[1]); endif; ?>

<div class="grid-stats">
  <div class="stat"><i class="fa-solid fa-newspaper"></i><div class="n"><?= $arArticles ?> / <?= $enArticles ?></div><div class="l">Arabic articles translated</div></div>
  <div class="stat"><i class="fa-solid fa-wrench"></i><div class="n"><?= $arTools ?> / <?= $enTools ?></div><div class="l">Arabic tools translated</div></div>
  <div class="stat"><i class="fa-solid fa-layer-group"></i><div class="n"><?= count($batches) ?></div><div class="l">Batches available now</div></div>
</div>

<div class="card" style="background:#f0f9ff;border-color:#bae6fd">
  <h3 style="margin-top:0"><i class="fa-solid fa-circle-info" style="color:#0284c7"></i> How this works</h3>
  <p class="hint" style="font-size:13.5px;line-height:1.7">
    Each Arabic article/tool is a real translated page — its own slug, served under <code>/ar/</code>, linked back to the English original for the language switcher and SEO <code>hreflang</code> tags. Applying a batch inserts those new rows; it never edits the English originals. Safe to re-run — it just skips rows that already exist.
    New batches are added over time as more content gets translated — come back to this page as more are published in updates.
  </p>
</div>

<?php if ($batches): ?>
<div class="card">
  <div class="toolbar">
    <h3 style="margin:0">Available batches</h3>
    <form method="post" onsubmit="return confirm('Apply all <?= count($batches) ?> batches now?')">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="batch" value="all">
      <button class="btn sm"><i class="fa-solid fa-forward"></i> Apply all batches</button>
    </form>
  </div>
  <table>
    <tr><th>Batch</th><th></th></tr>
    <?php foreach ($batches as $key => [$label, $fn]): ?>
    <tr>
      <td><?= e($label) ?></td>
      <td>
        <form method="post" style="margin:0">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="batch" value="<?= e($key) ?>">
          <button class="btn gray sm"><i class="fa-solid fa-play"></i> Apply</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php else: ?>
<div class="card"><p class="hint">No Arabic translation batches installed on this codebase yet.</p></div>
<?php endif; ?>

<div class="card">
  <h3 style="margin-top:0">Arabic pages so far</h3>
  <table>
    <tr><th>Type</th><th>Title</th><th>Slug</th></tr>
    <?php foreach ($pdo->query("SELECT 'Article' AS kind, title, slug FROM articles WHERE lang='ar' UNION ALL SELECT 'Tool', name, slug FROM tools WHERE lang='ar' ORDER BY kind, title")->fetchAll() as $row): ?>
    <tr>
      <td><?= e($row['kind']) ?></td>
      <td><?= e($row['title']) ?></td>
      <td dir="ltr"><code>/ar/<?= $row['kind'] === 'Article' ? 'article' : 'tool' ?>/<?= e($row['slug']) ?></code></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$arArticles && !$arTools): ?><tr><td colspan="3" style="color:#94a3b8;text-align:center;padding:20px">No Arabic content yet — apply a batch above.</td></tr><?php endif; ?>
  </table>
</div>
