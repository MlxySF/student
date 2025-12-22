<?php
// admin_handler.php - Backend handler for admin AJAX requests and form submissions
session_start();
require_once 'config.php';

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

if (!isAdminLoggedIn()) {
    if (isset($_GET['action']) && $_GET['action'] === 'get_student_details') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    header('Location: admin.php?page=login');
    exit;
}

// ===========================================================
// AJAX GET HANDLERS (JSON responses)
// ===========================================================

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    if ($_GET['action'] === 'get_student_details') {
        $student_id = $_GET['student_id'] ?? null;
        
        if (!$student_id) {
            echo json_encode(['error' => 'Student ID required']);
            exit;
        }
        
        try {
            // Get student enrollments
            $stmt = $pdo->prepare("
                SELECT e.*, c.class_name, c.class_code, c.monthly_fee
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
            echo json_encode([
                'error' => 'Database error: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    // Unknown GET action
    echo json_encode(['error' => 'Unknown action']);
    exit;
}

// ===========================================================
// POST FORM HANDLERS (redirect responses)
// ===========================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // ENROLL STUDENT IN CLASS
    if ($_POST['action'] === 'enroll_student') {
        $student_id = $_POST['student_id'];
        $class_id = $_POST['class_id'];
        $registration_id = $_POST['registration_id'] ?? null;
        
        try {
            // Check if already enrolled
            $check_stmt = $pdo->prepare("
                SELECT id FROM enrollments 
                WHERE student_id = ? AND class_id = ? AND status = 'active'
            ");
            $check_stmt->execute([$student_id, $class_id]);
            
            if ($check_stmt->rowCount() > 0) {
                $_SESSION['error'] = 'Student is already enrolled in this class!';
            } else {
                // Enroll student
                $enroll_stmt = $pdo->prepare("
                    INSERT INTO enrollments (student_id, class_id, status, enrolled_at)
                    VALUES (?, ?, 'active', NOW())
                ");
                
                if ($enroll_stmt->execute([$student_id, $class_id])) {
                    $_SESSION['success'] = 'Student enrolled successfully!';
                } else {
                    $_SESSION['error'] = 'Failed to enroll student.';
                }
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Database error: ' . $e->getMessage();
        }
        
        header('Location: admin.php?page=students');
        exit;
    }
    
    // UNENROLL STUDENT FROM CLASS
    if ($_POST['action'] === 'unenroll_student') {
        $enrollment_id = $_POST['enrollment_id'];
        
        try {
            // Set enrollment status to inactive instead of deleting
            $stmt = $pdo->prepare("
                UPDATE enrollments 
                SET status = 'inactive', unenrolled_at = NOW()
                WHERE id = ?
            ");
            
            if ($stmt->execute([$enrollment_id])) {
                $_SESSION['success'] = 'Student removed from class successfully!';
            } else {
                $_SESSION['error'] = 'Failed to remove student from class.';
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Database error: ' . $e->getMessage();
        }
        
        header('Location: admin.php?page=students');
        exit;
    }
    
    // EDIT STUDENT REGISTRATION
    if ($_POST['action'] === 'edit_student_registration') {
        $registration_id = $_POST['registration_id'];
        $name_en = $_POST['name_en'];
        $name_cn = $_POST['name_cn'] ?? '';
        $age = $_POST['age'];
        $school = $_POST['school'];
        $phone = $_POST['phone'];
        $ic = $_POST['ic'] ?? '';
        $student_status = $_POST['student_status'];
        
        try {
            $stmt = $pdo->prepare("
                UPDATE registrations 
                SET name_en = ?, name_cn = ?, age = ?, school = ?, phone = ?, ic = ?, student_status = ?
                WHERE id = ?
            ");
            
            if ($stmt->execute([$name_en, $name_cn, $age, $school, $phone, $ic, $student_status, $registration_id])) {
                // Also update students table if student_account_id exists
                $get_account_stmt = $pdo->prepare("SELECT student_account_id FROM registrations WHERE id = ?");
                $get_account_stmt->execute([$registration_id]);
                $account = $get_account_stmt->fetch();
                
                if ($account && $account['student_account_id']) {
                    $update_student_stmt = $pdo->prepare("
                        UPDATE students 
                        SET full_name = ?, phone = ?
                        WHERE id = ?
                    ");
                    $update_student_stmt->execute([$name_en, $phone, $account['student_account_id']]);
                }
                
                $_SESSION['success'] = 'Student information updated successfully!';
            } else {
                $_SESSION['error'] = 'Failed to update student information.';
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Database error: ' . $e->getMessage();
        }
        
        header('Location: admin.php?page=students');
        exit;
    }
    
    // Unknown POST action
    $_SESSION['error'] = 'Unknown action';
    header('Location: admin.php?page=dashboard');
    exit;
}

// No action specified
header('Location: admin.php?page=dashboard');
exit;
?>