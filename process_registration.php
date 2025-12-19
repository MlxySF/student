<?php
/**
 * process_registration.php - Complete Registration Processing with PHPMailer
 * Handles student registration, account creation, and email notification
 * Stage 3: Updated to support parent accounts and multi-child registration
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1); // TEMPORARILY SHOW ERRORS FOR DEBUGGING
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

function getEmailHTMLContent($studentName, $registrationNumber, $email, $password, $studentStatus) {
    return "<h1>Welcome to Wushu Sport Academy!</h1>
    <p>Dear {$studentName},</p>
    <p>Your registration ({$registrationNumber}) has been received!</p>
    <p>Login: {$email}</p>
    <p>Password: {$password}</p>
    <p>Status: {$studentStatus}</p>";
}

// ============================
// MAIN REGISTRATION PROCESSING
// ============================

try {
    error_log("=== Registration Process Started ===");
    
    $input = file_get_contents('php://input');
    error_log("Raw input length: " . strlen($input));
    
    $data = json_decode($input, true);
    if (!$data) {
        $jsonError = json_last_error_msg();
        error_log("JSON decode error: {$jsonError}");
        throw new Exception('Invalid JSON data received: ' . $jsonError);
    }

    error_log("Decoded data keys: " . implode(', ', array_keys($data)));

    $required = [
        'name_en', 'ic', 'age', 'school', 'status', 'phone', 'email',
        'events', 'schedule', 'parent_name', 'parent_ic',
        'form_date', 'signature_base64', 'signed_pdf_base64',
        'payment_amount', 'payment_date', 'payment_receipt_base64', 'class_count'
    ];

    foreach ($required as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            error_log("Missing field: {$field}");
            throw new Exception("Missing or empty required field: {$field}");
        }
    }

    error_log("All required fields present");

    $host = 'localhost';
    $dbname = 'mlxysf_student_portal';
    $username = 'mlxysf_student_portal';
    $password_db = 'YAjv86kdSAPpw';

    error_log("Connecting to database...");
    $conn = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password_db);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    error_log("Database connected successfully");

    $conn->beginTransaction();
    error_log("Transaction started");

    $email = trim($data['email']);
    error_log("Processing registration for email: {$email}");

    // Check for existing registration
    $stmt = $conn->prepare("SELECT id, payment_status, student_account_id FROM registrations WHERE email = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$email]);
    $existingRegistration = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingRegistration) {
        error_log("Found existing registration: ID={$existingRegistration['id']}, Status={$existingRegistration['payment_status']}");
    } else {
        error_log("No existing registration found");
    }

    $existingStudent = null;
    if (!$existingRegistration || $existingRegistration['payment_status'] === 'rejected') {
        $stmt = $conn->prepare("SELECT id, student_id FROM students WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $existingStudent = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existingStudent) {
            error_log("Found existing student: ID={$existingStudent['id']}");
        }
    }

    $isReregistration = false;
    $oldRegistrationId = null;
    $oldStudentAccountId = null;

    if ($existingRegistration && $existingRegistration['payment_status'] === 'rejected') {
        $isReregistration = true;
        $oldRegistrationId = (int)$existingRegistration['id'];
        $oldStudentAccountId = (int)$existingRegistration['student_account_id'];
        error_log("Reregistration detected: Old Reg ID={$oldRegistrationId}, Old Student ID={$oldStudentAccountId}");
    } elseif ($existingStudent || ($existingRegistration && $existingRegistration['payment_status'] !== 'rejected')) {
        error_log("Email already registered (not rejected)");
        throw new Exception('Email already registered. Please use a different email address.');
    }

    // Generate registration number
    $year = date('Y');
    $stmt = $conn->prepare("SELECT COUNT(*) FROM registrations WHERE YEAR(created_at) = ?");
    $stmt->execute([$year]);
    $count = (int)$stmt->fetchColumn();
    $regNumber = 'WSA' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    error_log("Generated registration number: {$regNumber}");

    // Student account password
    $generatedPassword = generateRandomPassword();
    $hashedPassword    = password_hash($generatedPassword, PASSWORD_DEFAULT);

    $studentId    = $regNumber;
    $fullName     = trim($data['name_en']);
    $phone        = trim($data['phone']);
    $studentStatus = trim($data['status']);

    error_log("Student details - Name: {$fullName}, Phone: {$phone}, Status: {$studentStatus}");

    // ===============================
    // Parent account resolution
    // ===============================

    $hasExistingParentAccount = !empty($data['has_parent_account']);
    $parentLoginPassword      = $data['parent_password_login'] ?? null;

    error_log("Has existing parent account: " . ($hasExistingParentAccount ? 'YES' : 'NO'));

    // Build parent data from form
    $parentData = [
        'name'  => trim($data['parent_name']),
        'email' => isset($data['parent_email']) && $data['parent_email'] !== ''
            ? trim($data['parent_email'])
            : trim($data['email']),
        'phone' => $data['parent_phone'] ?? $phone,
        'ic'    => trim($data['parent_ic']),
    ];

    error_log("Parent data - Name: {$parentData['name']}, Email: {$parentData['email']}, IC: {$parentData['ic']}");

    try {
        $parentAccountInfo = findOrCreateParentAccount(
            $conn,
            $parentData,
            $hasExistingParentAccount ? $parentLoginPassword : null
        );
        error_log("Parent account resolved - ID: {$parentAccountInfo['id']}, Is New: " . ($parentAccountInfo['is_new'] ? 'YES' : 'NO'));
    } catch (Exception $e) {
        error_log("Error in findOrCreateParentAccount: " . $e->getMessage());
        throw $e;
    }

    $parentAccountId    = $parentAccountInfo['id'];
    $isNewParentAccount = $parentAccountInfo['is_new'];
    $parentPlainPassword = $parentAccountInfo['plain_password'];

    // ======================
    // Create / update student
    // ======================

    $studentAccountId = null;

    if ($isReregistration && $oldStudentAccountId) {
        error_log("Updating existing student account ID: {$oldStudentAccountId}");
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
        error_log("Student account updated successfully");
    } else {
        error_log("Creating new student account");
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
        error_log("Student account created with ID: {$studentAccountId}");
    }

    // Link student to parent
    error_log("Linking student {$studentAccountId} to parent {$parentAccountId}");
    try {
        linkStudentToParent($conn, $parentAccountId, $studentAccountId, 'guardian');
        error_log("Student linked to parent successfully");
    } catch (Exception $e) {
        error_log("Error linking student to parent: " . $e->getMessage());
        throw $e;
    }

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
        error_log("Updating existing registration ID: {$oldRegistrationId}");
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
        error_log("Registration updated successfully");
    } else {
        error_log("Creating new registration record");
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
        error_log("Registration record created successfully");
    }

    $conn->commit();
    error_log("Transaction committed successfully");

    // Send email
    error_log("Sending registration email to {$email}");
    $emailSent = sendRegistrationEmail($email, $fullName, $regNumber, $generatedPassword, $studentStatus);
    error_log("Email sent: " . ($emailSent ? 'SUCCESS' : 'FAILED'));

    error_log("=== Registration Process Completed Successfully ===");

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
    error_log("=== PDO EXCEPTION ===");
    error_log("Error Message: " . $e->getMessage());
    error_log("Error Code: " . $e->getCode());
    error_log("Error File: " . $e->getFile() . " Line: " . $e->getLine());
    error_log("Stack Trace: " . $e->getTraceAsString());
    
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
        error_log("Transaction rolled back");
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage(),
        'details' => 'Please check error_log.txt for more details'
    ]);
} catch (Exception $e) {
    error_log("=== GENERAL EXCEPTION ===");
    error_log("Error Message: " . $e->getMessage());
    error_log("Error File: " . $e->getFile() . " Line: " . $e->getLine());
    error_log("Stack Trace: " . $e->getTraceAsString());
    
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
        error_log("Transaction rolled back");
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
