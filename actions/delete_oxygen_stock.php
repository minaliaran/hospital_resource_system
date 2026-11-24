<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
check_logged_in();
require_role('admin');
$hospital_id = $_SESSION['hospital_id'];
$oxygen_id = intval($_GET['id'] ?? 0);
if (!$oxygen_id) {
    header("Location: ../admin/oxygen_stock.php?error=invalid");
    exit;
}
$check = $conn->prepare("SELECT cylinder_type, quantity FROM oxygen_stock WHERE oxygen_id = ? AND hospital_id = ?");
$check->bind_param("ii", $oxygen_id, $hospital_id);
$check->execute();
$existing = $check->get_result()->fetch_assoc();
$check->close();
if (!$existing) {
    header("Location: ../admin/oxygen_stock.php?error=forbidden");
    exit;
}
$stmt = $conn->prepare("DELETE FROM oxygen_stock WHERE oxygen_id = ? AND hospital_id = ?");
$stmt->bind_param("ii", $oxygen_id, $hospital_id);
if ($stmt->execute()) {
    log_action($_SESSION['user_id'], 'delete_oxygen_stock', "Deleted oxygen stock record ID $oxygen_id ({$existing['cylinder_type']} Cylinder, quantity: {$existing['quantity']})", $conn);
    header("Location: ../admin/oxygen_stock.php?success=Stock record deleted successfully.");
} else {
    header("Location: ../admin/oxygen_stock.php?error=db");
}
$stmt->close();
