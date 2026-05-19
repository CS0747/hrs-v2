-- Add Holiday legend and Philippine holidays table
-- Created: 2026-05-19

-- ============================================================================
-- 1. Add Holiday (H) legend to shift_legends
-- ============================================================================
INSERT INTO shift_legends (code, department, time_range, color_primary, color_secondary, display_order, active)
VALUES
  ('H', NULL, 'Holiday', '#F44336', NULL, 3, TRUE)
ON DUPLICATE KEY UPDATE
  time_range = VALUES(time_range),
  color_primary = VALUES(color_primary),
  display_order = VALUES(display_order),
  active = VALUES(active);

-- ============================================================================
-- 2. Create holidays table
-- ============================================================================
CREATE TABLE IF NOT EXISTS holidays (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(200) NOT NULL,
  date DATE NOT NULL,
  type ENUM('Regular', 'Special Non-Working') DEFAULT 'Regular',
  recurring BOOLEAN DEFAULT TRUE COMMENT 'TRUE if holiday repeats yearly',
  active BOOLEAN DEFAULT TRUE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  UNIQUE KEY unique_date (date),
  INDEX idx_date (date),
  INDEX idx_type (type),
  INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. Populate Philippine holidays (2026)
-- ============================================================================
INSERT INTO holidays (name, date, type, recurring, active)
VALUES
  -- Regular Holidays
  ('New Year''s Day', '2026-01-01', 'Regular', TRUE, TRUE),
  ('Maundy Thursday', '2026-04-02', 'Regular', FALSE, TRUE),
  ('Good Friday', '2026-04-03', 'Regular', FALSE, TRUE),
  ('Araw ng Kagitingan (Day of Valor)', '2026-04-09', 'Regular', TRUE, TRUE),
  ('Labor Day', '2026-05-01', 'Regular', TRUE, TRUE),
  ('Independence Day', '2026-06-12', 'Regular', TRUE, TRUE),
  ('Ninoy Aquino Day', '2026-08-21', 'Special Non-Working', TRUE, TRUE),
  ('National Heroes Day', '2026-08-31', 'Regular', FALSE, TRUE),
  ('All Saints'' Day', '2026-11-01', 'Special Non-Working', TRUE, TRUE),
  ('Bonifacio Day', '2026-11-30', 'Regular', TRUE, TRUE),
  ('Feast of the Immaculate Conception of Mary', '2026-12-08', 'Special Non-Working', TRUE, TRUE),
  ('Christmas Day', '2026-12-25', 'Regular', TRUE, TRUE),
  ('Rizal Day', '2026-12-30', 'Regular', TRUE, TRUE),
  ('Last Day of the Year', '2026-12-31', 'Special Non-Working', TRUE, TRUE),
  
  -- Special Non-Working Days
  ('EDSA People Power Revolution Anniversary', '2026-02-25', 'Special Non-Working', TRUE, TRUE),
  ('Black Saturday', '2026-04-04', 'Special Non-Working', FALSE, TRUE),
  ('Eid al-Fitr', '2026-04-20', 'Regular', FALSE, TRUE),
  ('Eid al-Adha', '2026-06-27', 'Regular', FALSE, TRUE),
  ('All Souls'' Day', '2026-11-02', 'Special Non-Working', TRUE, TRUE),
  ('Christmas Eve', '2026-12-24', 'Special Non-Working', TRUE, TRUE)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  type = VALUES(type),
  recurring = VALUES(recurring),
  active = VALUES(active);

SELECT 'Holiday legend and Philippine holidays added successfully' AS status;
