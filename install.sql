CREATE DATABASE IF NOT EXISTS nook
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE nook;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(80) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default login: admin
-- Default password: admin123
-- After the first login, change the password manually via SQL or create your own user.
INSERT INTO users (username, password_hash)
SELECT 'admin', '$2y$12$9fkwvVaZF1sphTkCdKQ4gOyMcqBV7fN14XYxbMJmLoDa6GwsbSxZ6'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'admin');

CREATE TABLE IF NOT EXISTS cards (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  entry_type ENUM('media','note') NOT NULL DEFAULT 'media',
  title VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT NULL,
  body_html MEDIUMTEXT NULL,
  body_json MEDIUMTEXT NULL,
  is_hidden TINYINT(1) NOT NULL DEFAULT 0,
  deleted_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_cards_entry_type (entry_type),
  INDEX idx_cards_created_at (created_at),
  INDEX idx_cards_deleted_at (deleted_at),
  INDEX idx_cards_is_hidden (is_hidden),
  FULLTEXT KEY ft_cards_text (title, description, body_html)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS images (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  card_id INT UNSIGNED NOT NULL,
  original_filename VARCHAR(255) NOT NULL,
  stored_filename VARCHAR(255) NOT NULL,
  thumb_filename VARCHAR(255) NOT NULL DEFAULT '',
  media_type ENUM('image','video') NOT NULL DEFAULT 'image',
  mime VARCHAR(80) NOT NULL,
  width INT UNSIGNED NULL,
  height INT UNSIGNED NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_images_card_id (card_id),
  KEY idx_images_original_filename (original_filename),
  CONSTRAINT fk_images_card
    FOREIGN KEY (card_id) REFERENCES cards(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tags (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(80) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tags_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS card_tags (
  card_id INT UNSIGNED NOT NULL,
  tag_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (card_id, tag_id),
  KEY idx_card_tags_tag_id (tag_id),
  CONSTRAINT fk_card_tags_card
    FOREIGN KEY (card_id) REFERENCES cards(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_card_tags_tag
    FOREIGN KEY (tag_id) REFERENCES tags(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
