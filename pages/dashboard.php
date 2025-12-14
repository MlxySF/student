<?php
// pages/dashboard.php
// Get student information - FIXED: using student_status
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([getStudentId()]);
$student = $stmt->fetch();

// ... rest of the code ...
?>

<!-- Student Status Card - UPDATED -->
<div class="col-md-6 col-lg-3 mb-3">
    <div class="stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
        <div class="stat-icon bg-info">
            <i class="fas fa-user-tag"></i>
        </div>
        <div class="stat-content">
            <h3 style="font-size: 16px;">
                <?php 
                // Shorten display if too long
                $statusDisplay = $student['student_status']; // FIXED: changed from 'status' to 'student_status'
                if (strlen($statusDisplay) > 15) {
                    $statusDisplay = explode(' ', $statusDisplay)[0];
                }
                echo htmlspecialchars($statusDisplay); 
                ?>
            </h3>
            <p>Student Status</p>
        </div>
    </div>
</div>
