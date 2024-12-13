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

// Get date range filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get overall statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM users WHERE role = 'student') as total_students,
    (SELECT COUNT(*) FROM exams WHERE status = 'published') as total_exams,
    (SELECT COUNT(*) FROM exam_attempts WHERE status = 'completed') as total_attempts,
    (SELECT ROUND(AVG(score), 2) FROM exam_attempts WHERE status = 'completed') as average_score";
$stats_stmt = $db->query($stats_query);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get exam performance data
$exam_performance_query = "SELECT 
    e.title,
    COUNT(ea.id) as attempt_count,
    ROUND(AVG(ea.score), 2) as avg_score,
    ROUND(MIN(ea.score), 2) as min_score,
    ROUND(MAX(ea.score), 2) as max_score,
    ROUND(
        SUM(CASE WHEN ea.score >= e.pass_percentage THEN 1 ELSE 0 END) * 100.0 / COUNT(*),
        2
    ) as pass_rate
FROM exams e
LEFT JOIN exam_attempts ea ON e.id = ea.exam_id
WHERE ea.status = 'completed'
AND ea.end_time BETWEEN ? AND ?
GROUP BY e.id
ORDER BY attempt_count DESC";

$exam_performance_stmt = $db->prepare($exam_performance_query);
$exam_performance_stmt->execute([$start_date, $end_date . ' 23:59:59']);

// Get top performing students
$top_students_query = "SELECT 
    u.name,
    COUNT(DISTINCT ea.exam_id) as exams_taken,
    ROUND(AVG(ea.score), 2) as avg_score,
    MAX(ea.score) as highest_score
FROM users u
JOIN exam_attempts ea ON u.id = ea.user_id
WHERE ea.status = 'completed'
AND ea.end_time BETWEEN ? AND ?
GROUP BY u.id
HAVING exams_taken > 0
ORDER BY avg_score DESC
LIMIT 10";

$top_students_stmt = $db->prepare($top_students_query);
$top_students_stmt->execute([$start_date, $end_date . ' 23:59:59']);

// Get daily attempt counts for chart
$daily_attempts_query = "SELECT 
    DATE(end_time) as attempt_date,
    COUNT(*) as attempt_count,
    ROUND(AVG(score), 2) as avg_score
FROM exam_attempts
WHERE status = 'completed'
AND end_time BETWEEN ? AND ?
GROUP BY DATE(end_time)
ORDER BY attempt_date";

$daily_attempts_stmt = $db->prepare($daily_attempts_query);
$daily_attempts_stmt->execute([$start_date, $end_date . ' 23:59:59']);
$daily_attempts = $daily_attempts_stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare chart data
$chart_labels = [];
$chart_attempts = [];
$chart_scores = [];
foreach($daily_attempts as $day) {
    $chart_labels[] = date('M d', strtotime($day['attempt_date']));
    $chart_attempts[] = $day['attempt_count'];
    $chart_scores[] = $day['avg_score'];
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
                        <a class="nav-link active" id="COLO-WHITE" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i>Reports
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Reports & Analytics</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <form class="row g-3 align-items-center">
                        <div class="col-auto">
                            <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-auto">
                            <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">Total Students</h5>
                            <h2 class="card-text"><?php echo number_format($stats['total_students']); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title">Active Exams</h5>
                            <h2 class="card-text"><?php echo number_format($stats['total_exams']); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5 class="card-title">Total Attempts</h5>
                            <h2 class="card-text"><?php echo number_format($stats['total_attempts']); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">Average Score</h5>
                            <h2 class="card-text"><?php echo $stats['average_score']; ?>%</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Chart -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Exam Activity Trends</h5>
                </div>
                <div class="card-body">
                    <canvas id="activityChart" height="300"></canvas>
                </div>
            </div>

            <div class="row">
                <!-- Exam Performance Table -->
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Exam Performance</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Exam Title</th>
                                            <th>Attempts</th>
                                            <th>Avg Score</th>
                                            <th>Min Score</th>
                                            <th>Max Score</th>
                                            <th>Pass Rate</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($exam = $exam_performance_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                            <td><?php echo number_format($exam['attempt_count']); ?></td>
                                            <td><?php echo $exam['avg_score']; ?>%</td>
                                            <td><?php echo $exam['min_score']; ?>%</td>
                                            <td><?php echo $exam['max_score']; ?>%</td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: <?php echo $exam['pass_rate']; ?>%"
                                                         aria-valuenow="<?php echo $exam['pass_rate']; ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                        <?php echo $exam['pass_rate']; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Students -->
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Top Performing Students</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Exams</th>
                                            <th>Avg Score</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($student = $top_students_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                                            <td><?php echo $student['exams_taken']; ?></td>
                                            <td><?php echo $student['avg_score']; ?>%</td>
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

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Activity Chart
const ctx = document.getElementById('activityChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chart_labels); ?>,
        datasets: [{
            label: 'Number of Attempts',
            data: <?php echo json_encode($chart_attempts); ?>,
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1,
            yAxisID: 'y'
        }, {
            label: 'Average Score',
            data: <?php echo json_encode($chart_scores); ?>,
            borderColor: 'rgb(255, 99, 132)',
            tension: 0.1,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Number of Attempts'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Average Score (%)'
                },
                grid: {
                    drawOnChartArea: false
                }
            }
        }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
