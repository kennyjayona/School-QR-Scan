<?php

/**
 * Role-Based Access Control (RBAC) System
 * Defines permissions for each role
 */

// Define permissions for each role
$permissions = [
    'admin' => [
        'view_dashboard' => true,
        'view_students' => true,
        'add_students' => true,
        'edit_students' => true,
        'delete_students' => true,
        'view_teachers' => true,
        'add_teachers' => true,
        'edit_teachers' => true,
        'delete_teachers' => true,
        'view_attendance' => true,
        'mark_attendance' => true,
        'edit_attendance' => true,
        'delete_attendance' => true,
        'view_grades' => true,
        'add_grades' => true,
        'edit_grades' => true,
        'delete_grades' => true,
        'view_reports' => true,
        'export_reports' => true,
        'view_analytics' => true,
        'manage_users' => true,
        'generate_qr' => true,
        'view_settings' => true,
    ],
    'advisor' => [
        'view_dashboard' => true,
        'view_students' => true,
        'add_students' => false,
        'edit_students' => false,
        'delete_students' => false,
        'view_teachers' => true,
        'add_teachers' => false,
        'edit_teachers' => false,
        'delete_teachers' => false,
        'view_attendance' => true,
        'mark_attendance' => false,
        'edit_attendance' => false,
        'delete_attendance' => false,
        'view_grades' => true,
        'add_grades' => false,
        'edit_grades' => false,
        'delete_grades' => false,
        'view_reports' => true,
        'export_reports' => true,
        'view_analytics' => true,
        'manage_users' => false,
        'generate_qr' => false,
        'view_settings' => false,
    ],
    'teacher' => [
        'view_dashboard' => true,
        'view_students' => true,
        'add_students' => false,
        'edit_students' => false,
        'delete_students' => false,
        'view_teachers' => false,
        'add_teachers' => false,
        'edit_teachers' => false,
        'delete_teachers' => false,
        'view_attendance' => true,
        'mark_attendance' => true,
        'edit_attendance' => true,
        'delete_attendance' => false,
        'view_grades' => true,
        'add_grades' => true,
        'edit_grades' => true,
        'delete_grades' => false,
        'view_reports' => true,
        'export_reports' => false,
        'view_analytics' => false,
        'manage_users' => false,
        'generate_qr' => false,
        'view_settings' => false,
    ],
    'student' => [
        'view_dashboard' => true,
        'view_students' => false,
        'add_students' => false,
        'edit_students' => false,
        'delete_students' => false,
        'view_teachers' => false,
        'add_teachers' => false,
        'edit_teachers' => false,
        'delete_teachers' => false,
        'view_attendance' => true, // Own attendance only
        'mark_attendance' => false,
        'edit_attendance' => false,
        'delete_attendance' => false,
        'view_grades' => true, // Own grades only
        'add_grades' => false,
        'edit_grades' => false,
        'delete_grades' => false,
        'view_reports' => false,
        'export_reports' => false,
        'view_analytics' => false,
        'manage_users' => false,
        'generate_qr' => false,
        'view_settings' => false,
    ],
];

/**
 * Check if user is logged in and has access to page
 */
function checkPageAccess($allowed_roles = [])
{
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header('Location: login.php');
        exit;
    }

    // If no specific roles required, just check if logged in
    if (empty($allowed_roles)) {
        return true;
    }

    // Check if user's role is in allowed roles
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        $_SESSION['error'] = 'You do not have permission to access this page.';
        header('Location: dashboard.php');
        exit;
    }

    return true;
}

/**
 * Check if user has permission
 */
function has_permission($permission)
{
    global $permissions;

    if (!isset($_SESSION['role'])) {
        return false;
    }

    $role = $_SESSION['role'];

    if (!isset($permissions[$role])) {
        return false;
    }

    return $permissions[$role][$permission] ?? false;
}

/**
 * Require permission or redirect
 */
function require_permission($permission, $redirect = 'dashboard.php')
{
    if (!has_permission($permission)) {
        $_SESSION['error'] = 'You do not have permission to access this resource.';
        header("Location: $redirect");
        exit;
    }
}

/**
 * Check if user can perform action on resource
 */
function can_access($action, $resource_owner_id = null)
{
    $role = $_SESSION['role'] ?? null;
    $user_id = $_SESSION['user_id'] ?? null;

    // Admin can access everything
    if ($role === 'admin') {
        return true;
    }

    // Students can only access their own resources
    if ($role === 'student' && $resource_owner_id !== null) {
        return $user_id == $resource_owner_id;
    }

    // Check permission
    return has_permission($action);
}

/**
 * Get menu items based on role
 */
function get_menu_items()
{
    $role = $_SESSION['role'] ?? null;

    $menus = [
        'admin' => [
            ['icon' => 'tachometer-alt', 'text' => 'Dashboard', 'url' => 'dashboard.php'],
            ['icon' => 'user-graduate', 'text' => 'Manage Students', 'url' => 'admin/manage_students.php'],
            ['icon' => 'chalkboard-teacher', 'text' => 'Manage Teachers', 'url' => 'admin/manage_teachers.php'],
            ['icon' => 'chart-line', 'text' => 'Analytics', 'url' => 'admin/analytics.php'],
            ['icon' => 'file-alt', 'text' => 'Reports', 'url' => 'admin/reports.php'],
            ['icon' => 'qrcode', 'text' => 'Generate QR', 'url' => 'qr_generate.php'],
            ['icon' => 'user-shield', 'text' => 'Add Admin', 'url' => 'admin_registration.php'],
        ],
        'advisor' => [
            ['icon' => 'tachometer-alt', 'text' => 'Dashboard', 'url' => 'dashboard.php'],
            ['icon' => 'calendar-check', 'text' => 'View Attendance', 'url' => 'teacher/attendance.php'],
            ['icon' => 'clipboard-list', 'text' => 'View Grades', 'url' => 'teacher/grades.php'],
            ['icon' => 'file-alt', 'text' => 'Reports', 'url' => 'admin/reports.php'],
        ],
        'teacher' => [
            ['icon' => 'tachometer-alt', 'text' => 'Dashboard', 'url' => 'dashboard.php'],
            ['icon' => 'calendar-check', 'text' => 'Mark Attendance', 'url' => 'teacher/attendance.php'],
            ['icon' => 'clipboard-list', 'text' => 'Manage Grades', 'url' => 'teacher/grades.php'],
            ['icon' => 'qrcode', 'text' => 'QR Scanner', 'url' => 'qr_scan.html'],
        ],
        'student' => [
            ['icon' => 'tachometer-alt', 'text' => 'Dashboard', 'url' => 'dashboard.php'],
            ['icon' => 'calendar-check', 'text' => 'My Attendance', 'url' => 'student/my_attendance.php'],
            ['icon' => 'clipboard-list', 'text' => 'My Grades', 'url' => 'student/my_grades.php'],
        ],
    ];

    return $menus[$role] ?? [];
}
