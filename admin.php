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
    
    // VERIFY PAYMENT - FIXED: Now sends email notification
    if ($_POST['action'] === 'verify_payment') {
        error_log("[Admin] Processing payment verification");
        
        $payment_id = $_POST['payment_id'];
        $invoice_id = $_POST['invoice_id'];
        $verification_status = $_POST['verification_status'];
        $admin_notes = $_POST['admin_notes'] ?? '';
        
        error_log("[Admin] Payment ID: $payment_id, Status: $verification_status");
        
        // Update payment verification
        $stmt = $pdo->prepare("
            UPDATE payments 
            SET verification_status = ?, admin_notes = ? 
            WHERE id = ?
        ");
        $stmt->execute([$verification_status, $admin_notes, $payment_id]);
        
        error_log("[Admin] Payment record updated");
        
        // If verified, update invoice status to paid
        if ($verification_status === 'verified') {
            $update_invoice = $pdo->prepare("
                UPDATE invoices 
                SET status = 'paid', paid_date = NOW() 
                WHERE id = ?
            ");
            $update_invoice->execute([$invoice_id]);
            
            error_log("[Admin] Invoice marked as PAID");
            
            // FIXED: Send approval email to parent
            require_once 'send_payment_approval_email.php';
            error_log("[Admin] Attempting to send approval email...");
            $emailSent = sendPaymentApprovalEmail($payment_id, 'verified', $admin_notes);
            
            if ($emailSent) {
                error_log("[Admin] ✅ Approval email sent successfully");
                $_SESSION['success'] = "Payment verified, invoice marked as PAID, and approval email sent to parent!";
            } else {
                error_log("[Admin] ❌ Failed to send approval email");
                $_SESSION['success'] = "Payment verified and invoice marked as PAID! (Note: Email notification failed to send)";
            }
        } else {
            // If rejected, set invoice back to unpaid
            $update_invoice = $pdo->prepare("
                UPDATE invoices 
                SET status = 'unpaid' 
                WHERE id = ?
            ");
            $update_invoice->execute([$invoice_id]);
            
            error_log("[Admin] Invoice marked as UNPAID");
            
            // FIXED: Send rejection email to parent
            require_once 'send_payment_approval_email.php';
            error_log("[Admin] Attempting to send rejection email...");
            $emailSent = sendPaymentApprovalEmail($payment_id, 'rejected', $admin_notes);
            
            if ($emailSent) {
                error_log("[Admin] ✅ Rejection email sent successfully");
                $_SESSION['success'] = "Payment rejected and rejection email sent to parent!";
            } else {
                error_log("[Admin] ❌ Failed to send rejection email");
                $_SESSION['success'] = "Payment rejected! (Note: Email notification failed to send)";
            }
        }
        
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
        /* ... (keeping all the existing styles unchanged) ... */
    </style>
</head>
<body<?php echo ($page !== 'login') ? ' class="logged-in"' : ''; ?>>

<!-- ✨ NEW: Reload Toast Notification -->
<div class="reload-toast" id="reloadToast">
    <i class="fas fa-check-circle text-success"></i>
    <span>Data refreshed successfully!</span>
</div>

<?php if ($page === 'login'): ?>
    <!-- ... (login page unchanged) ... ?>
<?php else: ?>
    <!-- ... (admin panel unchanged) ... ?>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    /* ... (scripts unchanged) ... */
</script>
</body>
</html>
<?php ob_end_flush(); ?>