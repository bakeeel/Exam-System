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

// Handle student status changes
if(isset($_POST['action']) && isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];
    $action = $_POST['action'];
    
    if($action === 'block') {
        $status = 'blocked';
    } elseif($action === 'activate') {
        $status = 'active';
    }
    
    $update_query = "UPDATE users SET status = ? WHERE id = ? AND role = 'student'";
    $update_stmt = $db->prepare($update_query);
    if($update_stmt->execute([$status, $student_id])) {
        $_SESSION['success'] = "Student status updated successfully";
    } else {
        $_SESSION['error'] = "Failed to update student status";
    }
}

// Get search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build the query
$students_query = "SELECT u.*, 
                    COUNT(DISTINCT ea.id) as total_exams,
                    AVG(ea.score) as average_score,
                    MAX(ea.end_time) as last_activity
                  FROM users u 
                  LEFT JOIN exam_attempts ea ON u.id = ea.user_id
                  WHERE u.role = 'student'";
$params = [];

if($search) {
    $students_query .= " AND (u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if($status_filter) {
    $students_query .= " AND u.status = ?";
    $params[] = $status_filter;
}

$students_query .= " GROUP BY u.id ORDER BY u.id DESC";
$students_stmt = $db->prepare($students_query);
$students_stmt->execute($params);
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
                        <a class="nav-link active" id="COLO-WHITE" href="students.php">
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
                <h1 class="h2">Student Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <i class="fas fa-user-plus me-2"></i>Add New Student
                    </button>
                </div>
            </div>

            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Search and Filter -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" placeholder="Search by name or email" 
                                       value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-success" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="blocked" <?php echo $status_filter === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <a href="students.php" class="btn btn-secondary w-100">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Students List -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="studentsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Total Exams</th>
                                    <th>Average Score</th>
                                    <th>Last Activity</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($student = $students_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td><?php echo $student['total_exams']; ?></td>
                                        <td>
                                            <?php 
                                            echo $student['average_score'] 
                                                ? number_format($student['average_score'], 2) . '%' 
                                                : 'N/A';
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            echo $student['last_activity']
                                                ? date('Y-m-d H:i', strtotime($student['last_activity']))
                                                : 'Never';
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = [
                                                'active' => 'bg-success',
                                                'blocked' => 'bg-danger'
                                            ];
                                            $status = $student['status'] ?? 'active';
                                            ?>
                                            <span class="badge <?php echo $status_class[$status]; ?>">
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="view_student.php?id=<?php echo $student['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                    <?php if($status === 'active'): ?>
                                                        <input type="hidden" name="action" value="block">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                                onclick="return confirm('Are you sure you want to block this student?')">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <input type="hidden" name="action" value="activate">
                                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </form>
                                                <button type="button" class="btn btn-sm btn-outline-info" 
                                                        onclick="viewResults(<?php echo $student['id']; ?>)">
                                                    <i class="fas fa-chart-line"></i>
                                                </button>
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

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addStudentForm" action="add_student.php" method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" form="addStudentForm" class="btn btn-primary">Add Student</button>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize DataTables
$(document).ready(function() {
    $('#studentsTable').DataTable({
        "pageLength": 25,
        "order": [[4, "desc"]]
    });
});

// Function to view student results
function viewResults(studentId) {
    window.location.href = `student_results.php?id=${studentId}`;
}
</script>

<?php require_once '../includes/footer.php'; ?>
