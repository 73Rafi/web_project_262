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
