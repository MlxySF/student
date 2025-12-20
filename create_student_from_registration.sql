-- ===========================================================
-- HELPER: Create student records from approved registrations
-- Run this to create missing student records
-- ===========================================================

-- This will create student records for all approved registrations
-- that don't have a corresponding student record yet

INSERT INTO students (
    student_id,
    full_name,
    email,
    phone,
    password,
    parent_account_id,
    created_at
)
SELECT 
    r.registration_number,
    r.name_en,
    p.email,
    r.contact_number,
    p.password,  -- Use parent's password (they share login)
    r.parent_account_id,
    NOW()
FROM registrations r
JOIN parent_accounts p ON r.parent_account_id = p.id
WHERE r.payment_status = 'approved'
AND NOT EXISTS (
    SELECT 1 FROM students s 
    WHERE s.student_id = r.registration_number
);

-- Check how many students were created
SELECT COUNT(*) as students_created FROM students;
