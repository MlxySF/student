-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 20, 2025 at 06:16 PM (UPDATED)
-- Server version: 10.11.14-MariaDB
-- PHP Version: 8.1.34
-- UPDATED: Added payment_date column to payments table

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mlxysf_student_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','super_admin') DEFAULT 'admin',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('present','absent','late','excused') DEFAULT 'present',
  `notes` text DEFAULT NULL,
  `marked_by` int(11) DEFAULT NULL COMMENT 'Admin ID who marked attendance',
  `marked_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `class_code` varchar(20) NOT NULL,
  `class_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `day_of_week` varchar(20) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `monthly_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `enrollment_date` date NOT NULL,
  `status` enum('active','inactive','completed') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `class_code` varchar(20) DEFAULT NULL COMMENT 'Class code for easier reference (e.g., wsa-sun-10am)',
  `parent_account_id` int(11) DEFAULT NULL COMMENT 'Parent responsible for payment',
  `invoice_type` enum('monthly_fee','registration','equipment','event','other') DEFAULT 'monthly_fee',
  `description` text NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_month` varchar(20) DEFAULT NULL COMMENT 'Format: YYYY-MM or text like January 2025',
  `due_date` date NOT NULL,
  `status` enum('unpaid','pending','paid','cancelled','overdue') DEFAULT 'unpaid',
  `paid_date` datetime DEFAULT NULL,
  `sent_date` datetime DEFAULT NULL COMMENT 'When invoice notification was sent',
  `created_by` int(11) DEFAULT NULL COMMENT 'Admin ID who created invoice',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parent_accounts`
--

CREATE TABLE `parent_accounts` (
  `id` int(11) NOT NULL,
  `parent_id` varchar(20) NOT NULL COMMENT 'Format: PAR-YYYY-0001',
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `ic_number` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parent_child_relationships`
--

CREATE TABLE `parent_child_relationships` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `relationship` enum('father','mother','guardian','other') DEFAULT 'guardian',
  `is_primary` tinyint(1) DEFAULT 0 COMMENT 'Primary contact parent',
  `can_manage_payments` tinyint(1) DEFAULT 1,
  `can_view_attendance` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
-- UPDATED: Added payment_date column
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `invoice_id` int(11) DEFAULT NULL COMMENT 'Links payment to specific invoice',
  `parent_account_id` int(11) DEFAULT NULL COMMENT 'Parent who made the payment',
  `amount` decimal(10,2) NOT NULL,
  `payment_month` varchar(20) NOT NULL COMMENT 'Format: YYYY-MM or text',
  `payment_date` date DEFAULT NULL COMMENT 'Actual date when payment was made',
  `receipt_filename` varchar(255) DEFAULT NULL,
  `receipt_data` longtext DEFAULT NULL COMMENT 'Base64 encoded receipt image/PDF',
  `receipt_mime_type` varchar(50) DEFAULT NULL COMMENT 'e.g., image/jpeg, application/pdf',
  `receipt_size` int(11) DEFAULT NULL COMMENT 'Original file size in bytes',
  `verification_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verified_by` int(11) DEFAULT NULL COMMENT 'Admin ID who verified',
  `verified_date` datetime DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `upload_date` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

CREATE TABLE `registrations` (
  `id` int(11) NOT NULL,
  `registration_number` varchar(50) NOT NULL,
  `name_cn` varchar(100) DEFAULT NULL,
  `name_en` varchar(100) NOT NULL,
  `ic` varchar(20) NOT NULL,
  `age` int(11) NOT NULL,
  `school` varchar(200) DEFAULT NULL,
  `student_status` varchar(100) NOT NULL DEFAULT 'Student 学生',
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `level` varchar(50) DEFAULT NULL,
  `events` text NOT NULL,
  `schedule` text NOT NULL,
  `parent_name` varchar(100) NOT NULL,
  `parent_ic` varchar(20) NOT NULL,
  `form_date` datetime NOT NULL,
  `signature_base64` longtext DEFAULT NULL,
  `pdf_base64` longtext DEFAULT NULL,
  `payment_amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_receipt_base64` longtext DEFAULT NULL,
  `payment_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `class_count` int(11) DEFAULT 1,
  `student_account_id` int(11) DEFAULT NULL COMMENT 'Links to students table',
  `parent_account_id` int(11) DEFAULT NULL COMMENT 'Links to parent_accounts table',
  `registration_type` enum('individual','parent_managed') DEFAULT 'individual',
  `is_additional_child` tinyint(1) DEFAULT 0,
  `account_created` enum('yes','no') DEFAULT 'no',
  `password_generated` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `ic_number` varchar(20) DEFAULT NULL COMMENT 'IC/Passport number',
  `date_of_birth` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `school` varchar(200) DEFAULT NULL,
  `student_status` varchar(50) DEFAULT NULL COMMENT 'State Team 州队, Backup Team 后备队, Normal Student, etc',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `parent_account_id` int(11) DEFAULT NULL COMMENT 'Links to parent_accounts if this is a child account',
  `student_type` enum('independent','child') DEFAULT 'independent' COMMENT 'independent = logs in themselves, child = managed by parent',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_parent_children`
-- (See below for the actual view)
--
CREATE TABLE `view_parent_children` (
`parent_id` int(11)
,`parent_code` varchar(20)
,`parent_name` varchar(100)
,`parent_email` varchar(100)
,`parent_phone` varchar(20)
,`student_id` int(11)
,`student_code` varchar(20)
,`student_name` varchar(100)
,`student_email` varchar(100)
,`student_status` varchar(50)
,`relationship` enum('father','mother','guardian','other')
,`is_primary` tinyint(1)
,`enrolled_classes_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_parent_outstanding_invoices`
-- (See below for the actual view)
--
CREATE TABLE `view_parent_outstanding_invoices` (
`parent_id` int(11)
,`parent_code` varchar(20)
,`parent_name` varchar(100)
,`number_of_children` bigint(21)
,`total_unpaid_invoices` bigint(21)
,`total_amount_due` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Structure for view `view_parent_children`
--
DROP TABLE IF EXISTS `view_parent_children`;

CREATE ALGORITHM=UNDEFINED DEFINER=`mlxysf`@`localhost` SQL SECURITY DEFINER VIEW `view_parent_children`  AS SELECT `p`.`id` AS `parent_id`, `p`.`parent_id` AS `parent_code`, `p`.`full_name` AS `parent_name`, `p`.`email` AS `parent_email`, `p`.`phone` AS `parent_phone`, `s`.`id` AS `student_id`, `s`.`student_id` AS `student_code`, `s`.`full_name` AS `student_name`, `s`.`email` AS `student_email`, `s`.`student_status` AS `student_status`, `pcr`.`relationship` AS `relationship`, `pcr`.`is_primary` AS `is_primary`, count(`e`.`id`) AS `enrolled_classes_count` FROM (((`parent_accounts` `p` join `parent_child_relationships` `pcr` on(`p`.`id` = `pcr`.`parent_id`)) join `students` `s` on(`pcr`.`student_id` = `s`.`id`)) left join `enrollments` `e` on(`s`.`id` = `e`.`student_id` and `e`.`status` = 'active')) GROUP BY `p`.`id`, `s`.`id`, `pcr`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `view_parent_outstanding_invoices`
--
DROP TABLE IF EXISTS `view_parent_outstanding_invoices`;

CREATE ALGORITHM=UNDEFINED DEFINER=`mlxysf`@`localhost` SQL SECURITY DEFINER VIEW `view_parent_outstanding_invoices`  AS SELECT `p`.`id` AS `parent_id`, `p`.`parent_id` AS `parent_code`, `p`.`full_name` AS `parent_name`, count(distinct `s`.`id`) AS `number_of_children`, count(`i`.`id`) AS `total_unpaid_invoices`, sum(`i`.`amount`) AS `total_amount_due` FROM ((`parent_accounts` `p` join `students` `s` on(`p`.`id` = `s`.`parent_account_id`)) left join `invoices` `i` on(`s`.`id` = `i`.`student_id` and `i`.`status` in ('unpaid','overdue'))) GROUP BY `p`.`id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_username` (`username`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`student_id`,`class_id`,`attendance_date`),
  ADD KEY `marked_by` (`marked_by`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_class_id` (`class_id`),
  ADD KEY `idx_attendance_date` (`attendance_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `class_code` (`class_code`),
  ADD KEY `idx_class_code` (`class_code`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`student_id`,`class_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_class_id` (`class_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_invoice_number` (`invoice_number`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_parent_account_id` (`parent_account_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_due_date` (`due_date`),
  ADD KEY `idx_payment_month` (`payment_month`),
  ADD KEY `idx_class_code` (`class_code`);

--
-- Indexes for table `parent_accounts`
--
ALTER TABLE `parent_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `parent_id` (`parent_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_parent_id` (`parent_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `parent_child_relationships`
--
ALTER TABLE `parent_child_relationships`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_parent_student` (`parent_id`,`student_id`),
  ADD KEY `idx_parent_id` (`parent_id`),
  ADD KEY `idx_student_id` (`student_id`);

--
-- Indexes for table `payments`
-- UPDATED: Added index for payment_date
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `verified_by` (`verified_by`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_class_id` (`class_id`),
  ADD KEY `idx_invoice_id` (`invoice_id`),
  ADD KEY `idx_parent_account_id` (`parent_account_id`),
  ADD KEY `idx_verification_status` (`verification_status`),
  ADD KEY `idx_payment_month` (`payment_month`),
  ADD KEY `idx_payment_date` (`payment_date`),
  ADD KEY `idx_upload_date` (`upload_date`);

--
-- Indexes for table `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `registration_number` (`registration_number`),
  ADD KEY `idx_registration_number` (`registration_number`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_parent_account_id` (`parent_account_id`),
  ADD KEY `idx_student_account_id` (`student_account_id`),
  ADD KEY `idx_parent_account` (`parent_account_id`),
  ADD KEY `idx_registrations_student_status` (`student_status`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_parent_account_id` (`parent_account_id`),
  ADD KEY `idx_student_type` (`student_type`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parent_accounts`
--
ALTER TABLE `parent_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parent_child_relationships`
--
ALTER TABLE `parent_child_relationships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrations`
--
ALTER TABLE `registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
