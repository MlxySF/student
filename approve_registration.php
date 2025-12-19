<?php
/**
 * approve_registration.php
 * Approves a registration and automatically approves the linked invoice
 * Updates both registrations and invoices tables
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['registration_id'])) {
        throw new Exception('Missing registration_id');
    }
    
    $registrationId = (int)$data['registration_id'];
    $action = $data['action'] ?? 'approve'; // 'approve' or 'reject'
    $adminNotes = $data['notes'] ?? '';
    
    // Database connection
    $host = 'localhost';
    $dbname = 'mlxysf_student_portal';
    $username = 'mlxysf_student_portal';
    $password = 'YAjv86kdSAPpw';
    
    $conn = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $conn->beginTransaction();
    
    // Get registration details
    $stmt = $conn->prepare("SELECT id, registration_number, name_en, payment_status FROM registrations WHERE id = ?");
    $stmt->execute([$registrationId]);
    $registration = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registration) {
        throw new Exception('Registration not found');
    }
    
    if ($registration['payment_status'] !== 'pending') {
        throw new Exception('Registration is not pending approval');
    }
    
    if ($action === 'approve') {
        // 1. Update registration status to approved
        $stmt = $conn->prepare("
            UPDATE registrations 
            SET payment_status = 'approved',
                admin_notes = ?,
                approved_at = NOW(),
                approved_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$adminNotes, $_SESSION['admin_username'] ?? 'admin', $registrationId]);
        
        // 2. Auto-approve the linked invoice
        $stmt = $conn->prepare("
            UPDATE invoices 
            SET status = 'paid',
                paid_date = NOW(),
                updated_at = NOW()
            WHERE registration_id = ? AND status = 'pending'
        ");
        $stmt->execute([$registrationId]);
        $invoiceUpdated = $stmt->rowCount();
        
        $conn->commit();
        
        error_log("[Approve] Registration #{$registration['registration_number']} approved. Invoice auto-approved: " . ($invoiceUpdated > 0 ? 'YES' : 'NO'));
        
        echo json_encode([
            'success' => true,
            'message' => 'Registration approved successfully',
            'registration_number' => $registration['registration_number'],
            'student_name' => $registration['name_en'],
            'invoice_approved' => $invoiceUpdated > 0
        ]);
        
    } else if ($action === 'reject') {
        // 1. Update registration status to rejected
        $stmt = $conn->prepare("
            UPDATE registrations 
            SET payment_status = 'rejected',
                admin_notes = ?,
                reviewed_at = NOW(),
                reviewed_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$adminNotes, $_SESSION['admin_username'] ?? 'admin', $registrationId]);
        
        // 2. Update linked invoice to rejected/cancelled
        $stmt = $conn->prepare("
            UPDATE invoices 
            SET status = 'cancelled',
                updated_at = NOW()
            WHERE registration_id = ? AND status = 'pending'
        ");
        $stmt->execute([$registrationId]);
        $invoiceUpdated = $stmt->rowCount();
        
        $conn->commit();
        
        error_log("[Reject] Registration #{$registration['registration_number']} rejected. Invoice cancelled: " . ($invoiceUpdated > 0 ? 'YES' : 'NO'));
        
        echo json_encode([
            'success' => true,
            'message' => 'Registration rejected',
            'registration_number' => $registration['registration_number'],
            'student_name' => $registration['name_en'],
            'invoice_cancelled' => $invoiceUpdated > 0
        ]);
        
    } else {
        throw new Exception('Invalid action');
    }
    
} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('DB error in approve_registration.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Error in approve_registration.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
