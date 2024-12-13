<?php
session_start();
require_once '../includes/header.php';
require_once '../config/database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Please log in as an administrator";
    header("Location: ../index.php");
    exit();
}

$question_id = $_GET['id'];

try {
    // Create a database connection
    $database = new Database();
    $db = $database->getConnection();

    // Prepare and execute the query
    $query = "SELECT * FROM questions WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $question_id);
    $stmt->execute();

    // Fetch the question
    $question = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$question) {
        die('Error: Question not found.');
    }

   
// Display the question details
echo '<div class="container my-5">';
echo '<h1 class="text-center mb-4">Question Details</h1>';
echo '<div class="row">';

// Question Text
echo '<div class="col-md-6 mb-4">';
echo '<div class="card">';
echo '<div class="card-header"><strong>Text</strong></div>';
echo '<div class="card-body">';
echo '<p>' . htmlspecialchars($question['question_text']) . '</p>';
echo '</div>';
echo '</div>';
echo '</div>';

// Question Type
echo '<div class="col-md-6 mb-4">';
echo '<div class="card">';
echo '<div class="card-header"><strong>Type</strong></div>';
echo '<div class="card-body">';
echo '<p>' . htmlspecialchars($question['question_type']) . '</p>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '</div>'; // Close row

// Marks Section
echo '<div class="row">';
echo '<div class="col-md-6 mb-4">';
echo '<div class="card">';
echo '<div class="card-header"><strong>Marks</strong></div>';
echo '<div class="card-body">';
echo '<p>' . htmlspecialchars($question['marks']) . '</p>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '</div>'; // Close row

echo '</div>'; // Close container

} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}
?>
