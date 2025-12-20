<?php
session_start();
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
    // Fetch only the receipt data for this specific invoice
    $sql = "SELECT p.receipt_data, p.receipt_mime_type 
            FROM payments p 
            WHERE p.invoice_id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$invoice_id]);
    $payment = $stmt->fetch();
    
    if (!$payment) {
        echo json_encode(['error' => 'No receipt found']);
        exit;
    }
    
    if (empty($payment['receipt_data']) || empty($payment['receipt_mime_type'])) {
        echo json_encode(['error' => 'Receipt data is empty']);
        exit;
    }
    
    // Return receipt data
    echo json_encode([
        'success' => true,
        'receipt_data' => $payment['receipt_data'],
        'receipt_mime_type' => $payment['receipt_mime_type']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>