<?php
require_once __DIR__ . '/../config.php';
requireAdminLogin();

$pageTitle = 'ترقية قاعدة البيانات';
$activeNav = 'migrate';

$steps  = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfCheck()) {

    // ─── 1. إصلاح أعمدة جدول settings ───────────────────────────────
    try {
        $fields = array_column(
            $pdo->query("SHOW COLUMNS FROM settings")->fetchAll(PDO::FETCH_ASSOC),
            'Field'
        );
        if (!in_array('setting_key', $fields)) {
            $oldKey = $fields[0] ?? null;
            $oldVal = $fields[1] ?? null;
            if ($oldKey && $oldVal) {
                $pdo->exec("ALTER TABLE settings
                    CHANGE COLUMN `$oldKey` setting_key VARCHAR(100) NOT NULL,
                    CHANGE COLUMN `$oldVal` setting_value TEXT NOT NULL");
                $steps[] = "✔ تمت إعادة تسمية الأعمدة: $oldKey → setting_key، $oldVal → setting_value";
            }
        } else {
            $steps[] = '✔ جدول settings سليم (setting_key/setting_value)';
        }
    } catch (PDOException $e) {
        $errors[] = 'settings: ' . $e->getMessage();
    }

    // ─── 2. إضافة عمود product_type لجدول transactions ───────────────
    try {
        $txFields = array_column(
            $pdo->query("SHOW COLUMNS FROM transactions")->fetchAll(PDO::FETCH_ASSOC),
            'Field'
        );
        if (!in_array('product_type', $txFields)) {
            $pdo->exec("ALTER TABLE transactions
                ADD COLUMN product_type VARCHAR(100) DEFAULT NULL AFTER description");
            $steps[] = '✔ تمت إضافة عمود product_type للعمليات';
        } else {
            $steps[] = '✔ عمود product_type موجود';
        }
    } catch (PDOException $e) {
        $errors[] = 'transactions.product_type: ' . $e->getMessage();
    }

    // ─── 3. إنشاء الجداول المفقودة ───────────────────────────────────
    $tables = [
        'admin_remember_tokens' => "CREATE TABLE IF NOT EXISTS admin_remember_tokens (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            admin_id   INT NOT NULL,
            token_hash VARCHAR(64) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'site_users' => "CREATE TABLE IF NOT EXISTS site_users (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            google_id    VARCHAR(100) NOT NULL UNIQUE,
            email        VARCHAR(200) NOT NULL,
            display_name VARCHAR(150) NOT NULL,
            avatar_url   VARCHAR(500) DEFAULT NULL,
            username     VARCHAR(80)  DEFAULT NULL UNIQUE,
            bio          VARCHAR(300) DEFAULT NULL,
            created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_seen    DATETIME DEFAULT NULL,
            is_banned    TINYINT(1) NOT NULL DEFAULT 0,
            INDEX idx_google (google_id),
            INDEX idx_email  (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'chat_messages' => "CREATE TABLE IF NOT EXISTS chat_messages (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            user_id    INT NOT NULL,
            message    TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES site_users(id) ON DELETE CASCADE,
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'support_messages' => "CREATE TABLE IF NOT EXISTS support_messages (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            user_id    INT DEFAULT NULL,
            name       VARCHAR(150) DEFAULT NULL,
            message    TEXT NOT NULL,
            ip         VARCHAR(45) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'market_prices_raw' => "CREATE TABLE IF NOT EXISTS market_prices_raw (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            product_name VARCHAR(200) NOT NULL,
            price        DECIMAL(12,2) NOT NULL DEFAULT 0,
            currency     VARCHAR(10) NOT NULL DEFAULT 'SYP',
            unit         VARCHAR(50) DEFAULT 'قطعة',
            source       VARCHAR(20) DEFAULT 'telegram',
            source_group VARCHAR(200) DEFAULT NULL,
            raw_text     TEXT,
            status       ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            reviewed_at  DATETIME DEFAULT NULL,
            created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_status  (status),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'exchange_rates' => "CREATE TABLE IF NOT EXISTS exchange_rates (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            currency    VARCHAR(10) NOT NULL,
            rate_to_usd DECIMAL(15,4) NOT NULL,
            source      VARCHAR(100) DEFAULT NULL,
            recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_currency_time (currency, recorded_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'price_list' => "CREATE TABLE IF NOT EXISTS price_list (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            name       VARCHAR(200) NOT NULL,
            category   VARCHAR(100) DEFAULT 'عام',
            price      DECIMAL(12,2) NOT NULL DEFAULT 0,
            currency   VARCHAR(10) NOT NULL DEFAULT 'SYP',
            unit       VARCHAR(50) DEFAULT 'قطعة',
            is_active  TINYINT(1) NOT NULL DEFAULT 1,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_category (category),
            INDEX idx_active   (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'articles' => "CREATE TABLE IF NOT EXISTS articles (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            title       VARCHAR(300) NOT NULL,
            slug        VARCHAR(300) NOT NULL UNIQUE,
            description VARCHAR(500) DEFAULT NULL,
            tags        VARCHAR(500) DEFAULT NULL,
            content     LONGTEXT NOT NULL,
            status      ENUM('draft','published') NOT NULL DEFAULT 'draft',
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_slug   (slug),
            INDEX idx_status (status),
            INDEX idx_date   (created_at DESC)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];

    foreach ($tables as $name => $sql) {
        try {
            $pdo->exec($sql);
            $steps[] = "✔ جدول $name — جاهز";
        } catch (PDOException $e) {
            $errors[] = "$name: " . $e->getMessage();
        }
    }

    // ─── 4. إدراج الإعدادات الافتراضية (إن لم تكن موجودة) ───────────
    try {
        $defaults = [
            'shop_name' => 'دفتر الدكان', 'currency' => 'SYP',
            'banner_text' => '', 'banner_enabled' => '0',
            'support_enabled' => '1', 'chat_enabled' => '1', 'pwa_enabled' => '1',
            'google_client_id' => '', 'google_client_secret' => '',
            'telegram_bot_token' => '', 'telegram_chat_id' => '',
            'backup_key' => '', 'tg_market_webhook_secret' => '',
            'recaptcha_site_key' => '', 'recaptcha_secret_key' => '',
            'recaptcha_version' => 'v2', 'recaptcha_enabled' => '0',
            'cf_turnstile_site_key' => '', 'cf_turnstile_secret_key' => '',
            'cf_turnstile_enabled' => '0', 'rate_alert_threshold' => '3',
            'rate_alert_enabled' => '0', 'syp_manual_rate' => '0',
            'sitemap_include_customers' => '0', 'robots_allow_public' => '0',
            'rss_secret' => '',
        ];
        $ins = $pdo->prepare(
            "INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?,?)"
        );
        foreach ($defaults as $k => $v) { $ins->execute([$k, $v]); }
        $steps[] = '✔ تمت مزامنة الإعدادات الافتراضية';
    } catch (PDOException $e) {
        $errors[] = 'الإعدادات الافتراضية: ' . $e->getMessage();
    }
}

require __DIR__ . '/includes/admin-header.php';
?>
<style>
.mig-step{ padding:10px 14px; border-radius:8px; margin-bottom:8px; font-size:14px; display:flex; align-items:center; gap:10px; }
.mig-step.ok{ background:rgba(34,197,142,.1); color:var(--brand); border:1px solid rgba(34,197,142,.25); }
.mig-step.err{ background:rgba(239,106,95,.1); color:var(--debt); border:1px solid rgba(239,106,95,.25); }
</style>

<div class="panel" style="max-width:680px;">
  <h2><?= icon('database', 18) ?> ترقية قاعدة البيانات</h2>
  <p style="font-size:14px; color:var(--ink-dim); margin:0 0 20px;">
    تنفيذ هذه العملية يضيف الأعمدة والجداول المفقودة ويصلح أسماء الأعمدة القديمة.
    يمكن تشغيلها أكثر من مرة بأمان.
  </p>

  <?php if ($steps || $errors): ?>
  <div style="margin-bottom:24px;">
    <?php foreach ($steps as $s): ?>
    <div class="mig-step ok"><?= h($s) ?></div>
    <?php endforeach; ?>
    <?php foreach ($errors as $e): ?>
    <div class="mig-step err"><?= icon('alert', 15) ?> <?= h($e) ?></div>
    <?php endforeach; ?>
    <?php if (!$errors): ?>
    <div class="mig-step ok" style="font-weight:700; margin-top:12px;">
      <?= icon('check-circle', 16) ?> اكتملت الترقية بنجاح — يمكنك الآن استخدام جميع الأقسام
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
    <button class="btn btn-primary" type="submit"><?= icon('refresh', 16) ?> تشغيل الترقية</button>
    <a href="settings.php" class="btn btn-ghost" style="margin-inline-start:8px;">العودة للإعدادات</a>
  </form>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
