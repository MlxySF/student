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
            WHEN i.status = 'pending' THEN 2
            WHEN i.status = 'overdue' THEN 3
            WHEN i.status = 'paid' THEN 4
            ELSE 5
        END,
        i.due_date ASC,
        i.created_at DESC
");
$stmt->execute([getStudentId()]);
$all_invoices = $stmt->fetchAll();

// Separate by status
$unpaid_invoices = array_filter($all_invoices, fn($i) => $i['status'] === 'unpaid');
$pending_invoices = array_filter($all_invoices, fn($i) => $i['status'] === 'pending');
$paid_invoices = array_filter($all_invoices, fn($i) => $i['status'] === 'paid');
$cancelled_invoices = array_filter($all_invoices, fn($i) => $i['status'] === 'cancelled');
$overdue_invoices = array_filter($all_invoices, fn($i) => $i['status'] === 'overdue');

// Calculate totals
$unpaid_total = array_sum(array_column($unpaid_invoices, 'amount'));
$pending_total = array_sum(array_column($pending_invoices, 'amount'));
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
            <div class="stat-icon bg-info">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo count($pending_invoices); ?></h3>
                <p>Pending Verification</p>
                <small class="text-info"><strong><?php echo formatCurrency($pending_total); ?></strong></small>
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
</div>

<!-- Unpaid Invoices -->
<?php if (count($unpaid_invoices) > 0): ?>
<div class="card mb-4">
    <div class="card-header bg-warning text-dark">
        <i class="fas fa-exclamation-triangle"></i> Unpaid Invoices - Action Required
        <span class="badge bg-dark float-end"><?php echo count($unpaid_invoices); ?></span>
    </div>
    <div class="card-body">
        <div class="alert alert-warning mb-3">
            <i class="fas fa-info-circle"></i> These invoices need to be paid. Click "Pay" to upload your payment receipt.
        </div>
        <div class="table-responsive">
            <table class="table sp-invoices-table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Description</th>
                        <th class="sp-hide-mobile">Class</th>
                        <th>Amount</th>
                        <th class="sp-hide-mobile">Due Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($unpaid_invoices as $invoice): ?>
                        <tr class="sp-invoice-row">
                            <td>
                                <strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong>
                                <div class="d-md-none text-muted small">
                                    <?php echo formatDate($invoice['created_at']); ?>
                                </div>
                            </td>
                            <td class="sp-hide-mobile">
                                <?php echo formatDate($invoice['created_at']); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($invoice['description']); ?>
                                <div class="d-md-none text-muted small">
                                    <?php if ($invoice['class_name']): ?>
                                        <span class="badge bg-info"><?php echo $invoice['class_code']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="sp-hide-mobile">
                                <?php if ($invoice['class_name']): ?>
                                    <span class="badge bg-info"><?php echo $invoice['class_code']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo formatCurrency($invoice['amount']); ?></strong>
                                <div class="d-md-none text-muted small">
                                    Due: <?php echo formatDate($invoice['due_date']); ?>
                                </div>
                            </td>
                            <td class="sp-hide-mobile">
                                <?php echo formatDate($invoice['due_date']); ?>
                            </td>
                            <td class="sp-invoice-actions-cell">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button class="btn btn-success"
                                            data-bs-toggle="modal"
                                            data-bs-target="#payInvoiceModal<?php echo $invoice['id']; ?>">
                                        <i class="fas fa-credit-card"></i> Pay
                                    </button>
                                    <button class="btn btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewInvoiceModal<?php echo $invoice['id']; ?>">
                                        <i class="fas fa-eye"></i> View
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

<!-- Pending Verification Invoices -->
<?php if (count($pending_invoices) > 0): ?>
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <i class="fas fa-clock"></i> Pending Payment Verification
        <span class="badge bg-light text-dark float-end"><?php echo count($pending_invoices); ?></span>
    </div>
    <div class="card-body">
        <div class="alert alert-info mb-3">
            <i class="fas fa-hourglass-half"></i> <strong>Payment submitted!</strong> Your payment receipt has been uploaded and is awaiting admin verification. You will be notified once verified.
        </div>
        <div class="table-responsive">
            <table class="table sp-invoices-table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Description</th>
                        <th class="sp-hide-mobile">Class</th>
                        <th>Amount</th>
                        <th class="sp-hide-mobile">Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_invoices as $invoice): ?>
                        <tr class="sp-invoice-row">
                            <td>
                                <strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong>
                                <div class="d-md-none text-muted small">
                                    <?php echo formatDate($invoice['created_at']); ?>
                                </div>
                            </td>
                            <td class="sp-hide-mobile">
                                <?php echo formatDate($invoice['created_at']); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($invoice['description']); ?>
                                <div class="d-md-none">
                                    <?php if ($invoice['class_name']): ?>
                                        <span class="badge bg-info"><?php echo $invoice['class_code']; ?></span>
                                    <?php endif; ?>
                                    <div class="mt-1">
                                        <span class="badge bg-info"><i class="fas fa-clock"></i> Pending</span>
                                    </div>
                                </div>
                            </td>
                            <td class="sp-hide-mobile">
                                <?php if ($invoice['class_name']): ?>
                                    <span class="badge bg-info"><?php echo $invoice['class_code']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo formatCurrency($invoice['amount']); ?></strong>
                            </td>
                            <td class="sp-hide-mobile">
                                <span class="badge bg-info"><i class="fas fa-clock"></i> Awaiting Verification</span>
                            </td>
                            <td class="sp-invoice-actions-cell">
                                <button class="btn btn-sm btn-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#viewPendingInvoiceModal<?php echo $invoice['id']; ?>">
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

<!-- Payment Modals for Unpaid Invoices -->
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
            <th>Class</th>
            <td>
                <?php if ($invoice['class_name']): ?>
                    <span class="badge bg-info"><?php echo $invoice['class_code']; ?></span>
                    <?php echo htmlspecialchars($invoice['class_name']); ?>
                <?php else: ?>
                    <span class="text-muted">General (No specific class)</span>
                <?php endif; ?>
            </td>
          </tr>
          <tr>
            <th>Description</th>
            <td><?php echo nl2br(htmlspecialchars($invoice['description'])); ?></td>
          </tr>
          <tr>
            <th>Amount</th>
            <td><strong class="text-danger"><?php echo formatCurrency($invoice['amount']); ?></strong></td>
          </tr>
          <tr>
            <th>Due Date</th>
            <td><?php echo formatDate($invoice['due_date']); ?></td>
          </tr>
          <tr>
            <th>Status</th>
            <td><span class="badge bg-warning">Unpaid</span></td>
          </tr>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-success" data-bs-dismiss="modal" 
                data-bs-toggle="modal" data-bs-target="#payInvoiceModal<?php echo $invoice['id']; ?>">
          <i class="fas fa-credit-card"></i> Pay Now
        </button>
      </div>
    </div>
  </div>
</div>

<!-- PAY INVOICE MODAL -->
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

          <div class="alert alert-info">
            <strong>Invoice #:</strong> <?php echo htmlspecialchars($invoice['invoice_number']); ?><br>
            <strong>Amount:</strong> <?php echo formatCurrency($invoice['amount']); ?><br>
            <strong>Due:</strong> <?php echo formatDate($invoice['due_date']); ?>
          </div>

          <div class="mb-3">
            <label class="form-label">Upload Receipt (Image/PDF) *</label>
            <input type="file" name="receipt" class="form-control" accept="image/*,.pdf" required>
            <div class="form-text">Maximum size: 5MB | Accepted: JPG, PNG, PDF</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Notes (Optional)</label>
            <textarea name="notes" class="form-control" rows="3" 
                      placeholder="Add any additional notes about your payment..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-upload"></i> Submit Payment
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endforeach; ?>

<!-- View Modals for Pending Invoices -->
<?php foreach ($pending_invoices as $invoice): ?>
<div class="modal fade" id="viewPendingInvoiceModal<?php echo $invoice['id']; ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title">
          <i class="fas fa-clock"></i>
          Invoice Details - Pending Verification
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info">
          <i class="fas fa-info-circle"></i> <strong>Payment Status:</strong> Your payment receipt has been submitted and is currently awaiting admin verification. You'll be notified once the payment is verified.
        </div>
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
            <th>Class</th>
            <td>
                <?php if ($invoice['class_name']): ?>
                    <span class="badge bg-info"><?php echo $invoice['class_code']; ?></span>
                    <?php echo htmlspecialchars($invoice['class_name']); ?>
                <?php else: ?>
                    <span class="text-muted">General (No specific class)</span>
                <?php endif; ?>
            </td>
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
            <td><span class="badge bg-info"><i class="fas fa-clock"></i> Pending Verification</span></td>
          </tr>
        </table>
        <div class="alert alert-light border">
          <h6><i class="fas fa-question-circle"></i> What happens next?</h6>
          <ol class="mb-0">
            <li>Admin will review your payment receipt</li>
            <li>Once verified, this invoice will be marked as "Paid"</li>
            <li>You'll be able to see the payment confirmation in your dashboard</li>
          </ol>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
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
                        <th class="sp-hide-mobile">Type</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Paid Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($paid_invoices as $invoice): ?>
                        <tr>
                            <td><strong><?php echo $invoice['invoice_number']; ?></strong></td>
                            <td class="sp-hide-mobile">
                                <span class="badge bg-secondary">
                                    <?php echo ucfirst(str_replace('_', ' ', $invoice['invoice_type'])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars(substr($invoice['description'], 0, 50)) . (strlen($invoice['description']) > 50 ? '...' : ''); ?></td>
                            <td><strong><?php echo formatCurrency($invoice['amount']); ?></strong></td>
                            <td><?php echo $invoice['paid_date'] ? formatDate($invoice['paid_date']) : '-'; ?></td>
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

<style>
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