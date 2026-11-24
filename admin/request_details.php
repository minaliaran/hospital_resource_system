<?php
$page_title = "Request Details";
require_once __DIR__ . '/../includes/header.php';
require_role('admin');
$h = $_SESSION['hospital_id'];
$id = intval($_GET['id'] ?? 0);
if (!$id) { header("Location: staff_requests.php"); exit; }
$stmt = $conn->prepare("SELECT r.*, u.full_name, u.role, u.username FROM requests r JOIN users u ON r.created_by=u.user_id WHERE r.request_id=? AND r.hospital_id=?");
$stmt->bind_param("ii", $id, $h);
$stmt->execute();
$req = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$req) { header("Location: staff_requests.php?error=invalid"); exit; }
$own_blood_total = 0;
if ($req['resource_type'] === 'Blood' && $req['blood_group']) {
    $own_blood_total = $conn->query("SELECT COALESCE(SUM(quantity),0) FROM blood_stock WHERE hospital_id=$h AND blood_group='".$conn->real_escape_string($req['blood_group'])."'")->fetch_row()[0];
}
$own_oxy_total = $conn->query("SELECT COALESCE(SUM(quantity),0) FROM oxygen_stock WHERE hospital_id=$h")->fetch_row()[0];
$other_hospitals = [];
if ($req['resource_type'] === 'Blood' && $req['blood_group']) {
    $bg = $conn->real_escape_string($req['blood_group']);
    $other_hospitals = $conn->query("SELECT h.hospital_id, h.hospital_name, COALESCE(SUM(bs.quantity),0) as total FROM hospitals h LEFT JOIN blood_stock bs ON h.hospital_id=bs.hospital_id AND bs.blood_group='$bg' WHERE h.hospital_id != $h AND h.status='active' GROUP BY h.hospital_id ORDER BY total DESC")->fetch_all(MYSQLI_ASSOC);
} else {
    $other_hospitals = $conn->query("SELECT h.hospital_id, h.hospital_name, COALESCE(SUM(os.quantity),0) as total FROM hospitals h LEFT JOIN oxygen_stock os ON h.hospital_id=os.hospital_id WHERE h.hospital_id != $h AND h.status='active' GROUP BY h.hospital_id ORDER BY total DESC")->fetch_all(MYSQLI_ASSOC);
}
?>
<div class="mb-3">
    <a href="staff_requests.php" class="text-link text-md"><i class="fa fa-arrow-left"></i> Back to Requests</a>
</div>
<div class="page-header">
    <div>
        <div class="page-header-title">Request #<?php echo $req['request_id']; ?> Details</div>
        <div class="page-header-subtitle">Review emergency request submitted by <?php echo sanitize($req['full_name']); ?></div>
    </div>
    <span class="badge <?php echo get_status_class($req['status']); ?> badge-lg"><?php echo $req['status']; ?></span>
</div>
<div class="page-row">
    <div class="flex-col gap-4">
        <div class="panel">
            <div class="panel-header"><span class="panel-title"><i class="fa fa-file-alt text-primary"></i> Request Information</span></div>
            <div class="panel-body">
                <div class="grid-2-col">
                    <?php
                    $fields = [
                        'Requested By'  => sanitize($req['full_name']) . ' (' . str_replace('_',' ',$req['role']) . ')',
                        'Resource Type' => sanitize($req['resource_type']),
                        'Blood Group'   => $req['blood_group'] ? '<span class="text-danger fw-700">'.sanitize($req['blood_group']).'</span>' : '—',
                        'Cylinder Type' => $req['cylinder_type'] ? sanitize($req['cylinder_type']) : '—',
                        'Quantity'      => '<strong>' . $req['quantity'] . '</strong>',
                        'Priority'      => '<span class="badge ' . match($req['priority']) { 'Emergency'=>'badge-danger','High'=>'badge-warning','Medium'=>'badge-info',default=>'badge-secondary' } . '">' . $req['priority'] . '</span>',
                        'Status'        => '<span class="badge '.get_status_class($req['status']).'">'.sanitize($req['status']).'</span>',
                        'Submitted'     => date('M j, Y g:i a', strtotime($req['created_at'])),
                    ];
                    foreach ($fields as $label => $value):
                    ?>
                    <div>
                        <div class="text-xs fw-600 text-muted text-uppercase mb-1"><?php echo $label; ?></div>
                        <div class="text-md"><?php echo $value; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-3">
                    <div class="text-xs fw-600 text-muted text-uppercase mb-1">Reason / Clinical Notes</div>
                    <div class="reason-box"><?php echo sanitize($req['reason']); ?></div>
                </div>
                <?php if ($req['admin_note']): ?>
                <div class="mt-3">
                    <div class="text-xs fw-600 text-muted text-uppercase mb-1">Admin Review Note</div>
                    <div class="admin-note-box"><?php echo sanitize($req['admin_note']); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($req['status'] === 'Pending'): ?>
        <div class="panel">
            <div class="panel-header"><span class="panel-title"><i class="fa fa-tasks text-primary"></i> Review Actions</span></div>
            <div class="panel-body">
                <div class="flex-wrap-gap">
                    <form action="../actions/approve_request.php" method="POST" class="flex-1">
                        <input type="hidden" name="request_id" value="<?php echo $req['request_id']; ?>">
                        <div class="form-group"><label>Admin Note (optional)</label><textarea name="admin_note" class="form-control" rows="2" placeholder="e.g. Approved — stock dispatched from ward B."></textarea></div>
                        <button type="submit" class="btn btn-success btn-block"><i class="fa fa-check"></i> Approve Request</button>
                    </form>
                    <form action="../actions/reject_request.php" method="POST" class="flex-1">
                        <input type="hidden" name="request_id" value="<?php echo $req['request_id']; ?>">
                        <div class="form-group"><label>Rejection Reason <span class="required">*</span></label><textarea name="admin_note" class="form-control" rows="2" required placeholder="e.g. Insufficient stock. Request resubmit with correct qty."></textarea></div>
                        <button type="submit" class="btn btn-danger btn-block"><i class="fa fa-times"></i> Reject Request</button>
                    </form>
                </div>
                <div class="mt-3 py-2 border-t">
                    <div class="text-md fw-600 mb-2"><i class="fa fa-external-link-alt"></i> Forward to Another Hospital</div>
                    <a href="other_hospital_availability.php?req_id=<?php echo $req['request_id']; ?>" class="btn btn-info"><i class="fa fa-hospital-user"></i> Check Availability & Forward</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div class="flex-col gap-4">
        <div class="panel">
            <div class="panel-header"><span class="panel-title"><i class="fa fa-chart-bar text-primary"></i> Own Hospital Stock</span></div>
            <div class="panel-body">
                <?php if ($req['resource_type'] === 'Blood'): ?>
                <div class="text-sm text-muted mb-2"><?php echo sanitize($req['blood_group']); ?> Total Available</div>
                <div class="font-stat <?php echo $own_blood_total >= $req['quantity'] ? 'text-success' : 'text-danger'; ?>"><?php echo $own_blood_total; ?> <span class="font-unit">units</span></div>
                <div class="text-sm text-muted mt-1">Requested: <?php echo $req['quantity']; ?> units</div>
                <div class="badge badge-mt <?php echo $own_blood_total >= $req['quantity'] ? 'badge-success' : 'badge-danger'; ?>">
                    <?php echo $own_blood_total >= $req['quantity'] ? 'Sufficient stock' : 'Insufficient — Forward needed'; ?>
                </div>
                <?php else: ?>
                <div class="text-sm text-muted mb-2">Oxygen Total</div>
                <div class="font-stat <?php echo $own_oxy_total >= $req['quantity'] ? 'text-success' : 'text-danger'; ?>"><?php echo $own_oxy_total; ?> <span class="font-unit">cylinders</span></div>
                <div class="badge badge-mt <?php echo $own_oxy_total >= $req['quantity'] ? 'badge-success' : 'badge-danger'; ?>">
                    <?php echo $own_oxy_total >= $req['quantity'] ? 'Sufficient stock' : 'Insufficient — Forward needed'; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="panel">
            <div class="panel-header"><span class="panel-title"><i class="fa fa-hospital text-accent"></i> Other Hospitals</span></div>
            <div class="panel-sub-body">
                <?php foreach ($other_hospitals as $oh): ?>
                <div class="flex-between py-1 border-b">
                    <div class="text-md fw-600"><?php echo sanitize($oh['hospital_name']); ?></div>
                    <div class="flex-center-gap">
                        <span class="fw-700 text-lg"><?php echo $oh['total']; ?></span>
                        <span class="badge <?php echo get_status_class(get_blood_status_label($oh['total'])); ?>"><?php echo get_blood_status_label($oh['total']); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
                <a href="other_hospital_availability.php?req_id=<?php echo $req['request_id']; ?>" class="btn btn-primary btn-full mt-3"><i class="fa fa-paper-plane"></i> Send External Request</a>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
