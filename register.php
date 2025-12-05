<?php
require_once 'db_connect.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = in_array($_POST['role'] ?? 'student', ['student', 'teacher', 'advisor']) ? $_POST['role'] : 'student';
    
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
                // Check if email exists
                if (!empty($email)) {
                    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                    $stmt->bind_param('s', $email);
                    $stmt->execute();
                    
                    if ($stmt->get_result()->num_rows > 0) {
                        $error = 'Email already registered.';
                    }
                }
                
                if (empty($error)) {
                    $conn->begin_transaction();
                    
                    try {
                        // Hash password and insert user
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare('INSERT INTO users (username, name, email, password, role) VALUES (?, ?, ?, ?, ?)');
                        $stmt->bind_param('sssss', $username, $name, $email, $hashed_password, $role);
                        $stmt->execute();
                        $user_id = $stmt->insert_id;
                        
                        // If student, create student record with auto-generated student_id
                        if ($role === 'student') {
                            $student_id = 'STU' . date('Y') . str_pad($user_id, 4, '0', STR_PAD_LEFT);
                            $stmt2 = $conn->prepare('INSERT INTO students (student_id, name, contact_number, qr_code) VALUES (?, ?, ?, ?)');
                            $contact = $email ?? '';
                            $qr_code = $username; // Use username as QR code identifier
                            $stmt2->bind_param('ssss', $student_id, $name, $contact, $qr_code);
                            $stmt2->execute();
                        }
                        
                        $conn->commit();
                        $success = 'Registration successful! You can now login.';
                        
                        // Clear form
                        $_POST = [];
                    } catch (Exception $e) {
                        $conn->rollback();
                        $error = 'Registration failed: ' . $e->getMessage();
                    }
                }
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1561AD">
    <title>Register - Smart Classroom</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/global-theme.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1561AD 0%, #4D774E 50%, #0F172A 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            padding: 20px;
        }
        
        .dark body {
            background: linear-gradient(135deg, #0F172A 0%, #1E293B 50%, #334155 100%);
            background-size: 400% 400%;
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .register-container { width: 100%; max-width: 500px; }
        
        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .dark .register-card {
            background: rgba(30, 41, 59, 0.95);
            color: #E2E8F0;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .back-link:hover { color: #FBA92C; transform: translateX(-5px); }
        .back-link i { margin-right: 8px; }
        
        .logo-container { text-align: center; margin-bottom: 30px; }
        
        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #1561AD, #4D774E);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(21, 97, 173, 0.3);
        }
        
        .dark .logo { background: linear-gradient(135deg, #FBA92C, #1561AD); }
        .logo i { font-size: 40px; color: #fff; }
        
        .welcome-text {
            font-size: 24px;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 8px;
        }
        
        .dark .welcome-text { color: #E2E8F0; }
        
        .subtitle {
            font-size: 14px;
            color: #6B7280;
            margin-bottom: 30px;
        }
        
        .dark .subtitle { color: #94A3B8; }
        
        .form-group { margin-bottom: 20px; }
        
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .dark .form-label { color: #E2E8F0; }
        
        .input-wrapper { position: relative; }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9CA3AF;
            font-size: 16px;
        }
        
        .form-input, .form-select {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
            background: #fff;
            color: #1F2937;
        }
        
        .dark .form-input, .dark .form-select {
            background: #334155;
            border-color: #475569;
            color: #E2E8F0;
        }
        
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #1561AD;
            box-shadow: 0 0 0 3px rgba(21, 97, 173, 0.1);
        }
        
        .dark .form-input:focus, .dark .form-select:focus {
            border-color: #FBA92C;
            box-shadow: 0 0 0 3px rgba(251, 169, 44, 0.1);
        }
        
        .btn-register {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1561AD, #4D774E);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(21, 97, 173, 0.3);
        }
        
        .dark .btn-register { background: linear-gradient(135deg, #FBA92C, #1561AD); }
        
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-danger {
            background: #FEE2E2;
            color: #991B1B;
            border: 1px solid #FCA5A5;
        }
        
        .dark .alert-danger {
            background: rgba(220, 38, 38, 0.2);
            color: #FCA5A5;
            border-color: #991B1B;
        }
        
        .alert-success {
            background: #D1FAE5;
            color: #065F46;
            border: 1px solid #6EE7B7;
        }
        
        .dark .alert-success {
            background: rgba(16, 185, 129, 0.2);
            color: #6EE7B7;
            border-color: #059669;
        }
        
        .theme-toggle {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 18px;
        }
        
        .theme-toggle:hover {
            transform: scale(1.1);
            background: rgba(255, 255, 255, 0.3);
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #6B7280;
        }
        
        .dark .login-link { color: #94A3B8; }
        
        .login-link a {
            color: #1561AD;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover { text-decoration: underline; }
        .dark .login-link a { color: #FBA92C; }
        
        .form-hint {
            font-size: 12px;
            color: #6B7280;
            margin-top: 4px;
        }
        
        .dark .form-hint { color: #94A3B8; }
    </style>
</head>
<body>
    <button id="themeToggle" class="theme-toggle" aria-label="Toggle theme">
        <i id="themeIcon" class="fas fa-moon"></i>
    </button>

    <div class="register-container">
        <a href="login.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Login
        </a>

        <div class="register-card">
            <div class="logo-container">
                <div class="logo">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="welcome-text">Create Account</div>
                <div class="subtitle">Join Smart Classroom today</div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                    <a href="login.php" style="color: inherit; font-weight: 600;">Login now</a>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Full Name <span style="color: #EF4444;">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="name" class="form-input" placeholder="Enter your full name" 
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Username <span style="color: #EF4444;">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-at input-icon"></i>
                        <input type="text" name="username" class="form-input" placeholder="Choose a username" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                    </div>
                    <div class="form-hint">At least 4 characters</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="email" class="form-input" placeholder="your@email.com" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password <span style="color: #EF4444;">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" class="form-input" placeholder="Create a password" required>
                    </div>
                    <div class="form-hint">At least 6 characters</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm Password <span style="color: #EF4444;">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="confirm_password" class="form-input" placeholder="Confirm your password" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Register as <span style="color: #EF4444;">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-user-tag input-icon"></i>
                        <select name="role" class="form-select" required>
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                            <option value="advisor">Advisor</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn-register">
                    <i class="fas fa-user-plus"></i>
                    Create Account
                </button>
            </form>

            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>

    <script>
        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const html = document.documentElement;

        const savedTheme = localStorage.getItem('smart-classroom-theme') || 'light';
        html.classList.toggle('dark', savedTheme === 'dark');
        themeIcon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';

        themeToggle.addEventListener('click', () => {
            const isDark = html.classList.toggle('dark');
            const newTheme = isDark ? 'dark' : 'light';
            localStorage.setItem('smart-classroom-theme', newTheme);
            themeIcon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
        });
    </script>
</body>
</html>
