<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'advisor') {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Subject Students';
$current_page = 'my_classrooms';
$user_id = $_SESSION['user_id'];
$cs_id = $_GET['cs_id'] ?? 0;

// Verify classroom subject belongs to this advisor
$cs_check = $conn->prepare("SELECT cs.*, s.name as subject_name, s.code as subject_code, 
    c.classroom_name, c.id as classroom_id, u.name as teacher_name
    FROM classroom_subjects cs
    JOIN subjects s ON cs.subject_id = s.id
    JOIN classrooms c ON cs.classroom_id = c.id
    LEFT JOIN users u ON cs.teacher_id = u.id
    WHERE cs.id = ? AND cs.created_by = ?");
$cs_check->bind_param("ii", $cs_id, $user_id);
$cs_check->execute();
$subject = $cs_check->get_result()->fetch_assoc();

if (!$subject) {
    header('Location: my_classrooms.php');
    exit;
}

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $student_id = trim($_POST['student_id']);
        
        try {
            $stmt = $conn->prepare("INSERT INTO subject_students (classroom_subject_id, student_id) VALUES (?, ?)");
            $stmt->bind_param("is", $cs_id, $student_id);
            $stmt->execute();
            
            $message = 'Student enrolled successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error: Student may already be enrolled.';
            $message_type = 'danger';
        }
    } elseif ($action === 'add_bulk') {
        $student_ids = $_POST['student_ids'] ?? [];
        $added = 0;
        
        foreach ($student_ids as $student_id) {
            try {
                $stmt = $conn->prepare("INSERT INTO subject_students (classroom_subject_id, student_id) VALUES (?, ?)");
                $stmt->bind_param("is", $cs_id, $student_id);
                $stmt->execute();
                $added++;
            } catch (Exception $e) {
                // Skip if already enrolled
            }
        }
        
        $message = "$added student(s) enrolled successfully!";
        $message_type = 'success';
    } elseif ($action === 'auto_add') {
        $limit = intval($_POST['limit']);
        
        // Get students not yet enrolled
        $available = $conn->query("SELECT student_id FROM students 
            WHERE student_id NOT IN (SELECT student_id FROM subject_students WHERE classroom_subject_id = $cs_id)
            LIMIT $limit");
        
        $added = 0;
        while ($row = $available->fetch_assoc()) {
            try {
                $stmt = $conn->prepare("INSERT INTO subject_students (classroom_subject_id, student_id) VALUES (?, ?)");
                $stmt->bind_param("is", $cs_id, $row['student_id']);
                $stmt->execute();
                $added++;
            } catch (Exception $e) {}
        }
        
        $message = "Auto-added $added student(s)!";
        $message_type = 'success';
    } elseif ($action === 'remove') {
        $id = $_POST['id'];
        
        try {
            $stmt = $conn->prepare("DELETE FROM subject_students WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            $message = 'Student removed from subject!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error removing student.';
            $message_type = 'danger';
        }
    }
}

// Get enrolled students
$enrolled = $conn->query("SELECT ss.*, s.name, s.student_id, s.section, s.year_level
    FROM subject_students ss
    JOIN students s ON ss.student_id = s.student_id
    WHERE ss.classroom_subject_id = $cs_id
    ORDER BY s.name");

$enrolled_count = $enrolled->num_rows;
$remaining = $subject['capacity'] - $enrolled_count;

// Get available students (not enrolled)
$available_students = $conn->query("SELECT * FROM students 
    WHERE student_id NOT IN (SELECT student_id FROM subject_students WHERE classroom_subject_id = $cs_id)
    ORDER BY name");

include '../includes/advisor_header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Subject Info Card -->
<div class="content-card" style="margin-bottom: 20px;">
    <div style="padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <div>
                <h3 style="margin: 0; color: var(--primary-blue);">
                    <i class="fas fa-book"></i> <?php echo htmlspecialchars($subject['subject_name']); ?>
                </h3>
                <p style="margin: 5px 0 0 0; color: var(--text-secondary);">
                    <?php echo htmlspecialchars($subject['classroom_name']); ?> | 
                    Teacher: <?php echo htmlspecialchars($subject['teacher_name'] ?? 'Not assigned'); ?>
                </p>
            </div>
            <a href="classroom_subjects.php?classroom_id=<?php echo $subject['classroom_id']; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Subjects
            </a>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div style="padding: 15px; background: <?php echo $enrolled_count >= $subject['capacity'] ? '#fee2e2' : '#dbeafe'; ?>; border-radius: 8px;">
                <div style="font-size: 24px; font-weight: 600; color: <?php echo $enrolled_count >= $subject['capacity'] ? '#ef4444' : '#2563eb'; ?>;">
                    <?php echo $enrolled_count; ?> / <?php echo $subject['capacity']; ?>
                </div>
                <div style="font-size: 12px; color: var(--text-secondary);">Students Enrolled</div>
            </div>
            <div style="padding: 15px; background: #d1fae5; border-radius: 8px;">
                <div style="font-size: 24px; font-weight: 600; color: #10b981;">
                    <?php echo max(0, $remaining); ?>
                </div>
                <div style="font-size: 12px; color: var(--text-secondary);">Slots Available</div>
            </div>
            <div style="padding: 15px; background: #fef3c7; border-radius: 8px;">
                <div style="font-size: 24px; font-weight: 600; color: #f59e0b;">
                    <?php echo $enrolled_count > 0 ? round(($enrolled_count / $subject['capacity']) * 100) : 0; ?>%
                </div>
                <div style="font-size: 12px; color: var(--text-secondary);">Capacity Used</div>
            </div>
        </div>
    </div>
</div>

<!-- Enrolled Students -->
<div class="content-card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-users"></i>
            Enrolled Students (<?php echo $enrolled_count; ?>)
        </div>
        <div class="card-actions" style="display: flex; gap: 10px;">
            <?php if ($remaining > 0): ?>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#autoAddModal">
                    <i class="fas fa-magic"></i> Auto-Add
                </button>
                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#bulkAddModal">
                    <i class="fas fa-users-plus"></i> Bulk Add
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus"></i> Add Student
                </button>
            <?php else: ?>
                <span class="badge bg-danger" style="padding: 8px 15px;">Capacity Full</span>
            <?php endif; ?>
        </div>
    </div>
    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 12px;">Student ID</th>
                    <th style="padding: 12px;">Name</th>
                    <th style="padding: 12px;">Year & Section</th>
                    <th style="padding: 12px;">Enrolled Date</th>
                    <th style="padding: 12px; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($enrolled_count > 0): ?>
                    <?php while ($student = $enrolled->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px;"><strong><?php echo htmlspecialchars($student['student_id']); ?></strong></td>
                        <td style="padding: 12px;"><?php echo htmlspecialchars($student['name']); ?></td>
                        <td style="padding: 12px;">Grade <?php echo htmlspecialchars($student['year_level']); ?> - <?php echo htmlspecialchars($student['section']); ?></td>
                        <td style="padding: 12px;"><?php echo date('M d, Y', strtotime($student['enrolled_at'])); ?></td>
                        <td style="padding: 12px; text-align: center;">
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Remove this student from subject?');">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-user-minus"></i> Remove
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="padding: 40px; text-align: center; color: var(--text-secondary);">
                            <i class="fas fa-inbox" style="font-size: 48px; opacity: 0.3;"></i>
                            <p>No students enrolled yet. Click "Add Student" to enroll students.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--primary-blue); color: white;">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> Enroll Student</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Select Student *</label>
                        <select name="student_id" class="form-control" required>
                            <option value="">Choose a student</option>
                            <?php while ($student = $available_students->fetch_assoc()): ?>
                                <option value="<?php echo $student['student_id']; ?>">
                                    <?php echo htmlspecialchars($student['name']); ?> (<?php echo $student['student_id']; ?>) - Grade <?php echo $student['year_level']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Enroll Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Add Modal -->
<div class="modal fade" id="bulkAddModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--info); color: white;">
                <h5 class="modal-title"><i class="fas fa-users-plus"></i> Bulk Enroll Students</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_bulk">
                    <p>Select multiple students to enroll (Max: <?php echo $remaining; ?> students)</p>
                    <div style="max-height: 400px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 8px; padding: 10px;">
                        <?php 
                        $available_students->data_seek(0);
                        while ($student = $available_students->fetch_assoc()): 
                        ?>
                            <div class="form-check" style="padding: 8px;">
                                <input class="form-check-input" type="checkbox" name="student_ids[]" value="<?php echo $student['student_id']; ?>" id="student_<?php echo $student['id']; ?>">
                                <label class="form-check-label" for="student_<?php echo $student['id']; ?>">
                                    <strong><?php echo htmlspecialchars($student['name']); ?></strong> (<?php echo $student['student_id']; ?>) - Grade <?php echo $student['year_level']; ?>-<?php echo $student['section']; ?>
                                </label>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Enroll Selected</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Auto Add Modal -->
<div class="modal fade" id="autoAddModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--success); color: white;">
                <h5 class="modal-title"><i class="fas fa-magic"></i> Auto-Add Students</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="auto_add">
                    <p>Automatically enroll students up to capacity.</p>
                    <div class="mb-3">
                        <label class="form-label">Number of Students to Add</label>
                        <input type="number" name="limit" class="form-control" value="<?php echo $remaining; ?>" max="<?php echo $remaining; ?>" min="1" required>
                        <small class="text-muted">Available slots: <?php echo $remaining; ?></small>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Students will be added automatically from the available pool.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Auto-Add Students</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include '../includes/advisor_footer.php'; ?>
