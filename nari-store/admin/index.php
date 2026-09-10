<?php
define('NARI_ADMIN', true);
require_once __DIR__ . '/auth.php';
nari_require_login();

$settings = nari_read_json(SETTINGS_FILE);
$services = nari_read_json(SERVICES_FILE);
$csrf = nari_csrf_token();

$saved = $_GET['saved'] ?? '';
$error = $_GET['error'] ?? '';

$generatedHash = $_SESSION['nari_generated_hash'] ?? '';
unset($_SESSION['nari_generated_hash']);

function v($val, $default = '') {
    return htmlspecialchars((string)($val ?? $default), ENT_QUOTES, 'UTF-8');
}

$categories = [];
foreach ($services as $svc) {
    $categories[$svc['category']] = true;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>لوحة التحكم | ناري ستور</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="../assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="admin.css">
</head>
<body>

<div class="top-bar">
  <div class="wrap">
    <h1>🔥 لوحة تحكم <span>ناري ستور</span></h1>
    <div style="display:flex; align-items:center; gap:14px;">
      <a href="../index.html" target="_blank" class="btn ghost small">عرض الموقع ↗</a>
      <span style="color:var(--muted); font-size:.85rem;">مرحباً، <?= v($_SESSION['nari_admin_user'] ?? 'المشرف') ?></span>
      <a href="logout.php" class="btn ghost small">تسجيل الخروج</a>
    </div>
  </div>
</div>

<div class="wrap">

  <?php if ($saved === 'settings'): ?>
    <div class="success-box">تم حفظ الإعدادات العامة بنجاح. التغييرات تظهر فوراً على كل صفحات الموقع.</div>
  <?php elseif ($saved === 'services'): ?>
    <div class="success-box">تم حفظ حالة الخدمات بنجاح.</div>
  <?php elseif ($saved === 'password_hash'): ?>
    <div class="success-box">
      تم توليد قيمة التشفير الجديدة. انسخها والصقها مكان <code>ADMIN_PASSWORD_HASH</code> في ملف <code>admin/config.php</code> عبر مدير الملفات في استضافتك، ثم احفظ الملف:
      <br><br>
      <code style="display:block; background:var(--ink); padding:10px; border-radius:6px; word-break:break-all; margin-top:6px;"><?= v($generatedHash) ?></code>
    </div>
  <?php endif; ?>

  <?php if ($error === 'csrf'): ?>
    <div class="error-box">انتهت صلاحية الجلسة، أعد المحاولة.</div>
  <?php elseif ($error === 'phone'): ?>
    <div class="error-box">رقم واتساب غير صالح.</div>
  <?php elseif ($error === 'weak_password'): ?>
    <div class="error-box">كلمة المرور الجديدة قصيرة جداً (٨ أحرف على الأقل).</div>
  <?php endif; ?>

  <!-- الإعدادات العامة -->
  <div class="panel">
    <h2>الإعدادات العامة</h2>
    <p class="hint">هذه الإعدادات تُقرأ تلقائياً من كل صفحات الموقع (data/settings.json) — أي تعديل هنا ينعكس فوراً على الموقع بالكامل دون الحاجة لتعديل ملفات HTML.</p>
    <form method="post" action="save.php">
      <input type="hidden" name="csrf" value="<?= v($csrf) ?>">
      <input type="hidden" name="action" value="save_settings">

      <div class="grid2">
        <div class="field">
          <label>اسم الموقع</label>
          <input type="text" name="site_name" value="<?= v($settings['site_name'] ?? 'ناري ستور') ?>">
        </div>
        <div class="field">
          <label>الشعار الفرعي (Tagline)</label>
          <input type="text" name="tagline" value="<?= v($settings['tagline'] ?? '') ?>">
        </div>
        <div class="field">
          <label>رقم واتساب (بدون + ، مثال: 963994898623)</label>
          <input type="text" name="whatsapp_number" value="<?= v($settings['whatsapp_number'] ?? '') ?>" required>
        </div>
        <div class="field">
          <label>رقم واتساب للعرض (مثال: ‎+963 994 898 623)</label>
          <input type="text" name="whatsapp_display" value="<?= v($settings['whatsapp_display'] ?? '') ?>">
        </div>
        <div class="field">
          <label>رابط انستقرام (اختياري)</label>
          <input type="text" name="instagram_url" value="<?= v($settings['instagram_url'] ?? '') ?>" placeholder="https://instagram.com/...">
        </div>
        <div class="field">
          <label>رابط تيك توك (اختياري)</label>
          <input type="text" name="tiktok_url" value="<?= v($settings['tiktok_url'] ?? '') ?>" placeholder="https://tiktok.com/@...">
        </div>
        <div class="field">
          <label>رابط فيسبوك (اختياري)</label>
          <input type="text" name="facebook_url" value="<?= v($settings['facebook_url'] ?? '') ?>" placeholder="https://facebook.com/...">
        </div>
      </div>

      <hr style="border-color:var(--line); margin:22px 0;">

      <div class="check-row">
        <input type="checkbox" id="announcement_enabled" name="announcement_enabled" <?= !empty($settings['announcement_enabled']) ? 'checked' : '' ?>>
        <label for="announcement_enabled" style="margin:0;">تفعيل شريط إعلان علوي على كل الصفحات</label>
      </div>
      <div class="field">
        <label>نص شريط الإعلان</label>
        <input type="text" name="announcement_text" value="<?= v($settings['announcement_text'] ?? '') ?>">
      </div>

      <hr style="border-color:var(--line); margin:22px 0;">

      <div class="check-row">
        <input type="checkbox" id="maintenance_mode" name="maintenance_mode" <?= !empty($settings['maintenance_mode']) ? 'checked' : '' ?>>
        <label for="maintenance_mode" style="margin:0;">تفعيل وضع الصيانة (يظهر غطاء صيانة فوق كل الصفحات)</label>
      </div>
      <div class="field">
        <label>رسالة الصيانة</label>
        <textarea name="maintenance_message"><?= v($settings['maintenance_message'] ?? '') ?></textarea>
      </div>

      <button type="submit" class="btn" style="width:auto; padding:12px 28px;">حفظ الإعدادات العامة</button>
    </form>
  </div>

  <!-- حالة الخدمات -->
  <div class="panel">
    <h2>حالة الخدمات (<?= count($services) ?> خدمة)</h2>
    <p class="hint">أوقف أي خدمة مؤقتاً دون حذف صفحتها من الموقع — تظهر شارة "متوقفة مؤقتاً" على بطاقتها في الصفحة الرئيسية وصفحة كل الخدمات، ويختفي زر الطلب المباشر منها.</p>
    <form method="post" action="save.php">
      <input type="hidden" name="csrf" value="<?= v($csrf) ?>">
      <input type="hidden" name="action" value="save_services">

      <div style="overflow-x:auto;">
      <table class="svc-table">
        <thead>
          <tr><th>الخدمة</th><th>الفئة</th><th>الحالة</th><th>ملاحظة (تظهر على البطاقة)</th></tr>
        </thead>
        <tbody>
          <?php foreach ($services as $svc): $id = $svc['id']; ?>
          <tr>
            <td>
              <a href="../<?= v($svc['slug']) ?>" target="_blank"><?= v($svc['name']) ?></a>
            </td>
            <td class="cat"><?= v($svc['category']) ?></td>
            <td>
              <label style="display:flex; align-items:center; gap:8px; white-space:nowrap;">
                <input type="checkbox" name="services[<?= v($id) ?>][active]" <?= !empty($svc['active']) ? 'checked' : '' ?>>
                <span class="badge <?= !empty($svc['active']) ? 'on' : 'off' ?>"><?= !empty($svc['active']) ? 'مُفعّلة' : 'متوقفة' ?></span>
              </label>
            </td>
            <td>
              <input type="text" name="services[<?= v($id) ?>][note]" value="<?= v($svc['note'] ?? '') ?>" placeholder="مثال: قريباً">
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>

      <div style="margin-top:20px;">
        <button type="submit" class="btn" style="width:auto; padding:12px 28px;">حفظ حالة الخدمات</button>
      </div>
    </form>
  </div>

  <!-- تغيير كلمة المرور -->
  <div class="panel">
    <h2>تغيير كلمة مرور لوحة التحكم</h2>
    <p class="hint">لأسباب أمنية على الاستضافات المجانية، لا يمكن للسكربت تعديل ملف <code>config.php</code> نفسه تلقائياً. أدخل كلمة المرور الجديدة، وسنولّد لك القيمة المشفرة لتلصقها يدوياً في <code>admin/config.php</code> مكان <code>ADMIN_PASSWORD_HASH</code>.</p>
    <form method="post" action="save.php" class="grid2">
      <input type="hidden" name="csrf" value="<?= v($csrf) ?>">
      <input type="hidden" name="action" value="change_password">
      <div class="field">
        <label>كلمة المرور الجديدة (٨ أحرف على الأقل)</label>
        <input type="password" name="new_password" minlength="8" required>
      </div>
      <div class="field" style="display:flex; align-items:end;">
        <button type="submit" class="btn" style="width:auto; padding:12px 28px;">توليد كلمة المرور المشفّرة</button>
      </div>
    </form>
  </div>

  <!-- روابط سريعة -->
  <div class="panel">
    <h2>روابط سريعة</h2>
    <p class="hint">أدوات مفيدة بعد رفع الموقع على استضافتك.</p>
    <div class="grid3">
      <a class="btn ghost" href="../sitemap.xml" target="_blank">عرض خريطة الموقع (sitemap.xml)</a>
      <a class="btn ghost" href="../robots.txt" target="_blank">عرض ملف robots.txt</a>
      <a class="btn ghost" href="https://search.google.com/search-console" target="_blank">Google Search Console ↗</a>
    </div>
  </div>

</div>

<footer class="admin-foot">لوحة تحكم ناري ستور — للاستخدام الداخلي فقط، غير مفهرسة في محركات البحث.</footer>
</body>
</html>
