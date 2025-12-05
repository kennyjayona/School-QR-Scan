<?php
session_start();
require_once '../db_connect.php';

$page_title = 'Advisor Dashboard';
$current_page = 'dashboard';

$user_id = $_SESSION['user_id'];

// Get advisor's classroom
$classroom_query = $conn->prepare("SELECT id, classroom_name FROM classrooms WHERE advisor_id = ? LIMIT 1");
$classroom_query->bind_param("i", $user_id);
$classroom_query->execute();
$classroom_result = $classroom_query->get_result();
$classroom = $classroom_result->fetch_assoc();
$classroom_id = $classroom['id'] ?? null;

// Get statistics
$total_students = 0;
$today_attendance = 0;
$total_subjects = 0;
$attendance_rate = 0;

if ($classroom_id) {
    // Count all students (can be filtered by section/year_level later)
    $total_students = $conn->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'] ?? 0;
    $today = date('Y-m-d');
    $today_attendance = $conn->query("SELECT COUNT(*) as c FROM school_attendance WHERE date = '$today' AND time_in IS NOT NULL")->fetch_assoc()['c'] ?? 0;
    $total_subjects = $conn->query("SELECT COUNT(*) as c FROM subjects WHERE classroom_id = $classroom_id")->fetch_assoc()['c'] ?? 0;
    $attendance_rate = $total_students > 0 ? round(($today_attendance / $total_students) * 100, 1) : 0;
}

include '../includes/advisor_header.php';
?>

<?php if (!$classroom): ?>
    <div class="alert alert-warning" style="border-radius: 10px;">
        <i class="fas fa-exclamation-triangle"></i> You are not assigned to any classroom yet. Please contact the administrator.
    </div>
<?php else: ?>

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
                <div class="metric-label">My Students</div>
            </div>
            <div class="metric-footer">
                <i class="fas fa-info-circle"></i> Total students in class
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
                <i class="fas fa-chart-line"></i> <?php echo $attendance_rate; ?>% attendance rate
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
                <h3><?php echo $total_subjects; ?></h3>
                <div class="metric-label">Subjects</div>
            </div>
            <div class="metric-footer">
                <i class="fas fa-graduation-cap"></i> Class subjects
            </div>
        </div>

        <div class="metric-card danger">
            <div class="metric-header">
                <div class="metric-icon red">
                    <i class="fas fa-door-open"></i>
                </div>
                <div class="metric-status online">Assigned</div>
            </div>
            <div class="metric-body">
                <h3 style="font-size: 20px;"><?php echo htmlspecialchars($classroom['classroom_name']); ?></h3>
                <div class="metric-label">My Classroom</div>
            </div>
            <div class="metric-footer">
                <i class="fas fa-chalkboard"></i> Class advisor
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

    <!-- Classroom Overview -->
    <div class="content-card">
        <div class="card-header">
            <div class="card-title">
                <i class="fas fa-info-circle"></i>
                Classroom Overview
            </div>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div>
                <p style="margin-bottom: 15px;">
                    <strong style="color: var(--text-secondary); display: block; font-size: 12px; margin-bottom: 5px;">Classroom</strong>
                    <span style="font-size: 18px; font-weight: 600;"><?php echo htmlspecialchars($classroom['classroom_name']); ?></span>
                </p>
                <p style="margin-bottom: 15px;">
                    <strong style="color: var(--text-secondary); display: block; font-size: 12px; margin-bottom: 5px;">Total Students</strong>
                    <span style="font-size: 18px; font-weight: 600;"><?php echo $total_students; ?></span>
                </p>
            </div>
            <div>
                <p style="margin-bottom: 15px;">
                    <strong style="color: var(--text-secondary); display: block; font-size: 12px; margin-bottom: 5px;">Subjects</strong>
                    <span style="font-size: 18px; font-weight: 600;"><?php echo $total_subjects; ?></span>
                </p>
                <p style="margin-bottom: 15px;">
                    <strong style="color: var(--text-secondary); display: block; font-size: 12px; margin-bottom: 5px;">Attendance Rate (Today)</strong>
                    <span style="font-size: 18px; font-weight: 600; color: var(--success);"><?php echo $attendance_rate; ?>%</span>
                </p>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php include '../includes/advisor_footer.php'; ?>