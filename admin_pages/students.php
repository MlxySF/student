<?php
// admin_pages/students.php - View and edit students using REGISTRATIONS table
// UPDATED: Now uses registrations table instead of students table

// Handle status filter
$statusFilter = $_GET['status_filter'] ?? '';
$paymentStatusFilter = $_GET['payment_status'] ?? 'approved'; // Default to approved only

// Build query - use registrations table
$sql = "SELECT r.*, 
        pa.email as parent_email,
        pa.full_name as parent_name,
        (SELECT COUNT(*) FROM enrollments WHERE student_id = r.student_account_id AND status = 'active') as enrollment_count
        FROM registrations r
        LEFT JOIN parent_accounts pa ON r.parent_account_id = pa.id
        WHERE 1=1";
$params = [];

// Only show approved registrations by default (students with active accounts)
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
                </div>
            </div>
        </div>

        <!-- Students Table -->
        <div class="table-responsive">
            <table class="table table-striped table-hover data-table">
                <thead>
                    <tr>
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
                                <button class="btn btn-primary" onclick="viewStudent(<?php echo $student['id']; ?>, <?php echo $student['student_account_id']; ?>)" title="View & Manage Enrollments">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-success" onclick="enrollStudent(<?php echo $student['id']; ?>, <?php echo $student['student_account_id']; ?>, '<?php echo htmlspecialchars(addslashes($student['name_en'])); ?>')" title="Enroll in Class">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <?php else: ?>
                                <span class="badge bg-secondary">No Account</span>
                                <?php endif; ?>
                                <button class="btn btn-warning" onclick="editStudent(<?php echo $student['id']; ?>)" title="Edit Student">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
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

<!-- View Student Modal -->
<div class="modal fade" id="viewStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-eye"></i> Student Details & Enrollments</h5>
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
                <input type="hidden" name="student_id" id="enroll_student_account_id">
                <input type="hidden" name="registration_id" id="enroll_registration_id">
                
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
                <input type="hidden" name="action" value="edit_student_registration">
                <input type="hidden" name="registration_id" id="edit_registration_id">
                
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">English Name</label>
                        <input type="text" class="form-control" name="name_en" id="edit_name_en" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Chinese Name</label>
                        <input type="text" class="form-control" name="name_cn" id="edit_name_cn">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Age</label>
                        <input type="number" class="form-control" name="age" id="edit_age" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">School</label>
                        <input type="text" class="form-control" name="school" id="edit_school" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" id="edit_phone" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">IC Number</label>
                        <input type="text" class="form-control" name="ic" id="edit_ic">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Student Status</label>
                        <select class="form-select" name="student_status" id="edit_student_status" required>
                            <option value="Student 学生">Student 学生</option>
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
    const student = studentsData.find(s => s.id == registrationId);
    if (!student) return;

    // Show modal first
    const modal = new bootstrap.Modal(document.getElementById('viewStudentModal'));
    modal.show();

    // Fetch enrollments for this student using student_account_id
    fetch(`admin_handler.php?action=get_student_details&student_id=${studentAccountId}`)
        .then(response => response.json())
        .then(data => {
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3"><i class="fas fa-user"></i> Personal Information</h6>
                        <table class="table table-bordered table-sm">
                            <tr>
                                <th width="40%">Student ID:</th>
                                <td><strong>${student.registration_number}</strong></td>
                            </tr>
                            <tr>
                                <th>English Name:</th>
                                <td>${student.name_en}</td>
                            </tr>
                            ${student.name_cn ? `<tr><th>Chinese Name:</th><td>${student.name_cn}</td></tr>` : ''}
                            <tr>
                                <th>Age:</th>
                                <td>${student.age}</td>
                            </tr>
                            <tr>
                                <th>School:</th>
                                <td>${student.school}</td>
                            </tr>
                            <tr>
                                <th>Phone:</th>
                                <td>${student.phone}</td>
                            </tr>
                            <tr>
                                <th>Parent:</th>
                                <td>${student.parent_name || 'N/A'}</td>
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
                                <th>Registered:</th>
                                <td>${new Date(student.created_at).toLocaleDateString()}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3"><i class="fas fa-chalkboard-teacher"></i> Enrolled Classes</h6>
            `;

            if (data.enrollments && data.enrollments.length > 0) {
                html += '<div class="list-group">';
                data.enrollments.forEach(enrollment => {
                    html += `
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <strong>${enrollment.class_name}</strong><br>
                                    <small class="text-muted">
                                        <i class="fas fa-code"></i> ${enrollment.class_code} | 
                                        <i class="fas fa-dollar-sign"></i> RM ${parseFloat(enrollment.monthly_fee).toFixed(2)}/month
                                    </small>
                                </div>
                                <button class="btn btn-danger btn-sm" onclick="unenrollStudent(${studentAccountId}, ${enrollment.id}, '${enrollment.class_name}')" title="Remove from class">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
            } else {
                html += '<div class="alert alert-info"><i class="fas fa-info-circle"></i> No active enrollments</div>';
            }

            html += `
                        <div class="mt-3">
                            <button class="btn btn-success btn-sm w-100" onclick="bootstrap.Modal.getInstance(document.getElementById('viewStudentModal')).hide(); enrollStudent(${registrationId}, ${studentAccountId}, '${student.name_en}');">
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

function unenrollStudent(studentAccountId, enrollmentId, className) {
    if (!confirm(`Remove student from "${className}"?\n\nThis will set the enrollment status to inactive.`)) {
        return;
    }

    // Create a hidden form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'admin_handler.php';

    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'unenroll_student';

    const enrollmentIdInput = document.createElement('input');
    enrollmentIdInput.type = 'hidden';
    enrollmentIdInput.name = 'enrollment_id';
    enrollmentIdInput.value = enrollmentId;

    form.appendChild(actionInput);
    form.appendChild(enrollmentIdInput);
    document.body.appendChild(form);
    form.submit();
}

function enrollStudent(registrationId, studentAccountId, studentName) {
    document.getElementById('enroll_student_account_id').value = studentAccountId;
    document.getElementById('enroll_registration_id').value = registrationId;
    document.getElementById('enroll_student_name').textContent = studentName;
    document.getElementById('enroll_class_id').value = '';

    const modal = new bootstrap.Modal(document.getElementById('enrollStudentModal'));
    modal.show();
}

function editStudent(registrationId) {
    const student = studentsData.find(s => s.id == registrationId);
    if (!student) return;

    document.getElementById('edit_registration_id').value = student.id;
    document.getElementById('edit_name_en').value = student.name_en;
    document.getElementById('edit_name_cn').value = student.name_cn || '';
    document.getElementById('edit_age').value = student.age;
    document.getElementById('edit_school').value = student.school;
    document.getElementById('edit_phone').value = student.phone;
    document.getElementById('edit_ic').value = student.ic || '';
    document.getElementById('edit_student_status').value = student.student_status;

    const modal = new bootstrap.Modal(document.getElementById('editStudentModal'));
    modal.show();
}
</script>