<?php
// check_registration_columns.php - Check actual column structure
require_once 'config.php';

echo "<h2>Registrations Table Structure</h2>";

try {
    // Get column information from registrations table
    $stmt = $pdo->query("DESCRIBE registrations");
    $columns = $stmt->fetchAll();
    
    echo "<h3>All Columns in 'registrations' table:</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>Field Name</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    $hasPaymentStatus = false;
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>" . ($col['Default'] ?? '(NULL)') . "</td>";
        echo "</tr>";
        
        if ($col['Field'] === 'payment_status') {
            $hasPaymentStatus = true;
        }
    }
    echo "</table>";
    
    echo "<hr>";
    if ($hasPaymentStatus) {
        echo "<p style='color: green; font-size: 18px;'>✓ Column 'payment_status' EXISTS</p>";
    } else {
        echo "<p style='color: red; font-size: 18px;'>✗ Column 'payment_status' DOES NOT EXIST!</p>";
        echo "<p>This is why the UPDATE query isn't working. The column name might be different.</p>";
    }
    
    // Now let's see actual data
    echo "<h3>Current Registration Data (first 3 rows):</h3>";
    $stmt = $pdo->query("SELECT * FROM registrations ORDER BY created_at DESC LIMIT 3");
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($registrations)) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; font-size: 12px;'>";
        
        // Header
        echo "<tr style='background: #f0f0f0;'>";
        foreach (array_keys($registrations[0]) as $key) {
            echo "<th>{$key}</th>";
        }
        echo "</tr>";
        
        // Data rows
        foreach ($registrations as $reg) {
            echo "<tr>";
            foreach ($reg as $value) {
                $displayValue = $value;
                if (is_null($value)) {
                    $displayValue = '<em style="color: red;">(NULL)</em>';
                } elseif ($value === '') {
                    $displayValue = '<em style="color: orange;">(EMPTY)</em>';
                } elseif (strlen($value) > 50) {
                    $displayValue = substr($value, 0, 50) . '...';
                }
                echo "<td>{$displayValue}</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test UPDATE query
    echo "<hr>";
    echo "<h3>Testing UPDATE Query:</h3>";
    
    $testId = $registrations[0]['id'] ?? 1;
    echo "<p>Attempting to update registration ID: {$testId}</p>";
    
    $stmt = $pdo->prepare("UPDATE registrations SET payment_status = 'verified' WHERE id = ?");
    $result = $stmt->execute([$testId]);
    $rowCount = $stmt->rowCount();
    
    echo "<p>Query executed: " . ($result ? '<span style="color: green;">YES</span>' : '<span style="color: red;">NO</span>') . "</p>";
    echo "<p>Rows affected: <strong>{$rowCount}</strong></p>";
    
    if ($rowCount > 0) {
        // Check if value was actually saved
        $stmt = $pdo->prepare("SELECT payment_status FROM registrations WHERE id = ?");
        $stmt->execute([$testId]);
        $result = $stmt->fetch();
        echo "<p>Value after update: <strong style='color: blue;'>" . ($result['payment_status'] ?? '(NULL)') . "</strong></p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='admin.php?page=registrations'>← Back to Registrations</a></p>";
?>