<?php
/**
 * Secure File Serving Script
 * Provides authenticated access to uploaded files
 * Prevents direct file access through .htaccess protection
 */

session_start();

require_once 'config.php';
require_once 'auth_helper.php';
require_once 'file_storage_helper.php';

// Authentication check
if (!isLoggedIn()) {
    http_response_code(401);
    die('Unauthorized: Please login to view files');
}

// Get file parameters
$type = $_GET['type'] ?? ''; // payment_receipt, registration_doc
$filename = $_GET['file'] ?? '';
$download = isset($_GET['download']); // Force download instead of inline display

// Validate parameters
if (empty($type) || empty($filename)) {
    http_response_code(400);
    die('Invalid parameters');
}

// Sanitize filename to prevent directory traversal
$filename = basename($filename);
$filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $filename);

if (empty($filename)) {
    http_response_code(400);
    die('Invalid filename');
}

// Determine file directory based on type
switch ($type) {
    case 'payment_receipt':
        $directory = PAYMENT_RECEIPTS_DIR;
        break;
    case 'registration_doc':
        $directory = REGISTRATION_DOCS_DIR;
        break;
    default:
        http_response_code(400);
        die('Invalid file type');
}

$filepath = $directory . $filename;

// Check if file exists
if (!file_exists($filepath)) {
    http_response_code(404);
    die('File not found');
}

// Authorization check - verify user has access to this file
$hasAccess = false;

try {
    if ($type === 'payment_receipt') {
        // Check if this file belongs to a payment record accessible by the user
        $stmt = $pdo->prepare("
            SELECT p.id 
            FROM payments p
            WHERE p.receipt_filename = ?
            AND (
                p.student_id = ? 
                OR p.parent_account_id = ?
            )
            LIMIT 1
        ");
        
        $activeStudentId = getActiveStudentId();
        $parentId = isParent() ? getUserId() : null;
        
        $stmt->execute([$filename, $activeStudentId, $parentId]);
        $hasAccess = $stmt->fetch() !== false;
        
    } else if ($type === 'registration_doc') {
        // Check if this file belongs to a registration accessible by the user
        $stmt = $pdo->prepare("
            SELECT r.id 
            FROM registrations r
            WHERE (r.receipt_filename = ? OR r.pdf_filename = ? OR r.signature_filename = ?)
            AND r.student_account_id = ?
            LIMIT 1
        ");
        
        $activeStudentId = getActiveStudentId();
        $stmt->execute([$filename, $filename, $filename, $activeStudentId]);
        $hasAccess = $stmt->fetch() !== false;
    }
    
} catch (PDOException $e) {
    error_log("[Serve File] Database error: " . $e->getMessage());
    http_response_code(500);
    die('Server error');
}

// Deny access if user doesn't own this file
if (!$hasAccess) {
    http_response_code(403);
    error_log("[Serve File] Access denied: User ID=" . getUserId() . " attempted to access {$filename}");
    die('Access denied');
}

// Log file access
error_log("[Serve File] User ID=" . getUserId() . " accessed {$type}/{$filename}");

// Serve the file
serveFile($filepath, !$download);