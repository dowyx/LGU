<?php
// Database configuration for Public Safety Campaign Management System

// Database connection settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'public_safety_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

<<<<<<< HEAD
// Error reporting - Show ALL errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Security settings
define('SESSION_LIFETIME', 3600); // 1 hour in seconds
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_TIMEOUT', 900); // 15 minutes

// Session handling - silent approach
if (session_status() === PHP_SESSION_NONE) {
    // Try to configure session quietly
    @ini_set('session.cookie_httponly', 1);
    @ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
    @ini_set('session.use_only_cookies', 1);
    @ini_set('session.cookie_samesite', 'Strict');
    
    // Start session
    @session_start();
}
=======
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 1 in development, 0 in production
>>>>>>> a5ee48574ab959bafe1d5a07ba89c68909282e5a

// Create PDO connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
<<<<<<< HEAD
=======
        PDO::ATTR_PERSISTENT         => true,
>>>>>>> a5ee48574ab959bafe1d5a07ba89c68909282e5a
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
<<<<<<< HEAD
    // Test connection
    $pdo->query("SELECT 1");
    
} catch (PDOException $e) {
    // Show detailed error for debugging
    die("Database Connection Error Details:<br><br>
         Error Message: " . $e->getMessage() . "<br><br>
         Please check:<br>
         1. Is MySQL running in XAMPP?<br>
         2. Is the database 'public_safety_db' created?<br>
         3. Check phpMyAdmin to verify<br>
         4. Default XAMPP password is empty (no password)");
}

=======
} catch (PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database connection failed: " . $e->getMessage());
    
    // In production, show a generic error message
    if (defined('PRODUCTION') && PRODUCTION) {
        die("Database connection failed. Please contact the administrator.");
    } else {
        // In development, show detailed error
        die("Database connection failed: " . $e->getMessage());
    }
}

// Security settings
define('SESSION_LIFETIME', 3600); // 1 hour in seconds
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_TIMEOUT', 900); // 15 minutes

>>>>>>> a5ee48574ab959bafe1d5a07ba89c68909282e5a
// Helper functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function is_session_valid() {
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['login_time']) && 
           (time() - $_SESSION['login_time'] < SESSION_LIFETIME);
}

function regenerate_session() {
    session_regenerate_id(true);
    $_SESSION['login_time'] = time();
}

function check_login_attempts($pdo, $email) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as attempts 
        FROM login_attempts 
        WHERE email = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
    ");
    $stmt->execute([$email, LOGIN_ATTEMPT_TIMEOUT]);
    $attempts = $stmt->fetch(PDO::FETCH_ASSOC)['attempts'];
    
    return $attempts < MAX_LOGIN_ATTEMPTS;
}

function log_login_attempt($pdo, $email, $success) {
    $stmt = $pdo->prepare("
        INSERT INTO login_attempts (email, ip_address, attempted_at, success) 
        VALUES (?, ?, NOW(), ?)
    ");
    $stmt->execute([$email, $_SERVER['REMOTE_ADDR'], $success ? 1 : 0]);
}
<<<<<<< HEAD
?>
=======

// Initialize session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
>>>>>>> a5ee48574ab959bafe1d5a07ba89c68909282e5a
