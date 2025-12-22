<?php
// Student Attendance Page - Updated for multi-child parent support
// FIXED: Use student_account_id for parent portal

if (isParent()) {
    $stmt = $pdo->prepare("SELECT student_account_id FROM registrations WHERE id = ?");
    $stmt->execute([getActiveStudentId()]);
    $reg = $stmt->fetch();
    $studentAccountId = $reg['student_account_id'];
} else {
    $studentAccountId = getActiveStudentId();
}

// Get overall attendance statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
    FROM attendance 
    WHERE student_id = ?
");
$stmt->execute([$studentAccountId]);
$stats = $stmt->fetch();

$attendance_rate = $stats['total'] > 0 ? round(($stats['present'] / $stats['total']) * 100) : 0;

// Get recent attendance records
$stmt = $pdo->prepare("
    SELECT a.*, c.class_name, c.class_code
    FROM attendance a
    JOIN classes c ON a.class_id = c.id
    WHERE a.student_id = ?
    ORDER BY a.attendance_date DESC
    LIMIT 50
");
$stmt->execute([$studentAccountId]);
$attendance_records = $stmt->fetchAll();

// Get enrolled classes
$stmt = $pdo->prepare("
    SELECT DISTINCT c.id, c.class_name, c.class_code
    FROM enrollments e
    JOIN classes c ON e.class_id = c.id
    WHERE e.student_id = ? AND e.status = 'active'
    ORDER BY c.class_name
");
$stmt->execute([$studentAccountId]);
$enrolled_classes = $stmt->fetchAll();
?>

<?php if (isParent()): ?>
<div class="alert alert-info mb-3">
    <i class="fas fa-info-circle"></i> Viewing attendance for: <strong><?php echo htmlspecialchars(getActiveStudentName()); ?></strong>
</div>
<?php endif; ?>

<!-- Attendance Statistics -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-success">
                <i class="fas fa-check"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['present']; ?></h3>
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
                <h3><?php echo $stats['absent']; ?></h3>
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
                <h3><?php echo $stats['late']; ?></h3>
                <p>Late</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon <?php echo $attendance_rate >= 80 ? 'bg-success' : 'bg-danger'; ?>">
                <i class="fas fa-percentage"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $attendance_rate; ?>%</h3>
                <p>Attendance Rate</p>
            </div>
        </div>
    </div>
</div>

<!-- Overall Attendance Rate -->
<div class="card mb-4">
    <div class="card-body">
        <h5 class="mb-3">Overall Attendance Rate</h5>
        <div class="progress" style="height: 30px;">
            <div class="progress-bar <?php echo $attendance_rate >= 80 ? 'bg-success' : 'bg-danger'; ?>" 
                 style="width: <?php echo $attendance_rate; ?>%" 
                 aria-valuenow="<?php echo $attendance_rate; ?>" 
                 aria-valuemin="0" 
                 aria-valuemax="100">
                <strong><?php echo $attendance_rate; ?>%</strong>
            </div>
        </div>
        <div class="row mt-3 text-center">
            <div class="col-4">
                <i class="fas fa-check text-success"></i> Present: <strong><?php echo $stats['present']; ?></strong>
            </div>
            <div class="col-4">
                <i class="fas fa-clock text-warning"></i> Late: <strong><?php echo $stats['late']; ?></strong>
            </div>
            <div class="col-4">
                <i class="fas fa-times text-danger"></i> Absent: <strong><?php echo $stats['absent']; ?></strong>
            </div>
            <div class="col-12 mt-2">
                <i class="fas fa-list"></i> <strong><?php echo $stats['total']; ?></strong> Total
            </div>
        </div>
    </div>
</div>

<?php if (count($enrolled_classes) == 0): ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> Not enrolled in any classes yet. Enroll in classes to see attendance records.
</div>
<?php endif; ?>

<!-- Recent Attendance Records -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-history"></i> Recent Attendance Records
    </div>
    <div class="card-body">
        <?php if (count($attendance_records) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Class</th>
                            <th>Status</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance_records as $record): ?>
                            <tr>
                                <td><?php echo formatDate($record['attendance_date']); ?></td>
                                <td>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($record['class_code']); ?></span>
                                    <?php echo htmlspecialchars($record['class_name']); ?>
                                </td>
                                <td>
                                    <?php 
                                    $badge_class = '';
                                    switch($record['status']) {
                                        case 'present': $badge_class = 'bg-success'; break;
                                        case 'absent': $badge_class = 'bg-danger'; break;
                                        case 'late': $badge_class = 'bg-warning'; break;
                                        case 'excused': $badge_class = 'bg-info'; break;
                                        default: $badge_class = 'bg-secondary';
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($record['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($record['notes'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <p class="text-muted">No attendance records yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>