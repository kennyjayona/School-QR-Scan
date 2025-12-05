<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'advisor') {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Classroom Subjects';
$current_page = 'my_classrooms';
$user_id = $_SESSION['user_id'];
$classroom_id = $_GET['classroom_id'] ?? 0;

// Verify classroom belongs to this advisor
$classroom_check = $conn->prepare("SELECT * FROM classrooms WHERE id = ? AND created_by = ?");
$classroom_check->bind_param("ii", $classroom_id, $user_id);
$classroom_check->execute();
$classroom = $classroom_check->get_result()->fetch_assoc();

if (!$classroom) {
    header('Location: my_classrooms.php');
    exit;
}

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $subject_id = intval($_POST['subject_id']);
        $teacher_id = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : null;
        $capacity = intval($_POST['capacity']);
        
        try {
            $stmt = $conn->prepare("INSERT INTO classroom_subjects (classroom_id, subject_id, teacher_id, capacity, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiii", $classroom_id, $subject_id, $teacher_id, $capacity, $user_id);
            $stmt->execute();
            
            $message = 'Subject added to classroom successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . htmlspecialchars($e->getMessage());
            $message_type = 'danger';
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id']);
        $teacher_id = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : null;
        $capacity = intval($_POST['capacity']);
        
        try {
            $stmt = $conn->prepare("UPDATE classroom_subjects SET teacher_id = ?, capacity = ? WHERE id = ? AND created_by = ?");
            $stmt->bind_param("iiii", $teacher_id, $capacity, $id, $user_id);
            $stmt->execute();
            
            $message = 'Subject updated successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . htmlspecialchars($e->getMessage());
            $message_type = 'danger';
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        
        try {
            $stmt = $conn->prepare("DELETE FROM classroom_subjects WHERE id = ? AND created_by = ?");
            $stmt->bind_param("ii", $id, $user_id);
            $stmt->execute();
            
            $message = 'Subject removed from classroom!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . htmlspecialchars($e->getMessage());
            $message_type = 'danger';
        }
    }
}

// Get classroom subjects with details
$subjects_query = "SELECT cs.*, s.name as subject_name, s.code as subject_code, 
    u.name as teacher_name,
    (SELECT COUNT(*) FROM subject_students ss WHERE ss.classroom_subject_id = cs.id) as enrolled_count
    FROM classroom_subjects cs
    JOIN subjects s ON cs.subject_id = s.id
    LEFT JOIN users u ON cs.teacher_id = u.id
    WHERE cs.classroom_id = ?
    ORDER BY s.name";
$stmt = $conn->prepare($subjects_query);
$stmt->bind_param("i", $classroom_id);
$stmt->execute();
$classroom_subjects = $stmt->get_result();

// Get available subjects
$all_subjects = $conn->query("SELECT * FROM subjects ORDER BY name");

// Get available teachers
$teachers = $conn->query("SELECT id, name FROM users WHERE role IN ('teacher', 'advisor') ORDER BY name");

include '../includes/advisor_header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Classroom Info Card -->
<div class="content-card" style="margin-bottom: 20px;">
    <div style="padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0; color: var(--primary-blue);">
                    <i class="fas fa-door-open"></i> <?php echo htmlspecialchars($classroom['classroom_name']); ?>
                </h3>
                <p style="margin: 5px 0 0 0; color: var(--text-secondary);">
                    Grade <?php echo $classroom['year_level']; ?> - Section <?php echo $classroom['section']; ?>
                    <?php if ($classroom['room_number']): ?>
                        | Room <?php echo $classroom['room_number']; ?>
                    <?php endif; ?>
                </p>
            </div>
            <a href="my_classrooms.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Classrooms
            </a>
        </div>
    </div>
</div>

<!-- Subjects List -->
<div class="content-card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-book"></i>
            Subjects (<?php echo $classroom_subjects->num_rows; ?>)
        </div>
        <div class="card-actions">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus"></i> Add Subject
            </button>
        </div>
    </div>
    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 12px;">Subject</th>
                    <th style="padding: 12px;">Teacher</th>
                    <th style="padding: 12px;">Capacity</th>
                    <th style="padding: 12px;">Enrolled</th>
                    <th style="padding: 12px;">Status</th>
                    <th style="padding: 12px; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($classroom_subjects->num_rows > 0): ?>
                    <?php while ($cs = $classroom_subjects->fetch_assoc()): ?>
                    <?php 
                        $percentage = $cs['capacity'] > 0 ? ($cs['enrolled_count'] / $cs['capacity']) * 100 : 0;
                        $status_color = $percentage >= 100 ? 'danger' : ($percentage >= 80 ? 'warning' : 'success');
                    ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px;">
                            <strong><?php echo htmlspecialchars($cs['subject_name']); ?></strong>
                            <br><small style="color: var(--text-secondary);"><?php echo htmlspecialchars($cs['subject_code']); ?></small>
                        </td>
                        <td style="padding: 12px;"><?php echo htmlspecialchars($cs['teacher_name'] ?? 'Not assigned'); ?></td>
                        <td style="padding: 12px;"><?php echo $cs['capacity']; ?> students</td>
                        <td style="padding: 12px;">
                            <strong><?php echo $cs['enrolled_count']; ?></strong> / <?php echo $cs['capacity']; ?>
                        </td>
                        <td style="padding: 12px;">
                            <span class="badge bg-<?php echo $status_color; ?>">
                                <?php echo round($percentage); ?>% Full
                            </span>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <a href="subject_students.php?cs_id=<?php echo $cs['id']; ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-users"></i> Students
                            </a>
                            <button type="button" class="btn btn-sm btn-primary" onclick='editSubject(<?php echo json_encode($cs); ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Remove this subject from classroom?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $cs['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="padding: 40px; text-align: center; color: var(--text-secondary);">
                            <i class="fas fa-inbox" style="font-size: 48px; opacity: 0.3;"></i>
                            <p>No subjects added yet. Click "Add Subject" to get started.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Subject Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--primary-blue); color: white;">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Add Subject to Classroom</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Subject *</label>
                        <select name="subject_id" class="form-control" required>
                            <option value="">Select Subject</option>
                            <?php 
                            $all_subjects->data_seek(0);
                            while ($subject = $all_subjects->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $subject['id']; ?>">
                                    <?php echo htmlspecialchars($subject['name']); ?> (<?php echo htmlspecialchars($subject['code']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign Teacher</label>
                        <select name="teacher_id" class="form-control">
                            <option value="">No teacher assigned</option>
                            <?php 
                            $teachers->data_seek(0);
                            while ($teacher = $teachers->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $teacher['id']; ?>">
                                    <?php echo htmlspecialchars($teacher['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Student Capacity *</label>
                        <input type="number" name="capacity" class="form-control" value="40" required min="1">
                        <small class="text-muted">Maximum number of students for this subject</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Subject Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--warning); color: white;">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Subject</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" id="edit_subject_name" class="form-control" readonly style="background: #f3f4f6;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign Teacher</label>
                        <select name="teacher_id" id="edit_teacher_id" class="form-control">
                            <option value="">No teacher assigned</option>
                            <?php 
                            $teachers->data_seek(0);
                            while ($teacher = $teachers->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $teacher['id']; ?>">
                                    <?php echo htmlspecialchars($teacher['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Student Capacity *</label>
                        <input type="number" name="capacity" id="edit_capacity" class="form-control" required min="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Update Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editSubject(cs) {
    document.getElementById('edit_id').value = cs.id;
    document.getElementById('edit_subject_name').value = cs.subject_name + ' (' + cs.subject_code + ')';
    document.getElementById('edit_teacher_id').value = cs.teacher_id || '';
    document.getElementById('edit_capacity').value = cs.capacity;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php include '../includes/advisor_footer.php'; ?>
