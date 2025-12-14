<?php
// process_registration.php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

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
        if (!isset($data[$field]) || $data[$field] === '') {
            throw new Exception("Missing required field: $field");
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
    $studentId = $regNumber; // Use registration number as student ID
    $fullName = $data['name_en'];
    $email = $data['email'];
    $phone = $data['phone'];

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM students WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        throw new Exception("Email already registered. Please use a different email.");
    }

    // Insert into students table
    $stmt = $conn->prepare("
        INSERT INTO students (student_id, full_name, email, phone, password, status, created_at) 
        VALUES (?, ?, ?, ?, ?, 'active', NOW())
    ");
    $stmt->execute([$studentId, $fullName, $email, $phone, $hashedPassword]);
    $studentAccountId = $conn->lastInsertId();

    // Parse schedule to get classes
    $schedules = explode(', ', $data['schedule']);
    $classIds = [];

    foreach ($schedules as $schedule) {
        // Extract class code from schedule (e.g., "WSH-101: Monday 3-5pm" -> "WSH-101")
        if (preg_match('/([A-Z]+-\d+)/', $schedule, $matches)) {
            $classCode = $matches[1];
            
            // Get class ID
            $stmt = $conn->prepare("SELECT id FROM classes WHERE class_code = ?");
            $stmt->execute([$classCode]);
            $class = $stmt->fetch();
            
            if ($class) {
                $classIds[] = $class['id'];
                
                // Enroll student in class
                $stmt = $conn->prepare("
                    INSERT INTO enrollments (student_id, class_id, enrollment_date, status) 
                    VALUES (?, ?, ?, 'active')
                ");
                $stmt->execute([$studentAccountId, $class['id'], date('Y-m-d')]);
            }
        }
    }

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
        ':name_cn' => $data['name_cn'] ?? '',
        ':name_en' => $data['name_en'],
        ':ic' => $data['ic'],
        ':age' => $data['age'],
        ':school' => $data['school'],
        ':status' => $data['status'],
        ':phone' => $data['phone'],
        ':email' => $data['email'],
        ':level' => $data['level'] ?? '',
        ':events' => $data['events'],
        ':schedule' => $data['schedule'],
        ':parent_name' => $data['parent_name'],
        ':parent_ic' => $data['parent_ic'],
        ':form_date' => $data['form_date'],
        ':signature_base64' => $data['signature_base64'],
        ':pdf_base64' => $data['signed_pdf_base64'],
        ':payment_amount' => $data['payment_amount'],
        ':payment_date' => $data['payment_date'],
        ':payment_receipt_base64' => $data['payment_receipt_base64'],
        ':class_count' => $data['class_count'],
        ':student_account_id' => $studentAccountId,
        ':password_generated' => $generatedPassword
    ]);

    // Commit transaction
    $conn->commit();

    // Send email with credentials (optional - you can implement this later)
    // sendWelcomeEmail($email, $fullName, $studentId, $generatedPassword);

    echo json_encode([
        'success' => true,
        'registration_number' => $regNumber,
        'student_id' => $studentId,
        'email' => $email,
        'password' => $generatedPassword,
        'message' => 'Registration successful! Student account created.'
    ]);

} catch (PDOException $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
