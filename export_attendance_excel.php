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

// Get class information INCLUDING SCHEDULE
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

// Get all attendance records for the month with notes
// attendance.student_id is a foreign key that references students.id
$stmt = $pdo->prepare("
    SELECT student_id, attendance_date, status, notes
    FROM attendance
    WHERE class_id = ? AND attendance_date >= ? AND attendance_date <= ?
    ORDER BY attendance_date
");
$stmt->execute([$selected_class, $month_start, $month_end]);
$attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create attendance lookup array with status and notes
// attendance.student_id already contains students.id (foreign key)
$attendance_map = [];
foreach ($attendance_records as $record) {
    $key = $record['student_id'] . '_' . $record['attendance_date'];
    $attendance_map[$key] = [
        'status' => $record['status'],
        'notes' => $record['notes']
    ];
}

// Generate date range for the month - ONLY CLASS DAYS
$dates = [];
$current = strtotime($month_start);
$end = strtotime($month_end);

// Map day_of_week to PHP day number (1=Monday, 7=Sunday)
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
    $day_of_week = date('N', $current); // 1 = Monday, 7 = Sunday
    
    // If class has a specific day set, only include that day
    if ($class_day_number) {
        if ($day_of_week == $class_day_number) {
            $dates[] = $date_str;
        }
    } else {
        // If no day set, include all days (fallback to old behavior)
        $dates[] = $date_str;
    }
    
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

// Write title row (class name with schedule info)
$title_month = date('F Y', strtotime($selected_month . '-01'));
$schedule_info = '';
if ($class['day_of_week'] && $class['start_time'] && $class['end_time']) {
    $schedule_info = " - Every " . $class['day_of_week'] . " (" . 
                     date('g:i A', strtotime($class['start_time'])) . " - " . 
                     date('g:i A', strtotime($class['end_time'])) . ")";
}
fputcsv($output, [$class['class_name'] . $schedule_info . " - Attendance Report for " . $title_month]);

// Write empty row for spacing
fputcsv($output, []);

// Build header row with dates
$header = ['Student ID', 'Student Name'];
foreach ($dates as $date) {
    $day_num = date('d', strtotime($date));
    $header[] = $day_num;
}
$header[] = 'Total Present';
$header[] = 'Total Absent';
$header[] = 'Total Late';
$header[] = 'Attendance %';
fputcsv($output, $header);

// Write date row (full dates for reference)
$date_row = ['', ''];
foreach ($dates as $date) {
    $formatted_date = date('D d/m', strtotime($date));
    $date_row[] = $formatted_date;
}
$date_row[] = '';
$date_row[] = '';
$date_row[] = '';
$date_row[] = '';
fputcsv($output, $date_row);

// Write student attendance data
foreach ($students as $student) {
    $row = [
        $student['student_id'],
        $student['full_name']
    ];
    
    $total_present = 0;
    $total_absent = 0;
    $total_late = 0;
    $total_excused = 0;
    
    foreach ($dates as $date) {
        // Use students.id (database ID) to match with attendance.student_id
        $key = $student['id'] . '_' . $date;
        $attendance = $attendance_map[$key] ?? null;
        
        $cell_value = '';
        
        if ($attendance) {
            $status = $attendance['status'];
            $notes = $attendance['notes'];
            
            // Convert status to single character
            switch ($status) {
                case 'present':
                    $cell_value = 'P';
                    $total_present++;
                    break;
                case 'absent':
                    $cell_value = 'A';
                    $total_absent++;
                    break;
                case 'late':
                    $cell_value = 'L';
                    $total_late++;
                    break;
                case 'excused':
                    $cell_value = 'E';
                    $total_excused++;
                    break;
            }
            
            // Add notes in brackets if they exist
            if ($notes) {
                $cell_value .= " [" . $notes . "]";
            }
        }
        
        $row[] = $cell_value;
    }
    
    // Calculate attendance percentage
    $total_classes = count($dates);
    $attendance_percentage = $total_classes > 0 ? round(($total_present / $total_classes) * 100, 1) : 0;
    
    $row[] = $total_present;
    $row[] = $total_absent;
    $row[] = $total_late;
    $row[] = $attendance_percentage . '%';
    
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

// Add notes explanation
fputcsv($output, []);
fputcsv($output, ['Notes Format:']);
fputcsv($output, ['Notes appear in brackets after the status code']);
fputcsv($output, ['Example: P [Arrived late] or A [Sick leave]']);

// Add summary info
fputcsv($output, []);
fputcsv($output, ['Summary:']);
fputcsv($output, ['Total Class Days in ' . $title_month, count($dates)]);
if ($class['day_of_week']) {
    fputcsv($output, ['Class Day', $class['day_of_week']]);
    if ($class['start_time'] && $class['end_time']) {
        fputcsv($output, ['Class Time', date('g:i A', strtotime($class['start_time'])) . ' - ' . date('g:i A', strtotime($class['end_time']))]);
    }
}

fclose($output);
exit();