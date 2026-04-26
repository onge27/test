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

// Get available exams
$exams = $conn->query("
    SELECT e.*, c.name as course_name,
           CASE WHEN r.id IS NOT NULL THEN 1 ELSE 0 END as taken
    FROM exams e
    JOIN courses c ON e.course_id = c.id
    LEFT JOIN results r ON e.id = r.exam_id AND r.student_id = {$_SESSION['user_id']}
    ORDER BY e.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Exams - Student</title>
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
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="available_exams.php" class="active">Available Exams</a></li>
                    <li><a href="my_results.php">My Results</a></li>
                </ul>
            </div>

            <div class="content">
                <h2>Available Exams</h2>

                <?php if ($exams->num_rows > 0): ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 1.5rem;">
                        <?php while ($exam = $exams->fetch_assoc()): ?>
                            <div class="card">
                                <h3><?php echo htmlspecialchars($exam['title']); ?></h3>
                                <p><strong>Course:</strong> <?php echo htmlspecialchars($exam['course_name']); ?></p>
                                <p><strong>Duration:</strong> <?php echo $hasTimerMinutes ? $exam['timer_minutes'] : 'N/A'; ?> minutes</p>
                                <p><strong>Created:</strong> <?php echo date('M d, Y', strtotime($exam['created_at'])); ?></p>

                                <?php if ($exam['taken']): ?>
                                    <div style="background: #d4edda; color: #155724; padding: 0.75rem; border-radius: 5px; margin-top: 1rem; text-align: center;">
                                        <strong>✓ Already Completed</strong>
                                    </div>
                                <?php else: ?>
                                    <a href="take_exam.php?id=<?php echo $exam['id']; ?>" class="btn" style="width: 100%; margin-top: 1rem;">Take Exam</a>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <h3>No Exams Available</h3>
                        <p>There are no exams available at the moment. Please check back later.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>