<?php
$page_title = "Transfer Tracking";
require_once __DIR__ . '/../includes/header.php';
require_role('admin');
$h = $_SESSION['hospital_id'];
$filter = $_GET['status'] ?? '';

$sql = "SELECT t.*, hf.hospital_name as from_name, ht.hospital_name as to_name, u.full_name as receiver_user_name 
        FROM transfers t 
        JOIN hospitals hf ON t.from_hospital_id=hf.hospital_id 
        JOIN hospitals ht ON t.to_hospital_id=ht.hospital_id 
        LEFT JOIN users u ON t.received_by=u.user_id 
        WHERE (t.from_hospital_id=$h OR t.to_hospital_id=$h)";

if ($filter) {
    $sql .= " AND t.status='" . $conn->real_escape_string($filter) . "'";
}
$sql .= " ORDER BY FIELD(t.status,'In Transit','Pending','Completed','Cancelled'), t.transfer_date DESC";
$transfers = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

$counts = [];
foreach (['Pending','In Transit','Completed','Cancelled'] as $s) {
    $counts[$s] = $conn->query("SELECT COUNT(*) FROM transfers WHERE (from_hospital_id=$h OR to_hospital_id=$h) AND status='".addslashes($s)."'")->fetch_row()[0];
}
$total_count = array_sum($counts);
?>
<div class="page-header">
    <div>
        <div class="page-header-title">Transfer Tracking & Coordination</div>
        <div class="page-header-subtitle">Track and verify inter-hospital resource transfers in real-time</div>
    </div>
</div>

<div class="dashboard-grid grid-3-col mb-4">
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-label">Active Transfers (In Transit)</div>
            <div class="stat-card-value text-info"><?php echo $counts['In Transit']; ?></div>
            <div class="stat-card-sub">En route between facilities</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-label">Pending Dispatch</div>
            <div class="stat-card-value text-warning"><?php echo $counts['Pending']; ?></div>
            <div class="stat-card-sub">Assigned driver awaiting departure</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <div class="stat-card-label">Completed Transfers</div>
            <div class="stat-card-value text-success"><?php echo $counts['Completed']; ?></div>
            <div class="stat-card-sub">Delivered & inventory updated</div>
        </div>
    </div>
</div>

<div class="flex-wrap-gap mb-4">
    <a href="transfers.php" class="btn <?php echo !$filter ? 'btn-primary' : 'btn-secondary'; ?>">All Transfers (<?php echo $total_count; ?>)</a>
    <?php foreach ($counts as $s => $c): ?>
    <a href="?status=<?php echo urlencode($s); ?>" class="btn <?php echo $filter === $s ? 'btn-primary' : 'btn-secondary'; ?>">
        <?php echo $s; ?> (<?php echo $c; ?>)
    </a>
    <?php endforeach; ?>
</div>

<div class="panel">
    <div class="panel-header">
        <span class="panel-title">Inter-Hospital Transfer Records Log</span>
        <span class="text-sm text-muted">Click <strong>Track</strong> on any row to view real-time timeline</span>
    </div>
    <?php if (empty($transfers)): ?>
    <div class="empty-state">
        <p>No transfer records found for this filter.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Transfer ID</th>
                    <th>Providing (From)</th>
                    <th>Receiving (To)</th>
                    <th>Resource</th>
                    <th>Qty</th>
                    <th>Driver</th>
                    <th>Vehicle No</th>
                    <th>Status</th>
                    <th>Created Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transfers as $t): 
                    $tid_str = 'T-' . str_pad($t['transfer_id'], 3, '0', STR_PAD_LEFT);
                    $is_from_me = ($t['from_hospital_id'] == $h);
                    $is_to_me = ($t['to_hospital_id'] == $h);
                    $status = $t['status'];
                    
                    $transfer_json = [
                        'transfer_id'    => $t['transfer_id'],
                        'tid_str'        => $tid_str,
                        'from_name'      => $t['from_name'],
                        'to_name'        => $t['to_name'],
                        'is_from_me'     => $is_from_me,
                        'is_to_me'       => $is_to_me,
                        'resource_type'  => $t['resource_type'],
                        'blood_group'    => $t['blood_group'],
                        'cylinder_type'  => $t['cylinder_type'],
                        'quantity'       => $t['quantity'],
                        'driver_name'    => $t['driver_name'],
                        'vehicle_number' => $t['vehicle_number'],
                        'status'         => $t['status'],
                        'transfer_date'  => date('M j, Y g:i a', strtotime($t['transfer_date'])),
                        'received_date'  => $t['received_date'] ? date('M j, Y g:i a', strtotime($t['received_date'])) : 'Awaiting Delivery'
                    ];
                    $json_data_attr = htmlspecialchars(json_encode($transfer_json), ENT_QUOTES, 'UTF-8');
                ?>
                <tr>
                    <td class="fw-700"><code><?php echo $tid_str; ?></code></td>
                    <td>
                        <?php echo sanitize($t['from_name']); ?>
                        <?php if ($is_from_me): ?><span class="badge badge-info badge-sm">You</span><?php endif; ?>
                    </td>
                    <td>
                        <?php echo sanitize($t['to_name']); ?>
                        <?php if ($is_to_me): ?><span class="badge badge-success badge-sm">You</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($t['resource_type'] === 'Blood'): ?>
                            <span class="text-danger fw-700"><?php echo sanitize($t['blood_group']); ?></span> Blood
                        <?php else: ?>
                            <?php echo sanitize($t['cylinder_type']); ?> Oxygen
                        <?php endif; ?>
                    </td>
                    <td><strong><?php echo $t['quantity']; ?></strong></td>
                    <td><?php echo sanitize($t['driver_name']); ?></td>
                    <td><code><?php echo sanitize($t['vehicle_number']); ?></code></td>
                    <td>
                        <span class="badge <?php echo match($status) { 'Completed'=>'badge-success','In Transit'=>'badge-info','Cancelled'=>'badge-danger',default=>'badge-warning' }; ?>">
                            <?php echo $status; ?>
                        </span>
                    </td>
                    <td class="text-sm text-muted"><?php echo date('M j, Y', strtotime($t['transfer_date'])); ?></td>
                    <td>
                        <div class="action-btn-group">
                            <button type="button" class="btn btn-sm btn-secondary" onclick='openTrackModal(<?php echo json_encode($transfer_json); ?>)'>
                                <i class="fa fa-eye"></i> Track
                            </button>
                            <?php if ($status === 'Pending' && $is_from_me): ?>
                            <form action="../actions/update_transfer_status.php" method="POST" class="d-inline">
                                <input type="hidden" name="transfer_id" value="<?php echo $t['transfer_id']; ?>">
                                <input type="hidden" name="new_status" value="In Transit">
                                <button type="submit" class="btn btn-sm btn-primary" data-confirm="Dispatch this resource and mark In Transit?">
                                    <i class="fa fa-truck"></i> Dispatch
                                </button>
                            </form>
                            <?php elseif ($status === 'In Transit' && $is_to_me): ?>
                            <form action="../actions/update_transfer_status.php" method="POST" class="d-inline">
                                <input type="hidden" name="transfer_id" value="<?php echo $t['transfer_id']; ?>">
                                <input type="hidden" name="new_status" value="Completed">
                                <button type="submit" class="btn btn-sm btn-success" data-confirm="Confirm receipt? Stock will be added to your inventory.">
                                    <i class="fa fa-check"></i> Received
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>


<div id="trackModal" class="modal-overlay">
    <div class="modal-box modal-lg">
        <div class="modal-header">
            <div class="flex-center-gap">
                <span id="trk_tid" class="transfer-id-tag">T-000</span>
                <span id="trk_badge" class="badge badge-info badge-lg">In Transit</span>
            </div>
            <button class="modal-close" data-modal-close="trackModal">&times;</button>
        </div>

     
        <div class="transfer-route-banner">
            <div class="transfer-hosp-box">
                <span class="transfer-hosp-role">Providing (Sending)</span>
                <span id="trk_from" class="transfer-hosp-name">Hospital B</span>
            </div>

            <div class="transfer-arrow-divider">
                <div class="transfer-arrow-line"></div>
                <i class="fa fa-arrow-right"></i>
                <div class="transfer-arrow-line"></div>
            </div>

            <div class="transfer-hosp-box text-right">
                <span class="transfer-hosp-role">Receiving (Destination)</span>
                <span id="trk_to" class="transfer-hosp-name justify-end">Hospital A</span>
            </div>
        </div>

      
        <div class="transfer-timeline-container">
            <div class="progress-steps">
                <div class="step">
                    <div class="flex-col-center">
                        <div id="step_dot_1" class="step-dot done"><i class="fa fa-check"></i></div>
                        <div id="step_lbl_1" class="step-label text-success fw-600"> Accepted</div>
                    </div>
                    <div id="step_line_1" class="step-line done"></div>
                </div>

                <div class="step">
                    <div class="flex-col-center">
                        <div id="step_dot_2" class="step-dot done"><i class="fa fa-check"></i></div>
                        <div id="step_lbl_2" class="step-label text-success fw-600">Dispatched</div>
                    </div>
                    <div id="step_line_2" class="step-line done"></div>
                </div>

                <div class="step">
                    <div class="flex-col-center">
                        <div id="step_dot_3" class="step-dot active"><i class="fa fa-truck"></i></div>
                        <div id="step_lbl_3" class="step-label text-info fw-600">In Transit</div>
                    </div>
                    <div id="step_line_3" class="step-line"></div>
                </div>

                <div class="step">
                    <div class="flex-col-center">
                        <div id="step_dot_4" class="step-dot pending">4</div>
                        <div id="step_lbl_4" class="step-label text-muted">Confirm Receipt</div>
                    </div>
                </div>
            </div>
        </div>

    
        <div class="transfer-info-grid">
            <div class="transfer-info-item">
                <div class="transfer-info-lbl">Resource</div>
                <div id="trk_res" class="transfer-info-val text-danger fw-700">Blood</div>
            </div>
            <div class="transfer-info-item">
                <div class="transfer-info-lbl">Quantity</div>
                <div id="trk_qty" class="transfer-info-val">5 Units</div>
            </div>
            <div class="transfer-info-item">
                <div class="transfer-info-lbl">Driver Name</div>
                <div id="trk_driver" class="transfer-info-val">Driver</div>
            </div>
            <div class="transfer-info-item">
                <div class="transfer-info-lbl">Vehicle Number</div>
                <div id="trk_veh" class="transfer-info-val"><code>WP-1234</code></div>
            </div>
            <div class="transfer-info-item">
                <div class="transfer-info-lbl">Created Date</div>
                <div id="trk_cdate" class="transfer-info-val text-sm">Date</div>
            </div>
            <div class="transfer-info-item">
                <div class="transfer-info-lbl">Received Date</div>
                <div id="trk_rdate" class="transfer-info-val text-sm">Date</div>
            </div>
        </div>

        
        <div class="transfer-action-footer">
            <div id="trk_footer_msg" class="text-sm text-muted">
                Status updates in real-time.
            </div>
            <div id="trk_action_btn_container">
                
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="../assets/js/admin/admin_transfers.js?v=<?php echo time(); ?>"></script>

