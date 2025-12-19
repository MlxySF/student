<?php
/**
 * process_registration.php - Complete Registration Processing with PHPMailer
 * Handles student registration, account creation, and email notification
 * Stage 3: Multi-child parent system - auto-detects parent by email
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
// Helper: Generate password
// =========================
function generateRandomPassword(): string {
    $part1 = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 4);
    $part2 = substr(str_shuffle('abcdefghjkmnpqrstuvwxyz'), 0, 4);
    return $part1 . $part2;
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

    // Create new parent account
    $parentCode = generateParentCode($conn);
    $plainPassword = generateRandomPassword();
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

    error_log("[Parent] Created NEW parent account ID={$parentId}, code={$parentCode}, email={$email}");

    return [
        'id' => $parentId,
        'parent_id' => $parentCode,
        'is_new' => true,
        'plain_password' => $plainPassword,
    ];
}

function linkStudentToParent(PDO $conn, int $parentId, int $studentId, string $relationship = 'guardian'): void {
    // Update student record
    $stmt = $conn->prepare("UPDATE students SET parent_account_id = ?, student_type = 'child' WHERE id = ?");
    $stmt->execute([$parentId, $studentId]);

    // Insert relationship (ignore if already exists)
    $stmt = $conn->prepare("SELECT id FROM parent_child_relationships WHERE parent_id = ? AND student_id = ? LIMIT 1");
    $stmt->execute([$parentId, $studentId]);
    $exists = $stmt->fetchColumn();

    if (!$exists) {
        $stmt = $conn->prepare("INSERT INTO parent_child_relationships
            (parent_id, student_id, relationship, is_primary, can_manage_payments, can_view_attendance, created_at)
            VALUES (?, ?, ?, 1, 1, 1, NOW())");
        $stmt->execute([$parentId, $studentId, $relationship]);
        error_log("[Link] Linked student ID={$studentId} to parent ID={$parentId}");
    }
}

// ===============
// Email functions
// ===============

function sendRegistrationEmail($toEmail, $studentName, $registrationNumber, $password, $studentStatus, $isNewParent, $parentPassword = null) {
    $mail = new PHPMailer(true);
    try {
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
        $mail->Body    = getEmailHTMLContent($studentName, $registrationNumber, $toEmail, $password, $studentStatus, $isNewParent, $parentPassword);
        $mail->AltBody = "Registration Successful!";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error: {$mail->ErrorInfo}");
        return false;
    }
}

function getEmailHTMLContent($studentName, $registrationNumber, $toEmail, $childPassword, $studentStatus, $isNewParent, $parentPassword) {
    $parentSection = '';
    if ($isNewParent && $parentPassword) {
        $parentSection = "
        <div style='background: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; margin: 20px 0; border-radius: 8px;'>
            <h3 style='margin: 0 0 12px 0; color: #856404;'>🔑 Parent Account Created!</h3>
            <p style='margin: 0 0 8px 0; color: #856404;'><strong>This is your FIRST child registration.</strong> A parent account has been created for you.</p>
            <p style='margin: 0 0 8px 0; color: #856404;'><strong>Parent Login Email:</strong> {$toEmail}</p>
            <p style='margin: 0 0 8px 0; color: #856404;'><strong>Parent Password:</strong></p>
            <div style='font-size: 24px; color: #dc2626; font-weight: bold; font-family: monospace; letter-spacing: 3px; background: #fff; padding: 12px; border-radius: 6px; display: inline-block;'>{$parentPassword}</div>
            <p style='margin: 12px 0 0 0; font-size: 13px; color: #856404;'>⚠️ <strong>Save this password!</strong> Use it to login and manage all your children.</p>
        </div>
        ";
    } else {
        $parentSection = "
        <div style='background: #d1ecf1; border-left: 4px solid #0dcaf0; padding: 20px; margin: 20px 0; border-radius: 8px;'>
            <h3 style='margin: 0 0 12px 0; color: #0c5460;'>✅ Child Added to Your Account</h3>
            <p style='margin: 0; color: #0c5460;'>This child has been linked to your existing parent account. Login with your parent email to view all children.</p>
        </div>
        ";
    }

    return "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5; }
            .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color: white; padding: 32px 24px; text-align: center; }
            .header h1 { margin: 0 0 8px 0; font-size: 28px; font-weight: 700; }
            .content { padding: 32px 24px; background: white; }
            .child-info { background: #f8fafc; padding: 20px; margin: 20px 0; border-left: 4px solid #22c55e; border-radius: 8px; }
            .child-info h3 { margin: 0 0 12px 0; color: #166534; }
            .footer { text-align: center; padding: 24px; background: #f8fafc; color: #64748b; font-size: 13px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 Child Registered Successfully!</h1>
                <p>报名成功 · Registration Successful</p>
            </div>
            <div class='content'>
                <p style='font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 16px;'>Dear Parent,</p>
                <p style='margin-bottom: 16px;'>Your child has been successfully registered for Wushu training!</p>
                
                {$parentSection}
                
                <div class='child-info'>
                    <h3>👶 Child Details</h3>
                    <p style='margin: 4px 0;'><strong>Child Name:</strong> {$studentName}</p>
                    <p style='margin: 4px 0;'><strong>Student ID:</strong> {$registrationNumber}</p>
                    <p style='margin: 4px 0;'><strong>Status:</strong> {$studentStatus}</p>
                    <p style='margin: 4px 0;'><strong>Child Password:</strong> <code style='background: #fff; padding: 4px 8px; border: 1px solid #ddd; border-radius: 4px;'>{$childPassword}</code></p>
                </div>
                
                <div style='background: #eff6ff; border-left: 4px solid #3b82f6; padding: 16px; border-radius: 8px; margin: 20px 0;'>
                    <p style='margin: 0 0 8px 0; font-weight: 600; color: #1e40af;'>💡 To Register More Children:</p>
                    <p style='margin: 0; color: #1e40af; font-size: 14px;'>Simply use the <strong>same email address</strong> ({$toEmail}) when registering. All children will be automatically linked to your parent account!</p>
                </div>
            </div>
            <div class='footer'>
                <p><strong>Wushu Sport Academy 武术体育学院</strong></p>
                <p>📧 admin@wushusportacademy.com | 📱 +60 12-345 6789</p>
            </div>
        </div>
    </body>
    </html>
    ";
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

    $parentEmail = trim($data['email']); // This is the PARENT email from the form
    $childIC = trim($data['ic']);
    
    error_log("[Registration] Starting registration for parent email: {$parentEmail}, child IC: {$childIC}");

    // Check for duplicate CHILD (by IC), not by email
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

    // Student account password (for optional child login)
    $generatedPassword = generateRandomPassword();
    $hashedPassword    = password_hash($generatedPassword, PASSWORD_DEFAULT);

    $studentId    = $regNumber;
    $fullName     = trim($data['name_en']);
    $phone        = trim($data['phone']);
    $studentStatus = trim($data['status']);

    // ===============================
    // Find or Create Parent Account
    // ===============================

    $parentData = [
        'name'  => trim($data['parent_name']),
        'email' => $parentEmail, // Parent email
        'phone' => $phone,
        'ic'    => trim($data['parent_ic']),
    ];

    $parentAccountInfo = findOrCreateParentAccount($conn, $parentData);

    $parentAccountId     = $parentAccountInfo['id'];
    $parentCode          = $parentAccountInfo['parent_id'];
    $isNewParentAccount  = $parentAccountInfo['is_new'];
    $parentPlainPassword = $parentAccountInfo['plain_password'];

    // ======================
    // Create Student Account
    // ======================

    $stmt = $conn->prepare("INSERT INTO students
        (student_id, full_name, email, phone, password, student_status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $studentId,
        $fullName,
        $parentEmail, // Store parent email in student record
        $phone,
        $hashedPassword,
        $studentStatus,
    ]);
    $studentAccountId = (int)$conn->lastInsertId();
    
    error_log("[Student] Created student ID={$studentAccountId} ({$fullName})");

    // Link student to parent
    linkStudentToParent($conn, $parentAccountId, $studentAccountId, 'guardian');

    // ==========================
    // Insert Registration Record
    // ==========================

    $sql = "INSERT INTO registrations (
        registration_number, name_cn, name_en, ic, age, school, status,
        phone, email, level, events, schedule, parent_name, parent_ic,
        form_date, signature_base64, pdf_base64,
        payment_amount, payment_date, payment_receipt_base64, payment_status, class_count,
        student_account_id, account_created, password_generated,
        parent_account_id, registration_type, is_additional_child, created_at
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?, 'pending', ?,
        ?, 'yes', ?,
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
        $studentAccountId,
        $generatedPassword,
        $parentAccountId,
        $isNewParentAccount ? 0 : 1, // is_additional_child
    ]);

    $conn->commit();
    
    error_log("[Success] Reg#: {$regNumber}, Parent: {$parentCode} (ID:{$parentAccountId}), Student: {$studentAccountId}, IsNewParent: " . ($isNewParentAccount ? 'YES' : 'NO'));

    // Send email
    $emailSent = sendRegistrationEmail($parentEmail, $fullName, $regNumber, $generatedPassword, $studentStatus, $isNewParentAccount, $parentPlainPassword);

    echo json_encode([
        'success' => true,
        'registration_number' => $regNumber,
        'student_id' => $studentId,
        'email' => $parentEmail,
        'child_password' => $generatedPassword,
        'parent_account_id' => $parentAccountId,
        'parent_code' => $parentCode,
        'is_new_parent' => $isNewParentAccount,
        'parent_password' => $isNewParentAccount ? $parentPlainPassword : null,
        'email_sent' => $emailSent,
        'message' => $isNewParentAccount 
            ? 'Parent account created! Child registered successfully. You can now register more children using the same email.' 
            : 'Child added successfully to your parent account!',
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
