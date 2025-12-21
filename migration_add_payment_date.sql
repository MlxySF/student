-- Migration: Add payment_date column to payments and registrations tables
-- Date: 2025-12-20
-- Purpose: Allow users to specify actual payment date instead of using upload date

-- Add payment_date to payments table
ALTER TABLE `payments` 
ADD COLUMN `payment_date` DATE NULL COMMENT 'Actual date when payment was made' AFTER `payment_month`;

-- Add index for payment_date for faster queries
ALTER TABLE `payments` 
ADD INDEX `idx_payment_date` (`payment_date`);

-- Update existing records: set payment_date to upload_date for existing payments
UPDATE `payments` 
SET `payment_date` = DATE(`upload_date`) 
WHERE `payment_date` IS NULL AND `upload_date` IS NOT NULL;

-- Note: registrations table already has payment_date column (verified in schema)
-- No changes needed for registrations table

SELECT 'Migration completed successfully!' AS status;
