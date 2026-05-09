-- ============================================================
--  GEAMH HRIS — Departments Table
--  Run in phpMyAdmin > geamh_hris > SQL tab
-- ============================================================

USE geamh_hris;

CREATE TABLE IF NOT EXISTS `departments` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100)    NOT NULL UNIQUE,
  `code`        VARCHAR(20)     DEFAULT NULL COMMENT 'Short code e.g. NUR, MED',
  `description` VARCHAR(255)    DEFAULT NULL,
  `active`      TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dept_name` (`name`),
  KEY `idx_dept_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Seed departments ─────────────────────────────────────────────────────────
INSERT IGNORE INTO `departments` (`name`, `code`, `description`) VALUES
('Nursing',                'NUR',  'Nursing Services Department'),
('Medicine',               'MED',  'Medical Services Department'),
('Surgery',                'SUR',  'Surgical Services Department'),
('Pediatrics',             'PED',  'Pediatrics Department'),
('OB-Gynecology',          'OBG',  'Obstetrics and Gynecology Department'),
('Radiology',              'RAD',  'Radiology and Imaging Department'),
('Laboratory',             'LAB',  'Clinical Laboratory Department'),
('Pharmacy',               'PHA',  'Pharmacy Department'),
('Administrative',         'ADM',  'Administrative Department'),
('Finance',                'FIN',  'Finance and Accounting Department'),
('Maintenance',            'MNT',  'Maintenance and Engineering Department'),
('Security',               'SEC',  'Security Department'),
('Dietary',                'DIT',  'Dietary and Nutrition Department'),
('Medical Records',        'MRD',  'Medical Records Department'),
('Social Work',            'SWD',  'Social Work and Welfare Department'),
('Rehabilitation',         'RHB',  'Rehabilitation Medicine Department'),
('Medical Arts Building',  'MAB',  'Medical Arts Building'),
('Dialysis Extension Clinic', 'DEC', 'Dialysis Extension Clinic'),
('Human Resources',        'HRD',  'Human Resources Department'),
('Information Technology', 'ITD',  'Information Technology Department');

-- ── Add FK from employees.department to departments.name (optional) ──────────
-- Skipped intentionally — employees.department stores the name as VARCHAR
-- so existing data is preserved without migration.
-- To enforce referential integrity later, run:
--   ALTER TABLE employees ADD CONSTRAINT fk_emp_dept
--     FOREIGN KEY (department) REFERENCES departments(name)
--     ON UPDATE CASCADE ON DELETE SET NULL;
