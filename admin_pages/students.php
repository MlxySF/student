<?php
// Get all students with statistics
$stmt = $pdo->query("
    SELECT s.*, 
    COUNT(DISTINCT e.class_id) as enrolled_classes,
    COUNT(DISTINCT p.id) as total_payments
    FROM students s
    LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
    LEFT JOIN payments p ON s.id = p.student_id AND p.verification_status = 'verified'
    GROUP BY s.id
    ORDER BY s.created_at DESC
");
$all_students = $stmt->fetchAll();

// Get all classes for enrollment
$all_classes = $pdo->query("SELECT * FROM classes ORDER BY class_code")->fetchAll();
?>

<style>
    /* Mobile-friendly table */
    @media (max-width: 768px) {
        .hide-mobile {
            display: none !important;
        }

        .table td, .table th {
            padding: 10px 5px;
            font-size: 13px;
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }
    }

    @media (max-width: 480px) {
        .table td, .table th {
            padding: 8px 3px;
            font-size: 12px;
        }

        .btn-sm {
            padding: 3px 6px;
            font-size: 11px;
        }

        .badge {
            font-size: 10px;
            padding: 3px 6px;
        }
    }
</style>

<!-- Add Student Button -->
<div class="mb-3">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createStudentModal">
        <i class="fas fa-plus"></i> Create Student
    </button>
</div>

<!-- Students Table -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-users"></i> All Students (<?php echo count($all_students); ?>)
    </div>
    <div class="card-body">
        <?php if (count($all_students) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th class="hide-mobile">Email</th>
                            <th class="hide-mobile">Phone</th>
                            <th class="hide-mobile">Classes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_students as $student): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($student['student_id']); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>
                                    <div class="d-md-none text-muted small">
                                        <?php echo htmlspecialchars($student['email']); ?>
                                    </div>
                                </td>
                                <td class="hide-mobile"><?php echo htmlspecialchars($student['email']); ?></td>
                                <td class="hide-mobile"><?php echo htmlspecialchars($student['phone']); ?></td>
                                <td class="hide-mobile">
                                    <span class="badge bg-info"><?php echo $student['enrolled_classes']; ?></span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#viewStudentModal<?php echo $student['id']; ?>" title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editStudentModal<?php echo $student['id']; ?>" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger" onclick="if(confirm('Delete this student?')) document.getElementById('deleteForm<?php echo $student['id']; ?>').submit();" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>

                                    <!-- Hidden delete form -->
                                    <form id="deleteForm<?php echo $student['id']; ?>" method="POST" style="display:none;">
                                        <input type="hidden" name="action" value="delete_student">
                                        <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No students found. Create your first student!
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- View Student Modals (Outside table) -->
<?php foreach ($all_students as $student): ?>
    <div class="modal fade" id="viewStudentModal<?php echo $student['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user"></i> Student Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="40%">Student ID</th>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($student['student_id']); ?></span></td>
                        </tr>
                        <tr>
                            <th>Full Name</th>
                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td><?php echo htmlspecialchars($student['phone']); ?></td>
                        </tr>
                        <tr>
                            <th>Enrolled Classes</th>
                            <td><span class="badge bg-info"><?php echo $student['enrolled_classes']; ?></span></td>
                        </tr>
                        <tr>
                            <th>Verified Payments</th>
                            <td><span class="badge bg-success"><?php echo $student['total_payments']; ?></span></td>
                        </tr>
                        <tr>
                            <th>Registered Date</th>
                            <td><?php echo formatDate($student['created_at']); ?></td>
                        </tr>
                    </table>

                    <div class="mt-3">
    <button type="button" class="btn btn-sm btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#studentClassesModal<?php echo $student['id']; ?>">
        <i class="fas fa-chalkboard-teacher"></i> View Classes
    </button>
    <button type="button" class="btn btn-sm btn-success"
            data-bs-toggle="modal"
            data-bs-target="#studentPaymentsModal<?php echo $student['id']; ?>">
        <i class="fas fa-credit-card"></i> View Payments
    </button>
</div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div class="modal fade" id="editStudentModal<?php echo $student['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_student">
                        <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">

                        <div class="mb-3">
                            <label class="form-label">Student ID</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['student_id']); ?>" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Phone *</label>
                            <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($student['phone']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" name="password" class="form-control" placeholder="Enter new password">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
// Get this student's active enrollments
$enroll_stmt = $pdo->prepare("
    SELECT c.class_code, c.class_name, c.monthly_fee, e.enrollment_date, e.status
    FROM enrollments e
    JOIN classes c ON e.class_id = c.id
    WHERE e.student_id = ?
    ORDER BY e.enrollment_date DESC
");
$enroll_stmt->execute([$student['id']]);
$student_classes = $enroll_stmt->fetchAll();

// Get this student's payments
$pay_stmt = $pdo->prepare("
    SELECT p.*, c.class_code, c.class_name
    FROM payments p
    JOIN classes c ON p.class_id = c.id
    WHERE p.student_id = ?
    ORDER BY p.upload_date DESC
");
$pay_stmt->execute([$student['id']]);
$student_payments = $pay_stmt->fetchAll();
?>

<!-- Student Classes Modal -->
<div class="modal fade" id="studentClassesModal<?php echo $student['id']; ?>" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
            <i class="fas fa-chalkboard-teacher"></i>
            Classes - <?php echo htmlspecialchars($student['full_name']); ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php if (count($student_classes) > 0): ?>
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
                <?php foreach ($student_classes as $c): ?>
                  <tr>
                    <td><span class="badge bg-info"><?php echo htmlspecialchars($c['class_code']); ?></span></td>
                    <td><?php echo htmlspecialchars($c['class_name']); ?></td>
                    <td><?php echo formatCurrency($c['monthly_fee']); ?></td>
                    <td><?php echo formatDate($c['enrollment_date']); ?></td>
                    <td>
                      <?php if ($c['status'] === 'active'): ?>
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
          <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            This student is not enrolled in any classes.
          </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
    <button type="button"
            class="btn btn-secondary"
            data-bs-target="#viewStudentModal<?php echo $student['id']; ?>"
            data-bs-toggle="modal"
            data-bs-dismiss="modal">
        ← Back
    </button>
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
        Close
    </button>
</div>
    </div>
  </div>
</div>

<!-- Student Payments Modal -->
<div class="modal fade" id="studentPaymentsModal<?php echo $student['id']; ?>" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
            <i class="fas fa-credit-card"></i>
            Payments - <?php echo htmlspecialchars($student['full_name']); ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php if (count($student_payments) > 0): ?>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Class</th>
                  <th>Amount</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($student_payments as $p): ?>
                  <tr>
                    <td><?php echo formatDate($p['upload_date']); ?></td>
                    <td><span class="badge bg-info"><?php echo htmlspecialchars($p['class_code']); ?></span></td>
                    <td><strong><?php echo formatCurrency($p['amount']); ?></strong></td>
                    <td>
                      <?php if ($p['verification_status'] === 'pending'): ?>
                        <span class="badge bg-warning">Pending</span>
                      <?php elseif ($p['verification_status'] === 'verified'): ?>
                        <span class="badge bg-success">Verified</span>
                      <?php else: ?>
                        <span class="badge bg-danger">Rejected</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            This student has no payments yet.
          </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
    <button type="button"
            class="btn btn-secondary"
            data-bs-target="#viewStudentModal<?php echo $student['id']; ?>"
            data-bs-toggle="modal"
            data-bs-dismiss="modal">
        ← Back
    </button>
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
        Close
    </button>
</div>

    </div>
  </div>
</div>

<?php endforeach; ?>

<!-- Create Student Modal -->
<div class="modal fade" id="createStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> Create New Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_student">

                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone *</label>
                        <input type="tel" name="phone" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Student ID will be auto-generated
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>