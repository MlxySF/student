<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

// ============================================================
// HELPER FUNCTIONS
// ============================================================

// Check if student is logged in and redirect if not
function redirectIfNotLoggedIn() {
    if (!isset($_SESSION['student_id'])) {
        header('Location: index.php?page=login');
        exit;
    }
}

// Get current logged-in student ID
function getStudentId() {
    return $_SESSION['student_id'] ?? null;
}

// Validate receipt file upload
function validateReceiptFile($file) {
    $result = ['valid' => false, 'error' => ''];

    // Check file size (max 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB in bytes
    if ($file['size'] > $maxSize) {
        $result['error'] = 'File size must be less than 5MB.';
        return $result;
    }

    // Check file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        $result['error'] = 'Only JPG, PNG, and PDF files are allowed.';
        return $result;
    }

    $result['valid'] = true;
    return $result;
}

// Convert file to base64 for database storage
function fileToBase64($file) {
    if (!file_exists($file['tmp_name'])) {
        return false;
    }

    $fileData = file_get_contents($file['tmp_name']);
    if ($fileData === false) {
        return false;
    }

    $base64 = base64_encode($fileData);

    // Get MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    return [
        'data' => $base64,
        'mime' => $mimeType,
        'size' => $file['size']
    ];
}

// ============================================================
// END HELPER FUNCTIONS
// ============================================================


// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ?");
    $stmt->execute([$email]);
    $student = $stmt->fetch();

    if ($student && password_verify($password, $student['password'])) {
        $_SESSION['student_id'] = $student['id'];
        $_SESSION['student_name'] = $student['full_name'];
        header('Location: index.php?page=dashboard');
        exit;
    } else {
        $_SESSION['error'] = "Invalid email or password.";
    }
}

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    redirectIfNotLoggedIn();

    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    // Check if email is already used by another student
    $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
    $stmt->execute([$email, getStudentId()]);

    if ($stmt->fetch()) {
        $_SESSION['error'] = "Email is already used by another student.";
    } else {
        $stmt = $pdo->prepare("UPDATE students SET full_name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->execute([$full_name, $email, $phone, getStudentId()]);

        $_SESSION['student_name'] = $full_name;
        $_SESSION['success'] = "Profile updated successfully!";
    }

    header('Location: index.php?page=profile');
    exit;
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    redirectIfNotLoggedIn();

    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Get current student
    $stmt = $pdo->prepare("SELECT password FROM students WHERE id = ?");
    $stmt->execute([getStudentId()]);
    $student = $stmt->fetch();

    if (!password_verify($current_password, $student['password'])) {
        $_SESSION['error'] = "Current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $_SESSION['error'] = "Password must be at least 6 characters.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE students SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, getStudentId()]);

        $_SESSION['success'] = "Password changed successfully!";
    }

    header('Location: index.php?page=profile');
    exit;
}

// Handle Payment Upload - BASE64 STORAGE VERSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_payment') {
    redirectIfNotLoggedIn();

    // Determine payment type and get appropriate values
    $invoice_id = !empty($_POST['invoice_id']) ? $_POST['invoice_id'] : null;

    if ($invoice_id) {
        // Invoice Payment
        $class_id = !empty($_POST['invoice_class_id']) ? $_POST['invoice_class_id'] : null;
        $amount = $_POST['invoice_amount'];
        $payment_month = $_POST['invoice_payment_month'];
        $notes = $_POST['notes'] ?? '';

        // If no class_id from invoice, get first enrolled class
        if (!$class_id) {
            $stmt = $pdo->prepare("SELECT class_id FROM enrollments WHERE student_id = ? AND status = 'active' LIMIT 1");
            $stmt->execute([getStudentId()]);
            $enrollment = $stmt->fetch();
            $class_id = $enrollment ? $enrollment['class_id'] : null;
        }

        if (!$class_id) {
            $_SESSION['error'] = "Unable to process invoice payment. Please ensure you're enrolled in at least one class.";
            header('Location: index.php?page=payments');
            exit;
        }

    } else {
        // Class Payment
        $class_id = $_POST['class_id'];
        $amount = $_POST['amount'];
        $payment_month = $_POST['payment_month'];
        $notes = $_POST['notes'] ?? '';
    }

    // Handle file upload - BASE64 VERSION
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === 0) {

        // Validate file
        $validation = validateReceiptFile($_FILES['receipt']);

        if (!$validation['valid']) {
            $_SESSION['error'] = $validation['error'];
            header('Location: index.php?page=payments');
            exit;
        }

        // Convert to base64
        $receiptData = fileToBase64($_FILES['receipt']);

        if ($receiptData === false) {
            $_SESSION['error'] = "Failed to process receipt file.";
            header('Location: index.php?page=payments');
            exit;
        }

        // Generate filename for reference (not actually saved to disk)
        $ext = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
        $filename = 'receipt_' . getStudentId() . '_' . time() . '.' . $ext;

        // Insert payment record with base64 data
        $stmt = $pdo->prepare("
            INSERT INTO payments (
                student_id, 
                class_id, 
                amount, 
                payment_month, 
                receipt_filename,
                receipt_data,
                receipt_mime_type,
                receipt_size,
                admin_notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $note_prefix = $invoice_id ? "Invoice Payment - Invoice ID: $invoice_id. " : "";
        $full_notes = $note_prefix . $notes;

        $success = $stmt->execute([
            getStudentId(), 
            $class_id, 
            $amount, 
            $payment_month, 
            $filename,                    // Reference filename (not actually saved)
            $receiptData['data'],         // Base64 encoded receipt
            $receiptData['mime'],         // MIME type (image/jpeg, application/pdf, etc)
            $receiptData['size'],         // Original file size in bytes
            $full_notes
        ]);

        if ($success) {
            $_SESSION['success'] = "Payment uploaded successfully! Waiting for admin verification.";

            if ($invoice_id) {
                $_SESSION['success'] .= " (Invoice payment recorded)";
            }
        } else {
            $_SESSION['error'] = "Failed to save payment record.";
        }

    } else {
        $_SESSION['error'] = "Please select a receipt file.";
    }

    header('Location: index.php?page=payments');
    exit;
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$page = $_GET['page'] ?? 'login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Student Portal</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #7c3aed;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            border-radius: 0;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 4px solid var(--primary-color);
        }

        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }

        .content-area {
            padding: 40px;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 18px 25px;
            font-weight: 600;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 25px rgba(0,0,0,0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-right: 20px;
        }

        .stat-icon.bg-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stat-icon.bg-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
        .stat-icon.bg-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }
        .stat-icon.bg-danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }
        .stat-icon.bg-info { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; }

        .stat-content h3 {
            margin: 0;
            font-size: 32px;
            font-weight: bold;
            color: #1e293b;
        }

        .stat-content p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }

        .badge-status {
            padding: 8px 15px;
            font-size: 12px;
            font-weight: 600;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
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

        /* ============================================
           MOBILE OPTIMIZATION CSS
           ============================================ */

        /* Hamburger Menu Button (Hidden on Desktop) */
        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1001;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
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

            /* Compact user profile in sidebar */
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
    <div class="login-container animate__animated animate__fadeIn">
        <div class="login-card">
            <div class="text-center mb-4">
                <i class="fas fa-graduation-cap fa-4x text-primary mb-3"></i>
                <h2>Student Portal</h2>
                <p class="text-muted">Sign in to your account</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="action" value="login">
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" class="form-control form-control-lg" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" class="form-control form-control-lg" required>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
        </div>
    </div>

<?php else: ?>
    <?php redirectIfNotLoggedIn(); ?>

    <!-- Dashboard Layout -->
    <div class="main-container">
        <div class="row g-0">
            <!-- Sidebar -->
            <div class="col-md-3 sidebar">
                <div class="text-center mb-4 px-3">
                    <i class="fas fa-user-circle fa-3x mb-2"></i>
                    <h5><?php echo $_SESSION['student_name']; ?></h5>
                    <p class="text-muted small mb-0">Student</p>
                </div>

                <nav class="nav flex-column">
                    <a class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>" href="?page=dashboard">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a class="nav-link <?php echo $page === 'invoices' ? 'active' : ''; ?>" href="?page=invoices">
                        <i class="fas fa-file-invoice-dollar"></i> My Invoices
                    </a>
                    <a class="nav-link <?php echo $page === 'payments' ? 'active' : ''; ?>" href="?page=payments">
                        <i class="fas fa-credit-card"></i> Payments
                    </a>
                    <a class="nav-link <?php echo $page === 'attendance' ? 'active' : ''; ?>" href="?page=attendance">
                        <i class="fas fa-calendar-check"></i> Attendance
                    </a>
                    <a class="nav-link <?php echo $page === 'profile' ? 'active' : ''; ?>" href="?page=profile">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <hr class="text-white mx-3">
                    <a class="nav-link" href="?logout=1">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </nav>
            </div>

            <!-- Content Area -->
            <div class="col-md-9">
                <div class="content-area">
                    <h3 class="mb-4">
                        <i class="fas fa-<?php 
                            echo $page === 'dashboard' ? 'home' : 
                                ($page === 'invoices' ? 'file-invoice-dollar' :
                                ($page === 'payments' ? 'credit-card' : 
                                ($page === 'attendance' ? 'calendar-check' : 'user'))); 
                        ?>"></i>
                        <?php echo ucfirst($page); ?>
                    </h3>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeInDown temp-alert">
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeInDown temp-alert">
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <?php
                    // Include pages
                    switch($page) {
                        case 'dashboard':
                            include 'pages/dashboard.php';
                            break;
                        case 'invoices':
                            include 'pages/invoices.php';
                            break;
                        case 'payments':
                            include 'pages/payments.php';
                            break;
                        case 'attendance':
                            include 'pages/attendance.php';
                            break;
                        case 'profile':
                            include 'pages/profile.php';
                            break;
                        default:
                            include 'pages/dashboard.php';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-dismiss ONLY temporary alerts
    setTimeout(() => {
        document.querySelectorAll('.temp-alert').forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 500);
        });
    }, 5000);

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