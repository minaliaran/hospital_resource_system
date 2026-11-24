<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';
check_logged_in();
require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_id = $_SESSION['user_id'];
    $hospital_id = $_SESSION['hospital_id'];
    $request_id = intval($_POST['request_id'] ?? 0);
    $admin_note = trim($_POST['admin_note'] ?? '');
    if (!$request_id) {
        header("Location: ../admin/staff_requests.php?error=invalid");
        exit;
    }
    $stmt = $conn->prepare("SELECT * FROM requests WHERE request_id = ? AND hospital_id = ? AND status = 'Pending'");
    $stmt->bind_param("ii", $request_id, $hospital_id);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$req) {
        header("Location: ../admin/staff_requests.php?error=invalid");
        exit;
    }
    $qty = $req['quantity'];
    $res_type = $req['resource_type'];
    if ($res_type === 'Blood') {
        $bg = $req['blood_group'];
        $check_total = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) FROM blood_stock WHERE hospital_id = ? AND blood_group = ?");
        $check_total->bind_param("is", $hospital_id, $bg);
        $check_total->execute();
        $total_available = (int)$check_total->get_result()->fetch_row()[0];
        $check_total->close();

        if ($total_available < $qty) {
            header("Location: ../admin/request_details.php?id=$request_id&error=stock");
            exit;
        }

        $stock_stmt = $conn->prepare("SELECT blood_id, quantity FROM blood_stock WHERE hospital_id = ? AND blood_group = ? AND quantity > 0 ORDER BY quantity DESC");
        $stock_stmt->bind_param("is", $hospital_id, $bg);
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
        $ct = $req['cylinder_type'];
        $check_total = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) FROM oxygen_stock WHERE hospital_id = ? AND cylinder_type = ?");
        $check_total->bind_param("is", $hospital_id, $ct);
        $check_total->execute();
        $total_available = (int)$check_total->get_result()->fetch_row()[0];
        $check_total->close();

        if ($total_available < $qty) {
            header("Location: ../admin/request_details.php?id=$request_id&error=stock");
            exit;
        }

        $deduct = $conn->prepare("UPDATE oxygen_stock SET quantity = quantity - ? WHERE hospital_id = ? AND cylinder_type = ?");
        $deduct->bind_param("iis", $qty, $hospital_id, $ct);
        $deduct->execute();
        $deduct->close();
    }
    $update = $conn->prepare("UPDATE requests SET status = 'Completed', admin_note = ?, approved_by = ?, reviewed_at = NOW() WHERE request_id = ?");
    $update->bind_param("sii", $admin_note, $admin_id, $request_id);
    $update->execute();
    $update->close();
    log_action($admin_id, 'approve_request', "Approved request #$request_id and deducted stock internally", $conn);
    $notif_title = "Request Approved & Fulfilled";
    $notif_msg = "Your emergency request (#$request_id) for $qty units of $res_type has been approved and fulfilled from local stock. Note: $admin_note";
    create_notification($hospital_id, $notif_title, $notif_msg, $req['created_by'], $conn);
    $staff_email = $conn->query("SELECT email FROM hospitals WHERE hospital_id=$hospital_id")->fetch_row()[0]; 
    $subject = "Emergency Request Approved - #$request_id";
    $body = "Dear Staff,\n\nYour emergency request (#$request_id) has been approved and completed internally.\n\nResource: $res_type\nQuantity: $qty\nStatus: Approved & Completed\nAdmin Note: $admin_note\n\nSincerely,\nHRCS Team";
    send_system_email($staff_email, $subject, $body, $conn);
    header("Location: ../admin/staff_requests.php?success=Request approved and fulfilled from internal stock.");
} else {
    header("Location: ../index.php");
}
