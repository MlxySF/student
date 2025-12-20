<?php
// admin_pages/registrations.php - View registrations with BULK DELETE

$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

$sql = "SELECT id, registration_number, name_en, name_cn, ic, age, school, student_status,
        phone, email, level, events, schedule, parent_name, parent_ic, form_date,
        signature_base64, pdf_base64, payment_amount, payment_date, payment_receipt_base64,
        payment_status, class_count, student_account_id, account_created, password_generated,
        parent_account_id, registration_type, is_additional_child, created_at
        FROM registrations";

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

$countStmt = $pdo->query("SELECT payment_status, COUNT(*) as count FROM registrations GROUP BY payment_status");
$statusCounts = [];
while ($row = $countStmt->fetch()) {
    $statusCounts[$row['payment_status']] = $row['count'];
}
$totalCount = array_sum($statusCounts);
?>

<div class="card">
    <div class="card-header bg-warning text-white">
        <i class="fas fa-user-plus"></i> Student Registrations
    </div>
    <div class="card-body">
        <!-- Filter Buttons -->
        <div class="mb-4">
            <div class="btn-group" role="group">
                <a href="?page=registrations&status=all" class="btn <?php echo $statusFilter === 'all' ? 'btn-dark' : 'btn-outline-dark'; ?>">
                    <i class="fas fa-list"></i> All <span class="badge bg-light text-dark ms-1"><?php echo $totalCount; ?></span>
                </a>
                <a href="?page=registrations&status=pending" class="btn <?php echo $statusFilter === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                    <i class="fas fa-clock"></i> Pending <span class="badge bg-light text-dark ms-1"><?php echo $statusCounts['pending'] ?? 0; ?></span>
                </a>
                <a href="?page=registrations&status=approved" class="btn <?php echo $statusFilter === 'approved' ? 'btn-success' : 'btn-outline-success'; ?>">
                    <i class="fas fa-check-circle"></i> Approved <span class="badge bg-light text-dark ms-1"><?php echo $statusCounts['approved'] ?? 0; ?></span>
                </a>
                <a href="?page=registrations&status=rejected" class="btn <?php echo $statusFilter === 'rejected' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                    <i class="fas fa-times-circle"></i> Rejected <span class="badge bg-light text-dark ms-1"><?php echo $statusCounts['rejected'] ?? 0; ?></span>
                </a>
            </div>
            <button id="bulkSelectBtn-registrations" class="btn btn-primary ms-2">
                <i class="fas fa-check-square"></i> Select
            </button>
        </div>
        
        <div id="bulkActions-registrations" class="bulk-actions">
            <div class="d-flex align-items-center gap-3">
                <input type="checkbox" id="selectAll-registrations" class="bulk-checkbox form-check-input">
                <label for="selectAll-registrations" class="form-check-label fw-bold mb-0">Select All</label>
                <button id="bulkDeleteBtn-registrations" class="btn btn-bulk-delete" disabled>
                    <i class="fas fa-trash-alt"></i> Delete Selected
                </button>
            </div>
        </div>

        <?php if (empty($registrations)): ?>
        <div class="alert alert-info"><i class="fas fa-info-circle"></i> No registrations found<?php if ($statusFilter !== 'all'): ?> with status: <strong><?php echo ucfirst($statusFilter); ?></strong><?php endif; ?></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped data-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
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
                        <td><input type="checkbox" class="bulk-checkbox bulk-checkbox-registrations form-check-input" value="<?php echo $reg['id']; ?>"></td>
                        <td><strong><?php echo htmlspecialchars($reg['registration_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($reg['name_en']); ?><?php if ($reg['name_cn']): ?><br><small class="text-muted"><?php echo htmlspecialchars($reg['name_cn']); ?></small><?php endif; ?></td>
                        <td><?php echo htmlspecialchars($reg['email']); ?></td>
                        <td><span class="badge <?php echo strpos($reg['student_status'], 'State Team') !== false ? 'badge-state-team' : (strpos($reg['student_status'], 'Backup Team') !== false ? 'badge-backup-team' : 'badge-student'); ?>"><?php echo htmlspecialchars($reg['student_status']); ?></span></td>
                        <td><span class="badge bg-info"><?php echo $reg['class_count']; ?> classes</span></td>
                        <td><strong>RM <?php echo number_format($reg['payment_amount'], 2); ?></strong></td>
                        <td>
                            <?php 
                            $statusValue = $reg['payment_status'] ?? '';
                            $badgeColor = 'secondary';
                            $statusText = 'No Status';
                            if ($statusValue === 'approved') { $badgeColor = 'success'; $statusText = 'Approved'; }
                            elseif ($statusValue === 'pending') { $badgeColor = 'warning'; $statusText = 'Pending'; }
                            elseif ($statusValue === 'rejected') { $badgeColor = 'danger'; $statusText = 'Rejected'; }
                            elseif (!empty($statusValue)) { $statusText = ucfirst($statusValue); }
                            ?>
                            <span class="badge bg-<?php echo $badgeColor; ?>"><?php echo htmlspecialchars($statusText); ?></span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($reg['created_at'])); ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $reg['id']; ?>"><i class="fas fa-eye"></i></button>
                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $reg['id']; ?>"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php /* Modals truncated for space - keeping original delete and view modals */ ?>
