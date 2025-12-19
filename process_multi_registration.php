<?php
/**
 * process_multi_registration.php - Multi-Child Registration Processor
 * Handles registration of multiple children under one parent account
 * Stage 3 Enhancement
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// ============================================
// HELPER FUNCTIONS
// ============================================

function generateRandomPassword(): string {
    $part1 = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 4);
    $part2 = substr(str_shuffle('abcdefghjkmnpqrstuvwxyz'), 0, 4);
    return $part1 . $part2;
}

function generateParentCode(PDO $conn): string {
    $year = date('Y');
    $stmt = $conn->prepare("SELECT COUNT(*) FROM parent_accounts WHERE YEAR(created_at) = ?");
    $stmt->execute([$year]);
    $count = (int)$stmt->fetchColumn();
    return 'PAR-' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
}

function generateStudentCode(PDO $conn): string {
    $year = date('Y');
    $stmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE YEAR(created_at) = ?");
    $stmt->execute([$year]);
    $count = (int)$stmt->fetchColumn();
    return 'WSA' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
}

function calculateFee(int $classCount): int {
    if ($classCount === 1) return 120;
    if ($classCount === 2) return 200;
    if ($classCount === 3) return 280;
    if ($classCount >= 4) return 320;
    return 0;
}

function sendParentWelcomeEmail($toEmail, $parentName, $password, $children) {
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
        $mail->addAddress($toEmail, $parentName);

        $childrenList = '';
        foreach ($children as $child) {
            $childrenList .= "<li><strong>{$child['name']}</strong> - {$child['student_id']}</li>";
        }

        $mail->isHTML(true);
        $mail->Subject = '🎉 Welcome to Wushu Sport Academy - Parent Account Created';
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #1e293b;'>Welcome to Wushu Sport Academy!</h2>
            <p>Dear {$parentName},</p>
            <p>Your parent account has been created successfully. You can now manage all your children's training through one account.</p>
            
            <div style='background: #f8fafc; padding: 20px; border-radius: 10px; margin: 20px 0;'>
                <h3 style='margin-top: 0;'>🔑 Your Login Credentials</h3>
                <p><strong>Email:</strong> {$toEmail}</p>
                <p><strong>Password:</strong> {$password}</p>
                <p><a href='https://yoursite.com/index.php' style='background: #fbbf24; color: #1e293b; padding: 10px 20px; text-decoration: none; border-radius: 8px; display: inline-block; margin-top: 10px;'>Login Now</a></p>
            </div>

            <h3>👶 Registered Children:</h3>
            <ul>{$childrenList}</ul>

            <p><small>Please keep your credentials safe. You can change your password after logging in.</small></p>
        </div>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error: {$mail->ErrorInfo}");
        return false;
    }
}

// ============================================
// MAIN PROCESSING
// ============================================

try {
    error_log("=== Multi-Child Registration Started ===");
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data received');
    }

    error_log("Received data for parent: " . ($data['parent_email'] ?? 'unknown'));

    // Validate parent data
    $requiredParent = ['parent_name', 'parent_ic', 'parent_email', 'parent_phone'];
    foreach ($requiredParent as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing parent field: {$field}");
        }
    }

    // Validate children data
    if (empty($data['children']) || !is_array($data['children'])) {
        throw new Exception('No children data provided');
    }

    error_log("Number of children: " . count($data['children']));

    // Database connection
    $host = 'localhost';
    $dbname = 'mlxysf_student_portal';
    $username = 'mlxysf_student_portal';
    $password_db = 'YAjv86kdSAPpw';

    $conn = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password_db);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $conn->beginTransaction();
    error_log("Transaction started");

    // Check if parent email already exists
    $parentEmail = trim($data['parent_email']);
    $stmt = $conn->prepare("SELECT id, parent_id FROM parent_accounts WHERE email = ? LIMIT 1");
    $stmt->execute([$parentEmail]);
    $existingParent = $stmt->fetch(PDO::FETCH_ASSOC);

    $parentAccountId = null;
    $parentPassword = null;
    $isNewParent = false;

    if ($existingParent) {
        // Use existing parent account
        $parentAccountId = $existingParent['id'];
        $parentCode = $existingParent['parent_id'];
        error_log("Using existing parent account: {$parentCode}");
    } else {
        // Create new parent account
        $parentCode = generateParentCode($conn);
        $parentPassword = generateRandomPassword();
        $hashedPassword = password_hash($parentPassword, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            INSERT INTO parent_accounts (parent_id, full_name, email, phone, ic_number, password, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
        ");
        $stmt->execute([
            $parentCode,
            trim($data['parent_name']),
            $parentEmail,
            trim($data['parent_phone']),
            trim($data['parent_ic']),
            $hashedPassword
        ]);

        $parentAccountId = (int)$conn->lastInsertId();
        $isNewParent = true;
        error_log("Created new parent account: {$parentCode} (ID: {$parentAccountId})");
    }

    // Process each child
    $registeredChildren = [];
    $totalFee = 0;

    foreach ($data['children'] as $index => $child) {
        error_log("Processing child #{$index}: {$child['name_en']}");

        // Generate student credentials
        $studentCode = generateStudentCode($conn);
        $studentPassword = generateRandomPassword();
        $hashedPassword = password_hash($studentPassword, PASSWORD_DEFAULT);

        // Calculate age from IC
        $ic = $child['ic'];
        $year = 2000 + (int)substr($ic, 0, 2);
        $age = 2026 - $year;

        // Create student account
        $stmt = $conn->prepare("
            INSERT INTO students (student_id, full_name, email, phone, password, student_status, 
                                 parent_account_id, student_type, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'child', 'active', NOW())
        ");
        $stmt->execute([
            $studentCode,
            trim($child['name_en']),
            $parentEmail, // Use parent email
            trim($data['parent_phone']),
            $hashedPassword,
            trim($child['status']),
            $parentAccountId
        ]);

        $studentId = (int)$conn->lastInsertId();
        error_log("Created student account: {$studentCode} (ID: {$studentId})");

        // Link to parent
        $stmt = $conn->prepare("
            INSERT INTO parent_child_relationships 
            (parent_id, student_id, relationship, is_primary, can_manage_payments, can_view_attendance, created_at)
            VALUES (?, ?, 'child', 1, 1, 1, NOW())
        ");
        $stmt->execute([$parentAccountId, $studentId]);

        // Calculate fee for this child
        $scheduleCount = count($child['schedules']);
        $childFee = calculateFee($scheduleCount);
        $totalFee += $childFee;

        // Create registration record
        $regNumber = $studentCode; // Same as student code
        $stmt = $conn->prepare("
            INSERT INTO registrations (
                registration_number, name_cn, name_en, ic, age, school, status,
                phone, email, events, schedule, parent_name, parent_ic,
                form_date, signature_base64, pdf_base64,
                payment_amount, payment_date, payment_receipt_base64, payment_status,
                class_count, student_account_id, account_created, password_generated,
                parent_account_id, registration_type, is_additional_child, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, 'pending',
                ?, ?, 'yes', ?,
                ?, 'parent_managed', ?, NOW()
            )
        ");

        $stmt->execute([
            $regNumber,
            $child['name_cn'] ?? '',
            trim($child['name_en']),
            $ic,
            $age,
            trim($child['school']),
            trim($child['status']),
            trim($data['parent_phone']),
            $parentEmail,
            implode(', ', $child['events']),
            implode(', ', $child['schedules']),
            trim($data['parent_name']),
            trim($data['parent_ic']),
            date('Y-m-d'),
            $data['signature_base64'] ?? '',
            $data['signed_pdf_base64'] ?? '',
            $childFee,
            $data['payment_date'] ?? date('Y-m-d'),
            $data['payment_receipt_base64'] ?? '',
            $scheduleCount,
            $studentId,
            $studentPassword,
            $parentAccountId,
            $index > 0 ? 1 : 0 // First child = 0, additional = 1
        ]);

        $registeredChildren[] = [
            'name' => trim($child['name_en']),
            'student_id' => $studentCode,
            'password' => $studentPassword,
            'fee' => $childFee,
            'classes' => $scheduleCount
        ];
    }

    $conn->commit();
    error_log("Transaction committed successfully");

    // Send email to parent
    if ($isNewParent && $parentPassword) {
        $emailSent = sendParentWelcomeEmail($parentEmail, $data['parent_name'], $parentPassword, $registeredChildren);
        error_log("Email sent: " . ($emailSent ? 'SUCCESS' : 'FAILED'));
    }

    error_log("=== Multi-Child Registration Completed ===");

    echo json_encode([
        'success' => true,
        'message' => 'All children registered successfully!',
        'parent_account_id' => $parentAccountId,
        'parent_code' => $parentCode ?? null,
        'is_new_parent' => $isNewParent,
        'parent_password' => $isNewParent ? $parentPassword : null,
        'children' => $registeredChildren,
        'total_fee' => $totalFee,
        'email_sent' => $emailSent ?? false
    ]);

} catch (PDOException $e) {
    error_log("=== PDO EXCEPTION ===");
    error_log("Error: " . $e->getMessage());
    
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
        error_log("Transaction rolled back");
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
    
} catch (Exception $e) {
    error_log("=== EXCEPTION ===");
    error_log("Error: " . $e->getMessage());
    
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
