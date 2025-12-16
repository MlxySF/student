<?php
// admin_pages/students.php - View and edit students (form handlers removed)

// Handle status filter
$statusFilter = $_GET['status_filter'] ?? '';

// Build query
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

// Get all unique statuses for filter
$statusList = $pdo->query("SELECT DISTINCT student_status FROM students ORDER BY student_status")->fetchAll(PDO::FETCH_COLUMN);

// Get all classes for enrollment dropdown
$allClasses = $pdo->query("SELECT * FROM classes ORDER BY class_name")->fetchAll();
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
                                <button class="btn btn-primary" onclick="viewStudent(<?php echo $student['id']; ?>)" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-success" onclick="enrollStudent(<?php echo $student['id']; ?>)" title="Enroll in Class">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <button class="btn btn-warning" onclick="editStudent(<?php echo $student['id']; ?>)" title="Edit Student">
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

<!-- View Student Modal -->
<div class="modal fade" id="viewStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-eye"></i> Student Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewStudentContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enroll Student Modal -->
<div class="modal fade" id="enrollStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="admin_handler.php" id="enrollStudentForm">
                <input type="hidden" name="action" value="enroll_student">
                <input type="hidden" name="student_id" id="enroll_student_id">
                
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Enroll Student in Class</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong><i class="fas fa-user"></i> <span id="enroll_student_name"></span></strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select Class *</label>
                        <select class="form-select" name="class_id" id="enroll_class_id" required>
                            <option value="">Choose a class...</option>
                            <?php foreach ($allClasses as $class): ?>
                                <option value="<?php echo $class['id']; ?>">
                                    <?php echo htmlspecialchars($class['class_name']); ?> 
                                    (<?php echo htmlspecialchars($class['class_code']); ?>) - 
                                    RM <?php echo number_format($class['monthly_fee'], 2); ?>/month
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="alert alert-warning">
                        <small><i class="fas fa-exclamation-triangle"></i> The student will be enrolled immediately. Make sure to create corresponding invoices for monthly payments.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Enroll Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="admin_handler.php" id="editStudentForm">
                <input type="hidden" name="action" value="edit_student">
                <input type="hidden" name="student_id" id="edit_student_id">
                
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="full_name" id="edit_full_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="edit_email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" id="edit_phone" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="student_status" id="edit_student_status" required>
                            <option value="Normal Student">Normal Student</option>
                            <option value="State Team 州队">State Team 州队</option>
                            <option value="Backup Team 后备队">Backup Team 后备队</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const studentsData = <?php echo json_encode($students); ?>;

function viewStudent(id) {
    const student = studentsData.find(s => s.id == id);
    if (!student) return;

    // Show modal first
    const modal = new bootstrap.Modal(document.getElementById('viewStudentModal'));
    modal.show();

    // Fetch enrollments for this student
    fetch(`admin_handler.php?action=get_student_details&student_id=${id}`)
        .then(response => response.json())
        .then(data => {
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">Personal Information</h6>
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%">Student ID:</th>
                                <td><strong>${student.student_id}</strong></td>
                            </tr>
                            <tr>
                                <th>Full Name:</th>
                                <td>${student.full_name}</td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td>${student.email}</td>
                            </tr>
                            <tr>
                                <th>Phone:</th>
                                <td>${student.phone}</td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <span class="badge ${student.student_status.includes('State Team') ? 'badge-state-team' : 
                                        (student.student_status.includes('Backup Team') ? 'badge-backup-team' : 'badge-student')}">
                                        ${student.student_status}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Created:</th>
                                <td>${new Date(student.created_at).toLocaleDateString()}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">Enrolled Classes</h6>
                        <div class="list-group">
            `;

            if (data.enrollments && data.enrollments.length > 0) {
                data.enrollments.forEach(enrollment => {
                    html += `
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>${enrollment.class_name}</strong><br>
                                    <small class="text-muted">Code: ${enrollment.class_code}</small><br>
                                    <small class="text-muted">Fee: RM ${parseFloat(enrollment.monthly_fee).toFixed(2)}/month</small>
                                </div>
                                <span class="badge ${enrollment.status === 'active' ? 'bg-success' : 'bg-secondary'}">
                                    ${enrollment.status}
                                </span>
                            </div>
                        </div>
                    `;
                });
            } else {
                html += '<div class="alert alert-info">No active enrollments</div>';
            }

            html += `
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-success btn-sm w-100" onclick="bootstrap.Modal.getInstance(document.getElementById('viewStudentModal')).hide(); enrollStudent(${id});">
                                <i class="fas fa-plus"></i> Enroll in New Class
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('viewStudentContent').innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('viewStudentContent').innerHTML = '<div class="alert alert-danger">Failed to load student details.</div>';
        });
}

function enrollStudent(id) {
    const student = studentsData.find(s => s.id == id);
    if (!student) return;

    document.getElementById('enroll_student_id').value = student.id;
    document.getElementById('enroll_student_name').textContent = student.full_name;
    document.getElementById('enroll_class_id').value = '';

    const modal = new bootstrap.Modal(document.getElementById('enrollStudentModal'));
    modal.show();
}

function editStudent(id) {
    const student = studentsData.find(s => s.id == id);
    if (!student) return;

    document.getElementById('edit_student_id').value = student.id;
    document.getElementById('edit_full_name').value = student.full_name;
    document.getElementById('edit_email').value = student.email;
    document.getElementById('edit_phone').value = student.phone;
    document.getElementById('edit_student_status').value = student.student_status;

    const modal = new bootstrap.Modal(document.getElementById('editStudentModal'));
    modal.show();
}
</script>