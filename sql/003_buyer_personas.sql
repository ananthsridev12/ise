-- 003_buyer_personas.sql
-- Adds kb_personas table (Block 5: Buyer Personas).
-- MySQL 5.7+ compatible. Safe to re-run.

CREATE TABLE IF NOT EXISTS `kb_personas` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `name`                VARCHAR(255) NOT NULL,
  `title`               VARCHAR(255),
  `department`          VARCHAR(255),
  `seniority`           ENUM('C-Suite','VP','Director','Manager','Individual Contributor') DEFAULT 'Director',
  `vertical_id`         INT,
  `service_id`          INT,
  `reporting_to`        VARCHAR(255),
  `goals`               TEXT,
  `pain_points`         TEXT,
  `objections`          TEXT,
  `kpis`                VARCHAR(500),
  `decision_role`       ENUM('Economic Buyer','Champion','Technical Buyer','End User','Influencer','Blocker') DEFAULT 'Champion',
  `communication_style` TEXT,
  `preferred_content`   VARCHAR(500),
  `watering_holes`      VARCHAR(500),
  `email_hook`          TEXT,
  `created_at`          DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`vertical_id`) REFERENCES `kb_verticals`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`service_id`) REFERENCES `kb_services`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;
