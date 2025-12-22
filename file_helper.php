<?php
/**
 * file_helper.php - File Upload and Management Helper Functions
 * Date: 2025-12-21
 * Description: Handles saving files to local storage instead of base64 in database
 * 
 * Functions:
 * - saveBase64ToFile() - Convert base64 string to file
 * - saveUploadedFile() - Save $_FILES upload to disk
 * - deleteFile() - Remove file from disk
 * - getFileExtensionFromBase64() - Detect file type from base64
 * - generateUniqueFilename() - Create unique filename
 * - validateFileType() - Check if file type is allowed
 * - getFileMimeType() - Get MIME type from file
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

// Configuration
define('UPLOAD_BASE_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Allowed file types
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_DOCUMENT_TYPES', ['application/pdf']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf']);

/**
 * Save base64 encoded data to a file
 * 
 * @param string $base64Data Base64 encoded file data (with or without data URI prefix)
 * @param string $directory Subdirectory in uploads/ (e.g., 'payment_receipts', 'signatures')
 * @param string $prefix Filename prefix (e.g., 'receipt', 'signature', 'pdf')
 * @param int|string $identifier Unique identifier (student_id, registration_id, etc.)
 * @return array ['success' => bool, 'path' => string, 'error' => string]
 */
function saveBase64ToFile($base64Data, $directory, $prefix, $identifier, $userName = '', $additionalInfo = '') {
    try {
        // Validate input
        if (empty($base64Data)) {
            throw new Exception('Empty base64 data provided');
        }
        
        if (empty($directory) || empty($prefix)) {
            throw new Exception('Directory and prefix are required');
        }
        
        // Strip data URI prefix if present (e.g., "data:image/jpeg;base64,")
        $cleanBase64 = preg_replace('/^data:[^;]+;base64,/', '', $base64Data);
        
        // Decode base64 data
        $fileData = base64_decode($cleanBase64, true);
        
        if ($fileData === false) {
            throw new Exception('Invalid base64 data');
        }
        
        // Check file size
        $fileSize = strlen($fileData);
        if ($fileSize > MAX_FILE_SIZE) {
            throw new Exception('File size exceeds maximum allowed size of ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB');
        }
        
        if ($fileSize < 100) {
            throw new Exception('File size too small, possibly corrupted data');
        }
        
        // Detect file extension from data
        $extension = getFileExtensionFromData($fileData);
        
        if (!in_array($extension, ALLOWED_EXTENSIONS)) {
            throw new Exception('File type not allowed: ' . $extension);
        }
        
        // Generate unique filename
$filename = generateUniqueFilename($prefix, $identifier, $extension, $userName, $additionalInfo);

        
        // Create full directory path
        $fullDir = UPLOAD_BASE_DIR . rtrim($directory, '/') . '/';
        
        // Create directory if it doesn't exist
        if (!is_dir($fullDir)) {
            if (!mkdir($fullDir, 0755, true)) {
                throw new Exception('Failed to create directory: ' . $directory);
            }
        }
        
        // Full file path
        $filePath = $fullDir . $filename;
        
        // Save file
        if (file_put_contents($filePath, $fileData) === false) {
            throw new Exception('Failed to write file to disk');
        }
        
        // Set file permissions
        chmod($filePath, 0644);
        
        // Return relative path (from uploads/)
        $relativePath = $directory . '/' . $filename;
        
        error_log("[File Helper] Saved file: {$relativePath} (Size: {$fileSize} bytes)");
        
        return [
            'success' => true,
            'path' => $relativePath,
            'filename' => $filename,
            'size' => $fileSize,
            'error' => null
        ];
        
    } catch (Exception $e) {
        error_log("[File Helper] Error saving file: " . $e->getMessage());
        return [
            'success' => false,
            'path' => null,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Save uploaded file from $_FILES
 * 
 * @param array $uploadedFile $_FILES array element
 * @param string $directory Subdirectory in uploads/
 * @param string $prefix Filename prefix
 * @param int|string $identifier Unique identifier
 * @return array ['success' => bool, 'path' => string, 'error' => string]
 */
function saveUploadedFile($uploadedFile, $directory, $prefix, $identifier, $userName = '', $additionalInfo = '') {

    try {
        // Check for upload errors
        if (!isset($uploadedFile['error']) || is_array($uploadedFile['error'])) {
            throw new Exception('Invalid file upload');
        }
        
        // Check error code
        switch ($uploadedFile['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('File size exceeds limit');
            case UPLOAD_ERR_NO_FILE:
                throw new Exception('No file uploaded');
            default:
                throw new Exception('Upload error occurred');
        }
        
        // Validate file size
        if ($uploadedFile['size'] > MAX_FILE_SIZE) {
            throw new Exception('File size exceeds maximum allowed size');
        }
        
        // Validate MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($uploadedFile['tmp_name']);
        
        $allowedTypes = array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_DOCUMENT_TYPES);
        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('File type not allowed: ' . $mimeType);
        }
        
        // Get file extension
        $extension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ALLOWED_EXTENSIONS)) {
            throw new Exception('File extension not allowed: ' . $extension);
        }
        
        // Generate unique filename
$filename = generateUniqueFilename($prefix, $identifier, $extension, $userName, $additionalInfo);

        
        // Create full directory path
        $fullDir = UPLOAD_BASE_DIR . rtrim($directory, '/') . '/';
        
        if (!is_dir($fullDir)) {
            if (!mkdir($fullDir, 0755, true)) {
                throw new Exception('Failed to create directory');
            }
        }
        
        // Full file path
        $filePath = $fullDir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
            throw new Exception('Failed to move uploaded file');
        }
        
        // Set file permissions
        chmod($filePath, 0644);
        
        // Return relative path
        $relativePath = $directory . '/' . $filename;
        
        error_log("[File Helper] Uploaded file: {$relativePath}");
        
        return [
            'success' => true,
            'path' => $relativePath,
            'filename' => $filename,
            'size' => $uploadedFile['size'],
            'error' => null
        ];
        
    } catch (Exception $e) {
        error_log("[File Helper] Upload error: " . $e->getMessage());
        return [
            'success' => false,
            'path' => null,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Delete a file from disk
 * 
 * @param string $relativePath Relative path from uploads/ (e.g., 'payment_receipts/receipt_123.jpg')
 * @return bool Success status
 */
function deleteFile($relativePath) {
    if (empty($relativePath)) {
        return false;
    }
    
    $fullPath = UPLOAD_BASE_DIR . $relativePath;
    
    if (file_exists($fullPath)) {
        if (unlink($fullPath)) {
            error_log("[File Helper] Deleted file: {$relativePath}");
            return true;
        } else {
            error_log("[File Helper] Failed to delete file: {$relativePath}");
            return false;
        }
    }
    
    error_log("[File Helper] File not found for deletion: {$relativePath}");
    return false;
}

/**
 * Generate unique filename
 * 
 * @param string $prefix Prefix (e.g., 'receipt', 'signature')
 * @param int|string $identifier Unique ID
 * @param string $extension File extension
 * @return string Unique filename
 */
function generateUniqueFilename($prefix, $identifier, $extension, $userName = '', $additionalInfo = '') {
    $timestamp = date('YmdHis');
    $random = substr(md5(uniqid(mt_rand(), true)), 0, 6);
    
    // Sanitize user name for filename
    $cleanName = !empty($userName) ? sanitizeForFilename($userName) : 'unknown';
    
    // Build filename parts
    $parts = [$prefix, $identifier, $cleanName];
    
    // Add additional info if provided (e.g., invoice number, payment date)
    if (!empty($additionalInfo)) {
        $cleanInfo = sanitizeForFilename($additionalInfo);
        $parts[] = $cleanInfo;
    }
    
    $parts[] = $timestamp;
    $parts[] = $random;
    
    return implode('_', $parts) . '.' . $extension;
}

/**
 * Sanitize string for use in filename
 * Removes special characters, replaces spaces with underscores
 */
function sanitizeForFilename($string) {
    // Remove special characters, keep only alphanumeric, spaces, and hyphens
    $string = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $string);
    // Replace spaces with underscores
    $string = str_replace(' ', '_', $string);
    // Remove multiple underscores
    $string = preg_replace('/_+/', '_', $string);
    // Trim and limit length
    $string = substr(trim($string, '_'), 0, 50);
    return strtolower($string);
}


/**
 * Detect file extension from binary data
 * 
 * @param string $data Binary file data
 * @return string File extension
 */
function getFileExtensionFromData($data) {
    // Check file signatures (magic numbers)
    if (substr($data, 0, 3) === "\xFF\xD8\xFF") {
        return 'jpg';
    } elseif (substr($data, 0, 8) === "\x89PNG\r\n\x1A\n") {
        return 'png';
    } elseif (substr($data, 0, 6) === 'GIF87a' || substr($data, 0, 6) === 'GIF89a') {
        return 'gif';
    } elseif (substr($data, 0, 4) === 'RIFF' && substr($data, 8, 4) === 'WEBP') {
        return 'webp';
    } elseif (substr($data, 0, 4) === '%PDF') {
        return 'pdf';
    }
    
    // Default to jpg if cannot detect
    error_log('[File Helper] Could not detect file type from data, defaulting to jpg');
    return 'jpg';
}

/**
 * Get file MIME type from path
 * 
 * @param string $relativePath Relative path from uploads/
 * @return string|null MIME type or null if file not found
 */
function getFileMimeType($relativePath) {
    $fullPath = UPLOAD_BASE_DIR . $relativePath;
    
    if (!file_exists($fullPath)) {
        return null;
    }
    
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    return $finfo->file($fullPath);
}

/**
 * Check if file exists
 * 
 * @param string $relativePath Relative path from uploads/
 * @return bool
 */
function fileExists($relativePath) {
    if (empty($relativePath)) {
        return false;
    }
    
    $fullPath = UPLOAD_BASE_DIR . $relativePath;
    return file_exists($fullPath);
}

/**
 * Get full file path
 * 
 * @param string $relativePath Relative path from uploads/
 * @return string Full file system path
 */
function getFullFilePath($relativePath) {
    return UPLOAD_BASE_DIR . $relativePath;
}

/**
 * Validate file type by extension
 * 
 * @param string $extension File extension
 * @return bool
 */
function isAllowedExtension($extension) {
    return in_array(strtolower($extension), ALLOWED_EXTENSIONS);
}