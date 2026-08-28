<?php
/**
 * Runs the real Claude Code CLI on this server, unattended: no confirmation
 * prompts (--permission-mode bypassPermissions), full read/write access to
 * whatever the "Working directory" setting points at. Treat reaching this
 * page as equivalent to SSH access to the hosting account — anyone who can
 * log into this admin panel can have it edit any file or run any command
 * the PHP process's user can.
 */

if (isset($_GET['ajax']) && isset($_GET['run'])) {
    header('Content-Type: application/json');
    echo json_encode(agent_run_status($_GET['run']));
    exit;
}

$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_settings') {
        set_setting('agent_cli_path', trim($_POST['agent_cli_path'] ?? ''));
        set_setting('agent_cwd', trim($_POST['agent_cwd'] ?? ''));
        if (trim($_POST['agent_anthropic_api_key'] ?? '') !== '') {
            set_setting('agent_anthropic_api_key', trim($_POST['agent_anthropic_api_key']));
        }
        $msg = ['ok', 'Agent Console settings saved.'];
    }

    if ($action === 'run') {
        $prompt = trim($_POST['prompt'] ?? '');
        if ($prompt === '') {
            $msg = ['err', 'Enter a task for the agent first.'];
        } else {
            $runId = agent_launch($prompt);
            if ($runId === '') {
                $msg = ['err', 'Could not launch — check the CLI path in settings below and confirm it is installed and executable.'];
            } else {
                header('Location: ?page=agent-console&run=' . urlencode($runId));
                exit;
            }
        }
    }
}

$cliPath = trim(setting('agent_cli_path', ''));
$cwd     = trim(setting('agent_cwd', '')) ?: ROOT_PATH;
$hasKey  = trim(setting('agent_anthropic_api_key', '')) !== '';
$runs    = agent_list_runs();
$viewRun = trim($_GET['run'] ?? '');
$active  = $viewRun !== '' ? agent_run_status($viewRun) : null;
?>
<?php if ($msg): flash($msg[0], $msg[1]); endif; ?>

<div class="card" style="border-left:4px solid #dc2626">
  <h3 style="margin-top:0"><i class="fa-solid fa-triangle-exclamation" style="color:#dc2626"></i> Unattended mode</h3>
  <p class="hint">This runs the actual Claude Code CLI on this server with <code>--permission-mode bypassPermissions</code> — it makes every file edit and shell command without asking, against the working directory below. There is no undo. Keep this admin account's password strong and don't share admin access with anyone you wouldn't hand a root shell to.</p>
</div>

<div class="row2">
  <div class="card">
    <h3 style="margin-top:0"><i class="fa-solid fa-paper-plane"></i> New task</h3>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="run">
      <label>What should the agent do?</label>
      <textarea name="prompt" rows="5" placeholder="e.g. Create a subdomain blog.yassota.com via the cPanel API in CPanelClient.php, provision it, and confirm it resolves." required></textarea>
      <button class="btn" style="margin-top:10px" <?= $cliPath === '' ? 'disabled' : '' ?>><i class="fa-solid fa-play"></i> Run now</button>
      <?php if ($cliPath === ''): ?><p class="hint" style="color:#dc2626">Set the CLI path below first.</p><?php endif; ?>
    </form>
  </div>

  <div class="card">
    <h3 style="margin-top:0"><i class="fa-solid fa-gear"></i> Setup</h3>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="save_settings">
      <label>Claude Code CLI path</label>
      <input type="text" name="agent_cli_path" value="<?= e($cliPath) ?>" placeholder="/home/youruser/.nvm/versions/node/v20.x/bin/claude">
      <p class="hint">Absolute path from <code>which claude</code> after installing it over SSH — see the setup guide.</p>
      <label style="margin-top:10px">Working directory</label>
      <input type="text" name="agent_cwd" value="<?= e($cwd) ?>">
      <p class="hint">Where the agent runs — it can read/write anything under here.</p>
      <label style="margin-top:10px">Anthropic API key <?= $hasKey ? '<span class="badge ok">Set</span>' : '' ?></label>
      <input type="password" name="agent_anthropic_api_key" placeholder="<?= $hasKey ? 'Leave blank to keep the current key' : 'sk-ant-...' ?>">
      <p class="hint">Used only for headless runs from this page — API-key auth is required since unattended runs can't complete an interactive login.</p>
      <button class="btn" style="margin-top:14px"><i class="fa-solid fa-floppy-disk"></i> Save</button>
    </form>
  </div>
</div>

<?php if ($active && $active['found']): ?>
<div class="card" id="runCard" data-run="<?= e($viewRun) ?>">
  <div class="toolbar">
    <h3 style="margin:0">Run <code><?= e($viewRun) ?></code>
      <span id="runStatusBadge" class="badge <?= $active['status'] === 'finished' ? 'ok' : 'warn' ?>"><?= e($active['status']) ?></span>
    </h3>
  </div>
  <p class="hint" style="margin:6px 0 12px"><?= e($active['meta']['prompt'] ?? '') ?></p>
  <pre id="runLog" style="background:#0f172a;color:#cbd5e1;padding:16px;border-radius:10px;max-height:480px;overflow:auto;white-space:pre-wrap;font-size:12.5px"><?= e($active['log']) ?></pre>
</div>
<script>
(function(){
  var card = document.getElementById('runCard');
  if (!card || card.dataset.run === undefined) return;
  var badge = document.getElementById('runStatusBadge');
  if (badge.textContent.trim() === 'finished') return;
  var poll = setInterval(function(){
    fetch('?page=agent-console&ajax=1&run=' + encodeURIComponent(card.dataset.run))
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (!d.found) return;
        document.getElementById('runLog').textContent = d.log;
        badge.textContent = d.status;
        badge.className = 'badge ' + (d.status === 'finished' ? 'ok' : 'warn');
        if (d.status === 'finished') clearInterval(poll);
      });
  }, 2500);
})();
</script>
<?php endif; ?>

<div class="card">
  <h3 style="margin-top:0">Recent runs</h3>
  <table>
    <tr><th>When</th><th>Task</th><th>Status</th><th></th></tr>
    <?php foreach ($runs as $r): ?>
    <tr>
      <td style="white-space:nowrap"><?= e($r['started'] ? date('M j, H:i', strtotime($r['started'])) : $r['run_id']) ?></td>
      <td style="max-width:360px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($r['prompt']) ?></td>
      <td><?= $r['status'] === 'finished' ? '<span class="badge ok">finished</span>' : '<span class="badge warn">running</span>' ?></td>
      <td><a class="btn gray sm" href="?page=agent-console&run=<?= urlencode($r['run_id']) ?>">View</a></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$runs): ?><tr><td colspan="4" style="color:#94a3b8;text-align:center;padding:30px">No runs yet.</td></tr><?php endif; ?>
  </table>
</div>
