<?php
session_start();
require_once '../db_connect.php';

$page_title = 'Attendance';
$current_page = 'attendance';

$user_id = $_SESSION['user_id'];

// Get advisor's classroom
$classroom_query = $conn->prepare("SELECT id FROM classrooms WHERE advisor_id = ? LIMIT 1");
$classroom_query->bind_param("i", $user_id);
$classroom_query->execute();
$classroom_result = $classroom_query->get_result();
$classroom = $classroom_result->fetch_assoc();
$classroom_id = $classroom['id'] ?? null;

// Get today's attendance
$today = date('Y-m-d');
$attendance = null;
$total_attendance = 0;
if ($classroom_id) {
    $attendance = $conn->query("
        SELECT a.*, s.student_id, u.name as student_name 
        FROM attendance a 
        JOIN students s ON a.student_id = s.id 
        JOIN users u ON s.user_id = u.id
        WHERE s.classroom_id = $classroom_id AND a.date = '$today'
        ORDER BY a.time_in DESC
    ");
    $total_attendance = $attendance ? $attendance->num_rows : 0;
}

include '../includes/advisor_header.php';
?>

<!-- Attendance Records -->
<div class="content-card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-calendar-check"></i>
            Today's Attendance (<?php echo $today; ?>)
        </div>
        <div class="card-actions">
            <span class="badge" style="background: var(--success); color: white; padding: 8px 16px; font-size: 14px;">
                <?php echo $total_attendance; ?> Present
            </span>
        </div>
    </div>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Student Info</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Time In</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total_attendance > 0): ?>
                    <?php while ($row = $attendance->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 35px; height: 35px; border-radius: 50%; background: var(--primary-blue); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 12px;">
                                    <?php echo strtoupper(substr($row['student_name'], 0, 2)); ?>
                                </div>
                                <div>
                                    <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($row['student_name']); ?></div>
                                    <div style="font-size: 12px; color: var(--text-secondary);"><?php echo htmlspecialchars($row['student_id']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 12px; font-size: 14px;"><?php echo date('h:i A', strtotime($row['time_in'])); ?></td>
                        <td style="padding: 12px;">
                            <span style="padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; background: rgba(16, 185, 129, 0.1); color: var(--success);">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="padding: 40px; text-align: center; color: var(--text-secondary);">
                            <i class="fas fa-inbox" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 10px;"></i>
                            No attendance records for today
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/advisor_footer.php'; ?>
