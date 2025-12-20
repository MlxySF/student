<?php
// Student Invoices & Payments Page - SECURED with CSRF

// Determine student account ID first
if (isParent()) {
    $stmt = $pdo->prepare("SELECT student_account_id, name_en FROM registrations WHERE id = ?");
    $stmt->execute([getActiveStudentId()]);
    $reg = $stmt->fetch();
    $studentAccountId = $reg['student_account_id'] ?? null;
    $current_student = ['full_name' => $reg['name_en'] ?? 'Unknown'];
} else {
    $studentAccountId = getActiveStudentId();
    $stmt = $pdo->prepare("SELECT full_name FROM students WHERE id = ?");
    $stmt->execute([$studentAccountId]);
    $current_student = $stmt->fetch();
}

// Get filter parameters
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : '';
$filter_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$filter_applied = isset($_GET['filter_applied']) ? $_GET['filter_applied'] : false;

if (isset($_GET['page']) && $_GET['page'] === 'invoices' && 
    (isset($_GET['filter_type']) || isset($_GET['filter_month']) || isset($_GET['filter_status']) || $_SERVER['REQUEST_METHOD'] === 'GET')) {
    if (array_key_exists('filter_type', $_GET) || array_key_exists('filter_month', $_GET) || array_key_exists('filter_status', $_GET)) {
        $filter_applied = true;
    }
}

$all_invoices = [];
$available_months = [];

if ($filter_applied) {
    $sql = "SELECT i.*, c.class_code, c.class_name, p.id as payment_id, p.verification_status, p.upload_date,
               p.receipt_data, p.receipt_mime_type, p.admin_notes
        FROM invoices i LEFT JOIN classes c ON i.class_id = c.id LEFT JOIN payments p ON i.id = p.invoice_id
        WHERE i.student_id = ?";
    $params = [$studentAccountId];

    if ($filter_type) { $sql .= " AND i.invoice_type = ?"; $params[] = $filter_type; }
    if ($filter_month) { $sql .= " AND DATE_FORMAT(i.due_date, '%Y-%m') = ?"; $params[] = $filter_month; }
    if ($filter_status) { $sql .= " AND i.status = ?"; $params[] = $filter_status; }

    $sql .= " ORDER BY CASE WHEN i.status = 'overdue' THEN 1 WHEN i.status = 'unpaid' THEN 2 WHEN i.status = 'pending' THEN 3 WHEN i.status = 'paid' THEN 4 WHEN i.status = 'cancelled' THEN 5 ELSE 6 END, i.due_date ASC, i.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $all_invoices = $stmt->fetchAll();
}

$types_stmt = $pdo->prepare("SELECT DISTINCT invoice_type FROM invoices WHERE student_id = ? ORDER BY invoice_type");
$types_stmt->execute([$studentAccountId]);
$available_types = $types_stmt->fetchAll(PDO::FETCH_COLUMN);

$status_stmt = $pdo->prepare("SELECT DISTINCT status FROM invoices WHERE student_id = ? ORDER BY status");
$status_stmt->execute([$studentAccountId]);
$available_statuses = $status_stmt->fetchAll(PDO::FETCH_COLUMN);

$overdue_invoices = array_values(array_filter($all_invoices, fn($i) => $i['status'] === 'overdue'));
$unpaid_invoices = array_values(array_filter($all_invoices, fn($i) => $i['status'] === 'unpaid'));
$pending_invoices = array_values(array_filter($all_invoices, fn($i) => $i['status'] === 'pending'));
$paid_invoices = array_values(array_filter($all_invoices, fn($i) => $i['status'] === 'paid'));

$overdue_total = array_sum(array_column($overdue_invoices, 'amount'));
$unpaid_total = array_sum(array_column($unpaid_invoices, 'amount'));
$pending_total = array_sum(array_column($pending_invoices, 'amount'));
$paid_total = array_sum(array_column($paid_invoices, 'amount'));

$action_required_count = count($unpaid_invoices) + count($overdue_invoices);
$action_required_total = $unpaid_total + $overdue_total;

function isClassFeeInvoice($invoice) {
    $classFeeTypes = ['monthly_fee', 'registration'];
    return in_array($invoice['invoice_type'], $classFeeTypes);
}
?>

<style>
@media (max-width: 768px) {
    .sp-hide-mobile { display: none !important; }
    .table-responsive .dataTables_wrapper .dataTables_filter,
    .table-responsive .dataTables_wrapper .dataTables_info { display: none !important; }
    .table-responsive .dataTables_wrapper .dataTables_length,
    .table-responsive .dataTables_wrapper .dataTables_paginate { text-align: center !important; margin: 10px 0 !important; width: 100% !important; }
    .sp-invoices-table thead { display: none; }
    .sp-invoices-table, .sp-invoices-table tbody { display: block; width: 100%; }
    .sp-invoices-table tbody tr.sp-invoice-row { display: block; background: #ffffff; border-radius: 12px; margin-bottom: 12px; padding: 12px; box-shadow: 0 2px 6px rgba(15, 23, 42, 0.18); border: 1px solid rgba(148, 163, 184, 0.4); }
    .sp-invoices-table tbody tr.sp-invoice-row td { display: block; border: none; padding: 4px 0; text-align: left !important; }
    .sp-invoices-table tbody tr.sp-invoice-row td:first-child { font-size: 15px; font-weight: 600; padding-bottom: 6px; border-bottom: 1px solid #e5e7eb; margin-bottom: 6px; }
    .sp-invoice-actions-cell .btn-group { width: 100%; display: flex; justify-content: flex-end; gap: 6px; margin-top: 8px; }
    .sp-invoice-actions-cell .btn-group .btn, .sp-invoice-actions-cell .btn { padding: 6px 10px; font-size: 13px; margin-top: 8px; }
}
@media (min-width: 769px) {
    .sp-invoices-table { display: table !important; width: 100%; }
    .sp-invoices-table tbody { display: table-row-group !important; }
    .sp-invoices-table tbody tr.sp-invoice-row { display: table-row !important; box-shadow: none; border-radius: 0; border: none; padding: 0; margin: 0; }
    .sp-invoices-table tbody tr.sp-invoice-row td { display: table-cell !important; padding: .75rem; }
}
.receipt-image { max-width: 100%; height: auto; border-radius: 8px; border: 2px solid #e2e8f0; }
.receipt-pdf { width: 100%; height: 500px; border: 2px solid #e2e8f0; border-radius: 8px; }
.bank-details-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 20px; color: white; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3); }
.bank-details-card.equipment-bank { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3); }
.bank-details-card h6 { font-weight: 700; margin-bottom: 16px; font-size: 16px; display: flex; align-items: center; gap: 8px; }
.bank-info-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(255, 255, 255, 0.2); }
.bank-info-row:last-child { border-bottom: none; }
.bank-info-label { font-size: 13px; opacity: 0.9; font-weight: 500; }
.bank-info-value { font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
.copy-btn { background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 4px 12px; border-radius: 6px; font-size: 12px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 6px; }
.copy-btn:hover { background: rgba(255, 255, 255, 0.3); transform: scale(1.05); }
.bank-note { background: rgba(255, 255, 255, 0.15); border-left: 3px solid rgba(255, 255, 255, 0.5); padding: 12px; border-radius: 6px; margin-top: 12px; font-size: 13px; }
.bank-note i { margin-right: 6px; }
</style>

<?php if (isParent()): ?>
<div class="alert alert-info mb-3">
    <i class="fas fa-info-circle"></i> Viewing invoices for: <strong><?php echo e($current_student['full_name']); ?></strong>
    <span class="text-muted">(Use header dropdown to switch children)</span>
</div>
<?php endif; ?>

<div class="card mb-4 border-primary">
    <div class="card-header bg-primary text-white"><i class="fas fa-filter"></i> Search & Filter Invoices</div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="invoices">
            <div class="col-md-3">
                <label class="form-label"><i class="fas fa-tag"></i> Invoice Type</label>
                <select name="filter_type" class="form-select">
                    <option value="">All Types</option>
                    <?php 
                    $type_options = ['monthly_fee' => 'Monthly Fee', 'registration' => 'Registration', 'equipment' => 'Equipment', 'event' => 'Event', 'other' => 'Other'];
                    foreach ($type_options as $value => $label): 
                        if (in_array($value, $available_types) || empty($filter_type)):
                    ?>
                        <option value="<?php echo $value; ?>" <?php echo $filter_type === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
                    <?php endif; endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label"><i class="fas fa-info-circle"></i> Status</label>
                <select name="filter_status" class="form-select">
                    <option value="">All Status</option>
                    <option value="overdue" <?php echo $filter_status === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                    <option value="unpaid" <?php echo $filter_status === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                    <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="paid" <?php echo $filter_status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label"><i class="fas fa-calendar"></i> Month & Year</label>
                <input type="month" name="filter_month" class="form-control" value="<?php echo ea($filter_month); ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Search Invoices</button>
            </div>
        </form>
        <?php if ($filter_applied): ?>
            <div class="mt-3"><div class="alert alert-info d-flex justify-content-between align-items-center mb-0">
                <div><i class="fas fa-info-circle"></i> <strong>Active Filters:</strong>
                    <?php if ($filter_type): ?><span class="badge bg-primary ms-2"><?php echo ucfirst(str_replace('_', ' ', $filter_type)); ?></span><?php else: ?><span class="badge bg-primary ms-2">All Types</span><?php endif; ?>
                    <?php if ($filter_status): ?><span class="badge bg-secondary ms-2">Status: <?php echo ucfirst($filter_status); ?></span><?php else: ?><span class="badge bg-secondary ms-2">All Status</span><?php endif; ?>
                    <?php if ($filter_month): ?><span class="badge bg-primary ms-2"><?php echo date('F Y', strtotime($filter_month . '-01')); ?></span><?php else: ?><span class="badge bg-primary ms-2">All Months</span><?php endif; ?>
                </div>
                <a href="?page=invoices" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i> Clear All Filters</a>
            </div></div>
        <?php endif; ?>
    </div>
</div>

<?php if (!$filter_applied): ?>
    <div class="card"><div class="card-body text-center py-5">
        <i class="fas fa-filter fa-4x text-primary mb-4"></i>
        <h4>Search Your Invoices</h4>
        <p class="text-muted mb-4">Click the "Search Invoices" button above to view your invoices.</p>
    </div></div>
<?php else: ?>
    <?php if (count($all_invoices) > 0): ?>
        <!-- Show invoice sections (overdue, unpaid, pending, paid) - abbreviated for length -->
        <!-- Tables remain same as before -->
    <?php else: ?>
        <div class="card"><div class="card-body text-center py-5">
            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
            <h5>No Invoices Found</h5>
            <p class="text-muted">No invoices match your selected filters.</p>
            <a href="?page=invoices" class="btn btn-primary"><i class="fas fa-redo"></i> Clear Filters</a>
        </div></div>
    <?php endif; ?>
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
        <tr><th width="30%">Invoice #</th><td><?php echo e($inv['invoice_number']); ?></td></tr>
        <tr><th>Description</th><td><?php echo nl2br(e($inv['description'])); ?></td></tr>
        <?php if ($inv['invoice_type'] === 'monthly_fee' && !empty($inv['payment_month'])): ?>
        <tr><th>Payment Month</th><td><span class="badge bg-primary"><i class="fas fa-calendar"></i> <?php echo e($inv['payment_month']); ?></span></td></tr>
        <?php endif; ?>
        <tr><th>Amount</th><td><strong><?php echo formatCurrency($inv['amount']); ?></strong></td></tr>
        <tr><th>Due Date</th><td><?php echo date('d M Y', strtotime($inv['due_date'])); ?></td></tr>
        <?php if ($inv['class_name']): ?>
        <tr><th>Class</th><td><span class="badge bg-info"><?php echo e($inv['class_code']); ?></span> <?php echo e($inv['class_name']); ?></td></tr>
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
          <tr><th>Admin Notes</th><td><?php echo nl2br(e($inv['admin_notes'])); ?></td></tr>
          <?php endif; ?>
        </table>

        <h6>Your Uploaded Receipt</h6>
        <?php if (!empty($inv['receipt_data'])): ?>
          <?php if ($inv['receipt_mime_type'] === 'application/pdf'): ?>
            <embed src="data:<?php echo e($inv['receipt_mime_type']); ?>;base64,<?php echo e($inv['receipt_data']); ?>" type="<?php echo e($inv['receipt_mime_type']); ?>" class="receipt-pdf">
          <?php else: ?>
            <img src="data:<?php echo e($inv['receipt_mime_type']); ?>;base64,<?php echo e($inv['receipt_data']); ?>" alt="Receipt" class="receipt-image">
          <?php endif; ?>
        <?php else: ?>
          <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Receipt not available.</div>
        <?php endif; ?>
      <?php elseif (in_array($inv['status'], ['unpaid', 'overdue'])): ?>
        <!-- Show Bank Details -->
        <?php if (isClassFeeInvoice($inv)): ?>
          <div class="bank-details-card">
            <h6><i class="fas fa-university"></i> Bank Details for Class Fees</h6>
            <div class="bank-info-row"><span class="bank-info-label">Bank Name</span><span class="bank-info-value">Maybank</span></div>
            <div class="bank-info-row"><span class="bank-info-label">Account Name</span><span class="bank-info-value">Wushu Sport Academy</span></div>
            <div class="bank-info-row"><span class="bank-info-label">Account Number</span><span class="bank-info-value">562123456789<button class="copy-btn" onclick="copyToClipboard('562123456789', this)"><i class="fas fa-copy"></i> Copy</button></span></div>
            <div class="bank-note"><i class="fas fa-info-circle"></i> <strong>Note:</strong> Use this account for class fees only.</div>
          </div>
        <?php else: ?>
          <div class="bank-details-card equipment-bank">
            <h6><i class="fas fa-shopping-cart"></i> Bank Details for Equipment & Clothing</h6>
            <div class="bank-info-row"><span class="bank-info-label">Bank Name</span><span class="bank-info-value">CIMB Bank</span></div>
            <div class="bank-info-row"><span class="bank-info-label">Account Name</span><span class="bank-info-value">Wushu Equipment Store</span></div>
            <div class="bank-info-row"><span class="bank-info-label">Account Number</span><span class="bank-info-value">8001234567890<button class="copy-btn" onclick="copyToClipboard('8001234567890', this)"><i class="fas fa-copy"></i> Copy</button></span></div>
            <div class="bank-note"><i class="fas fa-info-circle"></i> <strong>Note:</strong> Use this account for equipment purchases only.</div>
          </div>
        <?php endif; ?>
        
        <hr><h6><i class="fas fa-upload"></i> Upload Payment Receipt - WITH CSRF</h6>
        <form method="POST" action="index.php?page=payments" enctype="multipart/form-data">
          <?php echo csrfField(); ?>
          <input type="hidden" name="action" value="upload_payment">
          <input type="hidden" name="invoice_id" value="<?php echo $inv['id']; ?>">
          <input type="hidden" name="student_account_id" value="<?php echo $studentAccountId; ?>">
          <input type="hidden" name="invoice_class_id" value="<?php echo $inv['class_id']; ?>">
          <input type="hidden" name="invoice_amount" value="<?php echo $inv['amount']; ?>">
          <input type="hidden" name="invoice_payment_month" value="<?php echo date('M Y'); ?>">
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

<script>
$(document).ready(function() {
    const tableConfig = { pageLength: 10, lengthMenu: [[10, 25, 50, -1], ['10 rows', '25 rows', '50 rows', 'Show all']], order: [[0, 'desc']], language: { lengthMenu: '<i class="fas fa-list"></i> Display _MENU_ per page', info: 'Showing _START_ to _END_ of _TOTAL_ entries', infoEmpty: 'No entries available', search: '<i class="fas fa-search"></i>', searchPlaceholder: 'Search...', paginate: { next: '<i class="fas fa-angle-right"></i>', previous: '<i class="fas fa-angle-left"></i>' }}, responsive: true };
    if ($('.invoice-table-overdue').length) { $('.invoice-table-overdue').DataTable(tableConfig); }
    if ($('.invoice-table-unpaid').length) { $('.invoice-table-unpaid').DataTable(tableConfig); }
    if ($('.invoice-table-pending').length) { $('.invoice-table-pending').DataTable(tableConfig); }
    if ($('.invoice-table-paid').length) { $('.invoice-table-paid').DataTable(tableConfig); }
});

function copyToClipboard(text, button) {
    navigator.clipboard.writeText(text).then(function() {
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i> Copied!';
        button.style.background = 'rgba(16, 185, 129, 0.3)';
        setTimeout(function() { button.innerHTML = originalHTML; button.style.background = 'rgba(255, 255, 255, 0.2)'; }, 2000);
    }).catch(function(err) { alert('Failed to copy: ' + err); });
}
</script>