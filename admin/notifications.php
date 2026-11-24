<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
$h = $_SESSION['hospital_id'];
$conn->query("UPDATE notifications SET is_read=1 WHERE hospital_id=$h");
$page_title = "Notifications";
require_once __DIR__ . '/../includes/header.php';
$notifs = $conn->query("SELECT * FROM notifications WHERE hospital_id=$h ORDER BY created_at DESC LIMIT 100")->fetch_all(MYSQLI_ASSOC);
?>
<div class="page-header">
    <div><div class="page-header-title">Notifications</div><div class="page-header-subtitle">System alerts and activity notifications for <?php echo sanitize($hospital_name); ?></div></div>
</div>
<div class="panel">
    <div class="panel-header"><span class="panel-title"><i class="fa fa-bell text-accent-icon"></i> All Notifications</span><span class="table-meta-text"><?php echo count($notifs); ?> notifications</span></div>
    <?php if (empty($notifs)): ?>
    <div class="empty-state"><i class="fa fa-bell-slash"></i><p>No notifications yet.</p></div>
    <?php else: ?>
    <div class="panel-sub-body">
    <?php foreach ($notifs as $n):
        $icon_map = ['blood'=>'fa-tint','oxygen'=>'fa-lungs','request'=>'fa-clipboard-list','transfer'=>'fa-truck-moving','external'=>'fa-hospital-user'];
        $icon = 'fa-bell';
        foreach ($icon_map as $k => $i) { if (stripos($n['title'], $k) !== false) { $icon = $i; break; } }
    ?>
    <div class="notif-item">
        <div class="notif-icon notif-icon-success"><i class="fa <?php echo $icon; ?>"></i></div>
        <div class="notif-content flex-1">
            <div class="notif-title"><?php echo sanitize($n['title']); ?></div>
            <div class="notif-msg"><?php echo sanitize($n['message']); ?></div>
            <div class="notif-time"><i class="far fa-clock"></i> <?php echo date('M j, Y \a\t g:i a', strtotime($n['created_at'])); ?></div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
