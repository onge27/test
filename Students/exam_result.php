<?php
include '../database/database.php';
include '../database/session.php';
requireStudent();

$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

// Get result
$stmt = $conn->prepare("
    SELECT r.*, e.title as exam_title, c.name as course_name
    FROM results r
    JOIN exams e ON r.exam_id = e.id
    JOIN courses c ON e.course_id = c.id
    WHERE r.exam_id = ? AND r.student_id = ?
");
$stmt->bind_param("ii", $exam_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!$result) {
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Result - Student</title>
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
        <div class="content" style="max-width: 600px; margin: 0 auto;">
            <div class="card" style="text-align: center;">
                <h2>Exam Completed!</h2>
                <h3><?php echo htmlspecialchars($result['exam_title']); ?></h3>
                <p><strong>Course:</strong> <?php echo htmlspecialchars($result['course_name']); ?></p>

                <div style="margin: 2rem 0; padding: 2rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px;">
                    <h1 style="font-size: 3rem; margin: 0;"><?php echo number_format($result['percentage'], 1); ?>%</h1>
                    <p style="margin: 0.5rem 0 0 0; font-size: 1.2rem;">Your Score</p>
                </div>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 2rem;">
                    <div style="text-align: center;">
                        <h4><?php echo $result['score']; ?>/<?php echo $result['total_questions']; ?></h4>
                        <p>Correct Answers</p>
                    </div>
                    <div style="text-align: center;">
                        <h4><?php echo date('M d, Y H:i', strtotime($result['submitted_at'])); ?></h4>
                        <p>Submitted At</p>
                    </div>
                </div>

                <?php if ($result['percentage'] >= 80): ?>
                    <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                        <strong>🎉 Excellent! You passed with distinction!</strong>
                    </div>
                <?php elseif ($result['percentage'] >= 60): ?>
                    <div style="background: #d1ecf1; color: #0c5460; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                        <strong>👍 Good job! You passed!</strong>
                    </div>
                <?php else: ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                        <strong>📚 Keep studying! You can do better next time.</strong>
                    </div>
                <?php endif; ?>

                <div style="display: flex; gap: 1rem; justify-content: center;">
                    <a href="dashboard.php" class="btn">Back to Dashboard</a>
                    <a href="my_results.php" class="btn btn-success">View All Results</a>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>