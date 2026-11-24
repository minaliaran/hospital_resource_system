<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
check_logged_in();
require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hospital_id = $_SESSION['hospital_id'];
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    if (empty($full_name) || empty($username) || empty($role) || empty($password) || empty($confirm_password)) {
        header("Location: ../admin/users.php?error=invalid");
        exit;
    }
    if ($password !== $confirm_password) {
        header("Location: ../admin/users.php?error=invalid&message=Passwords do not match");
        exit;
    }
    if (!in_array($role, ['blood_staff', 'oxygen_staff'])) {
        header("Location: ../admin/users.php?error=invalid");
        exit;
    }
    $check = $conn->prepare("SELECT user_id FROM users WHERE username = ? LIMIT 1");
    $check->bind_param("s", $username);
    $check->execute();
    if ($check->get_result()->fetch_assoc()) {
        header("Location: ../admin/users.php?error=invalid&message=Username already taken");
        exit;
    }
    $check->close();
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO users (hospital_id, full_name, username, password, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
    $stmt->bind_param("issss", $hospital_id, $full_name, $username, $hashed_password, $role);
    if ($stmt->execute()) {
        log_action($_SESSION['user_id'], 'add_user', "Created new staff user: $username ($role)", $conn);
        header("Location: ../admin/users.php?success=Staff account created successfully.");
    } else {
        header("Location: ../admin/users.php?error=db");
    }
    $stmt->close();
} else {
    header("Location: ../index.php");
}
