<?php
$page_title = "Other Hospital Availability";
require_once __DIR__ . '/../includes/header.php';
require_role('admin');
$h = $_SESSION['hospital_id'];
$req_id = intval($_GET['req_id'] ?? 0);

$hospitals = $conn->query("SELECT h.hospital_id, h.hospital_name, h.email, h.phone, h.address,
    
    (SELECT COALESCE(SUM(quantity),0) FROM blood_stock WHERE hospital_id=h.hospital_id AND blood_group='A+') as ap,
    (SELECT COALESCE(SUM(quantity),0) FROM blood_stock WHERE hospital_id=h.hospital_id AND blood_group='A-') as am,
    (SELECT COALESCE(SUM(quantity),0) FROM blood_stock WHERE hospital_id=h.hospital_id AND blood_group='B+') as bp,
    (SELECT COALESCE(SUM(quantity),0) FROM blood_stock WHERE hospital_id=h.hospital_id AND blood_group='B-') as bm,
    (SELECT COALESCE(SUM(quantity),0) FROM blood_stock WHERE hospital_id=h.hospital_id AND blood_group='AB+') as abp,
    (SELECT COALESCE(SUM(quantity),0) FROM blood_stock WHERE hospital_id=h.hospital_id AND blood_group='AB-') as abm,
    (SELECT COALESCE(SUM(quantity),0) FROM blood_stock WHERE hospital_id=h.hospital_id AND blood_group='O+') as op,
    (SELECT COALESCE(SUM(quantity),0) FROM blood_stock WHERE hospital_id=h.hospital_id AND blood_group='O-') as om,
    (SELECT COALESCE(SUM(quantity),0) FROM blood_stock WHERE hospital_id=h.hospital_id) as blood_total,

    (SELECT COALESCE(SUM(quantity),0) FROM oxygen_stock WHERE hospital_id=h.hospital_id AND cylinder_type='Small') as oxy_small,
    (SELECT COALESCE(SUM(quantity),0) FROM oxygen_stock WHERE hospital_id=h.hospital_id AND cylinder_type='Medium') as oxy_medium,
    (SELECT COALESCE(SUM(quantity),0) FROM oxygen_stock WHERE hospital_id=h.hospital_id AND cylinder_type='Large') as oxy_large,
    (SELECT COALESCE(SUM(quantity),0) FROM oxygen_stock WHERE hospital_id=h.hospital_id AND cylinder_type='Portable') as oxy_portable,
    (SELECT COALESCE(SUM(quantity),0) FROM oxygen_stock WHERE hospital_id=h.hospital_id AND cylinder_type='E-Type') as oxy_etype,
    (SELECT COALESCE(SUM(quantity),0) FROM oxygen_stock WHERE hospital_id=h.hospital_id AND cylinder_type='D-Type') as oxy_dtype,
    (SELECT COALESCE(SUM(quantity),0) FROM oxygen_stock WHERE hospital_id=h.hospital_id AND cylinder_type='J-Type') as oxy_jtype,
    (SELECT COALESCE(SUM(quantity),0) FROM oxygen_stock WHERE hospital_id=h.hospital_id) as oxy_total,
    (SELECT updated_at FROM oxygen_stock WHERE hospital_id=h.hospital_id ORDER BY updated_at DESC LIMIT 1) as last_updated
    FROM hospitals h WHERE h.hospital_id != $h AND h.status='active' ORDER BY h.hospital_name")->fetch_all(MYSQLI_ASSOC);

$total_ext_blood = array_sum(array_column($hospitals, 'blood_total'));
$total_ext_oxy = array_sum(array_column($hospitals, 'oxy_total'));
$hospital_count = count($hospitals);
?>

<div class="page-header">
    <div>
        <div class="page-header-title">Other Hospital Availability</div>
        <div class="page-header-subtitle">Real-time availability of all Blood Groups and Oxygen Cylinder types across partner hospitals</div>
    </div>
    <?php if ($req_id): ?>
    <a href="request_details.php?id=<?php echo $req_id; ?>" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Back to Request</a>
    <?php endif; ?>
</div>

<div class="dashboard-grid grid-3-col mb-4">
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-label">Partner Hospitals</div>
            <div class="stat-card-value"><?php echo $hospital_count; ?></div>
        </div>
        <div class="stat-card-icon icon-transfer"><i class="fa fa-hospital"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-label">Total Network Blood Stock</div>
            <div class="stat-card-value"><?php echo $total_ext_blood; ?> <span class="text-xs text-muted">units</span></div>
        </div>
        <div class="stat-card-icon icon-blood"><i class="fa fa-tint"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-label">Total Network Oxygen Stock</div>
            <div class="stat-card-value"><?php echo $total_ext_oxy; ?> <span class="text-xs text-muted">cylinders</span></div>
        </div>
        <div class="stat-card-icon icon-oxygen"><i class="fa fa-lungs"></i></div>
    </div>
</div>


<div class="tab-navigation">
    <button type="button" class="tab-btn active" data-tab="tab-overview"><i class="fa fa-layer-group"></i> All Overview</button>
    <button type="button" class="tab-btn" data-tab="tab-blood"><i class="fa fa-tint text-danger"></i> Blood Stock by Group</button>
    <button type="button" class="tab-btn" data-tab="tab-oxygen"><i class="fa fa-lungs text-info"></i> Oxygen by Cylinder Type</button>
</div>


<div id="tab-overview" class="tab-content-panel">
    <div class="panel">
        <div class="panel-header">
            <span class="panel-title"><i class="fa fa-hospital-user text-primary-icon"></i> Partner Hospital Resource Overview</span>
            <span class="text-sm text-muted"><i class="fa fa-info-circle"></i> View-only real-time network stock.</span>
        </div>
        <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Hospital Name</th>
                    <th class="text-center">Blood Total</th>
                    <th class="text-center">Oxygen Total</th>
                    <th>Overall Status</th>
                    <th>Contact</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($hospitals as $hosp): 
                $overall = ($hosp['blood_total'] > 15 && $hosp['oxy_total'] > 5) ? 'Available' : (($hosp['blood_total'] <= 5 || $hosp['oxy_total'] <= 2) ? 'Critical' : 'Low');
            ?>
            <tr>
                <td>
                    <strong><?php echo sanitize($hosp['hospital_name']); ?></strong>
                    <div class="text-xs text-muted"><?php echo sanitize($hosp['address']); ?></div>
                </td>
                <td class="text-center">
                    <span class="badge <?php echo $hosp['blood_total'] <= 5 ? 'badge-danger' : ($hosp['blood_total'] <= 15 ? 'badge-warning' : 'badge-success'); ?> fw-700">
                        <?php echo $hosp['blood_total']; ?> Units
                    </span>
                </td>
                <td class="text-center">
                    <span class="badge <?php echo $hosp['oxy_total'] <= 2 ? 'badge-danger' : ($hosp['oxy_total'] <= 5 ? 'badge-warning' : 'badge-success'); ?> fw-700">
                        <?php echo $hosp['oxy_total']; ?> Cylinders
                    </span>
                </td>
                <td><span class="badge <?php echo get_status_class($overall); ?>"><?php echo $overall; ?></span></td>
                <td><span class="text-sm text-muted"><i class="fa fa-phone mr-1"></i><?php echo sanitize($hosp['phone']); ?></span></td>
                <td>
                    <button type="button" class="btn btn-sm btn-primary btn-send-req" data-hosp-id="<?php echo $hosp['hospital_id']; ?>" data-hosp-name="<?php echo htmlspecialchars($hosp['hospital_name'], ENT_QUOTES); ?>">
                        <i class="fa fa-paper-plane"></i> Send Request
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>


<div id="tab-blood" class="tab-content-panel d-none">
    <div class="panel">
        <div class="panel-header">
            <span class="panel-title"><i class="fa fa-tint text-danger"></i> Blood Group Inventory Across Hospitals (Units)</span>
            <span class="text-sm text-muted">A+, A-, B+, B-, AB+, AB-, O+, O-</span>
        </div>
        <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Hospital Name</th>
                    <th class="text-center">A+</th>
                    <th class="text-center">A-</th>
                    <th class="text-center">B+</th>
                    <th class="text-center">B-</th>
                    <th class="text-center">AB+</th>
                    <th class="text-center">AB-</th>
                    <th class="text-center">O+</th>
                    <th class="text-center">O-</th>
                    <th class="text-center">Total</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($hospitals as $hosp): ?>
            <tr>
                <td><strong><?php echo sanitize($hosp['hospital_name']); ?></strong></td>
                <td class="text-center"><span class="<?php echo $hosp['ap'] == 0 ? 'text-danger font-weight-bold' : ($hosp['ap'] <= 5 ? 'text-warning font-weight-bold' : 'font-weight-bold'); ?>"><?php echo $hosp['ap']; ?></span></td>
                <td class="text-center"><span class="<?php echo $hosp['am'] == 0 ? 'text-danger font-weight-bold' : ($hosp['am'] <= 5 ? 'text-warning font-weight-bold' : 'font-weight-bold'); ?>"><?php echo $hosp['am']; ?></span></td>
                <td class="text-center"><span class="<?php echo $hosp['bp'] == 0 ? 'text-danger font-weight-bold' : ($hosp['bp'] <= 5 ? 'text-warning font-weight-bold' : 'font-weight-bold'); ?>"><?php echo $hosp['bp']; ?></span></td>
                <td class="text-center"><span class="<?php echo $hosp['bm'] == 0 ? 'text-danger font-weight-bold' : ($hosp['bm'] <= 5 ? 'text-warning font-weight-bold' : 'font-weight-bold'); ?>"><?php echo $hosp['bm']; ?></span></td>
                <td class="text-center"><span class="<?php echo $hosp['abp'] == 0 ? 'text-danger font-weight-bold' : ($hosp['abp'] <= 5 ? 'text-warning font-weight-bold' : 'font-weight-bold'); ?>"><?php echo $hosp['abp']; ?></span></td>
                <td class="text-center"><span class="<?php echo $hosp['abm'] == 0 ? 'text-danger font-weight-bold' : ($hosp['abm'] <= 5 ? 'text-warning font-weight-bold' : 'font-weight-bold'); ?>"><?php echo $hosp['abm']; ?></span></td>
                <td class="text-center"><span class="<?php echo $hosp['op'] == 0 ? 'text-danger font-weight-bold' : ($hosp['op'] <= 5 ? 'text-warning font-weight-bold' : 'font-weight-bold'); ?>"><?php echo $hosp['op']; ?></span></td>
                <td class="text-center"><span class="<?php echo $hosp['om'] == 0 ? 'text-danger font-weight-bold' : ($hosp['om'] <= 5 ? 'text-warning font-weight-bold' : 'font-weight-bold'); ?>"><?php echo $hosp['om']; ?></span></td>
                <td class="text-center"><strong class="badge badge-success"><?php echo $hosp['blood_total']; ?></strong></td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger btn-send-req" data-res-type="Blood" data-hosp-id="<?php echo $hosp['hospital_id']; ?>" data-hosp-name="<?php echo htmlspecialchars($hosp['hospital_name'], ENT_QUOTES); ?>">
                        <i class="fa fa-tint"></i> Request Blood
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>


<div id="tab-oxygen" class="tab-content-panel d-none">
    <div class="panel">
        <div class="panel-header">
            <span class="panel-title"><i class="fa fa-lungs text-info"></i> Oxygen Inventory by Cylinder Type Across Hospitals</span>
            <span class="text-sm text-muted">Small, Medium, Large, Portable, E-Type, D-Type, J-Type</span>
        </div>
        <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Hospital Name</th>
                    <th class="text-center">Small</th>
                    <th class="text-center">Medium</th>
                    <th class="text-center">Large</th>
                    <th class="text-center">Portable</th>
                    <th class="text-center">E-Type</th>
                    <th class="text-center">D-Type</th>
                    <th class="text-center">J-Type</th>
                    <th class="text-center">Total</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($hospitals as $hosp): ?>
            <tr>
                <td><strong><?php echo sanitize($hosp['hospital_name']); ?></strong></td>
                <td class="text-center"><span class="<?php echo $hosp['oxy_small'] == 0 ? 'text-muted' : ($hosp['oxy_small'] <= 2 ? 'text-danger font-weight-bold' : 'font-weight-bold'); ?>"><?php echo $hosp['oxy_small']; ?></span></td>
                <td class="text-center"><span class="<?php echo $hosp['oxy_medium'] == 0 ? 'text-muted' : ($hosp['oxy_medium'] <= 2 ? 'text-danger font-weight-bold' : 'font-weight-bold'); ?>"><?php echo $hosp['oxy_medium']; ?></span></td>
                <td class="text-center"><span class="<?php echo $hosp['oxy_large'] == 0 ? 'text-muted' : ($hosp['oxy_large'] <= 2 ? 'text-danger font-weight-bold' : 'font-weight-bold'); ?>"><?php echo $hosp['oxy_large']; ?></span></td>
                <td class="text-center"><span class="<?php echo $hosp['oxy_portable'] == 0 ? 'text-muted' : ($hosp['oxy_portable'] <= 2 ? 'text-danger font-weight-bold' : 'font-weight-bold'); ?>"><?php echo $hosp['oxy_portable']; ?></span></td>
                <td class="text-center"><span class="<?php echo $hosp['oxy_etype'] == 0 ? 'text-muted' : ($hosp['oxy_etype'] <= 2 ? 'text-danger font-weight-bold' : 'font-weight-bold'); ?>"><?php echo $hosp['oxy_etype']; ?></span></td>
                <td class="text-center"><span class="<?php echo $hosp['oxy_dtype'] == 0 ? 'text-muted' : ($hosp['oxy_dtype'] <= 2 ? 'text-danger font-weight-bold' : 'font-weight-bold'); ?>"><?php echo $hosp['oxy_dtype']; ?></span></td>
                <td class="text-center"><span class="<?php echo $hosp['oxy_jtype'] == 0 ? 'text-muted' : ($hosp['oxy_jtype'] <= 2 ? 'text-danger font-weight-bold' : 'font-weight-bold'); ?>"><?php echo $hosp['oxy_jtype']; ?></span></td>
                <td class="text-center"><strong class="badge badge-info"><?php echo $hosp['oxy_total']; ?></strong></td>
                <td>
                    <button type="button" class="btn btn-sm btn-info btn-send-req" data-res-type="Oxygen" data-hosp-id="<?php echo $hosp['hospital_id']; ?>" data-hosp-name="<?php echo htmlspecialchars($hosp['hospital_name'], ENT_QUOTES); ?>">
                        <i class="fa fa-lungs"></i> Request Oxygen
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>



<div class="alert alert-info mt-4">
    <i class="fa fa-info-circle"></i>
    <span>Data refreshes on page load. Stock status auto-updates based on real-time hospital records. Hospital administrators can coordinate inter-hospital transfers directly using the request buttons.</span>
</div>

<div id="sendReqModal" class="modal-overlay">
    <div class="modal-box modal-md">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-paper-plane text-primary"></i> Send External Request</span>
            <button class="modal-close" data-modal-close="sendReqModal">&times;</button>
        </div>
        <form action="../actions/send_external_request.php" method="POST" data-validate>
            <input type="hidden" name="receiver_hospital_id" id="recv_hosp_id">
            <?php if ($req_id): ?><input type="hidden" name="request_id" value="<?php echo $req_id; ?>"><?php endif; ?>
            <div class="form-group">
                <label>Sending To</label>
                <input type="text" id="recv_hosp_name" class="form-control readonly-field fw-600" readonly>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Resource Type <span class="required">*</span></label>
                    <select name="resource_type" id="req_res_type" class="form-control" required>
                        <option value="">Select type</option>
                        <option value="Blood">Blood</option>
                        <option value="Oxygen">Oxygen</option>
                    </select>
                </div>
                <div class="form-group" id="blood_group_row">
                    <label>Blood Group</label>
                    <select name="blood_group" class="form-control">
                        <option value="">N/A</option>
                        <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
                        <option value="<?php echo $g; ?>"><?php echo $g; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group d-none" id="cylinder_type_row">
                    <label>Cylinder Type</label>
                    <select name="cylinder_type" class="form-control">
                        <option value="">Any</option>
                        <?php foreach (['Small', 'Medium', 'Large', 'Portable', 'E-Type', 'D-Type', 'J-Type'] as $t): ?>
                        <option value="<?php echo $t; ?>"><?php echo $t; ?> Cylinder</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Quantity <span class="required">*</span></label>
                    <input type="number" name="quantity" class="form-control" min="1" required placeholder="e.g. 10">
                </div>
                <div class="form-group">
                    <label>Priority <span class="required">*</span></label>
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
                <label>Reason / Clinical Justification <span class="required">*</span></label>
                <textarea name="reason" class="form-control" rows="3" required placeholder="Describe the clinical urgency and specific patient need..."></textarea>
            </div>
            <div class="flex-end-gap">
                <button type="button" class="btn btn-secondary" data-modal-close="sendReqModal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa fa-paper-plane"></i> Send Request</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="../assets/js/admin/admin_other_hospital_availability.js?v=<?php echo time(); ?>"></script>

