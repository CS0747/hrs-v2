-- ============================================================
--  GEAMH HRIS — Audit Logs Migration
--  Run in phpMyAdmin > geamh_hris > SQL tab
--  Adds old_values, new_values, affected_table, record_id
--  Safe to run multiple times (uses IF NOT EXISTS pattern)
-- ============================================================

USE geamh_hris;

-- Add new columns if they don't exist yet
ALTER TABLE `audit_logs`
  ADD COLUMN IF NOT EXISTS `action_type`     ENUM('LOGIN','LOGOUT','CREATE','UPDATE','DELETE','VIEW','EXPORT','OTHER')
                                              NOT NULL DEFAULT 'OTHER'
                                              COMMENT 'Standardised action category'
                                              AFTER `action`,
  ADD COLUMN IF NOT EXISTS `affected_table`  VARCHAR(60)  DEFAULT NULL
                                              COMMENT 'DB table affected e.g. employees'
                                              AFTER `module`,
  ADD COLUMN IF NOT EXISTS `record_id`       INT UNSIGNED DEFAULT NULL
                                              COMMENT 'PK of the affected row'
                                              AFTER `affected_table`,
  ADD COLUMN IF NOT EXISTS `old_values`      JSON         DEFAULT NULL
                                              COMMENT 'Snapshot before change'
                                              AFTER `record_id`,
  ADD COLUMN IF NOT EXISTS `new_values`      JSON         DEFAULT NULL
                                              COMMENT 'Snapshot after change'
                                              AFTER `old_values`,
  ADD COLUMN IF NOT EXISTS `ip_address`      VARCHAR(45)  DEFAULT NULL
                                              COMMENT 'Client IP (IPv4 or IPv6)'
                                              AFTER `new_values`;

-- Add indexes for the new columns
ALTER TABLE `audit_logs`
  ADD INDEX IF NOT EXISTS `idx_log_action_type`    (`action_type`),
  ADD INDEX IF NOT EXISTS `idx_log_affected_table` (`affected_table`),
  ADD INDEX IF NOT EXISTS `idx_log_record_id`      (`record_id`);
