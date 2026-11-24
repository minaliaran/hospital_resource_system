<?php
$role        = $_SESSION['role'];
$full_name   = $_SESSION['full_name'];
$hospital_id = $_SESSION['hospital_id'];
$base = get_project_root_relative_path();
$cur = basename($_SERVER['SCRIPT_NAME']);
$dir = dirname($_SERVER['SCRIPT_NAME']);
$in_admin = strpos($dir, '/admin') !== false;
$in_staff = strpos($dir, '/staff') !== false;
function nav_li($cur, $file, $in_dir, $expected_dir, $icon, $label) {
    $active = ($cur === $file && $in_dir) ? ' class="active"' : '';
    return "<li{$active}><a href=\"#\"><span class=\"menu-icon\"><i class=\"fa {$icon}\"></i></span> {$label}</a></li>";
}
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo-row">
            <img src="<?php echo $base; ?>assets/images/hrcs_logo.png?v=<?php echo time(); ?>" class="sidebar-logo" alt="HRCS Logo">
            <div>
                <div class="brand-text-primary">HRCS</div>
                <div class="brand-sub-title">Colombo District</div>
            </div>
        </div>
        <div class="brand-text-secondary">Hospital Resource<br>Coordination System</div>
    </div>
    <ul class="sidebar-menu">
    <?php if ($role === 'admin'): ?>
        <li class="sidebar-section-label">MAIN</li>
        <li <?php echo ($cur === 'dashboard.php' && $in_admin) ? 'class="active"' : ''; ?>>
            <a href="<?php echo $base; ?>admin/dashboard.php"><span class="menu-icon"><i class="fa fa-th-large"></i></span> Dashboard</a>
        </li>
        <li class="sidebar-section-label">STOCK MANAGEMENT</li>
        <li <?php echo ($cur === 'blood_stock.php' && $in_admin) ? 'class="active"' : ''; ?>>
            <a href="<?php echo $base; ?>admin/blood_stock.php"><span class="menu-icon"><i class="fa fa-tint"></i></span> Blood Stock</a>
        </li>
        <li <?php echo ($cur === 'oxygen_stock.php' && $in_admin) ? 'class="active"' : ''; ?>>
            <a href="<?php echo $base; ?>admin/oxygen_stock.php"><span class="menu-icon"><i class="fa fa-lungs"></i></span> Oxygen Stock</a>
        </li>
        <li class="sidebar-section-label">REQUESTS & TRANSFERS</li>
        <li <?php echo (in_array($cur, ['staff_requests.php','request_details.php']) && $in_admin) ? 'class="active"' : ''; ?>>
            <a href="<?php echo $base; ?>admin/staff_requests.php"><span class="menu-icon"><i class="fa fa-clipboard-list"></i></span> Staff Requests</a>
        </li>
        <li <?php echo ($cur === 'other_hospital_availability.php' && $in_admin) ? 'class="active"' : ''; ?>>
            <a href="<?php echo $base; ?>admin/other_hospital_availability.php"><span class="menu-icon"><i class="fa fa-hospital-user"></i></span> Hospital Availability</a>
        </li>
        <li <?php echo ($cur === 'incoming_requests.php' && $in_admin) ? 'class="active"' : ''; ?>>
            <a href="<?php echo $base; ?>admin/incoming_requests.php"><span class="menu-icon"><i class="fa fa-inbox"></i></span> Incoming Requests</a>
        </li>
        <li <?php echo ($cur === 'outgoing_requests.php' && $in_admin) ? 'class="active"' : ''; ?>>
            <a href="<?php echo $base; ?>admin/outgoing_requests.php"><span class="menu-icon"><i class="fa fa-paper-plane"></i></span> Outgoing Requests</a>
        </li>
        <li <?php echo ($cur === 'transfers.php' && $in_admin) ? 'class="active"' : ''; ?>>
            <a href="<?php echo $base; ?>admin/transfers.php"><span class="menu-icon"><i class="fa fa-truck-moving"></i></span> Transfers</a>
        </li>
        <li class="sidebar-section-label">ADMINISTRATION</li>
        <li <?php echo ($cur === 'reports.php' && $in_admin) ? 'class="active"' : ''; ?>>
            <a href="<?php echo $base; ?>admin/reports.php"><span class="menu-icon"><i class="fa fa-chart-bar"></i></span> Reports</a>
        </li>
        <li <?php echo ($cur === 'users.php' && $in_admin) ? 'class="active"' : ''; ?>>
            <a href="<?php echo $base; ?>admin/users.php"><span class="menu-icon"><i class="fa fa-users-cog"></i></span> Staff Users</a>
        </li>
        <li <?php echo ($cur === 'notifications.php' && $in_admin) ? 'class="active"' : ''; ?>>
            <a href="<?php echo $base; ?>admin/notifications.php"><span class="menu-icon"><i class="fa fa-bell"></i></span> Notifications</a>
        </li>
        <li <?php echo ($cur === 'email_history.php' && $in_admin) ? 'class="active"' : ''; ?>>
            <a href="<?php echo $base; ?>admin/email_history.php"><span class="menu-icon"><i class="fa fa-envelope"></i></span> Email History</a>
        </li>
        <li <?php echo ($cur === 'audit_logs.php' && $in_admin) ? 'class="active"' : ''; ?>>
            <a href="<?php echo $base; ?>admin/audit_logs.php"><span class="menu-icon"><i class="fa fa-shield-alt"></i></span> Audit Logs</a>
        </li>
    <?php elseif ($role === 'blood_staff'): ?>
        <li class="sidebar-section-label">MAIN</li>
        <li <?php echo ($cur === 'dashboard.php' && $in_staff) ? 'class="active"' : ''; ?>>
            <a href="<?php echo $base; ?>staff/dashboard.php"><span class="menu-icon"><i class="fa fa-th-large"></i></span> Dashboard</a>
        </li>
        <li class="sidebar-section-label">BLOOD MANAGEMENT</li>
        <li <?php echo ($cur === 'view_blood_stock.php' && $in_staff) ? 'class="active"' : ''; ?>>
            <a href="<?php echo $base; ?>staff/view_blood_stock.php"><span class="menu-icon"><i class="fa fa-tint"></i></span> Blood Stock</a>
        </li>
        <li <?php echo ($cur === 'view_oxygen_stock.php' && $in_staff) ? 'class="active"' : ''; ?>>
            <a href="<?php echo $base; ?>staff/view_oxygen_stock.php"><span class="menu-icon"><i class="fa fa-lungs"></i></span> View Oxygen</a>
        </li>
        <li class="sidebar-section-label">REQUESTS</li>
        <li <?php echo ($cur === 'create_blood_request.php' && $in_staff) ? 'class="active"' : ''; ?>>
            <a href="<?php echo $base; ?>staff/create_blood_request.php"><span class="menu-icon"><i class="fa fa-plus-circle"></i></span> Create Request</a>
        </li>
        <li <?php echo ($cur === 'my_requests.php' && $in_staff) ? 'class="active"' : ''; ?>>
            <a href="<?php echo $base; ?>staff/my_requests.php"><span class="menu-icon"><i class="fa fa-list-alt"></i></span> My Requests</a>
        </li>
        <li <?php echo ($cur === 'notifications.php' && $in_staff) ? 'class="active"' : ''; ?>>
            <a href="<?php echo $base; ?>staff/notifications.php"><span class="menu-icon"><i class="fa fa-bell"></i></span> Notifications</a>
        </li>
        <li <?php echo ($cur === 'basic_reports.php' && $in_staff) ? 'class="active"' : ''; ?>>
            <a href="<?php echo $base; ?>staff/basic_reports.php"><span class="menu-icon"><i class="fa fa-file-alt"></i></span> Reports</a>
        </li>
    <?php elseif ($role === 'oxygen_staff'): ?>
        <li class="sidebar-section-label">MAIN</li>
        <li <?php echo ($cur === 'dashboard.php' && $in_staff) ? 'class="active"' : ''; ?>>
            <a href="<?php echo $base; ?>staff/dashboard.php"><span class="menu-icon"><i class="fa fa-th-large"></i></span> Dashboard</a>
        </li>
        <li class="sidebar-section-label">OXYGEN MANAGEMENT</li>
        <li <?php echo ($cur === 'view_oxygen_stock.php' && $in_staff) ? 'class="active"' : ''; ?>>
            <a href="<?php echo $base; ?>staff/view_oxygen_stock.php"><span class="menu-icon"><i class="fa fa-lungs"></i></span> Oxygen Stock</a>
        </li>
        <li <?php echo ($cur === 'view_blood_stock.php' && $in_staff) ? 'class="active"' : ''; ?>>
            <a href="<?php echo $base; ?>staff/view_blood_stock.php"><span class="menu-icon"><i class="fa fa-tint"></i></span> View Blood</a>
        </li>
        <li class="sidebar-section-label">REQUESTS</li>
        <li <?php echo ($cur === 'create_oxygen_request.php' && $in_staff) ? 'class="active"' : ''; ?>>
            <a href="<?php echo $base; ?>staff/create_oxygen_request.php"><span class="menu-icon"><i class="fa fa-plus-circle"></i></span> Create Request</a>
        </li>
        <li <?php echo ($cur === 'my_requests.php' && $in_staff) ? 'class="active"' : ''; ?>>
            <a href="<?php echo $base; ?>staff/my_requests.php"><span class="menu-icon"><i class="fa fa-list-alt"></i></span> My Requests</a>
        </li>
        <li <?php echo ($cur === 'notifications.php' && $in_staff) ? 'class="active"' : ''; ?>>
            <a href="<?php echo $base; ?>staff/notifications.php"><span class="menu-icon"><i class="fa fa-bell"></i></span> Notifications</a>
        </li>
        <li <?php echo ($cur === 'basic_reports.php' && $in_staff) ? 'class="active"' : ''; ?>>
            <a href="<?php echo $base; ?>staff/basic_reports.php"><span class="menu-icon"><i class="fa fa-file-alt"></i></span> Reports</a>
        </li>
    <?php endif; ?>
        <li class="logout-item">
            <a href="<?php echo $base; ?>auth/logout.php"><span class="menu-icon"><i class="fa fa-sign-out-alt"></i></span> Logout</a>
        </li>
    </ul>
    <div class="sidebar-footer">
        <div class="user-profile-small">
            <div class="user-avatar-sm"><?php echo strtoupper(substr($full_name, 0, 2)); ?></div>
            <div class="user-info-sm">
                <div class="name"><?php echo sanitize($full_name); ?></div>
                <div class="role"><?php echo str_replace('_', ' ', $role); ?></div>
            </div>
        </div>
    </div>
</aside>
