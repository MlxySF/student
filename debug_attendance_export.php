<?php
require_once 'config.php';

// Check if admin is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    die('Unauthorized access');
}

// Get parameters
$selected_class = $_GET['class_id'] ?? null;
$selected_month = $_GET['month'] ?? date('Y-m');

if (!$selected_class) {
    die('Please provide class_id in URL, e.g., ?class_id=1&month=2025-12');
}

echo "<h2>Attendance Export Debug</h2>";
echo "<p>Class ID: $selected_class</p>";
echo "<p>Month: $selected_month</p>";

// Get class information
$stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->execute([$selected_class]);
$class = $stmt->fetch();

if (!$class) {
    die('Class not found');
}

echo "<h3>Class Information</h3>";
echo "<pre>";
print_r($class);
echo "</pre>";

// Parse month and get date range
$month_start = date('Y-m-01', strtotime($selected_month . '-01'));
$month_end = date('Y-m-t', strtotime($selected_month . '-01'));

echo "<h3>Date Range</h3>";
echo "<p>Start: $month_start</p>";
echo "<p>End: $month_end</p>";

// Get all students enrolled in the class
$stmt = $pdo->prepare("
    SELECT s.id, s.student_id, s.full_name
    FROM enrollments e
    JOIN students s ON e.student_id = s.id
    WHERE e.class_id = ? AND e.status = 'active'
    ORDER BY s.full_name
");
$stmt->execute([$selected_class]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Enrolled Students (" . count($students) . ")</h3>";
echo "<pre>";
print_r($students);
echo "</pre>";

// Get all attendance records for the month
$stmt = $pdo->prepare("
    SELECT *
    FROM attendance
    WHERE class_id = ? AND attendance_date >= ? AND attendance_date <= ?
    ORDER BY attendance_date
");
$stmt->execute([$selected_class, $month_start, $month_end]);
$attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Attendance Records (" . count($attendance_records) . ")</h3>";
echo "<pre>";
print_r($attendance_records);
echo "</pre>";

// Create attendance lookup array
$attendance_map = [];
foreach ($attendance_records as $record) {
    $key = $record['student_id'] . '_' . $record['attendance_date'];
    $attendance_map[$key] = $record['status'];
}

echo "<h3>Attendance Map</h3>";
echo "<pre>";
print_r($attendance_map);
echo "</pre>";

// Generate date range for the month - ONLY CLASS DAYS
$dates = [];
$current = strtotime($month_start);
$end = strtotime($month_end);

$day_map = [
    'Monday' => 1,
    'Tuesday' => 2,
    'Wednesday' => 3,
    'Thursday' => 4,
    'Friday' => 5,
    'Saturday' => 6,
    'Sunday' => 7
];

$class_day_number = $class['day_of_week'] ? $day_map[$class['day_of_week']] : null;

while ($current <= $end) {
    $date_str = date('Y-m-d', $current);
    $day_of_week = date('N', $current);
    
    if ($class_day_number) {
        if ($day_of_week == $class_day_number) {
            $dates[] = $date_str;
        }
    } else {
        $dates[] = $date_str;
    }
    
    $current = strtotime('+1 day', $current);
}

echo "<h3>Class Dates (" . count($dates) . ")</h3>";
echo "<pre>";
print_r($dates);
echo "</pre>";

// Test lookup for first student
if (count($students) > 0 && count($dates) > 0) {
    echo "<h3>Test Lookup for First Student</h3>";
    $test_student = $students[0];
    echo "<p>Student ID (database): " . $test_student['id'] . "</p>";
    echo "<p>Student ID (display): " . $test_student['student_id'] . "</p>";
    echo "<p>Student Name: " . $test_student['full_name'] . "</p>";
    
    foreach ($dates as $date) {
        $key = $test_student['id'] . '_' . $date;
        $status = $attendance_map[$key] ?? 'NOT FOUND';
        echo "<p>Date: $date | Lookup Key: $key | Status: $status</p>";
    }
}

echo "<hr>";
echo "<p><a href='export_attendance_excel.php?class_id=$selected_class&month=$selected_month'>Try Export Again</a></p>";
echo "<p><a href='admin.php?page=attendance'>Back to Attendance Page</a></p>";