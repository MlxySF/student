<?php
session_start();
require_once 'config.php';
require_once 'security.php';
require_once 'auth_helper.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

function sendPasswordResetEmail($email, $token) {
    $mail = new PHPMailer(true);

    // Build reset URL
    $resetUrl = sprintf(
        '%s/reset_password.php?token=%s',
        rtrim((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']), '/'),
        urlencode($token)
    );

    try {
        // SMTP Configuration (existing code - keep as is)
$mail->isSMTP();
$mail->Host       = 'smtp.mailgun.org';
$mail->SMTPAuth   = true;
$mail->Username   = 'admin@wushusportacademy.com';
$mail->Password   = 'ecba365b1738b89bf64a840726e5171e-df55650e-001e65fb';
$mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port       = 587;
$mail->CharSet    = 'UTF-8';
$mail->Encoding   = 'base64';

// **ADD THESE NEW CONFIGURATIONS**
// Set sender/return path to match From address
$mail->Sender = 'admin@wushusportacademy.com';

// Add Message-ID and other anti-spam headers
$mail->MessageID = sprintf("<%s@%s>", uniqid(), 'wushusportacademy.com');
$mail->XMailer = ' '; // Hide PHPMailer signature to avoid spam triggers

// Recipients (existing code - keep as is)
$mail->setFrom('admin@wushusportacademy.com', 'Wushu Sport Academy');
$mail->addAddress($email);
$mail->addReplyTo('admin@wushusportacademy.com', 'Wushu Sport Academy');

        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request - Wushu Sport Academy';

        $mail->Body = "
            <p>Dear Parent,</p>
            <p>We received a request to reset the password for your Parent Portal account.</p>
            <p>Please click the link below to choose a new password:</p>
            <p><a href=\"{$resetUrl}\">Reset your password</a></p>
            <p>This link will expire in 1 hour. If you did not request a password reset, you can ignore this email.</p>
            <p>Wushu Sport Academy</p>
        ";

        $mail->AltBody = "To reset your password, open this link in your browser: {$resetUrl}";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('[Password Reset Email] Error: ' . $mail->ErrorInfo);
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config.php'; // ensure $pdo

    $email = sanitizeEmail($_POST['email'] ?? '');

    // Always show generic message to avoid user enumeration
    $genericMessage = 'If an account with that email exists, a password reset link has been sent. Please check your inbox.';

    if (!isValidEmail($email)) {
        $_SESSION['error'] = 'Please enter a valid email address.';
        header('Location: forgot_password.php');
        exit;
    }

    // Check if email exists in students or parent_accounts
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("
        SELECT email 
        FROM (
            SELECT email FROM students
            UNION
            SELECT email FROM parent_accounts
        ) AS all_emails
        WHERE email = ?
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $exists = $stmt->fetchColumn();

    // Even if not exists, still show generic message
    if ($exists) {
        // Invalidate old tokens
        $del = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE email = ?");
        $del->execute([$email]);

        // Create new token (random bytes + hash)
        $rawToken = bin2hex(random_bytes(32));
        $token    = hash('sha256', $rawToken) . '.' . bin2hex(random_bytes(8)); // adds randomness

        $expiresAt = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

        $ins = $pdo->prepare("
            INSERT INTO password_resets (email, token, expires_at, used)
            VALUES (?, ?, ?, 0)
        ");
        $ins->execute([$email, $token, $expiresAt]);

        // Send email with full $token as parameter (not the rawToken)
        sendPasswordResetEmail($email, $token);
    }

    $_SESSION['success'] = $genericMessage;
    header('Location: forgot_password.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - Parent Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh;">
<div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="card shadow-lg" style="max-width: 450px; width: 100%; border-radius: 20px;">
        <div class="card-body p-4">
            <h3 class="text-center mb-3">Forgot Password</h3>
            <p class="text-muted text-center mb-4">
                Enter your email and we will send you a link to reset your password.
            </p>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <form method="POST" action="forgot_password.php">
                <div class="mb-3">
                    <label class="form-label">Email address</label>
                    <input type="email" name="email" class="form-control form-control-lg" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100 mb-2">
                    Send reset link
                </button>
                <a href="index.php?page=login" class="btn btn-outline-secondary w-100">
                    Back to login
                </a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
