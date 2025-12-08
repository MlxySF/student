<?php
// Student Attendance Page - View attendance records

// Get enrolled classes
$stmt = $pdo->prepare("
    SELECT c.*, e.enrollment_date
    FROM enrollments e
    JOIN classes c ON e.class_id = c.id
    WHERE e.student_id = ? AND e.status = 'active'
    ORDER BY c.class_code
");
$stmt->execute([getStudentId()]);
$enrolled_classes = $stmt->fetchAll();

// Get attendance records
$stmt = $pdo->prepare("
    SELECT a.*, c.class_code, c.class_name
    FROM attendance a
    JOIN classes c ON a.class_id = c.id
    WHERE a.student_id = ?
    ORDER BY a.attendance_date DESC
    LIMIT 50
");
$stmt->execute([getStudentId()]);
$attendance_records = $stmt->fetchAll();

// Calculate attendance statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_records,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count
    FROM attendance
    WHERE student_id = ?
");
$stmt->execute([getStudentId()]);
$attendance_stats = $stmt->fetch();

// Calculate attendance percentage
$attendance_percentage = $attendance_stats['total_records'] > 0 
    ? round(($attendance_stats['present_count'] + $attendance_stats['late_count']) / $attendance_stats['total_records'] * 100, 1)
    : 0;
?>

<!-- Attendance Statistics -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-success">
                <i class="fas fa-check"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $attendance_stats['present_count']; ?></h3>
                <p>Present</p>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-danger">
                <i class="fas fa-times"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $attendance_stats['absent_count']; ?></h3>
                <p>Absent</p>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-warning">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $attendance_stats['late_count']; ?></h3>
                <p>Late</p>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-info">
                <i class="fas fa-percentage"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $attendance_percentage; ?>%</h3>
                <p>Attendance Rate</p>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Progress Bar -->
<div class="card mb-4">
    <div class="card-body">
        <h6 class="mb-3">Overall Attendance Rate</h6>
        <div class="progress" style="height: 30px;">
            <div class="progress-bar bg-success" role="progressbar" 
                 style="width: <?php echo $attendance_percentage; ?>%;" 
                 aria-valuenow="<?php echo $attendance_percentage; ?>" 
                 aria-valuemin="0" aria-valuemax="100">
                <?php echo $attendance_percentage; ?>%
            </div>
        </div>
        <div class="row mt-3">
            <div class="col text-center">
                <i class="fas fa-check text-success"></i>
                <strong><?php echo $attendance_stats['present_count']; ?></strong> Present
            </div>
            <div class="col text-center">
                <i class="fas fa-clock text-warning"></i>
                <strong><?php echo $attendance_stats['late_count']; ?></strong> Late
            </div>
            <div class="col text-center">
                <i class="fas fa-times text-danger"></i>
                <strong><?php echo $attendance_stats['absent_count']; ?></strong> Absent
            </div>
            <div class="col text-center">
                <i class="fas fa-list text-info"></i>
                <strong><?php echo $attendance_stats['total_records']; ?></strong> Total
            </div>
        </div>
    </div>
</div>

<!-- Attendance by Class -->
<?php foreach($enrolled_classes as $class): 
    // Get attendance for this class
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
        FROM attendance
        WHERE student_id = ? AND class_id = ?
    ");
    $stmt->execute([getStudentId(), $class['id']]);
    $class_stats = $stmt->fetch();

    $class_percentage = $class_stats['total'] > 0 
        ? round(($class_stats['present'] + $class_stats['late']) / $class_stats['total'] * 100, 1)
        : 0;
?>
    <div class="card mb-3">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <span>
                    <span class="badge bg-primary"><?php echo $class['class_code']; ?></span>
                    <?php echo htmlspecialchars($class['class_name']); ?>
                </span>
                <span class="badge bg-info"><?php echo $class_percentage; ?>% Attendance</span>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-9">
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar bg-success" style="width: <?php echo $class_percentage; ?>%;">
                            <?php echo $class_percentage; ?>%
                        </div>
                    </div>
                </div>
                <div class="col-md-3 text-end">
                    <small class="text-muted">
                        <?php echo $class_stats['present']; ?> present / 
                        <?php echo $class_stats['total']; ?> total
                    </small>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php if (count($enrolled_classes) === 0): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> You are not enrolled in any classes yet. Enroll in classes to see attendance records.
    </div>
<?php endif; ?>

<!-- Recent Attendance Records -->
<div class="card mt-4">
    <div class="card-header">
        <i class="fas fa-history"></i> Recent Attendance Records
    </div>
    <div class="card-body">
        <?php if ($attendance_records): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Class</th>
                            <th>Status</th>
                            <th>Marked At</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($attendance_records as $record): 
                            $status_class = $record['status'] === 'present' ? 'success' : 
                                          ($record['status'] === 'late' ? 'warning' : 'danger');
                            $status_icon = $record['status'] === 'present' ? 'check' : 
                                         ($record['status'] === 'late' ? 'clock' : 'times');
                        ?>
                            <tr>
                                <td><strong><?php echo formatDate($record['attendance_date']); ?></strong></td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $record['class_code']; ?></span>
                                    <br><small><?php echo htmlspecialchars($record['class_name']); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $status_class; ?>">
                                        <i class="fas fa-<?php echo $status_icon; ?>"></i>
                                        <?php echo ucfirst($record['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDateTime($record['created_at']); ?></td>
                                <td>
                                    <?php if ($record['notes']): ?>
                                        <small><?php echo htmlspecialchars($record['notes']); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-calendar-check fa-4x text-muted mb-3"></i>
                <h5>No attendance records yet.</h5>
                <p class="text-muted">Your attendance will be recorded when you attend classes.</p>
            </div>
        <?php endif; ?>
    </div>
</div>