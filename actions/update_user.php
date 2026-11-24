<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
check_logged_in();
require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hospital_id = $_SESSION['hospital_id'];
    $user_id = intval($_POST['user_id'] ?? 0);
    $full_name = trim($_POST['full_name'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    if (!$user_id || empty($full_name)) {
        header("Location: ../admin/users.php?error=invalid");
        exit;
    }
    $check = $conn->prepare("SELECT username FROM users WHERE user_id = ? AND hospital_id = ? AND role != 'admin'");
    $check->bind_param("ii", $user_id, $hospital_id);
    $check->execute();
    $u = $check->get_result()->fetch_assoc();
    $check->close();
    if (!$u) {
        header("Location: ../admin/users.php?error=forbidden");
        exit;
    }
    if (!empty($new_password)) {
        $hashed = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, password = ? WHERE user_id = ? AND hospital_id = ?");
        $stmt->bind_param("ssii", $full_name, $hashed, $user_id, $hospital_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name = ? WHERE user_id = ? AND hospital_id = ?");
        $stmt->bind_param("sii", $full_name, $user_id, $hospital_id);
    }
    if ($stmt->execute()) {
        log_action($_SESSION['user_id'], 'update_user', "Updated details for staff user: {$u['username']}", $conn);
        header("Location: ../admin/users.php?success=Staff account updated successfully.");
    } else {
        header("Location: ../admin/users.php?error=db");
    }
    $stmt->close();
} else {
    header("Location: ../index.php");
}
