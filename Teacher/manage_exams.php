<?php
include '../database/database.php';
include '../database/session.php';
requireTeacher();

$errors = [];
$success = '';
$dashboardWarning = '';

$hasTeacherId = false;
$columnCheck = $conn->query("SHOW COLUMNS FROM exams LIKE 'teacher_id'");
if ($columnCheck && $columnCheck->num_rows > 0) {
    $hasTeacherId = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_exam'])) {
    $exam_id = $_POST['exam_id'];

    if ($hasTeacherId) {
        // Check if exam belongs to teacher
        $stmt = $conn->prepare("SELECT id FROM exams WHERE id = ? AND teacher_id = ?");
        $stmt->bind_param("ii", $exam_id, $_SESSION['user_id']);
        $stmt->execute();

        if ($stmt->get_result()->num_rows === 1) {
            $conn->begin_transaction();
            try {
                // Delete related data
                $conn->query("DELETE FROM student_answers WHERE question_id IN (SELECT id FROM questions WHERE exam_id = $exam_id)");
                $conn->query("DELETE FROM questions WHERE exam_id = $exam_id");
                $conn->query("DELETE FROM results WHERE exam_id = $exam_id");
                $conn->query("DELETE FROM exams WHERE id = $exam_id");

                $conn->commit();
                $success = 'Exam deleted successfully';
            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = 'Failed to delete exam';
            }
        } else {
            $errors[] = 'Exam not found or access denied';
        }
    } else {
        $errors[] = 'Cannot delete exam because exams.teacher_id is missing from the database schema.';
    }
}

// Get teacher's exams
if ($hasTeacherId) {
    $exams = $conn->query("
        SELECT e.*, c.name as course_name,
               COUNT(DISTINCT q.id) as question_count,
               COUNT(DISTINCT r.student_id) as student_count
        FROM exams e
        JOIN courses c ON e.course_id = c.id
        LEFT JOIN questions q ON e.id = q.exam_id
        LEFT JOIN results r ON e.id = r.exam_id
        WHERE e.teacher_id = {$_SESSION['user_id']}
        GROUP BY e.id
        ORDER BY e.created_at DESC
    ");
} else {
    $dashboardWarning = 'Your database is missing the exams.teacher_id column. Your exam list cannot be loaded until the schema is updated.';
    $exams = $conn->query("SELECT 0 AS id WHERE 0");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Exams - Teacher</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">Exam System - Teacher</div>
            <div class="user-info">
                <span>Welcome, <?php echo $_SESSION['name']; ?></span>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="main-content">
            <div class="sidebar">
                <h3>Teacher Panel</h3>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="create_exam.php">Create Exam</a></li>
                    <li><a href="manage_exams.php" class="active">Manage Exams</a></li>
                </ul>
            </div>

            <div class="content">
                <h2>Manage Exams</h2>

                <?php if ($dashboardWarning): ?>
                    <div class="alert alert-error">
                        <p><?php echo $dashboardWarning; ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <p><?php echo $success; ?></p>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <h3>My Exams</h3>
                    <?php if ($exams->num_rows > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Course</th>
                                    <th>Questions</th>
                                    <th>Timer</th>
                                    <th>Students</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($exam = $exams->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                        <td><?php echo htmlspecialchars($exam['course_name']); ?></td>
                                        <td><?php echo $exam['question_count']; ?></td>
                                        <td><?php echo $exam['timer_minutes']; ?> min</td>
                                        <td><?php echo $exam['student_count']; ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($exam['created_at'])); ?></td>
                                        <td>
                                            <a href="view_exam.php?id=<?php echo $exam['id']; ?>" class="btn btn-success" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">View</a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirmDelete('Are you sure you want to delete this exam? All related data will be lost.')">
                                                <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                                                <button type="submit" name="delete_exam" class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No exams created yet. <a href="create_exam.php">Create your first exam</a></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>