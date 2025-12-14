<?php
// process_registration.php - OPTIMIZED VERSION
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

    // Log received data for debugging (remove sensitive data)
    $debugData = $data;
    unset($debugData['signature_base64'], $debugData['signed_pdf_base64'], $debugData['payment_receipt_base64']);
    error_log('Registration data received: ' . json_encode($debugData));

    // Validate required fields
    $required = ['name_en', 'ic', 'age', 'school', 'status', 'phone', 'email', 
                 'events', 'schedule', 'parent_name', 'parent_ic', 
                 'form_date', 'signature_base64', 'signed_pdf_base64',
                 'payment_amount', 'payment_date', 'payment_receipt_base64', 'class_count'];
    
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            error_log("Missing field: $field");
            throw new Exception("Missing required field: $field. Please complete all steps.");
        }
    }

    // Extract and validate payment receipt
    $receiptData = $data['payment_receipt_base64'];
    
    // Check if it's a data URL format
    if (preg_match('/^data:([a-zA-Z0-9]+\/[a-zA-Z0-9\-\+\.]+);base64,/', $receiptData, $matches)) {
        $mimeType = $matches[1];
        $receiptBase64 = preg_replace('/^data:[^;]+;base64,/', '', $receiptData);
        error_log("Receipt MIME type: $mimeType");
    } else {
        // If no data URL prefix, assume it's already base64
        $receiptBase64 = $receiptData;
        $mimeType = 'image/jpeg'; // default
        error_log('Receipt has no MIME prefix, using default');
    }

    // Validate base64
    if (!base64_decode($receiptBase64, true)) {
        throw new Exception('Invalid receipt file encoding. Please upload a valid image or PDF.');
    }

    // Validate MIME type
    $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
    if (!in_array($mimeType, $allowedMimes)) {
        throw new Exception('Invalid file type. Only JPG, PNG, and PDF are allowed.');
    }

    // Auto-extract level from events (FIX FOR MISSING LEVEL FIELD)
    $level = 'Mixed'; // default
    $events = $data['events'];
    if (strpos($events, '基础') !== false || strpos($events, 'Basic') !== false) {
        $level = '基础 Basic';
    } elseif (strpos($events, '初级') !== false || strpos($events, 'Junior') !== false) {
        $level = '初级 Junior';
    } elseif (strpos($events, 'B组') !== false || strpos($events, 'Group B') !== false) {
        $level = 'B组 Group B';
    } elseif (strpos($events, 'A组') !== false || strpos($events, 'Group A') !== false) {
        $level = 'A组 Group A';
    } elseif (strpos($events, '自选') !== false || strpos($events, 'Optional') !== false) {
        $level = '自选 Optional';
    }
    
    // If level provided in data, use it
    if (!empty($data['level'])) {
        $level = $data['level'];
    }

    error_log("Extracted level: $level");

    // Database connection
    require_once 'config.php';

    // Start transaction
    $pdo->beginTransaction();

    // Generate registration number
    $year = date('Y');
    $stmt = $pdo->query("SELECT COUNT(*) FROM registrations WHERE YEAR(created_at) = $year");
    $count = $stmt->fetchColumn();
    $regNumber = 'WSA' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

    error_log("Generated registration number: $regNumber");

    // Generate random secure password
    $generatedPassword = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 4) . 
                        substr(str_shuffle('abcdefghjkmnpqrstuvwxyz'), 0, 4);
    $hashedPassword = password_hash($generatedPassword, PASSWORD_DEFAULT);

    $fullName = $data['name_en'];
    $email = strtolower(trim($data['email']));
    $phone = $data['phone'];

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        throw new Exception("Email already registered. Please use a different email or login to your existing account.");
    }

    // Insert into students table
    $stmt = $pdo->prepare("
        INSERT INTO students (full_name, email, phone, password, ic_number, age, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$fullName, $email, $phone, $hashedPassword, $data['ic'], $data['age']]);
    $studentAccountId = $pdo->lastInsertId();

    error_log("Created student account ID: $studentAccountId");

    // Parse schedule to get classes and create enrollments
    $schedules = array_map('trim', explode(',', $data['schedule']));
    $classIds = [];

    // Map schedule strings to class names
    $scheduleToClassMap = [
        'Wushu Sport Academy: Sun 10am-12pm' => 'WSA - Sunday Morning (State/Backup)',
        'Wushu Sport Academy: Sun 12pm-2pm' => 'WSA - Sunday Afternoon',
        'Wushu Sport Academy: Wed 8pm-10pm' => 'WSA - Wednesday Evening',
        'SJK(C) Puay Chai 2: Tue 8pm-10pm' => 'PC2 - Tuesday Evening (State/Backup)',
        'Stadium Chinwoo: Sun 2pm-4pm' => 'Chinwoo - Sunday Afternoon (State/Backup)'
    ];

    foreach ($schedules as $schedule) {
        if (empty($schedule)) continue;
        
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
            error_log("Created new class: $className (ID: $classId)");
        }
        
        $classIds[] = $classId;
        
        // Enroll student in class
        $stmt = $pdo->prepare("
            INSERT INTO enrollments (student_id, class_id, enrollment_date, status) 
            VALUES (?, ?, ?, 'pending')
        ");
        $stmt->execute([$studentAccountId, $classId, date('Y-m-d')]);
        error_log("Enrolled student in class ID: $classId");
    }

    // Process signature and PDF base64
    $signatureBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $data['signature_base64']);
    $pdfBase64 = preg_replace('/^data:application\/pdf;base64,/', '', $data['signed_pdf_base64']);

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
    $result = $stmt->execute([
        ':reg_num' => $regNumber,
        ':name_cn' => $data['name_cn'] ?? '',
        ':name_en' => $data['name_en'],
        ':ic' => $data['ic'],
        ':age' => $data['age'],
        ':school' => $data['school'],
        ':status' => $data['status'],
        ':phone' => $data['phone'],
        ':email' => $email,
        ':level' => $level,
        ':events' => $data['events'],
        ':schedule' => $data['schedule'],
        ':parent_name' => $data['parent_name'],
        ':parent_ic' => $data['parent_ic'],
        ':form_date' => $data['form_date'],
        ':signature_base64' => $signatureBase64,
        ':pdf_base64' => $pdfBase64,
        ':payment_amount' => $data['payment_amount'],
        ':payment_date' => $data['payment_date'],
        ':payment_receipt_base64' => $receiptBase64,
        ':class_count' => $data['class_count'],
        ':student_account_id' => $studentAccountId,
        ':password_generated' => $generatedPassword
    ]);

    if (!$result) {
        error_log('Failed to insert registration: ' . print_r($stmt->errorInfo(), true));
        throw new Exception('Failed to save registration data.');
    }

    error_log("Registration record created successfully");

    // Create initial payment record
    // Get first class ID for payment record
    $firstClassId = !empty($classIds) ? $classIds[0] : null;
    
    if ($firstClassId) {
        $stmt = $pdo->prepare("
            INSERT INTO payments (
                student_id, class_id, amount, payment_month, payment_date,
                receipt_data, receipt_mime_type, status, admin_notes, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
        ");
        
        $paymentMonth = date('Y-m', strtotime($data['payment_date']));
        $adminNotes = "Initial registration payment. Registration #: $regNumber";
        
        $stmt->execute([
            $studentAccountId,
            $firstClassId,
            $data['payment_amount'],
            $paymentMonth,
            $data['payment_date'],
            $receiptBase64,
            $mimeType,
            $adminNotes
        ]);
        
        error_log("Payment record created");
    }

    // Commit transaction
    $pdo->commit();

    // Log successful registration
    error_log("✅ Registration successful: $regNumber, Email: $email, Student ID: $studentAccountId");

    // TODO: Send email with credentials (implement email function)
    // sendWelcomeEmail($email, $fullName, $email, $generatedPassword);

    // Return success response
    echo json_encode([
        'success' => true,
        'registration_number' => $regNumber,
        'student_id' => $studentAccountId,
        'email' => $email,
        'temp_password' => $generatedPassword, // In production, don't return password in response
        'message' => 'Registration successful! Your login credentials have been created.',
        'login_instructions' => [
            'email' => $email,
            'password_note' => 'Check your email for password (temporary password: ' . $generatedPassword . ')',
            'login_url' => '../index.php'
        ]
    ]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("❌ Database error in registration: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
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
    error_log("❌ Registration error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
