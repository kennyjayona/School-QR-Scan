<?php
require_once '../db_connect.php';

$page_title = 'My Subjects';
$current_page = 'subjects';

$user_id = $_SESSION['user_id'];

// Get subjects taught by this teacher
$subjects = $conn->query("SELECT * FROM subjects WHERE teacher_id = $user_id ORDER BY code");
$total_subjects = $subjects ? $subjects->num_rows : 0;

include '../includes/teacher_header.php';
?>

<!-- My Subjects -->
<div class="content-card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-book"></i>
            My Subjects (<?php echo $total_subjects; ?>)
        </div>
    </div>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Subject Code</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Subject Name</th>
                    <th style="padding: 12px; text-align: center; font-size: 13px; color: var(--text-secondary);">Units</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Year Level</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total_subjects > 0): ?>
                    <?php while ($subject = $subjects->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px;">
                            <span style="padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; background: rgba(59, 130, 246, 0.1); color: var(--info);">
                                <?php echo htmlspecialchars($subject['code']); ?>
                            </span>
                        </td>
                        <td style="padding: 12px; font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($subject['name']); ?></td>
                        <td style="padding: 12px; text-align: center; font-size: 14px;">-</td>
                        <td style="padding: 12px; font-size: 14px;">-</td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="padding: 40px; text-align: center; color: var(--text-secondary);">
                            <i class="fas fa-inbox" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 10px;"></i>
                            No subjects assigned yet
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/teacher_footer.php'; ?>
