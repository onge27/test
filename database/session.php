<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check user role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Function to redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit();
    }
}

// Function to redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!hasRole('admin')) {
        header('Location: ../index.php');
        exit();
    }
}

// Function to redirect if not teacher
function requireTeacher() {
    requireLogin();
    if (!hasRole('teacher')) {
        header('Location: ../index.php');
        exit();
    }
}

// Function to redirect if not student
function requireStudent() {
    requireLogin();
    if (!hasRole('student')) {
        header('Location: ../index.php');
        exit();
    }
}
?>