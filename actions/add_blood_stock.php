<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
check_logged_in();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hospital_id = $_SESSION['hospital_id'];
    $blood_group = $_POST['blood_group'] ?? '';
    $component = $_POST['component'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);
    $expiry_date = $_POST['expiry_date'] ?? '';
    if (empty($blood_group) || empty($component) || $quantity <= 0 || empty($expiry_date)) {
        $redirect = ($_SESSION['role'] === 'admin') ? '../admin/blood_stock.php' : '../staff/view_blood_stock.php';
        header("Location: $redirect?error=invalid");
        exit;
    }
    if (strtotime($expiry_date) < strtotime(date('Y-m-d'))) {
        $redirect = ($_SESSION['role'] === 'admin') ? '../admin/blood_stock.php' : '../staff/view_blood_stock.php';
        header("Location: $redirect?error=invalid_expiry");
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO blood_stock (hospital_id, blood_group, component, quantity, expiry_date) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");
    $stmt->bind_param("issisi", $hospital_id, $blood_group, $component, $quantity, $expiry_date, $quantity);
    if ($stmt->execute()) {
        log_action($_SESSION['user_id'], 'add_blood_stock', "Added/Updated $quantity units of $blood_group ($component, Expiry: $expiry_date)", $conn);
        $redirect = ($_SESSION['role'] === 'admin') ? '../admin/blood_stock.php' : '../staff/view_blood_stock.php';
        header("Location: $redirect?success=Blood stock added successfully.");
    } else {
        $redirect = ($_SESSION['role'] === 'admin') ? '../admin/blood_stock.php' : '../staff/view_blood_stock.php';
        header("Location: $redirect?error=db");
    }
    $stmt->close();
} else {
    header("Location: ../index.php");
}
