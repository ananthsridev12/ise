-- Migration 004: Multi-tenant auth system
-- Run in phpMyAdmin on de2shrnx_intel

CREATE TABLE IF NOT EXISTS `tenants` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `display_name` VARCHAR(255) NOT NULL DEFAULT '',
  `role` ENUM('admin','member') NOT NULL DEFAULT 'member',
  `active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
  `token` VARCHAR(64) PRIMARY KEY,
  `user_id` INT NOT NULL,
  `tenant_id` INT NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `platform_admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- KB vertical/service access per member
CREATE TABLE IF NOT EXISTS `user_kb_verticals` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `vertical_id` INT NOT NULL,
  UNIQUE KEY `uq_user_vertical` (`user_id`, `vertical_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`vertical_id`) REFERENCES `kb_verticals`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_kb_services` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `service_id` INT NOT NULL,
  UNIQUE KEY `uq_user_service` (`user_id`, `service_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`service_id`) REFERENCES `kb_services`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Add tenant_id to all KB and company tables (safe: column guards)
-- Run each ALTER separately if any fail
ALTER TABLE `kb_company`    ADD COLUMN `tenant_id` INT DEFAULT 1;
ALTER TABLE `kb_verticals`  ADD COLUMN `tenant_id` INT DEFAULT 1;
ALTER TABLE `kb_services`   ADD COLUMN `tenant_id` INT DEFAULT 1;
ALTER TABLE `kb_icps`       ADD COLUMN `tenant_id` INT DEFAULT 1;
ALTER TABLE `kb_tone`       ADD COLUMN `tenant_id` INT DEFAULT 1;
ALTER TABLE `kb_senders`    ADD COLUMN `tenant_id` INT DEFAULT 1;
ALTER TABLE `ai_settings`   ADD COLUMN `tenant_id` INT DEFAULT 1;
ALTER TABLE `companies`     ADD COLUMN `tenant_id` INT DEFAULT 1;
ALTER TABLE `email_drafts`  ADD COLUMN `tenant_id` INT DEFAULT 1;

-- Backfill existing rows to tenant 1
UPDATE `kb_company`   SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `kb_verticals` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `kb_services`  SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `kb_icps`      SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `kb_tone`      SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `kb_senders`   SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `ai_settings`  SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `companies`    SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `email_drafts` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;

-- Seed default SolidPro tenant (id will be 1)
INSERT IGNORE INTO `tenants` (`id`, `name`, `slug`, `active`) VALUES (1, 'SolidPro', 'solidpro', 1);

-- IMPORTANT: After running this migration, create your first platform admin and tenant admin
-- via the platform setup page at /platform/setup.php (one-time, then delete it)
