<?php
// admin_pages/students.php - With functional view and edit modals

// Handle Edit Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_student') {
    $student_id = $_POST['student_id'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $student_status = $_POST['student_status'];

    // Check if email is already used by another student
    $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
    $stmt->execute([$email, $student_id]);

    if ($stmt->fetch()) {
        $_SESSION['error'] = "Email is already used by another student.";
    } else {
        $stmt = $pdo->prepare("UPDATE students SET full_name = ?, email = ?, phone = ?, student_status = ? WHERE id = ?");
        $success = $stmt->execute([$full_name, $email, $phone, $student_status, $student_id]);

        if ($success) {
            $_SESSION['success'] = "Student updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update student.";
        }
    }

    header('Location: admin.php?page=students');
    exit;
}

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

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="" id="editStudentForm">
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
                            <option value="Student">Student</option>
                            <option value="State Team">State Team</option>
                            <option value="Backup Team">Backup Team</option>
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
                    </div>
                </div>
            `;

            document.getElementById('viewStudentContent').innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('viewStudentContent').innerHTML = '<div class="alert alert-danger">Failed to load student details. Please check the console for more information.</div>';
        });
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