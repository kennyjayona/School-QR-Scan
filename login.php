<?php
require_once 'config.php';
require_once 'db_connect.php';

$error = '';

// Rate limiting configuration
$max_attempts = 5;
$lockout_time = 900; // 15 minutes
$ip_address = $_SERVER['REMOTE_ADDR'];

// Initialize login attempts tracking
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = [];
}

$login_attempts = $_SESSION['login_attempts'][$ip_address]['count'] ?? 0;
$last_attempt = $_SESSION['login_attempts'][$ip_address]['time'] ?? 0;

// Check if IP is locked out
if ($login_attempts >= $max_attempts) {
    $time_diff = time() - $last_attempt;
    if ($time_diff < $lockout_time) {
        $remaining = ceil(($lockout_time - $time_diff) / 60);
        $error = "Too many failed login attempts. Please try again in $remaining minutes.";
    } else {
        // Reset after lockout period
        $_SESSION['login_attempts'][$ip_address] = ['count' => 0, 'time' => 0];
        $login_attempts = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($login_attempts >= $max_attempts) {
        $error = 'Too many failed attempts. Please try again later.';
    } elseif (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
        $login_attempts++;
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, username, password, role, name FROM users WHERE username = ? AND status = 'active'");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['name'] = $user['name'] ?? $user['username'];
                    
                    // Clear login attempts on successful login
                    unset($_SESSION['login_attempts'][$ip_address]);

                    // Update last login
                    $update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $update->bind_param("i", $user['id']);
                    $update->execute();

                    // Log activity
                    require_once 'includes/activity_logger.php';
                    log_activity('Logged in successfully', 'users', $user['id']);

                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Invalid username or password.';
                    $login_attempts++;
                    $_SESSION['login_attempts'][$ip_address] = ['count' => $login_attempts, 'time' => time()];
                    
                    $remaining = $max_attempts - $login_attempts;
                    if ($remaining > 0) {
                        $error .= " ($remaining attempts remaining)";
                    }
                }
            } else {
                $error = 'Invalid username or password.';
                $login_attempts++;
                $_SESSION['login_attempts'][$ip_address] = ['count' => $login_attempts, 'time' => time()];
                
                $remaining = $max_attempts - $login_attempts;
                if ($remaining > 0) {
                    $error .= " ($remaining attempts remaining)";
                }
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
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
    <title>Login - Smart Classroom</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/global-theme.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1561AD 0%, #4D774E 50%, #0F172A 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            transition: all 0.3s;
        }

        .dark body {
            background: linear-gradient(135deg, #0F172A 0%, #1E293B 50%, #334155 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }

        @keyframes gradientShift {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            transition: all 0.3s;
        }

        .dark .login-card {
            background: rgba(30, 41, 59, 0.95);
            color: #E2E8F0;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            color: #1561AD;
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .dark .back-link {
            color: #FBA92C;
        }

        .back-link:hover {
            color: #0d4a8a;
            transform: translateX(-5px);
        }

        .dark .back-link:hover {
            color: #e09820;
        }

        .back-link i {
            margin-right: 8px;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }

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

        .dark .logo {
            background: linear-gradient(135deg, #FBA92C, #1561AD);
        }

        .logo i {
            font-size: 40px;
            color: #fff;
        }

        .welcome-text {
            font-size: 24px;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 8px;
        }

        .dark .welcome-text {
            color: #E2E8F0;
        }

        .subtitle {
            font-size: 14px;
            color: #6B7280;
            margin-bottom: 30px;
        }

        .dark .subtitle {
            color: #94A3B8;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .dark .form-label {
            color: #E2E8F0;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9CA3AF;
            font-size: 16px;
        }

        .form-input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
            background: #fff;
            color: #1F2937;
        }

        .dark .form-input {
            background: #334155;
            border-color: #475569;
            color: #E2E8F0;
        }

        .form-input:focus {
            outline: none;
            border-color: #1561AD;
            box-shadow: 0 0 0 3px rgba(21, 97, 173, 0.1);
        }

        .dark .form-input:focus {
            border-color: #FBA92C;
            box-shadow: 0 0 0 3px rgba(251, 169, 44, 0.1);
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9CA3AF;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }

        .password-toggle:hover {
            color: #1561AD;
        }

        .dark .password-toggle:hover {
            color: #FBA92C;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            font-size: 14px;
            color: #6B7280;
            cursor: pointer;
        }

        .dark .checkbox-label {
            color: #94A3B8;
        }

        .checkbox-label input {
            margin-right: 8px;
            cursor: pointer;
        }

        .forgot-link {
            font-size: 14px;
            color: #1561AD;
            text-decoration: none;
            transition: all 0.3s;
        }

        .forgot-link:hover {
            color: #0d4a8a;
        }

        .dark .forgot-link {
            color: #FBA92C;
        }

        .dark .forgot-link:hover {
            color: #e09820;
        }

        .btn-login {
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

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(21, 97, 173, 0.3);
        }

        .dark .btn-login {
            background: linear-gradient(135deg, #FBA92C, #1561AD);
        }

        .features {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #E5E7EB;
        }

        .dark .features {
            border-top-color: #475569;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #6B7280;
        }

        .dark .feature-item {
            color: #94A3B8;
        }

        .feature-item i {
            color: #4D774E;
        }

        .dark .feature-item i {
            color: #FBA92C;
        }

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

        .signup-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #6B7280;
        }

        .dark .signup-link {
            color: #94A3B8;
        }

        .signup-link a {
            color: #1561AD;
            text-decoration: none;
            font-weight: 600;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }

        .dark .signup-link a {
            color: #FBA92C;
        }
    </style>
</head>

<body>
    <!-- Theme Toggle    -->
    <button id="themeToggle" class="theme-toggle" aria-label="Toggle theme">
        <i id="themeIcon" class="fas fa-moon"></i>
    </button>

    <div class="login-container">
        <div class="login-card">
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>

            <div class="logo-container">
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="welcome-text">Welcome Back!</div>
                <div class="subtitle">Sign in to your Smart Classroom account</div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input
                            type="text"
                            name="username"
                            class="form-input"
                            placeholder="Enter your username"
                            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                            required
                            autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-input"
                            placeholder="Enter your password"
                            required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="checkbox-wrapper">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember">
                        Show password
                    </label>
                    <a href="#" class="forgot-link">Forgot password?</a>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>

            <div class="features">
                <div class="feature-item">
                    <i class="fas fa-shield-alt"></i>
                    Secure
                </div>
                <div class="feature-item">
                    <i class="fas fa-clock"></i>
                    24/7 Access
                </div>
                <div class="feature-item">
                    <i class="fas fa-mobile-alt"></i>
                    Mobile Ready
                </div>
            </div>

            <div class="signup-link">
                Don't have an account? <a href="register.php">Sign up here</a>
            </div>
        </div>
    </div>

    <script>
        // Password toggle
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Show password checkbox
        document.querySelector('input[name="remember"]').addEventListener('change', function() {
            togglePassword();
        });

        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const html = document.documentElement;

        // Load saved theme
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