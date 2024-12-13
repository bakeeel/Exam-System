<?php
session_start();
require_once '../includes/header.php';
require_once '../config/database.php';

// Check if user is logged in and is an admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if(!isset($_GET['id'])) {
    header("Location: students.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$student_id = $_GET['id'];

// Get student details
$student_query = "SELECT * FROM users WHERE id = ? AND role = 'student'";
$student_stmt = $db->prepare($student_query);
$student_stmt->execute([$student_id]);
$student = $student_stmt->fetch(PDO::FETCH_ASSOC);

if(!$student) {
    header("Location: students.php");
    exit();
}

// Get student's exam attempts
$attempts_query = "SELECT ea.*, e.title as exam_title, e.pass_percentage
                  FROM exam_attempts ea 
                  JOIN exams e ON ea.exam_id = e.id 
                  WHERE ea.user_id = ? 
                  ORDER BY ea.created_at DESC";
$attempts_stmt = $db->prepare($attempts_query);
$attempts_stmt->execute([$student_id]);

// Get performance statistics
$stats_query = "SELECT 
                COUNT(*) as total_exams,
                AVG(score) as average_score,
                MAX(score) as highest_score,
                MIN(score) as lowest_score,
                SUM(CASE WHEN score >= e.pass_percentage THEN 1 ELSE 0 END) as passed_exams
                FROM exam_attempts ea
                JOIN exams e ON ea.exam_id = e.id
                WHERE ea.user_id = ? AND ea.status = 'completed'";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute([$student_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
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
                        <a class="nav-link" href="exams.php">
                            <i class="fas fa-file-alt me-2"></i>Manage Exams
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="questions.php">
                            <i class="fas fa-question-circle me-2"></i>Question Bank
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="students.php">
                            <i class="fas fa-users me-2"></i>Students
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i>Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog me-2"></i>Settings
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Student Profile</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="students.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Students
                    </a>
                </div>
            </div>

            <!-- Student Information -->
            <div class="row">
                <div class="col-md-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Student Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <img src="https://via.placeholder.com/150" class="rounded-circle" alt="Student Photo">
                            </div>
                            <div class="mb-3">
                                <strong>Name:</strong> <?php echo htmlspecialchars($student['name']); ?>
                            </div>
                            <div class="mb-3">
                                <strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?>
                            </div>
                            <div class="mb-3">
                                <strong>Status:</strong> 
                                <span class="badge <?php echo $student['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo ucfirst($student['status']); ?>
                                </span>
                            </div>
                            <div class="mb-3">
                                <strong>Joined:</strong> 
                                <?php echo date('M d, Y', strtotime($student['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <!-- Performance Statistics -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Performance Overview</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 text-center">
                                    <h4><?php echo $stats['total_exams']; ?></h4>
                                    <p class="text-muted">Total Exams</p>
                                </div>
                                <div class="col-md-3 text-center">
                                    <h4><?php echo number_format($stats['average_score'], 2); ?>%</h4>
                                    <p class="text-muted">Average Score</p>
                                </div>
                                <div class="col-md-3 text-center">
                                    <h4><?php echo $stats['passed_exams']; ?></h4>
                                    <p class="text-muted">Exams Passed</p>
                                </div>
                                <div class="col-md-3 text-center">
                                    <h4><?php echo number_format($stats['highest_score'], 2); ?>%</h4>
                                    <p class="text-muted">Highest Score</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Exam History -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Exam History</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Exam</th>
                                            <th>Score</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($attempt = $attempts_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($attempt['exam_title']); ?></td>
                                                <td>
                                                    <?php 
                                                    if($attempt['status'] === 'completed') {
                                                        echo number_format($attempt['score'], 2) . '%';
                                                        if($attempt['score'] >= $attempt['pass_percentage']) {
                                                            echo ' <span class="badge bg-success">Passed</span>';
                                                        } else {
                                                            echo ' <span class="badge bg-danger">Failed</span>';
                                                        }
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = [
                                                        'completed' => 'bg-success',
                                                        'in_progress' => 'bg-warning',
                                                        'abandoned' => 'bg-danger'
                                                    ];
                                                    ?>
                                                    <span class="badge <?php echo $status_class[$attempt['status']]; ?>">
                                                        <?php echo ucfirst($attempt['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y H:i', strtotime($attempt['created_at'])); ?></td>
                                                <td>
                                                    <a href="view_attempt.php?id=<?php echo $attempt['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> View Details
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
