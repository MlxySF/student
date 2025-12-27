<?php
opcache_reset();
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include config, security layer, auth helper, and file helper
require_once 'config.php';
require_once 'security.php';
require_once 'auth_helper.php';
require_once 'file_helper.php'; // NEW: File storage helper

// Include PHPMailer for admin notifications
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// Admin email configuration
define('ADMIN_EMAIL', 'admin@wushusportacademy.com');
define('ADMIN_NAME', 'Wushu Sport Academy');

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
        $mail->isSMTP();
        $mail->Host       = 'mail.wushusportacademy.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'admin@wushusportacademy.com';
        $mail->Password   = 'P1}tKwojKgl0vdMv';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('admin@wushusportacademy.com', 'Wushu Sport Academy');
        $mail->addAddress(ADMIN_EMAIL, ADMIN_NAME);
        $mail->addReplyTo('admin@wushusportacademy.com', 'Wushu Sport Academy');

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
    
    if (!empty($data['class_name'])) {
        $html .= "
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; font-weight: 600;'>Class:</td>
                        <td style='padding: 8px 0; color: #1e293b;'>{$data['class_code']} - {$data['class_name']}</td>
                    </tr>";
    }
    
    $html .= "
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

function redirectIfNotLoggedIn() {
    requireLogin();
}

if (!function_exists('getStudentId')) {
    function getStudentId() {
        return getActiveStudentId();
    }
}

// ============================================================
// AUTHENTICATION HANDLERS
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    verifyCSRF();
    
    $email = sanitizeEmail($_POST['email']);
    $password = $_POST['password'];
    
    if (!isValidEmail($email)) {
        $_SESSION['error'] = 'Invalid email format.';
        header('Location: index.php?page=login');
        exit;
    }
    
    $identifier = $email . '_' . $_SERVER['REMOTE_ADDR'];
    
    if (isRateLimited($identifier, 5, 300)) {
        $_SESSION['error'] = 'Too many login attempts. Please try again in 5 minutes.';
        header('Location: index.php?page=login');
        exit;
    }

    $authResult = authenticateUser($email, $password, $pdo);

    if ($authResult['success']) {
        clearRateLimit($identifier);
        createLoginSession($authResult);
        
        $userData = $authResult['user_data'];
        $userType = $authResult['user_type'];
        
        if ($userType === 'student') {
            $_SESSION['student_id'] = $userData['id'];
            $_SESSION['student_name'] = $userData['full_name'];
        } else if ($userType === 'parent') {
            $_SESSION['student_name'] = $authResult['children'][0]['full_name'] ?? 'Parent';
        }
        
        header('Location: index.php?page=dashboard');
        exit;
    } else {
        $errorType = $authResult['error'] ?? 'unknown';
        $errorMessage = $authResult['message'] ?? 'An error occurred during login.';
        
        switch ($errorType) {
            case 'rejected':
                $_SESSION['error'] = $errorMessage;
                $_SESSION['show_register_button'] = true;
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

if (isset($_GET['logout'])) {
    logoutUser();
}

// ============================================================
// FORM HANDLERS
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    redirectIfNotLoggedIn();
    verifyCSRF();

    $full_name = sanitizeString($_POST['full_name']);
    $email = sanitizeEmail($_POST['email']);
    $phone = sanitizeString($_POST['phone']);
    
    if (!isValidEmail($email)) {
        $_SESSION['error'] = 'Invalid email format.';
        header('Location: index.php?page=profile');
        exit;
    }
    
    if (!empty($phone) && !isValidPhone($phone)) {
        $_SESSION['error'] = 'Invalid phone number format.';
        header('Location: index.php?page=profile');
        exit;
    }

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    redirectIfNotLoggedIn();
    verifyCSRF();

    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $passwordCheck = isStrongPassword($new_password, 6);
    if (!$passwordCheck['valid']) {
        $_SESSION['error'] = $passwordCheck['error'];
        header('Location: index.php?page=profile');
        exit;
    }

    if (isStudent()) {
        $stmt = $pdo->prepare("SELECT password FROM students WHERE id = ?");
        $stmt->execute([getUserId()]);
    } else if (isParent()) {
        $stmt = $pdo->prepare("SELECT password FROM parent_accounts WHERE id = ?");
        $stmt->execute([getUserId()]);
    }
    
    $user = $stmt->fetch();

    if (!verifyPassword($current_password, $user['password'])) {
        $_SESSION['error'] = "Current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = "New passwords do not match.";
    } else {
        $hashed_password = hashPassword($new_password);
        
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


// Handle Payment Upload - NOW USING FILE STORAGE WITH CASH PAYMENT SUPPORT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_payment') {
    redirectIfNotLoggedIn();
    verifyCSRF();

    $invoice_id = !empty($_POST['invoice_id']) ? sanitizeInt($_POST['invoice_id']) : null;
    $is_invoice_payment = !empty($invoice_id);
    $payment_date = !empty($_POST['payment_date']) ? sanitizeString($_POST['payment_date']) : date('Y-m-d');
    
    // Get payment method (default to bank_transfer for backward compatibility)
    $payment_method = isset($_POST['payment_method']) ? sanitizeString($_POST['payment_method']) : 'bank_transfer';
    $is_cash_payment = ($payment_method === 'cash');

    if ($invoice_id) {
        $class_id = !empty($_POST['invoice_class_id']) ? sanitizeInt($_POST['invoice_class_id']) : null;
        $amount = sanitizeFloat($_POST['invoice_amount']);
        $payment_month = sanitizeString($_POST['invoice_payment_month'] ?? date('M Y'));
        $notes = '';

        if (!$class_id) {
            $stmt = $pdo->prepare("SELECT class_id FROM enrollments WHERE student_id = ? AND status = 'active' LIMIT 1");
            $stmt->execute([getActiveStudentId()]);
            $enrollment = $stmt->fetch();
            $class_id = $enrollment ? $enrollment['class_id'] : null;
        }
    } else {
        $class_id = sanitizeInt($_POST['class_id']);
        $amount = sanitizeFloat($_POST['amount']);
        $payment_month = sanitizeString($_POST['payment_month']);
        $notes = sanitizeString($_POST['notes'] ?? '');
    }
    
    if (!isValidAmount($amount)) {
        $_SESSION['error'] = 'Invalid payment amount.';
        $redirect_page = $is_invoice_payment ? 'invoices' : 'payments';
        header('Location: index.php?page=' . $redirect_page);
        exit;
    }

    // Initialize receipt variables
    $uploadResult = null;
    $receipt_path = null;
    $receipt_filename = null;
    $receipt_size = null;

    // Only process file upload if NOT cash payment
    if (!$is_cash_payment) {
        // Validate receipt file upload for bank transfers
        if (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== 0) {
            $_SESSION['error'] = "Please select a receipt file for bank transfer payments.";
            $redirect_page = $is_invoice_payment ? 'invoices' : 'payments';
            header('Location: index.php?page=' . $redirect_page);
            exit;
        }

        $fileValidation = isValidFileUpload($_FILES['receipt']);
        
        if (!$fileValidation['valid']) {
            $_SESSION['error'] = $fileValidation['error'];
            $redirect_page = $is_invoice_payment ? 'invoices' : 'payments';
            header('Location: index.php?page=' . $redirect_page);
            exit;
        }

        // Process file upload
        $activeStudentId = !empty($_POST['student_account_id']) ? 
                           intval($_POST['student_account_id']) : 
                           getActiveStudentId();
        
        if (!$activeStudentId) {
            $_SESSION['error'] = "Unable to identify student account. Please log out and log in again.";
            $redirect_page = $is_invoice_payment ? 'invoices' : 'payments';
            header('Location: index.php?page=' . $redirect_page);
            exit;
        }

        // Get student name for filename
        $stmt = $pdo->prepare("SELECT full_name FROM students WHERE id = ?");
        $stmt->execute([$activeStudentId]);
        $studentData = $stmt->fetch();
        $studentName = $studentData['full_name'] ?? 'Unknown';

        // Get invoice NUMBER (not ID) if this is an invoice payment
        $invoiceNumber = '';
        if ($invoice_id) {
            $stmt = $pdo->prepare("SELECT invoice_number FROM invoices WHERE id = ?");
            $stmt->execute([$invoice_id]);
            $invoiceData = $stmt->fetch();
            $invoiceNumber = $invoiceData['invoice_number'] ?? '';
        }

        // Save file
        $uploadResult = saveUploadedFile(
            $_FILES['receipt'],
            'payment_receipts',
            'receipt',
            $activeStudentId,
            $studentName,
            $invoiceNumber
        );

        if (!$uploadResult['success']) {
            $_SESSION['error'] = "Failed to save receipt: " . $uploadResult['error'];
            $redirect_page = $is_invoice_payment ? 'invoices' : 'payments';
            header('Location: index.php?page=' . $redirect_page);
            exit;
        }

        $receipt_path = $uploadResult['path'];
        $receipt_filename = $uploadResult['filename'];
        $receipt_size = $uploadResult['size'];
    } else {
        // Cash payment - use student ID for identification
        $activeStudentId = !empty($_POST['student_account_id']) ? 
                           intval($_POST['student_account_id']) : 
                           getActiveStudentId();
        
        if (!$activeStudentId) {
            $_SESSION['error'] = "Unable to identify student account. Please log out and log in again.";
            $redirect_page = $is_invoice_payment ? 'invoices' : 'payments';
            header('Location: index.php?page=' . $redirect_page);
            exit;
        }
    }

    $parent_account_id = null;
    if (isStudent()) {
        $studentData = getActiveStudentData($pdo);
        $parent_account_id = $studentData['parent_account_id'];
    } else if (isParent()) {
        $parent_account_id = getUserId();
    }

    try {
        // Check if payment already exists for this invoice
        $existing_payment_id = null;
        $old_receipt_path = null;
        
        if ($invoice_id) {
            $check_stmt = $pdo->prepare("
                SELECT id, receipt_path 
                FROM payments 
                WHERE invoice_id = ? AND student_id = ?
                ORDER BY id DESC 
                LIMIT 1
            ");
            $check_stmt->execute([$invoice_id, $activeStudentId]);
            $existing_payment = $check_stmt->fetch();
            
            if ($existing_payment) {
                $existing_payment_id = $existing_payment['id'];
                $old_receipt_path = $existing_payment['receipt_path'];
            }
        }

        if ($existing_payment_id) {
            // UPDATE existing payment
            $stmt = $pdo->prepare("
                UPDATE payments SET
                    class_id = ?,
                    amount = ?,
                    payment_month = ?,
                    payment_date = ?,
                    receipt_path = ?,
                    receipt_filename = ?,
                    receipt_size = ?,
                    payment_method = ?,
                    upload_date = NOW(),
                    verification_status = 'pending',
                    verified_by = NULL,
                    verified_date = NULL,
                    admin_notes = ''
                WHERE id = ?
            ");

            $success = $stmt->execute([
                $class_id,
                $amount,
                $payment_month,
                $payment_date,
                $receipt_path,
                $receipt_filename,
                $receipt_size,
                $payment_method,
                $existing_payment_id
            ]);
            
            // Delete old receipt file if update successful and not cash payment
            if ($success && !$is_cash_payment && $old_receipt_path && file_exists($old_receipt_path)) {
                deleteFile($old_receipt_path);
            }
            
            $payment_action = 'resubmitted';
        } else {
            // INSERT new payment
            $stmt = $pdo->prepare("
                INSERT INTO payments (
                    student_id, 
                    class_id, 
                    amount, 
                    payment_month,
                    payment_date,
                    receipt_path,
                    receipt_filename,
                    receipt_size,
                    payment_method,
                    admin_notes,
                    invoice_id,
                    parent_account_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $success = $stmt->execute([
                $activeStudentId, 
                $class_id,
                $amount, 
                $payment_month,
                $payment_date,
                $receipt_path,
                $receipt_filename,
                $receipt_size,
                $payment_method,
                $notes,
                $invoice_id,
                $parent_account_id
            ]);
            
            $payment_action = 'submitted';
        }

        if ($success) {
            if ($invoice_id) {
                $update = $pdo->prepare("UPDATE invoices SET status = 'pending' WHERE id = ?");
                $update->execute([$invoice_id]);
            }
            
            // Send admin notification email
            $stmt = $pdo->prepare("SELECT full_name, student_id FROM students WHERE id = ?");
            $stmt->execute([$activeStudentId]);
            $student = $stmt->fetch();
            
            $class_code = 'N/A';
            $class_name = 'No Class Assigned';
            if ($class_id) {
                $stmt = $pdo->prepare("SELECT class_code, class_name FROM classes WHERE id = ?");
                $stmt->execute([$class_id]);
                $class = $stmt->fetch();
                if ($class) {
                    $class_code = $class['class_code'];
                    $class_name = $class['class_name'];
                }
            }
            
            $invoice_number = '';
            $invoice_description = '';
            if ($invoice_id) {
                $stmt = $pdo->prepare("SELECT invoice_number, description FROM invoices WHERE id = ?");
                $stmt->execute([$invoice_id]);
                $invoice = $stmt->fetch();
                $invoice_number = $invoice['invoice_number'] ?? '';
                $invoice_description = $invoice['description'] ?? '';
            }
            
            $parent_name = '';
            $parent_email = '';
            if ($parent_account_id) {
                $stmt = $pdo->prepare("SELECT full_name, email FROM parent_accounts WHERE id = ?");
                $stmt->execute([$parent_account_id]);
                $parent = $stmt->fetch();
                $parent_name = $parent['full_name'] ?? '';
                $parent_email = $parent['email'] ?? '';
            }
            
            $file_size_formatted = $is_cash_payment ? 'N/A (Cash Payment)' : formatFileSize($receipt_size);
            $receipt_filename_display = $is_cash_payment ? 'N/A (Cash Payment)' : $receipt_filename;
            
            $adminNotificationData = [
                'student_name' => $student['full_name'],
                'student_id' => $student['student_id'],
                'class_code' => $class_code,
                'class_name' => $class_name,
                'payment_month' => $payment_month,
                'amount' => number_format($amount, 2),
                'receipt_filename' => $receipt_filename_display,
                'receipt_size' => $file_size_formatted,
                'upload_date' => date('Y-m-d H:i:s'),
                'invoice_number' => $invoice_number,
                'invoice_description' => $invoice_description,
                'parent_name' => $parent_name,
                'parent_email' => $parent_email
            ];
            
            sendAdminPaymentNotification($adminNotificationData);
            
            if ($invoice_id) {
                $payment_type = $is_cash_payment ? 'Cash payment' : 'Payment';
                $success_message = $payment_action === 'resubmitted' 
                    ? "{$payment_type} resubmitted successfully! Your invoice is now pending verification."
                    : "{$payment_type} submitted successfully! Your invoice is now pending verification.";
                $_SESSION['success'] = $success_message;
                header('Location: index.php?page=invoices&t=' . time());
                exit;
            } else {
                $_SESSION['success'] = "Payment uploaded successfully! Waiting for admin verification.";
                header('Location: index.php?page=payments');
                exit;
            }
        } else {
            // Delete uploaded file if database insert fails (only if not cash payment)
            if (!$is_cash_payment && $receipt_path) {
                deleteFile($receipt_path);
            }
            $_SESSION['error'] = "Failed to save payment record.";
        }
    } catch (PDOException $e) {
        // Delete uploaded file on error (only if not cash payment)
        if (!$is_cash_payment && $receipt_path) {
            deleteFile($receipt_path);
        }
        error_log("[Payment Upload Error] " . $e->getMessage());
        $_SESSION['error'] = "Database error: Unable to save payment. Please contact support if this persists.";
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
    <title><?php echo SITE_NAME; ?></title>
    
    <link rel="icon" type="image/png" href="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/Wushu+Sport+Academy+Circle+Yellow.png">
    <link rel="shortcut icon" type="image/png" href="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/Wushu+Sport+Academy+Circle+Yellow.png">
    <link rel="apple-touch-icon" href="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/Wushu+Sport+Academy+Circle+Yellow.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Noto+Sans+SC:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #7c3aed;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
        }

        * {
            font-family: 'Inter', 'Noto Sans SC', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            font-family: 'Inter', 'Noto Sans SC', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-top: 0;
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
            border-top: 6px solid #4f46e5;
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
            font-weight: 500;
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
            font-weight: 700;
            color: #1e293b;
        }

        .stat-content p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
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
            font-weight: 600;
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

        .login-card h2 {
            font-weight: 700;
        }

        .login-logo {
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
            display: block;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
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
            
            .sidebar {
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

            .btn {
                padding: 6px 12px;
                font-size: 13px;
            }

            .reload-toast {
                top: 70px;
                right: 10px;
                left: 10px;
                font-size: 14px;
            }
        }
        /* ========================================
   FLOATING TUTORIAL BUTTON
   ======================================== */
.floating-tutorial-btn {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    padding: 15px 20px;
    border-radius: 50px;
    box-shadow: 0 8px 30px rgba(245, 158, 11, 0.4);
    cursor: pointer;
    z-index: 1050;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    animation: floatPulse 3s ease-in-out infinite;
    border: 3px solid rgba(255, 255, 255, 0.3);
}

.floating-tutorial-btn:hover {
    transform: translateY(-8px) scale(1.05);
    box-shadow: 0 15px 40px rgba(245, 158, 11, 0.6);
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
}

@keyframes floatPulse {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-10px);
    }
}

.floating-btn-icon {
    width: 50px;
    height: 50px;
    min-width: 50px;
    background: rgba(255, 255, 255, 0.25);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    animation: iconPulse 2s ease-in-out infinite;
}

@keyframes iconPulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
}

.floating-btn-text {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
}

.btn-main-text {
    font-size: 16px;
    font-weight: 700;
    letter-spacing: 0.5px;
}

.btn-sub-text {
    font-size: 11px;
    opacity: 0.9;
    font-weight: 500;
    margin-top: 2px;
}

/* ========================================
   TUTORIAL MODAL - CLEAN DESIGN
   ======================================== */
.modal-tutorial .modal-dialog {
    max-width: 800px;
}

.modal-tutorial .modal-content {
    border-radius: 20px;
    border: none;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
}

.modal-tutorial .modal-header {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    color: white;
    padding: 25px 30px;
    border: none;
}

.modal-tutorial .modal-title {
    font-weight: 700;
    font-size: 22px;
    margin: 0;
}

.modal-tutorial .btn-close {
    filter: brightness(0) invert(1);
    opacity: 0.9;
}

.modal-tutorial .modal-body {
    padding: 30px;
}

/* Language Sections */
.tutorial-section {
    margin-bottom: 30px;
}

.tutorial-section:last-child {
    margin-bottom: 0;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
    padding: 20px;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-radius: 12px;
    border-left: 5px solid #4f46e5;
}

.section-flag {
    font-size: 36px;
    line-height: 1;
}

.section-title {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: #1e293b;
}

.section-subtitle {
    margin: 5px 0 0 0;
    font-size: 13px;
    color: #64748b;
}

/* Video Items Container */
.videos-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* Individual Video Item */
.tutorial-video-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.tutorial-video-item:hover {
    border-color: #4f46e5;
    transform: translateX(5px);
    box-shadow: 0 4px 15px rgba(79, 70, 229, 0.15);
    background: #f8fafc;
}

.tutorial-video-icon {
    width: 60px;
    height: 60px;
    min-width: 60px;
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    transition: all 0.3s ease;
}

.tutorial-video-item:hover .tutorial-video-icon {
    transform: scale(1.1) rotate(5deg);
}

.tutorial-video-content {
    flex: 1;
}

.tutorial-video-title {
    margin: 0 0 5px 0;
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
}

.tutorial-video-desc {
    margin: 0;
    font-size: 13px;
    color: #64748b;
}

/* Video Player - Clean design without header */
.video-player-container {
    display: none;
    margin: 15px 0;
    padding: 0;
    background: transparent;
    border-radius: 15px;
    opacity: 0;
    max-height: 0;
    overflow: hidden;
    transition: all 0.4s ease;
    position: relative;
}

.video-player-container.active {
    display: block;
    opacity: 1;
    max-height: 600px;
    animation: slideDown 0.4s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Floating Close Button - Beautiful Design */
.floating-close-btn {
    position: absolute;
    top: 15px;
    right: 15px;
    width: 45px;
    height: 45px;
    background: rgba(239, 68, 68, 0.95);
    color: white;
    border: 3px solid rgba(255, 255, 255, 0.9);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    cursor: pointer;
    z-index: 10;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    backdrop-filter: blur(10px);
}

.floating-close-btn:hover {
    background: rgba(220, 38, 38, 1);
    transform: scale(1.1) rotate(90deg);
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.5);
}

.floating-close-btn:active {
    transform: scale(0.95) rotate(90deg);
}

.floating-close-btn i {
    pointer-events: none;
}

.video-iframe-wrapper {
    position: relative;
    width: 100%;
    padding-bottom: 56.25%;
    background: #000;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
}

.video-iframe-wrapper video,
.video-iframe-wrapper iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: none;
}

/* ========================================
   RESPONSIVE - TABLET
   ======================================== */
@media (max-width: 768px) {
    .floating-tutorial-btn {
        bottom: 20px;
        right: 20px;
        padding: 12px 16px;
        gap: 10px;
    }

    .floating-btn-icon {
        width: 45px;
        height: 45px;
        min-width: 45px;
        font-size: 22px;
    }

    .btn-main-text {
        font-size: 14px;
    }

    .btn-sub-text {
        font-size: 10px;
    }

    .modal-tutorial .modal-dialog {
        margin: 0.5rem;
    }

    .modal-tutorial .modal-header {
        padding: 20px;
    }

    .modal-tutorial .modal-title {
        font-size: 18px;
    }

    .modal-tutorial .modal-body {
        padding: 20px;
    }

    .section-header {
        padding: 15px;
    }

    .section-flag {
        font-size: 28px;
    }

    .section-title {
        font-size: 17px;
    }

    .tutorial-video-item {
        padding: 12px;
    }

    .tutorial-video-icon {
        width: 50px;
        height: 50px;
        min-width: 50px;
        font-size: 20px;
    }

    .tutorial-video-title {
        font-size: 14px;
    }

    .tutorial-video-desc {
        font-size: 12px;
    }

    .floating-close-btn {
        width: 40px;
        height: 40px;
        font-size: 18px;
        top: 12px;
        right: 12px;
    }
}

/* ========================================
   RESPONSIVE - MOBILE
   ======================================== */
@media (max-width: 480px) {
    .floating-tutorial-btn {
        bottom: 15px;
        right: 15px;
        padding: 10px 14px;
    }

    .floating-btn-icon {
        width: 40px;
        height: 40px;
        min-width: 40px;
        font-size: 20px;
    }

    .btn-main-text {
        font-size: 13px;
    }

    .btn-sub-text {
        font-size: 9px;
    }

    .modal-tutorial .modal-body {
        padding: 15px;
    }

    .section-header {
        padding: 12px;
    }

    .section-flag {
        font-size: 24px;
    }

    .section-title {
        font-size: 16px;
    }

    .section-subtitle {
        font-size: 12px;
    }

    .tutorial-video-item {
        padding: 10px;
        gap: 12px;
    }

    .tutorial-video-icon {
        width: 45px;
        height: 45px;
        min-width: 45px;
        font-size: 18px;
    }

    .tutorial-video-title {
        font-size: 13px;
    }

    .tutorial-video-desc {
        font-size: 11px;
    }

    .floating-close-btn {
        width: 36px;
        height: 36px;
        font-size: 16px;
        top: 10px;
        right: 10px;
    }
}


    </style>
</head>
<body<?php echo ($page !== 'login') ? ' class="logged-in"' : ''; ?>>
    
    <!-- Floating Tutorial Button -->
<div class="floating-tutorial-btn" data-bs-toggle="modal" data-bs-target="#tutorialModal">
    <div class="floating-btn-icon">
        <i class="fas fa-play-circle"></i>
    </div>
    <div class="floating-btn-text">
        <span class="btn-main-text">Video Guides</span>
        <span class="btn-sub-text">Help Guide</span>
    </div>
</div>


<!-- GLOBAL LOADING OVERLAY -->
<div class="loading-overlay" id="globalLoadingOverlay">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <div class="loading-text">Processing...</div>
        <div class="loading-subtext">Please wait, do not close this window</div>
    </div>
</div>

<div class="reload-toast" id="reloadToast">
    <i class="fas fa-check-circle text-success"></i>
    <span>Data refreshed successfully!</span>
</div>

<?php if ($page === 'login'): ?>
        <div class="login-container animate__animated animate__fadeIn">
        <div class="login-card">
            <div class="text-center mb-4">
                <img src="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/Wushu+Sport+Academy+Circle+Yellow.png" 
                     alt="Wushu Sport Academy Logo" 
                     class="login-logo">
                <h2>Training Registrations</h2>
                <p class="text-muted">Welcome to Wushu Sport Academy</p>
            </div>

            <!-- MOVED & RESIZED: Register Section (Now Above Login) -->
            <div class="text-center mb-4 pb-4 border-bottom" style="background-color: #F0F8FF; padding: 25px; border-radius: 15px; border: 2px solid #4169E1;">
                <h3 style="color: #4169E1; font-weight: 800; margin-bottom: 15px; font-size: 21px;">
                    <i class="fas fa-user-plus"></i> NEW STUDENT?
                </h3>
                <a href="pages/register.php" class="btn btn-success w-100" style="padding: 15px 20px; font-size: 18px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 10px 20px rgba(3, 138, 255, 0.3); border-radius: 12px; background-color: #4169E1;">
                    <i class="fas fa-pen-to-square"></i> Register for 2026 Class
                </a>
                <!--<p class="text-muted mt-3 mb-0" style="font-size: 15px; font-weight: 600;">
                    Click to register!
                </p>-->
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

            <form method="POST" action="" class="submit-with-loading">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="login">
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" class="form-control form-control-lg" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" class="form-control form-control-lg" required>
                </div>
                <button type="submit" class="btn btn-outline-primary btn-lg w-100 mb-3">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="text-center mb-3">
                <p class="text-muted fw-bold">TO PREVIEW YOUR PERSONAL DATA, PLEASE LOG IN USING YOUR PREVIOUS SET EMAIL ADDRESS AND PASSWORD.</p>
            </div>
        </div>
    </div>


<?php else: ?>
    <?php redirectIfNotLoggedIn(); ?>

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
                    <span class="logo-subtitle">System</span>
                </div>
            </a>
        </div>

        <div class="header-right">
            <?php if (isParent()): ?>
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

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

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

    <div class="content-area">
        <div class="page-header">
            <h3 class="mb-0">
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
            <?php if ($page !== 'dashboard'): ?>
            <button class="btn btn-reload" onclick="reloadPageData()">
                <i class="fas fa-sync-alt"></i>
                <span>Refresh Data</span>
            </button>
            <?php endif; ?>
        </div>

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

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
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
        
        // Close video when modal is closed - MOVED INSIDE DOMContentLoaded
        const tutorialModal = document.getElementById('tutorialModal');
        if (tutorialModal) {
            tutorialModal.addEventListener('hidden.bs.modal', function () {
                closeVideo();
            });
        }
    });
    
    // Tutorial Video Player Functions - Clean design without title
function playVideo(language, part, element) {
    const playerId = `player-${language}-${part}`;
    const playerContainer = document.getElementById(playerId);
    
    // Close all other players first
    const allPlayers = document.querySelectorAll('.video-player-container');
    allPlayers.forEach(player => {
        if (player.id !== playerId) {
            player.classList.remove('active');
            player.innerHTML = '';
        }
    });
    
    // Define your video file paths
    const videos = {
        'en': {
            1: 'videos/tutorials/english/Registration Video.mp4',
            2: 'videos/tutorials/english/Student System Guide.mp4',
            3: 'videos/tutorials/english/Invoice Payment Video.mp4'
        },
        'zh': {
            1: 'videos/tutorials/chinese/如何注册报名表格.mp4',
            2: 'videos/tutorials/chinese/如何使用学生系统.mp4',
            3: 'videos/tutorials/chinese/如何缴付学费指南.mp4'
        }
    };
    
    const videoPath = videos[language][part];
    
    // Toggle player visibility
    if (playerContainer.classList.contains('active')) {
        // If already playing, close it
        playerContainer.classList.remove('active');
        playerContainer.innerHTML = '';
    } else {
        // Show player with video - NO TITLE, just video and floating close button
        playerContainer.innerHTML = `
            <button class="floating-close-btn" onclick="closeVideoPlayer('${playerId}')">
                <i class="fas fa-times"></i>
            </button>
            <div class="video-iframe-wrapper">
                <video controls autoplay style="width: 100%; height: 100%; object-fit: contain;">
                    <source src="${videoPath}" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>
        `;
        
        playerContainer.classList.add('active');
        
        // Smooth scroll to the player
        setTimeout(() => {
            playerContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 100);
    }
}

function closeVideoPlayer(playerId) {
    const playerContainer = document.getElementById(playerId);
    playerContainer.classList.remove('active');
    playerContainer.innerHTML = '';
}

// Close all videos when modal is closed
document.addEventListener('DOMContentLoaded', function() {
    const tutorialModal = document.getElementById('tutorialModal');
    if (tutorialModal) {
        tutorialModal.addEventListener('hidden.bs.modal', function () {
            const allPlayers = document.querySelectorAll('.video-player-container');
            allPlayers.forEach(player => {
                player.classList.remove('active');
                player.innerHTML = '';
            });
        });
    }
});



    function reloadPageData() {
        const reloadBtn = document.querySelector('.btn-reload');
        const reloadIcon = reloadBtn.querySelector('i');
        const reloadToast = document.getElementById('reloadToast');
        
        reloadBtn.classList.add('loading');
        reloadBtn.disabled = true;
        
        setTimeout(function() {
            window.location.href = window.location.href.split('#')[0] + '&_t=' + new Date().getTime();
        }, 300);
        
        setTimeout(function() {
            reloadToast.classList.add('show');
            setTimeout(function() {
                reloadToast.classList.remove('show');
            }, 2000);
        }, 400);
    }

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

<!-- Tutorial Modal -->
<div class="modal fade modal-tutorial" id="tutorialModal" tabindex="-1" aria-labelledby="tutorialModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tutorialModalLabel">
                    <i class="fas fa-graduation-cap"></i> System Guide Videos
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                
                <!-- Chinese Section -->
                <div class="tutorial-section">
                    <div class="section-header">
                        <div class="section-flag">🇨🇳</div>
                        <div>
                            <h3 class="section-title">中文版本 (Chinese Version)</h3>
                            <p class="section-subtitle">系统教学视频</p>
                        </div>
                    </div>
                    
                    <div class="videos-container">
                        <div class="tutorial-video-item" onclick="playVideo('zh', 1, this)">
                            <div class="tutorial-video-icon">
                                <i class="fas fa-play"></i>
                            </div>
                            <div class="tutorial-video-content">
                                <h4 class="tutorial-video-title">如何注册报名表格</h4>
                                <p class="tutorial-video-desc">注册表格概述</p>
                            </div>
                        </div>
                        
                        <!-- Video Player for Chinese Part 1 -->
                        <div class="video-player-container" id="player-zh-1"></div>
                        
                        <div class="tutorial-video-item" onclick="playVideo('zh', 2, this)">
                            <div class="tutorial-video-icon">
                                <i class="fas fa-play"></i>
                            </div>
                            <div class="tutorial-video-content">
                                <h4 class="tutorial-video-title">如何使用学生系统</h4>
                                <p class="tutorial-video-desc">学生系统指南</p>
                            </div>
                        </div>
                        
                        <!-- Video Player for Chinese Part 2 -->
                        <div class="video-player-container" id="player-zh-2"></div>
                        
                        <div class="tutorial-video-item" onclick="playVideo('zh', 3, this)">
                            <div class="tutorial-video-icon">
                                <i class="fas fa-play"></i>
                            </div>
                            <div class="tutorial-video-content">
                                <h4 class="tutorial-video-title">如何缴付学费指南</h4>
                                <p class="tutorial-video-desc">缴付学费指南</p>
                            </div>
                        </div>
                        
                        <!-- Video Player for Chinese Part 3 -->
                        <div class="video-player-container" id="player-zh-3"></div>
                    </div>
                </div>
                
                <!-- English Section -->
                <div class="tutorial-section">
                    <div class="section-header">
                        <div class="section-flag">🇬🇧</div>
                        <div>
                            <h3 class="section-title">English Version</h3>
                            <p class="section-subtitle">Learn how to use the parent portal</p>
                        </div>
                    </div>
                    
                    <div class="videos-container">
                        <div class="tutorial-video-item" onclick="playVideo('en', 1, this)">
                            <div class="tutorial-video-icon">
                                <i class="fas fa-play"></i>
                            </div>
                            <div class="tutorial-video-content">
                                <h4 class="tutorial-video-title">Registration Video</h4>
                                <p class="tutorial-video-desc">Registration Form Explanations</p>
                            </div>
                        </div>
                        
                        <!-- Video Player for English Part 1 -->
                        <div class="video-player-container" id="player-en-1"></div>
                        
                        <div class="tutorial-video-item" onclick="playVideo('en', 2, this)">
                            <div class="tutorial-video-icon">
                                <i class="fas fa-play"></i>
                            </div>
                            <div class="tutorial-video-content">
                                <h4 class="tutorial-video-title">Student System Guide</h4>
                                <p class="tutorial-video-desc">Guides you around the student portal</p>
                            </div>
                        </div>
                        
                        <!-- Video Player for English Part 2 -->
                        <div class="video-player-container" id="player-en-2"></div>
                        
                        <div class="tutorial-video-item" onclick="playVideo('en', 3, this)">
                            <div class="tutorial-video-icon">
                                <i class="fas fa-play"></i>
                            </div>
                            <div class="tutorial-video-content">
                                <h4 class="tutorial-video-title">Invoice Payment Video</h4>
                                <p class="tutorial-video-desc">Guide you through the invoicing system</p>
                            </div>
                        </div>
                        
                        <!-- Video Player for English Part 3 -->
                        <div class="video-player-container" id="player-en-3"></div>
                    </div>
                </div>

                

            </div>
        </div>
    </div>
</div>




</body>
</html>
<?php ob_end_flush(); ?>