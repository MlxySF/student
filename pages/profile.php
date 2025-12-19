<?php
// Student Profile Page - Updated for multi-child parent support
// FIXED: Enable phone, Chinese name editing, and school dropdown for parents

// Handle profile update for parents
if (isParent() && isset($_POST['action']) && $_POST['action'] === 'update_child_profile') {
    $registration_id = getActiveStudentId();
    $name_en = trim($_POST['name_en']);
    $name_cn = trim($_POST['name_cn']);
    $ic = trim($_POST['ic']);
    $phone = trim($_POST['phone']);
    $school = trim($_POST['school']);
    $school_other = isset($_POST['school_other']) ? trim($_POST['school_other']) : '';
    
    // If school is "Others", use the custom input
    if ($school === 'Others' && !empty($school_other)) {
        $school = $school_other;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE registrations SET name_en = ?, name_cn = ?, ic = ?, phone = ?, school = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$name_en, $name_cn, $ic, $phone, $school, $registration_id]);
        
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
        echo '<i class="fas fa-check-circle"></i> Child profile updated successfully!';
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
        
        // Reload updated data
        $_GET['updated'] = '1';
    } catch (Exception $e) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        echo '<i class="fas fa-exclamation-triangle"></i> Error updating profile: ' . htmlspecialchars($e->getMessage());
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

// Get student information using active student ID
if (isParent()) {
    $stmt = $pdo->prepare("SELECT r.*, s.id as student_account_id FROM registrations r LEFT JOIN students s ON r.student_account_id = s.id WHERE r.id = ?");
    $stmt->execute([getActiveStudentId()]);
    $student = $stmt->fetch();
    if ($student) {
        $student['full_name'] = $student['name_en'];
        $student['chinese_name'] = $student['name_cn'] ?? '';
        $student['student_id'] = $student['registration_number'];
        $student['ic_number'] = $student['ic'] ?? '';
    }
    $studentAccountId = $student['student_account_id'] ?? null;
} else {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([getActiveStudentId()]);
    $student = $stmt->fetch();
    $studentAccountId = getActiveStudentId();
}

if (!$student) {
    echo '<div class="alert alert-danger">Error: Student data not found.</div>';
    exit;
}

// Get enrolled classes count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as class_count 
    FROM enrollments 
    WHERE student_id = ? AND status = 'active'
");
$stmt->execute([$studentAccountId]);
$enrollment_stats = $stmt->fetch();

// Get payment statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_payments,
        SUM(CASE WHEN verification_status = 'verified' THEN 1 ELSE 0 END) as verified_payments,
        SUM(CASE WHEN verification_status = 'verified' THEN amount ELSE 0 END) as total_paid
    FROM payments 
    WHERE student_id = ?
");
$stmt->execute([$studentAccountId]);
$payment_stats = $stmt->fetch();

// Get invoice statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_invoices,
        SUM(CASE WHEN status = 'unpaid' THEN 1 ELSE 0 END) as unpaid_invoices,
        SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_invoices
    FROM invoices 
    WHERE student_id = ?
");
$stmt->execute([$studentAccountId]);
$invoice_stats = $stmt->fetch();
?>

<?php if (isParent()): ?>
<div class="alert alert-info mb-3">
    <i class="fas fa-info-circle"></i> Viewing profile for: <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>
    <span class="text-muted">(You can edit your child's profile)</span>
</div>
<?php endif; ?>

<div class="row">
    <!-- Profile Information Card -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user"></i> <?php echo isParent() ? 'Child' : 'My'; ?> Profile Information
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-12 text-center mb-4">
                        <div class="profile-avatar">
                            <i class="fas fa-user-circle fa-5x text-primary"></i>
                        </div>
                        <h4 class="mt-3 mb-1"><?php echo htmlspecialchars($student['full_name']); ?></h4>
                        <?php if (!empty($student['chinese_name'])): ?>
                        <p class="text-muted mb-2"><?php echo htmlspecialchars($student['chinese_name']); ?></p>
                        <?php endif; ?>
                        <p class="text-muted">
                            <span class="badge bg-dark"><?php echo $student['student_id']; ?></span>
                            <?php if (!empty($student['student_status']) && $student['student_status'] !== 'Student 学生'): ?>
                            <br><span class="badge <?php 
                                echo (strpos($student['student_status'], 'State Team') !== false) ? 'bg-success' : 
                                     ((strpos($student['student_status'], 'Backup Team') !== false) ? 'bg-warning' : 'bg-info');
                            ?>"><?php echo htmlspecialchars($student['student_status']); ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <td width="30%"><strong><i class="fas fa-id-card text-primary"></i> Student ID:</strong></td>
                                <td><?php echo $student['student_id']; ?></td>
                            </tr>
                            <tr>
                                <td><strong><i class="fas fa-user text-primary"></i> English Name:</strong></td>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                            </tr>
                            <?php if (!empty($student['chinese_name'])): ?>
                            <tr>
                                <td><strong><i class="fas fa-language text-primary"></i> Chinese Name:</strong></td>
                                <td><?php echo htmlspecialchars($student['chinese_name']); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td><strong><i class="fas fa-envelope text-primary"></i> Email:</strong></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                            </tr>
                            <?php if (!empty($student['phone'])): ?>
                            <tr>
                                <td><strong><i class="fas fa-phone text-primary"></i> Phone:</strong></td>
                                <td><?php echo htmlspecialchars($student['phone']); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if (!empty($student['ic_number'])): ?>
                            <tr>
                                <td><strong><i class="fas fa-id-card text-primary"></i> IC/Passport:</strong></td>
                                <td><?php echo htmlspecialchars($student['ic_number']); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if (!empty($student['school'])): ?>
                            <tr>
                                <td><strong><i class="fas fa-school text-primary"></i> School:</strong></td>
                                <td><?php echo htmlspecialchars($student['school']); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td><strong><i class="fas fa-calendar text-primary"></i> Registered:</strong></td>
                                <td><?php echo formatDateTime($student['created_at']); ?></td>
                            </tr>
                            <tr>
                                <td><strong><i class="fas fa-globe text-primary"></i> Timezone:</strong></td>
                                <td>GMT+8 (Malaysia)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="text-center mt-4">
                    <?php if (isParent()): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editChildProfileModal">
                            <i class="fas fa-edit"></i> Edit Child Profile
                        </button>
                    <?php else: ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            <i class="fas fa-edit"></i> Edit Profile
                        </button>
                        <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Card -->
    <div class="col-md-4 mb-4">
        <div class="card mb-3">
            <div class="card-header">
                <i class="fas fa-chart-bar"></i> Statistics
            </div>
            <div class="card-body">
                <div class="stat-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-book text-primary"></i>
                            <strong>Enrolled Classes</strong>
                        </div>
                        <span class="badge bg-primary"><?php echo $enrollment_stats['class_count'] ?? 0; ?></span>
                    </div>
                </div>

                <div class="stat-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-credit-card text-success"></i>
                            <strong>Total Payments</strong>
                        </div>
                        <span class="badge bg-success"><?php echo $payment_stats['total_payments'] ?? 0; ?></span>
                    </div>
                </div>

                <div class="stat-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-check-circle text-success"></i>
                            <strong>Verified Payments</strong>
                        </div>
                        <span class="badge bg-success"><?php echo $payment_stats['verified_payments'] ?? 0; ?></span>
                    </div>
                </div>

                <div class="stat-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-money-bill text-info"></i>
                            <strong>Total Paid</strong>
                        </div>
                        <strong class="text-success"><?php echo formatCurrency($payment_stats['total_paid'] ?? 0); ?></strong>
                    </div>
                </div>

                <hr>

                <div class="stat-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-file-invoice text-warning"></i>
                            <strong>Unpaid Invoices</strong>
                        </div>
                        <span class="badge bg-warning"><?php echo $invoice_stats['unpaid_invoices'] ?? 0; ?></span>
                    </div>
                </div>

                <div class="stat-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-file-invoice text-success"></i>
                            <strong>Paid Invoices</strong>
                        </div>
                        <span class="badge bg-success"><?php echo $invoice_stats['paid_invoices'] ?? 0; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> Account Info
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Account Status:</strong> <span class="badge bg-success">Active</span></p>
                <p class="mb-2"><strong>Account Type:</strong> 
                    <?php if (isParent()): ?>
                        <span class="badge bg-info">Managed by Parent</span>
                    <?php else: ?>
                        <span class="badge bg-primary">Independent</span>
                    <?php endif; ?>
                </p>
                <p class="mb-2"><strong>Member Since:</strong> <?php echo formatDate($student['created_at']); ?></p>
                <?php if (!empty($student['updated_at'])): ?>
                <p class="mb-0"><strong>Last Updated:</strong> <?php echo formatDateTime($student['updated_at']); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- My Classes -->
<?php
$stmt = $pdo->prepare("
    SELECT c.*, e.enrollment_date, e.status
    FROM enrollments e
    JOIN classes c ON e.class_id = c.id
    WHERE e.student_id = ?
    ORDER BY e.enrollment_date DESC
");
$stmt->execute([$studentAccountId]);
$my_classes = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <i class="fas fa-book"></i> Enrolled Classes
    </div>
    <div class="card-body">
        <?php if ($my_classes): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Class Code</th>
                            <th>Class Name</th>
                            <th>Monthly Fee</th>
                            <th>Enrollment Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($my_classes as $class): ?>
                            <tr>
                                <td><span class="badge bg-primary"><?php echo $class['class_code']; ?></span></td>
                                <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                <td><strong><?php echo formatCurrency($class['monthly_fee']); ?></strong></td>
                                <td><?php echo formatDateTime($class['enrollment_date']); ?></td>
                                <td>
                                    <?php if ($class['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                <p class="text-muted">Not enrolled in any classes yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Child Profile Modal (For Parents) -->
<?php if (isParent()): ?>
<div class="modal fade" id="editChildProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_child_profile">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Child Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">English Name 英文名 *</label>
                        <input type="text" name="name_en" class="form-control" 
                               value="<?php echo htmlspecialchars($student['name_en'] ?? $student['full_name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Chinese Name 中文名</label>
                        <input type="text" name="name_cn" class="form-control" 
                               value="<?php echo htmlspecialchars($student['name_cn'] ?? ''); ?>" 
                               placeholder="张三">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">IC Number/Passport 身份证号码</label>
                        <input type="text" name="ic" class="form-control" 
                               value="<?php echo htmlspecialchars($student['ic'] ?? ''); ?>" 
                               placeholder="000000-00-0000">
                        <small class="text-muted">Child's identification number</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number 电话号码 *</label>
                        <input type="tel" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>" 
                               placeholder="012-345 6789" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">School 学校 *</label>
                        <select name="school" id="school-edit" class="form-select" required>
                            <option value="">Select School...</option>
                            <option value="SJK(C) PUAY CHAI 2" <?php echo ($student['school'] ?? '') === 'SJK(C) PUAY CHAI 2' ? 'selected' : ''; ?>>SJK(C) PUAY CHAI 2 (培才二校)</option>
                            <option value="SJK(C) Chee Wen" <?php echo ($student['school'] ?? '') === 'SJK(C) Chee Wen' ? 'selected' : ''; ?>>SJK(C) Chee Wen</option>
                            <option value="SJK(C) Subang" <?php echo ($student['school'] ?? '') === 'SJK(C) Subang' ? 'selected' : ''; ?>>SJK(C) Subang</option>
                            <option value="SJK(C) Sin Ming" <?php echo ($student['school'] ?? '') === 'SJK(C) Sin Ming' ? 'selected' : ''; ?>>SJK(C) Sin Ming</option>
                            <option value="Others" <?php 
                                $predefined_schools = ['SJK(C) PUAY CHAI 2', 'SJK(C) Chee Wen', 'SJK(C) Subang', 'SJK(C) Sin Ming'];
                                echo (!empty($student['school']) && !in_array($student['school'], $predefined_schools)) ? 'selected' : ''; 
                            ?>>Others (其他)</option>
                        </select>
                    </div>
                    <div class="mb-3" id="school-other-container" style="<?php 
                        $predefined_schools = ['SJK(C) PUAY CHAI 2', 'SJK(C) Chee Wen', 'SJK(C) Subang', 'SJK(C) Sin Ming'];
                        echo (empty($student['school']) || in_array($student['school'], $predefined_schools)) ? 'display: none;' : ''; 
                    ?>">
                        <label class="form-label">Please specify school name *</label>
                        <input type="text" name="school_other" id="school-other-edit" class="form-control" 
                               value="<?php 
                                   $predefined_schools = ['SJK(C) PUAY CHAI 2', 'SJK(C) Chee Wen', 'SJK(C) Subang', 'SJK(C) Sin Ming'];
                                   echo (!empty($student['school']) && !in_array($student['school'], $predefined_schools)) ? htmlspecialchars($student['school']) : ''; 
                               ?>" 
                               placeholder="Enter school name">
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Student ID and registration details cannot be changed. Contact admin for major changes.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Show/hide school other field for edit modal
document.getElementById('school-edit').addEventListener('change', function() {
    const otherContainer = document.getElementById('school-other-container');
    const otherInput = document.getElementById('school-other-edit');
    if (this.value === 'Others') {
        otherContainer.style.display = 'block';
        otherInput.required = true;
    } else {
        otherContainer.style.display = 'none';
        otherInput.required = false;
        otherInput.value = '';
    }
});
</script>
<?php endif; ?>

<!-- Edit Profile Modal (For direct student login) -->
<?php if (!isParent()): ?>
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_profile">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" 
                               value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($student['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>" required>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Your Student ID cannot be changed.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="change_password">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key"></i> Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.stat-item {
    padding: 10px;
    border-radius: 8px;
    background: #f8f9fa;
}
.profile-avatar {
    display: inline-block;
}
</style>