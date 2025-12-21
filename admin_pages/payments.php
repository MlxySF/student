<?php
// Get all payments with student, class, and invoice information
// UPDATED: Fetch receipt_filename instead of receipt_data
$stmt = $pdo->query("
    SELECT p.*, 
           s.student_id, s.full_name, s.email, 
           c.class_code, c.class_name,
           i.invoice_number, i.amount as invoice_amount, i.status as invoice_status
    FROM payments p
    JOIN students s ON p.student_id = s.id
    LEFT JOIN classes c ON p.class_id = c.id
    LEFT JOIN invoices i ON p.invoice_id = i.id
    ORDER BY p.upload_date DESC
");
$all_payments = $stmt->fetchAll();
?>

<style>
@media (max-width: 768px) {
    .hide-mobile { display: none !important; }
    .table td, .table th { padding: 8px 5px; font-size: 12px; }
}
.receipt-image { 
    max-width: 100%; 
    height: auto; 
    border-radius: 8px; 
    border: 2px solid #e2e8f0; 
}
.receipt-pdf { 
    width: 100%; 
    height: 500px; 
    border: 2px solid #e2e8f0; 
    border-radius: 8px; 
}
</style>

<div class="card">
    <div class="card-header"><i class="fas fa-credit-card"></i> All Payments (<?php echo count($all_payments); ?>)</div>
    <div class="card-body">
        <?php if (count($all_payments) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Upload Date</th>
                            <th>Student</th>
                            <th class="hide-mobile">Invoice #</th>
                            <th class="hide-mobile">Class</th>
                            <th class="hide-mobile">Month</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_payments as $payment): ?>
                            <tr>
                                <td><?php echo formatDate($payment['upload_date']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($payment['full_name']); ?></strong>
                                    <div class="d-md-none text-muted small">
                                        <?php echo $payment['invoice_number'] ? htmlspecialchars($payment['invoice_number']) : 'No Invoice'; ?>
                                    </div>
                                </td>
                                <td class="hide-mobile">
                                    <?php if ($payment['invoice_number']): ?>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($payment['invoice_number']); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">No Invoice</span>
                                    <?php endif; ?>
                                </td>
                                <td class="hide-mobile">
                                    <?php if ($payment['class_code']): ?>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($payment['class_code']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="hide-mobile"><?php echo $payment['payment_month'] ?? '-'; ?></td>
                                <td><strong><?php echo formatCurrency($payment['amount']); ?></strong></td>
                                <td>
                                    <?php if ($payment['verification_status'] === 'pending'): ?>
                                        <span class="badge bg-warning">Pending</span>
                                    <?php elseif ($payment['verification_status'] === 'verified'): ?>
                                        <span class="badge bg-success">Verified</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#paymentModal<?php echo $payment['id']; ?>">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info"><i class="fas fa-info-circle"></i> No payments found.</div>
        <?php endif; ?>
    </div>
</div>

<?php foreach ($all_payments as $payment): ?>
<div class="modal fade" id="paymentModal<?php echo $payment['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title"><i class="fas fa-credit-card"></i> Payment Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <h6 class="mb-3">Payment Information</h6>
            <table class="table table-bordered">
                <tr><th width="30%">Student</th><td><?php echo htmlspecialchars($payment['full_name']); ?></td></tr>
                <tr><th>Student ID</th><td><span class="badge bg-secondary"><?php echo htmlspecialchars($payment['student_id']); ?></span></td></tr>
                <?php if ($payment['invoice_number']): ?>
                <tr>
                    <th>Invoice Number</th>
                    <td>
                        <span class="badge bg-primary"><?php echo htmlspecialchars($payment['invoice_number']); ?></span>
                        <span class="badge bg-<?php echo $payment['invoice_status'] === 'paid' ? 'success' : 'warning'; ?>">
                            <?php echo ucfirst($payment['invoice_status']); ?>
                        </span>
                    </td>
                </tr>
                <tr><th>Invoice Amount</th><td><strong><?php echo formatCurrency($payment['invoice_amount']); ?></strong></td></tr>
                <?php endif; ?>
                <?php if ($payment['class_code']): ?>
                <tr><th>Class</th><td><span class="badge bg-info"><?php echo htmlspecialchars($payment['class_code']); ?></span> <?php echo htmlspecialchars($payment['class_name']); ?></td></tr>
                <?php endif; ?>
                <?php if ($payment['payment_month']): ?>
                <tr><th>Payment Month</th><td><?php echo $payment['payment_month']; ?></td></tr>
                <?php endif; ?>
                <tr><th>Payment Amount</th><td><strong><?php echo formatCurrency($payment['amount']); ?></strong></td></tr>
                <tr><th>Upload Date</th><td><?php echo formatDateTime($payment['upload_date']); ?></td></tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <?php if ($payment['verification_status'] === 'pending'): ?>
                            <span class="badge bg-warning">Pending</span>
                        <?php elseif ($payment['verification_status'] === 'verified'): ?>
                            <span class="badge bg-success">Verified</span>
                            <?php if ($payment['verified_date']): ?>
                                <small class="text-muted"> on <?php echo formatDate($payment['verified_date']); ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-danger">Rejected</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <h6 class="mt-4 mb-3">Receipt Image</h6>
            <?php if (!empty($payment['receipt_filename'])): ?>
                <?php 
                // UPDATED: Use serve_file.php to display receipt from local storage
                $fileUrl = '../serve_file.php?type=payment_receipt&file=' . urlencode($payment['receipt_filename']);
                ?>
                <?php if ($payment['receipt_mime_type'] === 'application/pdf'): ?>
                    <div class="alert alert-info"><i class="fas fa-file-pdf"></i> PDF Receipt</div>
                    <iframe src="<?php echo htmlspecialchars($fileUrl); ?>" class="receipt-pdf" frameborder="0"></iframe>
                    <div class="mt-2">
                        <a href="<?php echo htmlspecialchars($fileUrl); ?>" target="_blank" class="btn btn-sm btn-primary">
                            <i class="fas fa-external-link-alt"></i> Open in New Tab
                        </a>
                        <a href="<?php echo htmlspecialchars($fileUrl . '&download'); ?>" class="btn btn-sm btn-success">
                            <i class="fas fa-download"></i> Download PDF
                        </a>
                    </div>
                <?php else: ?>
                    <img src="<?php echo htmlspecialchars($fileUrl); ?>" alt="Receipt" class="receipt-image">
                    <div class="mt-2">
                        <a href="<?php echo htmlspecialchars($fileUrl); ?>" target="_blank" class="btn btn-sm btn-primary">
                            <i class="fas fa-external-link-alt"></i> View Full Size
                        </a>
                        <a href="<?php echo htmlspecialchars($fileUrl . '&download'); ?>" class="btn btn-sm btn-success">
                            <i class="fas fa-download"></i> Download Image
                        </a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> No receipt file available.</div>
            <?php endif; ?>

            <?php if (!empty($payment['admin_notes'])): ?>
                <h6 class="mt-4 mb-3">Admin Notes</h6>
                <div class="alert alert-secondary"><?php echo nl2br(htmlspecialchars($payment['admin_notes'])); ?></div>
            <?php endif; ?>

            <?php if ($payment['verification_status'] === 'pending'): ?>
                <hr class="my-4">
                <h6 class="mb-3">Verify Payment</h6>
                <form method="POST" action="admin_handler.php">
                    <input type="hidden" name="action" value="verify_payment">
                    <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                    <input type="hidden" name="invoice_id" value="<?php echo $payment['invoice_id']; ?>">

                    <div class="mb-3">
                        <label class="form-label">Verification Status *</label>
                        <select name="verification_status" class="form-select" required>
                            <option value="">Select Status</option>
                            <option value="verified">✓ Verified - Approve Payment<?php echo $payment['invoice_id'] ? ' & Mark Invoice as Paid' : ''; ?></option>
                            <option value="rejected">✗ Rejected - Decline Payment</option>
                        </select>
                    </div>

                    <?php if ($payment['invoice_id']): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <strong>Note:</strong> Approving this payment will automatically mark invoice <strong><?php echo htmlspecialchars($payment['invoice_number']); ?></strong> as PAID.
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Admin Notes (Optional)</label>
                        <textarea name="admin_notes" class="form-control" rows="3" placeholder="Add any notes about this verification..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Verify Payment</button>
                </form>
            <?php endif; ?>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
    </div></div>
</div>
<?php endforeach; ?>