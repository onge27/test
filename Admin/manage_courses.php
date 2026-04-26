<?php
include '../database/database.php';
include '../database/session.php';
requireAdmin();

$errors = [];
$success = '';
$courseWarning = '';

$hasDescription = false;
$columnCheck = $conn->query("SHOW COLUMNS FROM courses LIKE 'description'");
if ($columnCheck && $columnCheck->num_rows > 0) {
    $hasDescription = true;
}

$hasCreatedBy = false;
$columnCheck = $conn->query("SHOW COLUMNS FROM courses LIKE 'created_by'");
if ($columnCheck && $columnCheck->num_rows > 0) {
    $hasCreatedBy = true;
}

if (!$hasDescription || !$hasCreatedBy) {
    $missing = [];
    if (!$hasDescription) {
        $missing[] = 'courses.description';
    }
    if (!$hasCreatedBy) {
        $missing[] = 'courses.created_by';
    }
    $courseWarning = 'Your database is missing the following courses columns: ' . implode(', ', $missing) . '. Some course features are unavailable.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_course'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);

        if (empty($name)) {
            $errors[] = 'Course name is required';
        } else {
            if ($hasDescription && $hasCreatedBy) {
                $stmt = $conn->prepare("INSERT INTO courses (name, description, created_by) VALUES (?, ?, ?)");
                $stmt->bind_param("ssi", $name, $description, $_SESSION['user_id']);
            } elseif ($hasDescription) {
                $stmt = $conn->prepare("INSERT INTO courses (name, description) VALUES (?, ?)");
                $stmt->bind_param("ss", $name, $description);
            } elseif ($hasCreatedBy) {
                $stmt = $conn->prepare("INSERT INTO courses (name, created_by) VALUES (?, ?)");
                $stmt->bind_param("si", $name, $_SESSION['user_id']);
            } else {
                $stmt = $conn->prepare("INSERT INTO courses (name) VALUES (?)");
                $stmt->bind_param("s", $name);
            }

            if ($stmt->execute()) {
                $success = 'Course added successfully';
            } else {
                $errors[] = 'Failed to add course';
            }
        }
    } elseif (isset($_POST['edit_course'])) {
        $id = $_POST['course_id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);

        if (empty($name)) {
            $errors[] = 'Course name is required';
        } else {
            if ($hasDescription) {
                $stmt = $conn->prepare("UPDATE courses SET name = ?, description = ? WHERE id = ?");
                $stmt->bind_param("ssi", $name, $description, $id);
            } else {
                $stmt = $conn->prepare("UPDATE courses SET name = ? WHERE id = ?");
                $stmt->bind_param("si", $name, $id);
            }

            if ($stmt->execute()) {
                $success = 'Course updated successfully';
            } else {
                $errors[] = 'Failed to update course';
            }
        }
    } elseif (isset($_POST['delete_course'])) {
        $id = $_POST['course_id'];
        $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $success = 'Course deleted successfully';
        } else {
            $errors[] = 'Failed to delete course';
        }
    }
}

// Get all courses
if ($hasDescription) {
    $courses = $conn->query("SELECT * FROM courses ORDER BY created_at DESC");
} else {
    $courses = $conn->query("SELECT id, name, created_at FROM courses ORDER BY created_at DESC");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - Admin</title>
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
                    <li><a href="manage_courses.php" class="active">Manage Courses</a></li>
                    <li><a href="manage_users.php">Manage Users</a></li>
                </ul>
            </div>

            <div class="content">
                <h2>Manage Courses</h2>

                <?php if ($courseWarning): ?>
                    <div class="alert alert-error">
                        <p><?php echo $courseWarning; ?></p>
                    </div>
                <?php endif; ?>

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
                    <h3>All Courses</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($course = $courses->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $course['id']; ?></td>
                                    <td><?php echo htmlspecialchars($course['name']); ?></td>
                                    <td><?php echo $hasDescription ? htmlspecialchars($course['description']) : 'N/A'; ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($course['created_at'])); ?></td>
                                    <td>
                                        <button onclick="editCourse(<?php echo $course['id']; ?>, '<?php echo addslashes($course['name']); ?>', '<?php echo addslashes($hasDescription ? $course['description'] : ''); ?>')" class="btn btn-success" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Edit</button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirmDelete()">
                                            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                            <button type="submit" name="delete_course" class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Delete</button>
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

    <!-- Edit Course Modal -->

    <script src="../js/script.js"></script>
    <script>
        function editCourse(id, name, description) {
            document.getElementById('edit_course_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_description').value = description;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>
</body>
</html>