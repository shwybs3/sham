<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$currentUser = getCurrentSiteUser($pdo);

// Resolve which profile to show
$targetUser = null;
$isOwn      = false;

if (isset($_GET['u']) && $_GET['u'] !== '') {
    $targetUser = getSiteUserByUsername($pdo, $_GET['u']);
    if (!$targetUser) {
        $pageTitle = 'المستخدم غير موجود';
        require __DIR__ . '/includes/page-header.php';
        echo '<main class="page narrow" style="padding-top:60px;text-align:center;color:var(--ink-faint);">'
           . icon('user', 48) . '<h2 style="margin-top:16px;">هذا المستخدم غير موجود</h2>'
           . '<a href="community.php" class="btn btn-ghost" style="margin-top:20px;">العودة للمجموعة</a>'
           . '</main>';
        require __DIR__ . '/includes/page-footer.php';
        exit;
    }
    $isOwn = $currentUser && $currentUser['id'] === $targetUser['id'];
} elseif ($currentUser) {
    $targetUser = $currentUser;
    $isOwn      = true;
} else {
    header('Location: login.php?redirect=profile.php');
    exit;
}

// Handle profile edit (own profile only)
$saveOk    = '';
$saveError = '';
if ($isOwn && $_SERVER['REQUEST_METHOD'] === 'POST' && csrfCheck()) {
    $username = trim($_POST['username'] ?? '');
    $name     = trim($_POST['display_name'] ?? '');
    $bio      = trim(mb_substr($_POST['bio'] ?? '', 0, 300));

    if ($username && !preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $saveError = 'اسم المستخدم يجب أن يكون 3-30 حرفاً (أحرف، أرقام، _)';
    } elseif (!$name) {
        $saveError = 'الاسم مطلوب';
    } else {
        try {
            if ($username) {
                $chk = $pdo->prepare("SELECT id FROM site_users WHERE username=? AND id!=?");
                $chk->execute([$username, $targetUser['id']]);
                if ($chk->fetch()) $saveError = 'اسم المستخدم مستخدم من شخص آخر';
            }
            if (!$saveError) {
                $pdo->prepare("UPDATE site_users SET username=?, display_name=?, bio=? WHERE id=?")
                    ->execute([$username ?: null, $name, $bio, $targetUser['id']]);
                $saveOk = 'تم الحفظ';
                $targetUser = getSiteUser($pdo, $targetUser['id']);
                if ($currentUser['id'] === $targetUser['id']) $currentUser = $targetUser;
            }
        } catch (PDOException $e) {
            $saveError = 'خطأ في الحفظ';
        }
    }
}

$msgCount     = getUserMessageCount($pdo, $targetUser['id']);
$recentPosts  = getUserMessages($pdo, $targetUser['id'], 30);
$handle       = $targetUser['username'] ?? '';
$pageTitle     = $handle ? '@' . $handle : $targetUser['display_name'];

require __DIR__ . '/includes/page-header.php';
?>

<style>
/* ── Profile Hero (Instagram/TikTok style) ── */
.prof-hero{
  position:relative;
  background:linear-gradient(170deg, rgba(34,197,142,.12) 0%, var(--bg) 60%);
  padding:36px 20px 28px;
  text-align:center;
  border-bottom:1px solid var(--line);
}
.prof-ring{
  width:96px; height:96px; border-radius:50%; margin:0 auto 14px;
  padding:3px;
  background:linear-gradient(135deg,#22c58e,#16a97d,#0b6e53);
  display:flex; align-items:center; justify-content:center;
}
.prof-ring-inner{
  width:100%; height:100%; border-radius:50%;
  background:var(--bg);
  display:flex; align-items:center; justify-content:center;
  font-size:36px; font-weight:800; color:var(--brand);
  overflow:hidden;
}
.prof-ring-inner img{ width:100%; height:100%; object-fit:cover; border-radius:50%; }
.prof-display-name{
  font-size:20px; font-weight:800; line-height:1.2; margin-bottom:4px;
}
.prof-username{
  font-size:14px; color:var(--ink-faint); margin-bottom:10px;
}
.prof-bio{
  font-size:14px; color:var(--ink-dim); line-height:1.6;
  max-width:340px; margin:0 auto 16px; white-space:pre-wrap; word-break:break-word;
}
.prof-stats{
  display:flex; justify-content:center; gap:32px; margin-bottom:16px;
}
.prof-stat{ text-align:center; }
.prof-stat-val{ font-size:20px; font-weight:800; color:var(--ink); line-height:1; }
.prof-stat-lbl{ font-size:12px; color:var(--ink-faint); margin-top:3px; }
.prof-actions{ display:flex; gap:10px; justify-content:center; }

/* ── Posts section ── */
.prof-posts{ max-width:600px; margin:0 auto; padding:16px 14px 80px; }
.prof-post-card{
  display:flex; gap:12px; align-items:flex-start;
  padding:14px 0; border-bottom:1px solid var(--line);
}
.prof-post-avatar{
  width:36px; height:36px; border-radius:50%; flex-shrink:0;
  background:linear-gradient(135deg,rgba(34,197,142,.2),rgba(34,197,142,.05));
  display:flex; align-items:center; justify-content:center;
  font-size:14px; font-weight:700; color:var(--brand); overflow:hidden;
}
.prof-post-avatar img{ width:100%; height:100%; object-fit:cover; border-radius:50%; }
.prof-post-body{ flex:1; min-width:0; }
.prof-post-meta{ display:flex; align-items:center; gap:6px; margin-bottom:4px; }
.prof-post-name{ font-size:13px; font-weight:700; color:var(--ink); }
.prof-post-time{ font-size:12px; color:var(--ink-faint); }
.prof-post-text{
  font-size:14px; line-height:1.6; color:var(--ink);
  word-break:break-word; white-space:pre-wrap;
}

/* ── Edit Modal ── */
.modal-backdrop{
  position:fixed; inset:0; z-index:900;
  background:rgba(0,0,0,.7); display:none;
  align-items:center; justify-content:center; padding:20px;
}
.modal-backdrop.open{ display:flex; }
.modal-box{
  background:var(--panel); border:1px solid var(--line);
  border-radius:16px; width:100%; max-width:440px;
  padding:24px; position:relative;
}
.modal-box h2{ font-size:17px; margin:0 0 20px; }
.modal-close{
  position:absolute; top:16px; inset-inline-end:16px;
  background:none; border:none; cursor:pointer;
  color:var(--ink-faint); font-size:20px; line-height:1; padding:4px;
}
.modal-close:hover{ color:var(--ink); }
.modal-field{ margin-bottom:14px; }
.modal-field label{ display:block; font-size:13px; color:var(--ink-dim); margin-bottom:5px; }
.modal-field input,
.modal-field textarea{
  width:100%; box-sizing:border-box; background:var(--surface-2);
  border:1px solid var(--line); border-radius:8px;
  padding:9px 12px; font-size:14px; color:var(--ink);
  font-family:inherit; outline:none;
}
.modal-field input:focus,
.modal-field textarea:focus{ border-color:var(--brand); }
.modal-field textarea{ resize:vertical; min-height:80px; }
.modal-hint{ font-size:11px; color:var(--ink-faint); margin-top:4px; }
</style>

<!-- Profile Hero -->
<div class="prof-hero">
  <div class="prof-ring">
    <div class="prof-ring-inner">
      <?php if ($targetUser['avatar_url']): ?>
      <img src="<?= h($targetUser['avatar_url']) ?>" alt="<?= h($targetUser['display_name']) ?>">
      <?php else: ?>
      <?= h(mb_substr($targetUser['display_name'], 0, 1, 'UTF-8')) ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="prof-display-name"><?= h($targetUser['display_name']) ?></div>
  <?php if ($handle): ?>
  <div class="prof-username">@<?= h($handle) ?></div>
  <?php endif; ?>
  <?php if (!empty($targetUser['bio'])): ?>
  <div class="prof-bio"><?= nl2br(h($targetUser['bio'])) ?></div>
  <?php endif; ?>

  <div class="prof-stats">
    <div class="prof-stat">
      <div class="prof-stat-val"><?= number_format($msgCount) ?></div>
      <div class="prof-stat-lbl">رسالة</div>
    </div>
    <div class="prof-stat">
      <div class="prof-stat-val"><?= date('Y', strtotime($targetUser['created_at'])) ?></div>
      <div class="prof-stat-lbl">عضو منذ</div>
    </div>
  </div>

  <div class="prof-actions">
    <?php if ($isOwn): ?>
    <button class="btn btn-ghost" onclick="openEditModal()"><?= icon('edit', 15) ?> تعديل الملف</button>
    <a href="auth/logout.php" class="btn btn-ghost" style="color:var(--debt);"><?= icon('login', 15) ?> خروج</a>
    <?php else: ?>
    <a href="community.php" class="btn btn-ghost"><?= icon('chat', 15) ?> المجموعة</a>
    <?php endif; ?>
  </div>

  <?php if ($saveOk): ?>
  <div class="notice ok" style="margin-top:14px; max-width:340px; margin-inline:auto;">
    <?= icon('check-circle', 14) ?> <?= h($saveOk) ?>
  </div>
  <?php endif; ?>
  <?php if ($saveError): ?>
  <div class="notice err" style="margin-top:14px; max-width:340px; margin-inline:auto;">
    <?= icon('alert', 14) ?> <?= h($saveError) ?>
  </div>
  <?php endif; ?>
</div>

<!-- Recent Posts -->
<div class="prof-posts">
  <?php if (empty($recentPosts)): ?>
  <div style="text-align:center; padding:50px 0; color:var(--ink-faint);">
    <?= icon('chat', 36) ?>
    <p style="margin-top:12px;">لا توجد رسائل بعد.</p>
  </div>
  <?php else: ?>
  <p style="font-size:13px; color:var(--ink-faint); margin:0 0 12px;">آخر الرسائل</p>
  <?php foreach ($recentPosts as $post):
    $pInitial = mb_substr($targetUser['display_name'], 0, 1, 'UTF-8');
  ?>
  <div class="prof-post-card">
    <div class="prof-post-avatar">
      <?php if ($targetUser['avatar_url']): ?>
      <img src="<?= h($targetUser['avatar_url']) ?>" alt="" loading="lazy">
      <?php else: ?>
      <?= h($pInitial) ?>
      <?php endif; ?>
    </div>
    <div class="prof-post-body">
      <div class="prof-post-meta">
        <span class="prof-post-name"><?= h($targetUser['display_name']) ?></span>
        <span style="color:var(--ink-faint); font-size:11px;">·</span>
        <span class="prof-post-time"><?= date('m/d H:i', strtotime($post['created_at'])) ?></span>
      </div>
      <div class="prof-post-text"><?= nl2br(h($post['message'])) ?></div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php if ($isOwn): ?>
<!-- Edit Modal -->
<div class="modal-backdrop" id="edit-modal" onclick="if(event.target===this)closeEditModal()">
  <div class="modal-box">
    <button class="modal-close" onclick="closeEditModal()" aria-label="إغلاق">✕</button>
    <h2><?= icon('edit', 18) ?> تعديل الملف الشخصي</h2>
    <form method="post" id="edit-form">
      <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
      <div class="modal-field">
        <label>الاسم الكامل</label>
        <input type="text" name="display_name" value="<?= h($targetUser['display_name']) ?>" required maxlength="100">
      </div>
      <div class="modal-field">
        <label>اسم المستخدم</label>
        <input type="text" name="username" value="<?= h($handle) ?>"
               placeholder="مثال: ahmad_2025" maxlength="30"
               pattern="[a-zA-Z0-9_]{3,30}">
        <div class="modal-hint">أحرف إنجليزية وأرقام و _ فقط (3-30 حرف)</div>
      </div>
      <div class="modal-field">
        <label>نبذة (اختياري)</label>
        <textarea name="bio" maxlength="300" placeholder="اكتب نبذة قصيرة..."><?= h($targetUser['bio'] ?? '') ?></textarea>
      </div>
      <div style="display:flex; gap:8px;">
        <button class="btn btn-primary" type="submit"><?= icon('save', 15) ?> حفظ</button>
        <button class="btn btn-ghost" type="button" onclick="closeEditModal()">إلغاء</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditModal(){ document.getElementById('edit-modal').classList.add('open'); document.body.style.overflow='hidden'; }
function closeEditModal(){ document.getElementById('edit-modal').classList.remove('open'); document.body.style.overflow=''; }
<?php if ($saveError): ?>
document.addEventListener('DOMContentLoaded', openEditModal);
<?php endif; ?>
</script>
<?php endif; ?>

<?php require __DIR__ . '/includes/page-footer.php'; ?>
