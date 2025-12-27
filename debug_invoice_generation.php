<?php
// debug_invoice_generation.php - Diagnostic script
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

echo "<h2>🔍 Invoice Generation Diagnostic Report</h2>";
echo "<hr>";

// 1. Check Students Table
echo "<h3>1️⃣ Students Table (first 5 rows)</h3>";
$stmt = $pdo->query("SELECT id, student_id, full_name FROM students LIMIT 5");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($students);
echo "</pre>";

// 2. Check Registrations Table
echo "<h3>2️⃣ Registrations Table (first 5 rows)</h3>";
$stmt = $pdo->query("SELECT id, registration_number, payment_status FROM registrations LIMIT 5");
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($registrations);
echo "</pre>";

// 3. Check Enrollments Table
echo "<h3>3️⃣ Enrollments Table (first 5 rows)</h3>";
$stmt = $pdo->query("SELECT id, student_id, class_id, status FROM enrollments LIMIT 5");
$enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($enrollments);
echo "</pre>";

// 4. Test the JOIN query
echo "<h3>4️⃣ Testing JOIN Query (Current Logic)</h3>";
$stmt = $pdo->prepare("
    SELECT DISTINCT 
        s.id as student_id, 
        s.student_id as student_number, 
        s.full_name,
        r.registration_number,
        r.payment_status,
        e.status as enrollment_status
    FROM students s
    INNER JOIN enrollments e ON s.id = e.student_id
    INNER JOIN registrations r ON s.student_id = r.registration_number
    WHERE r.payment_status = 'paid' AND e.status = 'active'
    ORDER BY s.full_name
");
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<p><strong>Result Count: " . count($results) . "</strong></p>";
echo "<pre>";
print_r($results);
echo "</pre>";

// 5. Check if student_id matches registration_number
echo "<h3>5️⃣ Checking Match Between student_id and registration_number</h3>";
$stmt = $pdo->query("
    SELECT 
        s.student_id as student_student_id,
        r.registration_number as reg_registration_number,
        CASE WHEN s.student_id = r.registration_number THEN '✅ MATCH' ELSE '❌ NO MATCH' END as match_status
    FROM students s
    LEFT JOIN registrations r ON s.student_id = r.registration_number
    LIMIT 10
");
$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($matches);
echo "</pre>";

// 6. Alternative: Check if we should join on student ID instead
echo "<h3>6️⃣ Alternative JOIN (using students.id = registrations.student_id if column exists)</h3>";
try {
    $stmt = $pdo->query("
        SELECT 
            s.id,
            s.student_id as student_number,
            s.full_name,
            r.student_id as reg_student_id,
            r.payment_status
        FROM students s
        INNER JOIN registrations r ON s.id = r.student_id
        WHERE r.payment_status = 'paid'
        LIMIT 5
    ");
    $alt_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p><strong>Alternative Result Count: " . count($alt_results) . "</strong></p>";
    echo "<pre>";
    print_r($alt_results);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>This means registrations table does NOT have a 'student_id' column</p>";
}

// 7. Show registrations table structure
echo "<h3>7️⃣ Registrations Table Structure</h3>";
$stmt = $pdo->query("DESCRIBE registrations");
$structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($structure);
echo "</pre>";

echo "<hr>";
echo "<p><strong>✅ Diagnostic Complete!</strong></p>";
echo "<p>Please share the output of sections 4, 5, and 7 to help identify the issue.</p>";
?>