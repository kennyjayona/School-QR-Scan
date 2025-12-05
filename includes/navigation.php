<?php
// Navigation based on user role
$role = $_SESSION['role'] ?? 'guest';
$current_page = $current_page ?? '';
$base_url = $base_url ?? '../';
?>
<!-- Modern Sidebar -->
<aside class="modern-sidebar">
    <div class="sidebar-header">
        <a href="<?php echo $base_url; ?>dashboard.php" class="sidebar-logo">
            <div class="sidebar-logo-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="sidebar-logo-text">
                <h3>Smart Classroom</h3>
                <p><?php echo ucfirst($role); ?> Portal</p>
            </div>
        </a>
    </div>

    <nav class="sidebar-menu">
        <?php if ($role === 'admin'): ?>
            <!-- ADMIN NAVIGATION -->
            <div class="menu-section">
                <div class="menu-section-title">Overview</div>
                <a href="<?php echo $base_url; ?>admin/dashboard_admin.php" class="menu-item <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Attendance</div>
                <a href="<?php echo $base_url; ?>qr_scan_time_in.html" class="menu-item">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>TIME IN</span>
                </a>
                <a href="<?php echo $base_url; ?>qr_scan_time_out.html" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>TIME OUT</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Management</div>
                <a href="<?php echo $base_url; ?>admin/manage_students.php" class="menu-item <?php echo $current_page == 'students' ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate"></i>
                    <span>Manage Students</span>
                </a>
                <a href="<?php echo $base_url; ?>admin/manage_teachers.php" class="menu-item <?php echo $current_page == 'teachers' ? 'active' : ''; ?>">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Manage Teachers</span>
                </a>
                <a href="<?php echo $base_url; ?>admin/manage_classrooms.php" class="menu-item <?php echo $current_page == 'classrooms' ? 'active' : ''; ?>">
                    <i class="fas fa-door-open"></i>
                    <span>Manage Classrooms</span>
                </a>
                <a href="<?php echo $base_url; ?>admin/manage_subjects.php" class="menu-item <?php echo $current_page == 'subjects' ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i>
                    <span>Manage Subjects</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Reports & Analytics</div>
                <a href="<?php echo $base_url; ?>admin/analytics.php" class="menu-item <?php echo $current_page == 'analytics' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </a>
                <a href="<?php echo $base_url; ?>admin/reports.php" class="menu-item <?php echo $current_page == 'reports' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>Reports</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Settings</div>
                <a href="<?php echo $base_url; ?>adminqr_generate.php" class="menu-item">
                    <i class="fas fa-qrcode"></i>
                    <span>Generate QR</span>
                </a>
                <a href="<?php echo $base_url; ?>admin/user_management.php" class="menu-item <?php echo $current_page == 'users' ? 'active' : ''; ?>">
                    <i class="fas fa-user-shield"></i>
                    <span>User Management</span>
                </a>
            </div>

        <?php elseif ($role === 'advisor'): ?>
            <!-- ADVISOR NAVIGATION -->
            <div class="menu-section">
                <div class="menu-section-title">Overview</div>
                <a href="<?php echo $base_url; ?>advisor/dashboard_advisor.php" class="menu-item <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Attendance</div>
                <a href="<?php echo $base_url; ?>qr_scan_time_in.html" class="menu-item">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>TIME IN</span>
                </a>
                <a href="<?php echo $base_url; ?>qr_scan_time_out.html" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>TIME OUT</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">My Classroom</div>
                <a href="<?php echo $base_url; ?>advisor/my_classroom.php" class="menu-item <?php echo $current_page == 'classroom' ? 'active' : ''; ?>">
                    <i class="fas fa-door-open"></i>
                    <span>Classroom Info</span>
                </a>
                <a href="<?php echo $base_url; ?>advisor/students.php" class="menu-item <?php echo $current_page == 'students' ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate"></i>
                    <span>My Students</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Records</div>
                <a href="<?php echo $base_url; ?>advisor/attendance.php" class="menu-item <?php echo $current_page == 'attendance' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>Attendance</span>
                </a>
                <a href="<?php echo $base_url; ?>advisor/grades.php" class="menu-item <?php echo $current_page == 'grades' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Grades</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Reports</div>
                <a href="<?php echo $base_url; ?>advisor/reports.php" class="menu-item <?php echo $current_page == 'reports' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>Class Reports</span>
                </a>
            </div>



        <?php elseif ($role === 'teacher'): ?>
            <!-- TEACHER NAVIGATION -->
            <div class="menu-section">
                <div class="menu-section-title">Overview</div>
                <a href="<?php echo $base_url; ?>teacher/dashboard_teacher.php" class="menu-item <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Attendance</div>
                <a href="<?php echo $base_url; ?>qr_scan_time_in.html" class="menu-item">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>TIME IN</span>
                </a>
                <a href="<?php echo $base_url; ?>qr_scan_time_out.html" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>TIME OUT</span>
                </a>
                <a href="<?php echo $base_url; ?>teacher/attendance.php" class="menu-item <?php echo $current_page == 'attendance' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>Attendance Records</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Academics</div>
                <a href="<?php echo $base_url; ?>teacher/my_subjects.php" class="menu-item <?php echo $current_page == 'subjects' ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i>
                    <span>My Subjects</span>
                </a>
                <a href="<?php echo $base_url; ?>teacher/grades.php" class="menu-item <?php echo $current_page == 'grades' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Grades</span>
                </a>
            </div>

            <!-- <div class="menu-section">
                <div class="menu-section-title">Tools</div>
                <a href="<?php echo $base_url; ?>qr_generate.php" class="menu-item">
                    <i class="fas fa-qrcode"></i>
                    <span>Generate QR</span>
                </a>
            </div> -->

        <?php elseif ($role === 'student'): ?>
            <!-- STUDENT NAVIGATION -->
            <div class="menu-section">
                <div class="menu-section-title">Overview</div>
                <a href="<?php echo $base_url; ?>student/dashboard_student.php" class="menu-item <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">My Records</div>
                <a href="<?php echo $base_url; ?>student/my_qr.php" class="menu-item <?php echo $current_page == 'qr' ? 'active' : ''; ?>">
                    <i class="fas fa-qrcode"></i>
                    <span>My QR Code</span>
                </a>
                <a href="<?php echo $base_url; ?>student/my_attendance.php" class="menu-item <?php echo $current_page == 'attendance' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>My Attendance</span>
                </a>
                <a href="<?php echo $base_url; ?>student/my_grades.php" class="menu-item <?php echo $current_page == 'grades' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>My Grades</span>
                </a>
            </div>

        <?php endif; ?>
    </nav>
</aside>