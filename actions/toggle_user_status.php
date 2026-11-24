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
    if (!$user_id) {
        header("Location: ../admin/users.php?error=invalid");
        exit;
    }
    $check = $conn->prepare("SELECT username, status FROM users WHERE user_id = ? AND hospital_id = ? AND role != 'admin'");
    $check->bind_param("ii", $user_id, $hospital_id);
    $check->execute();
    $u = $check->get_result()->fetch_assoc();
    $check->close();
    if (!$u) {
        header("Location: ../admin/users.php?error=forbidden");
        exit;
    }
    $new_status = ($u['status'] === 'active') ? 'inactive' : 'active';
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ? AND hospital_id = ?");
    $stmt->bind_param("sii", $new_status, $user_id, $hospital_id);
    if ($stmt->execute()) {
        log_action($_SESSION['user_id'], 'toggle_user_status', "Toggled status of user {$u['username']} to $new_status", $conn);
        header("Location: ../admin/users.php?success=User account status updated to $new_status.");
    } else {
        header("Location: ../admin/users.php?error=db");
    }
    $stmt->close();
} else {
    header("Location: ../index.php");
}
