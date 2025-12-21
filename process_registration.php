<?php
/**
 * process_registration.php - Complete Registration Processing with PHPMailer
 * Handles student registration, account creation, and email notification
 * ⭐ UPDATED: Payment receipts now saved to local files instead of database base64
 * 
 * Key Changes:
 * - Saves payment receipts to uploads/payment_receipts/ directory
 * - Stores only filename in database instead of full base64 data
 * - Uses file_storage_helper.php for secure file management
 * - Maintains all existing functionality
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

// ⭐ NEW: Include file storage helper
require_once 'file_storage_helper.php';

// Admin email configuration
define('ADMIN_EMAIL', 'chaichonghern@gmail.com');
define('ADMIN_NAME', 'Academy Admin');

// =========================
// Helper: Generate password from IC
// =========================
function generatePasswordFromIC(string $ic): string {
    // Remove dashes and get last 4 digits
    $icClean = str_replace('-', '', $ic);
    $last4 = substr($icClean, -4);
    return $last4;
}

// =========================
// Helper: Generate unique randomized registration number
// =========================
function generateUniqueRegistrationNumber(PDO $conn): string {
    $year = date('Y');
    $maxAttempts = 100;
    $attempt = 0;
    
    while ($attempt < $maxAttempts) {
        $randomSuffix = str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        $regNumber = 'WSA' . $year . '-' . $randomSuffix;
        
        $stmt = $conn->prepare("SELECT COUNT(*) FROM registrations WHERE registration_number = ?");
        $stmt->execute([$regNumber]);
        $count = (int)$stmt->fetchColumn();
        
        if ($count === 0) {
            error_log("[Reg Number] Generated: {$regNumber}");
            return $regNumber;
        }
        $attempt++;
    }
    
    $fallbackSuffix = substr(str_replace('.', '', microtime(true)), -4);
    $regNumber = 'WSA' . $year . '-' . $fallbackSuffix;
    error_log("[Reg Number] Fallback: {$regNumber}");
    return $regNumber;
}

// =========================
// Helper: Validate form date with time
// =========================
function validateFormDateTime($dateString): string {
    if (empty($dateString) || trim($dateString) === '') {
        return date('Y-m-d H:i:s');
    }
    
    $timestamp = strtotime($dateString);
    
    if ($timestamp === false || $timestamp < 0 || $timestamp > time()) {
        return date('Y-m-d H:i:s');
    }
    
    $year = (int)date('Y', $timestamp);
    $currentYear = (int)date('Y');
    
    if ($year < 2000 || $year > ($currentYear + 1)) {
        return date('Y-m-d H:i:s');
    }
    
    return date('Y-m-d H:i:s', $timestamp);
}

// ==========================================
// Check for duplicate English name
// ==========================================
function checkDuplicateEnglishName(PDO $conn, string $nameEn, string $currentIC): ?array {
    $stmt = $conn->prepare("
        SELECT id, registration_number, name_en, ic, payment_status, email, created_at 
        FROM registrations 
        WHERE LOWER(TRIM(name_en)) = LOWER(TRIM(?)) 
        AND ic != ?
        ORDER BY 
            CASE payment_status 
                WHEN 'approved' THEN 1 
                WHEN 'pending' THEN 2 
                WHEN 'rejected' THEN 3 
                ELSE 4 
            END,
            created_at DESC
        LIMIT 1
    ");
    $stmt->execute([trim($nameEn), $currentIC]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        error_log("[Duplicate] Name={$nameEn}, Status={$existing['payment_status']}");
        return $existing;
    }
    
    return null;
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

    $stmt = $conn->prepare("SELECT id, parent_id, password FROM parent_accounts WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        error_log("[Parent] Found existing ID={$existing['id']}");
        return [
            'id' => (int)$existing['id'],
            'parent_id' => $existing['parent_id'],
            'is_new' => false,
            'plain_password' => null,
        ];
    }

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
    error_log("[Parent] Created ID={$parentId}, code={$parentCode}");

    return [
        'id' => $parentId,
        'parent_id' => $parentCode,
        'is_new' => true,
        'plain_password' => $plainPassword,
    ];
}

function linkStudentToParent(PDO $conn, int $parentId, int $studentId, string $relationship = 'guardian'): void {
    $stmt = $conn->prepare("UPDATE students SET parent_account_id = ?, student_type = 'child' WHERE id = ?");
    $stmt->execute([$parentId, $studentId]);

    $stmt = $conn->prepare("SELECT id FROM parent_child_relationships WHERE parent_id = ? AND student_id = ? LIMIT 1");
    $stmt->execute([$parentId, $studentId]);
    $exists = $stmt->fetchColumn();

    if (!$exists) {
        $stmt = $conn->prepare("INSERT INTO parent_child_relationships
            (parent_id, student_id, relationship, is_primary, can_manage_payments, can_view_attendance, created_at)
            VALUES (?, ?, ?, 1, 1, 1, NOW())");
        $stmt->execute([$parentId, $studentId, $relationship]);
        error_log("[Link] Student={$studentId} to Parent={$parentId}");
    }
}

// ==========================================
// AUTO-ENROLLMENT HELPER FUNCTIONS
// ==========================================

function parseScheduleToClassCodes(string $scheduleString): array {
    $scheduleToClassCodeMap = [
        'Wushu Sport Academy: Sun 10am-12pm' => 'wsa-sun-10am',
        'Wushu Sport Academy: Sun 1pm-3pm' => 'wsa-sun-1pm',
        'Wushu Sport Academy: Wed 8pm-10pm' => 'wsa-wed-8pm',
        'SJK(C) Puay Chai 2: Tue 8pm-10pm' => 'pc2-tue-8pm',
        'SJK(C) Puay Chai 2: Wed 8pm-10pm' => 'pc2-wed-8pm',
        'SJK(C) Puay Chai 2: Fri 8pm-10pm' => 'pc2-fri-8pm',
        'Stadium Chinwoo: Sun 2pm-4pm' => 'chinwoo-sun-2pm',
    ];
    
    $classCodes = [];
    $scheduleArray = array_map('trim', explode(',', $scheduleString));
    
    foreach ($scheduleArray as $schedule) {
        if (isset($scheduleToClassCodeMap[$schedule])) {
            $classCodes[] = $scheduleToClassCodeMap[$schedule];
        } else {
            error_log("[Schedule] No mapping for: {$schedule}");
        }
    }
    
    return $classCodes;
}

function autoEnrollStudent(PDO $conn, int $studentId, string $scheduleString): array {
    $enrollmentResults = ['success' => [], 'failed' => [], 'skipped' => []];
    $classCodes = parseScheduleToClassCodes($scheduleString);
    
    if (empty($classCodes)) {
        error_log("[Auto-Enroll] No class codes found");
        return $enrollmentResults;
    }
    
    foreach ($classCodes as $classCode) {
        try {
            $stmt = $conn->prepare("SELECT id, class_name, status FROM classes WHERE class_code = ? LIMIT 1");
            $stmt->execute([$classCode]);
            $class = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$class) {
                $enrollmentResults['failed'][] = ['class_code' => $classCode, 'reason' => 'Not found'];
                continue;
            }
            
            if ($class['status'] !== 'active') {
                $enrollmentResults['skipped'][] = ['class_code' => $classCode, 'class_name' => $class['class_name'], 'reason' => 'Inactive'];
                continue;
            }
            
            $stmt = $conn->prepare("SELECT id FROM enrollments WHERE student_id = ? AND class_id = ? LIMIT 1");
            $stmt->execute([$studentId, $class['id']]);
            
            if ($stmt->fetch()) {
                $enrollmentResults['skipped'][] = ['class_code' => $classCode, 'class_name' => $class['class_name'], 'reason' => 'Already enrolled'];
                continue;
            }
            
            $stmt = $conn->prepare("INSERT INTO enrollments (student_id, class_id, enrollment_date, status, created_at) VALUES (?, ?, CURDATE(), 'active', NOW())");
            $stmt->execute([$studentId, $class['id']]);
            
            error_log("[Auto-Enroll] SUCCESS: Student={$studentId}, Class={$classCode}");
            $enrollmentResults['success'][] = ['class_code' => $classCode, 'class_name' => $class['class_name'], 'class_id' => $class['id']];
            
        } catch (PDOException $e) {
            error_log("[Auto-Enroll] ERROR: {$classCode} - " . $e->getMessage());
            $enrollmentResults['failed'][] = ['class_code' => $classCode, 'reason' => $e->getMessage()];
        }
    }
    
    return $enrollmentResults;
}

// ==========================================
// ⭐ UPDATED: REGISTRATION FEE INVOICES & PAYMENTS
// Now saves files locally instead of database
// ==========================================

function createRegistrationInvoicesAndPayments(PDO $conn, int $studentId, int $parentAccountId, float $totalAmount, string $studentName, array $enrolledClasses, array $paymentData): array {
    try {
        $results = [];
        $classCount = count($enrolledClasses);
        
        if ($classCount === 0) {
            throw new Exception("No enrolled classes found");
        }
        
        $amountPerClass = round($totalAmount / $classCount, 2);
        error_log("[Invoice] Total: {$totalAmount}, Classes: {$classCount}, Per Class: {$amountPerClass}");
        
        // ⭐ Save payment receipt to local file ONCE (shared across all invoices)
        $receiptSaveResult = saveBase64ToFile(
            $paymentData['receipt_base64'], 
            PAYMENT_RECEIPTS_DIR,
            'receipt_reg_' . $studentId . '_' . time()
        );
        
        if (!$receiptSaveResult['success']) {
            throw new Exception("Failed to save receipt: " . $receiptSaveResult['error']);
        }
        
        $receiptFilename = $receiptSaveResult['filename'];
        $receiptMimeType = $receiptSaveResult['mime_type'];
        $receiptFileSize = $receiptSaveResult['size'];
        
        error_log("[File] Saved receipt: {$receiptFilename} ({$receiptFileSize} bytes)");
        
        foreach ($enrolledClasses as $classData) {
            $invoiceNumber = 'INV-REG-' . date('Ym') . '-' . str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            $dueDate = date('Y-m-d');
            $description = "Registration Fee for {$studentName} - {$classData['class_name']} (" . date('Y') . ")";
            
            // Create invoice
            $stmt = $conn->prepare("
                INSERT INTO invoices (
                    invoice_number, student_id, parent_account_id, class_id, class_code,
                    invoice_type, description, amount, due_date, status, created_at
                ) VALUES (?, ?, ?, ?, ?, 'registration', ?, ?, ?, 'pending', NOW())
            ");
            
            $stmt->execute([
                $invoiceNumber, $studentId, $parentAccountId,
                $classData['class_id'], $classData['class_code'],
                $description, $amountPerClass, $dueDate
            ]);
            
            $invoiceId = (int)$conn->lastInsertId();
            error_log("[Invoice] Created: {$invoiceNumber} (ID: {$invoiceId})");
            
            // ⭐ Create payment record with FILENAME instead of base64 data
            $stmt = $conn->prepare("
                INSERT INTO payments (
                    student_id, class_id, invoice_id, parent_account_id,
                    amount, payment_month, payment_date,
                    receipt_filename, receipt_mime_type, receipt_size,
                    verification_status, upload_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $stmt->execute([
                $studentId, $classData['class_id'], $invoiceId, $parentAccountId,
                $amountPerClass, date('Y-m'), $paymentData['payment_date'],
                $receiptFilename, $receiptMimeType, $receiptFileSize
            ]);
            
            $paymentId = (int)$conn->lastInsertId();
            error_log("[Payment] Created ID={$paymentId}, File={$receiptFilename}");
            
            $results[] = [
                'invoice_id' => $invoiceId,
                'invoice_number' => $invoiceNumber,
                'payment_id' => $paymentId,
                'class_code' => $classData['class_code'],
                'class_name' => $classData['class_name'],
                'amount' => $amountPerClass,
                'receipt_filename' => $receiptFilename
            ];
        }
        
        return [
            'success' => true,
            'invoices' => $results,
            'total_invoices' => count($results),
            'receipt_filename' => $receiptFilename
        ];
        
    } catch (Exception $e) {
        error_log("[Invoice] ERROR: " . $e->getMessage());
        // Clean up saved file on error
        if (isset($receiptFilename) && $receiptFilename) {
            deleteFile(PAYMENT_RECEIPTS_DIR . $receiptFilename);
        }
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// ===============
// Email functions
// ===============

function sendRegistrationEmail($toEmail, $studentName, $registrationNumber, $childPassword, $studentStatus, $isNewParent, $parentPassword) {
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

        $mail->isHTML(true);
        $mail->Subject = '🎊 Registration Successful';
        $mail->Body    = "Registration successful for {$studentName}!";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("[Email] Error: " . $e->getMessage());
        return false;
    }
}

function sendAdminRegistrationNotification($data) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'chaichonghern@gmail.com';
        $mail->Password   = 'kyyj elhp dkdw gvki';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('noreply@wushusportacademy.com', 'Wushu Portal');
        $mail->addAddress(ADMIN_EMAIL);

        $mail->isHTML(true);
        $mail->Subject = '🔔 New Registration: ' . $data['student_name'];
        $mail->Body    = "New registration from {$data['student_name']}";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// ============================
// MAIN REGISTRATION PROCESSING
// ============================

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }

    $required = [
        'name_en', 'ic', 'age', 'school', 'status', 'phone', 'email',
        'events', 'schedule', 'parent_name', 'parent_ic',
        'form_date', 'signature_base64', 'signed_pdf_base64',
        'payment_amount', 'payment_date', 'payment_receipt_base64', 'class_count'
    ];

    foreach ($required as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            throw new Exception("Missing field: {$field}");
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
    $childNameEn = trim($data['name_en']);
    
    // Check for duplicates
    $duplicateCheck = checkDuplicateEnglishName($conn, $childNameEn, $childIC);
    
    if ($duplicateCheck) {
        $status = $duplicateCheck['payment_status'];
        
        if ($status === 'approved') {
            throw new Exception("Student '{$childNameEn}' is already registered (#{$duplicateCheck['registration_number']})");
        } elseif ($status === 'pending') {
            throw new Exception("Registration pending for '{$childNameEn}' (#{$duplicateCheck['registration_number']})");
        } elseif ($status === 'rejected') {
            $stmt = $conn->prepare("DELETE FROM registrations WHERE id = ?");
            $stmt->execute([$duplicateCheck['id']]);
        }
    }

    // Generate registration number
    $regNumber = generateUniqueRegistrationNumber($conn);
    $generatedPassword = generatePasswordFromIC($childIC);
    $hashedPassword = password_hash($generatedPassword, PASSWORD_DEFAULT);

    // Find or create parent
    $parentData = [
        'name' => trim($data['parent_name']),
        'email' => $parentEmail,
        'phone' => trim($data['phone']),
        'ic' => trim($data['parent_ic']),
    ];

    $parentAccountInfo = findOrCreateParentAccount($conn, $parentData);
    $parentAccountId = $parentAccountInfo['id'];
    $isNewParentAccount = $parentAccountInfo['is_new'];
    $parentPlainPassword = $parentAccountInfo['plain_password'];

    // Create student account
    $stmt = $conn->prepare("INSERT INTO students
        (student_id, full_name, email, phone, password, student_status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $regNumber,
        $childNameEn,
        $parentEmail,
        trim($data['phone']),
        $hashedPassword,
        trim($data['status']),
    ]);
    $studentAccountId = (int)$conn->lastInsertId();

    // Link to parent
    linkStudentToParent($conn, $parentAccountId, $studentAccountId);

    // Validate form date
    $validatedFormDateTime = validateFormDateTime($data['form_date']);

    // Insert registration (keeping base64 for signature and PDF for now)
    $stmt = $conn->prepare("
        INSERT INTO registrations (
            registration_number, name_cn, name_en, ic, age, school, student_status,
            phone, email, level, events, schedule, parent_name, parent_ic,
            form_date, signature_base64, pdf_base64,
            payment_amount, payment_date, payment_receipt_base64, payment_status, class_count,
            student_account_id, account_created, password_generated,
            parent_account_id, registration_type, is_additional_child, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', 'pending', ?,
            ?, 'yes', ?, ?, 'parent_managed', ?, NOW()
        )
    ");

    $stmt->execute([
        $regNumber, $data['name_cn'] ?? '', $childNameEn, $childIC,
        (int)$data['age'], trim($data['school']), trim($data['status']),
        trim($data['phone']), $parentEmail, $data['level'] ?? '',
        trim($data['events']), trim($data['schedule']),
        trim($data['parent_name']), trim($data['parent_ic']),
        $validatedFormDateTime, $data['signature_base64'], $data['signed_pdf_base64'],
        (float)$data['payment_amount'], trim($data['payment_date']),
        (int)$data['class_count'], $studentAccountId,
        $generatedPassword, $parentAccountId, $isNewParentAccount ? 0 : 1
    ]);

    // Auto-enroll in classes
    $enrollmentResults = autoEnrollStudent($conn, $studentAccountId, trim($data['schedule']));

    // Create invoices and save payment receipt to file
    $paymentData = [
        'receipt_base64' => $data['payment_receipt_base64'],
        'payment_date' => trim($data['payment_date'])
    ];
    
    $invoiceResult = createRegistrationInvoicesAndPayments(
        $conn, $studentAccountId, $parentAccountId,
        (float)$data['payment_amount'], $childNameEn,
        $enrollmentResults['success'], $paymentData
    );

    if (!$invoiceResult['success']) {
        throw new Exception("Invoice creation failed: " . ($invoiceResult['error'] ?? 'Unknown'));
    }

    $conn->commit();
    
    error_log("[SUCCESS] Reg: {$regNumber}, Student: {$studentAccountId}, Invoices: {$invoiceResult['total_invoices']}, File: {$invoiceResult['receipt_filename']}");

    // Send emails
    $emailSent = sendRegistrationEmail($parentEmail, $childNameEn, $regNumber, $generatedPassword, trim($data['status']), $isNewParentAccount, $parentPlainPassword);
    
    $adminData = [
        'student_name' => $childNameEn,
        'registration_number' => $regNumber,
    ];
    $adminEmailSent = sendAdminRegistrationNotification($adminData);

    echo json_encode([
        'success' => true,
        'registration_number' => $regNumber,
        'student_id' => $regNumber,
        'email' => $parentEmail,
        'password' => $generatedPassword,
        'parent_code' => $parentAccountInfo['parent_id'],
        'is_new_parent' => $isNewParentAccount,
        'parent_password' => $parentPlainPassword,
        'email_sent' => $emailSent,
        'admin_notified' => $adminEmailSent,
        'enrollment_results' => $enrollmentResults,
        'total_invoices' => $invoiceResult['total_invoices'],
        'receipt_saved_as_file' => true,
        'receipt_filename' => $invoiceResult['receipt_filename'],
        'message' => 'Registration successful! Payment receipt saved to local file.'
    ]);

} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('DB error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}