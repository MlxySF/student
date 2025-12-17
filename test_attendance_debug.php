<?php
session_start();
require_once 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    die('Not logged in');
}

echo "<h2>Attendance Debug Test</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>POST Data Received:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    $action = $_POST['action'] ?? '';
    $class_id = $_POST['class_id'] ?? null;
    $attendance_date = $_POST['attendance_date'] ?? null;
    $attendance_data = $_POST['attendance'] ?? [];
    
    echo "<h3>Extracted Values:</h3>";
    echo "Action: $action<br>";
    echo "Class ID: $class_id<br>";
    echo "Date: $attendance_date<br>";
    echo "Attendance Data:<br>";
    echo "<pre>";
    print_r($attendance_data);
    echo "</pre>";
    
    if ($action === 'bulk_attendance' && !empty($attendance_data)) {
        try {
            $pdo->beginTransaction();
            
            echo "<h3>Processing Each Student:</h3>";
            $marked_count = 0;
            
            foreach ($attendance_data as $student_id => $status) {
                echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px;'>";
                echo "Student ID: <strong>$student_id</strong><br>";
                echo "Status: <strong>$status</strong><br>";
                
                // Validate status
                $valid_statuses = ['present', 'absent', 'late', 'excused'];
                if (!in_array($status, $valid_statuses)) {
                    echo "<span style='color: red;'>❌ Invalid status - skipping</span>";
                    echo "</div>";
                    continue;
                }
                
                // Check if exists
                $checkStmt = $pdo->prepare("SELECT id, status FROM attendance WHERE student_id = ? AND class_id = ? AND attendance_date = ?");
                $checkStmt->execute([$student_id, $class_id, $attendance_date]);
                $existing = $checkStmt->fetch();
                
                if ($existing) {
                    echo "Found existing record (ID: {$existing['id']}, Current status: {$existing['status']})<br>";
                    
                    // Update
                    $updateStmt = $pdo->prepare("UPDATE attendance SET status = ? WHERE student_id = ? AND class_id = ? AND attendance_date = ?");
                    $updateStmt->execute([$status, $student_id, $class_id, $attendance_date]);
                    
                    echo "<span style='color: blue;'>✓ Updated to: $status</span><br>";
                    
                    // Verify update
                    $verifyStmt = $pdo->prepare("SELECT status FROM attendance WHERE student_id = ? AND class_id = ? AND attendance_date = ?");
                    $verifyStmt->execute([$student_id, $class_id, $attendance_date]);
                    $verified = $verifyStmt->fetch();
                    echo "Verified status in DB: <strong>{$verified['status']}</strong>";
                    
                } else {
                    echo "No existing record found<br>";
                    
                    // Insert
                    $insertStmt = $pdo->prepare("INSERT INTO attendance (student_id, class_id, attendance_date, status) VALUES (?, ?, ?, ?)");
                    $insertStmt->execute([$student_id, $class_id, $attendance_date, $status]);
                    
                    echo "<span style='color: green;'>✓ Inserted new record with status: $status</span><br>";
                    echo "New record ID: " . $pdo->lastInsertId();
                }
                
                $marked_count++;
                echo "</div>";
            }
            
            $pdo->commit();
            echo "<h3 style='color: green;'>✅ SUCCESS: Transaction committed. Marked $marked_count students.</h3>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<h3 style='color: red;'>❌ ERROR: " . $e->getMessage() . "</h3>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
    } else {
        echo "<p style='color: red;'>No attendance data to process or wrong action</p>";
    }
    
} else {
    // Show a test form
    $selected_class = $_GET['class_id'] ?? null;
    $selected_date = $_GET['date'] ?? date('Y-m-d');
    
    if ($selected_class) {
        $stmt = $pdo->prepare("
            SELECT s.id, s.student_id, s.full_name, a.status as attendance_status
            FROM enrollments e
            JOIN students s ON e.student_id = s.id
            LEFT JOIN attendance a ON a.student_id = s.id AND a.class_id = ? AND a.attendance_date = ?
            WHERE e.class_id = ? AND e.status = 'active'
            ORDER BY s.full_name
            LIMIT 3
        ");
        $stmt->execute([$selected_class, $selected_date, $selected_class]);
        $students = $stmt->fetchAll();
        
        if (count($students) > 0) {
            echo "<h3>Test Form (First 3 Students)</h3>";
            echo "<form method='POST'>";
            echo "<input type='hidden' name='action' value='bulk_attendance'>";
            echo "<input type='hidden' name='class_id' value='$selected_class'>";
            echo "<input type='hidden' name='attendance_date' value='$selected_date'>";
            
            echo "<table border='1' cellpadding='10'>";
            echo "<tr><th>Student ID</th><th>Name</th><th>Current Status</th><th>New Status</th></tr>";
            
            foreach ($students as $s) {
                $current = $s['attendance_status'] ?? 'not set';
                echo "<tr>";
                echo "<td>{$s['student_id']}</td>";
                echo "<td>{$s['full_name']}</td>";
                echo "<td>$current</td>";
                echo "<td>";
                echo "<select name='attendance[{$s['id']}]'>";
                echo "<option value='present'>Present</option>";
                echo "<option value='absent'>Absent</option>";
                echo "<option value='late'>Late</option>";
                echo "<option value='excused'>Excused</option>";
                echo "</select>";
                echo "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            echo "<br><button type='submit' style='padding: 10px 20px; font-size: 16px;'>Test Save Attendance</button>";
            echo "</form>";
        } else {
            echo "<p>No students found. <a href='?class_id=1&date=$selected_date'>Try class_id=1</a></p>";
        }
    } else {
        echo "<p>Select a class: <a href='?class_id=1&date=" . date('Y-m-d') . "'>Test with class_id=1</a></p>";
    }
}

echo "<hr>";
echo "<a href='admin.php?page=attendance'>← Back to Attendance Page</a>";
?>