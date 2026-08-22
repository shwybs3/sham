<?php
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'provision' && csrf_check()) {
    if (empty($_POST['confirm'])) {
        $result = ['ok' => false, 'error' => 'Please check the confirmation box — this creates a real subdomain and database.'];
    } else {
        $result = SiteProvisioner::provision(
            trim($_POST['subdomain'] ?? ''),
            trim($_POST['niche'] ?? ''),
            (int)($_POST['extra_articles'] ?? 0)
        );
    }
}

$subsites = $pdo->query("SELECT * FROM subsites ORDER BY created_at DESC")->fetchAll();
?>
<?php if (!CPanelClient::isConfigured() || trim(setting('cpanel_home_dir')) === ''): ?>
  <div class="card">
    <div class="flash err">cPanel isn't fully configured yet. Add your host, username, API token and account home directory in <a href="?page=settings&tab=subdomains">Settings &gt; Subdomains</a> before creating a subsite.</div>
  </div>
<?php endif; ?>

<?php if ($result): ?>
  <div class="card">
    <?php if ($result['ok']): ?>
      <div class="flash ok" style="margin-bottom:16px"><i class="fa-solid fa-circle-check"></i> Site created successfully.</div>
      <table>
        <tr><td>Site</td><td><a href="<?= e($result['site_url']) ?>" target="_blank"><?= e($result['site_url']) ?></a></td></tr>
        <tr><td>Admin panel</td><td><a href="<?= e($result['admin_url']) ?>" target="_blank"><?= e($result['admin_url']) ?></a></td></tr>
        <tr><td>Admin username</td><td><code><?= e($result['admin_user']) ?></code></td></tr>
        <tr><td>Admin password</td><td><code><?= e($result['admin_pass']) ?></code> <span class="hint">(shown once — save it now, then change it under Settings &gt; Security on the new site)</span></td></tr>
      </table>
      <?php if (!empty($result['log'])): ?>
      <div style="margin-top:16px;font-size:13px;color:var(--muted)">
        <?php foreach ($result['log'] as $l): ?><div><i class="fa-solid fa-check" style="color:var(--ok)"></i> <?= e($l) ?></div><?php endforeach; ?>
      </div>
      <?php endif; ?>
    <?php else: ?>
      <div class="flash err"><i class="fa-solid fa-triangle-exclamation"></i> <?= e($result['error']) ?></div>
      <?php if (!empty($result['log'])): ?>
      <div style="margin-top:12px;font-size:13px;color:var(--muted)">
        <?php foreach ($result['log'] as $l): ?><div><?= e($l) ?></div><?php endforeach; ?>
      </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="card">
  <h3 style="margin-top:0"><i class="fa-solid fa-plus"></i> Create a new subdomain site</h3>
  <p class="hint">This provisions a real, fully independent copy of this script: a new subdomain, a new database, its own admin account, and the same 21 starter articles + 20 tools + 10 products — optionally topped up with a few AI-written articles for the niche you specify.</p>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="action" value="provision">
    <div class="row2">
      <div><label>Subdomain name</label><input type="text" name="subdomain" placeholder="e.g. gaming" required>
        <p class="hint">Will become <code>subdomain.<?= e(CPanelClient::rootDomain() ?: 'yourdomain.com') ?></code></p>
      </div>
      <div><label>Niche / topic</label><input type="text" name="niche" placeholder="e.g. mobile gaming news"></div>
    </div>
    <label>Extra AI-written articles for this niche (0–5)</label>
    <input type="text" name="extra_articles" value="0" style="max-width:120px">
    <p class="hint">Uses your Gemini/OpenRouter key from Settings &gt; API Keys. Saved as drafts on the new site for review before publishing — same as everywhere else on this site.</p>

    <label style="display:flex;align-items:center;gap:8px;font-weight:700;margin-top:16px;color:#7c2d12">
      <input type="checkbox" name="confirm" style="width:auto" required>
      I understand this creates a real subdomain and database on my hosting account, using real cPanel API access.
    </label>

    <button class="btn" style="margin-top:16px" type="submit" <?= (!CPanelClient::isConfigured() || trim(setting('cpanel_home_dir')) === '') ? 'disabled' : '' ?>><i class="fa-solid fa-server"></i> Create subdomain site</button>
  </form>
</div>

<div class="card">
  <h3 style="margin-top:0">Provisioned subdomain sites</h3>
  <?php if (!$subsites): ?>
    <p class="hint">None yet.</p>
  <?php else: ?>
  <table>
    <tr><th>Domain</th><th>Niche</th><th>Status</th><th>Created</th><th></th></tr>
    <?php foreach ($subsites as $s): ?>
    <tr>
      <td><?= e($s['full_domain']) ?></td>
      <td><?= e($s['niche']) ?></td>
      <td>
        <?php if ($s['status'] === 'ready'): ?><span class="badge ok">Ready</span>
        <?php elseif ($s['status'] === 'failed'): ?><span class="badge off" title="<?= e($s['error_log']) ?>">Failed</span>
        <?php else: ?><span class="badge warn">Provisioning</span><?php endif; ?>
      </td>
      <td><?= date('M j, Y', strtotime($s['created_at'])) ?></td>
      <td>
        <?php if ($s['status'] === 'ready'): ?>
          <a class="btn gray sm" href="https://<?= e($s['full_domain']) ?>/" target="_blank">Visit</a>
          <a class="btn gray sm" href="https://<?= e($s['full_domain']) ?>/admin/" target="_blank">Admin</a>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>
