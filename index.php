<?php
include 'database/session.php';

if (isLoggedIn()) {
    // Redirect based on role
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: Admin/dashboard.php');
            break;
        case 'teacher':
            header('Location: Teacher/dashboard.php');
            break;
        case 'student':
            header('Location: Students/dashboard.php');
            break;
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Exam & Grading System</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="card animate-in" style="max-width: 800px; margin: 10vh auto; text-align: center;">
            <h1 style="font-size: 3rem; background: linear-gradient(to right, #6366f1, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 1rem;">
                Online Exam & Grading System
            </h1>
            <p style="font-size: 1.2rem; color: var(--text-muted); margin-bottom: 3rem;">
                Empowering education through seamless digital assessments and real-time grading.
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                <div class="card" style="padding: 2rem; background: white;">
                    <h3 style="color: var(--primary-color);">For Students</h3>
                    <p style="font-size: 0.9rem; margin-bottom: 1.5rem;">Take exams, track your progress, and view your results instantly.</p>
                    <a href="login.php" class="btn btn-primary" style="width: 100%;">Student Login</a>
                </div>
                <div class="card" style="padding: 2rem; background: white;">
                    <h3 style="color: var(--secondary-color);">For Teachers</h3>
                    <p style="font-size: 0.9rem; margin-bottom: 1.5rem;">Create exams, manage courses, and grade student submissions with ease.</p>
                    <a href="login.php" class="btn btn-success" style="width: 100%; background: var(--secondary-color);">Teacher Login</a>
                </div>
            </div>

            <div style="margin-top: 3rem;">
                <p>New to the platform? <a href="register.php" style="color: var(--primary-color); font-weight: 600; text-decoration: none;">Create an account</a></p>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>
