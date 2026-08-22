<?php
/* ═══════════════════════════════════════════════
   Self-healing schema — creates/repairs every table.
   Included by both config.php (normal runtime) and
   the install wizard (before config.generated.php exists).
   ═══════════════════════════════════════════════ */
function sh_ensure_schema(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
      id INT AUTO_INCREMENT PRIMARY KEY,
      username VARCHAR(80) NOT NULL UNIQUE,
      password_hash VARCHAR(255) NOT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
      `key` VARCHAR(120) NOT NULL PRIMARY KEY,
      `value` LONGTEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(120) NOT NULL,
      slug VARCHAR(140) NOT NULL UNIQUE,
      type ENUM('article','tool') NOT NULL DEFAULT 'article',
      icon VARCHAR(60) DEFAULT 'fa-folder',
      color VARCHAR(20) DEFAULT '#6366f1',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS articles (
      id INT AUTO_INCREMENT PRIMARY KEY,
      title VARCHAR(220) NOT NULL,
      slug VARCHAR(220) NOT NULL UNIQUE,
      category_id INT NULL,
      content_type ENUM('article','news','tutorial','comparison','review') NOT NULL DEFAULT 'article',
      excerpt VARCHAR(400) DEFAULT '',
      body LONGTEXT,
      hero_icon VARCHAR(60) DEFAULT 'fa-newspaper',
      hero_gradient VARCHAR(60) DEFAULT 'g1',
      meta_title VARCHAR(220) DEFAULT '',
      meta_description VARCHAR(400) DEFAULT '',
      meta_keywords VARCHAR(400) DEFAULT '',
      tags VARCHAR(300) DEFAULT '',
      author VARCHAR(100) DEFAULT 'Editorial Team',
      status ENUM('published','draft') NOT NULL DEFAULT 'draft',
      trending TINYINT(1) NOT NULL DEFAULT 0,
      views INT NOT NULL DEFAULT 0,
      reading_time INT NOT NULL DEFAULT 4,
      published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      INDEX (status), INDEX (content_type), INDEX (category_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS tools (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(180) NOT NULL,
      slug VARCHAR(200) NOT NULL UNIQUE,
      category_id INT NULL,
      icon_class VARCHAR(60) DEFAULT 'fa-wrench',
      tool_key VARCHAR(80) NOT NULL,
      short_description VARCHAR(250) DEFAULT '',
      full_description LONGTEXT,
      meta_title VARCHAR(220) DEFAULT '',
      meta_description VARCHAR(400) DEFAULT '',
      meta_keywords VARCHAR(400) DEFAULT '',
      status ENUM('published','draft') NOT NULL DEFAULT 'published',
      uses_count INT NOT NULL DEFAULT 0,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      INDEX (status), INDEX (tool_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS google_tokens (
      service VARCHAR(40) NOT NULL PRIMARY KEY,
      access_token LONGTEXT,
      refresh_token LONGTEXT,
      expires_at DATETIME NULL,
      scope VARCHAR(500) DEFAULT '',
      connected_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS ai_activity_log (
      id INT AUTO_INCREMENT PRIMARY KEY,
      actor VARCHAR(60) DEFAULT 'gemini',
      action VARCHAR(80),
      target_type VARCHAR(40),
      target_id INT NULL,
      summary VARCHAR(500),
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(200) NOT NULL,
      slug VARCHAR(220) NOT NULL UNIQUE,
      tagline VARCHAR(300) DEFAULT '',
      product_type VARCHAR(60) DEFAULT 'script',
      icon_class VARCHAR(60) DEFAULT 'fa-cube',
      art_key VARCHAR(40) DEFAULT 'p1',
      price DECIMAL(10,2) NOT NULL DEFAULT 0,
      compare_at_price DECIMAL(10,2) NULL,
      currency VARCHAR(8) NOT NULL DEFAULT 'USD',
      badge VARCHAR(60) DEFAULT '',
      short_description VARCHAR(400) DEFAULT '',
      full_description LONGTEXT,
      features TEXT,
      includes_list TEXT,
      demo_url VARCHAR(300) DEFAULT '',
      payment_url VARCHAR(300) DEFAULT '',
      meta_title VARCHAR(220) DEFAULT '',
      meta_description VARCHAR(400) DEFAULT '',
      meta_keywords VARCHAR(400) DEFAULT '',
      status ENUM('published','draft') NOT NULL DEFAULT 'published',
      featured TINYINT(1) NOT NULL DEFAULT 0,
      sort_order INT NOT NULL DEFAULT 0,
      views INT NOT NULL DEFAULT 0,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      INDEX (status), INDEX (featured)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
      id INT AUTO_INCREMENT PRIMARY KEY,
      product_id INT NULL,
      product_name VARCHAR(200) DEFAULT '',
      name VARCHAR(150) DEFAULT '',
      email VARCHAR(190) DEFAULT '',
      note TEXT,
      amount DECIMAL(10,2) DEFAULT 0,
      currency VARCHAR(8) DEFAULT 'USD',
      status ENUM('new','contacted','paid','delivered','cancelled') NOT NULL DEFAULT 'new',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      INDEX (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(150) DEFAULT '',
      email VARCHAR(190) DEFAULT '',
      message TEXT,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_login_attempts (
      id INT AUTO_INCREMENT PRIMARY KEY,
      ip VARCHAR(64),
      attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      success TINYINT(1) DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
