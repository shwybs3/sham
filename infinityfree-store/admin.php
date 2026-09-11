<?php
require __DIR__ . '/config.php';

/* ── تسجيل الدخول / الخروج ── */
if (isset($_GET['logout'])) { unset($_SESSION['ifs_admin']); header('Location: admin.php'); exit; }

if (!is_admin()) {
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
        $u = trim((string)($_POST['u'] ?? ''));
        $p = (string)($_POST['p'] ?? '');
        if ($u === setting('admin_user') && password_verify($p, setting('admin_pass_hash'))) {
            $_SESSION['ifs_admin'] = true;
            header('Location: admin.php');
            exit;
        }
        $err = 'بيانات الدخول غير صحيحة.';
    }
    ?><!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>تسجيل الدخول | لوحة الإدارة</title>
    <style>
    body{margin:0;font-family:Tahoma,Arial,sans-serif;background:#0b1120;color:#e5edf7;padding:60px 16px;display:flex;justify-content:center}
    .box{max-width:340px;width:100%;background:#141c30;border:1px solid #243149;border-radius:14px;padding:26px}
    h1{font-size:17px;margin:0 0 18px;text-align:center}
    label{display:block;font-size:12.5px;color:#8ea0bd;margin:12px 0 4px}
    input{width:100%;box-sizing:border-box;padding:10px;border-radius:8px;border:1px solid #243149;background:#0b1120;color:#e5edf7}
    button{width:100%;margin-top:18px;padding:11px;border:0;border-radius:9px;background:#22c55e;color:#052e16;font-weight:bold;cursor:pointer}
    .err{background:#450a0a;border:1px solid #991b1b;color:#fecaca;padding:9px;border-radius:8px;font-size:12.5px;margin-bottom:10px}
    </style></head><body><div class="box">
    <h1>🔐 لوحة الإدارة</h1>
    <?php if ($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <label>اسم المستخدم</label><input name="u" required autofocus>
      <label>كلمة المرور</label><input name="p" type="password" required>
      <button type="submit">دخول</button>
    </form>
    </div></body></html><?php
    exit;
}

/* ── إجراءات (POST) ── */
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'save_settings') {
        foreach (['site_title', 'site_desc', 'contact', 'nowpayments_api_key', 'nowpayments_ipn_secret', 'openrouter_api_key'] as $k) {
            if (isset($_POST[$k])) set_setting($k, trim((string)$_POST[$k]));
        }
        $flash = 'تم حفظ الإعدادات.';
    } elseif ($action === 'add_package') {
        $pdo->prepare("INSERT INTO packages (category, name, description, price_usd, sort_order) VALUES (?,?,?,?,?)")
            ->execute([trim((string)$_POST['category']), trim((string)$_POST['name']), trim((string)$_POST['description']), (float)$_POST['price_usd'], (int)($_POST['sort_order'] ?? 0)]);
        $flash = 'تمت إضافة الباقة.';
    } elseif ($action === 'edit_package') {
        $pdo->prepare("UPDATE packages SET category=?, name=?, description=?, price_usd=?, sort_order=? WHERE id=?")
            ->execute([trim((string)$_POST['category']), trim((string)$_POST['name']), trim((string)$_POST['description']), (float)$_POST['price_usd'], (int)($_POST['sort_order'] ?? 0), (int)$_POST['id']]);
        $flash = 'تم تحديث الباقة.';
    } elseif ($action === 'toggle_package') {
        $pdo->prepare("UPDATE packages SET active = 1 - active WHERE id = ?")->execute([(int)$_POST['id']]);
    } elseif ($action === 'delete_package') {
        $pdo->prepare("DELETE FROM packages WHERE id = ?")->execute([(int)$_POST['id']]);
        $flash = 'تم حذف الباقة.';
    } elseif ($action === 'fulfill_order') {
        $pdo->prepare("UPDATE orders SET fulfilled = 1 WHERE id = ?")->execute([(int)$_POST['id']]);
    }
}

$tab = $_GET['tab'] ?? 'packages';
$packages = $pdo->query("SELECT * FROM packages ORDER BY category, sort_order, id")->fetchAll();
$orders = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 100")->fetchAll();
$pendingCount = 0;
foreach ($orders as $o) if (in_array($o['status'], ['finished', 'confirmed'], true) && !$o['fulfilled']) $pendingCount++;
?><!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>لوحة الإدارة | <?= e(setting('site_title')) ?></title>
<style>
body{margin:0;font-family:Tahoma,Arial,sans-serif;background:#0b1120;color:#e5edf7}
nav{background:#141c30;border-bottom:1px solid #243149;padding:0 16px;display:flex;gap:4px;align-items:center;overflow-x:auto}
nav a{display:block;padding:14px 14px;color:#8ea0bd;text-decoration:none;font-size:13.5px;white-space:nowrap;border-bottom:2px solid transparent}
nav a.on{color:#fff;border-color:#22c55e}
nav .sp{flex:1}
main{max-width:980px;margin:0 auto;padding:22px 16px 60px}
h1{font-size:18px}
table{width:100%;border-collapse:collapse;font-size:13px;margin-top:14px}
th,td{padding:9px 8px;border-bottom:1px solid #243149;text-align:right;vertical-align:top}
th{color:#8ea0bd;font-weight:normal;font-size:12px}
.badge{font-size:11px;padding:2px 8px;border-radius:20px}
.badge.pending{background:#3f2d0a;color:#fbbf24}
.badge.paid{background:#052e16;color:#4ade80}
form.inline{display:inline}
input,select,textarea{width:100%;box-sizing:border-box;padding:7px 9px;border-radius:7px;border:1px solid #243149;background:#0f1a30;color:#e5edf7;font-size:12.5px;font-family:inherit}
.card{background:#141c30;border:1px solid #243149;border-radius:12px;padding:16px;margin-top:16px}
.grid2{display:grid;grid-template-columns:1fr 1fr 1fr auto auto;gap:8px;align-items:end}
button.btn{padding:8px 12px;border:0;border-radius:7px;background:#22c55e;color:#052e16;font-weight:bold;cursor:pointer;font-size:12.5px}
button.btn.gray{background:#243149;color:#e5edf7}
button.btn.red{background:#7f1d1d;color:#fecaca}
.flash{background:#052e16;border:1px solid #16a34a;color:#bbf7d0;padding:9px 12px;border-radius:8px;font-size:13px;margin-bottom:14px}
label{display:block;font-size:12px;color:#8ea0bd;margin:10px 0 4px}
small.hint{color:#8ea0bd}
</style></head><body>
<nav>
  <a href="?tab=packages" class="<?= $tab === 'packages' ? 'on' : '' ?>">الباقات</a>
  <a href="?tab=orders" class="<?= $tab === 'orders' ? 'on' : '' ?>">الطلبات <?= $pendingCount ? "($pendingCount)" : '' ?></a>
  <a href="?tab=settings" class="<?= $tab === 'settings' ? 'on' : '' ?>">الإعدادات والمفاتيح</a>
  <span class="sp"></span>
  <a href="index.php" target="_blank">👁 عرض المتجر</a>
  <a href="?logout=1">خروج</a>
</nav>
<main>
<?php if ($flash): ?><div class="flash"><?= e($flash) ?></div><?php endif; ?>

<?php if ($tab === 'packages'): ?>
  <h1>📦 الباقات</h1>
  <div class="card">
    <form method="post">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" value="add_package">
      <div class="grid2">
        <div><label>الفئة</label><input name="category" required placeholder="انستقرام"></div>
        <div><label>الاسم</label><input name="name" required></div>
        <div><label>السعر $</label><input name="price_usd" type="number" step="0.01" required></div>
        <div><label>الترتيب</label><input name="sort_order" type="number" value="0"></div>
        <button class="btn" type="submit">إضافة</button>
      </div>
      <label>الوصف</label><input name="description">
    </form>
  </div>
  <table>
    <tr><th>الفئة</th><th>الاسم</th><th>الوصف</th><th>السعر</th><th>الحالة</th><th></th></tr>
    <?php foreach ($packages as $p): ?>
    <tr>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="edit_package">
        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
        <td><input name="category" value="<?= e($p['category']) ?>"></td>
        <td><input name="name" value="<?= e($p['name']) ?>"></td>
        <td><input name="description" value="<?= e($p['description']) ?>"></td>
        <td style="width:90px"><input name="price_usd" type="number" step="0.01" value="<?= e($p['price_usd']) ?>"><input type="hidden" name="sort_order" value="<?= (int)$p['sort_order'] ?>"></td>
        <td><?= $p['active'] ? '<span class="badge paid">مفعّلة</span>' : '<span class="badge pending">معطّلة</span>' ?></td>
        <td style="white-space:nowrap">
          <button class="btn gray" type="submit">حفظ</button>
      </form>
      <form class="inline" method="post"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="toggle_package"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn gray" type="submit"><?= $p['active'] ? 'تعطيل' : 'تفعيل' ?></button></form>
      <form class="inline" method="post" onsubmit="return confirm('حذف نهائي؟')"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="delete_package"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn red" type="submit">حذف</button></form>
        </td>
    </tr>
    <?php endforeach; ?>
  </table>

<?php elseif ($tab === 'orders'): ?>
  <h1>🧾 الطلبات</h1>
  <table>
    <tr><th>#</th><th>الباقة</th><th>الهدف</th><th>التواصل</th><th>السعر</th><th>الحالة</th><th>تسليم</th></tr>
    <?php foreach ($orders as $o): $paid = in_array($o['status'], ['finished', 'confirmed'], true); ?>
    <tr>
      <td><?= (int)$o['id'] ?></td>
      <td><?= e($o['package_name']) ?> × <?= (int)$o['quantity'] ?></td>
      <td style="max-width:160px;word-break:break-all"><?= e($o['target_info']) ?></td>
      <td><?= e($o['contact']) ?></td>
      <td>$<?= number_format((float)$o['price_usd'], 2) ?></td>
      <td><span class="badge <?= $paid ? 'paid' : 'pending' ?>"><?= e($o['status'] ?: 'pending') ?></span></td>
      <td>
        <?php if (!$paid): ?>—
        <?php elseif ($o['fulfilled']): ?>✅
        <?php else: ?>
          <form method="post"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="fulfill_order"><input type="hidden" name="id" value="<?= (int)$o['id'] ?>"><button class="btn" type="submit">تم التنفيذ</button></form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$orders): ?><tr><td colspan="7">لا توجد طلبات بعد.</td></tr><?php endif; ?>
  </table>

<?php else: /* settings */ ?>
  <h1>⚙️ الإعدادات والمفاتيح</h1>
  <form method="post" class="card">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="action" value="save_settings">

    <label>اسم المتجر</label>
    <input name="site_title" value="<?= e(setting('site_title')) ?>">
    <label>وصف SEO</label>
    <textarea name="site_desc" rows="2"><?= e(setting('site_desc')) ?></textarea>
    <label>معلومات التواصل (تظهر أسفل الصفحة)</label>
    <input name="contact" value="<?= e(setting('contact')) ?>" placeholder="تيليجرام: @yourname">

    <hr style="border-color:#243149;margin:18px 0">
    <label>مفتاح NOWPayments API <small class="hint">— من nowpayments.io → Store settings → API keys</small></label>
    <input name="nowpayments_api_key" value="<?= e(setting('nowpayments_api_key')) ?>" style="direction:ltr;text-align:left">
    <label>مفتاح NOWPayments IPN Secret <small class="hint">— لتوثيق إشعارات الدفع</small></label>
    <input name="nowpayments_ipn_secret" value="<?= e(setting('nowpayments_ipn_secret')) ?>" style="direction:ltr;text-align:left">
    <p><small class="hint">رابط الإشعار (IPN callback) الذي تضعه في NOWPayments: <code><?= e(site_url('webhook.php')) ?></code></small></p>

    <hr style="border-color:#243149;margin:18px 0">
    <label>مفتاح OpenRouter API (مجاني) <small class="hint">— من openrouter.ai → Keys</small></label>
    <input name="openrouter_api_key" value="<?= e(setting('openrouter_api_key')) ?>" style="direction:ltr;text-align:left">

    <button class="btn" style="margin-top:18px" type="submit">حفظ الإعدادات</button>
  </form>
<?php endif; ?>
</main>
</body></html>
