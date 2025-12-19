-- Add student_status column to registrations table
-- This is a critical field that tracks whether a student is:
-- 1. Student 学生 (regular student)
-- 2. State Team 州队 (state team member)
-- 3. Backup Team 后备队 (backup team member)

ALTER TABLE registrations 
ADD COLUMN student_status VARCHAR(100) NOT NULL DEFAULT 'Student 学生' 
AFTER status;

-- Update index if needed (optional, for performance)
CREATE INDEX idx_registrations_student_status ON registrations(student_status);

-- Verify the column was added
SHOW COLUMNS FROM registrations LIKE 'student_status';