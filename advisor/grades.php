<?php
session_start();
require_once '../db_connect.php';

$page_title = 'Grades';
$current_page = 'grades';

$user_id = $_SESSION['user_id'];

// Get advisor's classroom
$classroom_query = $conn->prepare("SELECT id FROM classrooms WHERE advisor_id = ? LIMIT 1");
$classroom_query->bind_param("i", $user_id);
$classroom_query->execute();
$classroom_result = $classroom_query->get_result();
$classroom = $classroom_result->fetch_assoc();
$classroom_id = $classroom['id'] ?? null;

// Get grades for students in this classroom
$grades = null;
$total_grades = 0;
if ($classroom_id) {
    $grades = $conn->query("
        SELECT g.*, s.student_id, u.name as student_name 
        FROM grades g 
        JOIN students s ON g.student_id = s.id 
        JOIN users u ON s.user_id = u.id
        WHERE s.classroom_id = $classroom_id
        ORDER BY u.name, g.subject
        LIMIT 50
    ");
    $total_grades = $grades ? $grades->num_rows : 0;
}

include '../includes/advisor_header.php';
?>

<!-- Grades List -->
<div class="content-card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-chart-line"></i>
            Student Grades
        </div>
        <div class="card-actions">
            <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="🔍 Search..." style="width: 250px;">
        </div>
    </div>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;" id="gradesTable">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Student</th>
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
                        <td style="padding: 12px;">
                            <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($row['student_name']); ?></div>
                            <div style="font-size: 12px; color: var(--text-secondary);"><?php echo htmlspecialchars($row['student_id']); ?></div>
                        </td>
                        <td style="padding: 12px; font-size: 14px;"><?php echo htmlspecialchars($row['subject']); ?></td>
                        <td style="padding: 12px; font-size: 18px; font-weight: 700; color: var(--primary-blue);"><?php echo number_format($row['grade'], 2); ?></td>
                        <td style="padding: 12px; font-size: 14px;"><?php echo htmlspecialchars($row['term']); ?></td>
                        <td style="padding: 12px; font-size: 14px;"><?php echo htmlspecialchars($row['remarks'] ?? '-'); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="padding: 40px; text-align: center; color: var(--text-secondary);">
                            <i class="fas fa-inbox" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 10px;"></i>
                            No grades recorded yet
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('searchInput')?.addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase();
    const rows = document.querySelectorAll('#gradesTable tbody tr');
    rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(searchValue) ? '' : 'none';
    });
});
</script>

<?php include '../includes/advisor_footer.php'; ?>
