<?php
require_once 'database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Create student_answers table
    $sql = "CREATE TABLE IF NOT EXISTS student_answers (
        id INT PRIMARY KEY AUTO_INCREMENT,
        student_id INT NOT NULL,
        exam_id INT NOT NULL,
        question_id INT NOT NULL,
        answer_text TEXT NOT NULL,
        marks_obtained INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $db->exec($sql);
    echo "student_answers table created successfully!";

} catch(PDOException $e) {
    echo "Error creating student_answers table: " . $e->getMessage();
}
?>
