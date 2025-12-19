<?php
/**
 * test_registration_save.php
 * Diagnostic script to check if registration can be saved to database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Registration Database Test</h1>";

// Database connection
try {
    $host = 'localhost';
    $dbname = 'mlxysf_student_portal';
    $username = 'mlxysf_student_portal';
    $password = 'YAjv86kdSAPpw';

    $conn = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Database connection successful!</p>";

    // Check if registrations table exists
    echo "<h2>1. Check registrations table</h2>";
    $stmt = $conn->query("SHOW TABLES LIKE 'registrations'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ registrations table exists</p>";
        
        // Show table structure
        echo "<h3>Table Structure:</h3>";
        $stmt = $conn->query("DESCRIBE registrations");
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td></tr>";
        }
        echo "</table>";
        
        // Check if parent_account_id column exists
        $stmt = $conn->query("SHOW COLUMNS FROM registrations LIKE 'parent_account_id'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✅ parent_account_id column exists</p>";
        } else {
            echo "<p style='color: red;'>❌ parent_account_id column is MISSING!</p>";
            echo "<p>Run this SQL to add it:</p>";
            echo "<pre>ALTER TABLE registrations ADD COLUMN parent_account_id INT DEFAULT NULL AFTER parent_ic;</pre>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ registrations table does NOT exist!</p>";
    }

    // Check if parent_accounts table exists
    echo "<h2>2. Check parent_accounts table</h2>";
    $stmt = $conn->query("SHOW TABLES LIKE 'parent_accounts'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ parent_accounts table exists</p>";
        
        // Count records
        $stmt = $conn->query("SELECT COUNT(*) FROM parent_accounts");
        $count = $stmt->fetchColumn();
        echo "<p>Total parent accounts: <strong>{$count}</strong></p>";
        
    } else {
        echo "<p style='color: red;'>❌ parent_accounts table does NOT exist!</p>";
        echo "<p>You need to create this table first!</p>";
    }

    // Test insert
    echo "<h2>3. Test Registration Insert</h2>";
    
    try {
        $conn->beginTransaction();
        
        // Test parent creation
        $testParentEmail = 'test_' . time() . '@test.com';
        $testParentIC = '123456-78-9012';
        $testPassword = '9012'; // Last 4 digits
        $hash = password_hash($testPassword, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO parent_accounts 
            (parent_id, full_name, email, phone, ic_number, password, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())");
        
        $stmt->execute([
            'PAR-TEST-' . time(),
            'Test Parent',
            $testParentEmail,
            '012-3456789',
            $testParentIC,
            $hash
        ]);
        
        $parentId = $conn->lastInsertId();
        echo "<p style='color: green;'>✅ Test parent account created (ID: {$parentId})</p>";
        
        // Test registration insert
        $sql = "INSERT INTO registrations (
            registration_number, name_cn, name_en, ic, age, school, status,
            phone, email, level, events, schedule, parent_name, parent_ic,
            form_date, signature_base64, pdf_base64,
            payment_amount, payment_date, payment_receipt_base64, payment_status, class_count,
            parent_account_id, registration_type, is_additional_child, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, 'pending', ?,
            ?, 'parent_managed', ?, NOW()
        )";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'WSA-TEST-' . time(),
            '测试',
            'Test Child',
            '234567-89-0123',
            10,
            'Test School',
            'Student 学生',
            '012-3456789',
            $testParentEmail,
            'Beginner',
            'Changquan, Daoshu',
            'Sunday 10am-12pm',
            'Test Parent',
            $testParentIC,
            date('d/m/Y'),
            'data:image/png;base64,test',
            'data:application/pdf;base64,test',
            120.00,
            date('Y-m-d'),
            'data:image/png;base64,receipt',
            1,
            $parentId,
            0
        ]);
        
        $regId = $conn->lastInsertId();
        echo "<p style='color: green;'>✅ Test registration created (ID: {$regId})</p>";
        
        // Rollback to not save test data
        $conn->rollBack();
        echo "<p style='color: blue;'>ℹ️ Test data rolled back (not saved)</p>";
        
        echo "<h3 style='color: green;'>✅ ALL TESTS PASSED!</h3>";
        echo "<p>Your database is properly configured and can save registrations.</p>";
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo "<p style='color: red;'>❌ Test insert FAILED!</p>";
        echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>SQL State:</strong> " . $e->getCode() . "</p>";
    }

    // Show recent registrations
    echo "<h2>4. Recent Registrations</h2>";
    $stmt = $conn->query("SELECT registration_number, name_en, email, created_at, payment_status FROM registrations ORDER BY created_at DESC LIMIT 10");
    $regs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($regs) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Reg#</th><th>Child Name</th><th>Parent Email</th><th>Status</th><th>Created</th></tr>";
        foreach ($regs as $reg) {
            echo "<tr>";
            echo "<td>{$reg['registration_number']}</td>";
            echo "<td>{$reg['name_en']}</td>";
            echo "<td>{$reg['email']}</td>";
            echo "<td>{$reg['payment_status']}</td>";
            echo "<td>{$reg['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No registrations found in database.</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database connection failed!</p>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>Back to Home</a></p>";
?>