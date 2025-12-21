<?php
/**
 * get_receipt.php - API to retrieve payment receipt for admin viewing
 * UPDATED 2025-12-21: Now reads from local file storage using serve_file.php
 * Returns file path and metadata for client-side display
 */

// Suppress any output before JSON
error_reporting(0);
ini_set('display_errors', 0);

session_start();

// Set JSON header first
header('Content-Type: application/json');

// Check if config file exists
if (!file_exists('../../config.php')) {
    echo json_encode(['success' => false, 'error' => 'Configuration file not found']);
    exit;
}

require_once '../../config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get invoice ID from request
$invoice_id = isset($_GET['invoice_id']) ? intval($_GET['invoice_id']) : 0;

if ($invoice_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid invoice ID']);
    exit;
}

try {
    // Check if PDO connection exists
    if (!isset($pdo)) {
        echo json_encode(['success' => false, 'error' => 'Database connection not available']);
        exit;
    }
    
    // Fetch the receipt file path from payments table
    $sql = "SELECT p.receipt_path 
            FROM payments p 
            WHERE p.invoice_id = :invoice_id
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':invoice_id', $invoice_id, PDO::PARAM_INT);
    $stmt->execute();
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        echo json_encode(['success' => false, 'error' => 'No payment record found for this invoice']);
        exit;
    }
    
    if (empty($payment['receipt_path'])) {
        echo json_encode(['success' => false, 'error' => 'No receipt file path found']);
        exit;
    }
    
    $receiptPath = $payment['receipt_path'];
    
    // Check if file exists
    $fullPath = '../../' . $receiptPath;
    if (!file_exists($fullPath)) {
        echo json_encode(['success' => false, 'error' => 'Receipt file not found on server: ' . $receiptPath]);
        exit;
    }
    
    // Get file extension and determine MIME type
    $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'pdf' => 'application/pdf',
        'webp' => 'image/webp'
    ];
    
    $mimeType = isset($mimeTypes[$extension]) ? $mimeTypes[$extension] : 'application/octet-stream';
    
    // Read file and encode to base64 for embedding
    $fileData = file_get_contents($fullPath);
    if ($fileData === false) {
        echo json_encode(['success' => false, 'error' => 'Failed to read receipt file']);
        exit;
    }
    
    $base64Data = base64_encode($fileData);
    
    // Return receipt data
    echo json_encode([
        'success' => true,
        'receipt_data' => $base64Data,
        'receipt_mime_type' => $mimeType,
        'receipt_path' => $receiptPath,
        'file_size' => filesize($fullPath),
        'file_extension' => $extension
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>