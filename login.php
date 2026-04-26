<?php
include 'database/database.php';
include 'database/session.php';

$errors = [];

if (isLoggedIn()) {
    // Redirect based on role
    switch ($_SESSION['role']) {
        case 'admin': header('Location: Admin/dashboard.php'); break;
        case 'teacher': header('Location: Teacher/dashboard.php'); break;
        case 'student': header('Location: Students/dashboard.php'); break;
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $errors[] = 'Please fill in all fields';
    } else {
        $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $user['role'];

                switch ($user['role']) {
                    case 'admin': header('Location: Admin/dashboard.php'); break;
                    case 'teacher': header('Location: Teacher/dashboard.php'); break;
                    case 'student': header('Location: Students/dashboard.php'); break;
                }
                exit();
            } else {
                $errors[] = 'Invalid password';
            }
        } else {
            $errors[] = 'User not found';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Online Exam System</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="card animate-in" style="max-width: 450px; margin: 10vh auto;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h2 style="color: var(--primary-color);">Welcome Back</h2>
                <p style="color: var(--text-muted);">Please enter your credentials to login</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): echo "<p>$error</p>"; endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="name@example.com" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem; margin-top: 1rem;">
                    Sign In
                </button>
            </form>

            <div style="text-align: center; margin-top: 2rem; border-top: 1px solid #eee; padding-top: 1.5rem;">
                <p style="color: var(--text-muted);">Don't have an account? <a href="register.php" style="color: var(--primary-color); font-weight: 600; text-decoration: none;">Register Now</a></p>
                <div style="margin-top: 1rem;">
                    <a href="index.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem;">← Back to Home</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
