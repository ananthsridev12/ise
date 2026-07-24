-- Migration 005: kb_proof (Block 8) and kb_documents (Block 9)
-- Run in phpMyAdmin SQL tab after 004_icp_extra_fields.sql

CREATE TABLE IF NOT EXISTS `kb_proof` (
  `id`                INT AUTO_INCREMENT PRIMARY KEY,
  `client_name`       VARCHAR(255) NOT NULL,
  `client_industry`   VARCHAR(255),
  `client_size`       VARCHAR(255),
  `vertical_id`       INT,
  `service_id`        INT,
  `challenge`         TEXT,
  `solution`          TEXT,
  `outcomes`          TEXT,
  `metrics`           VARCHAR(500),
  `quote`             TEXT,
  `quote_attribution` VARCHAR(255),
  `is_public`         TINYINT(1) DEFAULT 1,
  `created_at`        DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`vertical_id`) REFERENCES `kb_verticals`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`service_id`) REFERENCES `kb_services`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `kb_documents` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `title`       VARCHAR(255) NOT NULL,
  `doc_type`    ENUM('case_study','whitepaper','brochure','deck','one_pager','roi_calculator','video','other') DEFAULT 'other',
  `url`         VARCHAR(1000),
  `description` TEXT,
  `use_case`    TEXT,
  `vertical_id` INT,
  `service_id`  INT,
  `is_public`   TINYINT(1) DEFAULT 1,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`vertical_id`) REFERENCES `kb_verticals`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`service_id`) REFERENCES `kb_services`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;
