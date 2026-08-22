<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/storage.php';

$u = current_user();
if (!$u) { $uid = uid_from_cookie(); $u = user_ensure($uid); }
$ok = false; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { $err = 'Session expired, please try again.'; }
    else {
        $email = trim($_POST['email'] ?? '');
        $category = trim($_POST['category'] ?? 'other');
        $msg = trim($_POST['message'] ?? '');
        if ($msg === '') { $err = 'Please describe the problem.'; }
        elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $err = 'Invalid email address.'; }
        else {
            report_save([
                'id' => bin2hex(random_bytes(8)),
                'uid' => $u['id'],
                'email' => $email ?: ($u['email'] ?? ''),
                'source' => 'report_page',
                'category' => $category,
                'message' => mb_substr($msg, 0, 4000),
                'ip' => get_ip(),
                'status' => 'open',
                'created_at' => now(),
            ]);
            $ok = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Report a Problem — <?= h(SITE_NAME) ?></title>
<meta name="description" content="Report a bug, submission failure, or billing issue with <?= h(SITE_NAME) ?>. Our team reviews every ticket.">
<link rel="icon" href="data:image/svg+xml,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="24" height="24" rx="6" fill="#dc2626"/><path d="M13 3 5 14h6l-1 8 9-13h-6l1-7z" fill="#fff"/></svg>') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<style>
:root{--bg:#f7f7f8;--surface:#fff;--surface2:#f4f5f7;--border:#e6e7eb;--border2:#d4d6dc;
  --red:#dc2626;--red2:#b91c1c;--red-soft:#fee2e2;--green:#16a34a;--charcoal:#374151;
  --text:#16181d;--muted:#6b7280;--muted2:#464b54;--radius:14px;
  --shadow-md:0 8px 24px rgba(17,17,20,.08)}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);line-height:1.7}
a{text-decoration:none;color:var(--red)}a:hover{text-decoration:underline}
.nav{background:rgba(255,255,255,.9);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);
  padding:16px 24px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:10}
.nav-logo{font-size:19px;font-weight:800;display:flex;align-items:center;gap:8px;color:var(--text)}
.nav-logo .icon{width:32px;height:32px;background:var(--red);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff}
.nav-links{display:flex;gap:20px}.nav-links a{color:var(--muted);font-size:14px}
.hero{padding:56px 24px 40px;text-align:center;border-bottom:1px solid var(--border)}
.hero-icon{width:56px;height:56px;border-radius:50%;background:var(--red-soft);color:var(--red);
  display:flex;align-items:center;justify-content:center;margin:0 auto 16px}
.hero h1{font-size:clamp(26px,4vw,38px);font-weight:800;margin-bottom:10px}
.hero p{color:var(--muted);font-size:15px;max-width:520px;margin:0 auto}
.container{max-width:640px;margin:0 auto;padding:48px 24px}
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:32px;box-shadow:var(--shadow-md)}
.form-group{margin-bottom:18px}
.form-group label{display:block;font-size:13px;font-weight:600;color:var(--muted2);margin-bottom:6px}
.form-group input,.form-group select,.form-group textarea{width:100%;background:var(--surface2);
  border:1px solid var(--border2);color:var(--text);padding:12px 14px;border-radius:10px;
  font-size:14px;font-family:'Inter',sans-serif;outline:none;transition:.2s}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:var(--red);box-shadow:0 0 0 3px var(--red-soft)}
.form-group textarea{min-height:140px;resize:vertical;line-height:1.6}
.btn-submit{width:100%;padding:14px;background:var(--red);color:#fff;border:none;border-radius:10px;
  font-size:15px;font-weight:700;cursor:pointer;font-family:'Inter',sans-serif;transition:.2s}
.btn-submit:hover{background:var(--red2)}
.alert{padding:14px 16px;border-radius:10px;font-size:14px;margin-bottom:20px;display:flex;gap:10px;align-items:flex-start}
.alert-success{background:rgba(22,163,74,.08);border:1px solid rgba(22,163,74,.25);color:#166534}
.alert-error{background:var(--red-soft);border:1px solid rgba(220,38,38,.25);color:var(--red2)}
.other-ways{margin-top:28px;padding-top:24px;border-top:1px solid var(--border);text-align:center;font-size:13px;color:var(--muted)}
footer{background:var(--surface);border-top:1px solid var(--border);padding:28px 24px;text-align:center;color:var(--muted);font-size:13px}
footer a{color:var(--muted);margin:0 8px}footer a:hover{color:var(--red)}
</style>
</head>
<body>
<nav class="nav">
  <a href="index.php" class="nav-logo"><div class="icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 3 5 14h6l-1 8 9-13h-6l1-7z"/></svg></div>Index<b style="color:var(--red)">Pro</b></a>
  <div class="nav-links"><a href="index.php">Home</a><a href="about.php">About</a><a href="privacy.php">Privacy</a></div>
</nav>

<div class="hero">
  <div class="hero-icon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 3v18"/><path d="M5 4h11l-2 4 2 4H5"/></svg></div>
  <h1>Report a Problem</h1>
  <p>Found a bug, a failed submission, or a billing issue? Tell us what happened — every report is reviewed by our team, not a bot.</p>
</div>

<div class="container">
  <div class="card">
    <?php if ($ok): ?>
      <div class="alert alert-success">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/></svg>
        <div><strong>Report received.</strong> Thank you — we'll follow up by email if you provided one. Most reports are reviewed within a few hours.</div>
      </div>
      <a href="index.php" style="font-size:14px">← Back to the tool</a>
    <?php else: ?>
      <?php if ($err): ?>
      <div class="alert alert-error">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><path d="M12 3 2 20h20L12 3Z"/><path d="M12 10v5"/></svg>
        <div><?= h($err) ?></div>
      </div>
      <?php endif ?>
      <form method="post">
        <?= csrf_field() ?>
        <div class="form-group">
          <label>What kind of problem is this?</label>
          <select name="category">
            <option value="submission_failed">Submissions failing / rejected by Google</option>
            <option value="key_upload">Service Account key won't upload or validate</option>
            <option value="billing">Billing / payment issue</option>
            <option value="account">Account / login issue</option>
            <option value="bug">Something looks broken on the site</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="form-group">
          <label>Your email (optional, so we can follow up)</label>
          <input type="email" name="email" placeholder="you@example.com" value="<?= h($u['email'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Describe the problem</label>
          <textarea name="message" placeholder="What happened? Include the exact error message if you have one, and roughly when it occurred." required></textarea>
        </div>
        <button type="submit" class="btn-submit">Send Report</button>
      </form>
      <div class="other-ways">Prefer email? Visit the <a href="about.php#contact">About page</a> for our contact details.</div>
    <?php endif ?>
  </div>
</div>

<footer>
  <p style="margin-bottom:10px">© <?= date('Y') ?> <?= h(SITE_NAME) ?> — All rights reserved</p>
  <a href="index.php">Home</a><a href="privacy.php">Privacy</a><a href="terms.php">Terms</a><a href="refund.php">Refund</a><a href="about.php">About</a>
</footer>
</body>
</html>
