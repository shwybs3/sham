<?php
$msg = null;
$inspection = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'genkey') {
        set_setting('indexnow_key', bin2hex(random_bytes(16)));
        $file = indexnow_keyfile();
        $msg = $file
            ? ['ok', 'New IndexNow key generated and key file published.']
            : ['err', 'Key generated, but the key file could not be written to the webroot. Check folder permissions.'];
    }

    if ($action === 'writekey') {
        $file = indexnow_keyfile();
        $msg = $file ? ['ok', 'Key file verified at ' . $file] : ['err', 'Could not write the key file — check webroot permissions.'];
    }

    if ($action === 'submit_one') {
        $url = trim($_POST['url'] ?? '');
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $msg = ['err', 'Enter a full URL including https://'];
        } else {
            $res = index_submit_url($url);
            $okCount = count(array_filter($res['indexnow'], fn($r) => !empty($r['ok'])));
            $msg = ['ok', "Submitted to $okCount of " . count(SH_INDEXNOW_ENDPOINTS) . ' IndexNow endpoints'
                . (!empty($res['google']['ok']) ? ', and accepted by the Google Indexing API.' : '.')];
        }
    }

    if ($action === 'submit_all') {
        $urls = sh_all_public_urls();
        $res  = indexnow_submit($urls);
        $okCount = count(array_filter($res, fn($r) => !empty($r['ok'])));
        $msg = ['ok', count($urls) . ' URLs submitted to ' . $okCount . ' of ' . count(SH_INDEXNOW_ENDPOINTS) . ' IndexNow endpoints.'];
    }

    if ($action === 'inspect') {
        $url = trim($_POST['inspect_url'] ?? '');
        $inspection = gsc_inspect_url($url);
    }

    if ($action === 'save_opts') {
        set_setting('google_indexing_enabled', isset($_POST['google_indexing_enabled']) ? '1' : '0');
        set_setting('indexnow_autokey', isset($_POST['indexnow_autokey']) ? '1' : '0');
        set_setting('auto_index_on_publish', isset($_POST['auto_index_on_publish']) ? '1' : '0');
        $msg = ['ok', 'Indexing options saved.'];
    }

    if ($action === 'clear_log') {
        $pdo->exec("DELETE FROM index_log");
        $msg = ['ok', 'Submission log cleared.'];
    }
}

$key      = trim(setting('indexnow_key', ''));
$keyFile  = $key !== '' ? ROOT_PATH . '/' . $key . '.txt' : '';
$keyOk    = $keyFile !== '' && file_exists($keyFile);
$urlCount = count(sh_all_public_urls());

try {
    $log = $pdo->query("SELECT * FROM index_log ORDER BY created_at DESC LIMIT 60")->fetchAll();
    $stats = $pdo->query("SELECT
        COUNT(*) AS total,
        SUM(status='ok') AS ok,
        SUM(status='failed') AS failed
      FROM index_log WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch();
} catch (PDOException $e) { $log = []; $stats = ['total'=>0,'ok'=>0,'failed'=>0]; }
?>
<?php if ($msg): flash($msg[0], $msg[1]); endif; ?>

<div class="grid-stats">
  <div class="stat"><i class="fa-solid fa-link"></i><div class="n"><?= number_format($urlCount) ?></div><div class="l">Indexable URLs</div></div>
  <div class="stat"><i class="fa-solid fa-paper-plane"></i><div class="n"><?= number_format((int)$stats['total']) ?></div><div class="l">Submissions (30d)</div></div>
  <div class="stat"><i class="fa-solid fa-circle-check"></i><div class="n"><?= number_format((int)$stats['ok']) ?></div><div class="l">Accepted</div></div>
  <div class="stat"><i class="fa-solid fa-triangle-exclamation"></i><div class="n"><?= number_format((int)$stats['failed']) ?></div><div class="l">Failed</div></div>
</div>

<div class="card">
  <h3 style="margin-top:0"><i class="fa-solid fa-key"></i> IndexNow key</h3>
  <p class="hint">IndexNow is a free, open protocol. One key covers Bing, Yandex, Seznam and Naver — no account or verification needed beyond the key file below. You do <strong>not</strong> get an IndexNow key from Search Console: any random string you publish at your own domain <em>is</em> the key. This page generates and publishes one for you.</p>

  <?php if ($key === ''): ?>
    <form method="post" style="margin-top:12px">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="genkey">
      <button class="btn"><i class="fa-solid fa-wand-magic-sparkles"></i> Generate my IndexNow key</button>
    </form>
  <?php else: ?>
    <div class="row2" style="margin-top:12px">
      <div>
        <label>Your key</label>
        <input type="text" value="<?= e($key) ?>" readonly onclick="this.select()">
      </div>
      <div>
        <label>Key file</label>
        <?php if ($keyOk): ?>
          <p><span class="badge ok"><i class="fa-solid fa-check"></i> Published</span>
             <a href="<?= e(rtrim(SITE_URL,'/') . '/' . $key . '.txt') ?>" target="_blank" rel="noopener" style="margin-left:8px;font-size:13px">verify →</a></p>
        <?php else: ?>
          <p><span class="badge warn">Missing — engines will reject submissions</span></p>
        <?php endif; ?>
      </div>
    </div>
    <div style="display:flex;gap:10px;margin-top:12px;flex-wrap:wrap">
      <form method="post"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="writekey">
        <button class="btn gray sm"><i class="fa-solid fa-rotate"></i> Re-publish key file</button></form>
      <form method="post" onsubmit="return confirm('Generating a new key invalidates the old one. Continue?')">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="genkey">
        <button class="btn gray sm"><i class="fa-solid fa-arrows-rotate"></i> Regenerate key</button></form>
    </div>
  <?php endif; ?>
</div>

<div class="card">
  <h3 style="margin-top:0"><i class="fa-solid fa-tower-broadcast"></i> Engines</h3>
  <table>
    <tr><th>Engine</th><th>Protocol</th><th>Status</th><th>Notes</th></tr>
    <?php foreach ([
      'Bing'   => 'IndexNow', 'Yandex' => 'IndexNow',
      'Seznam' => 'IndexNow', 'Naver'  => 'IndexNow',
    ] as $engine => $proto): ?>
    <tr>
      <td><b><?= $engine ?></b></td>
      <td><?= $proto ?></td>
      <td><?= $keyOk ? '<span class="badge ok">Ready</span>' : '<span class="badge off">Needs key</span>' ?></td>
      <td class="hint">Submitted instantly on publish</td>
    </tr>
    <?php endforeach; ?>
    <tr>
      <td><b>Google</b></td>
      <td>Indexing API</td>
      <td><?= GoogleOAuth::isConnected() ? ((int)setting('google_indexing_enabled',0) ? '<span class="badge ok">Enabled</span>' : '<span class="badge off">Disabled</span>') : '<span class="badge off">Not connected</span>' ?></td>
      <td class="hint">Google officially supports this API for JobPosting and BroadcastEvent pages only — other URLs may be accepted but ignored. Sitemaps + Search Console remain the reliable path for Google.</td>
    </tr>
    <tr>
      <td><b>Google</b></td>
      <td>Search Console</td>
      <td><?= GoogleOAuth::isConnected() ? '<span class="badge ok">Connected</span>' : '<span class="badge off">Not connected</span>' ?></td>
      <td class="hint">Used below to read back real index status per URL</td>
    </tr>
  </table>
  <p class="hint" style="margin-top:12px"><i class="fa-solid fa-circle-info"></i> Google and Bing both retired their old <code>/ping?sitemap=</code> endpoints in 2023, so this panel doesn't pretend to call them. Submit your sitemap once in Search Console and Bing Webmaster Tools; after that IndexNow handles the per-URL nudges.</p>
</div>

<div class="row2">
  <div class="card">
    <h3 style="margin-top:0"><i class="fa-solid fa-paper-plane"></i> Submit URLs</h3>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="submit_one">
      <label>Single URL</label>
      <input type="text" name="url" placeholder="<?= e(site_url('article/your-slug')) ?>">
      <button class="btn sm" style="margin-top:10px"><i class="fa-solid fa-paper-plane"></i> Submit this URL</button>
    </form>
    <div style="margin-top:18px;padding-top:18px;border-top:1px solid var(--line)">
      <form method="post" onsubmit="return confirm('Submit all <?= $urlCount ?> public URLs to every engine?')">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="submit_all">
        <button class="btn"><i class="fa-solid fa-rocket"></i> Submit all <?= $urlCount ?> URLs</button>
      </form>
      <p class="hint">Sends every published article, tool, page and product in one batch per engine. Safe to run after a bulk import; there's no benefit to running it more than once a day.</p>
    </div>
  </div>

  <div class="card">
    <h3 style="margin-top:0"><i class="fa-solid fa-magnifying-glass"></i> Check index status</h3>
    <p class="hint">Asks Google Search Console whether a URL is actually in the index — the real answer, not a guess.</p>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="inspect">
      <label>URL to inspect</label>
      <input type="text" name="inspect_url" value="<?= e($_POST['inspect_url'] ?? '') ?>" placeholder="<?= e(site_url('article/your-slug')) ?>">
      <button class="btn sm" style="margin-top:10px"><i class="fa-solid fa-magnifying-glass"></i> Inspect</button>
    </form>
    <?php if ($inspection !== null): ?>
      <?php if (!empty($inspection['error'])): ?>
        <p style="margin-top:14px"><span class="badge warn"><?= e(is_array($inspection['error']) ? ($inspection['error']['message'] ?? 'Error') : $inspection['error']) ?></span></p>
      <?php else: $r = $inspection['inspectionResult']['indexStatusResult'] ?? []; ?>
        <table style="margin-top:14px">
          <tr><td>Coverage</td><td><b><?= e($r['coverageState'] ?? '—') ?></b></td></tr>
          <tr><td>Verdict</td><td><?= ($r['verdict'] ?? '') === 'PASS' ? '<span class="badge ok">PASS</span>' : '<span class="badge warn">' . e($r['verdict'] ?? '—') . '</span>' ?></td></tr>
          <tr><td>Last crawled</td><td><?= !empty($r['lastCrawlTime']) ? e(date('M j, Y H:i', strtotime($r['lastCrawlTime']))) : 'Never' ?></td></tr>
          <tr><td>Robots.txt</td><td><?= e($r['robotsTxtState'] ?? '—') ?></td></tr>
          <tr><td>Indexing allowed</td><td><?= e($r['indexingState'] ?? '—') ?></td></tr>
        </table>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <h3 style="margin-top:0"><i class="fa-solid fa-sliders"></i> Automation</h3>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="save_opts">
    <label style="display:flex;align-items:center;gap:8px;font-weight:600"><input type="checkbox" name="auto_index_on_publish" style="width:auto" <?= (int)setting('auto_index_on_publish', 1) ? 'checked' : '' ?>> Submit automatically whenever an article, tool or page is published</label>
    <label style="display:flex;align-items:center;gap:8px;font-weight:600"><input type="checkbox" name="indexnow_autokey" style="width:auto" <?= (int)setting('indexnow_autokey', 1) ? 'checked' : '' ?>> Auto-generate an IndexNow key if none is set</label>
    <label style="display:flex;align-items:center;gap:8px;font-weight:600"><input type="checkbox" name="google_indexing_enabled" style="width:auto" <?= (int)setting('google_indexing_enabled', 0) ? 'checked' : '' ?>> Also call the Google Indexing API on publish</label>
    <p class="hint">Leave the Google Indexing API off unless your pages are JobPosting or BroadcastEvent — Google may return quota errors for other page types.</p>
    <button class="btn" style="margin-top:14px"><i class="fa-solid fa-floppy-disk"></i> Save</button>
  </form>
</div>

<div class="card">
  <div class="toolbar">
    <h3 style="margin:0">Submission log</h3>
    <?php if ($log): ?>
    <form method="post" onsubmit="return confirm('Clear the whole log?')">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="clear_log">
      <button class="btn red sm"><i class="fa-solid fa-trash"></i> Clear log</button>
    </form>
    <?php endif; ?>
  </div>
  <table>
    <tr><th>When</th><th>URL</th><th>Engine</th><th>Status</th><th>Detail</th></tr>
    <?php foreach ($log as $row): ?>
    <tr>
      <td style="white-space:nowrap"><?= e(date('M j, H:i', strtotime($row['created_at']))) ?></td>
      <td style="max-width:280px;overflow:hidden;text-overflow:ellipsis"><?= e($row['url']) ?></td>
      <td><code><?= e($row['engine']) ?></code></td>
      <td><?= $row['status'] === 'ok' ? '<span class="badge ok">' . (int)$row['http_code'] . '</span>' : '<span class="badge warn">' . e($row['status']) . '</span>' ?></td>
      <td class="hint"><?= e($row['message']) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$log): ?><tr><td colspan="5" style="color:#94a3b8;text-align:center;padding:30px">Nothing submitted yet.</td></tr><?php endif; ?>
  </table>
</div>
