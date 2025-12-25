<?php
// admin_pages/registrations.php - View registrations with proper status colors
// UPDATED 2025-12-21: Changed from base64 display to file serving

// Get filter parameter from URL, default to 'all'
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build SQL query based on filter
$sql = "
    SELECT 
        id,
        registration_number,
        name_en,
        name_cn,
        ic,
        age,
        school,
        student_status,
        phone,
        email,
        level,
        events,
        schedule,
        parent_name,
        parent_ic,
        form_date,
        signature_path,
        pdf_path,
        payment_amount,
        payment_date,
        payment_receipt_path,
        payment_status,
        class_count,
        student_account_id,
        account_created,
        password_generated,
        parent_account_id,
        registration_type,
        is_additional_child,
        created_at
    FROM registrations
";

// Add WHERE clause if filter is not 'all'
if ($statusFilter !== 'all') {
    $sql .= " WHERE payment_status = :status";
}

$sql .= " ORDER BY created_at DESC";

if ($statusFilter !== 'all') {
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['status' => $statusFilter]);
} else {
    $stmt = $pdo->query($sql);
}

$registrations = $stmt->fetchAll();

// Count by status for badge display
$countStmt = $pdo->query("
    SELECT 
        payment_status,
        COUNT(*) as count
    FROM registrations
    GROUP BY payment_status
");
$statusCounts = [];
while ($row = $countStmt->fetch()) {
    $statusCounts[$row['payment_status']] = $row['count'];
}

// Calculate total
$totalCount = array_sum($statusCounts);
?>

<div class="card">
    <div class="card-header bg-warning text-white">
        <i class="fas fa-user-plus"></i> Student Registrations
    </div>
    <div class="card-body">
        <!-- Filter Buttons -->
        <div class="mb-4">
            <div class="btn-group" role="group" aria-label="Registration status filter">
                <a href="?page=registrations&status=all" 
                   class="btn <?php echo $statusFilter === 'all' ? 'btn-dark' : 'btn-outline-dark'; ?>">
                    <i class="fas fa-list"></i> All
                    <span class="badge bg-light text-dark ms-1"><?php echo $totalCount; ?></span>
                </a>
                <a href="?page=registrations&status=pending" 
                   class="btn <?php echo $statusFilter === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                    <i class="fas fa-clock"></i> Pending
                    <span class="badge bg-light text-dark ms-1"><?php echo $statusCounts['pending'] ?? 0; ?></span>
                </a>
                <a href="?page=registrations&status=approved" 
                   class="btn <?php echo $statusFilter === 'approved' ? 'btn-success' : 'btn-outline-success'; ?>">
                    <i class="fas fa-check-circle"></i> Approved
                    <span class="badge bg-light text-dark ms-1"><?php echo $statusCounts['approved'] ?? 0; ?></span>
                </a>
                <a href="?page=registrations&status=rejected" 
                   class="btn <?php echo $statusFilter === 'rejected' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                    <i class="fas fa-times-circle"></i> Rejected
                    <span class="badge bg-light text-dark ms-1"><?php echo $statusCounts['rejected'] ?? 0; ?></span>
                </a>
            </div>
        </div>

        <?php if (empty($registrations)): ?>
        <div class="alert alert-info" role="alert">
            <i class="fas fa-info-circle"></i> 
            No registrations found
            <?php if ($statusFilter !== 'all'): ?>
                with status: <strong><?php echo ucfirst($statusFilter); ?></strong>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped data-table">
                <thead>
                    <tr>
                        <th>Reg #</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Student Status</th>
                        <th>Classes</th>
                        <th>Amount</th>
                        <th>Payment Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $reg): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($reg['registration_number']); ?></strong></td>
                        <td>
                            <?php echo htmlspecialchars($reg['name_en']); ?>
                            <?php if ($reg['name_cn']): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($reg['name_cn']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($reg['email']); ?></td>
                        <td>
                            <span class="badge <?php 
                                echo strpos($reg['student_status'], 'State Team') !== false ? 'badge-state-team' : 
                                    (strpos($reg['student_status'], 'Backup Team') !== false ? 'badge-backup-team' : 'badge-student'); 
                            ?>">
                                <?php echo htmlspecialchars($reg['student_status']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-info">
                                <?php echo $reg['class_count']; ?> classes
                            </span>
                        </td>
                        <td><strong>RM <?php echo number_format($reg['payment_amount'], 2); ?></strong></td>
                        <td>
                            <?php 
                            $statusValue = $reg['payment_status'] ?? '';
                            $badgeColor = 'secondary';
                            $statusText = 'No Status';
                            
                            // Map status values to display
                            if ($statusValue === 'approved') {
                                $badgeColor = 'success';
                                $statusText = 'Approved';
                            } elseif ($statusValue === 'pending') {
                                $badgeColor = 'warning';
                                $statusText = 'Pending';
                            } elseif ($statusValue === 'rejected') {
                                $badgeColor = 'danger';
                                $statusText = 'Rejected';
                            } elseif (!empty($statusValue)) {
                                // Show any other value as-is
                                $statusText = ucfirst($statusValue);
                            }
                            ?>
                            <span class="badge bg-<?php echo $badgeColor; ?>">
                                <?php echo htmlspecialchars($statusText); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($reg['created_at'])); ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $reg['id']; ?>">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $reg['id']; ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modals -->
<?php foreach ($registrations as $reg): ?>
<div class="modal fade" id="deleteModal<?php echo $reg['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> Delete Registration
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Are you sure you want to delete this registration?</strong></p>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i> This will also delete the associated student account if it exists.
                </div>
                <table class="table table-sm table-bordered">
                    <tr>
                        <th width="40%">Registration #:</th>
                        <td><?php echo htmlspecialchars($reg['registration_number']); ?></td>
                    </tr>
                    <tr>
                        <th>Student Name:</th>
                        <td><?php echo htmlspecialchars($reg['name_en']); ?></td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td><?php echo htmlspecialchars($reg['email']); ?></td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            <span class="badge bg-<?php echo $badgeColor; ?>">
                                <?php 
                                $statusValue = $reg['payment_status'] ?? '';
                                if ($statusValue === 'approved') echo 'Approved';
                                elseif ($statusValue === 'pending') echo 'Pending';
                                elseif ($statusValue === 'rejected') echo 'Rejected';
                                else echo 'No Status';
                                ?>
                            </span>
                        </td>
                    </tr>
                </table>
                <p class="text-danger mb-0"><strong>⚠️ This action cannot be undone!</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <form method="POST" action="admin_handler.php" class="d-inline">
                    <input type="hidden" name="action" value="delete_registration">
                    <input type="hidden" name="registration_id" value="<?php echo $reg['id']; ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Yes, Delete Registration
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Rejection Reason Modal -->
<?php foreach ($registrations as $reg): ?>
<div class="modal fade" id="rejectModal<?php echo $reg['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-times-circle"></i> Reject Registration
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="admin_handler.php">
                <input type="hidden" name="action" value="reject_registration">
                <input type="hidden" name="registration_id" value="<?php echo $reg['id']; ?>">
                
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle"></i> You are about to reject the registration for:
                    </div>
                    
                    <table class="table table-sm table-bordered mb-3">
                        <tr>
                            <th width="40%">Registration #:</th>
                            <td><?php echo htmlspecialchars($reg['registration_number']); ?></td>
                        </tr>
                        <tr>
                            <th>Student Name:</th>
                            <td><?php echo htmlspecialchars($reg['name_en']); ?></td>
                        </tr>
                        <tr>
                            <th>Parent Email:</th>
                            <td><?php echo htmlspecialchars($reg['email']); ?></td>
                        </tr>
                    </table>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <strong>Reason for Rejection</strong> <span class="text-danger">*</span>
                        </label>
                        <textarea 
                            name="reject_reason" 
                            class="form-control" 
                            rows="4" 
                            required
                            placeholder="Please explain why this registration was rejected. This will be sent to the parent via email.&#10;&#10;Example reasons:&#10;- Payment receipt is unclear/invalid&#10;- Incorrect payment amount&#10;- Required documents missing&#10;- Duplicate registration detected"></textarea>
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> This reason will be included in the rejection email sent to the parent.
                        </small>
                    </div>
                    
                    <div class="alert alert-danger mb-0">
                        <strong>⚠️ What will happen:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Registration status will be set to "Rejected"</li>
                            <li>Linked invoice will be cancelled</li>
                            <li>Payment record will be rejected</li>
                            <li>Parent will receive an email notification with your reason</li>
                        </ul>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times-circle"></i> Reject & Send Email
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Re-Approve Modal for Rejected Registrations -->
<?php foreach ($registrations as $reg): ?>
<?php if ($reg['payment_status'] === 'rejected'): ?>
<div class="modal fade" id="reapproveModal<?php echo $reg['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-redo"></i> Re-Approve Registration
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> You are about to re-approve this previously rejected registration:
                </div>
                
                <table class="table table-sm table-bordered mb-3">
                    <tr>
                        <th width="40%">Registration #:</th>
                        <td><?php echo htmlspecialchars($reg['registration_number']); ?></td>
                    </tr>
                    <tr>
                        <th>Student Name:</th>
                        <td><?php echo htmlspecialchars($reg['name_en']); ?></td>
                    </tr>
                    <tr>
                        <th>Parent Email:</th>
                        <td><?php echo htmlspecialchars($reg['email']); ?></td>
                    </tr>
                    <tr>
                        <th>Amount:</th>
                        <td><strong>RM <?php echo number_format($reg['payment_amount'], 2); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Current Status:</th>
                        <td><span class="badge bg-danger">Rejected</span></td>
                    </tr>
                </table>
                
                <div class="alert alert-success mb-0">
                    <strong>✅ What will happen:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Registration status → "Approved"</li>
                        <li>Student account will be activated</li>
                        <li>Invoice will be marked as "Paid"</li>
                        <li>Payment will be verified</li>
                        <li>Parent will receive approval email with login credentials</li>
                    </ul>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <form method="POST" action="admin_handler.php" class="d-inline">
                    <input type="hidden" name="action" value="reapprove_registration">
                    <input type="hidden" name="registration_id" value="<?php echo $reg['id']; ?>">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-circle"></i> Yes, Re-Approve Registration
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endforeach; ?>

<!-- View Modals (Outside the table) -->
<?php foreach ($registrations as $reg): ?>
<div class="modal fade" id="viewModal<?php echo $reg['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-user-circle"></i> 
                    Registration Details - <?php echo htmlspecialchars($reg['registration_number']); ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Student Information -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-light">
                                <strong><i class="fas fa-user"></i> Information</strong>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <th width="40%">Name (English):</th>
                                        <td><?php echo htmlspecialchars($reg['name_en']); ?></td>
                                    </tr>
                                    <?php if ($reg['name_cn']): ?>
                                    <tr>
                                        <th>Name (Chinese):</th>
                                        <td><?php echo htmlspecialchars($reg['name_cn']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <th>IC Number:</th>
                                        <td><?php echo htmlspecialchars($reg['ic']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Age:</th>
                                        <td><?php echo $reg['age']; ?> years old</td>
                                    </tr>
                                    <tr>
                                        <th>School:</th>
                                        <td><?php echo htmlspecialchars($reg['school']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Student Status:</th>
                                        <td>
                                            <span class="badge <?php 
                                                echo strpos($reg['student_status'], 'State Team') !== false ? 'badge-state-team' : 
                                                    (strpos($reg['student_status'], 'Backup Team') !== false ? 'badge-backup-team' : 'badge-student'); 
                                            ?>">
                                                <?php echo htmlspecialchars($reg['student_status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Phone:</th>
                                        <td><?php echo htmlspecialchars($reg['phone']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Parent's Email:</th>
                                        <td><?php echo htmlspecialchars($reg['email']); ?></td>
                                    </tr>
                                    <?php if ($reg['level']): ?>
                                    <tr>
                                        <th>Level:</th>
                                        <td><?php echo htmlspecialchars($reg['level']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Parent Information -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-light">
                                <strong><i class="fas fa-users"></i> Parent/Guardian Information</strong>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <th width="40%">Parent Name:</th>
                                        <td><?php echo htmlspecialchars($reg['parent_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Parent IC:</th>
                                        <td><?php echo htmlspecialchars($reg['parent_ic']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Form Date:</th>
                                        <td><?php echo date('F j, Y', strtotime($reg['form_date'])); ?></td>
                                    </tr>
                                </table>
                                
                                <div class="mt-3">
                                    <strong>Parent Signature:</strong>
                                    <div class="border p-2 mt-2 bg-light text-center">
                                        <?php if (!empty($reg['signature_path'])): ?>
                                            <img src="serve_file.php?path=<?php echo urlencode($reg['signature_path']); ?>" 
                                                 alt="Signature" 
                                                 style="max-width: 200px; max-height: 100px;">
                                        <?php else: ?>
                                            <p class="text-muted mb-0">No signature available</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Class Information -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-light">
                                <strong><i class="fas fa-chalkboard"></i> Class Information</strong>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <th width="40%">Events:</th>
                                        <td><?php echo htmlspecialchars($reg['events']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Schedule:</th>
                                        <td><?php echo nl2br(htmlspecialchars($reg['schedule'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Number of Classes:</th>
                                        <td><span class="badge bg-info"><?php echo $reg['class_count']; ?> classes</span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-light">
                                <strong><i class="fas fa-credit-card"></i> Payment Information</strong>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <th width="40%">Amount:</th>
                                        <td><strong class="text-primary">RM <?php echo number_format($reg['payment_amount'], 2); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <th>Payment Date:</th>
                                        <td><?php echo date('F j, Y', strtotime($reg['payment_date'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td>
                                            <?php 
                                            $statusValue = $reg['payment_status'] ?? '';
                                            $badgeColor = 'secondary';
                                            $statusText = 'No Status';
                                            
                                            if ($statusValue === 'approved') {
                                                $badgeColor = 'success';
                                                $statusText = 'Approved';
                                            } elseif ($statusValue === 'pending') {
                                                $badgeColor = 'warning';
                                                $statusText = 'Pending';
                                            } elseif ($statusValue === 'rejected') {
                                                $badgeColor = 'danger';
                                                $statusText = 'Rejected';
                                            } elseif (!empty($statusValue)) {
                                                $statusText = ucfirst($statusValue);
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $badgeColor; ?>">
                                                <?php echo htmlspecialchars($statusText); ?>
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                                
                                <div class="mt-3">
    <strong>Payment Receipt:</strong>
    <div class="border p-2 mt-2" style="min-height: 400px; background: #f8f9fa;">
        <?php if (!empty($reg['payment_receipt_path'])): ?>
            <?php 
            $fileExt = strtolower(pathinfo($reg['payment_receipt_path'], PATHINFO_EXTENSION));
            $isPdf = ($fileExt === 'pdf');
            ?>
            
            <?php if ($isPdf): ?>
                <!-- PDF - Live Preview -->
                <div class="text-center mb-2">
                    <span class="badge bg-danger"><i class="fas fa-file-pdf"></i> PDF Receipt</span>
                    <a href="../serve_file.php?path=<?php echo urlencode($reg['payment_receipt_path']); ?>" 
                       class="btn btn-sm btn-outline-danger ms-2" target="_blank">
                        <i class="fas fa-external-link-alt"></i> Open Full Screen
                    </a>
                </div>
                <iframe src="../serve_file.php?path=<?php echo urlencode($reg['payment_receipt_path']); ?>" 
                    style="width: 100%; height: 500px; border: 1px solid #dee2e6; border-radius: 4px;">
                </iframe>
            <?php else: ?>
                <!-- Image -->
                <div class="text-center">
                    <img src="../serve_file.php?path=<?php echo urlencode($reg['payment_receipt_path']); ?>" 
                         alt="Payment Receipt" class="img-fluid" 
                         style="max-height: 500px; cursor: pointer;"
                         onclick="window.open('../serve_file.php?path=<?php echo urlencode($reg['payment_receipt_path']); ?>', '_blank')">
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="d-flex align-items-center justify-content-center" style="height: 400px;">
                <p class="text-muted mb-0">No receipt available</p>
            </div>
        <?php endif; ?>
    </div>
</div>

                            </div>
                        </div>
                    </div>

                    <!-- Account Information -->
                    <!-- <div class="col-md-12 mb-4">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <strong><i class="fas fa-key"></i> Student Account Credentials</strong>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>Student ID:</strong>
                                        <p class="mb-0"><?php echo htmlspecialchars($reg['registration_number']); ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Email:</strong>
                                        <p class="mb-0"><?php echo htmlspecialchars($reg['email']); ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Password:</strong>
                                        <p class="mb-0">
                                            <code class="text-danger fs-5"><?php echo htmlspecialchars($reg['password_generated']); ?></code>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> -->
                </div>
            </div>
            <div class="modal-footer">
                <?php if (!empty($reg['pdf_path'])): ?>
                <a href="serve_file.php?path=<?php echo urlencode($reg['pdf_path']); ?>" 
                   class="btn btn-info" 
                   target="_blank"
                   title="View/Download signed registration agreement PDF">
                    <i class="fas fa-file-pdf"></i> View Agreement PDF
                </a>
                <?php endif; ?>
                
                <?php if ($reg['payment_status'] === 'pending'): ?>
                <form method="POST" action="admin_handler.php" class="d-inline">
                    <input type="hidden" name="action" value="verify_registration">
                    <input type="hidden" name="registration_id" value="<?php echo $reg['id']; ?>">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-circle"></i> Approve Payment
                    </button>
                </form>
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $reg['id']; ?>">
                    <i class="fas fa-times-circle"></i> Reject Payment
                </button>
                <?php endif; ?>
                
                <?php if ($reg['payment_status'] === 'rejected'): ?>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#reapproveModal<?php echo $reg['id']; ?>">
                    <i class="fas fa-redo"></i> Re-Approve Registration
                </button>
                <?php endif; ?>
                
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
