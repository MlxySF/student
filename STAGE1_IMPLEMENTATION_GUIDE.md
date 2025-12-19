# Stage 1: Multi-Child Support Implementation Guide

## Overview
Stage 1 implements the foundational database structure and authentication system to support parent accounts with multiple children.

## What's Included in Stage 1

### 1. Database Migration (`database_migration_multi_child.sql`) ✅
- Creates `parent_accounts` table for parent/guardian accounts
- Creates `parent_child_relationships` table for managing parent-child links
- Adds columns to existing tables:
  - `students`: `parent_account_id`, `student_type`, `ic_number`, `date_of_birth`, `age`, `school`, `student_status`
  - `registrations`: `parent_account_id`, `registration_type`
  - `invoices`: `parent_account_id`
  - `payments`: `parent_account_id`
- **Automatically migrates existing data** from registrations to create parent accounts
- Creates helpful database views for reporting

### 2. Authentication Helper (`auth_helper.php`) ✅
- Unified authentication supporting both parent and student logins
- Session management functions:
  - `isLoggedIn()`, `isParent()`, `isStudent()`
  - `getUserId()`, `getUserType()`, `getActiveStudentId()`
  - `setActiveStudent()` - for parents switching between children
- Context-aware data access functions
- Automatic child switching handler

### 3. Updated Student Portal (`index.php`) ✅
- Integrated with new authentication system
- **Child selector dropdown** for parent accounts (appears in header)
- Shows current child's name in page titles for parents
- Maintains backward compatibility with existing student logins
- Updated all form handlers to work with new system

## Installation Instructions

### Step 1: Backup Your Database ⚠️
**CRITICAL:** Before proceeding, create a complete database backup!

```bash
mysqldump -u mlxysf_student_portal -p mlxysf_student_portal > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Step 2: Run the Database Migration

1. **Download the migration file**:
   - [database_migration_multi_child.sql](https://github.com/MlxySF/student/blob/main/database_migration_multi_child.sql)

2. **Execute the migration**:
   ```bash
   mysql -u mlxysf_student_portal -p mlxysf_student_portal < database_migration_multi_child.sql
   ```

3. **Verify the migration**:
   ```sql
   -- Check that new tables exist
   SHOW TABLES LIKE 'parent%';
   
   -- Check that parent accounts were created from existing data
   SELECT COUNT(*) FROM parent_accounts;
   
   -- Check that students are linked to parents
   SELECT COUNT(*) FROM students WHERE parent_account_id IS NOT NULL;
   ```

### Step 3: Update Parent Email Addresses

The migration creates temporary email addresses for parents (e.g., `parent_IC123456@temp.com`). You need to update these:

```sql
-- View all parents needing email updates
SELECT parent_id, full_name, email, phone, ic_number 
FROM parent_accounts 
WHERE email LIKE '%@temp.com%';

-- Update individual parent emails (example)
UPDATE parent_accounts 
SET email = 'actual_parent_email@example.com' 
WHERE parent_id = 'PAR-2025-0001';
```

**OR** send emails to parents asking them to register their email addresses.

### Step 4: Set Parent Passwords

All parent accounts are created with default password: `password123`

**Option A: Reset all parent passwords manually**
```php
// Use this PHP script to generate new passwords
$new_password = 'YourSecurePassword123';
$hashed = password_hash($new_password, PASSWORD_DEFAULT);

UPDATE parent_accounts 
SET password = '$hashed' 
WHERE parent_id = 'PAR-2025-0001';
```

**Option B:** Implement a password reset system (recommended for production)

### Step 5: Test the New System

1. **Test Student Login** (existing functionality):
   - Login with existing student email and password
   - Should work exactly as before
   - Verify dashboard shows student data

2. **Test Parent Login** (new functionality):
   - Login with a parent email and password
   - Should see child selector in header
   - Switch between children using dropdown
   - Verify data changes for each child

3. **Test Child Switching**:
   - As a parent, navigate to different pages
   - Switch child using header dropdown
   - Verify correct child's data is displayed

## Understanding the New System

### Account Types

1. **Independent Student** (`student_type = 'independent'`):
   - Logs in with their own email/password
   - No parent account linked
   - Legacy behavior maintained

2. **Child Student** (`student_type = 'child'`):
   - Has a `parent_account_id` linking to parent
   - Can still login independently OR
   - Parent can view their data

3. **Parent Account** (new):
   - Stored in `parent_accounts` table
   - Can have multiple children linked
   - Views one child's data at a time
   - Switches between children using dropdown

### How Child Switching Works

1. Parent logs in → sees all their children
2. First child is selected by default
3. Parent can switch using dropdown in header
4. All queries use `getActiveStudentId()` to fetch correct child's data
5. Session stores `active_student_id` for current view

### Database Views Created

**`view_parent_children`** - Shows all parent-child relationships:
```sql
SELECT * FROM view_parent_children WHERE parent_id = 1;
```

**`view_parent_outstanding_invoices`** - Parent's total unpaid invoices:
```sql
SELECT * FROM view_parent_outstanding_invoices;
```

## Backward Compatibility

✅ **Existing student logins work unchanged**
✅ **All existing data is preserved**
✅ **Legacy functions maintained for compatibility**
✅ **Gradual migration - students can remain independent**

## What's Next?

### Stage 2: Student Portal Updates (Coming Next)
- Update all page queries to use `getActiveStudentId()`
- Add parent dashboard widget showing all children
- Update dashboard, invoices, payments, attendance, classes pages
- Implement parent-specific views

### Stage 3: Registration System Updates
- Allow parents to register multiple children
- Link new registrations to existing parent accounts
- Create parent account during first child registration

### Stage 4: Admin Portal Updates
- Show parent information in student details
- Allow admins to link/unlink children to parents
- Bulk invoice generation for all children

## Troubleshooting

### Issue: Migration fails with foreign key error
**Solution:** Make sure your database supports InnoDB and foreign keys are enabled
```sql
SHOW VARIABLES LIKE 'foreign_key_checks';
SET foreign_key_checks = 0;
-- Run migration
SET foreign_key_checks = 1;
```

### Issue: Parent login not working
**Solution:** Check that parent_accounts table exists and has data
```sql
SELECT * FROM parent_accounts LIMIT 5;
```

### Issue: Child selector not showing
**Solution:** Verify parent has children linked
```sql
SELECT * FROM parent_child_relationships WHERE parent_id = ?;
```

### Issue: Cannot switch between children
**Solution:** Check session data and auth_helper.php is included
```php
// Debug: Print session data
print_r($_SESSION);
```

## Rollback Instructions

If you need to rollback Stage 1:

```sql
-- Uncomment and run the rollback section at the end of database_migration_multi_child.sql
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
```

Then restore your backup:
```bash
mysql -u mlxysf_student_portal -p mlxysf_student_portal < backup_YYYYMMDD_HHMMSS.sql
```

## Support & Questions

If you encounter issues:
1. Check error logs: `error_log.txt`
2. Enable PHP error display for debugging
3. Verify database migration completed successfully
4. Test with a single parent account first

## Files Modified in Stage 1

- ✅ `database_migration_multi_child.sql` (NEW)
- ✅ `auth_helper.php` (NEW)
- ✅ `index.php` (UPDATED)
- ✅ `STAGE1_IMPLEMENTATION_GUIDE.md` (NEW)

## Success Criteria

- [ ] Database migration runs without errors
- [ ] Parent accounts created from existing registrations
- [ ] Existing student logins still work
- [ ] Parent can login with new credentials
- [ ] Child selector appears for parents
- [ ] Switching between children works
- [ ] Dashboard shows correct child's data

---

**Next:** Proceed to Stage 2 to update all student portal pages with parent support.
