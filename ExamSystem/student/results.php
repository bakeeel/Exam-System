<?php
session_start();
require_once '../config/database.php';
require_once '../includes/header.php';

// Check if user is logged in and is a student
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Get student's exam results with details
    $results_query = "SELECT er.*, e.title as exam_title, e.pass_percentage,
                            e.duration, COUNT(DISTINCT q.id) as total_questions,
                            SUM(q.marks) as total_marks,
                            (SELECT SUM(marks_obtained) 
                             FROM exam_answers 
                             WHERE result_id = er.id) as marks_obtained
                     FROM exam_results er
                     JOIN exams e ON er.exam_id = e.id
                     LEFT JOIN questions q ON e.id = q.exam_id
                     WHERE er.student_id = ? AND er.status = 'completed'
                     GROUP BY er.id
                     ORDER BY er.attempt_date DESC";

    $stmt = $db->prepare($results_query);
    $stmt->execute([$_SESSION['user_id']]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate statistics
    $total_exams = count($results);
    $passed_exams = 0;
    $total_score = 0;
    $total_possible = 0;

    foreach($results as $result) {
        if($result['marks_obtained'] >= ($result['total_marks'] * $result['pass_percentage'] / 100)) {
            $passed_exams++;
        }
        $total_score += $result['marks_obtained'];
        $total_possible += $result['total_marks'];
    }

    $average_score = $total_possible > 0 ? ($total_score / $total_possible) * 100 : 0;
} catch(Exception $e) {
    $_SESSION['error'] = "Error loading results: " . $e->getMessage();
    $results = [];
    $total_exams = $passed_exams = 0;
    $average_score = 0;
}
?>
<STYle>

#COLO-WHITE{
    color: #fff !important;
}
</STYle>

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
                            <i class="fas fa-file-alt me-2"></i>Available Exams
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" id="COLO-WHITE" href="results.php">
                            <i class="fas fa-chart-bar me-2"></i>My Results
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2 ">My Results</h1>
            </div>

            <!-- Performance Overview -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Exams Taken</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_exams; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar fa-2x text-gray-300"></i>
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
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Passed Exams</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $passed_exams; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Average Score
                                    </div>
                                    <div class="row no-gutters align-items-center">
                                        <div class="col-auto">
                                            <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                                <?php echo number_format($average_score, 1); ?>%
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="progress progress-sm mr-2">
                                                <div class="progress-bar bg-info" role="progressbar"
                                                    style="width: <?php echo $average_score; ?>%"></div>
                                            </div>
                                        </div>
                                    </div>
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
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Success Rate</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $total_exams > 0 ? number_format(($passed_exams / $total_exams) * 100, 1) : 0; ?>%
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-percentage fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results Table -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-dark">Exam Results History</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="resultsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Exam Title</th>
                                    <th>Attempt Date</th>
                                    <th>Score</th>
                                    <th>Questions</th>
                                    <th>Time Taken</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($results)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <div class="p-4">
                                                <i class="fas fa-info-circle fa-3x text-success mb-3"></i>
                                                <h5>No Exam Results Found</h5>
                                                <p class="text-muted">You haven't taken any exams yet.</p>
                                                <a href="exams.php" class="btn btn-dark">
                                                    <i class="fas fa-file-alt me-2"></i>View Available Exams
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($results as $result): ?>
                                        <?php 
                                        $percentage = ($result['marks_obtained'] / $result['total_marks']) * 100;
                                        $status = $percentage >= $result['pass_percentage'] ? 'Passed' : 'Failed';
                                        $status_class = $status === 'Passed' ? 'success' : 'danger';
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($result['exam_title']); ?></td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($result['attempt_date'])); ?></td>
                                            <td>
                                                <?php echo $result['marks_obtained']; ?>/<?php echo $result['total_marks']; ?>
                                                (<?php echo number_format($percentage, 1); ?>%)
                                            </td>
                                            <td><?php echo $result['total_questions']; ?></td>
                                            <td><?php echo $result['time_taken']; ?> mins</td>
                                            <td>
                                                <span class="badge bg-<?php echo $status_class; ?>">
                                                    <?php echo $status; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view_result.php?id=<?php echo $result['id']; ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    <i class="fas fa-eye me-1"></i>View Details
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- DataTables -->
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#resultsTable').DataTable({
        order: [[1, 'desc']], // Sort by attempt date by default
        language: {
            search: "Search results:",
            lengthMenu: "Show _MENU_ results per page",
            info: "Showing _START_ to _END_ of _TOTAL_ results",
            emptyTable: "No exam results found"
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
