<?php
session_start();
require_once 'config.php';

// Log all POST requests to a file for debugging
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $log_entry = "\n=== " . date('Y-m-d H:i:s') . " ===\n";
    $log_entry .= "Action: " . ($_POST['action'] ?? 'none') . "\n";
    $log_entry .= "POST Data: " . print_r($_POST, true) . "\n";
    file_put_contents(__DIR__ . '/attendance_log.txt', $log_entry, FILE_APPEND);
}

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Handle GET requests for data fetching
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    $action = $_GET['action'];
    
    if ($action === 'get_student_details') {
        if (!isAdminLoggedIn()) {
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        $student_id = $_GET['student_id'] ?? 0;
        
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    e.id, e.student_id, e.class_id, e.enrollment_date, e.status,
                    c.class_name, c.class_code, c.monthly_fee, c.description
                FROM enrollments e
                JOIN classes c ON e.class_id = c.id
                WHERE e.student_id = ? AND e.status = 'active'
                ORDER BY e.enrollment_date DESC
            ");
            $stmt->execute([$student_id]);
            $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['enrollments' => $enrollments]);
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// Check admin login for POST requests
if (!isAdminLoggedIn()) {
    header('Location: admin.php?page=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php');
    exit;
}

$action = $_POST['action'] ?? '';

// ============ REGISTRATION APPROVAL (MINIMAL COLUMNS) ============

if ($action === 'verify_registration') {
    $regId = $_POST['registration_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Get registration details including student_account_id
        $stmt = $pdo->prepare("SELECT id, registration_number, name_en, payment_status, student_account_id FROM registrations WHERE id = ?");
        $stmt->execute([$regId]);
        $registration = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$registration) {
            throw new Exception('Registration not found');
        }
        
        if ($registration['payment_status'] !== 'pending') {
            throw new Exception('Registration is not pending approval');
        }
        
        $studentAccountId = $registration['student_account_id'];
        $adminId = $_SESSION['admin_id'];
        
        // 1. Update registration status to approved
        $stmt = $pdo->prepare("UPDATE registrations SET payment_status = 'approved' WHERE id = ?");
        $stmt->execute([$regId]);
        
        // 2. Auto-approve the linked registration fee invoice
        $stmt = $pdo->prepare("
            UPDATE invoices 
            SET status = 'paid',
                paid_date = NOW()
            WHERE student_id = ? 
              AND invoice_type = 'registration' 
              AND status = 'pending'
        ");
        $stmt->execute([$studentAccountId]);
        $invoicesUpdated = $stmt->rowCount();
        
        // 3. Auto-verify linked payment records with proper status
        $paymentsUpdated = 0;
        if ($invoicesUpdated > 0) {
            // Get the invoice ID that was just approved
            $stmt = $pdo->prepare("
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
                // Verify the payment - set to 'verified' status with verified_by and verified_date
                $stmt = $pdo->prepare("
                    UPDATE payments 
                    SET verification_status = 'verified',
                        verified_by = ?,
                        verified_date = NOW(),
                        admin_notes = 'Auto-verified with registration approval'
                    WHERE invoice_id = ? 
                      AND verification_status = 'pending'
                ");
                $stmt->execute([$adminId, $invoice['id']]);
                $paymentsUpdated = $stmt->rowCount();
            }
        }
        
        $pdo->commit();
        
        $message = "Registration {$registration['registration_number']} approved successfully!";
        if ($invoicesUpdated > 0) {
            $message .= " Invoice marked as PAID.";
        }
        if ($paymentsUpdated > 0) {
            $message .= " Payment verified.";
        }
        
        error_log("[Approve] Reg#{$registration['registration_number']}: invoices=$invoicesUpdated, payments=$paymentsUpdated");
        $_SESSION['success'] = $message;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("[verify_registration] Error: " . $e->getMessage());
        $_SESSION['error'] = "Error approving registration: " . $e->getMessage();
    }
    
    header('Location: admin.php?page=registrations');
    exit;
}

if ($action === 'reject_registration') {
    $regId = $_POST['registration_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Get registration details
        $stmt = $pdo->prepare("SELECT id, registration_number, name_en, payment_status, student_account_id FROM registrations WHERE id = ?");
        $stmt->execute([$regId]);
        $registration = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$registration) {
            throw new Exception('Registration not found');
        }
        
        if ($registration['payment_status'] !== 'pending') {
            throw new Exception('Registration is not pending approval');
        }
        
        $studentAccountId = $registration['student_account_id'];
        
        // 1. Update registration status to rejected
        $stmt = $pdo->prepare("UPDATE registrations SET payment_status = 'rejected' WHERE id = ?");
        $stmt->execute([$regId]);
        
        // 2. Cancel linked invoice
        $stmt = $pdo->prepare("
            UPDATE invoices 
            SET status = 'cancelled'
            WHERE student_id = ? 
              AND invoice_type = 'registration' 
              AND status = 'pending'
        ");
        $stmt->execute([$studentAccountId]);
        $invoicesUpdated = $stmt->rowCount();
        
        // 3. Reject linked payment records (ONLY verification_status and admin_notes)
        $paymentsUpdated = 0;
        if ($invoicesUpdated > 0) {
            $stmt = $pdo->prepare("
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
                $stmt = $pdo->prepare("
                    UPDATE payments 
                    SET verification_status = 'rejected',
                        admin_notes = 'Rejected with registration'
                    WHERE invoice_id = ? 
                      AND verification_status = 'pending'
                ");
                $stmt->execute([$invoice['id']]);
                $paymentsUpdated = $stmt->rowCount();
            }
        }
        
        $pdo->commit();
        
        $message = "Registration {$registration['registration_number']} rejected.";
        if ($invoicesUpdated > 0) {
            $message .= " Invoice cancelled.";
        }
        if ($paymentsUpdated > 0) {
            $message .= " Payment rejected.";
        }
        
        error_log("[Reject] Reg#{$registration['registration_number']}: invoices=$invoicesUpdated, payments=$paymentsUpdated");
        $_SESSION['success'] = $message;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("[reject_registration] Error: " . $e->getMessage());
        $_SESSION['error'] = "Error rejecting registration: " . $e->getMessage();
    }
    
    header('Location: admin.php?page=registrations');
    exit;
}