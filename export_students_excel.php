<?php
require_once 'config.php';

// Check if admin is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    die('Unauthorized access');
}

// Get filter parameters
$statusFilter = $_GET['status_filter'] ?? '';
$paymentStatusFilter = $_GET['payment_status'] ?? 'approved';

// Build query - same as students.php
$sql = "SELECT r.*, 
        pa.email as parent_email,
        pa.full_name as parent_name,
        pa.phone as parent_phone,
        (SELECT COUNT(*) FROM enrollments WHERE student_id = r.student_account_id AND status = 'active') as enrollment_count,
        (SELECT GROUP_CONCAT(CONCAT(c.class_name, ' (', c.class_code, ')') SEPARATOR '; ') 
         FROM enrollments e 
         JOIN classes c ON e.class_id = c.id 
         WHERE e.student_id = r.student_account_id AND e.status = 'active') as enrolled_classes
        FROM registrations r
        LEFT JOIN parent_accounts pa ON r.parent_account_id = pa.id
        WHERE 1=1";
$params = [];

if ($paymentStatusFilter) {
    $sql .= " AND r.payment_status = ?";
    $params[] = $paymentStatusFilter;
}

if ($statusFilter) {
    $sql .= " AND r.student_status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate filename with current date
$filename = "students_export_" . date('Ymd_His') . ".csv";

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write title row
$title = "Students Export Report - Generated on " . date('F j, Y g:i A');
fputcsv($output, [$title]);

// Add filter info
$filter_info = "Filters Applied: ";
if ($paymentStatusFilter) {
    $filter_info .= "Payment Status = " . ucfirst($paymentStatusFilter) . " | ";
}
if ($statusFilter) {
    $filter_info .= "Student Status = " . $statusFilter;
}
fputcsv($output, [$filter_info]);

// Write empty row for spacing
fputcsv($output, []);

// Build header row
$header = [
    'Student ID',
    'Registration Number',
    'Name (English)',
    'Name (Chinese)',
    'Age',
    'School',
    'Grade',
    'Phone',
    'IC Number',
    'Email',
    'Student Status',
    'Payment Status',
    'Registered Events',
    'Parent Name',
    'Parent Email',
    'Parent Phone',
    'Number of Enrolled Classes',
    'Enrolled Classes',
    'Registration Date',
    'Remarks'
];
fputcsv($output, $header);

// Write student data
foreach ($students as $student) {
    $row = [
        $student['student_account_id'] ?? 'N/A',
        $student['registration_number'] ?? '',
        $student['name_en'] ?? '',
        $student['name_cn'] ?? '',
        $student['age'] ?? '',
        $student['school'] ?? '',
        $student['grade'] ?? '',
        $student['phone'] ?? '',
        $student['ic'] ?? '',
        $student['email'] ?? '',
        $student['student_status'] ?? '',
        ucfirst($student['payment_status'] ?? ''),
        $student['events'] ?? '',  // Registered events from registrations table
        $student['parent_name'] ?? 'N/A',
        $student['parent_email'] ?? '',
        $student['parent_phone'] ?? '',
        $student['enrollment_count'] ?? 0,
        $student['enrolled_classes'] ?? 'None',
        date('Y-m-d H:i', strtotime($student['created_at'])),
        $student['remarks'] ?? ''
    ];
    
    fputcsv($output, $row);
}

// Add summary at the end
fputcsv($output, []);
fputcsv($output, ['Summary:']);
fputcsv($output, ['Total Students', count($students)]);
fputcsv($output, ['Export Date', date('F j, Y g:i A')]);

// Add legend
fputcsv($output, []);
fputcsv($output, ['Student Status Types:']);
fputcsv($output, ['Student 学生', 'Regular student']);
fputcsv($output, ['State Team 州队', 'State team member']);
fputcsv($output, ['Backup Team 后备队', 'Backup team member']);

fputcsv($output, []);
fputcsv($output, ['Payment Status Types:']);
fputcsv($output, ['Approved', 'Registration fee paid and approved']);
fputcsv($output, ['Pending', 'Awaiting payment or approval']);
fputcsv($output, ['Rejected', 'Registration rejected']);

fclose($output);
exit();
