<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    
    $request_id    = intval($_GET['request_id'] ?? 0);
    $hospital_id   = intval($_GET['hospital_id'] ?? 0);
    $status        = trim($_GET['status'] ?? '');
    $priority      = trim($_GET['priority'] ?? '');
    $resource_type = trim($_GET['resource_type'] ?? '');

    $sql = "SELECT r.request_id,
                   r.hospital_id,
                   h.hospital_name,
                   r.created_by,
                   u.full_name AS requester_name,
                   u.role AS requester_role,
                   r.resource_type,
                   r.blood_group,
                   r.cylinder_type,
                   r.quantity,
                   r.priority,
                   r.reason,
                   r.status,
                   r.admin_note,
                   r.approved_by,
                   appr.full_name AS approved_by_name,
                   r.created_at,
                   r.reviewed_at
            FROM requests r
            JOIN hospitals h ON r.hospital_id = h.hospital_id
            JOIN users u ON r.created_by = u.user_id
            LEFT JOIN users appr ON r.approved_by = appr.user_id
            WHERE 1=1";

    $params = [];
    $types  = "";

    if ($request_id > 0) {
        $sql .= " AND r.request_id = ?";
        $params[] = $request_id;
        $types   .= "i";
    }
    if ($hospital_id > 0) {
        $sql .= " AND r.hospital_id = ?";
        $params[] = $hospital_id;
        $types   .= "i";
    }
    if (!empty($status)) {
        $sql .= " AND r.status = ?";
        $params[] = $status;
        $types   .= "s";
    }
    if (!empty($priority)) {
        $sql .= " AND r.priority = ?";
        $params[] = $priority;
        $types   .= "s";
    }
    if (!empty($resource_type)) {
        $sql .= " AND r.resource_type = ?";
        $params[] = $resource_type;
        $types   .= "s";
    }

    $sql .= " ORDER BY r.created_at DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

   
    $formatted_data = array_map(function($item) {
        $item['request_code'] = 'REQ-' . str_pad($item['request_id'], 3, '0', STR_PAD_LEFT);
        $item['resource_detail'] = ($item['resource_type'] === 'Blood') 
            ? $item['blood_group'] 
            : ($item['cylinder_type'] . ' Cylinder');
        return $item;
    }, $data);

    echo json_encode([
        'status'      => 'success',
        'total_count' => count($formatted_data),
        'timestamp'   => date('Y-m-d H:i:s'),
        'data'        => $formatted_data
    ], JSON_PRETTY_PRINT);
    exit;

} elseif ($method === 'POST') {
   
    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input, true);
    if (!$input) {
        $input = $_POST;
    }

    $created_by    = intval($input['created_by'] ?? 0);
    $hospital_id   = intval($input['hospital_id'] ?? 0);
    $resource_type = trim($input['resource_type'] ?? '');
    $blood_group   = !empty($input['blood_group']) ? trim($input['blood_group']) : null;
    $cylinder_type = !empty($input['cylinder_type']) ? trim($input['cylinder_type']) : null;
    $quantity      = intval($input['quantity'] ?? 0);
    $priority      = trim($input['priority'] ?? 'Emergency');
    $reason        = trim($input['reason'] ?? '');

    // Validation
    $errors = [];
    if ($created_by <= 0) {
        $errors[] = "created_by (User ID) is required.";
    }
    if ($hospital_id <= 0) {
        $errors[] = "hospital_id is required.";
    }
    if (!in_array($resource_type, ['Blood', 'Oxygen'])) {
        $errors[] = "resource_type must be either 'Blood' or 'Oxygen'.";
    }
    if ($resource_type === 'Blood' && empty($blood_group)) {
        $errors[] = "blood_group is required for Blood requests (e.g. A+, O-, etc.).";
    }
    if ($resource_type === 'Oxygen' && empty($cylinder_type)) {
        $errors[] = "cylinder_type is required for Oxygen requests (e.g. Small, Medium, Large, Portable, E-Type, D-Type, J-Type).";
    }
    if ($quantity <= 0) {
        $errors[] = "quantity must be greater than 0.";
    }
    if (!in_array($priority, ['Low', 'Medium', 'High', 'Emergency'])) {
        $priority = 'Emergency';
    }
    if (empty($reason)) {
        $errors[] = "reason (clinical justification) is required.";
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Validation failed.',
            'errors'  => $errors
        ], JSON_PRETTY_PRINT);
        exit;
    }

  
    $user_check = $conn->prepare("SELECT full_name, role FROM users WHERE user_id = ? AND hospital_id = ? AND status = 'active' LIMIT 1");
    $user_check->bind_param("ii", $created_by, $hospital_id);
    $user_check->execute();
    $user_res = $user_check->get_result()->fetch_assoc();
    $user_check->close();

    if (!$user_res) {
        http_response_code(404);
        echo json_encode([
            'status'  => 'error',
            'message' => 'User not found or inactive for the specified hospital.'
        ], JSON_PRETTY_PRINT);
        exit;
    }

    $requester_name = $user_res['full_name'];

  
    $stmt = $conn->prepare("INSERT INTO requests (created_by, hospital_id, resource_type, blood_group, cylinder_type, quantity, priority, reason, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("iisssiss", $created_by, $hospital_id, $resource_type, $blood_group, $cylinder_type, $quantity, $priority, $reason);

    if ($stmt->execute()) {
        $request_id = $conn->insert_id;
        $req_id_str = 'REQ-' . str_pad($request_id, 3, '0', STR_PAD_LEFT);
        $stmt->close();

      
        log_action($created_by, 'api_submit_request', "Submitted API emergency request #$request_id for $quantity $resource_type items", $conn);

       
        $admin_role = 'admin';
        $admin_stmt = $conn->prepare("SELECT user_id FROM users WHERE hospital_id = ? AND role = ? AND status = 'active'");
        $admin_stmt->bind_param("is", $hospital_id, $admin_role);
        $admin_stmt->execute();
        $admins = $admin_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $admin_stmt->close();

        $detail_str = ($resource_type === 'Blood') ? $blood_group : ($cylinder_type . " Cylinder");
        $notif_title = "New Emergency Request (API)";
        $notif_msg = "Staff member $requester_name submitted request (#$request_id) for $quantity units of $resource_type ($detail_str). Priority: $priority.";
        foreach ($admins as $adm) {
            create_notification($hospital_id, $notif_title, $notif_msg, $adm['user_id'], $conn);
        }

        
        $hospital_name = get_hospital_name($hospital_id, $conn);
        $subject = "Emergency Request Alert - " . $req_id_str;
        $date_time_str = date('d F Y, g:i A');
        $email_body = "Dear Hospital Admin,\n\n" .
                      "A new emergency resource request has been submitted through the API and is awaiting your review.\n\n" .
                      "Request Details:\n" .
                      "- Request ID: $req_id_str\n" .
                      "- Resource Type: $resource_type\n" .
                      "- Detail: $detail_str\n" .
                      "- Quantity: $quantity\n" .
                      "- Priority: $priority\n" .
                      "- Requested By: $requester_name\n" .
                      "- Hospital: $hospital_name\n" .
                      "- Reason: $reason\n" .
                      "- Date & Time: $date_time_str\n" .
                      "- Status: Pending Review\n\n" .
                      "Regards,\n" .
                      "Hospital Resource Coordination System (HRCS)";

        $hosp_email_res = $conn->query("SELECT email FROM hospitals WHERE hospital_id=$hospital_id");
        if ($hosp_email_res && $hosp_email_row = $hosp_email_res->fetch_row()) {
            send_system_email($hosp_email_row[0], $subject, $email_body, $conn);
        }

        http_response_code(201);
        echo json_encode([
            'status'       => 'success',
            'message'      => 'Emergency request created successfully.',
            'request_id'   => $request_id,
            'request_code' => $req_id_str,
            'created_at'   => date('Y-m-d H:i:s'),
            'data'         => [
                'hospital_id'   => $hospital_id,
                'hospital_name' => $hospital_name,
                'created_by'    => $created_by,
                'requester'     => $requester_name,
                'resource_type' => $resource_type,
                'detail'        => $detail_str,
                'quantity'      => $quantity,
                'priority'      => $priority,
                'reason'        => $reason,
                'status'        => 'Pending'
            ]
        ], JSON_PRETTY_PRINT);
    } else {
        http_response_code(500);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Database error while creating emergency request.'
        ], JSON_PRETTY_PRINT);
    }
    exit;

} else {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Method Not Allowed. Supported methods: GET, POST.'
    ], JSON_PRETTY_PRINT);
    exit;
}
