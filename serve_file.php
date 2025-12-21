<?php
/**
 * serve_file.php - Secure File Serving Script
 * Serves uploaded files with authentication and authorization checks
 * Supports: payment receipts, registration documents
 */

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to user
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/file_serve_debug.log');

require_once 'config.php';
require_once 'file_storage_helper.php';

// Log incoming request
error_log("[serve_file.php] === NEW REQUEST ===");
error_log("[serve_file.php] Type: " . ($_GET['type'] ?? 'NOT SET'));
error_log("[serve_file.php] File: " . ($_GET['file'] ?? 'NOT SET'));
error_log("[serve_file.php] Download: " . (isset($_GET['download']) ? 'YES' : 'NO'));
error_log("[serve_file.php] Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET'));
error_log("[serve_file.php] Session user_type: " . ($_SESSION['user_type'] ?? 'NOT SET'));

// Get request parameters
$type = $_GET['type'] ?? '';
$filename = $_GET['file'] ?? '';
$isDownload = isset($_GET['download']);

// Validate type
if (!in_array($type, ['payment_receipt', 'registration_doc'])) {
    error_log("[serve_file.php] ERROR: Invalid file type: $type");
    http_response_code(400);
    die('Invalid file type');
}

// Validate filename
if (empty($filename) || !isValidFilename($filename)) {
    error_log("[serve_file.php] ERROR: Invalid filename: $filename");
    http_response_code(400);
    die('Invalid filename');
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    error_log("[serve_file.php] ERROR: User not authenticated");
    http_response_code(401);
    die('Unauthorized - Please log in');
}

error_log("[serve_file.php] User authenticated: ID=" . $_SESSION['user_id'] . ", Type=" . $_SESSION['user_type']);

// Get file path based on type
if ($type === 'payment_receipt') {
    $directory = PAYMENT_RECEIPTS_DIR;
} else if ($type === 'registration_doc') {
    $directory = REGISTRATION_DOCS_DIR;
} else {
    error_log("[serve_file.php] ERROR: Unknown type: $type");
    http_response_code(400);
    die('Unknown file type');
}

$filepath = $directory . $filename;
error_log("[serve_file.php] Attempting to serve file: $filepath");
error_log("[serve_file.php] File exists: " . (file_exists($filepath) ? 'YES' : 'NO'));

if (!file_exists($filepath)) {
    error_log("[serve_file.php] ERROR: File not found: $filepath");
    http_response_code(404);
    die('File not found');
}

// Check file permissions
error_log("[serve_file.php] File readable: " . (is_readable($filepath) ? 'YES' : 'NO'));
error_log("[serve_file.php] File size: " . filesize($filepath) . " bytes");

// Authorization check: Verify user can access this file
// For now, we allow authenticated users to access files
// TODO: Add more granular permission checks if needed

// Get MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filepath);
finfo_close($finfo);

error_log("[serve_file.php] MIME type: $mimeType");

// Serve the file
if ($isDownload) {
    error_log("[serve_file.php] Serving as download");
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
} else {
    error_log("[serve_file.php] Serving inline");
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: inline; filename="' . basename($filename) . '"');
}

header('Content-Length: ' . filesize($filepath));
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');

error_log("[serve_file.php] Headers sent, outputting file...");

// Output file
readfile($filepath);

error_log("[serve_file.php] File served successfully");
exit;
?>