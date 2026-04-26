<?php
include '../database/database.php';
include '../database/session.php';
requireTeacher();

$errors = [];
$success = false;

// Check teacher_id column
$hasTeacherId = $conn->query("SHOW COLUMNS FROM exams LIKE 'teacher_id'")->num_rows > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_name = isset($_POST['course_name']) ? trim($_POST['course_name']) : '';
    $timer_minutes = isset($_POST['timer_minutes']) ? (int)$_POST['timer_minutes'] : 0;
    $questions = $_POST['questions'] ?? [];

    // VALIDATION
    if (empty($course_name) || $timer_minutes <= 0 || empty($questions)) {
        $errors[] = "Please complete all fields and add questions.";
    } else {
        $conn->begin_transaction();
        try {
            // 1. Handle Course (Get or Create)
            $stmt = $conn->prepare("SELECT id FROM courses WHERE name = ?");
            $stmt->bind_param("s", $course_name);
            $stmt->execute();
            $course_result = $stmt->get_result();
            
            if ($course_result->num_rows > 0) {
                $course_id = $course_result->fetch_assoc()['id'];
            } else {
                $stmt = $conn->prepare("INSERT INTO courses (name, created_by) VALUES (?, ?)");
                $stmt->bind_param("si", $course_name, $_SESSION['user_id']);
                $stmt->execute();
                $course_id = $conn->insert_id;
            }

            $title = $course_name . " Exam";

            // 2. Insert Exam
            $stmt = $conn->prepare("
                INSERT INTO exams (title, course_id, teacher_id, timer_minutes)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("siii", $title, $course_id, $_SESSION['user_id'], $timer_minutes);
            $stmt->execute();
            $exam_id = $conn->insert_id;

            // INSERT QUESTIONS
            foreach ($questions as $q) {

                $text = trim($q['text']);
                $type = $q['type'] ?? '';

                if (empty($text) || empty($type)) continue;

                $options = null;
                $correct_answer = null;

                // MCQ
                if ($type === 'mcq') {

                    $rawOptions = $q['options'] ?? [];
                    $formattedOptions = [];
                    $letters = range('A', 'H');

                    foreach ($rawOptions as $i => $opt) {
                        if (!empty(trim($opt))) {
                            $formattedOptions[$letters[$i]] = $opt;
                        }
                    }

                    $options = json_encode($formattedOptions);
                    $correct_answer = $q['correct'] ?? null;
                }

                // TRUE/FALSE
                elseif ($type === 'tf') {
                    $correct_answer = $q['correct'] ?? null;
                }

                // INSERT QUESTION
                $stmt = $conn->prepare("
                    INSERT INTO questions (exam_id, question_text, type, options, correct_answer)
                    VALUES (?, ?, ?, ?, ?)
                ");

                $stmt->bind_param("issss", $exam_id, $text, $type, $options, $correct_answer);
                $stmt->execute();
            }

            $conn->commit();
            $success = true;

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}

// GET COURSES
$courses = $conn->query("SELECT * FROM courses ORDER BY name");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Exam - Teacher</title>
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
                    <li><a href="create_exam.php" class="active">Create Exam</a></li>
                    <li><a href="manage_exams.php">Manage Exams</a></li>
                </ul>
            </div>

            <div class="content">
                <h2>Create New Exam</h2>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors as $e) echo "<p>$e</p>"; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        Exam Created Successfully! <a href="manage_exams.php">View Exams</a>
                    </div>
                <?php endif; ?>

                <form method="POST" id="createExamForm">
                    <div class="card">
                        <h3>Exam Details</h3>
                        <div class="form-group">
                            <label>Course Name</label>
                            <input type="text" name="course_name" placeholder="e.g. BSIT 1-1, Mathematics" required list="courseList">
                            <datalist id="courseList">
                                <?php while($c = $courses->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($c['name']); ?>">
                                <?php endwhile; ?>
                            </datalist>
                        </div>

                        <div class="form-group">
                            <label>Timer (minutes)</label>
                            <input type="number" name="timer_minutes" min="1" value="60" required>
                        </div>
                    </div>

                    <div id="questions"></div>

                    <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                        <button type="button" class="btn btn-success" onclick="addQuestion()">+ Add Question</button>
                        <button type="submit" class="btn">Create Exam</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>

