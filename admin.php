<?php
// admin.php - Complete Admin Panel
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

// Load PHPMailer for email sending
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

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
        
        error_log("[admin.php] verify_payment action triggered");
        error_log("[admin.php] Payment ID: {$payment_id}, Status: {$verification_status}");
        
        // Update payment verification
        $stmt = $pdo->prepare("
            UPDATE payments 
            SET verification_status = ?, admin_notes = ? 
            WHERE id = ?
        ");
        $stmt->execute([$verification_status, $admin_notes, $payment_id]);
        
        // If verified, update invoice status to paid
        if ($verification_status === 'verified') {
            $update_invoice = $pdo->prepare("
                UPDATE invoices 
                SET status = 'paid', paid_date = NOW() 
                WHERE id = ?
            ");
            $update_invoice->execute([$invoice_id]);
            
            // ✅ SEND APPROVAL EMAIL WITH PDF
            error_log("[admin.php] Attempting to send approval email");
            try {
                // Get payment details
                $sql = "
                    SELECT 
                        p.id as payment_id,
                        p.amount,
                        p.payment_month,
                        s.full_name as student_name,
                        s.student_id as student_number,
                        c.class_name,
                        c.class_code,
                        i.invoice_number,
                        i.description as invoice_description,
                        pa.email as parent_email,
                        pa.full_name as parent_name
                    FROM payments p
                    JOIN students s ON p.student_id = s.id
                    LEFT JOIN classes c ON p.class_id = c.id
                    LEFT JOIN invoices i ON p.invoice_id = i.id
                    LEFT JOIN parent_accounts pa ON p.parent_account_id = pa.id
                    WHERE p.id = ?
                ";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$payment_id]);
                $payment = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($payment && !empty($payment['parent_email'])) {
                    $mail = new PHPMailer(true);
                    
                    // SMTP Configuration
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'chaichonghern@gmail.com';
                    $mail->Password   = 'kyyj elhp dkdw gvki';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    $mail->CharSet    = 'UTF-8';
                    
                    $mail->setFrom('noreply@wushusportacademy.com', 'Wushu Sport Academy');
                    $mail->addAddress($payment['parent_email'], $payment['parent_name']);
                    $mail->addReplyTo('chaichonghern@gmail.com', 'Wushu Sport Academy');
                    
                    $mail->isHTML(true);
                    $mail->Subject = '✅ Payment Approved - ' . $payment['student_name'];
                    $mail->Body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                        <div style='background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; padding: 30px; text-align: center; border-radius: 12px 12px 0 0;'>
                            <h1 style='margin: 0; font-size: 28px;'>✅ Payment Approved!</h1>
                        </div>
                        <div style='background: white; padding: 32px; border-radius: 0 0 12px 12px;'>
                            <p>Dear Parent/Guardian,</p>
                            <p>Your payment for <strong>{$payment['student_name']}</strong> has been approved!</p>
                            <div style='background: #f8fafc; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                                <p><strong>Receipt Number:</strong> {$payment['invoice_number']}</p>
                                <p><strong>Student:</strong> {$payment['student_name']}</p>
                                <p><strong>Class:</strong> {$payment['class_name']}</p>
                                <p><strong>Amount:</strong> RM " . number_format($payment['amount'], 2) . "</p>
                                <p><strong>Status:</strong> <span style='color: #059669;'>APPROVED</span></p>
                            </div>
                            <p>Thank you for your payment!</p>
                            <p><strong>Wushu Sport Academy</strong></p>
                        </div>
                    </div>
                    ";
                    
                    $mail->send();
                    error_log("[admin.php] Email sent successfully to {$payment['parent_email']}");
                    $_SESSION['success'] = "Payment verified, invoice marked as PAID, and approval email sent!";
                } else {
                    error_log("[admin.php] Cannot send email - no parent email found");
                    $_SESSION['success'] = "Payment verified and invoice marked as PAID! (Email not sent - no parent email)"; 
                }
            } catch (Exception $e) {
                error_log("[admin.php] Email error: " . $e->getMessage());
                $_SESSION['success'] = "Payment verified and invoice marked as PAID! (Email failed: " . $e->getMessage() . ")";
            }
            
        } else {
            // If rejected, set invoice back to unpaid AND SEND REJECTION EMAIL
            $update_invoice = $pdo->prepare("
                UPDATE invoices 
                SET status = 'unpaid' 
                WHERE id = ?
            ");
            $update_invoice->execute([$invoice_id]);
            
            // ✅ SEND REJECTION EMAIL
            error_log("[admin.php] Attempting to send rejection email");
            try {
                // Get payment details
                $sql = "
                    SELECT 
                        p.id as payment_id,
                        p.amount,
                        p.payment_month,
                        s.full_name as student_name,
                        c.class_name,
                        i.invoice_number,
                        pa.email as parent_email,
                        pa.full_name as parent_name
                    FROM payments p
                    JOIN students s ON p.student_id = s.id
                    LEFT JOIN classes c ON p.class_id = c.id
                    LEFT JOIN invoices i ON p.invoice_id = i.id
                    LEFT JOIN parent_accounts pa ON p.parent_account_id = pa.id
                    WHERE p.id = ?
                ";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$payment_id]);
                $payment = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($payment && !empty($payment['parent_email'])) {
                    $mail = new PHPMailer(true);
                    
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'chaichonghern@gmail.com';
                    $mail->Password   = 'kyyj elhp dkdw gvki';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    $mail->CharSet    = 'UTF-8';
                    
                    $mail->setFrom('noreply@wushusportacademy.com', 'Wushu Sport Academy');
                    $mail->addAddress($payment['parent_email'], $payment['parent_name']);
                    
                    $mail->isHTML(true);
                    $mail->Subject = '⚠️ Payment Verification Required - ' . $payment['student_name'];
                    $mail->Body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                        <div style='background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 30px; text-align: center; border-radius: 12px 12px 0 0;'>
                            <h1 style='margin: 0; font-size: 28px;'>⚠️ Payment Rejected</h1>
                        </div>
                        <div style='background: white; padding: 32px; border-radius: 0 0 12px 12px;'>
                            <p>Dear Parent/Guardian,</p>
                            <p>Your payment for <strong>{$payment['student_name']}</strong> requires resubmission.</p>
                            <div style='background: #fef2f2; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ef4444;'>
                                <p><strong>Student:</strong> {$payment['student_name']}</p>
                                <p><strong>Class:</strong> {$payment['class_name']}</p>
                                <p><strong>Amount:</strong> RM " . number_format($payment['amount'], 2) . "</p>
                                <p><strong>Reason:</strong> {$admin_notes}</p>
                            </div>
                            <p>Please login to the portal and upload a clear payment receipt.</p>
                            <p><strong>Wushu Sport Academy</strong></p>
                        </div>
                    </div>
                    ";
                    
                    $mail->send();
                    error_log("[admin.php] Rejection email sent to {$payment['parent_email']}");
                    $_SESSION['success'] = "Payment rejected and notification email sent!";
                } else {
                    $_SESSION['success'] = "Payment rejected! (Email not sent - no parent email)";
                }
            } catch (Exception $e) {
                error_log("[admin.php] Email error: " . $e->getMessage());
                $_SESSION['success'] = "Payment rejected! (Email failed: " . $e->getMessage() . ")";
            }
        }
        
        header('Location: admin.php?page=invoices');
        exit;
    }
}

$page = $_GET['page'] ?? 'login';
?>