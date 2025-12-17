<?php
/**
 * display_helpers.php
 * 
 * Helper functions for properly displaying UTF-8 content including Chinese characters
 * Include this file at the top of pages that need to display student status with Chinese text
 */

/**
 * Safely display UTF-8 text with HTML special character escaping
 * This preserves Chinese characters while escaping HTML special characters
 * 
 * @param string $text The text to display
 * @return string The safely escaped text
 */
function safeDisplay($text) {
    if ($text === null || $text === '') {
        return '';
    }
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Get the CSS badge class for a student status
 * 
 * @param string $status The student status
 * @return string The CSS class name
 */
function getStatusBadgeClass($status) {
    if (strpos($status, 'State Team') !== false) {
        return 'badge-state-team';
    } elseif (strpos($status, 'Backup Team') !== false) {
        return 'badge-backup-team';
    } else {
        return 'badge-student';
    }
}

/**
 * Display student status badge with proper styling and UTF-8 encoding
 * 
 * @param string $status The student status
 * @return string The HTML for the status badge
 */
function displayStatusBadge($status) {
    $badgeClass = getStatusBadgeClass($status);
    $displayText = safeDisplay($status);
    return "<span class='badge {$badgeClass}'>{$displayText}</span>";
}

/**
 * Get payment status badge HTML
 * 
 * @param string $status The payment status (pending, approved, rejected, etc.)
 * @return string The HTML for the payment status badge
 */
function displayPaymentStatusBadge($status) {
    $badgeColor = 'secondary';
    $statusText = 'No Status';
    
    switch ($status) {
        case 'approved':
        case 'verified':
        case 'paid':
            $badgeColor = 'success';
            $statusText = ucfirst($status);
            break;
        case 'pending':
            $badgeColor = 'warning';
            $statusText = 'Pending';
            break;
        case 'rejected':
        case 'unpaid':
        case 'overdue':
            $badgeColor = 'danger';
            $statusText = ucfirst($status);
            break;
        default:
            if (!empty($status)) {
                $statusText = ucfirst($status);
            }
    }
    
    return "<span class='badge bg-{$badgeColor}'>" . safeDisplay($statusText) . "</span>";
}

/**
 * Ensure proper UTF-8 encoding for output
 * Call this at the beginning of your page output
 */
function ensureUTF8Output() {
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }
    // Ensure mb_string is using UTF-8
    if (function_exists('mb_internal_encoding')) {
        mb_internal_encoding('UTF-8');
    }
}
?>