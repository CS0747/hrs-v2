-- ============================================================
--  GEAMH HRIS — DTR Backend Migration
--  Run in phpMyAdmin > geamh_hris > SQL tab
-- ============================================================

USE geamh_hris;

-- DTR History log table (tracks every add/edit/delete action)
CREATE TABLE IF NOT EXISTS `dtr_history` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `dtr_record_id`    INT UNSIGNED  DEFAULT NULL COMMENT 'FK to dtr_records (nullable after delete)',
  `employee_no`      VARCHAR(20)   NOT NULL,
  `employee_name`    VARCHAR(150)  NOT NULL,
  `period`           VARCHAR(50)   NOT NULL,
  `transmittal_type` ENUM('Main','Thea','Other') NOT NULL DEFAULT 'Main',
  `action`           VARCHAR(60)   NOT NULL COMMENT 'e.g. DTR Submitted, DTR Updated, DTR Deleted',
  `status`           VARCHAR(30)   NOT NULL DEFAULT 'Pending',
  `remarks`          TEXT          DEFAULT NULL,
  `processed_by`     VARCHAR(100)  NOT NULL DEFAULT 'System',
  `created_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dtrh_emp`    (`employee_no`),
  KEY `idx_dtrh_action` (`action`),
  KEY `idx_dtrh_date`   (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
