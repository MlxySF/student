-- ============================================================
-- Migration: Convert Base64 Storage to Local File Storage
-- Date: 2025-12-21
-- Description: Adds new file path columns alongside existing
--              base64 columns for gradual migration
-- ============================================================

USE mlxysf_student_portal;

-- Step 1: Add new file path columns to registrations table
-- Keep base64 columns temporarily for backward compatibility during migration

ALTER TABLE `registrations`
  ADD COLUMN `signature_path` VARCHAR(255) DEFAULT NULL COMMENT 'Path to signature image file' AFTER `signature_base64`,
  ADD COLUMN `pdf_path` VARCHAR(255) DEFAULT NULL COMMENT 'Path to registration PDF file' AFTER `pdf_base64`,
  ADD COLUMN `payment_receipt_path` VARCHAR(255) DEFAULT NULL COMMENT 'Path to payment receipt file' AFTER `payment_receipt_base64`;

-- Step 2: Add new file path column to payments table

ALTER TABLE `payments`
  ADD COLUMN `receipt_path` VARCHAR(255) DEFAULT NULL COMMENT 'Path to payment receipt file' AFTER `receipt_data`;

-- Step 3: Add index on file path columns for faster lookups

ALTER TABLE `registrations`
  ADD INDEX `idx_signature_path` (`signature_path`),
  ADD INDEX `idx_pdf_path` (`pdf_path`),
  ADD INDEX `idx_payment_receipt_path` (`payment_receipt_path`);

ALTER TABLE `payments`
  ADD INDEX `idx_receipt_path` (`receipt_path`);

-- ============================================================
-- IMPORTANT NOTES:
-- ============================================================
-- 1. Base64 columns are NOT dropped yet to allow data migration
-- 2. After successful migration, run migration_cleanup.sql to:
--    - Drop base64 columns
--    - Drop receipt_mime_type column
-- 3. Existing records will have NULL in path columns until migrated
-- 4. New registrations will populate path columns only
-- ============================================================

-- Verify changes
SELECT 
    'registrations' as table_name,
    COUNT(*) as total_records,
    SUM(CASE WHEN signature_path IS NOT NULL THEN 1 ELSE 0 END) as with_signature_path,
    SUM(CASE WHEN pdf_path IS NOT NULL THEN 1 ELSE 0 END) as with_pdf_path,
    SUM(CASE WHEN payment_receipt_path IS NOT NULL THEN 1 ELSE 0 END) as with_receipt_path
FROM registrations
UNION ALL
SELECT 
    'payments' as table_name,
    COUNT(*) as total_records,
    SUM(CASE WHEN receipt_path IS NOT NULL THEN 1 ELSE 0 END) as with_receipt_path,
    0 as with_pdf_path,
    0 as with_payment_receipt_path
FROM payments;
