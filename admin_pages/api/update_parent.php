<?php
// admin_pages/api/update_parent.php - Update parent account information
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

$parent_id = isset($data['parent_id']) ? intval($data['parent_id']) : 0;
$full_name = isset($data['full_name']) ? trim($data['full_name']) : '';
$email = isset($data['email']) ? trim($data['email']) : '';
$phone = isset($data['phone']) ? trim($data['phone']) : '';
$ic_number = isset($data['ic_number']) ? trim($data['ic_number']) : '';
$status = isset($data['status']) ? $data['status'] : 'active';

// Validation
if (!$parent_id) {
    echo json_encode(['success' => false, 'message' => 'Parent ID is required']);
    exit;
}

if (empty($full_name)) {
    echo json_encode(['success' => false, 'message' => 'Full name is required']);
    exit;
}

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

if (empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Phone is required']);
    exit;
}

if (!in_array($status, ['active', 'inactive'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    // Verify parent exists
    $stmt = $pdo->prepare("SELECT id, parent_id, full_name, email FROM parent_accounts WHERE id = ?");
    $stmt->execute([$parent_id]);
    $parent = $stmt->fetch();
    
    if (!$parent) {
        echo json_encode(['success' => false, 'message' => 'Parent account not found']);
        exit;
    }
    
    // Check if email is already used by another parent
    if ($email !== $parent['email']) {
        $stmt = $pdo->prepare("SELECT id FROM parent_accounts WHERE email = ? AND id != ?");
        $stmt->execute([$email, $parent_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email is already used by another parent account']);
            exit;
        }
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Store old values for logging
    $old_values = [
        'full_name' => $parent['full_name'],
        'email' => $parent['email']
    ];
    
    // Update parent account
    $stmt = $pdo->prepare("
        UPDATE parent_accounts 
        SET full_name = ?, 
            email = ?, 
            phone = ?, 
            ic_number = ?, 
            status = ?, 
            updated_at = NOW() 
        WHERE id = ?
    ");
    
    $stmt->execute([
        $full_name,
        $email,
        $phone,
        $ic_number,
        $status,
        $parent_id
    ]);
    
    // Log admin action
    $stmt = $pdo->prepare("
        INSERT INTO admin_action_logs 
        (admin_id, action_type, target_type, target_id, details, created_at) 
        VALUES (?, 'update_parent', 'parent', ?, ?, NOW())
    ");
    
    $changes = [];
    if ($old_values['full_name'] !== $full_name) {
        $changes[] = "Name: '{$old_values['full_name']}' → '{$full_name}'";
    }
    if ($old_values['email'] !== $email) {
        $changes[] = "Email: '{$old_values['email']}' → '{$email}'";
    }
    
    $details = json_encode([
        'parent_id' => $parent['parent_id'],
        'changes' => $changes,
        'full_update' => [
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $phone,
            'ic_number' => $ic_number,
            'status' => $status
        ]
    ]);
    
    $stmt->execute([$_SESSION['admin_id'], $parent_id, $details]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Parent information updated successfully',
        'data' => [
            'parent_id' => $parent['parent_id'],
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $phone,
            'status' => $status,
            'changes_count' => count($changes)
        ]
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Update parent error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>