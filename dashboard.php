<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

$role = $_SESSION['role'];

// Redirect to role-specific dashboard
switch ($role) {
    case 'admin':
        header('Location: admin/dashboard_admin.php');
        break;
    case 'advisor':
        header('Location: advisor/dashboard_advisor.php');
        break;
    case 'teacher':
        header('Location: teacher/dashboard_teacher.php');
        break;
    case 'student':
        header('Location: student/dashboard_student.php');
        break;
    default:
        // Invalid role, logout
        session_destroy();
        header('Location: login.php');
        break;
}
exit;
?>
