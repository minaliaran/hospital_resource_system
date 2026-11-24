<?php
$page_title = "Outgoing External Requests";
require_once __DIR__ . '/../includes/header.php';
require_role('admin');
$h = $_SESSION['hospital_id'];
$filter = $_GET['status'] ?? '';
$sql = "SELECT er.*, hr.hospital_name as receiver_name FROM external_requests er JOIN hospitals hr ON er.receiver_hospital_id=hr.hospital_id WHERE er.sender_hospital_id=$h";
if ($filter) $sql .= " AND er.status='" . $conn->real_escape_string($filter) . "'";
$sql .= " ORDER BY er.created_at DESC";
$reqs = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
$counts = [];
foreach (['Pending','Accepted','Rejected','Cancelled'] as $s) {
    $counts[$s] = $conn->query("SELECT COUNT(*) FROM external_requests WHERE sender_hospital_id=$h AND status='$s'")->fetch_row()[0];
}
?>
<div class="page-header">
    <div>
        <div class="page-header-title">Outgoing External Requests</div>
        <div class="page-header-subtitle">Requests sent from your hospital to other hospitals</div>
    </div>
    <a href="other_hospital_availability.php" class="btn btn-primary"><i class="fa fa-plus"></i> New Request</a>
</div>
<div class="filter-btn-group">
    <a href="outgoing_requests.php" class="btn <?php echo !$filter ? 'btn-primary' : 'btn-secondary'; ?>">All (<?php echo array_sum($counts); ?>)</a>
    <?php foreach ($counts as $s => $c): ?>
    <a href="?status=<?php echo $s; ?>" class="btn <?php echo $filter === $s ? 'btn-primary' : 'btn-secondary'; ?>"><?php echo $s; ?> (<?php echo $c; ?>)</a>
    <?php endforeach; ?>
</div>
<div class="panel">
    <div class="panel-header">
        <span class="panel-title"><i class="fa fa-paper-plane text-primary-icon"></i> Sent Requests</span>
    </div>
    <?php if (empty($reqs)): ?>
    <div class="empty-state"><i class="fa fa-paper-plane"></i><p>No outgoing requests yet. <a href="other_hospital_availability.php" class="text-link">Check availability and send a request.</a></p></div>
    <?php else: ?>
    <div class="table-responsive">
    <table class="data-table">
        <thead><tr><th>ID</th><th>To Hospital</th><th>Resource</th><th>Blood Group</th><th>Qty</th><th>Priority</th><th>Status</th><th>Response Note</th><th>Sent</th></tr></thead>
        <tbody>
        <?php foreach ($reqs as $r): ?>
        <tr>
            <td class="table-row-id">#<?php echo $r['external_request_id']; ?></td>
            <td><strong><?php echo sanitize($r['receiver_name']); ?></strong></td>
            <td><?php echo sanitize($r['resource_type']); ?></td>
            <td><?php echo $r['blood_group'] ? '<span class="text-danger-bold">'.sanitize($r['blood_group']).'</span>' : ($r['cylinder_type'] ? sanitize($r['cylinder_type']) : '—'); ?></td>
            <td><strong><?php echo $r['quantity']; ?></strong></td>
            <td><span class="badge <?php echo match($r['priority']) { 'Emergency'=>'badge-danger','High'=>'badge-warning','Medium'=>'badge-info',default=>'badge-secondary' }; ?>"><?php echo $r['priority']; ?></span></td>
            <td><span class="badge <?php echo get_status_class($r['status']); ?>"><?php echo $r['status']; ?></span></td>
            <td class="text-sm text-muted max-w-180"><?php echo $r['response_note'] ? sanitize(substr($r['response_note'],0,60)) : '—'; ?></td>
            <td class="table-row-id"><?php echo date('M j, Y', strtotime($r['created_at'])); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
