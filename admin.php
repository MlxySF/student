<?php
// admin.php - Modern Admin Panel with Stunning Design
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

function getStudentId() {
    return null;
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

$page = $_GET['page'] ?? 'login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - <?php echo SITE_NAME; ?></title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- Modern Admin CSS -->
    <link rel="stylesheet" href="assets/css/modern-admin.css">
</head>
<body>

<?php if ($page === 'login'): ?>
    <!-- MODERN LOGIN PAGE -->
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <h1 class="auth-title">Admin Portal</h1>
                <p class="auth-subtitle">Sign in to manage your academy</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert-modern alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="fade-in">
                <input type="hidden" name="action" value="admin_login">
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-user"></i> Username</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn-modern btn-primary w-100">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
        </div>
    </div>

<?php else: ?>
    <?php redirectIfNotAdmin(); ?>
    
    <!-- ANIMATED BACKGROUND -->
    <div class="bg-animated"></div>

    <!-- MODERN SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">Wushu Academy</div>
            <div class="sidebar-subtitle">Admin Portal</div>
        </div>
        
        <div class="sidebar-nav">
            <div class="nav-item">
                <a class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>" href="?page=dashboard">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link <?php echo $page === 'registrations' ? 'active' : ''; ?>" href="?page=registrations">
                    <i class="fas fa-user-plus"></i>
                    <span>New Registrations</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link <?php echo $page === 'students' ? 'active' : ''; ?>" href="?page=students">
                    <i class="fas fa-users"></i>
                    <span>Students</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link <?php echo $page === 'classes' ? 'active' : ''; ?>" href="?page=classes">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Classes</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link <?php echo $page === 'invoices' ? 'active' : ''; ?>" href="?page=invoices">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Invoices</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link <?php echo $page === 'attendance' ? 'active' : ''; ?>" href="?page=attendance">
                    <i class="fas fa-calendar-check"></i>
                    <span>Attendance</span>
                </a>
            </div>
            <hr style="border-color: rgba(255,255,255,0.1); margin: 20px 15px;">
            <div class="nav-item">
                <a class="nav-link" href="?logout=1">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- CONTENT HEADER -->
        <div class="content-header">
            <div>
                <h1><?php echo ucfirst($page); ?></h1>
                <p>Welcome back, <?php echo $_SESSION['admin_name']; ?>!</p>
            </div>
            <div class="user-profile" style="display: flex; align-items: center; gap: 15px;">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['admin_name'], 0, 1)); ?>
                </div>
                <div>
                    <div style="font-weight: 600; font-size: 14px;"><?php echo $_SESSION['admin_name']; ?></div>
                    <div style="font-size: 12px; color: #64748b;"><?php echo ucfirst($_SESSION['admin_role']); ?></div>
                </div>
            </div>
        </div>

        <!-- ALERTS -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-modern alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-modern alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
            </div>
        <?php endif; ?>

        <!-- PAGE CONTENT -->
        <?php
        $pages_dir = 'admin_pages/';
        $page_file = $pages_dir . $page . '.php';
        
        if (file_exists($page_file)) {
            include $page_file;
        } else {
            include $pages_dir . 'dashboard.php';
        }
        ?>
    </div>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    // Initialize DataTables with modern styling
    $(document).ready(function() {
        $('.data-table').DataTable({
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search..."
            }
        });
    });

    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        $('.alert-modern').fadeOut('slow');
    }, 5000);
</script>
</body>
</html>