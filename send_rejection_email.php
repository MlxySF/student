<?php
/**
 * send_rejection_email.php
 * Sends email notification when a registration is rejected/cancelled
 * 
 * FIXED: Use centralized PHPMailer loader to prevent class conflicts
 */

// Load PHPMailer classes (centralized loader prevents duplicate declarations)
require_once __DIR__ . '/phpmailer_loader.php';

/**
 * Send rejection/cancellation email to parent
 * 
 * @param string $toEmail Parent email address
 * @param string $studentName Child's name
 * @param string $registrationNumber Registration number
 * @param string $rejectReason Optional admin notes/reason for rejection
 * @return bool True if email sent successfully, false otherwise
 */
function sendRejectionEmail($toEmail, $studentName, $registrationNumber, $rejectReason = '') {
    // Use fully qualified class name to avoid conflicts
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        error_log("[Rejection Email] Sending to: {$toEmail}, Student: {$studentName}, Reg#: {$registrationNumber}");
        
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = 'mail.wushusportacademy.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'admin@wushusportacademy.com';
        $mail->Password   = 'UZa;nENf]!xqpRak';
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Email Headers
        $mail->setFrom('admin@wushusportacademy.com', 'Wushu Sport Academy');
        $mail->addAddress($toEmail);

        // Email Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = '❌ Wushu Sport Academy - Registration Not Approved';
        $mail->Body    = getRejectionEmailHTML($studentName, $registrationNumber, $rejectReason);
        $mail->AltBody = "Your registration for {$studentName} (#{$registrationNumber}) was not approved. Please contact us or register again.";

        $mail->send();
        error_log("[Rejection Email] Successfully sent to {$toEmail}");
        return true;
        
    } catch (\Exception $e) {
        error_log("[Rejection Email] Error: {$mail->ErrorInfo}");
        return false;
    }
}

function getRejectionEmailHTML($studentName, $registrationNumber, $rejectReason) {
    $reasonSection = '';
    
    if (!empty(trim($rejectReason))) {
        $reasonSection = "
        <div style='background: #fef3c7; border-left: 4px solid #f59e0b; padding: 20px; border-radius: 8px; margin: 24px 0;'>
            <h6 class='mb-3' style='margin: 0 0 12px 0; color: #92400e; font-size: 16px;'><i class='fas fa-info-circle'></i> Reason</h6>
            <p style='margin: 0; color: #92400e; font-size: 14px; line-height: 1.6;'>" . nl2br(htmlspecialchars($rejectReason)) . "</p>
        </div>
        ";
    }
    
    $html = "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Registration Not Approved</title>
</head>
<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5;'>
    <div style='max-width: 600px; margin: 20px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
        <!-- Header -->
        <div style='background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color: white; padding: 40px 24px; text-align: center;'>
            <div style='width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: 50%; margin: 0 auto 16px; position: relative;'>
                <div style='position: absolute; top: 52%; left: 50%; transform: translate(-50%, -50%); font-size: 48px; line-height: 1;'>❌</div>
            </div>
            <h1 style='margin: 0 0 8px 0; font-size: 28px; font-weight: 700;'>Registration Not Approved</h1>
            <p style='margin: 0; font-size: 16px; opacity: 0.95;'>注册未通过 · Not Approved</p>
        </div>
        
        <!-- Body -->
        <div style='padding: 32px 24px; background: white;'>
            <p style='font-size: 18px; font-weight: 600; color: #1e293b; margin: 0 0 16px 0;'>Dear Parent,</p>
            
            <p style='margin: 0 0 24px 0; font-size: 15px; color: #475569; line-height: 1.7;'>
                We regret to inform you that the registration for <strong>{$studentName}</strong> (Registration #{$registrationNumber}) has not been approved at this time.
            </p>
            
            {$reasonSection}
            
            <div style='background: #fef2f2; border-left: 4px solid #ef4444; padding: 20px; border-radius: 8px; margin: 24px 0;'>
                <h6 style='margin: 0 0 12px 0; color: #991b1b; font-size: 16px;'><i class='fas fa-exclamation-circle'></i> What This Means</h6>
                <ul style='margin: 0; padding-left: 20px; color: #991b1b; font-size: 14px; line-height: 1.7;'>
                    <li>Your registration payment has been cancelled</li>
                    <li>No invoice has been generated</li>
                    <li>You can submit a new registration anytime</li>
                    <li>Your previous registration data has been removed</li>
                </ul>
            </div>
            
            <div style='background: #eff6ff; border-left: 4px solid #3b82f6; padding: 20px; border-radius: 8px; margin: 24px 0;'>
                <h6 style='margin: 0 0 12px 0; color: #1e40af; font-size: 16px;'>🔄 Next Steps</h6>
                <ol style='margin: 0; padding-left: 20px; color: #1e40af; font-size: 14px; line-height: 1.8;'>
                    <li>Review the reason above (if provided)</li>
                    <li>Contact us if you have any questions</li>
                    <li>You may register again with correct information</li>
                    <li>Ensure payment receipt is clear and shows correct amount</li>
                </ol>
            </div>
            
            <div style='background: #f8fafc; padding: 20px; border-radius: 8px; margin: 24px 0;'>
                <h6 style='margin: 0 0 12px 0; color: #1e293b; font-size: 16px;'>📞 Contact Information</h6>
                <p style='margin: 8px 0; color: #475569; font-size: 14px;'>
                    <strong>Email:</strong> admin@wushusportacademy.com<br>
                    <strong>Phone:</strong> +60 12-345 6789
                </p>
                <p style='margin: 12px 0 0 0; color: #64748b; font-size: 13px;'>
                    Our staff will be happy to assist you with any questions about this decision or help you with a new registration.
                </p>
            </div>
            
            <div style='text-align: center; margin-top: 32px;'>
                <a href='https://wushusportacademy.app.tc/student/pages/register.php' style='display: inline-block; background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); color: white; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 15px;'>
                    🔄 Register Again
                </a>
            </div>
            
            <p style='margin: 32px 0 0 0; color: #64748b; font-size: 13px; text-align: center; font-style: italic;'>
                We appreciate your interest in Wushu Sport Academy and hope to see you register again soon.
            </p>
        </div>
        
        <!-- Footer -->
        <div style="text-align: center; padding: 24px; background: #f8fafc; color: #64748b; font-size: 13px; border-top: 1px solid #e2e8f0;">
            <p style="margin: 0 0 8px 0; font-weight: 600; color: #1e293b; font-size: 15px;">Wushu Sport Academy 武术体育学院</p>
            <p style="margin: 4px 0;>📧 Email: admin@wushusportacademy.com</p>
            <p style="margin: 16px 0 0 0; font-size: 11px; color: #94a3b8;">This is an automated email.</p>
        </div>
    </div>
</body>
</html>";

    return $html;
}
?>