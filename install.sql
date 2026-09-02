-- Nook clean-install schema.
-- Create an empty database, select it, and import this file once.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS app_settings (
  setting_key VARCHAR(100) NOT NULL,
  setting_value LONGTEXT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS spaces (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(160) NOT NULL,
  password_hash VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_spaces_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS space_access_tokens (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  space_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_space_access_token (token_hash),
  KEY idx_space_access_lookup (user_id, space_id, expires_at),
  CONSTRAINT fk_space_access_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_space_access_space FOREIGN KEY (space_id) REFERENCES spaces(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cards (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  space_id INT UNSIGNED NOT NULL,
  entry_type ENUM('media','note') NOT NULL DEFAULT 'media',
  title VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT NULL,
  body_json LONGTEXT NULL,
  body_html LONGTEXT NULL,
  is_hidden TINYINT(1) NOT NULL DEFAULT 0,
  is_draft TINYINT(1) NOT NULL DEFAULT 0,
  is_pinned TINYINT(1) NOT NULL DEFAULT 0,
  pinned_at DATETIME NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 0,
  public_tag VARCHAR(100) NOT NULL DEFAULT '',
  publish_as_page TINYINT(1) NOT NULL DEFAULT 0,
  is_public_pinned TINYINT(1) NOT NULL DEFAULT 0,
  public_pinned_at DATETIME NULL,
  public_page_order INT NOT NULL DEFAULT 0,
  published_at DATETIME NULL,
  deleted_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cards_space (space_id, deleted_at, is_draft),
  KEY idx_cards_pinned (space_id, is_pinned, pinned_at),
  KEY idx_cards_public (is_published, publish_as_page, is_public_pinned),
  CONSTRAINT fk_cards_space FOREIGN KEY (space_id) REFERENCES spaces(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tags (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tags_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS card_tags (
  card_id BIGINT UNSIGNED NOT NULL,
  tag_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (card_id, tag_id),
  KEY idx_card_tags_tag (tag_id),
  CONSTRAINT fk_card_tags_card FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE,
  CONSTRAINT fk_card_tags_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media_files (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  card_id BIGINT UNSIGNED NOT NULL,
  role ENUM('content','attachment','inline') NOT NULL DEFAULT 'content',
  original_filename VARCHAR(255) NOT NULL,
  stored_path VARCHAR(700) NOT NULL,
  preview_path VARCHAR(700) NOT NULL DEFAULT '',
  media_type ENUM('image','video','pdf','stl','file') NOT NULL DEFAULT 'file',
  mime VARCHAR(120) NOT NULL DEFAULT 'application/octet-stream',
  size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
  width INT UNSIGNED NULL,
  height INT UNSIGNED NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  sha256 CHAR(64) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_media_card (card_id, role, sort_order),
  KEY idx_media_type (media_type),
  CONSTRAINT fk_media_card FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (username, password_hash)
SELECT 'admin', '0192023a7bbd73250516f069df18b500'
WHERE NOT EXISTS (SELECT 1 FROM users);

INSERT INTO spaces (name, password_hash)
SELECT CONVERT(0xD09ED181D0BDD0BED0B2D0BDD0B0D18F20D0BDD18BD187D0BAD0B0 USING utf8mb4), NULL
WHERE NOT EXISTS (SELECT 1 FROM spaces);

INSERT INTO app_settings (setting_key, setting_value)
SELECT 'default_space_id', CAST((SELECT id FROM spaces ORDER BY id LIMIT 1) AS CHAR)
WHERE NOT EXISTS (SELECT 1 FROM app_settings WHERE setting_key = 'default_space_id');

INSERT INTO app_settings (setting_key, setting_value) VALUES
  ('public_slug', 'blog'),
  ('public_header_html', '')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

SET FOREIGN_KEY_CHECKS = 1;
