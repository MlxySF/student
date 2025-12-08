<?php
// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM students");
$total_students = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM classes");
$total_classes = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM payments WHERE verification_status = 'pending'");
$pending_payments = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM payments WHERE verification_status = 'verified' AND MONTH(upload_date) = MONTH(CURRENT_DATE())");
$verified_this_month = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT SUM(amount) as total FROM payments WHERE verification_status = 'verified' AND MONTH(upload_date) = MONTH(CURRENT_DATE())");
$revenue_this_month = $stmt->fetch()['total'] ?? 0;

// Recent activities
$stmt = $pdo->query("
    SELECT s.full_name, s.student_id, p.amount, p.upload_date, p.verification_status, c.class_code
    FROM payments p
    JOIN students s ON p.student_id = s.id
    JOIN classes c ON p.class_id = c.id
    ORDER BY p.upload_date DESC
    LIMIT 10
");
$recent_payments = $stmt->fetchAll();

// Monthly revenue data for chart
$stmt = $pdo->query("
    SELECT DATE_FORMAT(upload_date, '%Y-%m') as month, SUM(amount) as total
    FROM payments
    WHERE verification_status = 'verified'
    AND upload_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month
");
$monthly_revenue = $stmt->fetchAll();
$months = array_column($monthly_revenue, 'month');
$revenues = array_column($monthly_revenue, 'total');
?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $total_students; ?></h3>
                <p>Total Students</p>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-success">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $total_classes; ?></h3>
                <p>Total Classes</p>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-warning">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $pending_payments; ?></h3>
                <p>Pending Payments</p>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-danger">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo formatCurrency($revenue_this_month); ?></h3>
                <p>This Month Revenue</p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-history"></i> Recent Activity
    </div>
    <div class="card-body">
        <?php if (count($recent_payments) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Class</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_payments as $payment): ?>
                            <tr>
                                <td><?php echo formatDate($payment['upload_date']); ?></td>
                                <td><?php echo htmlspecialchars($payment['full_name']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($payment['student_id']); ?></span></td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($payment['class_code']); ?></span></td>
                                <td><strong><?php echo formatCurrency($payment['amount']); ?></strong></td>
                                <td>
                                    <?php if ($payment['verification_status'] === 'pending'): ?>
                                        <span class="badge bg-warning">Pending</span>
                                    <?php elseif ($payment['verification_status'] === 'verified'): ?>
                                        <span class="badge bg-success">Verified</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?page=payments" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No recent payment activity.
            </div>
        <?php endif; ?>
    </div>
</div>