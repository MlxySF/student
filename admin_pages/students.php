<?php
// admin_pages/students.php - View and edit students with BULK DELETE

// Handle status filter
$statusFilter = $_GET['status_filter'] ?? '';
$paymentStatusFilter = $_GET['payment_status'] ?? 'approved';

// Build query
$sql = "SELECT r.*, 
        pa.email as parent_email,
        pa.full_name as parent_name,
        (SELECT COUNT(*) FROM enrollments WHERE student_id = r.student_account_id AND status = 'active') as enrollment_count
        FROM registrations r
        LEFT JOIN parent_accounts pa ON r.parent_account_id = pa.id
        WHERE 1=1";
$params = [];

if ($paymentStatusFilter) {
    $sql .= " AND r.payment_status = ?";
    $params[] = $paymentStatusFilter;
}

if ($statusFilter) {
    $sql .= " AND r.student_status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Get all unique statuses for filter
$statusList = $pdo->query("SELECT DISTINCT student_status FROM registrations WHERE payment_status = 'approved' ORDER BY student_status")->fetchAll(PDO::FETCH_COLUMN);

// Get all classes for enrollment dropdown
$allClasses = $pdo->query("SELECT * FROM classes ORDER BY class_name")->fetchAll();
?>

<div class="card">
    <div class="card-header bg-primary text-white">
        <i class="fas fa-users"></i> All Students
        <span class="badge bg-light text-primary ms-2"><?php echo count($students); ?> students</span>
    </div>
    <div class="card-body">
        <!-- Filter Controls -->
        <div class="row mb-4">
            <div class="col-md-3">
                <label class="form-label">Payment Status</label>
                <select class="form-select" onchange="updateFilters('payment_status', this.value)">
                    <option value="approved" <?php echo $paymentStatusFilter === 'approved' ? 'selected' : ''; ?>>Approved Only</option>
                    <option value="" <?php echo $paymentStatusFilter === '' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="pending" <?php echo $paymentStatusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="rejected" <?php echo $paymentStatusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Student Status</label>
                <select class="form-select" onchange="updateFilters('status_filter', this.value)">
                    <option value="">All Student Types</option>
                    <?php foreach ($statusList as $status): ?>
                        <option value="<?php echo htmlspecialchars($status); ?>" 
                            <?php echo $statusFilter === $status ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($status); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 text-end d-flex align-items-end">
                <div class="btn-group" role="group">
                    <a href="?page=students" class="btn btn-outline-secondary">
                        <i class="fas fa-sync"></i> Reset Filters
                    </a>
                    <!-- ✨ NEW: Bulk Select Button -->
                    <button id="bulkSelectBtn-students" class="btn btn-primary">
                        <i class="fas fa-check-square"></i> Select
                    </button>
                </div>
            </div>
        </div>
        
        <!-- ✨ NEW: Bulk Actions Bar -->
        <div id="bulkActions-students" class="bulk-actions">
            <div class="d-flex align-items-center gap-3">
                <input type="checkbox" id="selectAll-students" class="bulk-checkbox form-check-input">
                <label for="selectAll-students" class="form-check-label fw-bold mb-0">Select All</label>
                <button id="bulkDeleteBtn-students" class="btn btn-bulk-delete" disabled>
                    <i class="fas fa-trash-alt"></i> Delete Selected
                </button>
            </div>
        </div>

        <!-- Students Table -->
        <div class="table-responsive">
            <table class="table table-striped table-hover data-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
                        <th>Student ID</th>
                        <th>Name (EN / CN)</th>
                        <th>Age</th>
                        <th>School</th>
                        <th>Parent</th>
                        <th>Status</th>
                        <th>Enrolled</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="bulk-checkbox bulk-checkbox-students form-check-input" value="<?php echo $student['id']; ?>">
                        </td>
                        <td><strong><?php echo htmlspecialchars($student['registration_number']); ?></strong></td>
                        <td>
                            <div><strong><?php echo htmlspecialchars($student['name_en']); ?></strong></div>
                            <?php if (!empty($student['name_cn'])): ?>
                            <small class="text-muted"><?php echo htmlspecialchars($student['name_cn']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($student['age']); ?></td>
                        <td><small><?php echo htmlspecialchars($student['school']); ?></small></td>
                        <td>
                            <small>
                                <?php echo htmlspecialchars($student['parent_name'] ?? 'N/A'); ?><br>
                                <span class="text-muted"><?php echo htmlspecialchars($student['parent_email'] ?? $student['email']); ?></span>
                            </small>
                        </td>
                        <td>
                            <span class="badge <?php 
                                echo strpos($student['student_status'], 'State Team') !== false ? 'badge-state-team' : 
                                    (strpos($student['student_status'], 'Backup Team') !== false ? 'badge-backup-team' : 'badge-student'); 
                            ?>">
                                <?php echo htmlspecialchars($student['student_status']); ?>
                            </span>
                            <?php if ($student['payment_status'] !== 'approved'): ?>
                            <br><span class="badge bg-warning mt-1"><?php echo ucfirst($student['payment_status']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-info">
                                <?php echo $student['enrollment_count']; ?> <?php echo $student['enrollment_count'] === 1 ? 'class' : 'classes'; ?>
                            </span>
                        </td>
                        <td><small><?php echo date('M j, Y', strtotime($student['created_at'])); ?></small></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <?php if ($student['student_account_id']): ?>
                                <button class="btn btn-primary" onclick="viewStudent(<?php echo $student['id']; ?>, <?php echo $student['student_account_id']; ?>)" title="View & Manage">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-success" onclick="enrollStudent(<?php echo $student['id']; ?>, <?php echo $student['student_account_id']; ?>, '<?php echo htmlspecialchars(addslashes($student['name_en'])); ?>')" title="Enroll">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <?php else: ?>
                                <span class="badge bg-secondary">No Account</span>
                                <?php endif; ?>
                                <button class="btn btn-warning" onclick="editStudent(<?php echo $student['id']; ?>)" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="fas fa-users fa-3x mb-3"></i><br>
                            No students found with selected filters
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const studentsData = <?php echo json_encode($students); ?>;

function updateFilters(paramName, value) {
    const urlParams = new URLSearchParams(window.location.search);
    if (value) {
        urlParams.set(paramName, value);
    } else {
        urlParams.delete(paramName);
    }
    urlParams.set('page', 'students');
    window.location.href = '?' + urlParams.toString();
}

function viewStudent(registrationId, studentAccountId) {
    alert('View student feature - ID: ' + studentAccountId);
}

function enrollStudent(registrationId, studentAccountId, studentName) {
    alert('Enroll student: ' + studentName);
}

function editStudent(registrationId) {
    alert('Edit student - Reg ID: ' + registrationId);
}
</script>