<?php
/**
 * Email Sending with PHPMailer and SMTP
 * More reliable than PHP mail() function
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// If using manual download, use these lines instead:
require '/PHPMailer/Exception.php';
require '/PHPMailer/PHPMailer.php';
require '/PHPMailer/SMTP.php';

function sendRegistrationEmailSMTP($toEmail, $studentName, $registrationNumber, $password, $studentStatus) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'mail.wushusportacademy.com'; // Change to your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'admin@wushusportacademy.com'; // Your email
        $mail->Password   = 'UZa;nENf]!xqpRak'; // Your email app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // For Gmail, you need to:
        // 1. Enable 2-factor authentication
        // 2. Generate an "App Password" from Google Account settings
        // 3. Use that app password here

        // Recipients
        $mail->setFrom('admin@wushusportacademy.com', 'Wushu Sport Academy');
        $mail->addAddress($toEmail, $studentName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Wushu Sport Academy - Registration Successful';
        $mail->Body    = getEmailHTML($studentName, $registrationNumber, $toEmail, $password, $studentStatus);
        $mail->AltBody = "Registration Successful!\n\nStudent Name: $studentName\nRegistration Number: $registrationNumber\nEmail: $toEmail\nPassword: $password\nStatus: $studentStatus";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}

function getEmailHTML($studentName, $registrationNumber, $toEmail, $password, $studentStatus) {
    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #1e293b; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f8fafc; padding: 30px; border: 1px solid #e2e8f0; }
            .credentials { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #fbbf24; border-radius: 4px; }
            .footer { text-align: center; padding: 20px; color: #64748b; font-size: 12px; }
            strong { color: #1e293b; }
            .button { display: inline-block; padding: 12px 24px; background: #1e293b; color: white !important; text-decoration: none; border-radius: 6px; margin: 10px 0; }
            .highlight { font-size: 20px; color: #dc2626; font-weight: bold; font-family: monospace; letter-spacing: 2px; background: #fef2f2; padding: 8px 12px; border-radius: 4px; display: inline-block; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin: 0;'>Welcome to Wushu Sport Academy!</h1>
                <p style='margin: 8px 0 0 0; font-size: 14px;'>报名成功 · Registration Successful</p>
            </div>
            
            <div class='content'>
                <h2 style='color: #1e293b;'>Dear {$studentName},</h2>
                
                <p>Congratulations! Your registration has been successfully processed.</p>
                <p style='color: #475569;'>恭喜！您的报名已成功处理。</p>
                
                <div class='credentials'>
                    <h3 style='margin-top: 0; color: #1e293b;'>Your Account Credentials 您的账户凭证</h3>
                    <table cellpadding='8' style='width: 100%;'>
                        <tr>
                            <td style='width: 50%;'><strong>Registration Number:</strong></td>
                            <td style='font-size: 16px; font-weight: bold; color: #7c3aed;'>{$registrationNumber}</td>
                        </tr>
                        <tr>
                            <td><strong>Student ID:</strong></td>
                            <td style='font-weight: bold;'>{$registrationNumber}</td>
                        </tr>
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td>{$toEmail}</td>
                        </tr>
                        <tr>
                            <td><strong>Password:</strong></td>
                            <td><span class='highlight'>{$password}</span></td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td style='font-weight: bold; color: #16a34a;'>{$studentStatus}</td>
                        </tr>
                    </table>
                </div>
                
                <div style='background: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; border-radius: 4px; margin: 20px 0;'>
                    <p style='margin: 0; font-size: 14px; color: #92400e;'>
                        <strong>⚠️ Important:</strong> Please save your password in a secure location. You will need these credentials to access the student portal.
                    </p>
                </div>
                
                <p><strong>Next Steps 接下来:</strong></p>
                <ul style='line-height: 1.8;'>
                    <li>Your payment is currently under review 您的付款正在审核中</li>
                    <li>You will receive confirmation once verified 审核通过后您将收到确认</li>
                    <li>Login to the student portal with your credentials 使用您的凭证登录学生门户</li>
                    <li>View your class schedule and payment status 查看您的课程表和付款状态</li>
                </ul>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='https://your-domain.com/index.php?page=login' class='button' style='color: white;'>Login to Student Portal 登录学生门户</a>
                </div>
                
                <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 30px 0;'>
                
                <p style='font-size: 13px; color: #64748b;'>
                    If you did not request this registration, please contact us immediately at admin@wushusportacademy.com
                </p>
            </div>
            
            <div class='footer'>
                <p style='margin: 4px 0;'><strong>Wushu Sport Academy</strong></p>
                <p style='margin: 4px 0;'>No. 2, Jalan BP 5/6, Bandar Bukit Puchong, 47120 Puchong, Selangor</p>
                <p style='margin: 4px 0;'>Email: admin@wushusportacademy.com | Phone: +60 12-345 6789</p>
                <p style='margin: 12px 0 4px 0; font-size: 11px;'>This is an automated email. Please do not reply to this message.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}
?>
