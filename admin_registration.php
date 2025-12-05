<?php
// admin_registration.php - Special registration page for creating admin accounts
session_start();
require_once 'db_connect.php';

$error = '';
$success = '';

// Security: Only allow access if no admin exists OR if logged in as admin
$stmt = $conn->prepare('SELECT COUNT(*) as admin_count FROM users WHERE role = "admin"');
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$admin_exists = $result['admin_count'] > 0;

// If admin exists and user is not logged in as admin, deny access
if ($admin_exists && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || empty($name) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (strlen($username) < 4) {
        $error = 'Username must be at least 4 characters long.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        try {
            // Check if username exists
            $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $stmt->bind_param('s', $username);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                $error = 'Username already exists. Please choose another.';
            } else {
                // Hash password and insert admin user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $role = 'admin';
                $status = 'active';
                
                $stmt = $conn->prepare('INSERT INTO users (username, name, email, password, role, status) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('ssssss', $username, $name, $email, $hashed_password, $role, $status);
                
                if ($stmt->execute()) {
                    $success = 'Admin account created successfully! You can now login.';
                    log_system('INFO', 'New admin user created', "Username: $username");
                } else {
                    $error = 'Registration failed. Please try again.';
                    log_system('ERROR', 'Admin registration failed', $conn->error);
                }
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again.';
            log_system('ERROR', 'Admin registration exception', $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - Smart Classroom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1561AD;
            --secondary: #4D774E;
            --accent: #FBA92C;
            --light-bg: #F8FAFC;
            --dark-bg: #0F172A;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            min-height: 100vh;
            transition: all 0.3s;
        }
        
        .dark body {
            background: linear-gradient(135deg, var(--dark-bg) 0%, #1E293B 100%);
        }
        
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            border: none;
        }
        
        .dark .card {
            background-color: #1E293B;
            color: #E2E8F0;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: #0d4a8a;
            border-color: #0d4a8a;
        }
        
        .text-primary {
            color: var(--primary) !important;
        }
        
        .dark .text-primary {
            color: var(--accent) !important;
        }
        
        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .dark .form-control,
        .dark .form-select {
            background-color: #334155;
            border-color: #475569;
            color: #E2E8F0;
        }
        
        .dark .input-group-text {
            background-color: #334155;
            border-color: #475569;
            color: #E2E8F0;
        }
    </style>
</head>
<body>
    <!-- Theme Toggle -->
    <button id="themeToggle" class="btn btn-light theme-toggle rounded-circle" style="width: 50px; height: 50px;">
        <i id="themeIcon" class="fas fa-moon"></i>
    </button>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-user-shield fa-3x text-primary mb-3"></i>
                            <h2>Admin Registration</h2>
                            <p class="text-muted">Create Administrator Account</p>
                            <?php if (!$admin_exists): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No admin account exists. Create the first admin account.
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                                <br><a href="login.php" class="alert-link">Click here to login</a>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-at"></i></span>
                                    <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                                </div>
                                <small class="text-muted">At least 4 characters</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <small class="text-muted">At least 6 characters</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-user-shield"></i> Create Admin Account
                            </button>
                            
                            <div class="text-center">
                                <small>Already have an account? <a href="login.php">Login here</a></small>
                                <br>
                                <small><a href="landing.php">Back to Home</a></small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/theme-toggle.js"></script>
</body>
</html>
