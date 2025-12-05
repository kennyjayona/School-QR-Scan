<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$name = $_SESSION['name'] ?? $_SESSION['username'];
$role = $_SESSION['role'] ?? 'user';
$role_display = ucfirst($role);
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#F8FAFC">
    <title><?php echo $page_title ?? 'Dashboard'; ?> - Smart Classroom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/global-theme.css" rel="stylesheet">
    <style>
        body {
            padding-top: 56px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-wrapper {
            display: flex;
            flex: 1;
        }
        .sidebar {
            width: 250px;
            min-height: calc(100vh - 56px);
            position: fixed;
            top: 56px;
            left: 0;
            background-color: var(--card-light);
            border-right: 1px solid var(--border-light);
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 1000;
        }
        .dark .sidebar {
            background-color: var(--card-dark);
            border-right-color: var(--border-dark);
        }
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 20px;
            width: calc(100% - 250px);
        }
        .sidebar .nav-link {
            color: #1F2937; /* Dark gray for better visibility in light mode */
            padding: 12px 20px;
            border-radius: 8px;
            margin: 4px 8px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            font-weight: 500;
        }
        .dark .sidebar .nav-link {
            color: #E2E8F0; /* Light gray in dark mode */
        }
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
            color: #0038A8; /* DepEd Blue icons in light mode */
        }
        .dark .sidebar .nav-link i {
            color: #FCD116; /* DepEd Yellow icons in dark mode */
        }
        .sidebar .nav-link:hover {
            background-color: rgba(0, 56, 168, 0.1);
            color: #0038A8; /* DepEd Blue on hover */
        }
        .dark .sidebar .nav-link:hover {
            background-color: rgba(252, 209, 22, 0.1);
            color: #FCD116; /* DepEd Yellow on hover */
        }
        .sidebar .nav-link.active {
            background-color: var(--primary);
            color: #fff;
        }
        .dark .sidebar .nav-link.active {
            background-color: var(--accent);
            color: var(--dark-bg);
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
        footer {
            margin-left: 250px;
            width: calc(100% - 250px);
        }
        @media (max-width: 768px) {
            footer {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <button class="btn btn-link text-white d-md-none me-2" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap"></i> Smart Classroom
            </a>
            <div class="d-flex align-items-center ms-auto">
                <span class="navbar-text text-white me-3 d-none d-sm-inline">
                    <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($name); ?>
                    <small class="ms-1">(<?php echo $role_display; ?>)</small>
                </span>
                <button id="themeToggle" class="btn btn-outline-light btn-sm me-2">
                    <i id="themeIcon" class="fas fa-moon"></i>
                </button>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> <span class="d-none d-sm-inline">Logout</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="main-wrapper">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <nav class="nav flex-column py-3">
                <?php
                require_once 'includes/permissions.php';
                $menu_items = get_menu_items();
                $current_page = basename($_SERVER['PHP_SELF']);
                
                foreach ($menu_items as $item):
                    $is_active = ($current_page === $item['url']) ? 'active' : '';
                ?>
                    <a class="nav-link <?php echo $is_active; ?>" href="<?php echo $item['url']; ?>">
                        <i class="fas fa-<?php echo $item['icon']; ?>"></i>
                        <?php echo $item['text']; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
