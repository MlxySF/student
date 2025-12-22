<?php
/**
 * approve_registration.php
 * Approves a registration and automatically approves the linked invoice and payment
 * Updates registrations, invoices, and payments tables
 * FIXED: Find invoice by student_id (from student_account_id column)
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
    $dbname = 'wushuspo_portal';
    $username = 'wushuspo_admin';
    $password = '%==l;7tS*.OjXd**';
    
    $conn = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $conn->beginTransaction();
    
    // Get registration details including student_account_id
    $stmt = $conn->prepare("SELECT id, registration_number, name_en, payment_status, student_account_id FROM registrations WHERE id = ?");
    $stmt->execute([$registrationId]);
    $registration = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registration) {
        throw new Exception('Registration not found');
    }
    
    if ($registration['payment_status'] !== 'pending') {
        throw new Exception('Registration is not pending approval');
    }
    
    $studentAccountId = $registration['student_account_id'];
    
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
        
        // 2. Auto-approve the linked registration fee invoice (by student_id and type)
        $stmt = $conn->prepare("
            UPDATE invoices 
            SET status = 'paid',
                paid_date = NOW(),
                updated_at = NOW()
            WHERE student_id = ? 
              AND invoice_type = 'registration' 
              AND status = 'pending'
        ");
        $stmt->execute([$studentAccountId]);
        $invoicesUpdated = $stmt->rowCount();
        
        // 3. Auto-approve linked payment records (payment with same invoice_id)
        if ($invoicesUpdated > 0) {
            // Get the invoice ID that was just approved
            $stmt = $conn->prepare("
                SELECT id FROM invoices 
                WHERE student_id = ? 
                  AND invoice_type = 'registration' 
                  AND status = 'paid'
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$studentAccountId]);
            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($invoice) {
                // Approve the payment linked to this invoice
                $stmt = $conn->prepare("
                    UPDATE payments 
                    SET verification_status = 'approved',
                        verified_at = NOW(),
                        verified_by = ?,
                        admin_notes = 'Auto-approved with registration'
                    WHERE invoice_id = ? 
                      AND verification_status = 'pending'
                ");
                $stmt->execute([$_SESSION['admin_username'] ?? 'admin', $invoice['id']]);
                $paymentsUpdated = $stmt->rowCount();
                
                error_log("[Approve] Registration #{$registration['registration_number']} approved. Invoice ID {$invoice['id']} marked PAID, Payment approved: " . ($paymentsUpdated > 0 ? 'YES' : 'NO'));
            }
        }
        
        $conn->commit();
        
        error_log("[Approve] Registration #{$registration['registration_number']} approved. Invoices updated: {$invoicesUpdated}, Student ID: {$studentAccountId}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Registration approved successfully',
            'registration_number' => $registration['registration_number'],
            'student_name' => $registration['name_en'],
            'invoice_approved' => $invoicesUpdated > 0,
            'payment_approved' => isset($paymentsUpdated) && $paymentsUpdated > 0
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
            WHERE student_id = ? 
              AND invoice_type = 'registration' 
              AND status = 'pending'
        ");
        $stmt->execute([$studentAccountId]);
        $invoicesUpdated = $stmt->rowCount();
        
        // 3. Reject linked payment records
        if ($invoicesUpdated > 0) {
            $stmt = $conn->prepare("
                SELECT id FROM invoices 
                WHERE student_id = ? 
                  AND invoice_type = 'registration' 
                  AND status = 'cancelled'
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$studentAccountId]);
            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($invoice) {
                $stmt = $conn->prepare("
                    UPDATE payments 
                    SET verification_status = 'rejected',
                        verified_at = NOW(),
                        verified_by = ?,
                        admin_notes = CONCAT('Rejected with registration: ', ?)
                    WHERE invoice_id = ? 
                      AND verification_status = 'pending'
                ");
                $stmt->execute([
                    $_SESSION['admin_username'] ?? 'admin',
                    $adminNotes,
                    $invoice['id']
                ]);
                $paymentsUpdated = $stmt->rowCount();
            }
        }
        
        $conn->commit();
        
        error_log("[Reject] Registration #{$registration['registration_number']} rejected. Invoice cancelled: {$invoicesUpdated}, Student ID: {$studentAccountId}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Registration rejected',
            'registration_number' => $registration['registration_number'],
            'student_name' => $registration['name_en'],
            'invoice_cancelled' => $invoicesUpdated > 0,
            'payment_rejected' => isset($paymentsUpdated) && $paymentsUpdated > 0
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
