<?php
session_start();
require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 text-center">
        <h1 class="display-4 mb-4 fw-bold ">Welcome to Online Examination System</h1>
        <!-- <p class="lead mb-4">A professional platform for conducting online examinations with advanced features and secure testing environment.</p> -->
        
        <?php if(!isset($_SESSION['user_id'])): ?>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <h5 class="card-title">Get Started</h5>
                            <p class="card-text">Login or register to access exams and track your progress.</p>
                            <a href="login.php" class="btn btn-dark me-2">Login</a>
                            <a href="register.php" class="btn btn-outline-success">Register</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <h5 class="card-title">Quick Access</h5>
                            <?php if($_SESSION['role'] == 'admin'): ?>
                                <a href="admin/dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                            <?php else: ?>
                                <a href="student/dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <h5 class="card-title">Recent Activity</h5>
                            <p class="card-text">View your recent exam attempts and results.</p>
                            <a href="student/results.php" class="btn btn-outline-primary">View Results</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-5">
    <div class="col-md-4">
        <div class="card dashboard-card">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-clock me-2"></i>Timed Exams</h5>
                <p class="card-text">Take exams with automated timing and submission.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card dashboard-card">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-chart-bar me-2"></i>Instant Results</h5>
                <p class="card-text">Get detailed analysis of your performance instantly.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card dashboard-card">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-shield-alt me-2"></i>Secure Platform</h5>
                <p class="card-text">Advanced security measures to ensure fair examination.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
