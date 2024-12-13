<?php
session_start();
require_once '../includes/header.php';
require_once '../config/database.php';

// Check if user is logged in and is an admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle question deletion
if(isset($_POST['delete_question'])) {
    $question_id = $_POST['question_id'];
    $delete_query = "DELETE FROM questions WHERE id = ?";
    $delete_stmt = $db->prepare($delete_query);
    if($delete_stmt->execute([$question_id])) {
        $_SESSION['success'] = "Question deleted successfully";
    } else {
        $_SESSION['error'] = "Failed to delete question";
    }
}

// Get all exams for filter
$exams_query = "SELECT id, title FROM exams ORDER BY title";
$exams_stmt = $db->prepare($exams_query);
$exams_stmt->execute();
$exams = $exams_stmt->fetchAll(PDO::FETCH_ASSOC);

// Filter by exam if specified
$exam_filter = isset($_GET['exam_id']) ? $_GET['exam_id'] : '';
$question_type_filter = isset($_GET['type']) ? $_GET['type'] : '';

// Build the questions query with filters
$questions_query = "SELECT q.*, e.title as exam_title 
                   FROM questions q 
                   LEFT JOIN exams e ON q.exam_id = e.id 
                   WHERE 1=1";
$query_params = [];

if($exam_filter) {
    $questions_query .= " AND q.exam_id = ?";
    $query_params[] = $exam_filter;
}

if($question_type_filter) {
    $questions_query .= " AND q.question_type = ?";
    $query_params[] = $question_type_filter;
}

$questions_query .= " ORDER BY q.created_at DESC";
$questions_stmt = $db->prepare($questions_query);
$questions_stmt->execute($query_params);
?>
<style>
    #COLO-WHITE{
    color: #fff !important;
}
</style>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" id="COLO-WHITE" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="COLO-WHITE" href="exams.php">
                            <i class="fas fa-file-alt me-2"></i>Manage Exams
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" id="COLO-WHITE" href="questions.php">
                            <i class="fas fa-question-circle me-2"></i>Question Bank
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="COLO-WHITE" href="students.php">
                            <i class="fas fa-users me-2"></i>Students
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="COLO-WHITE" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i>Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="COLO-WHITE" href="settings.php">
                            <i class="fas fa-cog me-2"></i>Settings
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Question Bank</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                        <i class="fas fa-plus me-2"></i>Add New Question
                    </button>
                </div>
            </div>

            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-success">Filters</h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="exam_id" class="form-label">Filter by Exam</label>
                            <select class="form-select" id="exam_id" name="exam_id">
                                <option value="">All Exams</option>
                                <?php foreach($exams as $exam): ?>
                                    <option value="<?php echo $exam['id']; ?>" 
                                            <?php echo $exam_filter == $exam['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($exam['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="type" class="form-label">Question Type</label>
                            <select class="form-select" id="type" name="type">
                                <option value="">All Types</option>
                                <option value="multiple_choice" <?php echo $question_type_filter == 'multiple_choice' ? 'selected' : ''; ?>>
                                    Multiple Choice
                                </option>
                                <option value="true_false" <?php echo $question_type_filter == 'true_false' ? 'selected' : ''; ?>>
                                    True/False
                                </option>
                                <option value="essay" <?php echo $question_type_filter == 'essay' ? 'selected' : ''; ?>>
                                    Essay
                                </option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary d-block">Apply Filters</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Questions List -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Question</th>
                                    <th>Type</th>
                                    <th>Exam</th>
                                    <th>Marks</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($question = $questions_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            $question_text = htmlspecialchars($question['question_text']);
                                            echo strlen($question_text) > 100 ? substr($question_text, 0, 100) . '...' : $question_text;
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $type_badges = [
                                                'multiple_choice' => 'bg-primary',
                                                'true_false' => 'bg-success',
                                                'essay' => 'bg-info'
                                            ];
                                            $type_display = [
                                                'multiple_choice' => 'Multiple Choice',
                                                'true_false' => 'True/False',
                                                'essay' => 'Essay'
                                            ];
                                            ?>
                                            <span class="badge <?php echo $type_badges[$question['question_type']]; ?>">
                                                <?php echo $type_display[$question['question_type']]; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($question['exam_title']); ?></td>
                                        <td><?php echo $question['marks']; ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="editQuestion(<?php echo $question['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('Are you sure you want to delete this question?');">
                                                    <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                                    <button type="submit" name="delete_question" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-sm btn-outline-info" 
                                                        onclick="viewQuestion(<?php echo $question['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add Question Modal -->
<div class="modal fade" id="addQuestionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="questionForm" action="add_question.php" method="POST">
                    <div class="mb-3">
                        <label for="exam_id" class="form-label">Select Exam</label>
                        <select class="form-select" id="modal_exam_id" name="exam_id" required>
                            <?php foreach($exams as $exam): ?>
                                <option value="<?php echo $exam['id']; ?>">
                                    <?php echo htmlspecialchars($exam['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="question_type" class="form-label">Question Type</label>
                        <select class="form-select" id="question_type" name="question_type" required>
                            <option value="multiple_choice">Multiple Choice</option>
                            <option value="true_false">True/False</option>
                            <option value="essay">Essay</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="question_text" class="form-label">Question Text</label>
                        <textarea class="form-control" id="question_text" name="question_text" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="marks" class="form-label">Marks</label>
                        <input type="number" class="form-control" id="marks" name="marks" min="1" value="1" required>
                    </div>
                    <div id="options_container">
                        <!-- Options will be dynamically added here based on question type -->
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" form="questionForm" class="btn btn-primary">Save Question</button>
            </div>
        </div>
    </div>
</div>

<script>
// Function to handle question type change
document.getElementById('question_type').addEventListener('change', function() {
    const container = document.getElementById('options_container');
    container.innerHTML = '';
    
    if(this.value === 'multiple_choice') {
        container.innerHTML = `
            <div class="mb-3">
                <label class="form-label">Options</label>
                <div id="options">
                    <div class="input-group mb-2">
                        <div class="input-group-text">
                            <input type="radio" name="correct_option" value="0" required>
                        </div>
                        <input type="text" class="form-control" name="options[]" placeholder="Option 1" required>
                    </div>
                    <div class="input-group mb-2">
                        <div class="input-group-text">
                            <input type="radio" name="correct_option" value="1" required>
                        </div>
                        <input type="text" class="form-control" name="options[]" placeholder="Option 2" required>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addOption()">
                    Add Option
                </button>
            </div>
        `;
    } else if(this.value === 'true_false') {
        container.innerHTML = `
            <div class="mb-3">
                <label class="form-label">Correct Answer</label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="correct_answer" value="1" required>
                    <label class="form-check-label">True</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="correct_answer" value="0" required>
                    <label class="form-check-label">False</label>
                </div>
            </div>
        `;
    }
});

// Function to add more options for multiple choice questions
function addOption() {
    const optionsDiv = document.getElementById('options');
    const optionCount = optionsDiv.children.length;
    
    const newOption = document.createElement('div');
    newOption.className = 'input-group mb-2';
    newOption.innerHTML = `
        <div class="input-group-text">
            <input type="radio" name="correct_option" value="${optionCount}" required>
        </div>
        <input type="text" class="form-control" name="options[]" placeholder="Option ${optionCount + 1}" required>
        <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    optionsDiv.appendChild(newOption);
}

// Function to edit question (to be implemented)
function editQuestion(questionId) {
    // Implementation for editing question
    window.location.href = `edit_question.php?id=${questionId}`;
}

// Function to view question details (to be implemented)
function viewQuestion(questionId) {
    // Implementation for viewing question details
    window.location.href = `view_question.php?id=${questionId}`;
}
</script>

<?php require_once '../includes/footer.php'; ?>
