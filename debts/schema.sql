-- ══════════════════════════════════════════════════════
-- مخطط قاعدة بيانات دفتر الدكان — النسخة الكاملة
-- mysql -u USER -p DBNAME < schema.sql
-- ══════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS admin_users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(64) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
    setting_key   VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS customers (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    phone       VARCHAR(30) DEFAULT NULL,
    notes       VARCHAR(255) DEFAULT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS transactions (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    customer_id  INT NOT NULL,
    type         ENUM('purchase','payment') NOT NULL,
    amount       DECIMAL(12,2) NOT NULL,
    description  VARCHAR(255) DEFAULT NULL,
    product_type VARCHAR(100) DEFAULT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer (customer_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- جدول مستخدمي الموقع (تسجيل بجوجل)
CREATE TABLE IF NOT EXISTS site_users (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    google_id    VARCHAR(100) NOT NULL UNIQUE,
    email        VARCHAR(200) NOT NULL,
    display_name VARCHAR(150) NOT NULL,
    avatar_url   VARCHAR(500) DEFAULT NULL,
    username     VARCHAR(80) DEFAULT NULL UNIQUE,
    bio          VARCHAR(300) DEFAULT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen    DATETIME DEFAULT NULL,
    is_banned    TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_google (google_id),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- جدول رسائل الدردشة العامة
CREATE TABLE IF NOT EXISTS chat_messages (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    message    TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES site_users(id) ON DELETE CASCADE,
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- جدول رسائل الدعم
CREATE TABLE IF NOT EXISTS support_messages (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT DEFAULT NULL,
    name       VARCHAR(150) DEFAULT NULL,
    message    TEXT NOT NULL,
    ip         VARCHAR(45) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── الإعدادات الافتراضية ───
INSERT INTO settings (setting_key, setting_value) VALUES
('shop_name',               'دفتر الدكان'),
('currency',                'SYP'),
('banner_text',             ''),
('banner_enabled',          '0'),
('sitemap_include_customers','0'),
('robots_allow_public',     '0'),
('rss_secret',              ''),
('google_client_id',        ''),
('google_client_secret',    ''),
('telegram_bot_token',      ''),
('telegram_chat_id',        ''),
('backup_schedule',         'daily'),
('support_enabled',         '1'),
('chat_enabled',            '1'),
('pwa_enabled',             '1'),
('site_url',                '')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- جدول أسعار الصرف
CREATE TABLE IF NOT EXISTS exchange_rates (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    currency    VARCHAR(10) NOT NULL,
    rate_to_usd DECIMAL(15,4) NOT NULL,
    source      VARCHAR(100) DEFAULT NULL,
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_currency_time (currency, recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- جدول قائمة أسعار البضائع
CREATE TABLE IF NOT EXISTS price_list (
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
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- إعدادات إضافية
INSERT INTO settings (setting_key, setting_value) VALUES
('recaptcha_site_key',      ''),
('recaptcha_secret_key',    ''),
('recaptcha_version',       'v2'),
('recaptcha_enabled',       '0'),
('cf_turnstile_site_key',   ''),
('cf_turnstile_secret_key', ''),
('cf_turnstile_enabled',    '0'),
('rate_alert_threshold',    '3'),
('rate_alert_enabled',      '0'),
('syp_manual_rate',         '0'),
('backup_key',              '')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- بيانات تجريبية (احذفها في الإنتاج)
INSERT IGNORE INTO customers (id, name, phone) VALUES
(1, 'أبو محمد', '0999111222'),
(2, 'سارة خالد', '0988333444'),
(3, 'أحمد الحلبي', NULL);

INSERT IGNORE INTO transactions (customer_id, type, amount, description, product_type) VALUES
(1, 'purchase', 15000, 'رز + سكر + زيت', 'بقالة'),
(1, 'purchase', 8000, 'خبز وحليب', 'مخبز'),
(1, 'payment', 10000, 'دفعة جزئية', NULL),
(2, 'purchase', 22000, 'مواد تنظيف', 'تنظيف'),
(3, 'purchase', 5000, 'علبة بيض', 'بقالة');
