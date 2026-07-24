-- 004_icp_extra_fields.sql
-- Adds revenue_range and buying_process columns to kb_icps.
-- MySQL 5.7+ compatible. Safe to re-run.

DROP PROCEDURE IF EXISTS ise_migrate_004;
DELIMITER //
CREATE PROCEDURE ise_migrate_004()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'kb_icps'
      AND COLUMN_NAME  = 'revenue_range'
  ) THEN
    ALTER TABLE `kb_icps` ADD COLUMN `revenue_range` VARCHAR(255) AFTER `size_range`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'kb_icps'
      AND COLUMN_NAME  = 'buying_process'
  ) THEN
    ALTER TABLE `kb_icps` ADD COLUMN `buying_process` TEXT AFTER `disqualifiers`;
  END IF;
END //
DELIMITER ;
CALL ise_migrate_004();
DROP PROCEDURE IF EXISTS ise_migrate_004;
