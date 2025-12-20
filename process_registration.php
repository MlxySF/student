<?php
/**
 * process_registration.php - Complete Registration Processing with PHPMailer
 * Handles student registration, account creation, and email notification
 * Stage 3: Multi-child parent system - auto-detects parent by email
 * UPDATED: Password is now last 4 digits of IC number
 * UPDATED: Auto-enrollment into classes based on schedule selection
 * UPDATED: Creates registration fee invoice viewable in parent portal
 * UPDATED: Links payment receipt to invoice for admin verification
 * FIXED: Auto-detect MIME type from base64 image data
 * FIXED: Strip data URI prefix, save only pure base64 to database
 * UPDATED: Split invoices by class - one invoice per registered class with class code
 * FIXED: Use student_status column name in registrations INSERT statement
 * FIXED: Validate form_date to prevent invalid dates like "-0001"
 * UPDATED: Changed form_date to record both date and time (DATETIME format)
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

// =========================
// Helper: Detect MIME type from base64 image
// =========================
function detectMimeTypeFromBase64(string $base64Data): string {
    // Check if it has data URI prefix
    if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $matches)) {
        // Extract MIME type from data URI
        return 'image/' . $matches[1];
    }
    
    // Remove data URI prefix if present for checking signature
    $cleanBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64Data);
    
    // Decode first few bytes to check file signature
    $imageData = base64_decode(substr($cleanBase64, 0, 100));
    
    // Check image signatures (magic numbers)
    if (substr($imageData, 0, 3) === "\xFF\xD8\xFF") {
        return 'image/jpeg';
    } elseif (substr($imageData, 0, 8) === "\x89PNG\r\n\x1A\n") {
        return 'image/png';
    } elseif (substr($imageData, 0, 6) === 'GIF87a' || substr($imageData, 0, 6) === 'GIF89a') {
        return 'image/gif';
    } elseif (substr($imageData, 0, 2) === 'BM') {
        return 'image/bmp';
    } elseif (substr($imageData, 0, 4) === 'RIFF' && substr($imageData, 8, 4) === 'WEBP') {
        return 'image/webp';
    }
    
    // Default to JPEG if cannot detect
    error_log('[MIME Detection] Could not detect image type, defaulting to image/jpeg');
    return 'image/jpeg';
}

// =========================
// Helper: Strip data URI prefix from base64
// =========================
function stripDataURIPrefix(string $base64Data): string {
    // Remove data:image/xxx;base64, prefix if present
    return preg_replace('/^data:image\/\w+;base64,/', '', $base64Data);
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
// UPDATED: Create one invoice per class
// ==========================================

/**
 * Create registration fee invoices - ONE PER CLASS
 * Links the uploaded payment receipt to each invoice
 * Splits total registration fee equally across all classes
 */
function createRegistrationInvoicesAndPayments(PDO $conn, int $studentId, int $parentAccountId, float $totalAmount, string $studentName, array $enrolledClasses, array $paymentData): array {
    try {
        $results = [];
        $classCount = count($enrolledClasses);
        
        if ($classCount === 0) {
            throw new Exception("No enrolled classes found");
        }
        
        // Calculate amount per class (split total registration fee)
        $amountPerClass = round($totalAmount / $classCount, 2);
        
        error_log("[Invoice Split] Total: {$totalAmount}, Classes: {$classCount}, Per Class: {$amountPerClass}");
        
        foreach ($enrolledClasses as $classData) {
            // Generate unique invoice number
            $invoiceNumber = 'INV-REG-' . date('Ym') . '-' . str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            
            // Set due date to today (since payment is already uploaded)
            $dueDate = date('Y-m-d');
            
            // Description includes class name
            $description = "Registration Fee for {$studentName} - {$classData['class_name']} (" . date('Y') . ")";
            
            // Create invoice WITH class_id and class_code
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
                $classData['class_id'],
                $classData['class_code'],
                $description,
                $amountPerClass,
                $dueDate
            ]);
            
            $invoiceId = (int)$conn->lastInsertId();
            
            error_log("[Invoice] Created invoice: {$invoiceNumber} (ID: {$invoiceId}) for class {$classData['class_code']}, amount={$amountPerClass}");
            
            // Auto-detect MIME type from base64 data
            $receiptMimeType = detectMimeTypeFromBase64($paymentData['receipt_base64']);
            
            // Strip data URI prefix to get pure base64
            $pureBase64Receipt = stripDataURIPrefix($paymentData['receipt_base64']);
            
            // Create payment record linked to this invoice and class
            $stmt = $conn->prepare("
                INSERT INTO payments (
                    student_id,
                    class_id,
                    invoice_id,
                    parent_account_id,
                    amount,
                    payment_month,
                    receipt_data,
                    receipt_mime_type,
                    verification_status,
                    upload_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $stmt->execute([
                $studentId,
                $classData['class_id'],
                $invoiceId,
                $parentAccountId,
                $amountPerClass,
                date('Y-m'),
                $pureBase64Receipt,
                $receiptMimeType
            ]);
            
            $paymentId = (int)$conn->lastInsertId();
            
            error_log("[Payment] Created payment record (ID: {$paymentId}) linked to invoice {$invoiceNumber}");
            
            $results[] = [
                'invoice_id' => $invoiceId,
                'invoice_number' => $invoiceNumber,
                'payment_id' => $paymentId,
                'class_code' => $classData['class_code'],
                'class_name' => $classData['class_name'],
                'amount' => $amountPerClass
            ];
        }
        
        return [
            'success' => true,
            'invoices' => $results,
            'total_invoices' => count($results)
        ];
        
    } catch (PDOException $e) {
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
            <h3 style='margin: 0 0 16px 0; color: #856404; font-size: 20px;'>🔑 Parent Account Created!</h3>
            <p style='margin: 0 0 12px 0; color: #856404; font-size: 15px;'><strong>This is your FIRST child registration.</strong> A parent account has been created for you to manage all your children.</p>
            
            <table style='width: 100%; margin: 16px 0;'>
                <tr>
                    <td style='padding: 8px 0; color: #856404; font-weight: 600; width: 40%;'>Parent Login Email:</td>
                    <td style='padding: 8px 0; color: #856404;'>{$toEmail}</td>
                </tr>
                <tr>
                    <td style='padding: 12px 0 8px 0; color: #856404; font-weight: 600; vertical-align: top;'>Parent Password:</td>
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
            <p style='margin: 0; color: #0c5460; font-size: 14px;'>This child has been successfully linked to your existing parent account. Login with your parent email and password to view all children.</p>
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
                <h3 style='margin: 0 0 16px 0; color: #166534; font-size: 18px;'>👶 Child Account Details</h3>
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
                    <tr>
                        <td style='padding: 6px 0; color: #166534; font-weight: 600; vertical-align: top;'>Child Password:</td>
                        <td style='padding: 6px 0; color: #166534;'><code style='background: #fff; padding: 6px 12px; border: 1px solid #bbf7d0; border-radius: 4px; font-family: monospace; font-size: 14px;'>{$childPassword}</code><br><span style='font-size: 12px; color: #15803d;'>(Last 4 digits of child's IC · Optional for independent login)</span></td>
                    </tr>
                </table>
            </div>
            
            <div style='background: #eff6ff; border-left: 4px solid #3b82f6; padding: 20px; border-radius: 8px; margin: 24px 0;'>
                <p style='margin: 0 0 8px 0; font-weight: 600; color: #1e40af; font-size: 16px;'>💡 Register More Children</p>
                <p style='margin: 0; color: #1e40af; font-size: 14px; line-height: 1.6;'>To register additional children, simply use the <strong>same email address</strong> ({$toEmail}) when filling the registration form. All your children will be automatically linked to your parent account!</p>
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
                    <li>Login to the parent portal to view separate invoices for each class (registration fee is split equally)</li>
                    <li>After approval, all invoice statuses will change to Paid</li>
                    <li>Your child has been automatically enrolled in the selected classes</li>
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

    // Student account password (last 4 digits of child's IC)
    $generatedPassword = generatePasswordFromIC($childIC);
    $hashedPassword    = password_hash($generatedPassword, PASSWORD_DEFAULT);

    $studentId    = $regNumber;
    $fullName     = trim($data['name_en']);
    $phone        = trim($data['phone']);
    $studentStatus = trim($data['status']);
    $paymentAmount = (float)$data['payment_amount'];

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
    // UPDATED: Now records DATETIME
    // ==========================
    $validatedFormDateTime = validateFormDateTime($data['form_date']);
    error_log("[Registration] Original form_date: {$data['form_date']}, Validated: {$validatedFormDateTime}");

    // ==========================
    // Insert Registration Record
    // FIXED: Use student_status column name
    // FIXED: Validate form_date with time
    // ==========================

    $sql = "INSERT INTO registrations (
        registration_number, name_cn, name_en, ic, age, school, student_status,
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
        $studentStatus,  // This now goes into student_status column
        $phone,
        $parentEmail,
        isset($data['level']) ? trim($data['level']) : '',
        trim($data['events']),
        trim($data['schedule']),
        trim($data['parent_name']),
        trim($data['parent_ic']),
        $validatedFormDateTime,  // Use validated form datetime
        $data['signature_base64'],
        $data['signed_pdf_base64'],
        $paymentAmount,
        $data['payment_date'],
        $data['payment_receipt_base64'],
        (int)$data['class_count'],
        $studentAccountId,
        $generatedPassword,
        $parentAccountId,
        $isNewParentAccount ? 0 : 1, // is_additional_child
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
    // CREATE INVOICES (ONE PER CLASS) AND LINK PAYMENTS
    // ===============================
    
    $paymentData = [
        'receipt_base64' => $data['payment_receipt_base64']
    ];
    
    // Use the new function that creates one invoice per class
    $invoiceResult = createRegistrationInvoicesAndPayments(
        $conn, 
        $studentAccountId, 
        $parentAccountId, 
        $paymentAmount, 
        $fullName,
        $enrollmentResults['success'], // Pass enrolled classes with their codes
        $paymentData
    );

    $conn->commit();
    
    error_log("[Success] Reg#: {$regNumber}, Parent: {$parentCode} (ID:{$parentAccountId}), Student: {$studentAccountId}, Invoices: " . ($invoiceResult['total_invoices'] ?? 0));

    // Send email with proper parameters
    $emailSent = sendRegistrationEmail(
        $parentEmail, 
        $fullName, 
        $regNumber, 
        $generatedPassword, 
        $studentStatus, 
        $isNewParentAccount, 
        $parentPlainPassword
    );

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
        'enrollment_results' => $enrollmentResults,
        'invoice_created' => $invoiceResult['success'],
        'invoices' => $invoiceResult['invoices'] ?? [],
        'total_invoices' => $invoiceResult['total_invoices'] ?? 0,
        'form_datetime_recorded' => $validatedFormDateTime,
        'message' => $isNewParentAccount 
            ? 'Parent account created! Child registered with ' . ($invoiceResult['total_invoices'] ?? 0) . ' invoices (one per class).' 
            : 'Child added with ' . ($invoiceResult['total_invoices'] ?? 0) . ' invoices (one per class)!',
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
