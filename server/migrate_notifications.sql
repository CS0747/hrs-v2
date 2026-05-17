-- Create notifications table for real-time notification system
CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type VARCHAR(50) NOT NULL, -- 'password_reset', 'leave_request', 'travel_order', 'employee_added', 'training_added', 'audit_log'
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  reference_id INT NULL, -- ID of the related record (leave_id, employee_id, etc.)
  reference_type VARCHAR(50) NULL, -- 'leave', 'travel_order', 'employee', 'training', etc.
  link VARCHAR(255) NULL, -- Direct link to the item
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  read_at TIMESTAMP NULL,
  INDEX idx_user_id (user_id),
  INDEX idx_is_read (is_read),
  INDEX idx_created_at (created_at),
  INDEX idx_user_read (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
