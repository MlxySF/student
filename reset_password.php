<?php
session_start();
require_once 'config.php';
require_once 'security.php';
require_once 'auth_helper.php';

if (!function_exists('redirectToLogin')) {
    function redirectToLogin() {
        header('Location: index.php?page=login');
        exit;
    }
}

$token = $_GET['token'] ?? '';

// If POST, user is submitting new password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($token)) {
        $_SESSION['error'] = 'Invalid password reset link.';
        redirectToLogin();
    }

    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = 'New passwords do not match.';
        header('Location: reset_password.php?token=' . urlencode($token));
        exit;
    }

    // Check password strength with your existing helper
    $check = isStrongPassword($new_password, 6);
    if (!$check['valid']) {
        $_SESSION['error'] = $check['error'];
        header('Location: reset_password.php?token=' . urlencode($token));
        exit;
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Validate token
    $stmt = $pdo->prepare("
        SELECT * FROM password_resets
        WHERE token = ? AND used = 0 AND expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reset) {
        $_SESSION['error'] = 'This password reset link is invalid or has expired.';
        redirectToLogin();
    }

    $email = $reset['email'];

    // Determine if email belongs to student or parent
    $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT id FROM parent_accounts WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $parent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student && !$parent) {
        // No account with that email; still mark token used and show generic success
        $upd = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
        $upd->execute([$reset['id']]);

        $_SESSION['success'] = 'Password has been reset. You may now log in.';
        redirectToLogin();
    }

    $hashed = hashPassword($new_password);

    if ($student) {
        $upd = $pdo->prepare("UPDATE students SET password = ? WHERE id = ?");
        $upd->execute([$hashed, $student['id']]);
    }

    if ($parent) {
        $upd = $pdo->prepare("UPDATE parent_accounts SET password = ? WHERE id = ?");
        $upd->execute([$hashed, $parent['id']]);
    }

    // Mark token as used
    $upd = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
    $upd->execute([$reset['id']]);

    $_SESSION['success'] = 'Your password has been updated successfully. Please log in with your new password.';
    redirectToLogin();
    exit;
}

// If GET: show form (only if token looks present)
if (empty($token)) {
    $_SESSION['error'] = 'Invalid password reset link.';
    redirectToLogin();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - Parent Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh;">
<div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="card shadow-lg" style="max-width: 450px; width: 100%; border-radius: 20px;">
        <div class="card-body p-4">
            <h3 class="text-center mb-3">Choose New Password</h3>
            <p class="text-muted text-center mb-4">
                Enter a new password for your account.
            </p>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <form method="POST" action="reset_password.php">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div class="mb-3">
                    <label class="form-label">New password</label>
                    <input type="password" name="new_password" class="form-control form-control-lg" required>
                    <small class="text-muted">At least 6 characters (and must meet your existing strength rules).</small>
                </div>
                <div class="mb-4">
                    <label class="form-label">Confirm new password</label>
                    <input type="password" name="confirm_password" class="form-control form-control-lg" required>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100 mb-2">
                    Update password
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
