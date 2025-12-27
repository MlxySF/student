<?php
/**
 * send_approval_notification.php
 * Sends approval notification email when registration is approved
 * Call this after updating payment_status to 'approved'
 */

// Load PHPMailer classes (centralized loader prevents duplicate declarations)
require_once __DIR__ . '/phpmailer_loader.php';

function generatePasswordFromIC(string $ic): string {
    $icClean = str_replace('-', '', $ic);
    $last4 = substr($icClean, -4);
    return $last4;
}

function sendApprovalNotification($registrationId, $conn) {
    // Get registration details
    $stmt = $conn->prepare("
        SELECT 
            r.registration_number,
            r.name_en,
            r.email,
            r.parent_name,
            r.parent_ic,
            r.payment_status
        FROM registrations r
        WHERE r.id = ?
    ");
    $stmt->execute([$registrationId]);
    $reg = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reg) {
        return ['success' => false, 'error' => 'Registration not found'];
    }
    
    if ($reg['payment_status'] !== 'approved') {
        return ['success' => false, 'error' => 'Registration is not approved'];
    }
    
    $parentPassword = generatePasswordFromIC($reg['parent_ic']);
    
    $mail = new PHPMailer(true);
    try {
        // SMTP Configuration (existing code - keep as is)
$mail->isSMTP();
$mail->Host       = 'smtp.mailgun.org';
$mail->SMTPAuth   = true;
$mail->Username   = 'admin@wushusportacademy.com';
$mail->Password   = 'mailgun api here';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port       = 587;
$mail->CharSet    = 'UTF-8';
$mail->Encoding   = 'base64';

// **ADD THESE NEW CONFIGURATIONS**
// Set sender/return path to match From address
$mail->Sender = 'admin@wushusportacademy.com';

// Add Message-ID and other anti-spam headers
$mail->MessageID = sprintf("<%s@%s>", uniqid(), 'wushusportacademy.com');
$mail->XMailer = ' '; // Hide PHPMailer signature to avoid spam triggers

// Recipients (existing code - keep as is)
$mail->setFrom('admin@wushusportacademy.com', 'Wushu Sport Academy');
$mail->addAddress($reg['email']);
$mail->addReplyTo('admin@wushusportacademy.com', 'Wushu Sport Academy');

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = '✅ Registration Approved - Wushu Sport Academy';
        $mail->Body    = getApprovalEmailHTML(
            $reg['parent_name'],
            $reg['name_en'],
            $reg['registration_number'],
            $reg['email'],
            $parentPassword
        );
        $mail->AltBody = "Your registration has been approved!";

        $mail->send();
        
        // Log the email sending
        $logStmt = $conn->prepare("
            INSERT INTO email_logs (registration_id, email_type, recipient, sent_at, status)
            VALUES (?, 'approval', ?, NOW(), 'sent')
        ");
        $logStmt->execute([$registrationId, $reg['email']]);
        
        return ['success' => true, 'message' => 'Approval email sent successfully'];
    } catch (Exception $e) {
        error_log("[Approval Email Error] " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function getApprovalEmailHTML($parentName, $childName, $registrationNumber, $email, $password) {
    $html = "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Registration Approved</title>
</head>
<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5;'>
    <div style='max-width: 600px; margin: 20px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
        <div style='background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); color: white; padding: 40px 24px; text-align: center;'>
            <h1 style='margin: 0 0 8px 0; font-size: 32px; font-weight: 700;'>✅ Registration Approved!</h1>
            <p style='margin: 0; font-size: 16px; opacity: 0.95;'>已批准 · Your child can now start training</p>
        </div>
        
        <div style='padding: 32px 24px; background: white;'>
            <p style='font-size: 18px; font-weight: 600; color: #1e293b; margin: 0 0 16px 0;'>Dear {$parentName},</p>
            <p style='margin: 0 0 24px 0; font-size: 15px; color: #475569;'>Great news! Your child <strong>{$childName}</strong>'s registration has been <strong style='color: #16a34a;'>APPROVED</strong> by Wushu Sport Academy.</p>
            
            <div style='background: #dcfce7; border-left: 4px solid #16a34a; padding: 20px; margin: 24px 0; border-radius: 8px;'>
                <h3 style='margin: 0 0 16px 0; color: #166534; font-size: 18px;'>📝 Registration Details</h3>
                <table style='width: 100%;'>
                    <tr>
                        <td style='padding: 6px 0; color: #166534; font-weight: 600; width: 40%;'>Child Name:</td>
                        <td style='padding: 6px 0; color: #166534;'>{$childName}</td>
                    </tr>
                    <tr>
                        <td style='padding: 6px 0; color: #166534; font-weight: 600;'>Registration #:</td>
                        <td style='padding: 6px 0; color: #166534;'>{$registrationNumber}</td>
                    </tr>
                    <tr>
                        <td style='padding: 6px 0; color: #166534; font-weight: 600;'>Status:</td>
                        <td style='padding: 6px 0; color: #166534;'><span style='background: #22c55e; color: white; padding: 6px 16px; border-radius: 6px; font-size: 14px; font-weight: 700;'>APPROVED ✓</span></td>
                    </tr>
                </table>
            </div>
            
            <div style='background: #eff6ff; border-left: 4px solid #3b82f6; padding: 20px; border-radius: 8px; margin: 24px 0;'>
                <h3 style='margin: 0 0 16px 0; color: #1e40af; font-size: 18px;'>🔑 Parent Portal Access</h3>
                <table style='width: 100%;'>
                    <tr>
                        <td style='padding: 8px 0; color: #1e40af; font-weight: 600; width: 35%;'>Login URL:</td>
                        <td style='padding: 8px 0;'><a href='https://student.mlxysf.com' style='color: #2563eb; text-decoration: none; font-weight: 600;'>student.mlxysf.com</a></td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #1e40af; font-weight: 600;'>Email:</td>
                        <td style='padding: 8px 0; color: #1e40af;'>{$email}</td>
                    </tr>
                    <tr>
                        <td style='padding: 12px 0 8px 0; color: #1e40af; font-weight: 600; vertical-align: top;'>Password:</td>
                        <td style='padding: 12px 0 8px 0;'>
                            <div style='font-size: 24px; color: #dc2626; font-weight: bold; font-family: Courier, monospace; letter-spacing: 3px; background: #fff; padding: 12px 16px; border-radius: 6px; display: inline-block; border: 2px solid #3b82f6;'>{$password}</div>
                            <br>
                            <span style='font-size: 12px; color: #1e40af; display: block; margin-top: 6px;'>💡 Last 4 digits of your IC</span>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div style='background: #f0fdf4; padding: 20px; border-radius: 8px; margin: 24px 0; border-left: 4px solid #22c55e;'>
                <h4 style='margin: 0 0 12px 0; color: #166534; font-size: 16px;'>🎯 What You Can Do Now:</h4>
                <ul style='margin: 0; padding-left: 20px; color: #166534; font-size: 14px; line-height: 1.8;'>
                    <li><strong>Login</strong> to the parent portal using your credentials above</li>
                    <li><strong>View</strong> training schedules and class details</li>
                    <li><strong>Check</strong> attendance records for all your children</li>
                    <li><strong>Manage</strong> payments and view invoices</li>
                    <li><strong>Track</strong> your children's progress and achievements</li>
                </ul>
            </div>
            
            <div style='background: #fef3c7; padding: 16px; border-radius: 8px; border-left: 4px solid #f59e0b;'>
                <p style='margin: 0; font-size: 14px; color: #92400e;'>
                    <strong>⚠️ Important:</strong> Training starts according to the schedule you selected during registration. Please ensure your child arrives on time for the first session.
                </p>
            </div>
            
            <div style='text-align: center; margin: 32px 0 24px 0;'>
                <a href='https://student.mlxysf.com' style='display: inline-block; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: white; padding: 14px 32px; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);'>Login to Parent Portal →</a>
            </div>
        </div>
        
        <div style='text-align: center; padding: 24px; background: #f8fafc; color: #64748b; font-size: 13px; border-top: 1px solid #e2e8f0;'>
            <p style='margin: 0 0 8px 0; font-weight: 600; color: #1e293b; font-size: 15px;'>Wushu Sport Academy 武术体育学院</p>
            <p style='margin: 4px 0;'>📧 Email: admin@wushusportacademy.com</p>
            <p style='margin: 4px 0;'>📱 Phone: +60 12-345 6789</p>
            <p style='margin: 16px 0 0 0; font-size: 11px; color: #94a3b8;'>This is an automated email. Please do not reply directly to this message.</p>
        </div>
    </div>
</body>
</html>";

    return $html;
}

// If called directly (not included)
if (php_sapi_name() !== 'cli' && basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header('Content-Type: application/json');
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['registration_id'])) {
            throw new Exception('Missing registration_id');
        }
        
        $host = 'localhost';
        $dbname = 'wushuspo_portal';
        $username = 'wushuspo_admin';
        $password = '%==l;7tS*.OjXd**';
        
        $conn = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $result = sendApprovalNotification($input['registration_id'], $conn);
        
        echo json_encode($result);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
