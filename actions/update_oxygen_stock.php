<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
check_logged_in();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hospital_id = $_SESSION['hospital_id'];
    $oxygen_id = intval($_POST['oxygen_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    if (!$oxygen_id || $quantity < 0) {
        $redirect = ($_SESSION['role'] === 'admin') ? '../admin/oxygen_stock.php' : '../staff/view_oxygen_stock.php';
        header("Location: $redirect?error=invalid");
        exit;
    }
    $check = $conn->prepare("SELECT cylinder_type FROM oxygen_stock WHERE oxygen_id = ? AND hospital_id = ?");
    $check->bind_param("ii", $oxygen_id, $hospital_id);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();
    $check->close();
    if (!$existing) {
        $redirect = ($_SESSION['role'] === 'admin') ? '../admin/oxygen_stock.php' : '../staff/view_oxygen_stock.php';
        header("Location: $redirect?error=forbidden");
        exit;
    }
    $stmt = $conn->prepare("UPDATE oxygen_stock SET quantity = ? WHERE oxygen_id = ? AND hospital_id = ?");
    $stmt->bind_param("iii", $quantity, $oxygen_id, $hospital_id);
    if ($stmt->execute()) {
        log_action($_SESSION['user_id'], 'update_oxygen_stock', "Updated oxygen cylinder ID $oxygen_id ({$existing['cylinder_type']}) quantity to $quantity", $conn);
        if ($quantity <= 2) {
            $msg = "Critical Alert: Oxygen Cylinder stock ({$existing['cylinder_type']}) level is critical: $quantity remaining.";
            create_notification($hospital_id, "Critical Stock Level", $msg, null, $conn);
        }
        $redirect = ($_SESSION['role'] === 'admin') ? '../admin/oxygen_stock.php' : '../staff/view_oxygen_stock.php';
        header("Location: $redirect?success=Oxygen stock updated successfully.");
    } else {
        $redirect = ($_SESSION['role'] === 'admin') ? '../admin/oxygen_stock.php' : '../staff/view_oxygen_stock.php';
        header("Location: $redirect?error=db");
    }
    $stmt->close();
} else {
    header("Location: ../index.php");
}
