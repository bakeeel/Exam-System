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

// Handle exam creation
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Insert exam details
        $exam_query = "INSERT INTO exams (title, description, duration, pass_percentage, status, created_by) 
                      VALUES (?, ?, ?, ?, ?, ?)";
        $exam_stmt = $db->prepare($exam_query);
        $exam_stmt->execute([
            $_POST['title'],
            $_POST['description'],
            $_POST['duration'],
            $_POST['pass_percentage'],
            'draft',
            $_SESSION['user_id']
        ]);
        
        $exam_id = $db->lastInsertId();
        
        // Insert questions if any were submitted
        if(isset($_POST['questions']) && is_array($_POST['questions'])) {
            foreach($_POST['questions'] as $index => $question) {
                // Debug logging
                error_log("Question $index data: " . print_r($question, true));
                
                // Ensure all required fields are present
                if(!isset($question['text'], $question['type'], $question['marks'])) {
                    error_log("Missing fields - Text: " . isset($question['text']) . 
                             ", Type: " . isset($question['type']) . 
                             ", Marks: " . isset($question['marks']));
                    throw new Exception("Missing required question fields");
                }

                // Insert question
                $question_query = "INSERT INTO questions (exam_id, question_text, question_type, marks) 
                                 VALUES (?, ?, ?, ?)";
                $question_stmt = $db->prepare($question_query);
                $question_stmt->execute([
                    $exam_id,
                    $question['text'],
                    $question['type'],
                    $question['marks']
                    


                ]);
                
                $question_id = $db->lastInsertId();
                
                // Handle options based on question type
                if($question['type'] === 'multiple_choice' && isset($question['options'])) {
                    $option_query = "INSERT INTO question_options (question_id, option_text, is_correct) 
                                   VALUES (?, ?, ?)";
                    $option_stmt = $db->prepare($option_query);
                    
                    foreach($question['options'] as $key => $option) {
                        $is_correct = (isset($question['correct_option']) && $key == $question['correct_option']) ? 1 : 0;
                        $option_stmt->execute([$question_id, $option, $is_correct]);
                    }
                } elseif($question['type'] === 'true_false' && isset($question['correct_option'])) {
                    $option_query = "INSERT INTO question_options (question_id, option_text, is_correct) 
                                   VALUES (?, ?, ?), (?, ?, ?)";
                    $option_stmt = $db->prepare($option_query);
                    $correct_value = $question['correct_option'];
                    $option_stmt->execute([
                        $question_id, 'True', ($correct_value == 1 ? 1 : 0),
                        $question_id, 'False', ($correct_value == 0 ? 1 : 0)
                    ]);
                } elseif($question['type'] === 'essay' && isset($question['guidelines'])) {
                    // Update question with guidelines for essay type
                    $update_query = "UPDATE questions SET guidelines = ? WHERE id = ?";
                    $update_stmt = $db->prepare($update_query);
                    $update_stmt->execute([$question['guidelines'], $question_id]);
                }
            }
        }
        
        $db->commit();
        $_SESSION['success'] = "Exam created successfully";
        header("Location: exams.php");
        exit();
        
    } catch(Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "Failed to create exam: " . $e->getMessage();
    }
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
                        <a class="nav-link" id="COLO-WHITE" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" id="COLO-WHITE" href="exams.php">
                            <i class="fas fa-file-alt me-2"></i>Manage Exams
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="COLO-WHITE" href="questions.php">
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
                <h1 class="h2">Create New Exam</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="exams.php" class="btn btn-success">
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

            <form id="createExamForm" method="POST" class="needs-validation" novalidate>
                <!-- Exam Details -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-success">Exam Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="title" class="form-label">Exam Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                                <div class="invalid-feedback">Please provide an exam title.</div>
                            </div>
                            <div class="col-md-3">
                                <label for="duration" class="form-label">Duration (minutes)</label>
                                <input type="number" class="form-control" id="duration" name="duration" min="1" required>
                                <div class="invalid-feedback">Please specify the exam duration.</div>
                            </div>
                            <div class="col-md-3">
                                <label for="pass_percentage" class="form-label">Pass Percentage</label>
                                <input type="number" class="form-control" id="pass_percentage" name="pass_percentage" 
                                       min="0" max="100" required>
                                <div class="invalid-feedback">Please specify the pass percentage.</div>
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
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
                            <!-- Questions will be added here dynamically -->
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="row mb-4">
                    <div class="col-12">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Create Exam
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="previewExam()">
                            <i class="fas fa-eye me-2"></i>Preview
                        </button>
                    </div>
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
let questionCount = 0;

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
    
    // Initialize the question type
    const typeSelect = container.lastElementChild.querySelector('select');
    handleQuestionTypeChange(typeSelect);
}

// Function to remove a question
function removeQuestion(button) {
    if(confirm('Are you sure you want to remove this question?')) {
        button.closest('.question-item').remove();
        updateQuestionNumbers();
    }
}

// Function to update question numbers after removal
function updateQuestionNumbers() {
    document.querySelectorAll('.question-number').forEach((span, index) => {
        span.textContent = index + 1;
    });
    questionCount = document.querySelectorAll('.question-item').length;
}

// Function to handle question type change
function handleQuestionTypeChange(select) {
    const optionsContainer = select.closest('.row').querySelector('.options-container');
    const questionIndex = Array.from(document.querySelectorAll('.question-item')).indexOf(select.closest('.question-item'));
    
    switch(select.value) {
        case 'multiple_choice':
            optionsContainer.innerHTML = `
                <div class="mb-3">
                    <label class="form-label">Options</label>
                    <div class="options">
                        <div class="input-group mb-2">
                            <div class="input-group-text">
                                <input type="radio" name="questions[${questionIndex}][correct_option]" value="0" required>
                            </div>
                            <input type="text" class="form-control" name="questions[${questionIndex}][options][]" placeholder="Option 1" required>
                            <button type="button" class="btn btn-outline-danger" onclick="this.closest('.input-group').remove()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="input-group mb-2">
                            <div class="input-group-text">
                                <input type="radio" name="questions[${questionIndex}][correct_option]" value="1" required>
                            </div>
                            <input type="text" class="form-control" name="questions[${questionIndex}][options][]" placeholder="Option 2" required>
                            <button type="button" class="btn btn-outline-danger" onclick="this.closest('.input-group').remove()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="addOption(this, ${questionIndex})">
                        Add Option
                    </button>
                </div>
            `;
            break;
            
        case 'true_false':
            optionsContainer.innerHTML = `
                <div class="mb-3">
                    <label class="form-label">Correct Answer</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="questions[${questionIndex}][correct_option]" value="1" required>
                        <label class="form-check-label">True</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="questions[${questionIndex}][correct_option]" value="0" required>
                        <label class="form-check-label">False</label>
                    </div>
                </div>
            `;
            break;
            
        case 'essay':
            optionsContainer.innerHTML = `
                <div class="mb-3">
                    <label class="form-label">Answer Guidelines (Optional)</label>
                    <textarea class="form-control" name="questions[${questionIndex}][guidelines]" rows="2"></textarea>
                </div>
            `;
            break;
    }
}

// Function to add option for multiple choice questions
function addOption(button, questionIndex) {
    const optionsDiv = button.previousElementSibling;
    const optionCount = optionsDiv.children.length;
    
    const newOption = document.createElement('div');
    newOption.className = 'input-group mb-2';
    newOption.innerHTML = `
        <div class="input-group-text">
            <input type="radio" name="questions[${questionIndex}][correct_option]" value="${optionCount}" required>
        </div>
        <input type="text" class="form-control" name="questions[${questionIndex}][options][]" placeholder="Option ${optionCount + 1}" required>
        <button type="button" class="btn btn-outline-danger" onclick="this.closest('.input-group').remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    optionsDiv.appendChild(newOption);
}

// Function to preview exam
function previewExam() {
    // Save form data to session storage
    const formData = new FormData(document.getElementById('createExamForm'));
    sessionStorage.setItem('examPreview', JSON.stringify(Object.fromEntries(formData)));
    
    // Open preview in new window
    window.open('preview_exam.php', '_blank');
}

// Form validation
(function () {
    'use strict'
    
    const form = document.getElementById('createExamForm');
    form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        form.classList.add('was-validated');
    }, false);
})();

// Add initial question
addQuestion();
</script>

<?php require_once '../includes/footer.php'; ?>
