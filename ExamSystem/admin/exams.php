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

// Handle exam status changes
if(isset($_POST['action']) && isset($_POST['exam_id'])) {
    $exam_id = $_POST['exam_id'];
    $action = $_POST['action'];
    
    $status = ($action === 'publish') ? 'published' : 'archived';
    $update_query = "UPDATE exams SET status = ? WHERE id = ?";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->execute([$status, $exam_id]);
}

// Get all exams
$exams_query = "SELECT e.*, COUNT(q.id) as question_count, 
                (SELECT COUNT(*) FROM exam_attempts WHERE exam_id = e.id) as attempt_count 
                FROM exams e 
                LEFT JOIN questions q ON e.id = q.exam_id 
                GROUP BY e.id 
                ORDER BY e.created_at DESC";
$exams_stmt = $db->prepare($exams_query);
$exams_stmt->execute();
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
                        <a class="nav-link" id ="COLO-WHITE" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" id ="COLO-WHITE" href="exams.php">
                            <i class="fas fa-file-alt me-2"></i>Manage Exams
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id ="COLO-WHITE" href="questions.php">
                            <i class="fas fa-question-circle me-2"></i>Question Bank
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id ="COLO-WHITE" href="students.php">
                            <i class="fas fa-users me-2"></i>Students
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id ="COLO-WHITE" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i>Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id ="COLO-WHITE" href="settings.php">
                            <i class="fas fa-cog me-2"></i>Settings
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Exams</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="create_exam.php" class="btn btn-success">
                        <i class="fas fa-plus me-2"></i>Create New Exam
                    </a>
                </div>
            </div>

            <!-- Exams List -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Questions</th>
                                    <th>Duration</th>
                                    <th>Attempts</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($exam = $exams_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                        <td><?php echo $exam['question_count']; ?></td>
                                        <td><?php echo $exam['duration']; ?> mins</td>
                                        <td><?php echo $exam['attempt_count']; ?></td>
                                        <td>
                                            <?php
                                            $status_class = [
                                                'draft' => 'bg-secondary',
                                                'published' => 'bg-success',
                                                'archived' => 'bg-danger'
                                            ];
                                            ?>
                                            <span class="badge <?php echo $status_class[$exam['status']]; ?>">
                                                <?php echo ucfirst($exam['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($exam['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="edit_exam.php?id=<?php echo $exam['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if($exam['status'] === 'draft'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                                                        <input type="hidden" name="action" value="publish">
                                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if($exam['status'] === 'published'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                                                        <input type="hidden" name="action" value="archive">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-archive"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <a href="view_results.php?exam_id=<?php echo $exam['id']; ?>" 
                                                   class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-chart-bar"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
