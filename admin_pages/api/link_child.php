<?php
// admin_pages/api/link_child.php - Link a child to a parent account
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
$student_id = isset($data['student_id']) ? intval($data['student_id']) : 0;
$relationship = isset($data['relationship']) ? $data['relationship'] : 'guardian';
$is_primary = isset($data['is_primary']) ? (bool)$data['is_primary'] : false;
$replace_parent = isset($data['replace_parent']) ? (bool)$data['replace_parent'] : false;

if (!$parent_id || !$student_id) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    // Verify parent exists
    $stmt = $pdo->prepare("SELECT id, parent_id, full_name FROM parent_accounts WHERE id = ?");
    $stmt->execute([$parent_id]);
    $parent = $stmt->fetch();
    
    if (!$parent) {
        echo json_encode(['success' => false, 'message' => 'Parent account not found']);
        exit;
    }
    
    // Verify student exists
    $stmt = $pdo->prepare("SELECT id, student_id, full_name, parent_account_id FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }
    
    // Check if student already has a parent
    $stmt = $pdo->prepare("SELECT parent_id FROM parent_child_relationships WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $existing_link = $stmt->fetch();
    
    if ($existing_link && !$replace_parent) {
        echo json_encode([
            'success' => false, 
            'message' => 'Student is already linked to a parent. Enable "Replace Parent" to proceed.'
        ]);
        exit;
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // If replacing parent, remove old relationship
    if ($existing_link && $replace_parent) {
        $stmt = $pdo->prepare("DELETE FROM parent_child_relationships WHERE student_id = ?");
        $stmt->execute([$student_id]);
    }
    
    // If this is set as primary, unset other primary children for this parent
    if ($is_primary) {
        $stmt = $pdo->prepare("UPDATE parent_child_relationships SET is_primary = FALSE WHERE parent_id = ?");
        $stmt->execute([$parent_id]);
    }
    
    // Create new relationship
    $stmt = $pdo->prepare("
        INSERT INTO parent_child_relationships 
        (parent_id, student_id, relationship, is_primary, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$parent_id, $student_id, $relationship, $is_primary]);
    
    // Update student's parent_account_id
    $stmt = $pdo->prepare("UPDATE students SET parent_account_id = ? WHERE id = ?");
    $stmt->execute([$parent_id, $student_id]);
    
    // Log admin action
    $stmt = $pdo->prepare("
        INSERT INTO admin_action_logs 
        (admin_id, action_type, target_type, target_id, details, created_at) 
        VALUES (?, 'link_child', 'parent', ?, ?, NOW())
    ");
    $details = json_encode([
        'parent_id' => $parent['parent_id'],
        'parent_name' => $parent['full_name'],
        'student_id' => $student['student_id'],
        'student_name' => $student['full_name'],
        'relationship' => $relationship,
        'is_primary' => $is_primary,
        'replaced_parent' => $replace_parent
    ]);
    $stmt->execute([$_SESSION['admin_id'], $parent_id, $details]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Child successfully linked to parent account',
        'data' => [
            'parent_name' => $parent['full_name'],
            'student_name' => $student['full_name']
        ]
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Link child error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>