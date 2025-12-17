<?php
// Get all classes
$all_classes = $pdo->query("SELECT * FROM classes ORDER BY class_code")->fetchAll();

// Selected class and date
$selected_class = $_GET['class_id'] ?? null;
$selected_date = $_GET['date'] ?? date('Y-m-d');
$selected_month = $_GET['month'] ?? date('Y-m');

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
    ORDER BY a.attendance_date DESC, a.created_at DESC
    LIMIT 50
");
$recent_attendance = $stmt->fetchAll();
?>

<h3><i class="fas fa-calendar-check"></i> Attendance Management</h3>

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
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <input type="hidden" name="page" value="attendance">
            <div class="col-md-4">
                <label class="form-label">Select Class</label>
                <select name="class_id" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Select Class --</option>
                    <?php foreach($all_classes as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo $selected_class == $c['id'] ? 'selected' : ''; ?>>
                        <?php echo $c['class_code']; ?> - <?php echo htmlspecialchars($c['class_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Select Date</label>
                <input type="date" name="date" class="form-control" value="<?php echo $selected_date; ?>" onchange="this.form.submit()">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($selected_class && count($enrolled_students) > 0): ?>
<!-- Bulk Attendance Form -->
<div class="card mb-3">
    <div class="card-header">
        <i class="fas fa-users"></i> Mark Attendance - <?php echo formatDate($selected_date); ?>
    </div>
    <div class="card-body">
        <form method="POST">
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
                        <?php foreach($enrolled_students as $s): ?>
                        <tr>
                            <td><span class="badge bg-secondary"><?php echo $s['student_id']; ?></span></td>
                            <td><?php echo htmlspecialchars($s['full_name']); ?></td>
                            <td>
                                <select name="attendance[<?php echo $s['id']; ?>]" class="form-control form-control-sm" required>
                                    <option value="present" <?php echo $s['attendance_status'] === 'present' ? 'selected' : ''; ?>>Present</option>
                                    <option value="absent" <?php echo $s['attendance_status'] === 'absent' ? 'selected' : ''; ?>>Absent</option>
                                    <option value="late" <?php echo $s['attendance_status'] === 'late' ? 'selected' : ''; ?>>Late</option>
                                    <option value="excused" <?php echo $s['attendance_status'] === 'excused' ? 'selected' : ''; ?>>Excused</option>
                                </select>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#noteModal<?php echo $s['id']; ?>">
                                    <i class="fas fa-sticky-note"></i>
                                </button>

                                <!-- Note Modal -->
                                <div class="modal fade" id="noteModal<?php echo $s['id']; ?>">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <input type="hidden" name="action" value="mark_attendance">
                                                <input type="hidden" name="student_id" value="<?php echo $s['id']; ?>">
                                                <input type="hidden" name="class_id" value="<?php echo $selected_class; ?>">
                                                <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Add Note</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p><strong><?php echo htmlspecialchars($s['full_name']); ?></strong></p>
                                                    <div class="mb-3">
                                                        <label>Status</label>
                                                        <select name="status" class="form-control" required>
                                                            <option value="present" <?php echo $s['attendance_status'] === 'present' ? 'selected' : ''; ?>>Present</option>
                                                            <option value="absent" <?php echo $s['attendance_status'] === 'absent' ? 'selected' : ''; ?>>Absent</option>
                                                            <option value="late" <?php echo $s['attendance_status'] === 'late' ? 'selected' : ''; ?>>Late</option>
                                                            <option value="excused" <?php echo $s['attendance_status'] === 'excused' ? 'selected' : ''; ?>>Excused</option>
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
            <table class="table table-hover">
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
                        <td><?php echo formatDate($a['attendance_date']); ?></td>
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