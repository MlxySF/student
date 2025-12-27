<?php
/**
 * phpmailer_loader.php
 * Centralized PHPMailer class loader
 * Include this file once at the top of any script that needs PHPMailer
 * This prevents "Cannot redeclare class" errors when multiple email files are loaded
 */

// Only load PHPMailer classes if they haven't been loaded yet
if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    require_once __DIR__ . '/PHPMailer/Exception.php';
    require_once __DIR__ . '/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/SMTP.php';
}
?>