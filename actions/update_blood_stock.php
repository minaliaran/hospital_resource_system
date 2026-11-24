<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
check_logged_in();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hospital_id = $_SESSION['hospital_id'];
    $blood_id = intval($_POST['blood_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $expiry_date = $_POST['expiry_date'] ?? '';
    if (!$blood_id || $quantity < 0 || empty($expiry_date)) {
        $redirect = ($_SESSION['role'] === 'admin') ? '../admin/blood_stock.php' : '../staff/view_blood_stock.php';
        header("Location: $redirect?error=invalid");
        exit;
    }
    if (strtotime($expiry_date) < strtotime(date('Y-m-d'))) {
        $redirect = ($_SESSION['role'] === 'admin') ? '../admin/blood_stock.php' : '../staff/view_blood_stock.php';
        header("Location: $redirect?error=invalid_expiry");
        exit;
    }
    $check = $conn->prepare("SELECT blood_group, component, quantity FROM blood_stock WHERE blood_id = ? AND hospital_id = ?");
    $check->bind_param("ii", $blood_id, $hospital_id);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();
    $check->close();
    if (!$existing) {
        $redirect = ($_SESSION['role'] === 'admin') ? '../admin/blood_stock.php' : '../staff/view_blood_stock.php';
        header("Location: $redirect?error=forbidden");
        exit;
    }
    
    $dup_check = $conn->prepare("SELECT blood_id, quantity FROM blood_stock WHERE hospital_id = ? AND blood_group = ? AND component = ? AND expiry_date = ? AND blood_id != ?");
    $dup_check->bind_param("isssi", $hospital_id, $existing['blood_group'], $existing['component'], $expiry_date, $blood_id);
    $dup_check->execute();
    $dup = $dup_check->get_result()->fetch_assoc();
    $dup_check->close();

    if ($dup) {
       
        $merged_qty = $quantity; // Replace, not add — set the target record to new qty
        $merge = $conn->prepare("UPDATE blood_stock SET quantity = ? WHERE blood_id = ?");
        $merge->bind_param("ii", $quantity, $dup['blood_id']);
        $merge->execute();
        $merge->close();
        // Delete the old record since expiry_date moved to the dup
        $del = $conn->prepare("DELETE FROM blood_stock WHERE blood_id = ? AND hospital_id = ?");
        $del->bind_param("ii", $blood_id, $hospital_id);
        $del->execute();
        $del->close();
        log_action($_SESSION['user_id'], 'update_blood_stock', "Merged blood ID $blood_id into existing record (same group/component/expiry). New qty: $quantity", $conn);
        $redirect = ($_SESSION['role'] === 'admin') ? '../admin/blood_stock.php' : '../staff/view_blood_stock.php';
        header("Location: $redirect?success=Blood stock updated and merged successfully.");
    } else {
        $stmt = $conn->prepare("UPDATE blood_stock SET quantity = ?, expiry_date = ? WHERE blood_id = ? AND hospital_id = ?");
        $stmt->bind_param("isii", $quantity, $expiry_date, $blood_id, $hospital_id);
        if ($stmt->execute()) {
            log_action($_SESSION['user_id'], 'update_blood_stock', "Updated blood ID $blood_id ({$existing['blood_group']} {$existing['component']}) quantity to $quantity and expiry to $expiry_date", $conn);
            if ($quantity <= 5) {
                $msg = "Critical Alert: Blood Group {$existing['blood_group']} ({$existing['component']}) stock level is critical: $quantity units remaining.";
                create_notification($hospital_id, "Critical Stock Level", $msg, null, $conn);
            }
            $redirect = ($_SESSION['role'] === 'admin') ? '../admin/blood_stock.php' : '../staff/view_blood_stock.php';
            header("Location: $redirect?success=Blood stock updated successfully.");
        } else {
            $redirect = ($_SESSION['role'] === 'admin') ? '../admin/blood_stock.php' : '../staff/view_blood_stock.php';
            header("Location: $redirect?error=db");
        }
        $stmt->close();
    }
} else {
    header("Location: ../index.php");
}
