<?php
// admin_pages/api/unlink_child.php - Unlink a child from parent account
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
$keep_student = isset($data['keep_student']) ? (bool)$data['keep_student'] : true;

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
    
    // Verify student exists and is linked to this parent
    $stmt = $pdo->prepare("
        SELECT s.id, s.student_id, s.full_name, pcr.id as relationship_id 
        FROM students s
        INNER JOIN parent_child_relationships pcr ON s.id = pcr.student_id
        WHERE s.id = ? AND pcr.parent_id = ?
    ");
    $stmt->execute([$student_id, $parent_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not linked to this parent']);
        exit;
    }
    
    // Check for outstanding invoices
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as unpaid_count, COALESCE(SUM(amount), 0) as total_outstanding 
        FROM invoices 
        WHERE student_id = ? AND status IN ('unpaid', 'overdue')
    ");
    $stmt->execute([$student_id]);
    $invoice_check = $stmt->fetch();
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Remove relationship
    $stmt = $pdo->prepare("DELETE FROM parent_child_relationships WHERE id = ?");
    $stmt->execute([$student['relationship_id']]);
    
    // Update student's parent_account_id to NULL
    $stmt = $pdo->prepare("UPDATE students SET parent_account_id = NULL WHERE id = ?");
    $stmt->execute([$student_id]);
    
    // Log admin action
    $stmt = $pdo->prepare("
        INSERT INTO admin_action_logs 
        (admin_id, action_type, target_type, target_id, details, created_at) 
        VALUES (?, 'unlink_child', 'parent', ?, ?, NOW())
    ");
    $details = json_encode([
        'parent_id' => $parent['parent_id'],
        'parent_name' => $parent['full_name'],
        'student_id' => $student['student_id'],
        'student_name' => $student['full_name'],
        'had_outstanding' => $invoice_check['unpaid_count'] > 0,
        'outstanding_amount' => $invoice_check['total_outstanding']
    ]);
    $stmt->execute([$_SESSION['admin_id'], $parent_id, $details]);
    
    // Commit transaction
    $pdo->commit();
    
    $message = 'Child successfully unlinked from parent account.';
    if ($invoice_check['unpaid_count'] > 0) {
        $message .= ' Note: Student has ' . $invoice_check['unpaid_count'] . 
                    ' unpaid invoice(s) totaling RM ' . number_format($invoice_check['total_outstanding'], 2);
    }
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'data' => [
            'parent_name' => $parent['full_name'],
            'student_name' => $student['full_name'],
            'had_outstanding' => $invoice_check['unpaid_count'] > 0,
            'outstanding_amount' => $invoice_check['total_outstanding']
        ]
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Unlink child error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>