-- UIU Research Hub — fresh install schema
-- Safe to import into a new database. For the original Node-era database run
-- upgrade.sql first, then import this file.
CREATE DATABASE IF NOT EXISTS research_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER DATABASE research_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE research_portal;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(255) NOT NULL,
  role ENUM('student','teacher','researcher','admin') NOT NULL DEFAULT 'student',
  department VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  cv_path VARCHAR(255) NOT NULL DEFAULT '',
  bio TEXT NULL,
  research_interests VARCHAR(500) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  session_version INT UNSIGNED NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_department (department),
  INDEX idx_users_role_active (role, is_active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS papers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  authors VARCHAR(500) NOT NULL,
  abstract TEXT NOT NULL,
  keywords VARCHAR(500) NOT NULL,
  department VARCHAR(255) NOT NULL,
  category VARCHAR(100) NOT NULL,
  file_path VARCHAR(255) NOT NULL DEFAULT '',
  doi VARCHAR(160) NULL,
  external_url VARCHAR(500) NULL,
  publication_year SMALLINT UNSIGNED NULL,
  citation_count INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('draft','pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_papers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_papers_status_created (status, created_at),
  INDEX idx_papers_catalogue (category, department, publication_year),
  FULLTEXT KEY ft_papers_discovery (title, authors, abstract, keywords)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS saved_papers (
  user_id INT UNSIGNED NOT NULL,
  paper_id INT UNSIGNED NOT NULL,
  saved_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, paper_id),
  CONSTRAINT fk_saved_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_saved_paper FOREIGN KEY (paper_id) REFERENCES papers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS projects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  department VARCHAR(255) NOT NULL,
  status ENUM('Active','Recruiting','Completed') NOT NULL DEFAULT 'Recruiting',
  approval ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  capacity TINYINT UNSIGNED NULL,
  project_url VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_projects_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_projects_listing (approval, status, created_at),
  INDEX idx_projects_department (department)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS project_members (
  project_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  member_role ENUM('member','co_lead') NOT NULL DEFAULT 'member',
  joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (project_id, user_id),
  CONSTRAINT fk_members_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_members_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Joining a project is a reviewable request. Only approved requests create a
-- project_members record, so owners retain control of their research team.
CREATE TABLE IF NOT EXISTS project_join_requests (
  project_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  message VARCHAR(1000) NULL,
  status ENUM('pending','approved','rejected','withdrawn') NOT NULL DEFAULT 'pending',
  reviewed_by INT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (project_id, user_id),
  CONSTRAINT fk_join_requests_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_join_requests_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_join_requests_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_join_requests_owner_queue (status, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS discussions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  category VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  is_pinned TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_discussions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_discussions_category_created (category, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS replies (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  discussion_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_replies_discussion FOREIGN KEY (discussion_id) REFERENCES discussions(id) ON DELETE CASCADE,
  CONSTRAINT fk_replies_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_replies_discussion_created (discussion_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  message VARCHAR(500) NOT NULL,
  link_url VARCHAR(500) NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_notifications_inbox (user_id, is_read, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS resources (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  resource_type ENUM('guide','tool','funding','method') NOT NULL,
  url VARCHAR(500) NULL,
  department VARCHAR(255) NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_resources_featured (is_featured, resource_type)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_resets (
  user_id INT UNSIGNED PRIMARY KEY,
  token_hash CHAR(64) NULL,
  expires_at DATETIME NULL,
  requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  attempt_key VARCHAR(64) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_login_attempts_key_time (attempt_key, created_at)
) ENGINE=InnoDB;

-- An editorial record anchors the starter content. It is not an admin account.
INSERT INTO users (full_name, role, department, email, password, bio, research_interests)
SELECT 'Research Hub Editorial Team', 'researcher', 'Office of Research', 'research-hub@uiu.ac.bd',
       '$2y$10$S90yWIrrPfCwwHJocKmxuOSj5gb6vfcfJjPCNnpFMbDI2PzxNYkR6',
       'The Research Hub editorial record curates examples and starter resources for this portal.',
       'Research communication, interdisciplinary collaboration'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'research-hub@uiu.ac.bd');

INSERT INTO papers (user_id, title, authors, abstract, keywords, department, category, publication_year, citation_count, status)
SELECT u.id, 'Explainable machine learning for transparent student-support decisions',
       'UIU Research Hub Editorial Team',
       'A practical research note on combining interpretable features, human review, and clear communication when analytical systems are used to support students.',
       'explainable AI, learning analytics, fairness, education', 'Computer Science', 'Artificial Intelligence', 2026, 12, 'approved'
FROM users u WHERE u.email = 'research-hub@uiu.ac.bd'
  AND NOT EXISTS (SELECT 1 FROM papers WHERE title = 'Explainable machine learning for transparent student-support decisions');
INSERT INTO papers (user_id, title, authors, abstract, keywords, department, category, publication_year, citation_count, status)
SELECT u.id, 'Urban flood signals: low-cost sensing and community reporting in Dhaka',
       'UIU Research Hub Editorial Team',
       'This project outline brings together sensor readings and community observations to improve the timeliness and clarity of local flood reporting.',
       'climate resilience, sensors, urban systems, Dhaka', 'Electrical and Electronic Engineering', 'Environmental Science', 2026, 8, 'approved'
FROM users u WHERE u.email = 'research-hub@uiu.ac.bd'
  AND NOT EXISTS (SELECT 1 FROM papers WHERE title = 'Urban flood signals: low-cost sensing and community reporting in Dhaka');
INSERT INTO papers (user_id, title, authors, abstract, keywords, department, category, publication_year, citation_count, status)
SELECT u.id, 'Designing trustworthy interfaces for public-service data',
       'UIU Research Hub Editorial Team',
       'A design-led study of how language, hierarchy, and feedback can make complex public data more useful to non-specialist audiences.',
       'human-computer interaction, civic technology, information design', 'Computer Science', 'Data Science', 2025, 19, 'approved'
FROM users u WHERE u.email = 'research-hub@uiu.ac.bd'
  AND NOT EXISTS (SELECT 1 FROM papers WHERE title = 'Designing trustworthy interfaces for public-service data');

INSERT INTO projects (user_id, title, description, department, status, approval, capacity)
SELECT u.id, 'Climate-ready campus: heat, shade, and walkability', 'A cross-disciplinary study mapping thermal comfort across campus and proposing practical, measurable improvements. Looking for students interested in mapping, surveying, or visual storytelling.', 'Environmental Science', 'Recruiting', 'approved', 6
FROM users u WHERE u.email = 'research-hub@uiu.ac.bd'
  AND NOT EXISTS (SELECT 1 FROM projects WHERE title = 'Climate-ready campus: heat, shade, and walkability');
INSERT INTO projects (user_id, title, description, department, status, approval, capacity)
SELECT u.id, 'Bangla accessibility patterns for digital public services', 'We are reviewing everyday barriers in Bangla-first interfaces and testing small design changes with users. Researchers in UX, language, public policy, and software are welcome.', 'Computer Science', 'Recruiting', 'approved', 5
FROM users u WHERE u.email = 'research-hub@uiu.ac.bd'
  AND NOT EXISTS (SELECT 1 FROM projects WHERE title = 'Bangla accessibility patterns for digital public services');

INSERT INTO discussions (user_id, title, category, description, is_pinned)
SELECT u.id, 'Start here: how to turn a good question into a workable research plan', 'Research Methods', 'Share the question you are trying to answer, the people or system it concerns, and the evidence you think would be useful. Community members can help you sharpen the scope.', 1
FROM users u WHERE u.email = 'research-hub@uiu.ac.bd'
  AND NOT EXISTS (SELECT 1 FROM discussions WHERE title = 'Start here: how to turn a good question into a workable research plan');
INSERT INTO discussions (user_id, title, category, description, is_pinned)
SELECT u.id, 'What tool has made your literature review less chaotic?', 'Tools & Software', 'Compare the tools, note-taking methods, or citation workflows that have genuinely helped you keep a literature review clear and reproducible.', 0
FROM users u WHERE u.email = 'research-hub@uiu.ac.bd'
  AND NOT EXISTS (SELECT 1 FROM discussions WHERE title = 'What tool has made your literature review less chaotic?');
INSERT INTO discussions (user_id, title, category, description, is_pinned)
SELECT u.id, 'Funding leads and calls: share opportunities with enough context', 'Career & Funding', 'When posting an opportunity, include the deadline, eligibility, expected effort, and the source link so others can quickly decide whether it is a fit.', 1
FROM users u WHERE u.email = 'research-hub@uiu.ac.bd'
  AND NOT EXISTS (SELECT 1 FROM discussions WHERE title = 'Funding leads and calls: share opportunities with enough context');

INSERT INTO resources (title, description, resource_type, url, department, is_featured)
SELECT 'Research question canvas', 'A concise starting structure for framing a problem, evidence, constraints, and intended contribution.', 'guide', NULL, NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM resources WHERE title = 'Research question canvas');
INSERT INTO resources (title, description, resource_type, url, department, is_featured)
SELECT 'Reproducible literature review workflow', 'A lightweight guide to recording queries, screening decisions, citations, and synthesis notes.', 'method', NULL, NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM resources WHERE title = 'Reproducible literature review workflow');
INSERT INTO resources (title, description, resource_type, url, department, is_featured)
SELECT 'Crossref metadata search', 'Find scholarly metadata and publisher links through the research explorer.', 'tool', 'https://www.crossref.org/', NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM resources WHERE title = 'Crossref metadata search');
