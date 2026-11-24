<?php
$page_title = "Admin Dashboard";
require_once __DIR__ . '/../includes/header.php';
require_role('admin');
$h = $_SESSION['hospital_id'];
$total_blood  = $conn->query("SELECT COALESCE(SUM(quantity),0) FROM blood_stock WHERE hospital_id=$h")->fetch_row()[0];
$total_oxygen = $conn->query("SELECT COALESCE(SUM(quantity),0) FROM oxygen_stock WHERE hospital_id=$h")->fetch_row()[0];
$pending_req  = $conn->query("SELECT COUNT(*) FROM requests WHERE hospital_id=$h AND status='Pending'")->fetch_row()[0];
$incoming_ext = $conn->query("SELECT COUNT(*) FROM external_requests WHERE receiver_hospital_id=$h AND status='Pending'")->fetch_row()[0];
$transfers_active = $conn->query("SELECT COUNT(*) FROM transfers WHERE (from_hospital_id=$h OR to_hospital_id=$h) AND status IN ('Pending','In Transit')")->fetch_row()[0];
$critical_count = $conn->query("SELECT COUNT(*) FROM blood_stock WHERE hospital_id=$h AND status IN ('Critical','Out of Stock')")->fetch_row()[0];
$critical_count += $conn->query("SELECT COUNT(*) FROM oxygen_stock WHERE hospital_id=$h AND status IN ('Critical','Out of Stock')")->fetch_row()[0];
$recent_reqs = $conn->query("SELECT r.*, u.full_name, u.role FROM requests r JOIN users u ON r.created_by=u.user_id WHERE r.hospital_id=$h AND r.priority IN ('Emergency', 'High') ORDER BY r.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$blood_critical = $conn->query("SELECT blood_group, component, quantity, status FROM blood_stock WHERE hospital_id=$h AND status IN ('Critical','Out of Stock') ORDER BY quantity ASC LIMIT 3")->fetch_all(MYSQLI_ASSOC);
$oxygen_critical = $conn->query("SELECT cylinder_type, quantity, status FROM oxygen_stock WHERE hospital_id=$h AND status IN ('Critical','Out of Stock') ORDER BY quantity ASC LIMIT 3")->fetch_all(MYSQLI_ASSOC);
$notifs = $conn->query("SELECT title, message, created_at, is_read FROM notifications WHERE hospital_id=$h ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
$blood_stock_data = array_fill_keys($blood_groups, 0);
$blood_q = $conn->query("SELECT blood_group, SUM(quantity) FROM blood_stock WHERE hospital_id = $h GROUP BY blood_group");
while ($row = $blood_q->fetch_row()) {
    $blood_stock_data[$row[0]] = (int)$row[1];
}
$cylinder_types = ['Small', 'Medium', 'Large', 'Portable', 'E-Type', 'D-Type', 'J-Type'];
$oxygen_stock_data = array_fill_keys($cylinder_types, 0);
$oxygen_q = $conn->query("SELECT cylinder_type, SUM(quantity) FROM oxygen_stock WHERE hospital_id = $h GROUP BY cylinder_type");
while ($row = $oxygen_q->fetch_row()) {
    $oxygen_stock_data[$row[0]] = (int)$row[1];
}
$request_statuses = ['Pending', 'Approved', 'Rejected', 'Completed'];
$request_status_data = array_fill_keys($request_statuses, 0);
$request_q = $conn->query("SELECT status, COUNT(*) FROM requests WHERE hospital_id = $h GROUP BY status");
while ($row = $request_q->fetch_row()) {
    if (isset($request_status_data[$row[0]])) {
        $request_status_data[$row[0]] = (int)$row[1];
    }
}
?>
<div class="page-header">
    <div>
        <div class="page-header-title">Welcome, <?php echo sanitize(explode(' ', $full_name)[0]); ?></div>
        <div class="page-header-subtitle">Here's an overview of <?php echo sanitize($hospital_name); ?> resources</div>
    </div>
    <?php if ($pending_req > 0): ?>
    <a href="staff_requests.php" class="btn btn-primary"><i class="fa fa-bell"></i> <?php echo $pending_req; ?> Pending Request<?php echo $pending_req > 1 ? 's' : ''; ?></a>
    <?php endif; ?>
</div>
<div class="dashboard-grid grid-auto-fit">
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-label">Total Blood Units</div>
            <div class="stat-card-value"><?php echo number_format($total_blood); ?></div>
            <div class="stat-card-sub"><a href="blood_stock.php" class="text-link">View full stock →</a></div>
        </div>
        <div class="stat-card-icon icon-blood"><i class="fa fa-tint"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-label">Total Oxygen Cylinders</div>
            <div class="stat-card-value"><?php echo number_format($total_oxygen); ?></div>
            <div class="stat-card-sub"><a href="oxygen_stock.php" class="text-link">View full stock →</a></div>
        </div>
        <div class="stat-card-icon icon-oxygen"><i class="fa fa-lungs"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-label">Pending Requests</div>
            <div class="stat-card-value"><?php echo $pending_req; ?></div>
            <div class="stat-card-sub"><a href="staff_requests.php" class="text-link">Review now →</a></div>
        </div>
        <div class="stat-card-icon icon-request"><i class="fa fa-clipboard-list"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-label">Incoming External Requests</div>
            <div class="stat-card-value"><?php echo $incoming_ext; ?></div>
            <div class="stat-card-sub"><a href="incoming_requests.php" class="text-link">Open inbox →</a></div>
        </div>
        <div class="stat-card-icon icon-incoming"><i class="fa fa-inbox"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-label">Active Transfers</div>
            <div class="stat-card-value"><?php echo $transfers_active; ?></div>
            <div class="stat-card-sub"><a href="transfers.php" class="text-link">Track all →</a></div>
        </div>
        <div class="stat-card-icon icon-transfer"><i class="fa fa-truck-moving"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-label">Critical Stock Items</div>
            <div class="stat-card-value"><?php echo $critical_count; ?></div>
            <div class="stat-card-sub">
                <?php if ($critical_count > 0): ?>
                    <span class="text-danger fw-600"><i class="fa fa-exclamation-triangle"></i> Action required</span>
                <?php else: ?>
                    <span class="text-success fw-600"><i class="fa fa-check-circle"></i> Levels adequate</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="stat-card-icon <?php echo $critical_count > 0 ? 'icon-blood' : 'icon-transfer'; ?>"><i class="fa fa-exclamation-circle"></i></div>
    </div>
</div>
<div class="dashboard-charts-grid chart-grid">
    <div class="panel">
        <div class="panel-header"><span class="panel-title"><i class="fa fa-tint text-danger"></i> Blood Stock by Blood Group</span></div>
        <div class="panel-body chart-panel-body">
            <canvas id="bloodGroupChart" class="chart-canvas" data-labels='<?php echo json_encode(array_keys($blood_stock_data)); ?>' data-values='<?php echo json_encode(array_values($blood_stock_data)); ?>'></canvas>
        </div>
    </div>
    <div class="panel">
        <div class="panel-header"><span class="panel-title"><i class="fa fa-lungs text-info"></i> Oxygen Stock by Cylinder Type</span></div>
        <div class="panel-body chart-panel-body">
            <canvas id="oxygenTypeChart" class="chart-canvas" data-labels='<?php echo json_encode(array_keys($oxygen_stock_data)); ?>' data-values='<?php echo json_encode(array_values($oxygen_stock_data)); ?>'></canvas>
        </div>
    </div>
    <div class="panel">
        <div class="panel-header"><span class="panel-title"><i class="fa fa-pie-chart text-primary"></i> Request Status Summary</span></div>
        <div class="panel-body chart-panel-body-sm">
            <div class="chart-doughnut-container">
                <canvas id="requestStatusChart" data-labels='<?php echo json_encode(array_keys($request_status_data)); ?>' data-values='<?php echo json_encode(array_values($request_status_data)); ?>'></canvas>
            </div>
        </div>
    </div>
</div>
<div class="page-row">
    <div class="panel">
        <div class="panel-header">
            <span class="panel-title"><i class="fa fa-clipboard-list text-primary"></i> Recent Emergency Requests</span>
            <a href="staff_requests.php" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <?php if (empty($recent_reqs)): ?>
        <div class="empty-state"><p>No emergency requests submitted yet.</p></div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="data-table">
            <thead><tr><th>Staff Member</th><th>Resource</th><th>Blood Group</th><th>Qty</th><th>Priority</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($recent_reqs as $r): ?>
            <tr>
                <td><?php echo sanitize($r['full_name']); ?><br><span class="text-xs text-muted"><?php echo str_replace('_',' ',$r['role']); ?></span></td>
                <td><?php echo sanitize($r['resource_type']); ?></td>
                <td><?php echo $r['blood_group'] ? sanitize($r['blood_group']) : '<span class="text-disabled">—</span>'; ?></td>
                <td><?php echo sanitize($r['quantity']); ?></td>
                <td><span class="badge <?php echo $r['priority'] === 'Emergency' ? 'badge-danger' : 'badge-warning'; ?>"><?php echo $r['priority']; ?></span></td>
                <td><span class="badge <?php echo get_status_class($r['status']); ?>"><?php echo $r['status']; ?></span></td>
                <td><a href="request_details.php?id=<?php echo $r['request_id']; ?>" class="btn btn-sm btn-secondary">Review</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
    <div class="flex-col gap-4">
        <div class="panel">
            <div class="panel-header">
                <span class="panel-title"><i class="fa fa-exclamation-triangle text-danger"></i> Stock Alerts</span>
                <?php if ($critical_count > 0): ?><span class="badge badge-danger"><?php echo $critical_count; ?> Critical</span><?php endif; ?>
            </div>
            <?php if (empty($blood_critical) && empty($oxygen_critical)): ?>
            <div class="empty-state py-3"><i class="fa fa-check-circle text-success alert-icon-lg"></i><p>All levels adequate.</p></div>
            <?php else: ?>
            <div class="px-3">
                <?php foreach ($blood_critical as $a): ?>
                <div class="flex-between py-1 border-b">
                    <div>
                        <span class="text-danger fw-700 text-md"><?php echo sanitize($a['blood_group']); ?></span>
                        <span class="text-xs text-muted ml-1"><?php echo sanitize($a['component']); ?></span>
                    </div>
                    <div class="flex-center-gap">
                        <span class="fw-700"><?php echo $a['quantity']; ?> u</span>
                        <span class="badge <?php echo get_status_class($a['status']); ?>"><?php echo $a['status']; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php foreach ($oxygen_critical as $a): ?>
                <div class="flex-between py-1 border-b">
                    <div>
                        <span class="text-info fw-700 text-md"><?php echo sanitize($a['cylinder_type']); ?> Cyl</span>
                    </div>
                    <div class="flex-center-gap">
                        <span class="fw-700"><?php echo $a['quantity']; ?></span>
                        <span class="badge <?php echo get_status_class($a['status']); ?>"><?php echo $a['status']; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="py-2 px-3"><a href="other_hospital_availability.php" class="btn btn-primary btn-full">Check Other Hospitals</a></div>
            <?php endif; ?>
        </div>
        <div class="panel">
            <div class="panel-header">
                <span class="panel-title"><i class="fa fa-bell text-accent"></i> Notifications</span>
                <a href="notifications.php" class="text-link text-xs fw-600">All</a>
            </div>
            <div class="px-3">
            <?php if (empty($notifs)): ?>
                <div class="empty-state py-3"><i class="fa fa-bell-slash"></i><p>No notifications.</p></div>
            <?php else: ?>
                <?php foreach ($notifs as $nt): ?>
                <div class="notif-item">
                    <div class="notif-icon <?php echo $nt['is_read'] ? 'notif-read' : 'icon-request notif-unread'; ?>"><i class="fa fa-bell"></i></div>
                    <div class="notif-content">
                        <div class="notif-title"><?php echo sanitize($nt['title']); ?></div>
                        <div class="notif-msg"><?php echo sanitize(substr($nt['message'], 0, 70)) . (strlen($nt['message']) > 70 ? '...' : ''); ?></div>
                        <div class="notif-time"><?php echo date('M j, g:i a', strtotime($nt['created_at'])); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="<?php echo $base; ?>assets/js/vendor/chart.min.js"></script>
<script src="<?php echo $base; ?>assets/js/admin/admin_dashboard.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
