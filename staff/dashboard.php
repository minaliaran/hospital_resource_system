<?php
$page_title = "Staff Dashboard";
require_once __DIR__ . '/../includes/header.php';
require_role(['blood_staff', 'oxygen_staff']);
$h = $_SESSION['hospital_id'];
$uid = $_SESSION['user_id'];
$role = $_SESSION['role'];
$total_blood  = $conn->query("SELECT COALESCE(SUM(quantity),0) FROM blood_stock WHERE hospital_id=$h")->fetch_row()[0];
$total_oxygen = $conn->query("SELECT COALESCE(SUM(quantity),0) FROM oxygen_stock WHERE hospital_id=$h")->fetch_row()[0];
$my_pending   = $conn->query("SELECT COUNT(*) FROM requests WHERE created_by=$uid AND status='Pending'")->fetch_row()[0];
$my_approved  = $conn->query("SELECT COUNT(*) FROM requests WHERE created_by=$uid AND status='Approved'")->fetch_row()[0];
$low_blood = $conn->query("SELECT COUNT(*) FROM blood_stock WHERE hospital_id=$h AND status IN ('Low','Critical','Out of Stock')")->fetch_row()[0];
$low_oxy   = $conn->query("SELECT COUNT(*) FROM oxygen_stock WHERE hospital_id=$h AND status IN ('Low','Critical','Out of Stock')")->fetch_row()[0];
$recent_reqs = $conn->query("SELECT * FROM requests WHERE created_by=$uid ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$notifs      = $conn->query("SELECT * FROM notifications WHERE (hospital_id=$h AND (user_id=$uid OR user_id IS NULL)) ORDER BY created_at DESC LIMIT 4")->fetch_all(MYSQLI_ASSOC);
$create_link = $role === 'blood_staff' ? '../staff/create_blood_request.php' : '../staff/create_oxygen_request.php';
?>
<div class="page-header">
    <div>
        <div class="page-header-title">Welcome, <?php echo sanitize(explode(' ', $full_name)[0]); ?></div>
        <div class="page-header-subtitle"><?php echo $role === 'blood_staff' ? 'Blood Bank Department' : 'Oxygen Supply Department'; ?> · <?php echo sanitize($hospital_name); ?></div>
    </div>
    <a href="<?php echo $role === 'blood_staff' ? 'create_blood_request.php' : 'create_oxygen_request.php'; ?>" class="btn btn-primary"><i class="fa fa-plus-circle"></i> New Emergency Request</a>
</div>
<div class="dashboard-grid">
    <div class="stat-card"><div class="stat-card-info"><div class="stat-card-label">Blood Units Available</div><div class="stat-card-value"><?php echo $total_blood; ?></div><div class="stat-card-sub"><a href="view_blood_stock.php" class="text-link">View stock →</a></div></div><div class="stat-card-icon icon-blood"><i class="fa fa-tint"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><div class="stat-card-label">Oxygen Cylinders</div><div class="stat-card-value"><?php echo $total_oxygen; ?></div><div class="stat-card-sub"><a href="view_oxygen_stock.php" class="text-link">View stock →</a></div></div><div class="stat-card-icon icon-oxygen"><i class="fa fa-lungs"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><div class="stat-card-label">My Pending Requests</div><div class="stat-card-value"><?php echo $my_pending; ?></div><div class="stat-card-sub"><a href="my_requests.php" class="text-link">View requests →</a></div></div><div class="stat-card-icon icon-request"><i class="fa fa-clipboard-list"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><div class="stat-card-label">My Approved Requests</div><div class="stat-card-value"><?php echo $my_approved; ?></div></div><div class="stat-card-icon icon-transfer"><i class="fa fa-check-circle"></i></div></div>
</div>
<?php if ($low_blood > 0 || $low_oxy > 0): ?>
<div class="alert alert-warning">
    <i class="fa fa-exclamation-triangle"></i>
    <span>Stock Alert: <?php echo $low_blood > 0 ? "$low_blood blood stock item(s) are at low/critical levels." : ''; ?> <?php echo $low_oxy > 0 ? "$low_oxy oxygen stock item(s) need attention." : ''; ?></span>
</div>
<?php endif; ?>
<div class="page-row">
    <div class="panel">
        <div class="panel-header">
            <span class="panel-title"><i class="fa fa-list-alt text-primary"></i> My Recent Requests</span>
            <a href="my_requests.php" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <?php if (empty($recent_reqs)): ?>
        <div class="empty-state"><i class="fa fa-file-alt"></i><p>No requests submitted yet. <a href="<?php echo $role === 'blood_staff' ? 'create_blood_request.php' : 'create_oxygen_request.php'; ?>" class="text-link">Create one now.</a></p></div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="data-table">
            <thead><tr><th>ID</th><th>Resource</th><th>Qty</th><th>Priority</th><th>Status</th><th>Submitted</th></tr></thead>
            <tbody>
            <?php foreach ($recent_reqs as $r): ?>
            <tr>
                <td class="text-sm text-muted">#<?php echo $r['request_id']; ?></td>
                <td><?php echo sanitize($r['resource_type']); ?><?php if ($r['blood_group']): ?> <span class="text-danger fw-700"><?php echo sanitize($r['blood_group']); ?></span><?php endif; ?></td>
                <td><?php echo $r['quantity']; ?></td>
                <td><span class="badge <?php echo match($r['priority']) { 'Emergency'=>'badge-danger','High'=>'badge-warning','Medium'=>'badge-info',default=>'badge-secondary' }; ?>"><?php echo $r['priority']; ?></span></td>
                <td><span class="badge <?php echo get_status_class($r['status']); ?>"><?php echo $r['status']; ?></span></td>
                <td class="text-sm text-muted"><?php echo date('M j, Y', strtotime($r['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
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
            <?php foreach ($notifs as $n): ?>
            <div class="notif-item">
                <div class="notif-icon notif-icon-success"><i class="fa fa-bell"></i></div>
                <div class="notif-content">
                    <div class="notif-title"><?php echo sanitize($n['title']); ?></div>
                    <div class="notif-msg"><?php echo sanitize(substr($n['message'],0,60)).(strlen($n['message'])>60?'...':''); ?></div>
                    <div class="notif-time"><?php echo date('M j, g:i a', strtotime($n['created_at'])); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        </div>
        <div class="px-3 py-2 border-t mt-2">
            <div class="text-xs fw-600 text-muted text-uppercase mb-2">Quick Actions</div>
            <div class="flex-col gap-2">
                <a href="<?php echo $role === 'blood_staff' ? 'create_blood_request.php' : 'create_oxygen_request.php'; ?>" class="btn btn-primary btn-full"><i class="fa fa-plus-circle"></i> Create Emergency Request</a>
                <a href="<?php echo $role === 'blood_staff' ? 'view_blood_stock.php' : 'view_oxygen_stock.php'; ?>" class="btn btn-secondary btn-full"><i class="fa fa-eye"></i> View <?php echo $role === 'blood_staff' ? 'Blood' : 'Oxygen'; ?> Stock</a>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
