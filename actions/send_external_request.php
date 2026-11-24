<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';
check_logged_in();
require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_hospital_id = $_SESSION['hospital_id'];
    $receiver_hospital_id = intval($_POST['receiver_hospital_id'] ?? 0);
    $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : null;
    $resource_type = $_POST['resource_type'] ?? '';
    $blood_group = !empty($_POST['blood_group']) ? $_POST['blood_group'] : null;
    $cylinder_type = !empty($_POST['cylinder_type']) ? $_POST['cylinder_type'] : null;
    $quantity = intval($_POST['quantity'] ?? 0);
    $priority = $_POST['priority'] ?? 'Medium';
    $reason = trim($_POST['reason'] ?? '');
    if (!$receiver_hospital_id || empty($resource_type) || $quantity <= 0 || empty($reason)) {
        header("Location: ../admin/other_hospital_availability.php?error=invalid");
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO external_requests (request_id, sender_hospital_id, receiver_hospital_id, resource_type, blood_group, cylinder_type, quantity, priority, reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisssiss", $request_id, $sender_hospital_id, $receiver_hospital_id, $resource_type, $blood_group, $cylinder_type, $quantity, $priority, $reason);
    if ($stmt->execute()) {
        $ext_id = $conn->insert_id;
        if ($request_id) {
            $fwd = $conn->prepare("UPDATE requests SET status = 'Forwarded' WHERE request_id = ?");
            $fwd->bind_param("i", $request_id);
            $fwd->execute();
            $fwd->close();
            $orig_req = $conn->query("SELECT created_by FROM requests WHERE request_id=$request_id")->fetch_assoc();
            if ($orig_req) {
                $fwd_msg = "Your emergency request (#$request_id) has been forwarded to another hospital for external stock coordination.";
                create_notification($sender_hospital_id, "Request Forwarded", $fwd_msg, $orig_req['created_by'], $conn);
            }
        }
        log_action($_SESSION['user_id'], 'send_external_request', "Sent external request #$ext_id to hospital $receiver_hospital_id", $conn);
        $admin_role = 'admin';
        $recv_stmt = $conn->prepare("SELECT user_id FROM users WHERE hospital_id = ? AND role = ? AND status = 'active'");
        $recv_stmt->bind_param("is", $receiver_hospital_id, $admin_role);
        $recv_stmt->execute();
        $recv_admins = $recv_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $recv_stmt->close();
        $sender_name = get_hospital_name($sender_hospital_id, $conn);
        $detail_str = ($resource_type === 'Blood') ? $blood_group : ($cylinder_type . " Cylinder");
        $notif_title = "Incoming External Request";
        $notif_msg = "Emergency Request received from $sender_name for $quantity units of $resource_type ($detail_str). Priority: $priority.";
        foreach ($recv_admins as $adm) {
            create_notification($receiver_hospital_id, $notif_title, $notif_msg, $adm['user_id'], $conn);
        }
        $recv_email = $conn->query("SELECT email FROM hospitals WHERE hospital_id=$receiver_hospital_id")->fetch_row()[0];
        $subject = "Emergency Inter-Hospital Request - From $sender_name";
        $email_body = "Hello Team,\n\nYour hospital has received an emergency inter-hospital request.\n\nFrom: $sender_name\nResource: $resource_type ($detail_str)\nQuantity: $quantity\nPriority: $priority\nReason: $reason\n\nPlease log in to review and respond.\n\nSincerely,\nHRCS Team";
        send_system_email($recv_email, $subject, $email_body, $conn);
       header("Location: ../admin/outgoing_requests.php?success=External request sent successfully.");
exit;
    } else {
        header("Location: ../admin/other_hospital_availability.php?error=db"); exit;
    }
    $stmt->close();
} else {
    header("Location: ../index.php"); exit;
    
}
