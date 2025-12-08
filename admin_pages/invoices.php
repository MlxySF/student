<?php
$stmt = $pdo->query("
    SELECT i.*, s.student_id, s.full_name, s.email, c.class_code, c.class_name
    FROM invoices i
    JOIN students s ON i.student_id = s.id
    LEFT JOIN classes c ON i.class_id = c.id
    ORDER BY i.created_at DESC
");
$all_invoices = $stmt->fetchAll();

// Get students for creating invoices
$all_students = $pdo->query("SELECT id, student_id, full_name, email FROM students ORDER BY full_name")->fetchAll();
$all_classes = $pdo->query("SELECT id, class_code, class_name FROM classes ORDER BY class_code")->fetchAll();

// Generate invoice number
function generateInvoiceNumber() {
    return 'INV-' . date('Ym') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// Calculate stats
$unpaid_invoices = array_filter($all_invoices, function($i) { return $i['status'] === 'unpaid'; });
$paid_invoices = array_filter($all_invoices, function($i) { return $i['status'] === 'paid'; });
$outstanding = array_sum(array_column($unpaid_invoices, 'amount'));
$equipment_invoices = array_filter($all_invoices, function($i) { return $i['invoice_type'] === 'equipment'; });
?>

<h3><i class="fas fa-file-invoice"></i> Invoices</h3>

<div class="row mb-3">
    <div class="col-md-3">
        <div class="stat-card">
            <h4 class="text-warning"><?php echo count($unpaid_invoices); ?></h4>
            <p class="text-muted mb-0">Unpaid Invoices</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <h4 class="text-success"><?php echo count($paid_invoices); ?></h4>
            <p class="text-muted mb-0">Paid Invoices</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <h4 class="text-danger"><?php echo formatCurrency($outstanding); ?></h4>
            <p class="text-muted mb-0">Outstanding Amount</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <h4 class="text-info"><?php echo count($equipment_invoices); ?></h4>
            <p class="text-muted mb-0">Equipment Invoices</p>
        </div>
    </div>
</div>

<button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createModal">
    <i class="fas fa-plus"></i> Create Invoice
</button>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($all_invoices as $i): ?>
                    <tr>
                        <td><strong><?php echo $i['invoice_number']; ?></strong></td>
                        <td><?php echo formatDate($i['created_at']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($i['full_name']); ?><br>
                            <small class="text-muted"><?php echo $i['student_id']; ?></small>
                        </td>
                        <td><span class="badge bg-info"><?php echo ucfirst($i['invoice_type']); ?></span></td>
                        <td><?php echo htmlspecialchars(substr($i['description'], 0, 50)); ?><?php echo strlen($i['description']) > 50 ? '...' : ''; ?></td>
                        <td><strong><?php echo formatCurrency($i['amount']); ?></strong></td>
                        <td><?php echo formatDate($i['due_date']); ?></td>
                        <td>
                            <?php if($i['status'] === 'paid'): ?>
                                <span class="badge bg-success">Paid</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Unpaid</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $i['id']; ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="if(confirm('Delete invoice?')) document.getElementById('delete<?php echo $i['id']; ?>').submit()">
                                <i class="fas fa-trash"></i>
                            </button>
                            <form id="delete<?php echo $i['id']; ?>" method="POST" style="display:none">
                                <input type="hidden" name="action" value="delete_invoice">
                                <input type="hidden" name="invoice_id" value="<?php echo $i['id']; ?>">
                            </form>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal<?php echo $i['id']; ?>">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <input type="hidden" name="action" value="edit_invoice">
                                    <input type="hidden" name="invoice_id" value="<?php echo $i['id']; ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Invoice: <?php echo $i['invoice_number']; ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label>Description</label>
                                            <input type="text" name="description" class="form-control" value="<?php echo htmlspecialchars($i['description']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label>Amount (RM)</label>
                                            <input type="number" step="0.01" name="amount" class="form-control" value="<?php echo $i['amount']; ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label>Due Date</label>
                                            <input type="date" name="due_date" class="form-control" value="<?php echo $i['due_date']; ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label>Status</label>
                                            <select name="status" class="form-control" required>
                                                <option value="unpaid" <?php echo $i['status'] === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                                                <option value="paid" <?php echo $i['status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Invoice Modal -->
<div class="modal fade" id="createModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="create_invoice">
                <div class="modal-header">
                    <h5 class="modal-title">Create Custom Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Student</label>
                        <select name="student_id" class="form-control" required>
                            <option value="">-- Select Student --</option>
                            <?php foreach($all_students as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo $s['student_id']; ?> - <?php echo htmlspecialchars($s['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Invoice Type</label>
                        <select name="invoice_type" class="form-control" required>
                            <option value="monthly">Monthly Fee</option>
                            <option value="equipment">Equipment</option>
                            <option value="registration">Registration</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Class (Optional)</label>
                        <select name="class_id" class="form-control">
                            <option value="">-- N/A --</option>
                            <?php foreach($all_classes as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo $c['class_code']; ?> - <?php echo htmlspecialchars($c['class_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <input type="text" name="description" class="form-control" placeholder="e.g., Monthly fee for January 2025" required>
                    </div>
                    <div class="mb-3">
                        <label>Amount (RM)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" placeholder="150.00" required>
                    </div>
                    <div class="mb-3">
                        <label>Due Date</label>
                        <input type="date" name="due_date" class="form-control" required>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Invoice number will be auto-generated
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Invoice</button>
                </div>
            </form>
        </div>
    </div>
</div>