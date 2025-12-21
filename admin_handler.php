<?php
session_start();
require_once 'config.php';
require_once 'send_approval_email.php'; // Include email function
require_once 'send_rejection_email.php'; // Include rejection email function

// Check if this is an AJAX request (for bulk attendance)
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Log all POST requests to a file for debugging
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $log_entry = "\n=== " . date('Y-m-d H:i:s') . " ===\n";
    $log_entry .= "Action: " . ($_POST['action'] ?? 'none') . "\n";
    $log_entry .= "Is AJAX: " . ($isAjax ? 'YES' : 'NO') . "\n";
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
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    header('Location: admin.php?page=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php');
    exit;
}

$action = $_POST['action'] ?? '';

// ============ ATTENDANCE - BULK UPDATE WITH NOTES (AJAX) ============

if ($action === 'bulk_attendance') {
    file_put_contents(__DIR__ . '/attendance_log.txt', "\nBULK ATTENDANCE ACTION STARTED\n", FILE_APPEND);
    
    $class_id = $_POST['class_id'] ?? null;
    $attendance_date = $_POST['attendance_date'] ?? null;
    $attendance_data = $_POST['attendance'] ?? [];
    $notes_data = $_POST['notes'] ?? []; // NEW: Get notes from form

    if (empty($attendance_data)) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No attendance data provided']);
            exit;
        }
        $_SESSION['error'] = "No attendance data provided.";
        file_put_contents(__DIR__ . '/attendance_log.txt', "ERROR: No attendance data\n", FILE_APPEND);
        header('Location: admin.php?page=attendance&class_id=' . $class_id . '&date=' . $attendance_date);
        exit;
    }

    try {
        $pdo->beginTransaction();
        $marked_count = 0;
        $updated_count = 0;
        $inserted_count = 0;
        
        foreach ($attendance_data as $student_id => $status) {
            $notes = $notes_data[$student_id] ?? ''; // Get notes for this student
            
            file_put_contents(__DIR__ . '/attendance_log.txt', "Processing student $student_id with status $status and notes: $notes\n", FILE_APPEND);
            
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
                // Update existing record WITH NOTES
                $updateStmt = $pdo->prepare("UPDATE attendance SET status = ?, notes = ? WHERE student_id = ? AND class_id = ? AND attendance_date = ?");
                $updateStmt->execute([$status, $notes, $student_id, $class_id, $attendance_date]);
                file_put_contents(__DIR__ . '/attendance_log.txt', "Updated existing record\n", FILE_APPEND);
                $updated_count++;
            } else {
                // Insert new record WITH NOTES
                $insertStmt = $pdo->prepare("INSERT INTO attendance (student_id, class_id, attendance_date, status, notes) VALUES (?, ?, ?, ?, ?)");
                $insertStmt->execute([$student_id, $class_id, $attendance_date, $status, $notes]);
                file_put_contents(__DIR__ . '/attendance_log.txt', "Inserted new record\n", FILE_APPEND);
                $inserted_count++;
            }
            
            $marked_count++;
        }
        
        $pdo->commit();
        file_put_contents(__DIR__ . '/attendance_log.txt', "SUCCESS: Committed transaction, marked $marked_count students (Updated: $updated_count, Inserted: $inserted_count)\n", FILE_APPEND);
        
        $message = "Attendance saved successfully! Marked {$marked_count} student(s)";
        if ($updated_count > 0) {
            $message .= " (Updated: {$updated_count})";
        }
        if ($inserted_count > 0) {
            $message .= " (New: {$inserted_count})";
        }
        
        // Return JSON for AJAX, redirect for normal form
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => $message,
                'marked_count' => $marked_count,
                'updated_count' => $updated_count,
                'inserted_count' => $inserted_count
            ]);
            exit;
        }
        
        $_SESSION['success'] = $message;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        file_put_contents(__DIR__ . '/attendance_log.txt', "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Failed to save attendance: ' . $e->getMessage()]);
            exit;
        }
        
        $_SESSION['error'] = "Failed to save attendance: " . $e->getMessage();
    }
    
    header('Location: admin.php?page=attendance&class_id=' . $class_id . '&date=' . $attendance_date);
    exit;
}

// ... (REST OF THE FILE REMAINS UNCHANGED) ...
// Copy all the remaining code from the original file below:

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
        
        // Get registration details including parent account info
        $checkStmt = $pdo->prepare("
            SELECT 
                r.id, 
                r.registration_number, 
                r.student_account_id, 
                r.parent_account_id,
                r.email,
                s.parent_account_id as student_parent_id
            FROM registrations r
            LEFT JOIN students s ON r.student_account_id = s.id
            WHERE r.id = ?
        ");
        $checkStmt->execute([$regId]);
        $registration = $checkStmt->fetch();
        
        if (!$registration) {
            $_SESSION['error'] = "Registration not found (ID: $regId)";
            $pdo->rollBack();
        } else {
            $regNumber = $registration['registration_number'];
            $studentAccountId = $registration['student_account_id'];
            $parentAccountId = $registration['parent_account_id'] ?? $registration['student_parent_id'];
            $email = $registration['email'];
            
            error_log("[Delete Registration] Reg#: {$regNumber}, Student ID: {$studentAccountId}, Parent ID: {$parentAccountId}");
            
            // Delete the registration first
            $stmt = $pdo->prepare("DELETE FROM registrations WHERE id = ?");
            $stmt->execute([$regId]);
            
            // Delete associated student account if exists
            if ($studentAccountId) {
                // Delete parent-child relationship
                $stmt = $pdo->prepare("DELETE FROM parent_child_relationships WHERE student_id = ?");
                $stmt->execute([$studentAccountId]);
                
                // Delete enrollments
                $stmt = $pdo->prepare("DELETE FROM enrollments WHERE student_id = ?");
                $stmt->execute([$studentAccountId]);
                
                // Delete attendance records
                $stmt = $pdo->prepare("DELETE FROM attendance WHERE student_id = ?");
                $stmt->execute([$studentAccountId]);
                
                // Delete invoices
                $stmt = $pdo->prepare("DELETE FROM invoices WHERE student_id = ?");
                $stmt->execute([$studentAccountId]);
                
                // Delete payments
                $stmt = $pdo->prepare("DELETE FROM payments WHERE student_id = ?");
                $stmt->execute([$studentAccountId]);
                
                // Finally delete the student account
                $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
                $stmt->execute([$studentAccountId]);
                
                error_log("[Delete Registration] Deleted student account ID: {$studentAccountId}");
            }
            
            // Check if parent account should be deleted
            if ($parentAccountId) {
                // Count how many children this parent still has
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as child_count 
                    FROM students 
                    WHERE parent_account_id = ?
                ");
                $stmt->execute([$parentAccountId]);
                $result = $stmt->fetch();
                $childCount = (int)$result['child_count'];
                
                error_log("[Delete Registration] Parent ID {$parentAccountId} has {$childCount} remaining children");
                
                if ($childCount === 0) {
                    // No more children - delete the parent account
                    
                    // First delete any remaining relationships (should be none, but just in case)
                    $stmt = $pdo->prepare("DELETE FROM parent_child_relationships WHERE parent_id = ?");
                    $stmt->execute([$parentAccountId]);
                    
                    // Delete the parent account
                    $stmt = $pdo->prepare("SELECT parent_id, full_name FROM parent_accounts WHERE id = ?");
                    $stmt->execute([$parentAccountId]);
                    $parentInfo = $stmt->fetch();
                    
                    $stmt = $pdo->prepare("DELETE FROM parent_accounts WHERE id = ?");
                    $stmt->execute([$parentAccountId]);
                    
                    error_log("[Delete Registration] Deleted parent account: {$parentInfo['parent_id']} ({$parentInfo['full_name']})");
                    
                    $pdo->commit();
                    $_SESSION['success'] = "Registration {$regNumber} deleted successfully! Student account and parent account (no other children) have been removed.";
                } else {
                    // Parent still has other children - keep the parent account
                    $pdo->commit();
                    $_SESSION['success'] = "Registration {$regNumber} deleted successfully! Student account removed. Parent account retained ({$childCount} other child(ren) exist).";
                }
            } else {
                // No parent account linked
                $pdo->commit();
                $_SESSION['success'] = "Registration {$regNumber} and associated student account deleted successfully!";
            }
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("[Delete Registration] Error: " . $e->getMessage());
        $_SESSION['error'] = "Database error: " . $e->getMessage();
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

// ============ INVOICE MANAGEMENT ============

if ($action === 'create_invoice') {
    $student_id = $_POST['student_id'];
    $class_id = $_POST['class_id'] ?? null;
    $invoice_type = $_POST['invoice_type'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $due_date = $_POST['due_date'];
    $invoice_number = 'INV-' . date('Ym') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

    $stmt = $pdo->prepare("INSERT INTO invoices (invoice_number, student_id, class_id, invoice_type, description, amount, due_date, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'unpaid', NOW())");
    $stmt->execute([$invoice_number, $student_id, $class_id, $invoice_type, $description, $amount, $due_date]);
    $_SESSION['success'] = "Invoice created! #$invoice_number";
    header('Location: admin.php?page=invoices');
    exit;
}

if ($action === 'generate_monthly_invoices') {
    try {
        $currentMonth = date('M Y');
        $dueDate = date('Y-m-10');
        
        $enrollments = $pdo->query("
            SELECT 
                e.student_id, e.class_id,
                s.full_name,
                c.class_name, c.class_code, c.monthly_fee
            FROM enrollments e
            JOIN students s ON e.student_id = s.id
            JOIN classes c ON e.class_id = c.id
            WHERE e.status = 'active'
        ")->fetchAll();
        
        $created = 0;
        $skipped = 0;
        
        foreach ($enrollments as $enroll) {
            $check = $pdo->prepare("
                SELECT id FROM invoices 
                WHERE student_id = ? AND class_id = ?
                AND description LIKE ?
                AND MONTH(created_at) = MONTH(NOW())
                AND YEAR(created_at) = YEAR(NOW())
            ");
            $check->execute([$enroll['student_id'], $enroll['class_id'], "%$currentMonth%"]);
            
            if ($check->fetch()) {
                $skipped++;
                continue;
            }
            
            $invoiceNumber = 'INV-' . date('Ym') . '-' . rand(1000, 9999);
            $description = "Monthly Fee: {$enroll['class_name']} ({$enroll['class_code']}) - $currentMonth";
            
            $pdo->prepare("
                INSERT INTO invoices (
                    invoice_number, student_id, class_id, invoice_type,
                    description, amount, due_date, status, created_at
                ) VALUES (?, ?, ?, 'monthly', ?, ?, ?, 'unpaid', NOW())
            ")->execute([
                $invoiceNumber,
                $enroll['student_id'],
                $enroll['class_id'],
                $description,
                $enroll['monthly_fee'],
                $dueDate
            ]);
            
            $created++;
        }
        
        $_SESSION['success'] = "Monthly invoices generated! Created: $created, Skipped: $skipped";
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to generate invoices: " . $e->getMessage();
    }
    
    header('Location: admin.php?page=invoices');
    exit;
}

if ($action === 'edit_invoice') {
    $id = $_POST['invoice_id'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $due_date = $_POST['due_date'];
    $status = $_POST['status'];

    if ($status === 'paid') {
        $stmt = $pdo->prepare("UPDATE invoices SET description = ?, amount = ?, due_date = ?, status = ?, paid_date = NOW() WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("UPDATE invoices SET description = ?, amount = ?, due_date = ?, status = ?, paid_date = NULL WHERE id = ?");
    }
    $stmt->execute([$description, $amount, $due_date, $status, $id]);
    $_SESSION['success'] = "Invoice updated!";
    header('Location: admin.php?page=invoices');
    exit;
}

if ($action === 'delete_invoice') {
    $id = $_POST['invoice_id'];
    $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success'] = "Invoice deleted!";
    header('Location: admin.php?page=invoices');
    exit;
}

// ============ PAYMENT VERIFICATION ============

if ($action === 'verify_payment') {
    $payment_id = $_POST['payment_id'];
    $verification_status = $_POST['verification_status'];
    $admin_notes = $_POST['admin_notes'] ?? '';
    $invoice_id = $_POST['invoice_id'] ?? null;

    try {
        $pdo->beginTransaction();
        
        // Update payment status with admin info
        $stmt = $pdo->prepare("UPDATE payments SET verification_status = ?, admin_notes = ?, verified_date = NOW(), verified_by = ? WHERE id = ?");
        $stmt->execute([$verification_status, $admin_notes, $_SESSION['admin_id'] ?? null, $payment_id]);
        
        $emailSent = false;
        
        if ($verification_status === 'verified' && $invoice_id) {
            // Mark invoice as paid
            $stmt = $pdo->prepare("UPDATE invoices SET status = 'paid', paid_date = NOW() WHERE id = ?");
            $stmt->execute([$invoice_id]);
            
            $stmt = $pdo->prepare("SELECT invoice_number FROM invoices WHERE id = ?");
            $stmt->execute([$invoice_id]);
            $invoice = $stmt->fetch();
            
            $pdo->commit();
            
            // ✨ NEW: Send approval email with PDF receipt
            require_once 'send_payment_approval_email.php';
            try {
                $emailSent = sendPaymentApprovalEmail($payment_id, 'verified', $admin_notes);
            } catch (Exception $e) {
                error_log("[verify_payment] Email sending failed: " . $e->getMessage());
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
            
            // ✨ NEW: Send rejection email
            require_once 'send_payment_approval_email.php';
            try {
                $emailSent = sendPaymentApprovalEmail($payment_id, 'rejected', $admin_notes);
            } catch (Exception $e) {
                error_log("[verify_payment] Email sending failed: " . $e->getMessage());
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
            $_SESSION['success'] = "Payment status updated to: " . ucfirst($verification_status);
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Failed to verify payment: " . $e->getMessage();
    }
    
    header('Location: admin.php?page=payments');
    exit;
}

// ============ ATTENDANCE (OTHER ACTIONS) ============

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