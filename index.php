<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include config, security layer, auth helper, and file helper
require_once 'config.php';
require_once 'security.php';  // NEW: Security layer
require_once 'auth_helper.php';
require_once 'file_helper.php';  // NEW: File storage helper

// Include PHPMailer for admin notifications
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// Admin email configuration
define('ADMIN_EMAIL', 'chaichonghern@gmail.com');
define('ADMIN_NAME', 'Academy Admin');