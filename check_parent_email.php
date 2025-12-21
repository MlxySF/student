<?php
/**
 * check_parent_email.php
 * Check if parent email already exists in the system
 * Returns JSON response
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // Get email from request
    $email = isset($_GET['email']) ? trim($_GET['email']) : '';
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'exists' => false,
            'error' => 'Invalid email address'
        ]);
        exit;
    }
    
    // Database connection
    $host = 'localhost';
    $dbname = 'mlxysf_student_portal';
    $username = 'mlxysf_student_portal';
    $password = 'YAjv86kdSAPpw';
    
    $conn = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if email exists in parent_accounts table
    $stmt = $conn->prepare("SELECT id, parent_id, full_name, created_at FROM parent_accounts WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $parent = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($parent) {
        // Parent exists
        echo json_encode([
            'success' => true,
            'exists' => true,
            'parent_id' => $parent['parent_id'],
            'parent_name' => $parent['full_name'],
            'registered_date' => date('M j, Y', strtotime($parent['created_at']))
        ]);
    } else {
        // New parent
        echo json_encode([
            'success' => true,
            'exists' => false
        ]);
    }
    
} catch (PDOException $e) {
    error_log("[Check Email] Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'exists' => false,
        'error' => 'Database error'
    ]);
} catch (Exception $e) {
    error_log("[Check Email] Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'exists' => false,
        'error' => $e->getMessage()
    ]);
}
