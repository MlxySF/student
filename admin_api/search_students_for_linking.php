<?php
// admin_api/search_students_for_linking.php - Search for students to link to parent (Stage 4 Phase 2)
session_start();
require_once '../config.php';

header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$search = $_GET['search'] ?? '';
$parent_id = intval($_GET['parent_id'] ?? 0);

if (strlen($search) < 2) {
    echo json_encode(['success' => false, 'message' => 'Search term must be at least 2 characters.']);
    exit;
}

try {
    // Search for students that are NOT already linked to this parent
    // Show students that are either independent OR linked to other parents (for information)
    $stmt = $pdo->prepare("
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
            pa.parent_id as current_parent_id,
            pa.full_name as current_parent_name,
            CASE 
                WHEN s.parent_account_id IS NULL THEN 'independent'
                WHEN s.parent_account_id = ? THEN 'already_linked'
                ELSE 'linked_to_other'
            END as link_status,
            COUNT(DISTINCT e.id) as classes_count
        FROM students s
        LEFT JOIN parent_accounts pa ON s.parent_account_id = pa.id
        LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
        WHERE (
            s.student_id LIKE ? OR 
            s.full_name LIKE ? OR 
            s.email LIKE ?
        )
        GROUP BY s.id
        ORDER BY 
            CASE 
                WHEN s.parent_account_id IS NULL THEN 1
                WHEN s.parent_account_id = ? THEN 3
                ELSE 2
            END,
            s.full_name ASC
        LIMIT 20
    ");
    
    $searchTerm = "%{$search}%";
    $stmt->execute([
        $parent_id,
        $searchTerm, 
        $searchTerm, 
        $searchTerm,
        $parent_id
    ]);
    
    $students = $stmt->fetchAll();
    
    // Format the results
    $results = [];
    foreach ($students as $student) {
        $canLink = $student['link_status'] === 'independent';
        $statusBadge = '';
        $statusClass = '';
        
        switch ($student['link_status']) {
            case 'independent':
                $statusBadge = 'Independent';
                $statusClass = 'success';
                break;
            case 'already_linked':
                $statusBadge = 'Already Linked to This Parent';
                $statusClass = 'secondary';
                break;
            case 'linked_to_other':
                $statusBadge = "Linked to {$student['current_parent_name']} ({$student['current_parent_id']})";
                $statusClass = 'warning';
                break;
        }
        
        $results[] = [
            'id' => $student['id'],
            'student_id' => $student['student_id'],
            'full_name' => $student['full_name'],
            'email' => $student['email'],
            'phone' => $student['phone'],
            'age' => $student['age'],
            'school' => $student['school'],
            'student_status' => $student['student_status'],
            'classes_count' => $student['classes_count'],
            'link_status' => $student['link_status'],
            'status_badge' => $statusBadge,
            'status_class' => $statusClass,
            'can_link' => $canLink,
            'current_parent_id' => $student['current_parent_id'],
            'current_parent_name' => $student['current_parent_name']
        ];
    }
    
    echo json_encode([
        'success' => true, 
        'results' => $results,
        'count' => count($results)
    ]);
    
} catch (PDOException $e) {
    error_log("Search students error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
}
?>