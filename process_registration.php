<?php
/**
 * process_registration.php - Complete Registration Processing with PHPMailer
 * Handles student registration, account creation, and email notification
 * Stage 3: Updated to support parent accounts and multi-child registration
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
    }
}

// ===============
// Email functions
// (existing sendRegistrationEmail + getEmailHTMLContent kept as-is)
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
        $mail->Subject = '🎊 Wushu Sport Academy - Registration Successful';
        $mail->Body    = getEmailHTMLContent($studentName, $registrationNumber, $toEmail, $password, $studentStatus);
        $mail->AltBody = "Registration Successful!";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error: {$mail->ErrorInfo}");
        return false;
    }
}

// (getEmailHTMLContent unchanged - omitted here for brevity in this snippet)
// ...

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

    // Existing logic: prevent duplicate non-rejected registrations for same email
    $stmt = $conn->prepare("SELECT id, payment_status, student_account_id FROM registrations WHERE email = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$email]);
    $existingRegistration = $stmt->fetch(PDO::FETCH_ASSOC);

    $existingStudent = null;
    if (!$existingRegistration || $existingRegistration['payment_status'] === 'rejected') {
        $stmt = $conn->prepare("SELECT id, student_id FROM students WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $existingStudent = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    $isReregistration = false;
    $oldRegistrationId = null;
    $oldStudentAccountId = null;

    if ($existingRegistration && $existingRegistration['payment_status'] === 'rejected') {
        $isReregistration = true;
        $oldRegistrationId = (int)$existingRegistration['id'];
        $oldStudentAccountId = (int)$existingRegistration['student_account_id'];
    } elseif ($existingStudent || ($existingRegistration && $existingRegistration['payment_status'] !== 'rejected')) {
        throw new Exception('Email already registered. Please use a different email address.');
    }

    // Generate registration number
    $year = date('Y');
    $stmt = $conn->prepare("SELECT COUNT(*) FROM registrations WHERE YEAR(created_at) = ?");
    $stmt->execute([$year]);
    $count = (int)$stmt->fetchColumn();
    $regNumber = 'WSA' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

    // Student account password (child or independent student)
    $generatedPassword = generateRandomPassword();
    $hashedPassword    = password_hash($generatedPassword, PASSWORD_DEFAULT);

    $studentId    = $regNumber;
    $fullName     = trim($data['name_en']);
    $phone        = trim($data['phone']);
    $studentStatus = trim($data['status']);

    // ===============================
    // NEW: Parent account resolution
    // ===============================

    $hasExistingParentAccount = !empty($data['has_parent_account']);
    $parentLoginPassword      = $data['parent_password_login'] ?? null;

    // Build parent data from form
    $parentData = [
        'name'  => trim($data['parent_name']),
        'email' => isset($data['parent_email']) && $data['parent_email'] !== ''
            ? trim($data['parent_email'])
            : trim($data['email']), // fallback: use student email if no separate parent email
        'phone' => $data['parent_phone'] ?? $phone,
        'ic'    => trim($data['parent_ic']),
    ];

    $parentAccountInfo = findOrCreateParentAccount(
        $conn,
        $parentData,
        $hasExistingParentAccount ? $parentLoginPassword : null
    );

    $parentAccountId    = $parentAccountInfo['id'];
    $isNewParentAccount = $parentAccountInfo['is_new'];
    $parentPlainPassword = $parentAccountInfo['plain_password']; // only for new parent

    // ======================
    // Create / update student
    // ======================

    $studentAccountId = null;

    if ($isReregistration && $oldStudentAccountId) {
        $stmt = $conn->prepare("UPDATE students 
            SET student_id = ?, full_name = ?, email = ?, phone = ?, password = ?, student_status = ?
            WHERE id = ?");
        $stmt->execute([
            $studentId,
            $fullName,
            $email,
            $phone,
            $hashedPassword,
            $studentStatus,
            $oldStudentAccountId,
        ]);
        $studentAccountId = $oldStudentAccountId;
    } else {
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
    }

    // Link student to parent (Stage 3 core)
    linkStudentToParent($conn, $parentAccountId, $studentAccountId, 'guardian');

    // ==========================
    // Insert / update registration
    // ==========================

    $commonRegFields = [
        ':reg_num'   => $regNumber,
        ':name_cn'   => isset($data['name_cn']) ? trim($data['name_cn']) : '',
        ':name_en'   => $fullName,
        ':ic'        => trim($data['ic']),
        ':age'       => (int)$data['age'],
        ':school'    => trim($data['school']),
        ':status'    => $studentStatus,
        ':phone'     => $phone,
        ':email'     => $email,
        ':level'     => isset($data['level']) ? trim($data['level']) : '',
        ':events'    => trim($data['events']),
        ':schedule'  => trim($data['schedule']),
        ':parent_name' => trim($data['parent_name']),
        ':parent_ic'   => trim($data['parent_ic']),
        ':form_date'   => $data['form_date'],
        ':signature_base64' => $data['signature_base64'],
        ':pdf_base64'       => $data['signed_pdf_base64'],
        ':payment_amount'   => (float)$data['payment_amount'],
        ':payment_date'     => $data['payment_date'],
        ':payment_receipt_base64' => $data['payment_receipt_base64'],
        ':class_count'      => (int)$data['class_count'],
        ':student_account_id' => $studentAccountId,
        ':password_generated' => $generatedPassword,
        ':parent_account_id'  => $parentAccountId,
        ':registration_type'  => 'parent_managed',
        ':is_additional_child' => $isNewParentAccount ? 0 : 1,
    ];

    if ($isReregistration && $oldRegistrationId) {
        $sql = "UPDATE registrations SET
            registration_number = :reg_num,
            name_cn = :name_cn,
            name_en = :name_en,
            ic = :ic,
            age = :age,
            school = :school,
            status = :status,
            phone = :phone,
            email = :email,
            level = :level,
            events = :events,
            schedule = :schedule,
            parent_name = :parent_name,
            parent_ic = :parent_ic,
            form_date = :form_date,
            signature_base64 = :signature_base64,
            pdf_base64 = :pdf_base64,
            payment_amount = :payment_amount,
            payment_date = :payment_date,
            payment_receipt_base64 = :payment_receipt_base64,
            payment_status = 'pending',
            class_count = :class_count,
            student_account_id = :student_account_id,
            account_created = 'yes',
            password_generated = :password_generated,
            parent_account_id = :parent_account_id,
            registration_type = :registration_type,
            is_additional_child = :is_additional_child,
            created_at = NOW()
            WHERE id = :old_reg_id";

        $stmt = $conn->prepare($sql);
        $commonRegFields[':old_reg_id'] = $oldRegistrationId;
        $stmt->execute($commonRegFields);
    } else {
        $sql = "INSERT INTO registrations (
            registration_number, name_cn, name_en, ic, age, school, status,
            phone, email, level, events, schedule, parent_name, parent_ic,
            form_date, signature_base64, pdf_base64,
            payment_amount, payment_date, payment_receipt_base64, payment_status, class_count,
            student_account_id, account_created, password_generated,
            parent_account_id, registration_type, is_additional_child, created_at
        ) VALUES (
            :reg_num, :name_cn, :name_en, :ic, :age, :school, :status,
            :phone, :email, :level, :events, :schedule, :parent_name, :parent_ic,
            :form_date, :signature_base64, :pdf_base64,
            :payment_amount, :payment_date, :payment_receipt_base64, 'pending', :class_count,
            :student_account_id, 'yes', :password_generated,
            :parent_account_id, :registration_type, :is_additional_child, NOW()
        )";

        $stmt = $conn->prepare($sql);
        $stmt->execute($commonRegFields);
    }

    $conn->commit();

    // For now, keep sending student login email (can later switch to parent email template)
    $emailSent = sendRegistrationEmail($email, $fullName, $regNumber, $generatedPassword, $studentStatus);

    echo json_encode([
        'success' => true,
        'registration_number' => $regNumber,
        'student_id' => $studentId,
        'email' => $email,
        'password' => $generatedPassword,
        'status' => $studentStatus,
        'email_sent' => $emailSent,
        'is_reregistration' => $isReregistration,
        'parent_account_id' => $parentAccountId,
        'is_new_parent' => $isNewParentAccount,
        'message' => 'Registration successful with parent account linking. Account has been created.',
    ]);

} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('DB error in process_registration.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error.']);
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Error in process_registration.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
