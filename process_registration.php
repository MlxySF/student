<?php
/**
 * process_registration.php - Complete Registration Processing with PHPMailer
 * Handles student registration, account creation, and email notification
 * Stage 3: Multi-child parent system - auto-detects parent by email
 * UPDATED: Password is now last 4 digits of IC number
 * UPDATED: Auto-enrollment into classes based on schedule selection
 * UPDATED: Creates registration fee invoice viewable in parent portal
 * UPDATED: Links payment receipt to invoice for admin verification
 * UPDATED 2025-12-28: COMBINED registration invoice - one invoice for all classes instead of splitting
 * FIXED: Use student_status column name in registrations INSERT statement
 * FIXED: Validate form_date to prevent invalid dates like "-0001"
 * UPDATED: Changed form_date to record both date and time (DATETIME format)
 * UPDATED: Added admin email notification for new registrations
 * UPDATED: Added payment_date column to payments INSERT - user can specify when payment was actually made
 * FIXED: Randomize student ID to prevent duplicate entry errors when deleting mid-sequence registrations
 * UPDATED: Added duplicate name validation - check for approved/pending registrations, allow overwrite of rejected
 * MAJOR UPDATE 2025-12-21: Converted from base64 storage to local file storage
 * - Files now saved to uploads/ directory structure
 * - Database stores file paths instead of base64 data
 * - Uses file_helper.php for file operations
 * FIXED 2025-12-21: Save payment_receipt_path in registrations table
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 0);
ini_set('error_log', __DIR__ . '/error_log.txt');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'file_helper.php'; // NEW: File management functions

// Admin email configuration
define('ADMIN_EMAIL', 'admin@wushusportacademy.com');
define('ADMIN_NAME', 'Wushu Sport Academy');

/**
 * Generate password: either from IC last 4 digits OR custom password
 * @param string $ic Parent IC number
 * @param string|null $customPassword Custom password (if provided)
 * @param string $passwordType "ic_last4" or "custom"
 * @return string Plain text password
 */
function generatePassword(string $ic, ?string $customPassword, string $passwordType): string {
    if ($passwordType === 'custom' && !empty($customPassword)) {
        error_log("[Password] Using CUSTOM password (length: " . strlen($customPassword) . ")");
        return trim($customPassword);
    }
    
    // Default: use last 4 digits of IC
    $icClean = str_replace('-', '', $ic);
    $last4 = substr($icClean, -4);
    error_log("[Password] Using IC last 4 digits: {$last4}");
    return $last4;
}


// =========================
// Helper: Generate unique randomized registration number
// UPDATED: Uses random 4-digit suffix to prevent duplicate ID errors
// =========================
function generateUniqueRegistrationNumber(PDO $conn): string {
    $year = date('Y');
    $maxAttempts = 100; // Prevent infinite loop
    $attempt = 0;
    
    while ($attempt < $maxAttempts) {
        // Generate random 4-digit number (1000-9999)
        $randomSuffix = str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        $regNumber = 'WSA2026' . '-' . $randomSuffix;
        
        // Check if this registration number already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM registrations WHERE registration_number = ?");
        $stmt->execute([$regNumber]);
        $count = (int)$stmt->fetchColumn();
        
        if ($count === 0) {
            error_log("[Reg Number] Generated unique random registration number: {$regNumber}");
            return $regNumber;
        }
        
        $attempt++;
        error_log("[Reg Number] Collision detected for {$regNumber}, retrying... (attempt {$attempt})");
    }
    
    // Fallback: use timestamp-based unique suffix if random fails
    $fallbackSuffix = substr(str_replace('.', '', microtime(true)), -4);
    $regNumber = 'WSA2026' . '-' . $fallbackSuffix;
    error_log("[Reg Number] Using fallback timestamp-based number: {$regNumber}");
    return $regNumber;
}

// =========================
// Helper: Validate and fix form date with time
// UPDATED: Now returns DATETIME format instead of just DATE
// =========================
function validateFormDateTime($dateString): string {
    // If empty or invalid, use current datetime
    if (empty($dateString) || trim($dateString) === '') {
        error_log("[DateTime Validation] Empty form_date received, using current datetime");
        return date('Y-m-d H:i:s');
    }
    
    // Try to parse the date/datetime
    $timestamp = strtotime($dateString);
    
    // Check if date is valid and reasonable (not year -0001, not future)
    if ($timestamp === false || $timestamp < 0 || $timestamp > time()) {
        error_log("[DateTime Validation] Invalid form_date '{$dateString}', using current datetime");
        return date('Y-m-d H:i:s');
    }
    
    // Check if year is reasonable (between 2000 and current year + 1)
    $year = (int)date('Y', $timestamp);
    $currentYear = (int)date('Y');
    
    if ($year < 2000 || $year > ($currentYear + 1)) {
        error_log("[DateTime Validation] Unreasonable year {$year} in form_date '{$dateString}', using current datetime");
        return date('Y-m-d H:i:s');
    }
    
    // Date is valid, return in proper datetime format
    error_log("[DateTime Validation] Valid form_date: {$dateString}");
    return date('Y-m-d H:i:s', $timestamp);
}

// ==========================================
// NEW: Check for duplicate English name
// Returns array with status info if duplicate found
// ==========================================
function checkDuplicateEnglishName(PDO $conn, string $nameEn, string $currentIC): ?array {
    // Check if name already exists with approved or pending status
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
        error_log("[Duplicate Check] Found existing registration: Name={$nameEn}, Status={$existing['payment_status']}, RegNum={$existing['registration_number']}");
        return $existing;
    }
    
    return null;
}

// ==========================================
// Parent account helper functions
// ==========================================

function generateParentCode(PDO $conn): string {
    $year = date('Y');
    $maxAttempts = 100;
    $attempt = 0;
    
    while ($attempt < $maxAttempts) {
        // Generate random 4-digit number (1000-9999)
        $randomSuffix = str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        $parentCode = 'PAR-2026' . '-' . $randomSuffix;
        
        // Check if this parent code already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM parent_accounts WHERE parent_id = ?");
        $stmt->execute([$parentCode]);
        $count = (int)$stmt->fetchColumn();
        
        if ($count === 0) {
            error_log("[Parent Code] Generated unique random parent code: {$parentCode}");
            return $parentCode;
        }
        
        $attempt++;
        error_log("[Parent Code] Collision detected for {$parentCode}, retrying... (attempt {$attempt})");
    }
    
    // Fallback: use timestamp-based unique suffix if random fails
    $fallbackSuffix = substr(str_replace('.', '', microtime(true)), -4);
    $parentCode = 'PAR-' . $year . '-' . $fallbackSuffix;
    error_log("[Parent Code] Using fallback timestamp-based code: {$parentCode}");
    return $parentCode;
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
    $plainPassword = generatePassword(
        $parentData['ic'], 
        $parentData['custom_password'] ?? null,
        $parentData['password_type'] ?? 'ic_last4'
    );

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

// ==========================================
// AUTO-ENROLLMENT HELPER FUNCTIONS
// ==========================================

/**
 * Parse schedule string and extract data-schedule codes
 * Example input: "Wushu Sport Academy: Sun 10am-12pm, SJK(C) Puay Chai 2: Tue 8pm-10pm"
 * Maps to class codes in database
 */
function parseScheduleToClassCodes(string $scheduleString): array {
    // Define mapping between schedule strings and class codes
    $scheduleToClassCodeMap = [
        // Wushu Sport Academy
        'Wushu Sport Academy: Sun 10am-12pm' => 'wsa-sun-10am',
        'Wushu Sport Academy: Sun 1pm-3pm' => 'wsa-sun-1pm',
        'Wushu Sport Academy: Wed 8pm-10pm' => 'wsa-wed-8pm',
        
        // SJK(C) Puay Chai 2
        'SJK(C) Puay Chai 2: Tue 8pm-10pm' => 'pc2-tue-8pm',
        'SJK(C) Puay Chai 2: Wed 8pm-10pm' => 'pc2-wed-8pm',
        'SJK(C) Puay Chai 2: Fri 8pm-10pm' => 'pc2-fri-8pm',
        
        // Stadium Chinwoo
        'Stadium Chinwoo: Sun 2pm-4pm' => 'chinwoo-sun-2pm',
    ];
    
    $classCodes = [];
    
    // Split schedule string by comma and trim
    $scheduleArray = array_map('trim', explode(',', $scheduleString));
    
    foreach ($scheduleArray as $schedule) {
        if (isset($scheduleToClassCodeMap[$schedule])) {
            $classCodes[] = $scheduleToClassCodeMap[$schedule];
            error_log("[Schedule Parse] Mapped '{$schedule}' to '{$scheduleToClassCodeMap[$schedule]}'");
        } else {
            error_log("[Schedule Parse] WARNING: No mapping found for '{$schedule}'");
        }
    }
    
    return $classCodes;
}

/**
 * Automatically enroll student into classes based on schedule selection
 */
function autoEnrollStudent(PDO $conn, int $studentId, string $scheduleString): array {
    $enrollmentResults = [
        'success' => [],
        'failed' => [],
        'skipped' => []
    ];
    
    // Parse schedule to get class codes
    $classCodes = parseScheduleToClassCodes($scheduleString);
    
    if (empty($classCodes)) {
        error_log("[Auto-Enroll] No class codes parsed from schedule: {$scheduleString}");
        return $enrollmentResults;
    }
    
    error_log("[Auto-Enroll] Starting enrollment for student ID={$studentId}, Classes: " . implode(', ', $classCodes));
    
    foreach ($classCodes as $classCode) {
        try {
            // Find class by class_code
            $stmt = $conn->prepare("SELECT id, class_name, status FROM classes WHERE class_code = ? LIMIT 1");
            $stmt->execute([$classCode]);
            $class = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$class) {
                error_log("[Auto-Enroll] Class not found: {$classCode}");
                $enrollmentResults['failed'][] = [
                    'class_code' => $classCode,
                    'reason' => 'Class not found in database'
                ];
                continue;
            }
            
            if ($class['status'] !== 'active') {
                error_log("[Auto-Enroll] Class inactive: {$classCode}");
                $enrollmentResults['skipped'][] = [
                    'class_code' => $classCode,
                    'class_name' => $class['class_name'],
                    'reason' => 'Class is not active'
                ];
                continue;
            }
            
            // Check if already enrolled
            $stmt = $conn->prepare("SELECT id FROM enrollments WHERE student_id = ? AND class_id = ? LIMIT 1");
            $stmt->execute([$studentId, $class['id']]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                error_log("[Auto-Enroll] Already enrolled: Student={$studentId}, Class={$classCode}");
                $enrollmentResults['skipped'][] = [
                    'class_code' => $classCode,
                    'class_name' => $class['class_name'],
                    'reason' => 'Already enrolled'
                ];
                continue;
            }
            
            // Create enrollment
            $stmt = $conn->prepare("INSERT INTO enrollments 
                (student_id, class_id, enrollment_date, status, created_at) 
                VALUES (?, ?, CURDATE(), 'active', NOW())");
            $stmt->execute([$studentId, $class['id']]);
            
            error_log("[Auto-Enroll] SUCCESS: Enrolled student ID={$studentId} in class '{$class['class_name']}' (Code: {$classCode})");
            $enrollmentResults['success'][] = [
                'class_code' => $classCode,
                'class_name' => $class['class_name'],
                'class_id' => $class['id']
            ];
            
        } catch (PDOException $e) {
            error_log("[Auto-Enroll] ERROR enrolling in {$classCode}: " . $e->getMessage());
            $enrollmentResults['failed'][] = [
                'class_code' => $classCode,
                'reason' => $e->getMessage()
            ];
        }
    }
    
    return $enrollmentResults;
}

// ==========================================
// REGISTRATION FEE INVOICES & PAYMENTS
// UPDATED 2025-12-28: Create ONE COMBINED invoice instead of splitting by class
// ==========================================

/**
 * Create ONE combined registration fee invoice for all classes
 * Links the uploaded payment receipt to the invoice
 * UPDATED: Now creates single invoice instead of splitting
 */
function createRegistrationInvoicesAndPayments(PDO $conn, int $studentId, int $parentAccountId, float $totalAmount, string $studentName, array $enrolledClasses, array $paymentData, ?string $receiptPath): array {
    try {
        $classCount = count($enrolledClasses);
        
        if ($classCount === 0) {
            throw new Exception("No enrolled classes found");
        }
        
        // Generate unique invoice number
        $invoiceNumber = 'INV-REG-' . date('Ym') . '-' . str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        
        // Set due date to today (since payment is already uploaded)
        $dueDate = date('Y-m-d');
        
        // Build description with all enrolled classes
        $description = "Registration Fee for {$studentName} (" . date('Y') . ")\n";
        $description .= "Enrolled in {$classCount} class(es):\n";
        
        $classNames = [];
        $classCodes = [];
        
        foreach ($enrolledClasses as $classData) {
            $classNames[] = $classData['class_name'];
            $classCodes[] = $classData['class_code'];
            $description .= "- {$classData['class_name']} ({$classData['class_code']})\n";
        }
        
        // For single invoice, we store NULL for class_id if multiple classes
        // Store first class code for reference, or NULL if multiple
        $singleClassId = ($classCount === 1) ? $enrolledClasses[0]['class_id'] : null;
        $singleClassCode = ($classCount === 1) ? $enrolledClasses[0]['class_code'] : null;
        
        error_log("[Invoice Combined] Creating single invoice for {$classCount} classes, total: {$totalAmount}");
        error_log("[Invoice] Using receipt path: {$receiptPath}");
        
        // Create ONE invoice for all classes
        $stmt = $conn->prepare("
            INSERT INTO invoices (
                invoice_number,
                student_id,
                parent_account_id,
                class_id,
                class_code,
                invoice_type,
                description,
                amount,
                due_date,
                status,
                created_at
            ) VALUES (?, ?, ?, ?, ?, 'registration', ?, ?, ?, 'pending', NOW())
        ");
        
        $stmt->execute([
            $invoiceNumber,
            $studentId,
            $parentAccountId,
            $singleClassId,
            $singleClassCode,
            $description,
            $totalAmount,
            $dueDate
        ]);
        
        $invoiceId = (int)$conn->lastInsertId();
        
        error_log("[Invoice] Created combined invoice: {$invoiceNumber} (ID: {$invoiceId}), amount={$totalAmount}");
        
        // Create ONE payment record linked to the combined invoice
        $stmt = $conn->prepare("
            INSERT INTO payments (
                student_id,
                class_id,
                invoice_id,
                parent_account_id,
                amount,
                payment_month,
                payment_date,
                payment_method,
                receipt_path,
                verification_status,
                upload_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        
        $stmt->execute([
            $studentId,
            $singleClassId,
            $invoiceId,
            $parentAccountId,
            $totalAmount,
            date('Y-m'),
            $paymentData['payment_date'],
            $paymentData['payment_method'],
            $receiptPath
        ]);
        
        $paymentId = (int)$conn->lastInsertId();
        
        error_log("[Payment] Created payment record (ID: {$paymentId}) linked to invoice {$invoiceNumber}, receipt_path={$receiptPath}");
        
        return [
            'success' => true,
            'invoices' => [[
                'invoice_id' => $invoiceId,
                'invoice_number' => $invoiceNumber,
                'payment_id' => $paymentId,
                'class_count' => $classCount,
                'class_names' => implode(', ', $classNames),
                'class_codes' => implode(', ', $classCodes),
                'amount' => $totalAmount
            ]],
            'total_invoices' => 1,
            'receipt_path' => $receiptPath
        ];
        
    } catch (PDOException $e) {
        error_log("[Invoice/Payment] ERROR: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    } catch (Exception $e) {
        error_log("[Invoice/Payment] ERROR: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// ===============
// Email functions
// ===============

function sendRegistrationEmail($toEmail, $studentName, $registrationNumber, $childPassword, $studentStatus, $isNewParent, $parentPassword) {
    $mail = new PHPMailer(true);
    try {
        error_log("[Email] Sending to: {$toEmail}, isNewParent: " . ($isNewParent ? 'YES' : 'NO') . ", parentPassword: " . ($parentPassword ?? 'NULL'));
        
        // SMTP Configuration (existing code - keep as is)
$mail->isSMTP();
$mail->Host       = 'smtp.mailgun.org';
$mail->SMTPAuth   = true;
$mail->Username   = 'admin@wushusportacademy.com';
$mail->Password   = '';
$mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
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
$mail->addAddress($toEmail);
$mail->addReplyTo('admin@wushusportacademy.com', 'Wushu Sport Academy');

        $mail->isHTML(true);
        $mail->Subject = '🎊 Wushu Sport Academy - Child Registration Successful';
        $mail->Body    = getEmailHTMLContent($studentName, $registrationNumber, $toEmail, $childPassword, $studentStatus, $isNewParent, $parentPassword);
        $mail->AltBody = "Registration Successful!";

        $mail->send();
        error_log("[Email] Successfully sent to {$toEmail}");
        return true;
    } catch (Exception $e) {
        error_log("[Email] Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Send admin notification email for new registration
 */
function sendAdminRegistrationNotification($registrationData) {
    $mail = new PHPMailer(true);
    try {
        error_log("[Admin Email] Sending registration notification to " . ADMIN_EMAIL);
        
        // SMTP Configuration (existing code - keep as is)
        $mail->isSMTP();
        $mail->Host       = 'mail.wushusportacademy.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'admin@wushusportacademy.com';
        $mail->Password   = 'P1}tKwojKgl0vdMv';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        $mail->Encoding   = 'base64';

// Set sender/return path to match From address
$mail->Sender = 'admin@wushusportacademy.com';

// Add Message-ID and other anti-spam headers
$mail->MessageID = sprintf("<%s@%s>", uniqid(), 'wushusportacademy.com');
$mail->XMailer = ' '; // Hide PHPMailer signature to avoid spam triggers

// Recipients (existing code - keep as is)
$mail->setFrom('admin@wushusportacademy.com', 'Wushu Sport Academy');
$mail->addAddress(ADMIN_EMAIL, ADMIN_NAME);
$mail->addReplyTo('admin@wushusportacademy.com', 'Wushu Sport Academy');

        $mail->isHTML(true);
        $mail->Subject = '🔔 New Registration: ' . $registrationData['student_name'] . ' - ' . $registrationData['registration_number'];
        $mail->Body    = getAdminRegistrationEmailHTML($registrationData);
        $mail->AltBody = "New Registration: {$registrationData['student_name']} ({$registrationData['registration_number']})";

        $mail->send();
        error_log("[Admin Email] Successfully sent registration notification");
        return true;
    } catch (Exception $e) {
        error_log("[Admin Email] Error: {$mail->ErrorInfo}");
        return false;
    }
}

function getAdminRegistrationEmailHTML($data) {
    $classesHtml = '';
    if (!empty($data['classes'])) {
        $classesHtml = '<ul style="margin: 8px 0; padding-left: 20px;">';
        foreach ($data['classes'] as $class) {
            $classesHtml .= "<li>{$class['class_name']} (Code: {$class['class_code']})</li>";
        }
        $classesHtml .= '</ul>';
    }
    
    $html = "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>New Registration Alert</title>
</head>
<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5;'>
    <div style='max-width: 650px; margin: 20px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
        <div style='background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color: white; padding: 30px 24px; text-align: center;'>
            <h1 style='margin: 0 0 8px 0; font-size: 28px; font-weight: 700;'>🔔 New Student Registration</h1>
            <p style='margin: 0; font-size: 14px; opacity: 0.95;'>Requires Admin Review</p>
        </div>
        
        <div style='padding: 32px 24px; background: white;'>
            <div style='background: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; border-radius: 8px; margin-bottom: 24px;'>
                <p style='margin: 0; font-weight: 600; color: #92400e; font-size: 15px;'>⚠️ Action Required: Payment Verification</p>
                <p style='margin: 8px 0 0 0; font-size: 13px; color: #92400e;'>A new student has registered with payment receipt attached. Please review and verify.</p>
            </div>
            
            <div style='background: #f8fafc; border-radius: 8px; padding: 20px; margin-bottom: 24px;'>
                <h3 style='margin: 0 0 16px 0; color: #1e293b; font-size: 18px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;'>👶 Student Information</h3>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; font-weight: 600; width: 40%;'>Registration Number:</td>
                        <td style='padding: 8px 0; color: #1e293b; font-weight: 600;'>{$data['registration_number']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; font-weight: 600;'>Student Name:</td>
                        <td style='padding: 8px 0; color: #1e293b;'>{$data['student_name']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; font-weight: 600;'>IC Number:</td>
                        <td style='padding: 8px 0; color: #1e293b;'>{$data['ic']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; font-weight: 600;'>Age:</td>
                        <td style='padding: 8px 0; color: #1e293b;'>{$data['age']} years old</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; font-weight: 600;'>School:</td>
                        <td style='padding: 8px 0; color: #1e293b;'>{$data['school']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; font-weight: 600;'>Student Status:</td>
                        <td style='padding: 8px 0;'><span style='background: #dbeafe; padding: 4px 12px; border-radius: 4px; font-size: 13px; font-weight: 600; color: #1e40af;'>{$data['student_status']}</span></td>
                    </tr>
                </table>
            </div>
            
            <div style='background: #f0fdf4; border-radius: 8px; padding: 20px; margin-bottom: 24px;'>
                <h3 style='margin: 0 0 16px 0; color: #166534; font-size: 18px; border-bottom: 2px solid #bbf7d0; padding-bottom: 8px;'>👨‍👩‍👧 Parent Information</h3>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr>
                        <td style='padding: 8px 0; color: #166534; font-weight: 600; width: 40%;'>Parent Name:</td>
                        <td style='padding: 8px 0; color: #166534;'>{$data['parent_name']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #166534; font-weight: 600;'>Parent IC:</td>
                        <td style='padding: 8px 0; color: #166534;'>{$data['parent_ic']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #166534; font-weight: 600;'>Parent Email:</td>
                        <td style='padding: 8px 0; color: #166534;'><a href='mailto:{$data['parent_email']}' style='color: #059669; text-decoration: none;'>{$data['parent_email']}</a></td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #166534; font-weight: 600;'>Phone:</td>
                        <td style='padding: 8px 0; color: #166534;'>{$data['phone']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #166534; font-weight: 600;'>Parent Code:</td>
                        <td style='padding: 8px 0; color: #166534;'>{$data['parent_code']}</td>
                    </tr>
                </table>
            </div>
            
            <div style='background: #eff6ff; border-radius: 8px; padding: 20px; margin-bottom: 24px;'>
                <h3 style='margin: 0 0 16px 0; color: #1e40af; font-size: 18px; border-bottom: 2px solid #bfdbfe; padding-bottom: 8px;'>🏛️ Class Enrollment</h3>
                <p style='margin: 0 0 8px 0; color: #1e40af; font-weight: 600;'>Schedule: {$data['schedule']}</p>
                <p style='margin: 0 0 8px 0; color: #1e40af;'>Enrolled Classes ({$data['class_count']}):</p>
                {$classesHtml}
            </div>
            
            <div style='background: #fef2f2; border-radius: 8px; padding: 20px; margin-bottom: 24px;'>
                <h3 style='margin: 0 0 16px 0; color: #991b1b; font-size: 18px; border-bottom: 2px solid #fecaca; padding-bottom: 8px;'>💳 Payment Information</h3>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr>
                        <td style='padding: 8px 0; color: #991b1b; font-weight: 600; width: 40%;'>Total Amount:</td>
                        <td style='padding: 8px 0; color: #991b1b; font-weight: 700; font-size: 18px;'>RM {$data['payment_amount']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #991b1b; font-weight: 600;'>Payment Date:</td>
                        <td style='padding: 8px 0; color: #991b1b;'>{$data['payment_date']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #991b1b; font-weight: 600;'>Invoice:</td>
                        <td style='padding: 8px 0; color: #991b1b;'>1 combined invoice for all {$data['class_count']} classes</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #991b1b; font-weight: 600;'>Payment Status:</td>
                        <td style='padding: 8px 0;'><span style='background: #fef3c7; padding: 4px 12px; border-radius: 4px; font-size: 13px; font-weight: 600; color: #92400e;'>Pending Verification</span></td>
                    </tr>
                </table>
            </div>
            
            <div style='background: #f1f5f9; border-left: 4px solid #3b82f6; padding: 20px; border-radius: 8px;'>
                <p style='margin: 0 0 16px 0; font-weight: 600; color: #1e293b; font-size: 16px;'>👉 Next Steps:</p>
                <ol style='margin: 0; padding-left: 20px; color: #475569; font-size: 14px; line-height: 1.8;'>
                    <li>Login to the <strong>Admin Portal</strong></li>
                    <li>Go to <strong>Registrations</strong> section</li>
                    <li>Review registration form and payment receipt</li>
                    <li>Verify payment amount and details</li>
                    <li>Approve or reject the registration</li>
                </ol>
            </div>
        </div>
        
        <div style='text-align: center; padding: 24px; background: #f8fafc; color: #64748b; font-size: 13px; border-top: 1px solid #e2e8f0;'>
            <p style='margin: 0 0 8px 0; font-weight: 600; color: #1e293b; font-size: 15px;'>Wushu Sport Academy 武术体育学院</p>
            <p style='margin: 4px 0;'>Admin Portal System</p>
            <p style='margin: 16px 0 0 0; font-size: 11px; color: #94a3b8;'>This is an automated notification. Generated on " . date('Y-m-d H:i:s') . "</p>
        </div>
    </div>
</body>
</html>";

    return $html;
}

function getEmailHTMLContent($studentName, $registrationNumber, $toEmail, $childPassword, $studentStatus, $isNewParent, $parentPassword) {
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
            <h3 style='margin: 0 0 16px 0; color: #856404; font-size: 20px;'>🔑 Account Created!</h3>
            <p style='margin: 0 0 12px 0; color: #856404; font-size: 15px;'><strong>This is your FIRST registration.</strong> An account has been created for you to manage all your children.</p>
            
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
                        <span style='font-size: 13px; color: #856404; display: block; margin-top: 8px;'>💡 This is the <strong>last 4 digits of your IC number</strong></span>
                    </td>
                </tr>
            </table>
            
            <div style='background: #fff; padding: 12px 16px; border-radius: 6px; margin-top: 16px;'>
                <p style='margin: 0; font-size: 14px; color: #856404;'>⚠️ <strong>IMPORTANT:</strong> Save this password securely! You'll need it to:</p>
                <ul style='margin: 8px 0 0 20px; padding: 0; color: #856404; font-size: 14px;'>
                    <li>Login to the dashboard system</li>
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
            <h3 style='margin: 0 0 12px 0; color: #0c5460; font-size: 18px;'>✅ Child Added to Your Account</h3>
            <p style='margin: 0; color: #0c5460; font-size: 14px;'>This child has been successfully linked to your existing account. Login with your parent email and password to view all children.</p>
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
            <p style='margin: 0 0 24px 0; font-size: 15px; color: #475569;'>Your child <strong>{$studentName}</strong> has been successfully registered for Wushu training at Wushu Sport Academy!</p>
            
            {$parentSection}
            
            <div style='background: #f0fdf4; border-left: 4px solid #22c55e; padding: 20px; margin: 24px 0; border-radius: 8px;'>
                <h3 style='margin: 0 0 16px 0; color: #166534; font-size: 18px;'>👶 Child Details</h3>
                <table style='width: 100%;'>
                    <tr>
                        <td style='padding: 6px 0; color: #166534; font-weight: 600; width: 40%;'>Child Name:</td>
                        <td style='padding: 6px 0; color: #166534;'>{$studentName}</td>
                    </tr>
                    <tr>
                        <td style='padding: 6px 0; color: #166534; font-weight: 600;'>Student ID:</td>
                        <td style='padding: 6px 0; color: #166534;'>{$registrationNumber}</td>
                    </tr>
                    <tr>
                        <td style='padding: 6px 0; color: #166534; font-weight: 600;'>Status:</td>
                        <td style='padding: 6px 0; color: #166534;'><span style='background: #dcfce7; padding: 4px 12px; border-radius: 4px; font-size: 13px; font-weight: 600;'>{$studentStatus}</span></td>
                    </tr>
                </table>
            </div>
            
            <div style='background: #eff6ff; border-left: 4px solid #3b82f6; padding: 20px; border-radius: 8px; margin: 24px 0;'>
                <p style='margin: 0 0 8px 0; font-weight: 600; color: #1e40af; font-size: 16px;'>💡 Register More Children</p>
                <p style='margin: 0; color: #1e40af; font-size: 14px; line-height: 1.6;'>To register additional children, simply use the <strong>same email address</strong> ({$toEmail}) when filling the registration form. All your children will be automatically linked to your existing account!</p>
            </div>
            
            <div style='background: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; border-radius: 8px; margin: 24px 0;'>
                <p style='margin: 0 0 8px 0; font-weight: 600; color: #92400e; font-size: 15px;'>🔐 Easy Password System</p>
                <p style='margin: 0; color: #92400e; font-size: 13px; line-height: 1.6;'>All passwords are the <strong>last 4 digits of the IC number</strong> for easy remembering. Parent uses their IC, children use their IC.</p>
            </div>
            
            <div style='background: #f8fafc; padding: 20px; border-radius: 8px; margin: 24px 0;'>
                <h4 style='margin: 0 0 12px 0; color: #1e293b; font-size: 16px;'>📋 Next Steps:</h4>
                <ol style='margin: 0; padding-left: 20px; color: #475569; font-size: 14px; line-height: 1.8;'>
                    <li>Your payment receipt is under review by the academy</li>
                    <li>You will receive approval notification via email</li>
                    <li>Login to the dashboard system to view ONE combined invoice for all enrolled classes</li>
                    <li>After approval, the invoice status will change to Paid</li>
                    <li>Your child has been automatically enrolled in the selected classes</li>
                </ol>
            </div>
        </div>
        
        <div style='text-align: center; padding: 24px; background: #f8fafc; color: #64748b; font-size: 13px; border-top: 1px solid #e2e8f0;'>
            <p style='margin: 0 0 8px 0; font-weight: 600; color: #1e293b; font-size: 15px;'>Wushu Sport Academy 武术体育学院</p>
            <p style='margin: 4px 0;'>📧 Email: admin@wushusportacademy.com</p>
            <p style='margin: 16px 0 0 0; font-size: 11px; color: #94a3b8;'>This is an automated email.</p>
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
    'payment_amount', 'payment_method', 'class_count'
];

foreach ($required as $field) {
    if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
        throw new Exception("Missing or empty required field: {$field}");
    }
}

// Additional validation: receipt required for bank transfer
$paymentMethod = trim($data['payment_method']);
if ($paymentMethod === 'bank_transfer') {
    if (!isset($data['payment_receipt_base64']) || trim($data['payment_receipt_base64']) === '') {
        throw new Exception("Payment receipt is required for bank transfer");
    }
}


    $host = 'localhost';
    $dbname = 'wushuspo_portal';
    $username = 'wushuspo_admin';
    $password = '%==l;7tS*.OjXd**';

    $conn = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $conn->beginTransaction();

    $parentEmail = trim($data['email']); // This is the PARENT email from the form
    $childIC = trim($data['ic']);
    $childNameEn = trim($data['name_en']);
    
    error_log("[Registration] Starting registration for parent email: {$parentEmail}, child IC: {$childIC}, name: {$childNameEn}");

    // ==========================================
    // NEW: Check for duplicate English name
    // ==========================================
    $duplicateCheck = checkDuplicateEnglishName($conn, $childNameEn, $childIC);
    
    if ($duplicateCheck) {
        $status = $duplicateCheck['payment_status'];
        
        if ($status === 'approved') {
            throw new Exception(
                "A student with the name '{$childNameEn}' is already registered and APPROVED (Registration #: {$duplicateCheck['registration_number']}). ".                "If this is a different student, please use a slightly different English name (e.g., add middle name or initial). ".                "Contact admin at admin@wushusportacademy.com if you need assistance."
            );
        } elseif ($status === 'pending') {
            throw new Exception(
                "A student with the name '{$childNameEn}' already has a PENDING registration (Registration #: {$duplicateCheck['registration_number']}). ".                "Please wait for admin approval or contact the academy for assistance. Registration date: " . date('M j, Y', strtotime($duplicateCheck['created_at']))
            );
        } elseif ($status === 'rejected') {
            error_log("[Duplicate Override] Found rejected registration for '{$childNameEn}' (Reg#: {$duplicateCheck['registration_number']}). Allowing new registration to overwrite.");
            
            // Delete the rejected registration to allow fresh registration
            $stmt = $conn->prepare("DELETE FROM registrations WHERE id = ?");
            $stmt->execute([$duplicateCheck['id']]);
            error_log("[Duplicate Override] Deleted rejected registration ID={$duplicateCheck['id']}");
        }
    }

    // Check for duplicate CHILD (by IC)
    $stmt = $conn->prepare("SELECT id, registration_number, payment_status FROM registrations WHERE ic = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$childIC]);
    $existingChild = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingChild) {
        // Allow if previous registration was rejected, otherwise block
        if ($existingChild['payment_status'] === 'rejected') {
            error_log("[IC Override] Found rejected registration with IC: {$childIC}. Allowing re-registration.");
            $stmt = $conn->prepare("DELETE FROM registrations WHERE id = ?");
            $stmt->execute([$existingChild['id']]);
        } else {
            throw new Exception('A child with this IC number (' . $childIC . ') is already registered (Reg#: ' . $existingChild['registration_number'] . ', Status: ' . $existingChild['payment_status'] . '). Please check the IC number.');
        }
    }

    // UPDATED: Generate unique randomized registration number
    $regNumber = generateUniqueRegistrationNumber($conn);

    // Student account password (last 4 digits of child's IC)
    $generatedPassword = generatePassword($childIC, null, 'ic_last4');
    $hashedPassword    = password_hash($generatedPassword, PASSWORD_DEFAULT);

    $studentId    = $regNumber;
    $fullName     = $childNameEn;
    $phone        = trim($data['phone']);
    $studentStatus = trim($data['status']);
    $paymentAmount = (float)$data['payment_amount'];
    $paymentDate   = trim($data['payment_date']); // User-specified payment date

    // ===============================
    // Find or Create Parent Account
    // ===============================

    $parentData = [
        'name'  => trim($data['parent_name']),
        'email' => $parentEmail,
        'phone' => $phone,
        'ic'    => trim($data['parent_ic']),
        'password_type' => isset($data['password_type']) ? trim($data['password_type']) : 'ic_last4',
        'custom_password' => isset($data['custom_password']) ? trim($data['custom_password']) : null,
    ];


    $parentAccountInfo = findOrCreateParentAccount($conn, $parentData);

    $parentAccountId     = $parentAccountInfo['id'];
    $parentCode          = $parentAccountInfo['parent_id'];
    $isNewParentAccount  = $parentAccountInfo['is_new'];
    $parentPlainPassword = $parentAccountInfo['plain_password'];
    
    error_log("[Parent Info] ID={$parentAccountId}, Code={$parentCode}, IsNew={$isNewParentAccount}, Password=" . ($parentPlainPassword ?? 'NULL'));

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
    // Validate and fix form_date with time
    // ==========================
    $validatedFormDateTime = validateFormDateTime($data['form_date']);
    error_log("[Registration] Original form_date: {$data['form_date']}, Validated: {$validatedFormDateTime}");

        // ==========================
    // SAVE FILES TO LOCAL STORAGE
    // NEW: Convert base64 to files
    // FIXED: Save payment receipt BEFORE registration INSERT
    // UPDATED 2025-12-22: Include user name in filenames
    // ==========================
    
    // Save signature with user name
    $signatureResult = saveBase64ToFile(
    $data['signature_base64'],
    'signatures',
    'signature',
    $regNumber,
    $childNameEn,  // User name
    ''             // No additional info needed
);
    
    if (!$signatureResult['success']) {
        throw new Exception('Failed to save signature: ' . $signatureResult['error']);
    }
    
    $signaturePath = $signatureResult['path'];
    error_log("[File Save] Signature saved: {$signaturePath}");
    
    // Save PDF with user name
    $pdfResult = saveBase64ToFile(
    $data['signed_pdf_base64'],
    'registration_pdfs',
    'pdf',
    $regNumber,
    $childNameEn,  // User name
    ''             // No additional info needed
);
    
    if (!$pdfResult['success']) {
        throw new Exception('Failed to save PDF: ' . $pdfResult['error']);
    }
    
    $pdfPath = $pdfResult['path'];
    error_log("[File Save] PDF saved: {$pdfPath}");
    
// ==========================
// Save payment receipt (only for bank transfer)
// For cash payment, receipt_path will be NULL
// ==========================

$receiptPath = ''; // Initialize as empty string instead of null
$paymentMethod = isset($data['payment_method']) ? trim($data['payment_method']) : 'bank_transfer';
$paymentDate = isset($data['payment_date']) ? trim($data['payment_date']) : date('Y-m-d');

if ($paymentMethod === 'bank_transfer') {
    // Bank transfer requires receipt
    if (!isset($data['payment_receipt_base64']) || empty($data['payment_receipt_base64'])) {
        throw new Exception('Payment receipt is required for bank transfer');
    }
    
    $additionalInfo = "REG-{$regNumber}-{$paymentDate}";
    
    $receiptResult = saveBase64ToFile(
        $data['payment_receipt_base64'],
        'payment_receipts',
        'receipt',
        $studentAccountId,
        $childNameEn,
        $additionalInfo
    );
    
    if (!$receiptResult['success']) {
        throw new Exception('Failed to save payment receipt: ' . $receiptResult['error']);
    }
    
    $receiptPath = $receiptResult['path'];
    error_log("[File Save] Bank transfer receipt saved: {$receiptPath}");
} else {
    // Cash payment - no receipt needed, use empty string
    $receiptPath = '';
    error_log("[File Save] Cash payment - no receipt required, receiptPath set to empty string");
}

    
    // ==========================
    // Insert Registration Record
    // UPDATED: Store file paths instead of base64
    // FIXED: Include payment_receipt_path
    // ==========================

    $sql = "INSERT INTO registrations (
    registration_number, name_cn, name_en, ic, age, school, student_status,
    phone, email, level, events, schedule, parent_name, parent_ic,
    form_date, signature_path, pdf_path,
    payment_amount, payment_method, payment_date, payment_receipt_path, payment_status, class_count,
    student_account_id, account_created, password_generated,
    parent_account_id, registration_type, is_additional_child, created_at
) VALUES (
    ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?,
    ?, ?, ?, ?, 'pending', ?,
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
    $validatedFormDateTime,
    $signaturePath,
    $pdfPath,
    $paymentAmount,
    $paymentMethod,        // NEW: Payment method
    $paymentDate,
    $receiptPath,          // Can be NULL for cash payment
    (int)$data['class_count'],
    $studentAccountId,
    $generatedPassword,
    $parentAccountId,
    $isNewParentAccount ? 0 : 1,
]);


    // ===============================
    // AUTO-ENROLL INTO CLASSES
    // ===============================
    
    $scheduleString = trim($data['schedule']);
    error_log("[Auto-Enroll] Schedule string: {$scheduleString}");
    
    $enrollmentResults = autoEnrollStudent($conn, $studentAccountId, $scheduleString);
    
    error_log("[Auto-Enroll] Results - Success: " . count($enrollmentResults['success']) . 
              ", Failed: " . count($enrollmentResults['failed']) . 
              ", Skipped: " . count($enrollmentResults['skipped']));

    // ===============================
    // CREATE COMBINED INVOICE AND LINK PAYMENT
    // UPDATED: Now creates ONE invoice for all classes
    // ===============================
    
    $paymentData = [
        'payment_date' => $paymentDate,
        'payment_method' => $paymentMethod
    ];
    
    $invoiceResult = createRegistrationInvoicesAndPayments(
        $conn, 
        $studentAccountId, 
        $parentAccountId, 
        $paymentAmount, 
        $fullName,
        $enrollmentResults['success'],
        $paymentData,
        $receiptPath
    );

    $conn->commit();
    
    error_log("[Success] Reg#: {$regNumber}, Parent: {$parentCode} (ID:{$parentAccountId}), Student: {$studentAccountId}, Invoice: COMBINED (1 invoice), Payment Date: {$paymentDate}, Receipt: {$receiptPath}");

    // Send email to parent with proper parameters
    $emailSent = sendRegistrationEmail(
        $parentEmail, 
        $fullName, 
        $regNumber, 
                $generatedPassword, 
        $studentStatus, 
        $isNewParentAccount, 
        $parentPlainPassword
    );
    
    // ==========================
    // Send admin notification
    // ==========================
    $adminNotificationData = [
        'registration_number' => $regNumber,
        'student_name' => $fullName,
        'ic' => $childIC,
        'age' => (int)$data['age'],
        'school' => trim($data['school']),
        'student_status' => $studentStatus,
        'parent_name' => trim($data['parent_name']),
        'parent_ic' => trim($data['parent_ic']),
        'parent_email' => $parentEmail,
        'phone' => $phone,
        'parent_code' => $parentCode,
        'schedule' => $scheduleString,
        'class_count' => (int)$data['class_count'],
        'classes' => $enrollmentResults['success'],
        'payment_amount' => number_format($paymentAmount, 2),
        'payment_date' => $paymentDate,
        'total_invoices' => $invoiceResult['total_invoices'] ?? 1
    ];
    
    $adminEmailSent = sendAdminRegistrationNotification($adminNotificationData);
    error_log("[Admin Notification] Email sent: " . ($adminEmailSent ? 'YES' : 'NO'));

    echo json_encode([
        'success' => true,
        'registration_number' => $regNumber,
        'student_id' => $studentId,
        'email' => $parentEmail,
        'password' => $generatedPassword,
        'child_password' => $generatedPassword,
        'parent_account_id' => $parentAccountId,
        'parent_code' => $parentCode,
        'is_new_parent' => $isNewParentAccount,
        'parent_password' => $isNewParentAccount ? $parentPlainPassword : null,
        'email_sent' => $emailSent,
        'admin_notified' => $adminEmailSent,
        'enrollment_results' => $enrollmentResults,
        'invoice_created' => $invoiceResult['success'],
        'invoices' => $invoiceResult['invoices'] ?? [],
        'total_invoices' => 1, // Always 1 now (combined)
        'form_datetime_recorded' => $validatedFormDateTime,
        'payment_date' => $paymentDate,
        'files_saved' => [
            'signature' => $signaturePath,
            'pdf' => $pdfPath,
            'receipt' => $receiptPath
        ],
        'message' => $isNewParentAccount 
            ? 'Parent account created! Child registered with 1 combined invoice for all ' . count($enrollmentResults['success']) . ' classes.' 
            : 'Child added with 1 combined invoice for all ' . count($enrollmentResults['success']) . ' classes!',
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
