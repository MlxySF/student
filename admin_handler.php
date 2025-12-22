<?php
/**
 * admin_handler.php
 * Handles AJAX requests and form submissions for admin panel
 * Specifically for student management operations
 */

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

// Load PHPMailer using centralized loader
require_once __DIR__ . '/phpmailer_loader.php';

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function redirectIfNotAdmin() {
    if (!isAdminLoggedIn()) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

// ============================================
// GET STUDENT DETAILS (AJAX - Returns JSON)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_student_details') {
    redirectIfNotAdmin();
    
    header('Content-Type: application/json');
    
    $student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
    
    if (!$student_id) {
        echo json_encode(['error' => 'Invalid student ID']);
        exit;
    }
    
    try {
        // Get student's active enrollments
        $stmt = $pdo->prepare("
            SELECT e.*, c.class_name, c.class_code, c.monthly_fee, c.schedule
            FROM enrollments e
            JOIN classes c ON e.class_id = c.id
            WHERE e.student_id = ? AND e.status = 'active'
            ORDER BY c.class_name
        ");
        $stmt->execute([$student_id]);
        $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'enrollments' => $enrollments
        ]);
    } catch (PDOException $e) {
        error_log("[Admin Handler] Error fetching student details: " . $e->getMessage());
        echo json_encode(['error' => 'Database error']);
    }
    
    exit;
}

// ============================================
// ENROLL STUDENT
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enroll_student') {
    redirectIfNotAdmin();
    
    $student_id = intval($_POST['student_id']);
    $registration_id = intval($_POST['registration_id']);
    $class_id = intval($_POST['class_id']);
    
    if (!$student_id || !$class_id) {
        $_SESSION['error'] = 'Invalid student or class ID.';
        header('Location: admin.php?page=students');
        exit;
    }
    
    try {
        // Check if already enrolled
        $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND class_id = ? AND status = 'active'");
        $stmt->execute([$student_id, $class_id]);
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Student is already enrolled in this class!';
        } else {
            // Enroll student
            $stmt = $pdo->prepare("
                INSERT INTO enrollments (student_id, class_id, status, enrollment_date) 
                VALUES (?, ?, 'active', NOW())
            ");
            $stmt->execute([$student_id, $class_id]);
            
            $_SESSION['success'] = 'Student enrolled successfully!';
        }
    } catch (PDOException $e) {
        error_log("[Admin Handler] Error enrolling student: " . $e->getMessage());
        $_SESSION['error'] = 'Failed to enroll student. Database error.';
    }
    
    header('Location: admin.php?page=students');
    exit;
}

// ============================================
// UNENROLL STUDENT
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'unenroll_student') {
    redirectIfNotAdmin();
    
    $enrollment_id = intval($_POST['enrollment_id']);
    
    if (!$enrollment_id) {
        $_SESSION['error'] = 'Invalid enrollment ID.';
        header('Location: admin.php?page=students');
        exit;
    }
    
    try {
        // Set enrollment status to inactive instead of deleting
        $stmt = $pdo->prepare("UPDATE enrollments SET status = 'inactive', withdrawal_date = NOW() WHERE id = ?");
        $stmt->execute([$enrollment_id]);
        
        $_SESSION['success'] = 'Student removed from class successfully!';
    } catch (PDOException $e) {
        error_log("[Admin Handler] Error unenrolling student: " . $e->getMessage());
        $_SESSION['error'] = 'Failed to unenroll student. Database error.';
    }
    
    header('Location: admin.php?page=students');
    exit;
}

// ============================================
// EDIT STUDENT REGISTRATION
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_student_registration') {
    redirectIfNotAdmin();
    
    $registration_id = intval($_POST['registration_id']);
    $name_en = trim($_POST['name_en']);
    $name_cn = trim($_POST['name_cn']);
    $age = intval($_POST['age']);
    $school = trim($_POST['school']);
    $phone = trim($_POST['phone']);
    $ic = trim($_POST['ic']);
    $student_status = trim($_POST['student_status']);
    
    if (!$registration_id || !$name_en || !$age || !$school || !$phone) {
        $_SESSION['error'] = 'All required fields must be filled.';
        header('Location: admin.php?page=students');
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE registrations 
            SET name_en = ?, name_cn = ?, age = ?, school = ?, phone = ?, ic = ?, student_status = ?
            WHERE id = ?
        ");
        $stmt->execute([$name_en, $name_cn, $age, $school, $phone, $ic, $student_status, $registration_id]);
        
        // Also update student account if it exists
        $stmt = $pdo->prepare("SELECT student_account_id FROM registrations WHERE id = ?");
        $stmt->execute([$registration_id]);
        $reg = $stmt->fetch();
        
        if ($reg && $reg['student_account_id']) {
            $stmt = $pdo->prepare("
                UPDATE students 
                SET full_name = ?, phone = ?
                WHERE id = ?
            ");
            $stmt->execute([$name_en, $phone, $reg['student_account_id']]);
        }
        
        $_SESSION['success'] = 'Student information updated successfully!';
    } catch (PDOException $e) {
        error_log("[Admin Handler] Error updating student: " . $e->getMessage());
        $_SESSION['error'] = 'Failed to update student. Database error.';
    }
    
    header('Location: admin.php?page=students');
    exit;
}

// ============================================
// INVALID REQUEST
// ============================================
http_response_code(400);
echo json_encode(['error' => 'Invalid request']);
exit;
?>