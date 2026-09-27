-- Run ONCE only when migrating the ORIGINAL Node.js users table.
-- This keeps existing accounts, passwords and CV paths.
USE research_portal;
ALTER TABLE users ENGINE=InnoDB;
ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE users
    ADD COLUMN bio TEXT NULL,
    ADD COLUMN is_active TINYINT NOT NULL DEFAULT 1,
    ADD COLUMN session_version INT NOT NULL DEFAULT 1;
-- Next import database.sql to create the remaining tables.
