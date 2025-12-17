<?php
// admin_pages/registrations.php - View registrations with proper status colors

// Get all registrations
$stmt = $pdo->query("
    SELECT * FROM registrations 
    ORDER BY created_at DESC
");
$registrations = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header bg-warning text-white">
        <i class="fas fa-user-plus"></i> Student Registrations
    </div>
    <div class="card-body">
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
                                echo strpos($reg['status'], 'State Team') !== false ? 'badge-state-team' : 
                                    (strpos($reg['status'], 'Backup Team') !== false ? 'badge-backup-team' : 'badge-student'); 
                            ?>">
                                <?php echo htmlspecialchars($reg['status']); ?>
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
                                <strong><i class="fas fa-user"></i> Student Information</strong>
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
                                        <th>Status:</th>
                                        <td>
                                            <span class="badge <?php 
                                                echo strpos($reg['status'], 'State Team') !== false ? 'badge-state-team' : 
                                                    (strpos($reg['status'], 'Backup Team') !== false ? 'badge-backup-team' : 'badge-student'); 
                                            ?>">
                                                <?php echo htmlspecialchars($reg['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Phone:</th>
                                        <td><?php echo htmlspecialchars($reg['phone']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email:</th>
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
                                        <img src="<?php echo $reg['signature_base64']; ?>" 
                                             alt="Signature" 
                                             style="max-width: 200px; max-height: 100px;">
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
                                    <div class="border p-2 mt-2 text-center">
                                        <img src="<?php echo $reg['payment_receipt_base64']; ?>" 
                                             alt="Receipt" 
                                             class="img-fluid" 
                                             style="max-height: 300px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Information -->
                    <div class="col-md-12 mb-4">
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
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <?php if ($reg['payment_status'] === 'pending'): ?>
                <form method="POST" action="admin_handler.php" class="d-inline">
                    <input type="hidden" name="action" value="verify_registration">
                    <input type="hidden" name="registration_id" value="<?php echo $reg['id']; ?>">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-circle"></i> Approve Payment
                    </button>
                </form>
                <form method="POST" action="admin_handler.php" class="d-inline">
                    <input type="hidden" name="action" value="reject_registration">
                    <input type="hidden" name="registration_id" value="<?php echo $reg['id']; ?>">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this payment?')">
                        <i class="fas fa-times-circle"></i> Reject Payment
                    </button>
                </form>
                <?php endif; ?>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>