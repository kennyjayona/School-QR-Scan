<?php
session_start();
require_once '../db_connect.php';

$page_title = 'My Grades';
$current_page = 'grades';

$user_id = $_SESSION['user_id'];

// Get student record by matching username with qr_code
$username = $_SESSION['username'];
$student_query = $conn->prepare("SELECT student_id FROM students WHERE qr_code = ? LIMIT 1");
$student_query->bind_param("s", $username);
$student_query->execute();
$student_result = $student_query->get_result();
$student = $student_result->fetch_assoc();
$student_id = $student['student_id'] ?? null;

// Get grades
$grades = [];
$average_grade = 0;
if ($student_id) {
    $grades = $conn->query("
        SELECT * FROM grades 
        WHERE student_id = '$student_id' 
        ORDER BY term, subject
    ");
    
    $avg_result = $conn->query("SELECT AVG(grade) as average FROM grades WHERE student_id = '$student_id'");
    $average_grade = $avg_result->fetch_assoc()['average'] ?? 0;
}

$total_grades = $grades ? $grades->num_rows : 0;

include '../includes/student_header.php';
?>

<!-- Overall Average Card -->
<div class="content-card" style="background: linear-gradient(135deg, var(--success), #059669); color: white; border: none;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">Overall Average</div>
            <div style="font-size: 48px; font-weight: 700;"><?php echo $average_grade ? number_format($average_grade, 2) : 'N/A'; ?></div>
        </div>
        <div style="font-size: 64px; opacity: 0.2;">
            <i class="fas fa-star"></i>
        </div>
    </div>
</div>

<!-- Grades Table -->
<div class="content-card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-chart-line"></i>
            Grade Summary
        </div>
        <div class="card-actions">
            <span class="badge" style="background: var(--info); color: white; padding: 8px 16px; font-size: 14px;">
                <?php echo $total_grades; ?> Grades
            </span>
        </div>
    </div>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Subject</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Grade</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Term</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total_grades > 0): ?>
                    <?php while ($row = $grades->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px; font-size: 14px; font-weight: 600;">
                            <?php echo htmlspecialchars($row['subject']); ?>
                        </td>
                        <td style="padding: 12px; font-size: 18px; font-weight: 700; color: var(--primary-blue);">
                            <?php echo number_format($row['grade'], 2); ?>
                        </td>
                        <td style="padding: 12px; font-size: 14px;">
                            <?php echo htmlspecialchars($row['term']); ?>
                        </td>
                        <td style="padding: 12px; font-size: 14px; color: var(--text-secondary);">
                            <?php echo htmlspecialchars($row['remarks'] ?? '-'); ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="padding: 40px; text-align: center; color: var(--text-secondary);">
                            <i class="fas fa-inbox" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 10px;"></i>
                            No grades recorded yet
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/student_footer.php'; ?>
