-- ISE — Full Install Schema
-- Import this for a fresh install. For existing installs use sql/ migrations.

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
  `id`                 INT AUTO_INCREMENT PRIMARY KEY,
  `company_id`         INT NOT NULL,
  `subject`            VARCHAR(500),
  `body`               TEXT,
  `angle`              VARCHAR(100),
  `touch_number`       INT DEFAULT 1,
  `ai_provider`        VARCHAR(50),
  `ai_model`           VARCHAR(100),
  `matched_service_id` INT,
  `prompt_context`     TEXT,
  `status`             ENUM('draft','sent','replied') DEFAULT 'draft',
  `created_at`         DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `kb_company` (
  `id`                    INT AUTO_INCREMENT PRIMARY KEY,
  `name`                  VARCHAR(255),
  `tagline`               VARCHAR(500),
  `website`               VARCHAR(255),
  `founded_year`          VARCHAR(10),
  `size`                  VARCHAR(100),
  `hq`                    VARCHAR(255),
  `mission`               TEXT,
  `vision`                TEXT,
  `story`                 TEXT,
  `credibility_statement` TEXT,
  `notable_clients`       TEXT,
  `awards`                TEXT,
  `updated_at`            DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `kb_verticals` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `name`            VARCHAR(255) NOT NULL,
  `focus`           TEXT,
  `industries`      TEXT,
  `priority`        ENUM('core','growth','emerging') DEFAULT 'core',
  `differentiators` TEXT,
  `head_name`       VARCHAR(255),
  `positioning`     TEXT,
  `created_at`      DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `kb_services` (
  `id`                INT AUTO_INCREMENT PRIMARY KEY,
  `vertical_id`       INT,
  `name`              VARCHAR(255) NOT NULL,
  `one_liner`         VARCHAR(500),
  `industries`        TEXT,
  `icp_size`          VARCHAR(255),
  `buyer_titles`      TEXT,
  `engagement_model`  VARCHAR(100),
  `signal_keywords`   TEXT,
  `signal_types`      TEXT,
  `tech_triggers`     TEXT,
  `competing_tools`   TEXT,
  `description`       TEXT,
  `problem_statement` TEXT,
  `outcomes`          TEXT,
  `differentiators`   TEXT,
  `proof_points`      TEXT,
  `created_at`        DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`vertical_id`) REFERENCES `kb_verticals`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `kb_icps` (
  `id`                 INT AUTO_INCREMENT PRIMARY KEY,
  `name`               VARCHAR(255) NOT NULL,
  `vertical_id`        INT,
  `service_id`         INT,
  `size_range`         VARCHAR(255),
  `revenue_range`      VARCHAR(255),
  `industries`         TEXT,
  `geographies`        TEXT,
  `tech_stack_signals` TEXT,
  `trigger_events`     TEXT,
  `perfect_fit`        TEXT,
  `poor_fit`           TEXT,
  `disqualifiers`      TEXT,
  `buying_process`     TEXT,
  `created_at`         DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`vertical_id`) REFERENCES `kb_verticals`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`service_id`) REFERENCES `kb_services`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

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

CREATE TABLE IF NOT EXISTS `kb_tone` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `tone_descriptors`    VARCHAR(500),
  `anti_tone`           VARCHAR(500),
  `words_always`        TEXT,
  `words_never`         TEXT,
  `email_opening_style` TEXT,
  `cta_style`           TEXT,
  `email_length`        ENUM('short','medium','long') DEFAULT 'medium',
  `paragraph_style`     ENUM('one-liners','full-paragraphs','bullet-heavy') DEFAULT 'full-paragraphs',
  `good_example`        TEXT,
  `bad_example`         TEXT,
  `updated_at`          DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `kb_senders` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `full_name`           VARCHAR(255),
  `title`               VARCHAR(255),
  `email`               VARCHAR(255),
  `linkedin_url`        VARCHAR(500),
  `background`          TEXT,
  `credibility`         TEXT,
  `years_experience`    INT,
  `individual_tone`     TEXT,
  `email_opening_style` TEXT,
  `email_closing_style` TEXT,
  `verticals`           TEXT,
  `calendar_link`       VARCHAR(500),
  `signature`           TEXT,
  `example_emails`      TEXT,
  `is_default`          TINYINT(1) DEFAULT 0,
  `created_at`          DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `ai_settings` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `provider`            ENUM('gemini','claude','openai') DEFAULT 'gemini',
  `gemini_key`          VARCHAR(500),
  `claude_key`          VARCHAR(500),
  `openai_key`          VARCHAR(500),
  `model`               VARCHAR(100),
  `email_length`        ENUM('short','medium','long') DEFAULT 'medium',
  `num_touches`         INT DEFAULT 3,
  `touch_intervals`     VARCHAR(255) DEFAULT '0,3,7',
  `custom_instructions` TEXT,
  `updated_at`          DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

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

-- Auth tables (from sql/004_auth.sql)
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
