<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
check_logged_in();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hospital_id = $_SESSION['hospital_id'];
    $cylinder_type = $_POST['cylinder_type'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);
    if (empty($cylinder_type) || $quantity < 0) {
        $redirect = ($_SESSION['role'] === 'admin') ? '../admin/oxygen_stock.php' : '../staff/view_oxygen_stock.php';
        header("Location: $redirect?error=invalid");
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO oxygen_stock (hospital_id, cylinder_type, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");
    $stmt->bind_param("isii", $hospital_id, $cylinder_type, $quantity, $quantity);
    if ($stmt->execute()) {
        log_action($_SESSION['user_id'], 'add_oxygen_stock', "Added/Updated $quantity $cylinder_type oxygen cylinders", $conn);
        $redirect = ($_SESSION['role'] === 'admin') ? '../admin/oxygen_stock.php' : '../staff/view_oxygen_stock.php';
        header("Location: $redirect?success=Oxygen stock added successfully.");
    } else {
        $redirect = ($_SESSION['role'] === 'admin') ? '../admin/oxygen_stock.php' : '../staff/view_oxygen_stock.php';
        header("Location: $redirect?error=db");
    }
    $stmt->close();
} else {
    header("Location: ../index.php");
}
