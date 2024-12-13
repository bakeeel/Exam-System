<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'student';
    // $status = 'active';
    
    try {
        // Check if email already exists
        $check_query = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$email]);
        
        if($check_stmt->rowCount() > 0) {
            $_SESSION['error'] = "Email already exists";
        } else {
            // Insert new student
            $insert_query = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
            $insert_stmt = $db->prepare($insert_query);
            
            if($insert_stmt->execute([$name, $email, $password, $role])) {
                $_SESSION['success'] = "Student added successfully";
            } else {
                $_SESSION['error'] = "Failed to add student";
            }
        }
    } catch(Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: students.php");
    exit();
}
?>
