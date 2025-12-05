<?php
session_start();
require_once '../db_connect.php';

$page_title = 'Student Dashboard';
$current_page = 'dashboard';

$user_id = $_SESSION['user_id'];

// Get student record by matching username with qr_code
$username = $_SESSION['username'];
$student_query = $conn->prepare("SELECT s.* FROM students s WHERE s.qr_code = ? LIMIT 1");
$student_query->bind_param("s", $username);
$student_query->execute();
$student_result = $student_query->get_result();
$student = $student_result->fetch_assoc();
$student_id = $student['student_id'] ?? null;

// Get statistics
$total_attendance = 0;
$present_days = 0;
$total_subjects = 0;
$average_grade = 0;

if ($student_id) {
    $total_attendance = $conn->query("SELECT COUNT(*) as c FROM attendance WHERE student_id = '$student_id'")->fetch_assoc()['c'] ?? 0;
    $present_days = $conn->query("SELECT COUNT(*) as c FROM attendance WHERE student_id = '$student_id' AND status = 'Present'")->fetch_assoc()['c'] ?? 0;
    $total_subjects = $conn->query("SELECT COUNT(DISTINCT subject) as c FROM grades WHERE student_id = '$student_id'")->fetch_assoc()['c'] ?? 0;
    $average_grade = $conn->query("SELECT AVG(grade) as avg FROM grades WHERE student_id = '$student_id'")->fetch_assoc()['avg'] ?? 0;
}

$attendance_rate = $total_attendance > 0 ? round(($present_days / $total_attendance) * 100, 1) : 0;

include '../includes/student_header.php';
?>

<!-- Statistics Cards -->
<div class="metrics-row">
    <div class="metric-card info">
        <div class="metric-header">
            <div class="metric-icon blue">
                <i class="fas fa-percentage"></i>
            </div>
            <div class="metric-status online">Active</div>
        </div>
        <div class="metric-body">
            <h3><?php echo $attendance_rate; ?>%</h3>
            <div class="metric-label">Attendance Rate</div>
        </div>
        <div class="metric-footer">
            <i class="fas fa-info-circle"></i> Your attendance performance
        </div>
    </div>

    <div class="metric-card success">
        <div class="metric-header">
            <div class="metric-icon green">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="metric-status online">Present</div>
        </div>
        <div class="metric-body">
            <h3><?php echo $present_days; ?></h3>
            <div class="metric-label">Present Days</div>
        </div>
        <div class="metric-footer">
            <i class="fas fa-calendar-check"></i> Out of <?php echo $total_attendance; ?> days
        </div>
    </div>

    <div class="metric-card warning">
        <div class="metric-header">
            <div class="metric-icon yellow">
                <i class="fas fa-book"></i>
            </div>
            <div class="metric-status online">Enrolled</div>
        </div>
        <div class="metric-body">
            <h3><?php echo $total_subjects; ?></h3>
            <div class="metric-label">Subjects</div>
        </div>
        <div class="metric-footer">
            <i class="fas fa-graduation-cap"></i> Enrolled subjects
        </div>
    </div>

    <div class="metric-card danger">
        <div class="metric-header">
            <div class="metric-icon red">
                <i class="fas fa-star"></i>
            </div>
            <div class="metric-status online">Average</div>
        </div>
        <div class="metric-body">
            <h3><?php echo round($average_grade, 1); ?></h3>
            <div class="metric-label">Average Grade</div>
        </div>
        <div class="metric-footer">
            <i class="fas fa-chart-line"></i> Overall performance
        </div>
    </div>
</div>

<!-- Quick Links -->
<div class="content-card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-link"></i>
            Quick Links
        </div>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <a href="my_attendance.php" class="btn btn-primary" style="padding: 20px; text-align: center;">
            <i class="fas fa-calendar-check" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
            View My Attendance
        </a>
        <a href="my_grades.php" class="btn btn-success" style="padding: 20px; text-align: center;">
            <i class="fas fa-chart-line" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
            View My Grades
        </a>
    </div>
</div>

<!-- Student Information -->
<div class="content-card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-info-circle"></i>
            My Information
        </div>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
        <div>
            <p style="margin-bottom: 15px;">
                <strong style="color: var(--text-secondary); display: block; font-size: 12px; margin-bottom: 5px;">Full Name</strong>
                <span style="font-size: 16px;"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
            </p>
            <p style="margin-bottom: 15px;">
                <strong style="color: var(--text-secondary); display: block; font-size: 12px; margin-bottom: 5px;">Student ID</strong>
                <span style="font-size: 16px;"><?php echo htmlspecialchars($student['student_id'] ?? 'Not assigned'); ?></span>
            </p>
            <p style="margin-bottom: 15px;">
                <strong style="color: var(--text-secondary); display: block; font-size: 12px; margin-bottom: 5px;">Total Attendance Days</strong>
                <span style="font-size: 16px;"><?php echo $total_attendance; ?> days</span>
            </p>
        </div>
        <div>
            <p style="margin-bottom: 15px;">
                <strong style="color: var(--text-secondary); display: block; font-size: 12px; margin-bottom: 5px;">Present Days</strong>
                <span style="font-size: 16px;"><?php echo $present_days; ?> days</span>
            </p>
            <p style="margin-bottom: 15px;">
                <strong style="color: var(--text-secondary); display: block; font-size: 12px; margin-bottom: 5px;">Attendance Rate</strong>
                <span style="font-size: 16px; font-weight: 700; color: var(--success);"><?php echo $attendance_rate; ?>%</span>
            </p>
            <p style="margin-bottom: 15px;">
                <strong style="color: var(--text-secondary); display: block; font-size: 12px; margin-bottom: 5px;">Average Grade</strong>
                <span style="font-size: 16px; font-weight: 700; color: var(--primary-blue);"><?php echo round($average_grade, 2); ?></span>
            </p>
        </div>
    </div>
</div>

<?php include '../includes/student_footer.php'; ?>
