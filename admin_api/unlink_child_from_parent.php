<?php
// admin_api/unlink_child_from_parent.php - Unlink a child from parent account (Stage 4 Phase 2)
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
$keep_student_account = isset($input['keep_student_account']) ? (bool)$input['keep_student_account'] : true;

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
    
    // Check if relationship exists
    $stmt = $pdo->prepare("SELECT id FROM parent_child_relationships WHERE parent_id = ? AND student_id = ?");
    $stmt->execute([$parent_id, $student_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Student is not linked to this parent.']);
        exit;
    }
    
    // Check for outstanding invoices (warning only, not blocking)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total
        FROM invoices 
        WHERE student_id = ? AND status IN ('unpaid', 'overdue')
    ");
    $stmt->execute([$student_id]);
    $invoiceData = $stmt->fetch();
    $hasOutstanding = $invoiceData['count'] > 0;
    
    // Delete the parent-child relationship
    $stmt = $pdo->prepare("DELETE FROM parent_child_relationships WHERE parent_id = ? AND student_id = ?");
    $stmt->execute([$parent_id, $student_id]);
    
    // Update student's parent_account_id to NULL (make independent)
    if ($keep_student_account) {
        $stmt = $pdo->prepare("UPDATE students SET parent_account_id = NULL WHERE id = ?");
        $stmt->execute([$student_id]);
    }
    
    // Log admin action
    $stmt = $pdo->prepare("
        INSERT INTO admin_action_logs 
        (admin_id, action_type, target_type, target_id, details, created_at)
        VALUES (?, 'unlink_child', 'parent', ?, ?, NOW())
    ");
    $details = "Unlinked student {$student['full_name']} (ID: {$student['student_id']}) from parent {$parent['full_name']} (ID: {$parent['parent_id']})";
    if ($hasOutstanding) {
        $details .= " - Warning: Student had {$invoiceData['count']} outstanding invoice(s) totaling RM" . number_format($invoiceData['total'], 2);
    }
    $stmt->execute([$_SESSION['admin_id'], $parent_id, $details]);
    
    $message = "Successfully unlinked {$student['full_name']} from {$parent['full_name']}.";
    if ($hasOutstanding) {
        $message .= " Note: Student has {$invoiceData['count']} outstanding invoice(s) totaling RM" . number_format($invoiceData['total'], 2) . ".";
    }
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'warning' => $hasOutstanding ? "Student has outstanding invoices." : null
    ]);
    
} catch (PDOException $e) {
    error_log("Unlink child error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
}
?>