<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

try {
    if (isset($_POST['answers']) && is_array($_POST['answers'])) {
        $attempt_id = $_POST['attempt_id'];
        
        // Verify this attempt belongs to the current student
        $verify_query = "SELECT * FROM exam_attempts 
                        WHERE id = ? AND student_id = ? AND status = 'in_progress'";
        $verify_stmt = $db->prepare($verify_query);
        $verify_stmt->execute([$attempt_id, $_SESSION['student_id']]);
        
        if (!$verify_stmt->fetch()) {
            throw new Exception('Invalid attempt');
        }

        $db->beginTransaction();

        // Delete existing temporary answers
        $delete_query = "DELETE FROM temp_answers WHERE attempt_id = ?";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->execute([$attempt_id]);

        // Save current answers
        foreach ($_POST['answers'] as $question_id => $answer) {
            $save_query = "INSERT INTO temp_answers (attempt_id, question_id, answer_text) 
                          VALUES (?, ?, ?)";
            $save_stmt = $db->prepare($save_query);
            $save_stmt->execute([$attempt_id, $question_id, $answer]);
        }

        $db->commit();
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('No answers provided');
    }
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
