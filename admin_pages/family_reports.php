<?php
// admin_pages/family_reports.php - Family Reports and Analytics (Stage 4 Phase 4)
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin.php?page=login');
    exit;
}

// Get filter parameters
$children_filter = isset($_GET['children_filter']) ? $_GET['children_filter'] : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';
$outstanding_filter = isset($_GET['outstanding_filter']) ? $_GET['outstanding_filter'] : '';

// Build base query
$sql = "SELECT 
    pa.id,
    pa.parent_id,
    pa.full_name,
    pa.email,
    pa.phone,
    pa.status,
    COUNT(DISTINCT pcr.student_id) as children_count,
    COUNT(DISTINCT e.id) as total_enrollments,
    COALESCE(SUM(CASE WHEN i.status IN ('unpaid', 'overdue') THEN i.amount ELSE 0 END), 0) as total_outstanding,
    COALESCE(SUM(CASE WHEN i.status = 'paid' THEN i.amount ELSE 0 END), 0) as total_paid,
    COALESCE(SUM(i.amount), 0) as total_invoiced
FROM parent_accounts pa
LEFT JOIN parent_child_relationships pcr ON pa.id = pcr.parent_id
LEFT JOIN students s ON pcr.student_id = s.id
LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
LEFT JOIN invoices i ON s.id = i.student_id
WHERE 1=1";

$params = [];

// Apply filters
if ($children_filter) {
    switch($children_filter) {
        case '1':
            $sql .= " AND (SELECT COUNT(*) FROM parent_child_relationships WHERE parent_id = pa.id) = 1";
            break;
        case '2-3':
            $sql .= " AND (SELECT COUNT(*) FROM parent_child_relationships WHERE parent_id = pa.id) BETWEEN 2 AND 3";
            break;
        case '4+':
            $sql .= " AND (SELECT COUNT(*) FROM parent_child_relationships WHERE parent_id = pa.id) >= 4";
            break;
    }
}

if ($status_filter) {
    $sql .= " AND pa.status = ?";
    $params[] = $status_filter;
}

$sql .= " GROUP BY pa.id";

// Apply outstanding filter after grouping
if ($outstanding_filter) {
    $sql .= " HAVING total_outstanding " . 
            ($outstanding_filter === 'none' ? '= 0' : 
            ($outstanding_filter === 'low' ? 'BETWEEN 0.01 AND 500' : 
            ($outstanding_filter === 'medium' ? 'BETWEEN 500.01 AND 1000' : '> 1000')));
}

$sql .= " ORDER BY children_count DESC, total_outstanding DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$families = $stmt->fetchAll();

// Calculate overall statistics
$total_families = count($families);
$total_children = array_sum(array_column($families, 'children_count'));
$total_enrollments = array_sum(array_column($families, 'total_enrollments'));
$total_outstanding = array_sum(array_column($families, 'total_outstanding'));
$total_paid = array_sum(array_column($families, 'total_paid'));
$total_revenue = array_sum(array_column($families, 'total_invoiced'));

// Get distribution data
$distribution = [
    'single_child' => count(array_filter($families, fn($f) => $f['children_count'] == 1)),
    'two_children' => count(array_filter($families, fn($f) => $f['children_count'] == 2)),
    'three_children' => count(array_filter($families, fn($f) => $f['children_count'] == 3)),
    'four_plus' => count(array_filter($families, fn($f) => $f['children_count'] >= 4))
];
?>

<!-- Overall Statistics -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $total_families; ?></h3>
                <p>Total Families</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-info">
                <i class="fas fa-child"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $total_children; ?></h3>
                <p>Total Children</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-success">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-content">
                <h3>RM <?php echo number_format($total_paid, 2); ?></h3>
                <p>Total Paid</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card">
            <div class="stat-icon <?php echo ($total_outstanding > 0) ? 'bg-danger' : 'bg-success'; ?>">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="stat-content">
                <h3>RM <?php echo number_format($total_outstanding, 2); ?></h3>
                <p>Total Outstanding</p>
            </div>
        </div>
    </div>
</div>

<!-- Family Distribution -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Family Size Distribution</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="p-3">
                            <div class="h2 text-primary"><?php echo $distribution['single_child']; ?></div>
                            <small class="text-muted">Single Child Families</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3">
                            <div class="h2 text-success"><?php echo $distribution['two_children']; ?></div>
                            <small class="text-muted">Two Children Families</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3">
                            <div class="h2 text-warning"><?php echo $distribution['three_children']; ?></div>
                            <small class="text-muted">Three Children Families</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3">
                            <div class="h2" style="color: #7c3aed;"><?php echo $distribution['four_plus']; ?></div>
                            <small class="text-muted">4+ Children Families</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0"><i class="fas fa-filter"></i> Filters</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <input type="hidden" name="page" value="family_reports">
            
            <div class="col-md-3">
                <label class="form-label">Number of Children</label>
                <select class="form-select" name="children_filter">
                    <option value="">All Families</option>
                    <option value="1" <?php echo $children_filter === '1' ? 'selected' : ''; ?>>1 Child</option>
                    <option value="2-3" <?php echo $children_filter === '2-3' ? 'selected' : ''; ?>>2-3 Children</option>
                    <option value="4+" <?php echo $children_filter === '4+' ? 'selected' : ''; ?>>4+ Children</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Account Status</label>
                <select class="form-select" name="status_filter">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Outstanding Amount</label>
                <select class="form-select" name="outstanding_filter">
                    <option value="">All Amounts</option>
                    <option value="none" <?php echo $outstanding_filter === 'none' ? 'selected' : ''; ?>>No Outstanding</option>
                    <option value="low" <?php echo $outstanding_filter === 'low' ? 'selected' : ''; ?>>RM 0.01 - 500</option>
                    <option value="medium" <?php echo $outstanding_filter === 'medium' ? 'selected' : ''; ?>>RM 500 - 1,000</option>
                    <option value="high" <?php echo $outstanding_filter === 'high' ? 'selected' : ''; ?>>Above RM 1,000</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                </div>
            </div>
        </form>
        
        <?php if ($children_filter || $status_filter || $outstanding_filter): ?>
        <div class="mt-3">
            <a href="?page=family_reports" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-times"></i> Clear Filters
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Family Reports Table -->
<div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-table"></i> Family Financial Reports</h5>
        <div>
            <button class="btn btn-light btn-sm" onclick="exportToExcel()">
                <i class="fas fa-file-excel"></i> Export to Excel
            </button>
            <button class="btn btn-light btn-sm" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (count($families) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover data-table" id="familyReportTable">
                <thead>
                    <tr>
                        <th>Parent ID</th>
                        <th>Parent Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Children</th>
                        <th>Classes</th>
                        <th>Total Invoiced</th>
                        <th>Paid</th>
                        <th>Outstanding</th>
                        <th>Payment %</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($families as $family): 
                        $payment_percentage = $family['total_invoiced'] > 0 ? 
                            ($family['total_paid'] / $family['total_invoiced']) * 100 : 100;
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($family['parent_id']); ?></strong></td>
                        <td><?php echo htmlspecialchars($family['full_name']); ?></td>
                        <td><small><?php echo htmlspecialchars($family['email']); ?></small></td>
                        <td><small><?php echo htmlspecialchars($family['phone']); ?></small></td>
                        <td>
                            <span class="badge <?php 
                                echo ($family['children_count'] >= 4) ? 'bg-purple' : 
                                     (($family['children_count'] >= 2) ? 'bg-success' : 'bg-info');
                            ?>">
                                <?php echo $family['children_count']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-primary">
                                <?php echo $family['total_enrollments']; ?>
                            </span>
                        </td>
                        <td>RM <?php echo number_format($family['total_invoiced'], 2); ?></td>
                        <td>
                            <span class="text-success">
                                RM <?php echo number_format($family['total_paid'], 2); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($family['total_outstanding'] > 0): ?>
                            <span class="text-danger">
                                <strong>RM <?php echo number_format($family['total_outstanding'], 2); ?></strong>
                            </span>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar <?php 
                                    echo ($payment_percentage >= 80) ? 'bg-success' : 
                                         (($payment_percentage >= 50) ? 'bg-warning' : 'bg-danger');
                                ?>" 
                                     role="progressbar" 
                                     style="width: <?php echo $payment_percentage; ?>%"
                                     aria-valuenow="<?php echo $payment_percentage; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    <?php echo number_format($payment_percentage, 0); ?>%
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href="?page=parent_details&id=<?php echo $family['id']; ?>" 
                               class="btn btn-sm btn-primary" 
                               title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-secondary">
                        <th colspan="6" class="text-end"><strong>TOTALS:</strong></th>
                        <th><strong>RM <?php echo number_format($total_revenue, 2); ?></strong></th>
                        <th><strong class="text-success">RM <?php echo number_format($total_paid, 2); ?></strong></th>
                        <th><strong class="text-danger">RM <?php echo number_format($total_outstanding, 2); ?></strong></th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-search fa-4x mb-3 opacity-50"></i>
            <p>No families found matching your filters.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
@media print {
    .stat-card,
    .card-header button,
    .btn,
    .admin-sidebar,
    .top-header {
        display: none !important;
    }
}

.bg-purple {
    background: linear-gradient(135deg, #7c3aed, #6d28d9);
    color: white;
}

.opacity-50 {
    opacity: 0.5;
}
</style>

<script>
function exportToExcel() {
    // Simple CSV export
    let csv = 'Parent ID,Parent Name,Email,Phone,Children,Classes,Total Invoiced,Paid,Outstanding,Payment %\n';
    
    const table = document.getElementById('familyReportTable');
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td');
        const data = [
            cols[0].textContent.trim(),
            cols[1].textContent.trim(),
            cols[2].textContent.trim(),
            cols[3].textContent.trim(),
            cols[4].textContent.trim(),
            cols[5].textContent.trim(),
            cols[6].textContent.trim(),
            cols[7].textContent.trim(),
            cols[8].textContent.trim(),
            cols[9].textContent.trim().replace('%', '')
        ];
        csv += data.map(field => '"' + field + '"').join(',') + '\n';
    });
    
    // Download CSV
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'family_reports_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

$(document).ready(function() {
    $('.data-table').DataTable({
        pageLength: 25,
        order: [[8, 'desc']], // Sort by outstanding (descending)
        columnDefs: [
            { orderable: false, targets: 10 } // Disable sorting on Actions column
        ]
    });
});
</script>