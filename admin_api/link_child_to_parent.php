<?php
// admin_api/link_child_to_parent.php - Link a child to a parent account (Stage 4 Phase 2)
session_start();
require_once '../config.php';

header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$parent_id = intval($input['parent_id'] ?? 0);
$student_id = intval($input['student_id'] ?? 0);
$relationship = $input['relationship'] ?? 'guardian';
$is_primary = isset($input['is_primary']) ? (bool)$input['is_primary'] : false;

// Validate inputs
if (!$parent_id || !$student_id) {
    echo json_encode(['success' => false, 'message' => 'Parent ID and Student ID are required.']);
    exit;
}

try {
    // Check if parent exists
    $stmt = $pdo->prepare("SELECT id, parent_id, full_name FROM parent_accounts WHERE id = ?");
    $stmt->execute([$parent_id]);
    $parent = $stmt->fetch();
    
    if (!$parent) {
        echo json_encode(['success' => false, 'message' => 'Parent account not found.']);
        exit;
    }
    
    // Check if student exists
    $stmt = $pdo->prepare("SELECT id, student_id, full_name FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found.']);
        exit;
    }
    
    // Check if student is already linked to this parent
    $stmt = $pdo->prepare("SELECT id FROM parent_child_relationships WHERE parent_id = ? AND student_id = ?");
    $stmt->execute([$parent_id, $student_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Student is already linked to this parent.']);
        exit;
    }
    
    // Check if student is already linked to another parent
    $stmt = $pdo->prepare("
        SELECT pa.parent_id, pa.full_name 
        FROM parent_child_relationships pcr
        JOIN parent_accounts pa ON pcr.parent_id = pa.id
        WHERE pcr.student_id = ?
    ");
    $stmt->execute([$student_id]);
    $existingParent = $stmt->fetch();
    
    if ($existingParent) {
        echo json_encode([
            'success' => false, 
            'message' => "Student is already linked to parent: {$existingParent['full_name']} ({$existingParent['parent_id']}). Please unlink first."
        ]);
        exit;
    }
    
    // If setting as primary, unset other primary children for this parent
    if ($is_primary) {
        $stmt = $pdo->prepare("UPDATE parent_child_relationships SET is_primary = 0 WHERE parent_id = ?");
        $stmt->execute([$parent_id]);
    }
    
    // Create the link
    $stmt = $pdo->prepare("
        INSERT INTO parent_child_relationships 
        (parent_id, student_id, relationship, is_primary, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$parent_id, $student_id, $relationship, $is_primary ? 1 : 0]);
    
    // Update student's parent_account_id
    $stmt = $pdo->prepare("UPDATE students SET parent_account_id = ? WHERE id = ?");
    $stmt->execute([$parent_id, $student_id]);
    
    // Log admin action
    $stmt = $pdo->prepare("
        INSERT INTO admin_action_logs 
        (admin_id, action_type, target_type, target_id, details, created_at)
        VALUES (?, 'link_child', 'parent', ?, ?, NOW())
    ");
    $details = "Linked student {$student['full_name']} (ID: {$student['student_id']}) to parent {$parent['full_name']} (ID: {$parent['parent_id']})";
    $stmt->execute([$_SESSION['admin_id'], $parent_id, $details]);
    
    echo json_encode([
        'success' => true, 
        'message' => "Successfully linked {$student['full_name']} to {$parent['full_name']}."
    ]);
    
} catch (PDOException $e) {
    error_log("Link child error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
}
?>