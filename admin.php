<?php
session_start();
require_once 'config.php';

// Helper Functions
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: admin.php');
        exit;
    }
}

// Handle Admin Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'admin_login') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['admin_role'] = $admin['role'];
        header('Location: admin.php?page=dashboard');
        exit;
    } else {
        $_SESSION['error'] = "Invalid username or password.";
    }
}

// Handle Admin Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAdminLoggedIn()) {

    // ============ STUDENT MANAGEMENT ============

    // Create Student
    if (isset($_POST['action']) && $_POST['action'] === 'create_student') {
        $student_id = 'STU' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO students (student_id, full_name, email, phone, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$student_id, $full_name, $email, $phone, $password]);
            $_SESSION['success'] = "Student created successfully! Student ID: $student_id";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Failed to create student. Email may already exist.";
        }
        header('Location: admin.php?page=students');
        exit;
    }

    // Edit Student
    if (isset($_POST['action']) && $_POST['action'] === 'edit_student') {
        $id = $_POST['student_id'];
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];

        try {
            $stmt = $pdo->prepare("UPDATE students SET full_name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $phone, $id]);

            // Update password if provided
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE students SET password = ? WHERE id = ?");
                $stmt->execute([$password, $id]);
            }

            $_SESSION['success'] = "Student updated successfully!";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Failed to update student.";
        }
        header('Location: admin.php?page=students');
        exit;
    }

    // Delete Student
    if (isset($_POST['action']) && $_POST['action'] === 'delete_student') {
        $id = $_POST['student_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = "Student deleted successfully!";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Failed to delete student. They may have associated records.";
        }
        header('Location: admin.php?page=students');
        exit;
    }

    // ============ CLASS MANAGEMENT ============

    // Create Class
    if (isset($_POST['action']) && $_POST['action'] === 'create_class') {
        $class_code = strtoupper($_POST['class_code']);
        $class_name = $_POST['class_name'];
        $monthly_fee = $_POST['monthly_fee'];
        $description = $_POST['description'];

        try {
            $stmt = $pdo->prepare("INSERT INTO classes (class_code, class_name, monthly_fee, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$class_code, $class_name, $monthly_fee, $description]);
            $_SESSION['success'] = "Class created successfully!";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Failed to create class. Class code may already exist.";
        }
        header('Location: admin.php?page=classes');
        exit;
    }

    // Edit Class
    if (isset($_POST['action']) && $_POST['action'] === 'edit_class') {
        $id = $_POST['class_id'];
        $class_code = strtoupper($_POST['class_code']);
        $class_name = $_POST['class_name'];
        $monthly_fee = $_POST['monthly_fee'];
        $description = $_POST['description'];

        try {
            $stmt = $pdo->prepare("UPDATE classes SET class_code = ?, class_name = ?, monthly_fee = ?, description = ? WHERE id = ?");
            $stmt->execute([$class_code, $class_name, $monthly_fee, $description, $id]);
            $_SESSION['success'] = "Class updated successfully!";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Failed to update class.";
        }
        header('Location: admin.php?page=classes');
        exit;
    }

    // Delete Class
    if (isset($_POST['action']) && $_POST['action'] === 'delete_class') {
        $id = $_POST['class_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = "Class deleted successfully!";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Failed to delete class. It may have enrolled students.";
        }
        header('Location: admin.php?page=classes');
        exit;
    }

    // ============ INVOICE MANAGEMENT ============

    // Create Custom Invoice
    if (isset($_POST['action']) && $_POST['action'] === 'create_invoice') {
        $student_id = $_POST['student_id'];
        $invoice_type = $_POST['invoice_type'];
        $description = $_POST['description'];
        $amount = $_POST['amount'];
        $due_date = $_POST['due_date'];
        $class_id = !empty($_POST['class_id']) ? $_POST['class_id'] : null;

        // Generate unique invoice number
        $invoice_number = 'INV-' . date('Ym') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        try {
            $stmt = $pdo->prepare("INSERT INTO invoices (invoice_number, student_id, class_id, invoice_type, description, amount, due_date, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, 'unpaid', ?)");
            $stmt->execute([$invoice_number, $student_id, $class_id, $invoice_type, $description, $amount, $due_date, $_SESSION['admin_id']]);
            $_SESSION['success'] = "Invoice created successfully! Invoice #: $invoice_number";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Failed to create invoice: " . $e->getMessage();
        }
        header('Location: admin.php?page=invoices');
        exit;
    }

    // Edit Invoice
    if (isset($_POST['action']) && $_POST['action'] === 'edit_invoice') {
        $id = $_POST['invoice_id'];
        $description = $_POST['description'];
        $amount = $_POST['amount'];
        $due_date = $_POST['due_date'];
        $status = $_POST['status'];

        try {
            // If marking as paid, set paid_date
            if ($status === 'paid') {
                $stmt = $pdo->prepare("UPDATE invoices SET description = ?, amount = ?, due_date = ?, status = ?, paid_date = NOW() WHERE id = ?");
                $stmt->execute([$description, $amount, $due_date, $status, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE invoices SET description = ?, amount = ?, due_date = ?, status = ? WHERE id = ?");
                $stmt->execute([$description, $amount, $due_date, $status, $id]);
            }
            $_SESSION['success'] = "Invoice updated successfully!";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Failed to update invoice.";
        }
        header('Location: admin.php?page=invoices');
        exit;
    }

    // Delete Invoice
    if (isset($_POST['action']) && $_POST['action'] === 'delete_invoice') {
        $id = $_POST['invoice_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = "Invoice deleted successfully!";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Failed to delete invoice.";
        }
        header('Location: admin.php?page=invoices');
        exit;
    }

    // Send Invoice Notification
    if (isset($_POST['action']) && $_POST['action'] === 'send_invoice') {
        $invoice_id = $_POST['invoice_id'];
        try {
            $stmt = $pdo->prepare("
                SELECT i.*, s.full_name, s.email, s.student_id 
                FROM invoices i 
                JOIN students s ON i.student_id = s.id 
                WHERE i.id = ?
            ");
            $stmt->execute([$invoice_id]);
            $invoice = $stmt->fetch();

            // Mark as sent
            $stmt = $pdo->prepare("UPDATE invoices SET sent_date = NOW() WHERE id = ?");
            $stmt->execute([$invoice_id]);

            // In production, send actual email here
            // Example: mail($invoice['email'], "New Invoice - {$invoice['invoice_number']}", "Invoice details...");

            $_SESSION['success'] = "Invoice notification sent to {$invoice['email']}!";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Failed to send invoice.";
        }
        header('Location: admin.php?page=invoices');
        exit;
    }

    // ============ EXISTING ACTIONS ============

    // Enroll Student
    if (isset($_POST['action']) && $_POST['action'] === 'enroll_student') {
        $student_id = $_POST['student_id'];
        $class_id = $_POST['class_id'];
        $enrollment_date = $_POST['enrollment_date'];

        try {
            $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, class_id, enrollment_date) VALUES (?, ?, ?)");
            $stmt->execute([$student_id, $class_id, $enrollment_date]);
            $_SESSION['success'] = "Student enrolled successfully!";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Enrollment failed. Student may already be enrolled.";
        }
        header('Location: admin.php?page=students');
        exit;
    }

    // Verify Payment
    if (isset($_POST['action']) && $_POST['action'] === 'verify_payment') {
        $payment_id = $_POST['payment_id'];
        $status = $_POST['status'];
        $notes = $_POST['notes'];

        $stmt = $pdo->prepare("UPDATE payments SET verification_status = ?, admin_notes = ?, verified_by = ?, verified_date = NOW() WHERE id = ?");
        $stmt->execute([$status, $notes, $_SESSION['admin_id'], $payment_id]);
        $_SESSION['success'] = "Payment status updated!";
        header('Location: admin.php?page=payments');
        exit;
    }

    // Mark Attendance
    if (isset($_POST['action']) && $_POST['action'] === 'mark_attendance') {
        $student_id = $_POST['student_id'];
        $class_id = $_POST['class_id'];
        $attendance_date = $_POST['attendance_date'];
        $status = $_POST['status'];
        $notes = $_POST['notes'] ?? '';

        try {
            $stmt = $pdo->prepare("
                INSERT INTO attendance (student_id, class_id, attendance_date, status, marked_by, notes) 
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE status = ?, notes = ?
            ");
            $stmt->execute([$student_id, $class_id, $attendance_date, $status, $_SESSION['admin_id'], $notes, $status, $notes]);
            $_SESSION['success'] = "Attendance marked successfully!";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Failed to mark attendance.";
        }
        header('Location: admin.php?page=attendance');
        exit;
    }

    // Bulk Mark Attendance
    if (isset($_POST['action']) && $_POST['action'] === 'bulk_attendance') {
        $class_id = $_POST['class_id'];
        $attendance_date = $_POST['attendance_date'];
        $attendances = $_POST['attendance'] ?? [];

        foreach($attendances as $student_id => $status) {
            $stmt = $pdo->prepare("
                INSERT INTO attendance (student_id, class_id, attendance_date, status, marked_by) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE status = ?
            ");
            $stmt->execute([$student_id, $class_id, $attendance_date, $status, $_SESSION['admin_id'], $status]);
        }
        $_SESSION['success'] = "Bulk attendance marked successfully!";
        header('Location: admin.php?page=attendance');
        exit;
    }
}

// Admin Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

$page = $_GET['page'] ?? 'login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Admin Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #dc2626;
            --secondary: #991b1b;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            min-height: 100vh;
        }
        .main-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            margin: 30px auto;
            max-width: 1400px;
        }
        .sidebar {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: white;
            border-radius: 20px 0 0 20px;
            padding: 30px 0;
            min-height: 600px;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 30px;
            margin: 5px 0;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 4px solid var(--primary);
        }
        .content-area { padding: 40px; }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        .card-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 18px 25px;
            font-weight: 600;
        }
        .login-container {
            max-width: 450px;
            margin: 100px auto;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
        .stat-card {
            text-align: center;
            padding: 25px;
            border-radius: 15px;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        /* ============================================
       MOBILE OPTIMIZATION - ADD THIS
       ============================================ */

    /* Hamburger Menu Button (Hidden on Desktop) */
    .mobile-menu-btn {
        display: none;
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1001;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: white;
        border: none;
        border-radius: 10px;
        padding: 12px 15px;
        font-size: 20px;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    }

    .mobile-menu-btn:hover {
        transform: scale(1.05);
    }

    /* Mobile Overlay */
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 999;
    }

    .sidebar-overlay.active {
        display: block;
    }

    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        /* Show Hamburger Button */
        .mobile-menu-btn {
            display: block;
        }

        /* Make main container full width */
        .main-container {
            margin: 0;
            border-radius: 0;
            min-height: 100vh;
        }

        /* Hide sidebar by default */
        .sidebar {
            position: fixed;
            left: -100%;
            top: 0;
            height: 100vh;
            width: 280px;
            z-index: 1000;
            transition: left 0.3s ease;
            border-radius: 0;
            overflow-y: auto;
        }

        /* Show sidebar when active */
        .sidebar.active {
            left: 0;
        }

        /* Adjust content area for mobile */
        .content-area {
            padding: 70px 15px 20px 15px;
            margin-left: 0 !important;
        }

        /* Compact admin profile in sidebar */
        .sidebar .text-center {
            padding: 15px 10px 10px 10px;
        }

        .sidebar .text-center i {
            font-size: 2rem;
        }

        .sidebar .text-center h5 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .sidebar .text-center p {
            font-size: 12px;
        }

        /* Compact navigation links */
        .sidebar .nav-link {
            padding: 10px 20px;
            font-size: 14px;
        }

        .sidebar .nav-link i {
            font-size: 16px;
        }

        /* Make stat cards stack vertically */
        .stat-card {
            margin-bottom: 15px;
        }

        /* Smaller stat icons */
        .stat-icon {
            width: 50px;
            height: 50px;
            font-size: 20px;
            margin-right: 15px;
        }

        .stat-content h3 {
            font-size: 24px;
        }

        .stat-content p {
            font-size: 12px;
        }

        /* Make tables responsive */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Compact cards */
        .card {
            margin-bottom: 15px;
        }

        .card-header {
            padding: 12px 15px;
            font-size: 14px;
        }

        .card-body {
            padding: 15px;
        }

        /* Smaller buttons */
        .btn {
            padding: 8px 15px;
            font-size: 14px;
        }

        /* Page title */
        h3.mb-4 {
            font-size: 20px;
            margin-bottom: 15px !important;
        }

        /* Forms on mobile */
        .form-control, .form-select {
            font-size: 14px;
        }

        .modal-dialog {
            margin: 10px;
        }
    }

    @media (max-width: 480px) {
        /* Extra small screens */
        .sidebar {
            width: 250px;
        }

        .content-area {
            padding: 60px 10px 15px 10px;
        }

        h3.mb-4 {
            font-size: 18px;
        }

        .stat-content h3 {
            font-size: 20px;
        }

        .btn {
            padding: 6px 12px;
            font-size: 13px;
        }
    }
    </style>
</head>
<body>

<?php if ($page === 'login'): ?>
    <!-- Login Page -->
    <div class="login-container">
        <div class="login-card">
            <div class="text-center mb-4">
                <i class="fas fa-shield-alt fa-4x text-danger mb-3"></i>
                <h2>Admin Portal</h2>
                <p class="text-muted">Sign in to continue</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="admin_login">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-danger w-100">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
        </div>
    </div>

<?php else: ?>
    <?php requireAdminLogin(); ?>

    <!-- Main Layout -->
    <div class="main-container">
        <div class="row g-0">
            <!-- Sidebar -->
            <div class="col-md-3 sidebar">
                <div class="text-center mb-4 px-3">
                    <i class="fas fa-user-shield fa-3x mb-2"></i>
                    <h5><?php echo $_SESSION['admin_name']; ?></h5>
                    <p class="text-muted small"><?php echo ucfirst($_SESSION['admin_role']); ?></p>
                </div>

                <nav class="nav flex-column">
                    <a class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>" href="?page=dashboard">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a class="nav-link <?php echo $page === 'students' ? 'active' : ''; ?>" href="?page=students">
                        <i class="fas fa-users"></i> Students
                    </a>
                    <a class="nav-link <?php echo $page === 'classes' ? 'active' : ''; ?>" href="?page=classes">
                        <i class="fas fa-book"></i> Classes
                    </a>
                    <a class="nav-link <?php echo $page === 'invoices' ? 'active' : ''; ?>" href="?page=invoices">
                        <i class="fas fa-file-invoice"></i> Invoices
                    </a>
                    <a class="nav-link <?php echo $page === 'payments' ? 'active' : ''; ?>" href="?page=payments">
                        <i class="fas fa-credit-card"></i> Payments
                    </a>
                    <a class="nav-link <?php echo $page === 'attendance' ? 'active' : ''; ?>" href="?page=attendance">
                        <i class="fas fa-calendar-check"></i> Attendance
                    </a>
                    <hr class="text-white mx-3">
                    <a class="nav-link" href="?logout=1">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </nav>
            </div>

            <!-- Content -->
            <div class="col-md-9">
                <div class="content-area">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php
                    $page_file = "admin_pages/{$page}.php";
                    if (file_exists($page_file)) {
                        include $page_file;
                    } else {
                        echo '<div class="alert alert-warning">Page not found.</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ============================================
// MOBILE MENU OPTIMIZATION
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    // Create hamburger button
    const menuBtn = document.createElement('button');
    menuBtn.className = 'mobile-menu-btn';
    menuBtn.innerHTML = '<i class="fas fa-bars"></i>';
    document.body.appendChild(menuBtn);

    // Create overlay
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    // Get sidebar
    const sidebar = document.querySelector('.sidebar');

    // Toggle sidebar
    function toggleSidebar() {
        if (sidebar) {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');

            // Change icon
            if (sidebar.classList.contains('active')) {
                menuBtn.innerHTML = '<i class="fas fa-times"></i>';
            } else {
                menuBtn.innerHTML = '<i class="fas fa-bars"></i>';
            }
        }
    }

    // Event listeners
    menuBtn.addEventListener('click', toggleSidebar);
    overlay.addEventListener('click', toggleSidebar);

    // Close sidebar when clicking a link (on mobile)
    if (sidebar) {
        const navLinks = document.querySelectorAll('.sidebar .nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    setTimeout(toggleSidebar, 300);
                }
            });
        });
    }

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768 && sidebar) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            menuBtn.innerHTML = '<i class="fas fa-bars"></i>';
        }
    });
});
</script>
</body>
</html>