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

    // Get available exams
    $exam_query = "SELECT * FROM exams WHERE status = 'published' ORDER BY created_at DESC";
    $exam_stmt = $db->prepare($exam_query);
    $exam_stmt->execute();

} catch(PDOException $e) {
    $_SESSION['error'] = "Error loading exams: " . $e->getMessage();
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
                            <i class="fas fa-home me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" id="COLO-WHITE" href="exams.php">
                            <i class="fas fa-file-alt me-2"></i>Available Exams
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link"id="COLO-WHITE"  href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Available Exams</h1>
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

            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <?php if(isset($exam_stmt) && $exam_stmt->rowCount() > 0): ?>
                    <?php while($exam = $exam_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title text-center "><?php echo htmlspecialchars($exam['title']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars($exam['description']); ?></p>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-clock me-2"></i>Duration: <?php echo $exam['duration']; ?> minutes</li>
                                        <li><i class="fas fa-percentage me-2"></i>Pass Percentage: <?php echo $exam['pass_percentage']; ?>%</li>
                                    </ul>
                                </div>
                                <div class="card-footer bg-transparent border-top-0">
                                    <a href="take_exam.php?id=<?php echo $exam['id']; ?>" class="btn btn-dark w-100">
                                        <i class="fas fa-pencil-alt me-2"></i>Take Exam
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            No exams are currently available.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
