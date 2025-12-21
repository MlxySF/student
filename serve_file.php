<?php
/**
 * Secure File Serving Script
 * Validates user authentication before serving uploaded files
 * Prevents direct URL access to sensitive documents
 */

session_start();
require_once 'config.php';
require_once 'file_upload_helper.php';

// Check if user is logged in (admin, student, or parent)
function isAuthenticated() {
    return isset($_SESSION['admin_id']) || 
           isset($_SESSION['student_id']) || 
           isset($_SESSION['parent_id']);
}

// Check if file access is authorized
function isAuthorizedToViewFile($filepath, $userId, $userType) {
    // Admin can view all files
    if ($userType === 'admin') {
        return true;
    }
    
    // For students and parents, check if file belongs to them
    // Filename contains user/student ID as part of the unique identifier
    // This is a basic check - you may want to query database for more security
    
    $filename = basename($filepath);
    
    // Extract patterns from filename (format: prefix_uniqueid_timestamp.ext)
    // Example: receipt_123_1703123456_1703123456.jpg
    // The file system stores files with random IDs, so we need to check database ownership
    
    return true; // For now, allow authenticated users to view
    // TODO: Add database check to verify file ownership
}

// Main logic
try {
    // Check authentication
    if (!isAuthenticated()) {
        http_response_code(403);
        die('Unauthorized access. Please login.');
    }
    
    // Get file parameter
    if (!isset($_GET['file']) || empty($_GET['file'])) {
        http_response_code(400);
        die('No file specified');
    }
    
    $requestedFile = $_GET['file'];
    
    // Sanitize filename - prevent directory traversal
    $filename = basename($requestedFile);
    
    if ($filename !== $requestedFile || strpos($requestedFile, '..') !== false) {
        http_response_code(400);
        die('Invalid file request');
    }
    
    // Determine user type and ID
    $userType = null;
    $userId = null;
    
    if (isset($_SESSION['admin_id'])) {
        $userType = 'admin';
        $userId = $_SESSION['admin_id'];
    } elseif (isset($_SESSION['student_id'])) {
        $userType = 'student';
        $userId = $_SESSION['student_id'];
    } elseif (isset($_SESSION['parent_id'])) {
        $userType = 'parent';
        $userId = $_SESSION['parent_id'];
    }
    
    // Determine file directory based on type parameter
    $type = $_GET['type'] ?? 'payment';
    
    $allowedTypes = [
        'payment' => PAYMENT_RECEIPTS_DIR,
        'registration' => REGISTRATION_RECEIPTS_DIR,
        'signature' => SIGNATURE_DIR,
        'form' => PDF_FORMS_DIR
    ];
    
    if (!isset($allowedTypes[$type])) {
        http_response_code(400);
        die('Invalid file type');
    }
    
    $directory = $allowedTypes[$type];
    $filepath = $directory . $filename;
    
    // Check if file exists
    if (!file_exists($filepath)) {
        error_log("[Serve File] File not found: {$filepath}");
        http_response_code(404);
        die('File not found');
    }
    
    // Check authorization
    if (!isAuthorizedToViewFile($filepath, $userId, $userType)) {
        http_response_code(403);
        die('You are not authorized to view this file');
    }
    
    // Determine if download or inline display
    $download = isset($_GET['download']) && $_GET['download'] === '1';
    
    // Serve the file
    error_log("[Serve File] Serving {$filename} to {$userType} ID: {$userId}");
    serveFile($filepath, null, $download);
    
} catch (Exception $e) {
    error_log("[Serve File] Error: " . $e->getMessage());
    http_response_code(500);
    die('Error serving file');
}