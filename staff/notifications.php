<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role(['blood_staff', 'oxygen_staff']);
$h = $_SESSION['hospital_id'];
$uid = $_SESSION['user_id'];
$conn->query("UPDATE notifications SET is_read=1 WHERE hospital_id=$h AND (user_id=$uid OR user_id IS NULL)");
$page_title = "My Notifications";
require_once __DIR__ . '/../includes/header.php';
$notifs = $conn->query("SELECT * FROM notifications WHERE hospital_id=$h AND (user_id=$uid OR user_id IS NULL) ORDER BY created_at DESC LIMIT 50")->fetch_all(MYSQLI_ASSOC);
?>
<div class="page-header">
    <div>
        <div class="page-header-title">Notifications</div>
        <div class="page-header-subtitle">System notifications for <?php echo sanitize($full_name); ?></div>
    </div>
</div>
<div class="panel">
    <div class="panel-header">
        <span class="panel-title"><i class="fa fa-bell text-accent"></i> All Notifications</span>
        <span class="text-sm text-muted"><?php echo count($notifs); ?> total</span>
    </div>
    <?php if (empty($notifs)): ?>
    <div class="empty-state"><i class="fa fa-bell-slash"></i><p>No notifications.</p></div>
    <?php else: ?>
    <div class="px-3">
        <?php foreach ($notifs as $n): ?>
        <div class="notif-item">
            <div class="notif-icon notif-icon-success"><i class="fa fa-bell"></i></div>
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
