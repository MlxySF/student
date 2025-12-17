<?php
/**
 * fix_student_status_encoding.php
 * 
 * This script updates all student_status values in the database to include Chinese characters.
 * Run this once to fix the encoding issue.
 * 
 * Access this file in your browser: http://yourdomain.com/fix_student_status_encoding.php
 */

require_once 'config.php';

// Set UTF-8 encoding
header('Content-Type: text/html; charset=UTF-8');

echo "<!DOCTYPE html>";
echo "<html lang='en'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Fix Student Status Encoding</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }";
echo ".success { color: green; background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px; }";
echo ".error { color: red; background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 5px; }";
echo ".info { color: blue; background: #d1ecf1; padding: 10px; margin: 10px 0; border-radius: 5px; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<h1>🔧 Fix Student Status Encoding</h1>";
echo "<p>This script will update all student status values to include Chinese characters.</p>";

try {
    // Ensure UTF-8 connection
    $pdo->exec("SET NAMES utf8mb4");
    
    echo "<div class='info'>ℹ️ Starting database update...</div>";
    
    // Update students table
    echo "<h2>Updating Students Table</h2>";
    
    $updates = [
        'State Team' => 'State Team 州队',
        'Backup Team' => 'Backup Team 后备队',
        'Student' => 'Student 学生',
        'Normal Student' => 'Student 学生'
    ];
    
    $totalUpdated = 0;
    
    foreach ($updates as $oldStatus => $newStatus) {
        $stmt = $pdo->prepare("UPDATE students SET student_status = ? WHERE student_status = ?");
        $stmt->execute([$newStatus, $oldStatus]);
        $affected = $stmt->rowCount();
        
        if ($affected > 0) {
            echo "<div class='success'>✅ Updated $affected record(s): '$oldStatus' → '$newStatus'</div>";
            $totalUpdated += $affected;
        }
    }
    
    // Update registrations table
    echo "<h2>Updating Registrations Table</h2>";
    
    $regUpdated = 0;
    
    foreach ($updates as $oldStatus => $newStatus) {
        $stmt = $pdo->prepare("UPDATE registrations SET status = ? WHERE status = ?");
        $stmt->execute([$newStatus, $oldStatus]);
        $affected = $stmt->rowCount();
        
        if ($affected > 0) {
            echo "<div class='success'>✅ Updated $affected registration(s): '$oldStatus' → '$newStatus'</div>";
            $regUpdated += $affected;
        }
    }
    
    echo "<h2>🎉 Update Complete!</h2>";
    echo "<div class='success'>";
    echo "<strong>Summary:</strong><br>";
    echo "- Students updated: $totalUpdated<br>";
    echo "- Registrations updated: $regUpdated<br>";
    echo "</div>";
    
    echo "<h3>Verification</h3>";
    
    // Show current status values
    $stmt = $pdo->query("SELECT DISTINCT student_status, COUNT(*) as count FROM students GROUP BY student_status");
    $statuses = $stmt->fetchAll();
    
    echo "<div class='info'>";
    echo "<strong>Current Student Statuses in Database:</strong><ul>";
    foreach ($statuses as $status) {
        echo "<li>" . htmlspecialchars($status['student_status']) . " (₱ " . $status['count'] . " students)</li>";
    }
    echo "</ul></div>";
    
    echo "<p><strong>🚨 Important:</strong> After running this script, refresh your admin and student portals to see the changes.</p>";
    echo "<p><strong>🛡️ Security:</strong> Delete or rename this file after use to prevent unauthorized access.</p>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body>";
echo "</html>";
?>