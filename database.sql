-- Online Complaint & Grievance Management System schema
CREATE DATABASE IF NOT EXISTS `complaint_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `complaint_system`;

CREATE USER IF NOT EXISTS 'complaint_user'@'%' IDENTIFIED BY 'complaint_pass';
GRANT ALL PRIVILEGES ON `complaint_system`.* TO 'complaint_user'@'%';
FLUSH PRIVILEGES;

CREATE TABLE IF NOT EXISTS `departments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(120) NOT NULL UNIQUE,
  `description` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('user','admin','department','officer') NOT NULL DEFAULT 'user',
  `department_id` INT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_users_department` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `departments` (`name`, `description`) VALUES
('Information Technology', 'Technical support, systems, and digital services'),
('Infrastructure', 'Buildings, facilities, maintenance, and safety'),
('Academic Affairs', 'Courses, examinations, faculty, and academic services'),
('Student Affairs', 'Hostel, transport, welfare, and student support');

INSERT IGNORE INTO `categories` (`name`, `description`) VALUES
('IT Support', 'Technical issues, software, or hardware problems'),
('Infrastructure', 'Building, facilities, and maintenance issues'),
('Academic', 'Course, faculty, or examination issues'),
('Administrative', 'Office, documentation, and process issues'),
('Hostel', 'Hostel accommodation and facilities'),
('Transport', 'Bus, vehicle, and commute-related issues'),
('Ragging', 'Anti-ragging complaints'),
('Other', 'Miscellaneous complaints');

CREATE TABLE IF NOT EXISTS `complaints` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `department_id` INT DEFAULT NULL,
  `officer_id` INT DEFAULT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `priority` ENUM('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
  `description` TEXT NOT NULL,
  `status` ENUM('Pending','In Progress','Resolved','Rejected') NOT NULL DEFAULT 'Pending',
  `admin_remarks` TEXT DEFAULT NULL,
  `department_remarks` TEXT DEFAULT NULL,
  `officer_remarks` TEXT DEFAULT NULL,
  `resolved_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_complaints_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_complaints_department` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_complaints_officer` FOREIGN KEY (`officer_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_complaints_status` (`status`), INDEX `idx_complaints_department` (`department_id`), INDEX `idx_complaints_officer` (`officer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `complaint_timeline` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `complaint_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `old_value` VARCHAR(100) DEFAULT NULL,
  `new_value` VARCHAR(100) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`complaint_id`) REFERENCES `complaints`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `complaint_attachments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `complaint_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `file_type` VARCHAR(100) NOT NULL,
  `file_size` INT NOT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`complaint_id`) REFERENCES `complaints`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `complaint_comments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `complaint_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `comment` TEXT NOT NULL,
  `is_admin` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`complaint_id`) REFERENCES `complaints`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `complaint_id` INT DEFAULT NULL,
  `title` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('status_change','comment','assignment','system') DEFAULT 'system',
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`complaint_id`) REFERENCES `complaints`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- College staff demonstration accounts. Students create accounts through register.php; no student account is pre-seeded.
INSERT IGNORE INTO `users` (`full_name`, `email`, `password`, `role`, `department_id`) VALUES
('Administrator', 'admin@campus.edu', '$2y$10$uFFH0pkxnZOG70akOL5oxupzBXpYGWind8i2CIzvwLG6fjdvC.kha', 'admin', NULL),
('Infrastructure Manager', 'manager@campus.edu', '$2y$10$dVzRolKsqeMOn0ngi1zuf.iR3fVFuxMHiJun.IfLKUven5aUrMkNy', 'department', (SELECT id FROM departments WHERE name = 'Infrastructure' LIMIT 1)),
('Priya Nair', 'officer@campus.edu', '$2y$10$J9MW8nBeDi5GMJJmk7msAOppvttS.yQFtQJ15YPkP.ny9VKh2tFYC', 'officer', (SELECT id FROM departments WHERE name = 'Infrastructure' LIMIT 1));

-- Upgrade notes for existing installations:
-- ALTER TABLE users MODIFY role ENUM('user','admin','department','officer') NOT NULL DEFAULT 'user';
-- ALTER TABLE users ADD department_id INT NULL, ADD is_active TINYINT(1) NOT NULL DEFAULT 1;
-- ALTER TABLE complaints ADD department_id INT NULL, ADD officer_id INT NULL, ADD department_remarks TEXT NULL, ADD officer_remarks TEXT NULL;
-- Create departments before adding the foreign keys above, then run the CREATE TABLE statements for the new columns/tables.

-- College-project enhancement schema: security, accountability, SLA, feedback, and reporting
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `phone` VARCHAR(30) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `avatar_path` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `last_login_at` TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS `user_preferences` (
  `user_id` INT PRIMARY KEY,
  `email_notifications` TINYINT(1) NOT NULL DEFAULT 1,
  `push_notifications` TINYINT(1) NOT NULL DEFAULT 1,
  `notification_digest` ENUM('instant','daily','off') NOT NULL DEFAULT 'instant',
  `theme` ENUM('system','light','dark') NOT NULL DEFAULT 'system',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_preferences_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `user_preferences`
  ADD COLUMN IF NOT EXISTS `push_notifications` TINYINT(1) NOT NULL DEFAULT 1;

CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_key` VARCHAR(80) PRIMARY KEY,
  `setting_value` VARCHAR(255) NOT NULL,
  `updated_by` INT DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_settings_user` FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`) VALUES
  ('portal_name', 'CampusResolve'),
  ('support_email', 'support@campus.edu'),
  ('default_sla_hours', '72'),
  ('email_notifications_enabled', '1');

ALTER TABLE `complaints`
  ADD COLUMN IF NOT EXISTS `reference_no` VARCHAR(30) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `is_anonymous` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `anonymous_token_hash` CHAR(64) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `sla_due_at` DATETIME DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `escalated_at` DATETIME DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `escalation_reason` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `feedback_rating` TINYINT UNSIGNED DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `feedback_comment` TEXT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `feedback_at` DATETIME DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

UPDATE `complaints` SET `reference_no` = CONCAT('GRV-', YEAR(`created_at`), '-', LPAD(`id`, 5, '0')) WHERE `reference_no` IS NULL;
ALTER TABLE `complaints`
  MODIFY COLUMN `reference_no` VARCHAR(30) NOT NULL,
  ADD UNIQUE KEY IF NOT EXISTS `uq_complaints_reference_no` (`reference_no`),
  ADD INDEX IF NOT EXISTS `idx_complaints_sla_due_at` (`sla_due_at`),
  ADD INDEX IF NOT EXISTS `idx_complaints_reference_no` (`reference_no`);

CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(80) NOT NULL,
  `entity_id` BIGINT DEFAULT NULL,
  `description` TEXT NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_audit_user` (`user_id`), INDEX `idx_audit_entity` (`entity_type`, `entity_id`), INDEX `idx_audit_created` (`created_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `token_hash` CHAR(64) NOT NULL UNIQUE,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_password_reset_user` (`user_id`),
  CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sla_policies` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `priority` ENUM('Low','Medium','High','Critical') NOT NULL UNIQUE,
  `response_hours` INT NOT NULL,
  `resolution_hours` INT NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `sla_policies` (`priority`, `response_hours`, `resolution_hours`) VALUES
('Low', 72, 168), ('Medium', 48, 120), ('High', 24, 72), ('Critical', 4, 24);

CREATE TABLE IF NOT EXISTS `complaint_escalations` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `complaint_id` INT NOT NULL,
  `from_user_id` INT DEFAULT NULL,
  `to_user_id` INT DEFAULT NULL,
  `reason` VARCHAR(255) NOT NULL,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` DATETIME DEFAULT NULL,
  INDEX `idx_escalation_complaint` (`complaint_id`),
  CONSTRAINT `fk_escalation_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_escalation_from_user` FOREIGN KEY (`from_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_escalation_to_user` FOREIGN KEY (`to_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_escalation_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `push_subscriptions` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `endpoint` TEXT NOT NULL,
  `p256dh` VARCHAR(255) NOT NULL,
  `auth` VARCHAR(255) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_push_endpoint` (`endpoint`(191)),
  INDEX `idx_push_user` (`user_id`),
  CONSTRAINT `fk_push_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `push_notifications` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `complaint_id` INT DEFAULT NULL,
  `subscription_id` BIGINT DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `body` TEXT NOT NULL,
  `status` ENUM('sent','failed','not_configured') NOT NULL DEFAULT 'not_configured',
  `error_message` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `sent_at` DATETIME DEFAULT NULL,
  INDEX `idx_push_notification_user` (`user_id`),
  INDEX `idx_push_notification_created` (`created_at`),
  CONSTRAINT `fk_push_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_push_notification_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_push_notification_subscription` FOREIGN KEY (`subscription_id`) REFERENCES `push_subscriptions`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `email_notifications` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `complaint_id` INT DEFAULT NULL,
  `recipient` VARCHAR(150) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `body` TEXT NOT NULL,
  `status` ENUM('queued','sent','failed','logged') NOT NULL DEFAULT 'queued',
  `error_message` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `sent_at` DATETIME DEFAULT NULL,
  INDEX `idx_email_created` (`created_at`),
  CONSTRAINT `fk_email_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_email_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notification_queue` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `channel` ENUM('email','push') NOT NULL,
  `user_id` INT NOT NULL,
  `complaint_id` INT DEFAULT NULL,
  `recipient` VARCHAR(255) DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `body` TEXT NOT NULL,
  `payload_json` TEXT DEFAULT NULL,
  `status` ENUM('queued','processing','sent','failed','disabled') NOT NULL DEFAULT 'queued',
  `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `max_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `available_at` DATETIME NOT NULL,
  `locked_at` DATETIME DEFAULT NULL,
  `locked_by` VARCHAR(100) DEFAULT NULL,
  `last_error` VARCHAR(500) DEFAULT NULL,
  `sent_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_queue_ready` (`status`,`available_at`,`id`),
  INDEX `idx_queue_channel_status` (`channel`,`status`,`created_at`),
  INDEX `idx_queue_user` (`user_id`),
  CONSTRAINT `fk_queue_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_queue_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notification_delivery_logs` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `queue_id` BIGINT DEFAULT NULL,
  `channel` ENUM('email','push') NOT NULL,
  `user_id` INT DEFAULT NULL,
  `complaint_id` INT DEFAULT NULL,
  `status` VARCHAR(40) NOT NULL,
  `attempt` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `message` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_delivery_created` (`created_at`),
  INDEX `idx_delivery_status_channel` (`status`,`channel`),
  CONSTRAINT `fk_delivery_queue` FOREIGN KEY (`queue_id`) REFERENCES `notification_queue`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_delivery_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_delivery_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Existing installations receive generated references and SLA dates on first upgrade.
UPDATE `complaints` c
LEFT JOIN `sla_policies` s ON s.`priority` = c.`priority`
SET c.`sla_due_at` = DATE_ADD(c.`created_at`, INTERVAL s.`resolution_hours` HOUR)
WHERE c.`sla_due_at` IS NULL;
