-- Add shift legends for medical departments
-- This adds the nursing shift codes (62, 210, 106, 610, 26) to all medical-related departments

-- Medical Officer department
INSERT INTO shift_legends (code, department, time_range, color_primary, color_secondary, display_order, active)
VALUES
  ('62', 'Medical Officer', '6:00 AM - 2:00 PM', '#2196F3', NULL, 1, TRUE),
  ('210', 'Medical Officer', '2:00 PM - 10:00 PM', '#4CAF50', NULL, 2, TRUE),
  ('106', 'Medical Officer', '10:00 PM - 6:00 AM', '#F44336', NULL, 3, TRUE),
  ('610', 'Medical Officer', '6:00 AM - 10:00 PM', '#2196F3', '#4CAF50', 4, TRUE),
  ('26', 'Medical Officer', '2:00 PM - 6:00 AM', '#4CAF50', '#F44336', 5, TRUE),
  ('85', 'Medical Officer', '8:00 AM - 5:00 PM', '#000000', NULL, 6, TRUE),
  ('OFF', 'Medical Officer', 'Off Duty', '#F44336', NULL, 7, TRUE)
ON DUPLICATE KEY UPDATE
  time_range = VALUES(time_range),
  color_primary = VALUES(color_primary),
  color_secondary = VALUES(color_secondary),
  display_order = VALUES(display_order),
  active = VALUES(active);

-- Medical department
INSERT INTO shift_legends (code, department, time_range, color_primary, color_secondary, display_order, active)
VALUES
  ('62', 'Medical', '6:00 AM - 2:00 PM', '#2196F3', NULL, 1, TRUE),
  ('210', 'Medical', '2:00 PM - 10:00 PM', '#4CAF50', NULL, 2, TRUE),
  ('106', 'Medical', '10:00 PM - 6:00 AM', '#F44336', NULL, 3, TRUE),
  ('610', 'Medical', '6:00 AM - 10:00 PM', '#2196F3', '#4CAF50', 4, TRUE),
  ('26', 'Medical', '2:00 PM - 6:00 AM', '#4CAF50', '#F44336', 5, TRUE),
  ('85', 'Medical', '8:00 AM - 5:00 PM', '#000000', NULL, 6, TRUE),
  ('OFF', 'Medical', 'Off Duty', '#F44336', NULL, 7, TRUE)
ON DUPLICATE KEY UPDATE
  time_range = VALUES(time_range),
  color_primary = VALUES(color_primary),
  color_secondary = VALUES(color_secondary),
  display_order = VALUES(display_order),
  active = VALUES(active);

-- Emergency Room
INSERT INTO shift_legends (code, department, time_range, color_primary, color_secondary, display_order, active)
VALUES
  ('62', 'Emergency Room', '6:00 AM - 2:00 PM', '#2196F3', NULL, 1, TRUE),
  ('210', 'Emergency Room', '2:00 PM - 10:00 PM', '#4CAF50', NULL, 2, TRUE),
  ('106', 'Emergency Room', '10:00 PM - 6:00 AM', '#F44336', NULL, 3, TRUE),
  ('610', 'Emergency Room', '6:00 AM - 10:00 PM', '#2196F3', '#4CAF50', 4, TRUE),
  ('26', 'Emergency Room', '2:00 PM - 6:00 AM', '#4CAF50', '#F44336', 5, TRUE),
  ('85', 'Emergency Room', '8:00 AM - 5:00 PM', '#000000', NULL, 6, TRUE),
  ('OFF', 'Emergency Room', 'Off Duty', '#F44336', NULL, 7, TRUE)
ON DUPLICATE KEY UPDATE
  time_range = VALUES(time_range),
  color_primary = VALUES(color_primary),
  color_secondary = VALUES(color_secondary),
  display_order = VALUES(display_order),
  active = VALUES(active);

-- Pharmacy
INSERT INTO shift_legends (code, department, time_range, color_primary, color_secondary, display_order, active)
VALUES
  ('62', 'Pharmacy', '6:00 AM - 2:00 PM', '#2196F3', NULL, 1, TRUE),
  ('210', 'Pharmacy', '2:00 PM - 10:00 PM', '#4CAF50', NULL, 2, TRUE),
  ('106', 'Pharmacy', '10:00 PM - 6:00 AM', '#F44336', NULL, 3, TRUE),
  ('610', 'Pharmacy', '6:00 AM - 10:00 PM', '#2196F3', '#4CAF50', 4, TRUE),
  ('26', 'Pharmacy', '2:00 PM - 6:00 AM', '#4CAF50', '#F44336', 5, TRUE),
  ('85', 'Pharmacy', '8:00 AM - 5:00 PM', '#000000', NULL, 6, TRUE),
  ('OFF', 'Pharmacy', 'Off Duty', '#F44336', NULL, 7, TRUE)
ON DUPLICATE KEY UPDATE
  time_range = VALUES(time_range),
  color_primary = VALUES(color_primary),
  color_secondary = VALUES(color_secondary),
  display_order = VALUES(display_order),
  active = VALUES(active);

-- Laboratory
INSERT INTO shift_legends (code, department, time_range, color_primary, color_secondary, display_order, active)
VALUES
  ('62', 'Laboratory', '6:00 AM - 2:00 PM', '#2196F3', NULL, 1, TRUE),
  ('210', 'Laboratory', '2:00 PM - 10:00 PM', '#4CAF50', NULL, 2, TRUE),
  ('106', 'Laboratory', '10:00 PM - 6:00 AM', '#F44336', NULL, 3, TRUE),
  ('610', 'Laboratory', '6:00 AM - 10:00 PM', '#2196F3', '#4CAF50', 4, TRUE),
  ('26', 'Laboratory', '2:00 PM - 6:00 AM', '#4CAF50', '#F44336', 5, TRUE),
  ('85', 'Laboratory', '8:00 AM - 5:00 PM', '#000000', NULL, 6, TRUE),
  ('OFF', 'Laboratory', 'Off Duty', '#F44336', NULL, 7, TRUE)
ON DUPLICATE KEY UPDATE
  time_range = VALUES(time_range),
  color_primary = VALUES(color_primary),
  color_secondary = VALUES(color_secondary),
  display_order = VALUES(display_order),
  active = VALUES(active);

-- Radiology
INSERT INTO shift_legends (code, department, time_range, color_primary, color_secondary, display_order, active)
VALUES
  ('62', 'Radiology', '6:00 AM - 2:00 PM', '#2196F3', NULL, 1, TRUE),
  ('210', 'Radiology', '2:00 PM - 10:00 PM', '#4CAF50', NULL, 2, TRUE),
  ('106', 'Radiology', '10:00 PM - 6:00 AM', '#F44336', NULL, 3, TRUE),
  ('610', 'Radiology', '6:00 AM - 10:00 PM', '#2196F3', '#4CAF50', 4, TRUE),
  ('26', 'Radiology', '2:00 PM - 6:00 AM', '#4CAF50', '#F44336', 5, TRUE),
  ('85', 'Radiology', '8:00 AM - 5:00 PM', '#000000', NULL, 6, TRUE),
  ('OFF', 'Radiology', 'Off Duty', '#F44336', NULL, 7, TRUE)
ON DUPLICATE KEY UPDATE
  time_range = VALUES(time_range),
  color_primary = VALUES(color_primary),
  color_secondary = VALUES(color_secondary),
  display_order = VALUES(display_order),
  active = VALUES(active);

SELECT 'Medical department legends added successfully' AS status;
