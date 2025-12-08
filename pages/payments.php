<?php
// Student Payments Page - Upload payment receipts for classes OR invoices
// BASE64 VERSION - Receipts stored in database

// Get enrolled classes
$stmt = $pdo->prepare("
    SELECT c.*, e.enrollment_date
    FROM enrollments e
    JOIN classes c ON e.class_id = c.id
    WHERE e.student_id = ? AND e.status = 'active'
    ORDER BY c.class_code
");
$stmt->execute([getStudentId()]);
$enrolled_classes = $stmt->fetchAll();

// Get unpaid invoices for selection
$stmt = $pdo->prepare("
    SELECT i.*, c.class_code, c.class_name
    FROM invoices i
    LEFT JOIN classes c ON i.class_id = c.id
    WHERE i.student_id = ? AND i.status = 'unpaid'
    ORDER BY i.due_date ASC
");
$stmt->execute([getStudentId()]);
$unpaid_invoices = $stmt->fetchAll();

// Get payment history (EXCLUDING receipt_data for performance)
$stmt = $pdo->prepare("
    SELECT 
        p.id,
        p.student_id,
        p.class_id,
        p.amount,
        p.payment_month,
        p.receipt_filename,
        p.receipt_mime_type,
        p.receipt_size,
        p.upload_date,
        p.verification_status,
        p.verified_date,
        p.admin_notes,
        c.class_code, 
        c.class_name
    FROM payments p
    JOIN classes c ON p.class_id = c.id
    WHERE p.student_id = ?
    ORDER BY p.upload_date DESC
");
$stmt->execute([getStudentId()]);
$payment_history = $stmt->fetchAll();
?>

<!-- Upload Payment Card -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-upload"></i> Upload Payment Receipt
    </div>
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_payment">

            <div class="row">
                <!-- Payment Type Selection -->
                <div class="col-md-12 mb-3">
                    <label class="form-label">Payment Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="paymentType" required>
                        <option value="">Select payment type...</option>
                        <option value="class">Monthly Class Fee</option>
                        <option value="invoice">Custom Invoice Payment</option>
                    </select>
                </div>
            </div>

            <!-- For Class Payment -->
            <div id="classPaymentSection" style="display: none;">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Select Class <span class="text-danger">*</span></label>
                        <select name="class_id" id="classSelect" class="form-select">
                            <option value="">Choose class...</option>
                            <?php foreach($enrolled_classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>" data-fee="<?php echo $class['monthly_fee']; ?>">
                                    <?php echo $class['class_code']; ?> - <?php echo htmlspecialchars($class['class_name']); ?> 
                                    (<?php echo formatCurrency($class['monthly_fee']); ?>/month)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Payment Month <span class="text-danger">*</span></label>
                        <input type="month" name="payment_month" id="paymentMonth" class="form-control" 
                               value="<?php echo date('Y-m'); ?>">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Amount (RM) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="classAmount" class="form-control" 
                               step="0.01" min="0" placeholder="0.00">
                        <small class="text-muted">Amount will auto-fill based on class fee</small>
                    </div>
                </div>
            </div>

            <!-- For Invoice Payment -->
            <div id="invoicePaymentSection" style="display: none;">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Select Invoice <span class="text-danger">*</span></label>
                        <select name="invoice_id" id="invoiceSelect" class="form-select">
                            <option value="">Choose invoice...</option>
                            <?php foreach($unpaid_invoices as $invoice): 
                                $is_overdue = strtotime($invoice['due_date']) < time();
                            ?>
                                <option value="<?php echo $invoice['id']; ?>" 
                                        data-amount="<?php echo $invoice['amount']; ?>"
                                        data-class="<?php echo $invoice['class_id']; ?>">
                                    <?php echo $invoice['invoice_number']; ?> - 
                                    <?php echo htmlspecialchars(substr($invoice['description'], 0, 50)); ?> 
                                    (<?php echo formatCurrency($invoice['amount']); ?>)
                                    <?php if ($is_overdue): ?> - ⚠️ OVERDUE<?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (count($unpaid_invoices) === 0): ?>
                            <small class="text-success">✓ No unpaid invoices</small>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Amount (RM) <span class="text-danger">*</span></label>
                        <input type="number" name="invoice_amount" id="invoiceAmount" class="form-control" 
                               step="0.01" min="0" placeholder="0.00" readonly>
                        <small class="text-muted">Amount from invoice</small>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Payment Month</label>
                        <input type="month" name="invoice_payment_month" class="form-control" 
                               value="<?php echo date('Y-m'); ?>">
                        <small class="text-muted">For record keeping</small>
                    </div>
                </div>

                <input type="hidden" name="invoice_class_id" id="invoiceClassId">
            </div>

            <!-- Receipt Upload (Common for both) -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Upload Receipt <span class="text-danger">*</span></label>
                    <input type="file" name="receipt" class="form-control" 
                           accept=".jpg,.jpeg,.png,.pdf" required>
                    <small class="text-muted">Accepted: JPG, PNG, PDF (Max 5MB) - Stored securely in database</small>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Notes (Optional)</label>
                    <textarea name="notes" class="form-control" rows="2" 
                              placeholder="Add any additional notes here..."></textarea>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                <strong>Payment Instructions:</strong>
                <ol class="mb-0 mt-2">
                    <li>Select whether you're paying for a monthly class fee or a custom invoice</li>
                    <li>Choose the class or invoice you're paying for</li>
                    <li>Upload a clear photo or PDF of your payment receipt (stored securely in database)</li>
                    <li>Your payment will be verified by the administrator</li>
                </ol>
            </div>

            <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                <i class="fas fa-upload"></i> Upload Payment
            </button>
        </form>
    </div>
</div>

<!-- Payment History -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-history"></i> Payment History
    </div>
    <div class="card-body">
        <?php if ($payment_history): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Upload Date</th>
                            <th>Class</th>
                            <th>Month</th>
                            <th>Amount</th>
                            <th>File Size</th>
                            <th>Receipt</th>
                            <th>Status</th>
                            <th>Admin Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($payment_history as $payment): 
                            $status_class = $payment['verification_status'] === 'verified' ? 'success' : 
                                          ($payment['verification_status'] === 'rejected' ? 'danger' : 'warning');
                        ?>
                            <tr>
                                <td><?php echo formatDateTime($payment['upload_date']); ?></td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $payment['class_code']; ?></span>
                                    <br><small><?php echo htmlspecialchars($payment['class_name']); ?></small>
                                </td>
                                <td><?php echo formatMonth($payment['payment_month']); ?></td>
                                <td><strong><?php echo formatCurrency($payment['amount']); ?></strong></td>
                                <td><small class="text-muted"><?php echo formatFileSize($payment['receipt_size']); ?></small></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#receiptModal<?php echo $payment['id']; ?>">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $status_class; ?>">
                                        <?php echo ucfirst($payment['verification_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($payment['admin_notes']): ?>
                                        <small><?php echo htmlspecialchars($payment['admin_notes']); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-receipt fa-4x text-muted mb-3"></i>
                <h5>No payment records yet.</h5>
                <p class="text-muted">Upload your first payment above to get started.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Receipt Viewer Modals (fetch base64 data only when opened) -->
<?php foreach($payment_history as $payment): ?>
<div class="modal fade" id="receiptModal<?php echo $payment['id']; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file"></i> Payment Receipt - <?php echo $payment['receipt_filename']; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php
                // Fetch receipt data ONLY when modal is for this payment
                // This prevents loading all receipts at once (performance optimization)
                $stmt = $pdo->prepare("SELECT receipt_data, receipt_mime_type FROM payments WHERE id = ?");
                $stmt->execute([$payment['id']]);
                $receipt = $stmt->fetch();

                if ($receipt && $receipt['receipt_data']):
                    $dataURI = base64ToDataURI($receipt['receipt_data'], $receipt['receipt_mime_type']);

                    // Display based on file type
                    if (strpos($receipt['receipt_mime_type'], 'image') !== false):
                ?>
                    <!-- Image Receipt -->
                    <div class="text-center">
                        <img src="<?php echo $dataURI; ?>" 
                             class="img-fluid rounded" 
                             alt="Receipt"
                             style="max-height: 600px;">
                    </div>
                <?php elseif ($receipt['receipt_mime_type'] === 'application/pdf'): ?>
                    <!-- PDF Receipt -->
                    <embed src="<?php echo $dataURI; ?>" 
                           type="application/pdf" 
                           width="100%" 
                           height="600px">
                <?php else: ?>
                    <p class="text-muted">Unable to preview this file type.</p>
                <?php endif; ?>

                <div class="mt-3">
                    <p class="mb-1"><strong>File Type:</strong> <?php echo $receipt['receipt_mime_type']; ?></p>
                    <p class="mb-1"><strong>File Size:</strong> <?php echo formatFileSize($payment['receipt_size']); ?></p>
                    <p class="mb-0"><strong>Uploaded:</strong> <?php echo formatDateTime($payment['upload_date']); ?></p>
                </div>

                <?php else: ?>
                    <p class="text-danger">Receipt data not found.</p>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <?php if ($receipt && $receipt['receipt_data']): ?>
                    <a href="<?php echo $dataURI; ?>" 
                       download="<?php echo $payment['receipt_filename']; ?>"
                       class="btn btn-primary">
                        <i class="fas fa-download"></i> Download
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentType = document.getElementById('paymentType');
    const classSection = document.getElementById('classPaymentSection');
    const invoiceSection = document.getElementById('invoicePaymentSection');
    const classSelect = document.getElementById('classSelect');
    const classAmount = document.getElementById('classAmount');
    const invoiceSelect = document.getElementById('invoiceSelect');
    const invoiceAmount = document.getElementById('invoiceAmount');
    const invoiceClassId = document.getElementById('invoiceClassId');
    const submitBtn = document.getElementById('submitBtn');
    const paymentMonth = document.getElementById('paymentMonth');

    // Payment type change
    paymentType.addEventListener('change', function() {
        if (this.value === 'class') {
            classSection.style.display = 'block';
            invoiceSection.style.display = 'none';
            classSelect.required = true;
            classAmount.required = true;
            paymentMonth.required = true;
            invoiceSelect.required = false;
            invoiceAmount.required = false;
            submitBtn.disabled = false;
        } else if (this.value === 'invoice') {
            classSection.style.display = 'none';
            invoiceSection.style.display = 'block';
            classSelect.required = false;
            classAmount.required = false;
            paymentMonth.required = false;
            invoiceSelect.required = true;
            invoiceAmount.required = true;
            submitBtn.disabled = false;
        } else {
            classSection.style.display = 'none';
            invoiceSection.style.display = 'none';
            submitBtn.disabled = true;
        }
    });

    // Auto-fill amount when class selected
    classSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const fee = selectedOption.getAttribute('data-fee');
            classAmount.value = fee;
        } else {
            classAmount.value = '';
        }
    });

    // Auto-fill amount when invoice selected
    invoiceSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const amount = selectedOption.getAttribute('data-amount');
            const classId = selectedOption.getAttribute('data-class');
            invoiceAmount.value = amount;
            invoiceClassId.value = classId || '';
        } else {
            invoiceAmount.value = '';
            invoiceClassId.value = '';
        }
    });
});
</script>