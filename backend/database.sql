-- Fresh install: import in phpMyAdmin. For the old Node database, run upgrade.sql first.
CREATE DATABASE IF NOT EXISTS research_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER DATABASE research_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE research_portal;

CREATE TABLE IF NOT EXISTS users (
 id INT AUTO_INCREMENT PRIMARY KEY,
 full_name VARCHAR(255) NOT NULL,
 role VARCHAR(50) NOT NULL DEFAULT 'student',
 department VARCHAR(255) NOT NULL,
 email VARCHAR(255) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL,
 cv_path VARCHAR(255) NOT NULL,
 bio TEXT NULL,
 is_active TINYINT NOT NULL DEFAULT 1,
 session_version INT NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS papers (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL,
 title VARCHAR(255) NOT NULL,
 authors VARCHAR(255) NOT NULL,
 abstract TEXT NOT NULL,
 keywords VARCHAR(255) NOT NULL,
 department VARCHAR(255) NOT NULL,
 category VARCHAR(100) NOT NULL,
 file_path VARCHAR(255) NOT NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'pending',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS saved_papers (
 user_id INT NOT NULL,
 paper_id INT NOT NULL,
 PRIMARY KEY (user_id, paper_id),
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY (paper_id) REFERENCES papers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS projects (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL,
 title VARCHAR(255) NOT NULL,
 description TEXT NOT NULL,
 department VARCHAR(255) NOT NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'Recruiting',
 approval VARCHAR(20) NOT NULL DEFAULT 'pending',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS project_members (
 project_id INT NOT NULL,
 user_id INT NOT NULL,
 PRIMARY KEY (project_id, user_id),
 FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS discussions (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL,
 title VARCHAR(255) NOT NULL,
 category VARCHAR(100) NOT NULL,
 description TEXT NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS replies (
 id INT AUTO_INCREMENT PRIMARY KEY,
 discussion_id INT NOT NULL,
 user_id INT NOT NULL,
 body TEXT NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (discussion_id) REFERENCES discussions(id) ON DELETE CASCADE,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL,
 message VARCHAR(500) NOT NULL,
 is_read TINYINT NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- An admin issues a reset link after checking the user's identity.
CREATE TABLE IF NOT EXISTS password_resets (
 user_id INT PRIMARY KEY,
 token_hash VARCHAR(64) NULL,
 expires_at DATETIME NULL,
 requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_attempts (
 id INT AUTO_INCREMENT PRIMARY KEY,
 attempt_key VARCHAR(64) NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX (attempt_key, created_at)
) ENGINE=InnoDB;

-- NEW ADMIN: from the project terminal, run: php backend/create_admin.php
-- The script asks for name, email and password. No prior registration is needed.
-- Existing admins can also use Admin Panel > Add New Admin.
-- Or promote an EXISTING registered account in phpMyAdmin (replace the email):
-- UPDATE users SET role = 'admin' WHERE email = 'your-email@example.com';

-- Existing database: select research_portal in phpMyAdmin, then import this file.
-- These tables also appear in database.sql for fresh installations.
CREATE TABLE IF NOT EXISTS chat_conversations (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_one_id INT NOT NULL,
 user_two_id INT NOT NULL,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY chat_pair (user_one_id, user_two_id),
 INDEX chat_user_one (user_one_id, updated_at),
 INDEX chat_user_two (user_two_id, updated_at),
 FOREIGN KEY (user_one_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY (user_two_id) REFERENCES users(id) ON DELETE CASCADE,
 CHECK (user_one_id < user_two_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS chat_messages (
 id INT AUTO_INCREMENT PRIMARY KEY,
 conversation_id INT NOT NULL,
 sender_id INT NOT NULL,
 body TEXT NOT NULL,
 client_token CHAR(32) NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 read_at DATETIME NULL,
 UNIQUE KEY chat_send_token (conversation_id, sender_id, client_token),
 INDEX chat_history (conversation_id, id),
 INDEX chat_unread (conversation_id, read_at, sender_id),
 FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
 FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
