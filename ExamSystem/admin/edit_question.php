<?php
session_start();

require_once '../includes/header.php';
require_once '../config/database.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Error: Question ID is missing.');
}

$database = new Database();
$db = $database->getConnection();

$question_id = $_GET['id'];
    // Prepare and execute the query to fetch the question
    $query = "SELECT * FROM questions WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $question_id);
    $stmt->execute();

    // Fetch the question
    $question = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$question) {
        die('Error: Question not found.');
 }


  



     // Handle form submission
     if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $db->beginTransaction();

        try {

             // Delete existing questions
             $delete_questions_query = "DELETE FROM questions WHERE id = ?";
             $delete_questions_stmt = $db->prepare($delete_questions_query);
             $delete_questions_stmt->execute([$question_id]);

            // Insert updated questions
            if (isset($_POST['questions']) && is_array($_POST['questions'])) {
                foreach ($_POST['questions'] as $question) {
                    if (!isset($question['text'], $question['type'], $question['marks'])) {
                        throw new Exception("Missing required question fields");
                    }

                    if (isset($question['text'])) {
                        $question_text = $question['text'];
                    } else {
                        $question_text = '';
                    }

                    if (isset($question['question_text'])) {
                        $question_text = $question['question_text'];
                    } else {
                        $question_text = ''; 
                    }

                    $question_query = "update questions set question_text = ?, question_type = ?, marks = ? where id = ?";
                    $question_stmt = $db->prepare($question_query);
                    $question_stmt->execute([
                        $question_text,
                        $question['type'],
                        $question['marks'],
                        $question_id
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
            $_SESSION['success'] = "Question updated successfully";
            header("Location: view_question.php?id=$question_id");
            exit();
    }
                
 
    
 catch (Exception $e) {
    $db->rollBack();
    $_SESSION['error'] = "Failed to update exam: " . $e->getMessage();
    header("Location: questions.php");
    exit();
}

}



?>

<style>
    #COLO-WHITE{
    color: #fff !important;
}
</style>


<div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-success">Questions</h6>
                        <button type="button" class="btn btn-success btn-sm" onclick="addQuestion()">
                            <i class="fas fa-plus me-2"></i>Add Question
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="questionsContainer">
                            
                            <div class="question-item card mb-3">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Question #<?php echo $question_id + 1; ?></h6>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeQuestion(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <form id="editExamForm" method="POST" class="needs-validation" novalidate>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <label class="form-label">Question Text</label>
                                            <textarea class="form-control" name="questions[<?php echo $question_id; ?>][text]" 
                                                      rows="2" required><?php echo htmlspecialchars($question['question_text']); ?></textarea>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Type</label>
                                            <select class="form-select" name="questions[<?php echo $question_id; ?>][type]" 
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
                           >
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
