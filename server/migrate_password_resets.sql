-- Create password_reset_requests table
CREATE TABLE IF NOT EXISTS password_reset_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  username VARCHAR(50) NOT NULL,
  user_name VARCHAR(150) NOT NULL,
  status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  processed_at TIMESTAMP NULL,
  processed_by VARCHAR(150) NULL,
  notes TEXT NULL,
  INDEX idx_user_id (user_id),
  INDEX idx_status (status),
  INDEX idx_requested_at (requested_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
