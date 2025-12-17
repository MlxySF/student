-- ============================================================
-- MULTI-CHILD SUPPORT - DATABASE MIGRATION
-- Adds parent account system to support multiple children
-- Run this AFTER the base schema is already in place
-- ============================================================

-- ============================================================
-- TABLE: parent_accounts
-- Stores parent/guardian accounts that can manage multiple children
-- ============================================================
CREATE TABLE IF NOT EXISTS `parent_accounts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `parent_id` VARCHAR(20) UNIQUE NOT NULL COMMENT 'Format: PAR-YYYY-0001',
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `phone` VARCHAR(20),
  `ic_number` VARCHAR(20) COMMENT 'Parent IC/Passport',
  `password` VARCHAR(255) NOT NULL,
  `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
  `email_verified` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_parent_id (parent_id),
  INDEX idx_email (email),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: parent_student_links
-- Links parent accounts to student profiles (many-to-many)
-- ============================================================
CREATE TABLE IF NOT EXISTS `parent_student_links` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `parent_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `relationship` ENUM('parent', 'guardian', 'mother', 'father', 'other') DEFAULT 'parent',
  `is_primary` TINYINT(1) DEFAULT 0 COMMENT '1 if this is the primary parent for this student',
  `can_view_payments` TINYINT(1) DEFAULT 1,
  `can_make_payments` TINYINT(1) DEFAULT 1,
  `can_view_attendance` TINYINT(1) DEFAULT 1,
  `linked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`parent_id`) REFERENCES `parent_accounts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  UNIQUE KEY unique_parent_student (parent_id, student_id),
  INDEX idx_parent_id (parent_id),
  INDEX idx_student_id (student_id),
  INDEX idx_is_primary (is_primary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- MODIFY: students table
-- Add parent-related fields and remove direct login capability
-- ============================================================

-- Add new columns to students table
ALTER TABLE `students` 
  ADD COLUMN `name_cn` VARCHAR(100) COMMENT 'Chinese name' AFTER `full_name`,
  ADD COLUMN `ic_number` VARCHAR(20) COMMENT 'Student IC/Passport' AFTER `phone`,
  ADD COLUMN `age` INT COMMENT 'Student age' AFTER `ic_number`,
  ADD COLUMN `school` VARCHAR(200) COMMENT 'Current school' AFTER `age`,
  ADD COLUMN `student_status` VARCHAR(50) DEFAULT 'Student' COMMENT 'e.g., State Team 州队, Backup Team 后备队, Student 学生' AFTER `status`,
  ADD COLUMN `has_parent_account` TINYINT(1) DEFAULT 0 COMMENT '1 if linked to parent account, 0 if standalone student' AFTER `student_status`,
  MODIFY COLUMN `password` VARCHAR(255) NULL COMMENT 'NULL if parent-managed, password if standalone student';

-- Add indexes for new columns
ALTER TABLE `students`
  ADD INDEX idx_student_status (student_status),
  ADD INDEX idx_has_parent_account (has_parent_account);

-- ============================================================
-- MODIFY: registrations table (if exists)
-- Add parent information
-- ============================================================
ALTER TABLE `registrations`
  ADD COLUMN `parent_account_id` INT NULL COMMENT 'Links to parent account if created during registration' AFTER `student_account_id`,
  ADD INDEX idx_parent_account_id (parent_account_id);

-- Add foreign key if parent_accounts exists
ALTER TABLE `registrations`
  ADD CONSTRAINT fk_registrations_parent
  FOREIGN KEY (parent_account_id) REFERENCES parent_accounts(id) ON DELETE SET NULL;

-- ============================================================
-- UTILITY VIEWS
-- ============================================================

-- View: Parent Dashboard Summary
CREATE OR REPLACE VIEW v_parent_dashboard AS
SELECT 
    pa.id as parent_id,
    pa.parent_id as parent_code,
    pa.full_name as parent_name,
    pa.email as parent_email,
    s.id as student_id,
    s.student_id as student_code,
    s.full_name as student_name,
    s.student_status,
    s.status as student_status_active,
    psl.relationship,
    psl.is_primary,
    COUNT(DISTINCT e.id) as enrolled_classes_count,
    COUNT(DISTINCT CASE WHEN i.status IN ('unpaid', 'overdue') THEN i.id END) as unpaid_invoices_count,
    COUNT(DISTINCT CASE WHEN p.verification_status = 'pending' THEN p.id END) as pending_payments_count
FROM parent_accounts pa
INNER JOIN parent_student_links psl ON pa.id = psl.parent_id
INNER JOIN students s ON psl.student_id = s.id
LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
LEFT JOIN invoices i ON s.id = i.student_id
LEFT JOIN payments p ON s.id = p.student_id
GROUP BY pa.id, s.id;

-- View: All Students with Parent Info
CREATE OR REPLACE VIEW v_students_with_parents AS
SELECT 
    s.*,
    pa.parent_id,
    pa.full_name as parent_name,
    pa.email as parent_email,
    pa.phone as parent_phone,
    psl.relationship,
    psl.is_primary
FROM students s
LEFT JOIN parent_student_links psl ON s.id = psl.student_id AND psl.is_primary = 1
LEFT JOIN parent_accounts pa ON psl.parent_id = pa.id;

-- ============================================================
-- MIGRATION HELPER FUNCTIONS
-- ============================================================

-- Function to migrate existing students to parent accounts
-- This is a TEMPLATE - customize based on your needs
DELIMITER $$

CREATE PROCEDURE migrate_existing_students_to_parents()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_student_id INT;
    DECLARE v_email VARCHAR(100);
    DECLARE v_full_name VARCHAR(100);
    DECLARE v_phone VARCHAR(20);
    DECLARE v_password VARCHAR(255);
    DECLARE v_new_parent_id INT;
    DECLARE v_parent_code VARCHAR(20);
    DECLARE v_counter INT;
    
    DECLARE cur CURSOR FOR 
        SELECT id, email, full_name, phone, password 
        FROM students 
        WHERE has_parent_account = 0;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Get current year
    SET @current_year = YEAR(NOW());
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO v_student_id, v_email, v_full_name, v_phone, v_password;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Generate parent ID
        SELECT COUNT(*) + 1 INTO v_counter FROM parent_accounts WHERE YEAR(created_at) = @current_year;
        SET v_parent_code = CONCAT('PAR-', @current_year, '-', LPAD(v_counter, 4, '0'));
        
        -- Create parent account with same credentials
        INSERT INTO parent_accounts (parent_id, full_name, email, phone, password, status)
        VALUES (v_parent_code, v_full_name, v_email, v_phone, v_password, 'active');
        
        SET v_new_parent_id = LAST_INSERT_ID();
        
        -- Link parent to student
        INSERT INTO parent_student_links (parent_id, student_id, relationship, is_primary)
        VALUES (v_new_parent_id, v_student_id, 'parent', 1);
        
        -- Update student record
        UPDATE students 
        SET has_parent_account = 1, password = NULL 
        WHERE id = v_student_id;
        
    END LOOP;
    
    CLOSE cur;
END$$

DELIMITER ;

-- ============================================================
-- SAMPLE DATA FOR TESTING
-- ============================================================

-- Create a sample parent account
INSERT INTO parent_accounts (parent_id, full_name, email, phone, ic_number, password, status)
VALUES ('PAR-2025-0001', 'John Doe', 'john.doe@example.com', '+60123456789', '850101-01-1234', 
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active')
ON DUPLICATE KEY UPDATE parent_id = parent_id;

-- ============================================================
-- IMPORTANT NOTES FOR MIGRATION
-- ============================================================

/*
MIGRATION STEPS:

1. Backup your database first!
   mysqldump -u [user] -p mlxysf_student_portal > backup_before_migration.sql

2. Run this schema file:
   mysql -u [user] -p mlxysf_student_portal < database_schema_parent_multi_child.sql

3. Decide on migration strategy:
   
   OPTION A: Migrate all existing students to parent accounts
   - Run: CALL migrate_existing_students_to_parents();
   - This creates parent accounts for each student
   - Students will need to use their email to login as parent
   
   OPTION B: Keep existing students as standalone
   - Do nothing, new registrations will create parent accounts
   - Old students login as students, new ones login as parents
   
4. Update application code to use new parent system

5. Test thoroughly before going live!

6. Email all existing users about the change
*/

-- ============================================================
-- USEFUL QUERIES
-- ============================================================

-- View all parents with their children
-- SELECT 
--     pa.parent_id,
--     pa.full_name as parent_name,
--     pa.email,
--     GROUP_CONCAT(s.full_name SEPARATOR ', ') as children
-- FROM parent_accounts pa
-- LEFT JOIN parent_student_links psl ON pa.id = psl.parent_id
-- LEFT JOIN students s ON psl.student_id = s.id
-- GROUP BY pa.id;

-- View students without parent accounts
-- SELECT * FROM students WHERE has_parent_account = 0;

-- Count children per parent
-- SELECT 
--     pa.parent_id,
--     pa.full_name,
--     COUNT(psl.student_id) as num_children
-- FROM parent_accounts pa
-- LEFT JOIN parent_student_links psl ON pa.id = psl.parent_id
-- GROUP BY pa.id;

-- ============================================================
-- END OF MIGRATION SCHEMA
-- ============================================================