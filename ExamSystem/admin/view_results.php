<?php
session_start();
require_once '../includes/header.php';
require_once '../config/database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Please log in as an administrator";
    header("Location: ../index.php");
    exit();
}

// Check if exam ID is provided
if (!isset($_GET['exam_id']) || !is_numeric($_GET['exam_id'])) {
    $_SESSION['error'] = "Invalid exam ID";
    header("Location: exams.php");
    exit();
}

$exam_id = $_GET['exam_id'];

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();

    // First, get exam details
    $exam_query = "SELECT * FROM exams WHERE id = ?";
    $exam_stmt = $db->prepare($exam_query);
    $exam_stmt->execute([$exam_id]);
    $exam = $exam_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        $_SESSION['error'] = "Exam not found";
        header("Location: exams.php");
        exit();
    }

    // Get exam results
    $results_query = "SELECT er.*, u.name as student_name 
                     FROM exam_results er 
                     LEFT JOIN users u ON er.student_id = u.id 
                     WHERE er.exam_id = ? 
                     ORDER BY er.created_at DESC";
    $results_stmt = $db->prepare($results_query);
    $results_stmt->execute([$exam_id]);

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: exams.php");
    exit();
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">Exam Results: <?php echo htmlspecialchars($exam['title']); ?></h3>
                        <a href="exams.php" class="btn btn-primary btn-sm">Back to Exams</a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if($results_stmt->rowCount() > 0): ?>
                        <div class="table-responsive">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Student</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Marks</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Percentage</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($result = $results_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex px-2 py-1">
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm"><?php echo htmlspecialchars($result['student_name'] ?? 'Unknown Student'); ?></h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <p class="text-sm font-weight-bold mb-0">
                                                    <?php echo $result['obtained_marks']; ?> / <?php echo $result['total_marks']; ?>
                                                </p>
                                            </td>
                                            <td class="align-middle text-center text-sm">
                                                <span class="text-xs font-weight-bold">
                                                    <?php echo number_format($result['percentage'], 1); ?>%
                                                </span>
                                            </td>
                                            <td class="align-middle text-center text-sm">
                                                <?php if($result['passed']): ?>
                                                    <span class="badge badge-sm bg-gradient-success">Passed</span>
                                                <?php else: ?>
                                                    <span class="badge badge-sm bg-gradient-danger">Failed</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="align-middle text-center">
                                                <span class="text-secondary text-xs font-weight-bold">
                                                    <?php echo date('M d, Y H:i', strtotime($result['created_at'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            No results found for this exam.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
