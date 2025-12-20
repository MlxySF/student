<?php
/**
 * admin_bulk_delete.php - Handle bulk delete operations for admin tables
 * Supports: registrations, students, classes, invoices, attendance
 */

session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Get request data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['table']) || !isset($data['ids']) || !is_array($data['ids'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request data']);
    exit;
}

$table = $data['table'];
$ids = array_map('intval', $data['ids']); // Sanitize IDs

if (empty($ids)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No items selected']);
    exit;
}

// Allowed tables for bulk delete
$allowedTables = [
    'registrations' => [
        'table' => 'registrations',
        'id_column' => 'id',
        'related_deletes' => [
            // Delete associated student accounts
            ['table' => 'students', 'foreign_key' => 'id', 'source_key' => 'student_account_id']
        ]
    ],
    'students' => [
        'table' => 'students',
        'id_column' => 'id',
        'related_deletes' => [
            // Delete enrollments, attendance, payments
            ['table' => 'enrollments', 'foreign_key' => 'student_id', 'source_key' => 'id'],
            ['table' => 'attendance', 'foreign_key' => 'student_id', 'source_key' => 'id'],
            ['table' => 'payments', 'foreign_key' => 'student_id', 'source_key' => 'id'],
            ['table' => 'invoices', 'foreign_key' => 'student_id', 'source_key' => 'id']
        ]
    ],
    'classes' => [
        'table' => 'classes',
        'id_column' => 'id',
        'related_deletes' => [
            // Delete enrollments, attendance for this class
            ['table' => 'enrollments', 'foreign_key' => 'class_id', 'source_key' => 'id'],
            ['table' => 'attendance', 'foreign_key' => 'class_id', 'source_key' => 'id'],
            ['table' => 'invoices', 'foreign_key' => 'class_id', 'source_key' => 'id']
        ]
    ],
    'invoices' => [
        'table' => 'invoices',
        'id_column' => 'id',
        'related_deletes' => [
            // Delete associated payments
            ['table' => 'payments', 'foreign_key' => 'invoice_id', 'source_key' => 'id']
        ]
    ],
    'attendance' => [
        'table' => 'attendance',
        'id_column' => 'id',
        'related_deletes' => []
    ]
];

if (!isset($allowedTables[$table])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid table specified']);
    exit;
}

$config = $allowedTables[$table];

try {
    $pdo->beginTransaction();
    
    $deletedCount = 0;
    $errorCount = 0;
    $errors = [];
    
    foreach ($ids as $id) {
        try {
            // First, get the record to check what needs to be deleted
            $stmt = $pdo->prepare("SELECT * FROM {$config['table']} WHERE {$config['id_column']} = ?");
            $stmt->execute([$id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$record) {
                $errors[] = "Record ID {$id} not found";
                $errorCount++;
                continue;
            }
            
            // Delete related records first (foreign key constraints)
            foreach ($config['related_deletes'] as $related) {
                $sourceValue = $record[$related['source_key']];
                if ($sourceValue) {
                    $stmt = $pdo->prepare("DELETE FROM {$related['table']} WHERE {$related['foreign_key']} = ?");
                    $stmt->execute([$sourceValue]);
                    error_log("[Bulk Delete] Deleted related records from {$related['table']} where {$related['foreign_key']}={$sourceValue}");
                }
            }
            
            // Delete the main record
            $stmt = $pdo->prepare("DELETE FROM {$config['table']} WHERE {$config['id_column']} = ?");
            $stmt->execute([$id]);
            
            $deletedCount++;
            error_log("[Bulk Delete] Deleted record ID {$id} from {$config['table']}");
            
        } catch (PDOException $e) {
            $errors[] = "Failed to delete ID {$id}: " . $e->getMessage();
            $errorCount++;
            error_log("[Bulk Delete] Error deleting ID {$id}: " . $e->getMessage());
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'deleted_count' => $deletedCount,
        'error_count' => $errorCount,
        'total_requested' => count($ids),
        'errors' => $errors,
        'message' => "Successfully deleted {$deletedCount} of " . count($ids) . " items"
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('[Bulk Delete] Transaction error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
