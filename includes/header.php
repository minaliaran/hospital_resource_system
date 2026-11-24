<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
check_logged_in();
$user_id     = $_SESSION['user_id'];
$role        = $_SESSION['role'];
$hospital_id = $_SESSION['hospital_id'];
$full_name   = $_SESSION['full_name'];
$hospital_name = get_hospital_name($hospital_id, $conn);
$unread_count = 0;
if ($role === 'admin') {
    $n = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE hospital_id = ? AND is_read = 0");
    $n->bind_param("i", $hospital_id);
} else {
    $n = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE hospital_id = ? AND (user_id = ? OR user_id IS NULL) AND is_read = 0");
    $n->bind_param("ii", $hospital_id, $user_id);
}
$n->execute();
$n->bind_result($unread_count);
$n->fetch();
$n->close();
$page_title = $page_title ?? "HRCS Portal";
$base = get_project_root_relative_path();
$role_labels = [
    'admin'        => 'Hospital Administrator',
    'blood_staff'  => 'Blood Bank Department',
    'oxygen_staff' => 'Oxygen Supply Department',
];
$role_label = $role_labels[$role] ?? ucfirst($role);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo sanitize($page_title); ?> — HRCS</title>
<link rel="stylesheet" href="<?php echo $base; ?>assets/css/vendor/fontawesome.min.css">
<link rel="stylesheet" href="<?php echo $base; ?>assets/css/main.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="<?php echo $base; ?>assets/css/dashboard.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="<?php echo $base; ?>assets/css/stock.css?v=<?php echo time(); ?>">
<?php if ($role === 'admin'): ?>
<link rel="stylesheet" href="<?php echo $base; ?>assets/css/admin.css?v=<?php echo time(); ?>">
<?php else: ?>
<link rel="stylesheet" href="<?php echo $base; ?>assets/css/staff.css?v=<?php echo time(); ?>">
<?php endif; ?>
<?php if (strpos($_SERVER['REQUEST_URI'], 'report') !== false): ?>
<link rel="stylesheet" href="<?php echo $base; ?>assets/css/reports.css?v=<?php echo time(); ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?php echo $base; ?>assets/css/print.css" media="print">
</head>
<body>
<div class="app-container">
<?php include __DIR__ . '/sidebar.php'; ?>
<div class="main-content">
    <header class="topbar">
        <div class="topbar-left">
            <h1><?php echo sanitize($page_title); ?></h1>
            <p><i class="fa fa-hospital text-primary"></i> <?php echo sanitize($hospital_name); ?></p>
        </div>
        <div class="topbar-right">
            <div class="topbar-date"><i class="far fa-calendar-alt"></i> <?php echo date('l, F j, Y'); ?></div>
            <a href="<?php echo $base; ?><?php echo $role === 'admin' ? 'admin' : 'staff'; ?>/notifications.php" class="notif-btn">
                <i class="fa fa-bell"></i>
                <?php if ($unread_count > 0): ?><span class="notif-dot"><?php echo $unread_count; ?></span><?php endif; ?>
            </a>
            <div class="topbar-user">
                <div class="name"><?php echo sanitize($full_name); ?></div>
                <div class="dept"><?php echo $role_label; ?></div>
            </div>
        </div>
    </header>
    <main class="content-body">
<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success"><i class="fa fa-check-circle"></i> <?php echo sanitize($_GET['success']); ?></div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i>
<?php
    $e = $_GET['error'];
    echo match($e) {
        'forbidden'      => 'Access denied. You do not have permission for this section.',
        'db'             => 'Database error occurred. Please try again.',
        'invalid'        => 'Invalid form data. Please check your input.',
        'invalid_expiry' => 'Date is invalid. Expiry date cannot be in the past.',
        'stock'          => 'Insufficient stock available.',
        default          => 'An error occurred. Please try again.'
    };
?>
</div>
<?php endif; ?>
