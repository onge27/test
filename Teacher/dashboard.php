<?php
include '../database/database.php';
include '../database/session.php';
requireTeacher();

// Get stats
$hasTeacherId = false;
$columnCheck = $conn->query("SHOW COLUMNS FROM exams LIKE 'teacher_id'");
if ($columnCheck && $columnCheck->num_rows > 0) {
    $hasTeacherId = true;
}

$dashboardWarning = '';
if ($hasTeacherId) {
    $exam_count = $conn->query("SELECT COUNT(*) as count FROM exams WHERE teacher_id = {$_SESSION['user_id']}")->fetch_assoc()['count'];
    $course_count = $conn->query("SELECT COUNT(DISTINCT course_id) as count FROM exams WHERE teacher_id = {$_SESSION['user_id']}")->fetch_assoc()['count'];
    $student_count = $conn->query("SELECT COUNT(DISTINCT r.student_id) as count FROM results r JOIN exams e ON r.exam_id = e.id WHERE e.teacher_id = {$_SESSION['user_id']}")->fetch_assoc()['count'];

    $recent_exams = $conn->query("SELECT e.*, c.name as course_name FROM exams e JOIN courses c ON e.course_id = c.id WHERE e.teacher_id = {$_SESSION['user_id']} ORDER BY e.created_at DESC LIMIT 5");
} else {
    $exam_count = 0;
    $course_count = 0;
    $student_count = 0;
    $recent_exams = [];
    $dashboardWarning = 'Your database is missing the exams.teacher_id column. Teacher dashboard stats cannot be calculated until the schema is updated.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Online Examination System</title>
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
                    <li><a href="dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="create_exam.php">Create Exam</a></li>
                    <li><a href="manage_exams.php">Manage Exams</a></li>
                </ul>
            </div>

            <div class="content">
                <h2>Dashboard Overview</h2>

                <?php if ($dashboardWarning): ?>
                    <div class="alert alert-error">
                        <p><?php echo $dashboardWarning; ?></p>
                    </div>
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                    <div class="card">
                        <h3><?php echo $exam_count; ?></h3>
                        <p>My Exams</p>
                    </div>
                    <div class="card">
                        <h3><?php echo $course_count; ?></h3>
                        <p>Courses</p>
                    </div>
                    <div class="card">
                        <h3><?php echo $student_count; ?></h3>
                        <p>Students Taken Exams</p>
                    </div>
                </div>

                <div class="card">
                    <h3>Quick Actions</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                        <a href="create_exam.php" class="btn">Create New Exam</a>
                        <a href="manage_exams.php" class="btn">Manage Exams</a>
                    </div>
                </div>

                <div class="card">
                    <h3>Recent Exams</h3>
                    <?php if ($hasTeacherId && $recent_exams->num_rows > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Course</th>
                                    <th>Timer</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($exam = $recent_exams->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                        <td><?php echo htmlspecialchars($exam['course_name']); ?></td>
                                        <td><?php echo $exam['timer_minutes']; ?> min</td>
                                        <td><?php echo date('Y-m-d', strtotime($exam['created_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No exams created yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>