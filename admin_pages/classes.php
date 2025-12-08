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
                                        <?php echo htmlspecialchars($class['description']); ?>
                                    </div>
                                </td>
                                <td class="hide-mobile"><?php echo formatCurrency($class['monthly_fee']); ?></td>
                                <td class="hide-mobile">
                                    <span class="badge bg-success"><?php echo $class['enrolled_students']; ?></span>
                                </td>
                                <td class="hide-mobile"><?php echo htmlspecialchars($class['description']); ?></td>
                                <td class="hide-mobile"><?php echo formatDate($class['created_at']); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editClassModal<?php echo $class['id']; ?>" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger" onclick="if(confirm('Delete this class?')) document.getElementById('deleteClassForm<?php echo $class['id']; ?>').submit();" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>

                                    <form id="deleteClassForm<?php echo $class['id']; ?>" method="POST" style="display:none;">
                                        <input type="hidden" name="action" value="delete_class">
                                        <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No classes found. Create your first class!
            </div>
        <?php endif; ?>
    </div>
</div>