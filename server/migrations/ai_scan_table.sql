-- AI Scanning Tools table migration
-- Creates the ai_scanned_docs table if it doesn't exist

CREATE TABLE IF NOT EXISTS `ai_scanned_docs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `doc_type` varchar(100) DEFAULT 'Unknown',
  `file_size` varchar(50) DEFAULT NULL,
  `confidence` int(11) DEFAULT 0,
  `extracted_data` longtext DEFAULT NULL,
  `raw_text` longtext DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_doc_type` (`doc_type`),
  KEY `idx_status` (`status`),
  KEY `idx_uploaded_by` (`uploaded_by`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
