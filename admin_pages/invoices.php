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
    .hide-mobile { display: none !important; }
    .table-responsive .table thead { display: none; }
    .table-responsive .table, .table-responsive .table tbody { display: block; }
    .table-responsive .table tbody tr {
        display: block; background: #ffffff; border-radius: 10px;
        margin-bottom: 12px; padding: 12px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb;
    }
    .table-responsive .table tbody tr:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        transform: translateY(-2px); transition: all 0.2s ease;
    }
    .table-responsive .table tbody tr td {
        display: block; border: none; padding: 4px 0; text-align: left !important;
    }
    .table-responsive .table tbody tr td:first-child {
        font-size: 15px; font-weight: bold; padding-bottom: 8px;
        border-bottom: 1px solid #f0f0f0; margin-bottom: 8px;
    }
    .invoice-actions-cell .btn-group {
        width: 100%; display: flex; justify-content: flex-end; gap: 6px; margin-top: 8px;
    }
    .invoice-actions-cell .btn { padding: 8px 12px; }
}

@media (min-width: 769px) {
    .table-responsive .table { display: table !important; }
    .table-responsive .table tbody { display: table-row-group !important; }
    .table-responsive .table tbody tr { display: table-row !important; }
    .table-responsive .table tbody tr td { display: table-cell !important; }
}
</style>

<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-warning"><i class="fas fa-exclamation-circle"></i></div>
            <div class="stat-content"><h3><?php echo $unpaid_count; ?></h3><p>Unpaid Invoices</p></div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-info"><i class="fas fa-clock"></i></div>
            <div class="stat-content"><h3><?php echo $pending_count; ?></h3><p>Pending Verification</p></div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-success"><i class="fas fa-check-circle"></i></div>
            <div class="stat-content"><h3><?php echo $paid_count; ?></h3><p>Paid Invoices</p></div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-danger"><i class="fas fa-dollar-sign"></i></div>
            <div class="stat-content"><h3><?php echo formatCurrency($outstanding_amount); ?></h3><p>Outstanding Amount</p></div>
        </div>
    </div>
</div>

<div class="mb-3 d-flex gap-2 flex-wrap">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createInvoiceModal">
        <i class="fas fa-plus"></i> Create Invoice
    </button>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#generateMonthlyInvoicesModal">
        <i class="fas fa-calendar-alt"></i> Generate Monthly Invoices
    </button>
</div>

<div class="card">
    <div class="card-header"><i class="fas fa-file-invoice-dollar"></i> All Invoices (<?php echo count($all_invoices); ?>)</div>
    <div class="card-body">
        <?php if (count($all_invoices) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Invoice #</th><th>Date</th><th>Student</th>
                            <th class="hide-mobile">Type</th><th class="hide-mobile">Description</th>
                            <th>Amount</th><th class="hide-mobile">Due Date</th>
                            <th>Status</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
<?php foreach ($all_invoices as $invoice): 
    $status_badge = $invoice['status'] === 'paid' ? 'success' : ($invoice['status'] === 'pending' ? 'info' : ($invoice['status'] === 'overdue' ? 'danger' : ($invoice['status'] === 'cancelled' ? 'secondary' : 'warning')));
    $status_text = $invoice['status'] === 'paid' ? 'Paid' : ($invoice['status'] === 'pending' ? 'Pending Verification' : ($invoice['status'] === 'overdue' ? 'Overdue' : ($invoice['status'] === 'cancelled' ? 'Cancelled' : 'Unpaid')));
    $status_icon = $invoice['status'] === 'paid' ? 'check-circle' : ($invoice['status'] === 'pending' ? 'clock' : ($invoice['status'] === 'overdue' ? 'exclamation-triangle' : ($invoice['status'] === 'cancelled' ? 'ban' : 'exclamation-circle')));
?>
    <tr>
        <td class="text-nowrap"><strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong>
            <div class="d-md-none text-muted small"><?php echo formatDate($invoice['created_at']); ?></div></td>
        <td class="hide-mobile"><?php echo formatDate($invoice['created_at']); ?></td>
        <td><div><?php echo htmlspecialchars($invoice['full_name']); ?></div>
            <div class="d-md-none text-muted small"><?php echo htmlspecialchars($invoice['student_id']); ?> • <?php echo ucfirst($invoice['invoice_type']); ?></div></td>
        <td class="hide-mobile"><span class="badge bg-secondary"><?php echo ucfirst($invoice['invoice_type']); ?></span></td>
        <td class="hide-mobile"><?php $desc = htmlspecialchars($invoice['description']); echo (strlen($desc) > 50 ? substr($desc, 0, 50) . '…' : $desc); ?></td>
        <td><strong><?php echo formatCurrency($invoice['amount']); ?></strong>
            <div class="d-md-none text-muted small">Due: <?php echo formatDate($invoice['due_date']); ?></div></td>
        <td class="hide-mobile"><?php echo formatDate($invoice['due_date']); ?></td>
        <td><span class="badge bg-<?php echo $status_badge; ?>"><i class="fas fa-<?php echo $status_icon; ?>"></i> <?php echo $status_text; ?></span></td>
        <td class="invoice-actions-cell">
            <div class="btn-group btn-group-sm">
                <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#viewInvoiceModal<?php echo $invoice['id']; ?>"><i class="fas fa-eye"></i></button>
                <?php if ($invoice['status'] === 'paid'): ?>
                <a href="generate_invoice_pdf.php?invoice_id=<?php echo $invoice['id']; ?>" class="btn btn-success btn-sm" target="_blank"><i class="fas fa-file-pdf"></i></a>
                <?php endif; ?>
                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editInvoiceModal<?php echo $invoice['id']; ?>"><i class="fas fa-edit"></i></button>
                <button class="btn btn-danger" onclick="if(confirm('Delete?')) document.getElementById('deleteInvoiceForm<?php echo $invoice['id']; ?>').submit();"><i class="fas fa-trash"></i></button>
            </div>
            <form id="deleteInvoiceForm<?php echo $invoice['id']; ?>" method="POST" action="admin_handler.php" style="display:none;">
                <input type="hidden" name="action" value="delete_invoice"><input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
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

<?php foreach ($all_invoices as $invoice): ?>
<div class="modal fade" id="viewInvoiceModal<?php echo $invoice['id']; ?>" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-file-invoice"></i> Invoice Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <table class="table table-bordered">
                <tr><th width="40%">Invoice Number</th><td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td></tr>
                <tr><th>Student</th><td><?php echo htmlspecialchars($invoice['full_name']); ?></td></tr>
                <tr><th>Student ID</th><td><span class="badge bg-secondary"><?php echo htmlspecialchars($invoice['student_id']); ?></span></td></tr>
                <?php if ($invoice['class_code']): ?>
                <tr><th>Class</th><td><span class="badge bg-info"><?php echo htmlspecialchars($invoice['class_code']); ?></span> <?php echo htmlspecialchars($invoice['class_name']); ?></td></tr>
                <?php endif; ?>
                <tr><th>Type</th><td><span class="badge bg-secondary"><?php echo ucfirst($invoice['invoice_type']); ?></span></td></tr>
                <tr><th>Description</th><td><?php echo nl2br(htmlspecialchars($invoice['description'])); ?></td></tr>
                <tr><th>Amount</th><td><strong><?php echo formatCurrency($invoice['amount']); ?></strong></td></tr>
                <tr><th>Due Date</th><td><?php echo formatDate($invoice['due_date']); ?></td></tr>
                <tr><th>Status</th><td><span class="badge bg-<?php echo $invoice['status'] === 'paid' ? 'success' : 'warning'; ?>"><?php echo ucfirst($invoice['status']); ?></span></td></tr>
                <?php if ($invoice['paid_date']): ?>
                <tr><th>Paid Date</th><td><?php echo formatDateTime($invoice['paid_date']); ?></td></tr>
                <?php endif; ?>
                <tr><th>Created Date</th><td><?php echo formatDateTime($invoice['created_at']); ?></td></tr>
            </table>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
    </div></div>
</div>

<div class="modal fade" id="editInvoiceModal<?php echo $invoice['id']; ?>" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-edit"></i> Edit Invoice</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" action="admin_handler.php">
            <div class="modal-body">
                <input type="hidden" name="action" value="edit_invoice">
                <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                <div class="mb-3"><label class="form-label">Invoice Number</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($invoice['invoice_number']); ?>" disabled></div>
                <div class="mb-3"><label class="form-label">Description *</label>
                    <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($invoice['description']); ?></textarea></div>
                <div class="mb-3"><label class="form-label">Amount *</label>
                    <input type="number" name="amount" class="form-control" step="0.01" value="<?php echo $invoice['amount']; ?>" required></div>
                <div class="mb-3"><label class="form-label">Due Date *</label>
                    <input type="date" name="due_date" class="form-control" value="<?php echo $invoice['due_date']; ?>" required></div>
                <div class="mb-3"><label class="form-label">Status *</label>
                    <select name="status" class="form-select" required>
                        <option value="unpaid" <?php echo $invoice['status'] === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                        <option value="pending" <?php echo $invoice['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="paid" <?php echo $invoice['status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="overdue" <?php echo $invoice['status'] === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                        <option value="cancelled" <?php echo $invoice['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
            </div>
        </form>
    </div></div>
</div>
<?php endforeach; ?>

<div class="modal fade" id="createInvoiceModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-plus"></i> Create New Invoice</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" action="admin_handler.php">
            <div class="modal-body">
                <input type="hidden" name="action" value="create_invoice">
                <div class="mb-3"><label class="form-label">Student *</label>
                    <select name="student_id" class="form-select" required>
                        <option value="">Select Student</option>
                        <?php foreach ($all_students as $student): ?>
                            <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['full_name']); ?> (<?php echo htmlspecialchars($student['student_id']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">Invoice Type *</label>
                    <select name="invoice_type" class="form-select" required>
                        <option value="">Select Type</option>
                        <option value="monthly_fee">Monthly Fee</option>
                        <option value="registration">Registration</option>
                        <option value="equipment">Equipment</option>
                        <option value="event">Event</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">Class (Optional)</label>
                    <select name="class_id" class="form-select">
                        <option value="">Select Class</option>
                        <?php foreach ($all_classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_code']); ?> - <?php echo htmlspecialchars($class['class_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">Description *</label>
                    <textarea name="description" class="form-control" rows="3" required></textarea></div>
                <div class="mb-3"><label class="form-label">Amount (RM) *</label>
                    <input type="number" name="amount" class="form-control" step="0.01" required></div>
                <div class="mb-3"><label class="form-label">Due Date *</label>
                    <input type="date" name="due_date" class="form-control" required></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Create</button>
            </div>
        </form>
    </div></div>
</div>

<div class="modal fade" id="generateMonthlyInvoicesModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header bg-success text-white">
            <h5 class="modal-title"><i class="fas fa-calendar-alt"></i> Generate Monthly Invoices</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" action="admin_handler.php" onsubmit="return confirm('Generate monthly invoices for <?php echo date('F Y'); ?>?\n\nDue date: <?php echo date('F 10, Y'); ?>');">
            <div class="modal-body">
                <input type="hidden" name="action" value="generate_monthly_invoices">
                <div class="alert alert-info"><h6><i class="fas fa-info-circle"></i> About</h6>
                    <p class="mb-0">Creates monthly fee invoices for all students with active enrollments.</p></div>
                <div class="mb-3"><label class="form-label">Month:</label><p class="fw-bold"><?php echo date('F Y'); ?></p></div>
                <div class="mb-3"><label class="form-label">Due Date:</label><p class="fw-bold"><?php echo date('F 10, Y'); ?> (10th)</p></div>
                <div class="alert alert-warning"><strong><i class="fas fa-exclamation-triangle"></i> Important:</strong>
                    <ul class="mb-0">
                        <li>Only active enrollments</li><li>Duplicates skipped</li><li>Cannot be undone</li><li>Due date: 10th of month</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-play-circle"></i> Generate</button>
            </div>
        </form>
    </div></div>
</div>