CREATE TABLE IF NOT EXISTS `kb_assets` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id`    INT NOT NULL DEFAULT 1,
  `service_id`   INT,
  `vertical_id`  INT,
  `category`     ENUM('content','tool','entry_door') NOT NULL DEFAULT 'content',
  `asset_type`   ENUM('pdf','guide','whitepaper','assessment','calculator','diagnostic','checklist','template','webinar','free_audit','poc','pilot','consultation','other') DEFAULT 'pdf',
  `name`         VARCHAR(255) NOT NULL,
  `description`  TEXT,
  `url`          VARCHAR(1000),
  `cta_text`     VARCHAR(500),
  `use_in_touch` VARCHAR(20) DEFAULT '2',
  `target_stage` ENUM('awareness','consideration','decision') DEFAULT 'consideration',
  `is_active`    TINYINT(1) DEFAULT 1,
  `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`service_id`)  REFERENCES `kb_services`(`id`)  ON DELETE SET NULL,
  FOREIGN KEY (`vertical_id`) REFERENCES `kb_verticals`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;
