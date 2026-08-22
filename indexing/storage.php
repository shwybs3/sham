<?php
/**
 * storage.php — File-based JSON storage layer (no database)
 */

// ── Settings ──────────────────────────────────────────────────────────────────
function cfg(string $key, $default = '') {
    static $c = null;
    $f = DATA_DIR.'/settings.json';
    if ($c === null) $c = json_read($f);
    return $c[$key] ?? $default;
}
function cfg_set(string $key, $val): void {
    $f = DATA_DIR.'/settings.json'; $d = json_read($f); $d[$key] = $val; json_write($f,$d);
}
function cfg_batch(array $kv): void {
    $f = DATA_DIR.'/settings.json'; $d = json_read($f);
    foreach ($kv as $k=>$v) $d[$k] = $v; json_write($f,$d);
}

// ── Users ─────────────────────────────────────────────────────────────────────
function user_file(string $id): string { return DATA_DIR.'/users/'.preg_replace('/[^a-z0-9_\-]/i','',$id).'.json'; }

function user_get(string $id): array {
    $f = user_file($id);
    if (!file_exists($f)) return [
        'id'=>$id,'email'=>null,'name'=>null,'picture'=>null,'google_sub'=>null,
        'plan'=>'free','plan_expires'=>null,'daily_limit'=>100,'used_today'=>0,
        'used_date'=>date('Y-m-d'),'used_total'=>0,'ip'=>get_ip(),
        'ua'=>$_SERVER['HTTP_USER_AGENT']??'','country'=>get_country(),
        'created_at'=>now(),'updated_at'=>now(),'sa_key'=>null,'sa_email'=>null,
    ];
    return json_read($f);
}
function user_save(array $u): void {
    $u['updated_at'] = now();
    json_write(user_file($u['id']), $u);
}
function user_ensure(string $id): array {
    $u = user_get($id);
    if (!file_exists(user_file($id))) user_save($u);
    return $u;
}
function user_all(): array {
    $out = [];
    foreach (glob(DATA_DIR.'/users/*.json') ?: [] as $f) {
        $u = json_read($f); if ($u) $out[] = $u;
    }
    usort($out, fn($a,$b)=>strcmp($b['created_at']??'',$a['created_at']??''));
    return $out;
}

// ── Quota ─────────────────────────────────────────────────────────────────────
function quota(string $id): array {
    $u = user_ensure($id);
    // Reset daily count if new day
    if (($u['used_date']??'') !== date('Y-m-d')) {
        $u['used_today'] = 0; $u['used_date'] = date('Y-m-d'); user_save($u);
    }
    $planActive = !empty($u['plan_expires']) && $u['plan'] !== 'free' && strtotime($u['plan_expires']) > time();
    if (!$planActive && $u['plan'] !== 'free') {
        $u['plan'] = 'free'; $u['daily_limit'] = PLANS['free']['limit']; $u['plan_expires'] = null; user_save($u);
    }
    $limit = (int)($u['daily_limit'] ?? PLANS['free']['limit']);
    return [
        'used'   => (int)$u['used_today'],
        'limit'  => $limit,
        'remaining' => max(0, $limit - (int)$u['used_today']),
        'plan'   => $u['plan'] ?? 'free',
        'plan_active' => $planActive || $u['plan'] === 'free',
        'plan_expires' => $u['plan_expires'] ?? null,
    ];
}
function quota_inc(string $id): void {
    $u = user_get($id);
    if (($u['used_date']??'') !== date('Y-m-d')) { $u['used_today'] = 0; $u['used_date'] = date('Y-m-d'); }
    $u['used_today'] = ((int)$u['used_today']) + 1;
    $u['used_total'] = ((int)($u['used_total']??0)) + 1;
    user_save($u);
}
function grant_plan(string $id, string $plan, int $days): void {
    $u = user_ensure($id);
    $plans = PLANS;
    $base = (!empty($u['plan_expires']) && strtotime($u['plan_expires']) > time()) ? strtotime($u['plan_expires']) : time();
    $u['plan']        = $plan;
    $u['plan_expires']= date('Y-m-d H:i:s', $base + $days * 86400);
    $u['daily_limit'] = $plans[$plan]['limit'] ?? PLANS['free']['limit'];
    user_save($u);
}
function revoke_plan(string $id): void {
    $u = user_get($id);
    $u['plan'] = 'free'; $u['plan_expires'] = null; $u['daily_limit'] = PLANS['free']['limit'];
    user_save($u);
}
function adjust_credits(string $id, int $delta): void {
    $u = user_ensure($id);
    $u['daily_limit'] = max(0, ((int)($u['daily_limit'] ?? 100)) + $delta);
    user_save($u);
}

// ── Service Account Key ───────────────────────────────────────────────────────
function key_save(string $id, string $json, string $email): void {
    json_write(DATA_DIR.'/users/'.$id.'.key.json', ['json'=>$json,'email'=>$email,'saved_at'=>now()]);
}
function key_get(string $id): ?array {
    $f = DATA_DIR.'/users/'.$id.'.key.json';
    return file_exists($f) ? json_read($f) : null;
}
function key_delete(string $id): void { @unlink(DATA_DIR.'/users/'.$id.'.key.json'); }

// ── Payments ──────────────────────────────────────────────────────────────────
function pay_save(array $p): void {
    $p['updated_at'] = now();
    json_write(DATA_DIR.'/payments/'.preg_replace('/[^a-z0-9_\-]/i','',$p['order_id']).'.json', $p);
}
function pay_get(string $oid): ?array {
    $f = DATA_DIR.'/payments/'.preg_replace('/[^a-z0-9_\-]/i','',$oid).'.json';
    return file_exists($f) ? json_read($f) : null;
}
function pay_all(): array {
    $out = [];
    foreach (glob(DATA_DIR.'/payments/*.json') ?: [] as $f) { $p=json_read($f); if($p) $out[]=$p; }
    usort($out,fn($a,$b)=>strcmp($b['created_at']??'',$a['created_at']??''));
    return $out;
}

// ── Reports / support tickets (chat widget + Report a Problem page) ────────────
function report_save(array $r): void {
    if (empty($r['id'])) $r['id'] = bin2hex(random_bytes(8));
    json_write(DATA_DIR.'/reports/'.preg_replace('/[^a-f0-9]/i','',$r['id']).'.json', $r);
}
function report_all(): array {
    $out = [];
    foreach (glob(DATA_DIR.'/reports/*.json') ?: [] as $f) { $r=json_read($f); if($r) $out[]=$r; }
    usort($out,fn($a,$b)=>strcmp($b['created_at']??'',$a['created_at']??''));
    return $out;
}
function report_set_status(string $id, string $status): void {
    $f = DATA_DIR.'/reports/'.preg_replace('/[^a-f0-9]/i','',$id).'.json';
    if (!file_exists($f)) return;
    $r = json_read($f); $r['status'] = $status; $r['updated_at'] = now();
    json_write($f, $r);
}

// ── Logs ─────────────────────────────────────────────────────────────────────
function log_append(string $uid, string $url, bool $ok, string $msg=''): void {
    $e = json_encode(['uid'=>$uid,'url'=>$url,'ok'=>$ok,'msg'=>$msg,'at'=>now(),'ip'=>get_ip()],
        JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    file_put_contents(DATA_DIR.'/logs/gidx.jsonl', $e."\n", FILE_APPEND|LOCK_EX);
}
function log_all(int $limit=500): array {
    $f = DATA_DIR.'/logs/gidx.jsonl';
    if (!file_exists($f)) return [];
    $lines = array_filter(file($f, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES));
    $lines = array_slice(array_reverse($lines), 0, $limit);
    return array_values(array_filter(array_map(fn($l)=>json_decode($l,true), $lines)));
}
function log_clear(): void { @unlink(DATA_DIR.'/logs/gidx.jsonl'); }

// ── Stats ─────────────────────────────────────────────────────────────────────
function stats(): array {
    $users = user_all(); $pays = pay_all(); $now = time();
    $premium = count(array_filter($users, fn($u)=>($u['plan']??'free')!=='free'&&!empty($u['plan_expires'])&&strtotime($u['plan_expires'])>$now));
    $confirmed = array_filter($pays, fn($p)=>in_array($p['status']??'',['finished','confirmed'],true));
    $rev = array_sum(array_column($confirmed,'price_usd'));
    $logs = log_all(5000); $today = date('Y-m-d');
    $okLogs = count(array_filter($logs, fn($l)=>$l['ok']??false));
    $todayLogs = count(array_filter($logs, fn($l)=>str_starts_with($l['at']??'',$today)));
    return ['total_users'=>count($users),'premium_users'=>$premium,'total_payments'=>count($confirmed),
        'revenue'=>(float)$rev,'total_logs'=>count($logs),'ok_logs'=>$okLogs,
        'fail_logs'=>count($logs)-$okLogs,'today_logs'=>$todayLogs];
}

// ── Session / Auth ────────────────────────────────────────────────────────────
function current_user(): ?array {
    if (!empty($_SESSION['uid'])) return user_ensure($_SESSION['uid']);
    return null;
}
function login_user(array $u): void {
    user_save($u);
    $_SESSION['uid'] = $u['id'];
}
function logout_user(): void { unset($_SESSION['uid']); session_destroy(); }

function uid_from_cookie(): string {
    if (!empty($_COOKIE['_gidx_id']) && preg_match('/^[a-f0-9]{32}$/', $_COOKIE['_gidx_id']))
        return $_COOKIE['_gidx_id'];
    $id = bin2hex(random_bytes(16));
    setcookie('_gidx_id', $id, time()+3600*24*365*2, '/', '', true, true);
    $_COOKIE['_gidx_id'] = $id;
    return $id;
}

// ── Data Retention cleanup ────────────────────────────────────────────────────
function cleanup_old_data(): void {
    $cutoff = time() - DATA_RETENTION_DAYS * 86400;
    // Keys older than retention
    foreach (glob(DATA_DIR.'/users/*.key.json') ?: [] as $f) {
        $d = json_read($f);
        if (!empty($d['saved_at']) && strtotime($d['saved_at']) < $cutoff) @unlink($f);
    }
}
