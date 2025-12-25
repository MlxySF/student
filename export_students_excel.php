<?php
require_once 'config.php';

// Check if admin is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    die('Unauthorized access');
}

// Get filters
$statusFilter = $_GET['status_filter'] ?? '';
$paymentStatusFilter = $_GET['payment_status'] ?? 'approved';

// Build query - use registrations table with all details
$sql = "SELECT r.*, 
        pa.email as parent_email,
        pa.full_name as parent_name,
        pa.phone as parent_phone,
        (SELECT COUNT(*) FROM enrollments WHERE student_id = r.student_account_id AND status = 'active') as enrollment_count,
        (SELECT GROUP_CONCAT(c.class_name SEPARATOR ', ') 
         FROM enrollments e 
         JOIN classes c ON e.class_id = c.id 
         WHERE e.student_id = r.student_account_id AND e.status = 'active') as enrolled_classes
        FROM registrations r
        LEFT JOIN parent_accounts pa ON r.parent_account_id = pa.id
        WHERE 1=1";
$params = [];

// Apply filters
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

// Set headers for Excel download
$filename = "students_export_" . date('Ymd_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write title row
fputcsv($output, ['Student Details Report - Generated on ' . date('F d, Y g:i A')]);
fputcsv($output, []);

// Write header row
$header = [
    'Student ID',
    'Registration Number',
    'Name (English)',
    'Name (Chinese)',
    'Age',
    'School',
    'Phone',
    'IC Number',
    'Email',
    'Student Status',
    'Payment Status',
    'Registered Events',
    'Parent Name',
    'Parent Email',
    'Parent Phone',
    'Number of Classes Enrolled',
    'Enrolled Classes',
    'Registration Date'
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
        $student['phone'] ?? '',
        $student['ic'] ?? '',
        $student['email'] ?? '',
        $student['student_status'] ?? '',
        $student['payment_status'] ?? '',
        $student['events'] ?? 'None',  // Registered Events column
        $student['parent_name'] ?? 'N/A',
        $student['parent_email'] ?? 'N/A',
        $student['parent_phone'] ?? 'N/A',
        $student['enrollment_count'] ?? 0,
        $student['enrolled_classes'] ?? 'None',
        $student['created_at'] ? date('M d, Y', strtotime($student['created_at'])) : '',
    ];
    
    fputcsv($output, $row);
}

// Add summary at the end
fputcsv($output, []);
fputcsv($output, ['Summary:']);
fputcsv($output, ['Total Students Exported', count($students)]);
if ($paymentStatusFilter) {
    fputcsv($output, ['Payment Status Filter', ucfirst($paymentStatusFilter)]);
}
if ($statusFilter) {
    fputcsv($output, ['Student Status Filter', $statusFilter]);
}
fputcsv($output, ['Export Date/Time', date('F d, Y g:i A')]);

fclose($output);
exit();
