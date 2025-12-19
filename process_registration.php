<?php
/**
 * process_registration.php - Complete Registration Processing with PHPMailer
 * Handles student registration, account creation, and email notification
 * Stage 3: Updated to support parent accounts and multi-child registration
 * FIXED: Allow same parent email for multiple children
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
// NEW: Parent account helper functions (S3)
// ==========================================

/**
 * Generate a new parent_id like PAR-2025-0001
 */
function generateParentCode(PDO $conn): string {
    $year = date('Y');
    $stmt = $conn->prepare("SELECT COUNT(*) FROM parent_accounts WHERE YEAR(created_at) = ?");
    $stmt->execute([$year]);
    $count = (int)$stmt->fetchColumn();
    return 'PAR-' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
}

/**
 * Find existing parent by email, or create a new parent account.
 * When $loginPassword is provided, verifies password for existing parent.
 *
 * Returns array: [ 'id' => int, 'is_new' => bool, 'plain_password' => string|null ]
 */
function findOrCreateParentAccount(PDO $conn, array $parentData, ?string $loginPassword = null): array {
    $email = trim($parentData['email']);

    // 1) Check existing parent by email
    $stmt = $conn->prepare("SELECT id, password FROM parent_accounts WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // If login password is supplied (scenario: link additional child), verify it
        if ($loginPassword !== null && $loginPassword !== '') {
            if (!password_verify($loginPassword, $existing['password'])) {
                throw new Exception('Invalid parent email/password. Unable to link to existing parent account.');
            }
        }
        error_log("[Stage3] Using existing parent account ID={$existing['id']} ({$email})");
        return [
            'id' => (int)$existing['id'],
            'is_new' => false,
            'plain_password' => null,
        ];
    }

    // 2) Create new parent account
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

    error_log("[Stage3] Created new parent account ID={$parentId} ({$email})");

    return [
        'id' => $parentId,
        'is_new' => true,
        'plain_password' => $plainPassword,
    ];
}

/**
 * Link student to parent via parent_child_relationships and students.parent_account_id
 */
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
        error_log("[Stage3] Linked student ID={$studentId} to parent ID={$parentId}");
    }
}

// ===============
// Email functions
// ===============

function sendRegistrationEmail($toEmail, $studentName, $registrationNumber, $password, $studentStatus) {
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
        $mail->addAddress($toEmail, $studentName);
        $mail->addReplyTo('admin@wushusportacademy.com', 'Academy Admin');

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = '🎊 Wushu Sport Academy - Child Registration Successful';
        $mail->Body    = getEmailHTMLContent($studentName, $registrationNumber, $toEmail, $password, $studentStatus);
        $mail->AltBody = "Registration Successful!";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error: {$mail->ErrorInfo}");
        return false;
    }
}

function getEmailHTMLContent($studentName, $registrationNumber, $toEmail, $password, $studentStatus) {
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
            .credentials { background: #f8fafc; padding: 24px; margin: 24px 0; border-left: 4px solid #fbbf24; border-radius: 8px; }
            .credentials h3 { margin: 0 0 16px 0; font-size: 18px; color: #1e293b; }
            .password-highlight { font-size: 22px; color: #dc2626; font-weight: bold; font-family: 'Courier New', monospace; letter-spacing: 3px; background: #fef2f2; padding: 12px 16px; border-radius: 6px; display: inline-block; margin-top: 4px; }
            .footer { text-align: center; padding: 24px; background: #f8fafc; color: #64748b; font-size: 13px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Child Registered Successfully!</h1>
                <p>报名成功 · Registration Successful</p>
            </div>
            <div class='content'>
                <p style='font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 16px;'>Dear Parent,</p>
                <p style='margin-bottom: 16px;'>Your child <strong>{$studentName}</strong> has been successfully registered!</p>
                <p style='color: #64748b; margin-bottom: 24px;'>您的孩子已成功注册。</p>
                
                <div class='credentials'>
                    <h3>🔑 Child Account Details</h3>
                    <p><strong>Child Name:</strong> {$studentName}</p>
                    <p><strong>Student ID:</strong> {$registrationNumber}</p>
                    <p><strong>Status:</strong> {$studentStatus}</p>
                    <p><strong>Login Email:</strong> {$toEmail}</p>
                    <p><strong>Password:</strong><br><div class='password-highlight'>{$password}</div></p>
                </div>
                
                <p style='font-size: 14px; color: #64748b;'><strong>💡 To register more children:</strong> Use the same parent email to link them to your account.</p>
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

    $email = trim($data['email']);
    
    error_log("[Registration] Starting registration for email: {$email}");

    // FIXED: Check for duplicate CHILD (by IC), not by email
    // This allows multiple children to share the same parent email
    $childIC = trim($data['ic']);
    $stmt = $conn->prepare("SELECT id FROM registrations WHERE ic = ? AND payment_status != 'rejected' ORDER BY id DESC LIMIT 1");
    $stmt->execute([$childIC]);
    $existingChild = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingChild) {
        throw new Exception('A child with this IC number is already registered. Please use a different IC or contact admin.');
    }

    // Generate registration number
    $year = date('Y');
    $stmt = $conn->prepare("SELECT COUNT(*) FROM registrations WHERE YEAR(created_at) = ?");
    $stmt->execute([$year]);
    $count = (int)$stmt->fetchColumn();
    $regNumber = 'WSA' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

    // Student account password (child)
    $generatedPassword = generateRandomPassword();
    $hashedPassword    = password_hash($generatedPassword, PASSWORD_DEFAULT);

    $studentId    = $regNumber;
    $fullName     = trim($data['name_en']);
    $phone        = trim($data['phone']);
    $studentStatus = trim($data['status']);

    // ===============================
    // Parent account resolution
    // ===============================

    $parentData = [
        'name'  => trim($data['parent_name']),
        'email' => $email, // Use the email as parent email
        'phone' => $phone,
        'ic'    => trim($data['parent_ic']),
    ];

    $parentAccountInfo = findOrCreateParentAccount($conn, $parentData, null);

    $parentAccountId     = $parentAccountInfo['id'];
    $isNewParentAccount  = $parentAccountInfo['is_new'];
    $parentPlainPassword = $parentAccountInfo['plain_password'];

    // ======================
    // Create student
    // ======================

    $stmt = $conn->prepare("INSERT INTO students
        (student_id, full_name, email, phone, password, student_status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $studentId,
        $fullName,
        $email,
        $phone,
        $hashedPassword,
        $studentStatus,
    ]);
    $studentAccountId = (int)$conn->lastInsertId();
    
    error_log("[Registration] Created student ID={$studentAccountId}, linking to parent ID={$parentAccountId}");

    // Link student to parent
    linkStudentToParent($conn, $parentAccountId, $studentAccountId, 'guardian');

    // ==========================
    // Insert registration
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
        $email,
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
    
    error_log("[Registration] Success! Reg#: {$regNumber}, Parent: {$parentAccountId}, Student: {$studentAccountId}");

    // Send email
    $emailSent = sendRegistrationEmail($email, $fullName, $regNumber, $generatedPassword, $studentStatus);

    echo json_encode([
        'success' => true,
        'registration_number' => $regNumber,
        'student_id' => $studentId,
        'email' => $email,
        'password' => $generatedPassword,
        'status' => $studentStatus,
        'email_sent' => $emailSent,
        'parent_account_id' => $parentAccountId,
        'is_new_parent' => $isNewParentAccount,
        'message' => $isNewParentAccount 
            ? 'Registration successful! Parent account created. You can now register more children using the same email.' 
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
