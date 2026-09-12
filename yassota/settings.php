<?php
require __DIR__ . '/config.php';
$me = require_login();
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) $err = 'انتهت صلاحية الجلسة.';
    else {
        $act = $_POST['act'] ?? 'profile';
        if ($act === 'profile') {
            $name = trim($_POST['name'] ?? '');
            $bio = mb_substr(trim($_POST['bio'] ?? ''), 0, 300);
            $website = trim($_POST['website'] ?? '');
            if ($website !== '' && !preg_match('~^https?://~', $website)) $website = 'https://' . $website;
            if ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) $website = '';
            $location = mb_substr(trim($_POST['location'] ?? ''), 0, 100);
            $private = isset($_POST['is_private']) ? 1 : 0;
            $uname = preg_replace('/[^a-z0-9_]/', '', mb_strtolower(trim($_POST['username'] ?? $me['username'])));
            if (strlen($uname) < 3) $uname = $me['username'];
            if ($uname !== $me['username']) {
                $q = $pdo->prepare("SELECT 1 FROM users WHERE username=? AND id<>?"); $q->execute([$uname, $me['id']]);
                if ($q->fetch()) { $err = 'اسم المستخدم محجوز.'; $uname = $me['username']; }
            }
            $avatar = $me['avatar']; $cover = $me['cover'];
            if (!$err && !empty($_FILES['avatar']['name'])) { $u = ya_upload_image($_FILES['avatar'], 'avatars', 400); if ($u['ok']) $avatar = $u['url']; else $err = $u['error']; }
            if (!$err && !empty($_FILES['cover']['name'])) { $u = ya_upload_image($_FILES['cover'], 'covers', 1600); if ($u['ok']) $cover = $u['url']; else $err = $u['error']; }
            if (!$err) {
                $pdo->prepare("UPDATE users SET name=?, bio=?, website=?, location=?, is_private=?, username=?, avatar=?, cover=? WHERE id=?")
                    ->execute([$name ?: $me['username'], $bio, $website, $location, $private, $uname, $avatar, $cover, $me['id']]);
                $msg = 'تم حفظ ملفك الشخصي.'; $me = current_user();
            }
        } elseif ($act === 'password') {
            $cur = $_POST['current'] ?? ''; $new = $_POST['new'] ?? '';
            if ($me['password_hash'] !== '' && !password_verify($cur, $me['password_hash'])) $err = 'كلمة المرور الحالية غير صحيحة.';
            elseif (strlen($new) < 6) $err = 'كلمة المرور الجديدة 6 أحرف على الأقل.';
            else { $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([password_hash($new, PASSWORD_DEFAULT), $me['id']]); $msg = 'تم تغيير كلمة المرور.'; $me = current_user(); }
        }
    }
}
$me = current_user();
layout_top(['title' => 'الإعدادات | ' . setting('site_name', 'YASSOTA'), 'noindex' => true, 'active' => 'profile']);
?>
<div class="phead"><div><h1>الإعدادات</h1><p class="sub">عدّل ملفك الشخصي وحسابك</p></div></div>
<?php if ($msg): ?><div class="alert ok"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert err"><?= e($err) ?></div><?php endif; ?>
<form class="card" style="padding:18px;margin-bottom:16px" method="post" enctype="multipart/form-data">
  <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="act" value="profile">
  <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px">
    <span style="display:inline-grid;place-items:center;overflow:hidden;border-radius:50%;width:72px;height:72px;background:<?= e(avatar_bg($me)) ?>"><?= avatar_html($me,72) ?></span>
    <label class="btn btn-ghost" style="cursor:pointer">تغيير الصورة<input type="file" name="avatar" accept="image/*" hidden></label>
    <label class="btn btn-ghost" style="cursor:pointer">صورة الغلاف<input type="file" name="cover" accept="image/*" hidden></label>
  </div>
  <div class="field"><label>الاسم</label><input class="input" name="name" value="<?= e($me['name']) ?>" maxlength="80"></div>
  <div class="field"><label>اسم المستخدم</label><input class="input" name="username" value="<?= e($me['username']) ?>" pattern="[a-z0-9_]{3,30}" title="حروف إنجليزية صغيرة وأرقام و_"></div>
  <div class="field"><label>نبذة</label><textarea class="input" name="bio" rows="3" maxlength="300"><?= e($me['bio']) ?></textarea></div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
    <div class="field"><label>الموقع الإلكتروني</label><input class="input" name="website" value="<?= e($me['website']) ?>" placeholder="https://…"></div>
    <div class="field"><label>الموقع الجغرافي</label><input class="input" name="location" value="<?= e($me['location']) ?>"></div>
  </div>
  <label style="display:flex;align-items:center;gap:10px;margin:6px 0 16px;cursor:pointer"><input type="checkbox" name="is_private" <?= (int)$me['is_private']===1?'checked':'' ?>> <span>حساب خاص (يوافق المتابعون فقط على رؤية منشوراتك)</span></label>
  <button class="btn btn-primary btn-block" type="submit">حفظ التغييرات</button>
</form>
<form class="card" style="padding:18px;margin-bottom:16px" method="post">
  <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="act" value="password">
  <h3 style="margin:0 0 12px;font-family:var(--fd)">تغيير كلمة المرور</h3>
  <?php if ($me['password_hash'] !== ''): ?><div class="field"><label>كلمة المرور الحالية</label><input class="input" type="password" name="current"></div><?php endif; ?>
  <div class="field"><label>كلمة المرور الجديدة</label><input class="input" type="password" name="new" minlength="6"></div>
  <button class="btn btn-ghost" type="submit">تحديث كلمة المرور</button>
</form>
<a class="btn btn-ghost btn-block" href="<?= e(url('logout')) ?>"><?= icon('logout',18) ?> تسجيل الخروج</a>
<?php layout_bottom(); ?>
