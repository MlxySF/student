<?php
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
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper Functions
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d M Y, g:i A', strtotime($datetime));
}

function getCurrentDateTime() {
    return date('Y-m-d H:i:s');
}

function formatCurrency($amount) {
    return 'RM ' . number_format($amount, 2);
}

function formatMonth($date) {
    return date('F Y', strtotime($date));
}

function base64ToDataURI($base64, $mime) {
    return "data:$mime;base64,$base64";
}

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
