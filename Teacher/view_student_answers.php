<?php
include '../database/database.php';
include '../database/session.php';
requireTeacher();

$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

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

// Verify exam belongs to teacher
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

// Get student info
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ? AND role = 'student'");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    header('Location: view_exam.php?id=' . $exam_id);
    exit();
}

// Get questions and answers
$questions = $conn->query("
    SELECT q.*, sa.answer_text, sa.is_correct
    FROM questions q
    LEFT JOIN student_answers sa ON q.id = sa.question_id AND sa.student_id = $student_id
    WHERE q.exam_id = $exam_id
    ORDER BY q.id
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Answers - Teacher</title>
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
                <h2><?php echo htmlspecialchars($exam['title']); ?> - <?php echo htmlspecialchars($student['name']); ?>'s Answers</h2>

                <div class="card">
                    <h3>Student Information</h3>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($student['name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
                </div>

                <div class="card">
                    <h3>Answers</h3>
                    <?php $q_num = 1; $total_score = 0; $total_questions = 0; ?>
                    <?php while ($question = $questions->fetch_assoc()): ?>
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
                                <p><strong>Student's Answer:</strong> <?php echo $question['answer_text'] ? htmlspecialchars($question['answer_text']) : 'Not answered'; ?></p>
                            <?php elseif ($question['type'] === 'tf'): ?>
                                <?php if ($hasCorrectAnswer): ?>
                                    <p><strong>Correct Answer:</strong> <?php echo $question['correct_answer'] === 'true' ? 'True' : 'False'; ?></p>
                                <?php else: ?>
                                    <p class="alert alert-error">Error: Correct answer not available.</p>
                                <?php endif; ?>
                                <p><strong>Student's Answer:</strong> <?php echo $question['answer_text'] ? ucfirst($question['answer_text']) : 'Not answered'; ?></p>
                            <?php elseif ($question['type'] === 'essay'): ?>
                                <p><strong>Student's Answer:</strong></p>
                                <div style="border: 1px solid #ddd; padding: 1rem; border-radius: 5px; background: #f9f9f9;">
                                    <?php echo $question['answer_text'] ? nl2br(htmlspecialchars($question['answer_text'])) : 'Not answered'; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($question['type'] !== 'essay'): ?>
                                <p><strong>Result:</strong>
                                    <?php if ($question['is_correct'] === 1): ?>
                                        <span style="color: #28a745; font-weight: bold;">Correct</span>
                                        <?php $total_score++; ?>
                                    <?php elseif ($question['is_correct'] === 0): ?>
                                        <span style="color: #dc3545; font-weight: bold;">Incorrect</span>
                                    <?php else: ?>
                                        <span style="color: #ffc107; font-weight: bold;">Not graded</span>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>

                            <?php $total_questions++; ?>
                        </div>
                    <?php endwhile; ?>

                    <div style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 5px;">
                        <h4>Summary</h4>
                        <p><strong>Auto-graded Questions:</strong> <?php echo $total_score; ?>/<?php echo $total_questions; ?> correct</p>
                        <p><strong>Percentage:</strong> <?php echo $total_questions > 0 ? number_format(($total_score / $total_questions) * 100, 1) : 0; ?>%</p>
                    </div>
                </div>

                <div style="margin-top: 2rem;">
                    <a href="view_exam.php?id=<?php echo $exam_id; ?>" class="btn">Back to Exam</a>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>