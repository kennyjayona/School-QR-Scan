<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'advisor') {
    header('Location: ../login.php');
    exit;
}

$page_title = 'My Classrooms';
$current_page = 'my_classrooms';
$user_id = $_SESSION['user_id'];

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $classroom_name = trim($_POST['classroom_name']);
        $year_level = intval($_POST['year_level']);
        $section = trim($_POST['section']);
        $room_number = trim($_POST['room_number']);
        $capacity = !empty($_POST['capacity']) ? intval($_POST['capacity']) : 40;
        $school_year = date('Y') . '-' . (date('Y') + 1); // e.g., 2024-2025
        
        try {
            $stmt = $conn->prepare("INSERT INTO classrooms (classroom_name, section, year_level, school_year, room_number, capacity, advisor_id, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssissiii", $classroom_name, $section, $year_level, $school_year, $room_number, $capacity, $user_id, $user_id);
            $stmt->execute();
            
            $message = 'Classroom created successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . htmlspecialchars($e->getMessage());
            $message_type = 'danger';
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id']);
        $classroom_name = trim($_POST['classroom_name']);
        $year_level = intval($_POST['year_level']);
        $section = trim($_POST['section']);
        $room_number = trim($_POST['room_number']);
        $capacity = !empty($_POST['capacity']) ? intval($_POST['capacity']) : 40;
        
        try {
            $stmt = $conn->prepare("UPDATE classrooms SET classroom_name = ?, section = ?, year_level = ?, room_number = ?, capacity = ? WHERE id = ? AND created_by = ?");
            $stmt->bind_param("ssissii", $classroom_name, $section, $year_level, $room_number, $capacity, $id, $user_id);
            $stmt->execute();
            
            $message = 'Classroom updated successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . htmlspecialchars($e->getMessage());
            $message_type = 'danger';
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        
        try {
            $stmt = $conn->prepare("DELETE FROM classrooms WHERE id = ? AND created_by = ?");
            $stmt->bind_param("ii", $id, $user_id);
            $stmt->execute();
            
            $message = 'Classroom deleted successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . htmlspecialchars($e->getMessage());
            $message_type = 'danger';
        }
    }
}

// Get classrooms created by this advisor only
$classrooms = $conn->query("SELECT c.*, 
    (SELECT COUNT(*) FROM classroom_subjects cs WHERE cs.classroom_id = c.id) as subject_count
    FROM classrooms c 
    WHERE c.created_by = $user_id 
    ORDER BY c.year_level, c.section");

include '../includes/advisor_header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- My Classrooms -->
<div class="content-card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-door-open"></i>
            My Classrooms (<?php echo $classrooms->num_rows; ?>)
        </div>
        <div class="card-actions">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus"></i> Create Classroom
            </button>
        </div>
    </div>
    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 12px;">Classroom Name</th>
                    <th style="padding: 12px;">Year & Section</th>
                    <th style="padding: 12px;">Room</th>
                    <th style="padding: 12px;">Capacity</th>
                    <th style="padding: 12px;">Subjects</th>
                    <th style="padding: 12px; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($classrooms->num_rows > 0): ?>
                    <?php while ($classroom = $classrooms->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px;">
                            <a href="classroom_subjects.php?classroom_id=<?php echo $classroom['id']; ?>" style="color: var(--primary-blue); font-weight: 600; text-decoration: none;">
                                <i class="fas fa-door-open"></i> <?php echo htmlspecialchars($classroom['classroom_name']); ?>
                            </a>
                        </td>
                        <td style="padding: 12px;">Grade <?php echo htmlspecialchars($classroom['year_level']); ?> - <?php echo htmlspecialchars($classroom['section']); ?></td>
                        <td style="padding: 12px;"><?php echo htmlspecialchars($classroom['room_number'] ?? 'N/A'); ?></td>
                        <td style="padding: 12px;"><?php echo htmlspecialchars($classroom['capacity'] ?? 'N/A'); ?></td>
                        <td style="padding: 12px;">
                            <span class="badge bg-info"><?php echo $classroom['subject_count']; ?> subjects</span>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <button type="button" class="btn btn-sm btn-primary" onclick='editClassroom(<?php echo json_encode($classroom); ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this classroom and all its subjects?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $classroom['id']; ?>">
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
                            <p>No classrooms yet. Click "Create Classroom" to get started.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--primary-blue); color: white;">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Create New Classroom</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Classroom Name *</label>
                        <input type="text" name="classroom_name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Year Level *</label>
                            <select name="year_level" class="form-control" required>
                                <option value="">Select</option>
                                <?php for($i=7; $i<=12; $i++): ?>
                                <option value="<?php echo $i; ?>">Grade <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Section *</label>
                            <input type="text" name="section" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Room Number</label>
                            <input type="text" name="room_number" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Capacity</label>
                            <input type="number" name="capacity" class="form-control" placeholder="40">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Classroom</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--warning); color: white;">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Classroom</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Classroom Name *</label>
                        <input type="text" name="classroom_name" id="edit_classroom_name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Year Level *</label>
                            <select name="year_level" id="edit_year_level" class="form-control" required>
                                <?php for($i=7; $i<=12; $i++): ?>
                                <option value="<?php echo $i; ?>">Grade <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Section *</label>
                            <input type="text" name="section" id="edit_section" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Room Number</label>
                            <input type="text" name="room_number" id="edit_room_number" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Capacity</label>
                            <input type="number" name="capacity" id="edit_capacity" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Update Classroom</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editClassroom(classroom) {
    document.getElementById('edit_id').value = classroom.id;
    document.getElementById('edit_classroom_name').value = classroom.classroom_name;
    document.getElementById('edit_year_level').value = classroom.year_level;
    document.getElementById('edit_section').value = classroom.section;
    document.getElementById('edit_room_number').value = classroom.room_number || '';
    document.getElementById('edit_capacity').value = classroom.capacity || '';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php include '../includes/advisor_footer.php'; ?>
