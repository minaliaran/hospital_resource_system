<?php
$page_title = "Blood Stock Management";
require_once __DIR__ . '/../includes/header.php';
require_role('admin');
$h = $_SESSION['hospital_id'];
$search = trim($_GET['search'] ?? '');
$filter_group = $_GET['group'] ?? '';
$filter_status = $_GET['status'] ?? '';
$sql = "SELECT * FROM blood_stock WHERE hospital_id = $h";
if ($search) $sql .= " AND (blood_group LIKE '%$search%' OR component LIKE '%$search%')";
if ($filter_group) $sql .= " AND blood_group = '" . $conn->real_escape_string($filter_group) . "'";
if ($filter_status) $sql .= " AND status = '" . $conn->real_escape_string($filter_status) . "'";
$sql .= " ORDER BY blood_group, component";
$stocks = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
$total_units  = $conn->query("SELECT COALESCE(SUM(quantity),0) FROM blood_stock WHERE hospital_id=$h")->fetch_row()[0];
$available    = $conn->query("SELECT COALESCE(SUM(quantity),0) FROM blood_stock WHERE hospital_id=$h AND status='Available'")->fetch_row()[0];
$low_count    = $conn->query("SELECT COALESCE(SUM(quantity),0) FROM blood_stock WHERE hospital_id=$h AND status='Low'")->fetch_row()[0];
$expiring     = $conn->query("SELECT COUNT(*) FROM blood_stock WHERE hospital_id=$h AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY)")->fetch_row()[0];
?>
<div class="page-header">
    <div>
        <div class="page-header-title">Blood Stock Management</div>
        <div class="page-header-subtitle">Manage blood inventory for <?php echo sanitize($hospital_name); ?></div>
    </div>
    <button class="btn btn-primary" data-modal-open="addModal">
        <i class="fa fa-plus"></i> Add Blood Stock
    </button>
</div>
<div class="dashboard-grid grid-4-col">
    <div class="stat-card"><div class="stat-card-info"><div class="stat-card-label">Total Units</div><div class="stat-card-value"><?php echo $total_units; ?></div></div><div class="stat-card-icon icon-blood"><i class="fa fa-tint"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><div class="stat-card-label">Available</div><div class="stat-card-value"><?php echo $available; ?></div></div><div class="stat-card-icon icon-transfer"><i class="fa fa-check-circle"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><div class="stat-card-label">Low Stock Units</div><div class="stat-card-value"><?php echo $low_count; ?></div></div><div class="stat-card-icon icon-request"><i class="fa fa-exclamation-triangle"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><div class="stat-card-label">Expiring (7 days)</div><div class="stat-card-value"><?php echo $expiring; ?></div></div><div class="stat-card-icon icon-incoming"><i class="fa fa-calendar-times"></i></div></div>
</div>
<div class="panel panel-mb">
    <div class="panel-body">
        <form method="GET" class="flex-wrap-gap">
            <div class="form-group m-0 flex-1 min-w-180">
                <label>Search</label>
                <input type="text" name="search" class="form-control" placeholder="Blood group, component..." value="<?php echo sanitize($search); ?>">
            </div>
            <div class="form-group m-0">
                <label>Blood Group</label>
                <select name="group" class="form-control">
                    <option value="">All Groups</option>
                    <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
                    <option value="<?php echo $g; ?>" <?php echo $filter_group === $g ? 'selected' : ''; ?>><?php echo $g; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group m-0">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="">All Status</option>
                    <?php foreach (['Available','Low','Critical','Out of Stock'] as $s): ?>
                    <option value="<?php echo $s; ?>" <?php echo $filter_status === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="blood_stock.php" class="btn btn-secondary">Reset</a>
        </form>
    </div>
</div>
<div class="panel">
    <div class="panel-header">
        <span class="panel-title"><i class="fa fa-table text-danger"></i> Blood Unit Inventory</span>
        <span class="text-sm text-muted">Showing <?php echo count($stocks); ?> records</span>
    </div>
    <?php if (empty($stocks)): ?>
    <div class="empty-state"><i class="fa fa-tint"></i><p>No blood stock records found.</p></div>
    <?php else: ?>
    <div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr><th>Blood Group</th><th>Blood Component</th><th>Available Units</th><th>Expiry Date</th><th>Status</th><th>Last Updated</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($stocks as $s): ?>
        <tr>
            <td><span class="blood-group-badge"><?php echo sanitize($s['blood_group']); ?></span></td>
            <td><?php echo sanitize($s['component']); ?></td>
            <td><strong><?php echo $s['quantity']; ?></strong></td>
            <td><?php echo date('Y-m-d', strtotime($s['expiry_date'])); ?></td>
            <td><span class="badge <?php echo get_status_class($s['status']); ?>"><?php echo $s['status']; ?></span></td>
            <td><?php echo date('Y-m-d H:i', strtotime($s['updated_at'])); ?></td>
            <td>
                <button type="button" class="btn btn-sm btn-secondary btn-edit-blood" data-blood="<?php echo htmlspecialchars(json_encode($s)); ?>"><i class="fa fa-edit"></i></button>
                <a href="../actions/delete_blood_stock.php?id=<?php echo $s['blood_id']; ?>" class="btn btn-sm btn-danger" data-confirm="Delete this blood stock record?"><i class="fa fa-trash"></i></a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<div id="addModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-plus-circle text-primary"></i> Add Blood Stock</span>
            <button class="modal-close" data-modal-close="addModal">&times;</button>
        </div>
        <form action="../actions/add_blood_stock.php" method="POST" data-validate>
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Blood Group <span class="required">*</span></label>
                    <select name="blood_group" class="form-control" required>
                        <option value="">Select group</option>
                        <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
                        <option value="<?php echo $g; ?>"><?php echo $g; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Component <span class="required">*</span></label>
                    <select name="component" class="form-control" required>
                        <option value="">Select component</option>
                        <?php foreach (['Whole Blood','RBC','Platelets','Plasma'] as $c): ?>
                        <option value="<?php echo $c; ?>"><?php echo $c; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Quantity (Units) <span class="required">*</span></label>
                    <input type="number" name="quantity" class="form-control" min="1" required placeholder="e.g. 20">
                </div>
                <div class="form-group">
                    <label>Expiry Date <span class="required">*</span></label>
                    <input type="date" name="expiry_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <div class="flex-end-gap mt-2">
                <button type="button" class="btn btn-secondary" data-modal-close="addModal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Add Stock</button>
            </div>
        </form>
    </div>
</div>
<div id="editModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-edit text-primary"></i> Update Blood Stock</span>
            <button class="modal-close" data-modal-close="editModal">&times;</button>
        </div>
        <form action="../actions/update_blood_stock.php" method="POST" data-validate>
            <input type="hidden" name="blood_id" id="edit_blood_id">
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Blood Group</label>
                    <input type="text" id="edit_blood_group" class="form-control readonly-field" readonly>
                </div>
                <div class="form-group">
                    <label>Component</label>
                    <input type="text" id="edit_component" class="form-control readonly-field" readonly>
                </div>
                <div class="form-group">
                    <label>Quantity (Units) <span class="required">*</span></label>
                    <input type="number" name="quantity" id="edit_quantity" class="form-control" min="0" required>
                </div>
                <div class="form-group">
                    <label>Expiry Date <span class="required">*</span></label>
                    <input type="date" name="expiry_date" id="edit_expiry" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <div class="flex-end-gap mt-2">
                <button type="button" class="btn btn-secondary" data-modal-close="editModal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Update Stock</button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="../assets/js/admin/admin_blood_stock.js?v=<?php echo time(); ?>"></script>
