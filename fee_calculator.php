<?php
/**
 * fee_calculator.php - Centralized fee calculation logic
 * 
 * New Fee Structure:
 * - 1 class per week: RM30
 * - 2 classes per week: RM30 + RM27 = RM57
 * - 3 classes per week: RM30 + RM27 + RM24 = RM81
 * - 4 classes per week: RM30 + RM27 + RM24 + RM21 = RM102
 */

/**
 * Calculate the base weekly fee based on number of classes
 */
function getWeeklyFee($numberOfClasses) {
    switch ($numberOfClasses) {
        case 1:
            return 30.00;
        case 2:
            return 57.00; // RM30 + RM27
        case 3:
            return 81.00; // RM30 + RM27 + RM24
        case 4:
            return 102.00; // RM30 + RM27 + RM24 + RM21
        default:
            return 0.00;
    }
}

/**
 * Get fee breakdown for display
 */
function getFeeBreakdown($numberOfClasses) {
    switch ($numberOfClasses) {
        case 1:
            return 'RM30';
        case 2:
            return 'RM30 + RM27 = RM57';
        case 3:
            return 'RM30 + RM27 + RM24 = RM81';
        case 4:
            return 'RM30 + RM27 + RM24 + RM21 = RM102';
        default:
            return 'N/A';
    }
}

/**
 * Calculate number of classes for a specific day in a month after holiday deductions
 * 
 * @param string $dayOfWeek - e.g., "Tuesday", "Wednesday"
 * @param array $holidays - Array of holiday dates in Y-m-d format
 * @param int $monthNumber - Month (1-12)
 * @param int $yearNumber - Year (e.g., 2026)
 * @return int - Number of classes for that day
 */
function calculateClassesForDay($dayOfWeek, $holidays, $monthNumber, $yearNumber) {
    $dayMap = [
        'Sunday' => 0,
        'Monday' => 1,
        'Tuesday' => 2,
        'Wednesday' => 3,
        'Thursday' => 4,
        'Friday' => 5,
        'Saturday' => 6
    ];
    
    $dayNum = $dayMap[$dayOfWeek] ?? null;
    if ($dayNum === null) {
        error_log("calculateClassesForDay: Invalid day: $dayOfWeek");
        return 4; // Default fallback
    }
    
    // Get first and last day of the month
    $startDate = new DateTime("$yearNumber-$monthNumber-01");
    $endDate = new DateTime($startDate->format('Y-m-t'));
    
    // Get all dates matching the day of week
    $allDates = [];
    $currentDate = clone $startDate;
    while ($currentDate <= $endDate) {
        if ($currentDate->format('w') == $dayNum) {
            $dateStr = $currentDate->format('Y-m-d');
            $allDates[] = $dateStr;
        }
        $currentDate->modify('+1 day');
    }
    
    // Filter out holidays
    $validDates = array_filter($allDates, function($date) use ($holidays) {
        return !in_array($date, $holidays);
    });
    
    $monthName = $startDate->format('F Y');
    error_log("calculateClassesForDay: $dayOfWeek, $monthName: " . count($allDates) . " total, " . count($validDates) . " after holidays");
    
    return count($validDates);
}

/**
 * Load class holidays from database for a specific month
 * 
 * @param PDO $pdo - Database connection
 * @param int $monthNumber - Month (1-12)
 * @param int $yearNumber - Year (e.g., 2026)
 * @return array - Array of holiday dates in Y-m-d format
 */
function loadClassHolidays($pdo, $monthNumber, $yearNumber) {
    try {
        $stmt = $pdo->prepare("
            SELECT holiday_date 
            FROM class_holidays 
            WHERE MONTH(holiday_date) = ? AND YEAR(holiday_date) = ?
            ORDER BY holiday_date
        ");
        $stmt->execute([$monthNumber, $yearNumber]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        error_log("Error loading holidays: " . $e->getMessage());
        return [];
    }
}

/**
 * Calculate monthly fee for a student's enrolled classes
 * 
 * @param array $enrolledClasses - Array of class objects with 'day_of_week' property
 * @param array $classHolidays - Array of holiday dates
 * @param int $monthNumber - Month (1-12)
 * @param int $yearNumber - Year (e.g., 2026)
 * @return array - ['totalFee' => float, 'totalClasses' => int, 'breakdown' => array]
 */
function calculateMonthlyFee($enrolledClasses, $classHolidays, $monthNumber, $yearNumber) {
    if (empty($enrolledClasses)) {
        return [
            'totalFee' => 0,
            'totalClasses' => 0,
            'breakdown' => [],
            'weeklyFee' => 0,
            'numberOfWeeks' => 0
        ];
    }
    
    // Calculate classes per day
    $classesByDay = [];
    $totalClassesInMonth = 0;
    
    foreach ($enrolledClasses as $class) {
        $dayOfWeek = $class['day_of_week'];
        
        if (!isset($classesByDay[$dayOfWeek])) {
            $classCount = calculateClassesForDay($dayOfWeek, $classHolidays, $monthNumber, $yearNumber);
            $classesByDay[$dayOfWeek] = [
                'count' => $classCount,
                'className' => $class['class_name']
            ];
            $totalClassesInMonth += $classCount;
        }
    }
    
    // Get number of classes per week (unique days)
    $numberOfClassesPerWeek = count($classesByDay);
    
    // Get weekly fee
    $weeklyFee = getWeeklyFee($numberOfClassesPerWeek);
    
    // Calculate number of weeks (average weeks in month based on total classes)
    // We use the actual class count divided by classes per week
    $numberOfWeeks = $totalClassesInMonth / $numberOfClassesPerWeek;
    
    // Total fee = weekly fee × number of weeks
    $totalFee = $weeklyFee * $numberOfWeeks;
    
    // Build breakdown
    $breakdown = [];
    foreach ($classesByDay as $day => $data) {
        $breakdown[] = [
            'day' => $day,
            'className' => $data['className'],
            'classes' => $data['count']
        ];
    }
    
    return [
        'totalFee' => round($totalFee, 2),
        'totalClasses' => $totalClassesInMonth,
        'breakdown' => $breakdown,
        'weeklyFee' => $weeklyFee,
        'numberOfWeeks' => round($numberOfWeeks, 2),
        'classesPerWeek' => $numberOfClassesPerWeek,
        'feeBreakdown' => getFeeBreakdown($numberOfClassesPerWeek)
    ];
}

/**
 * Format fee calculation for display/invoice description
 */
function formatFeeDescription($feeData, $monthName) {
    $desc = "Monthly Fee - $monthName\n";
    $desc .= "Classes per week: {$feeData['classesPerWeek']}\n";
    $desc .= "Weekly fee: {$feeData['feeBreakdown']}\n";
    $desc .= "Number of weeks: {$feeData['numberOfWeeks']}\n\n";
    $desc .= "Class breakdown:\n";
    
    foreach ($feeData['breakdown'] as $item) {
        $desc .= "- {$item['day']}: {$item['className']} ({$item['classes']} classes)\n";
    }
    
    $desc .= "\nTotal: {$feeData['totalClasses']} classes";
    
    return $desc;
}
?>
