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
            WHEN i.status = 'overdue' THEN 1
            WHEN i.status = 'unpaid' THEN 2
            WHEN i.status = 'pending' THEN 3
            WHEN i.status = 'paid' THEN 4
            WHEN i.status = 'cancelled' THEN 5
            ELSE 6
        END,
        i.due_date ASC,
        i.created_at DESC
");
$stmt->execute([getStudentId()]);
$all_invoices = $stmt->fetchAll();

// Separate by status
$overdue_invoices = array_filter($all_invoices, fn($i) => $i['status'] === 'overdue');
$unpaid_invoices = array_filter($all_invoices, fn($i) => $i['status'] === 'unpaid');
$pending_invoices = array_filter($all_invoices, fn($i) => $i['status'] === 'pending');
$paid_invoices = array_filter($all_invoices, fn($i) => $i['status'] === 'paid');
$cancelled_invoices = array_filter($all_invoices, fn($i) => $i['status'] === 'cancelled');

// Calculate totals
$overdue_total = array_sum(array_column($overdue_invoices, 'amount'));
$unpaid_total = array_sum(array_column($unpaid_invoices, 'amount'));
$pending_total = array_sum(array_column($pending_invoices, 'amount'));
$paid_total = array_sum(array_column($paid_invoices, 'amount'));

// Combine unpaid and overdue for "action required" count
$action_required_count = count($unpaid_invoices) + count($overdue_invoices);
$action_required_total = $unpaid_total + $overdue_total;
?>

<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-danger">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $action_required_count; ?></h3>
                <p>Action Required</p>
                <small class="text-danger"><strong><?php echo formatCurrency($action_required_total); ?></strong></small>
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

<!-- OVERDUE Invoices (Urgent!) -->
<?php if (count($overdue_invoices) > 0): ?>
<div class="card mb-4 border-danger">
    <div class="card-header bg-danger text-white">
        <i class="fas fa-exclamation-circle"></i> OVERDUE INVOICES - URGENT!
        <span class="badge bg-light text-danger float-end"><?php echo count($overdue_invoices); ?></span>
    </div>
    <div class="card-body">
        <div class="alert alert-danger mb-3">
            <i class="fas fa-skull-crossbones"></i> <strong>URGENT:</strong> These invoices are past their due date. Please pay immediately to avoid late fees or suspension!
        </div>
        <div class="table-responsive">
            <table class="table sp-invoices-table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Created Date/Time</th>
                        <th>Description</th>
                        <th class="sp-hide-mobile">Class</th>
                        <th>Amount</th>
                        <th class="sp-hide-mobile">Due Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($overdue_invoices as $invoice): ?>
                        <tr class="sp-invoice-row table-danger">
                            <td>
                                <strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong>
                                <div class="d-md-none text-muted small">
                                    <?php echo date('d M Y, g:i A', strtotime($invoice['created_at'])); ?>
                                </div>
                                <div class="d-md-none">
                                    <span class="badge bg-danger">OVERDUE</span>
                                </div>
                            </td>
                            <td class="sp-hide-mobile">
                                <div><?php echo date('d M Y', strtotime($invoice['created_at'])); ?></div>
                                <small class="text-muted"><?php echo date('g:i A', strtotime($invoice['created_at'])); ?></small>
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
                                <strong class="text-danger"><?php echo formatCurrency($invoice['amount']); ?></strong>
                                <div class="d-md-none text-muted small">
                                    Due: <?php echo date('d M Y', strtotime($invoice['due_date'])); ?>
                                </div>
                            </td>
                            <td class="sp-hide-mobile">
                                <span class="text-danger"><strong><?php echo date('d M Y', strtotime($invoice['due_date'])); ?></strong></span>
                            </td>
                            <td class="sp-invoice-actions-cell">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button class="btn btn-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#payInvoiceModal<?php echo $invoice['id']; ?>">
                                        <i class="fas fa-credit-card"></i> PAY NOW
                                    </button>
                                    <button class="btn btn-outline-danger"
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
                        <th>Created Date/Time</th>
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
                                    <?php echo date('d M Y, g:i A', strtotime($invoice['created_at'])); ?>
                                </div>
                            </td>
                            <td class="sp-hide-mobile">
                                <div><?php echo date('d M Y', strtotime($invoice['created_at'])); ?></div>
                                <small class="text-muted"><?php echo date('g:i A', strtotime($invoice['created_at'])); ?></small>
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
                                    Due: <?php echo date('d M Y', strtotime($invoice['due_date'])); ?>
                                </div>
                            </td>
                            <td class="sp-hide-mobile">
                                <?php echo date('d M Y', strtotime($invoice['due_date'])); ?>
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
                        <th>Created Date/Time</th>
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
                                    <?php echo date('d M Y, g:i A', strtotime($invoice['created_at'])); ?>
                                </div>
                            </td>
                            <td class="sp-hide-mobile">
                                <div><?php echo date('d M Y', strtotime($invoice['created_at'])); ?></div>
                                <small class="text-muted"><?php echo date('g:i A', strtotime($invoice['created_at'])); ?></small>
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

<!-- Payment Modals for Unpaid AND OVERDUE Invoices -->
<?php 
$payable_invoices = array_merge($unpaid_invoices, $overdue_invoices);
foreach ($payable_invoices as $invoice): 
?>
<!-- VIEW INVOICE MODAL -->
<div class="modal fade" id="viewInvoiceModal<?php echo $invoice['id']; ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header <?php echo $invoice['status'] === 'overdue' ? 'bg-danger text-white' : ''; ?>">
        <h5 class="modal-title">
          <i class="fas fa-file-invoice"></i>
          Invoice Details
          <?php if ($invoice['status'] === 'overdue'): ?>
            <span class="badge bg-light text-danger ms-2">OVERDUE</span>
          <?php endif; ?>
        </h5>
        <button type="button" class="btn-close <?php echo $invoice['status'] === 'overdue' ? 'btn-close-white' : ''; ?>" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php if ($invoice['status'] === 'overdue'): ?>
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-triangle"></i> <strong>This invoice is overdue!</strong> Please pay immediately to avoid penalties.
        </div>
        <?php endif; ?>
        <table class="table table-bordered">
          <tr>
            <th width="30%">Invoice #</th>
            <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
          </tr>
          <tr>
            <th>Created Date & Time</th>
            <td>
                <?php echo date('d M Y', strtotime($invoice['created_at'])); ?>
                <span class="text-muted">at <?php echo date('g:i A', strtotime($invoice['created_at'])); ?></span>
            </td>
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
            <td>
              <?php echo date('d M Y', strtotime($invoice['due_date'])); ?>
              <?php if ($invoice['status'] === 'overdue'): ?>
                <span class="badge bg-danger ms-2">OVERDUE</span>
              <?php endif; ?>
            </td>
          </tr>
          <tr>
            <th>Status</th>
            <td>
              <?php if ($invoice['status'] === 'overdue'): ?>
                <span class="badge bg-danger">Overdue</span>
              <?php else: ?>
                <span class="badge bg-warning">Unpaid</span>
              <?php endif; ?>
            </td>
          </tr>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn <?php echo $invoice['status'] === 'overdue' ? 'btn-danger' : 'btn-success'; ?>" data-bs-dismiss="modal" 
                data-bs-toggle="modal" data-bs-target="#payInvoiceModal<?php echo $invoice['id']; ?>">
          <i class="fas fa-credit-card"></i> <?php echo $invoice['status'] === 'overdue' ? 'PAY NOW' : 'Pay Now'; ?>
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

          <div class="alert <?php echo $invoice['status'] === 'overdue' ? 'alert-danger' : 'alert-info'; ?>">
            <strong>Invoice #:</strong> <?php echo htmlspecialchars($invoice['invoice_number']); ?><br>
            <strong>Amount:</strong> <?php echo formatCurrency($invoice['amount']); ?><br>
            <strong>Due:</strong> <?php echo date('d M Y', strtotime($invoice['due_date'])); ?>
            <?php if ($invoice['status'] === 'overdue'): ?>
              <br><span class="badge bg-danger mt-1">OVERDUE</span>
            <?php endif; ?>
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
          <button type="submit" class="btn <?php echo $invoice['status'] === 'overdue' ? 'btn-danger' : 'btn-success'; ?>">
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
            <th>Created Date & Time</th>
            <td>
                <?php echo date('d M Y', strtotime($invoice['created_at'])); ?>
                <span class="text-muted">at <?php echo date('g:i A', strtotime($invoice['created_at'])); ?></span>
            </td>
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
            <td><?php echo date('d M Y', strtotime($invoice['due_date'])); ?></td>
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
                        <th>Paid Date/Time</th>
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
                            <td>
                                <?php if ($invoice['paid_date']): ?>
                                    <div><?php echo date('d M Y', strtotime($invoice['paid_date'])); ?></div>
                                    <small class="text-muted"><?php echo date('g:i A', strtotime($invoice['paid_date'])); ?></small>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
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
