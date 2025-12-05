<?php
require_once '../config.php';
require_once '../db_connect.php';

$page_title = 'Students';
$current_page = 'students';

$user_id = $_SESSION['user_id'];

// Get advisor's classroom
$classroom_query = $conn->prepare("SELECT id FROM classrooms WHERE advisor_id = ? LIMIT 1");
$classroom_query->bind_param("i", $user_id);
$classroom_query->execute();
$classroom_result = $classroom_query->get_result();
$classroom = $classroom_result->fetch_assoc();
$classroom_id = $classroom['id'] ?? null;

// Get students - for now show all students (can be filtered by section/year_level later)
$students = $conn->query("SELECT * FROM students ORDER BY name");
$total_students = $students ? $students->num_rows : 0;

include '../includes/advisor_header.php';
?>

<!-- Students List -->
<div class="content-card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-users"></i>
            My Students (<?php echo $total_students; ?>)
        </div>
        <div class="card-actions">
            <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="🔍 Search..." style="width: 250px;">
        </div>
    </div>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;" id="studentsTable">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Student Info</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Contact</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Section</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Year Level</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total_students > 0): ?>
                    <?php while ($student = $students->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 35px; height: 35px; border-radius: 50%; background: var(--primary-blue); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 12px;">
                                    <?php echo strtoupper(substr($student['name'], 0, 2)); ?>
                                </div>
                                <div>
                                    <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($student['name']); ?></div>
                                    <div style="font-size: 12px; color: var(--text-secondary);"><?php echo htmlspecialchars($student['student_id']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 12px; font-size: 14px;"><?php echo htmlspecialchars($student['contact_number'] ?? 'N/A'); ?></td>
                        <td style="padding: 12px; font-size: 14px;"><?php echo htmlspecialchars($student['section'] ?? 'N/A'); ?></td>
                        <td style="padding: 12px; font-size: 14px;"><?php echo htmlspecialchars($student['year_level'] ?? 'N/A'); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="padding: 40px; text-align: center; color: var(--text-secondary);">
                            <i class="fas fa-inbox" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 10px;"></i>
                            No students found
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
    const rows = document.querySelectorAll('#studentsTable tbody tr');
    rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(searchValue) ? '' : 'none';
    });
});
</script>

<?php include '../includes/advisor_footer.php'; ?>
