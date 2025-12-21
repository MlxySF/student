<?php
// admin_pages/dashboard.php - ULTRA-SAFE VERSION WITH ERROR HANDLING

try {
    // Get total students - ONLY COUNT APPROVED ACCOUNTS
    $totalStudents = $pdo->query(
        "SELECT COUNT(*) FROM registrations WHERE payment_status = 'approved'"
    )->fetchColumn();
} catch (PDOException $e) {
    $totalStudents = 0;
    error_log("Error counting students: " . $e->getMessage());
}

try {
    // Get students by status - ONLY COUNT APPROVED ACCOUNTS
    // Using payment_status = 'approved' filter
    $stmt = $pdo->query("
        SELECT student_status, COUNT(*) as count 
        FROM registrations 
        WHERE student_status IS NOT NULL 
        AND student_status != ''
        AND payment_status = 'approved'
        GROUP BY student_status
    ");
    $studentsByStatus = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $studentsByStatus = [];
    error_log("Error getting students by status: " . $e->getMessage());
}

try {
    // Get total classes - Check if 'status' column exists in classes table
    $totalClasses = $pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn();
} catch (PDOException $e) {
    $totalClasses = 0;
    error_log("Error counting classes: " . $e->getMessage());
}

try {
    // Get pending registrations
    $pendingRegistrations = $pdo->query("SELECT COUNT(*) FROM registrations WHERE payment_status = 'pending'")->fetchColumn();
} catch (PDOException $e) {
    $pendingRegistrations = 0;
    error_log("Error counting pending registrations: " . $e->getMessage());
}

try {
    // Get pending payments
    $pendingPayments = $pdo->query("SELECT COUNT(*) FROM payments WHERE verification_status = 'pending'")->fetchColumn();
} catch (PDOException $e) {
    $pendingPayments = 0;
    error_log("Error counting pending payments: " . $e->getMessage());
}

try {
    // Get unpaid invoices
    $unpaidInvoices = $pdo->query("SELECT COUNT(*) FROM invoices WHERE status = 'unpaid'")->fetchColumn();
} catch (PDOException $e) {
    $unpaidInvoices = 0;
    error_log("Error counting unpaid invoices: " . $e->getMessage());
}

try {
    // Get recent registrations - USE student_status column
    $stmt = $pdo->query("
        SELECT * FROM registrations 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recentRegistrations = $stmt->fetchAll();
} catch (PDOException $e) {
    $recentRegistrations = [];
    error_log("Error getting recent registrations: " . $e->getMessage());
}

try {
    // Get recent payments needing verification
    $stmt = $pdo->query("
        SELECT p.*, s.full_name, s.student_id, c.class_name
        FROM payments p
        JOIN students s ON p.student_id = s.id
        JOIN classes c ON p.class_id = c.id
        WHERE p.verification_status = 'pending'
        ORDER BY p.upload_date DESC
        LIMIT 5
    ");
    $recentPayments = $stmt->fetchAll();
} catch (PDOException $e) {
    $recentPayments = [];
    error_log("Error getting recent payments: " . $e->getMessage());
}

// Status display mapping with Chinese characters
$statusDisplayMap = [
    'State Team' => 'State Team 州队',
    'Backup Team' => 'Backup Team 后备队',
    'Student' => 'Student 学生'
];
?>

<div class="row">
    <!-- Total Students (Approved Only) -->
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon bg-primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $totalStudents; ?></h3>
                <p>Approved Students</p>
            </div>
        </div>
    </div>

    <!-- Pending Registrations -->
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon bg-warning">
                <i class="fas fa-user-plus"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $pendingRegistrations; ?></h3>
                <p>Pending Registrations</p>
            </div>
        </div>
    </div>

    <!-- Pending Payments -->
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon bg-danger">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $pendingPayments; ?></h3>
                <p>Pending Payments</p>
            </div>
        </div>
    </div>

    <!-- Active Classes -->
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon bg-success">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $totalClasses; ?></h3>
                <p>Total Classes</p>
            </div>
        </div>
    </div>
</div>

<!-- Students by Status -->
<div class="row mt-4">
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-chart-pie"></i> Students by Status (Approved Only)
            </div>
            <div class="card-body">
                <?php if (!empty($studentsByStatus)): ?>
                    <?php foreach ($studentsByStatus as $student_status => $count): 
                        // Get display text with Chinese characters
                        $displayStatus = isset($statusDisplayMap[$student_status]) ? $statusDisplayMap[$student_status] : $student_status;
                    ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <span class="badge <?php 
                                    echo strpos($student_status, 'State Team') !== false ? 'badge-state-team' : 
                                        (strpos($student_status, 'Backup Team') !== false ? 'badge-backup-team' : 'badge-student'); 
                                ?>">
                                    <?php echo $displayStatus; ?>
                                </span>
                            </div>
                            <strong><?php echo $count; ?> students</strong>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-users fa-2x mb-2"></i>
                        <p class="mb-0">No approved students yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Registrations -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
                <span><i class="fas fa-user-plus"></i> Recent Registrations</span>
                <a href="?page=registrations" class="btn btn-sm btn-light">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Reg #</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentRegistrations)): ?>
                                <?php foreach ($recentRegistrations as $reg): 
                                    // FIXED: Use student_status column instead of status
                                    $regStatus = $reg['student_status'];
                                    $displayRegStatus = isset($statusDisplayMap[$regStatus]) ? $statusDisplayMap[$regStatus] : $regStatus;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($reg['registration_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($reg['name_en']); ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            echo strpos($regStatus, 'State Team') !== false ? 'badge-state-team' : 
                                                (strpos($regStatus, 'Backup Team') !== false ? 'badge-backup-team' : 'badge-student'); 
                                        ?>">
                                            <?php echo $displayRegStatus; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $reg['payment_status'] === 'verified' || $reg['payment_status'] === 'approved' ? 'success' : 
                                                ($reg['payment_status'] === 'pending' ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst($reg['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($reg['created_at'])); ?></td>
                                    <td>
                                        <a href="?page=registrations&view=<?php echo $reg['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                        No recent registrations
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Payments -->
<div class="card">
    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
        <span><i class="fas fa-credit-card"></i> Payments Needing Verification</span>
        <a href="?page=payments" class="btn btn-sm btn-light">View All</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Class</th>
                        <th>Amount</th>
                        <th>Month</th>
                        <th>Upload Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentPayments)): ?>
                        <?php foreach ($recentPayments as $payment): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($payment['student_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($payment['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($payment['class_name']); ?></td>
                            <td><strong>RM <?php echo number_format($payment['amount'], 2); ?></strong></td>
                            <td><?php echo htmlspecialchars($payment['payment_month']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($payment['upload_date'])); ?></td>
                            <td>
                                <a href="?page=payments&view=<?php echo $payment['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> Review
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-check-circle fa-2x mb-2 d-block"></i>
                                No pending payments
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
