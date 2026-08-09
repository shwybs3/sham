<?php
/* Profile setup after Google OAuth — choose username, add bio, upload avatar */
require_once dirname(__DIR__) . '/config.php';
$pdo = $social_pdo;

if (!isset($_SESSION['oauth_pending'])) {
    header('Location: ' . SOCIAL_URL . '/auth/login');
    exit;
}

$pending = $_SESSION['oauth_pending'];
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', trim($_POST['username'] ?? ''));
    $display  = trim($_POST['display_name'] ?? $pending['name']);
    $bio      = trim($_POST['bio'] ?? '');

    if (strlen($username) < 3 || strlen($username) > 30) {
        $error = 'اسم المستخدم يجب أن يكون بين 3 و30 حرفاً';
    } else {
        // Check uniqueness
        $dup = $pdo->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
        $dup->execute([$username]);
        if ($dup->fetchColumn()) {
            $error = 'اسم المستخدم محجوز، اختر آخر';
        } else {
            // Handle avatar upload
            $avatarFile = null;
            if (!empty($_FILES['avatar']['tmp_name'])) {
                $avatarFile = sc_upload_image($_FILES['avatar'], 'avatars');
            }

            $pdo->prepare("INSERT INTO users (username,display_name,bio,avatar,email,google_id,status)
                           VALUES (?,?,?,?,?,?,'active')")
                ->execute([$username, $display, $bio ?: null, $avatarFile, $pending['email'], $pending['google_id']]);
            $newId = (int)$pdo->lastInsertId();
            $user  = $pdo->query("SELECT * FROM users WHERE id={$newId}")->fetch();
            unset($_SESSION['oauth_pending']);
            sc_login($user);
            header('Location: ' . SOCIAL_URL);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>إعداد ملفك الشخصي — <?= sc_h(SOCIAL_NAME) ?></title>
<link rel="icon" href="/social/assets/favicon.svg" type="image/svg+xml">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#0e1117;color:#e6edf3;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#161b22;border:1px solid #30363d;border-radius:14px;padding:36px;width:100%;max-width:440px}
h1{font-size:1.2rem;font-weight:700;margin-bottom:4px}
.sub{color:#8b949e;font-size:.87rem;margin-bottom:24px}
.field{margin-bottom:14px}
label{display:block;font-size:.83rem;color:#8b949e;margin-bottom:5px;font-weight:600}
.input{width:100%;background:#0e1117;border:1px solid #30363d;border-radius:8px;padding:10px 14px;color:#e6edf3;font-size:.92rem}
.input:focus{outline:none;border-color:#2f81f7}
.avatar-wrap{display:flex;align-items:center;gap:14px;margin-bottom:20px}
.avatar-preview{width:72px;height:72px;border-radius:50%;object-fit:cover;background:#30363d;border:2px solid #30363d}
.btn-primary{width:100%;background:#2f81f7;color:#fff;border:none;border-radius:8px;padding:12px;font-size:.95rem;font-weight:700;cursor:pointer}
.err{background:rgba(248,81,73,.1);border:1px solid #f85149;border-radius:8px;padding:10px;color:#f85149;font-size:.85rem;margin-bottom:14px}
</style>
</head>
<body>
<div class="card">
  <h1>🎉 أكمل إعداد حسابك</h1>
  <p class="sub">مرحباً <?= sc_h($pending['name']) ?>! اختر اسم مستخدم فريداً</p>

  <?php if ($error): ?><div class="err"><?= sc_h($error) ?></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <div class="avatar-wrap">
      <img src="/social/assets/default-avatar.svg" class="avatar-preview" id="avatar-preview" alt="">
      <div>
        <label style="display:block;margin-bottom:6px">الصورة الشخصية</label>
        <input type="file" name="avatar" accept="image/*" onchange="previewAvatar(this)">
        <div style="font-size:.75rem;color:#8b949e;margin-top:4px">اختياري — حتى 5 MB</div>
      </div>
    </div>
    <div class="field">
      <label>اسم المستخدم *</label>
      <input class="input" type="text" name="username" placeholder="ahmed_2025"
             pattern="[a-zA-Z0-9_]+" minlength="3" maxlength="30" required
             value="<?= sc_h(preg_replace('/[^a-zA-Z0-9_]/', '', strtolower(explode(' ', $pending['name'])[0]))) ?>">
    </div>
    <div class="field">
      <label>الاسم الظاهر *</label>
      <input class="input" type="text" name="display_name" required maxlength="60"
             value="<?= sc_h($pending['name']) ?>">
    </div>
    <div class="field">
      <label>نبذة عنك (اختياري)</label>
      <textarea class="input" name="bio" rows="2" maxlength="200" placeholder="اكتب شيئاً عن نفسك..."></textarea>
    </div>
    <button class="btn-primary" type="submit">إنشاء الحساب</button>
  </form>
</div>
<script>
function previewAvatar(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => document.getElementById('avatar-preview').src = e.target.result;
    reader.readAsDataURL(input.files[0]);
  }
}
</script>
</body>
</html>
