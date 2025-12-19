# Fresh Database Installation Guide

## Quick Start - Complete Database Setup

Since you've deleted your existing database, use this complete schema that includes all features including multi-child support from the start.

## Installation Steps

### Step 1: Create the Database

```bash
mysql -u root -p
```

Then in MySQL:

```sql
CREATE DATABASE mlxysf_student_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create database user (if not exists)
CREATE USER IF NOT EXISTS 'mlxysf_student_portal'@'localhost' IDENTIFIED BY 'YAjv86kdSAPpw';

-- Grant privileges
GRANT ALL PRIVILEGES ON mlxysf_student_portal.* TO 'mlxysf_student_portal'@'localhost';
FLUSH PRIVILEGES;

USE mlxysf_student_portal;
```

### Step 2: Import the Complete Schema

```bash
mysql -u mlxysf_student_portal -p mlxysf_student_portal < database_schema_complete_with_multi_child.sql
```

Or if you're already in MySQL:

```sql
USE mlxysf_student_portal;
source database_schema_complete_with_multi_child.sql;
```

### Step 3: Verify Installation

```sql
-- Check all tables were created
SHOW TABLES;

-- Should show:
-- admin_users
-- attendance
-- classes
-- enrollments
-- invoices
-- parent_accounts
-- parent_child_relationships
-- payments
-- registrations
-- students
-- view_parent_children (VIEW)
-- view_parent_outstanding_invoices (VIEW)

-- Verify sample data
SELECT * FROM admin_users;
SELECT * FROM parent_accounts;
SELECT * FROM students;
SELECT * FROM classes;
```

## Test Accounts Created

### Admin Account
- **Username:** `admin`
- **Password:** `admin123`
- **Access:** http://your-domain.com/student/admin.php

### Parent Account
- **Email:** `parent@example.com`
- **Password:** `parent123`
- **Parent ID:** PAR-2025-0001
- **Children:** 2 (Alice and Bob)

### Student Accounts (Children of parent)

**Child 1:**
- **Email:** `alice@example.com`
- **Password:** `student123`
- **Student ID:** WSA2025-0001
- **Status:** State Team 州队
- **Parent:** John Doe

**Child 2:**
- **Email:** `bob@example.com`
- **Password:** `student123`
- **Student ID:** WSA2025-0002
- **Status:** Normal Student
- **Parent:** John Doe

## What's Included

### Database Tables:
1. **admin_users** - Admin portal accounts
2. **parent_accounts** - Parent/guardian accounts (NEW)
3. **students** - Student accounts with parent linking
4. **parent_child_relationships** - Parent-child associations (NEW)
5. **classes** - Available courses
6. **enrollments** - Student class enrollments
7. **registrations** - Registration forms
8. **invoices** - Student invoices
9. **payments** - Payment records
10. **attendance** - Attendance tracking

### Database Views:
1. **view_parent_children** - Easy parent-child listing
2. **view_parent_outstanding_invoices** - Parent payment summary

### Sample Data:
- 1 Admin account
- 1 Parent account
- 2 Student accounts (as children)
- 4 Classes
- 2 Enrollments
- 2 Sample invoices

## Testing the System

### Test 1: Admin Login
1. Go to: `http://your-domain.com/student/admin.php`
2. Login with `admin` / `admin123`
3. Should see admin dashboard

### Test 2: Parent Login
1. Go to: `http://your-domain.com/student/`
2. Login with `parent@example.com` / `parent123`
3. Should see child selector in header
4. Should display Alice's data by default
5. Switch to Bob using dropdown
6. Data should update to show Bob's information

### Test 3: Student Login (Independent)
1. Go to: `http://your-domain.com/student/`
2. Login with `alice@example.com` / `student123`
3. Should see Alice's own dashboard
4. No child selector (student viewing own data)

### Test 4: View Parent's Children
```sql
-- View parent with all children
SELECT * FROM view_parent_children WHERE parent_code = 'PAR-2025-0001';

-- View parent's outstanding invoices
SELECT * FROM view_parent_outstanding_invoices;
```

## Key Features Now Available

✅ **Multi-child parent accounts**
✅ **Child selector dropdown**
✅ **Parent can view each child's data separately**
✅ **Students can still login independently**
✅ **Invoices linked to parent accounts**
✅ **Payments tracked by parent**
✅ **UTF-8 support for Chinese characters**

## Default Passwords

⚠️ **IMPORTANT:** Change these default passwords immediately in production!

- Admin: `admin123`
- Parent: `parent123`
- Students: `student123`

### To change a password:

```php
<?php
// Generate new password hash
$new_password = 'your_secure_password';
$hashed = password_hash($new_password, PASSWORD_DEFAULT);
echo $hashed;
?>
```

Then update in database:

```sql
-- For admin
UPDATE admin_users SET password = 'generated_hash' WHERE username = 'admin';

-- For parent
UPDATE parent_accounts SET password = 'generated_hash' WHERE email = 'parent@example.com';

-- For student
UPDATE students SET password = 'generated_hash' WHERE email = 'alice@example.com';
```

## Database Schema Features

### Parent Account Support
- Parents can have multiple children
- Each child can have multiple parents (divorced parents, guardians)
- Relationship types: father, mother, guardian, other
- Primary parent designation
- Permission controls per parent

### Student Types
- **Independent:** Logs in themselves, no parent account
- **Child:** Has parent account, parent can view their data

### Invoices & Payments
- Invoices linked to both student AND parent
- Payments tracked by who paid (parent or student)
- Parents can see all children's invoices

## Troubleshooting

### Cannot login as parent
**Check:**
```sql
SELECT * FROM parent_accounts WHERE email = 'parent@example.com';
SELECT * FROM parent_child_relationships WHERE parent_id = 1;
```

### Child selector not showing
**Check:**
- Are there children linked? Run: `SELECT * FROM view_parent_children;`
- Is auth_helper.php included in index.php?
- Check browser console for JavaScript errors

### Chinese characters showing as ??
**Fix:**
```sql
-- Check database charset
SHOW CREATE DATABASE mlxysf_student_portal;

-- Check table charset
SHOW CREATE TABLE students;

-- If needed, convert:
ALTER DATABASE mlxysf_student_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE students CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## Next Steps

1. **Change all default passwords**
2. **Test parent login and child switching**
3. **Add your real classes** (or keep the sample ones)
4. **Delete sample data** if not needed:
   ```sql
   DELETE FROM invoices WHERE invoice_number LIKE 'INV-2025%';
   DELETE FROM enrollments;
   DELETE FROM parent_child_relationships;
   DELETE FROM students WHERE student_id LIKE 'WSA2025%';
   DELETE FROM parent_accounts WHERE parent_id = 'PAR-2025-0001';
   ```
5. **Ready to accept new registrations!**

## Support

If you encounter issues:
- Check `error_log.txt` for PHP errors
- Check MySQL error log
- Verify all files are uploaded (auth_helper.php, updated index.php)
- Ensure database charset is utf8mb4

---

**Database Schema File:** [`database_schema_complete_with_multi_child.sql`](https://github.com/MlxySF/student/blob/main/database_schema_complete_with_multi_child.sql)

**Installation Complete!** You now have a fresh database with full multi-child parent support. 🎉
