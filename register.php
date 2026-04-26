<?php
include 'database/database.php';
include 'database/session.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
        $errors[] = 'Please fill in all fields';
    } elseif ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long';
    } elseif (!in_array($role, ['teacher', 'student'])) {
        $errors[] = 'Invalid role selected';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errors[] = 'Email already exists';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                $success = true;
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Online Exam System</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="card animate-in" style="max-width: 500px; margin: 5vh auto;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h2 style="color: var(--primary-color);">Create Account</h2>
                <p style="color: var(--text-muted);">Join our community today</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): echo "<p>$error</p>"; endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <p>Registration successful! <a href="login.php" style="font-weight: 700; color: #166534;">Login here</a></p>
                </div>
            <?php else: ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" placeholder="full name" required value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="email address" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="role">I am a...</label>
                        <select id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="student" <?php echo (isset($role) && $role === 'student') ? 'selected' : ''; ?>>Student</option>
                            <option value="teacher" <?php echo (isset($role) && $role === 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem; margin-top: 1rem;">
                        Register
                    </button>
                </form>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 2rem; border-top: 1px solid #eee; padding-top: 1.5rem;">
                <p style="color: var(--text-muted);">Already have an account? <a href="login.php" style="color: var(--primary-color); font-weight: 600; text-decoration: none;">Login</a></p>
                <div style="margin-top: 1rem;">
                    <a href="index.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem;">← Back to Home</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
