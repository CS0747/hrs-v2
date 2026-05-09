-- Add file_path column to ai_scanned_docs if not exists
USE geamh_hris;
ALTER TABLE `ai_scanned_docs`
  ADD COLUMN IF NOT EXISTS `file_path` VARCHAR(500) DEFAULT NULL COMMENT 'Relative path on server' AFTER `file_size`;
