<?php
session_start();
require_once '../includes/header.php';
require_once '../config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if exam ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid exam ID";
    header("Location: exams.php");
    exit();
}

$exam_id = $_GET['id'];

try {
    // Fetch exam details
    $exam_query = "SELECT * FROM exams WHERE id = ?";
    $exam_stmt = $db->prepare($exam_query);
    $exam_stmt->execute([$exam_id]);
    $exam = $exam_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        $_SESSION['error'] = "Exam not found";
        header("Location: exams.php");
        exit();
    }

    // Fetch questions for this exam
    $question_query = "SELECT * FROM questions WHERE exam_id = ?";
    $question_stmt = $db->prepare($question_query);
    $question_stmt->execute([$exam_id]);
    $questions = $question_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $db->beginTransaction();

        try {
            // Update exam details
            $update_exam_query = "UPDATE exams SET title = ?, description = ?, duration = ?, pass_percentage = ? WHERE id = ?";
            $update_exam_stmt = $db->prepare($update_exam_query);
            $update_exam_stmt->execute([
                $_POST['title'],
                $_POST['description'],
                $_POST['duration'],
                $_POST['pass_percentage'],
                $exam_id
            ]);

            // Delete existing questions
            $delete_questions_query = "DELETE FROM questions WHERE exam_id = ?";
            $delete_questions_stmt = $db->prepare($delete_questions_query);
            $delete_questions_stmt->execute([$exam_id]);

            // Insert updated questions
            if (isset($_POST['questions']) && is_array($_POST['questions'])) {
                foreach ($_POST['questions'] as $question) {
                    if (!isset($question['text'], $question['type'], $question['marks'])) {
                        throw new Exception("Missing required question fields");
                    }

                    $question_query = "INSERT INTO questions (exam_id, question_text, question_type, marks) 
                                     VALUES (?, ?, ?, ?)";
                    $question_stmt = $db->prepare($question_query);
                    $question_stmt->execute([
                        $exam_id,
                        $question['text'],
                        $question['type'],
                        $question['marks']
                    ]);

                    // Handle options for multiple choice questions
                    if ($question['type'] === 'multiple_choice' && isset($question['options'])) {
                        $question_id = $db->lastInsertId();
                        foreach ($question['options'] as $option) {
                            $option_query = "INSERT INTO question_options (question_id, option_text, is_correct) 
                                           VALUES (?, ?, ?)";
                            $option_stmt = $db->prepare($option_query);
                            $option_stmt->execute([
                                $question_id,
                                $option['text'],
                                isset($option['is_correct']) ? 1 : 0
                            ]);
                        }
                    }
                }
            }

            $db->commit();
            $_SESSION['success'] = "Exam updated successfully";
            header("Location: exams.php");
            exit();

        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = "Failed to update exam: " . $e->getMessage();
        }
    }

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: exams.php");
    exit();
}
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
                        <a class="nav-link" id="COLO-WHITE"href="dashboard.php">
                            <i class="fas fa-home me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" id="COLO-WHITE" href="exams.php">
                            <i class="fas fa-file-alt me-2"></i>Exams
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="COLO-WHITE" href="students.php">
                            <i class="fas fa-users me-2"></i>Students
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="COLO-WHITE" href="results.php">
                            <i class="fas fa-chart-bar me-2"></i>Results
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Edit Exam</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="exams.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Exams
                    </a>
                </div>
            </div>

            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form id="editExamForm" method="POST" class="needs-validation" novalidate>
                <!-- Exam Details -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Exam Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="title" class="form-label">Exam Title</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo htmlspecialchars($exam['title']); ?>" required>
                                <div class="invalid-feedback">Please provide an exam title.</div>
                            </div>
                            <div class="col-md-3">
                                <label for="duration" class="form-label">Duration (minutes)</label>
                                <input type="number" class="form-control" id="duration" name="duration" 
                                       value="<?php echo htmlspecialchars($exam['duration']); ?>" min="1" required>
                                <div class="invalid-feedback">Please specify the exam duration.</div>
                            </div>
                            <div class="col-md-3">
                                <label for="pass_percentage" class="form-label">Pass Percentage</label>
                                <input type="number" class="form-control" id="pass_percentage" name="pass_percentage" 
                                       value="<?php echo htmlspecialchars($exam['pass_percentage']); ?>"
                                       min="0" max="100" required>
                                <div class="invalid-feedback">Please specify the pass percentage.</div>
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="3" required><?php echo htmlspecialchars($exam['description']); ?></textarea>
                                <div class="invalid-feedback">Please provide an exam description.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Questions Section -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-success">Questions</h6>
                        <button type="button" class="btn btn-success btn-sm" onclick="addQuestion()">
                            <i class="fas fa-plus me-2"></i>Add Question
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="questionsContainer">
                            <?php foreach ($questions as $index => $question): ?>
                            <div class="question-item card mb-3">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Question #<?php echo $index + 1; ?></h6>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeQuestion(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <label class="form-label">Question Text</label>
                                            <textarea class="form-control" name="questions[<?php echo $index; ?>][text]" 
                                                      rows="2" required><?php echo htmlspecialchars($question['question_text']); ?></textarea>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Type</label>
                                            <select class="form-select" name="questions[<?php echo $index; ?>][type]" 
                                                    onchange="handleQuestionTypeChange(this)" required>
                                                <option value="multiple_choice" <?php echo $question['question_type'] === 'multiple_choice' ? 'selected' : ''; ?>>Multiple Choice</option>
                                                <option value="true_false" <?php echo $question['question_type'] === 'true_false' ? 'selected' : ''; ?>>True/False</option>
                                                <option value="essay" <?php echo $question['question_type'] === 'essay' ? 'selected' : ''; ?>>Essay</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Marks</label>
                                            <input type="number" class="form-control" name="questions[<?php echo $index; ?>][marks]" 
                                                   value="<?php echo htmlspecialchars($question['marks']); ?>" min="1" required>
                                        </div>
                                        <div class="col-12 options-container">
                                            <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                                <!-- Load options here -->
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="text-end mb-4">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </main>
    </div>
</div>

<!-- Question Template (Hidden) -->
<template id="questionTemplate">
    <div class="question-item card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Question #<span class="question-number"></span></h6>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeQuestion(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Question Text</label>
                    <textarea class="form-control" name="questions[0][text]" rows="2" required></textarea>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select class="form-select" name="questions[0][type]" onchange="handleQuestionTypeChange(this)" required>
                        <option value="multiple_choice">Multiple Choice</option>
                        <option value="true_false">True/False</option>
                        <option value="essay">Essay</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Marks</label>
                    <input type="number" class="form-control" name="questions[0][marks]" min="1" value="1" required>
                </div>
                <div class="col-12 options-container">
                    <!-- Options will be added here based on question type -->
                </div>
            </div>
        </div>
    </div>
</template>

<script>
// Function to add a new question
function addQuestion() {
    const container = document.getElementById('questionsContainer');
    const template = document.getElementById('questionTemplate');
    const questionCount = container.children.length + 1;
    
    // Clone template
    const clone = template.content.cloneNode(true);
    
    // Update question number
    clone.querySelector('.question-number').textContent = questionCount;
    
    // Update question index in form fields
    const questionIndex = questionCount - 1;
    clone.querySelectorAll('input, select, textarea').forEach(field => {
        if (field.name && field.name.includes('questions[')) {
            field.name = field.name.replace(/questions\[\d+\]/, `questions[${questionIndex}]`);
        }
    });
    
    // Add to container
    container.appendChild(clone);
}

// Function to remove a question
function removeQuestion(button) {
    const questionItem = button.closest('.question-item');
    questionItem.remove();
    
    // Update remaining question numbers
    const container = document.getElementById('questionsContainer');
    container.querySelectorAll('.question-item').forEach((item, index) => {
        item.querySelector('.question-number').textContent = index + 1;
        
        // Update field names
        item.querySelectorAll('input, select, textarea').forEach(field => {
            if (field.name && field.name.includes('questions[')) {
                field.name = field.name.replace(/questions\[\d+\]/, `questions[${index}]`);
            }
        });
    });
}

// Function to handle question type changes
function handleQuestionTypeChange(select) {
    const optionsContainer = select.closest('.row').querySelector('.options-container');
    const questionIndex = Array.from(document.querySelectorAll('.question-item')).indexOf(select.closest('.question-item'));
    
    switch(select.value) {
        case 'multiple_choice':
            optionsContainer.innerHTML = `
                <div class="options-list">
                    <div class="option-item mb-2">
                        <div class="input-group">
                            <div class="input-group-text">
                                <input type="radio" name="questions[${questionIndex}][correct_option]" value="0" required>
                            </div>
                            <input type="text" class="form-control" name="questions[${questionIndex}][options][]" 
                                   placeholder="Option 1" required>
                            <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addOption(this)">
                    <i class="fas fa-plus me-2"></i>Add Option
                </button>
            `;
            break;
            
        case 'true_false':
            optionsContainer.innerHTML = `
                <div class="true-false-options">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="questions[${questionIndex}][correct_option]" 
                               value="true" id="true${questionIndex}" required>
                        <label class="form-check-label" for="true${questionIndex}">True</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="questions[${questionIndex}][correct_option]" 
                               value="false" id="false${questionIndex}" required>
                        <label class="form-check-label" for="false${questionIndex}">False</label>
                    </div>
                </div>
            `;
            break;
            
        default:
            optionsContainer.innerHTML = '';
    }
}

// Function to add an option to multiple choice question
function addOption(button) {
    const optionsList = button.previousElementSibling;
    const questionIndex = Array.from(document.querySelectorAll('.question-item'))
        .indexOf(button.closest('.question-item'));
    const optionCount = optionsList.children.length + 1;
    
    const optionHtml = `
        <div class="option-item mb-2">
            <div class="input-group">
                <div class="input-group-text">
                    <input type="radio" name="questions[${questionIndex}][correct_option]" value="${optionCount - 1}" required>
                </div>
                <input type="text" class="form-control" name="questions[${questionIndex}][options][]" 
                       placeholder="Option ${optionCount}" required>
                <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
    
    optionsList.insertAdjacentHTML('beforeend', optionHtml);
}

// Function to remove an option
function removeOption(button) {
    const optionItem = button.closest('.option-item');
    const optionsList = optionItem.parentElement;
    
    if (optionsList.children.length > 1) {
        optionItem.remove();
        
        // Update remaining option numbers
        optionsList.querySelectorAll('.option-item').forEach((item, index) => {
            const input = item.querySelector('input[type="text"]');
            input.placeholder = `Option ${index + 1}`;
            
            const radio = item.querySelector('input[type="radio"]');
            radio.value = index;
        });
    }
}

// Form validation
(function () {
    'use strict'
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();

// Initialize question type handlers for existing questions
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('select[name$="[type]"]').forEach(select => {
        handleQuestionTypeChange(select);
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
