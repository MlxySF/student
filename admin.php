<?php
// admin.php - Complete Admin Panel
ob_start();
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
        
        $stmt = $pdo->prepare("
            INSERT INTO invoices (student_id, invoice_number, invoice_type, class_id, description, amount, due_date, payment_month, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'unpaid', NOW())
        ");
        
        if ($stmt->execute([$student_id, $invoice_number, $invoice_type, $class_id, $description, $amount, $due_date, $payment_month])) {
            $_SESSION['success'] = "Invoice created successfully!";
        } else {
            $_SESSION['error'] = "Failed to create invoice.";
        }
        
        header('Location: admin.php?page=invoices');
        exit;
    }
    
    // GENERATE MONTHLY INVOICES
    if ($_POST['action'] === 'generate_monthly_invoices') {
        $current_month = date('M Y'); // e.g., "Dec 2025"
        $due_date = date('Y-m-10'); // 10th of current month
        $generated_count = 0;
        
        // Get all active enrollments
        $stmt = $pdo->query("
            SELECT DISTINCT e.student_id, e.class_id, c.monthly_fee, s.student_id as student_code, s.full_name
            FROM enrollments e
            JOIN students s ON e.student_id = s.id
            JOIN classes c ON e.class_id = c.id
            WHERE e.status = 'active' AND c.monthly_fee > 0
        ");
        $enrollments = $stmt->fetchAll();
        
        foreach ($enrollments as $enrollment) {
            // Check if invoice already exists for this month
            $check_stmt = $pdo->prepare("
                SELECT id FROM invoices 
                WHERE student_id = ? 
                AND class_id = ? 
                AND invoice_type = 'monthly_fee' 
                AND payment_month = ?
            ");
            $check_stmt->execute([$enrollment['student_id'], $enrollment['class_id'], $current_month]);
            
            if ($check_stmt->rowCount() == 0) {
                // Generate invoice number
                $invoice_number = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                $description = "Monthly Fee - $current_month";
                
                $insert_stmt = $pdo->prepare("
                    INSERT INTO invoices (student_id, invoice_number, invoice_type, class_id, description, amount, due_date, payment_month, status, created_at) 
                    VALUES (?, ?, 'monthly_fee', ?, ?, ?, ?, ?, 'unpaid', NOW())
                ");
                
                if ($insert_stmt->execute([
                    $enrollment['student_id'],
                    $invoice_number,
                    $enrollment['class_id'],
                    $description,
                    $enrollment['monthly_fee'],
                    $due_date,
                    $current_month
                ])) {
                    $generated_count++;
                }
            }
        }
        
        $_SESSION['success'] = "Generated $generated_count monthly invoices for $current_month!";
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
        
        $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
        
        if ($stmt->execute([$invoice_id])) {
            $_SESSION['success'] = "Invoice deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete invoice.";
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
        
        error_log("[admin.php verify_payment] Starting - Payment ID: {$payment_id}, Status: {$verification_status}");
        
        // Update payment verification
        $stmt = $pdo->prepare("
            UPDATE payments 
            SET verification_status = ?, admin_notes = ?, verified_date = NOW(), verified_by = ? 
            WHERE id = ?
        ");
        $stmt->execute([$verification_status, $admin_notes, $_SESSION['admin_id'] ?? null, $payment_id]);
        
        // If verified, update invoice status to paid
        if ($verification_status === 'verified') {
            $update_invoice = $pdo->prepare("
                UPDATE invoices 
                SET status = 'paid', paid_date = NOW() 
                WHERE id = ?
            ");
            $update_invoice->execute([$invoice_id]);
            $_SESSION['success'] = "Payment verified and invoice marked as PAID!";
        } else {
            // If rejected, set invoice back to unpaid
            $update_invoice = $pdo->prepare("
                UPDATE invoices 
                SET status = 'unpaid' 
                WHERE id = ?
            ");
            $update_invoice->execute([$invoice_id]);
            $_SESSION['success'] = "Payment rejected!";
        }
        
        // ✨ FIXED: Pass $pdo as first parameter
        error_log("[admin.php verify_payment] Attempting to send email notification");
        require_once 'send_payment_approval_email.php';
        try {
            $emailSent = sendPaymentApprovalEmail($pdo, $payment_id, $verification_status, $admin_notes);
            error_log("[admin.php verify_payment] Email function returned: " . ($emailSent ? 'true' : 'false'));
            
            if ($emailSent && $verification_status === 'verified') {
                $_SESSION['success'] .= " Approval email with PDF receipt sent to parent.";
                error_log("[admin.php verify_payment] Approval email sent successfully");
            } else if ($emailSent && $verification_status === 'rejected') {
                $_SESSION['success'] .= " Rejection notification sent to parent.";
                error_log("[admin.php verify_payment] Rejection email sent successfully");
            } else {
                error_log("[admin.php verify_payment] Email was not sent (emailSent = false)");
            }
        } catch (Exception $e) {
            error_log("[admin.php verify_payment] Email error: " . $e->getMessage());
            error_log("[admin.php verify_payment] Stack trace: " . $e->getTraceAsString());
            $_SESSION['success'] .= " (Note: Email notification could not be sent)";
        }
        
        header('Location: admin.php?page=invoices');
        exit;
    }
}

$page = $_GET['page'] ?? 'login';
?>