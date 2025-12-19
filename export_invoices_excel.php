<?php
require_once 'config.php';

// Check if admin is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    die('Unauthorized access');
}

// Get filter parameters
$filter_month = isset($_GET['filter_month']) ? trim($_GET['filter_month']) : '';
$filter_type = isset($_GET['filter_type']) ? trim($_GET['filter_type']) : '';

// Convert month input (2025-12) to database format (Dec 2025)
$filter_month_formatted = '';
if ($filter_month) {
    $filter_month_formatted = date('M Y', strtotime($filter_month . '-01'));
}

// Build WHERE clauses for JOIN queries
$where_conditions = [];
$params = [];

if ($filter_month_formatted) {
    $where_conditions[] = "TRIM(i.payment_month) = ?";
    $params[] = $filter_month_formatted;
}

if ($filter_type) {
    $where_conditions[] = "i.invoice_type = ?";
    $params[] = $filter_type;
}

// Get all invoices with student and class information
$sql = "
    SELECT i.invoice_number, i.created_at, i.invoice_type, i.description, 
           i.amount, i.due_date, i.status, i.paid_date, i.payment_month,
           s.student_id, s.full_name, s.email,
           c.class_code, c.class_name
    FROM invoices i
    JOIN students s ON i.student_id = s.id
    LEFT JOIN classes c ON i.class_id = c.id";

if (count($where_conditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$sql .= " ORDER BY i.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for Excel download
$filename = "invoices_export_" . date('Y-m-d_His');
if ($filter_type) {
    $filename .= "_" . $filter_type;
}
if ($filter_month) {
    $filename .= "_" . str_replace('-', '', $filter_month);
}
$filename .= ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Define headers - always include class code and class name
$headers = [
    'Invoice Number',
    'Date Created',
    'Student ID',
    'Student Name',
    'Student Email',
    'Class Code',
    'Class Name',
    'Invoice Type',
    'Description',
    'Payment Month',
    'Amount (RM)',
    'Due Date',
    'Status',
    'Paid Date'
];

fputcsv($output, $headers);

// Write data rows
foreach ($invoices as $invoice) {
    $row = [
        $invoice['invoice_number'],
        date('Y-m-d H:i:s', strtotime($invoice['created_at'])),
        $invoice['student_id'],
        $invoice['full_name'],
        $invoice['email'],
        $invoice['class_code'] ?? 'N/A',
        $invoice['class_name'] ?? 'N/A',
        ucfirst(str_replace('_', ' ', $invoice['invoice_type'])),
        $invoice['description'],
        $invoice['payment_month'] ?? 'N/A',
        number_format($invoice['amount'], 2),
        $invoice['due_date'],
        ucfirst($invoice['status']),
        $invoice['paid_date'] ? date('Y-m-d H:i:s', strtotime($invoice['paid_date'])) : 'N/A'
    ];
    
    fputcsv($output, $row);
}

fclose($output);
exit();