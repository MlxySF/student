<?php
/**
 * send_approval_email.php
 * Sends approval notification email to parents when registration is approved
 * Call this after admin approves a registration
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'PHPMailer/Exception.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'config.php';

/**
 * Send approval email to parent
 * 
 * @param string $parentEmail Parent's email address
 * @param string $studentName Child's name
 * @param string $registrationNumber Student registration number
 * @param string $studentStatus Student status (Student/State Team/Backup)
 * @param string $parentPassword Parent login password (only for first child)
 * @param bool $isFirstChild Whether this is the first child for this parent
 * @return bool Success status
 */
function sendApprovalEmail($parentEmail, $studentName, $registrationNumber, $studentStatus, $parentPassword = null, $isFirstChild = false) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'chaichonghern@gmail.com';
        $mail->Password   = 'kyyj elhp dkdw gvki';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('noreply@wushusportacademy.com', 'Wushu Sport Academy');
        $mail->addAddress($parentEmail);
        $mail->addReplyTo('admin@wushusportacademy.com', 'Academy Admin');

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = '✅ Registration Approved - Wushu Sport Academy';
        $mail->Body    = getApprovalEmailHTML($parentEmail, $studentName, $registrationNumber, $studentStatus, $parentPassword, $isFirstChild);
        $mail->AltBody = "Your child {$studentName}'s registration has been approved!";

        $mail->send();
        error_log("[Approval Email] Sent to {$parentEmail} for child {$studentName}");
        return true;
    } catch (Exception $e) {
        error_log("[Approval Email] Failed to send to {$parentEmail}: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Generate HTML content for approval email
 */
function getApprovalEmailHTML($parentEmail, $studentName, $registrationNumber, $studentStatus, $parentPassword, $isFirstChild) {
    // Login credentials section
    $loginSection = '';
    
    if ($isFirstChild && $parentPassword) {
        $loginSection = "
        <div style='background: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; margin: 24px 0; border-radius: 8px;'>
            <h3 style='margin: 0 0 12px 0; color: #856404; font-size: 18px;'>🔑 Your Login Credentials</h3>
            <p style='margin: 0 0 12px 0; color: #856404; font-size: 14px;'>Use these credentials to access the parent portal:</p>
            <table style='width: 100%;'>
                <tr>
                    <td style='padding: 6px 0; color: #856404; font-weight: 600; width: 35%;'>Email:</td>
                    <td style='padding: 6px 0; color: #856404;'>{$parentEmail}</td>
                </tr>
                <tr>
                    <td style='padding: 10px 0 6px 0; color: #856404; font-weight: 600; vertical-align: top;'>Password:</td>
                    <td style='padding: 10px 0 6px 0;'>
                        <div style='font-size: 24px; color: #dc2626; font-weight: bold; font-family: Courier, monospace; letter-spacing: 3px; background: #fff; padding: 12px 16px; border-radius: 6px; display: inline-block; border: 2px solid #ffc107;'>{$parentPassword}</div>
                    </td>
                </tr>
            </table>
        </div>
        ";
    } else {
        $loginSection = "
        <div style='background: #e0f2fe; border-left: 4px solid #0ea5e9; padding: 16px; margin: 24px 0; border-radius: 8px;'>
            <p style='margin: 0; color: #075985; font-size: 14px;'>🔐 <strong>Login to Parent Portal:</strong> Use your existing parent email and password to view all your children.</p>
        </div>
        ";
    }
    
    $portalUrl = 'https://wushusportacademy.app.tc/student/index.php'; // Update with your actual portal URL
    
    $html = "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Registration Approved</title>
</head>
<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5;'>
    <div style='max-width: 600px; margin: 20px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
        <!-- Header -->
        <div style='background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 40px 24px; text-align: center;'>
            <div style='font-size: 48px; margin-bottom: 16px;'>✅</div>
            <h1 style='margin: 0 0 8px 0; font-size: 32px; font-weight: 700;'>Registration Approved!</h1>
            <p style='margin: 0; font-size: 16px; opacity: 0.95;'>注册已批准 · Welcome to Wushu Sport Academy</p>
        </div>
        
        <!-- Content -->
        <div style='padding: 32px 24px; background: white;'>
            <p style='font-size: 18px; font-weight: 600; color: #1e293b; margin: 0 0 16px 0;'>Dear Parent,</p>
            <p style='margin: 0 0 24px 0; font-size: 15px; color: #475569;'>Great news! Your child <strong>{$studentName}</strong>'s registration has been <strong style='color: #10b981;'>APPROVED</strong> by Wushu Sport Academy!</p>
            
            <!-- Approval Notice -->
            <div style='background: #f0fdf4; border: 2px solid #86efac; padding: 20px; margin: 24px 0; border-radius: 8px; text-align: center;'>
                <div style='font-size: 36px; margin-bottom: 8px;'>🎉</div>
                <h3 style='margin: 0 0 8px 0; color: #166534; font-size: 20px;'>Payment Verified</h3>
                <p style='margin: 0; color: #166534; font-size: 14px;'>Your child can now start attending classes!</p>
            </div>
            
            {$loginSection}
            
            <!-- Student Details -->
            <div style='background: #f8fafc; border-left: 4px solid #3b82f6; padding: 20px; margin: 24px 0; border-radius: 8px;'>
                <h3 style='margin: 0 0 16px 0; color: #1e293b; font-size: 18px;'>🎯 Student Information</h3>
                <table style='width: 100%;'>
                    <tr>
                        <td style='padding: 6px 0; color: #475569; font-weight: 600; width: 35%;'>Student Name:</td>
                        <td style='padding: 6px 0; color: #1e293b;'>{$studentName}</td>
                    </tr>
                    <tr>
                        <td style='padding: 6px 0; color: #475569; font-weight: 600;'>Student ID:</td>
                        <td style='padding: 6px 0; color: #1e293b;'><strong>{$registrationNumber}</strong></td>
                    </tr>
                    <tr>
                        <td style='padding: 6px 0; color: #475569; font-weight: 600;'>Status:</td>
                        <td style='padding: 6px 0;'><span style='background: #dbeafe; color: #1e40af; padding: 4px 12px; border-radius: 4px; font-size: 13px; font-weight: 600;'>{$studentStatus}</span></td>
                    </tr>
                </table>
            </div>
            
            <!-- Next Steps -->
            <div style='background: #eff6ff; border-left: 4px solid #3b82f6; padding: 20px; margin: 24px 0; border-radius: 8px;'>
                <h3 style='margin: 0 0 12px 0; color: #1e40af; font-size: 16px;'>📝 What's Next?</h3>
                <ol style='margin: 0; padding-left: 20px; color: #1e40af; font-size: 14px; line-height: 1.8;'>
                    <li><strong>Login to Parent Portal</strong> to view your child's class schedule</li>
                    <li><strong>Check attendance records</strong> and track progress</li>
                    <li><strong>View and pay monthly invoices</strong> online</li>
                    <li><strong>Bring your child to class</strong> on scheduled training days</li>
                </ol>
            </div>
            
            <!-- CTA Button -->
            <div style='text-align: center; margin: 32px 0;'>
                <a href='{$portalUrl}' style='display: inline-block; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; text-decoration: none; padding: 16px 40px; border-radius: 8px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);'>
                    🚀 Access Parent Portal
                </a>
            </div>
            
            <!-- Contact Info -->
            <div style='background: #f8fafc; padding: 16px; border-radius: 8px; margin: 24px 0;'>
                <p style='margin: 0 0 8px 0; color: #475569; font-size: 13px; font-weight: 600;'>Need Help?</p>
                <p style='margin: 0; color: #64748b; font-size: 13px;'>Contact us at <a href='mailto:admin@wushusportacademy.com' style='color: #3b82f6; text-decoration: none;'>admin@wushusportacademy.com</a> or call <strong>+60 12-345 6789</strong></p>
            </div>
        </div>
        
        <!-- Footer -->
        <div style='text-align: center; padding: 24px; background: #f8fafc; color: #64748b; font-size: 13px; border-top: 1px solid #e2e8f0;'>
            <p style='margin: 0 0 8px 0; font-weight: 600; color: #1e293b; font-size: 15px;'>Wushu Sport Academy 武术体育学院</p>
            <p style='margin: 4px 0;'>📧 admin@wushusportacademy.com | 📱 +60 12-345 6789</p>
            <p style='margin: 16px 0 0 0; font-size: 11px; color: #94a3b8;'>This is an automated notification. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>";

    return $html;
}

// ============================================
// Example usage (call from admin approval page)
// ============================================

if (isset($_POST['approve_registration'])) {
    $registrationId = intval($_POST['registration_id']);
    
    try {
        // Get registration details
        $stmt = $pdo->prepare("
            SELECT 
                r.email,
                r.name_en,
                r.registration_number,
                r.status,
                r.parent_account_id,
                pa.password as parent_password
            FROM registrations r
            LEFT JOIN parent_accounts pa ON r.parent_account_id = pa.id
            WHERE r.id = ?
        ");
        $stmt->execute([$registrationId]);
        $registration = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($registration) {
            // Update payment status to approved
            $updateStmt = $pdo->prepare("UPDATE registrations SET payment_status = 'approved' WHERE id = ?");
            $updateStmt->execute([$registrationId]);
            
            // Check if this is the first child for this parent
            $countStmt = $pdo->prepare("
                SELECT COUNT(*) as child_count 
                FROM registrations 
                WHERE parent_account_id = ? 
                AND payment_status = 'approved'
            ");
            $countStmt->execute([$registration['parent_account_id']]);
            $result = $countStmt->fetch(PDO::FETCH_ASSOC);
            $isFirstChild = ($result['child_count'] == 1);
            
            // Send approval email
            $emailSent = sendApprovalEmail(
                $registration['email'],
                $registration['name_en'],
                $registration['registration_number'],
                $registration['status'],
                $registration['parent_password'],
                $isFirstChild
            );
            
            if ($emailSent) {
                $_SESSION['success'] = "Registration approved and email sent to parent!";
            } else {
                $_SESSION['warning'] = "Registration approved but failed to send email.";
            }
        }
    } catch (Exception $e) {
        error_log("Error approving registration: " . $e->getMessage());
        $_SESSION['error'] = "Failed to approve registration.";
    }
}
?>
