<?php
session_start();
require_once '../config/database.php';
require_once '../includes/header.php';

// Check if user is logged in and is an admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get exam data from session storage (if creating new exam)
$exam_data = null;
$is_preview = true;

if(isset($_GET['id'])) {
    // Loading existing exam
    $exam_query = "SELECT e.*, COUNT(DISTINCT q.id) as question_count, 
                    SUM(q.marks) as total_marks
                  FROM exams e
                  LEFT JOIN questions q ON e.id = q.exam_id
                  WHERE e.id = ?
                  GROUP BY e.id";
    
    $exam_stmt = $db->prepare($exam_query);
    $exam_stmt->execute([$_GET['id']]);
    $exam_data = $exam_stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$exam_data) {
        $_SESSION['error'] = "Exam not found";
        header("Location: exams.php");
        exit();
    }
    
    // Get questions
    $questions_query = "SELECT q.*, 
                        GROUP_CONCAT(
                            CASE 
                                WHEN qo.is_correct = 1 THEN CONCAT(qo.id, ':1')
                                ELSE CONCAT(qo.id, ':0')
                            END
                            ORDER BY qo.id
                        ) as options
                      FROM questions q
                      LEFT JOIN question_options qo ON q.id = qo.question_id
                      WHERE q.exam_id = ?
                      GROUP BY q.id
                      ORDER BY q.id";
    
    $questions_stmt = $db->prepare($questions_query);
    $questions_stmt->execute([$_GET['id']]);
    $questions = $questions_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} else {
    // Get preview data from session storage
    $preview_data = isset($_SESSION['exam_preview']) ? $_SESSION['exam_preview'] : null;
    if(!$preview_data) {
        $_SESSION['error'] = "No exam data found for preview";
        header("Location: exams.php");
        exit();
    }
    
    $exam_data = json_decode($preview_data, true);
}
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="exams.php">
                            <i class="fas fa-file-alt me-2"></i>Manage Exams
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="questions.php">
                            <i class="fas fa-question-circle me-2"></i>Question Bank
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="students.php">
                            <i class="fas fa-users me-2"></i>Students
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i>Reports
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Exam Preview</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="exams.php" class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Back to Exams
                    </a>
                    <?php if(isset($_GET['id'])): ?>
                        <a href="edit_exam.php?id=<?php echo $_GET['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Edit Exam
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Exam Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Exam Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Title:</strong> <?php echo htmlspecialchars($exam_data['title']); ?></p>
                            <p><strong>Duration:</strong> <?php echo $exam_data['duration']; ?> minutes</p>
                            <p><strong>Pass Percentage:</strong> <?php echo $exam_data['pass_percentage']; ?>%</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Total Questions:</strong> <?php echo $exam_data['question_count'] ?? count($exam_data['questions']); ?></p>
                            <p><strong>Total Marks:</strong> <?php echo $exam_data['total_marks'] ?? 'N/A'; ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?php echo $exam_data['status'] == 'published' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($exam_data['status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <p><strong>Description:</strong></p>
                            <p><?php echo nl2br(htmlspecialchars($exam_data['description'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Questions Preview -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Questions</h5>
                </div>
                <div class="card-body">
                    <?php 
                    $questions_to_display = isset($questions) ? $questions : $exam_data['questions'];
                    foreach($questions_to_display as $index => $question): 
                    ?>
                        <div class="question-item mb-4 p-3 border rounded">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="mb-3">Question <?php echo $index + 1; ?></h5>
                                <span class="badge bg-primary"><?php echo $question['marks']; ?> marks</span>
                            </div>
                            
                            <p class="mb-3"><?php echo nl2br(htmlspecialchars($question['question_text'])); ?></p>
                            
                            <?php if($question['question_type'] == 'multiple_choice'): ?>
                                <div class="options-list">
                                    <?php 
                                    $options = explode(',', $question['options']);
                                    foreach($options as $option):
                                        list($option_id, $is_correct) = explode(':', $option);
                                    ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" disabled 
                                                   <?php echo $is_correct ? 'checked' : ''; ?>>
                                            <label class="form-check-label <?php echo $is_correct ? 'text-success fw-bold' : ''; ?>">
                                                <?php echo htmlspecialchars($option_text); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php elseif($question['question_type'] == 'true_false'): ?>
                                <div class="options-list">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" disabled 
                                               <?php echo $question['correct_answer'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label">True</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" disabled 
                                               <?php echo !$question['correct_answer'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label">False</label>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="essay-guidelines">
                                    <p class="text-muted"><strong>Answer Guidelines:</strong></p>
                                    <p><?php echo nl2br(htmlspecialchars($question['guidelines'] ?? 'No guidelines provided.')); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Add any preview-specific JavaScript here
document.addEventListener('DOMContentLoaded', function() {
    // Disable all form elements in preview mode
    document.querySelectorAll('input, textarea, select, button').forEach(function(element) {
        if (!element.classList.contains('btn-back')) {
            element.disabled = true;
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
