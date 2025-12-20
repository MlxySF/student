<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include config and new auth helper
require_once 'config.php';
require_once 'auth_helper.php';

// Include PHPMailer for admin notifications
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// Admin email configuration
define('ADMIN_EMAIL', 'chaichonghern@gmail.com');
define('ADMIN_NAME', 'Academy Admin');

// ============================================================
// EMAIL NOTIFICATION FUNCTIONS
// ============================================================

/**
 * Send admin notification for new payment upload
 */
function sendAdminPaymentNotification($paymentData) {
    $mail = new PHPMailer(true);
    try {
        error_log("[Admin Email] Sending payment notification to " . ADMIN_EMAIL);
        
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'chaichonghern@gmail.com';
        $mail->Password   = 'kyyj elhp dkdw gvki';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('noreply@wushusportacademy.com', 'Wushu Portal System');
        $mail->addAddress(ADMIN_EMAIL, ADMIN_NAME);

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = '💳 Payment Upload: ' . $paymentData['student_name'] . ' - RM ' . $paymentData['amount'];
        $mail->Body    = getAdminPaymentEmailHTML($paymentData);
        $mail->AltBody = "Payment Upload: {$paymentData['student_name']} paid RM {$paymentData['amount']}";

        $mail->send();
        error_log("[Admin Email] Successfully sent payment notification");
        return true;
    } catch (Exception $e) {
        error_log("[Admin Email] Error: {$mail->ErrorInfo}");
        return false;
    }
}

function getAdminPaymentEmailHTML($data) {
    $paymentType = !empty($data['invoice_number']) ? 'Invoice Payment' : 'Class Fee Payment';
    $paymentIcon = !empty($data['invoice_number']) ? '📝' : '🏫';
    
    $html = "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>New Payment Upload</title>
</head>
<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5;'>
    <div style='max-width: 650px; margin: 20px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
        <div style='background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; padding: 30px 24px; text-align: center;'>
            <h1 style='margin: 0 0 8px 0; font-size: 28px; font-weight: 700;'>💳 New Payment Uploaded</h1>
            <p style='margin: 0; font-size: 14px; opacity: 0.95;'>Pending Verification</p>
        </div>
        
        <div style='padding: 32px 24px; background: white;'>
            <div style='background: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; border-radius: 8px; margin-bottom: 24px;'>
                <p style='margin: 0; font-weight: 600; color: #92400e; font-size: 15px;'>⚠️ Action Required: Verify Payment Receipt</p>
                <p style='margin: 8px 0 0 0; font-size: 13px; color: #92400e;'>A student has uploaded a payment receipt. Please verify and approve/reject.</p>
            </div>
            
            <div style='background: #f8fafc; border-radius: 8px; padding: 20px; margin-bottom: 24px;'>
                <h3 style='margin: 0 0 16px 0; color: #1e293b; font-size: 18px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;'>{$paymentIcon} {$paymentType}</h3>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; font-weight: 600; width: 40%;'>Student Name:</td>
                        <td style='padding: 8px 0; color: #1e293b; font-weight: 600;'>{$data['student_name']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; font-weight: 600;'>Student ID:</td>
                        <td style='padding: 8px 0; color: #1e293b;'>{$data['student_id']}</td>
                    </tr>";
    
    if (!empty($data['invoice_number'])) {
        $html .= "
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; font-weight: 600;'>Invoice Number:</td>
                        <td style='padding: 8px 0; color: #1e293b;'>{$data['invoice_number']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; font-weight: 600;'>Invoice Description:</td>
                        <td style='padding: 8px 0; color: #1e293b;'>{$data['invoice_description']}</td>
                    </tr>";
    }
    
    $html .= "
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; font-weight: 600;'>Class:</td>
                        <td style='padding: 8px 0; color: #1e293b;'>{$data['class_code']} - {$data['class_name']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; font-weight: 600;'>Payment Month:</td>
                        <td style='padding: 8px 0; color: #1e293b;'>{$data['payment_month']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; font-weight: 600;'>Amount:</td>
                        <td style='padding: 8px 0; color: #059669; font-weight: 700; font-size: 20px;'>RM {$data['amount']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; font-weight: 600;'>Receipt File:</td>
                        <td style='padding: 8px 0; color: #1e293b;'>{$data['receipt_filename']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; font-weight: 600;'>File Size:</td>
                        <td style='padding: 8px 0; color: #1e293b;'>{$data['receipt_size']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; font-weight: 600;'>Upload Date:</td>
                        <td style='padding: 8px 0; color: #1e293b;'>{$data['upload_date']}</td>
                    </tr>
                </table>
            </div>";
    
    if (!empty($data['parent_name'])) {
        $html .= "
            <div style='background: #f0fdf4; border-radius: 8px; padding: 20px; margin-bottom: 24px;'>
                <h3 style='margin: 0 0 16px 0; color: #166534; font-size: 18px; border-bottom: 2px solid #bbf7d0; padding-bottom: 8px;'>👨‍👩‍👧 Parent Information</h3>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr>
                        <td style='padding: 8px 0; color: #166534; font-weight: 600; width: 40%;'>Parent Name:</td>
                        <td style='padding: 8px 0; color: #166534;'>{$data['parent_name']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #166534; font-weight: 600;'>Parent Email:</td>
                        <td style='padding: 8px 0; color: #166534;'><a href='mailto:{$data['parent_email']}' style='color: #059669; text-decoration: none;'>{$data['parent_email']}</a></td>
                    </tr>
                </table>
            </div>";
    }
    
    $html .= "
            <div style='background: #f1f5f9; border-left: 4px solid #3b82f6; padding: 20px; border-radius: 8px;'>
                <p style='margin: 0 0 16px 0; font-weight: 600; color: #1e293b; font-size: 16px;'>👉 Next Steps:</p>
                <ol style='margin: 0; padding-left: 20px; color: #475569; font-size: 14px; line-height: 1.8;'>
                    <li>Login to the <strong>Admin Portal</strong></li>
                    <li>Go to <strong>Payments</strong> section</li>
                    <li>View the uploaded receipt</li>
                    <li>Verify payment details match the receipt</li>
                    <li>Approve or reject the payment</li>";
    
    if (!empty($data['invoice_number'])) {
        $html .= "<li>If approved, the linked invoice will be marked as Paid</li>";
    }
    
    $html .= "
                </ol>
            </div>
        </div>
        
        <div style='text-align: center; padding: 24px; background: #f8fafc; color: #64748b; font-size: 13px; border-top: 1px solid #e2e8f0;'>
            <p style='margin: 0 0 8px 0; font-weight: 600; color: #1e293b; font-size: 15px;'>Wushu Sport Academy 武术体育学院</p>
            <p style='margin: 4px 0;'>Admin Portal System</p>
            <p style='margin: 16px 0 0 0; font-size: 11px; color: #94a3b8;'>This is an automated notification. Generated on " . date('Y-m-d H:i:s') . "</p>
        </div>
    </div>
</body>
</html>";

    return $html;
}

// ============================================================
// LEGACY HELPER FUNCTIONS (for backward compatibility)
// ============================================================

// Check if student is logged in and redirect if not
function redirectIfNotLoggedIn() {
    requireLogin();
}

// Get current logged-in student ID - now uses new auth system
if (!function_exists('getStudentId')) {
    function getStudentId() {
        return getActiveStudentId();
    }
}

// Validate receipt file upload
function validateReceiptFile($file) {
    $result = ['valid' => false, 'error' => ''];

    // Check file size (max 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB in bytes
    if ($file['size'] > $maxSize) {
        $result['error'] = 'File size must be less than 5MB.';
        return $result;
    }

    // Check file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        $result['error'] = 'Only JPG, PNG, and PDF files are allowed.';
        return $result;
    }

    $result['valid'] = true;
    return $result;
}

// Convert file to base64 for database storage
function fileToBase64($file) {
    if (!file_exists($file['tmp_name'])) {
        return false;
    }

    $fileData = file_get_contents($file['tmp_name']);
    if ($fileData === false) {
        return false;
    }

    $base64 = base64_encode($fileData);

    // Get MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    return [
        'data' => $base64,
        'mime' => $mimeType,
        'size' => $file['size']
    ];
}

// ============================================================
// AUTHENTICATION HANDLERS
// ============================================================

// Handle Login - UPDATED to handle rejection/pending status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Use new unified authentication
    $authResult = authenticateUser($email, $password, $pdo);

    if ($authResult['success']) {
        // Create session
        createLoginSession($authResult);
        
        // Set legacy session variables for backward compatibility
        $userData = $authResult['user_data'];
        $userType = $authResult['user_type'];
        
        if ($userType === 'student') {
            $_SESSION['student_id'] = $userData['id'];
            $_SESSION['student_name'] = $userData['full_name'];
        } else if ($userType === 'parent') {
            // For parents, set the first child as the active student
            $_SESSION['student_name'] = $authResult['children'][0]['full_name'] ?? 'Parent';
        }
        
        header('Location: index.php?page=dashboard');
        exit;
    } else {
        // Login failed - display appropriate error message
        $errorType = $authResult['error'] ?? 'unknown';
        $errorMessage = $authResult['message'] ?? 'An error occurred during login.';
        
        switch ($errorType) {
            case 'rejected':
                $_SESSION['error'] = $errorMessage;
                $_SESSION['show_register_button'] = true; // Flag to show registration button
                break;
            
            case 'pending':
                $_SESSION['error'] = $errorMessage;
                break;
            
            case 'no_registration':
                $_SESSION['error'] = $errorMessage;
                $_SESSION['show_register_button'] = true;
                break;
            
            case 'invalid_credentials':
            default:
                $_SESSION['error'] = $errorMessage;
                break;
        }
        
        header('Location: index.php?page=login');
        exit;
    }
}

// Handle Logout - use new function
if (isset($_GET['logout'])) {
    logoutUser();
}

// ============================================================
// FORM HANDLERS
// ============================================================

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    redirectIfNotLoggedIn();

    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    // Check if email is already used by another student
    $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
    $stmt->execute([$email, getActiveStudentId()]);

    if ($stmt->fetch()) {
        $_SESSION['error'] = "Email is already used by another student.";
    } else {
        $stmt = $pdo->prepare("UPDATE students SET full_name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->execute([$full_name, $email, $phone, getActiveStudentId()]);

        $_SESSION['student_name'] = $full_name;
        $_SESSION['success'] = "Profile updated successfully!";
    }

    header('Location: index.php?page=profile');
    exit;
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    redirectIfNotLoggedIn();

    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Get current student or parent password
    if (isStudent()) {
        $stmt = $pdo->prepare("SELECT password FROM students WHERE id = ?");
        $stmt->execute([getUserId()]);
    } else if (isParent()) {
        $stmt = $pdo->prepare("SELECT password FROM parent_accounts WHERE id = ?");
        $stmt->execute([getUserId()]);
    }
    
    $user = $stmt->fetch();

    if (!password_verify($current_password, $user['password'])) {
        $_SESSION['error'] = "Current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $_SESSION['error'] = "Password must be at least 6 characters.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        if (isStudent()) {
            $stmt = $pdo->prepare("UPDATE students SET password = ? WHERE id = ?");
        } else if (isParent()) {
            $stmt = $pdo->prepare("UPDATE parent_accounts SET password = ? WHERE id = ?");
        }
        
        $stmt->execute([$hashed_password, getUserId()]);
        $_SESSION['success'] = "Password changed successfully!";
    }

    header('Location: index.php?page=profile');
    exit;
}

// Handle Payment Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_payment') {
    redirectIfNotLoggedIn();

    // Determine payment type
    $invoice_id = !empty($_POST['invoice_id']) ? $_POST['invoice_id'] : null;
    $is_invoice_payment = !empty($invoice_id);

    if ($invoice_id) {
        $class_id = !empty($_POST['invoice_class_id']) ? $_POST['invoice_class_id'] : null;
        $amount = $_POST['invoice_amount'];
        $payment_month = !empty($_POST['invoice_payment_month']) ? $_POST['invoice_payment_month'] : date('M Y');
        $notes = '';

        if (!$class_id) {
            $stmt = $pdo->prepare("SELECT class_id FROM enrollments WHERE student_id = ? AND status = 'active' LIMIT 1");
            $stmt->execute([getActiveStudentId()]);
            $enrollment = $stmt->fetch();
            $class_id = $enrollment ? $enrollment['class_id'] : null;
        }

        if (!$class_id) {
            $_SESSION['error'] = "Unable to process invoice payment. Please ensure you're enrolled in at least one class.";
            header('Location: index.php?page=invoices');
            exit;
        }
    } else {
        $class_id = $_POST['class_id'];
        $amount = $_POST['amount'];
        $payment_month = $_POST['payment_month'];
        $notes = $_POST['notes'] ?? '';
    }

    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === 0) {
        $validation = validateReceiptFile($_FILES['receipt']);

        if (!$validation['valid']) {
            $_SESSION['error'] = $validation['error'];
            $redirect_page = $is_invoice_payment ? 'invoices' : 'payments';
            header('Location: index.php?page=' . $redirect_page);
            exit;
        }

        $receiptData = fileToBase64($_FILES['receipt']);

        if ($receiptData === false) {
            $_SESSION['error'] = "Failed to process receipt file.";
            $redirect_page = $is_invoice_payment ? 'invoices' : 'payments';
            header('Location: index.php?page=' . $redirect_page);
            exit;
        }

        $ext = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
        // FIX: Use getActiveStudentId() consistently instead of getStudentId()
        $filename = 'receipt_' . getActiveStudentId() . '_' . time() . '.' . $ext;

        // Get parent_account_id if this is a child account
        $parent_account_id = null;
        if (isStudent()) {
            $studentData = getActiveStudentData($pdo);
            $parent_account_id = $studentData['parent_account_id'];
        } else if (isParent()) {
            $parent_account_id = getUserId();
        }
        
        // Verify student_id exists before inserting
        $activeStudentId = getActiveStudentId();
        if (!$activeStudentId) {
            $_SESSION['error'] = "Unable to identify student account. Please log out and log in again.";
            $redirect_page = $is_invoice_payment ? 'invoices' : 'payments';
            header('Location: index.php?page=' . $redirect_page);
            exit;
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO payments (
                    student_id, 
                    class_id, 
                    amount, 
                    payment_month, 
                    receipt_filename,
                    receipt_data,
                    receipt_mime_type,
                    receipt_size,
                    admin_notes,
                    invoice_id,
                    parent_account_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $success = $stmt->execute([
                $activeStudentId, 
                $class_id, 
                $amount, 
                $payment_month, 
                $filename,
                $receiptData['data'],
                $receiptData['mime'],
                $receiptData['size'],
                $notes,
                $invoice_id,
                $parent_account_id
            ]);

            if ($success) {
                // Update invoice status if this is an invoice payment
                if ($invoice_id) {
                    $update = $pdo->prepare("UPDATE invoices SET status = 'pending' WHERE id = ?");
                    $update->execute([$invoice_id]);
                }
                
                // ==========================
                // Send admin notification email
                // NEW: Notify admin about payment upload
                // ==========================
                
                // Get student info
                $stmt = $pdo->prepare("SELECT full_name, student_id FROM students WHERE id = ?");
                $stmt->execute([$activeStudentId]);
                $student = $stmt->fetch();
                
                // Get class info
                $stmt = $pdo->prepare("SELECT class_code, class_name FROM classes WHERE id = ?");
                $stmt->execute([$class_id]);
                $class = $stmt->fetch();
                
                // Get invoice info if applicable
                $invoice_number = '';
                $invoice_description = '';
                if ($invoice_id) {
                    $stmt = $pdo->prepare("SELECT invoice_number, description FROM invoices WHERE id = ?");
                    $stmt->execute([$invoice_id]);
                    $invoice = $stmt->fetch();
                    $invoice_number = $invoice['invoice_number'] ?? '';
                    $invoice_description = $invoice['description'] ?? '';
                }
                
                // Get parent info if applicable
                $parent_name = '';
                $parent_email = '';
                if ($parent_account_id) {
                    $stmt = $pdo->prepare("SELECT full_name, email FROM parent_accounts WHERE id = ?");
                    $stmt->execute([$parent_account_id]);
                    $parent = $stmt->fetch();
                    $parent_name = $parent['full_name'] ?? '';
                    $parent_email = $parent['email'] ?? '';
                }
                
                // Format file size
                $file_size = $receiptData['size'];
                if ($file_size < 1024) {
                    $file_size_formatted = $file_size . ' B';
                } elseif ($file_size < 1024 * 1024) {
                    $file_size_formatted = number_format($file_size / 1024, 2) . ' KB';
                } else {
                    $file_size_formatted = number_format($file_size / (1024 * 1024), 2) . ' MB';
                }
                
                $adminNotificationData = [
                    'student_name' => $student['full_name'],
                    'student_id' => $student['student_id'],
                    'class_code' => $class['class_code'],
                    'class_name' => $class['class_name'],
                    'payment_month' => $payment_month,
                    'amount' => number_format($amount, 2),
                    'receipt_filename' => $filename,
                    'receipt_size' => $file_size_formatted,
                    'upload_date' => date('Y-m-d H:i:s'),
                    'invoice_number' => $invoice_number,
                    'invoice_description' => $invoice_description,
                    'parent_name' => $parent_name,
                    'parent_email' => $parent_email
                ];
                
                $adminEmailSent = sendAdminPaymentNotification($adminNotificationData);
                error_log("[Payment Upload] Admin notification email sent: " . ($adminEmailSent ? 'YES' : 'NO'));
                
                if ($invoice_id) {
                    $_SESSION['success'] = "Payment submitted successfully! Your invoice is now pending verification. Admin has been notified.";
                    header('Location: index.php?page=invoices&t=' . time());
                    exit;
                } else {
                    $_SESSION['success'] = "Payment uploaded successfully! Waiting for admin verification. Admin has been notified.";
                    header('Location: index.php?page=payments');
                    exit;
                }
            } else {
                $_SESSION['error'] = "Failed to save payment record.";
            }
        } catch (PDOException $e) {
            error_log("[Payment Upload Error] " . $e->getMessage());
            $_SESSION['error'] = "Database error: Unable to save payment. Please contact support if this persists.";
        }
    } else {
        $_SESSION['error'] = "Please select a receipt file.";
    }

    $redirect_page = $is_invoice_payment ? 'invoices' : 'payments';
    header('Location: index.php?page=' . $redirect_page);
    exit;
}

$page = $_GET['page'] ?? 'login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Student Portal</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #7c3aed;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-top: 0;
        }

        /* ============================================
           FIXED HEADER DESIGN
           ============================================ */

        .top-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 70px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
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
            height: 45px;
            width: auto;
            border-radius: 8px;
        }

        .school-logo .logo-placeholder {
            width: 45px;
            height: 45px;
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
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
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Child Selector for Parents */
        .child-selector {
            background: rgba(255,255,255,0.15);
            padding: 8px 15px;
            border-radius: 10px;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .child-selector:hover {
            background: rgba(255,255,255,0.25);
        }

        .child-selector select {
            background: transparent;
            border: none;
            color: white;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            padding: 0;
            outline: none;
        }

        .child-selector select option {
            background: #4f46e5;
            color: white;
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

        .sidebar {
            position: fixed;
            left: 0;
            top: 70px;
            width: 280px;
            height: calc(100vh - 70px);
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            z-index: 999;
            transition: left 0.3s ease;
            overflow-y: auto;
            padding: 20px 0;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 14px 25px;
            margin: 3px 0;
            border-radius: 0;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 4px solid #4f46e5;
        }

        .sidebar .nav-link i {
            width: 20px;
            font-size: 16px;
            text-align: center;
        }

        body.logged-in {
            padding-top: 70px;
        }

        .main-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            margin: 0;
            max-width: none;
        }

        .content-area {
            margin-left: 280px;
            padding: 30px;
            min-height: calc(100vh - 70px);
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 18px 25px;
            font-weight: 600;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 25px rgba(0,0,0,0.15);
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

        .stat-icon.bg-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stat-icon.bg-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
        .stat-icon.bg-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }
        .stat-icon.bg-danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }
        .stat-icon.bg-info { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; }

        .stat-content h3 {
            margin: 0;
            font-size: 32px;
            font-weight: bold;
            color: #1e293b;
        }

        .stat-content p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }

        .badge-status {
            padding: 8px 15px;
            font-size: 12px;
            font-weight: 600;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .login-container {
            max-width: 450px;
            margin: 100px auto;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }

        .btn-outline-primary {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background: transparent;
            padding: 10px 25px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-outline-primary:hover {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);
        }

        .login-card .border-top {
            border-color: rgba(0,0,0,0.1) !important;
        }

        @media (min-width: 769px) {
            .header-menu-btn {
                display: none;
            }

            .sidebar {
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

            .child-selector span {
                display: none;
            }

            .sidebar {
                left: -280px;
            }

            .sidebar.active {
                left: 0;
            }

            .content-area {
                margin-left: 0;
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

            .card-header {
                padding: 12px 15px;
                font-size: 14px;
            }

            .card-body {
                padding: 15px;
            }

            .btn {
                padding: 8px 15px;
                font-size: 14px;
            }

            h3.mb-4 {
                font-size: 20px;
                margin-bottom: 15px !important;
            }

            .form-control, .form-select {
                font-size: 14px;
            }

            .modal-dialog {
                margin: 10px;
            }
        }

        @media (max-width: 480px) {
            .top-header {
                height: 60px;
            }

            body.logged-in {
                padding-top: 60px;
            }
            
            .sidebar {
                top: 60px;
                height: calc(100vh - 60px);
            }

            .sidebar-overlay {
                top: 60px;
                height: calc(100vh - 60px);
            }

            .school-logo img,
            .school-logo .logo-placeholder {
                height: 38px;
                width: 38px;
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

            .btn {
                padding: 6px 12px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body<?php echo ($page !== 'login') ? ' class="logged-in"' : ''; ?>>

<?php if ($page === 'login'): ?>
    <!-- Login Page -->
    <div class="login-container animate__animated animate__fadeIn">
        <div class="login-card">
            <div class="text-center mb-4">
                <i class="fas fa-graduation-cap fa-4x text-primary mb-3"></i>
                <h2>Student Portal</h2>
                <p class="text-muted">Sign in to your account</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $_SESSION['error']; ?>
                    
                    <?php if (isset($_SESSION['show_register_button'])): ?>
                        <hr class="my-3">
                        <a href="pages/register.php" class="btn btn-outline-danger btn-sm w-100">
                            <i class="fas fa-pen-to-square"></i> Register New Application
                        </a>
                        <?php unset($_SESSION['show_register_button']); ?>
                    <?php endif; ?>
                    
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="action" value="login">
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" class="form-control form-control-lg" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" class="form-control form-control-lg" required>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="text-center mt-4 pt-3 border-top">
                <p class="text-muted mb-2">
                    <i class="fas fa-user-plus"></i> New Student?
                </p>
                <a href="pages/register.php" class="btn btn-outline-primary btn-lg w-100">
                    <i class="fas fa-pen-to-square"></i> Register for 2026 Wushu Training
                </a>
                <p class="text-muted mt-2" style="font-size: 12px;">
                    Complete your registration form and join our academy
                </p>
            </div>
        </div>
    </div>

<?php else: ?>
    <?php redirectIfNotLoggedIn(); ?>

    <!-- Fixed Top Header -->
    <div class="top-header">
        <div class="header-left">
            <button class="header-menu-btn" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>

            <a href="?page=dashboard" class="school-logo">
                <div class="logo-placeholder">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="logo-text">
                    <span class="logo-title">Wushu Academy</span>
                    <span class="logo-subtitle">Student Portal</span>
                </div>
            </a>
        </div>

        <div class="header-right">
            <?php if (isParent()): ?>
                <!-- Child Selector for Parents -->
                <div class="child-selector">
                    <i class="fas fa-child"></i>
                    <span>Viewing:</span>
                    <select onchange="window.location.href='?page=<?php echo $page; ?>&switch_child=' + this.value" class="form-select-sm">
                        <?php foreach (getParentChildren() as $child): ?>
                            <option value="<?php echo $child['id']; ?>" <?php echo ($child['id'] == getActiveStudentId()) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($child['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="header-user">
                <div class="header-user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="header-user-info">
                    <span class="header-user-name"><?php echo htmlspecialchars(getUserFullName()); ?></span>
                    <span class="header-user-role"><?php echo isParent() ? 'Parent Account' : 'Student'; ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar Navigation -->
    <div class="sidebar" id="sidebar">
        <nav class="nav flex-column">
            <a class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>" href="?page=dashboard">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a class="nav-link <?php echo $page === 'invoices' ? 'active' : ''; ?>" href="?page=invoices">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>My Invoices</span>
            </a>
            <a class="nav-link <?php echo $page === 'attendance' ? 'active' : ''; ?>" href="?page=attendance">
                <i class="fas fa-calendar-check"></i>
                <span>Attendance</span>
            </a>
            <a class="nav-link <?php echo $page === 'classes' ? 'active' : ''; ?>" href="?page=classes">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>My Classes</span>
            </a>
            <a class="nav-link <?php echo $page === 'profile' ? 'active' : ''; ?>" href="?page=profile">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
            <hr class="text-white mx-3">
            <a class="nav-link" href="?logout=1">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </nav>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <h3 class="mb-4">
            <i class="fas fa-<?php 
                echo $page === 'dashboard' ? 'home' : 
                    ($page === 'invoices' ? 'file-invoice-dollar' :
                    ($page === 'attendance' ? 'calendar-check' : 
                    ($page === 'classes' ? 'chalkboard-teacher' : 'user'))); 
            ?>"></i>
            <?php echo ucfirst($page); ?>
            <?php if (isParent()): ?>
                <small class="text-muted" style="font-size: 16px;">- <?php echo htmlspecialchars(getActiveStudentName()); ?></small>
            <?php endif; ?>
        </h3>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeInDown" role="alert">
                <i class="fas fa-check-circle"></i>
                <strong>Success!</strong> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeInDown" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <strong>Error!</strong> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php
        // Include pages
        $pages_dir = 'pages/';
        switch($page) {
            case 'dashboard':
                if (file_exists($pages_dir . 'dashboard.php')) {
                    include $pages_dir . 'dashboard.php';
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
            case 'classes':
                if (file_exists($pages_dir . 'classes.php')) {
                    include $pages_dir . 'classes.php';
                }
                break;
            case 'profile':
                if (file_exists($pages_dir . 'profile.php')) {
                    include $pages_dir . 'profile.php';
                }
                break;
            default:
                if (file_exists($pages_dir . 'dashboard.php')) {
                    include $pages_dir . 'dashboard.php';
                }
        }
        ?>
    </div>
<?php endif; ?>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // REMOVED: Auto-dismiss timeout for alerts
    // Alerts now only close when user clicks the X button

    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
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

        const navLinks = document.querySelectorAll('.sidebar .nav-link');
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
</script>
</body>
</html>