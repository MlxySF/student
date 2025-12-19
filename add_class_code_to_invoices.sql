-- ============================================================
-- DATABASE MIGRATION: Add class_code to invoices table
-- Purpose: Store class code in invoices for better tracking
-- Date: December 20, 2025
-- ============================================================

-- Add class_code column to invoices table
ALTER TABLE invoices 
ADD COLUMN class_code VARCHAR(20) NULL 
AFTER class_id,
ADD INDEX idx_class_code (class_code);

-- Update description
ALTER TABLE invoices 
MODIFY COLUMN class_code VARCHAR(20) NULL 
COMMENT 'Class code for easier reference (e.g., wsa-sun-10am)';

-- Verify the change
SHOW COLUMNS FROM invoices LIKE 'class_code';

-- ============================================================
-- VERIFICATION QUERY
-- Run this to confirm the column was added successfully
-- ============================================================
-- SELECT 
--   COLUMN_NAME,
--   COLUMN_TYPE,
--   IS_NULLABLE,
--   COLUMN_COMMENT
-- FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_SCHEMA = 'mlxysf_student_portal'
-- AND TABLE_NAME = 'invoices'
-- AND COLUMN_NAME = 'class_code';

-- ============================================================
-- END OF MIGRATION
-- ============================================================