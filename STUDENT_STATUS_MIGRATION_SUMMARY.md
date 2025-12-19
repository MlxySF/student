# Student Status Column Migration Summary

## Overview
This document summarizes the migration to use the `student_status` column in the `registrations` table throughout the Wushu Academy application.

## Database Changes

### 1. Add student_status Column
**File:** `ADD_STUDENT_STATUS_TO_REGISTRATIONS.sql`

**Action Required:** Run this SQL on your database:

```sql
ALTER TABLE registrations 
ADD COLUMN student_status VARCHAR(100) NOT NULL DEFAULT 'Student 学生' 
AFTER status;

CREATE INDEX idx_registrations_student_status ON registrations(student_status);
```

**Purpose:** 
- Tracks student type: "Student 学生", "State Team 州队", or "Backup Team 后备队"
- Essential for filtering and displaying student status throughout admin and parent portals

### 2. Migrate Existing Data (Optional)
If you have existing student_status data in the `students` table:

```sql
UPDATE registrations r
INNER JOIN students s ON r.student_account_id = s.id
SET r.student_status = s.student_status
WHERE s.student_status IS NOT NULL;
```

## Code Changes Made

### Files Updated ✅

#### 1. **admin_pages/registrations.php**
**Commit:** f01bf2561ef836396a1aef39d19cda211f25c607

**Changes:**
- Line 44: Changed `$reg['status']` to `$reg['student_status']`
- Line 222: Changed `$reg['status']` to `$reg['student_status']`
- Added "Student Status" label for clarity

**Impact:** Registration list now correctly displays student status badges

#### 2. **admin_pages/students.php** 
**Status:** Already correct (uses `student_status` from registrations)

**Features:**
- Filters by student_status
- Displays status badges (Student/State Team/Backup Team)
- Edit modal includes student_status dropdown

#### 3. **pages/dashboard.php** (Parent Portal)
**Status:** Already correct

**Features:**
- Line 171: Displays student_status in children summary table
- Line 359: Shows status badge in student info section
- Color-coded badges: Success (State Team), Warning (Backup Team), Info (Student)

#### 4. **pages/profile.php** (Parent Portal)
**Status:** Already correct

**Features:**
- Line 148: Displays student_status badge in profile header
- Properly reads from registrations table

#### 5. **admin_handler.php**
**Status:** Already correct

**Features:**
- `edit_student_registration` action updates student_status
- Properly writes to registrations table

## Student Status Values

### Valid Options
1. **"Student 学生"** (Default)
   - Regular enrolled student
   - Badge: Blue/Info color

2. **"State Team 州队"**
   - State-level team member
   - Badge: Green/Success color

3. **"Backup Team 后备队"**
   - Backup team member
   - Badge: Yellow/Warning color

## CSS Classes Used

```css
.badge-student { background: #3b82f6; } /* Blue */
.badge-state-team { background: #10b981; } /* Green */
.badge-backup-team { background: #f59e0b; } /* Yellow */
```

## Usage Throughout Application

### Admin Portal
1. **New Registrations Page** - View pending registrations
2. **Students Page** - Filter and manage students by status
3. **Registration Details Modal** - View student status in detail

### Parent Portal
1. **Dashboard** - View status for each child
2. **Profile** - Display student status badge
3. **Children Summary Table** - Compare status across children

## Testing Checklist

### After Running Migration SQL:

- [ ] Verify column exists: `SHOW COLUMNS FROM registrations LIKE 'student_status';`
- [ ] Check default value is set: Query should show `Default: Student 学生`
- [ ] Admin portal - Students page loads without error
- [ ] Admin portal - Can filter by student status
- [ ] Admin portal - Edit student modal shows status dropdown
- [ ] Admin portal - Registrations page displays status badges
- [ ] Parent portal - Dashboard shows status for children
- [ ] Parent portal - Profile page displays status badge
- [ ] Status badges display correct colors

## Migration Steps

### Step 1: Backup Database
```bash
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
```

### Step 2: Run Migration SQL
```bash
mysql -u username -p database_name < ADD_STUDENT_STATUS_TO_REGISTRATIONS.sql
```

OR via phpMyAdmin:
1. Go to SQL tab
2. Paste SQL from `ADD_STUDENT_STATUS_TO_REGISTRATIONS.sql`
3. Click "Go"

### Step 3: Verify Column Added
```sql
SHOW COLUMNS FROM registrations LIKE 'student_status';
```

Expected output:
```
Field: student_status
Type: varchar(100)
Null: NO
Key: MUL
Default: Student 学生
Extra: 
```

### Step 4: Test Application
1. Visit admin portal → Students page
2. Verify page loads without SQL errors
3. Test filtering by student status
4. Edit a student and change status
5. Visit parent portal dashboard
6. Verify status badges display correctly

### Step 5: Update Existing Records (Optional)
If you have students that should be State Team or Backup Team:

```sql
-- Example: Update specific students
UPDATE registrations 
SET student_status = 'State Team 州队' 
WHERE registration_number IN ('WA20240001', 'WA20240002');

UPDATE registrations 
SET student_status = 'Backup Team 后备队' 
WHERE registration_number IN ('WA20240003', 'WA20240004');
```

## Troubleshooting

### Error: Column 'student_status' not found
**Solution:** Run the migration SQL from Step 2

### Status not displaying
**Solution:** Check that column has data:
```sql
SELECT student_status, COUNT(*) 
FROM registrations 
GROUP BY student_status;
```

### Badges showing wrong colors
**Solution:** Clear browser cache and check CSS file includes badge styles

## Files Reference

### SQL Files
- `ADD_STUDENT_STATUS_TO_REGISTRATIONS.sql` - Schema migration

### PHP Files Modified
- `admin_pages/registrations.php` - Display status in admin
- `admin_pages/students.php` - Already correct
- `admin_handler.php` - Already correct
- `pages/dashboard.php` - Already correct (parent portal)
- `pages/profile.php` - Already correct (parent portal)

## Support

If you encounter any issues during migration:
1. Check database error logs
2. Verify SQL syntax is correct for your MySQL version
3. Ensure user has ALTER TABLE permissions
4. Test on staging environment first

---

**Migration Date:** December 20, 2025  
**Version:** 1.0  
**Status:** Complete - Ready for deployment