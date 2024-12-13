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
    
    try {
        $db->beginTransaction();
        
        // Insert question
        $question_query = "INSERT INTO questions (exam_id, question_text, question_type, marks) VALUES (?, ?, ?, ?)";
        $question_stmt = $db->prepare($question_query);
        $question_stmt->execute([
            $_POST['exam_id'],
            $_POST['question_text'],
            $_POST['question_type'],
            $_POST['marks']
        ]);
        
        $question_id = $db->lastInsertId();
        
        // Handle options based on question type
        if($_POST['question_type'] === 'multiple_choice') {
            $options = $_POST['options'];
            $correct_option = $_POST['correct_option'];
            
            $option_query = "INSERT INTO question_options (question_id, option_text, is_correct) VALUES (?, ?, ?)";
            $option_stmt = $db->prepare($option_query);
            
            foreach($options as $key => $option_text) {
                $is_correct = ($key == $correct_option) ? 1 : 0;
                $option_stmt->execute([$question_id, $option_text, $is_correct]);
            }
        } elseif($_POST['question_type'] === 'true_false') {
            $option_query = "INSERT INTO question_options (question_id, option_text, is_correct) VALUES (?, ?, ?)";
            $option_stmt = $db->prepare($option_query);
            
            // Add True option
            $option_stmt->execute([$question_id, 'True', $_POST['correct_answer'] == 1]);
            // Add False option
            $option_stmt->execute([$question_id, 'False', $_POST['correct_answer'] == 0]);
        }
        
        $db->commit();
        $_SESSION['success'] = "Question added successfully";
    } catch(Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "Failed to add question: " . $e->getMessage();
    }
    
    header("Location: questions.php");
    exit();
}
?>
