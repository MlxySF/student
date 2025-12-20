-- ============================================================
-- DATABASE OPTIMIZATION - Performance Indexes
-- Wushu Student Portal
-- ============================================================
-- Run these queries to add indexes for faster query performance
-- Estimated improvement: 50-80% faster queries on large datasets
-- ============================================================

-- ============================================================
-- STUDENTS TABLE
-- ============================================================

-- Index for email lookups (login, duplicate check)
CREATE INDEX IF NOT EXISTS idx_students_email 
ON students(email);

-- Index for student_id lookups (searching, reporting)
CREATE INDEX IF NOT EXISTS idx_students_student_id 
ON students(student_id);

-- Index for parent account lookups
CREATE INDEX IF NOT EXISTS idx_students_parent_account 
ON students(parent_account_id);

-- Composite index for active student queries
CREATE INDEX IF NOT EXISTS idx_students_status 
ON students(id, parent_account_id);

-- ============================================================
-- REGISTRATIONS TABLE
-- ============================================================

-- Index for registration number lookups
CREATE INDEX IF NOT EXISTS idx_registrations_number 
ON registrations(registration_number);

-- Index for account status (approved, pending, rejected)
CREATE INDEX IF NOT EXISTS idx_registrations_account_status 
ON registrations(account_status);

-- Index for payment status
CREATE INDEX IF NOT EXISTS idx_registrations_payment_status 
ON registrations(payment_status);

-- Index for student status (State Team, Backup Team, Student)
CREATE INDEX IF NOT EXISTS idx_registrations_student_status 
ON registrations(student_status);

-- Index for date sorting
CREATE INDEX IF NOT EXISTS idx_registrations_created_at 
ON registrations(created_at DESC);

-- Composite index for dashboard queries
CREATE INDEX IF NOT EXISTS idx_registrations_status_combo 
ON registrations(account_status, student_status);

-- Index for email lookups
CREATE INDEX IF NOT EXISTS idx_registrations_email 
ON registrations(email);

-- ============================================================
-- PAYMENTS TABLE
-- ============================================================

-- Index for student payment lookups
CREATE INDEX IF NOT EXISTS idx_payments_student 
ON payments(student_id);

-- Index for verification status (pending, verified, rejected)
CREATE INDEX IF NOT EXISTS idx_payments_verification 
ON payments(verification_status);

-- Index for upload date sorting
CREATE INDEX IF NOT EXISTS idx_payments_upload_date 
ON payments(upload_date DESC);

-- Index for invoice linkage
CREATE INDEX IF NOT EXISTS idx_payments_invoice 
ON payments(invoice_id);

-- Index for class payments
CREATE INDEX IF NOT EXISTS idx_payments_class 
ON payments(class_id);

-- Index for parent account payments
CREATE INDEX IF NOT EXISTS idx_payments_parent 
ON payments(parent_account_id);

-- Composite index for pending payment queries
CREATE INDEX IF NOT EXISTS idx_payments_pending_combo 
ON payments(verification_status, upload_date DESC);

-- Index for payment month filtering
CREATE INDEX IF NOT EXISTS idx_payments_month 
ON payments(payment_month);

-- ============================================================
-- INVOICES TABLE
-- ============================================================

-- Index for student invoice lookups
CREATE INDEX IF NOT EXISTS idx_invoices_student 
ON invoices(student_id);

-- Index for invoice status (unpaid, pending, paid)
CREATE INDEX IF NOT EXISTS idx_invoices_status 
ON invoices(status);

-- Index for invoice number (unique lookups)
CREATE INDEX IF NOT EXISTS idx_invoices_number 
ON invoices(invoice_number);

-- Index for invoice type
CREATE INDEX IF NOT EXISTS idx_invoices_type 
ON invoices(invoice_type);

-- Index for class-based invoices
CREATE INDEX IF NOT EXISTS idx_invoices_class 
ON invoices(class_id);

-- Index for due date sorting
CREATE INDEX IF NOT EXISTS idx_invoices_due_date 
ON invoices(due_date);

-- Index for payment month
CREATE INDEX IF NOT EXISTS idx_invoices_payment_month 
ON invoices(payment_month);

-- Composite index for unpaid invoice queries
CREATE INDEX IF NOT EXISTS idx_invoices_unpaid_combo 
ON invoices(student_id, status, due_date);

-- Index for creation date
CREATE INDEX IF NOT EXISTS idx_invoices_created_at 
ON invoices(created_at DESC);

-- ============================================================
-- ENROLLMENTS TABLE
-- ============================================================

-- Index for student enrollment lookups
CREATE INDEX IF NOT EXISTS idx_enrollments_student 
ON enrollments(student_id);

-- Index for class enrollment lookups
CREATE INDEX IF NOT EXISTS idx_enrollments_class 
ON enrollments(class_id);

-- Index for enrollment status (active, inactive, completed)
CREATE INDEX IF NOT EXISTS idx_enrollments_status 
ON enrollments(status);

-- Composite index for active enrollments
CREATE INDEX IF NOT EXISTS idx_enrollments_active_combo 
ON enrollments(student_id, class_id, status);

-- Index for enrollment date
CREATE INDEX IF NOT EXISTS idx_enrollments_date 
ON enrollments(enrollment_date);

-- ============================================================
-- CLASSES TABLE
-- ============================================================

-- Index for class code lookups
CREATE INDEX IF NOT EXISTS idx_classes_code 
ON classes(class_code);

-- Index for class name searches
CREATE INDEX IF NOT EXISTS idx_classes_name 
ON classes(class_name);

-- ============================================================
-- ATTENDANCE TABLE
-- ============================================================

-- Index for student attendance lookups
CREATE INDEX IF NOT EXISTS idx_attendance_student 
ON attendance(student_id);

-- Index for class attendance
CREATE INDEX IF NOT EXISTS idx_attendance_class 
ON attendance(class_id);

-- Index for attendance date
CREATE INDEX IF NOT EXISTS idx_attendance_date 
ON attendance(attendance_date DESC);

-- Index for attendance status
CREATE INDEX IF NOT EXISTS idx_attendance_status 
ON attendance(status);

-- Composite index for attendance queries
CREATE INDEX IF NOT EXISTS idx_attendance_combo 
ON attendance(student_id, class_id, attendance_date DESC);

-- ============================================================
-- PARENT_ACCOUNTS TABLE
-- ============================================================

-- Index for email login
CREATE INDEX IF NOT EXISTS idx_parent_accounts_email 
ON parent_accounts(email);

-- Index for phone number lookups
CREATE INDEX IF NOT EXISTS idx_parent_accounts_phone 
ON parent_accounts(phone);

-- ============================================================
-- ADMIN_USERS TABLE
-- ============================================================

-- Index for username login
CREATE INDEX IF NOT EXISTS idx_admin_users_username 
ON admin_users(username);

-- Index for role-based queries
CREATE INDEX IF NOT EXISTS idx_admin_users_role 
ON admin_users(role);

-- ============================================================
-- VERIFICATION AND MAINTENANCE
-- ============================================================

-- Show all indexes in the database
-- Uncomment to verify indexes were created:
-- SELECT 
--     TABLE_NAME,
--     INDEX_NAME,
--     COLUMN_NAME,
--     SEQ_IN_INDEX
-- FROM INFORMATION_SCHEMA.STATISTICS
-- WHERE TABLE_SCHEMA = 'mlxysf_student_portal'
-- ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

-- ============================================================
-- ANALYZE TABLES FOR OPTIMIZATION
-- ============================================================
-- Run these periodically to update statistics

ANALYZE TABLE students;
ANALYZE TABLE registrations;
ANALYZE TABLE payments;
ANALYZE TABLE invoices;
ANALYZE TABLE enrollments;
ANALYZE TABLE classes;
ANALYZE TABLE attendance;
ANALYZE TABLE parent_accounts;
ANALYZE TABLE admin_users;

-- ============================================================
-- NOTES
-- ============================================================
-- 1. These indexes will improve query performance significantly
-- 2. They take up additional disk space (typically 10-20% more)
-- 3. Run ANALYZE TABLE periodically to keep statistics updated
-- 4. Monitor slow query log to identify additional optimization needs
-- 5. Indexes are automatically maintained by MySQL
-- ============================================================

-- ============================================================
-- BACKUP RECOMMENDATION
-- ============================================================
-- Before running this optimization:
-- 1. Backup your database: mysqldump -u username -p mlxysf_student_portal > backup.sql
-- 2. Run during low-traffic period if possible
-- 3. Test in development environment first
-- ============================================================

-- Query execution complete!
-- Check your application performance - you should see significant improvements!
