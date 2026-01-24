<?php
// Logout script for Public Safety Campaign Management System

// Start session
session_start();

// Log the logout activity if database is available
try {
    require_once 'config/database.php';
    
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("INSERT INTO user_activity (user_id, activity_type, activity_time, ip_address) VALUES (?, 'logout', NOW(), ?)");
        $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR']]);
    }
} catch (Exception $e) {
    // Log error but continue with logout
    error_log("Logout logging error: " . $e->getMessage());
}

// Destroy all session data
$_SESSION = array();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>
