<?php
// Start the session if not already started
session_start();

// Verify if user is logged in and is an admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Log the logout action (optional)
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

try {
    // Record logout time
    $logout_query = "INSERT INTO user_logs (user_id, action, action_time) VALUES (?, 'logout', NOW())";
    $stmt = $db->prepare($logout_query);
    $stmt->execute([$_SESSION['user_id']]);
} catch (Exception $e) {
    // Continue with logout even if logging fails
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Clear any other cookies if they exist
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/');
}

// Set a logout message in a temporary cookie
setcookie('logout_message', 'You have been successfully logged out.', time() + 5, '/');

// Redirect to login page
header("Location: ../login.php");
exit();
