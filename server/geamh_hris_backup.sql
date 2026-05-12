-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 11, 2026 at 04:48 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `geamh_hris`
--

-- --------------------------------------------------------

--
-- Table structure for table `ai_scanned_docs`
--

CREATE TABLE `ai_scanned_docs` (
  `id` int(10) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `doc_type` enum('DTR','Leave Form','Payslip','Travel Order','Schedule','Unknown') NOT NULL DEFAULT 'Unknown',
  `file_size` varchar(20) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `confidence` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'OCR confidence 0-100',
  `extracted_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Key-value pairs from OCR' CHECK (json_valid(`extracted_data`)),
  `raw_text` mediumtext DEFAULT NULL,
  `status` enum('Processing','Processed','Review Needed') NOT NULL DEFAULT 'Processing',
  `uploaded_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `user_name` varchar(100) NOT NULL,
  `action` varchar(100) NOT NULL COMMENT 'e.g. Login, DTR Received, Employee Added',
  `action_type` enum('LOGIN','LOGOUT','CREATE','UPDATE','DELETE','VIEW','EXPORT','OTHER') NOT NULL DEFAULT 'OTHER' COMMENT 'Standardised action category',
  `module` enum('DTR','Leave','Payroll','Employee','T.O.','Auth','Schedule','Training','Tracking','Signatory','Department','Account','Other') NOT NULL DEFAULT 'Other',
  `affected_table` varchar(60) DEFAULT NULL COMMENT 'DB table affected e.g. employees',
  `record_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'PK of the affected row',
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Snapshot before change' CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Snapshot after change' CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'Client IP (IPv4 or IPv6)',
  `details` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'OK',
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `user_name`, `action`, `action_type`, `module`, `affected_table`, `record_id`, `old_values`, `new_values`, `ip_address`, `details`, `status`, `archived`, `created_at`) VALUES
(1, 1, 'Super Admin', 'Employee Updated', 'OTHER', 'Employee', NULL, NULL, NULL, NULL, '::1', 'Employee Agrimano, Rheanbelle updated.', 'OK', 0, '2026-05-04 07:08:40'),
(2, 1, 'Super Admin', 'Sign Up', 'OTHER', 'Auth', NULL, NULL, NULL, NULL, '::1', 'New user DIOS (dios123) registered.', 'OK', 0, '2026-05-05 03:02:42'),
(3, 1, 'Super Admin', 'Logout', 'LOGOUT', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged out.', 'OK', 0, '2026-05-05 03:03:05'),
(4, 4294967295, 'DIOS', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'DIOS logged in.', 'OK', 0, '2026-05-05 03:03:08'),
(5, 4294967295, 'DIOS', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'DIOS logged in.', 'OK', 0, '2026-05-05 03:06:35'),
(6, 4294967295, 'DIOS', 'Logout', 'LOGOUT', 'Auth', NULL, NULL, NULL, NULL, '::1', 'DIOS logged out.', 'OK', 0, '2026-05-05 03:06:56'),
(7, 1, 'Super Admin', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged in.', 'OK', 0, '2026-05-05 03:07:03'),
(8, 1, 'Super Admin', 'Logout', 'LOGOUT', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged out.', 'OK', 0, '2026-05-05 05:33:53'),
(9, 3, 'DIOS User', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'DIOS User logged in.', 'OK', 0, '2026-05-05 05:34:04'),
(10, 1, 'Super Admin', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged in.', 'OK', 0, '2026-05-07 02:03:41'),
(11, 1, 'Super Admin', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged in.', 'OK', 0, '2026-05-07 02:10:36'),
(12, 1, 'Super Admin', 'Employee Updated', 'OTHER', 'Employee', NULL, NULL, NULL, NULL, '::1', 'Employee Abaredes, Malayas updated.', 'OK', 0, '2026-05-07 02:39:41'),
(13, 1, 'Super Admin', 'Employee Updated', 'UPDATE', 'Employee', 'employees', 269, NULL, '{\"employeeNo\":\"KPFH-C080\",\"lastName\":\"Abaredes\",\"firstName\":\"Malaya\",\"middleName\":\"A.\",\"position\":\"Nursing Attendant I\",\"department\":\"Nursing\",\"employmentStatus\":\"Casual\",\"dateHired\":\"\",\"birthDate\":\"\",\"gender\":\"\",\"civilStatus\":\"\",\"address\":\"\",\"contactNo\":\"\",\"email\":\"\",\"salary\":0,\"sgStep\":\"0\",\"tin\":\"\",\"sss\":\"\",\"philhealth\":\"\",\"pagibig\":\"\",\"active\":true,\"designation\":\"\",\"id\":269}', '::1', 'Abaredes, Malaya was updated.', 'OK', 0, '2026-05-07 03:14:43'),
(14, 1, 'Super Admin', 'Employee Updated', 'OTHER', 'Employee', NULL, NULL, NULL, NULL, '::1', 'Employee Abaredes, Malaya updated.', 'OK', 0, '2026-05-07 03:14:43'),
(15, 1, 'Super Admin', 'Logout', 'LOGOUT', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged out.', 'OK', 0, '2026-05-07 03:20:11'),
(16, 3, 'DIOS User', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'DIOS User logged in.', 'OK', 0, '2026-05-07 03:21:11'),
(17, 3, 'DIOS User', 'Logout', 'LOGOUT', 'Auth', NULL, NULL, NULL, NULL, '::1', 'DIOS User logged out.', 'OK', 0, '2026-05-07 03:21:26'),
(18, 3, 'DIOS User', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'DIOS User logged in.', 'OK', 0, '2026-05-07 03:25:40'),
(19, 1, 'Super Admin', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged in.', 'OK', 0, '2026-05-07 04:59:17'),
(20, 1, 'Super Admin', 'Schedule Updated', 'UPDATE', 'Schedule', 'schedules', 3, '0', '{\"id\":\"3\",\"employeeNo\":\"GEAMH-C001\",\"employeeName\":\"Agrimano, Rheanbelle\",\"department\":\"Information Technology\",\"shift\":\"Morning\",\"shiftTime\":\"07:00 AM - 03:00 PM\",\"days\":[\"Mon\",\"Tue\",\"Wed\",\"Thu\",\"Sun\"],\"effectiveDate\":\"\",\"endDate\":\"\",\"restDay\":\"Fri, Sat\"}', '::1', 'Agrimano, Rheanbelle was updated.', 'OK', 0, '2026-05-07 05:13:19'),
(21, 1, 'Super Admin', 'Schedule Updated', 'UPDATE', 'Schedule', 'schedules', 3, '0', '{\"id\":\"3\",\"employeeNo\":\"GEAMH-C001\",\"employeeName\":\"Agrimano, Rheanbelle\",\"department\":\"Information Technology\",\"shift\":\"Morning\",\"shiftTime\":\"07:00 AM - 03:00 PM\",\"days\":[\"Mon\",\"Tue\",\"Wed\",\"Thu\",\"Sun\"],\"effectiveDate\":\"\",\"endDate\":\"\",\"restDay\":\"Fri, Sat\"}', '::1', 'Agrimano, Rheanbelle was updated.', 'OK', 0, '2026-05-07 05:13:20'),
(22, 1, 'Super Admin', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged in.', 'OK', 0, '2026-05-07 05:15:07'),
(23, 1, 'Super Admin', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged in.', 'OK', 0, '2026-05-07 05:30:27'),
(24, 1, 'Super Admin', 'Profile Change Requested', 'OTHER', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin requested a profile update. Awaiting approval.', 'OK', 0, '2026-05-07 05:44:00'),
(25, 1, 'Super Admin', 'Profile Updated', 'OTHER', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin updated their profile.', 'OK', 0, '2026-05-07 05:48:26'),
(26, 1, 'Super Admin', 'Profile Updated', 'OTHER', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin updated their profile.', 'OK', 0, '2026-05-07 05:48:36'),
(27, 1, 'Super Admin', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged in.', 'OK', 0, '2026-05-07 05:56:50'),
(28, 1, 'Super Admin', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged in.', 'OK', 0, '2026-05-07 06:55:35'),
(29, 1, 'Super Admin', 'Employee Deleted', 'DELETE', 'Employee', 'employees', 365, '0', NULL, '::1', 'Cadavid, Francine was deleted.', 'OK', 0, '2026-05-07 07:47:16'),
(30, 1, 'Super Admin', 'Logout', 'LOGOUT', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged out.', 'OK', 0, '2026-05-07 08:18:16'),
(31, 3, 'DIOS User', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'DIOS User logged in.', 'OK', 0, '2026-05-07 08:18:28'),
(32, 3, 'DIOS User', 'Logout', 'LOGOUT', 'Auth', NULL, NULL, NULL, NULL, '::1', 'DIOS User logged out.', 'OK', 0, '2026-05-07 08:21:14'),
(33, 1, 'Super Admin', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged in.', 'OK', 0, '2026-05-07 08:29:20'),
(34, 1, 'Super Admin', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged in.', 'OK', 0, '2026-05-07 08:35:10'),
(35, 2, 'HR Admin', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'HR Admin logged in.', 'OK', 0, '2026-05-08 00:30:50'),
(36, 2, 'HR Admin', 'Employee Updated', 'OTHER', 'Employee', NULL, NULL, NULL, NULL, '::1', 'Employee Abaredes, Malaya updated.', 'OK', 0, '2026-05-08 01:19:24'),
(37, 2, 'HR Admin', 'Employee Updated', 'UPDATE', 'Employee', 'employees', 269, NULL, '{\"employeeNo\":\"KPFH-C080\",\"lastName\":\"Abaredes\",\"firstName\":\"Malaya\",\"middleName\":\"A.\",\"position\":\"Nursing Attendant I\",\"department\":\"Nursing\",\"employmentStatus\":\"Casual\",\"dateHired\":\"2010-02-08\",\"birthDate\":\"\",\"gender\":\"\",\"civilStatus\":\"\",\"address\":\"\",\"contactNo\":\"\",\"email\":\"\",\"salary\":0,\"sgStep\":\"0\",\"tin\":\"\",\"sss\":\"\",\"philhealth\":\"\",\"pagibig\":\"\",\"active\":true,\"designation\":\"\",\"id\":269}', '::1', 'Abaredes, Malaya was updated.', 'OK', 0, '2026-05-08 01:19:24'),
(38, 2, 'HR Admin', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'HR Admin logged in.', 'OK', 0, '2026-05-08 05:34:17'),
(39, 1, 'Super Admin', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged in.', 'OK', 0, '2026-05-08 06:10:28'),
(40, 1, 'Super Admin', 'Logout', 'LOGOUT', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged out.', 'OK', 0, '2026-05-08 06:10:34'),
(41, 1, 'Super Admin', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged in.', 'OK', 0, '2026-05-08 06:11:09'),
(42, 1, 'Super Admin', 'Leave Added', 'CREATE', 'Leave', 'leave_records', 4, NULL, '{\"employeeNo\":\"GEAMH-C001\",\"employeeName\":\"Agrimano, Rheanbelle C.\",\"department\":\"Information Technology\",\"leaveType\":\"Sick Leave\",\"dateFrom\":\"2026-05-30\",\"dateTo\":\"2026-05-30\",\"days\":1,\"reason\":\"nangungulila\",\"status\":\"Pending\",\"approvedBy\":\"\",\"dateApproved\":\"\",\"remarks\":\"ahuhuhuhu\",\"id\":4}', '::1', 'Agrimano, Rheanbelle C. was added.', 'OK', 0, '2026-05-08 06:32:23'),
(43, 1, 'Super Admin', 'Leave Deleted', 'DELETE', 'Leave', 'leave_records', 4, '0', NULL, '::1', 'Agrimano, Rheanbelle C. was deleted.', 'OK', 0, '2026-05-08 06:38:48'),
(44, 1, 'Super Admin', 'Leave Added', 'CREATE', 'Leave', 'leave_records', 5, NULL, '{\"employeeNo\":\"GEAMH-C001\",\"employeeName\":\"Agrimano, Rheanbelle C.\",\"department\":\"Information Technology\",\"leaveType\":\"Sick Leave\",\"dateFrom\":\"2026-05-30\",\"dateTo\":\"2026-05-30\",\"days\":1,\"reason\":\"nangungulila\",\"status\":\"Pending\",\"approvedBy\":\"\",\"dateApproved\":\"\",\"remarks\":\"ahuhauhauhauhauh\\n\",\"id\":5}', '::1', 'Agrimano, Rheanbelle C. was added.', 'OK', 0, '2026-05-08 06:42:58'),
(45, 1, 'Super Admin', 'Leave Deleted', 'DELETE', 'Leave', 'leave_records', 3, '0', NULL, '::1', 'Bautista, Ana C. was deleted.', 'OK', 0, '2026-05-08 06:43:03'),
(46, 1, 'Super Admin', 'Leave Deleted', 'DELETE', 'Leave', 'leave_records', 2, '0', NULL, '::1', 'Santos, Pedro L. was deleted.', 'OK', 0, '2026-05-08 06:43:07'),
(47, 1, 'Super Admin', 'Leave Deleted', 'DELETE', 'Leave', 'leave_records', 1, '0', NULL, '::1', 'Dela Cruz, Juan S. was deleted.', 'OK', 0, '2026-05-08 06:43:11'),
(48, 1, 'Super Admin', 'Leave Deleted', 'DELETE', 'Leave', 'leave_records', 1, '0', NULL, '::1', 'Test, User was deleted.', 'OK', 0, '2026-05-08 07:20:11'),
(49, 1, 'Super Admin', 'Leave Added', 'CREATE', 'Leave', 'leave_records', 3, NULL, '{\"id\":3,\"employeeNo\":\"GEAMH-C001\",\"employeeName\":\"Agrimano, Rheanbelle C.\",\"department\":\"Information Technology\",\"leaveType\":\"Vacation Leave\",\"dateFrom\":\"2026-05-30\",\"dateTo\":\"2026-05-30\",\"days\":1,\"reason\":\"\",\"status\":\"Pending\",\"approvedBy\":\"\",\"dateApproved\":\"\",\"remarks\":\"\"}', '::1', 'Agrimano, Rheanbelle C. was added.', 'OK', 0, '2026-05-08 07:20:27'),
(50, 1, 'Super Admin', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged in.', 'OK', 0, '2026-05-09 01:13:27'),
(51, 1, 'Super Admin', 'Logout', 'LOGOUT', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged out.', 'OK', 0, '2026-05-09 01:22:59'),
(52, 2, 'HR Admin', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'HR Admin logged in.', 'OK', 0, '2026-05-09 01:23:11'),
(53, 1, 'Super Admin', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged in.', 'OK', 0, '2026-05-09 02:59:38'),
(54, 1, 'Super Admin', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged in.', 'OK', 0, '2026-05-09 03:07:26'),
(55, 1, 'Super Admin', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged in.', 'OK', 0, '2026-05-09 03:11:57'),
(56, 1, 'Super Admin', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged in.', 'OK', 0, '2026-05-09 03:17:12'),
(57, 1, 'Super Admin', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged in.', 'OK', 0, '2026-05-09 03:23:39'),
(58, 1, 'Super Admin', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged in.', 'OK', 0, '2026-05-09 03:50:53'),
(59, 1, 'Super Admin', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged in.', 'OK', 0, '2026-05-09 05:48:27'),
(60, 1, 'Super Admin', 'Employee Added', 'OTHER', 'Employee', NULL, NULL, NULL, NULL, '::1', 'New employee Sismaet, Angeline added.', 'OK', 0, '2026-05-09 05:50:37'),
(61, 1, 'Super Admin', 'Employee Added', 'CREATE', 'Employee', 'employees', 366, NULL, '{\"id\":\"366\",\"employeeNo\":\"GEAMH-666\",\"lastName\":\"Sismaet\",\"firstName\":\"Angeline\",\"middleName\":\"Dondoyano\",\"position\":\"\",\"designation\":\"\",\"department\":\"\",\"employmentStatus\":\"Permanent\",\"dateHired\":\"\",\"birthDate\":\"2004-10-06\",\"age\":\"0\",\"gender\":\"Female\",\"civilStatus\":\"Married\",\"address\":\"Ormoc City\",\"contactNo\":\"09909090900\",\"email\":\"angel@geamh.gov.ph\",\"salary\":0,\"sgStep\":\"\",\"tin\":\"\",\"sss\":\"\",\"philhealth\":\"\",\"pagibig\":\"\",\"active\":true}', '::1', 'Sismaet, Angeline was added.', 'OK', 0, '2026-05-09 05:50:37'),
(62, 1, 'Super Admin', 'Employee Updated', 'UPDATE', 'Employee', 'employees', 366, NULL, '{\"employeeNo\":\"GEAMH-666\",\"lastName\":\"Sismaet\",\"firstName\":\"Angeline\",\"middleName\":\"Dondoyano\",\"position\":\"Nurse IV\",\"department\":\"Nursing\",\"employmentStatus\":\"Part Time\",\"dateHired\":\"2026-02-25\",\"birthDate\":\"2004-10-06\",\"gender\":\"Female\",\"civilStatus\":\"Married\",\"address\":\"Ormoc City\",\"contactNo\":\"09909090900\",\"email\":\"angel@geamh.gov.ph\",\"salary\":0,\"sgStep\":\"\",\"tin\":\"\",\"sss\":\"\",\"philhealth\":\"\",\"pagibig\":\"\",\"active\":true,\"designation\":\"\",\"id\":366}', '::1', 'Sismaet, Angeline was updated.', 'OK', 0, '2026-05-09 05:52:55'),
(63, 1, 'Super Admin', 'Employee Updated', 'OTHER', 'Employee', NULL, NULL, NULL, NULL, '::1', 'Employee Sismaet, Angeline updated.', 'OK', 0, '2026-05-09 05:52:55'),
(64, 1, 'Super Admin', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged in.', 'OK', 0, '2026-05-09 06:46:39'),
(65, 1, 'Super Admin', 'Leave Added', 'CREATE', 'Leave', 'leave_records', NULL, NULL, '{\"employee_id\":\"366\",\"employee_no\":\"GEAMH-666\",\"employee_name\":\"Sismaet, Angeline Dondoyano\",\"department\":\"Nursing\",\"leave_type\":\"Forced Leave\",\"date_from\":\"2026-05-11\",\"date_to\":\"2026-05-19\",\"days\":8,\"reason\":\"\",\"status\":\"Disapproved\",\"approved_by\":\"Francine\",\"date_approved\":\"2026-05-09\",\"remarks\":\"\"}', '::1', 'Sismaet, Angeline Dondoyano was added.', 'OK', 0, '2026-05-09 06:48:55'),
(66, 1, 'Super Admin', 'Leave Updated', 'UPDATE', 'Leave', 'leave_records', 4, '0', '{\"id\":\"4\",\"employee_id\":\"366\",\"employee_no\":\"GEAMH-666\",\"employee_name\":\"Sismaet, Angeline Dondoyano\",\"department\":\"Nursing\",\"leave_type\":\"Forced Leave\",\"date_from\":\"2026-05-11\",\"date_to\":\"2026-05-19\",\"days\":\"8.0\",\"reason\":\"\",\"status\":\"Disapproved\",\"approved_by\":\"Francine\",\"date_approved\":\"2026-05-09\",\"remarks\":\"\",\"created_at\":\"2026-05-09 14:48:55\",\"updated_at\":\"2026-05-09 14:48:55\",\"employeeId\":\"366\",\"employeeNo\":\"GEAMH-666\",\"employeeName\":\"Sismaet, Angeline Dondoyano\"}', '::1', 'Sismaet, Angeline Dondoyano was updated.', 'OK', 0, '2026-05-09 06:50:11'),
(67, 1, 'Super Admin', 'Leave Updated', 'UPDATE', 'Leave', 'leave_records', 4, '0', '{\"id\":4,\"employeeId\":366,\"employeeNo\":\"GEAMH-666\",\"employeeName\":\"Sismaet, Angeline Dondoyano\",\"department\":\"Nursing\",\"leaveType\":\"Forced Leave\",\"dateFrom\":\"0000-00-00\",\"dateTo\":\"0000-00-00\",\"days\":8,\"reason\":\"\",\"status\":\"Disapproved\",\"approvedBy\":null,\"dateApproved\":null,\"remarks\":\"\"}', '::1', 'Sismaet, Angeline Dondoyano was updated.', 'OK', 0, '2026-05-09 06:52:05'),
(68, 1, 'Super Admin', 'Leave Updated', 'UPDATE', 'Leave', 'leave_records', 4, '0', '{\"id\":4,\"employeeId\":366,\"employeeNo\":\"GEAMH-666\",\"employeeName\":\"Sismaet, Angeline Dondoyano\",\"department\":\"Nursing\",\"leaveType\":\"Forced Leave\",\"dateFrom\":\"0000-00-00\",\"dateTo\":\"0000-00-00\",\"days\":8,\"reason\":\"\",\"status\":\"Pending\",\"approvedBy\":null,\"dateApproved\":null,\"remarks\":\"\"}', '::1', 'Sismaet, Angeline Dondoyano was updated.', 'OK', 0, '2026-05-09 06:57:28'),
(69, 1, 'Super Admin', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged in.', 'OK', 0, '2026-05-09 07:17:09'),
(70, 1, 'Super Admin', 'Travel Order Added', 'CREATE', '', 'travel order', NULL, NULL, '{\"employee_id\":null,\"employee_no\":\"666\",\"employee_name\":\"Angeline\",\"department\":\"Nursing\",\"destination\":\"Ecert\",\"purpose\":\"secret\",\"date_from\":\"\",\"date_to\":\"\",\"days\":1,\"transport\":\"Public Transport\",\"approved_by\":null,\"status\":\"Pending\",\"remarks\":\"\"}', '::1', 'Angeline was added.', 'OK', 0, '2026-05-09 08:14:48'),
(71, 1, 'Super Admin', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged in.', 'OK', 0, '2026-05-10 17:57:07'),
(72, 1, 'Super Admin', 'Signatory Updated', 'UPDATE', 'Signatory', 'signatories', 5, '0', '{\"id\":\"5\",\"name\":\"Thea Villanueva\",\"position\":\"HR Clerk II\",\"role\":\"DTR Processor\",\"department\":\"Human Resources\",\"order\":5,\"active\":false}', '::1', 'Thea Villanueva was updated.', 'OK', 0, '2026-05-10 18:00:33'),
(73, 1, 'Super Admin', 'Signatory Updated', 'UPDATE', 'Signatory', 'signatories', 5, '0', '{\"id\":\"5\",\"name\":\"Thea Villanueva\",\"position\":\"HR Clerk II\",\"role\":\"DTR Processor\",\"department\":\"Human Resources\",\"order\":5,\"active\":true}', '::1', 'Thea Villanueva was updated.', 'OK', 0, '2026-05-10 18:00:47'),
(74, 1, 'Super Admin', 'Employee Added', 'CREATE', 'Employee', 'employees', 367, NULL, '{\"id\":\"367\",\"employeeNo\":\"000\",\"lastName\":\"Mico\",\"firstName\":\"m\",\"middleName\":\"m\",\"position\":\"\",\"designation\":\"\",\"department\":\"\",\"employmentStatus\":\"Permanent\",\"dateHired\":\"\",\"birthDate\":\"\",\"age\":\"0\",\"gender\":\"Male\",\"civilStatus\":\"Single\",\"address\":\"\",\"contactNo\":\"\",\"email\":\"\",\"salary\":0,\"sgStep\":\"\",\"tin\":\"\",\"sss\":\"\",\"philhealth\":\"\",\"pagibig\":\"\",\"active\":true}', '::1', 'Mico, m was added.', 'OK', 0, '2026-05-10 18:47:24'),
(75, 1, 'Super Admin', 'Employee Added', 'OTHER', 'Employee', NULL, NULL, NULL, NULL, '::1', 'New employee Mico, m added.', 'OK', 0, '2026-05-10 18:47:24'),
(76, 1, 'Super Admin', 'Employee Deleted', 'DELETE', 'Employee', 'employees', 367, '0', NULL, '::1', 'Mico, m was deleted.', 'OK', 0, '2026-05-10 18:47:34'),
(77, 1, 'Super Admin', 'Login', 'LOGIN', 'Auth', NULL, NULL, NULL, NULL, '::1', 'Super Admin logged in.', 'OK', 0, '2026-05-11 01:52:34'),
(78, 1, 'Super Admin', 'DTR Added', 'CREATE', 'DTR', 'dtr_records', 1, NULL, '{\"employeeNo\":\"GEAMH-C001\",\"employeeName\":\"Agrimano, Rheanbelle C.\",\"department\":\"Information Technology\",\"period\":\"April 1-15, 2026\",\"transmittalType\":\"Main\",\"submittedBy\":\"basta\",\"dateSubmitted\":\"2026-05-11\",\"dateReceived\":\"2026-05-11\",\"verifiedBy\":\"basta\",\"verificationDate\":\"2026-05-11\",\"status\":\"Submitted\",\"remarks\":\"oks sha\",\"signatories\":[],\"signedBy\":[],\"processedBy\":\"Super Admin\",\"id\":1}', '::1', 'Agrimano, Rheanbelle C. was added.', 'OK', 0, '2026-05-11 02:23:04'),
(79, 1, 'Super Admin', 'DTR Added', 'OTHER', 'DTR', NULL, NULL, NULL, NULL, '::1', 'DTR of Agrimano, Rheanbelle C. (April 1-15, 2026) added.', 'OK', 0, '2026-05-11 02:23:04'),
(80, 1, 'Super Admin', 'Document Tracking Added', 'CREATE', '', 'document tracking', NULL, NULL, '{\"doc_type\":\"Travel Order\",\"doc_no\":\"123\",\"from_office\":\"me\",\"to_office\":\"HR Office\",\"date_forwarded\":\"2026-05-11\",\"date_received\":null,\"received_by\":\"Gonzales, Realyn P. (HR AMELA)\",\"status\":\"Pending\",\"remarks\":\"\"}', '::1', '123 was added.', 'OK', 0, '2026-05-11 02:39:16'),
(81, 1, 'Super Admin', 'Document Tracking Updated', 'UPDATE', '', 'document tracking', 1, '0', '{\"id\":\"1\",\"docType\":\"Travel Order\",\"docNo\":\"123\",\"from\":\"me\",\"to\":\"HR Office\",\"dateForwarded\":\"2026-05-11\",\"dateReceived\":\"2026-05-11\",\"receivedBy\":\"Gonzales, Realyn P. (HR AMELA)\",\"status\":\"Received\",\"remarks\":\"\"}', '::1', '123 was updated.', 'OK', 0, '2026-05-11 02:42:51'),
(82, 1, 'Super Admin', 'Signatory Updated', 'UPDATE', 'Signatory', 'signatories', 5, '0', '{\"id\":\"5\",\"name\":\"Thea Villanueva\",\"position\":\"HR Clerk II\",\"role\":\"DTR Processor\",\"department\":\"Human Resources\",\"order\":5,\"active\":false}', '::1', 'Thea Villanueva was updated.', 'OK', 0, '2026-05-11 02:43:25'),
(83, 1, 'Super Admin', 'Signatory Updated', 'UPDATE', 'Signatory', 'signatories', 5, '0', '{\"id\":\"5\",\"name\":\"Thea Villanueva\",\"position\":\"HR Clerk II\",\"role\":\"DTR Processor\",\"department\":\"Human Resources\",\"order\":5,\"active\":true}', '::1', 'Thea Villanueva was updated.', 'OK', 0, '2026-05-11 02:43:44');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) DEFAULT NULL COMMENT 'Short code e.g. NUR, MED',
  `description` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `code`, `description`, `active`, `created_at`, `updated_at`) VALUES
(1, 'Nursing', 'NUR', 'test', 1, '2026-04-24 05:35:47', '2026-04-24 05:38:43'),
(2, 'Medicine', 'MED', 'test', 1, '2026-04-24 05:37:53', '2026-04-24 05:37:53'),
(3, 'Information Technology', 'IT', 'test', 1, '2026-04-24 05:42:39', '2026-04-24 05:42:50'),
(4, 'Pharmacy', 'PHa', 'test', 1, '2026-05-07 05:09:25', '2026-05-07 05:09:25');

-- --------------------------------------------------------

--
-- Table structure for table `document_tracking`
--

CREATE TABLE `document_tracking` (
  `id` int(10) UNSIGNED NOT NULL,
  `doc_type` enum('DTR Transmittal','Leave Form','Travel Order','Payroll Document','Memorandum','Other') NOT NULL DEFAULT 'DTR Transmittal',
  `doc_no` varchar(50) NOT NULL,
  `from_office` varchar(100) NOT NULL,
  `to_office` varchar(100) NOT NULL,
  `date_forwarded` date NOT NULL,
  `date_received` date DEFAULT NULL,
  `received_by` varchar(100) DEFAULT NULL,
  `status` enum('Pending','In Transit','Received','Returned','Lost') NOT NULL DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_tracking`
--

INSERT INTO `document_tracking` (`id`, `doc_type`, `doc_no`, `from_office`, `to_office`, `date_forwarded`, `date_received`, `received_by`, `status`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 'Travel Order', '123', 'me', 'HR Office', '2026-05-11', '2026-05-11', 'Gonzales, Realyn P. (HR AMELA)', 'Received', '', '2026-05-11 02:39:16', '2026-05-11 02:42:51');

-- --------------------------------------------------------

--
-- Table structure for table `dtr_history`
--

CREATE TABLE `dtr_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `dtr_record_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK to dtr_records (nullable after delete)',
  `employee_no` varchar(20) NOT NULL,
  `employee_name` varchar(150) NOT NULL,
  `period` varchar(50) NOT NULL,
  `transmittal_type` enum('Main','Thea','Other') NOT NULL DEFAULT 'Main',
  `action` varchar(60) NOT NULL COMMENT 'e.g. DTR Submitted, DTR Updated, DTR Deleted',
  `status` varchar(30) NOT NULL DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `processed_by` varchar(100) NOT NULL DEFAULT 'System',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dtr_history`
--

INSERT INTO `dtr_history` (`id`, `dtr_record_id`, `employee_no`, `employee_name`, `period`, `transmittal_type`, `action`, `status`, `remarks`, `processed_by`, `created_at`) VALUES
(1, 1, 'GEAMH-C001', 'Agrimano, Rheanbelle C.', 'April 1-15, 2026', 'Main', 'DTR Submitted', 'Submitted', 'oks sha', 'Super Admin', '2026-05-11 02:23:04');

-- --------------------------------------------------------

--
-- Table structure for table `dtr_records`
--

CREATE TABLE `dtr_records` (
  `id` int(10) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED DEFAULT NULL,
  `employee_no` varchar(20) NOT NULL,
  `employee_name` varchar(150) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `period` varchar(50) NOT NULL COMMENT 'e.g. April 1-15, 2026',
  `transmittal_type` enum('Main','Thea','Other') NOT NULL DEFAULT 'Main',
  `submitted_by` varchar(100) DEFAULT NULL,
  `date_submitted` date DEFAULT NULL,
  `date_received` date DEFAULT NULL,
  `verified_by` varchar(100) DEFAULT NULL,
  `verification_date` date DEFAULT NULL,
  `status` enum('Pending','Submitted','Received','Verified','Returned') NOT NULL DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dtr_records`
--

INSERT INTO `dtr_records` (`id`, `employee_id`, `employee_no`, `employee_name`, `department`, `period`, `transmittal_type`, `submitted_by`, `date_submitted`, `date_received`, `verified_by`, `verification_date`, `status`, `remarks`, `created_at`, `updated_at`) VALUES
(1, NULL, 'GEAMH-C001', 'Agrimano, Rheanbelle C.', 'Information Technology', 'April 1-15, 2026', 'Main', 'basta', '2026-05-11', '2026-05-11', 'basta', '2026-05-11', 'Submitted', 'oks sha', '2026-05-11 02:23:04', '2026-05-11 02:23:04');

-- --------------------------------------------------------

--
-- Table structure for table `dtr_signatories`
--

CREATE TABLE `dtr_signatories` (
  `id` int(10) UNSIGNED NOT NULL,
  `dtr_id` int(10) UNSIGNED NOT NULL,
  `signatory_id` int(10) UNSIGNED NOT NULL,
  `signed` tinyint(1) NOT NULL DEFAULT 0,
  `signed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(10) UNSIGNED NOT NULL,
  `employee_no` varchar(20) NOT NULL COMMENT 'e.g. GEAMH-001',
  `last_name` varchar(60) NOT NULL,
  `first_name` varchar(60) NOT NULL,
  `middle_name` varchar(60) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `employment_status` enum('Permanent','Casual','Contractual','Job Order','Co-terminus') NOT NULL DEFAULT 'Casual',
  `date_hired` date DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `age` tinyint(3) UNSIGNED DEFAULT NULL,
  `gender` enum('Male','Female','') DEFAULT '',
  `civil_status` enum('Single','Married','Widowed','Separated','') DEFAULT '',
  `address` varchar(255) DEFAULT NULL,
  `contact_no` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sg_step` varchar(20) DEFAULT NULL COMMENT 'Salary Grade / Step',
  `tin_number` varchar(20) DEFAULT NULL,
  `sss_gsis_number` varchar(25) DEFAULT NULL,
  `phil_number` varchar(25) DEFAULT NULL COMMENT 'PhilHealth number',
  `pi_number` varchar(25) DEFAULT NULL COMMENT 'Pag-IBIG number',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_no`, `last_name`, `first_name`, `middle_name`, `position`, `designation`, `department`, `employment_status`, `date_hired`, `birth_date`, `age`, `gender`, `civil_status`, `address`, `contact_no`, `email`, `salary`, `sg_step`, `tin_number`, `sss_gsis_number`, `phil_number`, `pi_number`, `active`, `created_at`, `updated_at`) VALUES
(5, 'geamh-909', 'Ladua', 'Hesed', 'mark', '', '', '', 'Permanent', NULL, '1961-04-12', 0, '', 'Single', '', '', '', 0.00, '0', '', '', '', '', 1, '2026-04-24 01:52:04', '2026-04-28 01:39:54'),
(6, 'GEAMH-C001', 'Agrimano', 'Rheanbelle', 'C.', 'Administrative Aide III', '', 'Information Technology', 'Casual', NULL, '2002-05-30', 0, '', '', '', '', '', 0.00, '0', '', '', '', '', 1, '2026-04-24 03:45:20', '2026-05-04 07:08:40'),
(7, 'GEAMH-C002', 'Alcazar', 'Lesly Ann', 'P.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(8, 'GEAMH-C003', 'Almanzor', 'Kristel Jane', 'V.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(9, 'GEAMH-C004', 'Alvarez', 'Mariane Xyril', 'F.', 'Administrative Aide III', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(10, 'GEAMH-C005', 'Amurao', 'Chelsea', 'B.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(11, 'GEAMH-C006', 'Apostol', 'April Mae', '', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(12, 'GEAMH-C007', 'Aquiatan', 'Grace', 'A.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(13, 'GEAMH-C008', 'Arroyo', 'Mercy', 'C.', 'Social Welfare Assistant', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(14, 'GEAMH-C009', 'Asas', 'Lee Leigh', 'T.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(15, 'GEAMH-C010', 'Asas', 'Naira Jane', 'V.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(16, 'GEAMH-C011', 'Atas', 'Noel', 'M.', 'Administrative Aide I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(17, 'GEAMH-C012', 'Atijera', 'Edelyne Mae', 'B.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(18, 'GEAMH-C013', 'Aure', 'Chrystal Joice', 'B.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(19, 'GEAMH-C014', 'Austria', 'Airish', 'M.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(20, 'GEAMH-C015', 'Ayo', 'Marilyn', 'A.', 'Administrative Aide III', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(21, 'GEAMH-C016', 'Azarcon', 'Madelaine', 'V.', 'Pharmacist I', NULL, 'Pharmacy', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(22, 'GEAMH-C017', 'Bacsal', 'Feena', 'D.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(23, 'GEAMH-C018', 'Badian', 'Jaycelle', 'S.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(24, 'GEAMH-C019', 'Baladiang', 'Harold Jason', 'D.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(25, 'GEAMH-C020', 'Bale', 'Lylamie', 'R.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(26, 'GEAMH-C021', 'Balita', 'Edwin', 'S.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(27, 'GEAMH-C022', 'Bay', 'Michael', 'L.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(28, 'GEAMH-C023', 'Bersamina', 'Aebrille', 'C.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(29, 'GEAMH-C024', 'Billones', 'Lady Anne', 'P.', 'Medical Technologist I', NULL, 'Laboratory', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(30, 'GEAMH-C025', 'Bondal', 'Arvin', 'S.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(31, 'GEAMH-C026', 'Buendia', 'Pamela', 'T.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(32, 'GEAMH-C027', 'Bueno', 'Marion Joy', 'A.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(33, 'GEAMH-C028', 'Buhain', 'Madonna', 'T.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(34, 'GEAMH-C029', 'Buhay', 'Hazel Ann', 'D.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(35, 'GEAMH-C030', 'Buid', 'Ronnel', 'V.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(36, 'GEAMH-C031', 'Caacbay', 'Rodelynne', 'E.', 'Pharmacist I', NULL, 'Pharmacy', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(37, 'GEAMH-C032', 'Camarinta', 'Caryl', 'C.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(38, 'GEAMH-C033', 'Cayanan', 'Abigail', 'E.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(39, 'GEAMH-C034', 'Cayao', 'Ella Marie', 'M.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(40, 'GEAMH-C035', 'Cayao', 'Emerson', 'C.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(41, 'GEAMH-C036', 'Caymo', 'Myranhel', 'B.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(42, 'GEAMH-C037', 'Cervantes', 'Julienne Mirano', '', 'Pharmacist I', NULL, 'Pharmacy', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(43, 'GEAMH-C038', 'Comia', 'Irish', 'A.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(44, 'GEAMH-C039', 'Corral', 'Jennylyn', 'V.', 'Administrative Aide IV', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(45, 'GEAMH-C040', 'Costa', 'Marilyn', 'C.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(46, 'GEAMH-C041', 'Costelo', 'Reggie', 'C.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(47, 'GEAMH-C042', 'Cresido', 'Veronica', 'P.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(48, 'GEAMH-C043', 'Cresino', 'Mark Jay', 'R.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(49, 'GEAMH-C044', 'Crizaldo', 'Jonathan', 'C.', 'Administrative Aide IV', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(50, 'GEAMH-C045', 'Cubillo', 'Ma. Fatima', 'P.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(51, 'GEAMH-C046', 'Cueno', 'Haven Kyle', 'C.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(52, 'GEAMH-C047', 'Dabanda', 'Rosemarie', 'R.', 'Administrative Aide IV', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(53, 'GEAMH-C048', 'Dalisay', 'Chris Lester', 'C.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(54, 'GEAMH-C049', 'Dalisay', 'Fatima Joy', 'P.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(55, 'GEAMH-C050', 'De Borja', 'Verna Fe', 'M.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(56, 'GEAMH-C051', 'De Castro', 'ShaiNa', 'T.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(57, 'GEAMH-C052', 'De Castro', 'ShaiRa', 'T.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(58, 'GEAMH-C053', 'De Lara', 'Mary Christine', 'C.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(59, 'GEAMH-C054', 'De San Jose', 'Harlene', 'H.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(60, 'GEAMH-C055', 'De Vera', 'Joylyn', 'Z.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(61, 'GEAMH-C056', 'Decillo', 'Nida', 'J.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(62, 'GEAMH-C057', 'Dela Cruz', 'Maida', '', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(63, 'GEAMH-C058', 'Dela Cruz', 'Yvette', 'R.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(64, 'GEAMH-C059', 'Dela Pena', 'Wenilyn', 'M.', 'Physical Therapist I', NULL, 'Rehabilitation', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(65, 'GEAMH-C060', 'Delfin', 'Norman Vincent', 'R.', 'Physical Therapist I', NULL, 'Rehabilitation', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(66, 'GEAMH-C061', 'Delos Reyes', 'Kathy', 'M.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(67, 'GEAMH-C062', 'Delovino', 'Nadine', 'H.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(68, 'GEAMH-C063', 'Denosta', 'Genilyn', 'P.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(69, 'GEAMH-C064', 'Dilig', 'Imelda', 'R.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(70, 'GEAMH-C065', 'Dimapilis', 'Jake Kirby', 'L.', 'Pharmacist I', NULL, 'Pharmacy', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(71, 'GEAMH-C066', 'Dinglasan', 'Maribel', 'R.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(72, 'GEAMH-C067', 'Dolz', 'Camille', 'D.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(73, 'GEAMH-C068', 'Dumagat', 'Vernadeth', 'B.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(74, 'GEAMH-C069', 'Elicierto', 'Joan Michelle', 'L.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(75, 'GEAMH-C070', 'Eseque', 'Princess Joy', 'R.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(76, 'GEAMH-C071', 'Espiritu', 'Rowena', 'F.', 'Administrative Aide IV', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(77, 'GEAMH-C072', 'Estacio', 'Jennifer Mae Hayes', '', 'Medical Technologist I', NULL, 'Laboratory', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(78, 'GEAMH-C073', 'Fabre', 'Elaine', 'C.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(79, 'GEAMH-C074', 'Fabro', 'Ma. Jumeline', 'A.', 'Medical Technologist I', NULL, 'Laboratory', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(80, 'GEAMH-C075', 'Fello', 'Rencel Roan', 'C.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(81, 'GEAMH-C076', 'Feranil', 'Helbert', 'L.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(82, 'GEAMH-C077', 'Flores', 'Celerina', 'T.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(83, 'GEAMH-C078', 'Flores', 'Eidle Wise', 'B.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(84, 'GEAMH-C079', 'Flores', 'Gigi', 'F.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(85, 'GEAMH-C080', 'Flores', 'Maria Patrisha Nicole', '', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(86, 'GEAMH-C081', 'Flores', 'Reynaldo', 'B.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(87, 'GEAMH-C082', 'Fuaso', 'Joelee Larise Allen', 'L.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(88, 'GEAMH-C083', 'Gallego', 'Joan Heyniel', 'A.', 'Medical Technologist I', NULL, 'Laboratory', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(89, 'GEAMH-C084', 'Garcia', 'Lyra Gemma', 'M.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(90, 'GEAMH-C085', 'Golfo', 'Janelle', 'P.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(91, 'GEAMH-C086', 'Gonzales', 'Arjay', 'Y.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(92, 'GEAMH-C087', 'Gonzales', 'Daniel', 'P.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(93, 'GEAMH-C088', 'Gonzales', 'Lloyd', 'E.', 'Administrative Aide IV', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(94, 'GEAMH-C089', 'Gonzales', 'Tonette Bray', '', 'Physical Therapist I', NULL, 'Rehabilitation', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(95, 'GEAMH-C090', 'Guinto', 'Elvira', 'V.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(96, 'GEAMH-C091', 'Hernandez', 'Angelo Jehn Eidref', 'S.', 'Medical Technologist I', NULL, 'Laboratory', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(97, 'GEAMH-C092', 'Hernandez', 'Donato', 'Q.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(98, 'GEAMH-C093', 'Hernandez', 'Gabriel Kein', 'G.', 'Physical Therapist I', NULL, 'Rehabilitation', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(99, 'GEAMH-C094', 'Hernandez', 'Vanessa', 'B.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(100, 'GEAMH-C095', 'Herrera', 'Ethel Joy', 'G.', 'Administrative Aide IV', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(101, 'GEAMH-C096', 'Honrada', 'Rosafe', 'G.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(102, 'GEAMH-C097', 'Janolo', 'Russel Christian', 'E.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(103, 'GEAMH-C098', 'Jeciel', 'Daniel Vhince', 'M.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(104, 'GEAMH-C099', 'Junsay', 'Maria Menchie Loria', '', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(105, 'GEAMH-C100', 'Juson', 'Agnes Sanico', '', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(106, 'GEAMH-C101', 'Lascano', 'Elrish Laine', 'G.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(107, 'GEAMH-C102', 'Laure', 'Romeo Jr.', 'P.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(108, 'GEAMH-C103', 'Lavador', 'Edrize', 'A.', 'Social Welfare Assistant', NULL, 'Social Work', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(109, 'GEAMH-C104', 'Levita', 'Hariett Marian Ros', 'T.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(110, 'GEAMH-C105', 'Limbo', 'Edwin', 'P.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(111, 'GEAMH-C106', 'Lizardo', 'Jessica', 'B.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(112, 'GEAMH-C107', 'Macantan', 'Catherine', 'B.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(113, 'GEAMH-C108', 'Maderazo', 'Kim Gellyssa', 'D.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(114, 'GEAMH-C109', 'Magayam', 'Cleo Mae', 'B.', 'Medical Technologist I', NULL, 'Laboratory', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(115, 'GEAMH-C110', 'Malabanan', 'Jake Rojan', 'E.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(116, 'GEAMH-C111', 'Mendoza', 'Elionor Vargas', '', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(117, 'GEAMH-C112', 'Merca', 'Menerissa', 'R.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(118, 'GEAMH-C113', 'Mercado', 'Marnie Marie', 'B.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(119, 'GEAMH-C114', 'Moico', 'Princess May', 'P.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(120, 'GEAMH-C115', 'Mojica', 'Michelle', 'A.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(121, 'GEAMH-C116', 'Monton', 'Patrick', 'L.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(122, 'GEAMH-C117', 'Morada', 'Rowena Irish', 'A.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(123, 'GEAMH-C118', 'Murata', 'Rolf Nico', 'M.', 'Physical Therapist I', NULL, 'Rehabilitation', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(124, 'GEAMH-C119', 'Nicomedes', 'Ma. Cristina', 'M.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(125, 'GEAMH-C120', 'Niviar', 'Mark Carlo', 'A.', 'Medical Technologist I', NULL, 'Laboratory', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(126, 'GEAMH-C121', 'Nocidal', 'Henry', 'V.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(127, 'GEAMH-C122', 'Noel', 'Joanna Kristin', 'A.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(128, 'GEAMH-C123', 'Nuestro', 'Princess', 'R.', 'Social Welfare Assistant', NULL, 'Social Work', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(129, 'GEAMH-C124', 'Nueva', 'Jesusa', 'S.', 'Medical Technologist I', NULL, 'Laboratory', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(130, 'GEAMH-C125', 'Nueva', 'Kimberly Rose', 'B.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(131, 'GEAMH-C126', 'Nueva', 'Tyrone', 'P.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(132, 'GEAMH-C127', 'Pabalate', 'Digna', 'P.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(133, 'GEAMH-C128', 'Pacete', 'Cay Enriquez', '', 'Pharmacist I', NULL, 'Pharmacy', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(134, 'GEAMH-C129', 'Palmario', 'Prescila', 'G.', 'Administrative Aide IV', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(135, 'GEAMH-C130', 'Panganiban', 'Alfie', 'I.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(136, 'GEAMH-C131', 'Pejana', 'Roxanne', 'R.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(137, 'GEAMH-C132', 'Pendon', 'Alvin', 'R.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(138, 'GEAMH-C133', 'Penus', 'Maeka Penalba', '', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(139, 'GEAMH-C134', 'Perena', 'Gina', 'E.', 'Physical Therapist I', NULL, 'Rehabilitation', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(140, 'GEAMH-C135', 'Perena', 'Karen Louise', 'M.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(141, 'GEAMH-C136', 'Perin', 'Emma', 'R.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(142, 'GEAMH-C137', 'Pinazo', 'Katelyne', 'S.', 'Radiologic Technologist I', NULL, 'Radiology', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(143, 'GEAMH-C138', 'Poblete', 'Sheila Marie', 'P.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(144, 'GEAMH-C139', 'Puedan', 'Jenivic', 'E.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(145, 'GEAMH-C140', 'Punilas', 'Maria Beatriz', 'E.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(146, 'GEAMH-C141', 'Quijano', 'Renante', 'L.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(147, 'GEAMH-C142', 'Quirap', 'Marvin', 'S.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(148, 'GEAMH-C143', 'Rabanzo', 'Abegail', 'P.', 'Medical Technologist I', NULL, 'Laboratory', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(149, 'GEAMH-C144', 'Ramirez', 'Devy', '', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(150, 'GEAMH-C145', 'Ramos', 'Rosita', 'D.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(151, 'GEAMH-C146', 'Reyes', 'Clariza', 'B.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(152, 'GEAMH-C147', 'Reyes', 'Irish Mae Nabata', '', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(153, 'GEAMH-C148', 'Reyes', 'Maria Rhea', 'A.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(154, 'GEAMH-C149', 'Riego De Dios', 'Triccia Anne', 'M.', 'Medical Technologist I', NULL, 'Laboratory', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(155, 'GEAMH-C150', 'Rizo', 'Eddie Jr.', 'M.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(156, 'GEAMH-C151', 'Rodil', 'Bernard', 'A.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(157, 'GEAMH-C152', 'Rodil', 'Nicca', 'I.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(158, 'GEAMH-C153', 'Rom', 'Merly', 'C.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(159, 'GEAMH-C154', 'Romanes', 'Maybeljoy', 'C.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(160, 'GEAMH-C155', 'Salas', 'Ryza', 'G.', 'Medical Technologist I', NULL, 'Laboratory', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(161, 'GEAMH-C156', 'Salgado', 'Jay', 'R.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(162, 'GEAMH-C157', 'Saliedo', 'Richard', 'S.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(163, 'GEAMH-C158', 'Salvio', 'Melissa Joy', 'B.', 'Physical Therapist I', NULL, 'Rehabilitation', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(164, 'GEAMH-C159', 'Sanchez', 'Jaypee', 'H.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(165, 'GEAMH-C160', 'Santander', 'Ace', '', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(166, 'GEAMH-C161', 'Santos', 'Marjorie', 'G.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(167, 'GEAMH-C162', 'Secreto', 'Vilma', 'G.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(168, 'GEAMH-C163', 'Serrano', 'Ereca Jayomana', '', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(169, 'GEAMH-C164', 'Sibucao', 'Ma. Victoria', 'A.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(170, 'GEAMH-C165', 'Sierra', 'Ara Mina', 'M.', 'Medical Technologist I', NULL, 'Laboratory', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(171, 'GEAMH-C166', 'Signo', 'Lorie Antonette', 'D.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(172, 'GEAMH-C167', 'Simbahan', 'Lovely Lyl-Ann Sierra', '', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(173, 'GEAMH-C168', 'Socito', 'Kristel', 'E.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(174, 'GEAMH-C169', 'Sol', 'Christian Rafael', 'S.', 'Physical Therapist I', NULL, 'Rehabilitation', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(175, 'GEAMH-C170', 'Solis', 'Mylin', 'G.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(176, 'GEAMH-C171', 'Tafalla', 'Jess Jason', 'P.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(177, 'GEAMH-C172', 'Tagustos', 'Carizza Krisell', 'B.', 'Medical Technologist I', NULL, 'Laboratory', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(178, 'GEAMH-C173', 'Tuazon', 'Emma', 'C.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(179, 'GEAMH-C174', 'Valerio', 'Christianne Jane', 'L.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(180, 'GEAMH-C175', 'Viadoy', 'Concepcion', 'A.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(181, 'GEAMH-C176', 'Vicedo', 'Flordeliza', 'A.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(182, 'GEAMH-C177', 'Vicedo', 'Jhanine', 'P.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(183, 'GEAMH-C178', 'Vidallo', 'Upracio Jr.', 'A.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(184, 'GEAMH-C179', 'Villanueva', 'Ma Criselda', 'D.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(185, 'GEAMH-C180', 'Volante', 'Mary Avigail', 'A.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(186, 'GEAMH-C181', 'Aure', 'Kevin', 'R.', 'Nurse I', NULL, 'Medical Arts Building', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(187, 'GEAMH-C182', 'Acak', 'Rodelin Cabalfin', '', 'Administrative Aide I', NULL, 'Medical Arts Building', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(188, 'GEAMH-C183', 'Gatdula', 'Gat Rolliezen', '', 'Nurse I', NULL, 'Dialysis Extension Clinic', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(189, 'GEAMH-C184', 'Rollan', 'Darvin', '', 'Administrative Aide I', NULL, 'Dialysis Extension Clinic', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(190, 'KPFH-C001', 'Amparo', 'Rhoy', 'V.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(191, 'KPFH-C002', 'Amutan', 'Eunicaella', 'A.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(192, 'KPFH-C003', 'And', 'Daisy', 'Y.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(193, 'KPFH-C004', 'Anglo', 'Ian Rodel', 'B.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(194, 'KPFH-C005', 'Anglo', 'Joshua Christine', 'M.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(195, 'KPFH-C006', 'Asas', 'Mark Anthony', 'V.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(196, 'KPFH-C007', 'Baldoza', 'Jules Ashley', 'S.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(197, 'KPFH-C008', 'Bansales', 'Ma. Carmela', 'V.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(198, 'KPFH-C009', 'Belardo', 'Ailene Grace', 'F.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(199, 'KPFH-C010', 'Belleza', 'Maria Eden', 'C.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(200, 'KPFH-C011', 'Buale', 'Hazel', 'A.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(201, 'KPFH-C012', 'Bulatao', 'Sharon', 'B.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(202, 'KPFH-C013', 'Cabintoy', 'Clark', 'P.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(203, 'KPFH-C014', 'Cabrera', 'Andrew Louie', 'G.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(204, 'KPFH-C015', 'Calimutan', 'Girby Joe', 'C.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(205, 'KPFH-C016', 'Causaren', 'Jennilyn', 'C.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(206, 'KPFH-C017', 'Costa', 'Honeylyn', 'C.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(207, 'KPFH-C018', 'David', 'Rachel Anne', 'A.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(208, 'KPFH-C019', 'Denauto', 'Christine Angela', 'C.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(209, 'KPFH-C020', 'Ebojo', 'Stephanie Claire', 'B.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(210, 'KPFH-C021', 'Ersando', 'Diane Michelle', 'D.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(211, 'KPFH-C022', 'Escalante', 'Karen', 'P.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(212, 'KPFH-C023', 'Escano', 'Kith', 'E.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(213, 'KPFH-C024', 'Escober', 'Allianna Marie', 'P.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(214, 'KPFH-C025', 'Ganac', 'Sheen', 'S.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(215, 'KPFH-C026', 'Garcia', 'Juaymah', 'R.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(216, 'KPFH-C027', 'Glean', 'Jenelyn De Taza', '', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(217, 'KPFH-C028', 'Irinco', 'Geraldine', 'R.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(218, 'KPFH-C029', 'Jeciel', 'Jaypee', 'R.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(219, 'KPFH-C030', 'Limbitco', 'Louisa May', 'H.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(220, 'KPFH-C031', 'Madrid', 'Simon Archival', 'G.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(221, 'KPFH-C032', 'Malimban', 'Melba', 'O.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(222, 'KPFH-C033', 'Marges', 'Ma. Jamie', 'V.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(223, 'KPFH-C034', 'Nigoza', 'Marriah Realiesa Jayd', 'D.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(224, 'KPFH-C035', 'Orina', 'Joverlyn', 'B.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20');
INSERT INTO `employees` (`id`, `employee_no`, `last_name`, `first_name`, `middle_name`, `position`, `designation`, `department`, `employment_status`, `date_hired`, `birth_date`, `age`, `gender`, `civil_status`, `address`, `contact_no`, `email`, `salary`, `sg_step`, `tin_number`, `sss_gsis_number`, `phil_number`, `pi_number`, `active`, `created_at`, `updated_at`) VALUES
(225, 'KPFH-C036', 'Oriza', 'Joylene', 'R.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(226, 'KPFH-C037', 'Penalba', 'Kimberly', 'M.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(227, 'KPFH-C038', 'Perez', 'Jenelyn', 'A.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(228, 'KPFH-C039', 'Periodico', 'Aubrey Alliana', 'T.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(229, 'KPFH-C040', 'Perlado', 'Arish', 'R.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(230, 'KPFH-C041', 'Poniente', 'Jhay-Arr', 'L.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(231, 'KPFH-C042', 'Robles', 'Viktor Nigel', 'F.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(232, 'KPFH-C043', 'Rovillos', 'Joyce Ann', 'M.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(233, 'KPFH-C044', 'Sangalang', 'Mary Justinne', 'T.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(234, 'KPFH-C045', 'Singson', 'Louie Glenn', 'J.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(235, 'KPFH-C046', 'Soriano', 'Sherlie', 'S.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(236, 'KPFH-C047', 'Sortijas', 'Jacqueline', 'J.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(237, 'KPFH-C048', 'Sortijas', 'Leo Ray', 'P.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(238, 'KPFH-C049', 'Sumagui', 'Liezeth', 'P.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(239, 'KPFH-C050', 'Talay', 'Elaiza Mae', 'L.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(240, 'KPFH-C051', 'Tandoc', 'April Rose', 'D.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(241, 'KPFH-C052', 'Tapawan', 'Ansherina', 'A.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(242, 'KPFH-C053', 'Toledo', 'Kaizen Jay', 'R.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(243, 'KPFH-C054', 'Tupas', 'Alpha', 'M.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(244, 'KPFH-C055', 'Uy', 'Jasha Asshi', 'E.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(245, 'KPFH-C056', 'Asas', 'Monette', 'V.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(246, 'KPFH-C057', 'Salazar', 'Sofhia Bianca', 'B.', 'Nurse I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(247, 'KPFH-C058', 'Bataller', 'Christian Angelo', 'A.', 'Medical Technologist I', NULL, 'Laboratory', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(248, 'KPFH-C059', 'Briones', 'Maria Reinalyn', 'V.', 'Medical Technologist I', NULL, 'Laboratory', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(249, 'KPFH-C060', 'Capillo', 'Bethel Joy', 'C.', 'Medical Technologist I', NULL, 'Laboratory', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(250, 'KPFH-C061', 'Creencia', 'Japie Chester', 'M.', 'Medical Technologist I', NULL, 'Laboratory', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(251, 'KPFH-C062', 'Libiran', 'Marie Keicelyn', 'R.', 'Medical Technologist I', NULL, 'Laboratory', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(252, 'KPFH-C063', 'Liveta', 'Camella', 'L.', 'Medical Technologist I', NULL, 'Laboratory', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(253, 'KPFH-C064', 'Poblete', 'Suzaine', 'R.', 'Medical Technologist I', NULL, 'Laboratory', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(254, 'KPFH-C065', 'Ramirez', 'Michelle', 'S.', 'Medical Technologist I', NULL, 'Laboratory', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(255, 'KPFH-C066', 'Embudo', 'April Anne', 'S.', 'Pharmacist I', NULL, 'Pharmacy', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(256, 'KPFH-C067', 'Fojas', 'Gail Allyson', 'M.', 'Pharmacist I', NULL, 'Pharmacy', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(257, 'KPFH-C068', 'Mendoza', 'Luisa', 'T.', 'Pharmacist I', NULL, 'Pharmacy', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(258, 'KPFH-C069', 'Gan', 'Valerie', 'A.', 'Radiologic Technologist I', NULL, 'Radiology', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(259, 'KPFH-C070', 'Estilloso', 'Marvy Jay', 'V.', 'Radiologic Technologist I', NULL, 'Radiology', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(260, 'KPFH-C071', 'Jose', 'Resilyn', 'S.', 'Radiologic Technologist I', NULL, 'Radiology', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(261, 'KPFH-C072', 'Manuel', 'Monica', 'T.', 'Radiologic Technologist I', NULL, 'Radiology', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(262, 'KPFH-C073', 'Pascua', 'Marissa', 'U.', 'Radiologic Technologist I', NULL, 'Radiology', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(263, 'KPFH-C074', 'Sarmiento', 'John Rae', 'R.', 'Radiologic Technologist I', NULL, 'Radiology', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(264, 'KPFH-C075', 'Bawalan', 'Jerico Noelle', 'A.', 'Radiologic Technologist I', NULL, 'Radiology', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(265, 'KPFH-C076', 'Colorado', 'Rhodora', 'R.', 'Social Welfare Assistant', NULL, 'Social Work', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(266, 'KPFH-C077', 'Ortez', 'Albert Bryan', 'P.', 'Administrative Aide IV', NULL, 'Maintenance', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(267, 'KPFH-C078', 'Lugay', 'Herbert', 'C.', 'Computer Operator II', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(268, 'KPFH-C079', 'Bates', 'July', 'A.', 'Computer Operator I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(269, 'KPFH-C080', 'Abaredes', 'Malaya', 'A.', 'Nursing Attendant I', '', 'Nursing', 'Casual', '2010-02-08', NULL, 0, '', '', '', '', '', 0.00, '0', '', '', '', '', 1, '2026-04-24 03:45:20', '2026-05-08 01:19:23'),
(270, 'KPFH-C081', 'Agner', 'Analyn', 'P.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(271, 'KPFH-C082', 'Alviz', 'Hilda', 'C.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(272, 'KPFH-C083', 'Ampon', 'Lalota', 'O.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(273, 'KPFH-C084', 'Babaan', 'Ma. Ronnamae', '', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(274, 'KPFH-C085', 'Brucal', 'Rodielyn', 'M.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(275, 'KPFH-C086', 'Buclatin', 'Dairiegold', 'S.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(276, 'KPFH-C087', 'Dariagan', 'Chyda', 'D.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(277, 'KPFH-C088', 'Ersando', 'Vivian', 'S.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(278, 'KPFH-C089', 'Escosia', 'Lea', 'D.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(279, 'KPFH-C090', 'Espanola', 'Asuncion', 'N.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(280, 'KPFH-C091', 'Isaias', 'Nenita', 'R.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(281, 'KPFH-C092', 'Lachica', 'Marites', 'U.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(282, 'KPFH-C093', 'Laroa', 'Patrick', 'S.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(283, 'KPFH-C094', 'Leonor', 'Michellenie', 'M.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(284, 'KPFH-C095', 'Limare', 'Kindly', 'V.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(285, 'KPFH-C096', 'Lubigan', 'Jasmin', 'D.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(286, 'KPFH-C097', 'Mabuti', 'Jerrybel', 'L.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(287, 'KPFH-C098', 'Manalo', 'Jaramie', 'E.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(288, 'KPFH-C099', 'Mangalinao', 'Khrisha Mhel', 'L.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(289, 'KPFH-C100', 'Marquez', 'Charina', 'C.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(290, 'KPFH-C101', 'Matiag', 'Loida', 'E.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(291, 'KPFH-C102', 'Mendoza', 'Melinda', 'S.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(292, 'KPFH-C103', 'Oli', 'Rachel', 'L.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(293, 'KPFH-C104', 'Parco', 'Jecelito', 'S.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(294, 'KPFH-C105', 'Pocua', 'Jude', 'J.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(295, 'KPFH-C106', 'Ramirez', 'Sherry Rose', 'N.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(296, 'KPFH-C107', 'Rementas', 'Celzie Anne', 'M.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(297, 'KPFH-C108', 'Roguel', 'Rosemarie', 'S.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(298, 'KPFH-C109', 'Romero', 'Maribel', 'M.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(299, 'KPFH-C110', 'Saligumba', 'Jovelyn', 'S.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(300, 'KPFH-C111', 'Sangalang', 'Marjorie', 'O.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(301, 'KPFH-C112', 'Sierra', 'Ferdinand Nimroh Noah', '', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(302, 'KPFH-C113', 'Tan', 'Quennie', 'U.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(303, 'KPFH-C114', 'Vicedo', 'Marlene', 'B.', 'Nursing Attendant I', NULL, 'Nursing', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(304, 'KPFH-C115', 'Andam', 'Lionel Carlo', 'P.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(305, 'KPFH-C116', 'Asas', 'Joselito', 'C.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(306, 'KPFH-C117', 'Aspecto', 'Rosemarie', 'R.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(307, 'KPFH-C118', 'Avinante', 'Russel Bryell', 'E.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(308, 'KPFH-C119', 'Baclayo', 'April Joy', 'G.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(309, 'KPFH-C120', 'Bencito', 'Christopher', 'A.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(310, 'KPFH-C121', 'Calitis', 'Diana', 'D.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(311, 'KPFH-C122', 'Carza', 'Ferdinand', 'C.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(312, 'KPFH-C123', 'Cuesta', 'Ma. Cristina', 'V.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(313, 'KPFH-C124', 'Diloy', 'Gianne', 'D.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(314, 'KPFH-C125', 'Ersando', 'Anna Patrisha', 'N.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(315, 'KPFH-C126', 'Feudo', 'Carol', 'F.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(316, 'KPFH-C127', 'Lampadio', 'Rachelle', 'R.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(317, 'KPFH-C128', 'Lubigan', 'Jon Mariner', 'N.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(318, 'KPFH-C129', 'Pagtalunan', 'Leonardo Jr.', 'B.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(319, 'KPFH-C130', 'Penus', 'Liezel', 'M.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(320, 'KPFH-C131', 'Poniente', 'Maricris', 'L.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(321, 'KPFH-C132', 'Romanes', 'Rico', 'C.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(322, 'KPFH-C133', 'Torres', 'Joseph', 'G.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(323, 'KPFH-C134', 'Talagtag', 'Judith', 'M.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(324, 'KPFH-C135', 'Alano', 'Jennifer', 'A.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(325, 'KPFH-C136', 'Andion', 'Amela Remia', 'V.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(326, 'KPFH-C137', 'Aribal', 'Raquel', 'L.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(327, 'KPFH-C138', 'Arzabal', 'Bernagrace', 'N.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(328, 'KPFH-C139', 'Badian', 'Carlo', 'M.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(329, 'KPFH-C140', 'Bashan', 'Marjorie', 'S.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(330, 'KPFH-C141', 'Baylon', 'Randy', 'B.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(331, 'KPFH-C142', 'Bermas', 'Monica', 'B.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(332, 'KPFH-C143', 'Dela Cruz', 'Mary Grace', 'F.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(333, 'KPFH-C144', 'Dumadag', 'Emmie', 'I.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(334, 'KPFH-C145', 'Galduen', 'Reingelbert', 'Z.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(335, 'KPFH-C146', 'Guevarra', 'Besinisa', 'M.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(336, 'KPFH-C147', 'Hondo', 'Jennilyn', 'R.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(337, 'KPFH-C148', 'Jeciel', 'Jonathan', 'E.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(338, 'KPFH-C149', 'Marasigan', 'Mae Ann', 'A.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(339, 'KPFH-C150', 'Padua', 'Shariel Ann', 'S.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(340, 'KPFH-C151', 'Penus', 'Eva', 'R.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(341, 'KPFH-C152', 'Rala', 'Lalaine', 'A.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(342, 'KPFH-C153', 'Ramos', 'Jerson', 'J.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(343, 'KPFH-C154', 'Rom', 'Marineth', 'V.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(344, 'KPFH-C155', 'Roraldo', 'Jenievabes', 'D.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(345, 'KPFH-C156', 'Roxas', 'Michelle', 'P.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(346, 'KPFH-C157', 'Sadava', 'Jesur Jr.', 'M.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(347, 'KPFH-C158', 'Sallutan', 'Sherwin', 'R.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(348, 'KPFH-C159', 'Tongson', 'Melver Ace', 'N.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(349, 'KPFH-C160', 'Vivero', 'Daisy', 'R.', 'Administrative Aide III', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(350, 'KPFH-C161', 'Baldestamon', 'Gina', 'D.', 'Administrative Aide II', NULL, 'Laboratory', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(351, 'KPFH-C162', 'Ignaco', 'Marry Grace', 'V.', 'Administrative Aide II', NULL, 'Laboratory', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(352, 'KPFH-C163', 'Antonio', 'Larvie', 'H.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(353, 'KPFH-C164', 'Asas', 'Ayala', 'M.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(354, 'KPFH-C165', 'Asas', 'King James R. II', '', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(355, 'KPFH-C166', 'Cantanero', 'Darwin', 'C.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(356, 'KPFH-C167', 'Chavez', 'Allan', 'R.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(357, 'KPFH-C168', 'Gabas', 'Narciso', 'D.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(358, 'KPFH-C169', 'Jacob', 'Joseph', 'P.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(359, 'KPFH-C170', 'Llamado', 'Gerry', 'V.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(360, 'KPFH-C171', 'Maligmat', 'Jonald', 'Q.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(361, 'KPFH-C172', 'Retutar', 'Jemeniano', 'C.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(362, 'KPFH-C173', 'Romero', 'Radie', 'M.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(363, 'KPFH-C174', 'Romilla', 'Richard', 'A.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(364, 'KPFH-C175', 'Tubid', 'Alexander', 'L.', 'Administrative Aide I', NULL, 'Administrative', 'Casual', NULL, NULL, NULL, '', '', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-24 03:45:20', '2026-04-24 03:45:20'),
(366, 'GEAMH-666', 'Sismaet', 'Angeline', 'Dondoyano', 'Nurse IV', '', 'Nursing', '', '2026-02-25', '2004-10-06', 0, '', 'Married', 'Ormoc City', '09909090900', 'angel@geamh.gov.ph', 0.00, '0', '', '', '', '', 1, '2026-05-09 05:50:37', '2026-05-09 05:52:55');

-- --------------------------------------------------------

--
-- Table structure for table `leave_records`
--

CREATE TABLE `leave_records` (
  `id` int(10) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED DEFAULT NULL,
  `employee_no` varchar(20) NOT NULL,
  `employee_name` varchar(150) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `leave_type` enum('Vacation Leave','Sick Leave','Maternity Leave','Paternity Leave','Special Privilege Leave','Special Leave','Forced Leave','Emergency Leave','Study Leave','VAWC Leave','Rehabilitation Leave','Terminal Leave') NOT NULL DEFAULT 'Vacation Leave',
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `days` decimal(5,1) NOT NULL DEFAULT 1.0,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Approved','Disapproved','Cancelled') NOT NULL DEFAULT 'Pending',
  `approved_by` varchar(100) DEFAULT NULL,
  `date_approved` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_records`
--

INSERT INTO `leave_records` (`id`, `employee_id`, `employee_no`, `employee_name`, `department`, `leave_type`, `date_from`, `date_to`, `days`, `reason`, `status`, `approved_by`, `date_approved`, `remarks`, `created_at`, `updated_at`) VALUES
(3, 6, 'GEAMH-C001', 'Agrimano, Rheanbelle C.', 'Information Technology', 'Vacation Leave', '2026-05-30', '2026-05-30', 1.0, '', 'Pending', '', NULL, '', '2026-05-08 07:20:27', '2026-05-08 07:20:27'),
(4, 366, 'GEAMH-666', 'Sismaet, Angeline Dondoyano', 'Nursing', 'Forced Leave', '0000-00-00', '0000-00-00', 8.0, '', 'Pending', NULL, NULL, '', '2026-05-09 06:48:55', '2026-05-09 06:57:28');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_records`
--

CREATE TABLE `payroll_records` (
  `id` int(10) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED DEFAULT NULL,
  `employee_no` varchar(20) NOT NULL,
  `employee_name` varchar(150) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `period` varchar(10) NOT NULL COMMENT 'YYYY-MM format',
  `period_label` varchar(50) DEFAULT NULL COMMENT 'e.g. April 2026',
  `basic_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `pera` decimal(10,2) NOT NULL DEFAULT 2000.00,
  `rata` decimal(10,2) NOT NULL DEFAULT 0.00,
  `overtime` decimal(10,2) NOT NULL DEFAULT 0.00,
  `night_diff` decimal(10,2) NOT NULL DEFAULT 0.00,
  `gross_pay` decimal(12,2) NOT NULL DEFAULT 0.00,
  `withholding_tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `gsis` decimal(10,2) NOT NULL DEFAULT 0.00,
  `philhealth` decimal(10,2) NOT NULL DEFAULT 0.00,
  `pagibig` decimal(10,2) NOT NULL DEFAULT 100.00,
  `total_deductions` decimal(12,2) NOT NULL DEFAULT 0.00,
  `net_pay` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('Pending','Released','On Hold') NOT NULL DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(10) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED DEFAULT NULL,
  `employee_no` varchar(20) NOT NULL,
  `employee_name` varchar(150) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `shift` enum('Morning','Afternoon','Night','Split','Flexible') NOT NULL DEFAULT 'Morning',
  `shift_time` varchar(60) DEFAULT NULL COMMENT 'e.g. 07:00 AM - 03:00 PM',
  `days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Array of day abbreviations e.g. ["Mon","Tue"]' CHECK (json_valid(`days`)),
  `effective_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `rest_day` varchar(60) DEFAULT 'Saturday, Sunday',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `employee_id`, `employee_no`, `employee_name`, `department`, `shift`, `shift_time`, `days`, `effective_date`, `end_date`, `rest_day`, `created_at`, `updated_at`) VALUES
(3, NULL, 'GEAMH-C001', 'Agrimano, Rheanbelle', 'Information Technology', 'Morning', '07:00 AM - 03:00 PM', '[\"Mon\",\"Tue\",\"Wed\",\"Thu\",\"Sun\"]', NULL, NULL, 'Fri, Sat', '2026-04-24 07:12:03', '2026-04-24 07:12:03'),
(4, NULL, 'GEAMH-C003', 'Almanzor, Kristel Jane', 'Nursing', 'Morning', '07:00 AM - 03:00 PM', '[\"Mon\",\"Tue\",\"Wed\"]', NULL, NULL, 'Thu, Fri, Sat, Sun', '2026-04-24 07:13:05', '2026-04-24 07:13:05'),
(5, NULL, 'GEAMH-C001', 'Agrimano, Rheanbelle Marie', 'Information Technology', 'Morning', '07:00 AM - 03:00 PM', '[\"Mon\",\"Tue\",\"Wed\",\"Thu\",\"Sat\",\"Sun\"]', NULL, NULL, 'Fri', '2026-04-30 01:23:54', '2026-04-30 01:23:54');

-- --------------------------------------------------------

--
-- Table structure for table `signatories`
--

CREATE TABLE `signatories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL COMMENT 'e.g. Final Approver, HR Approver',
  `department` varchar(100) DEFAULT NULL,
  `signing_order` tinyint(4) NOT NULL DEFAULT 1,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `signatories`
--

INSERT INTO `signatories` (`id`, `name`, `position`, `role`, `department`, `signing_order`, `active`, `created_at`, `updated_at`) VALUES
(1, 'Dr. Maria Reyes', 'Chief of Hospital', 'Final Approver', 'Office of the Chief', 1, 1, '2026-04-22 08:15:22', '2026-04-22 08:15:22'),
(2, 'Mr. Jose Santos', 'HR Officer IV', 'HR Approver', 'Human Resources', 2, 1, '2026-04-22 08:15:22', '2026-04-22 08:15:22'),
(3, 'Ms. Ana Bautista', 'Administrative Officer V', 'Admin Approver', 'Administrative', 3, 1, '2026-04-22 08:15:22', '2026-04-22 08:15:22'),
(4, 'Mr. Pedro Cruz', 'Accountant III', 'Finance Approver', 'Finance', 4, 1, '2026-04-22 08:15:22', '2026-04-22 08:15:22'),
(5, 'Thea Villanueva', 'HR Clerk II', 'DTR Processor', 'Human Resources', 5, 1, '2026-04-22 08:15:22', '2026-05-11 02:43:44');

-- --------------------------------------------------------

--
-- Table structure for table `trainings`
--

CREATE TABLE `trainings` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `category` enum('Medical','Nursing','Administrative','Technical','Leadership','Safety','Other') NOT NULL DEFAULT 'Medical',
  `instructor` varchar(150) DEFAULT NULL,
  `venue` varchar(200) DEFAULT NULL,
  `date_from` date NOT NULL,
  `date_to` date DEFAULT NULL,
  `duration` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'Number of days',
  `max_participants` smallint(6) NOT NULL DEFAULT 30,
  `enrolled` smallint(6) NOT NULL DEFAULT 0,
  `status` enum('Upcoming','Ongoing','Completed','Cancelled') NOT NULL DEFAULT 'Upcoming',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trainings`
--

INSERT INTO `trainings` (`id`, `title`, `category`, `instructor`, `venue`, `date_from`, `date_to`, `duration`, `max_participants`, `enrolled`, `status`, `description`, `created_at`, `updated_at`) VALUES
(1, 'IT Training', 'Technical', 'Mr. Herbert Lugay', 'OPHO', '2026-04-30', '2026-04-30', 1, 30, 1, 'Upcoming', 'test', '2026-04-24 07:46:01', '2026-04-24 07:54:52'),
(2, 'HR Training', 'Administrative', '', 'OPHO', '2026-05-02', '2026-05-03', 2, 30, 0, 'Upcoming', 'TEST', '2026-04-24 07:49:25', '2026-04-24 07:49:25');

-- --------------------------------------------------------

--
-- Table structure for table `training_participants`
--

CREATE TABLE `training_participants` (
  `id` int(10) UNSIGNED NOT NULL,
  `training_id` int(10) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
  `attended` tinyint(1) NOT NULL DEFAULT 0,
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `training_participants`
--

INSERT INTO `training_participants` (`id`, `training_id`, `employee_id`, `attended`, `remarks`) VALUES
(1, 1, 6, 1, '');

-- --------------------------------------------------------

--
-- Table structure for table `travel_orders`
--

CREATE TABLE `travel_orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED DEFAULT NULL,
  `employee_no` varchar(20) NOT NULL,
  `employee_name` varchar(150) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `destination` varchar(255) NOT NULL,
  `purpose` text DEFAULT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `days` tinyint(4) NOT NULL DEFAULT 1,
  `transport` enum('Public Transport','Government Vehicle','Private Vehicle') NOT NULL DEFAULT 'Public Transport',
  `approved_by` varchar(100) DEFAULT NULL,
  `status` enum('Pending','Approved','Disapproved') NOT NULL DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `travel_orders`
--

INSERT INTO `travel_orders` (`id`, `employee_id`, `employee_no`, `employee_name`, `department`, `destination`, `purpose`, `date_from`, `date_to`, `days`, `transport`, `approved_by`, `status`, `remarks`, `created_at`, `updated_at`) VALUES
(1, NULL, '666', 'Angeline', 'Nursing', 'Ecert', 'secret', '0000-00-00', '0000-00-00', 1, 'Public Transport', NULL, 'Pending', '', '2026-05-09 08:14:48', '2026-05-09 08:14:48');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(64) NOT NULL COMMENT 'SHA-256 hash',
  `name` varchar(100) NOT NULL,
  `role` enum('Super Admin','Admin') NOT NULL DEFAULT 'Admin',
  `department` varchar(100) NOT NULL DEFAULT 'Human Resources',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `name`, `role`, `department`, `active`, `created_at`, `updated_at`) VALUES
(1, 'superadmin', 'e34f92a20532a873cb3184398070b4b82a8fa29cf48572c203dc5f0fa6158231', 'Super Admin', 'Super Admin', 'Human Resources', 1, '2026-04-22 08:15:22', '2026-04-22 08:15:22'),
(2, 'admin', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', 'HR Admin', 'Admin', 'Human Resources', 1, '2026-04-22 08:15:22', '2026-04-22 08:15:22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ai_scanned_docs`
--
ALTER TABLE `ai_scanned_docs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ai_user` (`uploaded_by`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_log_module` (`module`),
  ADD KEY `idx_log_user` (`user_id`),
  ADD KEY `idx_log_created` (`created_at`),
  ADD KEY `idx_log_action_type` (`action_type`),
  ADD KEY `idx_log_affected_table` (`affected_table`),
  ADD KEY `idx_log_record_id` (`record_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_dept_name` (`name`),
  ADD KEY `idx_dept_active` (`active`);

--
-- Indexes for table `document_tracking`
--
ALTER TABLE `document_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_track_docno` (`doc_no`),
  ADD KEY `idx_track_status` (`status`);

--
-- Indexes for table `dtr_history`
--
ALTER TABLE `dtr_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dtrh_emp` (`employee_no`),
  ADD KEY `idx_dtrh_action` (`action`),
  ADD KEY `idx_dtrh_date` (`created_at`);

--
-- Indexes for table `dtr_records`
--
ALTER TABLE `dtr_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dtr_emp` (`employee_no`),
  ADD KEY `idx_dtr_status` (`status`),
  ADD KEY `idx_dtr_period` (`period`),
  ADD KEY `fk_dtr_emp` (`employee_id`);

--
-- Indexes for table `dtr_signatories`
--
ALTER TABLE `dtr_signatories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_dtr_sig` (`dtr_id`,`signatory_id`),
  ADD KEY `fk_dtrsig_sig` (`signatory_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_no` (`employee_no`),
  ADD KEY `idx_emp_no` (`employee_no`),
  ADD KEY `idx_dept` (`department`),
  ADD KEY `idx_status` (`employment_status`),
  ADD KEY `idx_bdate` (`birth_date`);

--
-- Indexes for table `leave_records`
--
ALTER TABLE `leave_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_leave_emp` (`employee_no`),
  ADD KEY `idx_leave_status` (`status`),
  ADD KEY `idx_leave_dates` (`date_from`,`date_to`),
  ADD KEY `fk_leave_emp` (`employee_id`);

--
-- Indexes for table `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pay_emp` (`employee_no`),
  ADD KEY `idx_pay_period` (`period`),
  ADD KEY `idx_pay_status` (`status`),
  ADD KEY `fk_pay_emp` (`employee_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sched_emp` (`employee_no`),
  ADD KEY `idx_sched_dept` (`department`),
  ADD KEY `fk_sched_emp` (`employee_id`);

--
-- Indexes for table `signatories`
--
ALTER TABLE `signatories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sig_order` (`signing_order`);

--
-- Indexes for table `trainings`
--
ALTER TABLE `trainings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_train_status` (`status`),
  ADD KEY `idx_train_category` (`category`),
  ADD KEY `idx_train_date` (`date_from`);

--
-- Indexes for table `training_participants`
--
ALTER TABLE `training_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_train_emp` (`training_id`,`employee_id`),
  ADD KEY `fk_tp_emp` (`employee_id`);

--
-- Indexes for table `travel_orders`
--
ALTER TABLE `travel_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_to_emp` (`employee_no`),
  ADD KEY `idx_to_status` (`status`),
  ADD KEY `fk_to_emp` (`employee_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ai_scanned_docs`
--
ALTER TABLE `ai_scanned_docs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `document_tracking`
--
ALTER TABLE `document_tracking`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `dtr_history`
--
ALTER TABLE `dtr_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `dtr_records`
--
ALTER TABLE `dtr_records`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `dtr_signatories`
--
ALTER TABLE `dtr_signatories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=368;

--
-- AUTO_INCREMENT for table `leave_records`
--
ALTER TABLE `leave_records`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payroll_records`
--
ALTER TABLE `payroll_records`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `signatories`
--
ALTER TABLE `signatories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `trainings`
--
ALTER TABLE `trainings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `training_participants`
--
ALTER TABLE `training_participants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `travel_orders`
--
ALTER TABLE `travel_orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ai_scanned_docs`
--
ALTER TABLE `ai_scanned_docs`
  ADD CONSTRAINT `fk_ai_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `dtr_records`
--
ALTER TABLE `dtr_records`
  ADD CONSTRAINT `fk_dtr_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `dtr_signatories`
--
ALTER TABLE `dtr_signatories`
  ADD CONSTRAINT `fk_dtrsig_dtr` FOREIGN KEY (`dtr_id`) REFERENCES `dtr_records` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dtrsig_sig` FOREIGN KEY (`signatory_id`) REFERENCES `signatories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_records`
--
ALTER TABLE `leave_records`
  ADD CONSTRAINT `fk_leave_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD CONSTRAINT `fk_pay_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `fk_sched_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `training_participants`
--
ALTER TABLE `training_participants`
  ADD CONSTRAINT `fk_tp_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tp_training` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `travel_orders`
--
ALTER TABLE `travel_orders`
  ADD CONSTRAINT `fk_to_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
