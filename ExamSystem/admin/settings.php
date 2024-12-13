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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        if (isset($_POST['update_profile'])) {
            // Update admin profile
            $update_profile_query = "UPDATE users SET 
                name = ?,
                email = ?
                WHERE id = ? AND role = 'admin'";
            
            $profile_stmt = $db->prepare($update_profile_query);
            $profile_stmt->execute([
                $_POST['name'],
                $_POST['email'],
                $_SESSION['user_id']
            ]);
            
            $_SESSION['success'] = "Profile updated successfully";
        }
        
        elseif (isset($_POST['change_password'])) {
            // Verify current password
            $verify_query = "SELECT password FROM users WHERE id = ?";
            $verify_stmt = $db->prepare($verify_query);
            $verify_stmt->execute([$_SESSION['user_id']]);
            $current_hash = $verify_stmt->fetchColumn();
            
            if (password_verify($_POST['current_password'], $current_hash)) {
                if ($_POST['new_password'] === $_POST['confirm_password']) {
                    // Update password
                    $new_hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                    $update_password_query = "UPDATE users SET password = ? WHERE id = ?";
                    $password_stmt = $db->prepare($update_password_query);
                    $password_stmt->execute([$new_hash, $_SESSION['user_id']]);
                    
                    $_SESSION['success'] = "Password changed successfully";
                } else {
                    throw new Exception("New passwords do not match");
                }
            } else {
                throw new Exception("Current password is incorrect");
            }
        }
        
        elseif (isset($_POST['update_system'])) {
            // Update system settings
            // Note: You might want to create a separate settings table for these
            $_SESSION['success'] = "System settings updated successfully";
        }
        
        $db->commit();
        
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    // Redirect to prevent form resubmission
    header("Location: settings.php");
    exit();
}

// Get current user data
$user_query = "SELECT name, email FROM users WHERE id = ?";
$user_stmt = $db->prepare($user_query);
$user_stmt->execute([$_SESSION['user_id']]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);
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
                        <a class="nav-link active" id="COLO-WHITE" href="settings.php">
                            <i class="fas fa-cog me-2"></i>Settings
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Settings</h1>
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

            <div class="row">
                <!-- Profile Settings -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Profile Settings</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <button type="submit" name="update_profile" class="btn btn-success">
                                    <i class="fas fa-save me-2"></i>Save Profile
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Password Change -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" 
                                           name="current_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" 
                                           name="new_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" 
                                           name="confirm_password" required>
                                </div>
                                <button type="submit" name="change_password" class="btn btn-success">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- System Settings -->
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">System Settings</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Default Exam Duration (minutes)</label>
                                            <input type="number" class="form-control" name="default_duration" 
                                                   value="60" min="1">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Default Pass Percentage</label>
                                            <input type="number" class="form-control" name="default_pass_percentage" 
                                                   value="60" min="0" max="100">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Results Display</label>
                                            <select class="form-select" name="results_display">
                                                <option value="immediate">Immediate</option>
                                                <option value="after_completion">After Exam Completion</option>
                                                <option value="manual">Manual Release</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Maximum Attempts per Exam</label>
                                            <input type="number" class="form-control" name="max_attempts" 
                                                   value="1" min="1">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="allow_review" 
                                               name="allow_review" checked>
                                        <label class="form-check-label" for="allow_review">
                                            Allow Students to Review Their Answers
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="show_correct_answers" 
                                               name="show_correct_answers" checked>
                                        <label class="form-check-label" for="show_correct_answers">
                                            Show Correct Answers After Exam
                                        </label>
                                    </div>
                                </div>
                                <button type="submit" name="update_system" class="btn btn-success">
                                    <i class="fas fa-save me-2"></i>Save System Settings
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Backup & Maintenance -->
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Backup & Maintenance</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Database Backup</h6>
                                    <p class="text-muted">Create a backup of your database</p>
                                    <button type="button" class="btn btn-success mb-3" onclick="createBackup()">
                                        <i class="fas fa-download me-2"></i>Create Backup
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <h6>System Maintenance</h6>
                                    <p class="text-muted">Clear temporary files and optimize database</p>
                                    <button type="button" class="btn btn-warning mb-3" onclick="performMaintenance()">
                                        <i class="fas fa-broom me-2"></i>Perform Maintenance
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Form validation
(function () {
    'use strict'
    
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();

// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    if (this.value !== newPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

// Backup function
function createBackup() {
    if (confirm('Create database backup?')) {
        // Add backup functionality here
        alert('Backup created successfully');
    }
}

// Maintenance function
function performMaintenance() {
    if (confirm('Perform system maintenance?')) {
        // Add maintenance functionality here
        alert('Maintenance completed successfully');
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
