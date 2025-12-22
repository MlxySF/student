<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');
session_start();
require_once 'config.php';
require_once 'send_approval_email.php'; // Include email function
require_once 'send_rejection_email.php'; // Include rejection email function
require_once 'send_payment_approval_email.php'; // ✨ MOVED TO TOP - Include payment approval email
require_once 'send_invoice_notification.php'; // ✨ NEW: Include invoice notification email


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

// ============ REGISTRATION APPROVAL ============

if ($action === 'verify_registration') {
    $regId = $_POST['registration_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Get registration details including parent IC to generate parent password
        $stmt = $pdo->prepare("
            SELECT 
                r.id, r.registration_number, r.name_en, r.email, r.student_status,
                r.payment_status, r.student_account_id, r.parent_account_id,
                r.is_additional_child,
                r.parent_ic,
                pa.ic_number as parent_ic_from_account
            FROM registrations r
            LEFT JOIN parent_accounts pa ON r.parent_account_id = pa.id
            WHERE r.id = ?
        ");
        $stmt->execute([$regId]);
        $registration = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$registration) {
            throw new Exception('Registration not found');
        }
        
        if ($registration['payment_status'] !== 'pending') {
            throw new Exception('Registration is not pending approval');
        }
        
        $studentAccountId = $registration['student_account_id'];
        $parentAccountId = $registration['parent_account_id'];
        $adminId = $_SESSION['admin_id'] ?? null;
        
        // Generate PARENT password from PARENT IC (last 4 digits)
        $parentIC = $registration['parent_ic'] ?? $registration['parent_ic_from_account'];
        $parentPlainPassword = null;
        
        if (!empty($parentIC)) {
            // Remove dashes and get last 4 digits
            $parentIcClean = str_replace('-', '', $parentIC);
            $parentPlainPassword = substr($parentIcClean, -4);
            error_log("[verify_registration] Generated PARENT password from parent IC: {$parentPlainPassword}");
        } else {
            error_log("[verify_registration] WARNING: Parent IC not found!");
        }
        
        // 1. Update registration status to approved
        $stmt = $pdo->prepare("UPDATE registrations SET payment_status = 'approved' WHERE id = ?");
        $stmt->execute([$regId]);
        
        // 2. Auto-approve ALL linked registration fee invoices (not just one)
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
        
        error_log("[verify_registration] Updated {$invoicesUpdated} invoices to paid");
        
        // 3. Auto-verify ALL linked payment records (FIXED: not just one)
        $paymentsUpdated = 0;
        if ($invoicesUpdated > 0) {
            // Get ALL invoice IDs that were just approved (not just one)
            $stmt = $pdo->prepare("
                SELECT id FROM invoices 
                WHERE student_id = ? 
                  AND invoice_type = 'registration' 
                  AND status = 'paid'
                ORDER BY created_at DESC
            ");
            $stmt->execute([$studentAccountId]);
            $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("[verify_registration] Found " . count($invoices) . " paid invoices");
            
            // Update payments for ALL invoices (not just the first one)
            foreach ($invoices as $invoice) {
                $stmt = $pdo->prepare("
                    UPDATE payments 
                    SET verification_status = 'verified',
                        verified_date = NOW(),
                        verified_by = ?,
                        admin_notes = 'Auto-verified with registration approval'
                    WHERE invoice_id = ? 
                      AND verification_status = 'pending'
                ");
                $stmt->execute([$adminId, $invoice['id']]);
                $rowsAffected = $stmt->rowCount();
                $paymentsUpdated += $rowsAffected;
                
                error_log("[verify_registration] Updated {$rowsAffected} payments for invoice ID {$invoice['id']}");
            }
            
            error_log("[verify_registration] Total payments updated: {$paymentsUpdated}");
        }
        
        // 4. Check if this is the first child for this parent (to send password)
        $isFirstChild = ($registration['is_additional_child'] == 0);
        
        error_log("[verify_registration] IsFirstChild: " . ($isFirstChild ? 'YES' : 'NO') . ", Parent IC: {$parentIC}, PARENT Password: " . ($parentPlainPassword ?? 'NULL'));
        
        // Commit all database changes first
        $pdo->commit();
        
        // 5. Send approval email to parent
        $emailSent = false;
        try {
            $emailSent = sendApprovalEmail(
                $registration['email'],
                $registration['name_en'],
                $registration['registration_number'],
                $registration['student_status'],
                $parentPlainPassword,
                $isFirstChild
            );
        } catch (Exception $e) {
            error_log("[verify_registration] Email sending failed: " . $e->getMessage());
        }
        
        // Build success message
        $message = "Registration {$registration['registration_number']} approved successfully!";
        if ($invoicesUpdated > 0) {
            $message .= " {$invoicesUpdated} invoice(s) marked as PAID.";
        }
        if ($paymentsUpdated > 0) {
            $message .= " {$paymentsUpdated} payment(s) verified.";
        }
        if ($emailSent) {
            $message .= " Approval email sent to parent.";
        } else {
            $message .= " (Email notification failed - please contact parent manually)";
        }
        
        error_log("[Approve] Reg#{$registration['registration_number']}: invoices={$invoicesUpdated}, payments={$paymentsUpdated}, email=" . ($emailSent ? 'sent' : 'failed'));
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
    $rejectReason = $_POST['reject_reason'] ?? ''; // Optional admin notes
    
    try {
        $pdo->beginTransaction();
        
        // Get registration details INCLUDING EMAIL for notification
        $stmt = $pdo->prepare("SELECT id, registration_number, name_en, email, payment_status, student_account_id FROM registrations WHERE id = ?");
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
        
        // 2. Cancel ALL linked invoices (not just one)
        $stmt = $pdo->prepare("
            UPDATE invoices 
            SET status = 'cancelled'
            WHERE student_id = ? 
              AND invoice_type = 'registration' 
              AND status = 'pending'
        ");
        $stmt->execute([$studentAccountId]);
        $invoicesUpdated = $stmt->rowCount();
        
        error_log("[reject_registration] Cancelled {$invoicesUpdated} invoices");
        
        // 3. Reject ALL linked payment records (FIXED: not just one)
        $paymentsUpdated = 0;
        if ($invoicesUpdated > 0) {
            // Get ALL cancelled invoice IDs
            $stmt = $pdo->prepare("
                SELECT id FROM invoices 
                WHERE student_id = ? 
                  AND invoice_type = 'registration' 
                  AND status = 'cancelled'
                ORDER BY created_at DESC
            ");
            $stmt->execute([$studentAccountId]);
            $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("[reject_registration] Found " . count($invoices) . " cancelled invoices");
            
            // Reject payments for ALL invoices
            foreach ($invoices as $invoice) {
                $adminNotes = !empty($rejectReason) ? $rejectReason : 'Rejected with registration';
                $stmt = $pdo->prepare("
                    UPDATE payments 
                    SET verification_status = 'rejected',
                        verified_date = NOW(),
                        admin_notes = ?
                    WHERE invoice_id = ? 
                      AND verification_status = 'pending'
                ");
                $stmt->execute([$adminNotes, $invoice['id']]);
                $rowsAffected = $stmt->rowCount();
                $paymentsUpdated += $rowsAffected;
                
                error_log("[reject_registration] Rejected {$rowsAffected} payments for invoice ID {$invoice['id']}");
            }
            
            error_log("[reject_registration] Total payments rejected: {$paymentsUpdated}");
        }
        
        // Commit all database changes
        $pdo->commit();
        
        // 4. Send rejection email to parent
        $emailSent = false;
        try {
            $emailSent = sendRejectionEmail(
                $registration['email'],
                $registration['name_en'],
                $registration['registration_number'],
                $rejectReason
            );
        } catch (Exception $e) {
            error_log("[reject_registration] Email sending failed: " . $e->getMessage());
        }
        
        // Build success message
        $message = "Registration {$registration['registration_number']} rejected.";
        if ($invoicesUpdated > 0) {
            $message .= " {$invoicesUpdated} invoice(s) cancelled.";
        }
        if ($paymentsUpdated > 0) {
            $message .= " {$paymentsUpdated} payment(s) rejected.";
        }
        if ($emailSent) {
            $message .= " Rejection notification sent to parent.";
        } else {
            $message .= " (Email notification failed - please contact parent manually)";
        }
        
        error_log("[Reject] Reg#{$registration['registration_number']}: invoices={$invoicesUpdated}, payments={$paymentsUpdated}, email=" . ($emailSent ? 'sent' : 'failed'));
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

// ✨ NEW: Re-Approve Rejected Registration
if ($action === 'reapprove_registration') {
    $regId = $_POST['registration_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Get registration details
        $stmt = $pdo->prepare("
            SELECT 
                r.id, r.registration_number, r.name_en, r.email, r.student_status,
                r.payment_status, r.student_account_id, r.parent_account_id,
                r.is_additional_child,
                r.parent_ic,
                pa.ic_number as parent_ic_from_account
            FROM registrations r
            LEFT JOIN parent_accounts pa ON r.parent_account_id = pa.id
            WHERE r.id = ?
        ");
        $stmt->execute([$regId]);
        $registration = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$registration) {
            throw new Exception('Registration not found');
        }
        
        if ($registration['payment_status'] !== 'rejected') {
            throw new Exception('Registration is not rejected - cannot re-approve');
        }
        
        $studentAccountId = $registration['student_account_id'];
        $parentAccountId = $registration['parent_account_id'];
        $adminId = $_SESSION['admin_id'] ?? null;
        
        // Generate PARENT password from PARENT IC (last 4 digits)
        $parentIC = $registration['parent_ic'] ?? $registration['parent_ic_from_account'];
        $parentPlainPassword = null;
        
        if (!empty($parentIC)) {
            $parentIcClean = str_replace('-', '', $parentIC);
            $parentPlainPassword = substr($parentIcClean, -4);
            error_log("[reapprove_registration] Generated PARENT password from parent IC: {$parentPlainPassword}");
        } else {
            error_log("[reapprove_registration] WARNING: Parent IC not found!");
        }
        
        // 1. Update registration status from rejected to approved
        $stmt = $pdo->prepare("UPDATE registrations SET payment_status = 'approved' WHERE id = ?");
        $stmt->execute([$regId]);
        
        // 2. Reactivate ALL cancelled invoices (FIXED: not just one)
        $stmt = $pdo->prepare("
            UPDATE invoices 
            SET status = 'paid',
                paid_date = NOW()
            WHERE student_id = ? 
              AND invoice_type = 'registration' 
              AND status = 'cancelled'
        ");
        $stmt->execute([$studentAccountId]);
        $invoicesUpdated = $stmt->rowCount();
        
        error_log("[reapprove_registration] Reactivated {$invoicesUpdated} invoices");
        
        // 3. Verify ALL rejected payment records (FIXED: not just one)
        $paymentsUpdated = 0;
        if ($invoicesUpdated > 0) {
            // Get ALL reactivated invoice IDs
            $stmt = $pdo->prepare("
                SELECT id FROM invoices 
                WHERE student_id = ? 
                  AND invoice_type = 'registration' 
                  AND status = 'paid'
                ORDER BY created_at DESC
            ");
            $stmt->execute([$studentAccountId]);
            $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("[reapprove_registration] Found " . count($invoices) . " paid invoices");
            
            // Verify payments for ALL invoices
            foreach ($invoices as $invoice) {
                $stmt = $pdo->prepare("
                    UPDATE payments 
                    SET verification_status = 'verified',
                        verified_date = NOW(),
                        verified_by = ?,
                        admin_notes = 'Re-approved after initial rejection'
                    WHERE invoice_id = ? 
                      AND verification_status = 'rejected'
                ");
                $stmt->execute([$adminId, $invoice['id']]);
                $rowsAffected = $stmt->rowCount();
                $paymentsUpdated += $rowsAffected;
                
                error_log("[reapprove_registration] Verified {$rowsAffected} payments for invoice ID {$invoice['id']}");
            }
            
            error_log("[reapprove_registration] Total payments verified: {$paymentsUpdated}");
        }
        
        // 4. Check if this is the first child
        $isFirstChild = ($registration['is_additional_child'] == 0);
        
        error_log("[reapprove_registration] IsFirstChild: " . ($isFirstChild ? 'YES' : 'NO') . ", Parent IC: {$parentIC}, PARENT Password: " . ($parentPlainPassword ?? 'NULL'));
        
        // Commit all database changes
        $pdo->commit();
        
        // 5. Send approval email to parent (same as regular approval)
        $emailSent = false;
        try {
            $emailSent = sendApprovalEmail(
                $registration['email'],
                $registration['name_en'],
                $registration['registration_number'],
                $registration['student_status'],
                $parentPlainPassword,
                $isFirstChild
            );
        } catch (Exception $e) {
            error_log("[reapprove_registration] Email sending failed: " . $e->getMessage());
        }
        
        // Build success message
        $message = "Registration {$registration['registration_number']} re-approved successfully!";
        if ($invoicesUpdated > 0) {
            $message .= " {$invoicesUpdated} invoice(s) reactivated and marked as PAID.";
        }
        if ($paymentsUpdated > 0) {
            $message .= " {$paymentsUpdated} payment(s) verified.";
        }
        if ($emailSent) {
            $message .= " Approval email sent to parent.";
        } else {
            $message .= " (Email notification failed - please contact parent manually)";
        }
        
        error_log("[Re-Approve] Reg#{$registration['registration_number']}: invoices={$invoicesUpdated}, payments={$paymentsUpdated}, email=" . ($emailSent ? 'sent' : 'failed'));
        $_SESSION['success'] = $message;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("[reapprove_registration] Error: " . $e->getMessage());
        $_SESSION['error'] = "Error re-approving registration: " . $e->getMessage();
    }
    
    header('Location: admin.php?page=registrations');
    exit;
}

if ($action === 'delete_registration') {
    $regId = $_POST['registration_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Get comprehensive registration details
        $checkStmt = $pdo->prepare("
            SELECT 
                r.id, 
                r.registration_number, 
                r.name_en,
                r.student_account_id, 
                r.parent_account_id,
                r.email,
                r.signature_path,
                r.pdf_path,
                r.payment_receipt_path,
                s.parent_account_id as student_parent_id,
                s.full_name as student_full_name
            FROM registrations r
            LEFT JOIN students s ON r.student_account_id = s.id
            WHERE r.id = ?
        ");
        $checkStmt->execute([$regId]);
        $registration = $checkStmt->fetch();
        
        if (!$registration) {
            throw new Exception("Registration not found (ID: $regId)");
        }
        
        $regNumber = $registration['registration_number'];
        $studentName = $registration['name_en'] ?? $registration['student_full_name'] ?? 'Unknown';
        $studentAccountId = $registration['student_account_id'];
        $parentAccountId = $registration['parent_account_id'] ?? $registration['student_parent_id'];
        $email = $registration['email'];
        
        error_log("=== DELETE REGISTRATION STARTED ===");
        error_log("[Delete Registration] Reg#: {$regNumber}, Student: {$studentName}, Student ID: {$studentAccountId}, Parent ID: {$parentAccountId}");
        
        // Collect file paths for deletion
        $filesToDelete = [];
        foreach (['signature_path', 'pdf_path', 'payment_receipt_path'] as $field) {
            if (!empty($registration[$field])) {
                $path = $registration[$field];
                // Ensure uploads/ prefix
                if (strpos($path, 'uploads/') !== 0) {
                    $path = 'uploads/' . $path;
                }
                $filesToDelete[] = $path;
            }
        }
        
        error_log("[Delete Registration] Files to delete: " . count($filesToDelete) . " - " . implode(', ', $filesToDelete));
        
        // Step 1: Delete the registration record
        $stmt = $pdo->prepare("DELETE FROM registrations WHERE id = ?");
        $stmt->execute([$regId]);
        error_log("[Delete Registration] ✅ Registration record deleted");
        
        // Step 2: Delete associated student account and related data
        if ($studentAccountId) {
            // Delete in proper order to avoid foreign key constraints
            
            // 2a. Delete parent-child relationships
            $stmt = $pdo->prepare("DELETE FROM parent_child_relationships WHERE student_id = ?");
            $stmt->execute([$studentAccountId]);
            $relCount = $stmt->rowCount();
            error_log("[Delete Registration] ✅ Deleted {$relCount} parent-child relationship(s)");
            
            // 2b. Delete payments (must be before invoices due to foreign key)
            $stmt = $pdo->prepare("DELETE FROM payments WHERE student_id = ?");
            $stmt->execute([$studentAccountId]);
            $paymentCount = $stmt->rowCount();
            error_log("[Delete Registration] ✅ Deleted {$paymentCount} payment record(s)");
            
            // 2c. Delete invoices
            $stmt = $pdo->prepare("DELETE FROM invoices WHERE student_id = ?");
            $stmt->execute([$studentAccountId]);
            $invoiceCount = $stmt->rowCount();
            error_log("[Delete Registration] ✅ Deleted {$invoiceCount} invoice(s)");
            
            // 2d. Delete attendance records
            $stmt = $pdo->prepare("DELETE FROM attendance WHERE student_id = ?");
            $stmt->execute([$studentAccountId]);
            $attendanceCount = $stmt->rowCount();
            error_log("[Delete Registration] ✅ Deleted {$attendanceCount} attendance record(s)");
            
            // 2e. Delete enrollments
            $stmt = $pdo->prepare("DELETE FROM enrollments WHERE student_id = ?");
            $stmt->execute([$studentAccountId]);
            $enrollmentCount = $stmt->rowCount();
            error_log("[Delete Registration] ✅ Deleted {$enrollmentCount} enrollment(s)");
            
            // 2f. Finally delete the student account
            $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$studentAccountId]);
            error_log("[Delete Registration] ✅ Student account deleted (ID: {$studentAccountId})");
        }
        
        // Step 3: Check if parent account should be deleted
        $parentDeleted = false;
        $parentInfo = null;
        $remainingChildren = 0;
        
        if ($parentAccountId) {
            // Get parent info before checking children
            $stmt = $pdo->prepare("SELECT parent_id, full_name, email FROM parent_accounts WHERE id = ?");
            $stmt->execute([$parentAccountId]);
            $parentInfo = $stmt->fetch();
            
            // Count remaining children for this parent
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as child_count 
                FROM students 
                WHERE parent_account_id = ?
            ");
            $stmt->execute([$parentAccountId]);
            $result = $stmt->fetch();
            $remainingChildren = (int)$result['child_count'];
            
            error_log("[Delete Registration] Parent '{$parentInfo['full_name']}' (ID: {$parentAccountId}) has {$remainingChildren} remaining child(ren)");
            
            if ($remainingChildren === 0) {
                // No more children - DELETE the parent account
                
                // Delete any remaining relationships (should be none, but for safety)
                $stmt = $pdo->prepare("DELETE FROM parent_child_relationships WHERE parent_id = ?");
                $stmt->execute([$parentAccountId]);
                
                // Delete the parent account
                $stmt = $pdo->prepare("DELETE FROM parent_accounts WHERE id = ?");
                $stmt->execute([$parentAccountId]);
                
                $parentDeleted = true;
                error_log("[Delete Registration] ✅ Parent account DELETED: {$parentInfo['parent_id']} ({$parentInfo['full_name']})");
            } else {
                error_log("[Delete Registration] ℹ️ Parent account RETAINED: {$parentInfo['parent_id']} ({$parentInfo['full_name']}) - {$remainingChildren} child(ren) remain");
            }
        }
        
        // Step 4: Commit database changes
        $pdo->commit();
        error_log("[Delete Registration] ✅ Database transaction committed");
        
        // Step 5: Delete files from filesystem
        $filesDeleted = 0;
        $filesNotFound = 0;
        $filesFailed = 0;
        
        foreach ($filesToDelete as $filePath) {
            $fullPath = __DIR__ . '/' . $filePath;
            
            if (file_exists($fullPath)) {
                if (unlink($fullPath)) {
                    $filesDeleted++;
                    error_log("[Delete Registration] ✅ File deleted: {$filePath}");
                } else {
                    $filesFailed++;
                    error_log("[Delete Registration] ❌ Failed to delete file: {$filePath}");
                }
            } else {
                $filesNotFound++;
                error_log("[Delete Registration] ⚠️ File not found: {$filePath}");
            }
        }
        
        error_log("=== DELETE REGISTRATION COMPLETED ===");
        
        // Build detailed success message
        $message = "Registration {$regNumber} for {$studentName} deleted successfully!";
        
        // Add student deletion info
        if ($studentAccountId) {
            $details = [];
            if (isset($enrollmentCount) && $enrollmentCount > 0) $details[] = "{$enrollmentCount} enrollment(s)";
            if (isset($attendanceCount) && $attendanceCount > 0) $details[] = "{$attendanceCount} attendance record(s)";
            if (isset($invoiceCount) && $invoiceCount > 0) $details[] = "{$invoiceCount} invoice(s)";
            if (isset($paymentCount) && $paymentCount > 0) $details[] = "{$paymentCount} payment(s)";
            
            if (!empty($details)) {
                $message .= " Removed: " . implode(", ", $details) . ".";
            }
        }
        
        // Add parent account status
        if ($parentDeleted && $parentInfo) {
            $message .= " Parent account '{$parentInfo['full_name']}' also deleted (no remaining children).";
        } elseif ($parentAccountId && $parentInfo) {
            $message .= " Parent account '{$parentInfo['full_name']}' retained ({$remainingChildren} other child(ren)).";
        }
        
        // Add file deletion info
        if ($filesDeleted > 0) {
            $message .= " {$filesDeleted} file(s) deleted from storage.";
        }
        if ($filesFailed > 0) {
            $message .= " Warning: {$filesFailed} file(s) could not be deleted.";
        }
        
        $_SESSION['success'] = $message;
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("[Delete Registration] ❌ Database error: " . $e->getMessage());
        error_log("[Delete Registration] Stack trace: " . $e->getTraceAsString());
        $_SESSION['error'] = "Database error while deleting registration: " . $e->getMessage();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("[Delete Registration] ❌ Error: " . $e->getMessage());
        error_log("[Delete Registration] Stack trace: " . $e->getTraceAsString());
        $_SESSION['error'] = "Error deleting registration: " . $e->getMessage();
    }
    
    header('Location: admin.php?page=registrations');
    exit;
}


// ============ STUDENT MANAGEMENT ============

if ($action === 'edit_student_registration') {
    $registration_id = $_POST['registration_id'];
    $name_en = trim($_POST['name_en']);
    $name_cn = trim($_POST['name_cn'] ?? '');
    $age = intval($_POST['age']);
    $school = trim($_POST['school']);
    $phone = trim($_POST['phone']);
    $ic = trim($_POST['ic'] ?? '');
    $student_status = $_POST['student_status'];

    try {
        $stmt = $pdo->prepare("UPDATE registrations SET name_en = ?, name_cn = ?, age = ?, school = ?, phone = ?, ic = ?, student_status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$name_en, $name_cn, $age, $school, $phone, $ic, $student_status, $registration_id]);
        $_SESSION['success'] = "Student updated successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to update student: " . $e->getMessage();
    }

    header('Location: admin.php?page=students');
    exit;
}

if ($action === 'create_student') {
    $student_id = 'STU' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO students (student_id, full_name, email, phone, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $full_name, $email, $phone, $password]);
        $_SESSION['success'] = "Student created! ID: $student_id";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Failed to create student.";
    }
    header('Location: admin.php?page=students');
    exit;
}

if ($action === 'edit_student') {
    $id = $_POST['student_id'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $student_status = $_POST['student_status'] ?? 'Student';

    $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);

    if ($stmt->fetch()) {
        $_SESSION['error'] = "Email is already used by another student.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE students SET full_name = ?, email = ?, phone = ?, student_status = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $phone, $student_status, $id]);
            $_SESSION['success'] = "Student updated successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to update student: " . $e->getMessage();
        }
    }

    header('Location: admin.php?page=students');
    exit;
}

if ($action === 'delete_student') {
    $id = $_POST['student_id'];
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success'] = "Student deleted!";
    header('Location: admin.php?page=students');
    exit;
}

if ($action === 'enroll_student') {
    $student_id = $_POST['student_id'];
    $class_id = $_POST['class_id'];

    try {
        $checkActive = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND class_id = ? AND status = 'active'");
        $checkActive->execute([$student_id, $class_id]);
        
        if ($checkActive->fetch()) {
            $_SESSION['error'] = "Student is already enrolled in this class.";
        } else {
            $checkInactive = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND class_id = ? AND status = 'inactive'");
            $checkInactive->execute([$student_id, $class_id]);
            $inactiveEnrollment = $checkInactive->fetch();
            
            if ($inactiveEnrollment) {
                $stmt = $pdo->prepare("UPDATE enrollments SET status = 'active', enrollment_date = NOW() WHERE id = ?");
                $stmt->execute([$inactiveEnrollment['id']]);
                $_SESSION['success'] = "Student re-enrolled successfully!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, class_id, enrollment_date, status) VALUES (?, ?, NOW(), 'active')");
                $stmt->execute([$student_id, $class_id]);
                $_SESSION['success'] = "Student enrolled successfully!";
            }
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Enrollment failed: " . $e->getMessage();
    }
    header('Location: admin.php?page=students');
    exit;
}

if ($action === 'unenroll_student') {
    $enrollment_id = $_POST['enrollment_id'];

    try {
        $stmt = $pdo->prepare("UPDATE enrollments SET status = 'inactive' WHERE id = ?");
        $stmt->execute([$enrollment_id]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Student unenrolled successfully!";
        } else {
            $_SESSION['error'] = "Enrollment not found or already inactive.";
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Unenrollment failed: " . $e->getMessage();
    }
    header('Location: admin.php?page=students');
    exit;
}

// ============ CLASS MANAGEMENT ============

if ($action === 'create_class') {
    $class_code = strtoupper(trim($_POST['class_code']));
    $class_name = trim($_POST['class_name']);
    $monthly_fee = floatval($_POST['monthly_fee']);
    $description = trim($_POST['description'] ?? '');
    $day_of_week = trim($_POST['day_of_week'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');

    try {
        // Check if class code already EXISTS (if found, it's duplicate)
        $checkStmt = $pdo->prepare("SELECT id FROM classes WHERE class_code = ?");
        $checkStmt->execute([$class_code]);
        
        if ($checkStmt->fetch()) {
            // Class code FOUND - it's a duplicate
            $_SESSION['error'] = "Class code '{$class_code}' already exists. Please use a different code.";
        } else {
            // Class code NOT found - safe to create
            $stmt = $pdo->prepare("INSERT INTO classes (class_code, class_name, monthly_fee, description, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$class_code, $class_name, $monthly_fee, $description, $day_of_week, $start_time, $end_time]);
            $_SESSION['success'] = "Class '{$class_name}' created successfully!";
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Failed to create class: " . $e->getMessage();
    }
    header('Location: admin.php?page=classes');
    exit;
}

if ($action === 'edit_class') {
    $id = intval($_POST['class_id']);
    $class_code = strtoupper(trim($_POST['class_code']));
    $class_name = trim($_POST['class_name']);
    $monthly_fee = floatval($_POST['monthly_fee']);
    $description = trim($_POST['description'] ?? '');
    $day_of_week = trim($_POST['day_of_week'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');

    try {
        // Check if class code is used by another class
        $checkStmt = $pdo->prepare("SELECT id FROM classes WHERE class_code = ? AND id != ?");
        $checkStmt->execute([$class_code, $id]);
        
        if ($checkStmt->fetch()) {
            $_SESSION['error'] = "Class code '{$class_code}' is already used by another class.";
        } else {
            $stmt = $pdo->prepare("UPDATE classes SET class_code = ?, class_name = ?, monthly_fee = ?, description = ?, day_of_week = ?, start_time = ?, end_time = ? WHERE id = ?");
            $stmt->execute([$class_code, $class_name, $monthly_fee, $description, $day_of_week, $start_time, $end_time, $id]);
            $_SESSION['success'] = "Class '{$class_name}' updated successfully!";
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Failed to update class: " . $e->getMessage();
    }
    header('Location: admin.php?page=classes');
    exit;
}

if ($action === 'delete_class') {
    $id = $_POST['class_id'];
    $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success'] = "Class deleted!";
    header('Location: admin.php?page=classes');
    exit;
}

// ============ PAYMENT VERIFICATION ============

if ($action === 'verify_payment') {
    $payment_id = $_POST['payment_id'];
    $verification_status = $_POST['verification_status'];
    $admin_notes = $_POST['admin_notes'] ?? '';
    $invoice_id = $_POST['invoice_id'] ?? null;

    // ✨ DEBUG: Log the verification attempt
    error_log("========================================");
    error_log("[verify_payment] HANDLER CALLED");
    error_log("[verify_payment] Payment ID: {$payment_id}");
    error_log("[verify_payment] Status: {$verification_status}");
    error_log("[verify_payment] Invoice ID: {$invoice_id}");
    error_log("[verify_payment] Admin Notes: {$admin_notes}");
    error_log("========================================");

    try {
        $pdo->beginTransaction();
        
        // Update payment status with admin info
        $stmt = $pdo->prepare("UPDATE payments SET verification_status = ?, admin_notes = ?, verified_date = NOW(), verified_by = ? WHERE id = ?");
        $stmt->execute([$verification_status, $admin_notes, $_SESSION['admin_id'] ?? null, $payment_id]);
        
        error_log("[verify_payment] Payment record updated in database");
        
        $emailSent = false;
        
        if ($verification_status === 'verified' && $invoice_id) {
            // Mark invoice as paid
            $stmt = $pdo->prepare("UPDATE invoices SET status = 'paid', paid_date = NOW() WHERE id = ?");
            $stmt->execute([$invoice_id]);
            
            $stmt = $pdo->prepare("SELECT invoice_number FROM invoices WHERE id = ?");
            $stmt->execute([$invoice_id]);
            $invoice = $stmt->fetch();
            
            error_log("[verify_payment] Invoice marked as paid: {$invoice['invoice_number']}");
            
            $pdo->commit();
            error_log("[verify_payment] Database transaction committed");
            
            // ✨ Send approval email with PDF receipt
            error_log("[verify_payment] Calling sendPaymentApprovalEmail...");
            try {
                $emailSent = sendPaymentApprovalEmail($payment_id, 'verified', $admin_notes);
                error_log("[verify_payment] sendPaymentApprovalEmail returned: " . ($emailSent ? 'true' : 'false'));
            } catch (Exception $e) {
                error_log("[verify_payment] Exception during email: " . $e->getMessage());
                error_log("[verify_payment] Stack trace: " . $e->getTraceAsString());
            }
            
            $message = "Payment verified! Invoice {$invoice['invoice_number']} marked as PAID.";
            if ($emailSent) {
                $message .= " Approval email with PDF receipt sent to parent.";
            } else {
                $message .= " (Email notification failed - please contact parent manually)";
            }
            $_SESSION['success'] = $message;
            
        } else if ($verification_status === 'rejected') {
            $pdo->commit();
            error_log("[verify_payment] Database transaction committed");
            
            // ✨ Send rejection email
            error_log("[verify_payment] Calling sendPaymentApprovalEmail for rejection...");
            try {
                $emailSent = sendPaymentApprovalEmail($payment_id, 'rejected', $admin_notes);
                error_log("[verify_payment] sendPaymentApprovalEmail returned: " . ($emailSent ? 'true' : 'false'));
            } catch (Exception $e) {
                error_log("[verify_payment] Exception during email: " . $e->getMessage());
                error_log("[verify_payment] Stack trace: " . $e->getTraceAsString());
            }
            
            $message = "Payment status updated to: Rejected";
            if ($emailSent) {
                $message .= " Rejection notification sent to parent.";
            } else {
                $message .= " (Email notification failed - please contact parent manually)";
            }
            $_SESSION['success'] = $message;
            
        } else {
            $pdo->commit();
            error_log("[verify_payment] Database transaction committed (no email sent)");
            $_SESSION['success'] = "Payment status updated to: " . ucfirst($verification_status);
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("[verify_payment] Exception in handler: " . $e->getMessage());
        error_log("[verify_payment] Stack trace: " . $e->getTraceAsString());
        $_SESSION['error'] = "Failed to verify payment: " . $e->getMessage();
    }
    
    error_log("[verify_payment] Redirecting to admin.php?page=payments");
    error_log("========================================");
    
    header('Location: admin.php?page=payments');
    exit;
}

// ============ ATTENDANCE ============

if ($action === 'mark_attendance') {
    $student_id = $_POST['student_id'];
    $class_id = $_POST['class_id'];
    $attendance_date = $_POST['attendance_date'];
    $status = $_POST['status'];
    $notes = $_POST['notes'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT id FROM attendance WHERE student_id = ? AND class_id = ? AND attendance_date = ?");
        $stmt->execute([$student_id, $class_id, $attendance_date]);

        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE attendance SET status = ?, notes = ? WHERE student_id = ? AND class_id = ? AND attendance_date = ?");
            $stmt->execute([$status, $notes, $student_id, $class_id, $attendance_date]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO attendance (student_id, class_id, attendance_date, status, notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$student_id, $class_id, $attendance_date, $status, $notes]);
        }
        $_SESSION['success'] = "Attendance marked!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to mark attendance: " . $e->getMessage();
    }
    
    header('Location: admin.php?page=attendance&class_id=' . $class_id . '&date=' . $attendance_date);
    exit;
}

if ($action === 'bulk_attendance') {
    file_put_contents(__DIR__ . '/attendance_log.txt', "\nBULK ATTENDANCE ACTION STARTED\n", FILE_APPEND);
    
    $class_id = $_POST['class_id'] ?? null;
    $attendance_date = $_POST['attendance_date'] ?? null;
    $attendance_data = $_POST['attendance'] ?? [];

    if (empty($attendance_data)) {
        $_SESSION['error'] = "No attendance data provided.";
        file_put_contents(__DIR__ . '/attendance_log.txt', "ERROR: No attendance data\n", FILE_APPEND);
        header('Location: admin.php?page=attendance&class_id=' . $class_id . '&date=' . $attendance_date);
        exit;
    }

    try {
        $pdo->beginTransaction();
        $marked_count = 0;
        
        foreach ($attendance_data as $student_id => $status) {
            file_put_contents(__DIR__ . '/attendance_log.txt', "Processing student $student_id with status $status\n", FILE_APPEND);
            
            // Validate status value
            $valid_statuses = ['present', 'absent', 'late', 'excused'];
            if (!in_array($status, $valid_statuses)) {
                file_put_contents(__DIR__ . '/attendance_log.txt', "Invalid status: $status\n", FILE_APPEND);
                continue;
            }
            
            // Check if attendance record exists
            $checkStmt = $pdo->prepare("SELECT id FROM attendance WHERE student_id = ? AND class_id = ? AND attendance_date = ?");
            $checkStmt->execute([$student_id, $class_id, $attendance_date]);
            $existing = $checkStmt->fetch();
            
            if ($existing) {
                // Update existing record
                $updateStmt = $pdo->prepare("UPDATE attendance SET status = ? WHERE student_id = ? AND class_id = ? AND attendance_date = ?");
                $updateStmt->execute([$status, $student_id, $class_id, $attendance_date]);
                file_put_contents(__DIR__ . '/attendance_log.txt', "Updated existing record\n", FILE_APPEND);
            } else {
                // Insert new record
                $insertStmt = $pdo->prepare("INSERT INTO attendance (student_id, class_id, attendance_date, status) VALUES (?, ?, ?, ?)");
                $insertStmt->execute([$student_id, $class_id, $attendance_date, $status]);
                file_put_contents(__DIR__ . '/attendance_log.txt', "Inserted new record\n", FILE_APPEND);
            }
            
            $marked_count++;
        }
        
        $pdo->commit();
        file_put_contents(__DIR__ . '/attendance_log.txt', "SUCCESS: Committed transaction, marked $marked_count students\n", FILE_APPEND);
        $_SESSION['success'] = "Attendance saved successfully! Marked {$marked_count} student(s).";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        file_put_contents(__DIR__ . '/attendance_log.txt', "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
        $_SESSION['error'] = "Failed to save attendance: " . $e->getMessage();
    }
    
    header('Location: admin.php?page=attendance&class_id=' . $class_id . '&date=' . $attendance_date);
    exit;
}

if ($action === 'delete_attendance_day') {
    $class_id = $_POST['class_id'] ?? null;
    $attendance_date = $_POST['attendance_date'] ?? null;

    if (!$class_id || !$attendance_date) {
        $_SESSION['error'] = "Invalid data for deletion.";
        header('Location: admin.php?page=attendance');
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM attendance WHERE class_id = ? AND attendance_date = ?");
        $stmt->execute([$class_id, $attendance_date]);
        
        $deleted_count = $stmt->rowCount();
        
        if ($deleted_count > 0) {
            $_SESSION['success'] = "Deleted {$deleted_count} attendance record(s) for " . date('F j, Y', strtotime($attendance_date));
        } else {
            $_SESSION['error'] = "No attendance records found for this date.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to delete attendance: " . $e->getMessage();
    }
    
    header('Location: admin.php?page=attendance&class_id=' . $class_id . '&date=' . $attendance_date);
    exit;
}

// Default redirect
header('Location: admin.php');
exit;