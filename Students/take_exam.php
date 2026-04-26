<?php
include '../database/database.php';
include '../database/session.php';
requireStudent();

$exam_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* CHECKS */
$hasTimerMinutes = $conn->query("SHOW COLUMNS FROM exams LIKE 'timer_minutes'")->num_rows > 0;
$hasOptions = $conn->query("SHOW COLUMNS FROM questions LIKE 'options'")->num_rows > 0;
$hasCorrectAnswer = $conn->query("SHOW COLUMNS FROM questions LIKE 'correct_answer'")->num_rows > 0;

/* GET EXAM (UPDATED: NO courses JOIN, uses course_name) */
$stmt = $conn->prepare("
    SELECT e.*, c.name as course_name 
    FROM exams e
    JOIN courses c ON e.course_id = c.id
    WHERE e.id = ?
");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();

if (!$exam) {
    header('Location: available_exams.php');
    exit();
}

/* CHECK IF ALREADY TAKEN */
$stmt = $conn->prepare("
    SELECT id FROM results
    WHERE exam_id = ? AND student_id = ?
");
$stmt->bind_param("ii", $exam_id, $_SESSION['user_id']);
$stmt->execute();

if ($stmt->get_result()->num_rows > 0) {
    header('Location: available_exams.php');
    exit();
}

/* GET QUESTIONS */
$result = $conn->query("SELECT * FROM questions WHERE exam_id = $exam_id ORDER BY id");

$questions = [];

while ($q = $result->fetch_assoc()) {

    if ($q['type'] === 'mcq') {
        $opts = $hasOptions && !empty($q['options'])
            ? json_decode($q['options'], true)
            : null;

        if (!is_array($opts) || count($opts) < 2) {
            continue;
        }
    }

    $questions[] = $q;
}

$question_count = count($questions);

if ($question_count === 0) {
    die("No valid questions found for this exam.");
}

/* SUBMIT EXAM */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $answers = $_POST['answers'] ?? [];
    $score = 0;

    $conn->begin_transaction();

    try {

        $insert = $conn->prepare("
            INSERT INTO student_answers
            (question_id, student_id, answer_text, is_correct)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($questions as $q) {

            $qid = $q['id'];
            $answer = isset($answers[$qid]) ? trim($answers[$qid]) : '';
            $is_correct = 0;

            if ($hasCorrectAnswer && ($q['type'] === 'mcq' || $q['type'] === 'tf')) {

                $correct = $q['correct_answer'] ?? '';

                if ($q['type'] === 'mcq') {
                    $is_correct = ($answer === $correct) ? 1 : 0;
                } else {
                    $is_correct = (strtolower($answer) === strtolower($correct)) ? 1 : 0;
                }

                if ($is_correct === 1) $score++;
            }

            $insert->bind_param("iisi",
                $qid,
                $_SESSION['user_id'],
                $answer,
                $is_correct
            );

            $insert->execute();
        }

        $percentage = ($score / $question_count) * 100;

        $stmt = $conn->prepare("
            INSERT INTO results
            (exam_id, student_id, score, total_questions, percentage)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "iiiid",
            $exam_id,
            $_SESSION['user_id'],
            $score,
            $question_count,
            $percentage
        );

        $stmt->execute();

        $conn->commit();

        // clear timer
        echo "<script>localStorage.removeItem('exam_timer_{$exam_id}_{$_SESSION['user_id']}');</script>";

        header("Location: exam_result.php?exam_id=$exam_id");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        die("Submission Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($exam['title']); ?> - Taking Exam</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .exam-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .timer-fixed {
            position: sticky;
            top: 10px;
            z-index: 1000;
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
        }
        .timer-badge {
            background: #fff;
            padding: 10px 30px;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 2px solid #0056b3;
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
        }
        .timer-badge.low-time {
            border-color: #dc3545;
            color: #dc3545;
        }
        .question-card {
            margin-bottom: 20px;
        }
        .option-label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
            cursor: pointer;
        }
        .option-label:hover {
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">Exam System - Student</div>
            <div class="user-info">
                <span>Welcome, <?php echo $_SESSION['name']; ?></span>
                <span class="logout-btn" onclick="confirmExit()">Exit Exam</span>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="exam-container">
            <div class="timer-fixed">
                <div id="timerBadge" class="timer-badge">
                    Time Remaining: <span id="timeDisplay">--:--</span>
                </div>
            </div>

            <form method="POST" id="examForm">
                <?php $num = 1; foreach ($questions as $q): ?>
                    <div class="card question-card animate-in" style="animation-delay: <?php echo $num * 0.1; ?>s">
                        <h3 style="margin-bottom: 1.5rem;">Question <?php echo $num++; ?></h3>
                        <p style="font-size: 1.1rem; margin-bottom: 2rem; color: #444;">
                            <?php echo htmlspecialchars($q['question_text']); ?>
                        </p>

                        <?php if ($q['type'] === 'mcq'): ?>
                            <div class="options">
                                <?php $opts = json_decode($q['options'], true); ?>
                                <?php if (is_array($opts)): ?>
                                    <?php foreach ($opts as $k => $opt): ?>
                                        <label class="option-label">
                                            <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="<?php echo $k; ?>" required>
                                            <span><strong><?php echo $k; ?>.</strong> <?php echo htmlspecialchars($opt); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                        <?php elseif ($q['type'] === 'tf'): ?>
                            <div class="options">
                                <label class="option-label">
                                    <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="true" required>
                                    <span>True</span>
                                </label>
                                <label class="option-label">
                                    <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="false" required>
                                    <span>False</span>
                                </label>
                            </div>

                        <?php elseif ($q['type'] === 'short'): ?>
                            <div class="form-group">
                                <label>Your Answer (Short Answer)</label>
                                <input type="text" name="answers[<?php echo $q['id']; ?>]" required placeholder="Type your answer here..." autocomplete="off">
                            </div>

                        <?php else: ?>
                            <div class="form-group">
                                <label>Your Answer (Essay)</label>
                                <textarea name="answers[<?php echo $q['id']; ?>]" required placeholder="Type your answer here..." style="min-height: 200px;"></textarea>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div style="text-align: center; margin-bottom: 4rem;">
                    <button type="submit" class="btn btn-primary" style="padding: 1rem 4rem; font-size: 1.2rem;">Submit Exam</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Timer Logic
        let duration = <?php echo (int)$exam['timer_minutes']; ?> * 60;
        let key = "exam_timer_<?php echo $exam_id . '_' . $_SESSION['user_id']; ?>";
        let startTime = localStorage.getItem(key);

        if (!startTime) {
            startTime = Date.now();
            localStorage.setItem(key, startTime);
        } else {
            startTime = parseInt(startTime);
        }

        function updateTimer() {
            let elapsed = Math.floor((Date.now() - startTime) / 1000);
            let remaining = duration - elapsed;

            if (remaining <= 0) {
                document.getElementById("timeDisplay").innerHTML = "00:00";
                localStorage.removeItem(key);
                alert("Time is up! Your exam will be submitted automatically.");
                document.getElementById("examForm").submit();
                return;
            }

            if (remaining < 60) {
                document.getElementById("timerBadge").classList.add("low-time");
            }

            let minutes = Math.floor(remaining / 60);
            let seconds = remaining % 60;
            document.getElementById("timeDisplay").innerHTML = 
                (minutes < 10 ? "0" + minutes : minutes) + ":" + 
                (seconds < 10 ? "0" + seconds : seconds);
        }

        setInterval(updateTimer, 1000);
        updateTimer();

        function confirmExit() {
            if (confirm("Are you sure you want to exit? Your progress will not be saved if you leave before submitting.")) {
                window.location.href = "available_exams.php";
            }
        }

        // Prevent accidental refresh
        window.onbeforeunload = function() {
            return "You have an ongoing exam. Are you sure you want to leave?";
        };

        // Remove warning on form submit
        document.getElementById("examForm").onsubmit = function() {
            window.onbeforeunload = null;
        };
    </script>
</body>
</html>