<?php
/**
 * admin.php — ToolifyPro-style Admin Panel
 */
require_once __DIR__.'/config.php';
require_once __DIR__.'/storage.php';
require_once __DIR__.'/functions.php';

// ── Admin Auth ────────────────────────────────────────────────────────────────
$adminLogin = false;
if (!empty($_SESSION['admin_logged_in'])) $adminLogin = true;
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['_admin_action']??'') === 'login') {
    if ($_POST['user']===ADMIN_USER && password_verify($_POST['pass'], '$2y$12$'.substr(md5(ADMIN_PASS),0,22).'.'.md5(ADMIN_PASS.'salt')) ) {
        $_SESSION['admin_logged_in']=true; $adminLogin=true;
    } elseif ($_POST['user']===ADMIN_USER && $_POST['pass']===ADMIN_PASS) {
        $_SESSION['admin_logged_in']=true; $adminLogin=true;
    } else {
        $loginError = 'Invalid credentials';
    }
}
if (isset($_GET['logout_admin'])) { unset($_SESSION['admin_logged_in']); header('Location: admin.php'); exit; }

// ── Handle POST actions (admin only) ─────────────────────────────────────────
$msg = '';
if ($adminLogin && $_SERVER['REQUEST_METHOD']==='POST') {
    $act = $_POST['_admin_action'] ?? '';

    if ($act === 'save_settings') {
        $cfg = [];
        foreach (['np_api_key','np_ipn_secret','google_client_id','google_client_secret',
                  'site_name','site_url','admin_email','openrouter_api_key'] as $k)
            if (isset($_POST[$k])) $cfg[$k] = trim($_POST[$k]);
        cfg_batch($cfg);
        $msg = 'Settings saved successfully.';
    }

    if ($act === 'adjust_credits') {
        $uid   = trim($_POST['uid'] ?? '');
        $delta = (int)($_POST['delta'] ?? 0);
        if ($uid && $delta !== 0) { adjust_credits($uid, $delta); $msg = "Credits adjusted by $delta for user."; }
    }

    if ($act === 'grant_plan') {
        $uid  = trim($_POST['uid'] ?? '');
        $plan = trim($_POST['plan'] ?? '');
        $days = max(1,(int)($_POST['days'] ?? 30));
        if ($uid && isset(PLANS[$plan])) { grant_plan($uid,$plan,$days); $msg = "Plan '$plan' granted for $days days."; }
    }

    if ($act === 'revoke_plan') {
        $uid = trim($_POST['uid'] ?? '');
        if ($uid) { revoke_plan($uid); $msg = "Plan revoked."; }
    }

    if ($act === 'delete_user') {
        $uid = trim($_POST['uid'] ?? '');
        if ($uid) {
            @unlink(DATA_DIR.'/users/'.preg_replace('/[^a-z0-9_\-]/i','',$uid).'.json');
            @unlink(DATA_DIR.'/users/'.preg_replace('/[^a-z0-9_\-]/i','',$uid).'.key.json');
            $msg = "User deleted.";
        }
    }

    if ($act === 'clear_logs') { log_clear(); $msg = "Logs cleared."; }

    if ($act === 'cleanup') { cleanup_old_data(); $msg = "Old data cleaned up."; }

    if ($act === 'resolve_report') {
        $rid = trim($_POST['report_id'] ?? '');
        if ($rid) { report_set_status($rid, 'resolved'); $msg = "Report marked resolved."; }
    }
    if ($act === 'reopen_report') {
        $rid = trim($_POST['report_id'] ?? '');
        if ($rid) { report_set_status($rid, 'open'); $msg = "Report reopened."; }
    }
}

$tab = $_GET['tab'] ?? 'dashboard';

// ── Data for tabs ─────────────────────────────────────────────────────────────
if ($adminLogin) {
    $allUsers = user_all();
    $allPays  = pay_all();
    $allLogs  = log_all(500);
    $allReports = report_all();
    $st       = stats();
    $settings = [
        'np_api_key'           => cfg('np_api_key', NP_API_KEY),
        'np_ipn_secret'        => cfg('np_ipn_secret', NP_IPN_SECRET),
        'google_client_id'     => cfg('google_client_id', GOOGLE_CLIENT_ID),
        'google_client_secret' => cfg('google_client_secret', GOOGLE_CLIENT_SECRET),
        'site_name'            => cfg('site_name', SITE_NAME),
        'site_url'             => cfg('site_url', SITE_URL),
        'admin_email'          => cfg('admin_email', ''),
        'openrouter_api_key'   => cfg('openrouter_api_key', ''),
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel — <?= h(SITE_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
<style>
:root{
  --bg:#0f1117;--surface:#1a1d27;--surface2:#22263a;--surface3:#2d3148;
  --border:#2a2d3e;--accent:#6c63ff;--accent2:#a78bfa;--green:#10b981;
  --red:#ef4444;--yellow:#f59e0b;--blue:#3b82f6;--text:#e2e8f0;
  --text2:#94a3b8;--text3:#64748b;--radius:12px;--radius-sm:8px;
  --shadow:0 4px 24px rgba(0,0,0,.4);
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex}

/* ── Sidebar ── */
.sidebar{width:260px;min-height:100vh;background:var(--surface);border-right:1px solid var(--border);
  display:flex;flex-direction:column;position:sticky;top:0;height:100vh;overflow-y:auto;flex-shrink:0}
.sidebar-logo{padding:24px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px}
.sidebar-logo .logo-icon{width:38px;height:38px;background:linear-gradient(135deg,var(--accent),var(--accent2));
  border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.sidebar-logo .logo-text{font-size:18px;font-weight:700;color:var(--text)}
.sidebar-logo .logo-sub{font-size:11px;color:var(--text3)}
.nav-section{padding:8px 12px;margin-top:8px}
.nav-label{font-size:10px;font-weight:600;color:var(--text3);letter-spacing:.08em;text-transform:uppercase;padding:4px 8px 8px}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:var(--radius-sm);
  color:var(--text2);text-decoration:none;font-size:14px;font-weight:500;margin-bottom:2px;
  transition:all .2s;cursor:pointer}
.nav-item:hover{background:var(--surface2);color:var(--text)}
.nav-item.active{background:linear-gradient(135deg,rgba(108,99,255,.25),rgba(167,139,250,.15));
  color:var(--accent2);border:1px solid rgba(108,99,255,.3)}
.nav-item svg{width:18px;height:18px;flex-shrink:0}
.nav-badge{margin-left:auto;background:var(--accent);color:#fff;font-size:10px;
  font-weight:700;padding:2px 7px;border-radius:20px}

.sidebar-footer{margin-top:auto;padding:16px;border-top:1px solid var(--border)}
.sidebar-footer a{display:flex;align-items:center;gap:8px;color:var(--text3);font-size:13px;
  text-decoration:none;padding:8px;border-radius:var(--radius-sm);transition:.2s}
.sidebar-footer a:hover{background:var(--surface2);color:var(--red)}

/* ── Main ── */
.main{flex:1;display:flex;flex-direction:column;min-width:0}
.topbar{height:64px;background:var(--surface);border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;padding:0 28px;position:sticky;top:0;z-index:50}
.topbar-title{font-size:18px;font-weight:700}
.topbar-right{display:flex;align-items:center;gap:12px}
.topbar-badge{display:flex;align-items:center;gap:6px;background:var(--surface2);
  border:1px solid var(--border);border-radius:8px;padding:6px 12px;font-size:13px;color:var(--text2)}
.topbar-badge .dot{width:7px;height:7px;background:var(--green);border-radius:50%;animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}

.content{padding:28px;flex:1}

/* ── Cards ── */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:20px;margin-bottom:28px}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
  padding:22px;position:relative;overflow:hidden;transition:.2s}
.stat-card:hover{border-color:rgba(108,99,255,.4);transform:translateY(-1px)}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--grad)}
.stat-card[data-color="purple"]{--grad:linear-gradient(90deg,#6c63ff,#a78bfa)}
.stat-card[data-color="green"]{--grad:linear-gradient(90deg,#10b981,#34d399)}
.stat-card[data-color="blue"]{--grad:linear-gradient(90deg,#3b82f6,#60a5fa)}
.stat-card[data-color="yellow"]{--grad:linear-gradient(90deg,#f59e0b,#fbbf24)}
.stat-card[data-color="red"]{--grad:linear-gradient(90deg,#ef4444,#f87171)}
.stat-label{font-size:12px;color:var(--text3);font-weight:500;margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em}
.stat-value{font-size:32px;font-weight:800;color:var(--text)}
.stat-sub{font-size:12px;color:var(--text2);margin-top:4px}
.stat-icon{position:absolute;top:20px;right:20px;width:44px;height:44px;border-radius:10px;
  display:flex;align-items:center;justify-content:center;font-size:22px;background:rgba(255,255,255,.05)}

.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:24px}
.card-header{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;
  border-bottom:1px solid var(--border)}
.card-title{font-size:16px;font-weight:700;display:flex;align-items:center;gap:10px}
.card-title svg{color:var(--accent2)}

/* ── Table ── */
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:14px}
thead tr{background:var(--surface2)}
th{padding:12px 16px;text-align:left;font-size:11px;font-weight:600;color:var(--text3);
  letter-spacing:.06em;text-transform:uppercase;white-space:nowrap}
td{padding:13px 16px;border-bottom:1px solid var(--border);color:var(--text2);vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:rgba(255,255,255,.02)}
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;
  font-size:11px;font-weight:600;white-space:nowrap}
.badge-green{background:rgba(16,185,129,.15);color:#34d399;border:1px solid rgba(16,185,129,.3)}
.badge-red{background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.3)}
.badge-blue{background:rgba(59,130,246,.15);color:#60a5fa;border:1px solid rgba(59,130,246,.3)}
.badge-yellow{background:rgba(245,158,11,.15);color:#fbbf24;border:1px solid rgba(245,158,11,.3)}
.badge-purple{background:rgba(108,99,255,.15);color:#a78bfa;border:1px solid rgba(108,99,255,.3)}
.badge-gray{background:rgba(100,116,139,.15);color:#94a3b8;border:1px solid rgba(100,116,139,.3)}

/* ── Forms ── */
.form-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:18px}
.form-group{display:flex;flex-direction:column;gap:6px}
.form-group label{font-size:13px;font-weight:600;color:var(--text2)}
.form-group input,.form-group select,.form-group textarea{
  background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius-sm);
  color:var(--text);font-family:inherit;font-size:14px;padding:10px 14px;outline:none;
  transition:.2s;width:100%}
.form-group input:focus,.form-group select:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(108,99,255,.15)}
.form-hint{font-size:11px;color:var(--text3);margin-top:2px}

.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:var(--radius-sm);
  font-family:inherit;font-size:14px;font-weight:600;cursor:pointer;border:none;transition:.2s}
.btn-primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff}
.btn-primary:hover{opacity:.9;transform:translateY(-1px)}
.btn-danger{background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);color:#f87171}
.btn-danger:hover{background:rgba(239,68,68,.3)}
.btn-success{background:rgba(16,185,129,.15);border:1px solid rgba(16,185,129,.3);color:#34d399}
.btn-success:hover{background:rgba(16,185,129,.3)}
.btn-sm{padding:6px 14px;font-size:12px}

/* ── Alert ── */
.alert{padding:14px 18px;border-radius:var(--radius-sm);font-size:14px;margin-bottom:20px;
  display:flex;align-items:center;gap:10px;border:1px solid}
.alert-success{background:rgba(16,185,129,.1);border-color:rgba(16,185,129,.3);color:#34d399}
.alert-error{background:rgba(239,68,68,.1);border-color:rgba(239,68,68,.3);color:#f87171}

/* ── Modal ── */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:200;
  align-items:center;justify-content:center;backdrop-filter:blur(4px)}
.modal-overlay.open{display:flex}
.modal{background:var(--surface);border:1px solid var(--border);border-radius:16px;
  width:480px;max-width:95vw;box-shadow:var(--shadow)}
.modal-head{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;
  border-bottom:1px solid var(--border)}
.modal-head h3{font-size:16px;font-weight:700}
.modal-close{width:32px;height:32px;border-radius:8px;background:var(--surface2);border:none;
  color:var(--text2);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:18px}
.modal-body{padding:24px}
.modal-foot{display:flex;justify-content:flex-end;gap:10px;padding:16px 24px;border-top:1px solid var(--border)}

/* ── Login page ── */
.login-page{display:flex;align-items:center;justify-content:center;min-height:100vh;width:100%;
  background:radial-gradient(ellipse at 30% 30%, rgba(108,99,255,.2) 0%, transparent 60%),
  radial-gradient(ellipse at 70% 70%, rgba(16,185,129,.1) 0%, transparent 60%), var(--bg)}
.login-box{background:var(--surface);border:1px solid var(--border);border-radius:20px;
  padding:44px;width:420px;max-width:95vw;box-shadow:var(--shadow)}
.login-box h1{font-size:24px;font-weight:800;text-align:center;margin-bottom:6px}
.login-box p{color:var(--text2);text-align:center;font-size:14px;margin-bottom:32px}

/* ── User row actions ── */
.row-actions{display:flex;gap:6px;flex-wrap:wrap}

/* ── Log entry ── */
.log-ok{color:var(--green)}
.log-fail{color:var(--red)}
.monospace{font-family:monospace;font-size:12px}
.truncate{max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

/* ── Search bar ── */
.search-bar{display:flex;align-items:center;gap:10px;padding:14px 22px;border-bottom:1px solid var(--border);background:var(--surface2)}
.search-bar input{flex:1;background:var(--surface3);border:1px solid var(--border);border-radius:8px;
  color:var(--text);padding:9px 14px;font-size:13px;outline:none;font-family:inherit}
.search-bar input:focus{border-color:var(--accent)}

/* ── Revenue highlight ── */
.rev-chip{background:linear-gradient(135deg,rgba(16,185,129,.2),rgba(52,211,153,.1));
  border:1px solid rgba(16,185,129,.3);border-radius:8px;padding:4px 12px;font-size:13px;
  font-weight:700;color:#34d399}

/* ── Scrollbar ── */
::-webkit-scrollbar{width:6px;height:6px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px}
::-webkit-scrollbar-thumb:hover{background:var(--text3)}

/* ── Mobile ── */
@media(max-width:768px){
  .sidebar{display:none}
  .content{padding:16px}
  .stats-grid{grid-template-columns:1fr 1fr}
  .form-grid{grid-template-columns:1fr}
}
</style>
</head>
<body>
<?php if (!$adminLogin): ?>
<!-- ── LOGIN ─────────────────────────────────────────────────────────────── -->
<div class="login-page">
<div class="login-box">
  <div style="text-align:center;margin-bottom:20px">
    <div style="width:60px;height:60px;background:linear-gradient(135deg,#6c63ff,#a78bfa);border-radius:16px;
      display:inline-flex;align-items:center;justify-content:center;color:#fff;margin-bottom:12px"><?= icon('bolt',28) ?></div>
  </div>
  <h1>Admin Panel</h1>
  <p>Sign in to manage <?= h(SITE_NAME) ?></p>
  <?php if (!empty($loginError)): ?>
  <div class="alert alert-error"><?= icon('xCircle',15) ?> <?= h($loginError) ?></div>
  <?php endif; ?>
  <form method="POST">
    <input type="hidden" name="_admin_action" value="login">
    <div class="form-group" style="margin-bottom:16px">
      <label>Username</label>
      <input type="text" name="user" placeholder="admin" required autofocus>
    </div>
    <div class="form-group" style="margin-bottom:24px">
      <label>Password</label>
      <input type="password" name="pass" placeholder="••••••••" required>
    </div>
    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:13px">
      Sign In to Admin
    </button>
  </form>
  <p style="text-align:center;margin-top:20px;font-size:13px;color:var(--text3)">
    <a href="index.php" style="color:var(--accent2)">← Back to main site</a>
  </p>
</div>
</div>
<?php else: ?>
<!-- ── SIDEBAR ───────────────────────────────────────────────────────────── -->
<nav class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon"><?= icon('bolt',18) ?></div>
    <div>
      <div class="logo-text"><?= h(SITE_NAME) ?></div>
      <div class="logo-sub">Admin Panel</div>
    </div>
  </div>

  <div class="nav-section">
    <div class="nav-label">Overview</div>
    <a href="?tab=dashboard" class="nav-item <?= $tab==='dashboard'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Dashboard
    </a>
    <a href="?tab=users" class="nav-item <?= $tab==='users'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Users
      <span class="nav-badge"><?= count($allUsers) ?></span>
    </a>
    <a href="?tab=payments" class="nav-item <?= $tab==='payments'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
      Payments
      <span class="nav-badge"><?= count(array_filter($allPays,fn($p)=>in_array($p['status']??'',['finished','confirmed']))) ?></span>
    </a>
    <a href="?tab=logs" class="nav-item <?= $tab==='logs'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      Logs
    </a>
    <a href="?tab=reports" class="nav-item <?= $tab==='reports'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 3v18"/><path d="M5 4h11l-2 4 2 4H5"/></svg>
      Reports
      <span class="nav-badge"><?= count(array_filter($allReports,fn($r)=>($r['status']??'')==='open')) ?></span>
    </a>
    <a href="?tab=settings" class="nav-item <?= $tab==='settings'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
      Settings
    </a>
  </div>

  <div class="sidebar-footer">
    <a href="index.php" target="_blank">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
      View Site
    </a>
    <a href="?logout_admin=1">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Logout
    </a>
  </div>
</nav>

<!-- ── MAIN ──────────────────────────────────────────────────────────────── -->
<div class="main">
  <div class="topbar">
    <div class="topbar-title">
      <?= h(ucfirst($tab)) ?>
    </div>
    <div class="topbar-right">
      <span class="rev-chip"><?= icon('creditCard',13) ?> $<?= number_format($st['revenue'],2) ?> Revenue</span>
      <div class="topbar-badge"><span class="dot"></span> System Online</div>
    </div>
  </div>

  <div class="content">
    <?php if ($msg): ?>
    <div class="alert alert-success"><?= icon('checkCircle',15) ?> <?= h($msg) ?></div>
    <?php endif; ?>

<?php if ($tab === 'dashboard'): ?>
<!-- ══ DASHBOARD ══════════════════════════════════════════════════════════ -->
<div class="stats-grid">
  <div class="stat-card" data-color="purple">
    <div class="stat-icon"><?= icon('user',20) ?></div>
    <div class="stat-label">Total Users</div>
    <div class="stat-value"><?= number_format($st['total_users']) ?></div>
    <div class="stat-sub">Registered accounts</div>
  </div>
  <div class="stat-card" data-color="green">
    <div class="stat-icon"><?= icon('star',20) ?></div>
    <div class="stat-label">Premium Users</div>
    <div class="stat-value"><?= number_format($st['premium_users']) ?></div>
    <div class="stat-sub">Active paid plans</div>
  </div>
  <div class="stat-card" data-color="blue">
    <div class="stat-icon"><?= icon('chart',20) ?></div>
    <div class="stat-label">URLs Indexed Today</div>
    <div class="stat-value"><?= number_format($st['today_logs']) ?></div>
    <div class="stat-sub">Submissions today</div>
  </div>
  <div class="stat-card" data-color="yellow">
    <div class="stat-icon"><?= icon('creditCard',20) ?></div>
    <div class="stat-label">Total Payments</div>
    <div class="stat-value"><?= number_format($st['total_payments']) ?></div>
    <div class="stat-sub">Completed orders</div>
  </div>
  <div class="stat-card" data-color="green">
    <div class="stat-icon"><?= icon('creditCard',20) ?></div>
    <div class="stat-label">Total Revenue</div>
    <div class="stat-value">$<?= number_format($st['revenue'],2) ?></div>
    <div class="stat-sub">From paid plans</div>
  </div>
  <div class="stat-card" data-color="purple">
    <div class="stat-icon"><?= icon('external',20) ?></div>
    <div class="stat-label">Total Submissions</div>
    <div class="stat-value"><?= number_format($st['total_logs']) ?></div>
    <div class="stat-sub"><?= number_format($st['ok_logs']) ?> success / <?= number_format($st['fail_logs']) ?> fail</div>
  </div>
</div>

<!-- Recent Users -->
<div class="card">
  <div class="card-header">
    <div class="card-title">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
      Recent Users
    </div>
    <a href="?tab=users" class="btn btn-primary btn-sm">View All</a>
  </div>
  <div class="table-wrap">
  <table>
    <thead><tr><th>User</th><th>Plan</th><th>Used Today</th><th>Joined</th></tr></thead>
    <tbody>
    <?php foreach(array_slice($allUsers,0,8) as $u): ?>
    <tr>
      <td>
        <div style="font-weight:500"><?= h($u['name'] ?? 'Anonymous') ?></div>
        <div style="font-size:11px;color:var(--text3)"><?= h($u['email'] ?? $u['id']) ?></div>
      </td>
      <td>
        <?php $plan=$u['plan']??'free'; ?>
        <span class="badge badge-<?= $plan==='free'?'gray':($plan==='starter'?'blue':($plan==='pro'?'purple':'yellow')) ?>">
          <?= ucfirst($plan) ?>
        </span>
      </td>
      <td><?= (int)($u['used_today']??0) ?> / <?= (int)($u['daily_limit']??100) ?></td>
      <td class="monospace" style="font-size:12px"><?= h(substr($u['created_at']??'',0,10)) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<!-- Recent Payments -->
<div class="card">
  <div class="card-header">
    <div class="card-title">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
      Recent Payments
    </div>
    <a href="?tab=payments" class="btn btn-primary btn-sm">View All</a>
  </div>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Order</th><th>Plan</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
    <tbody>
    <?php foreach(array_slice($allPays,0,8) as $p): ?>
    <tr>
      <td class="monospace truncate"><?= h($p['order_id']??'') ?></td>
      <td><span class="badge badge-purple"><?= ucfirst($p['plan']??'') ?></span></td>
      <td style="color:var(--green);font-weight:600">$<?= number_format($p['price_usd']??0,2) ?></td>
      <td>
        <?php $st2=$p['status']??'pending'; ?>
        <span class="badge badge-<?= in_array($st2,['finished','confirmed'])?'green':($st2==='pending'?'yellow':'red') ?>">
          <?= h($st2) ?>
        </span>
      </td>
      <td class="monospace" style="font-size:12px"><?= h(substr($p['created_at']??'',0,10)) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<?php elseif ($tab === 'users'): ?>
<!-- ══ USERS ══════════════════════════════════════════════════════════════ -->
<div class="card">
  <div class="card-header">
    <div class="card-title">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
      All Users (<?= count($allUsers) ?>)
    </div>
  </div>
  <div class="search-bar">
    <input type="text" id="userSearch" placeholder="Search by name, email or ID..." oninput="filterTable('userTbl',this.value)">
    <select id="planFilter" onchange="filterTablePlan()" style="background:var(--surface3);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:9px 14px;font-size:13px;font-family:inherit;outline:none">
      <option value="">All Plans</option>
      <option value="free">Free</option>
      <option value="starter">Starter</option>
      <option value="pro">Pro</option>
      <option value="business">Business</option>
    </select>
  </div>
  <div class="table-wrap">
  <table id="userTbl">
    <thead><tr><th>User</th><th>Plan</th><th>Limit/Used</th><th>Country</th><th>Joined</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($allUsers as $u): ?>
    <?php $plan=$u['plan']??'free'; $exp=!empty($u['plan_expires'])?$u['plan_expires']:''; ?>
    <tr data-plan="<?= h($plan) ?>">
      <td>
        <div style="display:flex;align-items:center;gap:10px">
          <?php if(!empty($u['picture'])): ?>
            <img src="<?= h($u['picture']) ?>" width="32" height="32" style="border-radius:50%;object-fit:cover" loading="lazy">
          <?php else: ?>
            <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#6c63ff,#a78bfa);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#fff;flex-shrink:0">
              <?= strtoupper(substr($u['name']??$u['email']??'?',0,1)) ?>
            </div>
          <?php endif; ?>
          <div>
            <div style="font-weight:600"><?= h($u['name'] ?? 'Anonymous') ?></div>
            <div style="font-size:11px;color:var(--text3)"><?= h($u['email'] ?? $u['id']) ?></div>
          </div>
        </div>
      </td>
      <td>
        <span class="badge badge-<?= $plan==='free'?'gray':($plan==='starter'?'blue':($plan==='pro'?'purple':'yellow')) ?>">
          <?= ucfirst($plan) ?>
        </span>
        <?php if($exp): ?>
          <div style="font-size:10px;color:var(--text3);margin-top:3px">Exp: <?= h(substr($exp,0,10)) ?></div>
        <?php endif; ?>
      </td>
      <td>
        <span style="font-weight:600"><?= (int)($u['daily_limit']??100) ?></span>
        <span style="color:var(--text3)"> / <?= (int)($u['used_today']??0) ?> today</span>
        <div style="font-size:11px;color:var(--text3)">Total: <?= number_format($u['used_total']??0) ?></div>
      </td>
      <td><?= h($u['country']??'?') ?></td>
      <td class="monospace" style="font-size:11px"><?= h(substr($u['created_at']??'',0,10)) ?></td>
      <td>
        <div class="row-actions">
          <button class="btn btn-success btn-sm" onclick="openGrantModal('<?= h($u['id']) ?>','<?= h($u['name']??$u['email']??$u['id']) ?>')">Grant Plan</button>
          <button class="btn btn-primary btn-sm" onclick="openCreditModal('<?= h($u['id']) ?>','<?= h($u['name']??$u['email']??$u['id']) ?>')">Credits</button>
          <?php if($plan!=='free'): ?>
          <form method="POST" style="display:inline" onsubmit="return confirm('Revoke plan?')">
            <input type="hidden" name="_admin_action" value="revoke_plan">
            <input type="hidden" name="uid" value="<?= h($u['id']) ?>">
            <button type="submit" class="btn btn-danger btn-sm">Revoke</button>
          </form>
          <?php endif; ?>
          <form method="POST" style="display:inline" onsubmit="return confirm('Delete this user permanently?')">
            <input type="hidden" name="_admin_action" value="delete_user">
            <input type="hidden" name="uid" value="<?= h($u['id']) ?>">
            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
          </form>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<?php elseif ($tab === 'payments'): ?>
<!-- ══ PAYMENTS ════════════════════════════════════════════════════════════ -->
<?php
$confirmed = array_filter($allPays,fn($p)=>in_array($p['status']??'',['finished','confirmed']));
$pending   = array_filter($allPays,fn($p)=>($p['status']??'')==='pending');
$revenue   = array_sum(array_column($confirmed,'price_usd'));
?>
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
  <div class="stat-card" data-color="green"><div class="stat-icon"><?= icon('checkCircle',20) ?></div><div class="stat-label">Completed</div><div class="stat-value"><?= count($confirmed) ?></div></div>
  <div class="stat-card" data-color="yellow"><div class="stat-icon">⏳</div><div class="stat-label">Pending</div><div class="stat-value"><?= count($pending) ?></div></div>
  <div class="stat-card" data-color="blue"><div class="stat-icon"><?= icon('clipboard',20) ?></div><div class="stat-label">Total Orders</div><div class="stat-value"><?= count($allPays) ?></div></div>
  <div class="stat-card" data-color="green"><div class="stat-icon"><?= icon('creditCard',20) ?></div><div class="stat-label">Revenue</div><div class="stat-value">$<?= number_format($revenue,2) ?></div></div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
      All Payments
    </div>
  </div>
  <div class="search-bar">
    <input type="text" placeholder="Search by order ID or user ID..." oninput="filterTable('payTbl',this.value)">
  </div>
  <div class="table-wrap">
  <table id="payTbl">
    <thead><tr><th>Order ID</th><th>User</th><th>Plan</th><th>Amount</th><th>Currency</th><th>Status</th><th>Created</th></tr></thead>
    <tbody>
    <?php foreach($allPays as $p): ?>
    <?php $st2=$p['status']??'pending'; ?>
    <tr>
      <td class="monospace" style="font-size:11px"><?= h($p['order_id']??'') ?></td>
      <td class="monospace" style="font-size:11px"><?= h(substr($p['uid']??'',0,12)) ?>…</td>
      <td><span class="badge badge-purple"><?= ucfirst($p['plan']??'') ?></span></td>
      <td style="color:var(--green);font-weight:700">$<?= number_format($p['price_usd']??0,2) ?></td>
      <td style="text-transform:uppercase;font-size:12px"><?= h($p['pay_currency']??'') ?></td>
      <td><span class="badge badge-<?= in_array($st2,['finished','confirmed'])?'green':($st2==='pending'?'yellow':'red') ?>"><?= h($st2) ?></span></td>
      <td class="monospace" style="font-size:11px"><?= h(substr($p['created_at']??'',0,16)) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<?php elseif ($tab === 'logs'): ?>
<!-- ══ LOGS ════════════════════════════════════════════════════════════════ -->
<div class="card">
  <div class="card-header">
    <div class="card-title">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      Submission Logs (Last 500)
    </div>
    <form method="POST" onsubmit="return confirm('Clear all logs?')">
      <input type="hidden" name="_admin_action" value="clear_logs">
      <button type="submit" class="btn btn-danger btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M9 7V4h6v3m-8 0 1 13h8l1-13"/></svg> Clear Logs</button>
    </form>
  </div>
  <div class="search-bar">
    <input type="text" placeholder="Filter by URL or user ID..." oninput="filterTable('logTbl',this.value)">
  </div>
  <div class="table-wrap">
  <table id="logTbl">
    <thead><tr><th>Status</th><th>URL</th><th>User</th><th>IP</th><th>Message</th><th>Time</th></tr></thead>
    <tbody>
    <?php foreach($allLogs as $l): ?>
    <tr>
      <td><?php if($l['ok']??false): ?><span class="badge badge-green">✓ OK</span><?php else: ?><span class="badge badge-red">✗ Fail</span><?php endif; ?></td>
      <td class="truncate monospace" style="max-width:200px;font-size:11px"><?= h($l['url']??'') ?></td>
      <td class="monospace" style="font-size:11px"><?= h(substr($l['uid']??'',0,12)) ?>…</td>
      <td class="monospace" style="font-size:11px"><?= h($l['ip']??'') ?></td>
      <td style="font-size:12px;color:var(--text3)"><?= h($l['msg']??'') ?></td>
      <td class="monospace" style="font-size:11px"><?= h(substr($l['at']??'',0,16)) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<div style="margin-top:16px">
  <form method="POST">
    <input type="hidden" name="_admin_action" value="cleanup">
    <button type="submit" class="btn btn-danger"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px"><path d="M4 7h16M9 7V4h6v3m-8 0 1 13h8l1-13"/></svg>Run Data Cleanup (Remove files older than <?= DATA_RETENTION_DAYS ?> days)</button>
  </form>
</div>

<?php elseif ($tab === 'reports'): ?>
<!-- ══ REPORTS / SUPPORT TICKETS ════════════════════════════════════════════ -->
<div class="card">
  <div class="card-header">
    <div class="card-title">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 3v18"/><path d="M5 4h11l-2 4 2 4H5"/></svg>
      Reports &amp; Support Tickets (<?= count($allReports) ?>)
    </div>
  </div>
  <div class="search-bar">
    <input type="text" placeholder="Filter by email or message..." oninput="filterTable('reportTbl',this.value)">
  </div>
  <div class="table-wrap">
  <table id="reportTbl">
    <thead><tr><th>Status</th><th>Source</th><th>Category</th><th>Email</th><th>Message</th><th>IP</th><th>Time</th><th></th></tr></thead>
    <tbody>
    <?php if (empty($allReports)): ?>
    <tr><td colspan="8" style="text-align:center;color:var(--text3);padding:24px">No reports yet.</td></tr>
    <?php endif ?>
    <?php foreach($allReports as $r): ?>
    <tr>
      <td><?php if(($r['status']??'')==='resolved'): ?><span class="badge badge-green">Resolved</span><?php else: ?><span class="badge badge-yellow">Open</span><?php endif; ?></td>
      <td style="font-size:11px"><?= h($r['source']??'') ?></td>
      <td style="font-size:11px"><?= h($r['category']??'-') ?></td>
      <td class="monospace" style="font-size:11px"><?= h($r['email']??'-') ?></td>
      <td class="truncate" style="max-width:260px;font-size:12px;color:var(--text3)" title="<?= h($r['message']??'') ?>"><?= h($r['message']??'') ?></td>
      <td class="monospace" style="font-size:11px"><?= h($r['ip']??'') ?></td>
      <td class="monospace" style="font-size:11px"><?= h(substr($r['created_at']??'',0,16)) ?></td>
      <td>
        <form method="POST" style="display:inline">
          <input type="hidden" name="_admin_action" value="<?= ($r['status']??'')==='resolved'?'reopen_report':'resolve_report' ?>">
          <input type="hidden" name="report_id" value="<?= h($r['id']??'') ?>">
          <button type="submit" class="btn btn-sm <?= ($r['status']??'')==='resolved'?'':'btn-primary' ?>"><?= ($r['status']??'')==='resolved'?'Reopen':'Resolve' ?></button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<?php elseif ($tab === 'settings'): ?>
<!-- ══ SETTINGS ════════════════════════════════════════════════════════════ -->
<form method="POST">
  <input type="hidden" name="_admin_action" value="save_settings">

  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
        NOWPayments Configuration
      </div>
    </div>
    <div style="padding:24px">
      <div class="form-grid">
        <div class="form-group">
          <label>NOWPayments API Key</label>
          <input type="text" name="np_api_key" value="<?= h($settings['np_api_key']) ?>" placeholder="Enter NOWPayments API key">
          <div class="form-hint">Get your API key from nowpayments.io/store-settings</div>
        </div>
        <div class="form-group">
          <label>IPN Secret Key</label>
          <input type="text" name="np_ipn_secret" value="<?= h($settings['np_ipn_secret']) ?>" placeholder="IPN secret from NOWPayments">
          <div class="form-hint">Used to verify payment webhooks (IPN callbacks)</div>
        </div>
      </div>
      <div style="margin-top:14px;padding:14px;background:rgba(108,99,255,.1);border:1px solid rgba(108,99,255,.3);border-radius:8px;font-size:13px;color:var(--text2)">
        <strong>Webhook URL:</strong> <code style="color:var(--accent2)"><?= h(site_url('webhook.php')) ?></code><br>
        Set this as your IPN Callback URL in NOWPayments dashboard.
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
        Google OAuth (Optional)
      </div>
    </div>
    <div style="padding:24px">
      <div class="form-grid">
        <div class="form-group">
          <label>Google Client ID</label>
          <input type="text" name="google_client_id" value="<?= h($settings['google_client_id']) ?>" placeholder="xxxx.apps.googleusercontent.com">
        </div>
        <div class="form-group">
          <label>Google Client Secret</label>
          <input type="text" name="google_client_secret" value="<?= h($settings['google_client_secret']) ?>" placeholder="GOCSPX-...">
        </div>
      </div>
      <div style="margin-top:14px;padding:14px;background:rgba(220,38,38,.06);border:1px solid rgba(220,38,38,.2);border-radius:8px;font-size:13px;color:var(--text2)">
        <strong>Setup steps:</strong><br>
        1. Go to <strong>console.cloud.google.com</strong> → APIs &amp; Services → Credentials → Create OAuth client ID<br>
        2. App type: <strong>Web application</strong><br>
        3. Add authorized redirect URI: <code><?= h(site_url('api.php?action=oauth_callback')) ?></code><br>
        4. Copy Client ID and Client Secret here, then save.<br>
        5. Publish your OAuth consent screen (or add test users while testing).
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
        Site Configuration
      </div>
    </div>
    <div style="padding:24px">
      <div class="form-grid">
        <div class="form-group">
          <label>Site Name</label>
          <input type="text" name="site_name" value="<?= h($settings['site_name']) ?>">
        </div>
        <div class="form-group">
          <label>Site URL</label>
          <input type="url" name="site_url" value="<?= h($settings['site_url']) ?>" placeholder="https://yoursite.com">
        </div>
        <div class="form-group">
          <label>Admin Email</label>
          <input type="email" name="admin_email" value="<?= h($settings['admin_email']) ?>" placeholder="admin@yoursite.com">
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <?= icon('zap',18) ?>
        OpenRouter AI (Free Chat Assistant)
      </div>
    </div>
    <div style="padding:24px">
      <div class="form-group">
        <label>OpenRouter API Key</label>
        <input type="text" name="openrouter_api_key" value="<?= h($settings['openrouter_api_key']) ?>" placeholder="sk-or-v1-...">
      </div>
      <div style="margin-top:10px;padding:14px;background:rgba(22,163,74,.08);border:1px solid rgba(22,163,74,.25);border-radius:8px;font-size:13px;color:var(--text2)">
        <strong>Free AI chat for visitors.</strong> Get a free API key at <strong>openrouter.ai</strong> → create account → <em>Keys</em>. Free models like <code>deepseek/deepseek-chat-v3-5b:free</code> are used so there is no cost. When a key is set, the chat widget uses AI to answer support questions automatically before escalating to a ticket.
      </div>
    </div>
  </div>

  <div style="display:flex;gap:12px;margin-top:4px">
    <button type="submit" class="btn btn-primary" style="padding:13px 28px"><?= icon('checkCircle',14) ?> Save All Settings</button>
  </div>
</form>

<?php endif; ?>
  </div><!-- /content -->
</div><!-- /main -->

<!-- ── Grant Plan Modal ──────────────────────────────────────────────────── -->
<div class="modal-overlay" id="grantModal">
  <div class="modal">
    <div class="modal-head">
      <h3><?= icon('star',16) ?> Grant Plan</h3>
      <button class="modal-close" onclick="document.getElementById('grantModal').classList.remove('open')">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="_admin_action" value="grant_plan">
      <input type="hidden" name="uid" id="grantUid">
      <div class="modal-body">
        <p style="color:var(--text2);font-size:14px;margin-bottom:20px">Granting plan to: <strong id="grantName" style="color:var(--text)"></strong></p>
        <div class="form-grid" style="grid-template-columns:1fr 1fr">
          <div class="form-group">
            <label>Plan</label>
            <select name="plan">
              <option value="starter">Starter — 500/day</option>
              <option value="pro" selected>Pro — 2,000/day</option>
              <option value="business">Business — 10,000/day</option>
            </select>
          </div>
          <div class="form-group">
            <label>Days</label>
            <input type="number" name="days" value="30" min="1" max="3650">
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn" style="background:var(--surface2)" onclick="document.getElementById('grantModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Grant Plan</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Adjust Credits Modal ──────────────────────────────────────────────── -->
<div class="modal-overlay" id="creditModal">
  <div class="modal">
    <div class="modal-head">
      <h3><?= icon('settings',16) ?> Adjust Daily Limit</h3>
      <button class="modal-close" onclick="document.getElementById('creditModal').classList.remove('open')">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="_admin_action" value="adjust_credits">
      <input type="hidden" name="uid" id="creditUid">
      <div class="modal-body">
        <p style="color:var(--text2);font-size:14px;margin-bottom:20px">Adjusting credits for: <strong id="creditName" style="color:var(--text)"></strong></p>
        <div class="form-group">
          <label>Delta (positive to add, negative to remove)</label>
          <input type="number" name="delta" value="100" placeholder="e.g. 500 or -100">
          <div class="form-hint">Current daily limit will be adjusted by this amount</div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn" style="background:var(--surface2)" onclick="document.getElementById('creditModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Apply</button>
      </div>
    </form>
  </div>
</div>

<?php endif; ?>

<script>
function openGrantModal(uid, name) {
  document.getElementById('grantUid').value = uid;
  document.getElementById('grantName').textContent = name;
  document.getElementById('grantModal').classList.add('open');
}
function openCreditModal(uid, name) {
  document.getElementById('creditUid').value = uid;
  document.getElementById('creditName').textContent = name;
  document.getElementById('creditModal').classList.add('open');
}
function filterTable(id, q) {
  q = q.toLowerCase();
  document.querySelectorAll('#'+id+' tbody tr').forEach(tr => {
    tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}
function filterTablePlan() {
  const plan = document.getElementById('planFilter').value;
  document.querySelectorAll('#userTbl tbody tr').forEach(tr => {
    tr.style.display = (!plan || tr.dataset.plan === plan) ? '' : 'none';
  });
}
// Close modals on overlay click
document.querySelectorAll('.modal-overlay').forEach(o => {
  o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
});
</script>
</body>
</html>
