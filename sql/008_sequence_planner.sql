-- 008: Sequence planner columns for ai_settings and email_drafts
ALTER TABLE `ai_settings`
  ADD COLUMN `default_stage` ENUM('auto','tof','mof','bof') DEFAULT 'auto',
  ADD COLUMN `tof_score_max` INT DEFAULT 40,
  ADD COLUMN `bof_score_min` INT DEFAULT 71,
  ADD COLUMN `tof_sequence` TEXT,
  ADD COLUMN `mof_sequence` TEXT,
  ADD COLUMN `bof_sequence` TEXT;

ALTER TABLE `email_drafts`
  ADD COLUMN `funnel_stage` ENUM('tof','mof','bof'),
  ADD COLUMN `sequence_mode` ENUM('auto','manual') DEFAULT 'auto',
  ADD COLUMN `touch_intent` VARCHAR(50);
