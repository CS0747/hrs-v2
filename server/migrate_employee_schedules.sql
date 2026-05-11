-- ============================================================
-- Employee Schedules Table Migration
-- For storing duty schedules from AI scanning
-- ============================================================

USE `geamh_hris`;

CREATE TABLE IF NOT EXISTS `employee_schedules` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `employee_id`   INT UNSIGNED    DEFAULT NULL,
  `employee_no`   VARCHAR(20)     NOT NULL,
  `employee_name` VARCHAR(150)    NOT NULL,
  `department`    VARCHAR(100)    DEFAULT NULL,
  `period`        VARCHAR(50)     NOT NULL COMMENT 'e.g. May 2026, June 2026',
  `schedule_data` JSON            DEFAULT NULL COMMENT 'Array of 31 days schedule codes',
  `work_days`     INT             DEFAULT 0 COMMENT 'Total working days in period',
  `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_employee_no` (`employee_no`),
  KEY `idx_period` (`period`),
  KEY `idx_department` (`department`),
  UNIQUE KEY `unique_employee_period` (`employee_id`, `period`, `department`),
  
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Employee duty schedules from AI scanning';

-- Sample data for testing
INSERT IGNORE INTO `employee_schedules` 
(`employee_id`, `employee_no`, `employee_name`, `department`, `period`, `schedule_data`, `work_days`) 
VALUES
(NULL, 'GEAMH-001', 'DELA CRUZ, JUAN A.', 'Information Technology', 'May 2026', 
 '["85","85","O","O","85","85","85","85","O","O","85","85","85","85","O","O","85","85","85","85","O","O","85","85","85","85","O","O","85","85","O"]', 
 20),
(NULL, 'GEAMH-002', 'SANTOS, MARIA B.', 'Nursing', 'May 2026',
 '["O","85","85","85","85","O","O","85","85","85","85","O","O","85","85","85","85","O","O","85","85","85","85","O","O","85","85","85","85","O","O"]',
 20);

SELECT * FROM `employee_schedules` ORDER BY `created_at` DESC LIMIT 10;
