<?php
// fix_registration_status.php - Fix NULL or empty payment_status values
// Run this once to fix existing registrations with missing status

require_once 'config.php';

echo "<h2>Fixing Registration Payment Status</h2>";
echo "<p>This script will set any NULL or empty payment_status to 'pending'</p>";

try {
    // First, let's see what we have
    $stmt = $pdo->query("SELECT id, registration_number, payment_status FROM registrations");
    $registrations = $stmt->fetchAll();
    
    echo "<h3>Current Status:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Registration Number</th><th>Current Status</th></tr>";
    
    foreach ($registrations as $reg) {
        $status = $reg['payment_status'] ?? '(NULL)';
        if (empty($status)) $status = '(EMPTY)';
        echo "<tr><td>{$reg['id']}</td><td>{$reg['registration_number']}</td><td>{$status}</td></tr>";
    }
    echo "</table>";
    
    // Now fix any NULL or empty values
    $stmt = $pdo->prepare("UPDATE registrations SET payment_status = 'pending' WHERE payment_status IS NULL OR payment_status = ''");
    $stmt->execute();
    $rowsAffected = $stmt->rowCount();
    
    echo "<h3>Results:</h3>";
    echo "<p style='color: green; font-weight: bold;'>✓ Fixed {$rowsAffected} registration(s) with missing payment_status</p>";
    
    // Show updated status
    $stmt = $pdo->query("SELECT id, registration_number, payment_status FROM registrations");
    $registrations = $stmt->fetchAll();
    
    echo "<h3>Updated Status:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Registration Number</th><th>New Status</th></tr>";
    
    foreach ($registrations as $reg) {
        $status = $reg['payment_status'] ?? '(NULL)';
        echo "<tr><td>{$reg['id']}</td><td>{$reg['registration_number']}</td><td>{$status}</td></tr>";
    }
    echo "</table>";
    
    echo "<p style='margin-top: 20px;'><a href='admin.php?page=registrations'>← Back to Registrations</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Note:</strong> You can delete this file after running it once: <code>fix_registration_status.php</code></p>";
?>