-- ========================================
-- STAGE 3: Parent Account System Migration
-- Add missing columns to registrations table
-- ========================================

-- Run this SQL on your database to add the missing columns

USE mlxysf_student_portal;

-- Add parent_account_id column to registrations table
ALTER TABLE registrations 
ADD COLUMN IF NOT EXISTS parent_account_id INT DEFAULT NULL AFTER student_account_id,
ADD INDEX idx_parent_account (parent_account_id);

-- Add registration_type column (to track how student registered)
ALTER TABLE registrations 
ADD COLUMN IF NOT EXISTS registration_type VARCHAR(50) DEFAULT 'independent' AFTER parent_account_id;

-- Add is_additional_child column (to track if this is first or additional child)
ALTER TABLE registrations 
ADD COLUMN IF NOT EXISTS is_additional_child TINYINT(1) DEFAULT 0 AFTER registration_type;

-- Verify the columns were added
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'mlxysf_student_portal' 
  AND TABLE_NAME = 'registrations'
  AND COLUMN_NAME IN ('parent_account_id', 'registration_type', 'is_additional_child')
ORDER BY ORDINAL_POSITION;

-- Expected output should show:
-- parent_account_id | INT | YES | NULL
-- registration_type | VARCHAR(50) | YES | 'independent'
-- is_additional_child | TINYINT(1) | YES | 0
