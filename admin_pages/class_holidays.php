<?php
// Initialize session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once '../config.php';
require_once '../security.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Get current month and year, or from query params
$current_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$current_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

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
        .calendar-day {
            padding: 10px;
            border: 1px solid #ddd;
            min-height: 80px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .calendar-day:hover {
            background-color: #f8f9fa;
        }
        .calendar-day.holiday {
            background-color: #fee;
            border-color: #f88;
        }
        .calendar-day.today {
            border: 2px solid #007bff;
        }
        .calendar-header {
            background-color: #007bff;
            color: white;
            padding: 10px;
            font-weight: bold;
        }
        .stats-card {
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-calendar-times"></i> Class Holidays Management</h2>
                    <a href="../admin.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Admin
                    </a>
                </div>

                <!-- Month/Year Selector -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label">Month</label>
                                <select name="month" class="form-select">
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?php echo $m; ?>" <?php echo $m == $current_month ? 'selected' : ''; ?>>
                                            <?php echo $month_names[$m]; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Year</label>
                                <select name="year" class="form-select">
                                    <?php for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++): ?>
                                        <option value="<?php echo $y; ?>" <?php echo $y == $current_year ? 'selected' : ''; ?>>
                                            <?php echo $y; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> View
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card stats-card">
                            <div class="card-body">
                                <h6 class="text-muted">Total Days in Month</h6>
                                <h3><?php echo $total_days; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stats-card" style="border-left-color: #dc3545;">
                            <div class="card-body">
                                <h6 class="text-muted">Holidays/Non-Class Days</h6>
                                <h3><?php echo count($holidays); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stats-card" style="border-left-color: #28a745;">
                            <div class="card-body">
                                <h6 class="text-muted">Available Class Days</h6>
                                <h3><?php echo $available_days; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calendar View -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo $month_names[$current_month] . ' ' . $current_year; ?> Calendar</h5>
                        <small class="text-muted">Click on any date to mark/unmark as holiday</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <!-- Day headers -->
                            <?php
                            $day_names = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                            foreach ($day_names as $day) {
                                echo '<div class="col calendar-header text-center">' . $day . '</div>';
                            }
                            ?>
                        </div>
                        <div class="row g-2 mt-1">
                            <?php
                            // Get first day of month (0 = Sunday, 6 = Saturday)
                            $first_day = date('w', strtotime("$current_year-$current_month-1"));
                            
                            // Empty cells before first day
                            for ($i = 0; $i < $first_day; $i++) {
                                echo '<div class="col"></div>';
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
                                
                                echo '<div class="col">';
                                echo '<div class="' . $classes . '" onclick="toggleHoliday(\'' . $date . '\')">';
                                echo '<strong>' . $day . '</strong>';
                                if ($is_holiday) {
                                    // Find the holiday reason
                                    $reason = '';
                                    foreach ($holidays as $h) {
                                        if ($h['holiday_date'] == $date) {
                                            $reason = $h['reason'];
                                            break;
                                        }
                                    }
                                    echo '<br><small class="text-danger"><i class="fas fa-times-circle"></i> ' . htmlspecialchars($reason) . '</small>';
                                }
                                echo '</div>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Holidays List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Holidays & Non-Class Days List</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($holidays) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Day</th>
                                            <th>Reason</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($holidays as $holiday): ?>
                                            <tr>
                                                <td><?php echo date('d M Y', strtotime($holiday['holiday_date'])); ?></td>
                                                <td><?php echo date('l', strtotime($holiday['holiday_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($holiday['reason']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteHoliday('<?php echo $holiday['holiday_date']; ?>')">
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
            </div>
        </div>
    </div>

    <!-- Modal for adding holiday -->
    <div class="modal fade" id="holidayModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Mark as Holiday/Non-Class Day</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="holidayForm">
                        <input type="hidden" id="holiday_date" name="holiday_date">
                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="text" class="form-control" id="display_date" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <input type="text" class="form-control" id="reason" name="reason" 
                                   placeholder="e.g., Public Holiday, School Break, etc." required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveHoliday()">Save</button>
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
