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
        $student_id = trim($_POST['student_id']);
        $student_name = trim($_POST['student_name']);
        $section = trim($_POST['section']);
        $year_level = trim($_POST['year_level']);
        $contact_number = trim($_POST['contact_number']);

        // Handle photo upload
        $photo_path = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/students/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($file_extension, $allowed_extensions)) {
                $photo_filename = $student_id . '_' . time() . '.' . $file_extension;
                $photo_path = $upload_dir . $photo_filename;

                if (move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
                    $photo_path = 'uploads/students/' . $photo_filename;
                } else {
                    $photo_path = null;
                }
            }
        }

        try {
            $stmt = $conn->prepare("INSERT INTO students (student_id, name, section, year_level, contact_number, photo, qr_code) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $student_id, $student_name, $section, $year_level, $contact_number, $photo_path, $student_id);
            $stmt->execute();

            $message = 'Student added successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . htmlspecialchars($e->getMessage());
            $message_type = 'danger';
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'];
        $student_id = trim($_POST['student_id']);
        $student_name = trim($_POST['student_name']);
        $section = trim($_POST['section']);
        $year_level = trim($_POST['year_level']);
        $contact_number = trim($_POST['contact_number']);

        // Handle photo upload for edit
        $photo_update = '';
        $photo_params = [];
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/students/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($file_extension, $allowed_extensions)) {
                $photo_filename = $student_id . '_' . time() . '.' . $file_extension;
                $photo_path = $upload_dir . $photo_filename;

                if (move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
                    $photo_update = ', photo = ?';
                    $photo_params[] = 'uploads/students/' . $photo_filename;
                }
            }
        }

        try {
            $sql = "UPDATE students SET student_id = ?, name = ?, section = ?, year_level = ?, contact_number = ?" . $photo_update . " WHERE id = ?";
            $stmt = $conn->prepare($sql);

            $params = [$student_id, $student_name, $section, $year_level, $contact_number];
            if (!empty($photo_params)) {
                $params = array_merge($params, $photo_params);
            }
            $params[] = $id;

            $types = str_repeat('s', count($params) - 1) . 'i';
            $stmt->bind_param($types, ...$params);
            $stmt->execute();

            $message = 'Student updated successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . htmlspecialchars($e->getMessage());
            $message_type = 'danger';
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];

        try {
            $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $message = 'Student deleted successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . htmlspecialchars($e->getMessage());
            $message_type = 'danger';
        }
    }
}

// Get all students
$students = $conn->query("SELECT * FROM students ORDER BY year_level, section, name");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Smart Classroom</title>
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
                <a href="manage_students.php" class="menu-item active">
                    <i class="fas fa-user-graduate"></i>
                    <span>Manage Students</span>
                </a>
                <a href="manage_teachers.php" class="menu-item">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Manage Teachers</span>
                </a>
                <a href="manage_classrooms.php" class="menu-item">
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
                <h1>Manage Students</h1>
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

            <!-- Students Table -->
            <div class="content-card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-user-graduate"></i>
                        Students List
                    </div>
                    <div class="card-actions">
                        <button class="btn btn-primary" onclick="document.getElementById('addModal').style.display='flex'">
                            <i class="fas fa-plus"></i> Add Student
                        </button>
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border-color);">
                                <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Photo</th>
                                <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Student ID</th>
                                <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Name</th>
                                <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Year Level</th>
                                <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Section</th>
                                <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Contact</th>
                                <th style="padding: 12px; text-align: center; font-size: 13px; color: var(--text-secondary);">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($student = $students->fetch_assoc()): ?>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 12px;">
                                        <?php if ($student['photo']): ?>
                                            <img src="../<?php echo htmlspecialchars($student['photo']); ?>" alt="Photo" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                        <?php else: ?>
                                            <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary-blue); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px;">
                                                <?php echo strtoupper(substr($student['name'], 0, 2)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px; font-weight: 600;"><?php echo htmlspecialchars($student['student_id']); ?></td>
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td style="padding: 12px;">Grade <?php echo htmlspecialchars($student['year_level'] ?? 'N/A'); ?></td>
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($student['section'] ?? 'N/A'); ?></td>
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($student['contact_number'] ?? 'N/A'); ?></td>
                                    <td style="padding: 12px; text-align: center;">
                                        <a href="../qr_generate.php?student_id=<?php echo urlencode($student['student_id']); ?>" 
                                           class="btn btn-sm" 
                                           style="background: var(--primary-blue); color: white; margin-right: 5px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;"
                                           title="Generate QR Code">
                                            <i class="fas fa-qrcode"></i>
                                        </a>
                                        <button onclick='editStudent(<?php echo json_encode($student); ?>)' class="btn btn-sm" style="background: var(--warning); color: white; margin-right: 5px;">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this student?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
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
                <h3 style="margin: 0;"><i class="fas fa-user-plus"></i> Add New Student</h3>
                <button onclick="document.getElementById('addModal').style.display='none'" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-primary);">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data" style="padding: 20px;">
                <input type="hidden" name="action" value="add">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Student ID *</label>
                    <input type="text" name="student_id" required style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-primary);">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Full Name *</label>
                    <input type="text" name="student_name" required style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-primary);">
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
                        <input type="text" name="section" required style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-primary);">
                    </div>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Contact Number *</label>
                    <input type="text" name="contact_number" required placeholder="+639XXXXXXXXX" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-primary);">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Student Photo</label>
                    <input type="file" name="photo" accept="image/*" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-primary);">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="document.getElementById('addModal').style.display='none'" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: var(--card-bg); border-radius: 12px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
            <div style="padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0;"><i class="fas fa-user-edit"></i> Edit Student</h3>
                <button onclick="document.getElementById('editModal').style.display='none'" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-primary);">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data" style="padding: 20px;">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Student ID *</label>
                    <input type="text" name="student_id" id="edit_student_id" required style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-primary);">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Full Name *</label>
                    <input type="text" name="student_name" id="edit_name" required style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-primary);">
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
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Contact Number *</label>
                    <input type="text" name="contact_number" id="edit_contact_number" required style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-primary);">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">New Photo</label>
                    <div id="current_photo" style="margin-bottom: 10px;"></div>
                    <input type="file" name="photo" accept="image/*" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-primary);">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="document.getElementById('editModal').style.display='none'" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Student</button>
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

        // Edit Student Function
        function editStudent(student) {
            document.getElementById('edit_id').value = student.id;
            document.getElementById('edit_student_id').value = student.student_id;
            document.getElementById('edit_name').value = student.name;
            document.getElementById('edit_year_level').value = student.year_level || '';
            document.getElementById('edit_section').value = student.section || '';
            document.getElementById('edit_contact_number').value = student.contact_number || '';

            const photoDiv = document.getElementById('current_photo');
            if (student.photo) {
                photoDiv.innerHTML = '<img src="../' + student.photo + '" style="width: 80px; height: 80px; border-radius: 8px; object-fit: cover;">';
            } else {
                photoDiv.innerHTML = '<span style="color: var(--text-secondary);">No photo</span>';
            }

            document.getElementById('editModal').style.display = 'flex';
        }
    </script>
</body>

</html>