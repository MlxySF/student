<?php
// student/process_registration.php
error_reporting(0); // Hide errors from output
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Database connection
    require_once __DIR__ . '/../includes/db.php';

    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
        exit;
    }

    // Extract data
    $name_cn = $data['name_cn'] ?? '';
    $name_en = $data['name_en'] ?? '';
    $ic = $data['ic'] ?? '';
    $age = $data['age'] ?? 0;
    $school = $data['school'] ?? '';
    $status = $data['status'] ?? '';
    $phone = $data['phone'] ?? '';
    $email = $data['email'] ?? '';
    $level = $data['level'] ?? '';
    $events = $data['events'] ?? '';
    $schedule = $data['schedule'] ?? '';
    $parent_name = $data['parent_name'] ?? '';
    $parent_ic = $data['parent_ic'] ?? '';
    $form_date = $data['form_date'] ?? '';
    $signature_base64 = $data['signature_base64'] ?? '';
    $signed_pdf_base64 = $data['signed_pdf_base64'] ?? '';

    // Validate required fields
    if (empty($name_en) || empty($ic) || empty($email)) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }

    // Generate registration number
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM registrations WHERE YEAR(created_at) = 2026");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $count = $result['total'] + 1;
    $registration_number = 'REG2026' . str_pad($count, 3, '0', STR_PAD_LEFT);

    // Insert into database
    $sql = "INSERT INTO registrations (
        registration_number, name_cn, name_en, ic, age, school, status,
        phone, email, level, events, schedule, parent_name, parent_ic,
        form_date, signature_base64, signed_pdf_base64, created_at
    ) VALUES (
        :reg_num, :name_cn, :name_en, :ic, :age, :school, :status,
        :phone, :email, :level, :events, :schedule, :parent_name, :parent_ic,
        :form_date, :signature, :pdf, NOW()
    )";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':reg_num' => $registration_number,
        ':name_cn' => $name_cn,
        ':name_en' => $name_en,
        ':ic' => $ic,
        ':age' => $age,
        ':school' => $school,
        ':status' => $status,
        ':phone' => $phone,
        ':email' => $email,
        ':level' => $level,
        ':events' => $events,
        ':schedule' => $schedule,
        ':parent_name' => $parent_name,
        ':parent_ic' => $parent_ic,
        ':form_date' => $form_date,
        ':signature' => $signature_base64,
        ':pdf' => $signed_pdf_base64
    ]);

    echo json_encode([
        'success' => true,
        'registration_number' => $registration_number,
        'message' => 'Registration submitted successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
