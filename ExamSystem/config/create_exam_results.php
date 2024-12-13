<?php
require_once 'database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Create exam_results table
    $sql = "CREATE TABLE IF NOT EXISTS exam_results (
        id INT PRIMARY KEY AUTO_INCREMENT,
        student_id INT NOT NULL,
        exam_id INT NOT NULL,
        total_marks INT NOT NULL,
        obtained_marks INT NOT NULL,
        percentage DECIMAL(5,2) NOT NULL,
        passed BOOLEAN NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $db->exec($sql);
    echo "exam_results table created successfully!";

} catch(PDOException $e) {
    echo "Error creating exam_results table: " . $e->getMessage();
}
?>
