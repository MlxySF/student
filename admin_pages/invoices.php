<?php
// Get invoice statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM invoices WHERE status = 'unpaid'");
$unpaid_count = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM invoices WHERE status = 'pending'");
$pending_count = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM invoices WHERE status = 'paid'");
$paid_count = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT SUM(amount) as total FROM invoices WHERE status IN ('unpaid', 'pending')");
$outstanding_amount = $stmt->fetch()['total'] ?? 0;

// Get all invoices
$stmt = $pdo->query("
    SELECT i.*, s.student_id, s.full_name, s.email, c.class_code, c.class_name
    FROM invoices i
    JOIN students s ON i.student_id = s.id
    LEFT JOIN classes c ON i.class_id = c.id
    ORDER BY 
        CASE 
            WHEN i.status = 'unpaid' THEN 1
            WHEN i.status = 'pending' THEN 2
            WHEN i.status = 'overdue' THEN 3
            WHEN i.status = 'paid' THEN 4
            ELSE 5
        END,
        i.created_at DESC
");
$all_invoices = $stmt->fetchAll();

// Get students and classes for creating invoices
$all_students = $pdo->query("SELECT id, student_id, full_name, email FROM students ORDER BY full_name")->fetchAll();
$all_classes = $pdo->query("SELECT id, class_code, class_name FROM classes ORDER BY class_code")->fetchAll();
?>

<style>
@media (max-width: 768px) {
    .hide-mobile {
        display: none !important;
    }

    .table-responsive .table thead {
        display: none;
    }

    .table-responsive .table,
    .table-responsive .table tbody {
        display: block;
    }

    .table-responsive .table tbody tr {
        display: block;
        background: #ffffff;
        border-radius: 10px;
        margin-bottom: 12px;
        padding: 12px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
    }

    .table-responsive .table tbody tr:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        transform: translateY(-2px);
        transition: all 0.2s ease;
    }

    .table-responsive .table tbody tr td {
        display: block;
        border: none;
        padding: 4px 0;
        text-align: left !important;
    }

    .table-responsive .table tbody tr td:first-child {
        font-size: 15px;
        font-weight: bold;
        padding-bottom: 8px;
        border-bottom: 1px solid #f0f0f0;
        margin-bottom: 8px;
    }

    .invoice-actions-cell .btn-group {
        width: 100%;
        display: flex;
        justify-content: flex-end;
        gap: 6px;
        margin-top: 8px;
    }

    .invoice-actions-cell .btn {
        padding: 8px 12px;
    }
}

@media (min-width: 769px) {
    .table-responsive .table {
        display: table !important;
    }

    .table-responsive .table tbody {
        display: table-row-group !important;
    }

    .table-responsive .table tbody tr {
        display: table-row !important;
    }

    .table-responsive .table tbody tr td {
        display: table-cell !important;
    }
}
</style>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-warning">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $unpaid_count; ?></h3>
                <p>Unpaid Invoices</p>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-info">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $pending_count; ?></h3>
                <p>Pending Verification</p>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $paid_count; ?></h3>
                <p>Paid Invoices</p>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-danger">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo formatCurrency($outstanding_amount); ?></h3>
                <p>Outstanding Amount</p>
            </div>
        </div>
    </div>
</div>

<!-- Action Buttons -->
<div class="mb-3 d-flex gap-2 flex-wrap">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createInvoiceModal">
        <i class="fas fa-plus"></i> Create Invoice
    </button>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#generateMonthlyInvoicesModal">
        <i class="fas fa-calendar-alt"></i> Generate Monthly Invoices
    </button>
</div>

<!-- Invoices Table -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-file-invoice-dollar"></i> All Invoices (<?php echo count($all_invoices); ?>)
    </div>
    <div class="card-body">
        <?php if (count($all_invoices) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Date</th>
                            <th>Student</th>
                            <th class="hide-mobile">Type</th>
                            <th class="hide-mobile">Description</th>
                            <th>Amount</th>
                            <th class="hide-mobile">Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
<?php foreach ($all_invoices as $invoice): 
    if ($invoice['status'] === 'paid') {
        $status_badge = 'success';
        $status_text = 'Paid';
        $status_icon = 'check-circle';
    } elseif ($invoice['status'] === 'pending') {
        $status_badge = 'info';
        $status_text = 'Pending Verification';
        $status_icon = 'clock';
    } elseif ($invoice['status'] === 'overdue') {
        $status_badge = 'danger';
        $status_text = 'Overdue';
        $status_icon = 'exclamation-triangle';
    } elseif ($invoice['status'] === 'cancelled') {
        $status_badge = 'secondary';
        $status_text = 'Cancelled';
        $status_icon = 'ban';
    } else {
        $status_badge = 'warning';
        $status_text = 'Unpaid';
        $status_icon = 'exclamation-circle';
    }
?>
    <tr class="invoice-row">
        <td class="text-nowrap">
            <strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong>
            <div class="d-md-none text-muted small">
                <?php echo formatDate($invoice['created_at']); ?>
            </div>
        </td>
        <td class="hide-mobile"><?php echo formatDate($invoice['created_at']); ?></td>
        <td>
            <div class="invoice-student-main"><?php echo htmlspecialchars($invoice['full_name']); ?></div>
            <div class="d-md-none text-muted small">
                <?php echo htmlspecialchars($invoice['student_id']); ?> • <?php echo ucfirst($invoice['invoice_type']); ?>
            </div>
        </td>
        <td class="hide-mobile"><span class="badge bg-secondary"><?php echo ucfirst($invoice['invoice_type']); ?></span></td>
        <td class="hide-mobile">
            <?php
                $desc = htmlspecialchars($invoice['description']);
                echo (strlen($desc) > 50 ? substr($desc, 0, 50) . '…' : $desc);
            ?>
        </td>
        <td>
            <strong><?php echo formatCurrency($invoice['amount']); ?></strong>
            <div class="d-md-none text-muted small">Due: <?php echo formatDate($invoice['due_date']); ?></div>
        </td>
        <td class="hide-mobile"><?php echo formatDate($invoice['due_date']); ?></td>
        <td>
            <span class="badge bg-<?php echo $status_badge; ?>">
                <i class="fas fa-<?php echo $status_icon; ?>"></i> <?php echo $status_text; ?>
            </span>
        </td>
        <td class="invoice-actions-cell">
            <div class="btn-group btn-group-sm" role="group">
                <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#viewInvoiceModal<?php echo $invoice['id']; ?>" title="View Details">
                    <i class="fas fa-eye"></i>
                </button>
                <?php if ($invoice['status'] === 'paid'): ?>
                <a href="generate_invoice_pdf.php?invoice_id=<?php echo $invoice['id']; ?>" class="btn btn-success btn-sm" target="_blank" title="Download PDF">
                    <i class="fas fa-file-pdf"></i>
                </a>
                <?php endif; ?>
                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editInvoiceModal<?php echo $invoice['id']; ?>" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-danger" onclick="if(confirm('Delete this invoice?')) document.getElementById('deleteInvoiceForm<?php echo $invoice['id']; ?>').submit();" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <form id="deleteInvoiceForm<?php echo $invoice['id']; ?>" method="POST" style="display:none;">
                <input type="hidden" name="action" value="delete_invoice">
                <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
            </form>
        </td>
    </tr>
<?php endforeach; ?>
</tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info"><i class="fas fa-info-circle"></i> No invoices found.</div>
        <?php endif; ?>
    </div>
</div>

<!-- Generate Monthly Invoices Modal -->
<div class="modal fade" id="generateMonthlyInvoicesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-calendar-alt"></i> Generate Monthly Invoices</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="admin_handler.php" id="generateInvoicesForm" onsubmit="return confirmGenerate();">
                <div class="modal-body">
                    <input type="hidden" name="action" value="generate_monthly_invoices">
                    
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> About This Feature</h6>
                        <p class="mb-0">This will create monthly fee invoices for all students with active enrollments.</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Current Month:</label>
                        <p class="fw-bold"><?php echo date('F Y'); ?></p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Due Date:</label>
                        <p class="fw-bold"><?php echo date('F 10, Y'); ?> (10th of the month)</p>
                    </div>

                    <div class="alert alert-warning">
                        <strong><i class="fas fa-exclamation-triangle"></i> Important:</strong>
                        <ul class="mb-0">
                            <li>Only students with active enrollments will get invoices</li>
                            <li>Students already invoiced this month will be skipped</li>
                            <li>This action cannot be undone</li>
                            <li>All invoices will be marked as "Unpaid"</li>
                            <li>Due date will be set to the 10th of this month</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-play-circle"></i> Generate Invoices
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmGenerate() {
    return confirm(
        'GENERATE MONTHLY INVOICES?\n\n' +
        'This will create invoices for ALL students with active enrollments.\n\n' +
        'Month: <?php echo date("F Y"); ?>\n' +
        'Due Date: <?php echo date("F 10, Y"); ?> (10th of the month)\n\n' +
        'Click OK to proceed or Cancel to abort.'
    );
}
</script>