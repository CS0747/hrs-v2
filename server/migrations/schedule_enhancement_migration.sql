-- Schedule Management System Enhancement - Database Migration
-- Created: 2026-05-18
-- Purpose: Add calendar-based scheduling with shift legends and transmittals

-- ============================================================================
-- 1. Create shift_legends table
-- ============================================================================
CREATE TABLE IF NOT EXISTS shift_legends (
  id INT PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(10) NOT NULL,
  department VARCHAR(100) DEFAULT NULL COMMENT 'NULL for standard legends, specific for dept-specific',
  time_range VARCHAR(50) NOT NULL COMMENT 'e.g., "6:00 AM - 2:00 PM"',
  color_primary VARCHAR(7) NOT NULL COMMENT 'Hex color #RRGGBB',
  color_secondary VARCHAR(7) DEFAULT NULL COMMENT 'For split shifts (610, 26)',
  display_order INT DEFAULT 0,
  active BOOLEAN DEFAULT TRUE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  UNIQUE KEY unique_code_dept (code, department),
  INDEX idx_department (department),
  INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. Add new columns to schedules table
-- ============================================================================
ALTER TABLE schedules
  ADD COLUMN IF NOT EXISTS schedule_date DATE DEFAULT NULL COMMENT 'Specific date for this schedule',
  ADD COLUMN IF NOT EXISTS start_time TIME DEFAULT NULL COMMENT 'Shift start time',
  ADD COLUMN IF NOT EXISTS end_time TIME DEFAULT NULL COMMENT 'Shift end time',
  ADD COLUMN IF NOT EXISTS shift_code VARCHAR(10) DEFAULT NULL COMMENT '62, 210, 106, 610, 26, 85, OFF',
  ADD COLUMN IF NOT EXISTS shift_name VARCHAR(50) DEFAULT NULL COMMENT 'Morning, Evening, Night, Custom, OFF',
  ADD COLUMN IF NOT EXISTS status ENUM('Submitted', 'Pending', 'Missing') DEFAULT 'Pending',
  ADD COLUMN IF NOT EXISTS submitted_date DATETIME DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  ADD COLUMN IF NOT EXISTS created_by INT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS remarks TEXT DEFAULT NULL;

-- Add foreign key for created_by if users table exists
ALTER TABLE schedules
  ADD CONSTRAINT fk_schedules_created_by 
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

-- ============================================================================
-- 3. Add indexes for performance
-- ============================================================================
ALTER TABLE schedules
  ADD INDEX IF NOT EXISTS idx_schedule_date (schedule_date),
  ADD INDEX IF NOT EXISTS idx_employee_dept (employee_id, department),
  ADD INDEX IF NOT EXISTS idx_shift_code (shift_code),
  ADD INDEX IF NOT EXISTS idx_status (status);

-- ============================================================================
-- 4. Create schedule_transmittals table
-- ============================================================================
CREATE TABLE IF NOT EXISTS schedule_transmittals (
  id INT PRIMARY KEY AUTO_INCREMENT,
  department VARCHAR(100) NOT NULL,
  period_start DATE NOT NULL,
  period_end DATE NOT NULL,
  page_number INT DEFAULT 1,
  staff_count INT DEFAULT 0,
  submitted_count INT DEFAULT 0,
  date_submitted DATE DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  generated_by INT DEFAULT NULL,
  generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  
  INDEX idx_department (department),
  INDEX idx_period (period_start, period_end),
  FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. Populate shift_legends with standard legends
-- ============================================================================
INSERT INTO shift_legends (code, department, time_range, color_primary, color_secondary, display_order, active)
VALUES
  -- Standard legends (department = NULL)
  ('85', NULL, '8:00 AM - 5:00 PM', '#000000', NULL, 1, TRUE),
  ('OFF', NULL, 'Off Duty', '#F44336', NULL, 2, TRUE),
  
  -- Nursing department legends
  ('62', 'Nursing', '6:00 AM - 2:00 PM', '#2196F3', NULL, 1, TRUE),
  ('210', 'Nursing', '2:00 PM - 10:00 PM', '#4CAF50', NULL, 2, TRUE),
  ('106', 'Nursing', '10:00 PM - 6:00 AM', '#F44336', NULL, 3, TRUE),
  ('610', 'Nursing', '6:00 AM - 10:00 PM', '#2196F3', '#4CAF50', 4, TRUE),
  ('26', 'Nursing', '2:00 PM - 6:00 AM', '#4CAF50', '#F44336', 5, TRUE),
  ('85', 'Nursing', '8:00 AM - 5:00 PM', '#000000', NULL, 6, TRUE),
  ('OFF', 'Nursing', 'Off Duty', '#F44336', NULL, 7, TRUE)
ON DUPLICATE KEY UPDATE
  time_range = VALUES(time_range),
  color_primary = VALUES(color_primary),
  color_secondary = VALUES(color_secondary),
  display_order = VALUES(display_order),
  active = VALUES(active);

-- ============================================================================
-- 6. Migration verification queries
-- ============================================================================
-- Verify shift_legends table
SELECT 'shift_legends table created' AS status, COUNT(*) AS legend_count FROM shift_legends;

-- Verify schedules table columns
SELECT 'schedules table enhanced' AS status, 
       COUNT(*) AS total_schedules,
       COUNT(schedule_date) AS schedules_with_date,
       COUNT(shift_code) AS schedules_with_code
FROM schedules;

-- Verify schedule_transmittals table
SELECT 'schedule_transmittals table created' AS status, COUNT(*) AS transmittal_count FROM schedule_transmittals;

-- ============================================================================
-- 7. Rollback script (if needed)
-- ============================================================================
-- To rollback this migration, run:
-- DROP TABLE IF EXISTS schedule_transmittals;
-- DROP TABLE IF EXISTS shift_legends;
-- ALTER TABLE schedules DROP COLUMN IF EXISTS schedule_date;
-- ALTER TABLE schedules DROP COLUMN IF EXISTS start_time;
-- ALTER TABLE schedules DROP COLUMN IF EXISTS end_time;
-- ALTER TABLE schedules DROP COLUMN IF EXISTS shift_code;
-- ALTER TABLE schedules DROP COLUMN IF EXISTS shift_name;
-- ALTER TABLE schedules DROP COLUMN IF EXISTS status;
-- ALTER TABLE schedules DROP COLUMN IF EXISTS submitted_date;
-- ALTER TABLE schedules DROP COLUMN IF EXISTS last_updated;
-- ALTER TABLE schedules DROP COLUMN IF EXISTS created_by;
-- ALTER TABLE schedules DROP COLUMN IF EXISTS remarks;
-- ALTER TABLE schedules DROP INDEX IF EXISTS idx_schedule_date;
-- ALTER TABLE schedules DROP INDEX IF EXISTS idx_employee_dept;
-- ALTER TABLE schedules DROP INDEX IF EXISTS idx_shift_code;
-- ALTER TABLE schedules DROP INDEX IF EXISTS idx_status;
