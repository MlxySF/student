<?php
// Student Classes Page - Updated for multi-child parent support

// Get current student info - FIXED for parent portal
if (isParent()) {
    $stmt = $pdo->prepare("SELECT r.*, s.id as student_account_id FROM registrations r LEFT JOIN students s ON r.student_account_id = s.id WHERE r.id = ?");
    $stmt->execute([getActiveStudentId()]);
    $current_student = $stmt->fetch();
    $current_student['full_name'] = $current_student['name_en'];
    $studentAccountId = $current_student['student_account_id'];
} else {
    $stmt = $pdo->prepare("SELECT full_name FROM students WHERE id = ?");
    $stmt->execute([getActiveStudentId()]);
    $current_student = $stmt->fetch();
    $studentAccountId = getActiveStudentId();
}

// Get enrolled classes using student account ID
$stmt = $pdo->prepare("
    SELECT c.*, e.enrollment_date, e.status 
    FROM enrollments e 
    JOIN classes c ON e.class_id = c.id 
    WHERE e.student_id = ? 
    ORDER BY e.enrollment_date DESC
");
$stmt->execute([$studentAccountId]);
$enrolled_classes = $stmt->fetchAll();
?>

<?php if (isParent()): ?>
<div class="alert alert-info mb-3">
    <i class="fas fa-info-circle"></i> Viewing classes for: <strong><?php echo htmlspecialchars($current_student['full_name']); ?></strong>
</div>
<?php endif; ?>

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
                <?php if (isParent()): ?>
                    This child hasn't enrolled in any classes yet. Contact admin to enroll.
                <?php else: ?>
                    You haven't enrolled in any classes yet. Contact admin to enroll in classes.
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>