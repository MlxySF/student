<?php
// admin_pages/api/search_students.php - Search students for linking to parent
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$exclude_linked = isset($_GET['exclude_linked']) ? (bool)$_GET['exclude_linked'] : true;
$limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 50) : 20;

if (empty($search)) {
    echo json_encode(['success' => true, 'students' => []]);
    exit;
}

try {
    // Build search query
    $sql = "
        SELECT 
            s.id,
            s.student_id,
            s.full_name,
            s.email,
            s.phone,
            s.age,
            s.school,
            s.student_status,
            s.parent_account_id,
            pa.parent_id as parent_code,
            pa.full_name as parent_name,
            CASE 
                WHEN s.parent_account_id IS NOT NULL THEN 'Linked'
                ELSE 'Independent'
            END as link_status
        FROM students s
        LEFT JOIN parent_accounts pa ON s.parent_account_id = pa.id
        WHERE (
            s.student_id LIKE ? 
            OR s.full_name LIKE ? 
            OR s.email LIKE ?
            OR s.phone LIKE ?
        )
    ";
    
    $params = [
        "%{$search}%",
        "%{$search}%",
        "%{$search}%",
        "%{$search}%"
    ];
    
    // Optionally exclude already linked students
    if ($exclude_linked) {
        $sql .= " AND s.parent_account_id IS NULL";
    }
    
    $sql .= " ORDER BY s.created_at DESC LIMIT ?";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key + 1, $value);
    }
    $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
    
    $stmt->execute();
    $students = $stmt->fetchAll();
    
    // For each student, get enrollment count
    foreach ($students as &$student) {
        $enrollStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND status = 'active'"
        );
        $enrollStmt->execute([$student['id']]);
        $student['enrollments_count'] = $enrollStmt->fetchColumn();
        
        // Get outstanding invoices
        $invoiceStmt = $pdo->prepare(
            "SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total 
             FROM invoices 
             WHERE student_id = ? AND status IN ('unpaid', 'overdue')"
        );
        $invoiceStmt->execute([$student['id']]);
        $invoiceData = $invoiceStmt->fetch();
        $student['unpaid_invoices'] = $invoiceData['count'];
        $student['outstanding_amount'] = $invoiceData['total'];
    }
    
    echo json_encode([
        'success' => true,
        'students' => $students,
        'count' => count($students)
    ]);
    
} catch (Exception $e) {
    error_log("Search students error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>