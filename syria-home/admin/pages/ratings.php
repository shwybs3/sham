<?php
$msg = null;

if (isset($_GET['delete']) && csrf_check_get()) {
    $pdo->prepare("DELETE FROM ratings WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: ?page=ratings'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check() && ($_POST['action'] ?? '') === 'save') {
    set_setting('ratings_enabled', isset($_POST['ratings_enabled']) ? '1' : '0');
    $msg = ['ok', 'Rating settings saved.'];
}

try {
    $overall = $pdo->query("SELECT COUNT(*) AS c, AVG(rating) AS a FROM ratings")->fetch();
    $byType  = $pdo->query("SELECT entity_type, COUNT(*) c, AVG(rating) a FROM ratings GROUP BY entity_type")->fetchAll();
    $dist    = $pdo->query("SELECT rating, COUNT(*) c FROM ratings GROUP BY rating ORDER BY rating DESC")->fetchAll();

    /* Top-rated content, joined back to its title. Only entities with
       enough votes to carry star markup are shown as "live". */
    $top = $pdo->query("
        SELECT r.entity_type, r.entity_id, COUNT(*) c, AVG(r.rating) a,
               COALESCE(ar.title, t.name, p.name) AS title,
               COALESCE(ar.slug,  t.slug, p.slug) AS slug
        FROM ratings r
        LEFT JOIN articles ar ON r.entity_type='article' AND ar.id = r.entity_id
        LEFT JOIN tools    t  ON r.entity_type='tool'    AND t.id  = r.entity_id
        LEFT JOIN products p  ON r.entity_type='product' AND p.id  = r.entity_id
        GROUP BY r.entity_type, r.entity_id
        ORDER BY c DESC, a DESC
        LIMIT 40")->fetchAll();

    $recent = $pdo->query("SELECT * FROM ratings ORDER BY created_at DESC LIMIT 30")->fetchAll();
} catch (PDOException $e) {
    $overall = ['c' => 0, 'a' => 0]; $byType = []; $dist = []; $top = []; $recent = [];
    $msg = ['err', 'Database error: ' . $e->getMessage()];
}

$totalVotes = (int)($overall['c'] ?? 0);
$liveCount  = count(array_filter($top, fn($r) => (int)$r['c'] >= SH_RATING_MIN_VOTES));
?>
<?php if ($msg): flash($msg[0], $msg[1]); endif; ?>

<div class="grid-stats">
  <div class="stat"><i class="fa-solid fa-star"></i><div class="n"><?= number_format($totalVotes) ?></div><div class="l">Total votes</div></div>
  <div class="stat"><i class="fa-solid fa-chart-simple"></i><div class="n"><?= $totalVotes ? number_format((float)$overall['a'], 2) : '—' ?></div><div class="l">Average rating</div></div>
  <div class="stat"><i class="fa-solid fa-magnifying-glass-chart"></i><div class="n"><?= number_format($liveCount) ?></div><div class="l">Pages with star markup</div></div>
  <div class="stat"><i class="fa-solid fa-layer-group"></i><div class="n"><?= number_format(count($top)) ?></div><div class="l">Rated items</div></div>
</div>

<div class="card" style="background:#f0f9ff;border-color:#bae6fd">
  <h3 style="margin-top:0"><i class="fa-solid fa-circle-info" style="color:#0284c7"></i> How star snippets work here</h3>
  <p class="hint" style="font-size:13.5px;line-height:1.7">
    Google's structured-data policy requires review markup to reflect ratings genuinely collected from your users, and it issues
    manual actions for fabricated or self-serving review data. So this system emits <code>AggregateRating</code> JSON-LD
    <strong>only</strong> for pages with at least <?= SH_RATING_MIN_VOTES ?> real visitor votes — nothing is seeded or defaulted.
    Stars typically appear in results within a few days of Google re-crawling a qualifying page; use
    <a href="?page=indexing">Search Indexing</a> to nudge a re-crawl, and Google's Rich Results Test to confirm the markup parses.
    Article pages are eligible for review snippets only in some categories, so Google may parse the markup without showing stars.
  </p>
</div>

<div class="row2">
  <div class="card">
    <h3 style="margin-top:0">Score distribution</h3>
    <?php if (!$dist): ?>
      <p class="hint">No votes yet. The star widget is live at the bottom of every article and tool page.</p>
    <?php else: $max = max(array_column($dist, 'c')); foreach ($dist as $d): ?>
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:9px">
        <span style="width:52px;font-size:13px;font-weight:700;white-space:nowrap"><?= (int)$d['rating'] ?> <i class="fa-solid fa-star" style="color:#f59e0b;font-size:11px"></i></span>
        <div style="flex:1;background:#eef1f8;border-radius:99px;height:10px;overflow:hidden">
          <div style="height:100%;width:<?= $max ? round((int)$d['c'] / $max * 100) : 0 ?>%;background:linear-gradient(135deg,var(--brand1),var(--brand2))"></div>
        </div>
        <span style="width:46px;text-align:right;font-size:13px;color:var(--muted)"><?= number_format((int)$d['c']) ?></span>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <div class="card">
    <h3 style="margin-top:0">By content type</h3>
    <table>
      <tr><th>Type</th><th>Votes</th><th>Average</th></tr>
      <?php foreach ($byType as $b): ?>
      <tr>
        <td style="text-transform:capitalize"><?= e($b['entity_type']) ?>s</td>
        <td><?= number_format((int)$b['c']) ?></td>
        <td><b><?= number_format((float)$b['a'], 2) ?></b> / 5</td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$byType): ?><tr><td colspan="3" class="hint" style="text-align:center;padding:24px">Nothing rated yet.</td></tr><?php endif; ?>
    </table>

    <h3 style="margin-top:22px">Settings</h3>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="save">
      <label style="display:flex;align-items:center;gap:8px;font-weight:600"><input type="checkbox" name="ratings_enabled" style="width:auto" <?= (int)setting('ratings_enabled', 1) ? 'checked' : '' ?>> Show the rating widget on public pages</label>
      <button class="btn sm" style="margin-top:12px"><i class="fa-solid fa-floppy-disk"></i> Save</button>
    </form>
  </div>
</div>

<div class="card">
  <h3 style="margin-top:0">Rated content</h3>
  <table>
    <tr><th>Title</th><th>Type</th><th>Votes</th><th>Average</th><th>Star markup</th><th></th></tr>
    <?php foreach ($top as $t):
        $live = (int)$t['c'] >= SH_RATING_MIN_VOTES;
        $path = ['article' => 'article/', 'tool' => 'tool/', 'product' => 'product/'][$t['entity_type']] ?? '';
    ?>
    <tr>
      <td><?= e($t['title'] ?? '(deleted)') ?></td>
      <td style="text-transform:capitalize"><?= e($t['entity_type']) ?></td>
      <td><?= number_format((int)$t['c']) ?></td>
      <td><b><?= number_format((float)$t['a'], 2) ?></b></td>
      <td><?= $live
            ? '<span class="badge ok">Live</span>'
            : '<span class="badge off">Needs ' . (SH_RATING_MIN_VOTES - (int)$t['c']) . ' more</span>' ?></td>
      <td><?php if (!empty($t['slug'])): ?><a class="btn gray sm" target="_blank" href="<?= e(site_url($path . $t['slug'])) ?>">View</a><?php endif; ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$top): ?><tr><td colspan="6" style="color:#94a3b8;text-align:center;padding:30px">No ratings collected yet.</td></tr><?php endif; ?>
  </table>
</div>

<div class="card">
  <h3 style="margin-top:0">Recent votes</h3>
  <p class="hint" style="margin-bottom:12px">Voter fingerprints are salted SHA-256 hashes — the raw IP is never stored. Delete a vote only if you have reason to believe it's abusive; removing genuine votes to raise your average is exactly what the policy above forbids.</p>
  <table>
    <tr><th>When</th><th>Type</th><th>Item ID</th><th>Rating</th><th></th></tr>
    <?php foreach ($recent as $r): ?>
    <tr>
      <td style="white-space:nowrap"><?= e(date('M j, H:i', strtotime($r['created_at']))) ?></td>
      <td style="text-transform:capitalize"><?= e($r['entity_type']) ?></td>
      <td>#<?= (int)$r['entity_id'] ?></td>
      <td><?= str_repeat('★', (int)$r['rating']) ?><span style="color:#cbd5e1"><?= str_repeat('★', 5 - (int)$r['rating']) ?></span></td>
      <td><a class="btn red sm" href="?page=ratings&delete=<?= (int)$r['id'] ?>&csrf=<?= csrf_token() ?>" onclick="return confirm('Delete this vote?')">Delete</a></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$recent): ?><tr><td colspan="5" style="color:#94a3b8;text-align:center;padding:30px">No votes yet.</td></tr><?php endif; ?>
  </table>
</div>
