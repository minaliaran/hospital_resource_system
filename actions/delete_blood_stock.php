<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
check_logged_in();
require_role('admin');
$hospital_id = $_SESSION['hospital_id'];
$blood_id = intval($_GET['id'] ?? 0);
if (!$blood_id) {
    header("Location: ../admin/blood_stock.php?error=invalid");
    exit;
}
$check = $conn->prepare("SELECT blood_group, component, quantity FROM blood_stock WHERE blood_id = ? AND hospital_id = ?");
$check->bind_param("ii", $blood_id, $hospital_id);
$check->execute();
$existing = $check->get_result()->fetch_assoc();
$check->close();
if (!$existing) {
    header("Location: ../admin/blood_stock.php?error=forbidden");
    exit;
}
$stmt = $conn->prepare("DELETE FROM blood_stock WHERE blood_id = ? AND hospital_id = ?");
$stmt->bind_param("ii", $blood_id, $hospital_id);
if ($stmt->execute()) {
    log_action($_SESSION['user_id'], 'delete_blood_stock', "Deleted blood stock record ID $blood_id ({$existing['blood_group']} {$existing['component']}, quantity: {$existing['quantity']})", $conn);
    header("Location: ../admin/blood_stock.php?success=Stock record deleted successfully.");
} else {
    header("Location: ../admin/blood_stock.php?error=db");
}
$stmt->close();
