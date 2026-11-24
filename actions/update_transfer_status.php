<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';
check_logged_in();
require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hospital_id = $_SESSION['hospital_id'];
    $user_id = $_SESSION['user_id'];
    $transfer_id = intval($_POST['transfer_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? ''; 
    if (!$transfer_id || !in_array($new_status, ['In Transit', 'Completed'])) {
        header("Location: ../admin/transfers.php?error=invalid");
        exit;
    }
    $stmt = $conn->prepare("SELECT t.*, er.request_id, er.external_request_id FROM transfers t JOIN external_requests er ON t.external_request_id = er.external_request_id WHERE t.transfer_id = ?");
    $stmt->bind_param("i", $transfer_id);
    $stmt->execute();
    $t = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$t) {
        header("Location: ../admin/transfers.php?error=invalid");
        exit;
    }
    if ($new_status === 'In Transit') {
        if ($t['from_hospital_id'] != $hospital_id) {
            header("Location: ../admin/transfers.php?error=forbidden");
            exit;
        }
        $up = $conn->prepare("UPDATE transfers SET status = 'In Transit' WHERE transfer_id = ?");
        $up->bind_param("i", $transfer_id);
        $up->execute();
        $up->close();
        log_action($user_id, 'dispatch_transfer', "Dispatched transfer T-$transfer_id in transit", $conn);
        $admin_role = 'admin';
        $recv_stmt = $conn->prepare("SELECT user_id FROM users WHERE hospital_id = ? AND role = ? AND status = 'active'");
        $recv_stmt->bind_param("is", $t['to_hospital_id'], $admin_role);
        $recv_stmt->execute();
        $recv_admins = $recv_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $recv_stmt->close();
        $sender_name = get_hospital_name($t['from_hospital_id'], $conn);
        $notif_title = "Resource In Transit";
        $notif_msg = "Emergency Transfer T-$transfer_id from $sender_name has been dispatched. Vehicle: {$t['vehicle_number']}. Driver: {$t['driver_name']}.";
        foreach ($recv_admins as $adm) {
            create_notification($t['to_hospital_id'], $notif_title, $notif_msg, $adm['user_id'], $conn);
        }
        header("Location: ../admin/transfers.php?success=Transfer marked as In Transit.");
    } elseif ($new_status === 'Completed') {
        if ($t['to_hospital_id'] != $hospital_id) {
            header("Location: ../admin/transfers.php?error=forbidden");
            exit;
        }
        $up = $conn->prepare("UPDATE transfers SET status = 'Completed', received_date = NOW(), received_by = ? WHERE transfer_id = ?");
        $up->bind_param("ii", $user_id, $transfer_id);
        $up->execute();
        $up->close();
        $up_ext = $conn->prepare("UPDATE external_requests SET status = 'Accepted' WHERE external_request_id = ?");
        $up_ext->bind_param("i", $t['external_request_id']);
        $up_ext->execute();
        $up_ext->close();
        if ($t['request_id']) {
            $up_req = $conn->prepare("UPDATE requests SET status = 'Completed', admin_note = 'Transfer T-$transfer_id completed.' WHERE request_id = ?");
            $up_req->bind_param("i", $t['request_id']);
            $up_req->execute();
            $up_req->close();
            $orig_req = $conn->query("SELECT created_by FROM requests WHERE request_id={$t['request_id']}")->fetch_assoc();
            if ($orig_req) {
                $staff_msg = "Your emergency request (#{$t['request_id']}) has been fulfilled. Transfer T-$transfer_id has arrived and stock is updated.";
                create_notification($hospital_id, "Request Fulfilled", $staff_msg, $orig_req['created_by'], $conn);
            }
        }
        $qty = $t['quantity'];
        if ($t['resource_type'] === 'Blood') {
            $bg = $t['blood_group'];
            $comp_query = $conn->query("SELECT component FROM blood_stock WHERE hospital_id = $hospital_id AND blood_group = '" . $conn->real_escape_string($bg) . "' LIMIT 1")->fetch_assoc();
            $comp = $comp_query['component'] ?? 'Whole Blood';
            $exp = date('Y-m-d', strtotime('+30 days')); 
            $add_stock = $conn->prepare("INSERT INTO blood_stock (hospital_id, blood_group, component, quantity, expiry_date) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");
            $add_stock->bind_param("issisi", $hospital_id, $bg, $comp, $qty, $exp, $qty);
            $add_stock->execute();
            $add_stock->close();
        } else {
            $ct = $t['cylinder_type'];
            $add_stock = $conn->prepare("INSERT INTO oxygen_stock (hospital_id, cylinder_type, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");
            $add_stock->bind_param("isii", $hospital_id, $ct, $qty, $qty);
            $add_stock->execute();
            $add_stock->close();
        }
        log_action($user_id, 'complete_transfer', "Received transfer T-$transfer_id and updated local stock", $conn);
        $admin_role = 'admin';
        $send_stmt = $conn->prepare("SELECT user_id FROM users WHERE hospital_id = ? AND role = ? AND status = 'active'");
        $send_stmt->bind_param("is", $t['from_hospital_id'], $admin_role);
        $send_stmt->execute();
        $send_admins = $send_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $send_stmt->close();
        $receiver_name = get_hospital_name($hospital_id, $conn);
        $notif_title = "Transfer Completed";
        $notif_msg = "Transfer T-$transfer_id has been successfully received by $receiver_name. Flow closed.";
        foreach ($send_admins as $adm) {
            create_notification($t['from_hospital_id'], $notif_title, $notif_msg, $adm['user_id'], $conn);
        }
        header("Location: ../admin/transfers.php?success=Transfer received successfully and inventory updated.");
    }
} else {
    header("Location: ../index.php");
}
