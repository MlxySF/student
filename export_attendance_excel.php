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
    die('Class not selected');
}

// Get class information
$stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->execute([$selected_class]);
$class = $stmt->fetch();

if (!$class) {
    die('Class not found');
}

// Parse month and get date range
$month_start = date('Y-m-01', strtotime($selected_month . '-01'));
$month_end = date('Y-m-t', strtotime($selected_month . '-01'));

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

// Get all attendance records for the month
$stmt = $pdo->prepare("
    SELECT student_id, attendance_date, status, notes
    FROM attendance
    WHERE class_id = ? AND attendance_date >= ? AND attendance_date <= ?
    ORDER BY attendance_date
");
$stmt->execute([$selected_class, $month_start, $month_end]);
$attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create attendance lookup array
$attendance_map = [];
foreach ($attendance_records as $record) {
    $key = $record['student_id'] . '_' . $record['attendance_date'];
    $attendance_map[$key] = $record['status'];
}

// Generate date range for the month
$dates = [];
$current = strtotime($month_start);
$end = strtotime($month_end);
while ($current <= $end) {
    $date_str = date('Y-m-d', $current);
    $day_of_week = date('N', $current); // 1 = Monday, 7 = Sunday
    
    // Skip weekends if desired, or include them
    // For now, include all days
    $dates[] = $date_str;
    
    $current = strtotime('+1 day', $current);
}

// Set headers for Excel download
$filename = $class['class_code'] . "_attendance_" . str_replace('-', '', $selected_month) . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write title row (class name)
$title_month = date('F Y', strtotime($selected_month . '-01'));
fputcsv($output, [$class['class_name'] . " - Attendance Report for " . $title_month]);

// Write empty row for spacing
fputcsv($output, []);

// Build header row with dates
$header = ['Student ID', 'Student Name'];
foreach ($dates as $date) {
    $day_num = date('d', strtotime($date));
    $header[] = $day_num;
}
fputcsv($output, $header);

// Write date row (full dates for reference)
$date_row = ['', ''];
foreach ($dates as $date) {
    $formatted_date = date('D d/m', strtotime($date));
    $date_row[] = $formatted_date;
}
fputcsv($output, $date_row);

// Write student attendance data
foreach ($students as $student) {
    $row = [
        $student['student_id'],
        $student['full_name']
    ];
    
    foreach ($dates as $date) {
        $key = $student['id'] . '_' . $date;
        $status = $attendance_map[$key] ?? '';
        
        // Convert status to single character or abbreviation
        switch ($status) {
            case 'present':
                $row[] = 'P';
                break;
            case 'absent':
                $row[] = 'A';
                break;
            case 'late':
                $row[] = 'L';
                break;
            case 'excused':
                $row[] = 'E';
                break;
            default:
                $row[] = '';
        }
    }
    
    fputcsv($output, $row);
}

// Add legend at the end
fputcsv($output, []);
fputcsv($output, ['Legend:']);
fputcsv($output, ['P', 'Present']);
fputcsv($output, ['A', 'Absent']);
fputcsv($output, ['L', 'Late']);
fputcsv($output, ['E', 'Excused']);
fputcsv($output, ['', 'No Record']);

fclose($output);
exit();
