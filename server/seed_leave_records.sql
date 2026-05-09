-- ============================================================
-- Seed Leave Records
-- Sample data for testing Leave Management module
-- ============================================================

USE `geamh_hris`;

-- Clear existing leave records (optional - comment out if you want to keep existing data)
-- TRUNCATE TABLE `leave_records`;

-- Insert sample leave records
-- Note: Make sure these employee_ids exist in your employees table
INSERT INTO `leave_records` 
(`employee_id`, `employee_no`, `employee_name`, `department`, `leave_type`, `date_from`, `date_to`, `days`, `reason`, `status`, `approved_by`, `date_approved`, `remarks`) 
VALUES
-- Sample 1: Approved Vacation Leave
(NULL, 'GEAMH-001', 'Dela Cruz, Juan A.', 'Information Technology', 'Vacation Leave', '2026-05-15', '2026-05-17', 3.0, 'Family vacation', 'Approved', 'Dr. Maria Santos', '2026-05-10', 'Approved with pay'),

-- Sample 2: Pending Sick Leave
(NULL, 'GEAMH-002', 'Santos, Maria B.', 'Nursing', 'Sick Leave', '2026-05-20', '2026-05-22', 3.0, 'Medical checkup and recovery', 'Pending', NULL, NULL, NULL),

-- Sample 3: Approved Maternity Leave
(NULL, 'GEAMH-003', 'Reyes, Ana C.', 'Human Resources', 'Maternity Leave', '2026-06-01', '2026-08-30', 90.0, 'Maternity leave', 'Approved', 'Dr. Maria Santos', '2026-05-05', 'Full maternity benefits'),

-- Sample 4: Disapproved Emergency Leave
(NULL, 'GEAMH-004', 'Garcia, Pedro D.', 'Nursing', 'Emergency Leave', '2026-05-12', '2026-05-12', 1.0, 'Personal emergency', 'Disapproved', 'Dr. Maria Santos', '2026-05-11', 'Insufficient documentation'),

-- Sample 5: Pending Vacation Leave
(NULL, 'GEAMH-005', 'Lopez, Carmen E.', 'Pharmacy', 'Vacation Leave', '2026-06-10', '2026-06-14', 5.0, 'Summer vacation with family', 'Pending', NULL, NULL, NULL),

-- Sample 6: Approved Sick Leave
(NULL, 'GEAMH-006', 'Mendoza, Roberto F.', 'Laboratory', 'Sick Leave', '2026-05-08', '2026-05-10', 3.0, 'Flu and fever', 'Approved', 'Dr. Maria Santos', '2026-05-07', 'Medical certificate provided'),

-- Sample 7: Cancelled Vacation Leave
(NULL, 'GEAMH-007', 'Torres, Linda G.', 'Radiology', 'Vacation Leave', '2026-05-25', '2026-05-27', 3.0, 'Personal matters', 'Cancelled', NULL, NULL, 'Cancelled by employee'),

-- Sample 8: Approved Paternity Leave
(NULL, 'GEAMH-008', 'Ramos, Carlos H.', 'Information Technology', 'Paternity Leave', '2026-05-18', '2026-05-24', 7.0, 'Birth of child', 'Approved', 'Dr. Maria Santos', '2026-05-15', 'Paternity benefits granted'),

-- Sample 9: Pending Special Leave
(NULL, 'GEAMH-009', 'Cruz, Elena I.', 'Accounting', 'Special Leave', '2026-06-05', '2026-06-06', 2.0, 'Attending seminar', 'Pending', NULL, NULL, NULL),

-- Sample 10: Approved Study Leave
(NULL, 'GEAMH-010', 'Fernandez, Miguel J.', 'Human Resources', 'Study Leave', '2026-07-01', '2026-07-05', 5.0, 'Professional development training', 'Approved', 'Dr. Maria Santos', '2026-06-20', 'Approved for skills enhancement');

-- Verify inserted records
SELECT COUNT(*) as total_records FROM `leave_records`;
SELECT * FROM `leave_records` ORDER BY `date_from` DESC LIMIT 10;
