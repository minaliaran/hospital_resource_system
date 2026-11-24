<?php
$page_title = "Create Oxygen Emergency Request";
require_once __DIR__ . '/../includes/header.php';
require_role('oxygen_staff');
$h = $_SESSION['hospital_id'];
$oxy_summary = $conn->query("SELECT cylinder_type, quantity, status FROM oxygen_stock WHERE hospital_id=$h ORDER BY cylinder_type")->fetch_all(MYSQLI_ASSOC);
$oxy_total   = array_sum(array_column($oxy_summary, 'quantity'));
$stock_json = [];
foreach ($oxy_summary as $o) { $stock_json[$o['cylinder_type']] = $o['quantity']; }
?>
<div class="page-header">
    <div>
        <div class="page-header-title">Create Oxygen Emergency Request</div>
        <div class="page-header-subtitle">Submit an emergency oxygen request to your Hospital Admin</div>
    </div>
    <a href="my_requests.php" class="btn btn-secondary"><i class="fa fa-list-alt"></i> My Requests</a>
</div>
<div class="page-row">
    <div class="panel">
        <div class="panel-header">
            <span class="panel-title"><i class="fa fa-lungs text-info"></i> Oxygen Request Form</span>
        </div>
        <div class="panel-body">
            <div class="alert alert-info"><i class="fa fa-info-circle"></i> Your request will be reviewed by the Hospital Admin. You will receive a notification on approval or rejection.</div>
            <form action="../actions/submit_request.php" method="POST" data-validate>
                <input type="hidden" name="resource_type" value="Oxygen">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Cylinder Type <span class="required">*</span></label>
                        <select name="cylinder_type" id="cylinder_type_sel" class="form-control" required data-stock='<?php echo json_encode($stock_json); ?>' data-unit="cylinders">
                            <option value="">Select type</option>
                            <?php foreach (['Small', 'Medium', 'Large', 'Portable', 'E-Type', 'D-Type', 'J-Type'] as $t): ?>
                            <option value="<?php echo $t; ?>"><?php echo $t; ?> Cylinder</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Quantity (Cylinders) <span class="required">*</span></label>
                        <input type="number" name="quantity" class="form-control" min="1" required placeholder="e.g. 5">
                        <div class="form-hint">Current stock for selected type: <strong id="current_stock">—</strong></div>
                    </div>
                    <div class="form-group grid-col-all">
                        <label>Priority Level <span class="required">*</span></label>
                        <select name="priority" class="form-control" required>
                            <option value="">Select priority</option>
                            <option value="Emergency" class="text-danger">Critical — Immediate Response</option>
                            <option value="High">Urgent — Within 12 Hours</option>
                            <option value="Medium">Priority — Within 24 Hours</option>
                            <option value="Low">Routine — Within 48 Hours</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Clinical Reason / Ward Details <span class="required">*</span></label>
                    <textarea name="reason" class="form-control" rows="4" required placeholder="e.g. ICU oxygen supply critically low. 8 patients on ventilators. Requesting urgent resupply of Large cylinders."></textarea>
                    <div class="form-hint">Be specific — include ward name, number of patients affected, and urgency details.</div>
                </div>
                <div class="flex-end-gap mt-3">
                    <a href="my_requests.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-paper-plane"></i> Submit Request</button>
                </div>
            </form>
        </div>
    </div>
    <div class="panel h-fit">
        <div class="panel-header"><span class="panel-title"><i class="fa fa-lungs text-info"></i> Current Oxygen Stock</span></div>
        <div class="panel-sub-body">
            <?php if (empty($oxy_summary)): ?>
            <div class="empty-state py-3"><i class="fa fa-lungs"></i><p>No oxygen stock records.</p></div>
            <?php else: ?>
            <?php foreach ($oxy_summary as $o): ?>
            <div class="flex-between py-1 border-b">
                <span class="fw-600"><?php echo sanitize($o['cylinder_type']); ?> Cylinder</span>
                <div class="flex-center-gap">
                    <span class="fw-700"><?php echo $o['quantity']; ?></span>
                    <span class="badge <?php echo get_status_class($o['status']); ?>"><?php echo $o['status']; ?></span>
                </div>
            </div>
            <?php endforeach; ?>
            <div class="py-2 text-md text-muted">Total: <strong><?php echo $oxy_total; ?> cylinders</strong></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="../assets/js/create_request.js?v=<?php echo time(); ?>"></script>
