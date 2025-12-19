<?php
// admin_pages/family_reports.php - Family Reports (Stage 4 Phase 4)
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin.php?page=login');
    exit;
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$min_children = isset($_GET['min_children']) ? intval($_GET['min_children']) : 0;
$has_outstanding = isset($_GET['has_outstanding']) ? $_GET['has_outstanding'] : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'total_outstanding';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';

// Build query
$sql = "
    SELECT 
        pa.id,
        pa.parent_id,
        pa.full_name,
        pa.email,
        pa.phone,
        pa.status,
        pa.created_at,
        COUNT(DISTINCT pcr.student_id) as children_count,
        COUNT(DISTINCT e.id) as total_enrollments,
        COALESCE(SUM(CASE WHEN i.status = 'paid' THEN i.amount ELSE 0 END), 0) as total_paid,
        COALESCE(SUM(CASE WHEN i.status IN ('unpaid', 'overdue') THEN i.amount ELSE 0 END), 0) as total_outstanding,
        COALESCE(SUM(i.amount), 0) as total_invoiced,
        COUNT(DISTINCT CASE WHEN i.status IN ('unpaid', 'overdue') THEN i.id END) as unpaid_invoices_count,
        COUNT(DISTINCT CASE WHEN i.status = 'paid' THEN i.id END) as paid_invoices_count
    FROM parent_accounts pa
    LEFT JOIN parent_child_relationships pcr ON pa.id = pcr.parent_id
    LEFT JOIN students s ON pcr.student_id = s.id
    LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
    LEFT JOIN invoices i ON s.id = i.student_id
    WHERE 1=1
";

$params = [];

// Apply filters
if ($status_filter) {
    $sql .= " AND pa.status = ?";
    $params[] = $status_filter;
}

$sql .= " GROUP BY pa.id";

// Apply HAVING filters (after GROUP BY)
if ($min_children > 0) {
    $sql .= " HAVING children_count >= ?";
    $params[] = $min_children;
}

if ($has_outstanding === 'yes') {
    $sql .= ($min_children > 0 ? " AND" : " HAVING") . " total_outstanding > 0";
} elseif ($has_outstanding === 'no') {
    $sql .= ($min_children > 0 ? " AND" : " HAVING") . " total_outstanding = 0";
}

// Apply sorting
$allowed_sort = ['parent_id', 'full_name', 'children_count', 'total_outstanding', 'total_paid', 'created_at'];
if (in_array($sort_by, $allowed_sort)) {
    $sql .= " ORDER BY {$sort_by} " . ($sort_order === 'ASC' ? 'ASC' : 'DESC');
} else {
    $sql .= " ORDER BY total_outstanding DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$families = $stmt->fetchAll();

// Calculate totals
$total_families = count($families);
$total_children = array_sum(array_column($families, 'children_count'));
$total_outstanding_all = array_sum(array_column($families, 'total_outstanding'));
$total_paid_all = array_sum(array_column($families, 'total_paid'));
$total_invoiced_all = array_sum(array_column($families, 'total_invoiced'));
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4><i class="fas fa-chart-bar"></i> Family Financial Reports</h4>
                <p class="text-muted mb-0">Comprehensive view of all family accounts with financial summaries</p>
            </div>
            <div>
                <button class="btn btn-success" onclick="exportToExcel()">
                    <i class="fas fa-file-excel"></i> Export to Excel
                </button>
                <button class="btn btn-primary" onclick="printReport()">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
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
    <div class="col-md-3">
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
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-success">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-content">
                <h3>RM <?php echo number_format($total_paid_all, 2); ?></h3>
                <p>Total Paid</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-danger">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <h3>RM <?php echo number_format($total_outstanding_all, 2); ?></h3>
                <p>Total Outstanding</p>
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
                <label class="form-label">Parent Status</label>
                <select class="form-select" name="status">
                    <option value="" <?php echo $status_filter === '' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active Only</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Minimum Children</label>
                <select class="form-select" name="min_children">
                    <option value="0" <?php echo $min_children === 0 ? 'selected' : ''; ?>>All</option>
                    <option value="1" <?php echo $min_children === 1 ? 'selected' : ''; ?>>1+</option>
                    <option value="2" <?php echo $min_children === 2 ? 'selected' : ''; ?>>2+</option>
                    <option value="3" <?php echo $min_children === 3 ? 'selected' : ''; ?>>3+</option>
                    <option value="4" <?php echo $min_children === 4 ? 'selected' : ''; ?>>4+</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Outstanding Balance</label>
                <select class="form-select" name="has_outstanding">
                    <option value="" <?php echo $has_outstanding === '' ? 'selected' : ''; ?>>All</option>
                    <option value="yes" <?php echo $has_outstanding === 'yes' ? 'selected' : ''; ?>>Has Outstanding</option>
                    <option value="no" <?php echo $has_outstanding === 'no' ? 'selected' : ''; ?>>Fully Paid</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Sort By</label>
                <div class="input-group">
                    <select class="form-select" name="sort_by">
                        <option value="total_outstanding" <?php echo $sort_by === 'total_outstanding' ? 'selected' : ''; ?>>Outstanding Amount</option>
                        <option value="total_paid" <?php echo $sort_by === 'total_paid' ? 'selected' : ''; ?>>Paid Amount</option>
                        <option value="children_count" <?php echo $sort_by === 'children_count' ? 'selected' : ''; ?>>Children Count</option>
                        <option value="full_name" <?php echo $sort_by === 'full_name' ? 'selected' : ''; ?>>Parent Name</option>
                        <option value="created_at" <?php echo $sort_by === 'created_at' ? 'selected' : ''; ?>>Created Date</option>
                    </select>
                    <select class="form-select" name="sort_order" style="max-width: 100px;">
                        <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>DESC</option>
                        <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>ASC</option>
                    </select>
                </div>
            </div>
            
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Apply Filters
                </button>
                <a href="?page=family_reports" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Report Table -->
<div class="card" id="reportTable">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-table"></i> Family Financial Summary</h5>
    </div>
    <div class="card-body">
        <?php if (count($families) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover data-table" id="familyReportTable">
                <thead>
                    <tr>
                        <th>Parent ID</th>
                        <th>Parent Name</th>
                        <th>Contact</th>
                        <th>Children</th>
                        <th>Enrollments</th>
                        <th>Total Invoiced</th>
                        <th>Total Paid</th>
                        <th>Outstanding</th>
                        <th>Payment Rate</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($families as $family): 
                        $payment_rate = ($family['total_invoiced'] > 0) 
                            ? ($family['total_paid'] / $family['total_invoiced'] * 100) 
                            : 0;
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($family['parent_id']); ?></strong></td>
                        <td><?php echo htmlspecialchars($family['full_name']); ?></td>
                        <td>
                            <small>
                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($family['email']); ?><br>
                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($family['phone']); ?>
                            </small>
                        </td>
                        <td>
                            <span class="badge <?php 
                                echo ($family['children_count'] >= 4) ? 'bg-purple' : 
                                     (($family['children_count'] >= 2) ? 'bg-success' : 'bg-info');
                            ?>">
                                <?php echo $family['children_count']; ?>
                            </span>
                        </td>
                        <td><?php echo $family['total_enrollments']; ?></td>
                        <td>RM <?php echo number_format($family['total_invoiced'], 2); ?></td>
                        <td class="text-success"><strong>RM <?php echo number_format($family['total_paid'], 2); ?></strong></td>
                        <td>
                            <?php if ($family['total_outstanding'] > 0): ?>
                            <span class="text-danger"><strong>RM <?php echo number_format($family['total_outstanding'], 2); ?></strong></span><br>
                            <small class="text-muted"><?php echo $family['unpaid_invoices_count']; ?> invoice(s)</small>
                            <?php else: ?>
                            <span class="badge bg-success">Paid</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar <?php 
                                    echo ($payment_rate >= 80) ? 'bg-success' : 
                                         (($payment_rate >= 50) ? 'bg-warning' : 'bg-danger');
                                ?>" 
                                     role="progressbar" 
                                     style="width: <?php echo min($payment_rate, 100); ?>%">
                                    <?php echo number_format($payment_rate, 0); ?>%
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?php echo ($family['status'] === 'active') ? 'bg-success' : 'bg-secondary'; ?>">
                                <?php echo ucfirst($family['status']); ?>
                            </span>
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
                        <th colspan="5" class="text-end"><strong>TOTALS:</strong></th>
                        <th><strong>RM <?php echo number_format($total_invoiced_all, 2); ?></strong></th>
                        <th class="text-success"><strong>RM <?php echo number_format($total_paid_all, 2); ?></strong></th>
                        <th class="text-danger"><strong>RM <?php echo number_format($total_outstanding_all, 2); ?></strong></th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-chart-bar fa-4x mb-3 opacity-50"></i>
            <p>No families found matching the selected filters.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.bg-purple {
    background: linear-gradient(135deg, #7c3aed, #6d28d9);
    color: white;
}

.stat-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}

.stat-content h3 {
    margin: 0;
    font-size: 28px;
    font-weight: bold;
}

.stat-content p {
    margin: 0;
    color: #6c757d;
    font-size: 14px;
}

@media print {
    .btn, .card-header, .stat-card {
        display: none !important;
    }
}
</style>

<script>
const reportData = <?php echo json_encode($families); ?>;

function exportToExcel() {
    // Prepare data for export
    let csvContent = "Parent ID,Parent Name,Email,Phone,Children,Enrollments,Total Invoiced,Total Paid,Outstanding,Payment Rate,Status\n";
    
    reportData.forEach(family => {
        const paymentRate = (family.total_invoiced > 0) 
            ? (family.total_paid / family.total_invoiced * 100).toFixed(2) 
            : 0;
        
        csvContent += `"${family.parent_id}","${family.full_name}","${family.email}","${family.phone}",${family.children_count},${family.total_enrollments},${family.total_invoiced},${family.total_paid},${family.total_outstanding},${paymentRate}%,"${family.status}"\n`;
    });
    
    // Add totals row
    csvContent += `\n"TOTALS","","","",<?php echo $total_children; ?>,"",<?php echo $total_invoiced_all; ?>,<?php echo $total_paid_all; ?>,<?php echo $total_outstanding_all; ?>,"",""\n`;
    
    // Create download
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'family_financial_report_<?php echo date('Y-m-d'); ?>.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function printReport() {
    window.print();
}

$(document).ready(function() {
    $('.data-table').DataTable({
        pageLength: 50,
        order: [[7, 'desc']], // Sort by outstanding
        columnDefs: [
            { orderable: false, targets: [10] } // Disable sorting on Actions
        ]
    });
});
</script>