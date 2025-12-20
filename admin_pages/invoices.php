<?php
// Filters - trim to handle any whitespace issues
$filter_month = isset($_GET['filter_month']) ? trim($_GET['filter_month']) : '';
$filter_type = isset($_GET['filter_type']) ? trim($_GET['filter_type']) : '';
$filter_status = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';

// Parse the filter_month to handle year-only or year-month format
$filter_year = '';
$filter_month_formatted = '';
$is_year_only = false;

if ($filter_month) {
    // Check if it's just a year (4 digits) or year-month format
    if (preg_match('/^\d{4}$/', $filter_month)) {
        // Year only (e.g., "2025")
        $filter_year = $filter_month;
        $is_year_only = true;
    } elseif (preg_match('/^\d{4}-\d{2}$/', $filter_month)) {
        // Year-month format (e.g., "2025-12")
        $filter_month_formatted = date('M Y', strtotime($filter_month . '-01'));
    }
}

// Build WHERE clauses for single-table queries
$where_conditions = [];
$params = [];

if ($is_year_only) {
    // Filter by year only - match any month containing the year
    $where_conditions[] = "TRIM(payment_month) LIKE ?";
    $params[] = '%' . $filter_year;
} elseif ($filter_month_formatted) {
    // Filter by specific month
    $where_conditions[] = "TRIM(payment_month) = ?";
    $params[] = $filter_month_formatted;
}

if ($filter_type) {
    $where_conditions[] = "invoice_type = ?";
    $params[] = $filter_type;
}

if ($filter_status) {
    $where_conditions[] = "status = ?";
    $params[] = $filter_status;
}

$where_clause = count($where_conditions) > 0 ? " AND " . implode(" AND ", $where_conditions) : "";

// Build WHERE clauses for JOIN queries (with table alias)
$where_conditions_join = [];
$params_join = [];

if ($is_year_only) {
    // Filter by year only - match any month containing the year
    $where_conditions_join[] = "TRIM(i.payment_month) LIKE ?";
    $params_join[] = '%' . $filter_year;
} elseif ($filter_month_formatted) {
    // Filter by specific month
    $where_conditions_join[] = "TRIM(i.payment_month) = ?";
    $params_join[] = $filter_month_formatted;
}

if ($filter_type) {
    $where_conditions_join[] = "i.invoice_type = ?";
    $params_join[] = $filter_type;
}

if ($filter_status) {
    $where_conditions_join[] = "i.status = ?";
    $params_join[] = $filter_status;
}

// Get invoice statistics
$sql_unpaid = "SELECT COUNT(*) as total FROM invoices WHERE status = 'unpaid'" . $where_clause;
$stmt = $pdo->prepare($sql_unpaid);
$stmt->execute($params);
$unpaid_count = $stmt->fetch()['total'];

$sql_pending = "SELECT COUNT(*) as total FROM invoices WHERE status = 'pending'" . $where_clause;
$stmt = $pdo->prepare($sql_pending);
$stmt->execute($params);
$pending_count = $stmt->fetch()['total'];

$sql_paid = "SELECT COUNT(*) as total FROM invoices WHERE status = 'paid'" . $where_clause;
$stmt = $pdo->prepare($sql_paid);
$stmt->execute($params);
$paid_count = $stmt->fetch()['total'];

$sql_outstanding = "SELECT SUM(amount) as total FROM invoices WHERE status IN ('unpaid', 'pending')" . $where_clause;
$stmt = $pdo->prepare($sql_outstanding);
$stmt->execute($params);
$outstanding_amount = $stmt->fetch()['total'] ?? 0;

// Get all invoices with payment information - OPTIMIZED: Removed receipt_data and receipt_mime_type for performance
$sql = "
    SELECT i.*, 
           s.student_id, s.full_name, s.email, 
           c.class_code, c.class_name,
           p.id as payment_id, p.verification_status, 
           p.upload_date, p.payment_date, p.admin_notes
    FROM invoices i
    JOIN students s ON i.student_id = s.id
    LEFT JOIN classes c ON i.class_id = c.id
    LEFT JOIN payments p ON i.id = p.invoice_id";

if (count($where_conditions_join) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions_join);
}

$sql .= "
    ORDER BY 
        CASE 
            WHEN i.status = 'unpaid' THEN 1
            WHEN i.status = 'pending' THEN 2
            WHEN i.status = 'overdue' THEN 3
            WHEN i.status = 'paid' THEN 4
            ELSE 5
        END,
        i.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params_join);
$all_invoices = $stmt->fetchAll();

// Get students and classes for creating invoices
// FIXED: Filter by payment_status from registrations table, exclude 'rejected' students
$all_students = $pdo->query("
    SELECT s.id, s.student_id, s.full_name, s.email 
    FROM students s
    INNER JOIN registrations r ON s.id = r.student_account_id
    WHERE r.payment_status != 'rejected'
    GROUP BY s.id
    ORDER BY s.full_name
")->fetchAll();

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
.receipt-image { max-width: 100%; height: auto; border-radius: 8px; border: 2px solid #e2e8f0; }
.receipt-pdf { width: 100%; height: 500px; border: 2px solid #e2e8f0; border-radius: 8px; }
.receipt-loading { text-align: center; padding: 40px; }
</style>

<!-- Filter Form -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white"><i class="fas fa-filter"></i> Search & Filter Invoices</div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="invoices">
            
            <div class="col-md-3">
                <label class="form-label"><i class="fas fa-tag"></i> Invoice Type</label>
                <select name="filter_type" class="form-select">
                    <option value="">All Types</option>
                    <option value="monthly_fee" <?php echo $filter_type === 'monthly_fee' ? 'selected' : ''; ?>>Monthly Fee</option>
                    <option value="registration" <?php echo $filter_type === 'registration' ? 'selected' : ''; ?>>Registration</option>
                    <option value="equipment" <?php echo $filter_type === 'equipment' ? 'selected' : ''; ?>>Equipment</option>
                    <option value="event" <?php echo $filter_type === 'event' ? 'selected' : ''; ?>>Event</option>
                    <option value="other" <?php echo $filter_type === 'other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label"><i class="fas fa-info-circle"></i> Status</label>
                <select name="filter_status" class="form-select">
                    <option value="">All Status</option>
                    <option value="unpaid" <?php echo $filter_status === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                    <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="paid" <?php echo $filter_status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="overdue" <?php echo $filter_status === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                    <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label"><i class="fas fa-calendar"></i> Year or Month</label>
                <input type="text" name="filter_month" class="form-control" value="<?php echo htmlspecialchars($filter_month); ?>" placeholder="2025 or 2025-12">
                <small class="text-muted">Enter year (e.g., 2025) or year-month (e.g., 2025-12)</small>
            </div>
            
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>
            
            <?php if ($filter_month || $filter_type || $filter_status): ?>
            <div class="col-md-2">
                <a href="?page=invoices" class="btn btn-secondary w-100"><i class="fas fa-times"></i> Clear</a>
            </div>
            <?php endif; ?>
        </form>
        
        <?php if ($filter_month || $filter_type || $filter_status): ?>
            <div class="alert alert-info mt-3 mb-0">
                <i class="fas fa-info-circle"></i> <strong>Active Filters:</strong>
                <?php if ($filter_type): ?>
                    <span class="badge bg-primary ms-2"><?php echo ucfirst(str_replace('_', ' ', $filter_type)); ?></span>
                <?php endif; ?>
                <?php if ($filter_status): ?>
                    <span class="badge bg-secondary ms-2">Status: <?php echo ucfirst($filter_status); ?></span>
                <?php endif; ?>
                <?php if ($is_year_only): ?>
                    <span class="badge bg-primary ms-2">Year: <?php echo htmlspecialchars($filter_year); ?> (All Months)</span>
                <?php elseif ($filter_month_formatted): ?>
                    <span class="badge bg-primary ms-2"><?php echo htmlspecialchars($filter_month_formatted); ?></span>
                <?php endif; ?>
                <div class="small text-muted mt-1">Found <?php echo count($all_invoices); ?> invoice(s)</div>
            </div>
        <?php endif; ?>
    </div>
</div>

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
    <?php
    // Build export URL with current filters
    $export_url = 'export_invoices_excel.php?';
    if ($filter_type) {
        $export_url .= 'filter_type=' . urlencode($filter_type) . '&';
    }
    if ($filter_status) {
        $export_url .= 'filter_status=' . urlencode($filter_status) . '&';
    }
    if ($filter_month) {
        $export_url .= 'filter_month=' . urlencode($filter_month);
    }
    $export_url = rtrim($export_url, '?&');
    ?>
    <a href="<?php echo $export_url; ?>" class="btn btn-outline-success" <?php echo count($all_invoices) === 0 ? 'disabled' : ''; ?>>
        <i class="fas fa-file-excel"></i> Export to Excel
    </a>
</div>

<div class="card">
    <div class="card-header"><i class="fas fa-file-invoice-dollar"></i> All Invoices & Payments (<?php echo count($all_invoices); ?>)</div>
    <div class="card-body">
        <?php if (count($all_invoices) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover data-table">
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
    $has_payment = !empty($invoice['payment_id']);
?>
    <tr>
        <td class="text-nowrap"><strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong>
            <div class="d-md-none text-muted small"><?php echo formatDate($invoice['created_at']); ?></div>
            <?php if ($invoice['invoice_type'] === 'monthly_fee' && !empty($invoice['payment_month'])): ?>
                <div class="d-md-none"><span class="badge bg-primary"><i class="fas fa-calendar"></i> <?php echo htmlspecialchars($invoice['payment_month']); ?></span></div>
            <?php endif; ?>
            <?php if ($has_payment): ?>
                <div class="d-md-none"><span class="badge bg-info"><i class="fas fa-receipt"></i> Payment Uploaded</span></div>
                <?php if (!empty($invoice['payment_date'])): ?>
                    <div class="d-md-none"><span class="badge bg-success"><i class="fas fa-calendar-check"></i> Paid: <?php echo formatDate($invoice['payment_date']); ?></span></div>
                <?php endif; ?>
            <?php endif; ?>
        </td>
        <td class="hide-mobile"><?php echo formatDate($invoice['created_at']); ?></td>
        <td><div><?php echo htmlspecialchars($invoice['full_name']); ?></div>
            <div class="d-md-none text-muted small"><?php echo htmlspecialchars($invoice['student_id']); ?> • <?php echo ucfirst($invoice['invoice_type']); ?></div></td>
        <td class="hide-mobile"><span class="badge bg-secondary"><?php echo ucfirst($invoice['invoice_type']); ?></span></td>
        <td class="hide-mobile"><?php 
            $desc = htmlspecialchars($invoice['description']); 
            echo (strlen($desc) > 50 ? substr($desc, 0, 50) . '…' : $desc); 
            if ($invoice['invoice_type'] === 'monthly_fee' && !empty($invoice['payment_month'])): 
        ?><br><span class="badge bg-primary mt-1"><i class="fas fa-calendar"></i> <?php echo htmlspecialchars($invoice['payment_month']); ?></span><?php endif; ?></td>
        <td><strong><?php echo formatCurrency($invoice['amount']); ?></strong>
            <div class="d-md-none text-muted small">Due: <?php echo formatDate($invoice['due_date']); ?></div></td>
        <td class="hide-mobile"><?php echo formatDate($invoice['due_date']); ?></td>
        <td>
            <span class="badge bg-<?php echo $status_badge; ?>"><i class="fas fa-<?php echo $status_icon; ?>"></i> <?php echo $status_text; ?></span>
            <?php if ($has_payment): ?>
                <br><span class="badge bg-info mt-1"><i class="fas fa-receipt"></i> Receipt</span>
                <?php if (!empty($invoice['payment_date'])): ?>
                    <br><span class="badge bg-success mt-1"><i class="fas fa-calendar-check"></i> <?php echo formatDate($invoice['payment_date']); ?></span>
                <?php endif; ?>
            <?php endif; ?>
        </td>
        <td class="invoice-actions-cell">
            <div class="btn-group btn-group-sm">
                <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#viewInvoiceModal<?php echo $invoice['id']; ?>" onclick="loadReceipt(<?php echo $invoice['id']; ?>)"><i class="fas fa-eye"></i></button>
                <?php if ($invoice['status'] === 'paid'): ?>
                <a href="generate_invoice_pdf.php?invoice_id=<?php echo $invoice['id']; ?>" class="btn btn-success btn-sm" target="_blank"><i class="fas fa-file-pdf"></i></a>
                <?php endif; ?>
                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editInvoiceModal<?php echo $invoice['id']; ?>"><i class="fas fa-edit"></i></button>
                <button class="btn btn-danger" onclick="if(confirm('Delete?')) document.getElementById('deleteInvoiceForm<?php echo $invoice['id']; ?>').submit();"><i class="fas fa-trash"></i></button>
            </div>
            <form id="deleteInvoiceForm<?php echo $invoice['id']; ?>" method="POST" action="admin.php" style="display:none;">
                <input type="hidden" name="action" value="delete_invoice"><input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
            </form>
        </td>
    </tr>
<?php endforeach; ?>
</tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info"><i class="fas fa-info-circle"></i> 
                <?php if ($filter_month || $filter_type || $filter_status): ?>
                    No invoices match your selected filters. Try adjusting your search criteria.
                <?php else: ?>
                    No invoices found. Use the filter above to search for invoices.
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php foreach ($all_invoices as $invoice): ?>
<div class="modal fade" id="viewInvoiceModal<?php echo $invoice['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-file-invoice"></i> Invoice & Payment Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <h6 class="mb-3"><i class="fas fa-file-invoice"></i> Invoice Information</h6>
            <table class="table table-bordered">
                <tr><th width="30%">Invoice Number</th><td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td></tr>
                <tr><th>Student</th><td><?php echo htmlspecialchars($invoice['full_name']); ?></td></tr>
                <tr><th>Student ID</th><td><span class="badge bg-secondary"><?php echo htmlspecialchars($invoice['student_id']); ?></span></td></tr>
                <?php if ($invoice['class_code']): ?>
                <tr><th>Class</th><td><span class="badge bg-info"><?php echo htmlspecialchars($invoice['class_code']); ?></span> <?php echo htmlspecialchars($invoice['class_name']); ?></td></tr>
                <?php endif; ?>
                <tr><th>Type</th><td><span class="badge bg-secondary"><?php echo ucfirst($invoice['invoice_type']); ?></span></td></tr>
                <?php if ($invoice['invoice_type'] === 'monthly_fee' && !empty($invoice['payment_month'])): ?>
                <tr><th>Payment Month</th><td><span class="badge bg-primary"><i class="fas fa-calendar"></i> <?php echo htmlspecialchars($invoice['payment_month']); ?></span></td></tr>
                <?php endif; ?>
                <tr><th>Description</th><td><?php echo nl2br(htmlspecialchars($invoice['description'])); ?></td></tr>
                <tr><th>Amount</th><td><strong><?php echo formatCurrency($invoice['amount']); ?></strong></td></tr>
                <tr><th>Due Date</th><td><?php echo formatDate($invoice['due_date']); ?></td></tr>
                <tr><th>Status</th><td><span class="badge bg-<?php echo $invoice['status'] === 'paid' ? 'success' : 'warning'; ?>"><?php echo ucfirst($invoice['status']); ?></span></td></tr>
                <?php if ($invoice['paid_date']): ?>
                <tr><th>Paid Date</th><td><?php echo formatDateTime($invoice['paid_date']); ?></td></tr>
                <?php endif; ?>
                <tr><th>Created Date</th><td><?php echo formatDateTime($invoice['created_at']); ?></td></tr>
            </table>

            <?php if (!empty($invoice['payment_id'])): ?>
                <hr class="my-4">
                <h6 class="mb-3"><i class="fas fa-receipt"></i> Payment Information</h6>
                <table class="table table-bordered">
                    <?php if (!empty($invoice['payment_date'])): ?>
                    <tr>
                        <th width="30%" class="bg-success text-white"><i class="fas fa-calendar-check"></i> Payment Date (Actual)</th>
                        <td class="fw-bold text-success"><?php echo formatDate($invoice['payment_date']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr><th width="30%">Upload Date</th><td><?php echo formatDateTime($invoice['upload_date']); ?></td></tr>
                    <tr><th>Verification Status</th><td>
                        <?php if ($invoice['verification_status'] === 'verified'): ?>
                            <span class="badge bg-success"><i class="fas fa-check-circle"></i> Verified</span>
                        <?php elseif ($invoice['verification_status'] === 'rejected'): ?>
                            <span class="badge bg-danger"><i class="fas fa-times-circle"></i> Rejected</span>
                        <?php else: ?>
                            <span class="badge bg-warning"><i class="fas fa-clock"></i> Pending</span>
                        <?php endif; ?>
                    </td></tr>
                    <?php if (!empty($invoice['admin_notes'])): ?>
                    <tr><th>Admin Notes</th><td><?php echo nl2br(htmlspecialchars($invoice['admin_notes'])); ?></td></tr>
                    <?php endif; ?>
                </table>

                <h6 class="mb-3">Payment Receipt</h6>
                <div id="receiptContainer<?php echo $invoice['id']; ?>">
                    <div class="receipt-loading">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading receipt...</p>
                    </div>
                </div>

                <?php if ($invoice['verification_status'] === 'pending'): ?>
                    <hr class="my-4">
                    <h6 class="mb-3">Verify Payment</h6>
                    <form method="POST" action="admin.php">
                        <input type="hidden" name="action" value="verify_payment">
                        <input type="hidden" name="payment_id" value="<?php echo $invoice['payment_id']; ?>">
                        <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Verification Status *</label>
                            <select name="verification_status" class="form-select" required>
                                <option value="">Select Status</option>
                                <option value="verified">✓ Verified - Approve & Mark Invoice as Paid</option>
                                <option value="rejected">✗ Rejected - Decline Payment</option>
                            </select>
                        </div>
                        <?php if (!empty($invoice['payment_date'])): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-calendar-check"></i> <strong>Payment Date:</strong> <?php echo formatDate($invoice['payment_date']); ?>
                        </div>
                        <?php endif; ?>
                        <div class="alert alert-info"><i class="fas fa-info-circle"></i> Approving will automatically mark invoice as PAID.</div>
                        <div class="mb-3">
                            <label class="form-label">Admin Notes (Optional)</label>
                            <textarea name="admin_notes" class="form-control" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Verify Payment</button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <hr class="my-4">
                <div class="alert alert-secondary"><i class="fas fa-info-circle"></i> <strong>No payment uploaded yet.</strong> Student hasn't submitted payment receipt for this invoice.</div>
            <?php endif; ?>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
    </div></div>
</div>

<div class="modal fade" id="editInvoiceModal<?php echo $invoice['id']; ?>" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-edit"></i> Edit Invoice</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" action="admin.php">
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
        <form method="POST" action="admin.php">
            <div class="modal-body">
                <input type="hidden" name="action" value="create_invoice">
                <div class="mb-3"><label class="form-label">Student *</label>
                    <select name="student_id" class="form-select" required>
                        <option value="">Select Student</option>
                        <?php foreach ($all_students as $student): ?>
                            <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['full_name']); ?> (<?php echo htmlspecialchars($student['student_id']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Only students with approved payment status are shown</small>
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
        <form method="POST" action="admin.php" onsubmit="return confirm('Generate monthly invoices for <?php echo date('F Y'); ?>?\n\nDue date: <?php echo date('F 10, Y'); ?>');">
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

<script>
// Track which receipts have been loaded to avoid re-fetching
const loadedReceipts = new Set();

function loadReceipt(invoiceId) {
    console.log('Loading receipt for invoice:', invoiceId);
    
    // Check if already loaded
    if (loadedReceipts.has(invoiceId)) {
        console.log('Receipt already loaded for invoice:', invoiceId);
        return;
    }
    
    const container = document.getElementById('receiptContainer' + invoiceId);
    if (!container) {
        console.error('Receipt container not found for invoice:', invoiceId);
        return;
    }
    
    // Build correct URL path
    const apiUrl = 'admin_pages/api/get_receipt.php?invoice_id=' + invoiceId;
    console.log('Fetching from URL:', apiUrl);
    
    // Fetch receipt data via AJAX
    fetch(apiUrl)
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            
            if (data.success) {
                // Mark as loaded
                loadedReceipts.add(invoiceId);
                
                // Display receipt based on mime type
                if (data.receipt_mime_type === 'application/pdf') {
                    container.innerHTML = '<embed src="data:' + data.receipt_mime_type + ';base64,' + data.receipt_data + '" type="' + data.receipt_mime_type + '" class="receipt-pdf">';
                } else {
                    container.innerHTML = '<img src="data:' + data.receipt_mime_type + ';base64,' + data.receipt_data + '" alt="Receipt" class="receipt-image">';
                }
                console.log('Receipt displayed successfully');
            } else {
                console.error('API error:', data.error);
                container.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> ' + (data.error || 'No receipt available') + '</div>';
            }
        })
        .catch(error => {
            console.error('Error loading receipt:', error);
            container.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Failed to load receipt. Please try again.</div>';
        });
}
</script>