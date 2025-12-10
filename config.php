<?php
// ===================================================================
// TIMEZONE CONFIGURATION - Set to GMT+8 (Malaysia/Singapore)
// ===================================================================
date_default_timezone_set('Asia/Kuala_Lumpur');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'mlxysf_student_portal');
define('DB_USER', 'mlxysf_student_portal');
define('DB_PASS', 'YAjv86kdSAPpw');

// Site Configuration
define('SITE_NAME', 'Wushu Student Portal');
define('SITE_URL', 'https://wushusportacademy.app.tc/student/');

// Connect to Database
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Try to set MySQL timezone (will fail silently if no permission)
    try {
        $pdo->exec("SET time_zone = '+08:00'");
    } catch(PDOException $e) {
        // Ignore error if no permission - PHP will handle timezone conversion
    }
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// ===================================================================
// HELPER FUNCTIONS WITH GMT+8 TIMEZONE HANDLING
// ===================================================================

/**
 * Format date only (e.g., "09 Dec 2025")
 * Handles timezone conversion from MySQL UTC to GMT+8
 */
function formatDate($date) {
    if (empty($date)) return '-';
    
    try {
        // Create DateTime object and ensure GMT+8
        $dt = new DateTime($date);
        $dt->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'));
        return $dt->format('d M Y');
    } catch (Exception $e) {
        return '-';
    }
}

/**
 * Format date and time (e.g., "09 Dec 2025, 10:45 AM")
 * Handles timezone conversion from MySQL UTC to GMT+8
 */
function formatDateTime($datetime) {
    if (empty($datetime)) return '-';
    
    try {
        // Create DateTime object and ensure GMT+8
        $dt = new DateTime($datetime);
        $dt->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'));
        return $dt->format('d M Y, g:i A');
    } catch (Exception $e) {
        return '-';
    }
}

/**
 * Format time only (e.g., "10:45 AM")
 * Handles timezone conversion from MySQL UTC to GMT+8
 */
function formatTime($datetime) {
    if (empty($datetime)) return '-';
    
    try {
        // Create DateTime object and ensure GMT+8
        $dt = new DateTime($datetime);
        $dt->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'));
        return $dt->format('g:i A');
    } catch (Exception $e) {
        return '-';
    }
}

/**
 * Get current date and time in GMT+8 for database insertion
 * Returns format: "2025-12-09 10:45:30"
 */
function getCurrentDateTime() {
    $dt = new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur'));
    return $dt->format('Y-m-d H:i:s');
}

/**
 * Get current date only in GMT+8
 * Returns format: "2025-12-09"
 */
function getCurrentDate() {
    $dt = new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur'));
    return $dt->format('Y-m-d');
}

/**
 * Convert any date/datetime to MySQL format in GMT+8
 */
function toMySQLDateTime($datetime) {
    if (empty($datetime)) return null;
    
    try {
        $dt = new DateTime($datetime);
        $dt->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'));
        return $dt->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Format currency with RM prefix
 */
function formatCurrency($amount) {
    return 'RM ' . number_format($amount, 2);
}

/**
 * Format month and year (e.g., "December 2025")
 * Accepts formats like: "2025-12", "2025-12-01", or full datetime
 */
function formatMonth($date) {
    if (empty($date) || $date === null) return '-';
    
    // Clean up the input - remove HTML tags if any
    $date = strip_tags($date);
    $date = trim($date);
    
    // Return dash if still empty after cleanup
    if (empty($date)) return '-';
    
    try {
        // If format is "YYYY-MM" (from month input), append "-01" for parsing
        if (preg_match('/^\d{4}-\d{2}$/', $date)) {
            $date .= '-01';
        }
        
        $dt = new DateTime($date);
        $dt->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'));
        return $dt->format('F Y');
    } catch (Exception $e) {
        // If parsing fails, return the original value or a dash
        return '-';
    }
}

/**
 * Convert base64 data to data URI for embedding in HTML
 */
function base64ToDataURI($base64, $mime) {
    return "data:$mime;base64,$base64";
}

/**
 * Format file size in human readable format
 */
function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>
