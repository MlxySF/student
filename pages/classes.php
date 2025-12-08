<?php
// Get student ID from session
$student_id = getStudentId();

// Get enrolled classes
$stmt = $pdo->prepare("
    SELECT c.*, e.enrollment_date, e.status 
    FROM enrollments e 
    JOIN classes c ON e.class_id = c.id 
    WHERE e.student_id = ? 
    ORDER BY e.enrollment_date DESC
");
$stmt->execute([$student_id]);
$enrolled_classes = $stmt->fetchAll();
?>

<!-- My Enrolled Classes -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-chalkboard-teacher"></i> My Enrolled Classes
    </div>
    <div class="card-body">
        <?php if (count($enrolled_classes) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Class Code</th>
                            <th>Class Name</th>
                            <th>Monthly Fee</th>
                            <th>Enrollment Date</th>
                            <th>Status</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enrolled_classes as $class): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($class['class_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                <td><?php echo formatCurrency($class['monthly_fee']); ?></td>
                                <td><?php echo formatDate($class['enrollment_date']); ?></td>
                                <td>
                                    <?php if ($class['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($class['description'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                You haven't enrolled in any classes yet. Contact admin to enroll in classes.
            </div>
        <?php endif; ?>
    </div>
</div>