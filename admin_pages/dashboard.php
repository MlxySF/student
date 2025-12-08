<?php
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
    WHERE verification_status = 'verified' AND upload_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month
");
$monthly_revenue = $stmt->fetchAll();
$months = array_column($monthly_revenue, 'month');
$revenues = array_column($monthly_revenue, 'total');
?>

<h3><i class="fas fa-home"></i> Dashboard</h3>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <i class="fas fa-users fa-3x text-primary mb-2"></i>
            <h2><?php echo $total_students; ?></h2>
            <p class="text-muted mb-0">Total Students</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <i class="fas fa-book fa-3x text-success mb-2"></i>
            <h2><?php echo $total_classes; ?></h2>
            <p class="text-muted mb-0">Total Classes</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <i class="fas fa-clock fa-3x text-warning mb-2"></i>
            <h2><?php echo $pending_payments; ?></h2>
            <p class="text-muted mb-0">Pending Payments</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <i class="fas fa-dollar-sign fa-3x text-danger mb-2"></i>
            <h2><?php echo formatCurrency($revenue_this_month); ?></h2>
            <p class="text-muted mb-0">This Month Revenue</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-chart-line"></i> Recent Activities
    </div>
    <div class="card-body">
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
                    <?php foreach($recent_payments as $p): ?>
                    <tr>
                        <td><?php echo formatDate($p['upload_date']); ?></td>
                        <td><?php echo htmlspecialchars($p['full_name']); ?></td>
                        <td><span class="badge bg-secondary"><?php echo $p['student_id']; ?></span></td>
                        <td><?php echo $p['class_code']; ?></td>
                        <td><?php echo formatCurrency($p['amount']); ?></td>
                        <td>
                            <?php if($p['verification_status'] === 'verified'): ?>
                                <span class="badge bg-success">Verified</span>
                            <?php elseif($p['verification_status'] === 'rejected'): ?>
                                <span class="badge bg-danger">Rejected</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?page=payments" class="btn btn-sm btn-primary">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>