<?php
require_once __DIR__.'/config.php';

// ===== AUTH =====
$loginErr = '';
if (isset($_POST['do_login'])) {
    if (!csrfCheck()) {
        $loginErr = t('انتهت صلاحية الجلسة، أعد تحميل الصفحة','Session expired, reload the page');
    } elseif (rateLimited('login')) {
        $loginErr = t('محاولات كثيرة جداً، حاول لاحقاً','Too many attempts, try again later');
    } elseif (!verifyCaptcha()) {
        $loginErr = t('يرجى إتمام التحقق الأمني (CAPTCHA)','Please complete the security check (CAPTCHA)');
    } elseif (verifyAdminLogin($_POST['u'] ?? '', $_POST['p'] ?? '')) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        header('Location: admin.php'); exit;
    } else {
        $loginErr = t('بيانات خاطئة','Invalid credentials');
    }
}
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: admin.php'); exit;
}

// ===== LOGIN PAGE =====
if (!isAdmin()): ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Admin Login — DarkStore</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>body{font-family:'Cairo',sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:var(--bg);}
.login-box{background:var(--bg3);border:1px solid var(--cyan);border-radius:20px;padding:40px 36px;max-width:400px;width:100%;box-shadow:0 0 60px rgba(0,240,255,.1);}
</style>
</head>
<body>
<div class="login-box">
  <div style="text-align:center;margin-bottom:28px;">
    <svg width="60" height="60" viewBox="0 0 60 60" fill="none" style="display:block;margin:0 auto 12px;">
      <circle cx="30" cy="30" r="30" fill="rgba(0,240,255,0.08)"/>
      <path d="M30 16C24.477 16 20 20.477 20 26V30H18V44H42V30H40V26C40 20.477 35.523 16 30 16ZM30 20C33.314 20 36 22.686 36 26V30H24V26C24 22.686 26.686 20 30 20ZM30 34C31.657 34 33 35.343 33 37C33 38.657 31.657 40 30 40C28.343 40 27 38.657 27 37C27 35.343 28.343 34 30 34Z" fill="#00f0ff"/>
    </svg>
    <div class="logo">Admin Panel</div>
  </div>
  <?php if(!adminExists()): ?>
  <div class="alert alert-warning">
    <i class="fa-solid fa-triangle-exclamation"></i>
    <?= t('لا يوجد حساب أدمن بعد. شغّل install/create-admin.php مرة واحدة من المتصفح ثم احذفه فوراً.','No admin account yet. Run install/create-admin.php once from your browser, then delete it immediately.') ?>
  </div>
  <?php endif; ?>
  <?php if($loginErr): ?><div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i> <?= clean($loginErr) ?></div><?php endif; ?>
  <form method="POST" class="admin-form">
    <?php csrfField(); ?>
    <div class="form-group">
      <label>اسم المستخدم</label>
      <input type="text" name="u" required autofocus autocomplete="username">
    </div>
    <div class="form-group">
      <label>كلمة المرور</label>
      <input type="password" name="p" required autocomplete="current-password" placeholder="••••••••">
    </div>
    <?php renderCaptcha(); ?>
    <button type="submit" name="do_login" class="btn btn-primary w-full" style="justify-content:center;margin-top:8px;">
      <i class="fa-solid fa-right-to-bracket"></i> دخول
    </button>
  </form>
  <div style="text-align:center;margin-top:18px;"><a href="index.php" style="font-size:13px;color:var(--dim);">← الرئيسية</a></div>
</div>
</body></html>
<?php exit; endif;

// ===== ADMIN ACTIONS (كل عملية تعديل تتحقق من CSRF أولاً) =====
$action  = $_GET['action'] ?? 'dashboard';
$message = '';
$msgType = 'success';
$isPost  = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($isPost && !csrfCheck()) {
    $message = t('انتهت صلاحية الجلسة، أعد المحاولة','Session expired, please retry');
    $msgType = 'error';
} else {

if ($isPost && isset($_POST['save_product'])) {
    $id   = (int)($_POST['prod_id']??0);
    $data = [
        clean($_POST['name_ar']??''), clean($_POST['name_en']??''),
        (float)($_POST['price']??0), clean($_POST['category']??''),
        clean($_POST['icon']??'fa-cube'), clean($_POST['color']??'#00f0ff'),
        clean($_POST['duration_ar']??'مدى الحياة'), clean($_POST['duration_en']??'Lifetime'),
        (float)($_POST['rating']??5), (int)($_POST['sales']??0),
        clean($_POST['badge_ar']??''), clean($_POST['badge_en']??''),
        clean($_POST['badge_color']??'#00f0ff'),
        clean($_POST['short_ar']??''), clean($_POST['short_en']??''),
        clean($_POST['long_ar']??''), clean($_POST['long_en']??''),
        str_replace("\r","",clean($_POST['features_ar']??'')),
        str_replace("\r","",clean($_POST['features_en']??'')),
        clean($_POST['delivery_ar']??'تسليم فوري'), clean($_POST['delivery_en']??'Instant delivery'),
        clean($_POST['download_url']??''),
        isset($_POST['featured'])?1:0, isset($_POST['active'])?1:0,
    ];
    if ($id) {
        $data[] = $id;
        db()->prepare("UPDATE products SET name_ar=?,name_en=?,price=?,category=?,icon=?,color=?,
            duration_ar=?,duration_en=?,rating=?,sales=?,badge_ar=?,badge_en=?,badge_color=?,
            short_ar=?,short_en=?,long_ar=?,long_en=?,features_ar=?,features_en=?,
            delivery_ar=?,delivery_en=?,download_url=?,featured=?,active=? WHERE id=?")->execute($data);
        $message = 'تم تحديث المنتج بنجاح';
    } else {
        $sl = slug($data[0]?:$data[1]);
        if (!$sl) $sl = 'product-'.time();
        array_splice($data, 0, 0, [$sl]);
        dbInsertIgnore('products',
            ['slug','name_ar','name_en','price','category','icon','color',
             'duration_ar','duration_en','rating','sales','badge_ar','badge_en','badge_color',
             'short_ar','short_en','long_ar','long_en','features_ar','features_en',
             'delivery_ar','delivery_en','download_url','featured','active'],
            $data
        );
        $message = 'تمت إضافة المنتج بنجاح';
    }
    header('Location: admin.php?action=products&msg='.urlencode($message)); exit;
}

if ($action==='del_product' && isset($_GET['id'])) {
    db()->prepare("DELETE FROM products WHERE id=?")->execute([(int)$_GET['id']]);
    header('Location: admin.php?action=products&msg=تم+الحذف'); exit;
}

if ($action==='update_order' && isset($_GET['id'],$_GET['status'])) {
    $st  = clean($_GET['status']);
    if (in_array($st, ['paid','cancelled','pending'], true)) {
        $upd = $st==='paid' ? "UPDATE orders SET status=?,paid_at=".dbNow()." WHERE id=?" : "UPDATE orders SET status=? WHERE id=?";
        db()->prepare($upd)->execute([$st,(int)$_GET['id']]);
    }
    header('Location: admin.php?action=orders&msg=تم+التحديث'); exit;
}

if ($action==='update_refund' && isset($_GET['id'],$_GET['status'])) {
    $st = clean($_GET['status']);
    if (in_array($st, ['approved','rejected','pending'], true)) {
        db()->prepare("UPDATE refund_requests SET status=? WHERE id=?")->execute([$st,(int)$_GET['id']]);
    }
    header('Location: admin.php?action=refunds&msg=تم+التحديث'); exit;
}

if ($action==='del_message' && isset($_GET['id'])) {
    db()->prepare("DELETE FROM contact_messages WHERE id=?")->execute([(int)$_GET['id']]);
    header('Location: admin.php?action=messages&msg=تم+الحذف'); exit;
}

if ($isPost && isset($_POST['save_settings'])) {
    $keys = ['site_name_ar','site_name_en','tagline_ar','tagline_en','footer_ar','footer_en',
             'wallet_usdt','pay_timeout','telegram','contact_email','privacy_popup','cf_captcha',
             'cf_site_key','cf_secret_key','maintenance',
             'rl_login_max','rl_login_window','rl_checkout_max','rl_checkout_window',
             'rl_contact_max','rl_contact_window'];
    foreach ($keys as $k) {
        if (isset($_POST[$k])) setSetting($k, clean($_POST[$k]));
    }
    // checkboxes لا تُرسل إن لم تُحدَّد
    foreach (['privacy_popup','cf_captcha','maintenance'] as $cb) {
        if (!isset($_POST[$cb])) setSetting($cb, '0');
    }
    $message = 'تم حفظ الإعدادات';
    $action  = 'settings';
}

if ($isPost && isset($_POST['change_password'])) {
    $newPass = $_POST['new_password'] ?? '';
    if (strlen($newPass) < 10) {
        $message = 'كلمة المرور يجب ألا تقل عن 10 أحرف'; $msgType = 'error';
    } else {
        $row = db()->query("SELECT username FROM admins LIMIT 1")->fetch();
        createOrUpdateAdmin($row['username'] ?? 'admin', $newPass);
        $message = 'تم تغيير كلمة المرور بنجاح';
    }
    $action = 'settings';
}

if ($isPost && isset($_POST['save_cat'])) {
    $nar   = clean($_POST['cat_name_ar']??'');
    $nen   = clean($_POST['cat_name_en']??'');
    $cslug = slug($nen?:$nar);
    $icon  = clean($_POST['cat_icon']??'fa-tag');
    $color = clean($_POST['cat_color']??'#00f0ff');
    dbInsertIgnore('categories', ['name_ar','name_en','slug','icon','color'], [$nar,$nen,$cslug,$icon,$color]);
    header('Location: admin.php?action=categories&msg=تمت+الإضافة'); exit;
}

if ($action==='del_cat' && isset($_GET['id'])) {
    db()->prepare("DELETE FROM categories WHERE id=?")->execute([(int)$_GET['id']]);
    header('Location: admin.php?action=categories&msg=تم+الحذف'); exit;
}

} // end csrf-guarded actions

if (isset($_GET['msg'])) { $message = clean($_GET['msg']); }

$orders    = allOrders();
$prods     = allProducts();
$cats      = categoryList();
$refunds   = allRefundRequests();
$messages  = allContactMessages();
$pendingCnt= count(array_filter($orders, fn($o)=>$o['status']==='pending'));
$paidCnt   = count(array_filter($orders, fn($o)=>$o['status']==='paid'));
$revenue   = array_sum(array_map(fn($o)=>$o['status']==='paid'?$o['amount']:0, $orders));
$pendingRefunds = count(array_filter($refunds, fn($r)=>$r['status']==='pending'));

$editProd = null;
if ($action==='edit_product' && isset($_GET['id'])) {
    $s = db()->prepare("SELECT * FROM products WHERE id=?"); $s->execute([(int)$_GET['id']]);
    $editProd = $s->fetch();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>لوحة الإدارة — <?= clean(setting('site_name_ar','DarkStore')) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>body{font-family:'Cairo',sans-serif;}</style>
</head>
<body>
<div class="admin-layout">

<aside class="admin-sidebar">
  <a href="index.php" class="logo"><?= clean(setting('site_name_ar','DarkStore')) ?></a>
  <nav>
    <a href="admin.php" class="<?= $action==='dashboard'?'active':'' ?>"><i class="fa-solid fa-gauge"></i> لوحة التحكم</a>
    <a href="admin.php?action=products" class="<?= in_array($action,['products','add_product','edit_product'])?'active':'' ?>"><i class="fa-solid fa-box"></i> المنتجات</a>
    <a href="admin.php?action=categories" class="<?= $action==='categories'?'active':'' ?>"><i class="fa-solid fa-tags"></i> التصنيفات</a>
    <a href="admin.php?action=orders" class="<?= $action==='orders'?'active':'' ?>"><i class="fa-solid fa-receipt"></i> الطلبات <?php if($pendingCnt): ?><span style="background:var(--red);color:#fff;font-size:11px;padding:2px 7px;border-radius:10px;margin-right:4px;"><?= $pendingCnt ?></span><?php endif; ?></a>
    <a href="admin.php?action=refunds" class="<?= $action==='refunds'?'active':'' ?>"><i class="fa-solid fa-rotate-left"></i> طلبات الاسترداد <?php if($pendingRefunds): ?><span style="background:var(--gold);color:var(--bg);font-size:11px;padding:2px 7px;border-radius:10px;margin-right:4px;"><?= $pendingRefunds ?></span><?php endif; ?></a>
    <a href="admin.php?action=messages" class="<?= $action==='messages'?'active':'' ?>"><i class="fa-solid fa-envelope"></i> الرسائل</a>
    <a href="admin.php?action=settings" class="<?= $action==='settings'?'active':'' ?>"><i class="fa-solid fa-gear"></i> الإعدادات</a>
    <hr style="border-color:var(--border);margin:12px 0;">
    <a href="index.php" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> عرض الموقع</a>
    <a href="admin.php?logout=1" style="color:var(--red)!important;"><i class="fa-solid fa-right-from-bracket"></i> تسجيل الخروج</a>
  </nav>
</aside>

<main class="admin-main">

<?php if ($message): ?>
<div class="admin-alert <?= $msgType ?>"><i class="fa-solid fa-<?= $msgType==='success'?'check-circle':'circle-xmark' ?>"></i> <?= $message ?></div>
<?php endif; ?>

<?php if ($action==='dashboard'): ?>
<?php if (db_mode()==='sqlite' && defined('DB_MODE') && DB_MODE==='mysql'): ?>
<div class="alert alert-warning" style="margin-bottom:18px;">
  <i class="fa-solid fa-triangle-exclamation"></i>
  تعذّر الاتصال بقاعدة MySQL الخارجية — الموقع يعمل الآن على SQLite المحلي مؤقتاً كخطة طوارئ.
  تحقّق من بيانات <code>DB_HOST / DB_NAME / DB_USER / DB_PASS</code> بملف <code>config.php</code> ومن تفعيل Remote MySQL بالـ cPanel.
</div>
<?php elseif (db_mode()==='mysql'): ?>
<div class="alert alert-success" style="margin-bottom:18px;">
  <i class="fa-solid fa-database"></i> متصل حالياً بقاعدة بيانات MySQL خارجية (<?= clean(DB_NAME) ?>@<?= clean(DB_HOST) ?>)
</div>
<?php endif; ?>
<div class="admin-topbar">
  <h1><i class="fa-solid fa-gauge text-cyan"></i> لوحة التحكم</h1>
  <a href="admin.php?action=add_product" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> منتج جديد</a>
</div>
<div class="stat-cards">
  <div class="stat-card">
    <div class="sicon" style="background:rgba(0,240,255,.1);color:var(--cyan);"><i class="fa-solid fa-box"></i></div>
    <div><div class="snum"><?= count($prods) ?></div><div class="slabel">منتج</div></div>
  </div>
  <div class="stat-card">
    <div class="sicon" style="background:rgba(255,184,0,.1);color:var(--gold);"><i class="fa-solid fa-clock"></i></div>
    <div><div class="snum"><?= $pendingCnt ?></div><div class="slabel">طلب معلق</div></div>
  </div>
  <div class="stat-card">
    <div class="sicon" style="background:rgba(0,255,102,.1);color:var(--green);"><i class="fa-solid fa-check-circle"></i></div>
    <div><div class="snum"><?= $paidCnt ?></div><div class="slabel">طلب مدفوع</div></div>
  </div>
  <div class="stat-card">
    <div class="sicon" style="background:rgba(124,58,237,.1);color:var(--purple);"><i class="fa-solid fa-dollar-sign"></i></div>
    <div><div class="snum"><?= number_format($revenue,0) ?></div><div class="slabel">USDT إجمالي</div></div>
  </div>
</div>

<div class="admin-card">
  <h3>آخر الطلبات</h3>
  <div style="overflow-x:auto;">
  <table>
    <thead><tr><th>رقم الطلب</th><th>المنتج</th><th>المبلغ</th><th>الحالة</th><th>التاريخ</th><th>إجراء</th></tr></thead>
    <tbody>
    <?php foreach(array_slice($orders,0,8) as $o): ?>
    <tr>
      <td style="font-family:monospace;font-size:12px;"><?= clean($o['order_id']) ?></td>
      <td><?= clean($o['name_ar']??$o['product_slug']) ?></td>
      <td style="color:var(--cyan);font-weight:700;"><?= $o['amount'] ?> USDT</td>
      <td><span class="badge-status badge-<?= $o['status'] ?>"><?= ['pending'=>'معلق','paid'=>'مدفوع','cancelled'=>'ملغي'][$o['status']]??$o['status'] ?></span></td>
      <td style="font-size:12px;color:var(--dim);"><?= substr($o['created_at'],0,16) ?></td>
      <td>
        <?php if($o['status']==='pending'): ?>
        <a href="admin.php?action=update_order&id=<?= $o['id'] ?>&status=paid" class="btn btn-sm" style="background:rgba(0,255,102,.1);color:var(--green);border:1px solid rgba(0,255,102,.2);" onclick="return confirm('تأكيد الدفع؟')"><i class="fa-solid fa-check"></i></a>
        <a href="admin.php?action=update_order&id=<?= $o['id'] ?>&status=cancelled" class="btn btn-sm btn-danger" onclick="return confirm('إلغاء الطلب؟')"><i class="fa-solid fa-xmark"></i></a>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if(empty($orders)): ?><tr><td colspan="6" style="text-align:center;color:var(--dim);">لا توجد طلبات بعد</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
</div>

<?php elseif ($action==='products'): ?>
<div class="admin-topbar">
  <h1><i class="fa-solid fa-box text-cyan"></i> المنتجات</h1>
  <a href="admin.php?action=add_product" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> منتج جديد</a>
</div>
<div class="admin-card">
  <div style="overflow-x:auto;">
  <table>
    <thead><tr><th>المنتج</th><th>السعر</th><th>التصنيف</th><th>المبيعات</th><th>الحالة</th><th>إجراءات</th></tr></thead>
    <tbody>
    <?php foreach($prods as $p): ?>
    <tr>
      <td>
        <div style="display:flex;align-items:center;gap:10px;">
          <div style="width:36px;height:36px;border-radius:8px;background:<?= clean($p['color']) ?>18;display:flex;align-items:center;justify-content:center;color:<?= clean($p['color']) ?>;font-size:16px;flex-shrink:0;">
            <i class="fa-solid <?= clean($p['icon']) ?>"></i>
          </div>
          <div>
            <div style="font-weight:600;"><?= clean($p['name_ar']) ?></div>
            <div style="font-size:12px;color:var(--dim);"><?= clean($p['slug']) ?></div>
          </div>
        </div>
      </td>
      <td style="color:var(--cyan);font-weight:700;"><?= $p['price'] ?> USDT</td>
      <td><?= clean($p['category']) ?></td>
      <td><?= number_format($p['sales']) ?></td>
      <td><span class="badge-status" style="background:<?= $p['active']?'rgba(0,255,102,.1)':'rgba(255,0,85,.1)' ?>;color:<?= $p['active']?'var(--green)':'var(--red)' ?>;"><?= $p['active']?'نشط':'مخفي' ?></span></td>
      <td style="display:flex;gap:6px;flex-wrap:wrap;">
        <a href="admin.php?action=edit_product&id=<?= $p['id'] ?>" class="btn btn-sm" style="background:rgba(0,240,255,.1);color:var(--cyan);border:1px solid rgba(0,240,255,.2);"><i class="fa-solid fa-pen"></i></a>
        <a href="product.php?slug=<?= urlencode($p['slug']) ?>" target="_blank" class="btn btn-sm" style="background:rgba(255,184,0,.1);color:var(--gold);border:1px solid rgba(255,184,0,.2);"><i class="fa-solid fa-eye"></i></a>
        <a href="admin.php?action=del_product&id=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('حذف المنتج نهائياً؟')"><i class="fa-solid fa-trash"></i></a>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if(empty($prods)): ?><tr><td colspan="6" style="text-align:center;color:var(--dim);">لا توجد منتجات</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
</div>

<?php elseif (in_array($action,['add_product','edit_product'])): ?>
<div class="admin-topbar">
  <h1><i class="fa-solid fa-<?= $editProd?'pen':'plus' ?> text-cyan"></i> <?= $editProd?'تعديل المنتج':'إضافة منتج جديد' ?></h1>
  <a href="admin.php?action=products" class="btn btn-sm btn-outline"><i class="fa-solid fa-arrow-right"></i> رجوع</a>
</div>
<form method="POST" class="admin-form">
  <?php csrfField(); ?>
  <?php if($editProd): ?><input type="hidden" name="prod_id" value="<?= $editProd['id'] ?>"><?php endif; ?>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:22px;align-items:start;">

    <div class="admin-card">
      <h3>معلومات أساسية</h3>
      <div class="form-row">
        <div class="form-group">
          <label>الاسم (عربي) *</label>
          <input type="text" name="name_ar" required value="<?= clean($editProd['name_ar']??'') ?>">
        </div>
        <div class="form-group">
          <label>Name (English) *</label>
          <input type="text" name="name_en" required value="<?= clean($editProd['name_en']??'') ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>السعر (USDT) *</label>
          <input type="number" name="price" required step="0.01" min="0" value="<?= $editProd['price']??'' ?>">
        </div>
        <div class="form-group">
          <label>التصنيف *</label>
          <select name="category">
            <?php foreach($cats as $c): ?>
            <option value="<?= clean($c['name_ar']) ?>" <?= ($editProd['category']??'')===$c['name_ar']?'selected':'' ?>><?= clean($c['name_ar']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>أيقونة Font Awesome</label>
          <input type="text" name="icon" placeholder="fa-cube" value="<?= clean($editProd['icon']??'fa-cube') ?>">
        </div>
        <div class="form-group">
          <label>اللون</label>
          <input type="color" name="color" value="<?= clean($editProd['color']??'#00f0ff') ?>" style="height:44px;padding:4px;">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>المدة (عربي)</label>
          <input type="text" name="duration_ar" value="<?= clean($editProd['duration_ar']??'مدى الحياة') ?>">
        </div>
        <div class="form-group">
          <label>Duration (English)</label>
          <input type="text" name="duration_en" value="<?= clean($editProd['duration_en']??'Lifetime') ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>التقييم</label>
          <input type="number" name="rating" step="0.1" min="0" max="5" value="<?= $editProd['rating']??5 ?>">
        </div>
        <div class="form-group">
          <label>المبيعات</label>
          <input type="number" name="sales" min="0" value="<?= $editProd['sales']??0 ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>الشارة (عربي)</label>
          <input type="text" name="badge_ar" placeholder="الأكثر مبيعاً" value="<?= clean($editProd['badge_ar']??'') ?>">
        </div>
        <div class="form-group">
          <label>Badge (English)</label>
          <input type="text" name="badge_en" placeholder="Best Seller" value="<?= clean($editProd['badge_en']??'') ?>">
        </div>
      </div>
      <div class="form-group">
        <label>لون الشارة</label>
        <input type="color" name="badge_color" value="<?= clean($editProd['badge_color']??'#00f0ff') ?>" style="height:44px;padding:4px;">
      </div>
      <div style="display:flex;gap:20px;margin-top:8px;">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;">
          <input type="checkbox" name="featured" <?= ($editProd['featured']??1)?'checked':'' ?>> مميز (featured)
        </label>
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;">
          <input type="checkbox" name="active" <?= ($editProd['active']??1)?'checked':'' ?>> نشط
        </label>
      </div>
    </div>

    <div>
      <div class="admin-card" style="margin-bottom:18px;">
        <h3>الأوصاف</h3>
        <div class="form-group">
          <label>وصف قصير (عربي) *</label>
          <textarea name="short_ar" rows="2" required><?= clean($editProd['short_ar']??'') ?></textarea>
        </div>
        <div class="form-group">
          <label>Short description (English) *</label>
          <textarea name="short_en" rows="2" required><?= clean($editProd['short_en']??'') ?></textarea>
        </div>
        <div class="form-group">
          <label>وصف تفصيلي (عربي) *</label>
          <textarea name="long_ar" rows="4" required><?= clean($editProd['long_ar']??'') ?></textarea>
        </div>
        <div class="form-group">
          <label>Long description (English) *</label>
          <textarea name="long_en" rows="4" required><?= clean($editProd['long_en']??'') ?></textarea>
        </div>
      </div>

      <div class="admin-card" style="margin-bottom:18px;">
        <h3>المميزات (سطر لكل ميزة)</h3>
        <div class="form-group">
          <label>المميزات (عربي)</label>
          <textarea name="features_ar" rows="5" placeholder="✅ ميزة 1&#10;✅ ميزة 2"><?= clean($editProd['features_ar']??'') ?></textarea>
        </div>
        <div class="form-group">
          <label>Features (English)</label>
          <textarea name="features_en" rows="5" placeholder="✅ Feature 1&#10;✅ Feature 2"><?= clean($editProd['features_en']??'') ?></textarea>
        </div>
      </div>

      <div class="admin-card">
        <h3>التسليم</h3>
        <div class="form-group">
          <label>طريقة التسليم (عربي)</label>
          <input type="text" name="delivery_ar" value="<?= clean($editProd['delivery_ar']??'تسليم فوري عبر البريد الإلكتروني') ?>">
        </div>
        <div class="form-group">
          <label>Delivery method (English)</label>
          <input type="text" name="delivery_en" value="<?= clean($editProd['delivery_en']??'Instant delivery via email') ?>">
        </div>
        <div class="form-group">
          <label>رابط التحميل المباشر (اختياري)</label>
          <input type="url" name="download_url" placeholder="https://drive.google.com/..." value="<?= clean($editProd['download_url']??'') ?>">
          <div style="font-size:12px;color:var(--dim);margin-top:6px;">يُستخدم في صفحة download.php بعد اجتياز الكابتشا — اتركه فارغاً لإرسال الملف يدوياً بالبريد</div>
        </div>
      </div>
    </div>
  </div>

  <div style="margin-top:20px;display:flex;gap:12px;">
    <button type="submit" name="save_product" class="btn btn-primary">
      <i class="fa-solid fa-floppy-disk"></i> <?= $editProd?'حفظ التعديلات':'إضافة المنتج' ?>
    </button>
    <a href="admin.php?action=products" class="btn btn-outline">إلغاء</a>
  </div>
</form>

<?php elseif ($action==='categories'): ?>
<div class="admin-topbar">
  <h1><i class="fa-solid fa-tags text-cyan"></i> التصنيفات</h1>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:22px;align-items:start;">
  <div class="admin-card">
    <h3>إضافة تصنيف جديد</h3>
    <form method="POST" class="admin-form">
      <?php csrfField(); ?>
      <div class="form-group"><label>الاسم (عربي)</label><input type="text" name="cat_name_ar" required></div>
      <div class="form-group"><label>Name (English)</label><input type="text" name="cat_name_en" required></div>
      <div class="form-row">
        <div class="form-group"><label>أيقونة FA</label><input type="text" name="cat_icon" placeholder="fa-tag" value="fa-tag"></div>
        <div class="form-group"><label>اللون</label><input type="color" name="cat_color" value="#00f0ff" style="height:44px;padding:4px;"></div>
      </div>
      <button type="submit" name="save_cat" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> إضافة</button>
    </form>
  </div>
  <div class="admin-card">
    <h3>التصنيفات الحالية</h3>
    <table>
      <thead><tr><th>الاسم</th><th>الأيقونة</th><th>حذف</th></tr></thead>
      <tbody>
      <?php foreach($cats as $c): ?>
      <tr>
        <td><i class="fa-solid <?= clean($c['icon']) ?>" style="color:<?= clean($c['color']) ?>;margin-left:8px;"></i><?= clean($c['name_ar']) ?></td>
        <td style="font-family:monospace;font-size:12px;color:var(--dim);"><?= clean($c['icon']) ?></td>
        <td><a href="admin.php?action=del_cat&id=<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('حذف؟')"><i class="fa-solid fa-trash"></i></a></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php elseif ($action==='orders'): ?>
<div class="admin-topbar">
  <h1><i class="fa-solid fa-receipt text-cyan"></i> الطلبات</h1>
  <div style="font-size:14px;color:var(--dim);">إجمالي: <strong style="color:var(--cyan);"><?= number_format($revenue) ?> USDT</strong></div>
</div>
<div class="admin-card">
  <div style="overflow-x:auto;">
  <table>
    <thead><tr><th>رقم الطلب</th><th>المنتج</th><th>البريد</th><th>المبلغ</th><th>Hash</th><th>الحالة</th><th>التاريخ</th><th>إجراء</th></tr></thead>
    <tbody>
    <?php foreach($orders as $o): ?>
    <tr>
      <td style="font-family:monospace;font-size:11px;"><?= clean($o['order_id']) ?></td>
      <td><?= clean($o['name_ar']??$o['product_slug']) ?></td>
      <td style="font-size:12px;"><?= clean($o['user_email']??'-') ?></td>
      <td style="color:var(--cyan);font-weight:700;"><?= $o['amount'] ?> USDT</td>
      <td style="font-size:11px;font-family:monospace;max-width:120px;overflow:hidden;text-overflow:ellipsis;"><?= clean($o['tx_hash']??'-') ?></td>
      <td><span class="badge-status badge-<?= $o['status'] ?>"><?= ['pending'=>'معلق','paid'=>'مدفوع','cancelled'=>'ملغي'][$o['status']]??$o['status'] ?></span></td>
      <td style="font-size:11px;color:var(--dim);"><?= substr($o['created_at'],0,16) ?></td>
      <td style="display:flex;gap:4px;flex-wrap:wrap;">
        <?php if($o['status']==='pending'): ?>
        <a href="admin.php?action=update_order&id=<?= $o['id'] ?>&status=paid" class="btn btn-sm" style="background:rgba(0,255,102,.1);color:var(--green);border:1px solid rgba(0,255,102,.2);" onclick="return confirm('تأكيد الدفع؟')"><i class="fa-solid fa-check"></i></a>
        <a href="admin.php?action=update_order&id=<?= $o['id'] ?>&status=cancelled" class="btn btn-sm btn-danger" onclick="return confirm('إلغاء؟')"><i class="fa-solid fa-xmark"></i></a>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if(empty($orders)): ?><tr><td colspan="8" style="text-align:center;color:var(--dim);">لا توجد طلبات</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
</div>

<?php elseif ($action==='refunds'): ?>
<div class="admin-topbar">
  <h1><i class="fa-solid fa-rotate-left text-cyan"></i> طلبات الاسترداد</h1>
</div>
<div class="admin-card">
  <div style="overflow-x:auto;">
  <table>
    <thead><tr><th>رقم الطلب</th><th>البريد</th><th>السبب</th><th>التفاصيل</th><th>الحالة</th><th>التاريخ</th><th>إجراء</th></tr></thead>
    <tbody>
    <?php foreach($refunds as $r): ?>
    <tr>
      <td style="font-family:monospace;font-size:12px;"><?= clean($r['order_id']) ?></td>
      <td style="font-size:12px;"><?= clean($r['email']) ?></td>
      <td><?= clean($r['reason']) ?></td>
      <td style="max-width:260px;font-size:12px;color:var(--dim);"><?= nl2br(clean($r['details'])) ?></td>
      <td><span class="badge-status" style="background:<?= $r['status']==='approved'?'rgba(0,255,102,.1)':($r['status']==='rejected'?'rgba(255,0,85,.1)':'rgba(255,184,0,.1)') ?>;color:<?= $r['status']==='approved'?'var(--green)':($r['status']==='rejected'?'var(--red)':'var(--gold)') ?>;"><?= ['pending'=>'معلق','approved'=>'مقبول','rejected'=>'مرفوض'][$r['status']]??$r['status'] ?></span></td>
      <td style="font-size:11px;color:var(--dim);"><?= substr($r['created_at'],0,16) ?></td>
      <td style="display:flex;gap:4px;flex-wrap:wrap;">
        <?php if($r['status']==='pending'): ?>
        <a href="admin.php?action=update_refund&id=<?= $r['id'] ?>&status=approved" class="btn btn-sm" style="background:rgba(0,255,102,.1);color:var(--green);border:1px solid rgba(0,255,102,.2);" onclick="return confirm('قبول الاسترداد؟')"><i class="fa-solid fa-check"></i></a>
        <a href="admin.php?action=update_refund&id=<?= $r['id'] ?>&status=rejected" class="btn btn-sm btn-danger" onclick="return confirm('رفض الاسترداد؟')"><i class="fa-solid fa-xmark"></i></a>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if(empty($refunds)): ?><tr><td colspan="7" style="text-align:center;color:var(--dim);">لا توجد طلبات استرداد</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
</div>

<?php elseif ($action==='messages'): ?>
<div class="admin-topbar">
  <h1><i class="fa-solid fa-envelope text-cyan"></i> رسائل التواصل</h1>
</div>
<div class="admin-card">
  <div style="overflow-x:auto;">
  <table>
    <thead><tr><th>الاسم</th><th>البريد</th><th>الموضوع</th><th>الرسالة</th><th>التاريخ</th><th>حذف</th></tr></thead>
    <tbody>
    <?php foreach($messages as $m): ?>
    <tr>
      <td><?= clean($m['name']) ?></td>
      <td style="font-size:12px;"><?= clean($m['email']) ?></td>
      <td><?= clean($m['subject']??'-') ?></td>
      <td style="max-width:300px;font-size:12px;color:var(--dim);"><?= nl2br(clean($m['message'])) ?></td>
      <td style="font-size:11px;color:var(--dim);"><?= substr($m['created_at'],0,16) ?></td>
      <td><a href="admin.php?action=del_message&id=<?= $m['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('حذف الرسالة؟')"><i class="fa-solid fa-trash"></i></a></td>
    </tr>
    <?php endforeach; ?>
    <?php if(empty($messages)): ?><tr><td colspan="6" style="text-align:center;color:var(--dim);">لا توجد رسائل</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
</div>

<?php elseif ($action==='settings'): ?>
<div class="admin-topbar">
  <h1><i class="fa-solid fa-gear text-cyan"></i> الإعدادات</h1>
</div>
<form method="POST" class="admin-form">
  <?php csrfField(); ?>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:22px;">

    <div class="admin-card">
      <h3>معلومات الموقع</h3>
      <div class="form-group"><label>اسم الموقع (عربي)</label><input type="text" name="site_name_ar" value="<?= clean(setting('site_name_ar')) ?>"></div>
      <div class="form-group"><label>Site Name (English)</label><input type="text" name="site_name_en" value="<?= clean(setting('site_name_en')) ?>"></div>
      <div class="form-group"><label>الوصف (عربي)</label><input type="text" name="tagline_ar" value="<?= clean(setting('tagline_ar')) ?>"></div>
      <div class="form-group"><label>Tagline (English)</label><input type="text" name="tagline_en" value="<?= clean(setting('tagline_en')) ?>"></div>
      <div class="form-group"><label>نص الفوتر (عربي)</label><input type="text" name="footer_ar" value="<?= clean(setting('footer_ar')) ?>"></div>
      <div class="form-group"><label>Footer text (English)</label><input type="text" name="footer_en" value="<?= clean(setting('footer_en')) ?>"></div>
      <div class="form-group"><label>رابط تيليغرام</label><input type="url" name="telegram" value="<?= clean(setting('telegram')) ?>"></div>
      <div class="form-group"><label>بريد التواصل</label><input type="email" name="contact_email" value="<?= clean(setting('contact_email')) ?>"></div>
    </div>

    <div>
      <div class="admin-card" style="margin-bottom:18px;">
        <h3>الدفع</h3>
        <div class="form-group">
          <label>عنوان محفظة USDT</label>
          <input type="text" name="wallet_usdt" value="<?= clean(setting('wallet_usdt')) ?>">
        </div>
        <div class="form-group">
          <label>مهلة الدفع (ثانية)</label>
          <input type="number" name="pay_timeout" min="300" value="<?= clean(setting('pay_timeout','1200')) ?>">
        </div>
      </div>

      <div class="admin-card" style="margin-bottom:18px;">
        <h3>الخصوصية والصيانة</h3>
        <div class="form-group">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
            <input type="checkbox" name="privacy_popup" value="1" <?= setting('privacy_popup')==='1'?'checked':'' ?>>
            تفعيل Popup الموافقة على الشروط عند كل فتح للموقع
          </label>
        </div>
        <div class="form-group">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
            <input type="checkbox" name="maintenance" value="1" <?= setting('maintenance')==='1'?'checked':'' ?>>
            وضع الصيانة
          </label>
        </div>
      </div>

      <div class="admin-card" style="margin-bottom:18px;">
        <h3>Cloudflare Turnstile (CAPTCHA)</h3>
        <div class="form-group">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
            <input type="checkbox" name="cf_captcha" value="1" <?= setting('cf_captcha')==='1'?'checked':'' ?>>
            تفعيل CAPTCHA على تسجيل الدخول، الدفع، والتواصل
          </label>
        </div>
        <div class="form-group"><label>Site Key</label><input type="text" name="cf_site_key" value="<?= clean(setting('cf_site_key')) ?>"></div>
        <div class="form-group"><label>Secret Key</label><input type="text" name="cf_secret_key" value="<?= clean(setting('cf_secret_key')) ?>"></div>
        <p style="font-size:12px;color:var(--dim);">احصل على المفاتيح من <a href="https://dash.cloudflare.com/" target="_blank" rel="noopener">Cloudflare Dashboard</a> &larr; Turnstile</p>
      </div>

      <div class="admin-card">
        <h3><i class="fa-solid fa-shield-halved"></i> الحماية من الفلود والبوتات</h3>
        <p style="font-size:12px;color:var(--dim);margin-bottom:14px;">حد أقصى للمحاولات خلال نافذة زمنية لكل عنوان IP — يطبَّق تلقائياً حتى بدون CAPTCHA.</p>
        <div class="form-row">
          <div class="form-group"><label>حد محاولات الدخول</label><input type="number" name="rl_login_max" min="1" value="<?= clean(setting('rl_login_max','5')) ?>"></div>
          <div class="form-group"><label>نافذة الدخول (ثانية)</label><input type="number" name="rl_login_window" min="60" value="<?= clean(setting('rl_login_window','900')) ?>"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>حد محاولات الدفع</label><input type="number" name="rl_checkout_max" min="1" value="<?= clean(setting('rl_checkout_max','6')) ?>"></div>
          <div class="form-group"><label>نافذة الدفع (ثانية)</label><input type="number" name="rl_checkout_window" min="60" value="<?= clean(setting('rl_checkout_window','600')) ?>"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>حد محاولات التواصل</label><input type="number" name="rl_contact_max" min="1" value="<?= clean(setting('rl_contact_max','4')) ?>"></div>
          <div class="form-group"><label>نافذة التواصل (ثانية)</label><input type="number" name="rl_contact_window" min="60" value="<?= clean(setting('rl_contact_window','600')) ?>"></div>
        </div>
      </div>
    </div>
  </div>

  <div style="margin-top:20px;">
    <button type="submit" name="save_settings" class="btn btn-primary">
      <i class="fa-solid fa-floppy-disk"></i> حفظ الإعدادات
    </button>
  </div>
</form>

<div class="admin-card" style="margin-top:22px;max-width:460px;">
  <h3><i class="fa-solid fa-key"></i> تغيير كلمة مرور الأدمن</h3>
  <form method="POST" class="admin-form">
    <?php csrfField(); ?>
    <div class="form-group">
      <label>كلمة المرور الجديدة (10 أحرف على الأقل)</label>
      <input type="password" name="new_password" minlength="10" required autocomplete="new-password">
    </div>
    <button type="submit" name="change_password" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> تحديث كلمة المرور</button>
  </form>
</div>

<?php endif; ?>
</main>
</div>
</body>
</html>
