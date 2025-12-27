<?php
require_once 'config.php';

// Create logs directory
$logsDir = __DIR__ . '/logs';
if (!file_exists($logsDir)) {
    mkdir($logsDir, 0755, true);
}

$logFile = $logsDir . '/invoice_generation.log';

function logMessage($msg) {
    global $logFile;
    $entry = "[" . date('Y-m-d H:i:s') . "] $msg\n";
    file_put_contents($logFile, $entry, FILE_APPEND);
    echo $entry;
}

logMessage("========== MONTHLY INVOICE GENERATION STARTED ==========");

$currentMonth = date('M Y'); // e.g., "Dec 2025"
$dueDate = date('Y-m-t'); // Last day of month

// Get all active enrollments
$enrollments = $pdo->query("
    SELECT 
        e.student_id, e.class_id,
        s.full_name, s.student_id as student_number,
        c.class_name, c.class_code, c.monthly_fee
    FROM enrollments e
    JOIN students s ON e.student_id = s.id
    JOIN classes c ON e.class_id = c.id
    WHERE e.status = 'active'
")->fetchAll();

logMessage("Found " . count($enrollments) . " active enrollments");

$created = 0;
$skipped = 0;

foreach ($enrollments as $enroll) {
    // Check if invoice already exists for this month
    $check = $pdo->prepare("
        SELECT id FROM invoices 
        WHERE student_id = ? AND class_id = ?
        AND description LIKE ?
        AND MONTH(created_at) = MONTH(NOW())
        AND YEAR(created_at) = YEAR(NOW())
    ");
    $check->execute([$enroll['student_id'], $enroll['class_id'], "%$currentMonth%"]);
    
    if ($check->fetch()) {
        $skipped++;
        continue;
    }
    
    // Create invoice
    $invoiceNumber = 'INV-' . date('Ym') . '-' . rand(1000, 9999);
    $description = "Monthly Fee: {$enroll['class_name']} - $currentMonth";
    
    $pdo->prepare("
        INSERT INTO invoices (
            invoice_number, student_id, class_id, invoice_type,
            description, amount, due_date, status, created_at
        ) VALUES (?, ?, ?, 'monthly', ?, ?, ?, 'unpaid', NOW())
    ")->execute([
        $invoiceNumber,
        $enroll['student_id'],
        $enroll['class_id'],
        $description,
        $enroll['monthly_fee'],
        $dueDate
    ]);
    
    logMessage("✓ Created: $invoiceNumber for {$enroll['full_name']} - {$enroll['class_name']}");
    $created++;
}

logMessage("SUMMARY: Created=$created, Skipped=$skipped");
logMessage("========== COMPLETED ==========");
?>
