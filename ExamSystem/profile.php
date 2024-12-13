<?php
session_start();
require_once 'config/database.php';
require_once 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

if(isset($_SESSION['error'])|| isset($_SESSION['success'])){
unset($_SESSION['error']);
unset($_SESSION['success']); }

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $update_fields = [];
        $params = [];

        // Basic information update
        if (!empty($_POST['name'])) {
            $update_fields[] = "name = ?";
            $params[] = $_POST['name'];
        }
        if (!empty($_POST['email'])) {
            // Check if email is already taken by another user
            $email_check = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $email_check->execute([$_POST['email'], $_SESSION['user_id']]);
            if ($email_check->fetch()) {
                throw new Exception("Email is already taken by another user");
            }
            $update_fields[] = "email = ?";
            $params[] = $_POST['email'];
        }

        // Password update
        if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
            // Verify current password
            $verify = $db->prepare("SELECT password FROM users WHERE id = ?");
            $verify->execute([$_SESSION['user_id']]);
            $user = $verify->fetch(PDO::FETCH_ASSOC);

            if (!password_verify($_POST['current_password'], $user['password'])) {
                throw new Exception("Current password is incorrect");
            }

            // Validate new password
            if (strlen($_POST['new_password']) < 8) {
                throw new Exception("New password must be at least 8 characters long");
            }

            $update_fields[] = "password = ?";
            $params[] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        }

        if (!empty($update_fields)) {
            $params[] = $_SESSION['user_id'];
            $sql = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            $_SESSION['success'] = "Profile updated successfully";
            header("Location: profile.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Get user data
$stmt = $db->prepare("SELECT name, email, role, created_at FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
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

            <div class="card shadow">
                <div class="card-header">
                    <h4 class="mb-0">Profile Settings</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="profile.php" class="needs-validation" novalidate>
                        <!-- Basic Information -->
                        <div class="mb-4">
                            <h5>Basic Information</h5>
                            <hr>
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                <div class="invalid-feedback">Please enter your name.</div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Member Since</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" readonly>
                            </div>
                        </div>

                        <!-- Change Password -->
                        <div class="mb-4">
                            <h5>Change Password</h5>
                            <hr>
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                                <div class="form-text">Leave password fields empty if you don't want to change it.</div>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" 
                                       minlength="8">
                                <div class="invalid-feedback">Password must be at least 8 characters long.</div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-dark">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// Password validation
document.getElementById('new_password').addEventListener('input', function() {
    if (this.value.length > 0 && !document.getElementById('current_password').value) {
        document.getElementById('current_password').required = true;
    } else {
        document.getElementById('current_password').required = false;
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
