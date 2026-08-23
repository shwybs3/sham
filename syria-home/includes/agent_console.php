<?php
/**
 * Agent Console — runs the real Claude Code CLI on this server, headless,
 * triggered from the admin panel instead of an SSH terminal.
 *
 * Every run is launched detached (setsid + full I/O redirection) so it
 * keeps running after the triggering HTTP request finishes — that's what a
 * bare `claude -p "..."` typed into an SSH session does NOT survive: the
 * shared-hosting process manager (CloudLinux/LVE) kills it the moment the
 * SSH session that started it closes or hits its resource cap.
 *
 * This still runs on the SAME account and CAN read/write every file the
 * PHP process can, and (via --permission-mode bypassPermissions) makes
 * every edit/command without asking — there is no confirmation step. That
 * is the explicit tradeoff of "unattended": treat the admin login that can
 * reach this page as equivalent to full SSH access to the account.
 */

function agent_runs_dir(): string {
    $dir = ROOT_PATH . '/agent_runs';
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    $deny = $dir . '/.htaccess';
    if (!file_exists($deny)) @file_put_contents($deny, "Require all denied\n");
    return $dir;
}

function agent_cli_path(): string {
    $p = trim(setting('agent_cli_path', ''));
    return $p !== '' ? $p : 'claude';
}

function agent_cwd(): string {
    $p = trim(setting('agent_cwd', ''));
    return $p !== '' ? $p : ROOT_PATH;
}

function agent_api_key(): string {
    return trim(setting('agent_anthropic_api_key', ''));
}

/** Starts one detached run. Returns the run ID, or '' if it couldn't launch. */
function agent_launch(string $prompt): string {
    $prompt = trim($prompt);
    if ($prompt === '') return '';

    $dir = agent_runs_dir();
    $runId = date('Ymd-His') . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
    $runDir = $dir . '/' . $runId;
    @mkdir($runDir, 0750, true);

    $logFile  = $runDir . '/output.log';
    $pidFile  = $runDir . '/pid';
    $doneFile = $runDir . '/done';
    $metaFile = $runDir . '/meta.json';

    file_put_contents($metaFile, json_encode([
        'prompt'     => $prompt,
        'started_at' => date('c'),
        'cwd'        => agent_cwd(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    touch($logFile);

    $envPrefix = '';
    $key = agent_api_key();
    if ($key !== '') $envPrefix = 'env ANTHROPIC_API_KEY=' . escapeshellarg($key) . ' ';

    // setsid detaches from the calling process group; the trailing `&` plus
    // full stdio redirection lets PHP return immediately without the child
    // dying when this HTTP request (or an SSH session, if launched from one)
    // ends. `touch done` on the end marks completion for the poller below.
    $cmd = 'cd ' . escapeshellarg(agent_cwd()) . ' && '
         . 'setsid ' . $envPrefix . escapeshellarg(agent_cli_path())
         . ' -p ' . escapeshellarg($prompt)
         . ' --permission-mode bypassPermissions'
         . ' --output-format text'
         . ' > ' . escapeshellarg($logFile) . ' 2>&1'
         . ' < /dev/null'
         . '; touch ' . escapeshellarg($doneFile)
         . ' & echo $!';

    $pid = trim((string)@shell_exec($cmd));
    if ($pid === '' || !ctype_digit($pid)) return '';
    file_put_contents($pidFile, $pid);

    log_ai_activity('agent_console_run', 'agent_run', null, mb_substr($prompt, 0, 200));
    return $runId;
}

function agent_run_status(string $runId): array {
    $dir = agent_runs_dir() . '/' . basename($runId);
    if (!is_dir($dir)) return ['found' => false];
    $meta = json_decode((string)@file_get_contents($dir . '/meta.json'), true) ?: [];
    $done = file_exists($dir . '/done');
    return [
        'found'  => true,
        'status' => $done ? 'finished' : 'running',
        'meta'   => $meta,
        'log'    => (string)@file_get_contents($dir . '/output.log'),
    ];
}

/** Most recent runs first, newest N. */
function agent_list_runs(int $limit = 30): array {
    $dir = agent_runs_dir();
    $items = [];
    foreach (glob($dir . '/*', GLOB_ONLYDIR) ?: [] as $path) {
        $runId = basename($path);
        $meta = json_decode((string)@file_get_contents($path . '/meta.json'), true) ?: [];
        $items[] = [
            'run_id'  => $runId,
            'prompt'  => $meta['prompt'] ?? '',
            'started' => $meta['started_at'] ?? '',
            'status'  => file_exists($path . '/done') ? 'finished' : 'running',
        ];
    }
    usort($items, fn($a, $b) => strcmp($b['run_id'], $a['run_id']));
    return array_slice($items, 0, $limit);
}
