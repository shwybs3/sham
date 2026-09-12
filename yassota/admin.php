<?php
require __DIR__ . '/config.php';
$me = require_admin();
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    $a = $_POST['do'] ?? '';
    if ($a === 'settings') {
        foreach (['site_name','site_desc','site_keywords','site_url','og_image','google_client_id','google_verification','twitter_handle','lang','apk_version','apk_size','apk_sha256','apk_url','play_url','apk_min_android'] as $k)
            if (isset($_POST[$k])) set_setting($k, trim((string)$_POST[$k]));
        $flash = 'تم حفظ الإعدادات.';
    } elseif ($a === 'verify_user') { $pdo->prepare("UPDATE users SET verified=1-verified WHERE id=?")->execute([(int)$_POST['id']]); }
    elseif ($a === 'ban_user') { $pdo->prepare("UPDATE users SET banned=1-banned WHERE id=? AND is_admin=0")->execute([(int)$_POST['id']]); }
    elseif ($a === 'del_post') { $pdo->prepare("DELETE FROM posts WHERE id=?")->execute([(int)$_POST['id']]); $flash = 'حُذف المنشور.'; }
    elseif ($a === 'resolve_report') { $pdo->prepare("UPDATE reports SET status='closed' WHERE id=?")->execute([(int)$_POST['id']]); }
    elseif ($a === 'add_cat') { try { $pdo->prepare("INSERT INTO categories (slug,name,icon,description,sort_order) VALUES (?,?,?,?,?)")->execute([ya_slugify($_POST['name']), trim($_POST['name']), trim($_POST['icon'] ?: 'grid'), trim($_POST['description'] ?? ''), (int)($_POST['sort_order'] ?? 0)]); $flash = 'أُضيف القسم.'; } catch (Throwable $e) { $flash = 'القسم موجود.'; } }
    elseif ($a === 'del_cat') { $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([(int)$_POST['id']]); }
}

$tab = $_GET['tab'] ?? 'dash';
$stat = fn($q) => (int)$pdo->query($q)->fetch()['c'];
layout_top(['title' => 'الإدارة | ' . setting('site_name', 'YASSOTA'), 'noindex' => true, 'active' => 'admin']);
?>
<div class="phead"><div><h1><?= icon('shield',24) ?> لوحة الإدارة</h1><p class="sub">إدارة المنصة والمحتوى والإعدادات</p></div></div>
<div class="chips" style="flex-wrap:wrap">
  <?php foreach (['dash'=>'لوحة','users'=>'المستخدمون','posts'=>'المنشورات','reports'=>'البلاغات','cats'=>'الأقسام','seo'=>'SEO والإعدادات'] as $k=>$v): ?>
    <a class="chip<?= $tab===$k?' on':'' ?>" href="<?= e(url('admin?tab='.$k)) ?>"><?= e($v) ?></a>
  <?php endforeach; ?>
</div>
<?php if ($flash): ?><div class="alert ok"><?= e($flash) ?></div><?php endif; ?>

<?php if ($tab === 'dash'):
    $cards = [
        ['المستخدمون', $stat("SELECT COUNT(*) c FROM users"), 'user'],
        ['المنشورات', $stat("SELECT COUNT(*) c FROM posts"), 'grid'],
        ['التعليقات', $stat("SELECT COUNT(*) c FROM comments"), 'comment'],
        ['الإعجابات', $stat("SELECT COUNT(*) c FROM post_likes"), 'heart'],
        ['المتابعات', $stat("SELECT COUNT(*) c FROM follows"), 'user'],
        ['بلاغات مفتوحة', $stat("SELECT COUNT(*) c FROM reports WHERE status='open'"), 'flag'],
    ];
?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px">
    <?php foreach ($cards as $c): ?>
    <div class="card" style="padding:16px"><div style="color:var(--brand)"><?= icon($c[2],22) ?></div><div style="font-family:var(--fd);font-size:26px;font-weight:800;margin-top:6px"><?= num_fmt($c[1]) ?></div><div style="color:var(--muted);font-size:13px"><?= e($c[0]) ?></div></div>
    <?php endforeach; ?>
  </div>

<?php elseif ($tab === 'users'):
    $users = $pdo->query("SELECT * FROM users ORDER BY id DESC LIMIT 100")->fetchAll(); ?>
  <div class="card" style="padding:8px 14px">
  <?php foreach ($users as $u): ?>
    <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--line-2)">
      <?= avatar_html($u,38) ?>
      <a href="<?= e(user_url($u['username'])) ?>" style="flex:1;min-width:0"><b><?= e($u['name'] ?: $u['username']) ?></b><?= verified($u) ?><div style="color:var(--faint);font-size:12px">@<?= e($u['username']) ?> · <?= e($u['email']) ?></div></a>
      <form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="do" value="verify_user"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button class="btn btn-ghost" style="padding:6px 10px"><?= (int)$u['verified']?'إلغاء توثيق':'توثيق' ?></button></form>
      <?php if ((int)$u['is_admin']===0): ?><form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="do" value="ban_user"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button class="btn <?= (int)$u['banned']?'btn-ghost':'btn-outline' ?>" style="padding:6px 10px;<?= (int)$u['banned']?'':'color:var(--like);border-color:var(--like)' ?>"><?= (int)$u['banned']?'رفع الحظر':'حظر' ?></button></form><?php endif; ?>
    </div>
  <?php endforeach; ?>
  </div>

<?php elseif ($tab === 'posts'):
    $posts = $pdo->query("SELECT p.*, u.username FROM posts p JOIN users u ON u.id=p.user_id ORDER BY p.id DESC LIMIT 100")->fetchAll(); ?>
  <div class="card" style="padding:8px 14px">
  <?php foreach ($posts as $p): ?>
    <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--line-2)">
      <a href="<?= e(url('post/'.$p['slug'])) ?>" style="flex:1;min-width:0"><b style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($p['title']) ?></b><small style="color:var(--faint)">@<?= e($p['username']) ?> · <?= num_fmt($p['views']) ?> مشاهدة · <?= num_fmt($p['likes_count']) ?> إعجاب</small></a>
      <form method="post" onsubmit="return confirm('حذف المنشور نهائياً؟')"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="do" value="del_post"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn btn-outline" style="padding:6px 10px;color:var(--like);border-color:var(--like)"><?= icon('trash',16) ?></button></form>
    </div>
  <?php endforeach; ?>
  </div>

<?php elseif ($tab === 'reports'):
    $reps = $pdo->query("SELECT r.*, u.username reporter FROM reports r JOIN users u ON u.id=r.reporter_id ORDER BY r.status='open' DESC, r.id DESC LIMIT 100")->fetchAll(); ?>
  <div class="card" style="padding:8px 14px">
  <?php if (!$reps): ?><p class="empty">لا بلاغات.</p><?php endif; ?>
  <?php foreach ($reps as $r): ?>
    <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--line-2)">
      <span class="chip"><?= e($r['reason']) ?></span>
      <div style="flex:1"><b><?= e($r['target_type']) ?> #<?= (int)$r['target_id'] ?></b><div style="color:var(--faint);font-size:12px">بلاغ من @<?= e($r['reporter']) ?> · <?= e(time_ago($r['created_at'])) ?> · <?= e($r['status']) ?></div></div>
      <?php if ($r['status']==='open'): ?><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="do" value="resolve_report"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-ghost" style="padding:6px 10px">إغلاق</button></form><?php endif; ?>
    </div>
  <?php endforeach; ?>
  </div>

<?php elseif ($tab === 'cats'):
    $cats = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM posts p WHERE p.category_id=c.id) n FROM categories c ORDER BY sort_order")->fetchAll(); ?>
  <form class="card" style="padding:16px;margin-bottom:14px" method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="do" value="add_cat">
    <div style="display:grid;grid-template-columns:1fr 120px 1fr auto;gap:8px;align-items:end">
      <div><label style="font-size:12px;color:var(--muted)">الاسم</label><input class="input" name="name" required></div>
      <div><label style="font-size:12px;color:var(--muted)">الأيقونة</label><input class="input" name="icon" value="grid"></div>
      <div><label style="font-size:12px;color:var(--muted)">الوصف</label><input class="input" name="description"></div>
      <button class="btn btn-primary">إضافة</button>
    </div>
  </form>
  <div class="card" style="padding:8px 14px">
  <?php foreach ($cats as $c): ?>
    <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--line-2)">
      <span style="color:var(--brand)"><?= icon($c['icon'],20) ?></span>
      <a href="<?= e(category_url($c['slug'])) ?>" style="flex:1"><b><?= e($c['name']) ?></b> <small style="color:var(--faint)">/<?= e($c['slug']) ?> · <?= (int)$c['n'] ?> منشور</small></a>
      <form method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="do" value="del_cat"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn btn-outline" style="padding:6px 10px;color:var(--like);border-color:var(--like)"><?= icon('trash',16) ?></button></form>
    </div>
  <?php endforeach; ?>
  </div>

<?php else: ?>
  <form class="card" style="padding:18px" method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="do" value="settings">
    <h3 style="margin:0 0 4px;font-family:var(--fd)">الهوية و SEO</h3>
    <div class="field"><label>اسم الموقع</label><input class="input" name="site_name" value="<?= e(setting('site_name')) ?>"></div>
    <div class="field"><label>رابط الموقع (بدون / في النهاية)</label><input class="input" name="site_url" value="<?= e(setting('site_url')) ?>" placeholder="https://yassota.com" dir="ltr"></div>
    <div class="field"><label>الوصف الافتراضي (Meta Description)</label><textarea class="input" name="site_desc" rows="2"><?= e(setting('site_desc')) ?></textarea></div>
    <div class="field"><label>الكلمات المفتاحية</label><input class="input" name="site_keywords" value="<?= e(setting('site_keywords')) ?>"></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div class="field"><label>صورة OG الافتراضية (رابط)</label><input class="input" name="og_image" value="<?= e(setting('og_image')) ?>" dir="ltr"></div>
      <div class="field"><label>حساب تويتر/X</label><input class="input" name="twitter_handle" value="<?= e(setting('twitter_handle')) ?>" dir="ltr"></div>
    </div>
    <div class="field"><label>Google Search Console — رمز التحقق</label><input class="input" name="google_verification" value="<?= e(setting('google_verification')) ?>" dir="ltr" placeholder="محتوى وسم google-site-verification"></div>
    <hr style="border:0;border-top:1px solid var(--line);margin:18px 0">
    <h3 style="margin:0 0 4px;font-family:var(--fd)">تسجيل الدخول بجوجل</h3>
    <div class="field"><label>Google Client ID</label><input class="input" name="google_client_id" value="<?= e(setting('google_client_id')) ?>" dir="ltr" placeholder="xxxx.apps.googleusercontent.com"></div>
    <p style="color:var(--faint);font-size:12.5px;margin-top:-6px">أنشئه من Google Cloud Console ← Credentials ← OAuth Client (Web)، وأضف نطاقك في Authorized JavaScript origins. التفاصيل في README.</p>
    <hr style="border:0;border-top:1px solid var(--line);margin:18px 0">
    <h3 style="margin:0 0 4px;font-family:var(--fd)">تطبيق أندرويد (صفحة التحميل)</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div class="field"><label>الإصدار (versionName)</label><input class="input" name="apk_version" value="<?= e(setting('apk_version')) ?>" dir="ltr" placeholder="1.0.0"></div>
      <div class="field"><label>الحجم</label><input class="input" name="apk_size" value="<?= e(setting('apk_size')) ?>" dir="ltr" placeholder="6.4 MB"></div>
    </div>
    <div class="field"><label>SHA-256 لتوقيع الحزمة</label><input class="input" name="apk_sha256" value="<?= e(setting('apk_sha256')) ?>" dir="ltr"></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div class="field"><label>رابط تحميل APK</label><input class="input" name="apk_url" value="<?= e(setting('apk_url')) ?>" dir="ltr"></div>
      <div class="field"><label>رابط Google Play (اختياري)</label><input class="input" name="play_url" value="<?= e(setting('play_url')) ?>" dir="ltr"></div>
    </div>
    <button class="btn btn-primary btn-block" type="submit">حفظ كل الإعدادات</button>
  </form>
<?php endif; ?>
<?php layout_bottom(); ?>
