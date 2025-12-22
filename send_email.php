<?php
/**
 * Email Sending Function
 * Uses PHPMailer or PHP's mail() function
 */

function sendRegistrationEmail($toEmail, $studentName, $registrationNumber, $password, $studentStatus) {
    $subject = "Wushu Sport Academy - Registration Successful";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #1e293b; color: white; padding: 20px; text-align: center; }
            .content { background: #f8fafc; padding: 30px; border: 1px solid #e2e8f0; }
            .credentials { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #fbbf24; }
            .footer { text-align: center; padding: 20px; color: #64748b; font-size: 12px; }
            strong { color: #1e293b; }
            .button { display: inline-block; padding: 12px 24px; background: #1e293b; color: white; text-decoration: none; border-radius: 6px; margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Welcome to Wushu Sport Academy!</h1>
                <p>报名成功 · Registration Successful</p>
            </div>
            
            <div class='content'>
                <h2>Dear {$studentName},</h2>
                
                <p>Congratulations! Your registration has been successfully processed.</p>
                <p>恭喜！您的报名已成功处理。</p>
                
                <div class='credentials'>
                    <h3>Your Account Credentials 您的账户凭证</h3>
                    <p><strong>Registration Number 报名号码:</strong> {$registrationNumber}</p>
                    <p><strong>Student ID 学号:</strong> {$registrationNumber}</p>
                    <p><strong>Email 邮箱:</strong> {$toEmail}</p>
                    <p><strong>Password 密码:</strong> <span style='font-size: 18px; color: #dc2626; font-weight: bold;'>{$password}</span></p>
                    <p><strong>Status 身份:</strong> {$studentStatus}</p>
                </div>
                
                <p><strong>Next Steps 接下来:</strong></p>
                <ul>
                    <li>Your payment is currently under review 您的付款正在审核中</li>
                    <li>You will receive confirmation once verified 审核通过后您将收到确认</li>
                    <li>Login to the student portal with your credentials 使用您的凭证登录学生门户</li>
                    <li>View your class schedule and payment status 查看您的课程表和付款状态</li>
                </ul>
                
                <p style='text-align: center;'>
                    <a href='https://your-domain.com/index.php?page=login' class='button'>Login to Student Portal 登录学生门户</a>
                </p>
                
                <p style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0;'>
                    <strong>Important 重要:</strong> Please keep this email safe for your records. 
                    请妥善保管此邮件以作记录。
                </p>
            </div>
            
            <div class='footer'>
                <p>Wushu Sport Academy<br>
                No. 2, Jalan BP 5/6, Bandar Bukit Puchong, 47120 Puchong, Selangor<br>
                Email: admin@wushusportacademy.com</p>
                <p>This is an automated email. Please do not reply to this message.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Wushu Sport Academy <noreply@wushusportacademy.com>" . "\r\n";
    
    return mail($toEmail, $subject, $message, $headers);
}
?>
