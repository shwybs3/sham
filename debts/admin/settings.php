<?php
require_once __DIR__ . '/../config.php';
requireAdminLogin();

$ok    = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck()) {
        $error = 'انتهت صلاحية الجلسة، أعد المحاولة.';
    } else {
        $tab = $_POST['tab'] ?? 'general';

        if ($tab === 'general') {
            setSetting($pdo, 'shop_name',      trim($_POST['shop_name']  ?? 'دفتر الدكان'));
            setSetting($pdo, 'currency',       trim($_POST['currency']   ?? 'SYP'));
            setSetting($pdo, 'banner_text',    trim($_POST['banner_text'] ?? ''));
            setSetting($pdo, 'banner_enabled', isset($_POST['banner_enabled']) ? '1' : '0');
            setSetting($pdo, 'support_enabled',isset($_POST['support_enabled']) ? '1' : '0');
            setSetting($pdo, 'chat_enabled',   isset($_POST['chat_enabled']) ? '1' : '0');
            setSetting($pdo, 'pwa_enabled',    isset($_POST['pwa_enabled']) ? '1' : '0');
            $ok = 'تم حفظ الإعدادات العامة.';
        } elseif ($tab === 'google') {
            setSetting($pdo, 'google_client_id',     trim($_POST['google_client_id']     ?? ''));
            setSetting($pdo, 'google_client_secret', trim($_POST['google_client_secret'] ?? ''));
            $ok = 'تم حفظ إعدادات Google.';
        } elseif ($tab === 'telegram') {
            setSetting($pdo, 'telegram_bot_token',        trim($_POST['telegram_bot_token']        ?? ''));
            setSetting($pdo, 'telegram_chat_id',          trim($_POST['telegram_chat_id']          ?? ''));
            setSetting($pdo, 'backup_key',                trim($_POST['backup_key']                ?? ''));
            setSetting($pdo, 'tg_market_webhook_secret',  trim($_POST['tg_market_webhook_secret']  ?? ''));
            $ok = 'تم حفظ إعدادات تيليجرام.';
        } elseif ($tab === 'seo') {
            setSetting($pdo, 'robots_allow_public',       isset($_POST['robots_allow_public'])       ? '1' : '0');
            setSetting($pdo, 'sitemap_include_customers', isset($_POST['sitemap_include_customers']) ? '1' : '0');
            setSetting($pdo, 'rss_secret',                trim($_POST['rss_secret'] ?? ''));
            $ok = 'تم حفظ إعدادات SEO.';
        } elseif ($tab === 'security') {
            setSetting($pdo, 'recaptcha_site_key',      trim($_POST['recaptcha_site_key']      ?? ''));
            setSetting($pdo, 'recaptcha_secret_key',    trim($_POST['recaptcha_secret_key']    ?? ''));
            setSetting($pdo, 'recaptcha_version',       in_array($_POST['recaptcha_version'] ?? 'v2', ['v2','v3']) ? $_POST['recaptcha_version'] : 'v2');
            setSetting($pdo, 'recaptcha_enabled',       isset($_POST['recaptcha_enabled']) ? '1' : '0');
            setSetting($pdo, 'cf_turnstile_site_key',   trim($_POST['cf_turnstile_site_key']   ?? ''));
            setSetting($pdo, 'cf_turnstile_secret_key', trim($_POST['cf_turnstile_secret_key'] ?? ''));
            setSetting($pdo, 'cf_turnstile_enabled',    isset($_POST['cf_turnstile_enabled'])   ? '1' : '0');
            $ok = 'تم حفظ إعدادات الأمان.';
        } elseif ($tab === 'rates') {
            setSetting($pdo, 'syp_manual_rate',      (string)(float)($_POST['syp_manual_rate'] ?? 0));
            setSetting($pdo, 'rate_alert_threshold', (string)(float)($_POST['rate_alert_threshold'] ?? 3));
            setSetting($pdo, 'rate_alert_enabled',   isset($_POST['rate_alert_enabled']) ? '1' : '0');
            $ok = 'تم حفظ إعدادات أسعار الصرف.';
        }

        $settings = getAllSettings($pdo);
    }
}

$activeTab  = $_GET['tab'] ?? 'general';
$currencies = getCurrencies();
$pageTitle  = 'الإعدادات';
$activeNav  = 'settings';
require __DIR__ . '/includes/admin-header.php';
?>
<style>
.tabs{ display:flex; gap:4px; margin-bottom:24px; flex-wrap:wrap; }
.tab-btn{
  padding:9px 18px; border-radius:999px; border:1.5px solid var(--line); background:transparent; color:var(--ink-dim);
  font-family:var(--body); font-size:13.5px; font-weight:600; cursor:pointer; transition:.15s;
}
.tab-btn.active{ border-color:var(--brand); background:rgba(34,197,142,.1); color:var(--brand); }
.tab-pane{ display:none; }
.tab-pane.active{ display:block; }
.setting-row{ display:flex; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid var(--line); }
.setting-row label{ flex:1; font-size:14px; font-weight:600; }
.setting-row small{ display:block; color:var(--ink-faint); font-size:12px; font-weight:400; }
input[type=checkbox]{ width:20px; height:20px; cursor:pointer; }
.backup-btn{ margin-top:16px; }
.security-note{ font-size:12px; color:var(--ink-faint); margin:8px 0 0; padding:10px; background:var(--surface-2); border-radius:8px; }
</style>

<?php if ($ok): ?><div class="notice ok"><?= icon('check-circle', 16) ?> <?= h($ok) ?></div><?php endif; ?>
<?php if ($error): ?><div class="notice err"><?= icon('alert', 16) ?> <?= h($error) ?></div><?php endif; ?>

<div class="tabs">
  <button class="tab-btn <?= $activeTab==='general'  ?'active':'' ?>" onclick="switchTab('general')">
    <?= icon('settings',15) ?> عام
  </button>
  <button class="tab-btn <?= $activeTab==='google'   ?'active':'' ?>" onclick="switchTab('google')">
    <svg viewBox="0 0 24 24" width="15" height="15" style="vertical-align:middle;margin-inline-end:4px;"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
    Google
  </button>
  <button class="tab-btn <?= $activeTab==='telegram' ?'active':'' ?>" onclick="switchTab('telegram')">
    <?= icon('send',15) ?> تيليجرام
  </button>
  <button class="tab-btn <?= $activeTab==='rates'    ?'active':'' ?>" onclick="switchTab('rates')">
    <?= icon('trending-up',15) ?> أسعار الصرف
  </button>
  <button class="tab-btn <?= $activeTab==='security' ?'active':'' ?>" onclick="switchTab('security')">
    <?= icon('shield',15) ?> الأمان
  </button>
  <button class="tab-btn <?= $activeTab==='seo'      ?'active':'' ?>" onclick="switchTab('seo')">
    <?= icon('globe',15) ?> SEO
  </button>
</div>

<!-- ── عام ── -->
<div class="tab-pane <?= $activeTab==='general'?'active':'' ?>" id="tab-general">
  <div class="panel" style="max-width:560px;">
    <h2><?= icon('settings', 18) ?> الإعدادات العامة</h2>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
      <input type="hidden" name="tab"  value="general">

      <div class="field"><label>اسم المحل</label><input type="text" name="shop_name" value="<?= h($settings['shop_name'] ?? '') ?>" required></div>

      <div class="field">
        <label>العملة</label>
        <select name="currency">
          <?php foreach ($currencies as $code => $info): ?>
          <option value="<?= h($code) ?>" <?= ($settings['currency'] ?? 'SYP') === $code ? 'selected' : '' ?>>
            <?= h($info['symbol']) ?> — <?= h($info['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label>نص البنر المتحرك</label>
        <input type="text" name="banner_text" value="<?= h($settings['banner_text'] ?? '') ?>" placeholder="مثال: أهلاً بكم في دكاننا">
      </div>

      <div class="setting-row">
        <label>تفعيل البنر<small>يظهر شريط متحرك أعلى الصفحة</small></label>
        <input type="checkbox" name="banner_enabled" <?= ($settings['banner_enabled']??'0')==='1'?'checked':'' ?>>
      </div>
      <div class="setting-row">
        <label>نظام الدردشة<small>المجموعة العامة</small></label>
        <input type="checkbox" name="chat_enabled" <?= ($settings['chat_enabled']??'1')==='1'?'checked':'' ?>>
      </div>
      <div class="setting-row">
        <label>زر الدعم<small>الزر العائم للتواصل مع الدعم</small></label>
        <input type="checkbox" name="support_enabled" <?= ($settings['support_enabled']??'1')==='1'?'checked':'' ?>>
      </div>
      <div class="setting-row">
        <label>تطبيق الويب (PWA)<small>السماح بتثبيت التطبيق على الجوال</small></label>
        <input type="checkbox" name="pwa_enabled" <?= ($settings['pwa_enabled']??'1')==='1'?'checked':'' ?>>
      </div>

      <div style="margin-top:20px;">
        <button class="btn btn-primary" type="submit"><?= icon('check', 16) ?> حفظ الإعدادات</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Google ── -->
<div class="tab-pane <?= $activeTab==='google'?'active':'' ?>" id="tab-google">
  <div class="panel" style="max-width:560px;">
    <h2>إعدادات تسجيل الدخول بـ Google</h2>
    <p style="color:var(--ink-dim); font-size:13.5px; margin-bottom:20px;">
      أنشئ مشروعاً في <a href="https://console.developers.google.com" target="_blank" rel="noopener">Google Cloud Console</a>،
      فعّل "Google Identity Services"، ثم أنشئ OAuth 2.0 credentials وانسخ المفاتيح هنا.
    </p>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
      <input type="hidden" name="tab"  value="google">
      <div class="field">
        <label><?= icon('key',14) ?> Client ID</label>
        <input type="text" name="google_client_id" value="<?= h($settings['google_client_id'] ?? '') ?>"
               placeholder="xxxxxxxxxxxx.apps.googleusercontent.com" dir="ltr">
      </div>
      <div class="field">
        <label><?= icon('key',14) ?> Client Secret</label>
        <input type="password" name="google_client_secret" value="<?= h($settings['google_client_secret'] ?? '') ?>"
               placeholder="GOCSPX-..." dir="ltr">
      </div>
      <button class="btn btn-primary" type="submit"><?= icon('check', 16) ?> حفظ</button>
    </form>

    <?php if ($settings['google_client_id'] ?? ''): ?>
    <div class="notice ok" style="margin-top:16px;"><?= icon('check-circle',15) ?> تسجيل Google مفعّل</div>
    <?php else: ?>
    <div class="notice err" style="margin-top:16px;"><?= icon('alert',15) ?> تسجيل Google غير مفعّل</div>
    <?php endif; ?>
  </div>
</div>

<!-- ── تيليجرام ── -->
<div class="tab-pane <?= $activeTab==='telegram'?'active':'' ?>" id="tab-telegram">
  <div class="panel" style="max-width:560px;">
    <h2><?= icon('send',18) ?> إعدادات بوت تيليجرام</h2>
    <p style="color:var(--ink-dim); font-size:13.5px; margin-bottom:20px;">
      أنشئ بوتاً جديداً عبر <strong>@BotFather</strong> وانسخ التوكن هنا. احصل على معرف الدردشة من
      <strong>@userinfobot</strong>.
    </p>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
      <input type="hidden" name="tab"  value="telegram">
      <div class="field">
        <label><?= icon('key',14) ?> توكن البوت</label>
        <input type="password" name="telegram_bot_token" value="<?= h($settings['telegram_bot_token'] ?? '') ?>"
               placeholder="123456789:ABC-..." dir="ltr">
      </div>
      <div class="field">
        <label>معرف الدردشة (Chat ID)</label>
        <input type="text" name="telegram_chat_id" value="<?= h($settings['telegram_chat_id'] ?? '') ?>"
               placeholder="-100123456789 أو معرفك الشخصي" dir="ltr">
      </div>
      <div class="field">
        <label>مفتاح النسخ الاحتياطي التلقائي (API Key)</label>
        <input type="text" name="backup_key" value="<?= h($settings['backup_key'] ?? '') ?>"
               placeholder="كلمة سر للوصول التلقائي" dir="ltr">
        <small class="security-note">
          استخدم هذا المفتاح في Cron Job:<br>
          <code style="direction:ltr; display:block; margin-top:4px;">curl "https://yoursite.com/api/backup.php?key=YOUR_KEY"</code>
        </small>
      </div>
      <div class="field">
        <label><?= icon('zap',14) ?> سر Webhook أسعار السوق (tg_market_webhook_secret)</label>
        <input type="text" name="tg_market_webhook_secret" value="<?= h($settings['tg_market_webhook_secret'] ?? '') ?>"
               placeholder="كلمة سر عشوائية مثلاً: abc123xyz" dir="ltr">
        <small class="security-note">
          يُستخدم للتحقق من مصدر رسائل تيليجرام في Webhook أسعار السوق.<br>
          <a href="market-prices.php" style="color:var(--brand);">إدارة أسعار السوق ←</a>
        </small>
      </div>
      <button class="btn btn-primary" type="submit"><?= icon('check', 16) ?> حفظ</button>
    </form>

    <?php if ($settings['telegram_bot_token'] ?? ''): ?>
    <div style="margin-top:20px;">
      <a href="../api/backup.php" class="btn btn-ghost backup-btn" target="_blank">
        <?= icon('download', 16) ?> إرسال نسخة احتياطية الآن
      </a>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ── أسعار الصرف ── -->
<div class="tab-pane <?= $activeTab==='rates'?'active':'' ?>" id="tab-rates">
  <div class="panel" style="max-width:560px;">
    <h2><?= icon('trending-up',18) ?> إعدادات أسعار الصرف</h2>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
      <input type="hidden" name="tab"  value="rates">

      <div class="field">
        <label>سعر الليرة السورية (سوق السوداء) — يدوي</label>
        <input type="number" name="syp_manual_rate" min="0" step="1"
               value="<?= h($settings['syp_manual_rate'] ?? '0') ?>" dir="ltr"
               placeholder="مثال: 13500">
        <small style="color:var(--ink-faint); font-size:12px;">
          أدخل السعر الحالي للدولار بالليرة السورية. يُعرض في صفحة أسعار الصرف.
        </small>
      </div>

      <div class="field">
        <label>نسبة التغيير لإرسال تنبيه تيليجرام (%)</label>
        <input type="number" name="rate_alert_threshold" min="0.5" max="50" step="0.5"
               value="<?= h($settings['rate_alert_threshold'] ?? '3') ?>" dir="ltr">
        <small style="color:var(--ink-faint); font-size:12px;">
          عند تغير السعر بهذه النسبة أو أكثر يُرسل تنبيه تيليجرام تلقائياً.
        </small>
      </div>

      <div class="setting-row">
        <label>تفعيل تنبيهات تغيير الأسعار<small>يتطلب إعداد بوت تيليجرام أولاً</small></label>
        <input type="checkbox" name="rate_alert_enabled" <?= ($settings['rate_alert_enabled']??'0')==='1'?'checked':'' ?>>
      </div>

      <div style="margin-top:20px; display:flex; gap:12px; flex-wrap:wrap;">
        <button class="btn btn-primary" type="submit"><?= icon('check', 16) ?> حفظ</button>
        <a href="../api/rates.php" class="btn btn-ghost" target="_blank" id="fetch-rates-btn"
           onclick="fetchRatesNow(event)"><?= icon('trending-up', 16) ?> جلب الأسعار الآن</a>
        <a href="../rates.php" class="btn btn-ghost" target="_blank"><?= icon('external', 14) ?> عرض الصفحة</a>
        <a href="prices.php" class="btn btn-ghost"><?= icon('tag', 14) ?> إدارة الأسعار</a>
      </div>
    </form>

    <div style="margin-top:20px; padding:14px; background:var(--surface-2); border-radius:10px;">
      <div style="font-size:13px; font-weight:600; margin-bottom:8px;">المصادر المستخدمة:</div>
      <ul style="margin:0; padding-inline-start:20px; font-size:13px; color:var(--ink-dim); line-height:1.8;">
        <li><strong>frankfurter.app</strong> — USD/TRY/EUR/GBP (مجاني بدون مفتاح)</li>
        <li><strong>open.er-api.com</strong> — احتياطي (1500 طلب/شهر مجاني)</li>
        <li><strong>يدوي</strong> — سعر الليرة السورية (السوق السوداء)</li>
      </ul>
      <div style="font-size:12px; color:var(--ink-faint); margin-top:8px;">
        لتحديث تلقائي، أضف Cron Job يستدعي:
        <code style="direction:ltr; display:block; margin-top:4px;">curl -X POST "https://yoursite.com/api/rates.php"</code>
      </div>
    </div>
  </div>
</div>

<!-- ── الأمان ── -->
<div class="tab-pane <?= $activeTab==='security'?'active':'' ?>" id="tab-security">
  <div class="panel" style="max-width:560px; margin-bottom:20px;">
    <h2><?= icon('shield',18) ?> Cloudflare Turnstile</h2>
    <p style="color:var(--ink-dim); font-size:13.5px; margin-bottom:20px;">
      Cloudflare Turnstile بديل مجاني وخصوصي لـ CAPTCHA. أنشئ موقعاً في
      <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank" rel="noopener">لوحة Cloudflare</a>
      للحصول على المفاتيح.
    </p>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
      <input type="hidden" name="tab"  value="security">
      <div class="field">
        <label>Turnstile Site Key</label>
        <input type="text" name="cf_turnstile_site_key" value="<?= h($settings['cf_turnstile_site_key'] ?? '') ?>"
               placeholder="0x4AAAA..." dir="ltr">
      </div>
      <div class="field">
        <label>Turnstile Secret Key</label>
        <input type="password" name="cf_turnstile_secret_key" value="<?= h($settings['cf_turnstile_secret_key'] ?? '') ?>"
               placeholder="0x4AAAA..." dir="ltr">
      </div>
      <div class="setting-row" style="margin-bottom:24px;">
        <label>تفعيل Turnstile<small>يضاف لنموذج تسجيل الدخول ونموذج الدعم</small></label>
        <input type="checkbox" name="cf_turnstile_enabled" <?= ($settings['cf_turnstile_enabled']??'0')==='1'?'checked':'' ?>>
      </div>

      <hr style="border:none; border-top:1px solid var(--line); margin:20px 0;">
      <h3 style="font-size:16px; margin:0 0 16px;"><?= icon('check-circle', 16) ?> Google reCAPTCHA</h3>
      <p style="color:var(--ink-dim); font-size:13.5px; margin-bottom:20px;">
        احصل على مفاتيح من
        <a href="https://www.google.com/recaptcha/admin/create" target="_blank" rel="noopener">Google reCAPTCHA Admin</a>.
        v2 = مربع تحقق، v3 = نقاط صامتة.
      </p>
      <div class="field">
        <label>reCAPTCHA Site Key</label>
        <input type="text" name="recaptcha_site_key" value="<?= h($settings['recaptcha_site_key'] ?? '') ?>"
               placeholder="6L..." dir="ltr">
      </div>
      <div class="field">
        <label>reCAPTCHA Secret Key</label>
        <input type="password" name="recaptcha_secret_key" value="<?= h($settings['recaptcha_secret_key'] ?? '') ?>"
               placeholder="6L..." dir="ltr">
      </div>
      <div class="field">
        <label>الإصدار</label>
        <select name="recaptcha_version">
          <option value="v2" <?= ($settings['recaptcha_version']??'v2')==='v2'?'selected':'' ?>>v2 — مربع التحقق (Checkbox)</option>
          <option value="v3" <?= ($settings['recaptcha_version']??'v2')==='v3'?'selected':'' ?>>v3 — غير مرئي (Invisible Score)</option>
        </select>
      </div>
      <div class="setting-row" style="margin-bottom:20px;">
        <label>تفعيل reCAPTCHA<small>يُعطَّل تلقائياً إذا كان Turnstile مفعّلاً</small></label>
        <input type="checkbox" name="recaptcha_enabled" <?= ($settings['recaptcha_enabled']??'0')==='1'?'checked':'' ?>>
      </div>

      <button class="btn btn-primary" type="submit"><?= icon('check', 16) ?> حفظ إعدادات الأمان</button>
    </form>
  </div>
</div>

<!-- ── SEO ── -->
<div class="tab-pane <?= $activeTab==='seo'?'active':'' ?>" id="tab-seo">
  <div class="panel" style="max-width:560px;">
    <h2><?= icon('globe',18) ?> إعدادات SEO</h2>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
      <input type="hidden" name="tab"  value="seo">
      <div class="setting-row">
        <label>السماح لمحركات البحث<small>Allow robots / فهرسة عامة</small></label>
        <input type="checkbox" name="robots_allow_public" <?= ($settings['robots_allow_public']??'0')==='1'?'checked':'' ?>>
      </div>
      <div class="setting-row">
        <label>تضمين صفحات الزبائن في Sitemap</label>
        <input type="checkbox" name="sitemap_include_customers" <?= ($settings['sitemap_include_customers']??'0')==='1'?'checked':'' ?>>
      </div>
      <div class="field" style="margin-top:16px;">
        <label>مفتاح RSS السري (فارغ = بلا حماية)</label>
        <input type="text" name="rss_secret" value="<?= h($settings['rss_secret'] ?? '') ?>" dir="ltr">
      </div>
      <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:4px;">
        <a href="../sitemap.php" target="_blank" class="btn btn-ghost btn-sm"><?= icon('external',14) ?> sitemap.xml</a>
        <a href="../robots.php" target="_blank" class="btn btn-ghost btn-sm"><?= icon('external',14) ?> robots.txt</a>
        <a href="../rss.php" target="_blank" class="btn btn-ghost btn-sm"><?= icon('external',14) ?> RSS</a>
      </div>
      <div style="margin-top:20px;">
        <button class="btn btn-primary" type="submit"><?= icon('check', 16) ?> حفظ</button>
      </div>
    </form>
  </div>
</div>

<script>
const TABS = ['general','google','telegram','rates','security','seo'];
function switchTab(name){
  TABS.forEach(t=>{
    document.getElementById('tab-'+t).classList.toggle('active', t===name);
  });
  document.querySelectorAll('.tab-btn').forEach((b,i)=>{
    b.classList.toggle('active', TABS[i]===name);
  });
}

async function fetchRatesNow(e) {
  e.preventDefault();
  const btn = document.getElementById('fetch-rates-btn');
  btn.textContent = 'جارٍ الجلب...';
  btn.style.pointerEvents = 'none';
  try {
    const r = await fetch('../api/rates.php', {method:'POST'});
    const d = await r.json();
    btn.textContent = d.ok ? '✓ تم (' + d.count + ' عملة)' : '✗ خطأ';
  } catch(ex) {
    btn.textContent = '✗ فشل الاتصال';
  }
  setTimeout(() => { btn.innerHTML = '<?= icon('trending-up', 16) ?> جلب الأسعار الآن'; btn.style.pointerEvents=''; }, 3000);
}
</script>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
