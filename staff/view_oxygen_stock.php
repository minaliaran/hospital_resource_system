<?php
$page_title = "Oxygen Stock";
require_once __DIR__ . '/../includes/header.php';
require_role(['blood_staff', 'oxygen_staff']);
$h = $_SESSION['hospital_id'];
$role = $_SESSION['role'];
$is_oxy_staff = $role === 'oxygen_staff';
$stocks = $conn->query("SELECT * FROM oxygen_stock WHERE hospital_id=$h ORDER BY cylinder_type")->fetch_all(MYSQLI_ASSOC);
$total = $conn->query("SELECT COALESCE(SUM(quantity),0) FROM oxygen_stock WHERE hospital_id=$h")->fetch_row()[0];
$avail = $conn->query("SELECT COALESCE(SUM(quantity),0) FROM oxygen_stock WHERE hospital_id=$h AND status='Available'")->fetch_row()[0];
$crit  = $conn->query("SELECT COUNT(*) FROM oxygen_stock WHERE hospital_id=$h AND status IN ('Critical','Out of Stock')")->fetch_row()[0];
?>
<div class="page-header">
    <div>
        <div class="page-header-title">Oxygen Stock <?php echo $is_oxy_staff ? '' : '— View Only'; ?></div>
        <div class="page-header-subtitle"><?php echo sanitize($hospital_name); ?> oxygen cylinder inventory</div>
    </div>
    <?php if ($is_oxy_staff): ?>
    <button class="btn btn-primary" data-modal-open="addOxyModal"><i class="fa fa-plus"></i> Add Oxygen Stock</button>
    <?php endif; ?>
</div>
<div class="dashboard-grid grid-3-col">
    <div class="stat-card"><div class="stat-card-info"><div class="stat-card-label">Total Cylinders</div><div class="stat-card-value"><?php echo $total; ?></div></div><div class="stat-card-icon icon-oxygen"><i class="fa fa-lungs"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><div class="stat-card-label">Available</div><div class="stat-card-value"><?php echo $avail; ?></div></div><div class="stat-card-icon icon-transfer"><i class="fa fa-check-circle"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><div class="stat-card-label">Critical / Empty</div><div class="stat-card-value"><?php echo $crit; ?></div></div><div class="stat-card-icon icon-request"><i class="fa fa-exclamation-triangle"></i></div></div>
</div>
<div class="panel">
    <div class="panel-header">
        <span class="panel-title"><i class="fa fa-lungs text-info"></i> Oxygen Cylinder Inventory</span>
    </div>
    <?php if (empty($stocks)): ?>
    <div class="empty-state"><i class="fa fa-lungs"></i><p>No oxygen stock records found.</p></div>
    <?php else: ?>
    <div class="table-responsive">
    <table class="data-table">
        <thead><tr><th>Cylinder Type</th><th>Quantity</th><th>Status</th><th>Last Updated</th><?php if ($is_oxy_staff): ?><th>Action</th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($stocks as $s): ?>
        <tr>
            <td><strong><?php echo sanitize($s['cylinder_type']); ?> Cylinder</strong></td>
            <td><strong><?php echo $s['quantity']; ?></strong></td>
            <td><span class="badge <?php echo get_status_class($s['status']); ?>"><?php echo $s['status']; ?></span></td>
            <td class="text-sm text-muted"><?php echo date('M j, Y H:i', strtotime($s['updated_at'])); ?></td>
            <?php if ($is_oxy_staff): ?>
            <td><button type="button" class="btn btn-sm btn-secondary btn-edit-stock" data-stock="<?php echo htmlspecialchars(json_encode($s)); ?>"><i class="fa fa-edit"></i> Update</button></td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php if ($is_oxy_staff): ?>
<div id="addOxyModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header"><span class="modal-title"><i class="fa fa-plus-circle text-primary"></i> Add Oxygen Stock</span><button class="modal-close" data-modal-close="addOxyModal">&times;</button></div>
        <form action="../actions/add_oxygen_stock.php" method="POST" data-validate>
            <div class="form-grid-2">
                <div class="form-group"><label>Cylinder Type <span class="required">*</span></label><select name="cylinder_type" class="form-control" required><option value="">Select type</option><?php foreach (['Small', 'Medium', 'Large', 'Portable', 'E-Type', 'D-Type', 'J-Type'] as $t): ?><option value="<?php echo $t; ?>"><?php echo $t; ?> Cylinder</option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Quantity <span class="required">*</span></label><input type="number" name="quantity" class="form-control" min="0" required></div>
            </div>
            <div class="flex-end-gap"><button type="button" class="btn btn-secondary" data-modal-close="addOxyModal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Add Stock</button></div>
        </form>
    </div>
</div>
<div id="editOxyModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header"><span class="modal-title"><i class="fa fa-edit"></i> Update Oxygen Stock</span><button class="modal-close" data-modal-close="editOxyModal">&times;</button></div>
        <form action="../actions/update_oxygen_stock.php" method="POST">
            <input type="hidden" name="oxygen_id" id="edit_oid">
            <div class="form-grid-2">
                <div class="form-group"><label>Cylinder Type</label><input type="text" id="edit_otype" class="form-control readonly-field" readonly></div>
                <div class="form-group"><label>New Quantity <span class="required">*</span></label><input type="number" name="quantity" id="edit_oqty" class="form-control" min="0" required></div>
            </div>
            <div class="flex-end-gap"><button type="button" class="btn btn-secondary" data-modal-close="editOxyModal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Update</button></div>
        </form>
    </div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php if ($is_oxy_staff): ?>
<script src="../assets/js/staff/view_oxygen_stock.js?v=<?php echo time(); ?>"></script>
<?php endif; ?>
