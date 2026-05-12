-- ============================================================
--  Module Permissions Table
--  Stores per-role action permissions for each module.
--  Run this in phpMyAdmin > geamh_hris > SQL tab
-- ============================================================

USE `geamh_hris`;

CREATE TABLE IF NOT EXISTS `module_permissions` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `module`     VARCHAR(80)   NOT NULL COMMENT 'e.g. Employee Masterlist',
  `role`       VARCHAR(40)   NOT NULL COMMENT 'DIOS | Super Admin | Admin | Section Admin',
  `action`     VARCHAR(30)   NOT NULL COMMENT 'View | Add | Edit | Delete | Export | Approve | ...',
  `granted`    TINYINT(1)    NOT NULL DEFAULT 1,
  `updated_by` VARCHAR(100)  DEFAULT NULL,
  `updated_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_module_role_action` (`module`, `role`, `action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Seed default permissions ──────────────────────────────────────────────────
INSERT IGNORE INTO `module_permissions` (`module`, `role`, `action`, `granted`) VALUES
-- Dashboard
('Dashboard','DIOS','View',1),('Dashboard','Super Admin','View',1),('Dashboard','Admin','View',1),('Dashboard','Section Admin','View',1),
-- Employee Masterlist
('Employee Masterlist','DIOS','View',1),('Employee Masterlist','DIOS','Add',1),('Employee Masterlist','DIOS','Edit',1),('Employee Masterlist','DIOS','Delete',1),('Employee Masterlist','DIOS','Export',1),
('Employee Masterlist','Super Admin','View',1),('Employee Masterlist','Super Admin','Add',1),('Employee Masterlist','Super Admin','Edit',1),('Employee Masterlist','Super Admin','Delete',1),('Employee Masterlist','Super Admin','Export',1),
('Employee Masterlist','Admin','View',1),('Employee Masterlist','Admin','Add',1),('Employee Masterlist','Admin','Edit',1),('Employee Masterlist','Admin','Delete',0),('Employee Masterlist','Admin','Export',1),
('Employee Masterlist','Section Admin','View',1),('Employee Masterlist','Section Admin','Add',0),('Employee Masterlist','Section Admin','Edit',0),('Employee Masterlist','Section Admin','Delete',0),('Employee Masterlist','Section Admin','Export',0),
-- Employee Form
('Employee Form','DIOS','View',0),('Employee Form','DIOS','Add',0),('Employee Form','DIOS','Edit',0),('Employee Form','DIOS','Delete',0),
('Employee Form','Super Admin','View',1),('Employee Form','Super Admin','Add',1),('Employee Form','Super Admin','Edit',1),('Employee Form','Super Admin','Delete',1),
('Employee Form','Admin','View',1),('Employee Form','Admin','Add',1),('Employee Form','Admin','Edit',1),('Employee Form','Admin','Delete',0),
('Employee Form','Section Admin','View',0),('Employee Form','Section Admin','Add',0),('Employee Form','Section Admin','Edit',0),('Employee Form','Section Admin','Delete',0),
-- DTR Transmittal
('DTR Transmittal','DIOS','View',1),('DTR Transmittal','DIOS','Add',1),('DTR Transmittal','DIOS','Edit',1),('DTR Transmittal','DIOS','Delete',1),('DTR Transmittal','DIOS','Verify',1),('DTR Transmittal','DIOS','Export',1),
('DTR Transmittal','Super Admin','View',1),('DTR Transmittal','Super Admin','Add',1),('DTR Transmittal','Super Admin','Edit',1),('DTR Transmittal','Super Admin','Delete',1),('DTR Transmittal','Super Admin','Verify',1),('DTR Transmittal','Super Admin','Export',1),
('DTR Transmittal','Admin','View',1),('DTR Transmittal','Admin','Add',1),('DTR Transmittal','Admin','Edit',1),('DTR Transmittal','Admin','Delete',0),('DTR Transmittal','Admin','Verify',1),('DTR Transmittal','Admin','Export',1),
('DTR Transmittal','Section Admin','View',1),('DTR Transmittal','Section Admin','Add',0),('DTR Transmittal','Section Admin','Edit',0),('DTR Transmittal','Section Admin','Delete',0),('DTR Transmittal','Section Admin','Verify',0),('DTR Transmittal','Section Admin','Export',0),
-- Leave Management
('Leave Management','DIOS','View',1),('Leave Management','DIOS','Add',1),('Leave Management','DIOS','Edit',1),('Leave Management','DIOS','Delete',1),('Leave Management','DIOS','Approve',1),
('Leave Management','Super Admin','View',1),('Leave Management','Super Admin','Add',1),('Leave Management','Super Admin','Edit',1),('Leave Management','Super Admin','Delete',1),('Leave Management','Super Admin','Approve',1),
('Leave Management','Admin','View',1),('Leave Management','Admin','Add',1),('Leave Management','Admin','Edit',1),('Leave Management','Admin','Delete',0),('Leave Management','Admin','Approve',1),
('Leave Management','Section Admin','View',1),('Leave Management','Section Admin','Add',0),('Leave Management','Section Admin','Edit',0),('Leave Management','Section Admin','Delete',0),('Leave Management','Section Admin','Approve',0),
-- Travel Orders
('Travel Orders','DIOS','View',1),('Travel Orders','DIOS','Add',1),('Travel Orders','DIOS','Edit',1),('Travel Orders','DIOS','Delete',1),('Travel Orders','DIOS','Approve',1),
('Travel Orders','Super Admin','View',1),('Travel Orders','Super Admin','Add',1),('Travel Orders','Super Admin','Edit',1),('Travel Orders','Super Admin','Delete',1),('Travel Orders','Super Admin','Approve',1),
('Travel Orders','Admin','View',1),('Travel Orders','Admin','Add',1),('Travel Orders','Admin','Edit',1),('Travel Orders','Admin','Delete',0),('Travel Orders','Admin','Approve',1),
('Travel Orders','Section Admin','View',1),('Travel Orders','Section Admin','Add',0),('Travel Orders','Section Admin','Edit',0),('Travel Orders','Section Admin','Delete',0),('Travel Orders','Section Admin','Approve',0),
-- Schedule Database
('Schedule Database','DIOS','View',1),('Schedule Database','DIOS','Add',1),('Schedule Database','DIOS','Edit',1),('Schedule Database','DIOS','Delete',1),('Schedule Database','DIOS','Export',1),
('Schedule Database','Super Admin','View',1),('Schedule Database','Super Admin','Add',1),('Schedule Database','Super Admin','Edit',1),('Schedule Database','Super Admin','Delete',1),('Schedule Database','Super Admin','Export',1),
('Schedule Database','Admin','View',1),('Schedule Database','Admin','Add',1),('Schedule Database','Admin','Edit',1),('Schedule Database','Admin','Delete',0),('Schedule Database','Admin','Export',1),
('Schedule Database','Section Admin','View',1),('Schedule Database','Section Admin','Add',1),('Schedule Database','Section Admin','Edit',1),('Schedule Database','Section Admin','Delete',0),('Schedule Database','Section Admin','Export',0),
-- Trainings
('Trainings','DIOS','View',1),('Trainings','DIOS','Add',1),('Trainings','DIOS','Edit',1),('Trainings','DIOS','Delete',1),
('Trainings','Super Admin','View',1),('Trainings','Super Admin','Add',1),('Trainings','Super Admin','Edit',1),('Trainings','Super Admin','Delete',1),
('Trainings','Admin','View',1),('Trainings','Admin','Add',1),('Trainings','Admin','Edit',1),('Trainings','Admin','Delete',0),
('Trainings','Section Admin','View',1),('Trainings','Section Admin','Add',0),('Trainings','Section Admin','Edit',0),('Trainings','Section Admin','Delete',0),
-- Tracking / Receiving
('Tracking / Receiving','DIOS','View',1),('Tracking / Receiving','DIOS','Add',1),('Tracking / Receiving','DIOS','Edit',1),('Tracking / Receiving','DIOS','Delete',1),
('Tracking / Receiving','Super Admin','View',1),('Tracking / Receiving','Super Admin','Add',1),('Tracking / Receiving','Super Admin','Edit',1),('Tracking / Receiving','Super Admin','Delete',1),
('Tracking / Receiving','Admin','View',1),('Tracking / Receiving','Admin','Add',1),('Tracking / Receiving','Admin','Edit',1),('Tracking / Receiving','Admin','Delete',0),
('Tracking / Receiving','Section Admin','View',1),('Tracking / Receiving','Section Admin','Add',0),('Tracking / Receiving','Section Admin','Edit',0),('Tracking / Receiving','Section Admin','Delete',0),
-- Signatories
('Signatories','DIOS','View',0),('Signatories','DIOS','Add',0),('Signatories','DIOS','Edit',0),('Signatories','DIOS','Delete',0),
('Signatories','Super Admin','View',1),('Signatories','Super Admin','Add',1),('Signatories','Super Admin','Edit',1),('Signatories','Super Admin','Delete',1),
('Signatories','Admin','View',1),('Signatories','Admin','Add',1),('Signatories','Admin','Edit',1),('Signatories','Admin','Delete',0),
('Signatories','Section Admin','View',0),('Signatories','Section Admin','Add',0),('Signatories','Section Admin','Edit',0),('Signatories','Section Admin','Delete',0),
-- Audit History
('Audit History','DIOS','View',1),('Audit History','DIOS','Export',1),
('Audit History','Super Admin','View',1),('Audit History','Super Admin','Export',1),
('Audit History','Admin','View',1),('Audit History','Admin','Export',0),
('Audit History','Section Admin','View',0),('Audit History','Section Admin','Export',0),
-- Account Management
('Account Management','DIOS','View',0),('Account Management','DIOS','Add',0),('Account Management','DIOS','Edit',0),('Account Management','DIOS','Delete',0),
('Account Management','Super Admin','View',1),('Account Management','Super Admin','Add',1),('Account Management','Super Admin','Edit',1),('Account Management','Super Admin','Delete',1),
('Account Management','Admin','View',0),('Account Management','Admin','Add',0),('Account Management','Admin','Edit',0),('Account Management','Admin','Delete',0),
('Account Management','Section Admin','View',0),('Account Management','Section Admin','Add',0),('Account Management','Section Admin','Edit',0),('Account Management','Section Admin','Delete',0),
-- DIOS Account
('DIOS Account','DIOS','View',1),('DIOS Account','DIOS','Add',1),('DIOS Account','DIOS','Edit',1),('DIOS Account','DIOS','Delete',1),
('DIOS Account','Super Admin','View',1),('DIOS Account','Super Admin','Add',0),('DIOS Account','Super Admin','Edit',0),('DIOS Account','Super Admin','Delete',0),
('DIOS Account','Admin','View',0),('DIOS Account','Admin','Add',0),('DIOS Account','Admin','Edit',0),('DIOS Account','Admin','Delete',0),
('DIOS Account','Section Admin','View',0),('DIOS Account','Section Admin','Add',0),('DIOS Account','Section Admin','Edit',0),('DIOS Account','Section Admin','Delete',0),
-- DIOS System Control
('DIOS System Control','DIOS','View',1),('DIOS System Control','DIOS','Query',1),('DIOS System Control','DIOS','Browse',1),
('DIOS System Control','Super Admin','View',0),('DIOS System Control','Super Admin','Query',0),('DIOS System Control','Super Admin','Browse',0),
('DIOS System Control','Admin','View',0),('DIOS System Control','Admin','Query',0),('DIOS System Control','Admin','Browse',0),
('DIOS System Control','Section Admin','View',0),('DIOS System Control','Section Admin','Query',0),('DIOS System Control','Section Admin','Browse',0),
-- Version History
('Version History','DIOS','View',1),('Version History','DIOS','Export',1),
('Version History','Super Admin','View',1),('Version History','Super Admin','Export',1),
('Version History','Admin','View',1),('Version History','Admin','Export',0),
('Version History','Section Admin','View',0),('Version History','Section Admin','Export',0),
-- User Manual
('User Manual','DIOS','View',1),('User Manual','Super Admin','View',1),('User Manual','Admin','View',1),('User Manual','Section Admin','View',1),
-- Birthday Celebrants
('Birthday Celebrants','DIOS','View',1),('Birthday Celebrants','DIOS','Export',1),
('Birthday Celebrants','Super Admin','View',1),('Birthday Celebrants','Super Admin','Export',1),
('Birthday Celebrants','Admin','View',1),('Birthday Celebrants','Admin','Export',1),
('Birthday Celebrants','Section Admin','View',1),('Birthday Celebrants','Section Admin','Export',0),
-- AI Scanning Tools
('AI Scanning Tools','DIOS','View',1),('AI Scanning Tools','DIOS','Upload',1),('AI Scanning Tools','DIOS','Delete',1),
('AI Scanning Tools','Super Admin','View',1),('AI Scanning Tools','Super Admin','Upload',1),('AI Scanning Tools','Super Admin','Delete',1),
('AI Scanning Tools','Admin','View',1),('AI Scanning Tools','Admin','Upload',1),('AI Scanning Tools','Admin','Delete',0),
('AI Scanning Tools','Section Admin','View',0),('AI Scanning Tools','Section Admin','Upload',0),('AI Scanning Tools','Section Admin','Delete',0),
-- Departments
('Departments','DIOS','View',0),('Departments','DIOS','Add',0),('Departments','DIOS','Edit',0),('Departments','DIOS','Delete',0),
('Departments','Super Admin','View',1),('Departments','Super Admin','Add',1),('Departments','Super Admin','Edit',1),('Departments','Super Admin','Delete',1),
('Departments','Admin','View',1),('Departments','Admin','Add',1),('Departments','Admin','Edit',1),('Departments','Admin','Delete',0),
('Departments','Section Admin','View',0),('Departments','Section Admin','Add',0),('Departments','Section Admin','Edit',0),('Departments','Section Admin','Delete',0),
-- Audit Transmittal
('Audit Transmittal','DIOS','View',1),('Audit Transmittal','DIOS','Export',1),
('Audit Transmittal','Super Admin','View',1),('Audit Transmittal','Super Admin','Export',1),
('Audit Transmittal','Admin','View',1),('Audit Transmittal','Admin','Export',1),
('Audit Transmittal','Section Admin','View',0),('Audit Transmittal','Section Admin','Export',0);
