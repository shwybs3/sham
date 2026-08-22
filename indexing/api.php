<?php
/**
 * api.php — AJAX endpoint for all tool operations
 */
require_once __DIR__.'/config.php';
require_once __DIR__.'/storage.php';
require_once __DIR__.'/functions.php';

header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── Auth: get or create user ──────────────────────────────────────────────────
$u = current_user();
if (!$u) {
    $cookieId = uid_from_cookie();
    $u = user_ensure($cookieId);
}
$uid = $u['id'];

// ── OAuth callback ────────────────────────────────────────────────────────────
if ($action === 'oauth_callback') {
    if (($_GET['state']??'') !== ($_SESSION['oauth_state']??'x')) die(json_encode(['ok'=>false,'error'=>'State mismatch']));
    $r = google_oauth_exchange($_GET['code'] ?? '');
    if (!$r['ok']) die(json_encode($r));
    // Find existing user by google_sub or create new
    $found = null;
    foreach (user_all() as $eu) if (($eu['google_sub']??'') === $r['sub']) { $found=$eu; break; }
    if (!$found) {
        $id = 'g_'.substr(preg_replace('/[^a-z0-9]/i','',strtolower($r['email'])),0,8).'_'.bin2hex(random_bytes(6));
        $found = user_ensure($uid); // keep cookie user
        $found['id'] = $uid;
    }
    $found['google_sub']=$r['sub']; $found['email']=$r['email']; $found['name']=$r['name']; $found['picture']=$r['picture'];
    login_user($found);
    header('Location: '.site_url('index.php')); exit;
}

// ── Upload Service Account JSON ───────────────────────────────────────────────
if ($action === 'upload_key') {
    if (!csrf_ok()) die(json_encode(['ok'=>false,'error'=>'CSRF error']));
    $json = file_get_contents($_FILES['sa_key']['tmp_name'] ?? '');
    if (!$json) die(json_encode(['ok'=>false,'error'=>'No file received']));
    $v = validate_sa_key($json);
    if (!$v['ok']) die(json_encode($v));
    key_save($uid, $json, $v['client_email']);
    $u['sa_email'] = $v['client_email']; user_save($u);
    die(json_encode(['ok'=>true,'email'=>$v['client_email']]));
}

// ── Test / verify key ─────────────────────────────────────────────────────────
if ($action === 'test_key') {
    $k = key_get($uid);
    if (!$k) die(json_encode(['ok'=>false,'error'=>'No service account key uploaded']));
    $t = get_access_token($k['json']);
    die(json_encode($t));
}

// ── Delete key ────────────────────────────────────────────────────────────────
if ($action === 'delete_key') {
    if (!csrf_ok()) die(json_encode(['ok'=>false,'error'=>'CSRF error']));
    key_delete($uid); $u['sa_email']=null; user_save($u);
    die(json_encode(['ok'=>true]));
}

// ── Fetch sitemap ─────────────────────────────────────────────────────────────
if ($action === 'fetch_sitemap') {
    $url = filter_var(trim($_POST['url']??''), FILTER_VALIDATE_URL);
    if (!$url) die(json_encode(['ok'=>false,'error'=>'Invalid URL']));
    die(json_encode(fetch_sitemap($url)));
}

// ── Submit URLs ───────────────────────────────────────────────────────────────
if ($action === 'submit') {
    if (!csrf_ok()) die(json_encode(['ok'=>false,'error'=>'CSRF error']));
    if (empty($_SESSION['uid'])) die(json_encode(['ok'=>false,'error'=>'You must sign in with Google or an email/password account before submitting URLs.']));
    $urls = json_decode($_POST['urls'] ?? '[]', true);
    if (!is_array($urls)||empty($urls)) die(json_encode(['ok'=>false,'error'=>'No URLs provided']));
    $urls = array_slice(array_unique(array_filter(array_map('trim',$urls),'strlen')), 0, 200);

    $k = key_get($uid);
    if (!$k) die(json_encode(['ok'=>false,'error'=>'Upload your Google Service Account JSON key first']));

    $q = quota($uid);
    if ($q['remaining'] <= 0) die(json_encode(['ok'=>false,'error'=>"Daily quota exhausted ({$q['used']}/{$q['limit']}). Upgrade for more."]));

    $tok = get_access_token($k['json']);
    if (!$tok['ok']) die(json_encode(['ok'=>false,'error'=>'Access token error: '.$tok['error']]));

    $results=[]; $okCount=0; $failCount=0;
    $limit = min(count($urls), $q['remaining']);
    foreach (array_slice($urls,0,$limit) as $url) {
        $r = submit_url($tok['token'], $url);
        log_append($uid, $url, $r['ok'], $r['message']);
        quota_inc($uid);
        if ($r['ok']) $okCount++; else $failCount++;
        $results[]=['url'=>$url,'ok'=>$r['ok'],'msg'=>$r['message']];
    }
    die(json_encode(['ok'=>true,'results'=>$results,'ok_count'=>$okCount,'fail_count'=>$failCount,
        'skipped'=>count($urls)-$limit]));
}

// ── Quota status ──────────────────────────────────────────────────────────────
if ($action === 'quota') {
    die(json_encode(['ok'=>true,'quota'=>quota($uid),'user'=>['name'=>$u['name']??null,'email'=>$u['email']??null,'picture'=>$u['picture']??null,'plan'=>$u['plan']??'free']]));
}

// ── History (last 100) ────────────────────────────────────────────────────────
if ($action === 'history') {
    $logs = array_filter(log_all(500), fn($l)=>($l['uid']??'') === $uid);
    die(json_encode(['ok'=>true,'logs'=>array_values($logs)]));
}

// ── Create payment ────────────────────────────────────────────────────────────
if ($action === 'create_payment') {
    if (!csrf_ok()) die(json_encode(['ok'=>false,'error'=>'CSRF error']));
    $plan = $_POST['plan'] ?? '';
    $cur  = strtolower(trim($_POST['currency'] ?? 'usdttrc20'));
    $r = nowpay_create($uid, $plan, $cur);
    die(json_encode($r));
}

// ── Check payment status ──────────────────────────────────────────────────────
if ($action === 'check_payment') {
    $oid = trim($_GET['order_id'] ?? '');
    $p = $oid ? pay_get($oid) : null;
    die(json_encode(['ok'=>(bool)$p,'status'=>$p['status']??null,'plan'=>$p['plan']??null]));
}

// ── Login with email/password ─────────────────────────────────────────────────
if ($action === 'login_email') {
    if (!csrf_ok()) die(json_encode(['ok'=>false,'error'=>'CSRF error']));
    $email = strtolower(trim($_POST['email']??''));
    $pass  = $_POST['password']??'';
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)||!$pass) die(json_encode(['ok'=>false,'error'=>'Invalid credentials']));
    // Find user by email
    $found=null;
    foreach (user_all() as $eu) if (strtolower($eu['email']??'') === $email) { $found=$eu; break; }
    if (!$found) {
        // Register: create user
        $id='e_'.bin2hex(random_bytes(8));
        $found=user_ensure($uid); $found['id']=$uid; $found['email']=$email;
        $found['pass_hash']=password_hash($pass,PASSWORD_DEFAULT);
        $found['name']=explode('@',$email)[0];
        login_user($found); die(json_encode(['ok'=>true,'registered'=>true]));
    }
    if (empty($found['pass_hash'])||!password_verify($pass,$found['pass_hash'])) die(json_encode(['ok'=>false,'error'=>'Incorrect password']));
    login_user($found); die(json_encode(['ok'=>true,'plan'=>$found['plan']??'free']));
}

// ── Register ──────────────────────────────────────────────────────────────────
if ($action === 'register') {
    if (!csrf_ok()) die(json_encode(['ok'=>false,'error'=>'CSRF error']));
    $email=strtolower(trim($_POST['email']??'')); $pass=$_POST['password']??''; $name=trim($_POST['name']??'');
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) die(json_encode(['ok'=>false,'error'=>'Invalid email']));
    if (strlen($pass)<6) die(json_encode(['ok'=>false,'error'=>'Password must be at least 6 characters']));
    foreach (user_all() as $eu) if (strtolower($eu['email']??'')===$email) die(json_encode(['ok'=>false,'error'=>'Email already registered']));
    $found=user_ensure($uid); $found['email']=$email; $found['name']=$name?:explode('@',$email)[0];
    $found['pass_hash']=password_hash($pass,PASSWORD_DEFAULT);
    login_user($found); die(json_encode(['ok'=>true]));
}

// ── Report a Problem page submission ────────────────────────────────────────────
if ($action === 'report_problem') {
    if (!csrf_ok()) die(json_encode(['ok'=>false,'error'=>'CSRF error']));
    $email = trim($_POST['email'] ?? '');
    $category = trim($_POST['category'] ?? 'other');
    $msg = trim($_POST['message'] ?? '');
    if ($msg === '') die(json_encode(['ok'=>false,'error'=>'Please describe the problem']));
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) die(json_encode(['ok'=>false,'error'=>'Invalid email']));
    report_save([
        'id'      => bin2hex(random_bytes(8)),
        'uid'     => $uid,
        'email'   => $email ?: ($u['email'] ?? ''),
        'source'  => 'report_page',
        'category'=> $category,
        'message' => mb_substr($msg, 0, 4000),
        'ip'      => get_ip(),
        'status'  => 'open',
        'created_at' => now(),
    ]);
    die(json_encode(['ok'=>true]));
}

// ── AI chat via OpenRouter ────────────────────────────────────────────────────
if ($action === 'ai_chat') {
    if (!csrf_ok()) die(json_encode(['ok'=>false,'error'=>'CSRF error']));
    $msg = trim($_POST['message'] ?? '');
    if (!$msg) die(json_encode(['ok'=>false,'error'=>'Empty message']));
    $apiKey = cfg('openrouter_api_key', '');
    if (!$apiKey) die(json_encode(['ok'=>false,'reply'=>null]));
    $system = "You are a concise support assistant for IndexPro, a Google URL Indexing API tool. Answer in 2-3 sentences max. Key facts: users upload a Google Service Account JSON key, which must be added as an Owner in Google Search Console. Free plan: 100 URLs/day. Paid plans: Starter $7/mo 500/day, Pro $10/mo 2000/day, Business $25/mo 10000/day. Common failure: Indexing API not enabled or service account not a verified owner in Search Console.";
    $payload = json_encode([
        'model'    => 'deepseek/deepseek-chat-v3-5b:free',
        'messages' => [
            ['role'=>'system','content'=>$system],
            ['role'=>'user','content'=>mb_substr($msg,0,800)],
        ],
        'max_tokens' => 220,
    ]);
    $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
    curl_setopt_array($ch,[
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer '.$apiKey,
            'HTTP-Referer: '.SITE_URL,
            'X-Title: '.SITE_NAME,
        ],
        CURLOPT_TIMEOUT        => 18,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $resp = curl_exec($ch); curl_close($ch);
    $data = json_decode($resp,true);
    $reply = $data['choices'][0]['message']['content'] ?? null;
    die(json_encode(['ok'=>(bool)$reply,'reply'=>$reply ? trim($reply) : null]));
}

// ── Support chat message (saved as a real ticket for the admin panel) ──────────
if ($action === 'support_message') {
    if (!csrf_ok()) die(json_encode(['ok'=>false,'error'=>'CSRF error']));
    $msg = trim($_POST['message'] ?? '');
    if ($msg === '') die(json_encode(['ok'=>false,'error'=>'Empty message']));
    report_save([
        'id'      => bin2hex(random_bytes(8)),
        'uid'     => $uid,
        'email'   => $u['email'] ?? '',
        'source'  => 'chat',
        'message' => mb_substr($msg, 0, 2000),
        'ip'      => get_ip(),
        'status'  => 'open',
        'created_at' => now(),
    ]);
    die(json_encode(['ok'=>true]));
}

// ── Logout ────────────────────────────────────────────────────────────────────
if ($action === 'logout') { logout_user(); die(json_encode(['ok'=>true])); }

die(json_encode(['ok'=>false,'error'=>'Unknown action']));
