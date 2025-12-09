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

    /* Convert table to cards on mobile */
    .table-responsive .table thead {
        display: none; /* Hide table headers on mobile */
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

    /* Action buttons full width on mobile */
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

/* Keep normal table on desktop - NO changes above 768px */
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

<!-- Create Invoice Button -->
<div class="mb-3">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createInvoiceModal">
        <i class="fas fa-plus"></i> Create Invoice
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
    // Determine status display
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

        <td class="hide-mobile">
            <?php echo formatDate($invoice['created_at']); ?>
        </td>

        <td>
            <div class="invoice-student-main">
                <?php echo htmlspecialchars($invoice['full_name']); ?>
            </div>
            <div class="d-md-none text-muted small">
                <?php echo htmlspecialchars($invoice['student_id']); ?> •
                <?php echo ucfirst($invoice['invoice_type']); ?>
            </div>
        </td>

        <td class="hide-mobile">
            <span class="badge bg-secondary"><?php echo ucfirst($invoice['invoice_type']); ?></span>
        </td>

        <td class="hide-mobile">
            <?php
                $desc = htmlspecialchars($invoice['description']);
                echo (strlen($desc) > 50 ? substr($desc, 0, 50) . '…' : $desc);
            ?>
        </td>

        <td>
            <strong><?php echo formatCurrency($invoice['amount']); ?></strong>
            <div class="d-md-none text-muted small">
                Due: <?php echo formatDate($invoice['due_date']); ?>
            </div>
        </td>

        <td class="hide-mobile">
            <?php echo formatDate($invoice['due_date']); ?>
        </td>

        <td>
            <span class="badge bg-<?php echo $status_badge; ?>">
                <i class="fas fa-<?php echo $status_icon; ?>"></i> <?php echo $status_text; ?>
            </span>
        </td>

        <td class="invoice-actions-cell">
            <div class="btn-group btn-group-sm" role="group">
                <button class="btn btn-info"
                        data-bs-toggle="modal"
                        data-bs-target="#viewInvoiceModal<?php echo $invoice['id']; ?>"
                        title="View Details">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-warning"
                        data-bs-toggle="modal"
                        data-bs-target="#editInvoiceModal<?php echo $invoice['id']; ?>"
                        title="Edit Invoice">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-danger"
                        onclick="if(confirm('Delete this invoice? This action cannot be undone.')) document.getElementById('deleteInvoiceForm<?php echo $invoice['id']; ?>').submit();"
                        title="Delete Invoice">
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
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No invoices found. Create your first invoice!
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- View/Edit/Delete Modals (Outside table) -->
<?php foreach ($all_invoices as $invoice): 
    // Determine status display for modal
    if ($invoice['status'] === 'paid') {
        $modal_status_badge = 'success';
        $modal_status_text = 'Paid';
    } elseif ($invoice['status'] === 'pending') {
        $modal_status_badge = 'info';
        $modal_status_text = 'Pending Verification';
    } elseif ($invoice['status'] === 'overdue') {
        $modal_status_badge = 'danger';
        $modal_status_text = 'Overdue';
    } elseif ($invoice['status'] === 'cancelled') {
        $modal_status_badge = 'secondary';
        $modal_status_text = 'Cancelled';
    } else {
        $modal_status_badge = 'warning';
        $modal_status_text = 'Unpaid';
    }
?>
    <!-- View Invoice Modal -->
    <div class="modal fade" id="viewInvoiceModal<?php echo $invoice['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-invoice"></i> Invoice Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if ($invoice['status'] === 'pending'): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <strong>Payment Pending:</strong> Student has uploaded payment receipt. Please verify in the Payments section.
                        </div>
                    <?php endif; ?>
                    <table class="table table-bordered">
                        <tr>
                            <th width="40%">Invoice Number</th>
                            <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                        </tr>
                        <tr>
                            <th>Student</th>
                            <td><?php echo htmlspecialchars($invoice['full_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Student ID</th>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($invoice['student_id']); ?></span></td>
                        </tr>
                        <?php if ($invoice['class_code']): ?>
                        <tr>
                            <th>Class</th>
                            <td><span class="badge bg-info"><?php echo htmlspecialchars($invoice['class_code']); ?></span> <?php echo htmlspecialchars($invoice['class_name']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Type</th>
                            <td><span class="badge bg-secondary"><?php echo ucfirst($invoice['invoice_type']); ?></span></td>
                        </tr>
                        <tr>
                            <th>Description</th>
                            <td><?php echo nl2br(htmlspecialchars($invoice['description'])); ?></td>
                        </tr>
                        <tr>
                            <th>Amount</th>
                            <td><strong><?php echo formatCurrency($invoice['amount']); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Due Date</th>
                            <td><?php echo formatDate($invoice['due_date']); ?></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td><span class="badge bg-<?php echo $modal_status_badge; ?>"><?php echo $modal_status_text; ?></span></td>
                        </tr>
                        <?php if ($invoice['paid_date']): ?>
                        <tr>
                            <th>Paid Date</th>
                            <td><?php echo formatDateTime($invoice['paid_date']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Created Date</th>
                            <td><?php echo formatDateTime($invoice['created_at']); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="modal-footer">
                    <?php if ($invoice['status'] === 'pending'): ?>
                        <a href="?page=payments" class="btn btn-info">
                            <i class="fas fa-eye"></i> View Payment Receipt
                        </a>
                    <?php endif; ?>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Invoice Modal -->
    <div class="modal fade" id="editInvoiceModal<?php echo $invoice['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_invoice">
                        <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">

                        <?php if ($invoice['status'] === 'pending'): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> This invoice has a pending payment. Changing status manually may cause issues. Verify payment in Payments section instead.
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Invoice Number</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($invoice['invoice_number']); ?>" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($invoice['description']); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Amount *</label>
                            <input type="number" name="amount" class="form-control" step="0.01" value="<?php echo $invoice['amount']; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Due Date *</label>
                            <input type="date" name="due_date" class="form-control" value="<?php echo $invoice['due_date']; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-select" required>
                                <option value="unpaid" <?php echo $invoice['status'] === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                                <option value="pending" <?php echo $invoice['status'] === 'pending' ? 'selected' : ''; ?>>Pending Verification</option>
                                <option value="paid" <?php echo $invoice['status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                <option value="overdue" <?php echo $invoice['status'] === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                                <option value="cancelled" <?php echo $invoice['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            <div class="form-text">Note: Use Payments page to properly verify payments.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Invoice
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<!-- Create Invoice Modal -->
<div class="modal fade" id="createInvoiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Create New Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_invoice">

                    <div class="mb-3">
                        <label class="form-label">Student *</label>
                        <select name="student_id" class="form-select" required>
                            <option value="">Select Student</option>
                            <?php foreach ($all_students as $student): ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo htmlspecialchars($student['full_name']); ?> 
                                    (<?php echo htmlspecialchars($student['student_id']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Invoice Type *</label>
                        <select name="invoice_type" class="form-select" id="invoiceType" required>
                            <option value="">Select Type</option>
                            <option value="monthly_fee">Monthly Fee</option>
                            <option value="registration">Registration</option>
                            <option value="equipment">Equipment</option>
                            <option value="event">Event</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3" id="classSelectDiv">
                        <label class="form-label">Class (Optional)</label>
                        <select name="class_id" class="form-select">
                            <option value="">Select Class</option>
                            <?php foreach ($all_classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>">
                                    <?php echo htmlspecialchars($class['class_code']); ?> - 
                                    <?php echo htmlspecialchars($class['class_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description *</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="E.g. Monthly tuition for January 2025" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Amount (RM) *</label>
                        <input type="number" name="amount" class="form-control" step="0.01" placeholder="150.00" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Due Date *</label>
                        <input type="date" name="due_date" class="form-control" required>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Invoice number will be auto-generated. Status will be set to "Unpaid".
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>