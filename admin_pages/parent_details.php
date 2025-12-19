<?php
// admin_pages/parent_details.php - Parent Details with Children (Stage 4)
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin.php?page=login');
    exit;
}

$parent_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$parent_id) {
    $_SESSION['error'] = 'Invalid parent ID.';
    header('Location: admin.php?page=parent_accounts');
    exit;
}

// Get parent details
$stmt = $pdo->prepare("SELECT * FROM parent_accounts WHERE id = ?");
$stmt->execute([$parent_id]);
$parent = $stmt->fetch();

if (!$parent) {
    $_SESSION['error'] = 'Parent account not found.';
    header('Location: admin.php?page=parent_accounts');
    exit;
}

// Get all children with detailed information
$stmt = $pdo->prepare("
    SELECT 
        s.id,
        s.student_id,
        s.full_name,
        s.email,
        s.phone,
        s.age,
        s.school,
        s.student_status,
        s.created_at,
        pcr.relationship,
        pcr.is_primary,
        COUNT(DISTINCT e.id) as classes_count,
        COALESCE(SUM(CASE WHEN i.status IN ('unpaid', 'overdue') THEN i.amount ELSE 0 END), 0) as outstanding_amount,
        COUNT(DISTINCT CASE WHEN i.status IN ('unpaid', 'overdue') THEN i.id END) as unpaid_invoices_count
    FROM students s
    INNER JOIN parent_child_relationships pcr ON s.id = pcr.student_id
    LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
    LEFT JOIN invoices i ON s.id = i.student_id
    WHERE pcr.parent_id = ?
    GROUP BY s.id
    ORDER BY pcr.is_primary DESC, s.created_at ASC
");
$stmt->execute([$parent_id]);
$children = $stmt->fetchAll();

// Calculate family totals
$total_outstanding = array_sum(array_column($children, 'outstanding_amount'));
$total_unpaid_invoices = array_sum(array_column($children, 'unpaid_invoices_count'));
$total_classes = array_sum(array_column($children, 'classes_count'));

// Get recent activities (registrations)
$stmt = $pdo->prepare("
    SELECT 
        r.registration_number,
        r.name_en,
        r.created_at,
        r.payment_status
    FROM registrations r
    WHERE r.parent_account_id = ?
    ORDER BY r.created_at DESC
    LIMIT 10
");
$stmt->execute([$parent_id]);
$recent_activities = $stmt->fetchAll();
?>

<!-- Back Button -->
<div class="mb-3">
    <a href="?page=parent_accounts" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Parent Accounts
    </a>
</div>

<div class="row">
    <!-- Parent Information Card -->
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user"></i> Parent Information</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" 
                         style="width: 80px; height: 80px; font-size: 36px; font-weight: bold;">
                        <?php echo strtoupper(substr($parent['full_name'], 0, 1)); ?>
                    </div>
                    <h4 class="mt-3 mb-1"><?php echo htmlspecialchars($parent['full_name']); ?></h4>
                    <span class="badge <?php echo ($parent['status'] === 'active') ? 'bg-success' : 'bg-secondary'; ?>">
                        <?php echo ucfirst($parent['status']); ?>
                    </span>
                </div>

                <table class="table table-borderless table-sm">
                    <tr>
                        <td class="text-muted"><i class="fas fa-id-card"></i> Parent ID</td>
                        <td class="text-end"><strong><?php echo htmlspecialchars($parent['parent_id']); ?></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><i class="fas fa-envelope"></i> Email</td>
                        <td class="text-end">
                            <small><?php echo htmlspecialchars($parent['email']); ?></small>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted"><i class="fas fa-phone"></i> Phone</td>
                        <td class="text-end"><?php echo htmlspecialchars($parent['phone']); ?></td>
                    </tr>
                    <?php if (!empty($parent['ic_number'])): ?>
                    <tr>
                        <td class="text-muted"><i class="fas fa-id-badge"></i> IC Number</td>
                        <td class="text-end"><?php echo htmlspecialchars($parent['ic_number']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="text-muted"><i class="fas fa-calendar"></i> Created</td>
                        <td class="text-end"><?php echo date('d M Y', strtotime($parent['created_at'])); ?></td>
                    </tr>
                </table>

                <hr>

                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary btn-sm" onclick="editParent(<?php echo $parent['id']; ?>)">
                        <i class="fas fa-edit"></i> Edit Parent Info
                    </button>
                    <button class="btn btn-outline-success btn-sm" onclick="linkChild(<?php echo $parent['id']; ?>)">
                        <i class="fas fa-user-plus"></i> Link New Child
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Family Summary & Children -->
    <div class="col-lg-8">
        <!-- Family Financial Summary -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon bg-info">
                        <i class="fas fa-child"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo count($children); ?></h3>
                        <p>Total Children</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $total_classes; ?></h3>
                        <p>Active Classes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon <?php echo ($total_outstanding > 0) ? 'bg-danger' : 'bg-success'; ?>">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-content">
                        <h3>RM <?php echo number_format($total_outstanding, 2); ?></h3>
                        <p>Outstanding</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Children List -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-users"></i> Children (<?php echo count($children); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (count($children) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Status</th>
                                <th>School</th>
                                <th>Classes</th>
                                <th>Outstanding</th>
                                <th>Relationship</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($children as $child): ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($child['full_name']); ?></strong>
                                        <?php if ($child['is_primary']): ?>
                                        <span class="badge bg-primary">Primary</span>
                                        <?php endif; ?>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($child['student_id']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($child['student_status'])): ?>
                                    <span class="badge <?php 
                                        echo (strpos($child['student_status'], 'State Team') !== false) ? 'bg-success' : 
                                             ((strpos($child['student_status'], 'Backup Team') !== false) ? 'bg-warning' : 'bg-info');
                                    ?>">
                                        <?php echo htmlspecialchars($child['student_status']); ?>
                                    </span>
                                    <?php else: ?>
                                    <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars($child['school']); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $child['classes_count']; ?></span>
                                </td>
                                <td>
                                    <?php if ($child['outstanding_amount'] > 0): ?>
                                    <span class="badge bg-danger">
                                        RM <?php echo number_format($child['outstanding_amount'], 2); ?>
                                    </span>
                                    <br>
                                    <small class="text-muted"><?php echo $child['unpaid_invoices_count']; ?> invoice(s)</small>
                                    <?php else: ?>
                                    <span class="badge bg-success">Paid</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo ucfirst($child['relationship']); ?></small>
                                </td>
                                <td>
                                    <a href="?page=students&view=<?php echo $child['id']; ?>" 
                                       class="btn btn-sm btn-primary" 
                                       title="View Student Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="unlinkChild(<?php echo $parent['id']; ?>, <?php echo $child['id']; ?>, '<?php echo htmlspecialchars($child['full_name']); ?>')" 
                                            title="Unlink Child">
                                        <i class="fas fa-unlink"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-child fa-3x mb-3 opacity-50"></i>
                    <p>No children linked to this parent account.</p>
                    <button class="btn btn-success" onclick="linkChild(<?php echo $parent['id']; ?>)">
                        <i class="fas fa-user-plus"></i> Link First Child
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-history"></i> Recent Activity</h5>
            </div>
            <div class="card-body">
                <?php if (count($recent_activities) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Registration #</th>
                                <th>Child Name</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_activities as $activity): ?>
                            <tr>
                                <td><small><?php echo htmlspecialchars($activity['registration_number']); ?></small></td>
                                <td><?php echo htmlspecialchars($activity['name_en']); ?></td>
                                <td><small><?php echo date('d M Y', strtotime($activity['created_at'])); ?></small></td>
                                <td>
                                    <span class="badge <?php 
                                        echo ($activity['payment_status'] === 'approved') ? 'bg-success' : 
                                             (($activity['payment_status'] === 'pending') ? 'bg-warning' : 'bg-danger');
                                    ?>">
                                        <?php echo ucfirst($activity['payment_status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center text-muted mb-0">No recent activity.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.opacity-50 {
    opacity: 0.5;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}
</style>

<script>
function editParent(parentId) {
    // TODO: Open modal to edit parent info
    alert('Edit parent feature coming in Phase 3');
}

function linkChild(parentId) {
    // TODO: Open modal to link a child
    alert('Link child feature coming in Phase 2');
}

function unlinkChild(parentId, studentId, studentName) {
    if (confirm('Are you sure you want to unlink ' + studentName + ' from this parent account?\n\nThe student will become an independent account.')) {
        // TODO: Implement unlink API call
        alert('Unlink child feature coming in Phase 2');
    }
}
</script>