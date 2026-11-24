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
    if (!$request_id || empty($admin_note)) {
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
    $update = $conn->prepare("UPDATE requests SET status = 'Rejected', admin_note = ?, approved_by = ?, reviewed_at = NOW() WHERE request_id = ?");
    $update->bind_param("sii", $admin_note, $admin_id, $request_id);
    $update->execute();
    $update->close();
    log_action($admin_id, 'reject_request', "Rejected staff request #$request_id", $conn);
    $notif_title = "Request Rejected";
    $notif_msg = "Your emergency request (#$request_id) has been rejected by the Hospital Admin. Reason: $admin_note";
    create_notification($hospital_id, $notif_title, $notif_msg, $req['created_by'], $conn);
    $hosp_email = $conn->query("SELECT email FROM hospitals WHERE hospital_id=$hospital_id")->fetch_row()[0];
    $subject = "Emergency Request Rejected - #$request_id";
    $body = "Dear Staff,\n\nYour emergency request (#$request_id) has been rejected.\n\nResource: {$req['resource_type']}\nReason: $admin_note\n\nSincerely,\nHRCS Team";
    send_system_email($hosp_email, $subject, $body, $conn);
    header("Location: ../admin/staff_requests.php?success=Request rejected successfully."); exit;
} else {
    header("Location: ../index.php"); exit;
}
