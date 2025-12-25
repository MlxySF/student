<?php
// Initialize session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Get current month and year, or from query params
$current_month = isset($_GET['month']) ? intval($_GET['month']) : 1;  // Default to January
$current_year = isset($_GET['year']) ? intval($_GET['year']) : 2026;  // Default to 2026

// Fetch existing holidays for the selected month
$holidays_query = "SELECT * FROM class_holidays 
                   WHERE MONTH(holiday_date) = :month AND YEAR(holiday_date) = :year 
                   ORDER BY holiday_date";
$stmt = $pdo->prepare($holidays_query);
$stmt->execute(['month' => $current_month, 'year' => $current_year]);
$holidays = $stmt->fetchAll();

// Calculate available class days for the month
$total_days = cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);
$available_days = $total_days - count($holidays);

// Month names
$month_names = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

// Get the base URL for API calls
$base_url = rtrim(SITE_URL, '/');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Holidays Management - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            --info-gradient: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .calendar-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 15px;
        }

        .calendar-header-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .calendar-title {
            font-size: 28px;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .calendar-subtitle {
            color: #64748b;
            font-size: 14px;
        }

        /* Month/Year Selector */
        .selector-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        /* Statistics Cards */
        .stats-row {
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            margin-bottom: 15px;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary-gradient);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(0,0,0,0.15);
        }

        .stat-card.danger::before {
            background: var(--danger-gradient);
        }

        .stat-card.success::before {
            background: var(--success-gradient);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 15px;
            background: var(--primary-gradient);
            color: white;
        }

        .stat-card.danger .stat-icon {
            background: var(--danger-gradient);
        }

        .stat-card.success .stat-icon {
            background: var(--success-gradient);
        }

        .stat-value {
            font-size: 42px;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Calendar View */
        .calendar-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .calendar-card-header {
            text-align: center;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
            margin-bottom: 20px;
        }

        .calendar-month-title {
            font-size: 24px;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 5px;
        }

        .calendar-instruction {
            color: #64748b;
            font-size: 13px;
        }

        /* Day Headers */
        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            margin-bottom: 10px;
        }

        .weekday-header {
            text-align: center;
            font-weight: 700;
            font-size: 12px;
            color: white;
            padding: 10px 5px;
            border-radius: 10px;
            background: var(--primary-gradient);
        }

        /* Calendar Grid */
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
        }

        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            padding: 8px 4px;
            position: relative;
            min-height: 60px;
        }

        .calendar-day:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-color: #667eea;
        }

        .calendar-day.holiday {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-color: #ef4444;
        }

        .calendar-day.holiday:hover {
            background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
            border-color: #dc2626;
        }

        .calendar-day.today {
            border: 3px solid #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        .calendar-day.today::after {
            content: 'Today';
            position: absolute;
            top: 3px;
            right: 3px;
            background: #667eea;
            color: white;
            font-size: 7px;
            padding: 2px 4px;
            border-radius: 4px;
            font-weight: 700;
        }

        .day-number {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 3px;
        }

        .holiday .day-number {
            color: #dc2626;
        }

        .holiday-reason {
            font-size: 9px;
            color: #dc2626;
            text-align: center;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 2px;
            line-height: 1.2;
            word-break: break-word;
        }

        /* Holidays List */
        .holidays-list-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 20px;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }

        .table thead th {
            border: none;
            color: #1e293b;
            font-weight: 700;
            padding: 15px;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            transition: all 0.3s;
        }

        .table tbody tr:hover {
            background: #f8fafc;
            transform: scale(1.01);
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-color: #f1f5f9;
        }

        .btn-danger {
            background: var(--danger-gradient);
            border: none;
            border-radius: 10px;
            padding: 8px 16px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
        }

        /* Modal */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .modal-header {
            border-bottom: 2px solid #f1f5f9;
            padding: 25px 30px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 20px 20px 0 0;
        }

        .modal-title {
            font-weight: 700;
            color: #1e293b;
        }

        .modal-body {
            padding: 30px;
        }

        .modal-footer {
            border-top: 2px solid #f1f5f9;
            padding: 20px 30px;
        }

        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .btn-secondary {
            background: #64748b;
            border: none;
            border-radius: 12px;
            padding: 10px 25px;
            font-weight: 600;
        }

        .alert {
            border-radius: 15px;
            border: none;
            padding: 20px;
            font-weight: 600;
        }

        .alert-info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
        }

        /* Mobile Optimizations */
        @media (max-width: 768px) {
            .calendar-container {
                padding: 10px;
            }

            .calendar-header-card {
                padding: 15px;
                margin-bottom: 15px;
            }

            .calendar-title {
                font-size: 20px;
            }

            .calendar-subtitle {
                font-size: 12px;
            }

            .selector-card {
                padding: 15px;
                margin-bottom: 15px;
            }

            .btn-primary {
                padding: 10px 20px;
            }

            .stat-card {
                padding: 15px;
                margin-bottom: 10px;
            }

            .stat-icon {
                width: 50px;
                height: 50px;
                font-size: 22px;
                margin-bottom: 10px;
            }

            .stat-value {
                font-size: 32px;
            }

            .stat-label {
                font-size: 11px;
            }

            .calendar-card {
                padding: 15px;
                margin-bottom: 15px;
            }

            .calendar-month-title {
                font-size: 20px;
            }

            .calendar-instruction {
                font-size: 11px;
            }

            .calendar-weekdays {
                gap: 4px;
                margin-bottom: 8px;
            }

            .weekday-header {
                font-size: 10px;
                padding: 8px 2px;
                border-radius: 8px;
            }

            .calendar-grid {
                gap: 4px;
            }

            .calendar-day {
                border-radius: 8px;
                padding: 4px 2px;
                min-height: 50px;
                border-width: 1.5px;
            }

            .calendar-day.today {
                border-width: 2px;
                box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
            }

            .calendar-day.today::after {
                font-size: 6px;
                padding: 1px 3px;
                top: 2px;
                right: 2px;
            }

            .day-number {
                font-size: 13px;
                margin-bottom: 2px;
            }

            .holiday-reason {
                font-size: 7px;
                gap: 1px;
            }

            .holiday-reason i {
                font-size: 7px;
            }

            .holidays-list-card {
                padding: 15px;
            }

            .holidays-list-card h4 {
                font-size: 18px;
            }

            .table thead th {
                font-size: 10px;
                padding: 10px 8px;
            }

            .table tbody td {
                font-size: 12px;
                padding: 10px 8px;
            }

            .btn-danger {
                padding: 6px 12px;
                font-size: 12px;
            }

            /* Make header buttons wrap better on mobile */
            .calendar-header-card .d-flex {
                flex-direction: column;
                gap: 10px;
            }

            .btn-secondary {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .calendar-container {
                padding: 8px;
            }

            .calendar-header-card,
            .selector-card,
            .calendar-card,
            .holidays-list-card {
                border-radius: 15px;
            }

            .calendar-title {
                font-size: 18px;
            }

            .weekday-header {
                font-size: 9px;
                padding: 6px 1px;
            }

            .calendar-day {
                min-height: 45px;
            }

            .day-number {
                font-size: 12px;
            }

            .holiday-reason {
                font-size: 6px;
            }

            .stat-value {
                font-size: 28px;
            }
        }

        /* Landscape phone optimization */
        @media (max-width: 768px) and (orientation: landscape) {
            .stats-row .col-md-4 {
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid calendar-container mt-4">
        <!-- Header -->
        <div class="calendar-header-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="calendar-title">
                        <i class="fas fa-calendar-times"></i> Class Holidays
                    </h1>
                    <p class="calendar-subtitle">Manage non-class days and holidays</p>
                </div>
                <a href="admin.php?page=dashboard" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <!-- Month/Year Selector -->
        <div class="selector-card">
            <form method="GET" action="admin.php" class="row g-3 align-items-end">
                <input type="hidden" name="page" value="holidays">
                <div class="col-md-5 col-12">
                    <label class="form-label fw-bold">
                        <i class="fas fa-calendar"></i> Select Month
                    </label>
                    <select name="month" class="form-select">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo $m == $current_month ? 'selected' : ''; ?>>
                                <?php echo $month_names[$m]; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-5 col-12">
                    <label class="form-label fw-bold">
                        <i class="fas fa-calendar-alt"></i> Select Year
                    </label>
                    <select name="year" class="form-select">
                        <?php for ($y = 2025; $y <= 2027; $y++): ?>
                            <option value="<?php echo $y; ?>" <?php echo $y == $current_year ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2 col-12">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> View
                    </button>
                </div>
            </form>
        </div>

        <!-- Statistics -->
        <div class="row stats-row">
            <div class="col-md-4 col-12">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_days; ?></div>
                    <div class="stat-label">Total Days in Month</div>
                </div>
            </div>
            <div class="col-md-4 col-12">
                <div class="stat-card danger">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <div class="stat-value"><?php echo count($holidays); ?></div>
                    <div class="stat-label">Holidays / Non-Class Days</div>
                </div>
            </div>
            <div class="col-md-4 col-12">
                <div class="stat-card success">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-value"><?php echo $available_days; ?></div>
                    <div class="stat-label">Available Class Days</div>
                </div>
            </div>
        </div>

        <!-- Calendar View -->
        <div class="calendar-card">
            <div class="calendar-card-header">
                <h2 class="calendar-month-title">
                    <?php echo $month_names[$current_month] . ' ' . $current_year; ?>
                </h2>
                <p class="calendar-instruction">
                    <i class="fas fa-mouse-pointer"></i> Click on any date to mark/unmark as holiday
                </p>
            </div>
            
            <!-- Weekday Headers -->
            <div class="calendar-weekdays">
                <?php
                $day_names = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                foreach ($day_names as $day) {
                    echo '<div class="weekday-header">' . $day . '</div>';
                }
                ?>
            </div>

            <!-- Calendar Grid -->
            <div class="calendar-grid">
                <?php
                // Get first day of month (0 = Sunday, 6 = Saturday)
                $first_day = date('w', strtotime("$current_year-$current_month-1"));
                
                // Empty cells before first day
                for ($i = 0; $i < $first_day; $i++) {
                    echo '<div></div>';
                }
                
                // Create array of holiday dates for easy checking
                $holiday_dates = array_column($holidays, 'holiday_date');
                
                // Days of month
                for ($day = 1; $day <= $total_days; $day++) {
                    $date = sprintf("%04d-%02d-%02d", $current_year, $current_month, $day);
                    $is_holiday = in_array($date, $holiday_dates);
                    $is_today = ($date == date('Y-m-d'));
                    
                    $classes = 'calendar-day';
                    if ($is_holiday) $classes .= ' holiday';
                    if ($is_today) $classes .= ' today';
                    
                    echo '<div class="' . $classes . '" onclick="toggleHoliday(\'' . $date . '\')">';
                    echo '<div class="day-number">' . $day . '</div>';
                    if ($is_holiday) {
                        // Find the holiday reason
                        $reason = '';
                        foreach ($holidays as $h) {
                            if ($h['holiday_date'] == $date) {
                                $reason = $h['reason'];
                                break;
                            }
                        }
                        echo '<div class="holiday-reason">';
                        echo '<i class="fas fa-times-circle"></i>';
                        echo '<span>' . htmlspecialchars($reason) . '</span>';
                        echo '</div>';
                    }
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <!-- Holidays List -->
        <div class="holidays-list-card">
            <h4 class="mb-4">
                <i class="fas fa-list"></i> Holidays & Non-Class Days List
            </h4>
            <?php if (count($holidays) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><i class="fas fa-calendar-day"></i> Date</th>
                                <th><i class="fas fa-clock"></i> Day</th>
                                <th><i class="fas fa-info-circle"></i> Reason</th>
                                <th><i class="fas fa-cog"></i> Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($holidays as $holiday): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo date('d M Y', strtotime($holiday['holiday_date'])); ?></strong>
                                    </td>
                                    <td><?php echo date('l', strtotime($holiday['holiday_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($holiday['reason']); ?></td>
                                    <td>
                                        <button class="btn btn-danger btn-sm" onclick="deleteHoliday('<?php echo $holiday['holiday_date']; ?>')">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No holidays marked for this month. Click on calendar dates to add holidays.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal for adding holiday -->
    <div class="modal fade" id="holidayModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-calendar-plus"></i> Mark as Holiday/Non-Class Day
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="holidayForm">
                        <input type="hidden" id="holiday_date" name="holiday_date">
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-calendar"></i> Date
                            </label>
                            <input type="text" class="form-control" id="display_date" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-edit"></i> Reason
                            </label>
                            <input type="text" class="form-control" id="reason" name="reason" 
                                   placeholder="e.g., Public Holiday, School Break, etc." required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-primary" onclick="saveHoliday()">
                        <i class="fas fa-save"></i> Save
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Use absolute URL for API calls
        const API_BASE_URL = '<?php echo $base_url; ?>/admin_pages/api';
        let holidayModal;

        document.addEventListener('DOMContentLoaded', function() {
            holidayModal = new bootstrap.Modal(document.getElementById('holidayModal'));
        });

        function toggleHoliday(date) {
            // Check if this date already has a holiday
            const holidayDates = <?php echo json_encode(array_column($holidays, 'holiday_date')); ?>;
            
            if (holidayDates.includes(date)) {
                deleteHoliday(date);
            } else {
                // Show modal to add holiday
                document.getElementById('holiday_date').value = date;
                document.getElementById('display_date').value = formatDate(date);
                document.getElementById('reason').value = '';
                holidayModal.show();
            }
        }

        function formatDate(dateStr) {
            const date = new Date(dateStr);
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            return date.toLocaleDateString('en-US', options);
        }

        function saveHoliday() {
            const date = document.getElementById('holiday_date').value;
            const reason = document.getElementById('reason').value;

            if (!reason) {
                alert('Please enter a reason for the holiday');
                return;
            }

            fetch(API_BASE_URL + '/save_class_holiday.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin', // Include cookies for session
                body: JSON.stringify({
                    action: 'add',
                    holiday_date: date,
                    reason: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    holidayModal.hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the holiday');
            });
        }

        function deleteHoliday(date) {
            if (!confirm('Are you sure you want to remove this holiday?')) {
                return;
            }

            fetch(API_BASE_URL + '/save_class_holiday.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin', // Include cookies for session
                body: JSON.stringify({
                    action: 'delete',
                    holiday_date: date
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the holiday');
            });
        }
    </script>
</body>
</html>