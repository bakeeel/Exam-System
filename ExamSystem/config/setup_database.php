<?php
require_once 'database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Create exams table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS exams (
        id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        duration INT NOT NULL,
        pass_percentage DECIMAL(5,2) NOT NULL,
        status ENUM('draft', 'active', 'inactive') DEFAULT 'draft',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create questions table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS questions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        exam_id INT NOT NULL,
        question_text TEXT NOT NULL,
        question_type ENUM('multiple_choice', 'true_false', 'essay') NOT NULL,
        marks INT NOT NULL,
        options TEXT,
        correct_answer TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
    )");

    // Create student_answers table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS student_answers (
        id INT PRIMARY KEY AUTO_INCREMENT,
        student_id INT NOT NULL,
        exam_id INT NOT NULL,
        question_id INT NOT NULL,
        answer_text TEXT NOT NULL,
        marks_obtained INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
        FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
    )");

    // Create exam_results table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS exam_results (
        id INT PRIMARY KEY AUTO_INCREMENT,
        student_id INT NOT NULL,
        exam_id INT NOT NULL,
        total_marks INT NOT NULL,
        obtained_marks INT NOT NULL,
        percentage DECIMAL(5,2) NOT NULL,
        passed BOOLEAN NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
    )");

    echo "All database tables created successfully!";

} catch(PDOException $e) {
    die("Error setting up database: " . $e->getMessage());
}
?>
