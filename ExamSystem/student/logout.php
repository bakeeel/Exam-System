<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a student
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Check for any in-progress exams
    $check_query = "SELECT id FROM exam_results 
                   WHERE student_id = ? 
                   AND status = 'in_progress'";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$_SESSION['user_id']]);
    
    if($check_stmt->fetch()) {
        // Update in-progress exams to abandoned
        $update_query = "UPDATE exam_results 
                        SET status = 'abandoned', 
                            updated_at = CURRENT_TIMESTAMP 
                        WHERE student_id = ? 
                        AND status = 'in_progress'";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->execute([$_SESSION['user_id']]);
    }

    // Log the logout action
    $log_query = "INSERT INTO user_logs (user_id, action, action_time) 
                 VALUES (?, 'logout', NOW())";
    $log_stmt = $db->prepare($log_query);
    $log_stmt->execute([$_SESSION['user_id']]);

} catch(Exception $e) {
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
