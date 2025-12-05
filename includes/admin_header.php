<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$name = $_SESSION['name'] ?? $_SESSION['username'];
$user_id = $_SESSION['user_id'];
$page_title = $page_title ?? 'Dashboard';
$current_page = $current_page ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Smart Classroom</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/modern-dashboard.css" rel="stylesheet">
</head>

<body>
    <!-- Modern Sidebar -->
    <aside class="modern-sidebar">
        <div class="sidebar-header">
            <a href="dashboard_admin.php" class="sidebar-logo">
                <div class="sidebar-logo-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="sidebar-logo-text">
                    <h3>Smart Classroom</h3>
                    <p>Admin Portal</p>
                </div>
            </a>
        </div>

        <nav class="sidebar-menu">
            <div class="menu-section">
                <div class="menu-section-title">Overview</div>
                <a href="dashboard_admin.php" class="menu-item <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Attendance</div>
                <a href="../qr_scan_time_in.html" class="menu-item <?php echo $current_page == 'time_in' ? 'active' : ''; ?>">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>TIME IN</span>
                </a>
                <a href="../qr_scan_time_out.html" class="menu-item <?php echo $current_page == 'time_out' ? 'active' : ''; ?>">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>TIME OUT</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Management</div>
                <a href="manage_students.php" class="menu-item <?php echo $current_page == 'students' ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate"></i>
                    <span>Manage Students</span>
                </a>
                <a href="manage_teachers.php" class="menu-item <?php echo $current_page == 'teachers' ? 'active' : ''; ?>">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Manage Teachers</span>
                </a>
                <a href="manage_classrooms.php" class="menu-item <?php echo $current_page == 'classrooms' ? 'active' : ''; ?>">
                    <i class="fas fa-door-open"></i>
                    <span>Manage Classrooms</span>
                </a>
                <a href="manage_subjects.php" class="menu-item <?php echo $current_page == 'subjects' ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i>
                    <span>Manage Subjects</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Reports & Analytics</div>
                <a href="analytics.php" class="menu-item <?php echo $current_page == 'analytics' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </a>
                <a href="reports.php" class="menu-item <?php echo $current_page == 'reports' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>Reports</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Settings</div>
                <a href="../qr_generate.php" class="menu-item <?php echo $current_page == 'qr_generate' ? 'active' : ''; ?>">
                    <i class="fas fa-qrcode"></i>
                    <span>Generate QR</span>
                </a>
                <a href="user_management.php" class="menu-item <?php echo $current_page == 'users' ? 'active' : ''; ?>">
                    <i class="fas fa-user-shield"></i>
                    <span>User Management</span>
                </a>
            </div>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Navigation -->
        <nav class="top-navbar">
            <div class="navbar-title">
                <h1><?php echo $page_title; ?></h1>
            </div>
            <div class="navbar-actions">
                <button class="theme-toggle-btn" id="themeToggle">
                    <i class="fas fa-moon"></i>
                </button>
                <button class="notification-btn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </button>
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($name, 0, 2)); ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($name); ?></div>
                        <div class="user-role">Administrator</div>
                    </div>
                </div>
                <a href="../logout.php" class="btn btn-secondary">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </nav>

        <!-- Dashboard Content -->
        <div class="dashboard-container">