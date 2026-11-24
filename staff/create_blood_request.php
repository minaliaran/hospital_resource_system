<?php
$page_title = "Create Blood Emergency Request";
require_once __DIR__ . '/../includes/header.php';
require_role('blood_staff');
$h = $_SESSION['hospital_id'];
$blood_summary = $conn->query("SELECT blood_group, COALESCE(SUM(quantity),0) as total,
 status FROM blood_stock WHERE hospital_id=$h GROUP BY blood_group ORDER BY blood_group")->fetch_all(MYSQLI_ASSOC);
?>
<div class="page-header">
    <div>
        <div class="page-header-title">Create Blood Emergency Request</div>
        <div class="page-header-subtitle">Submit an emergency blood request to your Hospital Admin</div>
    </div>
    <a href="my_requests.php" class="btn btn-secondary"><i class="fa fa-list-alt"></i> My Requests</a>
</div>
<div class="page-row">
    <div class="panel">
        <div class="panel-header">
            <span class="panel-title"><i class="fa fa-tint text-danger"></i> Blood Request Form</span>
        </div>
        <div class="panel-body">
            <div class="alert alert-info"><i class="fa fa-info-circle"></i> Your request will be sent to the Hospital Admin for review. You will receive a notification once a decision is made.</div>
            <form action="../actions/submit_request.php" method="POST" data-validate>
                <input type="hidden" name="resource_type" value="Blood">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Blood Group Required <span class="required">*</span></label>
                        <?php
                        $stock_json = [];
                        foreach ($blood_summary as $b) { $stock_json[$b['blood_group']] = $b['total']; }
                        ?>
                        <select name="blood_group" id="blood_group_sel" class="form-control" required data-stock='<?php echo json_encode($stock_json); ?>' data-unit="units">
                            <option value="">Select blood group</option>
                            <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
                            <option value="<?php echo $g; ?>"><?php echo $g; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Component Type <span class="required">*</span></label>
                        <select name="component" class="form-control" required>
                            <option value="">Select component</option>
                            <?php foreach (['Whole Blood','RBC','Platelets','Plasma'] as $c): ?>
                            <option value="<?php echo $c; ?>"><?php echo $c; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Quantity Needed (Units) <span class="required">*</span></label>
                        <input type="number" name="quantity" class="form-control" min="1" required placeholder="e.g. 5">
                        <div class="form-hint">Current hospital stock: <strong id="current_stock">—</strong></div>
                    </div>
                    <div class="form-group">
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
                    <label>Reason / Clinical Notes <span class="required">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" required placeholder="Describe clinical urgency, patient conditions or specific case details..."></textarea>
                </div>
                <div class="flex-end-gap">
                    <button type="reset" class="btn btn-secondary">Reset</button>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-paper-plane"></i> Submit Request</button>
                </div>
            </form>
        </div>
    </div>
    <div class="panel">
        <div class="panel-header">
            <span class="panel-title"><i class="fa fa-hospital text-accent"></i> Current Hospital Stock Summary</span>
        </div>
        <div class="panel-body">
            <?php if (empty($blood_summary)): ?>
            <div class="empty-state py-2"><i class="fa fa-tint"></i><p>No stock recorded yet.</p></div>
            <?php else: ?>
            <?php foreach ($blood_summary as $b): ?>
            <div class="flex-between py-1 border-b">
                <span class="blood-group-badge"><?php echo sanitize($b['blood_group']); ?></span>
                <div class="flex-center-gap">
                    <span class="fw-700"><?php echo sanitize($b['total']); ?> units</span>
                    <span class="badge <?php echo get_status_class(get_blood_status_label($b['total'])); ?>"><?php echo get_blood_status_label($b['total']); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="../assets/js/create_request.js?v=<?php echo time(); ?>"></script>
