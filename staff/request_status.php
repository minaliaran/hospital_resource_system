<?php
$page_title = "Request Status & Tracking";
require_once __DIR__ . '/../includes/header.php';
require_role(['blood_staff', 'oxygen_staff']);
$uid = $_SESSION['user_id'];
$h = $_SESSION['hospital_id'];
$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header("Location: my_requests.php");
    exit;
}
$stmt = $conn->prepare("SELECT r.*, u.full_name FROM requests r JOIN users u ON r.created_by=u.user_id WHERE r.request_id=? AND r.created_by=?");
$stmt->bind_param("ii", $id, $uid);
$stmt->execute();
$req = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$req) {
    header("Location: my_requests.php?error=invalid");
    exit;
}
$ext_req = null;
if ($req['status'] === 'Forwarded' || $req['status'] === 'Accepted' || $req['status'] === 'Completed') {
    $stmt = $conn->prepare("SELECT er.*, h.hospital_name as receiver_name FROM external_requests er JOIN hospitals h ON er.receiver_hospital_id=h.hospital_id WHERE er.request_id=? ORDER BY er.created_at DESC LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $ext_req = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
$transfer = null;
if ($ext_req && ($ext_req['status'] === 'Accepted' || $req['status'] === 'Completed')) {
    $stmt = $conn->prepare("SELECT t.*, hf.hospital_name as from_name, ht.hospital_name as to_name FROM transfers t JOIN hospitals hf ON t.from_hospital_id=hf.hospital_id JOIN hospitals ht ON t.to_hospital_id=ht.hospital_id WHERE t.external_request_id=? ORDER BY t.transfer_id DESC LIMIT 1");
    $stmt->bind_param("i", $ext_req['external_request_id']);
    $stmt->execute();
    $transfer = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<div class="mb-3">
    <a href="my_requests.php" class="text-link text-md"><i class="fa fa-arrow-left"></i> Back to My Requests</a>
</div>
<div class="page-header">
    <div>
        <div class="page-header-title">Track Request #<?php echo $req['request_id']; ?></div>
        <div class="page-header-subtitle">Real-time status of your emergency resource request</div>
    </div>
    <span class="badge <?php echo get_status_class($req['status']); ?> badge-lg"><?php echo $req['status']; ?></span>
</div>
<div class="panel mb-4">
    <div class="panel-body">
        <div class="progress-steps">
            <?php
            $steps = [
                ['Submitted', true, false],
                ['Admin Review', ($req['status'] !== 'Pending'), ($req['status'] === 'Pending')],
                ['Approved / Forwarded', ($req['status'] === 'Approved' || $req['status'] === 'Forwarded' || $req['status'] === 'Accepted' || $req['status'] === 'Completed'), ($req['status'] === 'Approved' || $req['status'] === 'Forwarded')],
                ['Accepted by Partner', ($req['status'] === 'Accepted' || $req['status'] === 'Completed' || ($req['status'] === 'Approved' && !$ext_req)), ($req['status'] === 'Forwarded' && $ext_req && $ext_req['status'] === 'Pending')],
                ['Delivery & Completed', ($req['status'] === 'Completed'), ($req['status'] === 'Accepted' || ($transfer && $transfer['status'] === 'In Transit'))]
            ];
            $count_steps = count($steps);
            foreach ($steps as $idx => $step) {
                $name = $step[0];
                $is_done = $step[1];
                $is_active = $step[2];
                $dot_class = 'pending';
                if ($is_done) $dot_class = 'done';
                elseif ($is_active) $dot_class = 'active';
                $icon = ($is_done) ? '<i class="fa fa-check"></i>' : ($idx + 1);
                $label_class = $is_active ? 'text-info fw-600' : ($is_done ? 'text-success fw-600' : 'text-muted fw-400');
                ?>
                <div class="step">
                    <div class="flex-col-center">
                        <div class="step-dot <?php echo $dot_class; ?>"><?php echo $icon; ?></div>
                        <div class="step-label <?php echo $label_class; ?>"><?php echo $name; ?></div>
                    </div>
                    <?php if ($idx < $count_steps - 1): ?>
                        <div class="step-line <?php echo ($steps[$idx+1][1]) ? 'done' : ''; ?>"></div>
                    <?php endif; ?>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</div>
<div class="page-row">
    <div class="panel">
        <div class="panel-header"><span class="panel-title"><i class="fa fa-info-circle text-primary"></i> Request Details</span></div>
        <div class="panel-body">
            <div class="grid-2-col">
                <div>
                    <div class="text-xs fw-600 text-muted text-uppercase mb-1">Resource Type</div>
                    <div class="text-lg fw-600"><?php echo sanitize($req['resource_type']); ?></div>
                </div>
                <?php if ($req['blood_group']): ?>
                <div>
                    <div class="text-xs fw-600 text-muted text-uppercase mb-1">Blood Group</div>
                    <div class="text-lg fw-700 text-danger"><?php echo sanitize($req['blood_group']); ?></div>
                </div>
                <?php endif; ?>
                <?php if ($req['cylinder_type']): ?>
                <div>
                    <div class="text-xs fw-600 text-muted text-uppercase mb-1">Cylinder Type</div>
                    <div class="text-lg fw-600"><?php echo sanitize($req['cylinder_type']); ?> Cylinder</div>
                </div>
                <?php endif; ?>
                <div>
                    <div class="text-xs fw-600 text-muted text-uppercase mb-1">Quantity Requested</div>
                    <div class="text-xl fw-700"><?php echo sanitize($req['quantity']); ?></div>
                </div>
                <div>
                    <div class="text-xs fw-600 text-muted text-uppercase mb-1">Priority</div>
                    <div class="text-lg"><span class="badge <?php echo match($req['priority']){ 'Emergency'=>'badge-danger','High'=>'badge-warning','Medium'=>'badge-info',default=>'badge-secondary'}; ?>"><?php echo $req['priority']; ?></span></div>
                </div>
                <div>
                    <div class="text-xs fw-600 text-muted text-uppercase mb-1">Date Submitted</div>
                    <div class="text-md text-muted"><?php echo date('M j, Y g:i a', strtotime($req['created_at'])); ?></div>
                </div>
            </div>
            <div class="mt-4 py-2 border-t">
                <div class="text-xs fw-600 text-muted text-uppercase mb-1">Your Clinical Reason</div>
                <div class="reason-box"><?php echo sanitize($req['reason']); ?></div>
            </div>
            <?php if ($req['admin_note']): ?>
            <div class="mt-3">
                <div class="text-xs fw-600 text-muted text-uppercase mb-1">Hospital Admin Review Note</div>
                <div class="admin-note-box"><?php echo sanitize($req['admin_note']); ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="flex-col gap-4">
        <?php if ($ext_req): ?>
        <div class="panel">
            <div class="panel-header"><span class="panel-title"><i class="fa fa-hospital text-accent"></i> Inter-Hospital Coordination</span></div>
            <div class="panel-body text-md">
                <p class="mb-2">Your Hospital Admin forwarded this request to a partner hospital due to local stock shortage.</p>
                <div class="info-sub-box">
                    <div class="mb-1"><strong>Partner Hospital:</strong> <?php echo sanitize($ext_req['receiver_name']); ?></div>
                    <div class="mb-1"><strong>Coordination Status:</strong> <span class="badge <?php echo get_status_class($ext_req['status']); ?>"><?php echo $ext_req['status']; ?></span></div>
                    <?php if ($ext_req['response_note']): ?>
                    <div><strong>Partner Admin Note:</strong> <?php echo sanitize($ext_req['response_note']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($transfer): ?>
        <div class="panel">
            <div class="panel-header"><span class="panel-title"><i class="fa fa-truck-moving text-primary"></i> Dispatch & Transfer</span></div>
            <div class="panel-body text-md">
                <div class="flex-col gap-2">
                    <div><strong>Transfer ID:</strong> T-<?php echo str_pad($transfer['transfer_id'], 3, '0', STR_PAD_LEFT); ?></div>
                    <div><strong>Driver Name:</strong> <?php echo sanitize($transfer['driver_name']); ?></div>
                    <div><strong>Vehicle Number:</strong> <code><?php echo sanitize($transfer['vehicle_number']); ?></code></div>
                    <div><strong>Current Stage:</strong> <span class="badge <?php echo ($transfer['status'] === 'Completed') ? 'badge-success' : (($transfer['status'] === 'In Transit') ? 'badge-info' : 'badge-warning'); ?>"><?php echo $transfer['status']; ?></span></div>
                    <?php if ($transfer['status'] === 'In Transit'): ?>
                        <div class="transit-box">
                             Resource is currently in transit!
                        </div>
                    <?php elseif ($transfer['status'] === 'Completed'): ?>
                        <div class="completed-box">
                             Received & stock updated successfully!
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
