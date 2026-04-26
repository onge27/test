<?php
include '../database/database.php';
include '../database/session.php';
requireAdmin();

// Get stats
$user_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role != 'admin'")->fetch_assoc()['count'];
$course_count = $conn->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()['count'];
$exam_count = $conn->query("SELECT COUNT(*) as count FROM exams")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Online Examination System</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo"> Admin</div>
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
                    <li><a href="dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="manage_courses.php">Manage Courses</a></li>
                    <li><a href="manage_users.php">Manage Users</a></li>
                </ul>
            </div>

            <div class="content">
                <h2>Dashboard Overview</h2>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                    <div class="card">
                        <h3><?php echo $user_count; ?></h3>
                        <p>Total Users</p>
                    </div>
                    <div class="card">
                        <h3><?php echo $course_count; ?></h3>
                        <p>Total Courses</p>
                    </div>
                    <div class="card">
                        <h3><?php echo $exam_count; ?></h3>
                        <p>Total Exams</p>
                    </div>
                </div>

                <div class="card">
                    <h3>Quick Actions</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                        <a href="manage_courses.php" class="btn">Manage Courses</a>
                        <a href="manage_users.php" class="btn">Manage Users</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>