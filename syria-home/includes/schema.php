<?php
/* ═══════════════════════════════════════════════
   Self-healing schema — creates/repairs every table.
   Included by both config.php (normal runtime) and
   the install wizard (before config.generated.php exists).
   ═══════════════════════════════════════════════ */
/** Every table this script owns, for sh_reset_schema() below. */
const SH_TABLES = [
    'admins', 'settings', 'categories', 'articles', 'tools', 'google_tokens',
    'ai_activity_log', 'products', 'orders', 'contact_messages', 'admin_login_attempts',
    'payments', 'unlocks', 'subsites', 'article_schema_blocks', 'link_check_results', 'pages',
    'index_log', 'ratings', 'coupons', 'apps',
];

/**
 * Drops every table this script owns, if present — used ONLY by the
 * install wizard's final step, right before sh_ensure_schema().
 *
 * Root-cause fix: `CREATE TABLE IF NOT EXISTS` is a no-op on a table
 * that already exists, even if that table's columns don't match what
 * this script expects (e.g. a stale, partially-created `settings`
 * table left behind by an earlier failed install attempt against the
 * same database) — every install after that silently keeps using the
 * broken table and fails with a confusing "Unknown column" error.
 * Since this only runs before install.lock exists, no real site data
 * can exist yet, so a clean wipe-and-recreate here is always safe.
 */
function sh_reset_schema(PDO $pdo): void {
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach (SH_TABLES as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
}

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

    /* Real crypto payments (NOWPayments). One row per invoice, covering
       three kinds of checkout: buying a store product, tipping/supporting
       an article or tool, and unlocking a premium article or tool. */
    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
      id INT AUTO_INCREMENT PRIMARY KEY,
      order_id VARCHAR(120) NOT NULL UNIQUE,
      identifier VARCHAR(32) NOT NULL,
      reference_type ENUM('product','tip','unlock_article','unlock_tool') NOT NULL,
      reference_id INT NULL,
      reference_label VARCHAR(220) DEFAULT '',
      customer_email VARCHAR(190) DEFAULT '',
      price_usd DECIMAL(10,2) NOT NULL DEFAULT 0,
      pay_currency VARCHAR(20) NOT NULL,
      pay_address VARCHAR(255) DEFAULT '',
      pay_amount DECIMAL(24,8) NOT NULL DEFAULT 0,
      actually_paid DECIMAL(24,8) NULL,
      status VARCHAR(30) NOT NULL DEFAULT 'pending',
      raw_ipn_json MEDIUMTEXT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX idx_identifier (identifier),
      INDEX idx_status (status),
      INDEX idx_reference (reference_type, reference_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    /* Which visitor (by cookie identifier) has paid to unlock which
       premium article/tool — checked on every view of premium content. */
    $pdo->exec("CREATE TABLE IF NOT EXISTS unlocks (
      id INT AUTO_INCREMENT PRIMARY KEY,
      identifier VARCHAR(32) NOT NULL,
      content_type ENUM('article','tool') NOT NULL,
      content_id INT NOT NULL,
      order_id VARCHAR(120) DEFAULT '',
      unlocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uniq_unlock (identifier, content_type, content_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    sh_ensure_column($pdo, 'articles', 'is_premium', "TINYINT(1) NOT NULL DEFAULT 0");
    sh_ensure_column($pdo, 'articles', 'premium_price', "DECIMAL(10,2) NOT NULL DEFAULT 3.00");
    sh_ensure_column($pdo, 'tools', 'is_premium', "TINYINT(1) NOT NULL DEFAULT 0");
    sh_ensure_column($pdo, 'tools', 'premium_price', "DECIMAL(10,2) NOT NULL DEFAULT 3.00");
    sh_ensure_column($pdo, 'articles', 'auto_link', "TINYINT(1) NOT NULL DEFAULT 1");
    sh_ensure_column($pdo, 'articles', 'hero_image_path', "VARCHAR(300) DEFAULT ''");

    /* Independent subdomain sites provisioned via cPanel — each one is a
       full separate install of this same script, its own DB, its own
       admin account. This table just tracks what's been created. */
    $pdo->exec("CREATE TABLE IF NOT EXISTS subsites (
      id INT AUTO_INCREMENT PRIMARY KEY,
      subdomain VARCHAR(80) NOT NULL,
      root_domain VARCHAR(190) NOT NULL,
      full_domain VARCHAR(220) NOT NULL,
      doc_root VARCHAR(400) DEFAULT '',
      db_name VARCHAR(80) DEFAULT '',
      db_user VARCHAR(80) DEFAULT '',
      admin_user VARCHAR(80) DEFAULT '',
      niche VARCHAR(220) DEFAULT '',
      status ENUM('provisioning','ready','failed') NOT NULL DEFAULT 'provisioning',
      error_log TEXT,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uniq_domain (full_domain)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    /* Extra JSON-LD blocks (FAQPage/HowTo/Review) an admin builds for a
       specific article via the Schema Generator, layered on top of the
       automatic Article/NewsArticle schema every article already gets. */
    $pdo->exec("CREATE TABLE IF NOT EXISTS article_schema_blocks (
      id INT AUTO_INCREMENT PRIMARY KEY,
      article_id INT NOT NULL,
      schema_type ENUM('FAQPage','HowTo','Review') NOT NULL,
      payload_json LONGTEXT NOT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      INDEX (article_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    /* Last broken-link scan results, so the admin page can show them
       without re-crawling on every visit. */
    $pdo->exec("CREATE TABLE IF NOT EXISTS link_check_results (
      id INT AUTO_INCREMENT PRIMARY KEY,
      article_id INT NULL,
      article_title VARCHAR(220) DEFAULT '',
      url VARCHAR(1000) NOT NULL,
      is_internal TINYINT(1) NOT NULL DEFAULT 0,
      http_status INT DEFAULT 0,
      ok TINYINT(1) NOT NULL DEFAULT 0,
      checked_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    /* Admin-managed static pages (About, Contact, Privacy, Terms + custom). */
    $pdo->exec("CREATE TABLE IF NOT EXISTS pages (
      id INT AUTO_INCREMENT PRIMARY KEY,
      slug VARCHAR(120) NOT NULL UNIQUE,
      title VARCHAR(220) NOT NULL,
      body LONGTEXT,
      meta_title VARCHAR(220) DEFAULT '',
      meta_description VARCHAR(400) DEFAULT '',
      show_in_footer TINYINT(1) NOT NULL DEFAULT 1,
      status ENUM('published','draft') NOT NULL DEFAULT 'published',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    /* Every URL submission to every search engine, for the Indexing page. */
    $pdo->exec("CREATE TABLE IF NOT EXISTS index_log (
      id INT AUTO_INCREMENT PRIMARY KEY,
      url VARCHAR(600) NOT NULL,
      engine VARCHAR(48) NOT NULL,
      status VARCHAR(16) NOT NULL DEFAULT 'ok',
      http_code INT NOT NULL DEFAULT 0,
      message VARCHAR(400) DEFAULT '',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_created (created_at),
      INDEX idx_engine (engine)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    /* Real visitor ratings. The UNIQUE key is what keeps the aggregate
       honest — one vote per entity per visitor fingerprint. */
    $pdo->exec("CREATE TABLE IF NOT EXISTS ratings (
      id INT AUTO_INCREMENT PRIMARY KEY,
      entity_type ENUM('article','tool','product') NOT NULL,
      entity_id INT NOT NULL,
      rating TINYINT NOT NULL,
      ip_hash CHAR(64) NOT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uniq_vote (entity_type, entity_id, ip_hash),
      INDEX idx_entity (entity_type, entity_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    sh_ensure_column($pdo, 'tools', 'affiliate_url', "VARCHAR(500) DEFAULT ''");
    sh_ensure_column($pdo, 'tools', 'is_premium', "TINYINT(1) NOT NULL DEFAULT 0");
    sh_ensure_column($pdo, 'tools', 'premium_price', "DECIMAL(10,2) NOT NULL DEFAULT 3.00");
    sh_ensure_column($pdo, 'tools', 'tool_code', "LONGTEXT");
    sh_ensure_column($pdo, 'tools', 'replaces', "VARCHAR(200) DEFAULT ''");

    /* Promo codes for the store. discount_type 'percent' or 'fixed'
       (USD). product_id NULL = applies to every product. */
    $pdo->exec("CREATE TABLE IF NOT EXISTS coupons (
      id INT AUTO_INCREMENT PRIMARY KEY,
      code VARCHAR(40) NOT NULL UNIQUE,
      discount_type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
      discount_value DECIMAL(10,2) NOT NULL DEFAULT 10,
      product_id INT NULL,
      max_uses INT NULL,
      used_count INT NOT NULL DEFAULT 0,
      expires_at DATETIME NULL,
      status ENUM('active','disabled') NOT NULL DEFAULT 'active',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    sh_ensure_column($pdo, 'payments', 'coupon_code', "VARCHAR(40) DEFAULT ''");
    sh_ensure_column($pdo, 'payments', 'discount_usd', "DECIMAL(10,2) NOT NULL DEFAULT 0");

    /* App directory — paste a Google Play Store link, the icon and
       screenshots get fetched once and stored locally forever (same
       fetch-and-compress pattern as article/tool hero images), so the
       public page never hotlinks Google's CDN. */
    $pdo->exec("CREATE TABLE IF NOT EXISTS apps (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(200) NOT NULL,
      slug VARCHAR(220) NOT NULL UNIQUE,
      play_store_url VARCHAR(500) NOT NULL,
      developer VARCHAR(200) DEFAULT '',
      category VARCHAR(120) DEFAULT '',
      icon_path VARCHAR(300) DEFAULT '',
      short_description VARCHAR(400) DEFAULT '',
      full_description LONGTEXT,
      screenshots TEXT,
      meta_title VARCHAR(220) DEFAULT '',
      meta_description VARCHAR(400) DEFAULT '',
      status ENUM('published','draft') NOT NULL DEFAULT 'published',
      views INT NOT NULL DEFAULT 0,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    /* Bilingual content — an Arabic article/tool is a normal row with its
       own slug and lang='ar', pointing back at the English row it's a
       translation of (translation_of). The English row itself never sets
       translation_of; its Arabic counterpart is found by reverse lookup. */
    sh_ensure_column($pdo, 'articles', 'lang', "VARCHAR(5) NOT NULL DEFAULT 'en'");
    sh_ensure_column($pdo, 'articles', 'translation_of', "INT NULL");
    sh_ensure_column($pdo, 'tools', 'lang', "VARCHAR(5) NOT NULL DEFAULT 'en'");
    sh_ensure_column($pdo, 'tools', 'translation_of', "INT NULL");

    /* Real citations/sources for E-E-A-T + AdSense trust signals — never
       fabricated, only filled in when an article actually cites something. */
    sh_ensure_column($pdo, 'articles', 'sources', "TEXT");

    /* Cross-domain "network" footer — a JSON list of {name,url,tagline,color}
       shown on every domain in the family so visitors can hop between them. */
    $pdo->exec("INSERT IGNORE INTO settings (`key`, `value`) VALUES ('network_sites', " . $pdo->quote(json_encode([
        ['name' => 'Yassota.com',        'url' => 'https://yassota.com',          'tagline' => 'The main hub — every site in the network', 'color' => '#0f172a'],
        ['name' => 'Blogs Yassota',      'url' => 'https://syria-home.yassota.com', 'tagline' => 'Articles, tutorials, news & comparisons', 'color' => '#2563eb'],
        ['name' => 'Indexing Yassota',   'url' => 'https://indexing.yassota.com', 'tagline' => 'Fast search-engine indexing tools', 'color' => '#7c3aed'],
        ['name' => 'Yassota Trends',     'url' => 'https://trends.yassota.com',   'tagline' => 'What is trending right now', 'color' => '#16a34a'],
        ['name' => 'Yassota Chat',       'url' => 'https://chat.yassota.com',     'tagline' => 'Free AI chat assistant', 'color' => '#dc2626'],
    ])) . ")");
}

/**
 * Portable "add column if missing" — works on MySQL 5.7+ and MariaDB
 * alike, unlike `ALTER TABLE ... ADD COLUMN IF NOT EXISTS` which only
 * MySQL 8.0.29+ supports.
 */
function sh_ensure_column(PDO $pdo, string $table, string $column, string $definition): void {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    $stmt->execute([$table, $column]);
    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}
