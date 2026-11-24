<?php
$page_title = "Incoming External Requests";
require_once __DIR__ . '/../includes/header.php';
require_role('admin');
$h = $_SESSION['hospital_id'];
$filter = $_GET['status'] ?? '';
$sql = "SELECT er.*, hs.hospital_name as sender_name FROM external_requests er JOIN hospitals hs ON er.sender_hospital_id=hs.hospital_id WHERE er.receiver_hospital_id=$h";
if ($filter) $sql .= " AND er.status='" . $conn->real_escape_string($filter) . "'";
$sql .= " ORDER BY FIELD(er.status,'Pending','Accepted','Rejected','Cancelled'), er.created_at DESC";
$reqs = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
$counts = [];
foreach (['Pending','Accepted','Rejected','Cancelled'] as $s) {
    $counts[$s] = $conn->query("SELECT COUNT(*) FROM external_requests WHERE receiver_hospital_id=$h AND status='$s'")->fetch_row()[0];
}
?>
<div class="page-header">
    <div>
        <div class="page-header-title">Incoming External Requests</div>
        <div class="page-header-subtitle">Requests received from other hospitals needing your resources</div>
    </div>
    <?php if ($counts['Pending'] > 0): ?>
    <span class="badge badge-danger badge-status-lg"><?php echo $counts['Pending']; ?> Awaiting Response</span>
    <?php endif; ?>
</div>
<div class="filter-btn-group">
    <a href="incoming_requests.php" class="btn <?php echo !$filter ? 'btn-primary' : 'btn-secondary'; ?>">All (<?php echo array_sum($counts); ?>)</a>
    <?php foreach ($counts as $s => $c): ?>
    <a href="?status=<?php echo $s; ?>" class="btn <?php echo $filter === $s ? 'btn-primary' : 'btn-secondary'; ?>"><?php echo $s; ?> (<?php echo $c; ?>)</a>
    <?php endforeach; ?>
</div>
<div class="panel">
    <div class="panel-header">
        <span class="panel-title"><i class="fa fa-inbox text-primary-icon"></i> Incoming Request Inbox</span>
    </div>
    <?php if (empty($reqs)): ?>
    <div class="empty-state"><p>No incoming requests found.</p></div>
    <?php else: ?>
    <div class="table-responsive">
    <table class="data-table">
        <thead><tr><th>ID</th><th>From Hospital</th><th>Resource</th><th>Blood Group</th><th>Qty</th><th>Priority</th><th>Status</th><th>Received</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($reqs as $r): ?>
        <tr>
            <td class="table-row-id">#<?php echo $r['external_request_id']; ?></td>
            <td><strong><?php echo sanitize($r['sender_name']); ?></strong></td>
            <td><?php echo sanitize($r['resource_type']); ?></td>
            <td><?php echo $r['blood_group'] ? '<span class="text-danger-bold">'.sanitize($r['blood_group']).'</span>' : ($r['cylinder_type'] ? sanitize($r['cylinder_type']) : '—'); ?></td>
            <td><strong><?php echo $r['quantity']; ?></strong></td>
            <td><span class="badge <?php echo match($r['priority']) { 'Emergency'=>'badge-danger','High'=>'badge-warning','Medium'=>'badge-info',default=>'badge-secondary' }; ?>"><?php echo $r['priority']; ?></span></td>
            <td><span class="badge <?php echo get_status_class($r['status']); ?>"><?php echo $r['status']; ?></span></td>
            <td class="table-row-date"><?php echo date('M j, Y g:i a', strtotime($r['created_at'])); ?></td>
            <td>
                <?php if ($r['status'] === 'Pending'): ?>
                <div class="action-btn-group">
                    <button type="button" class="btn btn-sm btn-success btn-respond" data-ext-id="<?php echo $r['external_request_id']; ?>" data-action="Accept" data-sender="<?php echo htmlspecialchars($r['sender_name'], ENT_QUOTES); ?>" data-qty="<?php echo $r['quantity']; ?>" data-resource="<?php echo htmlspecialchars($r['resource_type'], ENT_QUOTES); ?>"><i class="fa fa-check"></i> Accept</button>
                    <button type="button" class="btn btn-sm btn-danger btn-respond" data-ext-id="<?php echo $r['external_request_id']; ?>" data-action="Reject" data-sender="<?php echo htmlspecialchars($r['sender_name'], ENT_QUOTES); ?>" data-qty="<?php echo $r['quantity']; ?>" data-resource="<?php echo htmlspecialchars($r['resource_type'], ENT_QUOTES); ?>"><i class="fa fa-times"></i> Reject</button>
                </div>
                <?php else: ?>
                <span class="table-row-date">Responded <?php echo $r['responded_at'] ? date('M j', strtotime($r['responded_at'])) : ''; ?></span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<div id="respondModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <span class="modal-title" id="respondTitle"></span>
            <button class="modal-close" data-modal-close="respondModal">&times;</button>
        </div>
        <div id="respondInfo" class="info-sub-box"></div>
        <form action="../actions/respond_external_request.php" method="POST">
            <input type="hidden" name="external_request_id" id="resp_ext_id">
            <input type="hidden" name="action" id="resp_action">
            <div id="driver_fields" class="d-none">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Driver Name <span class="required">*</span></label>
                        <input type="text" name="driver_name" id="driver_name" class="form-control" placeholder="e.g. Sunil Perera">
                    </div>
                    <div class="form-group">
                        <label>Vehicle Number <span class="required">*</span></label>
                        <input type="text" name="vehicle_number" id="vehicle_number" class="form-control" placeholder="e.g. WP-5678">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label id="resp_note_label">Response Note (optional)</label>
                <textarea name="response_note" class="form-control" rows="3" id="resp_note" placeholder="Add any relevant notes..."></textarea>
            </div>
            <div class="flex-end-gap">
                <button type="button" class="btn btn-secondary" data-modal-close="respondModal">Cancel</button>
                <button type="submit" id="respondSubmitBtn" class="btn btn-success"><i class="fa fa-check"></i> Confirm Accept</button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="../assets/js/admin/admin_incoming_requests.js?v=<?php echo time(); ?>"></script>
