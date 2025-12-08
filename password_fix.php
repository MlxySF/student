<?php
// password_fix.php - Admin Password Fix Script
// Upload this file and run it ONCE to fix admin login

echo "<!DOCTYPE html>
<html>
<head>
    <title>Admin Password Fix</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 30px; max-width: 900px; margin: 0 auto; background: #f5f5f5; }
        .box { background: white; padding: 25px; border-radius: 10px; margin: 20px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .info { background: #d1ecf1; color: #0c5460; border-left: 4px solid #17a2b8; }
        .warning { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }
        pre { background: #f8f9fa; padding: 15px; overflow-x: auto; border-radius: 5px; }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
    </style>
</head>
<body>";

echo "<h2>🔧 Admin Password Fix Tool</h2>";

// Generate correct hash for 'admin123'
$correct_hash = password_hash('admin123', PASSWORD_DEFAULT);

echo "<div class='box info'>";
echo "<h3>✓ Password Hash Generated</h3>";
echo "<p><strong>Password:</strong> admin123</p>";
echo "<p><strong>Hash:</strong></p>";
echo "<pre>" . htmlspecialchars($correct_hash) . "</pre>";
echo "</div>";

// Try automatic fix
if (file_exists('config.php')) {
    echo "<div class='box'>";
    echo "<h3>🔄 Attempting Automatic Fix...</h3>";

    try {
        require_once 'config.php';

        // Check if admin exists
        $stmt = $pdo->query("SELECT * FROM admin_users WHERE username = 'admin'");
        $admin = $stmt->fetch();

        if ($admin) {
            // Update password
            $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE username = 'admin'");
            $stmt->execute([$correct_hash]);

            echo "</div><div class='box success'>";
            echo "<h3>✅ SUCCESS! Password Updated</h3>";
            echo "<p><strong>You can now login with:</strong></p>";
            echo "<ul>";
            echo "<li><strong>URL:</strong> admin.php</li>";
            echo "<li><strong>Username:</strong> admin</li>";
            echo "<li><strong>Password:</strong> admin123</li>";
            echo "</ul>";
            echo "</div>";

            echo "<div class='box warning'>";
            echo "<h3>⚠️ IMPORTANT - Security Steps</h3>";
            echo "<ol>";
            echo "<li><strong>DELETE this file (password_fix.php) immediately!</strong></li>";
            echo "<li>Login to admin panel</li>";
            echo "<li>Change the password from default 'admin123'</li>";
            echo "</ol>";
            echo "</div>";

        } else {
            // Admin doesn't exist, create it
            echo "<p>Admin user not found. Creating new admin user...</p>";
            $stmt = $pdo->prepare("INSERT INTO admin_users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(['admin', $correct_hash, 'System Administrator', 'admin@portal.com', 'super_admin']);

            echo "</div><div class='box success'>";
            echo "<h3>✅ SUCCESS! Admin User Created</h3>";
            echo "<p><strong>Login credentials:</strong></p>";
            echo "<ul>";
            echo "<li><strong>Username:</strong> admin</li>";
            echo "<li><strong>Password:</strong> admin123</li>";
            echo "</ul>";
            echo "</div>";
        }

    } catch(PDOException $e) {
        echo "</div><div class='box error'>";
        echo "<h3>❌ Automatic Fix Failed</h3>";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>Manual Solution:</strong> Run this SQL query in phpMyAdmin:</p>";
        echo "<pre>UPDATE admin_users SET password = '" . $correct_hash . "' WHERE username = 'admin';</pre>";
        echo "</div>";
    }
} else {
    echo "<div class='box error'>";
    echo "<h3>❌ config.php Not Found</h3>";
    echo "<p>Please ensure this file is in the same directory as config.php</p>";
    echo "<p><strong>Manual Solution:</strong> Run this SQL in phpMyAdmin:</p>";
    echo "<pre>UPDATE admin_users SET password = '" . $correct_hash . "' WHERE username = 'admin';</pre>";
    echo "</div>";
}

echo "</body></html>";
?>