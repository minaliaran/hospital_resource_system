<?php
$page_title = "Audit Logs";
require_once __DIR__ . '/../includes/header.php';
require_role('admin');
$h = $_SESSION['hospital_id'];
$logs = $conn->query("SELECT al.*, u.full_name, u.role FROM audit_logs al LEFT JOIN users u ON al.user_id=u.user_id WHERE u.hospital_id=$h OR al.user_id IS NULL ORDER BY al.created_at DESC LIMIT 200")->fetch_all(MYSQLI_ASSOC);
?>
<div class="page-header">
    <div><div class="page-header-title">Audit Logs</div><div class="page-header-subtitle">Complete record of user actions and system events for your hospital</div></div>
</div>
<div class="panel">
    <div class="panel-header"><span class="panel-title"><i class="fa fa-shield-alt text-primary-icon"></i> Action Log</span><span class="table-meta-text">Last 200 entries</span></div>
    <?php if (empty($logs)): ?>
    <div class="empty-state"><i class="fa fa-shield-alt"></i><p>No audit logs yet.</p></div>
    <?php else: ?>
    <div class="table-responsive">
    <table class="data-table">
        <thead><tr><th>User</th><th>Role</th><th>Action</th><th>Description</th><th>IP Address</th><th>Timestamp</th></tr></thead>
        <tbody>
        <?php foreach ($logs as $log): ?>
        <tr>
            <td class="fw-600"><?php echo $log['full_name'] ? sanitize($log['full_name']) : '<span class="text-muted">System</span>'; ?></td>
            <td class="text-sm"><?php echo $log['role'] ? str_replace('_',' ',ucwords($log['role'],'_')) : '—'; ?></td>
            <td><span class="badge badge-info"><?php echo sanitize($log['action']); ?></span></td>
            <td class="text-md max-w-180"><?php echo sanitize(substr($log['description'],0,80)); ?><?php echo strlen($log['description'])>80?'...':''; ?></td>
            <td class="table-row-id"><?php echo sanitize($log['ip_address']); ?></td>
            <td class="table-row-date"><?php echo date('M j, Y g:i a', strtotime($log['created_at'])); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
