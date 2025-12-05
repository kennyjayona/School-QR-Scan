<?php
session_start();
require_once '../db_connect.php';

$page_title = 'Manage Grades';
$current_page = 'grades';

$message = '';
$message_type = '';

// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_grade') {
    $student_id = $_POST['student_id'];
    $subject = trim($_POST['subject']);
    $grade = floatval($_POST['grade']);
    $term = trim($_POST['term']);
    $remarks = trim($_POST['remarks']);
    
    try {
        $stmt = $conn->prepare("INSERT INTO grades (student_id, subject, grade, term, remarks) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdss", $student_id, $subject, $grade, $term, $remarks);
        $stmt->execute();
        $message = 'Grade added successfully!';
        $message_type = 'success';
    } catch (Exception $e) {
        $message = 'Error adding grade: ' . htmlspecialchars($e->getMessage());
        $message_type = 'danger';
    }
}

// Get all students
$students = $conn->query("
    SELECT s.student_id, s.name 
    FROM students s 
    ORDER BY s.name
");

// Get recent grades
$grades = $conn->query("
    SELECT g.*, s.student_id, s.name as student_name 
    FROM grades g 
    JOIN students s ON g.student_id = s.student_id 
    ORDER BY g.id DESC 
    LIMIT 20
");

include '../includes/teacher_header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert" style="border-radius: 10px;">
    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Grades Management -->
<div class="content-card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-chart-line"></i>
            Recent Grades
        </div>
        <div class="card-actions">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGradeModal">
                <i class="fas fa-plus"></i> Add Grade
            </button>
        </div>
    </div>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
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
                <?php if ($grades->num_rows > 0): ?>
                    <?php while ($row = $grades->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px;">
                            <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($row['student_name']); ?></div>
                            <div style="font-size: 12px; color: var(--text-secondary);"><?php echo htmlspecialchars($row['student_id']); ?></div>
                        </td>
                        <td style="padding: 12px; font-size: 14px;"><?php echo htmlspecialchars($row['subject']); ?></td>
                        <td style="padding: 12px; font-size: 16px; font-weight: 700; color: var(--primary-blue);"><?php echo number_format($row['grade'], 2); ?></td>
                        <td style="padding: 12px; font-size: 14px;"><?php echo htmlspecialchars($row['term']); ?></td>
                        <td style="padding: 12px; font-size: 14px;"><?php echo htmlspecialchars($row['remarks']); ?></td>
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

<!-- Add Grade Modal -->
<div class="modal fade" id="addGradeModal" tabindex="-1" aria-labelledby="addGradeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--primary-blue); color: white;">
                <h5 class="modal-title" id="addGradeModalLabel">
                    <i class="fas fa-plus-circle"></i> Add New Grade
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_grade">
                    <div class="mb-3">
                        <label class="form-label">Student <span class="text-danger">*</span></label>
                        <select name="student_id" class="form-control" required>
                            <option value="">Select Student</option>
                            <?php while ($student = $students->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($student['student_id']); ?>">
                                    <?php echo htmlspecialchars($student['name']) . ' (' . htmlspecialchars($student['student_id']) . ')'; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject <span class="text-danger">*</span></label>
                        <input type="text" name="subject" class="form-control" required placeholder="e.g., Mathematics">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Grade <span class="text-danger">*</span></label>
                        <input type="number" name="grade" class="form-control" required step="0.01" min="0" max="100" placeholder="0-100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Term <span class="text-danger">*</span></label>
                        <select name="term" class="form-control" required>
                            <option value="">Select Term</option>
                            <option value="1st Quarter">1st Quarter</option>
                            <option value="2nd Quarter">2nd Quarter</option>
                            <option value="3rd Quarter">3rd Quarter</option>
                            <option value="4th Quarter">4th Quarter</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Optional remarks"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Grade
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/teacher_footer.php'; ?>
