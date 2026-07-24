-- Migration 003: email_threads for conversation memory + outreach tracking columns
-- Run in phpMyAdmin after deploying this commit.
-- Server: MySQL 5.7 — JSON column type supported since 5.7.8

CREATE TABLE IF NOT EXISTS `email_threads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `service_id` INT DEFAULT NULL,
  `sender_id` INT DEFAULT NULL,
  `provider` VARCHAR(50) DEFAULT NULL,
  `model` VARCHAR(100) DEFAULT NULL,
  `system_prompt` TEXT,
  `messages` JSON,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `email_drafts`
  ADD COLUMN `thread_id` INT DEFAULT NULL,
  ADD COLUMN `next_action_date` DATE DEFAULT NULL;
