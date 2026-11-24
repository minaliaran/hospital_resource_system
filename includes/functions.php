<?php
function log_action($user_id, $action, $description, $conn) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $action, $description, $ip_address);
    $stmt->execute();
    $stmt->close();
}
function create_notification($hospital_id, $title, $message, $user_id, $conn) {
    $stmt = $conn->prepare("INSERT INTO notifications (hospital_id, user_id, title, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $hospital_id, $user_id, $title, $message);
    $stmt->execute();
    $stmt->close();
}
function send_email($recipient, $subject, $message, $sent_by, $conn) {
    $status = 'sent'; 
    $stmt = $conn->prepare("INSERT INTO email_logs (recipient, subject, message, status, sent_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $recipient, $subject, $message, $status, $sent_by);
    $stmt->execute();
    $stmt->close();
    return true;
}
function get_hospital_name($hospital_id, $conn) {
    $stmt = $conn->prepare("SELECT hospital_name FROM hospitals WHERE hospital_id = ?");
    $stmt->bind_param("i", $hospital_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $name = "Unknown Hospital";
    if ($row = $result->fetch_assoc()) {
        $name = $row['hospital_name'];
    }
    $stmt->close();
    return $name;
}
function get_blood_status_label($quantity) {
    if ($quantity <= 0) return 'Out of Stock';
    if ($quantity <= 5) return 'Critical';
    if ($quantity <= 15) return 'Low';
    return 'Available';
}
function get_oxygen_status_label($quantity) {
    if ($quantity <= 0) return 'Out of Stock';
    if ($quantity <= 2) return 'Critical';
    if ($quantity <= 5) return 'Low';
    return 'Available';
}
function get_status_class($status) {
    $status = strtolower($status);
    switch ($status) {
        case 'available':
        case 'completed':
        case 'approved':
        case 'accepted':
        case 'active':
        case 'sent':
            return 'badge-success';
        case 'low':
        case 'pending':
        case 'in transit':
            return 'badge-warning';
        case 'critical':
        case 'out of stock':
        case 'rejected':
        case 'failed':
        case 'cancelled':
        case 'inactive':
            return 'badge-danger';
        default:
            return 'badge-secondary';
    }
}
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
