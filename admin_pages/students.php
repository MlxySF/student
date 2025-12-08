<?php
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

<h3><i class="fas fa-users"></i> Students</h3>

<button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createModal">
    <i class="fas fa-plus"></i> Create Student
</button>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Enrolled Classes</th>
                        <th>Verified Payments</th>
                        <th>Registered Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($all_students as $s): ?>
                    <tr>
                        <td><span class="badge bg-secondary"><?php echo $s['student_id']; ?></span></td>
                        <td><?php echo htmlspecialchars($s['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($s['email']); ?></td>
                        <td><?php echo htmlspecialchars($s['phone']); ?></td>
                        <td><?php echo $s['enrolled_classes']; ?></td>
                        <td><?php echo $s['total_payments']; ?></td>
                        <td><?php echo formatDate($s['created_at']); ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $s['id']; ?>">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#enrollModal<?php echo $s['id']; ?>">
                                <i class="fas fa-plus-circle"></i> Enroll
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="if(confirm('Delete this student?')) document.getElementById('delete<?php echo $s['id']; ?>').submit()">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                            <form id="delete<?php echo $s['id']; ?>" method="POST" style="display:none">
                                <input type="hidden" name="action" value="delete_student">
                                <input type="hidden" name="student_id" value="<?php echo $s['id']; ?>">
                            </form>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal<?php echo $s['id']; ?>">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <input type="hidden" name="action" value="edit_student">
                                    <input type="hidden" name="student_id" value="<?php echo $s['id']; ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Student</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label>Full Name</label>
                                            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($s['full_name']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label>Email</label>
                                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($s['email']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label>Phone</label>
                                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($s['phone']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label>New Password (leave blank to keep current)</label>
                                            <input type="password" name="password" class="form-control">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Enroll Modal -->
                    <div class="modal fade" id="enrollModal<?php echo $s['id']; ?>">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <input type="hidden" name="action" value="enroll_student">
                                    <input type="hidden" name="student_id" value="<?php echo $s['id']; ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Enroll Student</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong><?php echo htmlspecialchars($s['full_name']); ?></strong> (<?php echo $s['student_id']; ?>)</p>
                                        <div class="mb-3">
                                            <label>Select Class</label>
                                            <select name="class_id" class="form-control" required>
                                                <option value="">-- Select Class --</option>
                                                <?php foreach($all_classes as $c): ?>
                                                <option value="<?php echo $c['id']; ?>"><?php echo $c['class_code']; ?> - <?php echo htmlspecialchars($c['class_name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label>Enrollment Date</label>
                                            <input type="date" name="enrollment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success">Enroll</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Student Modal -->
<div class="modal fade" id="createModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="create_student">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Full Name</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Student ID will be auto-generated
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Student</button>
                </div>
            </form>
        </div>
    </div>
</div>