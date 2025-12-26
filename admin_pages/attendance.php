<?php
// Get all classes with schedule
$all_classes = $pdo->query("SELECT * FROM classes ORDER BY class_code")->fetchAll();

// Selected class and date
$selected_class = $_GET['class_id'] ?? null;
$selected_date = $_GET['date'] ?? date('Y-m-d');
$selected_month = $_GET['month'] ?? date('Y-m');

// Get class details for schedule restrictions
$class_details = null;
$date_validation_error = '';
if ($selected_class) {
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
    $stmt->execute([$selected_class]);
    $class_details = $stmt->fetch();
    
    // Validate selected date matches class schedule
    if ($class_details && $class_details['day_of_week']) {
        $selected_day_name = date('l', strtotime($selected_date));
        
        if ($selected_day_name !== $class_details['day_of_week']) {
            $date_validation_error = "Invalid date: This class only meets on {$class_details['day_of_week']}s. The selected date ({$selected_date}) is a {$selected_day_name}.";
            
            // Auto-correct to nearest valid date
            $day_map = [
                'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4,
                'Friday' => 5, 'Saturday' => 6, 'Sunday' => 0
            ];
            $target_day = $day_map[$class_details['day_of_week']];
            $current = strtotime($selected_date);
            
            // Find next occurrence of the class day
            for ($i = 0; $i < 7; $i++) {
                if (date('w', $current) == $target_day) {
                    $selected_date = date('Y-m-d', $current);
                    $date_validation_error .= " Redirecting to nearest {$class_details['day_of_week']}: " . date('M j, Y', strtotime($selected_date));
                    break;
                }
                $current = strtotime('+1 day', $current);
            }
        }
    }
}

// Get students enrolled in selected class
$enrolled_students = [];
if ($selected_class) {
    $stmt = $pdo->prepare("
        SELECT s.id, s.student_id, s.full_name, a.status as attendance_status, a.notes
        FROM enrollments e
        JOIN students s ON e.student_id = s.id
        LEFT JOIN attendance a ON a.student_id = s.id AND a.class_id = ? AND a.attendance_date = ?
        WHERE e.class_id = ? AND e.status = 'active'
        ORDER BY s.full_name
    ");
    $stmt->execute([$selected_class, $selected_date, $selected_class]);
    $enrolled_students = $stmt->fetchAll();
}

// Get recent attendance records
$stmt = $pdo->query("
    SELECT a.*, s.student_id, s.full_name, c.class_code, c.class_name
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    JOIN classes c ON a.class_id = c.id
    ORDER BY a.attendance_date DESC, a.marked_at DESC
    LIMIT 500
");
$recent_attendance = $stmt->fetchAll();
?>

<!-- Export Section -->
<div class="card mb-3">
    <div class="card-header bg-success text-white">
        <i class="fas fa-file-excel"></i> Export Attendance to Excel
    </div>
    <div class="card-body">
        <form method="GET" action="export_attendance_excel.php" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label"><i class="fas fa-chalkboard"></i> Select Class</label>
                <select name="class_id" class="form-control" required>
                    <option value="">-- Select Class --</option>
                    <?php foreach($all_classes as $c): ?>
                    <option value="<?php echo $c['id']; ?>">
                        <?php echo $c['class_code']; ?> - <?php echo htmlspecialchars($c['class_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label"><i class="fas fa-calendar"></i> Export Month</label>
                <input type="month" name="month" class="form-control" value="<?php echo $selected_month; ?>" required>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-success w-100">
                    <i class="fas fa-file-excel"></i> Export to Excel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Class and Date Filter -->
<div class="card mb-3">
    <div class="card-header">
        <i class="fas fa-filter"></i> Mark Attendance - Filter
        <?php if ($class_details && $class_details['day_of_week']): ?>
            <span class="badge bg-primary ms-2">Only <?php echo $class_details['day_of_week']; ?>s Allowed</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if ($date_validation_error && strpos($date_validation_error, 'Redirecting') !== false): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> <?php echo $date_validation_error; ?>
            </div>
        <?php endif; ?>
        
        <form method="GET" class="row g-3" id="filterForm">
            <input type="hidden" name="page" value="attendance">
            <div class="col-md-4">
                <label class="form-label">Select Class *</label>
                <select name="class_id" id="classSelect" class="form-control" required onchange="this.form.submit()">
                    <option value="">-- Select Class --</option>
                    <?php foreach($all_classes as $c): ?>
                    <option value="<?php echo $c['id']; ?>" 
                            data-day="<?php echo $c['day_of_week'] ?? ''; ?>"
                            <?php echo $selected_class == $c['id'] ? 'selected' : ''; ?>>
                        <?php echo $c['class_code']; ?> - <?php echo htmlspecialchars($c['class_name']); ?>
                        <?php if ($c['day_of_week']): ?>
                            (<?php echo $c['day_of_week']; ?>s)
                        <?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">Select Date (use arrows or type) *</label>
                <div class="input-group">
                    <button type="button" class="btn btn-outline-secondary" id="prevDate" title="Previous class day">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <input type="date" name="date" id="dateSelect" class="form-control" value="<?php echo $selected_date; ?>" required>
                    <button type="button" class="btn btn-outline-secondary" id="nextDate" title="Next class day">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <small class="text-primary" id="dateHint"></small>
                <small class="text-danger" id="dateError" style="display:none;"></small>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> Load Attendance
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Date navigation with validation
const classSelect = document.getElementById('classSelect');
const dateSelect = document.getElementById('dateSelect');
const prevDateBtn = document.getElementById('prevDate');
const nextDateBtn = document.getElementById('nextDate');
const dateHint = document.getElementById('dateHint');
const dateError = document.getElementById('dateError');
const filterForm = document.getElementById('filterForm');

const dayMap = {
    'Monday': 1,
    'Tuesday': 2,
    'Wednesday': 3,
    'Thursday': 4,
    'Friday': 5,
    'Saturday': 6,
    'Sunday': 0
};

let currentClassDay = null;
let currentDayNumber = null;

function updateDateRestriction() {
    const selectedOption = classSelect.options[classSelect.selectedIndex];
    const classDay = selectedOption.getAttribute('data-day');
    
    currentClassDay = classDay;
    currentDayNumber = classDay ? dayMap[classDay] : null;
    
    if (classDay && dayMap.hasOwnProperty(classDay)) {
        dateHint.textContent = `Use arrows to navigate between ${classDay}s, or type a date manually`;
        dateHint.style.display = 'block';
        prevDateBtn.disabled = false;
        nextDateBtn.disabled = false;
    } else {
        dateHint.textContent = 'No schedule set for this class';
        dateHint.style.display = 'block';
        prevDateBtn.disabled = true;
        nextDateBtn.disabled = true;
    }
}

function findNextClassDate(fromDate, direction = 1) {
    if (!currentDayNumber && currentDayNumber !== 0) {
        return fromDate;
    }
    
    let current = new Date(fromDate);
    
    // Move one day in the direction first
    current.setDate(current.getDate() + direction);
    
    // Find next occurrence of the class day
    for (let i = 0; i < 7; i++) {
        if (current.getDay() === currentDayNumber) {
            return current.toISOString().split('T')[0];
        }
        current.setDate(current.getDate() + direction);
    }
    
    return fromDate;
}

function validateSelectedDate() {
    if (!currentClassDay || !dateSelect.value) {
        dateError.style.display = 'none';
        return;
    }
    
    const selectedDate = new Date(dateSelect.value + 'T00:00:00');
    const selectedDayOfWeek = selectedDate.getDay();
    
    if (selectedDayOfWeek !== currentDayNumber) {
        const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const selectedDayName = dayNames[selectedDayOfWeek];
        
        dateError.textContent = `⚠️ Warning: Selected date is a ${selectedDayName}. This class only meets on ${currentClassDay}s. Click "Load Attendance" to auto-correct.`;
        dateError.style.display = 'block';
        dateSelect.style.borderColor = '#ffc107';
    } else {
        dateError.style.display = 'none';
        dateSelect.style.borderColor = '';
    }
}

if (classSelect && dateSelect) {
    // Previous date button
    prevDateBtn.addEventListener('click', function() {
        if (!dateSelect.value) return;
        const prevDate = findNextClassDate(dateSelect.value, -1);
        dateSelect.value = prevDate;
        validateSelectedDate();
    });
    
    // Next date button
    nextDateBtn.addEventListener('click', function() {
        if (!dateSelect.value) return;
        const nextDate = findNextClassDate(dateSelect.value, 1);
        dateSelect.value = nextDate;
        validateSelectedDate();
    });
    
    // Class change
    classSelect.addEventListener('change', function() {
        updateDateRestriction();
        validateSelectedDate();
    });
    
    // Date change/input
    dateSelect.addEventListener('change', validateSelectedDate);
    dateSelect.addEventListener('input', validateSelectedDate);
    
    // Run on page load
    updateDateRestriction();
    validateSelectedDate();
}
</script>

<?php if ($selected_class && count($enrolled_students) > 0): ?>
<!-- Bulk Attendance Form -->
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-users"></i> Mark Attendance - <?php echo date('l, F j, Y', strtotime($selected_date)); ?></span>
        <?php 
        // Check if attendance exists for this class and date
        $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM attendance WHERE class_id = ? AND attendance_date = ?");
        $checkStmt->execute([$selected_class, $selected_date]);
        $attendanceExists = $checkStmt->fetch()['count'] > 0;
        
        if ($attendanceExists): ?>
            <button class="btn btn-danger btn-sm" onclick="if(confirm('Delete ALL attendance records for this class on <?php echo date('F j, Y', strtotime($selected_date)); ?>?')) document.getElementById('deleteAttendanceForm').submit();">
                <i class="fas fa-trash"></i> Delete All Attendance for This Day
            </button>
            <form id="deleteAttendanceForm" method="POST" action="admin_handler.php" style="display:none;">
                <input type="hidden" name="action" value="delete_attendance_day">
                <input type="hidden" name="class_id" value="<?php echo $selected_class; ?>">
                <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">
            </form>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form method="POST" action="admin_handler.php" id="bulkAttendanceForm">
            <input type="hidden" name="action" value="bulk_attendance">
            <input type="hidden" name="class_id" value="<?php echo $selected_class; ?>">
            <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Status</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($enrolled_students as $s): 
                            $current_status = $s['attendance_status'] ?? 'present';
                        ?>
                        <tr>
                            <td><span class="badge bg-secondary"><?php echo $s['student_id']; ?></span></td>
                            <td><?php echo htmlspecialchars($s['full_name']); ?></td>
                            <td>
                                <select name="attendance[<?php echo $s['id']; ?>]" class="form-control form-control-sm">
                                    <option value="present" <?php echo $current_status === 'present' ? 'selected' : ''; ?>>Present</option>
                                    <option value="absent" <?php echo $current_status === 'absent' ? 'selected' : ''; ?>>Absent</option>
                                    <option value="late" <?php echo $current_status === 'late' ? 'selected' : ''; ?>>Late</option>
                                    <option value="excused" <?php echo $current_status === 'excused' ? 'selected' : ''; ?>>Excused</option>
                                </select>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#noteModal<?php echo $s['id']; ?>">
                                    <i class="fas fa-sticky-note"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-save"></i> Save Attendance
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Note Modals (OUTSIDE the main form) -->
<?php foreach($enrolled_students as $s): 
    $current_status = $s['attendance_status'] ?? 'present';
?>
<div class="modal fade" id="noteModal<?php echo $s['id']; ?>">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="admin_handler.php">
                <input type="hidden" name="action" value="mark_attendance">
                <input type="hidden" name="student_id" value="<?php echo $s['id']; ?>">
                <input type="hidden" name="class_id" value="<?php echo $selected_class; ?>">
                <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Add Note - <?php echo htmlspecialchars($s['full_name']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Status</label>
                        <select name="status" class="form-control" required>
                            <option value="present" <?php echo $current_status === 'present' ? 'selected' : ''; ?>>Present</option>
                            <option value="absent" <?php echo $current_status === 'absent' ? 'selected' : ''; ?>>Absent</option>
                            <option value="late" <?php echo $current_status === 'late' ? 'selected' : ''; ?>>Late</option>
                            <option value="excused" <?php echo $current_status === 'excused' ? 'selected' : ''; ?>>Excused</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($s['notes'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php elseif($selected_class): ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> No students enrolled in this class.
</div>
<?php endif; ?>

<!-- Recent Attendance History -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-history"></i> Recent Attendance Records
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Status</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recent_attendance as $a): ?>
                    <tr>
                        <td><?php echo date('D, M j, Y', strtotime($a['attendance_date'])); ?></td>
                        <td>
                            <?php echo htmlspecialchars($a['full_name']); ?><br>
                            <small class="text-muted"><?php echo $a['student_id']; ?></small>
                        </td>
                        <td><?php echo $a['class_code']; ?> - <?php echo htmlspecialchars($a['class_name']); ?></td>
                        <td>
                            <?php if($a['status'] === 'present'): ?>
                                <span class="badge bg-success">Present</span>
                            <?php elseif($a['status'] === 'absent'): ?>
                                <span class="badge bg-danger">Absent</span>
                            <?php elseif($a['status'] === 'late'): ?>
                                <span class="badge bg-warning">Late</span>
                            <?php else: ?>
                                <span class="badge bg-info">Excused</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($a['notes'] ?? '-'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>