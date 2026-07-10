-- YASSOTA - Database Schema
-- ملاحظة: لم يعد استيراد هذا الملف مطلوباً — الموقع ينشئ ويصحح كل الجداول
-- تلقائياً بمجرد الاتصال الصحيح بقاعدة البيانات (راجع config.php > ensure_schema()).
-- هذا الملف موجود فقط كمرجع/نسخة احتياطية لمن يفضّل الاستيراد اليدوي.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL UNIQUE,
  icon_svg TEXT,
  sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS apps (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(220) NOT NULL,
  slug VARCHAR(240) NOT NULL UNIQUE,
  category_id INT,
  developer VARCHAR(200),
  version VARCHAR(60),
  play_store_version VARCHAR(60),
  android_version VARCHAR(60),
  size_mb VARCHAR(30),
  license VARCHAR(60) DEFAULT 'Free',
  package_name VARCHAR(200),
  icon_path VARCHAR(300),
  screenshots JSON,
  short_description VARCHAR(500),
  long_description MEDIUMTEXT,
  features JSON,
  pros JSON,
  cons JSON,
  install_steps JSON,
  faq JSON,
  whats_new TEXT,
  download_url VARCHAR(600),
  mirror2_url VARCHAR(600),
  mirror3_url VARCHAR(600),
  rating DECIMAL(2,1) DEFAULT 4.5,
  downloads INT DEFAULT 0,
  views INT DEFAULT 0,
  seo_title VARCHAR(255),
  meta_description VARCHAR(320),
  keywords VARCHAR(500),
  status ENUM('published','draft') DEFAULT 'draft',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  INDEX idx_slug (slug),
  INDEX idx_status (status),
  INDEX idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  `key` VARCHAR(100) PRIMARY KEY,
  `value` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- تصنيفات أولية
INSERT IGNORE INTO categories (name, slug, sort_order) VALUES
('تطبيقات', 'apps', 1),
('ألعاب', 'games', 2),
('تعديل وتصميم', 'design', 3),
('أدوات', 'tools', 4),
('تواصل اجتماعي', 'social', 5),
('إنتاجية', 'productivity', 6);
