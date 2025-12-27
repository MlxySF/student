<?php
/**
 * cron_monthly_invoices.php - Automated Monthly Invoice Generation
 * 
 * PURPOSE: Auto-generates monthly invoices for all students with approved registrations
 * SCHEDULE: Run on 1st of each month via cron job
 * 
 * UPDATED 2025-12-28: Generate ONE combined invoice per student (not per class)
 * - Calculates fees using same logic as fee_calculator.js
 * - Creates single invoice with breakdown of all classes
 * - Sends email notification to parent
 */

require_once 'config.php';

// Create logs directory
$logsDir = __DIR__ . '/logs';
if (!file_exists($logsDir)) {
    mkdir($logsDir, 0755, true);
}

$logFile = $logsDir . '/invoice_generation_' . date('Y-m') . '.log';

function logMessage($msg) {
    global $logFile;
    $entry = "[" . date('Y-m-d H:i:s') . "] $msg\n";
    file_put_contents($logFile, $entry, FILE_APPEND);
    echo $entry;
}

/**
 * Calculate student monthly fee using same logic as fee_calculator.js
 * Returns: ['total_fee', 'num_classes', 'total_sessions', 'breakdown']
 */
function calculateStudentMonthlyFee($pdo, $student_id, $month, $year) {
    // Get all active class enrollments for student
    $stmt = $pdo->prepare("
        SELECT 
            c.id as class_id,
            c.class_name,
            c.class_code,
            c.monthly_fee,
            c.day_of_week,
            c.price_per_session
        FROM enrollments e
        INNER JOIN classes c ON e.class_id = c.id
        WHERE e.student_id = ? AND e.status = 'active'
        ORDER BY c.class_name
    ");
    $stmt->execute([$student_id]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($classes)) {
        return [
            'total_fee' => 0,
            'num_classes' => 0,
            'total_sessions' => 0,
            'breakdown' => []
        ];
    }
    
    $breakdown = [];
    $totalFee = 0;
    $totalSessions = 0;
    
    // Count sessions for each class in the month
    foreach ($classes as $class) {
        $sessionCount = countSessionsForMonth($class['day_of_week'], $month, $year);
        
        if ($sessionCount > 0) {
            $classFee = $sessionCount * $class['price_per_session'];
            $totalFee += $classFee;
            $totalSessions += $sessionCount;
            
            $breakdown[] = [
                'class_id' => $class['class_id'],
                'class_name' => $class['class_name'],
                'class_code' => $class['class_code'],
                'day_of_week' => $class['day_of_week'],
                'session_count' => $sessionCount,
                'price_per_session' => $class['price_per_session'],
                'class_fee' => $classFee
            ];
        }
    }
    
    // Sort breakdown by session count (descending) then by class name
    usort($breakdown, function($a, $b) {
        if ($a['session_count'] != $b['session_count']) {
            return $b['session_count'] - $a['session_count'];
        }
        return strcmp($a['class_name'], $b['class_name']);
    });
    
    // Add position numbers
    foreach ($breakdown as $i => &$item) {
        $item['position'] = $i + 1;
    }
    
    return [
        'total_fee' => $totalFee,
        'num_classes' => count($breakdown),
        'total_sessions' => $totalSessions,
        'breakdown' => $breakdown
    ];
}

/**
 * Count number of sessions for a specific day of week in a month
 */
function countSessionsForMonth($dayOfWeek, $month, $year) {
    $dayMap = [
        'Sunday' => 0, 'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3,
        'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6
    ];
    
    if (!isset($dayMap[$dayOfWeek])) {
        return 0;
    }
    
    $targetDay = $dayMap[$dayOfWeek];
    $count = 0;
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $timestamp = mktime(0, 0, 0, $month, $day, $year);
        if (date('w', $timestamp) == $targetDay) {
            $count++;
        }
    }
    
    return $count;
}

logMessage("========== MONTHLY INVOICE GENERATION STARTED ==========");

$current_month_name = date('M Y'); // e.g., "Dec 2025"
$month = date('n'); // Month as number (1-12)
$year = date('Y');
$due_date = date('Y-m-10'); // Due on the 10th of the month

$generated_count = 0;
$skipped_count = 0;
$emailsSent = 0;
$emailsFailed = 0;
$total_amount = 0;

logMessage("[Generate Monthly Invoices] Starting for {$current_month_name}");

try {
    // Get all students with approved registrations and active enrollments
    // FIXED: Join registrations using students.student_id = registrations.registration_number
    $stmt = $pdo->prepare("
        SELECT DISTINCT s.id as student_id, s.full_name, s.student_id as student_number
        FROM students s
        INNER JOIN enrollments e ON s.id = e.student_id
        INNER JOIN registrations r ON s.student_id = r.registration_number
        WHERE r.payment_status = 'approved' AND e.status = 'active'
        ORDER BY s.full_name
    ");
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    logMessage("[Generate Monthly Invoices] Found " . count($students) . " students with paid registration and active enrollments");
    
    foreach ($students as $student) {
        $student_id = $student['student_id'];
        $student_name = $student['full_name'];
        
        // Check if invoice already exists for this student and month
        $check_stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM invoices 
            WHERE student_id = ? 
              AND invoice_type = 'monthly_fee' 
              AND payment_month = ?
        ");
        $check_stmt->execute([$student_id, $current_month_name]);
        $existing = $check_stmt->fetch();
        
        if ($existing['count'] > 0) {
            logMessage("[Generate Monthly Invoices] Skipping {$student_name} - invoice already exists for {$current_month_name}");
            $skipped_count++;
            continue;
        }
        
        // Calculate fee using the same logic as fee_calculator.js
        $feeData = calculateStudentMonthlyFee($pdo, $student_id, $month, $year);
        
        // Skip if no active enrollments or zero fee
        if ($feeData['total_fee'] <= 0) {
            logMessage("[Generate Monthly Invoices] Skipping {$student_name} - no classes or zero fee");
            $skipped_count++;
            continue;
        }
        
        // Generate invoice number
        $invoice_number = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Build description with breakdown
        $description = "Monthly Fee - {$current_month_name}\n";
        $description .= "Total: {$feeData['num_classes']} class(es), {$feeData['total_sessions']} session(s)\n\n";
        $description .= "Breakdown (sorted by sessions):\n";
        
        foreach ($feeData['breakdown'] as $item) {
            $description .= sprintf(
                "%d. %s (%s): %d sessions × RM%.2f = RM%.2f\n",
                $item['position'],
                $item['class_name'],
                $item['day_of_week'],
                $item['session_count'],
                $item['price_per_session'],
                $item['class_fee']
            );
        }
        
        // Use the first class_id for the invoice (or null if multiple classes)
        $class_id = ($feeData['num_classes'] == 1) ? $feeData['breakdown'][0]['class_id'] : null;
        
        // Insert invoice
        $insert_stmt = $pdo->prepare("
            INSERT INTO invoices (
                student_id, 
                invoice_number, 
                invoice_type, 
                class_id, 
                description, 
                amount, 
                due_date, 
                payment_month, 
                status, 
                created_at
            ) VALUES (?, ?, 'monthly_fee', ?, ?, ?, ?, ?, 'unpaid', NOW())
        ");
        
        if ($insert_stmt->execute([
            $student_id,
            $invoice_number,
            $class_id,
            $description,
            $feeData['total_fee'],
            $due_date,
            $current_month_name
        ])) {
            $generated_count++;
            $total_amount += $feeData['total_fee'];
            
            logMessage("[Generate Monthly Invoices] ✅ Created {$invoice_number} for {$student_name}: RM" . number_format($feeData['total_fee'], 2));
            
            // Send email notification
            $invoiceId = $pdo->lastInsertId();
            if (file_exists(__DIR__ . '/send_invoice_notification.php')) {
                require_once __DIR__ . '/send_invoice_notification.php';
                try {
                    if (sendInvoiceNotification($pdo, $invoiceId)) {
                        $emailsSent++;
                        logMessage("[Email] Sent invoice notification to {$student_name}");
                    } else {
                        $emailsFailed++;
                        logMessage("[Email] Failed to send invoice notification to {$student_name}");
                    }
                } catch (Exception $e) {
                    $emailsFailed++;
                    logMessage("[Generate Monthly Invoices] Email error for {$student_name}: " . $e->getMessage());
                }
            }
        } else {
            logMessage("[Generate Monthly Invoices] ❌ Failed to create invoice for {$student_name}");
        }
    }
    
    // Build detailed success message
    $message = "Generated {$generated_count} monthly invoices for {$current_month_name}!";
    
    if ($generated_count > 0) {
        $message .= " Total amount: RM" . number_format($total_amount, 2);
    }
    
    if ($skipped_count > 0) {
        $message .= " | Skipped {$skipped_count} (already exists or no classes)";
    }
    
    if ($emailsSent > 0 || $emailsFailed > 0) {
        $message .= " | Emails: {$emailsSent} sent, {$emailsFailed} failed";
    }
    
    logMessage("[Generate Monthly Invoices] Completed: {$message}");
    logMessage("========== COMPLETED ==========");
    
} catch (Exception $e) {
    logMessage("[Generate Monthly Invoices] Error: " . $e->getMessage());
    logMessage("========== FAILED ==========");
}
?>