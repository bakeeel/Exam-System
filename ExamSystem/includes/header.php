<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">ExamSystem</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php if($_SESSION['role'] == 'admin'): ?>
                            <li class="nav-item"><a class="nav-link" href="/ExamSystem/admin/dashboard.php">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link" href="/ExamSystem/admin/exams.php">Manage Exams</a></li>
                            <li class="nav-item"><a class="nav-link" href="/ExamSystem/admin/questions.php">Question Bank</a></li>
                            <li class="nav-item"><a class="nav-link" href="/ExamSystem/admin/students.php">Students</a></li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link" href="/ExamSystem/student/dashboard.php">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link" href="/ExamSystem/student/exams.php">Available Exams</a></li>
                            <li class="nav-item"><a class="nav-link" href="/ExamSystem/student/results.php">My Results</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="/ExamSystem/profile.php">Profile</a></li>
                        <li class="nav-item">
                            <?php if($_SESSION['role'] == 'admin'): ?>
                                <a class="nav-link" href="/ExamSystem/admin/logout.php">Logout</a>
                            <?php else: ?>
                                <a class="nav-link" href="/ExamSystem/student/logout.php">Logout</a>
                            <?php endif; ?>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="/ExamSystem/login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="/ExamSystem/register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content Wrapper -->
    <div class="wrapper flex-grow-1"><?php // This wrapper will push the footer down ?>
        <div class="container mt-4">
