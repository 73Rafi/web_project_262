-- UIU Research Hub migration for an existing pre-PHP installation.
-- Back up the database first. Run this script ONCE, then import database.sql.
USE research_portal;

ALTER TABLE users ENGINE=InnoDB;
ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- These additions apply to the original Node.js users table. If a previous
-- migration has already added a column, omit that individual ALTER clause.
ALTER TABLE users
  ADD COLUMN bio TEXT NULL,
  ADD COLUMN research_interests VARCHAR(500) NULL,
  ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN session_version INT UNSIGNED NOT NULL DEFAULT 1,
  ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Import database.sql after this migration. It creates the portal tables and
-- starter content, including the project_join_requests review queue, without
-- deleting existing account records.
