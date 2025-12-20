<?php
// admin.php - Complete Admin Panel
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Redirect if not logged in
function redirectIfNotAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: admin.php?page=login');
        exit;
    }
}

// Safety stub function: This should NOT be used in admin context
// Defined here only to prevent "undefined function" errors if accidentally called
if (!function_exists('getStudentId')) {
    function getStudentId() {
        return null;
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

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Handle Invoice Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    redirectIfNotAdmin();
    
    // CREATE INVOICE
    if ($_POST['action'] === 'create_invoice') {
        $student_id = $_POST['student_id'];
        $invoice_type = $_POST['invoice_type'];
        $class_id = !empty($_POST['class_id']) ? $_POST['class_id'] : null;
        $description = $_POST['description'];
        $amount = $_POST['amount'];
        $due_date = $_POST['due_date'];
        
        // Generate invoice number
        $invoice_number = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Set payment_month for monthly_fee type
        $payment_month = null;
        if ($invoice_type === 'monthly_fee') {
            $payment_month = date('M Y'); // e.g., "Dec 2025"
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO invoices (student_id, invoice_number, invoice_type, class_id, description, amount, due_date, payment_month, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'unpaid', NOW())
        ");
        
        if ($stmt->execute([$student_id, $invoice_number, $invoice_type, $class_id, $description, $amount, $due_date, $payment_month])) {
            $_SESSION['success'] = "Invoice created successfully!";
        } else {
            $_SESSION['error'] = "Failed to create invoice.";
        }
        
        header('Location: admin.php?page=invoices');
        exit;
    }
    
    // GENERATE MONTHLY INVOICES
    if ($_POST['action'] === 'generate_monthly_invoices') {
        $current_month = date('M Y'); // e.g., "Dec 2025"
        $due_date = date('Y-m-10'); // 10th of current month
        $generated_count = 0;
        
        // Get all active enrollments
        $stmt = $pdo->query("
            SELECT DISTINCT e.student_id, e.class_id, c.monthly_fee, s.student_id as student_code, s.full_name
            FROM enrollments e
            JOIN students s ON e.student_id = s.id
            JOIN classes c ON e.class_id = c.id
            WHERE e.status = 'active' AND c.monthly_fee > 0
        ");
        $enrollments = $stmt->fetchAll();
        
        foreach ($enrollments as $enrollment) {
            // Check if invoice already exists for this month
            $check_stmt = $pdo->prepare("
                SELECT id FROM invoices 
                WHERE student_id = ? 
                AND class_id = ? 
                AND invoice_type = 'monthly_fee' 
                AND payment_month = ?
            ");
            $check_stmt->execute([$enrollment['student_id'], $enrollment['class_id'], $current_month]);
            
            if ($check_stmt->rowCount() == 0) {
                // Generate invoice number
                $invoice_number = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                $description = "Monthly Fee - $current_month";
                
                $insert_stmt = $pdo->prepare("
                    INSERT INTO invoices (student_id, invoice_number, invoice_type, class_id, description, amount, due_date, payment_month, status, created_at) 
                    VALUES (?, ?, 'monthly_fee', ?, ?, ?, ?, ?, 'unpaid', NOW())
                ");
                
                if ($insert_stmt->execute([
                    $enrollment['student_id'],
                    $invoice_number,
                    $enrollment['class_id'],
                    $description,
                    $enrollment['monthly_fee'],
                    $due_date,
                    $current_month
                ])) {
                    $generated_count++;
                }
            }
        }
        
        $_SESSION['success'] = "Generated $generated_count monthly invoices for $current_month!";
        header('Location: admin.php?page=invoices');
        exit;
    }
    
    // EDIT INVOICE
    if ($_POST['action'] === 'edit_invoice') {
        $invoice_id = $_POST['invoice_id'];
        $description = $_POST['description'];
        $amount = $_POST['amount'];
        $due_date = $_POST['due_date'];
        $status = $_POST['status'];
        
        $stmt = $pdo->prepare("
            UPDATE invoices 
            SET description = ?, amount = ?, due_date = ?, status = ? 
            WHERE id = ?
        ");
        
        if ($stmt->execute([$description, $amount, $due_date, $status, $invoice_id])) {
            $_SESSION['success'] = "Invoice updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update invoice.";
        }
        
        header('Location: admin.php?page=invoices');
        exit;
    }
    
    // DELETE INVOICE
    if ($_POST['action'] === 'delete_invoice') {
        $invoice_id = $_POST['invoice_id'];
        
        $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
        
        if ($stmt->execute([$invoice_id])) {
            $_SESSION['success'] = "Invoice deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete invoice.";
        }
        
        header('Location: admin.php?page=invoices');
        exit;
    }
    
    // VERIFY PAYMENT
    if ($_POST['action'] === 'verify_payment') {
        $payment_id = $_POST['payment_id'];
        $invoice_id = $_POST['invoice_id'];
        $verification_status = $_POST['verification_status'];
        $admin_notes = $_POST['admin_notes'] ?? '';
        
        // Update payment verification
        $stmt = $pdo->prepare("
            UPDATE payments 
            SET verification_status = ?, admin_notes = ? 
            WHERE id = ?
        ");
        $stmt->execute([$verification_status, $admin_notes, $payment_id]);
        
        // If verified, update invoice status to paid
        if ($verification_status === 'verified') {
            $update_invoice = $pdo->prepare("
                UPDATE invoices 
                SET status = 'paid', paid_date = NOW() 
                WHERE id = ?
            ");
            $update_invoice->execute([$invoice_id]);
            $_SESSION['success'] = "Payment verified and invoice marked as PAID!";
        } else {
            // If rejected, set invoice back to unpaid
            $update_invoice = $pdo->prepare("
                UPDATE invoices 
                SET status = 'unpaid' 
                WHERE id = ?
            ");
            $update_invoice->execute([$invoice_id]);
            $_SESSION['success'] = "Payment rejected!";
        }
        
        header('Location: admin.php?page=invoices');
        exit;
    }
}

$page = $_GET['page'] ?? 'login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #7c3aed;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #06b6d4;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
        }

        /* Fixed Top Header */
        .top-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 70px;
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-menu-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 45px;
            height: 45px;
            border-radius: 10px;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .header-menu-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.05);
        }

        .school-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            text-decoration: none;
        }

        .school-logo img {
            height: 45px;
            width: auto;
            border-radius: 8px;
        }

        .school-logo .logo-placeholder {
            width: 45px;
            height: 45px;
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .school-logo .logo-text {
            display: flex;
            flex-direction: column;
        }

        .school-logo .logo-title {
            font-size: 18px;
            font-weight: 700;
            line-height: 1.2;
        }

        .school-logo .logo-subtitle {
            font-size: 12px;
            opacity: 0.9;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-user {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
        }

        .header-user-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 700;
        }

        .header-user-info {
            display: flex;
            flex-direction: column;
        }

        .header-user-name {
            font-size: 14px;
            font-weight: 600;
            line-height: 1.2;
        }

        .header-user-role {
            font-size: 11px;
            opacity: 0.9;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 70px;
            left: 0;
            width: 100%;
            height: calc(100vh - 70px);
            background: rgba(0,0,0,0.5);
            z-index: 998;
        }

        .sidebar-overlay.active {
            display: block;
        }

        .admin-sidebar {
            position: fixed;
            left: 0;
            top: 70px;
            width: 280px;
            height: calc(100vh - 70px);
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            overflow-y: auto;
            z-index: 999;
            transition: left 0.3s ease;
            padding: 20px 0;
        }

        .admin-sidebar .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 14px 25px;
            margin: 3px 0;
            border-radius: 0;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 4px solid var(--primary-color);
        }

        .admin-sidebar .nav-link i {
            width: 20px;
            font-size: 16px;
            text-align: center;
        }

        body.logged-in {
            padding-top: 70px;
        }

        .admin-content {
            margin-left: 280px;
            min-height: calc(100vh - 70px);
        }

        .content-area {
            padding: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            transition: transform 0.3s;
            margin-bottom: 20px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 25px rgba(0,0,0,0.12);
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

        .stat-icon.bg-primary { background: linear-gradient(135deg, #2563eb, #1e40af); color: white; }
        .stat-icon.bg-success { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .stat-icon.bg-warning { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .stat-icon.bg-danger { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
        .stat-icon.bg-info { background: linear-gradient(135deg, #06b6d4, #0891b2); color: white; }

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

        .table {
            background: white;
        }

        .table thead {
            background: #f8fafc;
        }

        .badge {
            padding: 6px 12px;
            font-weight: 600;
            font-size: 11px;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .login-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 450px;
            width: 100%;
        }

        /* ✨ NEW: Reload Button Styles */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .btn-reload {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-reload:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
            color: white;
        }

        .btn-reload i {
            transition: transform 0.6s;
        }

        .btn-reload.loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .reload-toast {
            position: fixed;
            top: 90px;
            right: 20px;
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            z-index: 9999;
            display: none;
            align-items: center;
            gap: 10px;
        }

        .reload-toast.show {
            display: flex;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* ✨ NEW: Enhanced DataTables Pagination Styles */
        .dataTables_wrapper .dataTables_length {
            margin-bottom: 1rem;
        }

        .dataTables_wrapper .dataTables_length select {
            padding: 5px 10px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            min-width: 70px;
            margin: 0 5px;
        }

        .dataTables_wrapper .dataTables_info {
            color: #64748b;
            font-size: 14px;
        }

        .dataTables_wrapper .dataTables_paginate {
            margin-top: 1rem;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 6px 12px;
            margin: 0 2px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: white;
            color: #475569;
            transition: all 0.2s;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: white;
            border-color: #2563eb;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        @media (min-width: 769px) {
            .header-menu-btn {
                display: none;
            }

            .admin-sidebar {
                left: 0;
            }

            .sidebar-overlay {
                display: none !important;
            }
        }

        @media (max-width: 768px) {
            .top-header {
                padding: 0 15px;
            }

            .school-logo .logo-text {
                display: none;
            }

            .header-user-info {
                display: none;
            }

            .admin-sidebar {
                left: -280px;
            }

            .admin-sidebar.active {
                left: 0;
            }

            .admin-content {
                margin-left: 0;
            }

            .content-area {
                padding: 20px 15px;
            }

            .stat-card {
                margin-bottom: 15px;
            }

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

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .btn-reload {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .top-header {
                height: 60px;
            }

            body.logged-in {
                padding-top: 60px;
            }
            
            .admin-sidebar {
                top: 60px;
                height: calc(100vh - 60px);
            }

            .sidebar-overlay {
                top: 60px;
                height: calc(100vh - 60px);
            }

            .school-logo img,
            .school-logo .logo-placeholder {
                height: 38px;
                width: 38px;
            }

            .header-menu-btn {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }

            .header-user-avatar {
                width: 35px;
                height: 35px;
                font-size: 16px;
            }

            .content-area {
                padding: 15px 10px;
                min-height: calc(100vh - 60px);
            }

            h3.mb-4 {
                font-size: 18px;
            }

            .stat-content h3 {
                font-size: 20px;
            }

            .reload-toast {
                top: 70px;
                right: 10px;
                left: 10px;
                font-size: 14px;
            }
        }

        .badge-student {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: white;
        }

        .badge-state-team {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .badge-backup-team {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
    </style>
</head>
<body<?php echo ($page !== 'login') ? ' class="logged-in"' : ''; ?>>

<!-- ✨ NEW: Reload Toast Notification -->
<div class="reload-toast" id="reloadToast">
    <i class="fas fa-check-circle text-success"></i>
    <span>Data refreshed successfully!</span>
</div>

<?php if ($page === 'login'): ?>
    <div class="login-container">
        <div class="login-card">
            <div class="text-center mb-4">
                <i class="fas fa-shield-halved fa-4x text-primary mb-3"></i>
                <h2>Admin Portal</h2>
                <p class="text-muted">Sign in to your admin account</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="action" value="admin_login">
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-user"></i> Username</label>
                    <input type="text" name="username" class="form-control form-control-lg" required autofocus>
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
    <?php redirectIfNotAdmin(); ?>

    <div class="top-header">
        <div class="header-left">
            <button class="header-menu-btn" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>

            <a href="?page=dashboard" class="school-logo">
                <div class="logo-placeholder">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <div class="logo-text">
                    <span class="logo-title">Wushu Academy</span>
                    <span class="logo-subtitle">Admin Portal</span>
                </div>
            </a>
        </div>

        <div class="header-right">
            <div class="header-user">
                <div class="header-user-avatar">
                    <?php echo strtoupper(substr($_SESSION['admin_name'], 0, 1)); ?>
                </div>
                <div class="header-user-info">
                    <span class="header-user-name"><?php echo $_SESSION['admin_name']; ?></span>
                    <span class="header-user-role"><?php echo ucfirst($_SESSION['admin_role']); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="admin-sidebar" id="adminSidebar">
        <nav class="nav flex-column">
            <a class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>" href="?page=dashboard">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a class="nav-link <?php echo $page === 'registrations' ? 'active' : ''; ?>" href="?page=registrations">
                <i class="fas fa-user-plus"></i>
                <span>New Registrations</span>
            </a>
            <a class="nav-link <?php echo $page === 'students' ? 'active' : ''; ?>" href="?page=students">
                <i class="fas fa-users"></i>
                <span>Students</span>
            </a>
            <a class="nav-link <?php echo $page === 'classes' ? 'active' : ''; ?>" href="?page=classes">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Classes</span>
            </a>
            <a class="nav-link <?php echo $page === 'invoices' ? 'active' : ''; ?>" href="?page=invoices">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Invoices</span>
            </a>
            <a class="nav-link <?php echo $page === 'attendance' ? 'active' : ''; ?>" href="?page=attendance">
                <i class="fas fa-calendar-check"></i>
                <span>Attendance</span>
            </a>
            <hr class="text-white mx-3">
            <a class="nav-link" href="?logout=1">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </nav>
    </div>

    <div class="admin-content">
        <div class="content-area">
            <!-- ✨ NEW: Page Header with Reload Button -->
            <div class="page-header">
                <h3 class="mb-0">
                    <i class="fas fa-<?php 
                        echo $page === 'dashboard' ? 'home' : 
                            ($page === 'registrations' ? 'user-plus' :
                            ($page === 'students' ? 'users' : 
                            ($page === 'classes' ? 'chalkboard-teacher' : 
                            ($page === 'invoices' ? 'file-invoice-dollar' : 'calendar-check')))); 
                    ?>"></i>
                    <?php echo ucfirst($page); ?>
                </h3>
                <?php if ($page !== 'dashboard'): ?>
                <button class="btn btn-reload" onclick="reloadPageData()">
                    <i class="fas fa-sync-alt"></i>
                    <span>Refresh Data</span>
                </button>
                <?php endif; ?>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php
            $pages_dir = 'admin_pages/';
            switch($page) {
                case 'dashboard':
                    if (file_exists($pages_dir . 'dashboard.php')) {
                        include $pages_dir . 'dashboard.php';
                    }
                    break;
                case 'registrations':
                    if (file_exists($pages_dir . 'registrations.php')) {
                        include $pages_dir . 'registrations.php';
                    }
                    break;
                case 'students':
                    if (file_exists($pages_dir . 'students.php')) {
                        include $pages_dir . 'students.php';
                    }
                    break;
                case 'classes':
                    if (file_exists($pages_dir . 'classes.php')) {
                        include $pages_dir . 'classes.php';
                    }
                    break;
                case 'invoices':
                    if (file_exists($pages_dir . 'invoices.php')) {
                        include $pages_dir . 'invoices.php';
                    }
                    break;
                case 'payments':
                    if (file_exists($pages_dir . 'payments.php')) {
                        include $pages_dir . 'payments.php';
                    }
                    break;
                case 'attendance':
                    if (file_exists($pages_dir . 'attendance.php')) {
                        include $pages_dir . 'attendance.php';
                    }
                    break;
                default:
                    if (file_exists($pages_dir . 'dashboard.php')) {
                        include $pages_dir . 'dashboard.php';
                    }
            }
            ?>
        </div>
    </div>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    // ✨ NEW: Reload Page Data Function
    function reloadPageData() {
        const reloadBtn = document.querySelector('.btn-reload');
        const reloadIcon = reloadBtn.querySelector('i');
        const reloadToast = document.getElementById('reloadToast');
        
        // Add loading state
        reloadBtn.classList.add('loading');
        reloadBtn.disabled = true;
        
        // Get current URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const currentPage = urlParams.get('page') || 'dashboard';
        
        // Simulate data reload with a small delay for user feedback
        setTimeout(function() {
            // Reload the page without cache
            window.location.href = window.location.href.split('#')[0] + '&_t=' + new Date().getTime();
        }, 300);
        
        // Show toast notification
        setTimeout(function() {
            reloadToast.classList.add('show');
            setTimeout(function() {
                reloadToast.classList.remove('show');
            }, 2000);
        }, 400);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('adminSidebar');
        const overlay = document.getElementById('sidebarOverlay');

        if (!menuToggle || !sidebar || !overlay) return;

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            const icon = menuToggle.querySelector('i');
            if (sidebar.classList.contains('active')) {
                icon.className = 'fas fa-times';
            } else {
                icon.className = 'fas fa-bars';
            }
        }

        menuToggle.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);

        const navLinks = document.querySelectorAll('.admin-sidebar .nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    setTimeout(toggleSidebar, 200);
                }
            });
        });

        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                const icon = menuToggle.querySelector('i');
                if (icon) icon.className = 'fas fa-bars';
            }
        });
    });

    // ✨ ENHANCED: DataTables with customizable page length selector
    $(document).ready(function() {
        $('.data-table').DataTable({
            pageLength: 25,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                ['10 rows', '25 rows', '50 rows', '100 rows', 'Show all']
            ],
            order: [[0, 'desc']],
            language: {
                lengthMenu: '<i class="fas fa-list"></i> Display _MENU_ per page',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                infoEmpty: 'No entries available',
                infoFiltered: '(filtered from _MAX_ total entries)',
                search: '<i class="fas fa-search"></i>',
                searchPlaceholder: 'Search...',
                paginate: {
                    first: '<i class="fas fa-angle-double-left"></i>',
                    last: '<i class="fas fa-angle-double-right"></i>',
                    next: '<i class="fas fa-angle-right"></i>',
                    previous: '<i class="fas fa-angle-left"></i>'
                }
            },
            responsive: true
        });
    });
</script>
</body>
</html>