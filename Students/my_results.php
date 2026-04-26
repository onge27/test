<?php
include '../database/database.php';
include '../database/session.php';
requireStudent();

// Get student's results
$results = $conn->query("
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
    <title>My Results - Student</title>
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
                    <li><a href="available_exams.php">Available Exams</a></li>
                    <li><a href="my_results.php" class="active">My Results</a></li>
                </ul>
            </div>

            <div class="content">
                <h2>My Exam Results</h2>

                <?php if ($results->num_rows > 0): ?>
                    <div class="card">
                        <h3>Exam History</h3>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Exam</th>
                                    <th>Course</th>
                                    <th>Score</th>
                                    <th>Percentage</th>
                                    <th>Grade</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($result = $results->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($result['exam_title']); ?></td>
                                        <td><?php echo htmlspecialchars($result['course_name']); ?></td>
                                        <td><?php echo $result['score']; ?>/<?php echo $result['total_questions']; ?></td>
                                        <td><?php echo number_format($result['percentage'], 1); ?>%</td>
                                        <td>
                                            <?php if ($result['percentage'] >= 90): ?>
                                                <span style="color: #28a745; font-weight: bold;">A+</span>
                                            <?php elseif ($result['percentage'] >= 80): ?>
                                                <span style="color: #28a745; font-weight: bold;">A</span>
                                            <?php elseif ($result['percentage'] >= 70): ?>
                                                <span style="color: #20c997; font-weight: bold;">B+</span>
                                            <?php elseif ($result['percentage'] >= 60): ?>
                                                <span style="color: #20c997; font-weight: bold;">B</span>
                                            <?php elseif ($result['percentage'] >= 50): ?>
                                                <span style="color: #ffc107; font-weight: bold;">C</span>
                                            <?php elseif ($result['percentage'] >= 40): ?>
                                                <span style="color: #fd7e14; font-weight: bold;">D</span>
                                            <?php else: ?>
                                                <span style="color: #dc3545; font-weight: bold;">F</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, Y H:i', strtotime($result['submitted_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="card">
                        <h3>Statistics</h3>
                        <?php
                        $results->data_seek(0);
                        $total_exams = $results->num_rows;
                        $total_score = 0;
                        $highest = 0;
                        $lowest = 100;

                        while ($result = $results->fetch_assoc()) {
                            $total_score += $result['percentage'];
                            $highest = max($highest, $result['percentage']);
                            $lowest = min($lowest, $result['percentage']);
                        }

                        $average = $total_exams > 0 ? $total_score / $total_exams : 0;
                        ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                            <div style="text-align: center;">
                                <h4><?php echo $total_exams; ?></h4>
                                <p>Total Exams</p>
                            </div>
                            <div style="text-align: center;">
                                <h4><?php echo number_format($average, 1); ?>%</h4>
                                <p>Average Score</p>
                            </div>
                            <div style="text-align: center;">
                                <h4><?php echo number_format($highest, 1); ?>%</h4>
                                <p>Highest Score</p>
                            </div>
                            <div style="text-align: center;">
                                <h4><?php echo number_format($lowest, 1); ?>%</h4>
                                <p>Lowest Score</p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <h3>No Results Yet</h3>
                        <p>You haven't taken any exams yet. <a href="available_exams.php">Browse available exams</a> to get started!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>