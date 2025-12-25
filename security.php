<?php
/**
 * Security Layer for Wushu Student Portal
 * Provides input validation, sanitization, and CSRF protection
 */

// ============================================================
// SESSION SECURITY
// ============================================================

/**
 * Initialize secure session with proper settings
 */
function initSecureSession() {
    // Prevent session fixation
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Lax'); // Changed from Strict to Lax to fix form submission
        
        // Use secure cookies if on HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', 1);
        }
        
        session_start();
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['last_regeneration'])) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
}

// ============================================================
// CSRF PROTECTION
// ============================================================

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token input field HTML
 */
function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verify CSRF token from POST request
 */
function verifyCSRF() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            http_response_code(403);
            die('CSRF validation failed. Please refresh the page and try again.');
        }
    }
}

// ============================================================
// INPUT SANITIZATION
// ============================================================

/**
 * Sanitize string input (removes HTML/scripts)
 */
function sanitizeString($input) {
    if ($input === null) return null;
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize email
 */
function sanitizeEmail($email) {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

/**
 * Sanitize integer
 */
function sanitizeInt($input) {
    return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
}

/**
 * Sanitize float/decimal
 */
function sanitizeFloat($input) {
    return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
}

/**
 * Sanitize URL
 */
function sanitizeURL($url) {
    return filter_var(trim($url), FILTER_SANITIZE_URL);
}

/**
 * Sanitize filename
 */
function sanitizeFilename($filename) {
    // Remove any path components
    $filename = basename($filename);
    // Remove any characters that aren't alphanumeric, dots, hyphens, or underscores
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    return $filename;
}

// ============================================================
// INPUT VALIDATION
// ============================================================

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Malaysian format)
 */
function isValidPhone($phone) {
    // Remove spaces and dashes
    $phone = preg_replace('/[\s-]/', '', $phone);
    // Check if it's a valid Malaysian phone number
    return preg_match('/^(\+?6?0)[0-9]{8,10}$/', $phone);
}

/**
 * Validate date format (YYYY-MM-DD)
 */
function isValidDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Validate file upload
 */
function isValidFileUpload($file, $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'], $maxSize = 5242880) {
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['valid' => false, 'error' => 'No file uploaded or invalid upload.'];
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        $maxSizeMB = $maxSize / 1048576;
        return ['valid' => false, 'error' => "File size exceeds {$maxSizeMB}MB limit."];
    }
    
    // Check file type using MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['valid' => false, 'error' => 'Invalid file type. Only JPG, PNG, and PDF allowed.'];
    }
    
    // Additional check for image files
    if (strpos($mimeType, 'image/') === 0) {
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return ['valid' => false, 'error' => 'Invalid image file.'];
        }
    }
    
    return ['valid' => true, 'mime' => $mimeType];
}

/**
 * Validate amount/currency
 */
function isValidAmount($amount) {
    // Check if it's a valid number with up to 2 decimal places
    return preg_match('/^\d+(\.\d{1,2})?$/', $amount) && floatval($amount) > 0;
}

// ============================================================
// SQL INJECTION PREVENTION
// ============================================================

/**
 * Prepare safe SQL query with named parameters
 * Always use PDO prepared statements instead of direct queries
 */
function safeQuery($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// ============================================================
// XSS PREVENTION
// ============================================================

/**
 * Escape output for HTML display
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Escape for HTML attribute
 */
function ea($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Escape for JavaScript string
 */
function ejs($string) {
    return json_encode($string ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

// ============================================================
// RATE LIMITING
// ============================================================

/**
 * Simple rate limiting (for login attempts, etc.)
 */
function isRateLimited($identifier, $maxAttempts = 5, $timeWindow = 300) {
    $key = 'rate_limit_' . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 1, 'first_attempt' => time()];
        return false;
    }
    
    $data = $_SESSION[$key];
    
    // Reset if time window has passed
    if (time() - $data['first_attempt'] > $timeWindow) {
        $_SESSION[$key] = ['count' => 1, 'first_attempt' => time()];
        return false;
    }
    
    // Increment counter
    $_SESSION[$key]['count']++;
    
    // Check if limit exceeded
    if ($data['count'] >= $maxAttempts) {
        return true;
    }
    
    return false;
}

/**
 * Clear rate limit
 */
function clearRateLimit($identifier) {
    $key = 'rate_limit_' . md5($identifier);
    unset($_SESSION[$key]);
}

// ============================================================
// SECURE PASSWORD HANDLING
// ============================================================

/**
 * Validate password strength
 */
function isStrongPassword($password, $minLength = 8) {
    if (strlen($password) < $minLength) {
        return ['valid' => false, 'error' => "Password must be at least {$minLength} characters long."];
    }
    
    // Optionally check for complexity (commented out for now)
    // if (!preg_match('/[A-Z]/', $password)) {
    //     return ['valid' => false, 'error' => 'Password must contain at least one uppercase letter.'];
    // }
    // if (!preg_match('/[a-z]/', $password)) {
    //     return ['valid' => false, 'error' => 'Password must contain at least one lowercase letter.'];
    // }
    // if (!preg_match('/[0-9]/', $password)) {
    //     return ['valid' => false, 'error' => 'Password must contain at least one number.'];
    // }
    
    return ['valid' => true];
}

/**
 * Hash password securely
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// ============================================================
// FILE UPLOAD SECURITY
// ============================================================

/**
 * Generate secure random filename
 */
function generateSecureFilename($originalFilename) {
    $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
    // Whitelist allowed extensions
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
    
    if (!in_array($extension, $allowedExtensions)) {
        return false;
    }
    
    return bin2hex(random_bytes(16)) . '.' . $extension;
}

// ============================================================
// INITIALIZE SECURITY ON LOAD
// ============================================================

// Auto-initialize secure session when this file is included
initSecureSession();
?>