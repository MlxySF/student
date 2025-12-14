<?php
// admin_pages/students.php - Fixed with student_status column

// Handle status filter
$statusFilter = $_GET['status_filter'] ?? '';

// Build query - FIXED: using student_status
$sql = "SELECT * FROM students WHERE 1=1";
$params = [];

if ($statusFilter) {
    $sql .= " AND student_status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Get all unique statuses for filter - FIXED: using student_status
$statusList = $pdo->query("SELECT DISTINCT student_status FROM students ORDER BY student_status")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="card">
    <div class="card-header bg-primary text-white">
        <i class="fas fa-users"></i> All Students
    </div>
    <div class="card-body">
        <!-- Filter Controls -->
        <div class="row mb-4">
            <div class="col-md-4">
                <label class="form-label">Filter by Status</label>
                <select class="form-select" onchange="window.location.href='?page=students&status_filter=' + this.value">
                    <option value="">All Students</option>
                    <?php foreach ($statusList as $status): ?>
                        <option value="<?php echo htmlspecialchars($status); ?>" 
                            <?php echo $statusFilter === $status ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($status); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-8 text-end d-flex align-items-end">
                <div class="btn-group" role="group">
                    <a href="?page=students" class="btn btn-outline-secondary">
                        <i class="fas fa-sync"></i> Reset Filters
                    </a>
                </div>
            </div>
        </div>

        <!-- Students Table -->
        <div class="table-responsive">
            <table class="table table-striped data-table">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Enrolled</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): 
                        // Get enrollment count
                        $enrollStmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND status = 'active'");
                        $enrollStmt->execute([$student['id']]);
                        $enrollCount = $enrollStmt->fetchColumn();
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($student['student_id']); ?></strong></td>
                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td><?php echo htmlspecialchars($student['phone']); ?></td>
                        <td>
                            <span class="badge <?php 
                                echo strpos($student['student_status'], 'State Team') !== false ? 'badge-state-team' : 
                                    (strpos($student['student_status'], 'Backup Team') !== false ? 'badge-backup-team' : 'badge-student'); 
                            ?>">
                                <?php echo htmlspecialchars($student['student_status']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-info">
                                <?php echo $enrollCount; ?> <?php echo $enrollCount === 1 ? 'class' : 'classes'; ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($student['created_at'])); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-primary" onclick="viewStudent(<?php echo $student['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-warning" onclick="editStudent(<?php echo $student['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No students found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function viewStudent(id) {
    // Implement view student details
    alert('View student ' + id);
}

function editStudent(id) {
    // Implement edit student
    alert('Edit student ' + id);
}
</script>
