-- ══════════════════════════════════════════════════════
-- Yassota Connect — Database Schema
-- ══════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS yassota_social CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE yassota_social;

-- ─── Users ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  display_name  VARCHAR(100) NOT NULL DEFAULT '',
  bio           TEXT         DEFAULT NULL,
  avatar        VARCHAR(255) DEFAULT NULL,
  email         VARCHAR(255) NOT NULL UNIQUE,
  google_id     VARCHAR(128) DEFAULT NULL,
  password_hash VARCHAR(255) DEFAULT NULL,
  status        ENUM('active','banned','pending') NOT NULL DEFAULT 'active',
  verified      TINYINT(1)   NOT NULL DEFAULT 0,
  verified_until DATE         DEFAULT NULL,
  premium_tier  ENUM('free','plus','pro','premium') NOT NULL DEFAULT 'free',
  premium_until DATE         DEFAULT NULL,
  follower_count INT UNSIGNED NOT NULL DEFAULT 0,
  following_count INT UNSIGNED NOT NULL DEFAULT 0,
  post_count    INT UNSIGNED NOT NULL DEFAULT 0,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_seen_at  DATETIME     DEFAULT NULL,
  INDEX idx_username (username),
  INDEX idx_email    (email),
  INDEX idx_google   (google_id)
) ENGINE=InnoDB;

-- ─── Follows ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS follows (
  follower_id  BIGINT UNSIGNED NOT NULL,
  following_id BIGINT UNSIGNED NOT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (follower_id, following_id),
  INDEX idx_following (following_id)
) ENGINE=InnoDB;

-- ─── Posts ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS posts (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      BIGINT UNSIGNED NOT NULL,
  content      TEXT            NOT NULL,
  media_type   ENUM('none','image','video','file') NOT NULL DEFAULT 'none',
  media_urls   JSON            DEFAULT NULL,
  post_type    ENUM('post','script','tool_share','article') NOT NULL DEFAULT 'post',
  visibility   ENUM('public','followers','private') NOT NULL DEFAULT 'public',
  is_flagged   TINYINT(1)   NOT NULL DEFAULT 0,
  like_count   INT UNSIGNED NOT NULL DEFAULT 0,
  comment_count INT UNSIGNED NOT NULL DEFAULT 0,
  share_count  INT UNSIGNED NOT NULL DEFAULT 0,
  view_count   INT UNSIGNED NOT NULL DEFAULT 0,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user    (user_id),
  INDEX idx_public  (visibility, created_at DESC),
  FULLTEXT INDEX ft_content (content)
) ENGINE=InnoDB;

-- ─── Likes ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS likes (
  user_id    BIGINT UNSIGNED NOT NULL,
  post_id    BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, post_id)
) ENGINE=InnoDB;

-- ─── Comments ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS comments (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id    BIGINT UNSIGNED NOT NULL,
  user_id    BIGINT UNSIGNED NOT NULL,
  parent_id  BIGINT UNSIGNED DEFAULT NULL,
  content    TEXT NOT NULL,
  like_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_post (post_id),
  INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- ─── Direct Messages ───────────────────────────────────
CREATE TABLE IF NOT EXISTS conversations (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type       ENUM('direct','group') NOT NULL DEFAULT 'direct',
  name       VARCHAR(100) DEFAULT NULL,
  avatar     VARCHAR(255) DEFAULT NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_msg_at DATETIME DEFAULT NULL,
  INDEX idx_last (last_msg_at DESC)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS conversation_members (
  conv_id    BIGINT UNSIGNED NOT NULL,
  user_id    BIGINT UNSIGNED NOT NULL,
  role       ENUM('member','admin') NOT NULL DEFAULT 'member',
  joined_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_read  DATETIME DEFAULT NULL,
  PRIMARY KEY (conv_id, user_id),
  INDEX idx_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS messages (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conv_id    BIGINT UNSIGNED NOT NULL,
  user_id    BIGINT UNSIGNED NOT NULL,
  content    TEXT NOT NULL,
  media_url  VARCHAR(512) DEFAULT NULL,
  is_deleted TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_conv (conv_id, created_at DESC),
  INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- ─── Tools Marketplace ─────────────────────────────────
CREATE TABLE IF NOT EXISTS marketplace_tools (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      BIGINT UNSIGNED NOT NULL,
  slug         VARCHAR(120) NOT NULL UNIQUE,
  title        VARCHAR(200) NOT NULL,
  description  TEXT         NOT NULL,
  long_desc    LONGTEXT     DEFAULT NULL,
  category     VARCHAR(80)  DEFAULT NULL,
  tool_type    ENUM('free','paid') NOT NULL DEFAULT 'free',
  price_usd    DECIMAL(8,2) DEFAULT 0.00,
  download_url VARCHAR(512) DEFAULT NULL,
  demo_url     VARCHAR(512) DEFAULT NULL,
  images       JSON         DEFAULT NULL,
  tags         JSON         DEFAULT NULL,
  is_legal     TINYINT(1)   NOT NULL DEFAULT 1,
  is_approved  TINYINT(1)   NOT NULL DEFAULT 0,
  status       ENUM('draft','published','banned') NOT NULL DEFAULT 'draft',
  view_count   INT UNSIGNED NOT NULL DEFAULT 0,
  download_count INT UNSIGNED NOT NULL DEFAULT 0,
  rating_avg   DECIMAL(3,2) DEFAULT 0.00,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user   (user_id),
  INDEX idx_status (status, created_at DESC),
  FULLTEXT INDEX ft_title (title, description)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tool_purchases (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tool_id    BIGINT UNSIGNED NOT NULL,
  user_id    BIGINT UNSIGNED NOT NULL,
  amount_usd DECIMAL(8,2) NOT NULL,
  payment_id VARCHAR(128) DEFAULT NULL,
  status     ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_tool (tool_id),
  INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- ─── Subscriptions / Payments ──────────────────────────
CREATE TABLE IF NOT EXISTS subscriptions (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    BIGINT UNSIGNED NOT NULL,
  plan       ENUM('badge','plus','pro','premium') NOT NULL,
  amount_usd DECIMAL(8,2) NOT NULL,
  payment_id VARCHAR(128) DEFAULT NULL,
  status     ENUM('pending','active','expired','failed') NOT NULL DEFAULT 'pending',
  starts_at  DATE DEFAULT NULL,
  expires_at DATE DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user   (user_id),
  INDEX idx_status (status, expires_at)
) ENGINE=InnoDB;

-- ─── Notifications ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    BIGINT UNSIGNED NOT NULL,
  from_id    BIGINT UNSIGNED DEFAULT NULL,
  type       VARCHAR(40) NOT NULL,
  ref_id     BIGINT UNSIGNED DEFAULT NULL,
  body       VARCHAR(300) DEFAULT NULL,
  is_read    TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id, is_read, created_at DESC)
) ENGINE=InnoDB;

-- ─── Reports ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS reports (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reporter_id BIGINT UNSIGNED NOT NULL,
  ref_type   ENUM('post','comment','user','tool') NOT NULL,
  ref_id     BIGINT UNSIGNED NOT NULL,
  reason     VARCHAR(200) NOT NULL,
  status     ENUM('open','reviewed','dismissed') NOT NULL DEFAULT 'open',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_open (status, created_at)
) ENGINE=InnoDB;

-- ─── Settings ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS settings (
  cfg_key   VARCHAR(100) NOT NULL PRIMARY KEY,
  cfg_value TEXT         DEFAULT NULL
) ENGINE=InnoDB;
