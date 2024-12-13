<?php
session_start();
require_once '../includes/header.php';
require_once '../config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if student is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    $_SESSION['error'] = "Please log in as a student";
    header("Location: ../index.php");
    exit();
}

// Check if exam ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid exam ID";
    header("Location: dashboard.php");
    exit();
}

$exam_id = $_GET['id'];
$student_id = $_SESSION['user_id'];

try {
    // Fetch exam details
    $exam_query = "SELECT * FROM exams WHERE id = ?";
    $exam_stmt = $db->prepare($exam_query);
    $exam_stmt->execute([$exam_id]);
    $exam = $exam_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        $_SESSION['error'] = "Exam not found";
        header("Location: dashboard.php");
        exit();
    }

    // Fetch questions
    $question_query = "SELECT * FROM questions WHERE exam_id = ? ORDER BY id";
    $question_stmt = $db->prepare($question_query);
    $question_stmt->execute([$exam_id]);
    $questions = $question_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $db->beginTransaction();

        try {
            // Calculate score
            $total_marks = 0;
            $obtained_marks = 0;

            foreach ($questions as $question) {
                $total_marks += $question['marks'];
                
                if (isset($_POST['answers'][$question['id']])) {
                    $answer = $_POST['answers'][$question['id']];
                    $marks_obtained = 0;
                    
                    // For multiple choice and true/false questions
                    if ($question['question_type'] !== 'essay') {
                        if (isset($question['correct_answer']) && $answer === $question['correct_answer']) {
                            $marks_obtained = $question['marks'];
                            $obtained_marks += $marks_obtained;
                        }
                    }

                    // Save student's answer
                    $save_answer_query = "INSERT INTO student_answers 
                                        (student_id, exam_id, question_id, answer_text, marks_obtained) 
                                        VALUES (?, ?, ?, ?, ?)";
                    $save_answer_stmt = $db->prepare($save_answer_query);
                    $save_answer_stmt->execute([
                        $student_id,
                        $exam_id,
                        $question['id'],
                        $answer,
                        $marks_obtained
                    ]);
                }
            }

            // Calculate percentage
            $percentage = ($total_marks > 0) ? ($obtained_marks / $total_marks) * 100 : 0;
            $passed = $percentage >= $exam['pass_percentage'];

            // Save exam result
            $result_query = "INSERT INTO exam_results 
                            (student_id, exam_id, total_marks, obtained_marks, percentage, passed) 
                            VALUES (?, ?, ?, ?, ?, ?)";
            $result_stmt = $db->prepare($result_query);
            $result_stmt->execute([
                $student_id,
                $exam_id,
                $total_marks,
                $obtained_marks,
                $percentage,
                $passed ? 1 : 0
            ]);

            $db->commit();
            $_SESSION['success'] = "Exam submitted successfully! View your results in the My Results section.";
            header("Location: view_results.php");
            exit();

        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = "Failed to submit exam: " . $e->getMessage();
        }
    }

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: dashboard.php");
    exit();
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary"><?php echo htmlspecialchars($exam['title']); ?></h6>
                    <div class="text-muted">
                        Duration: <?php echo $exam['duration']; ?> minutes | 
                        Pass Percentage: <?php echo $exam['pass_percentage']; ?>%
                    </div>
                </div>
                <div class="card-body">
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php 
                            echo $_SESSION['error'];
                            unset($_SESSION['error']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-info mb-4">
                        <?php echo htmlspecialchars($exam['description']); ?>
                    </div>

                    <form method="POST" id="examForm" onsubmit="return confirm('Are you sure you want to submit your exam?');">
                        <?php if(empty($questions)): ?>
                            <div class="alert alert-warning">
                                No questions available for this exam.
                            </div>
                        <?php else: ?>
                            <?php foreach ($questions as $index => $question): ?>
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <strong>Question <?php echo $index + 1; ?></strong>
                                        <span class="float-end"><?php echo $question['marks']; ?> marks</span>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-3"><?php echo htmlspecialchars($question['question_text']); ?></p>
                                        
                                        <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                            <?php 
                                            $options = explode('|', $question['options']);
                                            foreach ($options as $option): 
                                            ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" 
                                                           name="answers[<?php echo $question['id']; ?>]" 
                                                           value="<?php echo htmlspecialchars($option); ?>" required>
                                                    <label class="form-check-label">
                                                        <?php echo htmlspecialchars($option); ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>

                                        <?php elseif ($question['question_type'] === 'true_false'): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" 
                                                       name="answers[<?php echo $question['id']; ?>]" 
                                                       value="true" required>
                                                <label class="form-check-label">True</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" 
                                                       name="answers[<?php echo $question['id']; ?>]" 
                                                       value="false" required>
                                                <label class="form-check-label">False</label>
                                            </div>

                                        <?php else: ?>
                                            <textarea class="form-control" 
                                                      name="answers[<?php echo $question['id']; ?>]" 
                                                      rows="4" required></textarea>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Exam
                                </button>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Prevent accidental navigation
window.onbeforeunload = function() {
    return "Are you sure you want to leave? Your exam progress will be lost.";
};

// Remove navigation warning when submitting form
document.getElementById('examForm').addEventListener('submit', function() {
    window.onbeforeunload = null;
});
</script>

<?php require_once '../includes/footer.php'; ?>
