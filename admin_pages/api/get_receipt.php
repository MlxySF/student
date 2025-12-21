<?php
/**
 * get_receipt.php - Secure Receipt File Serving for Admin
 * Directly serves payment receipt files with admin authentication
 */

// Suppress any output before headers
error_reporting(0);
ini_set('display_errors', 0);

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if config file exists
if (!file_exists('../../config.php')) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Configuration file not found']);
    exit;
}

require_once '../../config.php';
require_once '../../file_storage_helper.php';

// Get invoice ID from request
$invoice_id = isset($_GET['invoice_id']) ? intval($_GET['invoice_id']) : 0;

if ($invoice_id <= 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid invoice ID']);
    exit;
}

try {
    // Check if PDO connection exists
    if (!isset($pdo)) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database connection not available']);
        exit;
    }
    
    // Fetch receipt filename and MIME type
    $sql = "SELECT p.receipt_filename, p.receipt_mime_type 
            FROM payments p 
            WHERE p.invoice_id = :invoice_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':invoice_id', $invoice_id, PDO::PARAM_INT);
    $stmt->execute();
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'No receipt found for this invoice']);
        exit;
    }
    
    if (empty($payment['receipt_filename']) || empty($payment['receipt_mime_type'])) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Receipt file not available']);
        exit;
    }
    
    // Construct file path
    $filepath = PAYMENT_RECEIPTS_DIR . $payment['receipt_filename'];
    
    // Validate file exists
    if (!file_exists($filepath)) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Receipt file not found on server']);
        exit;
    }
    
    // Validate file is readable
    if (!is_readable($filepath)) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Receipt file not accessible']);
        exit;
    }
    
    // Serve the file directly
    header('Content-Type: ' . $payment['receipt_mime_type']);
    header('Content-Disposition: inline; filename="' . basename($payment['receipt_filename']) . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: private, max-age=3600');
    header('X-Content-Type-Options: nosniff');
    
    // Output file content
    readfile($filepath);
    exit;
    
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>