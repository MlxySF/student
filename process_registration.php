<?php
// process_registration.php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

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

    // Validate payment receipt format
    if (!preg_match('/^data:([a-zA-Z0-9]+\/[a-zA-Z0-9\-\+\.]+);base64,/', $data['payment_receipt_base64'])) {
        throw new Exception('Invalid receipt format. Please upload a valid image or PDF file.');
    }

    // Database connection
    require_once 'config.php';

    // Start transaction
    $pdo->beginTransaction();

    // Generate registration number
    $year = date('Y');
    $stmt = $pdo->query("SELECT COUNT(*) FROM registrations WHERE YEAR(created_at) = $year");
    $count = $stmt->fetchColumn();
    $regNumber = 'WSA' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

    // Generate random password (8 characters)
    $generatedPassword = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 4) . 
                        substr(str_shuffle('abcdefghjkmnpqrstuvwxyz'), 0, 4);
    $hashedPassword = password_hash($generatedPassword, PASSWORD_DEFAULT);

    // Use registration number as student ID
    $studentId = $regNumber;
    $fullName = $data['name_en'];
    $email = $data['email'];
    $phone = $data['phone'];

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        throw new Exception("Email already registered. Please use a different email.");
    }

    // Insert into students table
    $stmt = $pdo->prepare("
        INSERT INTO students (full_name, email, phone, password, ic_number, age, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$fullName, $email, $phone, $hashedPassword, $data['ic'], $data['age']]);
    $studentAccountId = $pdo->lastInsertId();

    // Parse schedule to get classes and create enrollments
    $schedules = explode(', ', $data['schedule']);
    $classIds = [];

    // Map schedule strings to class names (you can expand this mapping)
    $scheduleToClassMap = [
        'Wushu Sport Academy: Sun 10am-12pm' => 'WSA - Sunday Morning (State/Backup)',
        'Wushu Sport Academy: Sun 12pm-2pm' => 'WSA - Sunday Afternoon',
        'Wushu Sport Academy: Wed 8pm-10pm' => 'WSA - Wednesday Evening',
        'SJK(C) Puay Chai 2: Tue 8pm-10pm' => 'PC2 - Tuesday Evening (State/Backup)',
        'Stadium Chinwoo: Sun 2pm-4pm' => 'Chinwoo - Sunday Afternoon (State/Backup)'
    ];

    foreach ($schedules as $schedule) {
        $schedule = trim($schedule);
        $className = $scheduleToClassMap[$schedule] ?? $schedule;
        
        // Try to find existing class or create a new entry
        $stmt = $pdo->prepare("SELECT id FROM classes WHERE class_name = ? OR schedule = ?");
        $stmt->execute([$className, $schedule]);
        $class = $stmt->fetch();
        
        if ($class) {
            $classId = $class['id'];
        } else {
            // Create class if doesn't exist
            $stmt = $pdo->prepare("
                INSERT INTO classes (class_name, schedule, created_at) 
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$className, $schedule]);
            $classId = $pdo->lastInsertId();
        }
        
        $classIds[] = $classId;
        
        // Enroll student in class
        $stmt = $pdo->prepare("
            INSERT INTO enrollments (student_id, class_id, enrollment_date, status) 
            VALUES (?, ?, ?, 'pending')
        ");
        $stmt->execute([$studentAccountId, $classId, date('Y-m-d')]);
    }

    // Extract base64 data from payment receipt (remove data URL prefix)
    $receiptBase64 = preg_replace('/^data:([a-zA-Z0-9]+\/[a-zA-Z0-9\-\+\.]+);base64,/', '', $data['payment_receipt_base64']);
    
    // Extract MIME type
    preg_match('/^data:([a-zA-Z0-9]+\/[a-zA-Z0-9\-\+\.]+);base64,/', $data['payment_receipt_base64'], $matches);
    $mimeType = $matches[1] ?? 'image/jpeg';

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

    $stmt = $pdo->prepare($sql);
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
        ':level' => $data['level'] ?? 'Not Specified',
        ':events' => $data['events'],
        ':schedule' => $data['schedule'],
        ':parent_name' => $data['parent_name'],
        ':parent_ic' => $data['parent_ic'],
        ':form_date' => $data['form_date'],
        ':signature_base64' => $data['signature_base64'],
        ':pdf_base64' => $data['signed_pdf_base64'],
        ':payment_amount' => $data['payment_amount'],
        ':payment_date' => $data['payment_date'],
        ':payment_receipt_base64' => $receiptBase64,
        ':class_count' => $data['class_count'],
        ':student_account_id' => $studentAccountId,
        ':password_generated' => $generatedPassword
    ]);

    // Create initial payment record
    $stmt = $pdo->prepare("
        INSERT INTO payments (
            student_id, amount, payment_month, payment_date,
            receipt_data, receipt_mime_type, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $paymentMonth = date('Y-m', strtotime($data['payment_date']));
    $stmt->execute([
        $studentAccountId,
        $data['payment_amount'],
        $paymentMonth,
        $data['payment_date'],
        $receiptBase64,
        $mimeType
    ]);

    // Commit transaction
    $pdo->commit();

    // Log successful registration
    error_log("Registration successful: $regNumber, Email: $email");

    // Return success response
    echo json_encode([
        'success' => true,
        'registration_number' => $regNumber,
        'student_id' => $studentAccountId,
        'email' => $email,
        'password' => $generatedPassword,
        'message' => 'Registration successful! You can now login with your email and the password sent to you.',
        'login_url' => 'index.php?page=login'
    ]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Database error in registration: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred. Please try again or contact support.',
        'details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Registration error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
