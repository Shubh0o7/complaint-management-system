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

-- Default admin account. Password: admin123; change it after first login.
INSERT IGNORE INTO `users` (`full_name`, `email`, `password`, `role`) VALUES
('Administrator', 'admin@cms.com', '$2y$10$jWETlUvz2B3DXUNXIEOxDuLQd8/nHsJx.AluIPnftNhR8X0dPX6E6', 'admin');

-- Upgrade notes for existing installations:
-- ALTER TABLE users MODIFY role ENUM('user','admin','department','officer') NOT NULL DEFAULT 'user';
-- ALTER TABLE users ADD department_id INT NULL, ADD is_active TINYINT(1) NOT NULL DEFAULT 1;
-- ALTER TABLE complaints ADD department_id INT NULL, ADD officer_id INT NULL, ADD department_remarks TEXT NULL, ADD officer_remarks TEXT NULL;
-- Create departments before adding the foreign keys above, then run the CREATE TABLE statements for the new columns/tables.
