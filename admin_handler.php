<?php
session_start();
require_once 'config.php';

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Handle GET requests for data fetching
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    $action = $_GET['action'];
    
    if ($action === 'get_student_details') {
        // Check admin login for GET requests too
        if (!isAdminLoggedIn()) {
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        $student_id = $_GET['student_id'] ?? 0;
        
        try {
            // Get student enrollments with class details (removed day and time columns)
            $stmt = $pdo->prepare("
                SELECT 
                    e.id,
                    e.student_id,
                    e.class_id,
                    e.enrollment_date,
                    e.status,
                    c.class_name,
                    c.class_code,
                    c.monthly_fee,
                    c.description
                FROM enrollments e
                JOIN classes c ON e.class_id = c.id
                WHERE e.student_id = ?
                ORDER BY e.enrollment_date DESC
            ");
            $stmt->execute([$student_id]);
            $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['enrollments' => $enrollments]);
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// Check admin login for POST requests
if (!isAdminLoggedIn()) {
    header('Location: admin.php?page=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php');
    exit;
}

$action = $_POST['action'] ?? '';

// ============ REGISTRATION APPROVAL ============

if ($action === 'verify_registration') {
    $regId = $_POST['registration_id'];
    try {
        // Update registration status to verified
        $stmt = $pdo->prepare("UPDATE registrations SET payment_status = 'verified' WHERE id = ?");
        $stmt->execute([$regId]);
        
        // Check if any row was actually updated
        $rowCount = $stmt->rowCount();
        if ($rowCount > 0) {
            $_SESSION['success'] = "Registration payment verified successfully!";
        } else {
            $_SESSION['error'] = "Registration not found or already verified (ID: $regId, Rows affected: $rowCount)";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to verify registration: " . $e->getMessage();
    }
    header('Location: admin.php?page=registrations');
    exit;
}

if ($action === 'reject_registration') {
    $regId = $_POST['registration_id'];
    try {
        $stmt = $pdo->prepare("UPDATE registrations SET payment_status = 'rejected' WHERE id = ?");
        $stmt->execute([$regId]);
        
        $rowCount = $stmt->rowCount();
        if ($rowCount > 0) {
            $_SESSION['success'] = "Registration payment rejected.";
        } else {
            $_SESSION['error'] = "Registration not found (ID: $regId)";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to reject registration: " . $e->getMessage();
    }
    header('Location: admin.php?page=registrations');
    exit;
}

// ============ STUDENT MANAGEMENT ============

if ($action === 'create_student') {
    $student_id = 'STU' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO students (student_id, full_name, email, phone, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $full_name, $email, $phone, $password]);
        $_SESSION['success'] = "Student created! ID: $student_id";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Failed to create student.";
    }
    header('Location: admin.php?page=students');
    exit;
}

if ($action === 'edit_student') {
    $id = $_POST['student_id'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $student_status = $_POST['student_status'] ?? 'Student';

    // Check if email is already used by another student
    $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);

    if ($stmt->fetch()) {
        $_SESSION['error'] = "Email is already used by another student.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE students SET full_name = ?, email = ?, phone = ?, student_status = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $phone, $student_status, $id]);
            $_SESSION['success'] = "Student updated successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to update student: " . $e->getMessage();
        }
    }

    header('Location: admin.php?page=students');
    exit;
}

if ($action === 'delete_student') {
    $id = $_POST['student_id'];
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success'] = "Student deleted!";
    header('Location: admin.php?page=students');
    exit;
}

if ($action === 'enroll_student') {
    $student_id = $_POST['student_id'];
    $class_id = $_POST['class_id'];

    try {
        $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, class_id, enrollment_date, status) VALUES (?, ?, NOW(), 'active')");
        $stmt->execute([$student_id, $class_id]);
        $_SESSION['success'] = "Student enrolled!";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Already enrolled or error occurred.";
    }
    header('Location: admin.php?page=students');
    exit;
}

// ============ CLASS MANAGEMENT ============

if ($action === 'create_class') {
    $class_code = strtoupper($_POST['class_code']);
    $class_name = $_POST['class_name'];
    $monthly_fee = $_POST['monthly_fee'];
    $description = $_POST['description'] ?? '';

    try {
        $stmt = $pdo->prepare("INSERT INTO classes (class_code, class_name, monthly_fee, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$class_code, $class_name, $monthly_fee, $description]);
        $_SESSION['success'] = "Class created!";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Class code already exists.";
    }
    header('Location: admin.php?page=classes');
    exit;
}

if ($action === 'edit_class') {
    $id = $_POST['class_id'];
    $class_code = strtoupper($_POST['class_code']);
    $class_name = $_POST['class_name'];
    $monthly_fee = $_POST['monthly_fee'];
    $description = $_POST['description'] ?? '';

    $stmt = $pdo->prepare("UPDATE classes SET class_code = ?, class_name = ?, monthly_fee = ?, description = ? WHERE id = ?");
    $stmt->execute([$class_code, $class_name, $monthly_fee, $description, $id]);
    $_SESSION['success'] = "Class updated!";
    header('Location: admin.php?page=classes');
    exit;
}

if ($action === 'delete_class') {
    $id = $_POST['class_id'];
    $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success'] = "Class deleted!";
    header('Location: admin.php?page=classes');
    exit;
}

// ============ INVOICE MANAGEMENT ============

if ($action === 'create_invoice') {
    $student_id = $_POST['student_id'];
    $class_id = $_POST['class_id'] ?? null;
    $invoice_type = $_POST['invoice_type'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $due_date = $_POST['due_date'];
    $invoice_number = 'INV-' . date('Ym') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

    $stmt = $pdo->prepare("INSERT INTO invoices (invoice_number, student_id, class_id, invoice_type, description, amount, due_date, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'unpaid', NOW())");
    $stmt->execute([$invoice_number, $student_id, $class_id, $invoice_type, $description, $amount, $due_date]);
    $_SESSION['success'] = "Invoice created! #$invoice_number";
    header('Location: admin.php?page=invoices');
    exit;
}

if ($action === 'edit_invoice') {
    $id = $_POST['invoice_id'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $due_date = $_POST['due_date'];
    $status = $_POST['status'];

    if ($status === 'paid') {
        $stmt = $pdo->prepare("UPDATE invoices SET description = ?, amount = ?, due_date = ?, status = ?, paid_date = NOW() WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("UPDATE invoices SET description = ?, amount = ?, due_date = ?, status = ?, paid_date = NULL WHERE id = ?");
    }
    $stmt->execute([$description, $amount, $due_date, $status, $id]);
    $_SESSION['success'] = "Invoice updated!";
    header('Location: admin.php?page=invoices');
    exit;
}

if ($action === 'delete_invoice') {
    $id = $_POST['invoice_id'];
    $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success'] = "Invoice deleted!";
    header('Location: admin.php?page=invoices');
    exit;
}

// ============ PAYMENT VERIFICATION ============

if ($action === 'verify_payment') {
    $payment_id = $_POST['payment_id'];
    $verification_status = $_POST['verification_status'];
    $admin_notes = $_POST['admin_notes'] ?? '';

    $stmt = $pdo->prepare("UPDATE payments SET verification_status = ?, admin_notes = ?, verified_date = NOW() WHERE id = ?");
    $stmt->execute([$verification_status, $admin_notes, $payment_id]);
    $_SESSION['success'] = "Payment status updated!";
    header('Location: admin.php?page=payments');
    exit;
}

// ============ ATTENDANCE ============

if ($action === 'mark_attendance') {
    $student_id = $_POST['student_id'];
    $class_id = $_POST['class_id'];
    $attendance_date = $_POST['attendance_date'];
    $status = $_POST['status'];
    $notes = $_POST['notes'] ?? '';

    $stmt = $pdo->prepare("SELECT id FROM attendance WHERE student_id = ? AND class_id = ? AND attendance_date = ?");
    $stmt->execute([$student_id, $class_id, $attendance_date]);

    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("UPDATE attendance SET status = ?, notes = ? WHERE student_id = ? AND class_id = ? AND attendance_date = ?");
        $stmt->execute([$status, $notes, $student_id, $class_id, $attendance_date]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO attendance (student_id, class_id, attendance_date, status, notes, marked_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$student_id, $class_id, $attendance_date, $status, $notes]);
    }

    $_SESSION['success'] = "Attendance marked!";
    header('Location: admin.php?page=attendance');
    exit;
}

// Default redirect
header('Location: admin.php');
exit;
?>