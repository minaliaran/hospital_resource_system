<?php
$page_title = "My Requests";
require_once __DIR__ . '/../includes/header.php';
require_role(['blood_staff', 'oxygen_staff']);
$uid  = $_SESSION['user_id'];
$role = $_SESSION['role'];
$filter = $_GET['status'] ?? '';
$sql = "SELECT * FROM requests WHERE created_by=$uid";
if ($filter) $sql .= " AND status='" . $conn->real_escape_string($filter) . "'";
$sql .= " ORDER BY created_at DESC";
$reqs = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
$counts = [];
foreach (['Pending','Approved','Rejected','Forwarded','Completed'] as $s) {
    $counts[$s] = $conn->query("SELECT COUNT(*) FROM requests WHERE created_by=$uid AND status='$s'")->fetch_row()[0];
}
?>
<div class="page-header">
    <div>
        <div class="page-header-title">My Emergency Requests</div>
        <div class="page-header-subtitle">Track all requests you have submitted</div>
    </div>
    <a href="<?php echo $role === 'blood_staff' ? 'create_blood_request.php' : 'create_oxygen_request.php'; ?>" class="btn btn-primary"><i class="fa fa-plus"></i> New Request</a>
</div>
<div class="flex-wrap-gap mb-4">
    <a href="my_requests.php" class="btn <?php echo !$filter ? 'btn-primary' : 'btn-secondary'; ?>">All (<?php echo array_sum($counts); ?>)</a>
    <?php foreach ($counts as $s => $c): ?>
    <a href="?status=<?php echo $s; ?>" class="btn <?php echo $filter === $s ? 'btn-primary' : 'btn-secondary'; ?>"><?php echo $s; ?> (<?php echo $c; ?>)</a>
    <?php endforeach; ?>
</div>
<div class="panel">
    <div class="panel-header">
        <span class="panel-title"><i class="fa fa-list-alt text-primary"></i> Request History</span>
    </div>
    <?php if (empty($reqs)): ?>
    <div class="empty-state"><i class="fa fa-inbox"></i><p>No requests found. <a href="<?php echo $role === 'blood_staff' ? 'create_blood_request.php' : 'create_oxygen_request.php'; ?>" class="text-link">Submit your first request.</a></p></div>
    <?php else: ?>
    <div class="table-responsive">
    <table class="data-table">
        <thead><tr><th>ID</th><th>Resource</th><th>Qty</th><th>Priority</th><th>Status</th><th>Admin Note</th><th>Submitted</th><th>Details</th></tr></thead>
        <tbody>
        <?php foreach ($reqs as $r): ?>
        <tr>
            <td class="text-sm text-muted">#<?php echo $r['request_id']; ?></td>
            <td>
                <?php echo sanitize($r['resource_type']); ?>
                <?php if ($r['blood_group']): ?><span class="text-danger fw-700 ml-1"><?php echo sanitize($r['blood_group']); ?></span><?php endif; ?>
                <?php if ($r['cylinder_type']): ?><span class="text-info ml-1"><?php echo sanitize($r['cylinder_type']); ?></span><?php endif; ?>
            </td>
            <td><strong><?php echo $r['quantity']; ?></strong></td>
            <td><span class="badge <?php echo match($r['priority']) { 'Emergency'=>'badge-danger','High'=>'badge-warning','Medium'=>'badge-info',default=>'badge-secondary' }; ?>"><?php echo $r['priority']; ?></span></td>
            <td><span class="badge <?php echo get_status_class($r['status']); ?>"><?php echo $r['status']; ?></span></td>
            <td class="text-sm text-muted max-w-180"><?php echo $r['admin_note'] ? sanitize(substr($r['admin_note'],0,55)) . '...' : '<span class="text-disabled">—</span>'; ?></td>
            <td class="text-sm text-muted"><?php echo date('M j, Y', strtotime($r['created_at'])); ?></td>
            <td><a href="request_status.php?id=<?php echo $r['request_id']; ?>" class="btn btn-sm btn-secondary"><i class="fa fa-eye"></i> Track</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
