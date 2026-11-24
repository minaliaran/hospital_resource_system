<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';
check_logged_in();
require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiver_hospital_id = $_SESSION['hospital_id'];
    $user_id = $_SESSION['user_id'];
    $external_request_id = intval($_POST['external_request_id'] ?? 0);
    $action = $_POST['action'] ?? ''; 
    $response_note = trim($_POST['response_note'] ?? '');
    $driver_name = trim($_POST['driver_name'] ?? '');
    $vehicle_number = trim($_POST['vehicle_number'] ?? '');
    if (!$external_request_id || !in_array($action, ['Accept', 'Reject'])) {
        header("Location: ../admin/incoming_requests.php?error=invalid");
        exit;
    }
    $stmt = $conn->prepare("SELECT * FROM external_requests WHERE external_request_id = ? AND receiver_hospital_id = ? AND status = 'Pending'");
    $stmt->bind_param("ii", $external_request_id, $receiver_hospital_id);
    $stmt->execute();
    $ext = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$ext) {
        header("Location: ../admin/incoming_requests.php?error=invalid");
        exit;
    }
    $qty = $ext['quantity'];
    $res_type = $ext['resource_type'];
    $request_id = $ext['request_id'];
    $sender_hospital_id = $ext['sender_hospital_id'];
    if ($action === 'Accept') {
        if (empty($driver_name) || empty($vehicle_number)) {
            header("Location: ../admin/incoming_requests.php?error=invalid");
            exit;
        }
        if ($res_type === 'Blood') {
            $bg = $ext['blood_group'];
            $check_total = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) FROM blood_stock WHERE hospital_id = ? AND blood_group = ?");
            $check_total->bind_param("is", $receiver_hospital_id, $bg);
            $check_total->execute();
            $total_available = (int)$check_total->get_result()->fetch_row()[0];
            $check_total->close();

            if ($total_available < $qty) {
                header("Location: ../admin/incoming_requests.php?error=stock");
                exit;
            }

            $stock_stmt = $conn->prepare("SELECT blood_id, quantity FROM blood_stock WHERE hospital_id = ? AND blood_group = ? AND quantity > 0 ORDER BY quantity DESC");
            $stock_stmt->bind_param("is", $receiver_hospital_id, $bg);
            $stock_stmt->execute();
            $batches = $stock_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stock_stmt->close();

            $remaining_to_deduct = $qty;
            foreach ($batches as $batch) {
                if ($remaining_to_deduct <= 0) break;
                $deduct_amount = min($remaining_to_deduct, (int)$batch['quantity']);
                $deduct = $conn->prepare("UPDATE blood_stock SET quantity = quantity - ? WHERE blood_id = ?");
                $deduct->bind_param("ii", $deduct_amount, $batch['blood_id']);
                $deduct->execute();
                $deduct->close();
                $remaining_to_deduct -= $deduct_amount;
            }
        } else {
            $ct = $ext['cylinder_type'];
            $check_total = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) FROM oxygen_stock WHERE hospital_id = ? AND cylinder_type = ?");
            $check_total->bind_param("is", $receiver_hospital_id, $ct);
            $check_total->execute();
            $total_available = (int)$check_total->get_result()->fetch_row()[0];
            $check_total->close();

            if ($total_available < $qty) {
                header("Location: ../admin/incoming_requests.php?error=stock");
                exit;
            }

            $deduct = $conn->prepare("UPDATE oxygen_stock SET quantity = quantity - ? WHERE hospital_id = ? AND cylinder_type = ?");
            $deduct->bind_param("iis", $qty, $receiver_hospital_id, $ct);
            $deduct->execute();
            $deduct->close();
        }
        $update = $conn->prepare("UPDATE external_requests SET status = 'Accepted', response_note = ?, responded_at = NOW() WHERE external_request_id = ?");
        $update->bind_param("si", $response_note, $external_request_id);
        $update->execute();
        $update->close();
        if ($request_id) {
            $up_orig = $conn->prepare("UPDATE requests SET status = 'Accepted', admin_note = ? WHERE request_id = ?");
            $orig_note = "Accepted by partner hospital. Delivery in progress. " . $response_note;
            $up_orig->bind_param("si", $orig_note, $request_id);
            $up_orig->execute();
            $up_orig->close();
        }
        $trans = $conn->prepare("INSERT INTO transfers (external_request_id, from_hospital_id, to_hospital_id, resource_type, blood_group, cylinder_type, quantity, driver_name, vehicle_number, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
        $trans->bind_param("iiisssiss", $external_request_id, $receiver_hospital_id, $sender_hospital_id, $res_type, $ext['blood_group'], $ext['cylinder_type'], $qty, $driver_name, $vehicle_number);
        $trans->execute();
        $trans->close();
        log_action($user_id, 'accept_external_request', "Accepted external request #$external_request_id and created transfer dispatch", $conn);
        $admin_role = 'admin';
        $send_stmt = $conn->prepare("SELECT user_id FROM users WHERE hospital_id = ? AND role = ? AND status = 'active'");
        $send_stmt->bind_param("is", $sender_hospital_id, $admin_role);
        $send_stmt->execute();
        $send_admins = $send_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $send_stmt->close();
        $receiver_name = get_hospital_name($receiver_hospital_id, $conn);
        $notif_title = "External Request Accepted";
        $notif_msg = "Request #$external_request_id was accepted by $receiver_name. Transfer T-{$conn->insert_id} is pending dispatch. Driver: $driver_name.";
        foreach ($send_admins as $adm) {
            create_notification($sender_hospital_id, $notif_title, $notif_msg, $adm['user_id'], $conn);
        }
        $sender_email = $conn->query("SELECT email FROM hospitals WHERE hospital_id=$sender_hospital_id")->fetch_row()[0];
        $req_id_str = 'REQ-' . str_pad($external_request_id, 3, '0', STR_PAD_LEFT);
        $subject = "Inter-Hospital Request Approved - " . $req_id_str;
        $requesting_hospital_name = get_hospital_name($sender_hospital_id, $conn);
        $receiving_hospital_name = get_hospital_name($receiver_hospital_id, $conn);
        $bg_line = ($res_type === 'Blood') ? "* Blood Group: " . $ext['blood_group'] . "\n" : "";
        $qty_str = $qty . ($res_type === 'Blood' ? ' units' : ' cylinders');
        $body = "Dear Hospital Admin,\n\n" .
                "This is to inform you that your inter-hospital emergency resource request has been reviewed and approved by the receiving hospital through the Hospital Resource Coordination System (HRCS).\n\n" .
                "Request Details:\n\n" .
                "* Request ID: $req_id_str\n" .
                "* Resource Type: $res_type\n" .
                $bg_line .
                "* Quantity: $qty_str\n" .
                "* Requesting Hospital: $requesting_hospital_name\n" .
                "* Receiving Hospital: $receiving_hospital_name\n" .
                "* Current Status: Accepted\n\n" .
                "Please proceed with the necessary transfer coordination through the HRCS.\n\n" .
                "Regards,\n" .
                "Hospital Admin\n" .
                "$receiving_hospital_name\n" .
                "Hospital Resource Coordination System (HRCS)";
        send_system_email($sender_email, $subject, $body, $conn);
        header("Location: ../admin/incoming_requests.php?success=Request accepted. Transfer process started.");
    } else {
        $update = $conn->prepare("UPDATE external_requests SET status = 'Rejected', response_note = ?, responded_at = NOW() WHERE external_request_id = ?");
        $update->bind_param("si", $response_note, $external_request_id);
        $update->execute();
        $update->close();
        if ($request_id) {
            $up_orig = $conn->prepare("UPDATE requests SET status = 'Rejected', admin_note = ? WHERE request_id = ?");
            $orig_note = "Rejected by partner hospital. Reason: " . $response_note;
            $up_orig->bind_param("si", $orig_note, $request_id);
            $up_orig->execute();
            $up_orig->close();
        }
        log_action($user_id, 'reject_external_request', "Rejected external request #$external_request_id. Note: $response_note", $conn);
        $admin_role = 'admin';
        $send_stmt = $conn->prepare("SELECT user_id FROM users WHERE hospital_id = ? AND role = ? AND status = 'active'");
        $send_stmt->bind_param("is", $sender_hospital_id, $admin_role);
        $send_stmt->execute();
        $send_admins = $send_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $send_stmt->close();
        $receiver_name = get_hospital_name($receiver_hospital_id, $conn);
        $notif_title = "External Request Rejected";
        $notif_msg = "Request #$external_request_id was rejected by $receiver_name. Reason: $response_note";
        foreach ($send_admins as $adm) {
            create_notification($sender_hospital_id, $notif_title, $notif_msg, $adm['user_id'], $conn);
        }
        $sender_email = $conn->query("SELECT email FROM hospitals WHERE hospital_id=$sender_hospital_id")->fetch_row()[0];
        $req_id_str = 'REQ-' . str_pad($external_request_id, 3, '0', STR_PAD_LEFT);
        $subject = "Inter-Hospital Request Rejected - " . $req_id_str;
        $requesting_hospital_name = get_hospital_name($sender_hospital_id, $conn);
        $receiving_hospital_name = get_hospital_name($receiver_hospital_id, $conn);
        $bg_line = ($res_type === 'Blood') ? "* Blood Group: " . $ext['blood_group'] . "\n" : "";
        $qty_str = $qty . ($res_type === 'Blood' ? ' units' : ' cylinders');
        $body = "Dear Hospital Admin,\n\n" .
                "This is to inform you that your inter-hospital emergency resource request has been reviewed by the receiving hospital and could not be approved at this time.\n\n" .
                "Request Details:\n\n" .
                "* Request ID: $req_id_str\n" .
                "* Resource Type: $res_type\n" .
                $bg_line .
                "* Quantity: $qty_str\n" .
                "* Requesting Hospital: $requesting_hospital_name\n" .
                "* Receiving Hospital: $receiving_hospital_name\n" .
                "* Current Status: Rejected\n\n" .
                "Reason for Rejection:\n" .
                "$response_note\n\n" .
                "Please review the request in the HRCS for further details.\n\n" .
                "Regards,\n" .
                "Hospital Admin\n" .
                "$receiving_hospital_name\n" .
                "Hospital Resource Coordination System (HRCS)";
        send_system_email($sender_email, $subject, $body, $conn);
        header("Location: ../admin/incoming_requests.php?success=Request rejected successfully."); exit;
    }
} else {
    header("Location: ../index.php"); exit;
}
