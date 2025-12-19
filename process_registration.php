<?php
/**
 * process_registration.php - Complete Registration Processing with PHPMailer
 * Handles student registration and parent account creation
 * Stage 4: Parent-only accounts - Children managed under parent account
 * Password: Parent IC last 4 digits
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// =========================
// Helper: Generate password from IC
// =========================
function generatePasswordFromIC(string $ic): string {
    // Remove dashes and get last 4 digits
    $icClean = str_replace('-', '', $ic);
    $last4 = substr($icClean, -4);
    return $last4;
}

// ==========================================
// Parent account helper functions
// ==========================================

function generateParentCode(PDO $conn): string {
    $year = date('Y');
    $stmt = $conn->prepare("SELECT COUNT(*) FROM parent_accounts WHERE YEAR(created_at) = ?");
    $stmt->execute([$year]);
    $count = (int)$stmt->fetchColumn();
    return 'PAR-' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
}

function findOrCreateParentAccount(PDO $conn, array $parentData): array {
    $email = trim($parentData['email']);

    // Check if parent account already exists with this email
    $stmt = $conn->prepare("SELECT id, parent_id, password FROM parent_accounts WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        error_log("[Parent] Found existing parent account ID={$existing['id']} for email={$email}");
        return [
            'id' => (int)$existing['id'],
            'parent_id' => $existing['parent_id'],
            'is_new' => false,
            'plain_password' => null,
        ];
    }

    // Create new parent account - Password is parent IC last 4 digits
    $parentCode = generateParentCode($conn);
    $plainPassword = generatePasswordFromIC($parentData['ic']);
    $hash = password_hash($plainPassword, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO parent_accounts
        (parent_id, full_name, email, phone, ic_number, password, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())");

    $stmt->execute([
        $parentCode,
        trim($parentData['name']),
        $email,
        trim($parentData['phone'] ?? ''),
        trim($parentData['ic'] ?? ''),
        $hash,
    ]);

    $parentId = (int)$conn->lastInsertId();

    error_log("[Parent] Created NEW parent account ID={$parentId}, code={$parentCode}, email={$email}, password={$plainPassword}");

    return [
        'id' => $parentId,
        'parent_id' => $parentCode,
        'is_new' => true,
        'plain_password' => $plainPassword,
    ];
}

// ===============
// Email functions
// ===============

function sendRegistrationEmail($toEmail, $childName, $registrationNumber, $childStatus, $isNewParent, $parentPassword, $childrenCount) {
    $mail = new PHPMailer(true);
    try {
        error_log("[Email] Sending to: {$toEmail}, isNewParent: " . ($isNewParent ? 'YES' : 'NO') . ", parentPassword: " . ($parentPassword ?? 'NULL'));
        
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'chaichonghern@gmail.com';
        $mail->Password   = 'kyyj elhp dkdw gvki';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('noreply@wushusportacademy.com', 'Wushu Sport Academy');
        $mail->addAddress($toEmail);
        $mail->addReplyTo('admin@wushusportacademy.com', 'Academy Admin');

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = '🎊 Wushu Sport Academy - Child Registration Successful';
        $mail->Body    = getRegistrationEmailHTML($childName, $registrationNumber, $toEmail, $childStatus, $isNewParent, $parentPassword, $childrenCount);
        $mail->AltBody = "Registration Successful!";

        $mail->send();
        error_log("[Email] Successfully sent to {$toEmail}");
        return true;
    } catch (Exception $e) {
        error_log("[Email] Error: {$mail->ErrorInfo}");
        return false;
    }
}

function sendApprovalEmail($toEmail, $parentName, $childName, $registrationNumber, $parentPassword) {
    $mail = new PHPMailer(true);
    try {
        error_log("[Approval Email] Sending to: {$toEmail}");
        
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'chaichonghern@gmail.com';
        $mail->Password   = 'kyyj elhp dkdw gvki';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('noreply@wushusportacademy.com', 'Wushu Sport Academy');
        $mail->addAddress($toEmail);
        $mail->addReplyTo('admin@wushusportacademy.com', 'Academy Admin');

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = '✅ Registration Approved - Wushu Sport Academy';
        $mail->Body    = getApprovalEmailHTML($parentName, $childName, $registrationNumber, $toEmail, $parentPassword);
        $mail->AltBody = "Your registration has been approved!";

        $mail->send();
        error_log("[Approval Email] Successfully sent to {$toEmail}");
        return true;
    } catch (Exception $e) {
        error_log("[Approval Email] Error: {$mail->ErrorInfo}");
        return false;
    }
}

function getRegistrationEmailHTML($childName, $registrationNumber, $toEmail, $childStatus, $isNewParent, $parentPassword, $childrenCount) {
    // Build parent section
    $parentSection = '';
    
    if ($isNewParent) {
        // FIRST CHILD - Show parent account details
        if (!$parentPassword || trim($parentPassword) === '') {
            error_log("[Email Template] WARNING: isNewParent=true but parentPassword is empty!");
            $parentPassword = "ERROR: Password not generated";
        }
        
        $parentSection = "
        <div style='background: #fff3cd; border-left: 4px solid #ffc107; padding: 24px; margin: 24px 0; border-radius: 8px;'>
            <h3 style='margin: 0 0 16px 0; color: #856404; font-size: 20px;'>🔑 Parent Account Created!</h3>
            <p style='margin: 0 0 12px 0; color: #856404; font-size: 15px;'><strong>This is your FIRST child registration.</strong> A parent account has been created for you to manage all your children.</p>
            
            <table style='width: 100%; margin: 16px 0;'>
                <tr>
                    <td style='padding: 8px 0; color: #856404; font-weight: 600; width: 40%;'>Login Email:</td>
                    <td style='padding: 8px 0; color: #856404;'>{$toEmail}</td>
                </tr>
                <tr>
                    <td style='padding: 12px 0 8px 0; color: #856404; font-weight: 600; vertical-align: top;'>Password:</td>
                    <td style='padding: 12px 0 8px 0;'>
                        <div style='font-size: 28px; color: #dc2626; font-weight: bold; font-family: Courier, monospace; letter-spacing: 4px; background: #fff; padding: 16px 20px; border-radius: 8px; display: inline-block; border: 2px solid #ffc107;'>{$parentPassword}</div>
                        <br>
                        <span style='font-size: 13px; color: #856404; display: block; margin-top: 8px;'>💡 This is the <strong>last 4 digits of your (parent) IC number</strong></span>
                    </td>
                </tr>
            </table>
            
            <div style='background: #fff; padding: 12px 16px; border-radius: 6px; margin-top: 16px;'>
                <p style='margin: 0; font-size: 14px; color: #856404;'>⚠️ <strong>IMPORTANT:</strong> Save this password! You'll use it to:</p>
                <ul style='margin: 8px 0 0 20px; padding: 0; color: #856404; font-size: 14px;'>
                    <li>Login to the parent portal</li>
                    <li>View all your children's data</li>
                    <li>Manage payments and attendance</li>
                    <li>Register additional children</li>
                </ul>
            </div>
        </div>
        ";
    } else {
        // ADDITIONAL CHILD - Show confirmation
        $parentSection = "
        <div style='background: #d1ecf1; border-left: 4px solid #0dcaf0; padding: 20px; margin: 20px 0; border-radius: 8px;'>
            <h3 style='margin: 0 0 12px 0; color: #0c5460; font-size: 18px;'>✅ Child Added to Your Parent Account</h3>
            <p style='margin: 0 0 8px 0; color: #0c5460; font-size: 14px;'>This is child #{$childrenCount} linked to your parent account.</p>
            <p style='margin: 0; color: #0c5460; font-size: 14px;'>Login with your parent email and password to view all children.</p>
        </div>
        ";
    }

    $html = "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Registration Successful</title>
</head>
<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5;'>
    <div style='max-width: 600px; margin: 20px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
        <div style='background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color: white; padding: 40px 24px; text-align: center;'>
            <h1 style='margin: 0 0 8px 0; font-size: 32px; font-weight: 700;'>🎉 Registration Successful!</h1>
            <p style='margin: 0; font-size: 16px; opacity: 0.95;'>报名成功 · Child Registered</p>
        </div>
        
        <div style='padding: 32px 24px; background: white;'>
            <p style='font-size: 18px; font-weight: 600; color: #1e293b; margin: 0 0 16px 0;'>Dear Parent,</p>
            <p style='margin: 0 0 24px 0; font-size: 15px; color: #475569;'>Your child <strong>{$childName}</strong> has been successfully registered for Wushu training at Wushu Sport Academy!</p>
            
            {$parentSection}
            
            <div style='background: #f0fdf4; border-left: 4px solid #22c55e; padding: 20px; margin: 24px 0; border-radius: 8px;'>
                <h3 style='margin: 0 0 16px 0; color: #166534; font-size: 18px;'>👶 Child Registration Details</h3>
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
                        <td style='padding: 6px 0; color: #166534;'><span style='background: #dcfce7; padding: 4px 12px; border-radius: 4px; font-size: 13px; font-weight: 600;'>{$childStatus}</span></td>
                    </tr>
                </table>
                <p style='margin: 16px 0 0 0; font-size: 13px; color: #166534; font-style: italic;'>All children are managed under your parent account. No separate child login needed.</p>
            </div>
            
            <div style='background: #eff6ff; border-left: 4px solid #3b82f6; padding: 20px; border-radius: 8px; margin: 24px 0;'>
                <p style='margin: 0 0 8px 0; font-weight: 600; color: #1e40af; font-size: 16px;'>💡 Register More Children</p>
                <p style='margin: 0; color: #1e40af; font-size: 14px; line-height: 1.6;'>To register additional children, simply use the <strong>same email address</strong> ({$toEmail}) when filling the registration form. All your children will be automatically linked to your parent account!</p>
            </div>
            
            <div style='background: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; border-radius: 8px; margin: 24px 0;'>
                <p style='margin: 0 0 8px 0; font-weight: 600; color: #92400e; font-size: 15px;'>🔐 Simple Password System</p>
                <p style='margin: 0; color: #92400e; font-size: 13px; line-height: 1.6;'>Your password is the <strong>last 4 digits of your (parent) IC number</strong>. Easy to remember!</p>
            </div>
            
            <div style='background: #f8fafc; padding: 20px; border-radius: 8px; margin: 24px 0;'>
                <h4 style='margin: 0 0 12px 0; color: #1e293b; font-size: 16px;'>📋 Next Steps:</h4>
                <ol style='margin: 0; padding-left: 20px; color: #475569; font-size: 14px; line-height: 1.8;'>
                    <li>Your payment is under review by the academy</li>
                    <li>You'll receive an <strong>approval notification email</strong> once verified</li>
                    <li>After approval, login to the parent portal with your credentials</li>
                    <li>View schedules, attendance, invoices, and manage all your children</li>
                </ol>
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

// ============================
// MAIN REGISTRATION PROCESSING
// ============================

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) {
        throw new Exception('Invalid JSON data received');
    }

    $required = [
        'name_en', 'ic', 'age', 'school', 'status', 'phone', 'email',
        'events', 'schedule', 'parent_name', 'parent_ic',
        'form_date', 'signature_base64', 'signed_pdf_base64',
        'payment_amount', 'payment_date', 'payment_receipt_base64', 'class_count'
    ];

    foreach ($required as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            throw new Exception("Missing or empty required field: {$field}");
        }
    }

    $host = 'localhost';
    $dbname = 'mlxysf_student_portal';
    $username = 'mlxysf_student_portal';
    $password = 'YAjv86kdSAPpw';

    $conn = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $conn->beginTransaction();

    $parentEmail = trim($data['email']);
    $childIC = trim($data['ic']);
    
    error_log("[Registration] Starting registration for parent email: {$parentEmail}, child IC: {$childIC}");

    // Check for duplicate CHILD (by IC)
    $stmt = $conn->prepare("SELECT id, registration_number FROM registrations WHERE ic = ? AND payment_status != 'rejected' ORDER BY id DESC LIMIT 1");
    $stmt->execute([$childIC]);
    $existingChild = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingChild) {
        throw new Exception('A child with this IC number (' . $childIC . ') is already registered (Reg#: ' . $existingChild['registration_number'] . '). Please check the IC number.');
    }

    // Generate registration number
    $year = date('Y');
    $stmt = $conn->prepare("SELECT COUNT(*) FROM registrations WHERE YEAR(created_at) = ?");
    $stmt->execute([$year]);
    $count = (int)$stmt->fetchColumn();
    $regNumber = 'WSA' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

    $fullName     = trim($data['name_en']);
    $phone        = trim($data['phone']);
    $studentStatus = trim($data['status']);

    // ===============================
    // Find or Create Parent Account
    // ===============================

    $parentData = [
        'name'  => trim($data['parent_name']),
        'email' => $parentEmail,
        'phone' => $phone,
        'ic'    => trim($data['parent_ic']),
    ];

    $parentAccountInfo = findOrCreateParentAccount($conn, $parentData);

    $parentAccountId     = $parentAccountInfo['id'];
    $parentCode          = $parentAccountInfo['parent_id'];
    $isNewParentAccount  = $parentAccountInfo['is_new'];
    $parentPlainPassword = $parentAccountInfo['plain_password'];
    
    // Count how many children this parent has (including this new one)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM registrations WHERE email = ? AND payment_status != 'rejected'");
    $stmt->execute([$parentEmail]);
    $childrenCount = (int)$stmt->fetchColumn() + 1;
    
    error_log("[Parent Info] ID={$parentAccountId}, Code={$parentCode}, IsNew={$isNewParentAccount}, Password=" . ($parentPlainPassword ?? 'NULL') . ", Children={$childrenCount}");

    // Parent password for email (always show for reference)
    $parentPasswordForEmail = $isNewParentAccount ? $parentPlainPassword : generatePasswordFromIC(trim($data['parent_ic']));

    // ==========================
    // Insert Registration Record
    // ==========================

    $sql = "INSERT INTO registrations (
        registration_number, name_cn, name_en, ic, age, school, status,
        phone, email, level, events, schedule, parent_name, parent_ic,
        form_date, signature_base64, pdf_base64,
        payment_amount, payment_date, payment_receipt_base64, payment_status, class_count,
        parent_account_id, registration_type, is_additional_child, created_at
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?, 'pending', ?,
        ?, 'parent_managed', ?, NOW()
    )";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $regNumber,
        isset($data['name_cn']) ? trim($data['name_cn']) : '',
        $fullName,
        $childIC,
        (int)$data['age'],
        trim($data['school']),
        $studentStatus,
        $phone,
        $parentEmail,
        isset($data['level']) ? trim($data['level']) : '',
        trim($data['events']),
        trim($data['schedule']),
        trim($data['parent_name']),
        trim($data['parent_ic']),
        $data['form_date'],
        $data['signature_base64'],
        $data['signed_pdf_base64'],
        (float)$data['payment_amount'],
        $data['payment_date'],
        $data['payment_receipt_base64'],
        (int)$data['class_count'],
        $parentAccountId,
        $isNewParentAccount ? 0 : 1, // is_additional_child
    ]);

    $conn->commit();
    
    error_log("[Success] Reg#: {$regNumber}, Parent: {$parentCode} (ID:{$parentAccountId}), Children Count: {$childrenCount}, IsNewParent: " . ($isNewParentAccount ? 'YES' : 'NO'));

    // Send registration email
    $emailSent = sendRegistrationEmail(
        $parentEmail, 
        $fullName, 
        $regNumber,
        $studentStatus, 
        $isNewParentAccount, 
        $parentPasswordForEmail,
        $childrenCount
    );

    echo json_encode([
        'success' => true,
        'registration_number' => $regNumber,
        'email' => $parentEmail,
        'password' => $parentPasswordForEmail,
        'parent_account_id' => $parentAccountId,
        'parent_code' => $parentCode,
        'is_new_parent' => $isNewParentAccount,
        'children_count' => $childrenCount,
        'email_sent' => $emailSent,
        'message' => $isNewParentAccount 
            ? 'Parent account created! Child registered successfully. Check your email for login credentials.' 
            : "Child #{$childrenCount} added successfully to your parent account!",
    ]);

} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('DB error in process_registration.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Error in process_registration.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
