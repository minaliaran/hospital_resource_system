<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';
check_logged_in();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $created_by = $_SESSION['user_id'];
    $hospital_id = $_SESSION['hospital_id'];
    $resource_type = $_POST['resource_type'] ?? '';
    $blood_group = $_POST['blood_group'] ?? null;
    $cylinder_type = $_POST['cylinder_type'] ?? null;
    $quantity = intval($_POST['quantity'] ?? 0);
    $priority = $_POST['priority'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    $return_page = ($resource_type === 'Oxygen') ? '../staff/create_oxygen_request.php' : '../staff/create_blood_request.php';

    if (empty($resource_type) || $quantity <= 0 || empty($priority) || empty($reason)) {
        header("Location: $return_page?error=invalid");
        exit;
    }
    if ($resource_type === 'Blood' && empty($blood_group)) {
        header("Location: ../staff/create_blood_request.php?error=invalid");
        exit;
    }
    if ($resource_type === 'Oxygen' && empty($cylinder_type)) {
        header("Location: ../staff/create_oxygen_request.php?error=invalid");
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO requests (created_by, hospital_id, resource_type, blood_group, cylinder_type, quantity, priority, reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssiss", $created_by, $hospital_id, $resource_type, $blood_group, $cylinder_type, $quantity, $priority, $reason);
    if ($stmt->execute()) {
        $request_id = $conn->insert_id;
        log_action($created_by, 'submit_request', "Submitted request #$request_id for $quantity $resource_type items", $conn);
        $admin_role = 'admin';
        $admin_stmt = $conn->prepare("SELECT user_id, full_name, username FROM users WHERE hospital_id = ? AND role = ? AND status = 'active'");
        $admin_stmt->bind_param("is", $hospital_id, $admin_role);
        $admin_stmt->execute();
        $admins = $admin_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $admin_stmt->close();
        $detail_str = ($resource_type === 'Blood') ? $blood_group : ($cylinder_type . " Cylinder");
        $notif_title = "New Emergency Request";
        $notif_msg = "Staff member {$_SESSION['full_name']} submitted a request (#$request_id) for $quantity units of $resource_type ($detail_str). Priority: $priority.";
        foreach ($admins as $adm) {
            create_notification($hospital_id, $notif_title, $notif_msg, $adm['user_id'], $conn);
        }
        $hospital_name = get_hospital_name($hospital_id, $conn);
        $req_id_str = 'REQ-' . str_pad($request_id, 3, '0', STR_PAD_LEFT);
        $subject = "Emergency Request Alert - " . $req_id_str;
        $date_time_str = date('d F Y, g:i A');
        $email_body = "Dear Hospital Admin,\n\n" .
                      "A new emergency resource request has been submitted through the Hospital Resource Coordination System (HRCS) and is awaiting your review.\n\n" .
                      "Request Details:\n" .
                      "- Request ID: $req_id_str\n" .
                      "- Resource Type: $resource_type\n";
        if ($resource_type === 'Blood') {
            $email_body .= "- Blood Group: $blood_group\n" .
                           "- Quantity: $quantity units\n";
        } else {
            $email_body .= "- Cylinder Type: $cylinder_type\n" .
                           "- Quantity: $quantity cylinders\n";
        }
        $email_body .= "- Priority: $priority\n" .
                       "- Requested By: " . $_SESSION['full_name'] . "\n" .
                       "- Hospital: $hospital_name\n" .
                       "- Date & Time: $date_time_str\n" .
                       "- Status: Pending Review\n\n\n\n" .
                       "Regards,\n" .
                       "Hospital Admin\n" .
                       "$hospital_name\n" .
                       "Hospital Resource Coordination System (HRCS)";
        $hosp_email = $conn->query("SELECT email FROM hospitals WHERE hospital_id=$hospital_id")->fetch_row()[0];
        send_system_email($hosp_email, $subject, $email_body, $conn);
       header("Location: ../staff/my_requests.php?success=Emergency request submitted successfully.");
exit;
    } else {
        header("Location: $return_page?error=db");
    }
    $stmt->close();
} else {
    header("Location: ../index.php");
}
