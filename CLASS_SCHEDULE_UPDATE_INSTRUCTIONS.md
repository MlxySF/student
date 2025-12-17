# Class Schedule Implementation Instructions

This document explains how to add class schedules (day of week and time slots) to your system.

## Step 1: Run the SQL Update

1. Go to phpMyAdmin and select your database `mlxysf_student_portal`
2. Go to the SQL tab
3. Copy and paste the contents of `update_class_schedule.sql`
4. Click "Go" to execute

This will add three new columns to the `classes` table:
- `day_of_week` (Monday, Tuesday, Wednesday, Thursday, Friday, Saturday, Sunday)
- `start_time` (e.g., 10:00:00)
- `end_time` (e.g., 12:00:00)

## Step 2: Update admin_handler.php

Open `admin_handler.php` and find the section:

```php
// ============ CLASS MANAGEMENT ============
```

Replace the `create_class` and `edit_class` actions with the code from `admin_handler_class_schedule_update.php`

OR manually update:

### In create_class action:
Change FROM:
```php
$stmt = $pdo->prepare("INSERT INTO classes (class_code, class_name, monthly_fee, description) VALUES (?, ?, ?, ?)");
$stmt->execute([$class_code, $class_name, $monthly_fee, $description]);
```

TO:
```php
$day_of_week = $_POST['day_of_week'] ?? null;
$start_time = $_POST['start_time'] ?? null;
$end_time = $_POST['end_time'] ?? null;

$stmt = $pdo->prepare("INSERT INTO classes (class_code, class_name, monthly_fee, description, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$class_code, $class_name, $monthly_fee, $description, $day_of_week, $start_time, $end_time]);
```

### In edit_class action:
Change FROM:
```php
$stmt = $pdo->prepare("UPDATE classes SET class_code = ?, class_name = ?, monthly_fee = ?, description = ? WHERE id = ?");
$stmt->execute([$class_code, $class_name, $monthly_fee, $description, $id]);
```

TO:
```php
$day_of_week = $_POST['day_of_week'] ?? null;
$start_time = $_POST['start_time'] ?? null;
$end_time = $_POST['end_time'] ?? null;

$stmt = $pdo->prepare("UPDATE classes SET class_code = ?, class_name = ?, monthly_fee = ?, description = ?, day_of_week = ?, start_time = ?, end_time = ? WHERE id = ?");
$stmt->execute([$class_code, $class_name, $monthly_fee, $description, $day_of_week, $start_time, $end_time, $id]);
```

## Step 3: Files Already Updated

These files have already been updated:
- ✅ `admin_pages/classes.php` - Now includes schedule fields in create/edit forms

## Step 4: What This Enables

Once implemented, each class will have:
- A specific day of the week (e.g., Sunday)
- Start time (e.g., 10:00 AM)
- End time (e.g., 12:00 PM)

This will be used for:
1. **Displaying class schedules** throughout the website
2. **Attendance export** - Only export attendance for dates that match the class day
3. **Calendar views** - Show when each class meets

## Step 5: Set Existing Classes Schedule

After running the SQL, go to Admin Portal > Classes and edit each class to set their schedule:
- WSA Class: Sunday, 10:00 AM - 12:00 PM
- (Set others as needed)

## Next Steps

After completing these steps, I can update:
1. Attendance export to only include class days (e.g., only Sundays for Sunday classes)
2. Student portal to show class schedules
3. Registration forms to display schedule information
4. Calendar view for class schedules

## Questions?

If you need help with any step, let me know!