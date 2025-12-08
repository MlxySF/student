<?php
// Get all payments with student and class information
$stmt = $pdo->query("
    SELECT p.*, s.student_id, s.full_name, s.email, c.class_code, c.class_name
    FROM payments p
    JOIN students s ON p.student_id = s.id
    JOIN classes c ON p.class_id = c.id
    ORDER BY p.upload_date DESC
");
$all_payments = $stmt->fetchAll();
?>

<style>
    /* Responsive table */
    @media (max-width: 768px) {
        .hide-mobile {
            display: none !important;
        }

        .table td, .table th {
            padding: 8px 5px;
            font-size: 12px;
        }
    }

    /* Receipt image styling */
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

<!-- Payments Table -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-credit-card"></i> All Payments (<?php echo count($all_payments); ?>)
    </div>
    <div class="card-body">
        <?php if (count($all_payments) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Upload Date</th>
                            <th>Student</th>
                            <th class="hide-mobile">Student ID</th>
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
                                        <?php echo htmlspecialchars($payment['class_code']); ?> - <?php echo $payment['payment_month']; ?>
                                    </div>
                                </td>
                                <td class="hide-mobile">
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($payment['student_id']); ?></span>
                                </td>
                                <td class="hide-mobile">
                                    <span class="badge bg-info"><?php echo htmlspecialchars($payment['class_code']); ?></span>
                                </td>
                                <td class="hide-mobile"><?php echo $payment['payment_month']; ?></td>
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
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No payments found.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Payment Detail Modals -->
<?php foreach ($all_payments as $payment): ?>
<div class="modal fade" id="paymentModal<?php echo $payment['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-credit-card"></i> Payment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Payment Information -->
                <h6 class="mb-3">Payment Information</h6>
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Student</th>
                        <td><?php echo htmlspecialchars($payment['full_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Student ID</th>
                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($payment['student_id']); ?></span></td>
                    </tr>
                    <tr>
                        <th>Class</th>
                        <td><span class="badge bg-info"><?php echo htmlspecialchars($payment['class_code']); ?></span> <?php echo htmlspecialchars($payment['class_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Payment Month</th>
                        <td><?php echo $payment['payment_month']; ?></td>
                    </tr>
                    <tr>
                        <th>Amount</th>
                        <td><strong><?php echo formatCurrency($payment['amount']); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Upload Date</th>
                        <td><?php echo formatDate($payment['upload_date']); ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <?php if ($payment['verification_status'] === 'pending'): ?>
                                <span class="badge bg-warning">Pending</span>
                            <?php elseif ($payment['verification_status'] === 'verified'): ?>
                                <span class="badge bg-success">Verified</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Rejected</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <!-- Receipt Image -->
                <h6 class="mt-4 mb-3">Receipt Image</h6>
                <?php if (!empty($payment['receipt_data']) && !empty($payment['receipt_mime_type'])): ?>
                    <?php if ($payment['receipt_mime_type'] === 'application/pdf'): ?>
                        <!-- PDF Receipt -->
                        <div class="alert alert-info">
                            <i class="fas fa-file-pdf"></i> PDF Receipt
                        </div>
                        <embed src="data:<?php echo $payment['receipt_mime_type']; ?>;base64,<?php echo $payment['receipt_data']; ?>" 
                               type="<?php echo $payment['receipt_mime_type']; ?>" 
                               class="receipt-pdf">
                    <?php else: ?>
                        <!-- Image Receipt -->
                        <img src="data:<?php echo $payment['receipt_mime_type']; ?>;base64,<?php echo $payment['receipt_data']; ?>" 
                             alt="Receipt" 
                             class="receipt-image">
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> No receipt image available.
                        <p class="mb-0 mt-2">The student may not have uploaded a receipt yet.</p>
                    </div>
                <?php endif; ?>

                <!-- Admin Notes -->
                <?php if (!empty($payment['admin_notes'])): ?>
                    <h6 class="mt-4 mb-3">Admin Notes</h6>
                    <div class="alert alert-secondary">
                        <?php echo nl2br(htmlspecialchars($payment['admin_notes'])); ?>
                    </div>
                <?php endif; ?>

                <!-- Verification Form -->
                <?php if ($payment['verification_status'] === 'pending'): ?>
                    <hr class="my-4">
                    <h6 class="mb-3">Verify Payment</h6>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="verify_payment">
                        <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">

                        <div class="mb-3">
                            <label class="form-label">Verification Status *</label>
                            <select name="status" class="form-select" required>
                                <option value="">Select Status</option>
                                <option value="verified">Verified - Approve Payment</option>
                                <option value="rejected">Rejected - Decline Payment</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Admin Notes (Optional)</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Add any notes about this verification..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check"></i> Verify Payment
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>