<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$page_title = 'User Management';
$current_page = 'users';
$base_url = '../';
$name = $_SESSION['name'] ?? $_SESSION['username'];

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $username = trim($_POST['username']);
        $name_input = trim($_POST['name']);
        $email = trim($_POST['email']);
        $role = trim($_POST['role']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        try {
            $stmt = $conn->prepare("INSERT INTO users (username, password, name, email, role, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("sssss", $username, $password, $name_input, $email, $role);
            $stmt->execute();

            $message = 'User added successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . htmlspecialchars($e->getMessage());
            $message_type = 'danger';
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        try {
            // Don't allow deleting yourself
            if ($id == $_SESSION['user_id']) {
                $message = 'You cannot delete your own account!';
                $message_type = 'warning';
            } else {
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();

                $message = 'User deleted successfully!';
                $message_type = 'success';
            }
        } catch (Exception $e) {
            $message = 'Error deleting user.';
            $message_type = 'danger';
        }
    } elseif ($action === 'toggle_status') {
        $id = $_POST['id'];
        $new_status = $_POST['status'];
        
        try {
            // Don't allow deactivating yourself
            if ($id == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'You cannot deactivate your own account!']);
                exit;
            }
            
            $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->bind_param("ii", $new_status, $id);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'User status updated successfully!']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error updating status.']);
            exit;
        }
    }
}

// Get all users
try {
    $users = $conn->query("SELECT * FROM users ORDER BY role, name");
} catch (Exception $e) {
    $users = $conn->query("SELECT * FROM users LIMIT 0");
}

$total_users = $users->num_rows;

include '../includes/admin_header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Users List -->
<div class="content-card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-users-cog"></i>
            All Users (<?php echo $total_users; ?>)
        </div>
        <div class="card-actions" style="display: flex; gap: 10px; align-items: center;">
            <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="🔍 Search users..." style="width: 250px;">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addUserModal">
                <i class="fas fa-plus"></i> Add New User
            </button>
        </div>
    </div>
    <div style="overflow-x: auto;">
        <table class="table" id="usersTable">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">User Info</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Username</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Email</th>
                    <th style="padding: 12px; text-align: center; font-size: 13px; color: var(--text-secondary);">Role</th>
                    <th style="padding: 12px; text-align: center; font-size: 13px; color: var(--text-secondary);">Status</th>
                    <th style="padding: 12px; text-align: center; font-size: 13px; color: var(--text-secondary);">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = $users->fetch_assoc()):
                    $role_colors = [
                        'admin' => 'var(--danger)',
                        'teacher' => 'var(--warning)',
                        'student' => 'var(--info)',
                        'advisor' => 'var(--success)'
                    ];
                    $role_color = $role_colors[$user['role']] ?? 'var(--text-secondary)';
                ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: <?php echo $role_color; ?>; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px;">
                                    <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                                </div>
                                <div>
                                    <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($user['name']); ?></div>
                                    <div style="font-size: 12px; color: var(--text-secondary);">ID: <?php echo htmlspecialchars($user['id']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 12px; font-size: 14px;"><?php echo htmlspecialchars($user['username']); ?></td>
                        <td style="padding: 12px; font-size: 14px;"><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                        <td style="padding: 12px; text-align: center;">
                            <span style="padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; background: rgba(<?php
                                                                                                                                    echo $user['role'] === 'admin' ? '239, 68, 68' : ($user['role'] === 'teacher' ? '245, 158, 11' : ($user['role'] === 'student' ? '59, 130, 246' : '16, 185, 129'));
                                                                                                                                    ?>, 0.1); color: <?php echo $role_color; ?>; text-transform: capitalize;">
                                <?php echo htmlspecialchars($user['role']); ?>
                            </span>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <?php 
                            $is_active = $user['is_active'] ?? 1;
                            if ($user['id'] != $_SESSION['user_id']): 
                            ?>
                                <button type="button" 
                                        class="btn btn-sm <?php echo $is_active ? 'btn-success' : 'btn-secondary'; ?> status-toggle-btn"
                                        data-user-id="<?php echo $user['id']; ?>"
                                        data-current-status="<?php echo $is_active; ?>"
                                        onclick="toggleUserStatus(this)">
                                    <i class="fas fa-<?php echo $is_active ? 'check-circle' : 'ban'; ?>"></i>
                                    <?php echo $is_active ? 'Active' : 'Inactive'; ?>
                                </button>
                            <?php else: ?>
                                <span class="badge bg-success">
                                    <i class="fas fa-check-circle"></i> Active (You)
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this user? This action cannot be undone.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="color: var(--text-secondary); font-size: 12px;">Current User</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--primary-blue); color: white;">
                <h5 class="modal-title" id="addUserModalLabel">
                    <i class="fas fa-user-plus"></i> Add New User
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
                            <input type="text" name="name" class="form-control" required placeholder="User full name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required placeholder="user@example.com">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" required placeholder="Enter password">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-control" required>
                                <option value="">Select Role</option>
                                <option value="admin">Admin</option>
                                <option value="teacher">Teacher</option>
                                <option value="student">Student</option>
                                <option value="advisor">Advisor</option>
                            </select>
                        </div>
                    </div>
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Note:</strong> Make sure to save the password securely. Users will need it to login.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add User
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
        const table = document.getElementById('usersTable');
        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

        for (let row of rows) {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchValue) ? '' : 'none';
        }
    });

    // Toggle user status (activate/deactivate)
    function toggleUserStatus(button) {
        const userId = button.dataset.userId;
        const currentStatus = parseInt(button.dataset.currentStatus);
        const newStatus = currentStatus === 1 ? 0 : 1;
        const actionText = newStatus === 1 ? 'activate' : 'deactivate';
        
        if (!confirm(`Are you sure you want to ${actionText} this user?`)) {
            return;
        }
        
        // Disable button during request
        button.disabled = true;
        
        // Send AJAX request
        fetch('user_management.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=toggle_status&id=${userId}&status=${newStatus}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update button appearance
                button.dataset.currentStatus = newStatus;
                if (newStatus === 1) {
                    button.className = 'btn btn-sm btn-success status-toggle-btn';
                    button.innerHTML = '<i class="fas fa-check-circle"></i> Active';
                } else {
                    button.className = 'btn btn-sm btn-secondary status-toggle-btn';
                    button.innerHTML = '<i class="fas fa-ban"></i> Inactive';
                }
                
                // Show success message
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success alert-dismissible fade show';
                alertDiv.innerHTML = `
                    <i class="fas fa-check-circle"></i> ${data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.content-card').insertAdjacentElement('beforebegin', alertDiv);
                
                // Auto-dismiss after 3 seconds
                setTimeout(() => {
                    alertDiv.remove();
                }, 3000);
            } else {
                alert('Error: ' + data.message);
            }
            button.disabled = false;
        })
        .catch(error => {
            alert('An error occurred. Please try again.');
            button.disabled = false;
        });
    }

    // Auto-close modal on success and reload
    <?php if ($message && $message_type === 'success'): ?>
        $('#addUserModal').modal('hide');
        setTimeout(function() {
            location.reload();
        }, 500);
    <?php endif; ?>
</script>

<?php include '../includes/admin_footer.php'; ?>