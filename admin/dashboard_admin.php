<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$name = $_SESSION['name'] ?? $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// Get statistics with error handling
try {
    $total_students = $conn->query('SELECT COUNT(*) AS c FROM students')->fetch_assoc()['c'] ?? 0;
} catch (Exception $e) {
    $total_students = 0;
}

try {
    $total_teachers = $conn->query('SELECT COUNT(*) AS c FROM users WHERE role = "teacher"')->fetch_assoc()['c'] ?? 0;
} catch (Exception $e) {
    $total_teachers = 0;
}

try {
    $total_advisors = $conn->query('SELECT COUNT(*) AS c FROM users WHERE role = "advisor"')->fetch_assoc()['c'] ?? 0;
} catch (Exception $e) {
    $total_advisors = 0;
}

// Check if school_attendance table exists
$today = date('Y-m-d');
$today_attendance = 0;
$on_time = 0;
$late = 0;

try {
    $result = $conn->query("SELECT COUNT(*) AS c FROM school_attendance WHERE date = CURDATE() AND time_in IS NOT NULL");
    if ($result) {
        $today_attendance = $result->fetch_assoc()['c'] ?? 0;
    }
} catch (Exception $e) {
    // Table doesn't exist, use regular attendance
    try {
        $result = $conn->query("SELECT COUNT(*) AS c FROM attendance WHERE date = CURDATE()");
        if ($result) {
            $today_attendance = $result->fetch_assoc()['c'] ?? 0;
        }
    } catch (Exception $e2) {
        $today_attendance = 0;
    }
}

try {
    $result = $conn->query("SELECT COUNT(*) AS c FROM school_attendance WHERE date = '$today' AND status = 'On Time'");
    if ($result) {
        $on_time = $result->fetch_assoc()['c'] ?? 0;
    }
} catch (Exception $e) {
    $on_time = 0;
}

try {
    $result = $conn->query("SELECT COUNT(*) AS c FROM school_attendance WHERE date = '$today' AND status = 'Late'");
    if ($result) {
        $late = $result->fetch_assoc()['c'] ?? 0;
    }
} catch (Exception $e) {
    $late = 0;
}

// Get attendance rate
$attendance_rate = $total_students > 0 ? round(($today_attendance / $total_students) * 100, 1) : 0;

// Get recent activities
try {
    $recent_activities = $conn->query("
        SELECT sa.*, s.student_id, u.name as student_name 
        FROM school_attendance sa
        JOIN students s ON sa.student_id = s.id
        JOIN users u ON s.user_id = u.id
        WHERE sa.date = CURDATE()
        ORDER BY sa.time_in DESC
        LIMIT 5
    ");
    if (!$recent_activities) {
        $recent_activities = $conn->query("SELECT * FROM students LIMIT 0"); // Empty result
    }
} catch (Exception $e) {
    $recent_activities = $conn->query("SELECT * FROM students LIMIT 0"); // Empty result
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Smart Classroom</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
                <a href="dashboard_admin.php" class="menu-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Attendance</div>
                <a href="../qr_scan_time_in.html" class="menu-item">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>TIME IN</span>
                </a>
                <a href="../qr_scan_time_out.html" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>TIME OUT</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Management</div>
                <a href="manage_students.php" class="menu-item">
                    <i class="fas fa-user-graduate"></i>
                    <span>Manage Students</span>
                </a>
                <a href="manage_teachers.php" class="menu-item">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Manage Teachers</span>
                </a>
                <a href="manage_classrooms.php" class="menu-item">
                    <i class="fas fa-door-open"></i>
                    <span>Manage Classrooms</span>
                </a>
                <a href="manage_subjects.php" class="menu-item">
                    <i class="fas fa-book"></i>
                    <span>Manage Subjects</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Reports & Analytics</div>
                <a href="analytics.php" class="menu-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </a>
                <a href="reports.php" class="menu-item">
                    <i class="fas fa-file-alt"></i>
                    <span>Reports</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Settings</div>
                <a href="../qr_generate.php" class="menu-item">
                    <i class="fas fa-qrcode"></i>
                    <span>Generate QR</span>
                </a>
                <a href="user_management.php" class="menu-item">
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
                <h1>Dashboard</h1>
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
            <!-- Metric Cards -->
            <div class="metrics-row">
                <div class="metric-card info">
                    <div class="metric-header">
                        <div class="metric-icon blue">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="metric-status online">Active</div>
                    </div>
                    <div class="metric-body">
                        <h3><?php echo $total_students; ?></h3>
                        <div class="metric-label">Total Students</div>
                    </div>
                    <div class="metric-footer">
                        <i class="fas fa-info-circle"></i> Enrolled this year
                    </div>
                </div>

                <div class="metric-card success">
                    <div class="metric-header">
                        <div class="metric-icon green">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="metric-status online">Today</div>
                    </div>
                    <div class="metric-body">
                        <h3><?php echo $today_attendance; ?></h3>
                        <div class="metric-label">Present Today</div>
                    </div>
                    <div class="metric-footer">
                        <i class="fas fa-chart-line"></i> <?php echo $attendance_rate; ?>% attendance rate
                    </div>
                </div>

                <div class="metric-card warning">
                    <div class="metric-header">
                        <div class="metric-icon yellow">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="metric-status online">Active</div>
                    </div>
                    <div class="metric-body">
                        <h3><?php echo $total_teachers; ?></h3>
                        <div class="metric-label">Total Teachers</div>
                    </div>
                    <div class="metric-footer">
                        <i class="fas fa-users"></i> Teaching staff
                    </div>
                </div>

                <div class="metric-card danger">
                    <div class="metric-header">
                        <div class="metric-icon red">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="metric-status offline">Alert</div>
                    </div>
                    <div class="metric-body">
                        <h3><?php echo $late; ?></h3>
                        <div class="metric-label">Late Today</div>
                    </div>
                    <div class="metric-footer">
                        <i class="fas fa-exclamation-triangle"></i> After 7:30 AM
                    </div>
                </div>
            </div>

            <!-- TIME IN / TIME OUT Quick Actions -->
            <div class="content-card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-bolt"></i>
                        Quick Actions - School Attendance
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <a href="../qr_scan_time_in.html" style="text-decoration: none;">
                        <div style="background: linear-gradient(135deg, #10b981, #059669); padding: 30px; border-radius: 12px; text-align: center; color: white; transition: transform 0.2s;">
                            <i class="fas fa-sign-in-alt" style="font-size: 48px; margin-bottom: 15px;"></i>
                            <h3 style="margin-bottom: 10px;">TIME IN</h3>
                            <p style="opacity: 0.9; margin: 0;">Student Arrival / School Entry</p>
                        </div>
                    </a>
                    <a href="../qr_scan_time_out.html" style="text-decoration: none;">
                        <div style="background: linear-gradient(135deg, #ef4444, #dc2626); padding: 30px; border-radius: 12px; text-align: center; color: white; transition: transform 0.2s;">
                            <i class="fas fa-sign-out-alt" style="font-size: 48px; margin-bottom: 15px;"></i>
                            <h3 style="margin-bottom: 10px;">TIME OUT</h3>
                            <p style="opacity: 0.9; margin: 0;">Student Dismissal / School Exit</p>
                        </div>
                    </a>
                </div>
                <div style="margin-top: 15px; padding: 12px; background: rgba(59, 130, 246, 0.1); border-radius: 8px; font-size: 13px; color: var(--info);">
                    <i class="fas fa-info-circle"></i> SMS notifications are sent to parents only for TIME IN and TIME OUT
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="content-card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-history"></i>
                        Recent Activity - Today's Attendance
                    </div>
                    <div class="card-actions">
                        <a href="reports.php" class="btn btn-secondary">
                            <i class="fas fa-eye"></i> View All
                        </a>
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border-color);">
                                <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Student</th>
                                <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Time In</th>
                                <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Time Out</th>
                                <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 12px;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="width: 35px; height: 35px; border-radius: 50%; background: var(--primary-blue); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 12px;">
                                                <?php echo strtoupper(substr($activity['student_name'], 0, 2)); ?>
                                            </div>
                                            <div>
                                                <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($activity['student_name']); ?></div>
                                                <div style="font-size: 12px; color: var(--text-secondary);"><?php echo htmlspecialchars($activity['student_id']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 12px; font-size: 14px;">
                                        <?php echo $activity['time_in'] ? date('h:i A', strtotime($activity['time_in'])) : '-'; ?>
                                    </td>
                                    <td style="padding: 12px; font-size: 14px;">
                                        <?php echo $activity['time_out'] ? date('h:i A', strtotime($activity['time_out'])) : '-'; ?>
                                    </td>
                                    <td style="padding: 12px;">
                                        <?php
                                        $status_class = $activity['status'] === 'On Time' ? 'success' : 'warning';
                                        $status_color = $activity['status'] === 'On Time' ? 'var(--success)' : 'var(--warning)';
                                        ?>
                                        <span style="padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; background: rgba(<?php echo $activity['status'] === 'On Time' ? '16, 185, 129' : '245, 158, 11'; ?>, 0.1); color: <?php echo $status_color; ?>;">
                                            <?php echo htmlspecialchars($activity['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const html = document.documentElement;

        // Load saved theme
        const savedTheme = localStorage.getItem('smart-classroom-theme') || 'light';
        html.classList.toggle('dark', savedTheme === 'dark');
        themeToggle.querySelector('i').className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';

        themeToggle.addEventListener('click', () => {
            const isDark = html.classList.toggle('dark');
            const newTheme = isDark ? 'dark' : 'light';
            localStorage.setItem('smart-classroom-theme', newTheme);
            themeToggle.querySelector('i').className = isDark ? 'fas fa-sun' : 'fas fa-moon';
        });
    </script>
</body>

</html>