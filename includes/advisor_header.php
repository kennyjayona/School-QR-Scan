<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'advisor') {
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
            <a href="dashboard_advisor.php" class="sidebar-logo">
                <div class="sidebar-logo-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="sidebar-logo-text">
                    <h3>Smart Classroom</h3>
                    <p>Advisor Portal</p>
                </div>
            </a>
        </div>

        <nav class="sidebar-menu">
            <div class="menu-section">
                <div class="menu-section-title">Overview</div>
                <a href="dashboard_advisor.php" class="menu-item <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Attendance</div>
                <a href="../attendance_scanner.php" class="menu-item <?php echo $current_page == 'attendance_scanner' ? 'active' : ''; ?>">
                    <i class="fas fa-qrcode"></i>
                    <span>QR Scanner</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">My Classrooms</div>
                <a href="my_classrooms.php" class="menu-item <?php echo $current_page == 'my_classrooms' ? 'active' : ''; ?>">
                    <i class="fas fa-chalkboard"></i>
                    <span>My Classrooms</span>
                </a>
                <a href="my_classroom.php" class="menu-item <?php echo $current_page == 'classroom' ? 'active' : ''; ?>">
                    <i class="fas fa-door-open"></i>
                    <span>Classroom Info</span>
                </a>
                <a href="students.php" class="menu-item <?php echo $current_page == 'students' ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate"></i>
                    <span>My Students</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Records</div>
                <a href="attendance.php" class="menu-item <?php echo $current_page == 'attendance' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>Attendance</span>
                </a>
                <a href="grades.php" class="menu-item <?php echo $current_page == 'grades' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Grades</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">QR Codes</div>
                <a href="../qr_generate.php" class="menu-item <?php echo $current_page == 'qr_generate' ? 'active' : ''; ?>">
                    <i class="fas fa-qrcode"></i>
                    <span>Generate QR</span>
                </a>
                <a href="../qr_bulk_generate.php" class="menu-item <?php echo $current_page == 'qr_bulk' ? 'active' : ''; ?>">
                    <i class="fas fa-th"></i>
                    <span>Bulk QR Generation</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Reports</div>
                <a href="analytics.php" class="menu-item <?php echo $current_page == 'analytics' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </a>
                <a href="reports.php" class="menu-item <?php echo $current_page == 'reports' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>Attendance Reports</span>
                </a>
                <a href="grades_report.php" class="menu-item <?php echo $current_page == 'grades_report' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Grade Reports</span>
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
                    <span class="notification-badge">0</span>
                </button>
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($name, 0, 2)); ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($name); ?></div>
                        <div class="user-role">Advisor</div>
                    </div>
                </div>
                <a href="../logout.php" class="btn btn-secondary">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </nav>

        <!-- Dashboard Content -->
        <div class="dashboard-container">
