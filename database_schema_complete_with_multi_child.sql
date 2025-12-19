-- ============================================================
-- WUSHU STUDENT PORTAL - COMPLETE DATABASE SCHEMA
-- Includes multi-child parent account support from the start
-- Fresh installation script for new database
-- ============================================================

-- Drop existing tables if needed (for fresh install)
DROP TABLE IF EXISTS attendance;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS invoices;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS parent_child_relationships;
DROP TABLE IF EXISTS classes;
DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS parent_accounts;
DROP TABLE IF EXISTS admin_users;

-- ============================================================
-- TABLE: admin_users
-- Stores admin accounts for the admin portal
-- ============================================================
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100),
  `role` ENUM('admin', 'super_admin') DEFAULT 'admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: parent_accounts
-- Stores parent/guardian accounts who can have multiple children
-- ============================================================
CREATE TABLE IF NOT EXISTS `parent_accounts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `parent_id` VARCHAR(20) UNIQUE NOT NULL COMMENT 'Format: PAR-YYYY-0001',
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `phone` VARCHAR(20),
  `ic_number` VARCHAR(20),
  `password` VARCHAR(255) NOT NULL,
  `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_parent_id (parent_id),
  INDEX idx_email (email),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: students
-- Stores student accounts and information
-- Now includes parent account linking and additional fields
-- ============================================================
CREATE TABLE IF NOT EXISTS `students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` VARCHAR(20) UNIQUE NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `phone` VARCHAR(20),
  `password` VARCHAR(255) NOT NULL,
  `ic_number` VARCHAR(20) NULL COMMENT 'IC/Passport number',
  `date_of_birth` DATE NULL,
  `age` INT NULL,
  `school` VARCHAR(200) NULL,
  `student_status` VARCHAR(50) NULL COMMENT 'State Team 州队, Backup Team 后备队, Normal Student, etc',
  `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
  `parent_account_id` INT NULL COMMENT 'Links to parent_accounts if this is a child account',
  `student_type` ENUM('independent', 'child') DEFAULT 'independent' COMMENT 'independent = logs in themselves, child = managed by parent',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_student_id (student_id),
  INDEX idx_email (email),
  INDEX idx_status (status),
  INDEX idx_parent_account_id (parent_account_id),
  INDEX idx_student_type (student_type),
  CONSTRAINT fk_students_parent 
    FOREIGN KEY (parent_account_id) 
    REFERENCES parent_accounts(id) 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: parent_child_relationships
-- Explicit many-to-many relationship between parents and children
-- Allows for divorced parents, guardians, etc.
-- ============================================================
CREATE TABLE IF NOT EXISTS `parent_child_relationships` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `parent_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `relationship` ENUM('father', 'mother', 'guardian', 'other') DEFAULT 'guardian',
  `is_primary` BOOLEAN DEFAULT FALSE COMMENT 'Primary contact parent',
  `can_manage_payments` BOOLEAN DEFAULT TRUE,
  `can_view_attendance` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (parent_id) REFERENCES parent_accounts(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  UNIQUE KEY unique_parent_student (parent_id, student_id),
  INDEX idx_parent_id (parent_id),
  INDEX idx_student_id (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: classes
-- Stores available classes/courses
-- ============================================================
CREATE TABLE IF NOT EXISTS `classes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `class_code` VARCHAR(20) UNIQUE NOT NULL,
  `class_name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `monthly_fee` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_class_code (class_code),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: enrollments
-- Links students to classes
-- ============================================================
CREATE TABLE IF NOT EXISTS `enrollments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `class_id` INT NOT NULL,
  `enrollment_date` DATE NOT NULL,
  `status` ENUM('active', 'inactive', 'completed') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
  UNIQUE KEY unique_enrollment (student_id, class_id),
  INDEX idx_student_id (student_id),
  INDEX idx_class_id (class_id),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: registrations
-- Stores registration forms (kept for compatibility)
-- Now includes parent account linking
-- ============================================================
CREATE TABLE IF NOT EXISTS `registrations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `registration_number` VARCHAR(50) UNIQUE NOT NULL,
  `name_cn` VARCHAR(100),
  `name_en` VARCHAR(100) NOT NULL,
  `ic` VARCHAR(20) NOT NULL,
  `age` INT NOT NULL,
  `school` VARCHAR(200),
  `status` VARCHAR(50) COMMENT 'State Team, Backup Team, etc',
  `phone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `level` VARCHAR(50),
  `events` TEXT NOT NULL,
  `schedule` TEXT NOT NULL,
  `parent_name` VARCHAR(100) NOT NULL,
  `parent_ic` VARCHAR(20) NOT NULL,
  `form_date` DATE NOT NULL,
  `signature_base64` LONGTEXT,
  `pdf_base64` LONGTEXT,
  `payment_amount` DECIMAL(10, 2) NOT NULL,
  `payment_date` DATE NOT NULL,
  `payment_receipt_base64` LONGTEXT,
  `payment_status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `class_count` INT DEFAULT 1,
  `student_account_id` INT NULL COMMENT 'Links to students table',
  `parent_account_id` INT NULL COMMENT 'Links to parent_accounts table',
  `registration_type` ENUM('individual', 'parent_managed') DEFAULT 'individual',
  `account_created` ENUM('yes', 'no') DEFAULT 'no',
  `password_generated` VARCHAR(50),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_registration_number (registration_number),
  INDEX idx_email (email),
  INDEX idx_payment_status (payment_status),
  INDEX idx_parent_account_id (parent_account_id),
  INDEX idx_student_account_id (student_account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: invoices
-- Stores all invoices for students
-- Now includes parent account for payment responsibility
-- ============================================================
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_number` VARCHAR(50) UNIQUE NOT NULL,
  `student_id` INT NOT NULL,
  `class_id` INT NULL,
  `parent_account_id` INT NULL COMMENT 'Parent responsible for payment',
  `invoice_type` ENUM('monthly_fee', 'registration', 'equipment', 'event', 'other') DEFAULT 'monthly_fee',
  `description` TEXT NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `payment_month` VARCHAR(20) NULL COMMENT 'Format: YYYY-MM or text like January 2025',
  `due_date` DATE NOT NULL,
  `status` ENUM('unpaid', 'pending', 'paid', 'cancelled', 'overdue') DEFAULT 'unpaid',
  `paid_date` DATETIME NULL,
  `sent_date` DATETIME NULL COMMENT 'When invoice notification was sent',
  `created_by` INT NULL COMMENT 'Admin ID who created invoice',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `admin_users`(`id`) ON DELETE SET NULL,
  INDEX idx_invoice_number (invoice_number),
  INDEX idx_student_id (student_id),
  INDEX idx_parent_account_id (parent_account_id),
  INDEX idx_status (status),
  INDEX idx_due_date (due_date),
  INDEX idx_payment_month (payment_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: payments
-- Stores payment records with base64-encoded receipts
-- Now includes parent account for tracking who paid
-- ============================================================
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `class_id` INT NOT NULL,
  `invoice_id` INT NULL COMMENT 'Links payment to specific invoice',
  `parent_account_id` INT NULL COMMENT 'Parent who made the payment',
  `amount` DECIMAL(10, 2) NOT NULL,
  `payment_month` VARCHAR(20) NOT NULL COMMENT 'Format: YYYY-MM or text',
  `receipt_filename` VARCHAR(255) NULL,
  `receipt_data` LONGTEXT NULL COMMENT 'Base64 encoded receipt image/PDF',
  `receipt_mime_type` VARCHAR(50) NULL COMMENT 'e.g., image/jpeg, application/pdf',
  `receipt_size` INT NULL COMMENT 'Original file size in bytes',
  `verification_status` ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
  `verified_by` INT NULL COMMENT 'Admin ID who verified',
  `verified_date` DATETIME NULL,
  `admin_notes` TEXT NULL,
  `upload_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`verified_by`) REFERENCES `admin_users`(`id`) ON DELETE SET NULL,
  INDEX idx_student_id (student_id),
  INDEX idx_class_id (class_id),
  INDEX idx_invoice_id (invoice_id),
  INDEX idx_parent_account_id (parent_account_id),
  INDEX idx_verification_status (verification_status),
  INDEX idx_payment_month (payment_month),
  INDEX idx_upload_date (upload_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: attendance
-- Tracks student attendance for each class
-- ============================================================
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `class_id` INT NOT NULL,
  `attendance_date` DATE NOT NULL,
  `status` ENUM('present', 'absent', 'late', 'excused') DEFAULT 'present',
  `notes` TEXT NULL,
  `marked_by` INT NULL COMMENT 'Admin ID who marked attendance',
  `marked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`marked_by`) REFERENCES `admin_users`(`id`) ON DELETE SET NULL,
  UNIQUE KEY unique_attendance (student_id, class_id, attendance_date),
  INDEX idx_student_id (student_id),
  INDEX idx_class_id (class_id),
  INDEX idx_attendance_date (attendance_date),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- VIEWS FOR REPORTING
-- ============================================================

-- View: Parent with all their children
CREATE OR REPLACE VIEW view_parent_children AS
SELECT 
  p.id as parent_id,
  p.parent_id as parent_code,
  p.full_name as parent_name,
  p.email as parent_email,
  p.phone as parent_phone,
  s.id as student_id,
  s.student_id as student_code,
  s.full_name as student_name,
  s.email as student_email,
  s.student_status,
  pcr.relationship,
  pcr.is_primary,
  COUNT(e.id) as enrolled_classes_count
FROM parent_accounts p
JOIN parent_child_relationships pcr ON p.id = pcr.parent_id
JOIN students s ON pcr.student_id = s.id
LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
GROUP BY p.id, s.id, pcr.id;

-- View: Parent's total outstanding invoices across all children
CREATE OR REPLACE VIEW view_parent_outstanding_invoices AS
SELECT 
  p.id as parent_id,
  p.parent_id as parent_code,
  p.full_name as parent_name,
  COUNT(DISTINCT s.id) as number_of_children,
  COUNT(i.id) as total_unpaid_invoices,
  SUM(i.amount) as total_amount_due
FROM parent_accounts p
JOIN students s ON p.id = s.parent_account_id
LEFT JOIN invoices i ON s.id = i.student_id AND i.status IN ('unpaid', 'overdue')
GROUP BY p.id;

-- ============================================================
-- SAMPLE DATA (Optional - for testing)
-- ============================================================

-- Create default admin account
-- Password: admin123 (change this immediately in production!)
INSERT INTO `admin_users` (`username`, `password`, `full_name`, `email`, `role`) 
VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@wushu.com', 'super_admin')
ON DUPLICATE KEY UPDATE `username` = `username`;

-- Create sample classes
INSERT INTO `classes` (`class_code`, `class_name`, `description`, `monthly_fee`, `status`) 
VALUES 
('WSH-101', 'Beginner Wushu', 'Introduction to Wushu for beginners', 150.00, 'active'),
('WSH-201', 'Intermediate Wushu', 'Intermediate level Wushu training', 200.00, 'active'),
('WSH-301', 'Advanced Wushu', 'Advanced Wushu techniques and forms', 250.00, 'active'),
('WSH-401', 'State Team Training', 'Elite training for state team members', 300.00, 'active')
ON DUPLICATE KEY UPDATE `class_code` = `class_code`;

-- Create sample parent account
-- Password: parent123
INSERT INTO `parent_accounts` (`parent_id`, `full_name`, `email`, `phone`, `ic_number`, `password`, `status`)
VALUES
('PAR-2025-0001', 'John Doe', 'parent@example.com', '012-3456789', '850101-01-1234', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active')
ON DUPLICATE KEY UPDATE `parent_id` = `parent_id`;

-- Create sample students (children)
-- Password: student123
INSERT INTO `students` (`student_id`, `full_name`, `email`, `phone`, `password`, `ic_number`, `age`, `school`, `student_status`, `status`, `parent_account_id`, `student_type`)
VALUES
('WSA2025-0001', 'Alice Doe', 'alice@example.com', '012-1111111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '150101-01-1234', 10, 'SJKC Test School', 'State Team 州队', 'active', 1, 'child'),
('WSA2025-0002', 'Bob Doe', 'bob@example.com', '012-2222222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '160101-01-1234', 9, 'SJKC Test School', 'Normal Student', 'active', 1, 'child')
ON DUPLICATE KEY UPDATE `student_id` = `student_id`;

-- Link children to parent
INSERT INTO `parent_child_relationships` (`parent_id`, `student_id`, `relationship`, `is_primary`, `can_manage_payments`, `can_view_attendance`)
VALUES
(1, 1, 'father', TRUE, TRUE, TRUE),
(1, 2, 'father', FALSE, TRUE, TRUE)
ON DUPLICATE KEY UPDATE `parent_id` = `parent_id`;

-- Enroll students in classes
INSERT INTO `enrollments` (`student_id`, `class_id`, `enrollment_date`, `status`)
VALUES
(1, 4, '2025-01-01', 'active'),
(2, 1, '2025-01-01', 'active')
ON DUPLICATE KEY UPDATE `student_id` = `student_id`;

-- Create sample invoices
INSERT INTO `invoices` (`invoice_number`, `student_id`, `class_id`, `parent_account_id`, `invoice_type`, `description`, `amount`, `payment_month`, `due_date`, `status`)
VALUES
('INV-2025-0001', 1, 4, 1, 'monthly_fee', 'January 2025 - State Team Training', 300.00, '2025-01', '2025-01-31', 'unpaid'),
('INV-2025-0002', 2, 1, 1, 'monthly_fee', 'January 2025 - Beginner Wushu', 150.00, '2025-01', '2025-01-31', 'unpaid')
ON DUPLICATE KEY UPDATE `invoice_number` = `invoice_number`;

-- ============================================================
-- USEFUL QUERIES FOR MAINTENANCE
-- ============================================================

-- Check for overdue invoices and update status
-- Run this periodically (e.g., daily cron job)
-- UPDATE invoices 
-- SET status = 'overdue' 
-- WHERE status = 'unpaid' 
-- AND due_date < CURDATE();

-- View all unpaid invoices with student and parent info
-- SELECT 
--   i.invoice_number,
--   s.student_id,
--   s.full_name as student_name,
--   p.full_name as parent_name,
--   p.email as parent_email,
--   i.amount,
--   i.due_date,
--   i.status
-- FROM invoices i
-- JOIN students s ON i.student_id = s.id
-- LEFT JOIN parent_accounts p ON i.parent_account_id = p.id
-- WHERE i.status IN ('unpaid', 'overdue')
-- ORDER BY i.due_date ASC;

-- View payment verification queue
-- SELECT 
--   p.id,
--   s.student_id,
--   s.full_name as student_name,
--   pa.full_name as parent_name,
--   c.class_code,
--   p.amount,
--   p.payment_month,
--   p.upload_date
-- FROM payments p
-- JOIN students s ON p.student_id = s.id
-- JOIN classes c ON p.class_id = c.id
-- LEFT JOIN parent_accounts pa ON p.parent_account_id = pa.id
-- WHERE p.verification_status = 'pending'
-- ORDER BY p.upload_date DESC;

-- ============================================================
-- BACKUP REMINDER
-- ============================================================
-- IMPORTANT: Set up regular database backups!
-- Command: mysqldump -u [username] -p mlxysf_student_portal > backup_$(date +%Y%m%d).sql
-- Schedule: Daily at minimum, more frequently for production

-- ============================================================
-- END OF SCHEMA
-- ============================================================