<?php
// Suppress any output before JSON
error_reporting(0);
ini_set('display_errors', 0);

session_start();

// Set JSON header first
header('Content-Type: application/json');

// Check if config file exists
if (!file_exists('../../includes/config.php')) {
    echo json_encode(['error' => 'Configuration file not found']);
    exit;
}

require_once '../../includes/config.php';

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
    
    // Fetch only the receipt data for this specific invoice
    $sql = "SELECT p.receipt_data, p.receipt_mime_type 
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
    
    if (empty($payment['receipt_data']) || empty($payment['receipt_mime_type'])) {
        echo json_encode(['error' => 'Receipt data is empty']);
        exit;
    }
    
    // Handle BLOB data - check if it's already base64 encoded or raw binary
    $receipt_data = $payment['receipt_data'];
    
    // If receipt_data is a resource (BLOB), read it and convert to base64
    if (is_resource($receipt_data)) {
        $receipt_data = stream_get_contents($receipt_data);
        if ($receipt_data === false) {
            echo json_encode(['error' => 'Failed to read receipt data']);
            exit;
        }
        $receipt_data = base64_encode($receipt_data);
    } 
    // If it's binary string (not base64), encode it
    else if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $receipt_data)) {
        $receipt_data = base64_encode($receipt_data);
    }
    // Otherwise it's already base64, use as-is
    
    // Return receipt data
    echo json_encode([
        'success' => true,
        'receipt_data' => $receipt_data,
        'receipt_mime_type' => $payment['receipt_mime_type']
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>