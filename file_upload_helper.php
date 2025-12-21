<?php
/**
 * File Upload Helper - Handles file uploads and management
 * Replaces base64 database storage with local file storage
 * Created: 2025-12-21
 */

// Define upload directories
define('UPLOAD_BASE_DIR', __DIR__ . '/uploads/');
define('PAYMENT_RECEIPTS_DIR', UPLOAD_BASE_DIR . 'payment_receipts/');
define('REGISTRATION_RECEIPTS_DIR', UPLOAD_BASE_DIR . 'registration_receipts/');
define('SIGNATURE_DIR', UPLOAD_BASE_DIR . 'signatures/');
define('PDF_FORMS_DIR', UPLOAD_BASE_DIR . 'registration_forms/');

// Create directories if they don't exist
function createUploadDirectories() {
    $dirs = [
        UPLOAD_BASE_DIR,
        PAYMENT_RECEIPTS_DIR,
        REGISTRATION_RECEIPTS_DIR,
        SIGNATURE_DIR,
        PDF_FORMS_DIR
    ];
    
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
            // Create index.php to prevent directory listing
            file_put_contents($dir . 'index.php', '<?php header("HTTP/1.1 403 Forbidden"); exit; ?>');
            error_log("[File Helper] Created directory: {$dir}");
        }
    }
}

/**
 * Save uploaded file from $_FILES
 * @param array $fileData - $_FILES['field_name'] array
 * @param string $directory - Target directory (use constants)
 * @param string $prefix - Filename prefix (e.g., 'receipt_', 'sig_')
 * @return array - ['success' => bool, 'filepath' => string, 'filename' => string, 'error' => string]
 */
function saveUploadedFile($fileData, $directory, $prefix = '') {
    try {
        // Validate file was uploaded
        if (!isset($fileData['error']) || $fileData['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . ($fileData['error'] ?? 'Unknown error'));
        }
        
        // Validate file size (max 5MB)
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($fileData['size'] > $maxSize) {
            throw new Exception('File too large. Maximum size is 5MB.');
        }
        
        // Get file extension
        $originalName = basename($fileData['name']);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        // Validate file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        if (!in_array($ext, $allowedTypes)) {
            throw new Exception('Invalid file type. Allowed: JPG, PNG, GIF, PDF');
        }
        
        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileData['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'application/pdf'
        ];
        
        if (!in_array($mimeType, $allowedMimes)) {
            throw new Exception('Invalid file MIME type: ' . $mimeType);
        }
        
        // Generate unique filename
        $uniqueId = uniqid() . '_' . time();
        $filename = $prefix . $uniqueId . '.' . $ext;
        $filepath = $directory . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($fileData['tmp_name'], $filepath)) {
            throw new Exception('Failed to save uploaded file');
        }
        
        // Set file permissions
        chmod($filepath, 0644);
        
        error_log("[File Helper] Saved file: {$filename} (Size: {$fileData['size']} bytes, MIME: {$mimeType})");
        
        return [
            'success' => true,
            'filepath' => $filepath,
            'filename' => $filename,
            'mime_type' => $mimeType,
            'size' => $fileData['size'],
            'error' => null
        ];
        
    } catch (Exception $e) {
        error_log("[File Helper] Error saving file: " . $e->getMessage());
        return [
            'success' => false,
            'filepath' => null,
            'filename' => null,
            'mime_type' => null,
            'size' => 0,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Save base64 data to file (for migration from old system)
 * @param string $base64Data - Base64 encoded file data (with or without data URI prefix)
 * @param string $directory - Target directory
 * @param string $prefix - Filename prefix
 * @param string $defaultExt - Default extension if not detected (default: 'jpg')
 * @return array - Same format as saveUploadedFile()
 */
function saveBase64ToFile($base64Data, $directory, $prefix = '', $defaultExt = 'jpg') {
    try {
        if (empty($base64Data)) {
            throw new Exception('Empty base64 data');
        }
        
        // Detect MIME type from data URI prefix
        $mimeType = null;
        $ext = $defaultExt;
        
        if (preg_match('/^data:([a-zA-Z0-9\/]+);base64,/', $base64Data, $matches)) {
            $mimeType = $matches[1];
            // Remove data URI prefix
            $base64Data = preg_replace('/^data:[a-zA-Z0-9\/]+;base64,/', '', $base64Data);
            
            // Map MIME to extension
            $mimeToExt = [
                'image/jpeg' => 'jpg',
                'image/jpg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'application/pdf' => 'pdf'
            ];
            
            if (isset($mimeToExt[$mimeType])) {
                $ext = $mimeToExt[$mimeType];
            }
        }
        
        // Decode base64
        $fileData = base64_decode($base64Data);
        
        if ($fileData === false || strlen($fileData) === 0) {
            throw new Exception('Failed to decode base64 data');
        }
        
        $size = strlen($fileData);
        
        // Validate size (max 5MB)
        $maxSize = 5 * 1024 * 1024;
        if ($size > $maxSize) {
            throw new Exception('File too large. Maximum size is 5MB.');
        }
        
        // Detect MIME type from actual file content if not already detected
        if (!$mimeType) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_buffer($finfo, $fileData);
            finfo_close($finfo);
        }
        
        // Generate unique filename
        $uniqueId = uniqid() . '_' . time();
        $filename = $prefix . $uniqueId . '.' . $ext;
        $filepath = $directory . $filename;
        
        // Save file
        if (file_put_contents($filepath, $fileData) === false) {
            throw new Exception('Failed to write file');
        }
        
        // Set file permissions
        chmod($filepath, 0644);
        
        error_log("[File Helper] Saved base64 file: {$filename} (Size: {$size} bytes, MIME: {$mimeType})");
        
        return [
            'success' => true,
            'filepath' => $filepath,
            'filename' => $filename,
            'mime_type' => $mimeType,
            'size' => $size,
            'error' => null
        ];
        
    } catch (Exception $e) {
        error_log("[File Helper] Error saving base64 file: " . $e->getMessage());
        return [
            'success' => false,
            'filepath' => null,
            'filename' => null,
            'mime_type' => null,
            'size' => 0,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Delete a file
 * @param string $filepath - Full path to file
 * @return bool - Success status
 */
function deleteUploadedFile($filepath) {
    try {
        if (!file_exists($filepath)) {
            error_log("[File Helper] File not found for deletion: {$filepath}");
            return false;
        }
        
        if (!unlink($filepath)) {
            throw new Exception('Failed to delete file');
        }
        
        error_log("[File Helper] Deleted file: {$filepath}");
        return true;
        
    } catch (Exception $e) {
        error_log("[File Helper] Error deleting file: " . $e->getMessage());
        return false;
    }
}

/**
 * Read file and output to browser (for secure file serving)
 * @param string $filepath - Full path to file
 * @param string $mimeType - MIME type (optional, will auto-detect if not provided)
 * @param bool $download - Force download instead of display
 * @param string $downloadName - Custom filename for download
 */
function serveFile($filepath, $mimeType = null, $download = false, $downloadName = null) {
    try {
        if (!file_exists($filepath)) {
            http_response_code(404);
            die('File not found');
        }
        
        // Auto-detect MIME type if not provided
        if (!$mimeType) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filepath);
            finfo_close($finfo);
        }
        
        // Get filename
        $filename = $downloadName ?: basename($filepath);
        
        // Set headers
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filepath));
        
        if ($download) {
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        } else {
            header('Content-Disposition: inline; filename="' . $filename . '"');
        }
        
        // Prevent caching for sensitive files
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output file
        readfile($filepath);
        exit;
        
    } catch (Exception $e) {
        error_log("[File Helper] Error serving file: " . $e->getMessage());
        http_response_code(500);
        die('Error serving file');
    }
}

/**
 * Get file info
 * @param string $filepath - Full path to file
 * @return array|null - File info or null if not found
 */
function getFileInfo($filepath) {
    if (!file_exists($filepath)) {
        return null;
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filepath);
    finfo_close($finfo);
    
    return [
        'filepath' => $filepath,
        'filename' => basename($filepath),
        'size' => filesize($filepath),
        'mime_type' => $mimeType,
        'modified' => filemtime($filepath),
        'exists' => true
    ];
}

// Initialize directories on include
createUploadDirectories();

error_log("[File Helper] Loaded successfully");