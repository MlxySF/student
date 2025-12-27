<?php
// admin.php - Complete Admin Panel
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Redirect if not logged in
function redirectIfNotAdmin() {
    if (!isAdminLoggedIn()) {
        // Use JavaScript redirect as fallback if headers already sent
        if (headers_sent()) {
            echo '<script>window.location.href = "admin.php?page=login";</script>';
            exit;
        }
        header('Location: admin.php?page=login');
        exit;
    }
}


// Safety stub function: This should NOT be used in admin context
// Defined here only to prevent "undefined function" errors if accidentally called
if (!function_exists('getStudentId')) {
    function getStudentId() {
        return null;
    }
}

/**
 * Calculate monthly fee for a student based on their active enrollments
 * Uses the same logic as fee_calculator.js:
 * - Fetches session counts for each enrolled class
 * - Sorts classes by session count (descending)
 * - Applies tiered pricing: 1st=RM30, 2nd=RM27, 3rd=RM24, 4th=RM21 per session
 * 
 * @param PDO $pdo Database connection
 * @param int $student_id Student ID
 * @param int $month Month (1-12)
 * @param int $year Year
 * @return array ['total_fee' => float, 'breakdown' => array, 'total_sessions' => int]
 */
function calculateStudentMonthlyFee($pdo, $student_id, $month, $year) {
    // Session pricing tiers (matches fee_calculator.js)
    $sessionPricing = [30, 27, 24, 21];
    
    // Get all active enrollments for the student
    $stmt = $pdo->prepare("
        SELECT 
            e.class_id,
            c.class_code,
            c.class_name,
            c.day_of_week
        FROM enrollments e
        JOIN classes c ON e.class_id = c.id
        WHERE e.student_id = ? AND e.status = 'active'
        ORDER BY c.class_name
    ");
    $stmt->execute([$student_id]);
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($enrollments)) {
        return [
            'total_fee' => 0,
            'breakdown' => [],
            'total_sessions' => 0,
            'num_classes' => 0
        ];
    }
    
    // Get holidays for the month
    $holidays_stmt = $pdo->prepare("
        SELECT holiday_date 
        FROM class_holidays 
        WHERE MONTH(holiday_date) = ? AND YEAR(holiday_date) = ?
    ");
    $holidays_stmt->execute([$month, $year]);
    $holidays = [];
    while ($row = $holidays_stmt->fetch()) {
        $holidays[] = $row['holiday_date'];
    }
    
    // Calculate session count for each class
    $classesData = [];
    $total_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    
    foreach ($enrollments as $enrollment) {
        $session_count = 0;
        $day_name = $enrollment['day_of_week']; // e.g., "Monday", "Tuesday"
        
        // Count available dates (non-holidays matching the class day)
        for ($day = 1; $day <= $total_days; $day++) {
            $date = sprintf("%04d-%02d-%02d", $year, $month, $day);
            $current_day_name = date('l', strtotime($date));
            
            // If not a holiday and matches the class day, count it
            if (!in_array($date, $holidays) && $current_day_name === $day_name) {
                $session_count++;
            }
        }
        
        $classesData[] = [
            'class_id' => $enrollment['class_id'],
            'class_code' => $enrollment['class_code'],
            'class_name' => $enrollment['class_name'],
            'day_of_week' => $day_name,
            'session_count' => $session_count
        ];
    }
    
    // ✅ SORT classes by session count DESCENDING (matches fee_calculator.js)
    // This ensures classes with fewer sessions get lower pricing (last position)
    usort($classesData, function($a, $b) {
        return $b['session_count'] - $a['session_count'];
    });
    
    // Calculate fee for each class based on sorted position
    $total_fee = 0;
    $breakdown = [];
    $total_sessions = 0;
    
    foreach ($classesData as $index => $classData) {
        $price_per_session = $sessionPricing[$index] ?? $sessionPricing[count($sessionPricing) - 1];
        $session_count = $classData['session_count'];
        $class_fee = $price_per_session * $session_count;
        
        $total_fee += $class_fee;
        $total_sessions += $session_count;
        
        $breakdown[] = [
            'position' => $index + 1,
            'class_id' => $classData['class_id'],
            'class_code' => $classData['class_code'],
            'class_name' => $classData['class_name'],
            'day_of_week' => $classData['day_of_week'],
            'price_per_session' => $price_per_session,
            'session_count' => $session_count,
            'class_fee' => $class_fee
        ];
    }
    
    return [
        'total_fee' => $total_fee,
        'breakdown' => $breakdown,
        'total_sessions' => $total_sessions,
        'num_classes' => count($classesData)
    ];
}

// Handle Admin Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'admin_login') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['admin_role'] = $admin['role'];
        header('Location: admin.php?page=dashboard');
        exit;
    } else {
        $_SESSION['error'] = "Invalid username or password.";
    }
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Handle Invoice Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    redirectIfNotAdmin();
    
    // CREATE INVOICE
if ($_POST['action'] === 'create_invoice') {
    $student_id = $_POST['student_id'];
    $invoice_type = $_POST['invoice_type'];
    $class_id = !empty($_POST['class_id']) ? $_POST['class_id'] : null;
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $due_date = $_POST['due_date'];
    
    // Generate invoice number
    $invoice_number = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Set payment_month for monthly_fee type
    $payment_month = null;
    if ($invoice_type === 'monthly_fee') {
        $payment_month = date('M Y'); // e.g., "Dec 2025"
    }
    
    error_log("[admin.php create_invoice] Creating invoice: {$invoice_number}");
    
    $stmt = $pdo->prepare("
        INSERT INTO invoices (student_id, invoice_number, invoice_type, class_id, description, amount, due_date, payment_month, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'unpaid', NOW())
    ");
    
    if ($stmt->execute([$student_id, $invoice_number, $invoice_type, $class_id, $description, $amount, $due_date, $payment_month])) {
        // ✨ NEW: Get invoice ID and send email notification
        $invoiceId = $pdo->lastInsertId();
        error_log("[admin.php create_invoice] Invoice created with ID: {$invoiceId}");
        
        // ✨ NEW: Send notification email
        require_once 'send_invoice_notification.php';
        $emailSent = false;
        try {
            $emailSent = sendInvoiceNotification($pdo, $invoiceId);
            error_log("[admin.php create_invoice] Email result: " . ($emailSent ? 'SUCCESS' : 'FAILED'));
        } catch (Exception $e) {
            error_log("[admin.php create_invoice] Email exception: " . $e->getMessage());
        }
        
        // Build success message
        $message = "Invoice created successfully! #{$invoice_number}";
        if ($emailSent) {
            $message .= " ✅ Email notification sent.";
        } else {
            $message .= " ⚠️ Email notification failed.";
        }
        $_SESSION['success'] = $message;
    } else {
        $_SESSION['error'] = "Failed to create invoice.";
    }
    
    header('Location: admin.php?page=invoices');
    exit;
}

if ($_POST['action'] === 'generate_monthly_invoices') {
    $current_month_name = date('M Y'); // e.g., "Dec 2025"
    $month = date('n'); // Month as number (1-12)
    $year = date('Y');
    $due_date = date('Y-m-10'); // Due on the 10th of the month
    
    $generated_count = 0;
    $skipped_count = 0;
    $emailsSent = 0;
    $emailsFailed = 0;
    $total_amount = 0;
    
    error_log("[Generate Monthly Invoices] Starting for {$current_month_name}");
    
    try {
        // ✅ FIXED: Join registrations using students.student_id = registrations.registration_number
        $stmt = $pdo->prepare("
            SELECT DISTINCT s.id as student_id, s.full_name, s.student_id as student_number
            FROM students s
            INNER JOIN enrollments e ON s.id = e.student_id
            INNER JOIN registrations r ON s.student_id = r.registration_number
            WHERE r.payment_status = 'approved' AND e.status = 'active'
            ORDER BY s.full_name
        ");
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("[Generate Monthly Invoices] Found " . count($students) . " students with paid registration and active enrollments");
        
        foreach ($students as $student) {
            $student_id = $student['student_id'];
            $student_name = $student['full_name'];
            
            // Check if invoice already exists for this student and month
            $check_stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM invoices 
                WHERE student_id = ? 
                  AND invoice_type = 'monthly_fee' 
                  AND payment_month = ?
            ");
            $check_stmt->execute([$student_id, $current_month_name]);
            $existing = $check_stmt->fetch();
            
            if ($existing['count'] > 0) {
                error_log("[Generate Monthly Invoices] Skipping {$student_name} - invoice already exists for {$current_month_name}");
                $skipped_count++;
                continue;
            }
            
            // ✅ Calculate fee using the same logic as fee_calculator.js
            $feeData = calculateStudentMonthlyFee($pdo, $student_id, $month, $year);
            
            // Skip if no active enrollments or zero fee
            if ($feeData['total_fee'] <= 0) {
                error_log("[Generate Monthly Invoices] Skipping {$student_name} - no classes or zero fee");
                $skipped_count++;
                continue;
            }
            
            // Generate invoice number
            $invoice_number = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Build description with breakdown
            $description = "Monthly Fee - {$current_month_name}\n";
            $description .= "Total: {$feeData['num_classes']} class(es), {$feeData['total_sessions']} session(s)\n\n";
            $description .= "Breakdown (sorted by sessions):\n";
            
            foreach ($feeData['breakdown'] as $item) {
                $description .= sprintf(
                    "%d. %s (%s): %d sessions × RM%.2f = RM%.2f\n",
                    $item['position'],
                    $item['class_name'],
                    $item['day_of_week'],
                    $item['session_count'],
                    $item['price_per_session'],
                    $item['class_fee']
                );
            }
            
            // Use the first class_id for the invoice (or null if multiple classes)
            $class_id = ($feeData['num_classes'] == 1) ? $feeData['breakdown'][0]['class_id'] : null;
            
            // Insert invoice
            $insert_stmt = $pdo->prepare("
                INSERT INTO invoices (
                    student_id, 
                    invoice_number, 
                    invoice_type, 
                    class_id, 
                    description, 
                    amount, 
                    due_date, 
                    payment_month, 
                    status, 
                    created_at
                ) VALUES (?, ?, 'monthly_fee', ?, ?, ?, ?, ?, 'unpaid', NOW())
            ");
            
            if ($insert_stmt->execute([
                $student_id,
                $invoice_number,
                $class_id,
                $description,
                $feeData['total_fee'],
                $due_date,
                $current_month_name
            ])) {
                $generated_count++;
                $total_amount += $feeData['total_fee'];
                
                error_log("[Generate Monthly Invoices] ✅ Created {$invoice_number} for {$student_name}: RM" . number_format($feeData['total_fee'], 2));
                
                // Send email notification
                $invoiceId = $pdo->lastInsertId();
                require_once 'send_invoice_notification.php';
                try {
                    if (sendInvoiceNotification($pdo, $invoiceId)) {
                        $emailsSent++;
                    } else {
                        $emailsFailed++;
                    }
                } catch (Exception $e) {
                    $emailsFailed++;
                    error_log("[Generate Monthly Invoices] Email error for {$student_name}: " . $e->getMessage());
                }
            } else {
                error_log("[Generate Monthly Invoices] ❌ Failed to create invoice for {$student_name}");
            }
        }
        
        // Build detailed success message
        $message = "Generated {$generated_count} monthly invoices for {$current_month_name}!";
        
        if ($generated_count > 0) {
            $message .= " Total amount: RM" . number_format($total_amount, 2);
        }
        
        if ($skipped_count > 0) {
            $message .= " | Skipped {$skipped_count} (already exists or no classes)";
        }
        
        if ($emailsSent > 0 || $emailsFailed > 0) {
            $message .= " | Emails: {$emailsSent} sent, {$emailsFailed} failed";
        }
        
        $_SESSION['success'] = $message;
        error_log("[Generate Monthly Invoices] Completed: {$message}");
        
    } catch (Exception $e) {
        error_log("[Generate Monthly Invoices] Error: " . $e->getMessage());
        $_SESSION['error'] = "Error generating invoices: " . $e->getMessage();
    }
    
    header('Location: admin.php?page=invoices');
    exit;
}

    
    // EDIT INVOICE
    if ($_POST['action'] === 'edit_invoice') {
        $invoice_id = $_POST['invoice_id'];
        $description = $_POST['description'];
        $amount = $_POST['amount'];
        $due_date = $_POST['due_date'];
        $status = $_POST['status'];
        
        $stmt = $pdo->prepare("
            UPDATE invoices 
            SET description = ?, amount = ?, due_date = ?, status = ? 
            WHERE id = ?
        ");
        
        if ($stmt->execute([$description, $amount, $due_date, $status, $invoice_id])) {
            $_SESSION['success'] = "Invoice updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update invoice.";
        }
        
        header('Location: admin.php?page=invoices');
        exit;
    }
    
    // DELETE INVOICE
if ($_POST['action'] === 'delete_invoice') {
    $invoice_id = $_POST['invoice_id'];
    
    try {
        $pdo->beginTransaction();
        
        // ✨ NEW: Get invoice details and payment receipt paths
        $stmt = $pdo->prepare("
            SELECT 
                i.invoice_number,
                p.id as payment_id,
                p.receipt_path
            FROM invoices i
            LEFT JOIN payments p ON i.id = p.invoice_id
            WHERE i.id = ?
        ");
        $stmt->execute([$invoice_id]);
        $invoiceData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$invoiceData) {
            throw new Exception("Invoice not found");
        }
        
        $invoiceNumber = $invoiceData['invoice_number'];
        $receiptPath = $invoiceData['receipt_path'];
        $filesDeleted = 0;
        
        // ✨ NEW: Delete receipt file from filesystem if it exists
        if (!empty($receiptPath)) {
            // Add uploads/ prefix if not already present
            $path = $receiptPath;
            if (strpos($path, 'uploads/') !== 0) {
                $path = 'uploads/' . $path;
            }
            
            $fullPath = __DIR__ . '/' . $path;
            
            if (file_exists($fullPath)) {
                if (unlink($fullPath)) {
                    $filesDeleted++;
                    error_log("[Delete Invoice] ✅ Deleted receipt file: {$fullPath}");
                } else {
                    error_log("[Delete Invoice] ❌ Failed to delete receipt file: {$fullPath}");
                }
            } else {
                error_log("[Delete Invoice] ⚠️ Receipt file not found: {$fullPath}");
            }
        }
        
        // Delete associated payment records first (if any)
        if (!empty($invoiceData['payment_id'])) {
            $stmt = $pdo->prepare("DELETE FROM payments WHERE invoice_id = ?");
            $stmt->execute([$invoice_id]);
            error_log("[Delete Invoice] Deleted payment record for invoice {$invoiceNumber}");
        }
        
        // Delete the invoice
        $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
        $stmt->execute([$invoice_id]);
        
        $pdo->commit();
        
        // Build success message
        $message = "Invoice {$invoiceNumber} deleted successfully!";
        if ($filesDeleted > 0) {
            $message .= " Receipt file removed from storage.";
        }
        
        $_SESSION['success'] = $message;
        error_log("[Delete Invoice] Success: {$invoiceNumber}, files deleted: {$filesDeleted}");
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("[Delete Invoice] Error: " . $e->getMessage());
        $_SESSION['error'] = "Failed to delete invoice: " . $e->getMessage();
    }
    
    header('Location: admin.php?page=invoices');
    exit;
}


    
    // VERIFY PAYMENT
if ($_POST['action'] === 'verify_payment') {
    $payment_id = $_POST['payment_id'];
    $invoice_id = $_POST['invoice_id'];
    $verification_status = $_POST['verification_status'];
    $admin_notes = $_POST['admin_notes'] ?? '';

    error_log("========================================");
    error_log("[admin.php verify_payment] Starting - Payment ID: {$payment_id}, Status: {$verification_status}");
    
    try {
        $pdo->beginTransaction();
        
        // ✨ NEW: Get payment receipt path BEFORE updating
        $stmt = $pdo->prepare("SELECT receipt_path, student_id FROM payments WHERE id = ?");
        $stmt->execute([$payment_id]);
        $payment = $stmt->fetch();
        $receiptPath = $payment['receipt_path'] ?? null;
        $studentId = $payment['student_id'] ?? null;
        
        error_log("[admin.php verify_payment] Receipt path: " . ($receiptPath ?? 'none'));
        error_log("[admin.php verify_payment] Student ID: " . ($studentId ?? 'none'));
        
        // Update payment verification
        $stmt = $pdo->prepare("UPDATE payments SET verification_status = ?, admin_notes = ?, verified_date = NOW(), verified_by = ? WHERE id = ?");
        $stmt->execute([$verification_status, $admin_notes, $_SESSION['admin_id'] ?? null, $payment_id]);
        
        error_log("[admin.php verify_payment] Payment record updated");
        
        if ($verification_status === 'verified') {
            // Update invoice status to paid
            $updateInvoice = $pdo->prepare("UPDATE invoices SET status = 'paid', paid_date = NOW() WHERE id = ?");
            $updateInvoice->execute([$invoice_id]);
            
            error_log("[admin.php verify_payment] Invoice marked as PAID");
            
            $pdo->commit();
            
            $_SESSION['success'] = "Payment verified and invoice marked as PAID!";
            
        } else if ($verification_status === 'rejected') {
            // ✨ NEW: Rename receipt file to include "rejected"
            if (!empty($receiptPath)) {
                require_once 'file_helper.php';
                
                error_log("[admin.php verify_payment] Renaming receipt file with rejection marker...");
                $renameResult = renameFileWithRejection($receiptPath);
                
                if ($renameResult['success']) {
                    $newPath = $renameResult['new_path'];
                    
                    // Update receipt_path in database
                    $stmt = $pdo->prepare("UPDATE payments SET receipt_path = ? WHERE id = ?");
                    $stmt->execute([$newPath, $payment_id]);
                    
                    if ($renameResult['already_renamed']) {
                        error_log("[admin.php verify_payment] Receipt already marked as rejected: {$newPath}");
                    } else {
                        error_log("[admin.php verify_payment] Receipt renamed successfully: {$receiptPath} -> {$newPath}");
                    }
                } else {
                    error_log("[admin.php verify_payment] WARNING: Failed to rename receipt file: " . $renameResult['error']);
                    // Continue with rejection even if file rename fails
                }
            } else {
                error_log("[admin.php verify_payment] No receipt path found for this payment");
            }
            
            // Update invoice status to rejected
            $updateInvoice = $pdo->prepare("UPDATE invoices SET status = 'rejected' WHERE id = ?");
            $updateInvoice->execute([$invoice_id]);
            
            error_log("[admin.php verify_payment] Invoice marked as REJECTED");
            
            $pdo->commit();
            
            $_SESSION['success'] = "Payment rejected and invoice marked as REJECTED!";
            if (!empty($receiptPath)) {
                $_SESSION['success'] .= " Receipt file marked as rejected.";
            }
        } else {
            $pdo->commit();
            $_SESSION['success'] = "Payment status updated!";
        }
        
        // ✨ SEND EMAIL NOTIFICATION
        error_log("[admin.php verify_payment] Attempting to send email notification");
        require_once 'send_payment_approval_email.php';
        
        try {
            $emailSent = sendPaymentApprovalEmail($pdo, $payment_id, $verification_status, $admin_notes);
            error_log("[admin.php verify_payment] Email function returned: " . ($emailSent ? 'true' : 'false'));
            
            if ($emailSent) {
                if ($verification_status === 'verified') {
                    $_SESSION['success'] .= " Approval email with PDF receipt sent to parent.";
                    error_log("[admin.php verify_payment] Approval email sent successfully");
                } else if ($verification_status === 'rejected') {
                    $_SESSION['success'] .= " Rejection notification sent to parent.";
                    error_log("[admin.php verify_payment] Rejection email sent successfully");
                }
            } else {
                error_log("[admin.php verify_payment] Email was not sent (emailSent = false)");
                $_SESSION['success'] .= " (Note: Email notification could not be sent)";
            }
        } catch (Exception $e) {
            error_log("[admin.php verify_payment] Email error: " . $e->getMessage());
            error_log("[admin.php verify_payment] Stack trace: " . $e->getTraceAsString());
            $_SESSION['success'] .= " (Note: Email notification could not be sent)";
        }
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("[admin.php verify_payment] ERROR: " . $e->getMessage());
        error_log("[admin.php verify_payment] Stack trace: " . $e->getTraceAsString());
        $_SESSION['error'] = "Failed to verify payment: " . $e->getMessage();
    }
    
    error_log("[admin.php verify_payment] Redirecting to admin.php?page=invoices");
    error_log("========================================");
    
    header('Location: admin.php?page=invoices');
    exit;
}

// REJECT APPROVED PAYMENT (Reverse approval)
if ($_POST['action'] === 'reject_approved_payment') {
    $payment_id = $_POST['payment_id'];
    $invoice_id = $_POST['invoice_id'];
    $rejection_reason = $_POST['rejection_reason'] ?? 'Payment approval reversed by admin';

    error_log("========================================");
    error_log("[admin.php reject_approved_payment] Starting - Payment ID: {$payment_id}");
    
    try {
        $pdo->beginTransaction();
        
        // Get payment and receipt details
        $stmt = $pdo->prepare("SELECT receipt_path FROM payments WHERE id = ?");
        $stmt->execute([$payment_id]);
        $payment = $stmt->fetch();
        $receiptPath = $payment['receipt_path'] ?? null;
        
        error_log("[admin.php reject_approved_payment] Receipt path: " . ($receiptPath ?? 'none'));
        
        // Update payment status back to rejected
        $stmt = $pdo->prepare("
            UPDATE payments 
            SET verification_status = 'rejected', 
                admin_notes = ?,
                verified_date = NOW(),
                verified_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$rejection_reason, $_SESSION['admin_id'] ?? null, $payment_id]);
        
        error_log("[admin.php reject_approved_payment] Payment status updated to rejected");
        
        // Rename receipt file to include "rejected" marker
        if (!empty($receiptPath)) {
            require_once 'file_helper.php';
            
            error_log("[admin.php reject_approved_payment] Renaming receipt file...");
            $renameResult = renameFileWithRejection($receiptPath);
            
            if ($renameResult['success']) {
                $newPath = $renameResult['new_path'];
                
                // Update receipt_path in database
                $stmt = $pdo->prepare("UPDATE payments SET receipt_path = ? WHERE id = ?");
                $stmt->execute([$newPath, $payment_id]);
                
                if ($renameResult['already_renamed']) {
                    error_log("[admin.php reject_approved_payment] Receipt already marked as rejected: {$newPath}");
                } else {
                    error_log("[admin.php reject_approved_payment] Receipt renamed: {$receiptPath} -> {$newPath}");
                }
            } else {
                error_log("[admin.php reject_approved_payment] WARNING: Failed to rename receipt: " . $renameResult['error']);
            }
        }
        
        // Update invoice status back to rejected
        $stmt = $pdo->prepare("
            UPDATE invoices 
            SET status = 'rejected', 
                paid_date = NULL 
            WHERE id = ?
        ");
        $stmt->execute([$invoice_id]);
        
        error_log("[admin.php reject_approved_payment] Invoice marked as REJECTED");
        
        $pdo->commit();
        
        $_SESSION['success'] = "Approved payment has been rejected successfully! Invoice marked as REJECTED.";
        
        // Send rejection email notification
        error_log("[admin.php reject_approved_payment] Sending rejection notification email");
        require_once 'send_payment_approval_email.php';
        
        try {
            $emailSent = sendPaymentApprovalEmail($pdo, $payment_id, 'rejected', $rejection_reason);
            
            if ($emailSent) {
                $_SESSION['success'] .= " Rejection notification sent to parent.";
                error_log("[admin.php reject_approved_payment] Rejection email sent successfully");
            } else {
                error_log("[admin.php reject_approved_payment] Rejection email failed");
                $_SESSION['success'] .= " (Note: Email notification could not be sent)";
            }
        } catch (Exception $e) {
            error_log("[admin.php reject_approved_payment] Email error: " . $e->getMessage());
            $_SESSION['success'] .= " (Note: Email notification could not be sent)";
        }
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("[admin.php reject_approved_payment] ERROR: " . $e->getMessage());
        $_SESSION['error'] = "Failed to reject payment: " . $e->getMessage();
    }
    
    error_log("[admin.php reject_approved_payment] Redirecting to invoices page");
    error_log("========================================");
    
    header('Location: admin.php?page=invoices');
    exit;
}


}

$page = $_GET['page'] ?? 'login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Admin Panel</title>
    
    <!-- ✨ NEW: Favicon -->
    <link rel="icon" type="image/png" href="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/Wushu+Sport+Academy+Circle+Yellow.png">
    <link rel="shortcut icon" type="image/png" href="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/Wushu+Sport+Academy+Circle+Yellow.png">
    <link rel="apple-touch-icon" href="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/Wushu+Sport+Academy+Circle+Yellow.png">
    
    <!-- Google Fonts: Inter (English) + Noto Sans SC (Chinese) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Noto+Sans+SC:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #7c3aed;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #06b6d4;
        }

        * {
            font-family: 'Inter', 'Noto Sans SC', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            font-family: 'Inter', 'Noto Sans SC', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8fafc;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* ============================================
           LOADING OVERLAY - Prevents duplicate submissions
           ============================================ */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
        }

        .loading-overlay.active {
            display: flex;
        }

        .loading-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            max-width: 400px;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 6px solid #f3f3f3;
            border-top: 6px solid #2563eb;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .loading-subtext {
            font-size: 14px;
            color: #64748b;
        }

        /* Fixed Top Header */
        .top-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 70px;
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-menu-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 45px;
            height: 45px;
            border-radius: 10px;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .header-menu-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.05);
        }

        .school-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            text-decoration: none;
        }

        .school-logo img {
            height: 50px;
            width: 50px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        .school-logo .logo-text {
            display: flex;
            flex-direction: column;
        }

        .school-logo .logo-title {
            font-size: 18px;
            font-weight: 700;
            line-height: 1.2;
        }

        .school-logo .logo-subtitle {
            font-size: 12px;
            opacity: 0.9;
            font-weight: 500;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-user {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
        }

        .header-user-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 700;
        }

        .header-user-info {
            display: flex;
            flex-direction: column;
        }

        .header-user-name {
            font-size: 14px;
            font-weight: 600;
            line-height: 1.2;
        }

        .header-user-role {
            font-size: 11px;
            opacity: 0.9;
            font-weight: 500;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 70px;
            left: 0;
            width: 100%;
            height: calc(100vh - 70px);
            background: rgba(0,0,0,0.5);
            z-index: 998;
        }

        .sidebar-overlay.active {
            display: block;
        }

        .admin-sidebar {
            position: fixed;
            left: 0;
            top: 70px;
            width: 280px;
            height: calc(100vh - 70px);
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            overflow-y: auto;
            z-index: 999;
            transition: left 0.3s ease;
            padding: 20px 0;
        }

        .admin-sidebar .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 14px 25px;
            margin: 3px 0;
            border-radius: 0;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }

        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 4px solid var(--primary-color);
        }

        .admin-sidebar .nav-link i {
            width: 20px;
            font-size: 16px;
            text-align: center;
        }

        body.logged-in {
            padding-top: 70px;
        }

        .admin-content {
            margin-left: 280px;
            min-height: calc(100vh - 70px);
        }

        .content-area {
            padding: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            transition: transform 0.3s;
            margin-bottom: 20px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 25px rgba(0,0,0,0.12);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-right: 20px;
        }

        .stat-icon.bg-primary { background: linear-gradient(135deg, #2563eb, #1e40af); color: white; }
        .stat-icon.bg-success { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .stat-icon.bg-warning { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .stat-icon.bg-danger { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
        .stat-icon.bg-info { background: linear-gradient(135deg, #06b6d4, #0891b2); color: white; }

        .stat-content h3 {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
            color: #1e293b;
        }

        .stat-content p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
        }

        .table {
            background: white;
        }

        .table thead {
            background: #f8fafc;
        }

        .badge {
            padding: 6px 12px;
            font-weight: 600;
            font-size: 11px;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .login-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 450px;
            width: 100%;
        }

        .login-card h2 {
            font-weight: 700;
        }

        /* NEW: Logo styling for login page */
        .login-logo {
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
            display: block;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        /* ✨ NEW: Reload Button Styles */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .btn-reload {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-reload:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
            color: white;
        }

        .btn-reload i {
            transition: transform 0.6s;
        }

        .btn-reload.loading i {
            animation: spin 1s linear infinite;
        }

        .reload-toast {
            position: fixed;
            top: 90px;
            right: 20px;
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            z-index: 9999;
            display: none;
            align-items: center;
            gap: 10px;
        }

        .reload-toast.show {
            display: flex;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* ✨ FIXED: DataTables Pagination Dropdown - Prevent Arrow Overlap */
        .dataTables_wrapper .dataTables_length {
            margin-bottom: 1rem;
        }

        .dataTables_wrapper .dataTables_length select {
            padding: 5px 30px 5px 10px !important;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            min-width: 90px;
            margin: 0 8px;
            background-color: white;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23475569' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 12px;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            cursor: pointer;
            font-weight: 500;
        }

        .dataTables_wrapper .dataTables_length label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            color: #475569;
        }

        .dataTables_wrapper .dataTables_info {
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
        }

        .dataTables_wrapper .dataTables_paginate {
            margin-top: 1rem;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 6px 12px;
            margin: 0 2px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: white;
            color: #475569;
            transition: all 0.2s;
            font-weight: 500;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: white;
            border-color: #2563eb;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        @media (min-width: 769px) {
            .header-menu-btn {
                display: none;
            }

            .admin-sidebar {
                left: 0;
            }

            .sidebar-overlay {
                display: none !important;
            }
        }

        @media (max-width: 768px) {
            .top-header {
                padding: 0 15px;
            }

            .school-logo .logo-text {
                display: none;
            }

            .header-user-info {
                display: none;
            }

            .admin-sidebar {
                left: -280px;
            }

            .admin-sidebar.active {
                left: 0;
            }

            .admin-content {
                margin-left: 0;
            }

            .content-area {
                padding: 20px 15px;
            }

            .stat-card {
                margin-bottom: 15px;
            }

            .stat-icon {
                width: 50px;
                height: 50px;
                font-size: 20px;
                margin-right: 15px;
            }

            .stat-content h3 {
                font-size: 24px;
            }

            .stat-content p {
                font-size: 12px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .btn-reload {
                width: 100%;
                justify-content: center;
            }

            .loading-content {
                margin: 20px;
                padding: 30px 20px;
            }
        }

        @media (max-width: 480px) {
            .top-header {
                height: 60px;
            }

            body.logged-in {
                padding-top: 60px;
            }
            
            .admin-sidebar {
                top: 60px;
                height: calc(100vh - 60px);
            }

            .sidebar-overlay {
                top: 60px;
                height: calc(100vh - 60px);
            }

            .school-logo img {
                height: 42px;
                width: 42px;
            }

            .header-menu-btn {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }

            .header-user-avatar {
                width: 35px;
                height: 35px;
                font-size: 16px;
            }

            .content-area {
                padding: 15px 10px;
                min-height: calc(100vh - 60px);
            }

            h3.mb-4 {
                font-size: 18px;
            }

            .stat-content h3 {
                font-size: 20px;
            }

            .reload-toast {
                top: 70px;
                right: 10px;
                left: 10px;
                font-size: 14px;
            }
        }

        .badge-student {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: white;
        }

        .badge-state-team {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .badge-backup-team {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
    </style>
</head>
<body<?php echo ($page !== 'login') ? ' class="logged-in"' : ''; ?>>

<!-- GLOBAL LOADING OVERLAY -->
<div class="loading-overlay" id="globalLoadingOverlay">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <div class="loading-text">Processing...</div>
        <div class="loading-subtext">Please wait, do not close this window</div>
    </div>
</div>

<!-- ✨ NEW: Reload Toast Notification -->
<div class="reload-toast" id="reloadToast">
    <i class="fas fa-check-circle text-success"></i>
    <span>Data refreshed successfully!</span>
</div>

<?php if ($page === 'login'): ?>
    <div class="login-container">
        <div class="login-card">
            <div class="text-center mb-4">
                <img src="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/Wushu+Sport+Academy+Circle+Yellow.png" 
                     alt="Wushu Sport Academy Logo" 
                     class="login-logo">
                <h2>Admin Portal</h2>
                <p class="text-muted">Sign in to your admin account</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="submit-with-loading">
                <input type="hidden" name="action" value="admin_login">
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-user"></i> Username</label>
                    <input type="text" name="username" class="form-control form-control-lg" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" class="form-control form-control-lg" required>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
        </div>
    </div>

<?php else: ?>
    <?php redirectIfNotAdmin(); ?>

    <div class="top-header">
        <div class="header-left">
            <button class="header-menu-btn" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>

            <a href="?page=dashboard" class="school-logo">
                <img src="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/Wushu+Sport+Academy+Circle+Yellow.png" 
                     alt="WSA Logo">
                <div class="logo-text">
                    <span class="logo-title">Wushu Sport Academy</span>
                    <span class="logo-subtitle">Admin Portal</span>
                </div>
            </a>
        </div>

        <div class="header-right">
            <div class="header-user">
                <div class="header-user-avatar">
                    <?php echo strtoupper(substr($_SESSION['admin_name'], 0, 1)); ?>
                </div>
                <div class="header-user-info">
                    <span class="header-user-name"><?php echo $_SESSION['admin_name']; ?></span>
                    <span class="header-user-role"><?php echo ucfirst($_SESSION['admin_role']); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="admin-sidebar" id="adminSidebar">
        <nav class="nav flex-column">
            <a class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>" href="?page=dashboard">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a class="nav-link <?php echo $page === 'registrations' ? 'active' : ''; ?>" href="?page=registrations">
                <i class="fas fa-user-plus"></i>
                <span>New Registrations</span>
            </a>
            <a class="nav-link <?php echo $page === 'students' ? 'active' : ''; ?>" href="?page=students">
                <i class="fas fa-users"></i>
                <span>Students</span>
            </a>
            <a class="nav-link <?php echo $page === 'classes' ? 'active' : ''; ?>" href="?page=classes">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Classes</span>
            </a>
            <a class="nav-link <?php echo $page === 'invoices' ? 'active' : ''; ?>" href="?page=invoices">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Invoices</span>
            </a>
            <a class="nav-link <?php echo $page === 'attendance' ? 'active' : ''; ?>" href="?page=attendance">
                <i class="fas fa-calendar-check"></i>
                <span>Attendance</span>
            </a>
            <a class="nav-link <?php echo $page === 'holidays' ? 'active' : ''; ?>" href="?page=holidays">
                <i class="fas fa-calendar-times"></i>
                <span>Manage Holidays</span>
            </a>
            <hr class="text-white mx-3">
            <a class="nav-link" href="?logout=1">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </nav>
    </div>

    <div class="admin-content">
        <div class="content-area">
            <!-- ✨ NEW: Page Header with Reload Button -->
            <div class="page-header">
                <h3 class="mb-0">
                    <i class="fas fa-<?php 
                        echo $page === 'dashboard' ? 'home' : 
                            ($page === 'registrations' ? 'user-plus' :
                            ($page === 'students' ? 'users' : 
                            ($page === 'classes' ? 'chalkboard-teacher' : 
                            ($page === 'holidays' ? 'calendar-times' : 
                            ($page === 'invoices' ? 'file-invoice-dollar' : 'calendar-check' ))))); 
                    ?>"></i>
                    <?php echo ucfirst($page); ?>
                </h3>
                <?php if ($page !== 'dashboard'): ?>
                <button class="btn btn-reload" onclick="reloadPageData()">
                    <i class="fas fa-sync-alt"></i>
                    <span>Refresh Data</span>
                </button>
                <?php endif; ?>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php
            $pages_dir = 'admin_pages/';
            switch($page) {
                case 'dashboard':
                    if (file_exists($pages_dir . 'dashboard.php')) {
                        include $pages_dir . 'dashboard.php';
                    }
                    break;
                case 'registrations':
                    if (file_exists($pages_dir . 'registrations.php')) {
                        include $pages_dir . 'registrations.php';
                    }
                    break;
                case 'students':
                    if (file_exists($pages_dir . 'students.php')) {
                        include $pages_dir . 'students.php';
                    }
                    break;
                case 'classes':
                    if (file_exists($pages_dir . 'classes.php')) {
                        include $pages_dir . 'classes.php';
                    }
                    break;
                case 'invoices':
                    if (file_exists($pages_dir . 'invoices.php')) {
                        include $pages_dir . 'invoices.php';
                    }
                    break;
                case 'payments':
                    if (file_exists($pages_dir . 'payments.php')) {
                        include $pages_dir . 'payments.php';
                    }
                    break;
                case 'attendance':
                    if (file_exists($pages_dir . 'attendance.php')) {
                        include $pages_dir . 'attendance.php';
                    }
                    break;
                case 'holidays':
                    if (file_exists($pages_dir . 'class_holidays.php')) {
                        include $pages_dir . 'class_holidays.php';
                    }
                    break;
                default:
                    if (file_exists($pages_dir . 'dashboard.php')) {
                        include $pages_dir . 'dashboard.php';
                    }
            }
            ?>
        </div>
    </div>
<?php endif; ?>

<!-- FIXED: Changed jQuery CDN from code.jquery.com to cdnjs.cloudflare.com -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    // ============================================================
    // GLOBAL LOADING OVERLAY HANDLER
    // Prevents duplicate form submissions
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        const overlay = document.getElementById('globalLoadingOverlay');
        
        // Find all forms that should show loading overlay
        const forms = document.querySelectorAll('.submit-with-loading, form[method="POST"]');
        
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                // Don't show overlay for search/filter forms (GET method)
                if (form.method.toLowerCase() === 'post') {
                    // Show overlay
                    overlay.classList.add('active');
                    
                    // Disable all submit buttons in the form
                    const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
                    submitButtons.forEach(btn => {
                        btn.disabled = true;
                    });
                }
            });
        });
    });

    // ✨ NEW: Reload Page Data Function
    function reloadPageData() {
        const reloadBtn = document.querySelector('.btn-reload');
        const reloadIcon = reloadBtn.querySelector('i');
        const reloadToast = document.getElementById('reloadToast');
        
        // Add loading state
        reloadBtn.classList.add('loading');
        reloadBtn.disabled = true;
        
        // Get current URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const currentPage = urlParams.get('page') || 'dashboard';
        
        // Simulate data reload with a small delay for user feedback
        setTimeout(function() {
            // Reload the page without cache
            window.location.href = window.location.href.split('#')[0] + '&_t=' + new Date().getTime();
        }, 300);
        
        // Show toast notification
        setTimeout(function() {
            reloadToast.classList.add('show');
            setTimeout(function() {
                reloadToast.classList.remove('show');
            }, 2000);
        }, 400);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('adminSidebar');
        const overlay = document.getElementById('sidebarOverlay');

        if (!menuToggle || !sidebar || !overlay) return;

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            const icon = menuToggle.querySelector('i');
            if (sidebar.classList.contains('active')) {
                icon.className = 'fas fa-times';
            } else {
                icon.className = 'fas fa-bars';
            }
        }

        menuToggle.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);

        const navLinks = document.querySelectorAll('.admin-sidebar .nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    setTimeout(toggleSidebar, 200);
                }
            });
        });

        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                const icon = menuToggle.querySelector('i');
                if (icon) icon.className = 'fas fa-bars';
            }
        });
    });

    // ✨ FIXED: DataTables - Disable initial sorting to respect SQL ORDER BY
    $(document).ready(function() {
        $('.data-table').DataTable({
            pageLength: 10,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                ['10 rows', '25 rows', '50 rows', '100 rows', 'Show all']
            ],
            order: [], // Disable initial sorting - respect server-side SQL order
            language: {
                lengthMenu: '<i class="fas fa-list"></i> Display _MENU_ per page',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                infoEmpty: 'No entries available',
                infoFiltered: '(filtered from _MAX_ total entries)',
                search: '<i class="fas fa-search"></i>',
                searchPlaceholder: 'Search...',
                paginate: {
                    first: '<i class="fas fa-angle-double-left"></i>',
                    last: '<i class="fas fa-angle-double-right"></i>',
                    next: '<i class="fas fa-angle-right"></i>',
                    previous: '<i class="fas fa-angle-left"></i>'
                }
            },
            responsive: true
        });
    });
</script>
</body>
</html>