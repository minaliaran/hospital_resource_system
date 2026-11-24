<?php
$page_title = "Blood Stock";
require_once __DIR__ . '/../includes/header.php';
require_role(['blood_staff', 'oxygen_staff']);
$h = $_SESSION['hospital_id'];
$role = $_SESSION['role'];
$is_blood_staff = $role === 'blood_staff';
$search = trim($_GET['search'] ?? '');
$filter_group = $_GET['group'] ?? '';
$sql = "SELECT * FROM blood_stock WHERE hospital_id=$h";
if ($search) $sql .= " AND (blood_group LIKE '%$search%' OR component LIKE '%$search%')";
if ($filter_group) $sql .= " AND blood_group='" . $conn->real_escape_string($filter_group) . "'";
$sql .= " ORDER BY blood_group, component";
$stocks = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
$total = $conn->query("SELECT COALESCE(SUM(quantity),0) FROM blood_stock WHERE hospital_id=$h")->fetch_row()[0];
$avail = $conn->query("SELECT COALESCE(SUM(quantity),0) FROM blood_stock WHERE hospital_id=$h AND status='Available'")->fetch_row()[0];
$low_count = $conn->query("SELECT COUNT(*) FROM blood_stock WHERE hospital_id=$h AND status IN ('Low','Critical','Out of Stock')")->fetch_row()[0];
?>
<div class="page-header">
    <div>
        <div class="page-header-title">Blood Stock <?php echo $is_blood_staff ? '' : '— View Only'; ?></div>
        <div class="page-header-subtitle"><?php echo sanitize($hospital_name); ?> blood inventory</div>
    </div>
    <?php if ($is_blood_staff): ?>
    <button class="btn btn-primary" data-modal-open="addBloodModal"><i class="fa fa-plus"></i> Add Blood Stock</button>
    <?php endif; ?>
</div>
<div class="dashboard-grid grid-3-col">
    <div class="stat-card"><div class="stat-card-info"><div class="stat-card-label">Total Units</div><div class="stat-card-value"><?php echo $total; ?></div></div><div class="stat-card-icon icon-blood"><i class="fa fa-tint"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><div class="stat-card-label">Available</div><div class="stat-card-value"><?php echo $avail; ?></div></div><div class="stat-card-icon icon-transfer"><i class="fa fa-check-circle"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><div class="stat-card-label">Low / Critical</div><div class="stat-card-value"><?php echo $low_count; ?></div></div><div class="stat-card-icon icon-request"><i class="fa fa-exclamation-triangle"></i></div></div>
</div>
<div class="panel panel-mb">
    <div class="panel-body">
        <form method="GET" class="flex-wrap-gap">
            <div class="form-group m-0 flex-1 min-w-160"><label>Search</label><input type="text" name="search" class="form-control" placeholder="Group or component..." value="<?php echo sanitize($search); ?>"></div>
            <div class="form-group m-0"><label>Blood Group</label><select name="group" class="form-control"><option value="">All Groups</option><?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?><option value="<?php echo $g; ?>" <?php echo $filter_group===$g?'selected':''; ?>><?php echo $g; ?></option><?php endforeach; ?></select></div>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="view_blood_stock.php" class="btn btn-secondary">Reset</a>
        </form>
    </div>
</div>
<div class="panel">
    <div class="panel-header"><span class="panel-title"><i class="fa fa-tint text-danger"></i> Blood Inventory</span></div>
    <?php if (empty($stocks)): ?>
    <div class="empty-state"><i class="fa fa-tint"></i><p>No blood stock found.</p></div>
    <?php else: ?>
    <div class="table-responsive">
    <table class="data-table">
        <thead><tr><th>Blood Group</th><th>Component</th><th>Quantity</th><th>Status</th><th>Expiry Date</th><th>Last Updated</th><?php if ($is_blood_staff): ?><th>Action</th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($stocks as $s): ?>
        <tr>
            <td><span class="blood-group-badge"><?php echo sanitize($s['blood_group']); ?></span></td>
            <td><?php echo sanitize($s['component']); ?></td>
            <td><strong><?php echo $s['quantity']; ?></strong></td>
            <td><span class="badge <?php echo get_status_class($s['status']); ?>"><?php echo $s['status']; ?></span></td>
            <td><?php echo date('Y-m-d', strtotime($s['expiry_date'])); ?></td>
            <td class="text-sm text-muted"><?php echo date('M j, Y H:i', strtotime($s['updated_at'])); ?></td>
            <?php if ($is_blood_staff): ?>
            <td><button type="button" class="btn btn-sm btn-secondary btn-edit-stock" data-stock="<?php echo htmlspecialchars(json_encode($s)); ?>"><i class="fa fa-edit"></i> Update</button></td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<?php if ($is_blood_staff): ?>
<div id="addBloodModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header"><span class="modal-title"><i class="fa fa-plus-circle text-primary"></i> Add Blood Stock</span><button class="modal-close" data-modal-close="addBloodModal">&times;</button></div>
        <form action="../actions/add_blood_stock.php" method="POST" data-validate>
            <div class="form-grid-2">
                <div class="form-group"><label>Blood Group <span class="required">*</span></label><select name="blood_group" class="form-control" required><option value="">Select</option><?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?><option value="<?php echo $g; ?>"><?php echo $g; ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Component <span class="required">*</span></label><select name="component" class="form-control" required><option value="">Select</option><?php foreach (['Whole Blood','RBC','Platelets','Plasma'] as $c): ?><option value="<?php echo $c; ?>"><?php echo $c; ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Quantity <span class="required">*</span></label><input type="number" name="quantity" class="form-control" min="1" required></div>
                <div class="form-group"><label>Expiry Date <span class="required">*</span></label><input type="date" name="expiry_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>"></div>
            </div>
            <div class="flex-end-gap"><button type="button" class="btn btn-secondary" data-modal-close="addBloodModal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Add Stock</button></div>
        </form>
    </div>
</div>
<div id="editBloodModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header"><span class="modal-title"><i class="fa fa-edit"></i> Update Blood Stock</span><button class="modal-close" data-modal-close="editBloodModal">&times;</button></div>
        <form action="../actions/update_blood_stock.php" method="POST" data-validate>
            <input type="hidden" name="blood_id" id="edit_bid">
            <div class="form-grid-2">
                <div class="form-group"><label>Blood Group</label><input type="text" id="edit_bg" class="form-control readonly-field" readonly></div>
                <div class="form-group"><label>Component</label><input type="text" id="edit_comp" class="form-control readonly-field" readonly></div>
                <div class="form-group"><label>New Quantity <span class="required">*</span></label><input type="number" name="quantity" id="edit_qty" class="form-control" min="0" required></div>
                <div class="form-group"><label>Expiry Date <span class="required">*</span></label><input type="date" name="expiry_date" id="edit_exp" class="form-control" required min="<?php echo date('Y-m-d'); ?>"></div>
            </div>
            <div class="flex-end-gap"><button type="button" class="btn btn-secondary" data-modal-close="editBloodModal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Update</button></div>
        </form>
    </div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php if ($is_blood_staff): ?>
<script src="../assets/js/staff/view_blood_stock.js?v=<?php echo time(); ?>"></script>
<?php endif; ?>
