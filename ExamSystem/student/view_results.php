<?php
session_start();
require_once '../includes/header.php';
require_once '../config/database.php';

// Check if user is logged in and is a student
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    $_SESSION['error'] = "Please log in as a student";
    header("Location: ../index.php");
    exit();
}

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();

    // Get student's exam results
    $results_query = "SELECT er.*, e.title, e.pass_percentage 
                     FROM exam_results er 
                     JOIN exams e ON er.exam_id = e.id 
                     WHERE er.student_id = ? 
                     ORDER BY er.created_at DESC";
    
    $results_stmt = $db->prepare($results_query);
    $results_stmt->execute([$_SESSION['user_id']]);

} catch(PDOException $e) {
    $_SESSION['error'] = "Error loading results: " . $e->getMessage();
}
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="exams.php">
                            <i class="fas fa-file-alt me-2"></i>Available Exams
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="view_results.php">
                            <i class="fas fa-chart-bar me-2"></i>My Results
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">My Exam Results</h1>
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

            <div class="card shadow mb-4">
                <div class="card-body">
                    <?php if($results_stmt->rowCount() > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Exam Title</th>
                                        <th>Total Marks</th>
                                        <th>Obtained Marks</th>
                                        <th>Percentage</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($result = $results_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($result['title']); ?></td>
                                            <td><?php echo $result['total_marks']; ?></td>
                                            <td><?php echo $result['obtained_marks']; ?></td>
                                            <td><?php echo number_format($result['percentage'], 2); ?>%</td>
                                            <td>
                                                <?php if($result['passed']): ?>
                                                    <span class="badge bg-success">Passed</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Failed</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M d, Y H:i', strtotime($result['created_at'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            You haven't taken any exams yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
