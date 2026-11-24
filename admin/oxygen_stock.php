<?php
$page_title = "Oxygen Stock Management";
require_once __DIR__ . '/../includes/header.php';
require_role('admin');
$h = $_SESSION['hospital_id'];
$stocks = $conn->query("SELECT * FROM oxygen_stock WHERE hospital_id=$h ORDER BY cylinder_type")->fetch_all(MYSQLI_ASSOC);
$total_o = $conn->query("SELECT COALESCE(SUM(quantity),0) FROM oxygen_stock WHERE hospital_id=$h")->fetch_row()[0];
$avail_o = $conn->query("SELECT COALESCE(SUM(quantity),0) FROM oxygen_stock WHERE hospital_id=$h AND status='Available'")->fetch_row()[0];
$crit_o  = $conn->query("SELECT COUNT(*) FROM oxygen_stock WHERE hospital_id=$h AND status IN ('Critical','Out of Stock')")->fetch_row()[0];
?>
<div class="page-header">
    <div>
        <div class="page-header-title">Oxygen Stock Management</div>
        <div class="page-header-subtitle">Manage oxygen cylinder inventory for <?php echo sanitize($hospital_name); ?></div>
    </div>
    <button class="btn btn-primary" data-modal-open="addOxyModal">
        <i class="fa fa-plus"></i> Add Oxygen Stock
    </button>
</div>
<div class="dashboard-grid grid-3-col">
    <div class="stat-card"><div class="stat-card-info"><div class="stat-card-label">Total Cylinders</div><div class="stat-card-value"><?php echo $total_o; ?></div></div><div class="stat-card-icon icon-oxygen"><i class="fa fa-lungs"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><div class="stat-card-label">Available Cylinders</div><div class="stat-card-value"><?php echo $avail_o; ?></div></div><div class="stat-card-icon icon-transfer"><i class="fa fa-check-circle"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><div class="stat-card-label">Critical / Empty</div><div class="stat-card-value"><?php echo $crit_o; ?></div></div><div class="stat-card-icon icon-request"><i class="fa fa-exclamation-triangle"></i></div></div>
</div>
<div class="panel">
    <div class="panel-header">
        <span class="panel-title"><i class="fa fa-lungs text-info"></i> Oxygen Cylinder Inventory</span>
        <span class="text-sm text-muted"><?php echo count($stocks); ?> records</span>
    </div>
    <?php if (empty($stocks)): ?>
    <div class="empty-state"><i class="fa fa-lungs"></i><p>No oxygen stock records. Add some above.</p></div>
    <?php else: ?>
    <div class="table-responsive">
    <table class="data-table">
        <thead><tr><th>Cylinder Type</th><th>Quantity</th><th>Status</th><th>Last Updated</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($stocks as $s): ?>
        <tr>
            <td><strong><?php echo sanitize($s['cylinder_type']); ?> Cylinder</strong></td>
            <td><strong><?php echo $s['quantity']; ?></strong></td>
            <td><span class="badge <?php echo get_status_class($s['status']); ?>"><?php echo $s['status']; ?></span></td>
            <td><?php echo date('Y-m-d H:i', strtotime($s['updated_at'])); ?></td>
            <td>
                <button type="button" class="btn btn-sm btn-secondary btn-edit-oxy" data-oxy="<?php echo htmlspecialchars(json_encode($s)); ?>"><i class="fa fa-edit"></i></button>
                <a href="../actions/delete_oxygen_stock.php?id=<?php echo $s['oxygen_id']; ?>" class="btn btn-sm btn-danger" data-confirm="Delete this record?"><i class="fa fa-trash"></i></a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<div class="panel panel-mt">
    <div class="panel-header"><span class="panel-title">Status Threshold Guide</span></div>
    <div class="panel-body flex-wrap-gap">
        <div class="text-center"><span class="badge badge-danger badge-lg">Out of Stock</span><div class="text-xs text-muted mt-1">0 cylinders</div></div>
        <div class="text-center"><span class="badge badge-danger badge-lg">Critical</span><div class="text-xs text-muted mt-1">1–2 cylinders</div></div>
        <div class="text-center"><span class="badge badge-warning badge-lg">Low</span><div class="text-xs text-muted mt-1">3–5 cylinders</div></div>
        <div class="text-center"><span class="badge badge-success badge-lg">Available</span><div class="text-xs text-muted mt-1">6+ cylinders</div></div>
    </div>
</div>
<div id="addOxyModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-plus-circle text-primary"></i> Add Oxygen Stock</span>
            <button class="modal-close" data-modal-close="addOxyModal">&times;</button>
        </div>
        <form action="../actions/add_oxygen_stock.php" method="POST" data-validate>
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Cylinder Type <span class="required">*</span></label>
                    <select name="cylinder_type" class="form-control" required>
                        <option value="">Select type</option>
                        <?php foreach (['Small', 'Medium', 'Large', 'Portable', 'E-Type', 'D-Type', 'J-Type'] as $t): ?>
                        <option value="<?php echo $t; ?>"><?php echo $t; ?> Cylinder</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Quantity <span class="required">*</span></label>
                    <input type="number" name="quantity" class="form-control" min="0" required placeholder="e.g. 10">
                </div>
            </div>
            <div class="flex-end-gap">
                <button type="button" class="btn btn-secondary" data-modal-close="addOxyModal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Add Stock</button>
            </div>
        </form>
    </div>
</div>
<div id="editOxyModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-edit text-primary"></i> Update Oxygen Stock</span>
            <button class="modal-close" data-modal-close="editOxyModal">&times;</button>
        </div>
        <form action="../actions/update_oxygen_stock.php" method="POST" data-validate>
            <input type="hidden" name="oxygen_id" id="edit_oxy_id">
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Cylinder Type</label>
                    <input type="text" id="edit_oxy_type" class="form-control readonly-field" readonly>
                </div>
                <div class="form-group">
                    <label>New Quantity <span class="required">*</span></label>
                    <input type="number" name="quantity" id="edit_oxy_qty" class="form-control" min="0" required>
                </div>
            </div>
            <div class="flex-end-gap">
                <button type="button" class="btn btn-secondary" data-modal-close="editOxyModal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Update</button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="../assets/js/admin/admin_oxygen_stock.js?v=<?php echo time(); ?>"></script>
