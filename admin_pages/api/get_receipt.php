<?php
// Suppress any output before JSON
error_reporting(0);
ini_set('display_errors', 0);

session_start();

// Set JSON header first
header('Content-Type: application/json');

// Check if config file exists
if (!file_exists('../../config.php')) {
    echo json_encode(['error' => 'Configuration file not found']);
    exit;
}

require_once '../../config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get invoice ID from request
$invoice_id = isset($_GET['invoice_id']) ? intval($_GET['invoice_id']) : 0;

if ($invoice_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid invoice ID']);
    exit;
}

try {
    // Check if PDO connection exists
    if (!isset($pdo)) {
        echo json_encode(['error' => 'Database connection not available']);
        exit;
    }
    
    // UPDATED: Fetch receipt_filename instead of receipt_data
    $sql = "SELECT p.receipt_filename, p.receipt_mime_type 
            FROM payments p 
            WHERE p.invoice_id = :invoice_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':invoice_id', $invoice_id, PDO::PARAM_INT);
    $stmt->execute();
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        echo json_encode(['error' => 'No receipt found for this invoice']);
        exit;
    }
    
    if (empty($payment['receipt_filename']) || empty($payment['receipt_mime_type'])) {
        echo json_encode(['error' => 'Receipt file not available']);
        exit;
    }
    
    // UPDATED: Return file URL instead of base64 data
    // Use serve_file.php to serve the file securely
    $fileUrl = '../../serve_file.php?type=payment_receipt&file=' . urlencode($payment['receipt_filename']);
    
    // Return receipt file information
    echo json_encode([
        'success' => true,
        'receipt_url' => $fileUrl,
        'receipt_filename' => $payment['receipt_filename'],
        'receipt_mime_type' => $payment['receipt_mime_type'],
        'is_pdf' => ($payment['receipt_mime_type'] === 'application/pdf')
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>