-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 22, 2025 at 06:15 PM
-- Server version: 8.0.37
-- PHP Version: 8.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wushuspo_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('admin','super_admin') COLLATE utf8mb4_unicode_ci DEFAULT 'admin',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$XSq8AIXTO7Fk6batfthTa.L8IpgUsfEOG35LwXUuPCmVci23tD8Nm', 'System Administrator', 'admin@portal.com', 'super_admin', '2025-12-21 10:12:20', '2025-12-21 10:12:20');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `class_id` int NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('present','absent','late','excused') COLLATE utf8mb4_unicode_ci DEFAULT 'present',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `marked_by` int DEFAULT NULL COMMENT 'Admin ID who marked attendance',
  `marked_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_attendance` (`student_id`,`class_id`,`attendance_date`),
  KEY `marked_by` (`marked_by`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_class_id` (`class_id`),
  KEY `idx_attendance_date` (`attendance_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
CREATE TABLE IF NOT EXISTS `classes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `class_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `day_of_week` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `monthly_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `class_code` (`class_code`),
  KEY `idx_class_code` (`class_code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `class_code`, `class_name`, `description`, `day_of_week`, `start_time`, `end_time`, `monthly_fee`, `status`, `created_at`, `updated_at`) VALUES
(1, 'WSA-WED-8PM', 'Wushu Sport Academy: Wed 8pm-10pm', '(C 和 太极套路)', 'Wednesday', '20:00:00', '22:00:00', 120.00, 'active', '2025-12-21 10:29:59', '2025-12-21 10:29:59'),
(2, 'WSA-SUN-10AM', 'Wushu Sport Academy: Sun 10am-12pm', '(A/B/C/D 传统和太极套路)', 'Sunday', '10:00:00', '12:00:00', 120.00, 'active', '2025-12-21 10:30:30', '2025-12-21 10:30:30'),
(3, 'WSA-SUN-1PM', 'Wushu Sport Academy: Sun 1pm-3pm', '(C/D 和太极套路)', 'Sunday', '13:00:00', '15:00:00', 120.00, 'active', '2025-12-21 10:31:05', '2025-12-21 10:31:05'),
(4, 'PC2-TUE-8PM', 'SJK(C) Puay Chai 2: Tue 8pm-10pm', '(A/B/C 和 传统套路)', 'Tuesday', '20:00:00', '22:00:00', 120.00, 'active', '2025-12-21 10:31:42', '2025-12-21 10:31:42'),
(5, 'PC2-WED-8PM', 'SJK(C) Puay Chai 2: Wed 8pm-10pm', '全部组别 All Groups (A/B/C/D 套路) 没有太极 和 没有传统', 'Wednesday', '20:00:00', '22:00:00', 120.00, 'active', '2025-12-21 10:32:17', '2025-12-21 10:32:17'),
(6, 'PC2-FRI-8PM', 'SJK(C) Puay Chai 2: Wed 8pm-10pm', '太极套路而已', 'Wednesday', '20:00:00', '22:00:00', 120.00, 'active', '2025-12-21 10:32:57', '2025-12-21 10:32:57');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

DROP TABLE IF EXISTS `enrollments`;
CREATE TABLE IF NOT EXISTS `enrollments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `class_id` int NOT NULL,
  `enrollment_date` date NOT NULL,
  `status` enum('active','inactive','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_enrollment` (`student_id`,`class_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_class_id` (`class_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` int NOT NULL,
  `class_id` int DEFAULT NULL,
  `class_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Class code for easier reference (e.g., wsa-sun-10am)',
  `parent_account_id` int DEFAULT NULL COMMENT 'Parent responsible for payment',
  `invoice_type` enum('monthly_fee','registration','equipment','event','other') COLLATE utf8mb4_unicode_ci DEFAULT 'monthly_fee',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_month` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Format: YYYY-MM or text like January 2025',
  `due_date` date NOT NULL,
  `status` enum('unpaid','pending','paid','overdue','cancelled','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'unpaid',
  `paid_date` datetime DEFAULT NULL,
  `sent_date` datetime DEFAULT NULL COMMENT 'When invoice notification was sent',
  `created_by` int DEFAULT NULL COMMENT 'Admin ID who created invoice',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `class_id` (`class_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_invoice_number` (`invoice_number`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_parent_account_id` (`parent_account_id`),
  KEY `idx_status` (`status`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_payment_month` (`payment_month`),
  KEY `idx_class_code` (`class_code`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parent_accounts`
--

DROP TABLE IF EXISTS `parent_accounts`;
CREATE TABLE IF NOT EXISTS `parent_accounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parent_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Format: PAR-YYYY-0001',
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ic_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','inactive','suspended') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `parent_id` (`parent_id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parent_child_relationships`
--

DROP TABLE IF EXISTS `parent_child_relationships`;
CREATE TABLE IF NOT EXISTS `parent_child_relationships` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parent_id` int NOT NULL,
  `student_id` int NOT NULL,
  `relationship` enum('father','mother','guardian','other') COLLATE utf8mb4_unicode_ci DEFAULT 'guardian',
  `is_primary` tinyint(1) DEFAULT '0' COMMENT 'Primary contact parent',
  `can_manage_payments` tinyint(1) DEFAULT '1',
  `can_view_attendance` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_parent_student` (`parent_id`,`student_id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_student_id` (`student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `class_id` int DEFAULT NULL,
  `invoice_id` int DEFAULT NULL COMMENT 'Links payment to specific invoice',
  `parent_account_id` int DEFAULT NULL COMMENT 'Parent who made the payment',
  `amount` decimal(10,2) NOT NULL,
  `payment_month` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Format: YYYY-MM or text',
  `payment_date` date DEFAULT NULL COMMENT 'Actual date when payment was made',
  `receipt_filename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_data` longtext COLLATE utf8mb4_unicode_ci COMMENT 'Base64 encoded receipt image/PDF',
  `receipt_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Path to payment receipt file',
  `receipt_mime_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g., image/jpeg, application/pdf',
  `receipt_size` int DEFAULT NULL COMMENT 'Original file size in bytes',
  `verification_status` enum('pending','verified','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `verified_by` int DEFAULT NULL COMMENT 'Admin ID who verified',
  `verified_date` datetime DEFAULT NULL,
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `upload_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `verified_by` (`verified_by`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_class_id` (`class_id`),
  KEY `idx_invoice_id` (`invoice_id`),
  KEY `idx_parent_account_id` (`parent_account_id`),
  KEY `idx_verification_status` (`verification_status`),
  KEY `idx_payment_month` (`payment_month`),
  KEY `idx_upload_date` (`upload_date`),
  KEY `idx_payment_date` (`payment_date`),
  KEY `idx_receipt_path` (`receipt_path`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

DROP TABLE IF EXISTS `registrations`;
CREATE TABLE IF NOT EXISTS `registrations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `registration_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_cn` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_en` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ic` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `age` int NOT NULL,
  `school` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_status` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Student 学生',
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `events` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `schedule` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_ic` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `form_date` datetime NOT NULL,
  `signature_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Path to signature image file',
  `pdf_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Path to registration PDF file',
  `payment_amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_receipt_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Path to payment receipt file',
  `payment_status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `class_count` int DEFAULT '1',
  `student_account_id` int DEFAULT NULL COMMENT 'Links to students table',
  `parent_account_id` int DEFAULT NULL COMMENT 'Links to parent_accounts table',
  `registration_type` enum('individual','parent_managed') COLLATE utf8mb4_unicode_ci DEFAULT 'individual',
  `is_additional_child` tinyint(1) DEFAULT '0',
  `account_created` enum('yes','no') COLLATE utf8mb4_unicode_ci DEFAULT 'no',
  `password_generated` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `registration_number` (`registration_number`),
  KEY `idx_registration_number` (`registration_number`),
  KEY `idx_email` (`email`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_parent_account_id` (`parent_account_id`),
  KEY `idx_student_account_id` (`student_account_id`),
  KEY `idx_parent_account` (`parent_account_id`),
  KEY `idx_registrations_student_status` (`student_status`),
  KEY `idx_signature_path` (`signature_path`),
  KEY `idx_pdf_path` (`pdf_path`),
  KEY `idx_payment_receipt_path` (`payment_receipt_path`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
CREATE TABLE IF NOT EXISTS `students` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ic_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IC/Passport number',
  `date_of_birth` date DEFAULT NULL,
  `age` int DEFAULT NULL,
  `school` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'State Team 州队, Backup Team 后备队, Normal Student, etc',
  `status` enum('active','inactive','suspended') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `parent_account_id` int DEFAULT NULL COMMENT 'Links to parent_accounts if this is a child account',
  `student_type` enum('independent','child') COLLATE utf8mb4_unicode_ci DEFAULT 'independent' COMMENT 'independent = logs in themselves, child = managed by parent',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_parent_account_id` (`parent_account_id`),
  KEY `idx_student_type` (`student_type`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `view_parent_children`
--

DROP TABLE IF EXISTS `view_parent_children`;
CREATE TABLE IF NOT EXISTS `view_parent_children` (
  `parent_id` int DEFAULT NULL,
  `parent_code` varchar(20) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `parent_name` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `parent_email` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `parent_phone` varchar(20) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `student_code` varchar(20) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `student_name` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `student_email` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `student_status` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `relationship` enum('father','mother','guardian','other') COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT NULL,
  `enrolled_classes_count` bigint DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `view_parent_outstanding_invoices`
--

DROP TABLE IF EXISTS `view_parent_outstanding_invoices`;
CREATE TABLE IF NOT EXISTS `view_parent_outstanding_invoices` (
  `parent_id` int DEFAULT NULL,
  `parent_code` varchar(20) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `parent_name` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `number_of_children` bigint DEFAULT NULL,
  `total_unpaid_invoices` bigint DEFAULT NULL,
  `total_amount_due` decimal(32,2) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`marked_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoices_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `parent_child_relationships`
--
ALTER TABLE `parent_child_relationships`
  ADD CONSTRAINT `parent_child_relationships_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `parent_accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parent_child_relationships_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payments_ibfk_4` FOREIGN KEY (`verified_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_parent` FOREIGN KEY (`parent_account_id`) REFERENCES `parent_accounts` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
