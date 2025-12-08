-- ============================================================
-- WUSHU STUDENT PORTAL - DATABASE SCHEMA
-- Complete production-ready schema with all tables and relationships
-- ============================================================

-- Drop existing tables if needed (for fresh install)
-- DROP TABLE IF EXISTS attendance;
-- DROP TABLE IF EXISTS payments;
-- DROP TABLE IF EXISTS invoices;
-- DROP TABLE IF EXISTS enrollments;
-- DROP TABLE IF EXISTS classes;
-- DROP TABLE IF EXISTS students;
-- DROP TABLE IF EXISTS admin_users;

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
-- TABLE: students
-- Stores student accounts and information
-- ============================================================
CREATE TABLE IF NOT EXISTS `students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` VARCHAR(20) UNIQUE NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `phone` VARCHAR(20),
  `password` VARCHAR(255) NOT NULL,
  `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_student_id (student_id),
  INDEX idx_email (email),
  INDEX idx_status (status)
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
-- TABLE: invoices
-- Stores all invoices for students
-- ============================================================
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_number` VARCHAR(50) UNIQUE NOT NULL,
  `student_id` INT NOT NULL,
  `class_id` INT NULL,
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
  INDEX idx_status (status),
  INDEX idx_due_date (due_date),
  INDEX idx_payment_month (payment_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: payments
-- Stores payment records with base64-encoded receipts
-- ============================================================
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `class_id` INT NOT NULL,
  `invoice_id` INT NULL COMMENT 'Links payment to specific invoice',
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
('WSH-301', 'Advanced Wushu', 'Advanced Wushu techniques and forms', 250.00, 'active')
ON DUPLICATE KEY UPDATE `class_code` = `class_code`;

-- ============================================================
-- USEFUL QUERIES FOR MAINTENANCE
-- ============================================================

-- Check for overdue invoices and update status
-- Run this periodically (e.g., daily cron job)
-- UPDATE invoices 
-- SET status = 'overdue' 
-- WHERE status = 'unpaid' 
-- AND due_date < CURDATE();

-- View all unpaid invoices with student info
-- SELECT 
--   i.invoice_number,
--   s.student_id,
--   s.full_name,
--   s.email,
--   i.amount,
--   i.due_date,
--   i.status
-- FROM invoices i
-- JOIN students s ON i.student_id = s.id
-- WHERE i.status IN ('unpaid', 'overdue')
-- ORDER BY i.due_date ASC;

-- View payment verification queue
-- SELECT 
--   p.id,
--   s.student_id,
--   s.full_name,
--   c.class_code,
--   p.amount,
--   p.payment_month,
--   p.upload_date
-- FROM payments p
-- JOIN students s ON p.student_id = s.id
-- JOIN classes c ON p.class_id = c.id
-- WHERE p.verification_status = 'pending'
-- ORDER BY p.upload_date DESC;

-- ============================================================
-- INDEXES FOR PERFORMANCE
-- ============================================================
-- All necessary indexes are already created above in table definitions

-- ============================================================
-- BACKUP REMINDER
-- ============================================================
-- IMPORTANT: Set up regular database backups!
-- Command: mysqldump -u [username] -p mlxysf_student_portal > backup_$(date +%Y%m%d).sql
-- Schedule: Daily at minimum, more frequently for production

-- ============================================================
-- END OF SCHEMA
-- ============================================================