-- 001_initial_schema.sql
-- Original 4 tables for a fresh ISE install.
-- Run this first, then run 002_knowledge_base.sql.

CREATE TABLE IF NOT EXISTS `companies` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `name`         VARCHAR(255) NOT NULL,
  `url`          VARCHAR(500),
  `industry`     VARCHAR(100),
  `country`      VARCHAR(100),
  `score`        INT DEFAULT 0,
  `priority`     ENUM('High','Medium','Low') DEFAULT 'Low',
  `signal_count` INT DEFAULT 0,
  `top_signal`   VARCHAR(100),
  `signal_types` VARCHAR(500),
  `tech_stack`   TEXT,
  `enriched_at`  DATETIME,
  `status`       ENUM('pending','enriched','outreach','done') DEFAULT 'pending',
  `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `signals` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `company_id`     INT NOT NULL,
  `title`          VARCHAR(500),
  `snippet`        TEXT,
  `url`            VARCHAR(1000),
  `published_date` VARCHAR(100),
  `source`         VARCHAR(50),
  `created_at`     DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `company_tech` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `company_id`   INT NOT NULL,
  `tool`         VARCHAR(100),
  `category`     VARCHAR(50),
  `confidence`   INT DEFAULT 60,
  `source_url`   VARCHAR(1000),
  `source_title` VARCHAR(500),
  `detected_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `company_tool` (`company_id`, `tool`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `email_drafts` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `subject`    VARCHAR(500),
  `body`       TEXT,
  `angle`      VARCHAR(100),
  `status`     ENUM('draft','sent','replied') DEFAULT 'draft',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;
