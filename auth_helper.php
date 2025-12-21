<?php
/**
 * auth_helper.php - Parent-Only Authentication Helper
 * Only parents can login - children are managed under parent account
 * No separate student accounts
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// SESSION MANAGEMENT FUNCTIONS
// ============================================================

/**
 * Check if user is logged in (parent only now)
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'parent';
}

/**
 * Check if logged in as parent
 */
function isParent() {
    return isLoggedIn();
}

/**
 * Check if logged in as student (DEPRECATED - always returns false)
 */
function isStudent() {
    return false; // No more student logins
}

/**
 * Get current user ID (parent account ID)
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user type (always 'parent')
 */
function getUserType() {
    return 'parent';
}

/**
 * Get currently selected child registration ID
 */
function getActiveStudentId() {
    return $_SESSION['active_student_id'] ?? null;
}

/**
 * Set active child for parent (when switching between children)
 */
function setActiveStudent($registrationId) {
    if (isParent()) {
        $_SESSION['active_student_id'] = $registrationId;
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
    return $_SESSION['user_full_name'] ?? 'Parent';
}

/**
 * Get parent's children list
 */
function getParentChildren() {
    return $_SESSION['children'] ?? [];
}

/**
 * Get active child's full name
 */
function getActiveStudentName() {
    $children = getParentChildren();
    $activeId = getActiveStudentId();
    foreach ($children as $child) {
        if ($child['id'] == $activeId) {
            return $child['full_name'];
        }
    }
    return null;
}

// ============================================================
// AUTHENTICATION FUNCTIONS
// ============================================================

/**
 * Authenticate parent user
 * Returns array with success status and user data
 * UPDATED: Only allow login if parent has at least one APPROVED child
 */
function authenticateUser($email, $password, $pdo) {
    $email = trim($email);
    
    // Only authenticate as parent (no student login)
    $stmt = $pdo->prepare("
        SELECT id, parent_id, full_name, email, password, status
        FROM parent_accounts 
        WHERE email = ? AND status = 'active'
    ");
    $stmt->execute([$email]);
    $parent = $stmt->fetch();
    
    if ($parent && password_verify($password, $parent['password'])) {
        // Parent credentials are correct
        // Check if they have any APPROVED children
        $stmt = $pdo->prepare("
            SELECT 
                r.id,
                r.registration_number,
                r.name_en as full_name,
                r.name_cn,
                r.age,
                r.school,
                r.student_status,
                r.payment_status,
                r.events,
                r.schedule
            FROM registrations r
            WHERE r.parent_account_id = ? AND r.payment_status = 'approved'
            ORDER BY r.created_at ASC
        ");
        $stmt->execute([$parent['id']]);
        $approvedChildren = $stmt->fetchAll();
        
        // Check if there are any approved children
        if (empty($approvedChildren)) {
            // No approved children - check if all registrations were rejected
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as rejected_count
                FROM registrations 
                WHERE parent_account_id = ? AND payment_status = 'rejected'
            ");
            $stmt->execute([$parent['id']]);
            $result = $stmt->fetch();
            $rejectedCount = (int)$result['rejected_count'];
            
            // Check if there are pending registrations
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as pending_count
                FROM registrations 
                WHERE parent_account_id = ? AND payment_status = 'pending'
            ");
            $stmt->execute([$parent['id']]);
            $result = $stmt->fetch();
            $pendingCount = (int)$result['pending_count'];
            
            if ($rejectedCount > 0 && $pendingCount == 0) {
                // All registrations rejected, none pending
                return [
                    'success' => false,
                    'error' => 'rejected',
                    'message' => 'Your registration has been rejected. Please contact the academy or submit a new registration.'
                ];
            } else if ($pendingCount > 0) {
                // Has pending registrations waiting for approval
                return [
                    'success' => false,
                    'error' => 'pending',
                    'message' => 'Your registration is pending approval. Please wait for the academy to review your payment and approve your registration.'
                ];
            } else {
                // No registrations at all (shouldn't happen, but handle it)
                return [
                    'success' => false,
                    'error' => 'no_registration',
                    'message' => 'No registration found. Please submit a new registration.'
                ];
            }
        }
        
        // Parent has approved children - login successful
        return [
            'success' => true,
            'user_type' => 'parent',
            'user_data' => $parent,
            'children' => $approvedChildren
        ];
    }
    
    // Authentication failed - invalid credentials
    return [
        'success' => false,
        'error' => 'invalid_credentials',
        'message' => 'Invalid email or password'
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
    $userType = 'parent';
    
    // Store session data
    $_SESSION['user_id'] = $userData['id'];
    $_SESSION['user_type'] = $userType;
    $_SESSION['user_email'] = $userData['email'];
    $_SESSION['user_full_name'] = $userData['full_name'];
    $_SESSION['parent_id'] = $userData['parent_id'];
    $_SESSION['login_time'] = time();
    $_SESSION['children'] = $authResult['children'];
    
    // Set first child as active by default
    if (!empty($authResult['children'])) {
        $_SESSION['active_student_id'] = $authResult['children'][0]['id'];
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
 * Get active child data from registrations table
 */
function getActiveStudentData($pdo) {
    $registrationId = getActiveStudentId();
    if (!$registrationId) {
        return null;
    }
    
    $stmt = $pdo->prepare("
        SELECT * FROM registrations WHERE id = ?
    ");
    $stmt->execute([$registrationId]);
    return $stmt->fetch();
}

/**
 * Get student ID string (e.g., WSA2025-0001) for active child
 */
if (!function_exists('getStudentId')) {
    function getStudentId() {
        $children = getParentChildren();
        $activeId = getActiveStudentId();
        foreach ($children as $child) {
            if ($child['id'] == $activeId) {
                return $child['registration_number'];
            }
        }
        return null;
    }
}

/**
 * Check if parent can access specific child registration
 */
function canAccessStudent($registrationId, $pdo) {
    if (!isParent()) {
        return false;
    }
    
    $children = getParentChildren();
    foreach ($children as $child) {
        if ($child['id'] == $registrationId) {
            return true;
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
        $registrationId = intval($_GET['switch_child']);
        
        // Verify this child belongs to the parent
        $children = getParentChildren();
        foreach ($children as $child) {
            if ($child['id'] === $registrationId) {
                setActiveStudent($registrationId);
                
                // Redirect to remove the switch_child parameter
                $page = $_GET['page'] ?? 'dashboard';
                header("Location: index.php?page=$page");
                exit;
            }
        }
    }
}

// ============================================================
// BACKWARD COMPATIBILITY HELPERS
// ============================================================

/**
 * Get active student ID - for backward compatibility
 * Now returns registration ID instead of student account ID
 */
function getActiveStudentId_Legacy() {
    return getActiveStudentId();
}

// ============================================================
// AUTO-CALL: Handle child switching on every page load
// ============================================================
if (isLoggedIn()) {
    handleChildSwitch();
}
?>