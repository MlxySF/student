<?php
/**
 * File Storage Helper
 * Handles local file storage instead of base64 database storage
 * Provides secure file upload, retrieval, and deletion functions
 */

// Define uploads directory
define('UPLOADS_DIR', __DIR__ . '/uploads/');
define('PAYMENT_RECEIPTS_DIR', UPLOADS_DIR . 'payment_receipts/');
define('REGISTRATION_DOCS_DIR', UPLOADS_DIR . 'registration_docs/');

/**
 * Initialize directory structure
 * Creates necessary directories if they don't exist
 */
function initializeUploadDirectories() {
    $directories = [
        UPLOADS_DIR,
        PAYMENT_RECEIPTS_DIR,
        REGISTRATION_DOCS_DIR
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
            error_log("[File Storage] Created directory: {$dir}");
        }
    }
    
    // Create .htaccess to protect uploads directory
    $htaccess_content = "# Prevent direct access\nDeny from all";
    $htaccess_path = UPLOADS_DIR . '.htaccess';
    
    if (!file_exists($htaccess_path)) {
        file_put_contents($htaccess_path, $htaccess_content);
        error_log("[File Storage] Created .htaccess protection");
    }
}

/**
 * Save uploaded file from $_FILES array
 * 
 * @param array $file $_FILES array element
 * @param string $directory Target directory (e.g., PAYMENT_RECEIPTS_DIR)
 * @param string|null $custom_filename Optional custom filename
 * @return array ['success' => bool, 'filename' => string, 'filepath' => string, 'error' => string]
 */
function saveUploadedFile($file, $directory, $custom_filename = null) {
    try {
        // Validate file
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'error' => 'Invalid file upload'];
        }
        
        // Check file size (max 5MB)
        $maxSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'File size exceeds 5MB limit'];
        }
        
        // Validate MIME type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return ['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, GIF, and PDF allowed.'];
        }
        
        // Get file extension
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (empty($extension)) {
            // Fallback to mime type
            $mimeToExt = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'application/pdf' => 'pdf'
            ];
            $extension = $mimeToExt[$mimeType] ?? 'bin';
        }
        
        // Generate secure filename
        if ($custom_filename) {
            $filename = $custom_filename;
        } else {
            $filename = uniqid('file_' . date('Ymd_His') . '_', true) . '.' . $extension;
        }
        
        // Sanitize filename
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
        
        // Full file path
        $filepath = $directory . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Set file permissions
            chmod($filepath, 0644);
            
            error_log("[File Storage] Saved file: {$filename} (MIME: {$mimeType}, Size: {$file['size']} bytes)");
            
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'mime_type' => $mimeType,
                'size' => $file['size']
            ];
        } else {
            return ['success' => false, 'error' => 'Failed to move uploaded file'];
        }
        
    } catch (Exception $e) {
        error_log("[File Storage] Error: " . $e->getMessage());
        return ['success' => false, 'error' => 'File save error: ' . $e->getMessage()];
    }
}

/**
 * Save base64 data to file
 * Used for converting existing base64 data or API uploads
 * 
 * @param string $base64Data Base64 encoded data (with or without data URI prefix)
 * @param string $directory Target directory
 * @param string|null $filename Custom filename
 * @param string|null $mimeType MIME type (will be detected if not provided)
 * @return array ['success' => bool, 'filename' => string, 'filepath' => string, 'error' => string]
 */
function saveBase64ToFile($base64Data, $directory, $filename = null, $mimeType = null) {
    try {
        // Strip data URI prefix if present
        $base64Data = preg_replace('/^data:[^;]+;base64,/', '', $base64Data);
        
        // Decode base64
        $fileData = base64_decode($base64Data, true);
        if ($fileData === false) {
            return ['success' => false, 'error' => 'Invalid base64 data'];
        }
        
        // Check file size
        $fileSize = strlen($fileData);
        $maxSize = 5 * 1024 * 1024;
        if ($fileSize > $maxSize) {
            return ['success' => false, 'error' => 'File size exceeds 5MB limit'];
        }
        
        // Detect MIME type if not provided
        if (!$mimeType) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_buffer($finfo, $fileData);
            finfo_close($finfo);
        }
        
        // Validate MIME type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
        if (!in_array($mimeType, $allowedTypes)) {
            return ['success' => false, 'error' => 'Invalid file type'];
        }
        
        // Get extension from MIME type
        $mimeToExt = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'application/pdf' => 'pdf'
        ];
        $extension = $mimeToExt[$mimeType] ?? 'bin';
        
        // Generate filename if not provided
        if (!$filename) {
            $filename = uniqid('file_' . date('Ymd_His') . '_', true) . '.' . $extension;
        } else if (!pathinfo($filename, PATHINFO_EXTENSION)) {
            $filename .= '.' . $extension;
        }
        
        // Sanitize filename
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
        
        // Full file path
        $filepath = $directory . $filename;
        
        // Save file
        if (file_put_contents($filepath, $fileData) !== false) {
            chmod($filepath, 0644);
            
            error_log("[File Storage] Saved base64 file: {$filename} (MIME: {$mimeType}, Size: {$fileSize} bytes)");
            
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'mime_type' => $mimeType,
                'size' => $fileSize
            ];
        } else {
            return ['success' => false, 'error' => 'Failed to write file'];
        }
        
    } catch (Exception $e) {
        error_log("[File Storage] Error: " . $e->getMessage());
        return ['success' => false, 'error' => 'File save error: ' . $e->getMessage()];
    }
}

/**
 * Delete a file
 * 
 * @param string $filepath Full path to file
 * @return bool Success status
 */
function deleteFile($filepath) {
    try {
        if (file_exists($filepath)) {
            if (unlink($filepath)) {
                error_log("[File Storage] Deleted file: {$filepath}");
                return true;
            }
        }
        return false;
    } catch (Exception $e) {
        error_log("[File Storage] Delete error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get file info
 * 
 * @param string $filepath Full path to file
 * @return array|false ['size' => int, 'mime_type' => string, 'exists' => bool]
 */
function getFileInfo($filepath) {
    try {
        if (!file_exists($filepath)) {
            return ['exists' => false];
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filepath);
        finfo_close($finfo);
        
        return [
            'exists' => true,
            'size' => filesize($filepath),
            'mime_type' => $mimeType,
            'modified' => filemtime($filepath)
        ];
    } catch (Exception $e) {
        error_log("[File Storage] Get info error: " . $e->getMessage());
        return false;
    }
}

/**
 * Serve file for download/display
 * Should be used through serve_file.php for security
 * 
 * @param string $filepath Full path to file
 * @param bool $inline Display inline (true) or force download (false)
 */
function serveFile($filepath, $inline = true) {
    if (!file_exists($filepath)) {
        http_response_code(404);
        die('File not found');
    }
    
    // Get file info
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filepath);
    finfo_close($finfo);
    
    $filename = basename($filepath);
    $filesize = filesize($filepath);
    
    // Set headers
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . $filesize);
    
    if ($inline) {
        header('Content-Disposition: inline; filename="' . $filename . '"');
    } else {
        header('Content-Disposition: attachment; filename="' . $filename . '"');
    }
    
    // Cache control
    header('Cache-Control: private, max-age=3600');
    header('Pragma: private');
    
    // Output file
    readfile($filepath);
    exit;
}

/**
 * Clean up old files (for maintenance)
 * 
 * @param string $directory Directory to clean
 * @param int $days Delete files older than X days
 * @return int Number of files deleted
 */
function cleanupOldFiles($directory, $days = 365) {
    $deleted = 0;
    $threshold = time() - ($days * 24 * 60 * 60);
    
    try {
        $files = glob($directory . '*');
        
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $threshold) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        error_log("[File Storage] Cleanup: Deleted {$deleted} old files from {$directory}");
        return $deleted;
    } catch (Exception $e) {
        error_log("[File Storage] Cleanup error: " . $e->getMessage());
        return 0;
    }
}

// Initialize directories on include
initializeUploadDirectories();