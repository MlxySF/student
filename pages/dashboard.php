<?php
// pages/dashboard.php
// Get student information
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([getStudentId()]);
$student = $stmt->fetch();

// Get enrolled classes count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM enrollments WHERE student_id = ? AND status = 'active'");
$stmt->execute([getStudentId()]);
$classesCount = $stmt->fetch()['count'];

// Get unpaid invoices count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM invoices WHERE student_id = ? AND status IN ('unpaid', 'overdue')");
$stmt->execute([getStudentId()]);
$unpaidInvoicesCount = $stmt->fetch()['count'];

// Get pending payments count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM payments WHERE student_id = ? AND verification_status = 'pending'");
$stmt->execute([getStudentId()]);
$pendingPaymentsCount = $stmt->fetch()['count'];

// Get total attendance percentage
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present
    FROM attendance 
    WHERE student_id = ?
");
$stmt->execute([getStudentId()]);
$attendanceData = $stmt->fetch();
$attendancePercentage = $attendanceData['total'] > 0 
    ? round(($attendanceData['present'] / $attendanceData['total']) * 100) 
    : 0;

// Get recent invoices (last 5)
$stmt = $pdo->prepare("
    SELECT 
        i.*,
        c.class_name
    FROM invoices i
    LEFT JOIN classes c ON i.class_id = c.id
    WHERE i.student_id = ?
    ORDER BY i.created_at DESC
    LIMIT 5
");
$stmt->execute([getStudentId()]);
$recentInvoices = $stmt->fetchAll();

// Get upcoming classes (enrolled active classes)
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        e.enrollment_date,
        e.status as enrollment_status
    FROM enrollments e
    JOIN classes c ON e.class_id = c.id
    WHERE e.student_id = ? AND e.status = 'active'
    ORDER BY c.class_name
    LIMIT 5
");
$stmt->execute([getStudentId()]);
$enrolledClasses = $stmt->fetchAll();

// Get recent attendance records (last 10)
$stmt = $pdo->prepare("
    SELECT 
        a.*,
        c.class_name,
        c.class_code
    FROM attendance a
    JOIN classes c ON a.class_id = c.id
    WHERE a.student_id = ?
    ORDER BY a.attendance_date DESC
    LIMIT 10
");
$stmt->execute([getStudentId()]);
$recentAttendance = $stmt->fetchAll();
?>

<!-- Dashboard Statistics Cards -->
<div class="row">
    <!-- Classes Enrolled Card -->
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="stat-card animate__animated animate__fadeInUp">
            <div class="stat-icon bg-primary">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $classesCount; ?></h3>
                <p>Classes Enrolled</p>
            </div>
        </div>
    </div>

    <!-- Unpaid Invoices Card -->
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
            <div class="stat-icon <?php echo $unpaidInvoicesCount > 0 ? 'bg-danger' : 'bg-success'; ?>">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $unpaidInvoicesCount; ?></h3>
                <p>Unpaid Invoices</p>
            </div>
        </div>
    </div>

    <!-- Pending Payments Card -->
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
            <div class="stat-icon bg-warning">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $pendingPaymentsCount; ?></h3>
                <p>Pending Verification</p>
            </div>
        </div>
    </div>

    <!-- Attendance Percentage Card -->
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
            <div class="stat-icon <?php echo $attendancePercentage >= 80 ? 'bg-success' : 'bg-danger'; ?>">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $attendancePercentage; ?>%</h3>
                <p>Attendance Rate</p>
            </div>
        </div>
    </div>
</div>

<!-- Student Welcome Message -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card animate__animated animate__fadeIn">
            <div class="card-body">
                <h4 class="mb-3">
                    <i class="fas fa-hand-wave text-warning"></i>
                    Welcome back, <?php echo htmlspecialchars($student['full_name']); ?>!
                </h4>
                <p class="text-muted mb-2">
                    <i class="fas fa-envelope"></i> <strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?>
                </p>
                <p class="text-muted mb-2">
                    <i class="fas fa-phone"></i> <strong>Phone:</strong> <?php echo htmlspecialchars($student['phone']); ?>
                </p>
                <p class="text-muted mb-0">
                    <i class="fas fa-id-badge"></i> <strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_id']); ?>
                </p>
                <?php if (!empty($student['student_status'])): ?>
                <p class="text-muted mb-0">
                    <i class="fas fa-user-tag"></i> <strong>Status:</strong> 
                    <span class="badge 
                        <?php 
                        echo ($student['student_status'] === 'State Team 州队') ? 'bg-success' : 
                             (($student['student_status'] === 'Backup Team 后备队') ? 'bg-warning' : 'bg-primary');
                        ?>">
                        <?php echo htmlspecialchars($student['student_status']); ?>
                    </span>
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Column: Recent Invoices & Classes -->
    <div class="col-lg-6">
        <!-- Recent Invoices Card -->
        <div class="card mb-4 animate__animated animate__fadeInLeft">
            <div class="card-header">
                <i class="fas fa-file-invoice-dollar"></i> Recent Invoices
            </div>
            <div class="card-body">
                <?php if (count($recentInvoices) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Class</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentInvoices as $invoice): ?>
                                <tr>
                                    <td><small><?php echo htmlspecialchars($invoice['invoice_number']); ?></small></td>
                                    <td><small><?php echo htmlspecialchars($invoice['class_name'] ?? 'N/A'); ?></small></td>
                                    <td><strong>RM <?php echo number_format($invoice['amount'], 2); ?></strong></td>
                                    <td>
                                        <span class="badge badge-status 
                                            <?php 
                                            echo ($invoice['status'] === 'paid') ? 'bg-success' : 
                                                 (($invoice['status'] === 'pending') ? 'bg-warning' : 
                                                 (($invoice['status'] === 'overdue') ? 'bg-danger' : 'bg-secondary'));
                                            ?>">
                                            <?php echo ucfirst($invoice['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="?page=invoices" class="btn btn-primary btn-sm">
                            <i class="fas fa-arrow-right"></i> View All Invoices
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                        <p>No invoices yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Enrolled Classes Card -->
        <div class="card mb-4 animate__animated animate__fadeInLeft" style="animation-delay: 0.2s;">
            <div class="card-header">
                <i class="fas fa-chalkboard-teacher"></i> My Classes
            </div>
            <div class="card-body">
                <?php if (count($enrolledClasses) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($enrolledClasses as $class): ?>
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">
                                        <i class="fas fa-book text-primary"></i>
                                        <?php echo htmlspecialchars($class['class_name']); ?>
                                    </h6>
                                    <p class="mb-1 text-muted small">
                                        <i class="fas fa-code"></i> <?php echo htmlspecialchars($class['class_code']); ?>
                                    </p>
                                    <?php if (!empty($class['description'])): ?>
                                    <p class="mb-0 text-muted" style="font-size: 12px;">
                                        <?php echo htmlspecialchars(substr($class['description'], 0, 60)) . '...'; ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                                <span class="badge bg-success">RM <?php echo number_format($class['monthly_fee'], 2); ?>/mo</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="?page=classes" class="btn btn-primary btn-sm">
                            <i class="fas fa-arrow-right"></i> View All Classes
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-chalkboard-teacher fa-3x mb-3 opacity-50"></i>
                        <p class="mb-2">You're not enrolled in any classes yet</p>
                        <small>Please wait for admin to enroll you in classes</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Attendance & Quick Actions -->
    <div class="col-lg-6">
        <!-- Recent Attendance Card -->
        <div class="card mb-4 animate__animated animate__fadeInRight">
            <div class="card-header">
                <i class="fas fa-calendar-check"></i> Recent Attendance
            </div>
            <div class="card-body">
                <?php if (count($recentAttendance) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Class</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentAttendance as $att): ?>
                                <tr>
                                    <td><small><?php echo date('M d, Y', strtotime($att['attendance_date'])); ?></small></td>
                                    <td><small><?php echo htmlspecialchars($att['class_code']); ?></small></td>
                                    <td>
                                        <span class="badge badge-status 
                                            <?php 
                                            echo ($att['status'] === 'present') ? 'bg-success' : 
                                                 (($att['status'] === 'absent') ? 'bg-danger' : 'bg-warning');
                                            ?>">
                                            <?php echo ucfirst($att['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="?page=attendance" class="btn btn-primary btn-sm">
                            <i class="fas fa-arrow-right"></i> View Full Attendance
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-calendar-times fa-3x mb-3 opacity-50"></i>
                        <p>No attendance records yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions Card -->
        <div class="card mb-4 animate__animated animate__fadeInRight" style="animation-delay: 0.2s;">
            <div class="card-header">
                <i class="fas fa-bolt"></i> Quick Actions
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if ($unpaidInvoicesCount > 0): ?>
                    <a href="?page=invoices" class="btn btn-danger">
                        <i class="fas fa-exclamation-circle"></i> Pay Unpaid Invoices (<?php echo $unpaidInvoicesCount; ?>)
                    </a>
                    <?php endif; ?>
                    <a href="?page=payments" class="btn btn-primary">
                        <i class="fas fa-credit-card"></i> Make a Payment
                    </a>
                    <a href="?page=attendance" class="btn btn-info">
                        <i class="fas fa-calendar-check"></i> Check Attendance
                    </a>
                    <a href="?page=profile" class="btn btn-secondary">
                        <i class="fas fa-user-edit"></i> Update Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- Important Notice Card -->
        <?php if ($unpaidInvoicesCount > 0): ?>
        <div class="card border-danger mb-4 animate__animated animate__pulse">
            <div class="card-header bg-danger text-white">
                <i class="fas fa-bell"></i> Important Notice
            </div>
            <div class="card-body">
                <p class="mb-2">
                    <i class="fas fa-exclamation-triangle text-danger"></i>
                    You have <strong><?php echo $unpaidInvoicesCount; ?> unpaid invoice(s)</strong>.
                </p>
                <p class="text-muted small mb-0">
                    Please make payment as soon as possible to avoid service interruption.
                </p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.list-group-item {
    border-left: 3px solid transparent;
    transition: all 0.3s;
}

.list-group-item:hover {
    border-left-color: var(--primary-color);
    background-color: #f8f9fa;
    transform: translateX(5px);
}

.table-hover tbody tr:hover {
    background-color: #f8f9fa;
    cursor: pointer;
}

.badge-status {
    padding: 6px 12px;
    font-size: 11px;
    font-weight: 600;
}

.opacity-50 {
    opacity: 0.5;
}
</style>