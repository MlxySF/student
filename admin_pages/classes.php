<?php
$classes = $pdo->query("
    SELECT c.*, COUNT(DISTINCT e.student_id) as enrolled_students
    FROM classes c
    LEFT JOIN enrollments e ON c.id = e.class_id AND e.status = 'active'
    GROUP BY c.id
    ORDER BY c.class_code
")->fetchAll();
?>

<h3><i class="fas fa-book"></i> Classes</h3>

<button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createModal">
    <i class="fas fa-plus"></i> Create Class
</button>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Class Code</th>
                        <th>Class Name</th>
                        <th>Monthly Fee</th>
                        <th>Enrolled Students</th>
                        <th>Description</th>
                        <th>Created Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($classes as $c): ?>
                    <tr>
                        <td><span class="badge bg-primary"><?php echo $c['class_code']; ?></span></td>
                        <td><?php echo htmlspecialchars($c['class_name']); ?></td>
                        <td><?php echo formatCurrency($c['monthly_fee']); ?></td>
                        <td><span class="badge bg-success"><?php echo $c['enrolled_students']; ?></span></td>
                        <td><?php echo htmlspecialchars(substr($c['description'], 0, 50)); ?><?php echo strlen($c['description']) > 50 ? '...' : ''; ?></td>
                        <td><?php echo formatDate($c['created_at']); ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $c['id']; ?>">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="if(confirm('Delete this class?')) document.getElementById('delete<?php echo $c['id']; ?>').submit()">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                            <form id="delete<?php echo $c['id']; ?>" method="POST" style="display:none">
                                <input type="hidden" name="action" value="delete_class">
                                <input type="hidden" name="class_id" value="<?php echo $c['id']; ?>">
                            </form>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal<?php echo $c['id']; ?>">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <input type="hidden" name="action" value="edit_class">
                                    <input type="hidden" name="class_id" value="<?php echo $c['id']; ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Class</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label>Class Code</label>
                                            <input type="text" name="class_code" class="form-control" value="<?php echo $c['class_code']; ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label>Class Name</label>
                                            <input type="text" name="class_name" class="form-control" value="<?php echo htmlspecialchars($c['class_name']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label>Monthly Fee (RM)</label>
                                            <input type="number" step="0.01" name="monthly_fee" class="form-control" value="<?php echo $c['monthly_fee']; ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label>Description</label>
                                            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($c['description']); ?></textarea>
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
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Class Modal -->
<div class="modal fade" id="createModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="create_class">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Class Code</label>
                        <input type="text" name="class_code" class="form-control" placeholder="e.g., MATH101" required>
                    </div>
                    <div class="mb-3">
                        <label>Class Name</label>
                        <input type="text" name="class_name" class="form-control" placeholder="e.g., Mathematics Level A" required>
                    </div>
                    <div class="mb-3">
                        <label>Monthly Fee (RM)</label>
                        <input type="number" step="0.01" name="monthly_fee" class="form-control" placeholder="150.00" required>
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Class description..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Class</button>
                </div>
            </form>
        </div>
    </div>
</div>