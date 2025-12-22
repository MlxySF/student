<?php
/**
 * serve_file.php - Secure File Serving Endpoint
 * Date: 2025-12-21
 * Description: Serves uploaded files with authentication and security checks
 * 
 * Usage:
 *   serve_file.php?path=payment_receipts/receipt_123_20251221_a3f9.jpg
 *   serve_file.php?path=signatures/signature_WSA2025-1234_20251221_b4e8.png
 *   serve_file.php?path=registration_pdfs/pdf_WSA2025-1234_20251221_c5f9.pdf
 * 
 * Security Features:
 * - Requires admin authentication
 * - Validates file paths (prevents directory traversal)
 * - Only serves files from uploads directory
 * - Checks file existence
 * - Sets proper MIME types
 * - Prevents PHP execution
 */

session_start();
require_once 'file_helper.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

/**
 * Check if user is authenticated as admin
 */
function isAdminAuthenticated() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Validate file path to prevent directory traversal attacks
 */
function isValidPath($path) {
    // Check for null or empty
    if (empty($path)) {
        return false;
    }
    
    // Check for directory traversal attempts
    if (strpos($path, '..') !== false) {
        error_log("[Serve File] Directory traversal attempt detected: {$path}");
        return false;
    }
    
    // Check for absolute paths
    if (strpos($path, '/') === 0 || strpos($path, '\\') === 0) {
        error_log("[Serve File] Absolute path attempt detected: {$path}");
        return false;
    }
    
    // Must start with allowed directories
    $allowedDirs = ['payment_receipts/', 'signatures/', 'registration_pdfs/'];
    $isAllowed = false;
    foreach ($allowedDirs as $dir) {
        if (strpos($path, $dir) === 0) {
            $isAllowed = true;
            break;
        }
    }
    
    if (!$isAllowed) {
        error_log("[Serve File] Invalid directory: {$path}");
        return false;
    }
    
    return true;
}

/**
 * Get MIME type for file
 */
function getMimeType($filePath) {
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    $mimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
    ];
    
    return $mimeTypes[$extension] ?? 'application/octet-stream';
}

// ============================================
// MAIN FILE SERVING LOGIC
// ============================================

try {
    // Check authentication
    if (!isAdminAuthenticated()) {
        error_log("[Serve File] Unauthorized access attempt");
        http_response_code(403);
        die('Access denied. Admin authentication required.');
    }
    
    // Get file path from query parameter
    $relativePath = $_GET['path'] ?? '';
    
    if (empty($relativePath)) {
        http_response_code(400);
        die('Missing file path parameter');
    }
    
    // Validate path
    if (!isValidPath($relativePath)) {
        error_log("[Serve File] Invalid path rejected: {$relativePath}");
        http_response_code(400);
        die('Invalid file path');
    }
    
    // Get full file path
    $fullPath = getFullFilePath($relativePath);
    
    // Check if file exists
    if (!file_exists($fullPath)) {
        error_log("[Serve File] File not found: {$fullPath}");
        http_response_code(404);
        die('File not found');
    }
    
    // Check if it's a file (not directory)
    if (!is_file($fullPath)) {
        error_log("[Serve File] Not a file: {$fullPath}");
        http_response_code(400);
        die('Invalid file');
    }
    
    // Get file info
    $fileSize = filesize($fullPath);
    $mimeType = getMimeType($fullPath);
    $fileName = basename($fullPath);
    
    // Log access
    error_log("[Serve File] Serving file: {$relativePath} (Size: {$fileSize} bytes, MIME: {$mimeType})");
    
    // Set headers
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . $fileSize);
    header('Content-Disposition: inline; filename="' . $fileName . '"');
    header('Cache-Control: private, max-age=3600'); // Cache for 1 hour
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    
    // Prevent PHP execution
    if (pathinfo($fullPath, PATHINFO_EXTENSION) === 'php') {
        error_log("[Serve File] Attempted to serve PHP file: {$relativePath}");
        http_response_code(403);
        die('Access denied');
    }
    
    // Clear output buffer
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Output file
    readfile($fullPath);
    exit;
    
} catch (Exception $e) {
    error_log("[Serve File] Error: " . $e->getMessage());
    http_response_code(500);
    die('Internal server error');
}