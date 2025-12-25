<?php
// Initialize session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once '../../config.php';
require_once '../../security.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
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
        $check_query = "SELECT id FROM class_holidays WHERE holiday_date = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $holiday_date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Holiday already exists for this date']);
            exit();
        }
        $stmt->close();
        
        // Insert new holiday
        $insert_query = "INSERT INTO class_holidays (holiday_date, reason, created_at) VALUES (?, ?, NOW())";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ss", $holiday_date, $reason);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Holiday added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add holiday']);
        }
        $stmt->close();
        
    } elseif ($action === 'delete') {
        // Delete holiday
        $delete_query = "DELETE FROM class_holidays WHERE holiday_date = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("s", $holiday_date);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Holiday removed successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Holiday not found']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove holiday']);
        }
        $stmt->close();
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>