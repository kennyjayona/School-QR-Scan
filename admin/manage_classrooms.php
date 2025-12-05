<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$name = $_SESSION['name'] ?? $_SESSION['username'];
$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $classroom_name = trim($_POST['classroom_name']);
        $year_level = intval($_POST['year_level']);
        $section = trim($_POST['section']);
        $advisor_id = !empty($_POST['advisor_id']) ? intval($_POST['advisor_id']) : null;
        $room_number = trim($_POST['room_number']);
        $capacity = !empty($_POST['capacity']) ? intval($_POST['capacity']) : 40;
        $school_year = date('Y') . '-' . (date('Y') + 1); // e.g., 2024-2025
        $created_by = $_SESSION['user_id']; // Admin user ID
        
        try {
            $stmt = $conn->prepare("INSERT INTO classrooms (classroom_name, section, year_level, school_year, advisor_id, created_by, room_number, capacity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssissisi", $classroom_name, $section, $year_level, $school_year, $advisor_id, $created_by, $room_number, $capacity);
            $stmt->execute();
            
            $message = 'Classroom added successfully!';
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
        $advisor_id = !empty($_POST['advisor_id']) ? intval($_POST['advisor_id']) : null;
        $room_number = trim($_POST['room_number']);
        $capacity = !empty($_POST['capacity']) ? intval($_POST['capacity']) : 40;
        
        try {
            $stmt = $conn->prepare("UPDATE classrooms SET classroom_name = ?, section = ?, year_level = ?, advisor_id = ?, room_number = ?, capacity = ? WHERE id = ?");
            $stmt->bind_param("ssiisii", $classroom_name, $section, $year_level, $advisor_id, $room_number, $capacity, $id);
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
            $stmt = $conn->prepare("DELETE FROM classrooms WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            $message = 'Classroom deleted successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . htmlspecialchars($e->getMessage());
            $message_type = 'danger';
        }
    }
}

// Get all classrooms with advisor info
try {
    // First check if year_level column exists
    $check_column = $conn->query("SHOW COLUMNS FROM classrooms LIKE 'year_level'");
    
    if ($check_column && $check_column->num_rows > 0) {
        // Column exists, use it in ORDER BY
        $classrooms = $conn->query("
            SELECT c.*, u.name as advisor_name 
            FROM classrooms c 
            LEFT JOIN users u ON c.advisor_id = u.id 
            ORDER BY c.year_level, c.section
        ");
    } else {
        // Column doesn't exist, order by classroom_name only
        $classrooms = $conn->query("
            SELECT c.*, u.name as advisor_name 
            FROM classrooms c 
            LEFT JOIN users u ON c.advisor_id = u.id 
            ORDER BY c.classroom_name
        ");
    }
    
    if (!$classrooms) {
        throw new Exception("Error fetching classrooms: " . $conn->error);
    }
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage() . "<br><br>Please run fix_all_issues.php to fix database issues.");
}

// Get all advisors for dropdown
try {
    $advisors = $conn->query("SELECT id, name FROM users WHERE role = 'advisor' ORDER BY name");
    if (!$advisors) {
        $advisors = $conn->query("SELECT id, name FROM users LIMIT 0"); // Empty result
    }
} catch (Exception $e) {
    $advisors = $conn->query("SELECT id, name FROM users LIMIT 0"); // Empty result
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classrooms - Smart Classroom</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/modern-dashboard.css" rel="stylesheet">
</head>
<body>
    <!-- Modern Sidebar -->
    <aside class="modern-sidebar">
        <div class="sidebar-header">
            <a href="dashboard_admin.php" class="sidebar-logo">
                <div class="sidebar-logo-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="sidebar-logo-text">
                    <h3>Smart Classroom</h3>
                    <p>Admin Portal</p>
                </div>
            </a>
        </div>

        <nav class="sidebar-menu">
            <div class="menu-section">
                <div class="menu-section-title">Overview</div>
                <a href="dashboard_admin.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Attendance</div>
                <a href="../qr_scan_time_in.html" class="menu-item">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>TIME IN</span>
                </a>
                <a href="../qr_scan_time_out.html" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>TIME OUT</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Management</div>
                <a href="manage_students.php" class="menu-item">
                    <i class="fas fa-user-graduate"></i>
                    <span>Manage Students</span>
                </a>
                <a href="manage_teachers.php" class="menu-item">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Manage Teachers</span>
                </a>
                <a href="manage_classrooms.php" class="menu-item active">
                    <i class="fas fa-door-open"></i>
                    <span>Manage Classrooms</span>
                </a>
                <a href="manage_subjects.php" class="menu-item">
                    <i class="fas fa-book"></i>
                    <span>Manage Subjects</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Reports & Analytics</div>
                <a href="analytics.php" class="menu-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </a>
                <a href="reports.php" class="menu-item">
                    <i class="fas fa-file-alt"></i>
                    <span>Reports</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Settings</div>
                <a href="../qr_generate.php" class="menu-item">
                    <i class="fas fa-qrcode"></i>
                    <span>Generate QR</span>
                </a>
                <a href="user_management.php" class="menu-item">
                    <i class="fas fa-user-shield"></i>
                    <span>User Management</span>
                </a>
            </div>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Navigation -->
        <nav class="top-navbar">
            <div class="navbar-title">
                <h1>Manage Classrooms</h1>
            </div>
            <div class="navbar-actions">
                <button class="theme-toggle-btn" id="themeToggle">
                    <i class="fas fa-moon"></i>
                </button>
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($name, 0, 2)); ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($name); ?></div>
                        <div class="user-role">Administrator</div>
                    </div>
                </div>
                <a href="../logout.php" class="btn btn-secondary">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </nav>

        <!-- Dashboard Content -->
        <div class="dashboard-container">
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" style="padding: 15px; border-radius: 8px; margin-bottom: 20px; background: rgba(<?php echo $message_type === 'success' ? '16, 185, 129' : '239, 68, 68'; ?>, 0.1); border-left: 4px solid var(--<?php echo $message_type === 'success' ? 'success' : 'danger'; ?>); color: var(--<?php echo $message_type === 'success' ? 'success' : 'danger'; ?>);">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <!-- Classrooms Table -->
            <div class="content-card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-door-open"></i>
                        Classrooms List
                    </div>
                    <div class="card-actions">
                        <button class="btn btn-primary" onclick="document.getElementById('addModal').style.display='flex'">
                            <i class="fas fa-plus"></i> Add Classroom
                        </button>
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border-color);">
                                <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Classroom Name</th>
                                <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Year Level</th>
                                <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Section</th>
                                <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Room Number</th>
                                <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Capacity</th>
                                <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Advisor</th>
                                <th style="padding: 12px; text-align: center; font-size: 13px; color: var(--text-secondary);">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($classroom = $classrooms->fetch_assoc()): ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px; font-weight: 600;"><?php echo htmlspecialchars($classroom['classroom_name']); ?></td>
                                <td style="padding: 12px;">Grade <?php echo htmlspecialchars($classroom['year_level']); ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($classroom['section']); ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($classroom['room_number'] ?? 'N/A'); ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($classroom['capacity'] ?? 'N/A'); ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($classroom['advisor_name'] ?? 'Not Assigned'); ?></td>
                                <td style="padding: 12px; text-align: center;">
                                    <button onclick='editClassroom(<?php echo json_encode($classroom); ?>)' class="btn btn-sm" style="background: var(--warning); color: white; margin-right: 5px;">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this classroom?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $classroom['id']; ?>">
                                        <button type="submit" class="btn btn-sm" style="background: var(--danger); color: white;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Add Modal -->
    <div id="addModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: var(--card-bg); border-radius: 12px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
            <div style="padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0;"><i class="fas fa-plus-circle"></i> Add New Classroom</h3>
                <button onclick="document.getElementById('addModal').style.display='none'" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-primary);">&times;</button>
            </div>
            <form method="POST" style="padding: 20px;">
                <input type="hidden" name="action" value="add">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Classroom Name *</label>
                    <input type="text" name="classroom_name" required placeholder="e.g., Grade 7 - Section A" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-primary);">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Year Level *</label>
                        <select name="year_level" required style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-primary);">
                            <option value="">Select</option>
                            <option value="7">Grade 7</option>
                            <option value="8">Grade 8</option>
                            <option value="9">Grade 9</option>
                            <option value="10">Grade 10</option>
                            <option value="11">Grade 11</option>
                            <option value="12">Grade 12</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Section *</label>
                        <input type="text" name="section" required placeholder="e.g., A, B, C" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-primary);">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Room Number</label>
                        <input type="text" name="room_number" placeholder="e.g., 101, 202" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-primary);">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Capacity</label>
                        <input type="number" name="capacity" placeholder="e.g., 40" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-primary);">
                    </div>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Assign Advisor</label>
                    <select name="advisor_id" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-primary);">
                        <option value="">No Advisor</option>
                        <?php 
                        $advisors->data_seek(0);
                        while ($advisor = $advisors->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $advisor['id']; ?>"><?php echo htmlspecialchars($advisor['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="document.getElementById('addModal').style.display='none'" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Classroom</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: var(--card-bg); border-radius: 12px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
            <div style="padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0;"><i class="fas fa-edit"></i> Edit Classroom</h3>
                <button onclick="document.getElementById('editModal').style.display='none'" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-primary);">&times;</button>
            </div>
            <form method="POST" style="padding: 20px;">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Classroom Name *</label>
                    <input type="text" name="classroom_name" id="edit_classroom_name" required style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-primary);">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Year Level *</label>
                        <select name="year_level" id="edit_year_level" required style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-primary);">
                            <option value="">Select</option>
                            <option value="7">Grade 7</option>
                            <option value="8">Grade 8</option>
                            <option value="9">Grade 9</option>
                            <option value="10">Grade 10</option>
                            <option value="11">Grade 11</option>
                            <option value="12">Grade 12</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Section *</label>
                        <input type="text" name="section" id="edit_section" required style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-primary);">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Room Number</label>
                        <input type="text" name="room_number" id="edit_room_number" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-primary);">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Capacity</label>
                        <input type="number" name="capacity" id="edit_capacity" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-primary);">
                    </div>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Assign Advisor</label>
                    <select name="advisor_id" id="edit_advisor_id" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-primary);">
                        <option value="">No Advisor</option>
                        <?php 
                        $advisors->data_seek(0);
                        while ($advisor = $advisors->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $advisor['id']; ?>"><?php echo htmlspecialchars($advisor['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="document.getElementById('editModal').style.display='none'" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Classroom</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const html = document.documentElement;
        const savedTheme = localStorage.getItem('smart-classroom-theme') || 'light';
        html.classList.toggle('dark', savedTheme === 'dark');
        themeToggle.querySelector('i').className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';

        themeToggle.addEventListener('click', () => {
            const isDark = html.classList.toggle('dark');
            const newTheme = isDark ? 'dark' : 'light';
            localStorage.setItem('smart-classroom-theme', newTheme);
            themeToggle.querySelector('i').className = isDark ? 'fas fa-sun' : 'fas fa-moon';
        });

        // Edit Classroom Function
        function editClassroom(classroom) {
            document.getElementById('edit_id').value = classroom.id;
            document.getElementById('edit_classroom_name').value = classroom.classroom_name;
            document.getElementById('edit_year_level').value = classroom.year_level;
            document.getElementById('edit_section').value = classroom.section;
            document.getElementById('edit_room_number').value = classroom.room_number || '';
            document.getElementById('edit_capacity').value = classroom.capacity || '';
            document.getElementById('edit_advisor_id').value = classroom.advisor_id || '';
            
            document.getElementById('editModal').style.display = 'flex';
        }
    </script>
</body>
</html>
