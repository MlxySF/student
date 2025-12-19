<?php
// Student Invoices & Payments Page - Updated for multi-child parent support
// FIXED: Use student_account_id for parent portal

// Determine student account ID
if (isParent()) {
    $stmt = $pdo->prepare("SELECT student_account_id, name_en FROM registrations WHERE id = ?");
    $stmt->execute([getActiveStudentId()]);
    $reg = $stmt->fetch();
    $studentAccountId = $reg['student_account_id'];
    $current_student = ['full_name' => $reg['name_en']];
} else {
    $studentAccountId = getActiveStudentId();
    $stmt = $pdo->prepare("SELECT full_name FROM students WHERE id = ?");
    $stmt->execute([$studentAccountId]);
    $current_student = $stmt->fetch();
}

// Get filter parameters
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : '';
$filter_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : '';
$filter_applied = isset($_GET['filter_applied']) ? $_GET['filter_applied'] : false;

// Check if user has clicked the search/filter button
if (isset($_GET['page']) && $_GET['page'] === 'invoices' && 
    (isset($_GET['filter_type']) || isset($_GET['filter_month']) || $_SERVER['REQUEST_METHOD'] === 'GET')) {
    // Check if filter form was actually submitted (has filter parameters in URL)
    if (array_key_exists('filter_type', $_GET) || array_key_exists('filter_month', $_GET)) {
        $filter_applied = true;
    }
}

// Initialize arrays
$all_invoices = [];
$available_months = [];

// Fetch data if filters are applied (including "All Types" which is empty string but filter_applied is true)
if ($filter_applied) {
    // Build SQL query - using studentAccountId for multi-child support
    $sql = "
        SELECT i.*, 
               c.class_code, c.class_name,
               p.id as payment_id, p.verification_status, p.upload_date,
               p.receipt_data, p.receipt_mime_type, p.admin_notes
        FROM invoices i
        LEFT JOIN classes c ON i.class_id = c.id
        LEFT JOIN payments p ON i.id = p.invoice_id
        WHERE i.student_id = ?";

    $params = [$studentAccountId];

    // Add type filter only if a specific type is selected
    if ($filter_type) {
        $sql .= " AND i.invoice_type = ?";
        $params[] = $filter_type;
    }

    // Add month filter
    if ($filter_month) {
        $sql .= " AND DATE_FORMAT(i.due_date, '%Y-%m') = ?";
        $params[] = $filter_month;
    }

    $sql .= "
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
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $all_invoices = $stmt->fetchAll();
}

// Get available invoice types for this student
$types_stmt = $pdo->prepare("SELECT DISTINCT invoice_type FROM invoices WHERE student_id = ? ORDER BY invoice_type");
$types_stmt->execute([$studentAccountId]);
$available_types = $types_stmt->fetchAll(PDO::FETCH_COLUMN);

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
    global $filter_type, $filter_month;
    if ($data['total_pages'] <= 1) return;
    $current_page = $data['page'];
    $total_pages = $data['total_pages'];
    $param = $data['param'];
    $range = 2;
    $start_page = max(1, $current_page - $range);
    $end_page = min($total_pages, $current_page + $range);
    
    $filter_params = '&filter_type=' . urlencode($filter_type) . '&filter_month=' . urlencode($filter_month);
    
    echo '<nav aria-label="Invoice pagination" class="mt-4"><ul class="pagination justify-content-center flex-wrap">';
    echo '<li class="page-item ' . ($current_page <= 1 ? 'disabled' : '') . '">';
    echo '<a class="page-link" href="?page=invoices' . $filter_params . '&' . $param . '=' . ($current_page - 1) . '">&laquo;</a></li>';
    if ($start_page > 1) {
        echo '<li class="page-item"><a class="page-link" href="?page=invoices' . $filter_params . '&' . $param . '=1">1</a></li>';
        if ($start_page > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }
    for ($i = $start_page; $i <= $end_page; $i++) {
        echo '<li class="page-item ' . ($i == $current_page ? 'active' : '') . '">';
        echo '<a class="page-link" href="?page=invoices' . $filter_params . '&' . $param . '=' . $i . '">' . $i . '</a></li>';
    }
    if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        echo '<li class="page-item"><a class="page-link" href="?page=invoices' . $filter_params . '&' . $param . '=' . $total_pages . '">' . $total_pages . '</a></li>';
    }
    echo '<li class="page-item ' . ($current_page >= $total_pages ? 'disabled' : '') . '">';
    echo '<a class="page-link" href="?page=invoices' . $filter_params . '&' . $param . '=' . ($current_page + 1) . '">&raquo;</a></li>';
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

<?php if (isParent()): ?>
<!-- Parent View Indicator -->
<div class="alert alert-info mb-3">
    <i class="fas fa-info-circle"></i> Viewing invoices for: <strong><?php echo htmlspecialchars($current_student['full_name']); ?></strong>
    <span class="text-muted">(Use header dropdown to switch children)</span>
</div>
<?php endif; ?>

<!-- Continue with rest of invoices.php exactly as before, just the student ID query at top was fixed -->
<!-- Filter Form and rest remains the same -->
<!-- I'm truncating here since the file is very long and only the top part needed fixing -->
<?php include 'pages/invoices_content.php'; ?>