<?php
require_once __DIR__ . '/config.php';
requireSiteLogin();

$currentUser = getCurrentSiteUser($pdo);
if (!$currentUser) { header('Location: login.php'); exit; }

$ok    = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['csrf']) && hash_equals(csrfToken(), $_POST['csrf'])) {
    $username = trim($_POST['username'] ?? '');
    $name     = trim($_POST['display_name'] ?? '');
    $bio      = trim(mb_substr($_POST['bio'] ?? '', 0, 300));

    if ($username && !preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $error = 'اسم المستخدم يجب أن يكون 3-30 حرفاً (أحرف، أرقام، _)';
    } elseif (!$name) {
        $error = 'الاسم مطلوب';
    } else {
        try {
            if ($username) {
                $chk = $pdo->prepare("SELECT id FROM site_users WHERE username=? AND id!=?");
                $chk->execute([$username, $currentUser['id']]);
                if ($chk->fetch()) { $error = 'اسم المستخدم مستخدم من شخص آخر'; }
            }
            if (!$error) {
                $pdo->prepare("UPDATE site_users SET username=?, display_name=?, bio=? WHERE id=?")
                    ->execute([$username ?: null, $name, $bio, $currentUser['id']]);
                $ok = 'تم حفظ الملف الشخصي';
                $currentUser = getSiteUser($pdo, $currentUser['id']);
            }
        } catch (PDOException $e) {
            $error = 'خطأ في الحفظ';
        }
    }
}

$pageTitle = 'الملف الشخصي';
require __DIR__ . '/includes/page-header.php';
?>
<main class="page narrow" style="padding-top:28px;">

  <div class="profile-header">
    <div class="profile-avatar">
      <?php if ($currentUser['avatar_url']): ?>
      <img src="<?= h($currentUser['avatar_url']) ?>" alt="الصورة الشخصية">
      <?php else: ?>
      <?= h(mb_substr($currentUser['display_name'], 0, 1, 'UTF-8')) ?>
      <?php endif; ?>
    </div>
    <div class="profile-name"><?= h($currentUser['display_name']) ?></div>
    <?php if ($currentUser['username']): ?>
    <div class="profile-username">@<?= h($currentUser['username']) ?></div>
    <?php endif; ?>
    <div class="profile-joined"><?= icon('calendar', 14) ?> عضو منذ <?= date('Y/m/d', strtotime($currentUser['created_at'])) ?></div>
    <?php if ($currentUser['bio']): ?>
    <p style="color:var(--ink-dim); font-size:14px; margin-top:10px;"><?= nl2br(h($currentUser['bio'])) ?></p>
    <?php endif; ?>
  </div>

  <?php if ($ok): ?><div class="notice ok"><?= icon('check', 16) ?> <?= h($ok) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="notice err"><?= icon('alert', 16) ?> <?= h($error) ?></div><?php endif; ?>

  <div class="panel">
    <h2><?= icon('edit', 18) ?> تعديل الملف الشخصي</h2>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
      <div class="field">
        <label>الاسم الكامل</label>
        <input type="text" name="display_name" value="<?= h($currentUser['display_name']) ?>" required maxlength="100">
      </div>
      <div class="field">
        <label>اسم المستخدم (اختياري)</label>
        <input type="text" name="username" value="<?= h($currentUser['username'] ?? '') ?>"
               placeholder="مثال: ahmad_2025" maxlength="30"
               pattern="[a-zA-Z0-9_]{3,30}">
        <small style="color:var(--ink-faint); font-size:12px;">أحرف إنجليزية وأرقام و _ فقط</small>
      </div>
      <div class="field">
        <label>نبذة عن نفسك (اختياري)</label>
        <textarea name="bio" rows="3" maxlength="300" placeholder="اكتب نبذة قصيرة..."><?= h($currentUser['bio'] ?? '') ?></textarea>
      </div>
      <button class="btn btn-primary btn-block" type="submit"><?= icon('save', 16) ?> حفظ التغييرات</button>
    </form>
  </div>

  <div class="panel">
    <h2><?= icon('shield', 18) ?> معلومات الحساب</h2>
    <table style="width:100%; font-size:14px; border-collapse:collapse;">
      <tr style="border-bottom:1px solid var(--line);">
        <td style="padding:10px 0; color:var(--ink-dim);">البريد الإلكتروني</td>
        <td style="padding:10px 0; font-weight:600;"><?= h($currentUser['email']) ?></td>
      </tr>
      <tr style="border-bottom:1px solid var(--line);">
        <td style="padding:10px 0; color:var(--ink-dim);">تسجيل الدخول عبر</td>
        <td style="padding:10px 0;">Google</td>
      </tr>
      <tr>
        <td style="padding:10px 0; color:var(--ink-dim);">آخر دخول</td>
        <td style="padding:10px 0;"><?= $currentUser['last_seen'] ? date('Y/m/d H:i', strtotime($currentUser['last_seen'])) : 'الآن' ?></td>
      </tr>
    </table>
    <div style="margin-top:18px;">
      <a href="auth/logout.php" class="btn btn-danger"><?= icon('logout', 16) ?> تسجيل الخروج</a>
    </div>
  </div>

  <p style="font-size:12px; color:var(--ink-faint); text-align:center; margin-top:20px;">
    للاطلاع على البيانات التي نجمعها راجع <a href="privacy-policy.php">سياسة الخصوصية</a>.
  </p>
</main>

<?php require __DIR__ . '/includes/page-footer.php'; ?>
