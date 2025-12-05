<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'advisor'])) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Manage Teachers';
$current_page = 'teachers';
$base_url = '../';

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $username = trim($_POST['username']);
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $department = trim($_POST['department']);
        $specialization = trim($_POST['specialization']);
        $contact_number = trim($_POST['contact_number']);
        $password = password_hash('teacher123', PASSWORD_DEFAULT);
        
        try {
            $stmt = $conn->prepare("INSERT INTO users (username, password, name, email, role, department, specialization, contact_number) VALUES (?, ?, ?, ?, 'teacher', ?, ?, ?)");
            $stmt->bind_param("sssssss", $username, $password, $name, $email, $department, $specialization, $contact_number);
            $stmt->execute();
            
            $message = 'Teacher added successfully! Default password: teacher123';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . htmlspecialchars($e->getMessage());
            $message_type = 'danger';
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'];
        $username = trim($_POST['username']);
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $department = trim($_POST['department']);
        $specialization = trim($_POST['specialization']);
        $contact_number = trim($_POST['contact_number']);
        
        try {
            $stmt = $conn->prepare("UPDATE users SET username = ?, name = ?, email = ?, department = ?, specialization = ?, contact_number = ? WHERE id = ? AND role = 'teacher'");
            $stmt->bind_param("ssssssi", $username, $name, $email, $department, $specialization, $contact_number, $id);
            $stmt->execute();
            
            $message = 'Teacher updated successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . htmlspecialchars($e->getMessage());
            $message_type = 'danger';
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        try {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'teacher'");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            $message = 'Teacher deleted successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error deleting teacher.';
            $message_type = 'danger';
        }
    }
}

// Get all teachers
try {
    $teachers = $conn->query("SELECT * FROM users WHERE role = 'teacher' ORDER BY name");
} catch (Exception $e) {
    $teachers = $conn->query("SELECT * FROM users LIMIT 0");
}

$total_teachers = $teachers->num_rows;

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

<!-- Teachers List -->
<div class="content-card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-chalkboard-teacher"></i>
            Teachers List (<?php echo $total_teachers; ?>)
        </div>
        <div class="card-actions" style="display: flex; gap: 10px; align-items: center;">
            <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="🔍 Search teachers..." style="width: 250px;">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addTeacherModal">
                <i class="fas fa-plus"></i> Add New Teacher
            </button>
        </div>
    </div>
    <div style="overflow-x: auto;">
        <table class="table" id="teachersTable">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Teacher Info</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Username</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Email</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Department</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Specialization</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Contact</th>
                    <th style="padding: 12px; text-align: center; font-size: 13px; color: var(--text-secondary);">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($teacher = $teachers->fetch_assoc()): ?>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 12px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--warning); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px;">
                                <?php echo strtoupper(substr($teacher['name'], 0, 2)); ?>
                            </div>
                            <div>
                                <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($teacher['name']); ?></div>
                                <div style="font-size: 12px; color: var(--text-secondary);">ID: <?php echo htmlspecialchars($teacher['id']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="padding: 12px; font-size: 14px;"><?php echo htmlspecialchars($teacher['username']); ?></td>
                    <td style="padding: 12px; font-size: 14px;"><?php echo htmlspecialchars($teacher['email'] ?? 'N/A'); ?></td>
                    <td style="padding: 12px; font-size: 14px;"><?php echo htmlspecialchars($teacher['department'] ?? 'N/A'); ?></td>
                    <td style="padding: 12px; font-size: 14px;"><?php echo htmlspecialchars($teacher['specialization'] ?? 'N/A'); ?></td>
                    <td style="padding: 12px; font-size: 14px;"><?php echo htmlspecialchars($teacher['contact_number'] ?? 'N/A'); ?></td>
                    <td style="padding: 12px; text-align: center;">
                        <button type="button" class="btn btn-sm btn-primary me-1" onclick='editTeacher(<?php echo json_encode($teacher); ?>)'>
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this teacher? This action cannot be undone.');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $teacher['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Teacher Modal -->
<div class="modal fade" id="addTeacherModal" tabindex="-1" aria-labelledby="addTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--warning); color: white;">
                <h5 class="modal-title" id="addTeacherModalLabel">
                    <i class="fas fa-chalkboard-teacher"></i> Add New Teacher
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" required placeholder="For login">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="Teacher full name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required placeholder="teacher@example.com">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                            <input type="text" name="contact_number" class="form-control" required placeholder="+639XXXXXXXXX">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" class="form-control" placeholder="e.g., Mathematics">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Specialization</label>
                            <input type="text" name="specialization" class="form-control" placeholder="e.g., Algebra, Calculus">
                        </div>
                    </div>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle"></i> Default password will be: <strong>teacher123</strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Teacher
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Teacher Modal -->
<div class="modal fade" id="editTeacherModal" tabindex="-1" aria-labelledby="editTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--warning); color: white;">
                <h5 class="modal-title" id="editTeacherModalLabel">
                    <i class="fas fa-user-edit"></i> Edit Teacher
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="editTeacherForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" id="edit_username" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                            <input type="text" name="contact_number" id="edit_contact_number" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" id="edit_department" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Specialization</label>
                            <input type="text" name="specialization" id="edit_specialization" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Update Teacher
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase();
    const table = document.getElementById('teachersTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    for (let row of rows) {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchValue) ? '' : 'none';
    }
});

// Edit teacher function
function editTeacher(teacher) {
    document.getElementById('edit_id').value = teacher.id;
    document.getElementById('edit_username').value = teacher.username;
    document.getElementById('edit_name').value = teacher.name;
    document.getElementById('edit_email').value = teacher.email || '';
    document.getElementById('edit_contact_number').value = teacher.contact_number || '';
    document.getElementById('edit_department').value = teacher.department || '';
    document.getElementById('edit_specialization').value = teacher.specialization || '';
    
    // Show modal
    new bootstrap.Modal(document.getElementById('editTeacherModal')).show();
}

// Auto-close modal on success and reload
<?php if ($message && $message_type === 'success'): ?>
const addModal = bootstrap.Modal.getInstance(document.getElementById('addTeacherModal'));
const editModal = bootstrap.Modal.getInstance(document.getElementById('editTeacherModal'));
if (addModal) addModal.hide();
if (editModal) editModal.hide();
setTimeout(function() {
    location.reload();
}, 500);
<?php endif; ?>
</script>

<?php 
if ($_SESSION['role'] === 'advisor') {
    include '../includes/advisor_footer.php';
} else {
    include '../includes/admin_footer.php';
}
?>
