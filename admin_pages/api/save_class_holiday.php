<?php
// Set JSON header first
header('Content-Type: application/json');

// Initialize session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once '../../config.php';

// Debug: Log session info (remove this after fixing)
error_log('Session ID: ' . session_id());
error_log('User ID in session: ' . ($_SESSION['user_id'] ?? 'NOT SET'));
error_log('Role in session: ' . ($_SESSION['role'] ?? 'NOT SET'));

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    error_log('Authorization failed - User ID: ' . ($_SESSION['user_id'] ?? 'none') . ', Role: ' . ($_SESSION['role'] ?? 'none'));
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized access',
        'debug' => [
            'session_id' => session_id(),
            'has_user_id' => isset($_SESSION['user_id']),
            'has_role' => isset($_SESSION['role']),
            'role_value' => $_SESSION['role'] ?? 'not set'
        ]
    ]);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit();
}

$action = $input['action'] ?? '';
$holiday_date = $input['holiday_date'] ?? '';

// Validate date
if (!$holiday_date || !strtotime($holiday_date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date']);
    exit();
}

try {
    if ($action === 'add') {
        $reason = $input['reason'] ?? 'Holiday';
        
        // Check if holiday already exists
        $check_query = "SELECT id FROM class_holidays WHERE holiday_date = :holiday_date";
        $stmt = $pdo->prepare($check_query);
        $stmt->execute(['holiday_date' => $holiday_date]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Holiday already exists for this date']);
            exit();
        }
        
        // Insert new holiday
        $insert_query = "INSERT INTO class_holidays (holiday_date, reason, created_at) VALUES (:holiday_date, :reason, NOW())";
        $stmt = $pdo->prepare($insert_query);
        
        if ($stmt->execute(['holiday_date' => $holiday_date, 'reason' => $reason])) {
            echo json_encode(['success' => true, 'message' => 'Holiday added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add holiday']);
        }
        
    } elseif ($action === 'delete') {
        // Delete holiday
        $delete_query = "DELETE FROM class_holidays WHERE holiday_date = :holiday_date";
        $stmt = $pdo->prepare($delete_query);
        
        if ($stmt->execute(['holiday_date' => $holiday_date])) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Holiday removed successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Holiday not found']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove holiday']);
        }
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>