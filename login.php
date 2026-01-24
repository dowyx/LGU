<?php
// Start session
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit();
}

// Include database configuration
require_once 'config/database.php';

// Initialize variables
$error = '';
$email = '';
$password = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        try {
            // Query database for user
            $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Login successful - set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['login_time'] = time();
                
                // Update last login time
                $update_stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $update_stmt->execute([$user['id']]);
                
                // Redirect to dashboard
                header('Location: home.php');
                exit();
            } else {
                // Login failed
                $error = 'Invalid email or password';
                
                // Log failed login attempt
                $log_stmt = $pdo->prepare("INSERT INTO login_attempts (email, ip_address, attempted_at, success) VALUES (?, ?, NOW(), 0)");
                $log_stmt->execute([$email, $_SERVER['REMOTE_ADDR']]);
            }
        } catch (PDOException $e) {
            $error = 'Database error. Please try again later.';
            // Log actual error for debugging
            error_log("Login error: " . $e->getMessage());
        }
    }
}

// Check for demo mode (for development/testing)
$demo_mode = false;
if (isset($_GET['demo']) && $_GET['demo'] === '1') {
    $demo_mode = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Styles/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Login - Public Safety</title>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo" id="logo">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h1>Public Safety</h1>
            <p>Campaign Management System</p>
        </div>

        <div class="login-card">
            <h2 class="login-title">Login to Dashboard</h2>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="login.php" id="loginForm">
                <div class="form-group email-container">
                    <label class="form-label">
                        <i class="fas fa-envelope" style="margin-right: 8px;"></i>
                        Email Address
                    </label>
                    <div style="position: relative;">
                        <i class="fas fa-user input-icon"></i>
                        <input type="email"
                               id="email"
                               name="email"
                               class="form-input"
                               placeholder="john.doe@safety.gov"
                               value="<?php echo htmlspecialchars($email ?: 'john.doe@safety.gov'); ?>"
                               required>
                    </div>
                    <div class="error-message" id="email-error">Please enter a valid email address</div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock" style="margin-right: 8px;"></i>
                        Password
                    </label>
                    <div class="password-container">
                        <i class="fas fa-key input-icon"></i>
                        <input type="password"
                               id="password"
                               name="password"
                               class="form-input"
                               placeholder="Enter your password"
                               value="<?php echo htmlspecialchars($password ?: ($demo_mode ? 'demo123' : '')); ?>"
                               required>
                        <button type="button" class="toggle-password" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="error-message" id="password-error">Please enter your password</div>
                </div>

                <button type="submit" class="login-button" id="loginButton">
                    <i class="fas fa-sign-in-alt" style="margin-right: 10px;"></i>
                    Login to Dashboard
                </button>
            </form>

            <?php if ($demo_mode): ?>
            <div class="demo-credentials">
                <h4>
                    <i class="fas fa-info-circle"></i>
                    Demo Credentials
                </h4>
                <p>
                    <span class="highlight">Email:</span> john.doe@safety.gov<br>
                    <span class="highlight">Password:</span> demo123
                </p>
            </div>
            <?php endif; ?>

            <div class="login-footer">
                <p>For security reasons, please contact administrator for credentials</p>
            </div>
        </div>
    </div>

    <div class="loading-overlay" id="loadingOverlay" style="display: none;">
        <div class="loading-spinner"></div>
        <h3>Logging in...</h3>
        <p>Welcome to Public Safety System</p>
    </div>

    <script>
        // Form validation and submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            let isValid = true;
            
            // Reset error messages
            document.getElementById('email-error').style.display = 'none';
            document.getElementById('password-error').style.display = 'none';
            
            // Validate email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                document.getElementById('email-error').style.display = 'block';
                isValid = false;
            }
            
            // Validate password
            if (password.length < 1) {
                document.getElementById('password-error').style.display = 'block';
                isValid = false;
            }
            
            if (isValid) {
                // Show loading overlay
                document.getElementById('loadingOverlay').style.display = 'flex';
                
                // Submit form
                this.submit();
            }
        });
        
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Clear error messages on input
        document.getElementById('email').addEventListener('input', function() {
            document.getElementById('email-error').style.display = 'none';
        });
        
        document.getElementById('password').addEventListener('input', function() {
            document.getElementById('password-error').style.display = 'none';
        });
        
        // Auto-focus email field
        document.getElementById('email').focus();
        
        // Add keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const activeElement = document.activeElement;
                if (activeElement.id === 'email') {
                    e.preventDefault();
                    document.getElementById('password').focus();
                }
            }
        });
    </script>
    
    <?php if (file_exists('Scripts/utils.js')): ?>
    <script src="Scripts/utils.js"></script>
    <?php endif; ?>
</body>
</html>