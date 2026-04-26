<?php
include '../database/database.php';
include '../database/session.php';
requireStudent();

// Check if timer_minutes column exists in exams table
$hasTimerMinutes = false;
$columnCheck = $conn->query("SHOW COLUMNS FROM exams LIKE 'timer_minutes'");
if ($columnCheck && $columnCheck->num_rows > 0) {
    $hasTimerMinutes = true;
}

// Get available exams for student's courses (assuming all students can take all exams for now)
// In a real system, you'd have course enrollment, but for simplicity, all exams are available
$available_exams = $conn->query("
    SELECT e.*, c.name as course_name,
           CASE WHEN r.id IS NOT NULL THEN 1 ELSE 0 END as taken
    FROM exams e
    JOIN courses c ON e.course_id = c.id
    LEFT JOIN results r ON e.id = r.exam_id AND r.student_id = {$_SESSION['user_id']}
    ORDER BY e.created_at DESC
");

// Get student's results
$my_results = $conn->query("
    SELECT r.*, e.title as exam_title, c.name as course_name
    FROM results r
    JOIN exams e ON r.exam_id = e.id
    JOIN courses c ON e.course_id = c.id
    WHERE r.student_id = {$_SESSION['user_id']}
    ORDER BY r.submitted_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Online Examination System</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">Exam System - Student</div>
            <div class="user-info">
                <span>Welcome, <?php echo $_SESSION['name']; ?></span>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="main-content">
            <div class="sidebar">
                <h3>Student Panel</h3>
                <ul>
                    <li><a href="dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="available_exams.php">Available Exams</a></li>
                    <li><a href="my_results.php">My Results</a></li>
                </ul>
            </div>

            <div class="content">
                <h2>Dashboard Overview</h2>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                    <div class="card">
                        <h3><?php echo $available_exams->num_rows; ?></h3>
                        <p>Available Exams</p>
                    </div>
                    <div class="card">
                        <h3><?php echo $my_results->num_rows; ?></h3>
                        <p>Exams Taken</p>
                    </div>
                    <?php
                    $avg_score = 0;
                    if ($my_results->num_rows > 0) {
                        $total = 0;
                        $count = 0;
                        $my_results->data_seek(0);
                        while ($result = $my_results->fetch_assoc()) {
                            $total += $result['percentage'];
                            $count++;
                        }
                        $avg_score = $count > 0 ? $total / $count : 0;
                    }
                    ?>
                    <div class="card">
                        <h3><?php echo number_format($avg_score, 1); ?>%</h3>
                        <p>Average Score</p>
                    </div>
                </div>

                <div class="card">
                    <h3>Available Exams</h3>
                    <?php if ($available_exams->num_rows > 0): ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                            <?php $available_exams->data_seek(0); while ($exam = $available_exams->fetch_assoc()): ?>
                                <div class="card" style="margin: 0;">
                                    <h4><?php echo htmlspecialchars($exam['title']); ?></h4>
                                    <p><strong>Course:</strong> <?php echo htmlspecialchars($exam['course_name']); ?></p>
                                    <p><strong>Timer:</strong> <?php echo $hasTimerMinutes ? $exam['timer_minutes'] : 'N/A'; ?> minutes</p>
                                    <p><strong>Status:</strong>
                                        <?php if ($exam['taken']): ?>
                                            <span style="color: #28a745;">Completed</span>
                                        <?php else: ?>
                                            <span style="color: #ffc107;">Available</span>
                                        <?php endif; ?>
                                    </p>
                                    <?php if (!$exam['taken']): ?>
                                        <a href="take_exam.php?id=<?php echo $exam['id']; ?>" class="btn" style="margin-top: 1rem;">Take Exam</a>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p>No exams available at the moment.</p>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h3>Recent Results</h3>
                    <?php if ($my_results->num_rows > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Exam</th>
                                    <th>Course</th>
                                    <th>Score</th>
                                    <th>Percentage</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $my_results->data_seek(0); $count = 0; while (($result = $my_results->fetch_assoc()) && $count < 5): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($result['exam_title']); ?></td>
                                        <td><?php echo htmlspecialchars($result['course_name']); ?></td>
                                        <td><?php echo $result['score']; ?>/<?php echo $result['total_questions']; ?></td>
                                        <td><?php echo number_format($result['percentage'], 1); ?>%</td>
                                        <td><?php echo date('Y-m-d', strtotime($result['submitted_at'])); ?></td>
                                    </tr>
                                    <?php $count++; ?>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <a href="my_results.php" class="btn" style="margin-top: 1rem;">View All Results</a>
                    <?php else: ?>
                        <p>You haven't taken any exams yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>