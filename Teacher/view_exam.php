<?php
include '../database/database.php';
include '../database/session.php';
requireTeacher();

$exam_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if timer_minutes column exists in exams table
$hasTimerMinutes = false;
$columnCheck = $conn->query("SHOW COLUMNS FROM exams LIKE 'timer_minutes'");
if ($columnCheck && $columnCheck->num_rows > 0) {
    $hasTimerMinutes = true;
}

// Check if teacher_id column exists
$hasTeacherId = false;
$columnCheck = $conn->query("SHOW COLUMNS FROM exams LIKE 'teacher_id'");
if ($columnCheck && $columnCheck->num_rows > 0) {
    $hasTeacherId = true;
}

// Check if options column exists in questions table
$hasOptions = false;
$columnCheck = $conn->query("SHOW COLUMNS FROM questions LIKE 'options'");
if ($columnCheck && $columnCheck->num_rows > 0) {
    $hasOptions = true;
}

// Check if correct_answer column exists in questions table
$hasCorrectAnswer = false;
$columnCheck = $conn->query("SHOW COLUMNS FROM questions LIKE 'correct_answer'");
if ($columnCheck && $columnCheck->num_rows > 0) {
    $hasCorrectAnswer = true;
}

// Check if teacher_id column exists
$hasTeacherId = false;
$columnCheck = $conn->query("SHOW COLUMNS FROM exams LIKE 'teacher_id'");
if ($columnCheck && $columnCheck->num_rows > 0) {
    $hasTeacherId = true;
}

// Get exam details
if ($hasTeacherId) {
    $stmt = $conn->prepare("SELECT e.*, c.name as course_name FROM exams e JOIN courses c ON e.course_id = c.id WHERE e.id = ? AND e.teacher_id = ?");
    $stmt->bind_param("ii", $exam_id, $_SESSION['user_id']);
    $stmt->execute();
    $exam = $stmt->get_result()->fetch_assoc();
} else {
    // If no teacher_id column, just check if exam exists (less secure but prevents crash)
    $stmt = $conn->prepare("SELECT e.*, c.name as course_name FROM exams e JOIN courses c ON e.course_id = c.id WHERE e.id = ?");
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $exam = $stmt->get_result()->fetch_assoc();
}

if (!$exam) {
    header('Location: manage_exams.php');
    exit();
}

// Get questions
$questions = $conn->query("SELECT * FROM questions WHERE exam_id = $exam_id ORDER BY id");

// Get results
$results = $conn->query("
    SELECT r.*, u.name as student_name, u.email
    FROM results r
    JOIN users u ON r.student_id = u.id
    WHERE r.exam_id = $exam_id
    ORDER BY r.submitted_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Exam - Teacher</title>
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
                <h2><?php echo htmlspecialchars($exam['title']); ?></h2>

                <div class="card">
                    <h3>Exam Details</h3>
                    <p><strong>Course:</strong> <?php echo htmlspecialchars($exam['course_name']); ?></p>
                    <p><strong>Timer:</strong> <?php echo $hasTimerMinutes ? $exam['timer_minutes'] : 'N/A'; ?> minutes</p>
                    <p><strong>Created:</strong> <?php echo date('Y-m-d H:i', strtotime($exam['created_at'])); ?></p>
                </div>

                <div class="card">
                    <h3>Questions</h3>
                    <?php $q_num = 1; while ($question = $questions->fetch_assoc()): ?>
                        <div class="question">
                            <h4>Question <?php echo $q_num++; ?>: <?php echo htmlspecialchars($question['question_text']); ?></h4>
                            <p><strong>Type:</strong> <?php echo ucfirst($question['type']); ?></p>

                            <?php if ($question['type'] === 'mcq'): ?>
                                <p><strong>Options:</strong></p>
                                <?php if ($hasOptions && !empty($question['options'])): ?>
                                    <?php $options = json_decode($question['options'], true); ?>
                                    <?php if (is_array($options)): ?>
                                        <ul>
                                            <?php foreach ($options as $key => $option): ?>
                                                <li><?php echo $key; ?>: <?php echo htmlspecialchars($option); ?>
                                                    <?php if ($hasCorrectAnswer && $key === $question['correct_answer']): ?>
                                                        <strong>(Correct)</strong>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="alert alert-error">Error: Invalid question options format.</p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="alert alert-error">Error: This multiple choice question is missing its options.</p>
                                <?php endif; ?>
                            <?php elseif ($question['type'] === 'tf'): ?>
                                <?php if ($hasCorrectAnswer): ?>
                                    <p><strong>Correct Answer:</strong> <?php echo $question['correct_answer'] === 'true' ? 'True' : 'False'; ?></p>
                                <?php else: ?>
                                    <p class="alert alert-error">Error: Correct answer not available.</p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>

                <div class="card">
                    <h3>Student Results</h3>
                    <?php if ($results->num_rows > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Email</th>
                                    <th>Score</th>
                                    <th>Total</th>
                                    <th>Percentage</th>
                                    <th>Submitted</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($result = $results->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($result['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($result['email']); ?></td>
                                        <td><?php echo $result['score']; ?></td>
                                        <td><?php echo $result['total_questions']; ?></td>
                                        <td><?php echo number_format($result['percentage'], 1); ?>%</td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($result['submitted_at'])); ?></td>
                                        <td>
                                            <a href="view_student_answers.php?exam_id=<?php echo $exam_id; ?>&student_id=<?php echo $result['student_id']; ?>" class="btn btn-success" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">View Answers</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No students have taken this exam yet.</p>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 2rem;">
                    <a href="manage_exams.php" class="btn">Back to Exams</a>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>