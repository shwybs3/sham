<?php
$msg = null;
$batches = content_expansion_batches();

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

try {
    $articleLens = $pdo->query("SELECT slug, title, CHAR_LENGTH(body) AS len FROM articles ORDER BY id")->fetchAll();
    $toolLens = $pdo->query("SELECT slug, name, CHAR_LENGTH(full_description) AS len FROM tools ORDER BY id")->fetchAll();
} catch (PDOException $e) { $articleLens = []; $toolLens = []; }

$TARGET = 10000;
$articlesDone = count(array_filter($articleLens, fn($a) => (int)$a['len'] >= $TARGET));
$toolsDone = count(array_filter($toolLens, fn($t) => (int)$t['len'] >= $TARGET));
?>
<?php if ($msg): flash($msg[0], $msg[1]); endif; ?>

<div class="grid-stats">
  <div class="stat"><i class="fa-solid fa-newspaper"></i><div class="n"><?= $articlesDone ?> / <?= count($articleLens) ?></div><div class="l">Articles at 10k+ chars</div></div>
  <div class="stat"><i class="fa-solid fa-wrench"></i><div class="n"><?= $toolsDone ?> / <?= count($toolLens) ?></div><div class="l">Tools at 10k+ chars</div></div>
  <div class="stat"><i class="fa-solid fa-layer-group"></i><div class="n"><?= count($batches) ?></div><div class="l">Batches available now</div></div>
</div>

<div class="card" style="background:#f0f9ff;border-color:#bae6fd">
  <h3 style="margin-top:0"><i class="fa-solid fa-circle-info" style="color:#0284c7"></i> How this works</h3>
  <p class="hint" style="font-size:13.5px;line-height:1.7">
    The starter content shipped short by design so the site wasn't slow to install. This page expands it to long-form (10,000+ characters per page) in small batches — real written sections, not padding, since thin AI-generated-looking content is exactly what search engines and AdSense reviewers penalize.
    Each batch is safe to re-run; it just re-applies the same text. New batches are added over time — come back to this page as more are published in updates.
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
<div class="card"><p class="hint">No expansion batches installed on this codebase yet.</p></div>
<?php endif; ?>

<div class="row2">
  <div class="card">
    <h3 style="margin-top:0">Articles</h3>
    <table>
      <tr><th>Title</th><th>Length</th></tr>
      <?php foreach ($articleLens as $a): ?>
      <tr>
        <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($a['title']) ?></td>
        <td><?= (int)$a['len'] >= $TARGET ? '<span class="badge ok">' : '<span class="badge off">' ?><?= number_format((int)$a['len']) ?></span></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$articleLens): ?><tr><td colspan="2" style="color:#94a3b8;text-align:center;padding:20px">No articles seeded yet.</td></tr><?php endif; ?>
    </table>
  </div>
  <div class="card">
    <h3 style="margin-top:0">Tools</h3>
    <table>
      <tr><th>Name</th><th>Length</th></tr>
      <?php foreach ($toolLens as $t): ?>
      <tr>
        <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($t['name']) ?></td>
        <td><?= (int)$t['len'] >= $TARGET ? '<span class="badge ok">' : '<span class="badge off">' ?><?= number_format((int)$t['len']) ?></span></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$toolLens): ?><tr><td colspan="2" style="color:#94a3b8;text-align:center;padding:20px">No tools seeded yet.</td></tr><?php endif; ?>
    </table>
  </div>
</div>
