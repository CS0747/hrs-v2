-- ============================================================
--  GEAMH HRIS — Complete Database Schema
--  Copy and paste this entire file into phpMyAdmin > SQL tab
--  Database: geamh_hris
-- ============================================================

CREATE DATABASE IF NOT EXISTS `geamh_hris`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `geamh_hris`;

-- ============================================================
-- 1. USERS  (authentication / system accounts)
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `username`    VARCHAR(50)     NOT NULL UNIQUE,
  `password`    VARCHAR(64)     NOT NULL COMMENT 'SHA-256 hash',
  `name`        VARCHAR(100)    NOT NULL,
  `role`        ENUM('Super Admin','Admin') NOT NULL DEFAULT 'Admin',
  `department`  VARCHAR(100)    NOT NULL DEFAULT 'Human Resources',
  `active`      TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default accounts (passwords are SHA-256 hashed)
-- superadmin123 → SHA2('superadmin123', 256)
-- admin123      → SHA2('admin123', 256)
INSERT IGNORE INTO `users` (`id`, `username`, `password`, `name`, `role`, `department`) VALUES
(1, 'superadmin', SHA2('superadmin123', 256), 'Super Admin', 'Super Admin', 'Human Resources'),
(2, 'admin',      SHA2('admin123', 256),      'HR Admin',    'Admin',       'Human Resources');


-- ============================================================
-- 2. EMPLOYEES  (masterlist)
-- ============================================================
CREATE TABLE IF NOT EXISTS `employees` (
  `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `employee_no`       VARCHAR(20)     NOT NULL UNIQUE COMMENT 'e.g. GEAMH-001',
  `last_name`         VARCHAR(60)     NOT NULL,
  `first_name`        VARCHAR(60)     NOT NULL,
  `middle_name`       VARCHAR(60)     DEFAULT NULL,
  `position`          VARCHAR(100)    DEFAULT NULL,
  `designation`       VARCHAR(100)    DEFAULT NULL,
  `department`        VARCHAR(100)    DEFAULT NULL,
  `employment_status` ENUM('Permanent','Casual','Contractual','Job Order','Co-terminus')
                                      NOT NULL DEFAULT 'Casual',
  `date_hired`        DATE            DEFAULT NULL,
  `birth_date`        DATE            DEFAULT NULL,
  `age`               TINYINT UNSIGNED DEFAULT NULL,
  `gender`            ENUM('Male','Female','') DEFAULT '',
  `civil_status`      ENUM('Single','Married','Widowed','Separated','') DEFAULT '',
  `address`           VARCHAR(255)    DEFAULT NULL,
  `contact_no`        VARCHAR(15)     DEFAULT NULL,
  `email`             VARCHAR(100)    DEFAULT NULL,
  `salary`            DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
  `sg_step`           VARCHAR(20)     DEFAULT NULL COMMENT 'Salary Grade / Step',
  `tin_number`        VARCHAR(20)     DEFAULT NULL,
  `sss_gsis_number`   VARCHAR(25)     DEFAULT NULL,
  `phil_number`       VARCHAR(25)     DEFAULT NULL COMMENT 'PhilHealth number',
  `pi_number`         VARCHAR(25)     DEFAULT NULL COMMENT 'Pag-IBIG number',
  `active`            TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_emp_no`   (`employee_no`),
  KEY `idx_dept`     (`department`),
  KEY `idx_status`   (`employment_status`),
  KEY `idx_bdate`    (`birth_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
-- 3. LEAVE RECORDS
-- ============================================================
CREATE TABLE IF NOT EXISTS `leave_records` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `employee_id`   INT UNSIGNED  DEFAULT NULL,
  `employee_no`   VARCHAR(20)   NOT NULL,
  `employee_name` VARCHAR(150)  NOT NULL,
  `department`    VARCHAR(100)  DEFAULT NULL,
  `leave_type`    ENUM(
    'Vacation Leave','Sick Leave','Maternity Leave','Paternity Leave',
    'Special Leave','Emergency Leave','Forced Leave','Study Leave',
    'Rehabilitation Leave','Terminal Leave'
  ) NOT NULL DEFAULT 'Vacation Leave',
  `date_from`     DATE          NOT NULL,
  `date_to`       DATE          NOT NULL,
  `days`          DECIMAL(5,1)  NOT NULL DEFAULT 1,
  `reason`        TEXT          DEFAULT NULL,
  `status`        ENUM('Pending','Approved','Disapproved','Cancelled')
                                NOT NULL DEFAULT 'Pending',
  `approved_by`   VARCHAR(100)  DEFAULT NULL,
  `date_approved` DATE          DEFAULT NULL,
  `remarks`       TEXT          DEFAULT NULL,
  `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_leave_emp`    (`employee_no`),
  KEY `idx_leave_status` (`status`),
  KEY `idx_leave_dates`  (`date_from`, `date_to`),
  CONSTRAINT `fk_leave_emp` FOREIGN KEY (`employee_id`)
    REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
-- 4. DTR RECORDS  (Daily Time Record / Transmittal)
-- ============================================================
CREATE TABLE IF NOT EXISTS `dtr_records` (
  `id`                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `employee_id`       INT UNSIGNED  DEFAULT NULL,
  `employee_no`       VARCHAR(20)   NOT NULL,
  `employee_name`     VARCHAR(150)  NOT NULL,
  `department`        VARCHAR(100)  DEFAULT NULL,
  `period`            VARCHAR(50)   NOT NULL COMMENT 'e.g. April 1-15, 2026',
  `transmittal_type`  ENUM('Main','Thea','Other') NOT NULL DEFAULT 'Main',
  `submitted_by`      VARCHAR(100)  DEFAULT NULL,
  `date_submitted`    DATE          DEFAULT NULL,
  `date_received`     DATE          DEFAULT NULL,
  `verified_by`       VARCHAR(100)  DEFAULT NULL,
  `verification_date` DATE          DEFAULT NULL,
  `status`            ENUM('Pending','Submitted','Received','Verified','Returned')
                                    NOT NULL DEFAULT 'Pending',
  `remarks`           TEXT          DEFAULT NULL,
  `created_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dtr_emp`    (`employee_no`),
  KEY `idx_dtr_status` (`status`),
  KEY `idx_dtr_period` (`period`),
  CONSTRAINT `fk_dtr_emp` FOREIGN KEY (`employee_id`)
    REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- DTR signatories junction (which signatories are required / have signed)
-- Created AFTER signatories table so FKs resolve correctly
CREATE TABLE IF NOT EXISTS `dtr_signatories` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dtr_id`       INT UNSIGNED NOT NULL,
  `signatory_id` INT UNSIGNED NOT NULL,
  `signed`       TINYINT(1)   NOT NULL DEFAULT 0,
  `signed_at`    DATETIME     DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dtr_sig` (`dtr_id`, `signatory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
-- 5. PAYROLL RECORDS
-- ============================================================
CREATE TABLE IF NOT EXISTS `payroll_records` (
  `id`               INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `employee_id`      INT UNSIGNED   DEFAULT NULL,
  `employee_no`      VARCHAR(20)    NOT NULL,
  `employee_name`    VARCHAR(150)   NOT NULL,
  `position`         VARCHAR(100)   DEFAULT NULL,
  `department`       VARCHAR(100)   DEFAULT NULL,
  `period`           VARCHAR(10)    NOT NULL COMMENT 'YYYY-MM format',
  `period_label`     VARCHAR(50)    DEFAULT NULL COMMENT 'e.g. April 2026',
  -- Earnings
  `basic_salary`     DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  `pera`             DECIMAL(10,2)  NOT NULL DEFAULT 2000.00,
  `rata`             DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `overtime`         DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `night_diff`       DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `gross_pay`        DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  -- Deductions
  `withholding_tax`  DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `gsis`             DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `philhealth`       DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `pagibig`          DECIMAL(10,2)  NOT NULL DEFAULT 100.00,
  `total_deductions` DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  `net_pay`          DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  -- Meta
  `status`           ENUM('Pending','Released','On Hold') NOT NULL DEFAULT 'Pending',
  `remarks`          TEXT           DEFAULT NULL,
  `created_at`       TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pay_emp`    (`employee_no`),
  KEY `idx_pay_period` (`period`),
  KEY `idx_pay_status` (`status`),
  CONSTRAINT `fk_pay_emp` FOREIGN KEY (`employee_id`)
    REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
-- 6. SCHEDULES
-- ============================================================
CREATE TABLE IF NOT EXISTS `schedules` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `employee_id`    INT UNSIGNED  DEFAULT NULL,
  `employee_no`    VARCHAR(20)   NOT NULL,
  `employee_name`  VARCHAR(150)  NOT NULL,
  `department`     VARCHAR(100)  DEFAULT NULL,
  `shift`          ENUM('Morning','Afternoon','Night','Split','Flexible')
                                 NOT NULL DEFAULT 'Morning',
  `shift_time`     VARCHAR(60)   DEFAULT NULL COMMENT 'e.g. 07:00 AM - 03:00 PM',
  `days`           JSON          NOT NULL COMMENT 'Array of day abbreviations e.g. ["Mon","Tue"]',
  `effective_date` DATE          DEFAULT NULL,
  `end_date`       DATE          DEFAULT NULL,
  `rest_day`       VARCHAR(60)   DEFAULT 'Saturday, Sunday',
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sched_emp`  (`employee_no`),
  KEY `idx_sched_dept` (`department`),
  CONSTRAINT `fk_sched_emp` FOREIGN KEY (`employee_id`)
    REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
-- 7. TRAVEL ORDERS
-- ============================================================
CREATE TABLE IF NOT EXISTS `travel_orders` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `employee_id`   INT UNSIGNED  DEFAULT NULL,
  `employee_no`   VARCHAR(20)   NOT NULL,
  `employee_name` VARCHAR(150)  NOT NULL,
  `department`    VARCHAR(100)  DEFAULT NULL,
  `destination`   VARCHAR(255)  NOT NULL,
  `purpose`       TEXT          DEFAULT NULL,
  `date_from`     DATE          NOT NULL,
  `date_to`       DATE          NOT NULL,
  `days`          TINYINT       NOT NULL DEFAULT 1,
  `transport`     ENUM('Public Transport','Government Vehicle','Private Vehicle')
                                NOT NULL DEFAULT 'Public Transport',
  `approved_by`   VARCHAR(100)  DEFAULT NULL,
  `status`        ENUM('Pending','Approved','Disapproved') NOT NULL DEFAULT 'Pending',
  `remarks`       TEXT          DEFAULT NULL,
  `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_to_emp`    (`employee_no`),
  KEY `idx_to_status` (`status`),
  CONSTRAINT `fk_to_emp` FOREIGN KEY (`employee_id`)
    REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
-- 8. TRAININGS
-- ============================================================
CREATE TABLE IF NOT EXISTS `trainings` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `title`            VARCHAR(200)  NOT NULL,
  `category`         ENUM('Medical','Nursing','Administrative','Technical',
                          'Leadership','Safety','Other')
                                   NOT NULL DEFAULT 'Medical',
  `instructor`       VARCHAR(150)  DEFAULT NULL,
  `venue`            VARCHAR(200)  DEFAULT NULL,
  `date_from`        DATE          NOT NULL,
  `date_to`          DATE          DEFAULT NULL,
  `duration`         TINYINT       NOT NULL DEFAULT 1 COMMENT 'Number of days',
  `max_participants` SMALLINT      NOT NULL DEFAULT 30,
  `enrolled`         SMALLINT      NOT NULL DEFAULT 0,
  `status`           ENUM('Upcoming','Ongoing','Completed','Cancelled')
                                   NOT NULL DEFAULT 'Upcoming',
  `description`      TEXT          DEFAULT NULL,
  `created_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_train_status`   (`status`),
  KEY `idx_train_category` (`category`),
  KEY `idx_train_date`     (`date_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Training participants (which employees attended which training)
CREATE TABLE IF NOT EXISTS `training_participants` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `training_id` INT UNSIGNED NOT NULL,
  `employee_id` INT UNSIGNED NOT NULL,
  `attended`    TINYINT(1)   NOT NULL DEFAULT 0,
  `remarks`     VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_train_emp` (`training_id`, `employee_id`),
  CONSTRAINT `fk_tp_training` FOREIGN KEY (`training_id`)
    REFERENCES `trainings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tp_emp` FOREIGN KEY (`employee_id`)
    REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
-- 9. SIGNATORIES
-- ============================================================
CREATE TABLE IF NOT EXISTS `signatories` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(100)  NOT NULL,
  `position`      VARCHAR(100)  DEFAULT NULL,
  `role`          VARCHAR(100)  DEFAULT NULL COMMENT 'e.g. Final Approver, HR Approver',
  `department`    VARCHAR(100)  DEFAULT NULL,
  `signing_order` TINYINT       NOT NULL DEFAULT 1,
  `active`        TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sig_order` (`signing_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default signatories
INSERT IGNORE INTO `signatories` (`id`, `name`, `position`, `role`, `department`, `signing_order`, `active`) VALUES
(1, 'Dr. Maria Reyes',   'Chief of Hospital',         'Final Approver',  'Office of the Chief', 1, 1),
(2, 'Mr. Jose Santos',   'HR Officer IV',             'HR Approver',     'Human Resources',     2, 1),
(3, 'Ms. Ana Bautista',  'Administrative Officer V',  'Admin Approver',  'Administrative',      3, 1),
(4, 'Mr. Pedro Cruz',    'Accountant III',            'Finance Approver','Finance',             4, 1),
(5, 'Thea Villanueva',   'HR Clerk II',               'DTR Processor',   'Human Resources',     5, 1);


-- ============================================================
-- 10. DOCUMENT TRACKING & RECEIVING
-- ============================================================
CREATE TABLE IF NOT EXISTS `document_tracking` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `doc_type`       ENUM('DTR Transmittal','Leave Form','Travel Order',
                        'Payroll Document','Memorandum','Other')
                                 NOT NULL DEFAULT 'DTR Transmittal',
  `doc_no`         VARCHAR(50)   NOT NULL,
  `from_office`    VARCHAR(100)  NOT NULL,
  `to_office`      VARCHAR(100)  NOT NULL,
  `date_forwarded` DATE          NOT NULL,
  `date_received`  DATE          DEFAULT NULL,
  `received_by`    VARCHAR(100)  DEFAULT NULL,
  `status`         ENUM('Pending','In Transit','Received','Returned','Lost')
                                 NOT NULL DEFAULT 'Pending',
  `remarks`        TEXT          DEFAULT NULL,
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_track_docno`  (`doc_no`),
  KEY `idx_track_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
-- 11. AUDIT LOGS
-- ============================================================
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED  DEFAULT NULL,
  `user_name`  VARCHAR(100)  NOT NULL,
  `action`     VARCHAR(100)  NOT NULL COMMENT 'e.g. Login, DTR Received, Employee Added',
  `module`     ENUM('DTR','Leave','Payroll','Employee','T.O.','Auth',
                    'Schedule','Training','Tracking','Signatory','Other')
                             NOT NULL DEFAULT 'Other',
  `details`    TEXT          DEFAULT NULL,
  `status`     VARCHAR(20)   NOT NULL DEFAULT 'OK',
  `archived`   TINYINT(1)    NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_log_module`  (`module`),
  KEY `idx_log_user`    (`user_id`),
  KEY `idx_log_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
-- 12. AI SCANNED DOCUMENTS  (optional persistence)
-- ============================================================
CREATE TABLE IF NOT EXISTS `ai_scanned_docs` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `file_name`      VARCHAR(255)  NOT NULL,
  `doc_type`       ENUM('DTR','Leave Form','Payslip','Travel Order','Unknown')
                                 NOT NULL DEFAULT 'Unknown',
  `file_size`      VARCHAR(20)   DEFAULT NULL,
  `confidence`     TINYINT       NOT NULL DEFAULT 0 COMMENT 'OCR confidence 0-100',
  `extracted_data` JSON          DEFAULT NULL COMMENT 'Key-value pairs from OCR',
  `raw_text`       MEDIUMTEXT    DEFAULT NULL,
  `status`         ENUM('Processing','Processed','Review Needed')
                                 NOT NULL DEFAULT 'Processing',
  `uploaded_by`    INT UNSIGNED  DEFAULT NULL,
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_ai_user` FOREIGN KEY (`uploaded_by`)
    REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Add FKs to dtr_signatories now that both referenced tables exist
ALTER TABLE `dtr_signatories`
  ADD CONSTRAINT `fk_dtrsig_dtr` FOREIGN KEY (`dtr_id`)
    REFERENCES `dtr_records` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dtrsig_sig` FOREIGN KEY (`signatory_id`)
    REFERENCES `signatories` (`id`) ON DELETE CASCADE;

-- ============================================================
-- Done! All 14 tables created.
-- Tables: users, employees, leave_records, dtr_records,
--         dtr_signatories, payroll_records, schedules,
--         travel_orders, trainings, training_participants,
--         signatories, document_tracking, audit_logs,
--         ai_scanned_docs
-- ============================================================
