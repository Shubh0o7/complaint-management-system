-- Complaint Management System - Phase 1, 2 & 3
-- Database Schema
-- Import this file into MySQL via phpMyAdmin or CLI

CREATE DATABASE IF NOT EXISTS `complaint_system`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `complaint_system`;

-- ============================================
-- PHASE 1 & 2 TABLES
-- ============================================

-- Users table (updated: added role column for admin support)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('user', 'admin') DEFAULT 'user',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categories table (Phase 2)
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default categories
INSERT IGNORE INTO `categories` (`name`, `description`) VALUES
('IT Support', 'Technical issues, software/hardware problems'),
('Infrastructure', 'Building, facilities, maintenance issues'),
('Academic', 'Course-related, faculty, examination issues'),
('Administrative', 'Office, documentation, process issues'),
('Hostel', 'Hostel accommodation and facilities'),
('Transport', 'Bus, vehicle, commute related issues'),
('Ragging', 'Anti-ragging complaints'),
('Other', 'Miscellaneous complaints');

-- Complaints table (updated: added priority and category_id)
CREATE TABLE IF NOT EXISTS `complaints` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `priority` ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
  `description` TEXT NOT NULL,
  `status` ENUM('Pending', 'In Progress', 'Resolved', 'Rejected') DEFAULT 'Pending',
  `admin_remarks` TEXT DEFAULT NULL,
  `resolved_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin account (password: admin123 - change after first login)
INSERT IGNORE INTO `users` (`full_name`, `email`, `password`, `role`) VALUES
('Administrator', 'admin@cms.com', '$2y$10$8K1p/a0dR1xqM8k3UKJHueWbEFwKEFNkBfQr3VCdLwMqRqSFqKmFi', 'admin');

-- ============================================
-- PHASE 3 TABLES
-- ============================================

-- Complaint Timeline / Activity Log (tracks all status changes and actions)
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

-- Complaint Attachments (file uploads linked to complaints)
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

-- Complaint Comments (conversation between user and admin)
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

-- Notifications (in-app notification system)
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `complaint_id` INT DEFAULT NULL,
  `title` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('status_change', 'comment', 'assignment', 'system') DEFAULT 'system',
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`complaint_id`) REFERENCES `complaints`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- MIGRATION QUERIES (for existing databases)
-- ============================================

-- Phase 2 migrations (run if upgrading from Phase 1):
-- ALTER TABLE `users` ADD COLUMN `role` ENUM('user', 'admin') DEFAULT 'user' AFTER `password`;
-- ALTER TABLE `complaints` ADD COLUMN `priority` ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium' AFTER `category`;
-- ALTER TABLE `complaints` ADD COLUMN `admin_remarks` TEXT DEFAULT NULL AFTER `status`;
-- ALTER TABLE `complaints` ADD COLUMN `resolved_at` TIMESTAMP NULL DEFAULT NULL AFTER `admin_remarks`;
-- ALTER TABLE `complaints` MODIFY COLUMN `status` ENUM('Pending', 'In Progress', 'Resolved', 'Rejected') DEFAULT 'Pending';

-- Phase 3 migrations (run if upgrading from Phase 2):
-- Just run the CREATE TABLE IF NOT EXISTS statements above for the 4 new tables.
-- They will only create if they don't already exist.
