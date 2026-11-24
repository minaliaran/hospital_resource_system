<?php
$page_title = "Departmental Reports";
require_once __DIR__ . '/../includes/header.php';
require_role(['blood_staff', 'oxygen_staff']);
$h = $_SESSION['hospital_id'];
$uid = $_SESSION['user_id'];
$role = $_SESSION['role'];
$is_blood = $role === 'blood_staff';
?>
<div class="page-header">
    <div>
        <div class="page-header-title">Basic Reports & Analytics</div>
        <div class="page-header-subtitle"><?php echo $is_blood ? 'Blood Bank' : 'Oxygen Supply'; ?> department overview</div>
    </div>
</div>
<?php if ($is_blood): ?>
<div class="page-row">
    <div class="panel">
        <div class="panel-header"><span class="panel-title"><i class="fa fa-tint text-danger"></i> Blood Stock Levels Summary</span></div>
        <div class="table-responsive">
        <table class="data-table">
            <thead><tr><th>Blood Group</th><th>Component</th><th>Quantity (Units)</th><th>Status</th></tr></thead>
            <tbody>
            <?php
            $rows = $conn->query("SELECT * FROM blood_stock WHERE hospital_id=$h ORDER BY blood_group, component")->fetch_all(MYSQLI_ASSOC);
            $total = 0;
            foreach ($rows as $r): $total += $r['quantity']; ?>
            <tr>
                <td><span class="text-danger fw-700"><?php echo sanitize($r['blood_group']); ?></span></td>
                <td><?php echo sanitize($r['component']); ?></td>
                <td><strong><?php echo $r['quantity']; ?></strong></td>
                <td><span class="badge <?php echo get_status_class($r['status']); ?>"><?php echo $r['status']; ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr class="tfoot-highlight"><td colspan="2" class="tfoot-cell">TOTAL AVAILABLE</td><td class="tfoot-cell"><?php echo $total; ?> units</td><td></td></tr></tfoot>
        </table>
        </div>
    </div>
    <div class="panel">
        <div class="panel-header"><span class="panel-title"><i class="fa fa-exclamation-triangle text-warning"></i> Low & Critical Stock Alerts</span></div>
        <div class="panel-body">
            <?php
            $alerts = $conn->query("SELECT * FROM blood_stock WHERE hospital_id=$h AND status IN ('Low','Critical','Out of Stock') ORDER BY quantity ASC")->fetch_all(MYSQLI_ASSOC);
            if (empty($alerts)): ?>
            <div class="empty-state py-3"><i class="fa fa-check-circle text-success alert-icon-lg"></i><p>All stock levels are adequate.</p></div>
            <?php else: ?>
                <?php foreach ($alerts as $a): ?>
                <div class="flex-between py-1 border-b">
                    <div><span class="text-danger fw-700"><?php echo sanitize($a['blood_group']); ?></span> - <span class="text-sm text-muted"><?php echo sanitize($a['component']); ?></span></div>
                    <div class="flex-center-gap ml-auto"><strong><?php echo $a['quantity']; ?> u</strong> <span class="badge <?php echo get_status_class($a['status']); ?>"><?php echo $a['status']; ?></span></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php else: ?>
<div class="page-row">
    <div class="panel">
        <div class="panel-header"><span class="panel-title"><i class="fa fa-lungs text-info"></i> Oxygen Cylinders Summary</span></div>
        <div class="table-responsive">
        <table class="data-table">
            <thead><tr><th>Cylinder Type</th><th>Quantity</th><th>Status</th></tr></thead>
            <tbody>
            <?php
            $rows = $conn->query("SELECT * FROM oxygen_stock WHERE hospital_id=$h ORDER BY cylinder_type")->fetch_all(MYSQLI_ASSOC);
            $total = 0;
            foreach ($rows as $r): $total += $r['quantity']; ?>
            <tr>
                <td><strong><?php echo sanitize($r['cylinder_type']); ?> Cylinder</strong></td>
                <td><strong><?php echo $r['quantity']; ?></strong></td>
                <td><span class="badge <?php echo get_status_class($r['status']); ?>"><?php echo $r['status']; ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr class="tfoot-highlight"><td class="tfoot-cell">TOTAL CYLINDERS</td><td class="tfoot-cell"><?php echo $total; ?> cylinders</td><td></td></tr></tfoot>
        </table>
        </div>
    </div>
    <div class="panel">
        <div class="panel-header"><span class="panel-title"><i class="fa fa-exclamation-triangle text-warning"></i> Low & Critical Stock Alerts</span></div>
        <div class="panel-body">
            <?php
            $alerts = $conn->query("SELECT * FROM oxygen_stock WHERE hospital_id=$h AND status IN ('Low','Critical','Out of Stock') ORDER BY quantity ASC")->fetch_all(MYSQLI_ASSOC);
            if (empty($alerts)): ?>
            <div class="empty-state py-3"><i class="fa fa-check-circle text-success alert-icon-lg"></i><p>All stock levels are adequate.</p></div>
            <?php else: ?>
                <?php foreach ($alerts as $a): ?>
                <div class="flex-between py-1 border-b">
                    <div><strong><?php echo sanitize($a['cylinder_type']); ?> Cylinder</strong></div>
                    <div class="flex-center-gap ml-auto"><strong><?php echo $a['quantity']; ?></strong> <span class="badge <?php echo get_status_class($a['status']); ?>"><?php echo $a['status']; ?></span></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>
<div class="panel panel-mt">
    <div class="panel-header"><span class="panel-title"><i class="fa fa-history"></i> My Request Statistics</span></div>
    <div class="panel-body grid-4-col text-center">
        <?php
        $stats = ['Pending','Approved','Rejected','Completed'];
        foreach ($stats as $s):
            $c = $conn->query("SELECT COUNT(*) FROM requests WHERE created_by=$uid AND status='$s'")->fetch_row()[0];
        ?>
        <div class="stat-box">
            <div class="stat-box-val"><?php echo $c; ?></div>
            <div class="stat-box-lbl"><?php echo $s; ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
