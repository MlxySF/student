<?php
/**
 * File Upload Helper
 * Handles saving payment receipts and other files to local storage
 * CONVERTED FROM: Base64 database storage
 * CONVERTED TO: Local file system storage in uploads/ directory
 */

// Define upload directories
define('UPLOAD_BASE_DIR', __DIR__ . '/uploads');
define('PAYMENT_RECEIPTS_DIR', UPLOAD_BASE_DIR . '/payment_receipts');
define('REGISTRATION_RECEIPTS_DIR', UPLOAD_BASE_DIR . '/registration_receipts');
define('STUDENT_DOCUMENTS_DIR', UPLOAD_BASE_DIR . '/student_documents');
define('ADMIN_DOCUMENTS_DIR', UPLOAD_BASE_DIR . '/admin_documents');

/**
 * Initialize upload directories
 * Creates necessary folders if they don't exist
 */
function initializeUploadDirectories() {
    $directories = [
        UPLOAD_BASE_DIR,
        PAYMENT_RECEIPTS_DIR,
        REGISTRATION_RECEIPTS_DIR,
        STUDENT_DOCUMENTS_DIR,
        ADMIN_DOCUMENTS_DIR
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
            
            // Create .htaccess in each directory for additional protection
            $htaccess = $dir . '/.htaccess';
            if (!file_exists($htaccess)) {
                file_put_contents($htaccess, "# Deny direct access\nOrder deny,allow\nDeny from all\n");
            }
            
            error_log("[Upload Dir] Created: {$dir}");
        }
    }
}

/**
 * Save base64 image/file to local storage
 * @param string $base64Data Base64 encoded file data (with or without data URI prefix)
 * @param string $directory Target directory (e.g., PAYMENT_RECEIPTS_DIR)
 * @param string $prefix Filename prefix (e.g., 'receipt_', 'invoice_')
 * @param string|null $existingFilename Optional: existing filename to update
 * @return array ['success' => bool, 'filename' => string, 'filepath' => string, 'error' => string]
 */
function saveBase64File($base64Data, $directory, $prefix = 'file_', $existingFilename = null) {
    try {
        // Initialize directories if not exists
        initializeUploadDirectories();
        
        // Detect MIME type and extension
        $fileInfo = detectFileTypeFromBase64($base64Data);
        $extension = $fileInfo['extension'];
        $mimeType = $fileInfo['mime'];
        
        // Strip data URI prefix
        $pureBase64 = preg_replace('/^data:[^;]+;base64,/', '', $base64Data);
        
        // Decode base64
        $fileData = base64_decode($pureBase64);
        if ($fileData === false) {
            throw new Exception('Failed to decode base64 data');
        }
        
        // Generate filename
        if ($existingFilename && file_exists($directory . '/' . $existingFilename)) {
            // Update existing file
            $filename = $existingFilename;
            error_log("[File Upload] Updating existing file: {$filename}");
        } else {
            // Create new filename
            $timestamp = time();
            $random = mt_rand(1000, 9999);
            $filename = $prefix . $timestamp . '_' . $random . '.' . $extension;
            error_log("[File Upload] Creating new file: {$filename}");
        }
        
        // Full file path
        $filepath = $directory . '/' . $filename;
        
        // Save file
        $bytesWritten = file_put_contents($filepath, $fileData);
        if ($bytesWritten === false) {
            throw new Exception('Failed to write file to disk');
        }
        
        // Verify file exists
        if (!file_exists($filepath)) {
            throw new Exception('File was not saved successfully');
        }
        
        $filesize = filesize($filepath);
        error_log("[File Upload] Success: {$filename} ({$filesize} bytes, type: {$mimeType})");
        
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'relative_path' => str_replace(__DIR__ . '/', '', $filepath),
            'filesize' => $filesize,
            'mime_type' => $mimeType,
            'error' => null
        ];
        
    } catch (Exception $e) {
        error_log("[File Upload] Error: " . $e->getMessage());
        return [
            'success' => false,
            'filename' => null,
            'filepath' => null,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Save uploaded file from $_FILES
 * @param array $fileArray $_FILES['field_name'] array
 * @param string $directory Target directory
 * @param string $prefix Filename prefix
 * @return array ['success' => bool, 'filename' => string, 'filepath' => string, 'error' => string]
 */
function saveUploadedFile($fileArray, $directory, $prefix = 'file_') {
    try {
        // Initialize directories
        initializeUploadDirectories();
        
        // Validate file upload
        if (!isset($fileArray['tmp_name']) || !is_uploaded_file($fileArray['tmp_name'])) {
            throw new Exception('Invalid file upload');
        }
        
        if ($fileArray['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $fileArray['error']);
        }
        
        // Validate file size (max 10MB)
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($fileArray['size'] > $maxSize) {
            throw new Exception('File size exceeds 10MB limit');
        }
        
        // Get file extension
        $originalName = $fileArray['name'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        // Validate file type
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception('File type not allowed. Allowed types: ' . implode(', ', $allowedExtensions));
        }
        
        // Generate filename
        $timestamp = time();
        $random = mt_rand(1000, 9999);
        $filename = $prefix . $timestamp . '_' . $random . '.' . $extension;
        $filepath = $directory . '/' . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($fileArray['tmp_name'], $filepath)) {
            throw new Exception('Failed to move uploaded file');
        }
        
        $filesize = filesize($filepath);
        $mimeType = mime_content_type($filepath);
        
        error_log("[File Upload] Success: {$filename} ({$filesize} bytes, type: {$mimeType})");
        
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'relative_path' => str_replace(__DIR__ . '/', '', $filepath),
            'filesize' => $filesize,
            'mime_type' => $mimeType,
            'error' => null
        ];
        
    } catch (Exception $e) {
        error_log("[File Upload] Error: " . $e->getMessage());
        return [
            'success' => false,
            'filename' => null,
            'filepath' => null,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Delete file from local storage
 * @param string $filepath Full or relative path to file
 * @return bool Success status
 */
function deleteLocalFile($filepath) {
    try {
        // Handle relative paths
        if (!file_exists($filepath)) {
            $filepath = __DIR__ . '/' . $filepath;
        }
        
        if (!file_exists($filepath)) {
            error_log("[File Delete] File not found: {$filepath}");
            return false;
        }
        
        if (unlink($filepath)) {
            error_log("[File Delete] Success: {$filepath}");
            return true;
        }
        
        error_log("[File Delete] Failed: {$filepath}");
        return false;
        
    } catch (Exception $e) {
        error_log("[File Delete] Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Detect file type from base64 data
 * @param string $base64Data Base64 encoded data
 * @return array ['mime' => string, 'extension' => string]
 */
function detectFileTypeFromBase64($base64Data) {
    // Check for data URI prefix
    if (preg_match('/^data:([^;]+);base64,/', $base64Data, $matches)) {
        $mimeType = $matches[1];
        $extension = getExtensionFromMime($mimeType);
        return ['mime' => $mimeType, 'extension' => $extension];
    }
    
    // Strip any data URI prefix
    $cleanBase64 = preg_replace('/^data:[^;]+;base64,/', '', $base64Data);
    
    // Decode first few bytes to check file signature
    $imageData = base64_decode(substr($cleanBase64, 0, 200));
    
    // Check file signatures (magic numbers)
    if (substr($imageData, 0, 3) === "\xFF\xD8\xFF") {
        return ['mime' => 'image/jpeg', 'extension' => 'jpg'];
    } elseif (substr($imageData, 0, 8) === "\x89PNG\r\n\x1A\n") {
        return ['mime' => 'image/png', 'extension' => 'png'];
    } elseif (substr($imageData, 0, 6) === 'GIF87a' || substr($imageData, 0, 6) === 'GIF89a') {
        return ['mime' => 'image/gif', 'extension' => 'gif'];
    } elseif (substr($imageData, 0, 4) === '%PDF') {
        return ['mime' => 'application/pdf', 'extension' => 'pdf'];
    } elseif (substr($imageData, 0, 2) === 'BM') {
        return ['mime' => 'image/bmp', 'extension' => 'bmp'];
    } elseif (substr($imageData, 0, 4) === 'RIFF' && substr($imageData, 8, 4) === 'WEBP') {
        return ['mime' => 'image/webp', 'extension' => 'webp'];
    }
    
    // Default to JPEG
    error_log('[File Type Detection] Could not detect type, defaulting to JPEG');
    return ['mime' => 'image/jpeg', 'extension' => 'jpg'];
}

/**
 * Get file extension from MIME type
 * @param string $mimeType MIME type
 * @return string File extension
 */
function getExtensionFromMime($mimeType) {
    $mimeMap = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/bmp' => 'bmp',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];
    
    return $mimeMap[$mimeType] ?? 'jpg';
}

/**
 * Get file URL for display (relative path for web access)
 * @param string $filepath Full or relative file path
 * @return string URL path
 */
function getFileUrl($filepath) {
    $relativePath = str_replace(__DIR__ . '/', '', $filepath);
    return '/' . $relativePath;
}

/**
 * Check if file exists
 * @param string $filepath Full or relative path
 * @return bool
 */
function fileExistsLocal($filepath) {
    if (!file_exists($filepath)) {
        $filepath = __DIR__ . '/' . $filepath;
    }
    return file_exists($filepath);
}

/**
 * Get file size in human readable format
 * @param string $filepath Full or relative path
 * @return string File size (e.g., "1.5 MB")
 */
function getHumanFileSize($filepath) {
    if (!file_exists($filepath)) {
        $filepath = __DIR__ . '/' . $filepath;
    }
    
    if (!file_exists($filepath)) {
        return 'N/A';
    }
    
    $bytes = filesize($filepath);
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}
?>
