<?php
// Initialize session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once '../../config.php';

// Set JSON header
header('Content-Type: application/json');

// Get parameters - DEFAULT TO JANUARY 2026
$month = isset($_GET['month']) ? intval($_GET['month']) : 1;  // Changed: Default to January
$year = isset($_GET['year']) ? intval($_GET['year']) : 2026;  // Changed: Default to 2026
$class_code = isset($_GET['class_code']) ? $_GET['class_code'] : '';

// Validate month and year
if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
    echo json_encode(['success' => false, 'message' => 'Invalid month or year']);
    exit();
}

try {
    // Extract day from class_code (e.g., "wsa-sun-10am" -> "Sunday")
    // Map short day names to full names
    $dayMap = [
        'sun' => 'Sunday',
        'mon' => 'Monday',
        'tue' => 'Tuesday',
        'wed' => 'Wednesday',
        'thu' => 'Thursday',
        'fri' => 'Friday',
        'sat' => 'Saturday'
    ];
    
    $dayName = '';
    if ($class_code) {
        // Extract day part from class code (e.g., "wsa-sun-10am" -> "sun")
        $parts = explode('-', $class_code);
        if (count($parts) >= 2) {
            $dayShort = strtolower($parts[1]); // Get the day part (sun, mon, tue, etc.)
            if (isset($dayMap[$dayShort])) {
                $dayName = $dayMap[$dayShort];
            }
        }
    }
    
    // Get holidays for the specified month
    $holidays_query = "SELECT holiday_date FROM class_holidays 
                       WHERE MONTH(holiday_date) = :month AND YEAR(holiday_date) = :year";
    $stmt = $pdo->prepare($holidays_query);
    $stmt->execute(['month' => $month, 'year' => $year]);
    
    $holidays = [];
    while ($row = $stmt->fetch()) {
        $holidays[] = $row['holiday_date'];
    }
    
    // Calculate total days in month
    $total_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    
    // Generate all dates in the month
    $all_dates = [];
    $available_dates = [];
    
    for ($day = 1; $day <= $total_days; $day++) {
        $date = sprintf("%04d-%02d-%02d", $year, $month, $day);
        $day_of_week = date('w', strtotime($date)); // 0 = Sunday, 6 = Saturday
        $current_day_name = date('l', strtotime($date));
        
        $all_dates[] = [
            'date' => $date,
            'day' => $day,
            'day_of_week' => $day_of_week,
            'day_name' => $current_day_name,
            'is_holiday' => in_array($date, $holidays)
        ];
        
        // If not a holiday and matches the class day, add to available dates
        if (!in_array($date, $holidays)) {
            if ($dayName && $current_day_name === $dayName) {
                $available_dates[] = [
                    'date' => $date,
                    'day' => $day,
                    'day_name' => $current_day_name,
                    'formatted' => date('D, d M Y', strtotime($date))
                ];
            } elseif (!$dayName) {
                // No specific day filter, return all non-holiday dates
                $available_dates[] = [
                    'date' => $date,
                    'day' => $day,
                    'day_name' => $current_day_name,
                    'formatted' => date('D, d M Y', strtotime($date))
                ];
            }
        }
    }
    
    // Calculate class count
    $class_count = count($available_dates);
    
    echo json_encode([
        'success' => true,
        'class_code' => $class_code,
        'day_name' => $dayName,
        'month' => $month,
        'year' => $year,
        'total_days' => $total_days,
        'holidays_count' => count($holidays),
        'holidays' => $holidays,
        'class_count' => $class_count,
        'available_dates' => $available_dates,
        'all_dates' => $all_dates
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>