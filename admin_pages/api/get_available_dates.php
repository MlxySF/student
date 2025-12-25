<?php
// Initialize session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once '../../config.php';

// Set JSON header
header('Content-Type: application/json');

// Get parameters
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$class_schedule = isset($_GET['class_schedule']) ? $_GET['class_schedule'] : '';

// Validate month and year
if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
    echo json_encode(['success' => false, 'message' => 'Invalid month or year']);
    exit();
}

try {
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
        $day_name = date('l', strtotime($date));
        
        $all_dates[] = [
            'date' => $date,
            'day' => $day,
            'day_of_week' => $day_of_week,
            'day_name' => $day_name,
            'is_holiday' => in_array($date, $holidays)
        ];
        
        // If not a holiday, add to available dates
        if (!in_array($date, $holidays)) {
            // If class_schedule is provided, filter by day of week
            if ($class_schedule) {
                // Parse class schedule (e.g., "Monday", "Tuesday", etc.)
                $schedule_days = explode(',', $class_schedule);
                $schedule_days = array_map('trim', $schedule_days);
                
                if (in_array($day_name, $schedule_days)) {
                    $available_dates[] = [
                        'date' => $date,
                        'day' => $day,
                        'day_name' => $day_name,
                        'formatted' => date('D, d M Y', strtotime($date))
                    ];
                }
            } else {
                // No schedule filter, all non-holiday dates are available
                $available_dates[] = [
                    'date' => $date,
                    'day' => $day,
                    'day_name' => $day_name,
                    'formatted' => date('D, d M Y', strtotime($date))
                ];
            }
        }
    }
    
    // Calculate class count
    $class_count = count($available_dates);
    
    echo json_encode([
        'success' => true,
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