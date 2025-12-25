<?php
/**
 * serve_file.php - Securely serve uploaded files (receipts, signatures, PDFs)
 * This file handles serving user-uploaded content stored in the uploads directory
 * 
 * Usage: serve_file.php?path=uploads/receipts/filename.jpg
 */

session_start();

// Security: Only allow admin users to access files
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    die('Access denied');
}

// Get the file path from query parameter
$requestedPath = isset($_GET['path']) ? $_GET['path'] : '';

if (empty($requestedPath)) {
    http_response_code(400);
    die('No file path provided');
}

// Security: Prevent directory traversal attacks
if (strpos($requestedPath, '..') !== false) {
    http_response_code(403);
    die('Invalid path');
}

// Build the full file path (go up one directory from admin_pages to root)
$rootDir = dirname(__DIR__); // Go up to root directory
$fullPath = $rootDir . '/' . $requestedPath;

// Normalize the path to prevent traversal
$realPath = realpath($fullPath);
$allowedBasePath = realpath($rootDir . '/uploads');

// Security check: Ensure the file is within the uploads directory
if ($realPath === false || strpos($realPath, $allowedBasePath) !== 0) {
    http_response_code(403);
    error_log("File access denied: Requested=$requestedPath, Real=$realPath, Allowed=$allowedBasePath");
    die('Access denied to this file location');
}

// Check if file exists
if (!file_exists($realPath) || !is_file($realPath)) {
    http_response_code(404);
    error_log("File not found: $realPath");
    die('File not found');
}

// Get file extension and set appropriate content type
$extension = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
$contentTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'pdf' => 'application/pdf',
    'txt' => 'text/plain'
];

$contentType = isset($contentTypes[$extension]) ? $contentTypes[$extension] : 'application/octet-stream';

// Set headers
header('Content-Type: ' . $contentType);
header('Content-Length: ' . filesize($realPath));
header('Cache-Control: private, max-age=3600'); // Cache for 1 hour
header('X-Content-Type-Options: nosniff');

// For PDFs and images, allow inline display
if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'pdf'])) {
    header('Content-Disposition: inline; filename="' . basename($realPath) . '"');
} else {
    // For other files, force download
    header('Content-Disposition: attachment; filename="' . basename($realPath) . '"');
}

// Output the file
readfile($realPath);
exit;
?>