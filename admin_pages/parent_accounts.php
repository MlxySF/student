<?php
// admin_pages/parent_accounts.php - Parent Accounts Management (Stage 4)
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin.php?page=login');
    exit;
}

// Get all parent accounts with children count and outstanding amounts
$sql = "SELECT 
    pa.id,
    pa.parent_id,
    pa.full_name,
    pa.email,
    pa.phone,
    pa.ic_number,
    pa.status,
    pa.created_at,
    COUNT(DISTINCT pcr.student_id) as children_count,
    COALESCE(SUM(CASE WHEN i.status IN ('unpaid', 'overdue') THEN i.amount ELSE 0 END), 0) as total_outstanding
FROM parent_accounts pa
LEFT JOIN parent_child_relationships pcr ON pa.id = pcr.parent_id
LEFT JOIN students s ON pcr.student_id = s.id
LEFT JOIN invoices i ON s.id = i.student_id AND i.status IN ('unpaid', 'overdue')
GROUP BY pa.id
ORDER BY pa.created_at DESC";

$stmt = $pdo->query($sql);
$parents = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo count($parents); ?></h3>
                <p>Total Parents</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-success">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo count(array_filter($parents, fn($p) => $p['status'] === 'active')); ?></h3>
                <p>Active Parents</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-info">
                <i class="fas fa-child"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo array_sum(array_column($parents, 'children_count')); ?></h3>
                <p>Total Children</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-danger">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-content">
                <h3>RM <?php echo number_format(array_sum(array_column($parents, 'total_outstanding')), 2); ?></h3>
                <p>Total Outstanding</p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-users"></i> Parent Accounts</h5>
        <div>
            <button class="btn btn-light btn-sm" onclick="location.reload()">
                <i class="fas fa-sync"></i> Refresh
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (count($parents) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>Parent ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Children</th>
                        <th>Outstanding</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($parents as $parent): ?>
                    <tr>
                        <td>
                            <strong class="text-primary"><?php echo htmlspecialchars($parent['parent_id']); ?></strong>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" 
                                     style="width: 35px; height: 35px; font-size: 14px; font-weight: bold;">
                                    <?php echo strtoupper(substr($parent['full_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <strong><?php echo htmlspecialchars($parent['full_name']); ?></strong>
                                </div>
                            </div>
                        </td>
                        <td>
                            <small><?php echo htmlspecialchars($parent['email']); ?></small>
                        </td>
                        <td>
                            <small><?php echo htmlspecialchars($parent['phone']); ?></small>
                        </td>
                        <td>
                            <span class="badge <?php 
                                echo ($parent['children_count'] >= 4) ? 'bg-purple' : 
                                     (($parent['children_count'] >= 2) ? 'bg-success' : 'bg-info');
                            ?>">
                                <?php echo $parent['children_count']; ?> child<?php echo $parent['children_count'] != 1 ? 'ren' : ''; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($parent['total_outstanding'] > 0): ?>
                            <span class="badge bg-danger">
                                RM <?php echo number_format($parent['total_outstanding'], 2); ?>
                            </span>
                            <?php else: ?>
                            <span class="badge bg-success">Paid</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?php echo ($parent['status'] === 'active') ? 'bg-success' : 'bg-secondary'; ?>">
                                <?php echo ucfirst($parent['status']); ?>
                            </span>
                        </td>
                        <td>
                            <small><?php echo date('d M Y', strtotime($parent['created_at'])); ?></small>
                        </td>
                        <td>
                            <a href="?page=parent_details&id=<?php echo $parent['id']; ?>" 
                               class="btn btn-sm btn-primary" 
                               title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-users fa-4x mb-3 opacity-50"></i>
            <p>No parent accounts found.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.bg-purple {
    background: linear-gradient(135deg, #7c3aed, #6d28d9);
    color: white;
}

.opacity-50 {
    opacity: 0.5;
}

.table tbody tr {
    vertical-align: middle;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}
</style>

<script>
$(document).ready(function() {
    $('.data-table').DataTable({
        pageLength: 25,
        order: [[7, 'desc']], // Sort by created date
        columnDefs: [
            { orderable: false, targets: 8 } // Disable sorting on Actions column
        ]
    });
});
</script>