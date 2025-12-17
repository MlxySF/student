-- ============================================================
-- MULTI-CHILD SUPPORT DATABASE MIGRATION
-- This script adds parent account functionality
-- Run this AFTER backing up your database!
-- ============================================================

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
-- UPDATE TABLE: students
-- Add parent_account_id to link students to parents
-- Add student_type to distinguish between independent and child accounts
-- ============================================================
ALTER TABLE `students` 
  ADD COLUMN `parent_account_id` INT NULL COMMENT 'Links to parent_accounts table if this is a child account',
  ADD COLUMN `student_type` ENUM('independent', 'child') DEFAULT 'independent' COMMENT 'independent = logs in themselves, child = managed by parent',
  ADD COLUMN `ic_number` VARCHAR(20) NULL COMMENT 'IC/Passport number',
  ADD COLUMN `date_of_birth` DATE NULL,
  ADD COLUMN `age` INT NULL,
  ADD COLUMN `school` VARCHAR(200) NULL,
  ADD COLUMN `student_status` VARCHAR(50) NULL COMMENT 'State Team, Backup Team, Normal Student, etc',
  ADD INDEX idx_parent_account_id (parent_account_id),
  ADD INDEX idx_student_type (student_type),
  ADD CONSTRAINT fk_students_parent 
    FOREIGN KEY (parent_account_id) 
    REFERENCES parent_accounts(id) 
    ON DELETE SET NULL;

-- ============================================================
-- TABLE: parent_child_relationships
-- Explicit many-to-many relationship (in case of divorced parents, etc.)
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
-- UPDATE TABLE: registrations
-- Add parent account linking
-- ============================================================
ALTER TABLE `registrations` 
  ADD COLUMN `parent_account_id` INT NULL COMMENT 'If registered through parent account',
  ADD COLUMN `registration_type` ENUM('individual', 'parent_managed') DEFAULT 'individual',
  ADD INDEX idx_parent_account_id (parent_account_id);

-- ============================================================
-- UPDATE TABLE: invoices
-- Track which parent should pay (if child account)
-- ============================================================
ALTER TABLE `invoices`
  ADD COLUMN `parent_account_id` INT NULL COMMENT 'Parent responsible for payment',
  ADD INDEX idx_parent_account_id (parent_account_id);

-- ============================================================
-- UPDATE TABLE: payments  
-- Track which parent made the payment
-- ============================================================
ALTER TABLE `payments`
  ADD COLUMN `parent_account_id` INT NULL COMMENT 'Parent who made the payment',
  ADD INDEX idx_parent_account_id (parent_account_id);

-- ============================================================
-- MIGRATE EXISTING DATA
-- Convert existing registrations to create parent accounts if needed
-- ============================================================

-- Step 1: Create parent accounts from unique parent info in registrations
INSERT INTO parent_accounts (parent_id, full_name, email, ic_number, phone, password, status)
SELECT 
  CONCAT('PAR-', YEAR(NOW()), '-', LPAD((@row_num := @row_num + 1), 4, '0')) as parent_id,
  r.parent_name,
  CONCAT('parent_', r.parent_ic, '@temp.com') as email, -- Temporary email, will need to update
  r.parent_ic,
  r.phone,
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' as password, -- Default password: password123
  'active'
FROM registrations r
CROSS JOIN (SELECT @row_num := 0) t
WHERE r.parent_ic IS NOT NULL 
  AND r.parent_ic != ''
GROUP BY r.parent_ic, r.parent_name
ON DUPLICATE KEY UPDATE email = email;

-- Step 2: Link existing students to parent accounts based on IC
UPDATE students s
JOIN registrations r ON s.email = r.email
JOIN parent_accounts p ON r.parent_ic = p.ic_number
SET 
  s.parent_account_id = p.id,
  s.student_type = 'child',
  s.ic_number = r.ic,
  s.age = r.age,
  s.school = r.school,
  s.student_status = r.status
WHERE r.parent_ic IS NOT NULL;

-- Step 3: Create explicit parent-child relationships
INSERT INTO parent_child_relationships (parent_id, student_id, relationship, is_primary, can_manage_payments, can_view_attendance)
SELECT 
  p.id,
  s.id,
  'guardian',
  TRUE,
  TRUE,
  TRUE
FROM students s
JOIN parent_accounts p ON s.parent_account_id = p.id
WHERE s.student_type = 'child'
ON DUPLICATE KEY UPDATE relationship = relationship;

-- Step 4: Update registration records with parent_account_id
UPDATE registrations r
JOIN parent_accounts p ON r.parent_ic = p.ic_number
SET 
  r.parent_account_id = p.id,
  r.registration_type = 'parent_managed'
WHERE r.parent_ic IS NOT NULL;

-- Step 5: Link existing invoices to parent accounts
UPDATE invoices i
JOIN students s ON i.student_id = s.id
SET i.parent_account_id = s.parent_account_id
WHERE s.parent_account_id IS NOT NULL;

-- Step 6: Link existing payments to parent accounts  
UPDATE payments p
JOIN students s ON p.student_id = s.id
SET p.parent_account_id = s.parent_account_id
WHERE s.parent_account_id IS NOT NULL;

-- ============================================================
-- USEFUL VIEWS FOR REPORTING
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
-- POST-MIGRATION INSTRUCTIONS
-- ============================================================

-- IMPORTANT: After running this migration:
-- 1. Send password reset emails to all parent accounts
-- 2. Ask parents to update their email addresses from the temporary ones
-- 3. Update the student portal to show child selector for parents
-- 4. Update registration form to support parent account creation
-- 5. Test login functionality for both parent and student accounts

-- Generate password reset tokens (add to your application)
-- Example query to list parents needing email updates:
-- SELECT parent_id, full_name, email, phone 
-- FROM parent_accounts 
-- WHERE email LIKE '%@temp.com%';

-- ============================================================
-- ROLLBACK SCRIPT (Keep for safety)
-- ============================================================
/*
-- To rollback this migration (BE CAREFUL!):
DROP VIEW IF EXISTS view_parent_outstanding_invoices;
DROP VIEW IF EXISTS view_parent_children;

ALTER TABLE payments DROP COLUMN parent_account_id;
ALTER TABLE invoices DROP COLUMN parent_account_id;
ALTER TABLE registrations DROP COLUMN registration_type, DROP COLUMN parent_account_id;

ALTER TABLE students 
  DROP FOREIGN KEY fk_students_parent,
  DROP COLUMN student_status,
  DROP COLUMN school,
  DROP COLUMN age,
  DROP COLUMN date_of_birth,
  DROP COLUMN ic_number,
  DROP COLUMN student_type,
  DROP COLUMN parent_account_id;

DROP TABLE IF EXISTS parent_child_relationships;
DROP TABLE IF EXISTS parent_accounts;
*/

-- ============================================================
-- END OF MIGRATION
-- ============================================================