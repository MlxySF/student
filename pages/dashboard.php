<?php
// Student Dashboard - Updated with Invoice Support

// Get student information
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([getStudentId()]);
$student = $stmt->fetch();

// Get enrolled classes
$stmt = $pdo->prepare("
    SELECT c.*, e.enrollment_date, e.status as enrollment_status
    FROM enrollments e
    JOIN classes c ON e.class_id = c.id
    WHERE e.student_id = ? AND e.status = 'active'
    ORDER BY c.class_code
");
$stmt->execute([getStudentId()]);
$enrolled_classes = $stmt->fetchAll();

// Get pending payments
$stmt = $pdo->prepare("
    SELECT COUNT(*) as pending_count
    FROM payments
    WHERE student_id = ? AND verification_status = 'pending'
");
$stmt->execute([getStudentId()]);
$pending_payments = $stmt->fetch()['pending_count'];

// Get verified payments
$stmt = $pdo->prepare("
    SELECT COUNT(*) as verified_count
    FROM payments
    WHERE student_id = ? AND verification_status = 'verified'
");
$stmt->execute([getStudentId()]);
$verified_payments = $stmt->fetch()['verified_count'];

// Get invoices (NEW!)
$stmt = $pdo->prepare("
    SELECT i.*, c.class_code, c.class_name
    FROM invoices i
    LEFT JOIN classes c ON i.class_id = c.id
    WHERE i.student_id = ?
    ORDER BY i.created_at DESC
");
$stmt->execute([getStudentId()]);
$all_invoices = $stmt->fetchAll();

// Count unpaid invoices
$unpaid_invoices = array_filter($all_invoices, fn($inv) => $inv['status'] === 'unpaid');
$unpaid_count = count($unpaid_invoices);
$unpaid_total = array_sum(array_column($unpaid_invoices, 'amount'));
?>

<div class="row">
    <!-- Welcome Card -->
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h4 class="mb-1">Welcome back, <?php echo htmlspecialchars($student['full_name']); ?>! 👋</h4>
                <p class="text-muted mb-0">Student ID: <span class="badge bg-dark"><?php echo $student['student_id']; ?></span></p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Enrolled Classes -->
    <div class="col-md-3 mb-4">
        <div class="stat-card">
            <div class="stat-icon bg-primary">
                <i class="fas fa-book"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo count($enrolled_classes); ?></h3>
                <p>Enrolled Classes</p>
            </div>
        </div>
    </div>

    <!-- Pending Payments -->
    <div class="col-md-3 mb-4">
        <div class="stat-card">
            <div class="stat-icon bg-warning">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $pending_payments; ?></h3>
                <p>Pending Verification</p>
            </div>
        </div>
    </div>

    <!-- Verified Payments -->
    <div class="col-md-3 mb-4">
        <div class="stat-card">
            <div class="stat-icon bg-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $verified_payments; ?></h3>
                <p>Verified Payments</p>
            </div>
        </div>
    </div>

    <!-- Unpaid Invoices (NEW!) -->
    <div class="col-md-3 mb-4">
        <div class="stat-card">
            <div class="stat-icon bg-danger">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $unpaid_count; ?></h3>
                <p>Unpaid Invoices</p>
            </div>
        </div>
    </div>
</div>

<!-- Unpaid Invoices Alert (Stays visible - no auto-dismiss!) -->
<?php if ($unpaid_count > 0): ?>
<div class="row">
    <div class="col-12 mb-4">
        <div class="alert alert-warning alert-dismissible fade show invoice-alert" role="alert">
            <h5 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> You have <?php echo $unpaid_count; ?> unpaid invoice(s)</h5>
            <p class="mb-0">Total outstanding amount: <strong><?php echo formatCurrency($unpaid_total); ?></strong></p>
            <p class="mb-0 mt-2">
                <a href="?page=invoices" class="btn btn-sm btn-warning">
                    <i class="fas fa-eye"></i> View Invoices
                </a>
            </p>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <!-- Enrolled Classes Card -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-book"></i> My Classes
            </div>
            <div class="card-body">
                <?php if ($enrolled_classes): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Class Name</th>
                                    <th>Monthly Fee</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($enrolled_classes as $class): ?>
                                    <tr>
                                        <td><span class="badge bg-primary"><?php echo $class['class_code']; ?></span></td>
                                        <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                        <td><strong><?php echo formatCurrency($class['monthly_fee']); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-4">
                        <i class="fas fa-info-circle"></i> You are not enrolled in any classes yet.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Invoices Card (NEW!) -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-file-invoice"></i> Recent Invoices
            </div>
            <div class="card-body">
                <?php if ($all_invoices): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $recent_invoices = array_slice($all_invoices, 0, 5);
                                foreach($recent_invoices as $invoice): 
                                    $status_class = $invoice['status'] === 'paid' ? 'success' : 
                                                  ($invoice['status'] === 'cancelled' ? 'secondary' : 'warning');
                                ?>
                                    <tr>
                                        <td><small><?php echo $invoice['invoice_number']; ?></small></td>
                                        <td><?php echo htmlspecialchars(substr($invoice['description'], 0, 30)) . '...'; ?></td>
                                        <td><strong><?php echo formatCurrency($invoice['amount']); ?></strong></td>
                                        <td><span class="badge bg-<?php echo $status_class; ?>"><?php echo ucfirst($invoice['status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-2">
                        <a href="?page=invoices" class="btn btn-sm btn-outline-primary">
                            View All Invoices <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-4">
                        <i class="fas fa-check-circle"></i> No invoices yet.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-bolt"></i> Quick Actions
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <a href="?page=payments" class="btn btn-outline-primary w-100">
                            <i class="fas fa-upload"></i> Upload Payment
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="?page=invoices" class="btn btn-outline-warning w-100">
                            <i class="fas fa-file-invoice"></i> View Invoices
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="?page=attendance" class="btn btn-outline-info w-100">
                            <i class="fas fa-calendar-check"></i> My Attendance
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="?page=profile" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-user"></i> My Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>