<?php
// Get all classes with enrolled student count
$all_classes = $pdo->query("
    SELECT c.*, COUNT(DISTINCT e.student_id) as enrolled_students
    FROM classes c
    LEFT JOIN enrollments e ON c.id = e.class_id AND e.status = 'active'
    GROUP BY c.id
    ORDER BY c.class_code
")->fetchAll();
?>

<style>
    @media (max-width: 768px) {
        .hide-mobile { display: none !important; }
        .table td, .table th { padding: 10px 5px; font-size: 13px; }
        .btn-sm { padding: 4px 8px; font-size: 12px; }
    }

    @media (max-width: 480px) {
        .table td, .table th { padding: 8px 3px; font-size: 12px; }
        .btn-sm { padding: 3px 6px; font-size: 11px; }
        .badge { font-size: 10px; padding: 3px 6px; }
    }
</style>

<!-- Create Class Button -->
<div class="mb-3">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createClassModal">
        <i class="fas fa-plus"></i> Create Class
    </button>
</div>

<!-- Classes Table -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-chalkboard-teacher"></i> All Classes (<?php echo count($all_classes); ?>)
    </div>
    <div class="card-body">
        <?php if (count($all_classes) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Class Code</th>
                            <th>Class Name</th>
                            <th class="hide-mobile">Monthly Fee</th>
                            <th class="hide-mobile">Enrolled</th>
                            <th class="hide-mobile">Description</th>
                            <th class="hide-mobile">Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_classes as $class): ?>
                            <tr>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($class['class_code']); ?></span></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($class['class_name']); ?></strong>
                                    <div class="d-md-none text-muted small">
                                        RM <?php echo number_format($class['monthly_fee'], 2); ?>/mo
                                    </div>
                                </td>
                                <td class="hide-mobile">RM <?php echo number_format($class['monthly_fee'], 2); ?></td>
                                <td class="hide-mobile">
                                    <span class="badge bg-success"><?php echo $class['enrolled_students']; ?> students</span>
                                </td>
                                <td class="hide-mobile"><?php echo htmlspecialchars($class['description']); ?></td>
                                <td class="hide-mobile"><?php echo date('M d, Y', strtotime($class['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editClassModal<?php echo $class['id']; ?>" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger" onclick="if(confirm('Delete this class?')) document.getElementById('deleteClassForm<?php echo $class['id']; ?>').submit();" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>

                                    <form id="deleteClassForm<?php echo $class['id']; ?>" method="POST" action="admin_handler.php" style="display:none;">
                                        <input type="hidden" name="action" value="delete_class">
                                        <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                                    </form>
                                </td>
                            </tr>

                            <!-- Edit Class Modal -->
                            <div class="modal fade" id="editClassModal<?php echo $class['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Class</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="admin_handler.php">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="edit_class">
                                                <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                                                <div class="mb-3">
                                                    <label class="form-label">Class Code *</label>
                                                    <input type="text" name="class_code" class="form-control" value="<?php echo htmlspecialchars($class['class_code']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Class Name *</label>
                                                    <input type="text" name="class_name" class="form-control" value="<?php echo htmlspecialchars($class['class_name']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Monthly Fee (RM) *</label>
                                                    <input type="number" step="0.01" name="monthly_fee" class="form-control" value="<?php echo $class['monthly_fee']; ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Description</label>
                                                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($class['description']); ?></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
                                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info"><i class="fas fa-info-circle"></i> No classes found. Create your first class!</div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Class Modal -->
<div class="modal fade" id="createClassModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Create New Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="admin_handler.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_class">
                    <div class="mb-3">
                        <label class="form-label">Class Code *</label>
                        <input type="text" name="class_code" class="form-control" placeholder="e.g., WSA-SUN-10AM" required>
                        <small class="text-muted">Unique code for the class</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Class Name *</label>
                        <input type="text" name="class_name" class="form-control" placeholder="Wushu Sport Academy: Sun 10am-12pm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monthly Fee (RM) *</label>
                        <input type="number" step="0.01" name="monthly_fee" class="form-control" placeholder="200.00" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Class details"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Create Class</button>
                </div>
            </form>
        </div>
    </div>
</div>