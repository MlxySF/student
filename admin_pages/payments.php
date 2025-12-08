<?php
$stmt = $pdo->query("
    SELECT p.*, s.student_id, s.full_name, s.email, c.class_code, c.class_name
    FROM payments p
    JOIN students s ON p.student_id = s.id
    JOIN classes c ON p.class_id = c.id
    ORDER BY p.upload_date DESC
");
$all_payments = $stmt->fetchAll();
?>

<h3><i class="fas fa-credit-card"></i> Payment Verification</h3>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Upload Date</th>
                        <th>Student</th>
                        <th>Student ID</th>
                        <th>Class</th>
                        <th>Payment Month</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Receipt</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($all_payments as $p): ?>
                    <tr>
                        <td><?php echo formatDate($p['upload_date']); ?></td>
                        <td><?php echo htmlspecialchars($p['full_name']); ?></td>
                        <td><span class="badge bg-secondary"><?php echo $p['student_id']; ?></span></td>
                        <td><?php echo $p['class_code']; ?> - <?php echo htmlspecialchars($p['class_name']); ?></td>
                        <td><?php echo formatMonth($p['payment_month']); ?></td>
                        <td><strong><?php echo formatCurrency($p['amount']); ?></strong></td>
                        <td>
                            <?php if($p['verification_status'] === 'verified'): ?>
                                <span class="badge bg-success">Verified</span>
                            <?php elseif($p['verification_status'] === 'rejected'): ?>
                                <span class="badge bg-danger">Rejected</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($p['receipt_image']): ?>
                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#receiptModal<?php echo $p['id']; ?>">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#verifyModal<?php echo $p['id']; ?>">
                                <i class="fas fa-check-circle"></i> Verify
                            </button>
                        </td>
                    </tr>

                    <!-- Receipt Modal -->
                    <?php if($p['receipt_image']): ?>
                    <div class="modal fade" id="receiptModal<?php echo $p['id']; ?>">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Payment Receipt</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <?php
                                    $dataUri = base64ToDataURI($p['receipt_image'], $p['receipt_mime']);
                                    if(strpos($p['receipt_mime'], 'image') !== false):
                                    ?>
                                        <img src="<?php echo $dataUri; ?>" class="img-fluid" alt="Receipt">
                                    <?php else: ?>
                                        <p>PDF Receipt</p>
                                        <a href="<?php echo $dataUri; ?>" download="receipt.pdf" class="btn btn-primary">
                                            <i class="fas fa-download"></i> Download PDF
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Verify Modal -->
                    <div class="modal fade" id="verifyModal<?php echo $p['id']; ?>">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <input type="hidden" name="action" value="verify_payment">
                                    <input type="hidden" name="payment_id" value="<?php echo $p['id']; ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Verify Payment</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>Student:</strong> <?php echo htmlspecialchars($p['full_name']); ?> (<?php echo $p['student_id']; ?>)</p>
                                        <p><strong>Class:</strong> <?php echo $p['class_code']; ?> - <?php echo htmlspecialchars($p['class_name']); ?></p>
                                        <p><strong>Amount:</strong> <?php echo formatCurrency($p['amount']); ?></p>
                                        <p><strong>Payment Month:</strong> <?php echo formatMonth($p['payment_month']); ?></p>

                                        <div class="mb-3">
                                            <label>Verification Status</label>
                                            <select name="status" class="form-control" required>
                                                <option value="pending" <?php echo $p['verification_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="verified" <?php echo $p['verification_status'] === 'verified' ? 'selected' : ''; ?>>Verified</option>
                                                <option value="rejected" <?php echo $p['verification_status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label>Admin Notes</label>
                                            <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($p['admin_notes'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save"></i> Save
                                        </button>
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