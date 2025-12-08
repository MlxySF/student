<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

// Helper function to check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Handle Admin Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
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
            } else {
                $stmt = $pdo->prepare("UPDATE invoices SET description = ?, amount = ?, due_date = ?, status = ? WHERE id = ?");
            }
            $stmt->execute([$description, $amount, $due_date, $status, $id]);
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

    // ============ ENROLLMENT ============

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

    // ============ PAYMENT VERIFICATION ============

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

    // ============ ATTENDANCE ============

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
            padding-top: 0;
        }

        /* ============================================
           FIXED HEADER DESIGN - ADMIN
           ============================================ */

        /* Fixed Top Header */
        .top-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 70px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
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

        .sidebar {
            position: fixed;
            left: 0;
            top: 70px;
            width: 280px;
            height: calc(100vh - 70px);
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            z-index: 999;
            transition: left 0.3s ease;
            overflow-y: auto;
            padding: 20px 0;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 14px 25px;
            margin: 3px 0;
            border-radius: 0;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 4px solid #4f46e5;
        }

        .sidebar .nav-link i {
            width: 20px;
            font-size: 16px;
            text-align: center;
        }

        body.logged-in {
            padding-top: 70px;
        }

        .main-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            margin: 0;
            max-width: none;
        }

        .content-area {
            margin-left: 280px;
            padding: 30px;
            min-height: calc(100vh - 70px);
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
           MOBILE-RESPONSIVE TABLE DESIGN
           ============================================ */

        /* Hide table headers on mobile */
        @media (max-width: 768px) {
            .table-responsive table thead {
                display: none;
            }

            .table-responsive table,
            .table-responsive table tbody,
            .table-responsive table tr,
            .table-responsive table td {
                display: block;
                width: 100%;
            }

            .table-responsive table tr {
                background: white;
                border: 1px solid #dee2e6;
                border-radius: 10px;
                margin-bottom: 15px;
                padding: 15px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }

            .table-responsive table td {
                text-align: left !important;
                padding: 10px 0;
                border: none;
                position: relative;
                padding-left: 45%;
                min-height: 40px;
                display: flex;
                align-items: center;
            }

            .table-responsive table td:before {
                content: attr(data-label);
                position: absolute;
                left: 0;
                width: 40%;
                padding-right: 10px;
                font-weight: 600;
                color: #4f46e5;
                text-align: left;
                font-size: 13px;
            }

            .table-responsive table td:first-child {
                padding-top: 0;
            }

            .table-responsive table td:last-child {
                padding-bottom: 0;
                border-bottom: none;
            }

            /* Action buttons in mobile view */
            .table-responsive table td .btn {
                padding: 6px 12px;
                font-size: 12px;
                margin: 2px;
            }

            .table-responsive table td .btn-group {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
            }

            /* Badges in mobile view */
            .table-responsive table td .badge {
                display: inline-block;
                font-size: 11px;
                padding: 5px 10px;
            }

            /* Forms in tables (attendance, etc) */
            .table-responsive table td select,
            .table-responsive table td input {
                font-size: 13px;
                padding: 6px 10px;
            }
        }

        /* Desktop View */
        @media (min-width: 769px) {
            .header-menu-btn {
                display: none;
            }

            .sidebar {
                left: 0;
            }

            .sidebar-overlay {
                display: none !important;
            }
        }

        /* Mobile View */
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

            .sidebar {
                left: -280px;
            }

            .sidebar.active {
                left: 0;
            }

            .content-area {
                margin-left: 0;
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

            .card-header {
                padding: 12px 15px;
                font-size: 14px;
            }

            .card-body {
                padding: 15px;
            }

            .btn {
                padding: 8px 15px;
                font-size: 14px;
            }

            h3.mb-4 {
                font-size: 20px;
                margin-bottom: 15px !important;
            }

            .form-control, .form-select {
                font-size: 14px;
            }

            .modal-dialog {
                margin: 10px;
            }

            .table-responsive {
                overflow-x: visible;
                -webkit-overflow-scrolling: touch;
            }
        }

        @media (max-width: 480px) {
            .top-header {
                height: 60px;
            }

            body.logged-in {
                padding-top: 60px;
            }

            .sidebar {
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

            .btn {
                padding: 6px 12px;
                font-size: 13px;
            }

            /* Even more compact mobile tables */
            .table-responsive table td {
                padding: 8px 0;
                padding-left: 40%;
                font-size: 13px;
            }

            .table-responsive table td:before {
                font-size: 12px;
                width: 38%;
            }

            .table-responsive table tr {
                padding: 12px;
                margin-bottom: 12px;
            }
        }
    </style>
</head>
<body<?php echo ($page !== 'login') ? ' class="logged-in"' : ''; ?>>

<?php if ($page === 'login'): ?>
    <!-- Login Page -->
    <div class="login-container animate__animated animate__fadeIn">
        <div class="login-card">
            <div class="text-center mb-4">
                <i class="fas fa-user-shield fa-4x text-primary mb-3"></i>
                <h2>Admin Portal</h2>
                <p class="text-muted">Sign in to continue</p>
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
    <?php if (!isAdminLoggedIn()): header('Location: admin.php'); exit; endif; ?>

    <!-- Fixed Top Header -->
    <div class="top-header">
        <!-- Left Side: Menu + Logo -->
        <div class="header-left">
            <!-- Hamburger Menu Button -->
            <button class="header-menu-btn" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>

            <!-- School Logo -->
            <a href="?page=dashboard" class="school-logo">
                <!-- Option 1: If you have a logo image, use this: -->
                <!-- <img src="assets/logo.png" alt="School Logo"> -->

                <!-- Option 2: Placeholder icon (current) -->
                <div class="logo-placeholder">
                    <i class="fas fa-graduation-cap"></i>
                </div>

                <div class="logo-text">
                    <span class="logo-title">Wushu Academy</span>
                    <span class="logo-subtitle">Admin Portal</span>
                </div>
            </a>
        </div>

        <!-- Right Side: User Info -->
        <div class="header-right">
            <div class="header-user">
                <div class="header-user-avatar">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="header-user-info">
                    <span class="header-user-name"><?php echo $_SESSION['admin_name']; ?></span>
                    <span class="header-user-role">Administrator</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar Navigation -->
    <div class="sidebar" id="sidebar">
        <nav class="nav flex-column">
            <a class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>" href="?page=dashboard">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
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
            <a class="nav-link <?php echo $page === 'payments' ? 'active' : ''; ?>" href="?page=payments">
                <i class="fas fa-credit-card"></i>
                <span>Payments</span>
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

    <!-- Content Area -->
    <div class="content-area">
        <h3 class="mb-4">
            <i class="fas fa-<?php 
                echo $page === 'dashboard' ? 'home' : 
                    ($page === 'students' ? 'users' :
                    ($page === 'classes' ? 'chalkboard-teacher' :
                    ($page === 'invoices' ? 'file-invoice-dollar' : 
                    ($page === 'payments' ? 'credit-card' : 'calendar-check')))); 
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
        // Include admin pages
        $pages_dir = 'admin_pages/';
        switch($page) {
            case 'dashboard':
                if (file_exists($pages_dir . 'dashboard.php')) {
                    include $pages_dir . 'dashboard.php';
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
    // MOBILE MENU TOGGLE
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        if (!menuToggle || !sidebar || !overlay) return;

        // Toggle sidebar
        function toggleSidebar() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');

            // Change icon
            const icon = menuToggle.querySelector('i');
            if (sidebar.classList.contains('active')) {
                icon.className = 'fas fa-times';
            } else {
                icon.className = 'fas fa-bars';
            }
        }

        // Event listeners
        menuToggle.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);

        // Close sidebar when clicking a link (mobile only)
        const navLinks = document.querySelectorAll('.sidebar .nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    setTimeout(toggleSidebar, 200);
                }
            });
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                const icon = menuToggle.querySelector('i');
                if (icon) icon.className = 'fas fa-bars';
            }
        });
    });
</script>
</body>
</html>