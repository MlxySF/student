<?php
// Student Invoices & Payments Page - Unified view

// Get all invoices with payment information for this student
$stmt = $pdo->prepare("
    SELECT i.*, 
           c.class_code, c.class_name,
           p.id as payment_id, p.verification_status, p.upload_date,
           p.receipt_data, p.receipt_mime_type, p.admin_notes
    FROM invoices i
    LEFT JOIN classes c ON i.class_id = c.id
    LEFT JOIN payments p ON i.id = p.invoice_id
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
$overdue_invoices = array_values(array_filter($all_invoices, fn($i) => $i['status'] === 'overdue'));
$unpaid_invoices = array_values(array_filter($all_invoices, fn($i) => $i['status'] === 'unpaid'));
$pending_invoices = array_values(array_filter($all_invoices, fn($i) => $i['status'] === 'pending'));
$paid_invoices = array_values(array_filter($all_invoices, fn($i) => $i['status'] === 'paid'));

// Calculate totals
$overdue_total = array_sum(array_column($overdue_invoices, 'amount'));
$unpaid_total = array_sum(array_column($unpaid_invoices, 'amount'));
$pending_total = array_sum(array_column($pending_invoices, 'amount'));
$paid_total = array_sum(array_column($paid_invoices, 'amount'));

$action_required_count = count($unpaid_invoices) + count($overdue_invoices);
$action_required_total = $unpaid_total + $overdue_total;

// Pagination
$per_page = 5;

function paginateInvoices($invoices, $param) {
    global $per_page;
    $page = isset($_GET[$param]) ? max(1, intval($_GET[$param])) : 1;
    $total_pages = max(1, ceil(count($invoices) / $per_page));
    $offset = ($page - 1) * $per_page;
    return [
        'items' => array_slice($invoices, $offset, $per_page),
        'page' => $page,
        'total_pages' => $total_pages,
        'param' => $param
    ];
}

$overdue_paginated = paginateInvoices($overdue_invoices, 'overdue_page');
$unpaid_paginated = paginateInvoices($unpaid_invoices, 'unpaid_page');
$pending_paginated = paginateInvoices($pending_invoices, 'pending_page');
$paid_paginated = paginateInvoices($paid_invoices, 'paid_page');

function renderPagination($data) {
    if ($data['total_pages'] <= 1) return;
    $current_page = $data['page'];
    $total_pages = $data['total_pages'];
    $param = $data['param'];
    $range = 2;
    $start_page = max(1, $current_page - $range);
    $end_page = min($total_pages, $current_page + $range);
    
    echo '<nav aria-label="Invoice pagination" class="mt-4"><ul class="pagination justify-content-center flex-wrap">';
    echo '<li class="page-item ' . ($current_page <= 1 ? 'disabled' : '') . '">';
    echo '<a class="page-link" href="?page=invoices&' . $param . '=' . ($current_page - 1) . '">&laquo;</a></li>';
    if ($start_page > 1) {
        echo '<li class="page-item"><a class="page-link" href="?page=invoices&' . $param . '=1">1</a></li>';
        if ($start_page > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }
    for ($i = $start_page; $i <= $end_page; $i++) {
        echo '<li class="page-item ' . ($i == $current_page ? 'active' : '') . '">';
        echo '<a class="page-link" href="?page=invoices&' . $param . '=' . $i . '">' . $i . '</a></li>';
    }
    if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        echo '<li class="page-item"><a class="page-link" href="?page=invoices&' . $param . '=' . $total_pages . '">' . $total_pages . '</a></li>';
    }
    echo '<li class="page-item ' . ($current_page >= $total_pages ? 'disabled' : '') . '">';
    echo '<a class="page-link" href="?page=invoices&' . $param . '=' . ($current_page + 1) . '">&raquo;</a></li>';
    echo '</ul></nav>';
}
?>

<style>
@media (max-width: 768px) {
    .sp-hide-mobile { display: none !important; }
    .sp-invoices-table thead { display: none; }
    .sp-invoices-table, .sp-invoices-table tbody { display: block; width: 100%; }
    .sp-invoices-table tbody tr.sp-invoice-row {
        display: block; background: #ffffff; border-radius: 12px;
        margin-bottom: 12px; padding: 12px;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.18);
        border: 1px solid rgba(148, 163, 184, 0.4);
    }
    .sp-invoices-table tbody tr.sp-invoice-row td {
        display: block; border: none; padding: 4px 0; text-align: left !important;
    }
    .sp-invoices-table tbody tr.sp-invoice-row td:first-child {
        font-size: 15px; font-weight: 600; padding-bottom: 6px;
        border-bottom: 1px solid #e5e7eb; margin-bottom: 6px;
    }
    .sp-invoice-actions-cell .btn-group { width: 100%; display: flex; justify-content: flex-end; gap: 6px; margin-top: 8px; }
    .sp-invoice-actions-cell .btn-group .btn, .sp-invoice-actions-cell .btn { padding: 6px 10px; font-size: 13px; margin-top: 8px; }
    .pagination { font-size: 14px; }
    .pagination .page-link { padding: 6px 10px; margin: 2px; }
}
@media (min-width: 769px) {
    .sp-invoices-table { display: table !important; width: 100%; }
    .sp-invoices-table tbody { display: table-row-group !important; }
    .sp-invoices-table tbody tr.sp-invoice-row { display: table-row !important; box-shadow: none; border-radius: 0; border: none; padding: 0; margin: 0; }
    .sp-invoices-table tbody tr.sp-invoice-row td { display: table-cell !important; padding: .75rem; }
}
.receipt-image { max-width: 100%; height: auto; border-radius: 8px; border: 2px solid #e2e8f0; }
.receipt-pdf { width: 100%; height: 500px; border: 2px solid #e2e8f0; border-radius: 8px; }
</style>

<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-content"><h3><?php echo $action_required_count; ?></h3><p>Action Required</p>
                <small class="text-danger"><strong><?php echo formatCurrency($action_required_total); ?></strong></small></div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-info"><i class="fas fa-clock"></i></div>
            <div class="stat-content"><h3><?php echo count($pending_invoices); ?></h3><p>Pending Verification</p>
                <small class="text-info"><strong><?php echo formatCurrency($pending_total); ?></strong></small></div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-success"><i class="fas fa-check-circle"></i></div>
            <div class="stat-content"><h3><?php echo count($paid_invoices); ?></h3><p>Paid Invoices</p>
                <small class="text-success"><strong><?php echo formatCurrency($paid_total); ?></strong></small></div>
        </div>
    </div>
</div>

<?php if (count($overdue_invoices) > 0): ?>
<div class="card mb-4 border-danger">
    <div class="card-header bg-danger text-white"><i class="fas fa-exclamation-circle"></i> OVERDUE INVOICES - URGENT!
        <span class="badge bg-light text-danger float-end"><?php echo count($overdue_invoices); ?></span></div>
    <div class="card-body">
        <div class="alert alert-danger mb-3"><i class="fas fa-skull-crossbones"></i> <strong>URGENT:</strong> These invoices are past their due date. Please pay immediately!</div>
        <div class="table-responsive">
            <table class="table sp-invoices-table">
                <thead><tr><th>Invoice #</th><th>Date</th><th>Description</th><th class="sp-hide-mobile">Class</th><th>Amount</th><th class="sp-hide-mobile">Due Date</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($overdue_paginated['items'] as $inv): ?>
                        <tr class="sp-invoice-row table-danger">
                            <td><strong><?php echo htmlspecialchars($inv['invoice_number']); ?></strong>
                                <div class="d-md-none text-muted small"><?php echo date('d M Y', strtotime($inv['created_at'])); ?></div>
                                <div class="d-md-none"><span class="badge bg-danger">OVERDUE</span></div></td>
                            <td class="sp-hide-mobile"><?php echo date('d M Y', strtotime($inv['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($inv['description']); ?></td>
                            <td class="sp-hide-mobile"><?php echo $inv['class_code'] ? '<span class="badge bg-info">' . $inv['class_code'] . '</span>' : '-'; ?></td>
                            <td><strong class="text-danger"><?php echo formatCurrency($inv['amount']); ?></strong>
                                <div class="d-md-none text-muted small">Due: <?php echo date('d M Y', strtotime($inv['due_date'])); ?></div></td>
                            <td class="sp-hide-mobile"><span class="text-danger"><strong><?php echo date('d M Y', strtotime($inv['due_date'])); ?></strong></span></td>
                            <td class="sp-invoice-actions-cell">
                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#invoiceModal<?php echo $inv['id']; ?>"><i class="fas fa-eye"></i> View & Pay</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php renderPagination($overdue_paginated); ?>
    </div>
</div>
<?php endif; ?>

<?php if (count($unpaid_invoices) > 0): ?>
<div class="card mb-4">
    <div class="card-header bg-warning text-dark"><i class="fas fa-exclamation-triangle"></i> Unpaid Invoices
        <span class="badge bg-dark float-end"><?php echo count($unpaid_invoices); ?></span></div>
    <div class="card-body">
        <div class="alert alert-warning mb-3"><i class="fas fa-info-circle"></i> These invoices need payment. Upload your receipt to complete payment.</div>
        <div class="table-responsive">
            <table class="table sp-invoices-table">
                <thead><tr><th>Invoice #</th><th>Date</th><th>Description</th><th class="sp-hide-mobile">Class</th><th>Amount</th><th class="sp-hide-mobile">Due Date</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($unpaid_paginated['items'] as $inv): ?>
                        <tr class="sp-invoice-row">
                            <td><strong><?php echo htmlspecialchars($inv['invoice_number']); ?></strong>
                                <div class="d-md-none text-muted small"><?php echo date('d M Y', strtotime($inv['created_at'])); ?></div></td>
                            <td class="sp-hide-mobile"><?php echo date('d M Y', strtotime($inv['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($inv['description']); ?></td>
                            <td class="sp-hide-mobile"><?php echo $inv['class_code'] ? '<span class="badge bg-info">' . $inv['class_code'] . '</span>' : '-'; ?></td>
                            <td><strong><?php echo formatCurrency($inv['amount']); ?></strong>
                                <div class="d-md-none text-muted small">Due: <?php echo date('d M Y', strtotime($inv['due_date'])); ?></div></td>
                            <td class="sp-hide-mobile"><?php echo date('d M Y', strtotime($inv['due_date'])); ?></td>
                            <td class="sp-invoice-actions-cell">
                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#invoiceModal<?php echo $inv['id']; ?>"><i class="fas fa-upload"></i> Pay</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php renderPagination($unpaid_paginated); ?>
    </div>
</div>
<?php endif; ?>

<?php if (count($pending_invoices) > 0): ?>
<div class="card mb-4">
    <div class="card-header bg-info text-white"><i class="fas fa-clock"></i> Pending Verification
        <span class="badge bg-light text-dark float-end"><?php echo count($pending_invoices); ?></span></div>
    <div class="card-body">
        <div class="alert alert-info mb-3"><i class="fas fa-hourglass-half"></i> Payment receipts submitted. Awaiting admin verification.</div>
        <div class="table-responsive">
            <table class="table sp-invoices-table">
                <thead><tr><th>Invoice #</th><th>Date</th><th>Description</th><th class="sp-hide-mobile">Class</th><th>Amount</th><th class="sp-hide-mobile">Receipt Uploaded</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($pending_paginated['items'] as $inv): ?>
                        <tr class="sp-invoice-row">
                            <td><strong><?php echo htmlspecialchars($inv['invoice_number']); ?></strong>
                                <div class="d-md-none"><span class="badge bg-info"><i class="fas fa-clock"></i> Pending</span></div></td>
                            <td class="sp-hide-mobile"><?php echo date('d M Y', strtotime($inv['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($inv['description']); ?></td>
                            <td class="sp-hide-mobile"><?php echo $inv['class_code'] ? '<span class="badge bg-info">' . $inv['class_code'] . '</span>' : '-'; ?></td>
                            <td><strong><?php echo formatCurrency($inv['amount']); ?></strong></td>
                            <td class="sp-hide-mobile"><?php echo $inv['upload_date'] ? date('d M Y', strtotime($inv['upload_date'])) : '-'; ?></td>
                            <td class="sp-invoice-actions-cell">
                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#invoiceModal<?php echo $inv['id']; ?>"><i class="fas fa-eye"></i> View</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php renderPagination($pending_paginated); ?>
    </div>
</div>
<?php endif; ?>

<?php if (count($paid_invoices) > 0): ?>
<div class="card mb-4">
    <div class="card-header bg-success text-white"><i class="fas fa-check-circle"></i> Paid Invoices
        <span class="badge bg-light text-dark float-end"><?php echo count($paid_invoices); ?></span></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table sp-invoices-table">
                <thead><tr><th>Invoice #</th><th>Date</th><th>Description</th><th class="sp-hide-mobile">Class</th><th>Amount</th><th class="sp-hide-mobile">Paid Date</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($paid_paginated['items'] as $inv): ?>
                        <tr class="sp-invoice-row">
                            <td><strong><?php echo htmlspecialchars($inv['invoice_number']); ?></strong>
                                <div class="d-md-none"><span class="badge bg-success">PAID</span></div></td>
                            <td class="sp-hide-mobile"><?php echo date('d M Y', strtotime($inv['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($inv['description']); ?></td>
                            <td class="sp-hide-mobile"><?php echo $inv['class_code'] ? '<span class="badge bg-info">' . $inv['class_code'] . '</span>' : '-'; ?></td>
                            <td><strong><?php echo formatCurrency($inv['amount']); ?></strong></td>
                            <td class="sp-hide-mobile"><?php echo $inv['paid_date'] ? date('d M Y', strtotime($inv['paid_date'])) : '-'; ?></td>
                            <td class="sp-invoice-actions-cell">
                                <a href="generate_invoice_pdf.php?invoice_id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-success" target="_blank"><i class="fas fa-download"></i> PDF</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php renderPagination($paid_paginated); ?>
    </div>
</div>
<?php endif; ?>

<?php if (count($all_invoices) === 0): ?>
<div class="card"><div class="card-body text-center py-5">
    <i class="fas fa-file-invoice fa-4x text-muted mb-3"></i>
    <h5>No Invoices Yet</h5>
    <p class="text-muted">You don't have any invoices at the moment.</p>
</div></div>
<?php endif; ?>

<?php foreach ($all_invoices as $inv): ?>
<div class="modal fade" id="invoiceModal<?php echo $inv['id']; ?>" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header <?php echo $inv['status'] === 'overdue' ? 'bg-danger text-white' : ($inv['status'] === 'pending' ? 'bg-info text-white' : ''); ?>">
      <h5 class="modal-title"><i class="fas fa-file-invoice"></i> Invoice & Payment Details</h5>
      <button type="button" class="btn-close <?php echo in_array($inv['status'], ['overdue', 'pending']) ? 'btn-close-white' : ''; ?>" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <?php if ($inv['status'] === 'overdue'): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <strong>OVERDUE!</strong> Please pay immediately.</div>
      <?php endif; ?>
      
      <h6><i class="fas fa-file-invoice"></i> Invoice Information</h6>
      <table class="table table-bordered mb-4">
        <tr><th width="30%">Invoice #</th><td><?php echo htmlspecialchars($inv['invoice_number']); ?></td></tr>
        <tr><th>Description</th><td><?php echo nl2br(htmlspecialchars($inv['description'])); ?></td></tr>
        <tr><th>Amount</th><td><strong><?php echo formatCurrency($inv['amount']); ?></strong></td></tr>
        <tr><th>Due Date</th><td><?php echo date('d M Y', strtotime($inv['due_date'])); ?></td></tr>
        <?php if ($inv['class_name']): ?>
        <tr><th>Class</th><td><span class="badge bg-info"><?php echo $inv['class_code']; ?></span> <?php echo htmlspecialchars($inv['class_name']); ?></td></tr>
        <?php endif; ?>
      </table>

      <?php if (!empty($inv['payment_id'])): ?>
        <hr><h6><i class="fas fa-receipt"></i> Payment Information</h6>
        <table class="table table-bordered mb-3">
          <tr><th width="30%">Upload Date</th><td><?php echo date('d M Y, g:i A', strtotime($inv['upload_date'])); ?></td></tr>
          <tr><th>Status</th><td>
            <?php if ($inv['verification_status'] === 'verified'): ?>
              <span class="badge bg-success"><i class="fas fa-check-circle"></i> Verified</span>
            <?php elseif ($inv['verification_status'] === 'rejected'): ?>
              <span class="badge bg-danger"><i class="fas fa-times-circle"></i> Rejected</span>
            <?php else: ?>
              <span class="badge bg-warning"><i class="fas fa-clock"></i> Pending Verification</span>
            <?php endif; ?>
          </td></tr>
          <?php if (!empty($inv['admin_notes'])): ?>
          <tr><th>Admin Notes</th><td><?php echo nl2br(htmlspecialchars($inv['admin_notes'])); ?></td></tr>
          <?php endif; ?>
        </table>

        <h6>Your Uploaded Receipt</h6>
        <?php if (!empty($inv['receipt_data'])): ?>
          <?php if ($inv['receipt_mime_type'] === 'application/pdf'): ?>
            <embed src="data:<?php echo $inv['receipt_mime_type']; ?>;base64,<?php echo $inv['receipt_data']; ?>" type="<?php echo $inv['receipt_mime_type']; ?>" class="receipt-pdf">
          <?php else: ?>
            <img src="data:<?php echo $inv['receipt_mime_type']; ?>;base64,<?php echo $inv['receipt_data']; ?>" alt="Receipt" class="receipt-image">
          <?php endif; ?>
        <?php else: ?>
          <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Receipt not available.</div>
        <?php endif; ?>
      <?php elseif (in_array($inv['status'], ['unpaid', 'overdue'])): ?>
        <hr><h6><i class="fas fa-upload"></i> Upload Payment Receipt</h6>
        <form method="POST" action="index.php?page=payments" enctype="multipart/form-data">
          <input type="hidden" name="action" value="upload_payment">
          <input type="hidden" name="invoice_id" value="<?php echo $inv['id']; ?>">
          <input type="hidden" name="invoice_class_id" value="<?php echo $inv['class_id']; ?>">
          <input type="hidden" name="invoice_amount" value="<?php echo $inv['amount']; ?>">
          <div class="mb-3">
            <label class="form-label">Receipt (Image/PDF) *</label>
            <input type="file" name="receipt" class="form-control" accept="image/*,.pdf" required>
            <div class="form-text">Max 5MB | JPG, PNG, PDF</div>
          </div>
          <button type="submit" class="btn btn-success"><i class="fas fa-upload"></i> Submit Payment</button>
        </form>
      <?php endif; ?>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
  </div></div>
</div>
<?php endforeach; ?>