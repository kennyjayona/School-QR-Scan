<?php
session_start();
require_once '../db_connect.php';

$page_title = 'My Attendance';
$current_page = 'attendance';

$user_id = $_SESSION['user_id'];

// Get student record by matching username with qr_code
$username = $_SESSION['username'];
$student_query = $conn->prepare("SELECT student_id FROM students WHERE qr_code = ? LIMIT 1");
$student_query->bind_param("s", $username);
$student_query->execute();
$student_result = $student_query->get_result();
$student = $student_result->fetch_assoc();
$student_id = $student['student_id'] ?? null;

// Get attendance records
$attendance_records = [];
if ($student_id) {
    $attendance_records = $conn->query("
        SELECT * FROM attendance 
        WHERE student_id = '$student_id' 
        ORDER BY date DESC, time_in DESC 
        LIMIT 50
    ");
}

$total_records = $attendance_records ? $attendance_records->num_rows : 0;

include '../includes/student_header.php';
?>

<!-- Attendance Records -->
<div class="content-card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-calendar-check"></i>
            My Attendance Records
        </div>
        <div class="card-actions">
            <span class="badge" style="background: var(--info); color: white; padding: 8px 16px; font-size: 14px;">
                <?php echo $total_records; ?> Records
            </span>
        </div>
    </div>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Date</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Time In</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Status</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Subject</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total_records > 0): ?>
                    <?php while ($row = $attendance_records->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px; font-size: 14px; font-weight: 600;">
                            <?php echo date('M d, Y', strtotime($row['date'])); ?>
                        </td>
                        <td style="padding: 12px; font-size: 14px;">
                            <?php echo date('h:i A', strtotime($row['time_in'])); ?>
                        </td>
                        <td style="padding: 12px;">
                            <?php
                            $status_color = $row['status'] === 'Present' ? 'success' : 'warning';
                            $status_bg = $row['status'] === 'Present' ? '16, 185, 129' : '245, 158, 11';
                            ?>
                            <span style="padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; background: rgba(<?php echo $status_bg; ?>, 0.1); color: var(--<?php echo $status_color; ?>);">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </td>
                        <td style="padding: 12px; font-size: 14px; color: var(--text-secondary);">
                            <?php echo htmlspecialchars($row['subject'] ?? 'General'); ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="padding: 40px; text-align: center; color: var(--text-secondary);">
                            <i class="fas fa-inbox" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 10px;"></i>
                            No attendance records found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/student_footer.php'; ?>
