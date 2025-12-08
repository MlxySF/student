<?php
// My Classes Page
$student_id = getStudentId();

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

<div class="animate__animated animate__fadeIn">
    <h2 class="mb-4"><i class="fas fa-book"></i> My Classes</h2>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-list"></i> Enrolled Classes
        </div>
        <div class="card-body">
            <?php if ($enrolled_classes): ?>
                <div class="table-responsive">
                    <table class="table table-modern table-hover">
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
                            <?php foreach($enrolled_classes as $class): ?>
                                <tr>
                                    <td><span class="badge bg-primary fs-6"><?php echo $class['class_code']; ?></span></td>
                                    <td><strong><?php echo $class['class_name']; ?></strong></td>
                                    <td class="text-success"><strong><?php echo formatCurrency($class['monthly_fee']); ?></strong></td>
                                    <td><?php echo formatDate($class['enrollment_date']); ?></td>
                                    <td>
                                        <?php if ($class['status'] === 'active'): ?>
                                            <span class="badge bg-success badge-status">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary badge-status">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $class['description']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-book fa-4x text-muted mb-3"></i>
                    <p class="text-muted">You haven't enrolled in any classes yet.</p>
                    <small class="text-muted">Contact admin to enroll in classes.</small>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>