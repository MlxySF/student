<?php
// Student Invoices Page - View all invoices and pay

// Get all invoices for this student
$stmt = $pdo->prepare("
    SELECT i.*, c.class_code, c.class_name
    FROM invoices i
    LEFT JOIN classes c ON i.class_id = c.id
    WHERE i.student_id = ?
    ORDER BY 
        CASE 
            WHEN i.status = 'unpaid' THEN 1
            WHEN i.status = 'paid' THEN 2
            ELSE 3
        END,
        i.due_date ASC,
        i.created_at DESC
");
$stmt->execute([getStudentId()]);
$all_invoices = $stmt->fetchAll();

// Separate by status
$unpaid_invoices = array_filter($all_invoices, fn($i) => $i['status'] === 'unpaid');
$paid_invoices = array_filter($all_invoices, fn($i) => $i['status'] === 'paid');
$cancelled_invoices = array_filter($all_invoices, fn($i) => $i['status'] === 'cancelled');

// Calculate totals
$unpaid_total = array_sum(array_column($unpaid_invoices, 'amount'));
$paid_total = array_sum(array_column($paid_invoices, 'amount'));
?>

<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-warning">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo count($unpaid_invoices); ?></h3>
                <p>Unpaid Invoices</p>
                <small class="text-danger"><strong><?php echo formatCurrency($unpaid_total); ?></strong></small>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo count($paid_invoices); ?></h3>
                <p>Paid Invoices</p>
                <small class="text-success"><strong><?php echo formatCurrency($paid_total); ?></strong></small>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-info">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo count($all_invoices); ?></h3>
                <p>Total Invoices</p>
            </div>
        </div>
    </div>
</div>

<!-- Unpaid Invoices -->
<?php if (count($unpaid_invoices) > 0): ?>
<div class="card mb-4">
    <div class="card-header bg-warning text-dark">
        <i class="fas fa-exclamation-triangle"></i> Unpaid Invoices
        <span class="badge bg-dark float-end"><?php echo count($unpaid_invoices); ?></span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table sp-invoices-table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Related Class</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
<?php foreach ($unpaid_invoices as $invoice): ?>
    <tr class="sp-invoice-row">
        <!-- Invoice number + date (mobile shows date under number) -->
        <td class="text-nowrap">
            <strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong>
            <div class="d-md-none text-muted small">
                <?php echo formatDate($invoice['created_at']); ?>
            </div>
        </td>

        <!-- Desktop date column -->
        <td class="sp-hide-mobile">
            <?php echo formatDate($invoice['created_at']); ?>
        </td>

        <!-- Class / type info -->
        <td>
            <div class="sp-invoice-main">
                <?php echo htmlspecialchars($invoice['class_name'] ?? 'General'); ?>
            </div>
            <div class="d-md-none text-muted small">
                <?php echo ucfirst($invoice['invoice_type']); ?>
            </div>
        </td>

        <!-- Desktop type column -->
        <td class="sp-hide-mobile">
            <span class="badge bg-info"><?php echo ucfirst($invoice['invoice_type']); ?></span>
        </td>

        <!-- Amount -->
        <td>
            <strong><?php echo formatCurrency($invoice['amount']); ?></strong>
            <div class="d-md-none text-muted small">
                Due: <?php echo formatDate($invoice['due_date']); ?>
            </div>
        </td>

        <!-- Desktop due date -->
        <td class="sp-hide-mobile">
            <?php echo formatDate($invoice['due_date']); ?>
        </td>

        <!-- Status -->
        <td>
            <?php if ($invoice['status'] === 'unpaid'): ?>
                <span class="badge bg-warning">Unpaid</span>
            <?php elseif ($invoice['status'] === 'paid'): ?>
                <span class="badge bg-success">Paid</span>
            <?php else: ?>
                <span class="badge bg-secondary">Overdue</span>
            <?php endif; ?>
        </td>

        <!-- Actions (e.g. Pay / View) -->
        <td class="sp-invoice-actions-cell">
            <div class="btn-group btn-group-sm" role="group">
                <?php if ($invoice['status'] === 'unpaid'): ?>
                    <button class="btn btn-success"
                            data-bs-toggle="modal"
                            data-bs-target="#payInvoiceModal<?php echo $invoice['id']; ?>">
                        Pay
                    </button>
                <?php endif; ?>
                <button class="btn btn-outline-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#viewInvoiceModal<?php echo $invoice['id']; ?>">
                    View
                </button>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
</tbody>

            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php foreach ($unpaid_invoices as $invoice): ?>

<!-- VIEW INVOICE MODAL -->
<div class="modal fade" id="viewInvoiceModal<?php echo $invoice['id']; ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-file-invoice"></i>
          Invoice Details
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered">
          <tr>
            <th width="30%">Invoice #</th>
            <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
          </tr>
          <tr>
            <th>Date</th>
            <td><?php echo formatDate($invoice['created_at']); ?></td>
          </tr>
          <tr>
            <th>Class / Type</th>
            <td>
                <?php echo htmlspecialchars($invoice['class_name'] ?? 'General'); ?>
                (<?php echo ucfirst($invoice['invoice_type']); ?>)
            </td>
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
            <td>
              <?php if ($invoice['status'] === 'unpaid'): ?>
                <span class="badge bg-warning">Unpaid</span>
              <?php elseif ($invoice['status'] === 'paid'): ?>
                <span class="badge bg-success">Paid</span>
              <?php else: ?>
                <span class="badge bg-secondary">Overdue</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php if (!empty($invoice['description'])): ?>
          <tr>
            <th>Description</th>
            <td><?php echo nl2br(htmlspecialchars($invoice['description'])); ?></td>
          </tr>
          <?php endif; ?>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- PAY INVOICE MODAL -->
<?php if ($invoice['status'] === 'unpaid'): ?>
<div class="modal fade" id="payInvoiceModal<?php echo $invoice['id']; ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-credit-card"></i>
          Pay Invoice
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="index.php?page=payments" enctype="multipart/form-data">
        <div class="modal-body">
          <input type="hidden" name="action" value="upload_payment">
          <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
          <input type="hidden" name="invoice_class_id" value="<?php echo $invoice['class_id']; ?>">
          <input type="hidden" name="invoice_amount" value="<?php echo $invoice['amount']; ?>">
          <input type="hidden" name="invoice_payment_month" value="<?php echo $invoice['payment_month']; ?>">

          <p class="mb-2">
            <strong>Invoice #:</strong> <?php echo htmlspecialchars($invoice['invoice_number']); ?><br>
            <strong>Amount:</strong> <?php echo formatCurrency($invoice['amount']); ?><br>
            <strong>Due:</strong> <?php echo formatDate($invoice['due_date']); ?>
          </p>

          <div class="mb-3">
            <label class="form-label">Upload Receipt (image/PDF) *</label>
            <input type="file" name="receipt" class="form-control" accept="image/*,.pdf" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Notes (optional)</label>
            <textarea name="notes" class="form-control" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-check"></i> Submit Payment
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php endforeach; ?>


<!-- Paid Invoices -->
<?php if (count($paid_invoices) > 0): ?>
<div class="card mb-4">
    <div class="card-header bg-success text-white">
        <i class="fas fa-check-circle"></i> Paid Invoices
        <span class="badge bg-light text-dark float-end"><?php echo count($paid_invoices); ?></span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Paid Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($paid_invoices as $invoice): ?>
                        <tr>
                            <td><strong><?php echo $invoice['invoice_number']; ?></strong></td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?php echo ucfirst(str_replace('_', ' ', $invoice['invoice_type'])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars(substr($invoice['description'], 0, 50)) . (strlen($invoice['description']) > 50 ? '...' : ''); ?></td>
                            <td><strong><?php echo formatCurrency($invoice['amount']); ?></strong></td>
                            <td><?php echo formatDateTime($invoice['paid_date']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" 
                                        data-bs-target="#viewInvoice<?php echo $invoice['id']; ?>">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Cancelled Invoices -->
<?php if (count($cancelled_invoices) > 0): ?>
<div class="card mb-4">
    <div class="card-header bg-secondary text-white">
        <i class="fas fa-ban"></i> Cancelled Invoices
        <span class="badge bg-light text-dark float-end"><?php echo count($cancelled_invoices); ?></span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($cancelled_invoices as $invoice): ?>
                        <tr class="text-muted">
                            <td><?php echo $invoice['invoice_number']; ?></td>
                            <td><span class="badge bg-secondary"><?php echo ucfirst(str_replace('_', ' ', $invoice['invoice_type'])); ?></span></td>
                            <td><?php echo htmlspecialchars(substr($invoice['description'], 0, 50)); ?></td>
                            <td><?php echo formatCurrency($invoice['amount']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" 
                                        data-bs-target="#viewInvoice<?php echo $invoice['id']; ?>">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- No Invoices Message -->
<?php if (count($all_invoices) === 0): ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="fas fa-file-invoice fa-4x text-muted mb-3"></i>
        <h5>No Invoices Yet</h5>
        <p class="text-muted">You don't have any invoices at the moment.</p>
    </div>
</div>
<?php endif; ?>

<!-- Invoice Detail Modals -->
<?php foreach($all_invoices as $invoice): 
    $status_class = $invoice['status'] === 'paid' ? 'success' : 
                   ($invoice['status'] === 'cancelled' ? 'secondary' : 'warning');
?>
<div class="modal fade" id="viewInvoice<?php echo $invoice['id']; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-invoice"></i> Invoice Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Invoice Header -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">Invoice Information</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td><strong>Invoice Number:</strong></td>
                                <td><?php echo $invoice['invoice_number']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Issue Date:</strong></td>
                                <td><?php echo formatDateTime($invoice['created_at']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Due Date:</strong></td>
                                <td>
                                    <?php 
                                    $is_overdue = strtotime($invoice['due_date']) < time() && $invoice['status'] === 'unpaid';
                                    ?>
                                    <span class="<?php echo $is_overdue ? 'text-danger fw-bold' : ''; ?>">
                                        <?php echo formatDate($invoice['due_date']); ?>
                                        <?php if ($is_overdue): ?>
                                            <i class="fas fa-exclamation-triangle"></i> OVERDUE
                                        <?php endif; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Type:</strong></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo ucfirst(str_replace('_', ' ', $invoice['invoice_type'])); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">Related Information</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td><strong>Related Class:</strong></td>
                                <td>
                                    <?php if ($invoice['class_code']): ?>
                                        <span class="badge bg-primary"><?php echo $invoice['class_code']; ?></span>
                                        <?php echo htmlspecialchars($invoice['class_name']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">No specific class</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge bg-<?php echo $status_class; ?> badge-lg">
                                        <?php echo strtoupper($invoice['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php if ($invoice['paid_date']): ?>
                            <tr>
                                <td><strong>Paid On:</strong></td>
                                <td><?php echo formatDateTime($invoice['paid_date']); ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <h6 class="text-muted mb-2">Description</h6>
                    <div class="alert alert-light">
                        <?php echo nl2br(htmlspecialchars($invoice['description'])); ?>
                    </div>
                </div>

                <!-- Amount -->
                <div class="row">
                    <div class="col-12">
                        <div class="p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Total Amount:</h5>
                                <h3 class="mb-0 text-<?php echo $invoice['status'] === 'unpaid' ? 'danger' : 'success'; ?>">
                                    <?php echo formatCurrency($invoice['amount']); ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Instructions for Unpaid -->
                <?php if ($invoice['status'] === 'unpaid'): ?>
                <div class="alert alert-info mt-4">
                    <h6><i class="fas fa-info-circle"></i> Payment Instructions</h6>
                    <p class="mb-2">To pay this invoice, please:</p>
                    <ol class="mb-2">
                        <li>Make payment to the school's bank account</li>
                        <li>Take a photo/screenshot of the payment receipt</li>
                        <li>Upload it through the <strong>Payments</strong> page</li>
                        <li>Select the related class (if applicable)</li>
                        <li>Include invoice number: <strong><?php echo $invoice['invoice_number']; ?></strong> in the notes</li>
                    </ol>
                    <a href="?page=payments" class="btn btn-sm btn-primary">
                        <i class="fas fa-upload"></i> Upload Payment Now
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <?php if ($invoice['status'] === 'unpaid'): ?>
                    <a href="?page=payments" class="btn btn-primary">
                        <i class="fas fa-credit-card"></i> Make Payment
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<style>
.badge-lg {
    font-size: 1rem;
    padding: 0.5rem 1rem;
}

/* Student portal invoices table – mobile card layout */
@media (max-width: 768px) {
    .sp-hide-mobile {
        display: none !important;
    }

    .sp-invoices-table thead {
        display: none; /* hide header on mobile */
    }

    .sp-invoices-table,
    .sp-invoices-table tbody {
        display: block;
        width: 100%;
    }

    .sp-invoices-table tbody tr.sp-invoice-row {
        display: block;
        background: #ffffff;
        border-radius: 12px;
        margin-bottom: 12px;
        padding: 12px;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.18);
        border: 1px solid rgba(148, 163, 184, 0.4);
    }

    .sp-invoices-table tbody tr.sp-invoice-row td {
        display: block;
        border: none;
        padding: 4px 0;
        text-align: left !important;
    }

    .sp-invoices-table tbody tr.sp-invoice-row td:first-child {
        font-size: 15px;
        font-weight: 600;
        padding-bottom: 6px;
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 6px;
    }

    .sp-invoice-actions-cell .btn-group {
        width: 100%;
        display: flex;
        justify-content: flex-end;
        gap: 6px;
        margin-top: 8px;
    }

    .sp-invoice-actions-cell .btn-group .btn {
        padding: 6px 10px;
        font-size: 13px;
    }
}

@media (min-width: 769px) {
    /* keep normal table on desktop */
    .sp-invoices-table {
        display: table !important;
        width: 100%;
    }
    .sp-invoices-table tbody {
        display: table-row-group !important;
    }
    .sp-invoices-table tbody tr.sp-invoice-row {
        display: table-row !important;
        box-shadow: none;
        border-radius: 0;
        border: none;
        padding: 0;
        margin: 0;
    }
    .sp-invoices-table tbody tr.sp-invoice-row td {
        display: table-cell !important;
        padding: .75rem;
    }
}
</style>