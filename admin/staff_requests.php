<?php
$page_title = "Staff Emergency Requests";
require_once __DIR__ . '/../includes/header.php';
require_role('admin');
$h = $_SESSION['hospital_id'];
$filter = $_GET['status'] ?? '';
$sql = "SELECT r.*, u.full_name, u.role FROM requests r JOIN users u ON r.created_by=u.user_id WHERE r.hospital_id=$h";
if ($filter) $sql .= " AND r.status='" . $conn->real_escape_string($filter) . "'";
$sql .= " ORDER BY FIELD(r.status,'Pending','Approved','Forwarded','Rejected','Completed'), r.created_at DESC";
$requests = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
$counts = [];
foreach (['Pending','Approved','Rejected','Forwarded','Completed'] as $s) {
    $counts[$s] = $conn->query("SELECT COUNT(*) FROM requests WHERE hospital_id=$h AND status='$s'")->fetch_row()[0];
}
?>
<div class="page-header">
    <div>
        <div class="page-header-title">Staff Emergency Requests</div>
        <div class="page-header-subtitle">Review blood and oxygen requests submitted by your staff</div>
    </div>
</div>
<div class="flex-wrap-gap mb-4">
    <a href="staff_requests.php" class="btn <?php echo !$filter ? 'btn-primary' : 'btn-secondary'; ?>">All (<?php echo array_sum($counts); ?>)</a>
    <?php foreach ($counts as $s => $c): ?>
    <a href="?status=<?php echo $s; ?>" class="btn <?php echo $filter === $s ? 'btn-primary' : 'btn-secondary'; ?>">
        <?php echo $s; ?> (<?php echo $c; ?>)
    </a>
    <?php endforeach; ?>
</div>
<div class="panel">
    <div class="panel-header">
        <span class="panel-title"><i class="fa fa-clipboard-list text-primary"></i> Request List</span>
        <span class="text-sm text-muted"><?php echo count($requests); ?> requests</span>
    </div>
    <?php if (empty($requests)): ?>
    <div class="empty-state"><p>No requests found for this filter.</p></div>
    <?php else: ?>
    <div class="table-responsive">
    <table class="data-table">
        <thead><tr><th>ID</th><th>Staff Member</th><th>Resource</th><th>Blood Group</th><th>Qty</th><th>Priority</th><th>Status</th><th>Submitted</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($requests as $r): ?>
        <tr>
            <td class="text-sm text-muted">#<?php echo $r['request_id']; ?></td>
            <td>
                <div class="fw-600 text-md"><?php echo sanitize($r['full_name']); ?></div>
                <div class="text-xs text-muted"><?php echo str_replace('_',' ',ucwords($r['role'],'_')); ?></div>
            </td>
            <td><?php echo sanitize($r['resource_type']); ?></td>
            <td><?php echo $r['blood_group'] ? '<span class="text-danger fw-700">'.sanitize($r['blood_group']).'</span>' : '<span class="text-disabled">—</span>'; ?></td>
            <td><strong><?php echo $r['quantity']; ?></strong></td>
            <td>
                <span class="badge <?php 
                    echo match($r['priority']) {
                        'Emergency' => 'badge-danger',
                        'High'      => 'badge-warning',
                        'Medium'    => 'badge-info',
                        default     => 'badge-secondary'
                    };
                ?>"><?php echo $r['priority']; ?></span>
            </td>
            <td><span class="badge <?php echo get_status_class($r['status']); ?>"><?php echo $r['status']; ?></span></td>
            <td class="text-sm text-muted"><?php echo date('M j, Y g:i a', strtotime($r['created_at'])); ?></td>
            <td><a href="request_details.php?id=<?php echo $r['request_id']; ?>" class="btn btn-sm btn-primary">Review</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
