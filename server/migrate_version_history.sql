-- ============================================================
--  GEAMH HRIS — Version History Migration
--  Extends audit_logs.module ENUM to cover all tracked modules
--  Safe to run multiple times
-- ============================================================

USE geamh_hris;

-- Extend the module ENUM to include Training (was missing)
ALTER TABLE `audit_logs`
  MODIFY COLUMN `module`
    ENUM('DTR','Leave','Payroll','Employee','T.O.','Auth',
         'Schedule','Training','Tracking','Signatory','Department','Account','Other')
    NOT NULL DEFAULT 'Other';

-- Ensure action_type column exists (from previous migration)
ALTER TABLE `audit_logs`
  ADD COLUMN IF NOT EXISTS `action_type`
    ENUM('LOGIN','LOGOUT','CREATE','UPDATE','DELETE','VIEW','EXPORT','OTHER')
    NOT NULL DEFAULT 'OTHER' AFTER `action`,
  ADD COLUMN IF NOT EXISTS `affected_table` VARCHAR(60)  DEFAULT NULL AFTER `module`,
  ADD COLUMN IF NOT EXISTS `record_id`      INT UNSIGNED DEFAULT NULL AFTER `affected_table`,
  ADD COLUMN IF NOT EXISTS `old_values`     JSON         DEFAULT NULL AFTER `record_id`,
  ADD COLUMN IF NOT EXISTS `new_values`     JSON         DEFAULT NULL AFTER `old_values`,
  ADD COLUMN IF NOT EXISTS `ip_address`     VARCHAR(45)  DEFAULT NULL AFTER `new_values`;

-- Indexes
ALTER TABLE `audit_logs`
  ADD INDEX IF NOT EXISTS `idx_log_action_type`    (`action_type`),
  ADD INDEX IF NOT EXISTS `idx_log_affected_table` (`affected_table`),
  ADD INDEX IF NOT EXISTS `idx_log_record_id`      (`record_id`);
