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

// Get total number of students
$students_query = "SELECT COUNT(*) as total FROM users WHERE role = 'student'";
$students_stmt = $db->prepare($students_query);
$students_stmt->execute();
$total_students = $students_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get total number of exams
$exams_query = "SELECT COUNT(*) as total FROM exams";
$exams_stmt = $db->prepare($exams_query);
$exams_stmt->execute();
$total_exams = $exams_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get recent exam attempts
$attempts_query = "SELECT 
    u.name as student_name,
    e.title as exam_title,
    ea.score,
    ea.end_time as activity_date,
    CASE 
        WHEN ea.score >= e.pass_percentage THEN 'Passed'
        ELSE 'Failed'
    END as result
FROM exam_attempts ea
JOIN users u ON ea.user_id = u.id
JOIN exams e ON ea.exam_id = e.id
WHERE ea.status = 'completed'
ORDER BY ea.end_time DESC
LIMIT 5";

$attempts_stmt = $db->query($attempts_query);

// Get active exams count
$active_exams_query = "SELECT COUNT(*) as total FROM exams WHERE status = 'published'";
$active_exams_stmt = $db->prepare($active_exams_query);
$active_exams_stmt->execute();
$active_exams = $active_exams_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get total attempts today
$today_start = date('Y-m-d 00:00:00');
$today_end = date('Y-m-d 23:59:59');

$today_attempts_query = "SELECT
    COUNT(DISTINCT ea.id) as attempts_today,
    COUNT(DISTINCT ea.user_id) as unique_students,
    ROUND(AVG(ea.score), 2) as average_score,
    COUNT(CASE WHEN ea.score >= e.pass_percentage THEN 1 END) as passed_count
FROM exam_attempts ea
JOIN exams e ON ea.exam_id = e.id
WHERE ea.status = 'completed'
AND ea.end_time BETWEEN ? AND ?";

$today_attempts_stmt = $db->prepare($today_attempts_query);
$today_attempts_stmt->execute([$today_start, $today_end]);
$today_attempts = $today_attempts_stmt->fetch(PDO::FETCH_ASSOC);
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
                        <a class="nav-link active" id="COLO-WHITE" href="dashboard.php">
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
                <h1 class="h2">Admin Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="create_exam.php" class="btn btn-sm btn-outline-primary">Create New Exam</a>
                        <a href="reports.php" class="btn btn-sm btn-outline-secondary">Generate Report</a>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Students</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_students; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Exams</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_exams; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Active Exams</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $active_exams; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Today's Attempts</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $today_attempts['attempts_today']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Recent Exam Attempts</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Exam</th>
                                            <th>Score</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($attempt = $attempts_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($attempt['student_name']); ?></td>
                                                <td><?php echo htmlspecialchars($attempt['exam_title']); ?></td>
                                                <td><?php echo $attempt['score']; ?>%</td>
                                                <td><?php echo date('M d, Y H:i', strtotime($attempt['activity_date'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $attempt['result'] == 'Passed' ? 'success' : 'danger'; ?>"><?php echo $attempt['result']; ?></span>
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
