<?php
require_once __DIR__ . '/../config.php';

if (is_admin_logged_in()) { header('Location: index.php'); exit; }

$error = '';
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

$recentFails = $pdo->prepare("SELECT COUNT(*) FROM admin_login_attempts WHERE ip = ? AND success = 0 AND attempted_at > (NOW() - INTERVAL 15 MINUTE)");
$recentFails->execute([$ip]);
$locked = (int)$recentFails->fetchColumn() >= 8;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$locked) {
    if (!csrf_check()) {
        $error = 'Session expired, please try again.';
    } else {
        $u = trim($_POST['username'] ?? '');
        $p = (string)($_POST['password'] ?? '');
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
        $stmt->execute([$u]);
        $admin = $stmt->fetch();

        $ok = $admin && password_verify($p, $admin['password_hash']);
        $pdo->prepare("INSERT INTO admin_login_attempts (ip, success) VALUES (?, ?)")->execute([$ip, $ok ? 1 : 0]);

        if ($ok) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_user'] = $admin['username'];
            header('Location: index.php');
            exit;
        }
        $error = 'Invalid username or password.';
    }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login — <?= e(setting('site_name', 'Syria Home')) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
body{margin:0;font-family:'Segoe UI',Arial,sans-serif;background:linear-gradient(160deg,#0f172a,#1e1b4b);min-height:100vh;display:flex;align-items:center;justify-content:center}
.box{background:#fff;border-radius:18px;padding:36px;width:100%;max-width:380px;box-shadow:0 30px 80px rgba(0,0,0,.35)}
.logo{display:flex;align-items:center;gap:10px;font-weight:800;font-size:20px;margin-bottom:26px;color:#0f172a}
.logo span{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#6366f1,#22d3ee);display:flex;align-items:center;justify-content:center;color:#fff}
label{display:block;font-size:13px;font-weight:700;margin:14px 0 6px;color:#334155}
input{width:100%;padding:12px 13px;border:1px solid #dde3ee;border-radius:10px;font-size:14px;box-sizing:border-box}
button{width:100%;margin-top:22px;background:linear-gradient(135deg,#6366f1,#22d3ee);color:#fff;border:0;padding:13px;border-radius:10px;font-weight:800;font-size:14px;cursor:pointer}
.err{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:10px 13px;border-radius:9px;font-size:13px;margin-top:14px}
</style></head><body>
<div class="box">
  <div class="logo"><span><i class="fa-solid fa-layer-group"></i></span> Admin Login</div>
  <?php if ($locked): ?>
    <div class="err">Too many failed attempts. Try again in a few minutes.</div>
  <?php else: ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <label>Username</label>
    <input name="username" autofocus required>
    <label>Password</label>
    <input type="password" name="password" required>
    <button type="submit">Sign in</button>
    <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
  </form>
  <?php endif; ?>
</div>
</body></html>
