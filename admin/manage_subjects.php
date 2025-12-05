<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'advisor'])) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Manage Subjects';
$current_page = 'subjects';

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $subject_code = trim($_POST['subject_code']);
        $subject_name = trim($_POST['subject_name']);
        
        try {
            $stmt = $conn->prepare("INSERT INTO subjects (code, name) VALUES (?, ?)");
            $stmt->bind_param("ss", $subject_code, $subject_name);
            $stmt->execute();
            
            $message = 'Subject added successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . htmlspecialchars($e->getMessage());
            $message_type = 'danger';
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'];
        $subject_code = trim($_POST['subject_code']);
        $subject_name = trim($_POST['subject_name']);
        
        try {
            $stmt = $conn->prepare("UPDATE subjects SET code = ?, name = ? WHERE id = ?");
            $stmt->bind_param("ssi", $subject_code, $subject_name, $id);
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
            $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            $message = 'Subject deleted successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error deleting subject.';
            $message_type = 'danger';
        }
    }
}

// Get all subjects with teacher and classroom info
$subjects = null;
$total_subjects = 0;

try {
    $subjects = $conn->query("
        SELECT s.*, 
               u.name as teacher_name,
               c.name as classroom_name
        FROM subjects s 
        LEFT JOIN users u ON s.teacher_id = u.id 
        LEFT JOIN classrooms c ON s.classroom_id = c.id 
        ORDER BY s.code
    ");
    if ($subjects) {
        $total_subjects = $subjects->num_rows;
    }
} catch (Exception $e) {
    // Fallback to simple query if JOIN fails
    try {
        $subjects = $conn->query("SELECT * FROM subjects ORDER BY code");
        if ($subjects) {
            $total_subjects = $subjects->num_rows;
        }
    } catch (Exception $e2) {
        // If still fails, set to null
        $subjects = null;
        $total_subjects = 0;
    }
}

// Include appropriate header based on role
if ($_SESSION['role'] === 'advisor') {
    include '../includes/advisor_header.php';
} else {
    include '../includes/admin_header.php';
}
?>

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Subjects List -->
<div class="content-card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-book"></i>
            Subjects List (<?php echo $total_subjects; ?>)
        </div>
        <div class="card-actions" style="display: flex; gap: 10px; align-items: center;">
            <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="🔍 Search subjects..." style="width: 250px;">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                <i class="fas fa-plus"></i> Add New Subject
            </button>
        </div>
    </div>
    <div style="overflow-x: auto;">
        <table class="table" id="subjectsTable">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Subject Code</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Subject Name</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Teacher</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Classroom</th>
                    <th style="padding: 12px; text-align: center; font-size: 13px; color: var(--text-secondary);">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($subjects && $total_subjects > 0): ?>
                    <?php while ($subject = $subjects->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px;">
                            <span style="padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; background: rgba(59, 130, 246, 0.1); color: var(--info);">
                                <?php echo htmlspecialchars($subject['code'] ?? 'N/A'); ?>
                            </span>
                        </td>
                        <td style="padding: 12px; font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($subject['name']); ?></td>
                        <td style="padding: 12px; font-size: 14px; color: var(--text-secondary);"><?php echo htmlspecialchars($subject['teacher_name'] ?? 'Not assigned'); ?></td>
                        <td style="padding: 12px; font-size: 14px; color: var(--text-secondary);"><?php echo htmlspecialchars($subject['classroom_name'] ?? 'Not assigned'); ?></td>
                        <td style="padding: 12px; text-align: center;">
                            <button type="button" class="btn btn-sm btn-primary me-1" onclick='editSubject(<?php echo json_encode($subject); ?>)'>
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this subject? This action cannot be undone.');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $subject['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="padding: 40px; text-align: center; color: var(--text-secondary);">
                            <i class="fas fa-inbox" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 10px;"></i>
                            No subjects found. Click "Add New Subject" to create one.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--info); color: white;">
                <h5 class="modal-title" id="addSubjectModalLabel">
                    <i class="fas fa-book"></i> Add New Subject
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Subject Code <span class="text-danger">*</span></label>
                            <input type="text" name="subject_code" class="form-control" required placeholder="e.g., MATH101">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Subject Name <span class="text-danger">*</span></label>
                            <input type="text" name="subject_name" class="form-control" required placeholder="e.g., Mathematics">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Subject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Subject Modal -->
<div class="modal fade" id="editSubjectModal" tabindex="-1" aria-labelledby="editSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--warning); color: white;">
                <h5 class="modal-title" id="editSubjectModalLabel">
                    <i class="fas fa-edit"></i> Edit Subject
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="editSubjectForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Subject Code <span class="text-danger">*</span></label>
                        <input type="text" name="subject_code" id="edit_subject_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject Name <span class="text-danger">*</span></label>
                        <input type="text" name="subject_name" id="edit_subject_name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Update Subject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase();
    const table = document.getElementById('subjectsTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    for (let row of rows) {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchValue) ? '' : 'none';
    }
});

// Edit subject function
function editSubject(subject) {
    document.getElementById('edit_id').value = subject.id;
    document.getElementById('edit_subject_code').value = subject.code;
    document.getElementById('edit_subject_name').value = subject.name;
    
    // Show modal
    new bootstrap.Modal(document.getElementById('editSubjectModal')).show();
}

// Auto-close modal on success and reload
<?php if ($message && $message_type === 'success'): ?>
const addModal = bootstrap.Modal.getInstance(document.getElementById('addSubjectModal'));
const editModal = bootstrap.Modal.getInstance(document.getElementById('editSubjectModal'));
if (addModal) addModal.hide();
if (editModal) editModal.hide();
setTimeout(function() {
    location.reload();
}, 500);
<?php endif; ?>
</script>

<?php 
// Include appropriate footer based on role
if ($_SESSION['role'] === 'advisor') {
    include '../includes/advisor_footer.php';
} else {
    include '../includes/admin_footer.php';
}
?>
