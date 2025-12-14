<?php
// process_registration.php - Using student_status column
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('Invalid JSON data received');
    }

    // Validate required fields
    $required = ['name_en', 'ic', 'age', 'school', 'status', 'phone', 'email', 
                 'events', 'schedule', 'parent_name', 'parent_ic', 
                 'form_date', 'signature_base64', 'signed_pdf_base64',
                 'payment_amount', 'payment_date', 'payment_receipt_base64', 'class_count'];
    
    foreach ($required as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            throw new Exception("Missing or empty required field: $field");
        }
    }

    // Database connection
    $host = 'localhost';
    $dbname = 'mlxysf_student_portal';
    $username = 'mlxysf_student_portal';
    $password = 'YAjv86kdSAPpw';

    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Start transaction
    $conn->beginTransaction();

    // Generate registration number
    $year = date('Y');
    $stmt = $conn->query("SELECT COUNT(*) FROM registrations WHERE YEAR(created_at) = $year");
    $count = $stmt->fetchColumn();
    $regNumber = 'WSA' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

    // Generate random password (8 characters)
    $generatedPassword = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 4) . 
                        substr(str_shuffle('abcdefghjkmnpqrstuvwxyz'), 0, 4);
    $hashedPassword = password_hash($generatedPassword, PASSWORD_DEFAULT);

    // Create student account
    $studentId = $regNumber;
    $fullName = trim($data['name_en']);
    $email = trim($data['email']);
    $phone = trim($data['phone']);
    $studentStatus = trim($data['status']); // Student/State Team/Backup Team

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM students WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        throw new Exception("Email already registered. Please use a different email.");
    }

    // Insert into students table - WITH student_status column
    $stmt = $conn->prepare("
        INSERT INTO students (student_id, full_name, email, phone, password, student_status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$studentId, $fullName, $email, $phone, $hashedPassword, $studentStatus]);
    $studentAccountId = $conn->lastInsertId();

    // Insert into registrations table
    $sql = "INSERT INTO registrations (
        registration_number, name_cn, name_en, ic, age, school, status,
        phone, email, level, events, schedule, parent_name, parent_ic,
        form_date, signature_base64, pdf_base64, 
        payment_amount, payment_date, payment_receipt_base64, payment_status, class_count,
        student_account_id, account_created, password_generated, created_at
    ) VALUES (
        :reg_num, :name_cn, :name_en, :ic, :age, :school, :status,
        :phone, :email, :level, :events, :schedule, :parent_name, :parent_ic,
        :form_date, :signature_base64, :pdf_base64,
        :payment_amount, :payment_date, :payment_receipt_base64, 'pending', :class_count,
        :student_account_id, 'yes', :password_generated, NOW()
    )";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':reg_num' => $regNumber,
        ':name_cn' => isset($data['name_cn']) ? trim($data['name_cn']) : '',
        ':name_en' => $fullName,
        ':ic' => trim($data['ic']),
        ':age' => intval($data['age']),
        ':school' => trim($data['school']),
        ':status' => $studentStatus,
        ':phone' => $phone,
        ':email' => $email,
        ':level' => isset($data['level']) ? trim($data['level']) : '',
        ':events' => trim($data['events']),
        ':schedule' => trim($data['schedule']),
        ':parent_name' => trim($data['parent_name']),
        ':parent_ic' => trim($data['parent_ic']),
        ':form_date' => $data['form_date'],
        ':signature_base64' => $data['signature_base64'],
        ':pdf_base64' => $data['signed_pdf_base64'],
        ':payment_amount' => floatval($data['payment_amount']),
        ':payment_date' => $data['payment_date'],
        ':payment_receipt_base64' => $data['payment_receipt_base64'],
        ':class_count' => intval($data['class_count']),
        ':student_account_id' => $studentAccountId,
        ':password_generated' => $generatedPassword
    ]);

    // Commit transaction
    $conn->commit();

    error_log("Registration successful: $regNumber for $email with status: $studentStatus");

    echo json_encode([
        'success' => true,
        'registration_number' => $regNumber,
        'student_id' => $studentId,
        'email' => $email,
        'password' => $generatedPassword,
        'status' => $studentStatus,
        'message' => 'Registration successful! Your account has been created.'
    ]);

} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
