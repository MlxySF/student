<?php
/**
 * auth_helper.php - Unified Authentication Helper
 * Supports both parent and student account logins
 * Handles child selection for parent accounts
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// SESSION MANAGEMENT FUNCTIONS
// ============================================================

/**
 * Check if user is logged in (parent or student)
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

/**
 * Check if logged in as parent
 */
function isParent() {
    return isLoggedIn() && $_SESSION['user_type'] === 'parent';
}

/**
 * Check if logged in as student
 */
function isStudent() {
    return isLoggedIn() && $_SESSION['user_type'] === 'student';
}

/**
 * Get current user ID (parent or student)
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user type ('parent' or 'student')
 */
function getUserType() {
    return $_SESSION['user_type'] ?? null;
}

/**
 * Get currently selected student ID (for parents viewing child's data)
 * For students, returns their own ID
 */
function getActiveStudentId() {
    if (isStudent()) {
        return $_SESSION['user_id'];
    } else if (isParent()) {
        return $_SESSION['active_student_id'] ?? null;
    }
    return null;
}

/**
 * Set active student for parent (when switching between children)
 */
function setActiveStudent($studentId) {
    if (isParent()) {
        $_SESSION['active_student_id'] = $studentId;
        return true;
    }
    return false;
}

/**
 * Get user email
 */
function getUserEmail() {
    return $_SESSION['user_email'] ?? null;
}

/**
 * Get user full name
 */
function getUserFullName() {
    return $_SESSION['user_full_name'] ?? 'User';
}

/**
 * Get parent's children list (only if logged in as parent)
 */
function getParentChildren() {
    return $_SESSION['children'] ?? [];
}

/**
 * Get active student full name
 */
function getActiveStudentName() {
    if (isStudent()) {
        return getUserFullName();
    } else if (isParent()) {
        $children = getParentChildren();
        $activeId = getActiveStudentId();
        foreach ($children as $child) {
            if ($child['id'] == $activeId) {
                return $child['full_name'];
            }
        }
    }
    return null;
}

// ============================================================
// AUTHENTICATION FUNCTIONS
// ============================================================

/**
 * Authenticate user (student or parent)
 * Returns array with success status and user data
 */
function authenticateUser($email, $password, $pdo) {
    $email = trim($email);
    
    // Try to authenticate as student first
    $stmt = $pdo->prepare("
        SELECT id, student_id, full_name, email, password, status, student_type, parent_account_id
        FROM students 
        WHERE email = ? AND status = 'active'
    ");
    $stmt->execute([$email]);
    $student = $stmt->fetch();
    
    if ($student && password_verify($password, $student['password'])) {
        // Student login successful
        return [
            'success' => true,
            'user_type' => 'student',
            'user_data' => $student
        ];
    }
    
    // Try to authenticate as parent
    $stmt = $pdo->prepare("
        SELECT id, parent_id, full_name, email, password, status
        FROM parent_accounts 
        WHERE email = ? AND status = 'active'
    ");
    $stmt->execute([$email]);
    $parent = $stmt->fetch();
    
    if ($parent && password_verify($password, $parent['password'])) {
        // Parent login successful - get their children
        $stmt = $pdo->prepare("
            SELECT 
                s.id, s.student_id, s.full_name, s.email, s.student_status,
                pcr.relationship, pcr.is_primary
            FROM students s
            JOIN parent_child_relationships pcr ON s.id = pcr.student_id
            WHERE pcr.parent_id = ? AND s.status = 'active'
            ORDER BY pcr.is_primary DESC, s.full_name ASC
        ");
        $stmt->execute([$parent['id']]);
        $children = $stmt->fetchAll();
        
        return [
            'success' => true,
            'user_type' => 'parent',
            'user_data' => $parent,
            'children' => $children
        ];
    }
    
    // Authentication failed
    return [
        'success' => false,
        'error' => 'Invalid email or password'
    ];
}

/**
 * Create login session
 */
function createLoginSession($authResult) {
    if (!$authResult['success']) {
        return false;
    }
    
    $userData = $authResult['user_data'];
    $userType = $authResult['user_type'];
    
    // Store common session data
    $_SESSION['user_id'] = $userData['id'];
    $_SESSION['user_type'] = $userType;
    $_SESSION['user_email'] = $userData['email'];
    $_SESSION['user_full_name'] = $userData['full_name'];
    $_SESSION['login_time'] = time();
    
    if ($userType === 'student') {
        $_SESSION['student_id'] = $userData['student_id'];
        $_SESSION['student_type'] = $userData['student_type'] ?? 'independent';
        $_SESSION['parent_account_id'] = $userData['parent_account_id'];
    } else if ($userType === 'parent') {
        $_SESSION['parent_id'] = $userData['parent_id'];
        $_SESSION['children'] = $authResult['children'];
        
        // Set first child as active by default
        if (!empty($authResult['children'])) {
            $_SESSION['active_student_id'] = $authResult['children'][0]['id'];
        }
    }
    
    return true;
}

/**
 * Logout user
 */
function logoutUser() {
    session_unset();
    session_destroy();
    header('Location: index.php?page=login');
    exit;
}

/**
 * Require login - redirect to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php?page=login');
        exit;
    }
}

// ============================================================
// DATA ACCESS FUNCTIONS (Context-aware)
// ============================================================

/**
 * Get student data for active context
 * If parent: returns selected child's data
 * If student: returns own data
 */
function getActiveStudentData($pdo) {
    $studentId = getActiveStudentId();
    if (!$studentId) {
        return null;
    }
    
    $stmt = $pdo->prepare("
        SELECT * FROM students WHERE id = ?
    ");
    $stmt->execute([$studentId]);
    return $stmt->fetch();
}

/**
 * Get student ID string (e.g., WSA2025-0001) for active context
 */
function getStudentId() {
    if (isStudent()) {
        return $_SESSION['student_id'] ?? null;
    } else if (isParent()) {
        $children = getParentChildren();
        $activeId = getActiveStudentId();
        foreach ($children as $child) {
            if ($child['id'] == $activeId) {
                return $child['student_id'];
            }
        }
    }
    return null;
}

/**
 * Check if parent can access specific student
 */
function canAccessStudent($studentId, $pdo) {
    // Students can only access themselves
    if (isStudent()) {
        return getUserId() == $studentId;
    }
    
    // Parents can access their children
    if (isParent()) {
        $children = getParentChildren();
        foreach ($children as $child) {
            if ($child['id'] == $studentId) {
                return true;
            }
        }
    }
    
    return false;
}

// ============================================================
// HELPER FUNCTION: Switch Child (for parents)
// ============================================================

/**
 * Handle child switching for parent accounts
 */
function handleChildSwitch() {
    if (isParent() && isset($_GET['switch_child'])) {
        $studentId = intval($_GET['switch_child']);
        
        // Verify this child belongs to the parent
        $children = getParentChildren();
        foreach ($children as $child) {
            if ($child['id'] === $studentId) {
                setActiveStudent($studentId);
                
                // Redirect to remove the switch_child parameter
                $page = $_GET['page'] ?? 'dashboard';
                header("Location: index.php?page=$page");
                exit;
            }
        }
    }
}

// ============================================================
// AUTO-CALL: Handle child switching on every page load
// ============================================================
if (isLoggedIn()) {
    handleChildSwitch();
}
?>
