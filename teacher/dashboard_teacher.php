<?php
session_start();
require_once '../db_connect.php';

$page_title = 'Teacher Dashboard';
$current_page = 'dashboard';

$user_id = $_SESSION['user_id'];

// Get statistics
$total_students = $conn->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'] ?? 0;
$today = date('Y-m-d');
$today_attendance = $conn->query("SELECT COUNT(*) as c FROM attendance WHERE date = '$today'")->fetch_assoc()['c'] ?? 0;

// Get subjects taught by this teacher
$subjects = $conn->query("SELECT COUNT(*) as c FROM subjects WHERE teacher_id = $user_id")->fetch_assoc()['c'] ?? 0;

// Get recent attendance
$recent_attendance = $conn->query("
    SELECT a.*, s.name as student_name 
    FROM attendance a 
    JOIN students s ON a.student_id = s.student_id 
    WHERE a.date = '$today' 
    ORDER BY a.time_in DESC 
    LIMIT 10
");

include '../includes/teacher_header.php';
?>

<!-- Statistics Cards -->
<div class="metrics-row">
    <div class="metric-card info">
        <div class="metric-header">
            <div class="metric-icon blue">
                <i class="fas fa-users"></i>
            </div>
            <div class="metric-status online">Active</div>
        </div>
        <div class="metric-body">
            <h3><?php echo $total_students; ?></h3>
            <div class="metric-label">Total Students</div>
        </div>
        <div class="metric-footer">
            <i class="fas fa-info-circle"></i> All enrolled students
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
            <div class="metric-label">Today's Attendance</div>
        </div>
        <div class="metric-footer">
            <i class="fas fa-chart-line"></i> Present today
        </div>
    </div>

    <div class="metric-card warning">
        <div class="metric-header">
            <div class="metric-icon yellow">
                <i class="fas fa-book"></i>
            </div>
            <div class="metric-status online">Active</div>
        </div>
        <div class="metric-body">
            <h3><?php echo $subjects; ?></h3>
            <div class="metric-label">My Subjects</div>
        </div>
        <div class="metric-footer">
            <i class="fas fa-chalkboard-teacher"></i> Teaching subjects
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="content-card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-bolt"></i>
            Quick Actions
        </div>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <a href="../qr_scan.html" class="btn btn-success" style="padding: 20px; text-align: center;">
            <i class="fas fa-qrcode" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
            Scan QR Code
        </a>
        <a href="attendance.php" class="btn btn-primary" style="padding: 20px; text-align: center;">
            <i class="fas fa-list" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
            View Attendance
        </a>
        <a href="grades.php" class="btn btn-secondary" style="padding: 20px; text-align: center;">
            <i class="fas fa-edit" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
            Enter Grades
        </a>
    </div>
</div>

<!-- Recent Attendance -->
<div class="content-card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-history"></i>
            Recent Attendance - Today
        </div>
        <div class="card-actions">
            <a href="attendance.php" class="btn btn-secondary">
                <i class="fas fa-eye"></i> View All
            </a>
        </div>
    </div>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Student Name</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Time In</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recent_attendance->num_rows > 0): ?>
                    <?php while ($row = $recent_attendance->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px; font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars($row['student_name']); ?></td>
                        <td style="padding: 12px; font-size: 14px;"><?php echo date('h:i A', strtotime($row['time_in'])); ?></td>
                        <td style="padding: 12px;">
                            <span style="padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; background: rgba(<?php echo $row['status'] === 'Present' ? '16, 185, 129' : '245, 158, 11'; ?>, 0.1); color: var(--<?php echo $row['status'] === 'Present' ? 'success' : 'warning'; ?>);">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="padding: 20px; text-align: center; color: var(--text-secondary);">No attendance records today</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/teacher_footer.php'; ?>
