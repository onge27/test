<?php
include '../database/database.php';
include '../database/session.php';
requireAdmin();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $id = $_POST['user_id'];

    // Don't allow deleting admin
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if ($user['role'] === 'admin') {
            $errors[] = 'Cannot delete admin user';
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $success = 'User deleted successfully';
            } else {
                $errors[] = 'Failed to delete user';
            }
        }
    }
}

// Get all users except current admin
$hasCreatedAt = false;
$columnCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'created_at'");
if ($columnCheck && $columnCheck->num_rows > 0) {
    $hasCreatedAt = true;
}

if ($hasCreatedAt) {
    $users = $conn->query("SELECT id, name, email, role, created_at FROM users WHERE id != {$_SESSION['user_id']} ORDER BY created_at DESC");
} else {
    $users = $conn->query("SELECT id, name, email, role FROM users WHERE id != {$_SESSION['user_id']} ORDER BY name ASC");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">Exam System - Admin</div>
            <div class="user-info">
                <span>Welcome, <?php echo $_SESSION['name']; ?></span>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="main-content">
            <div class="sidebar">
                <h3>Admin Panel</h3>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="manage_courses.php">Manage Courses</a></li>
                    <li><a href="manage_users.php" class="active">Manage Users</a></li>
                </ul>
            </div>

            <div class="content">
                <h2>Manage Users</h2>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <p><?php echo $success; ?></p>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <h3>All Users</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo ucfirst($user['role']); ?></td>
                                    <td><?php echo $hasCreatedAt ? date('Y-m-d', strtotime($user['created_at'])) : 'N/A'; ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;" onsubmit="return confirmDelete('Are you sure you want to delete this user?')">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="delete_user" class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>