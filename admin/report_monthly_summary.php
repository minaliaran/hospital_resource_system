<?php
$page_title = "Monthly Resource Summary";
require_once __DIR__ . '/../includes/header.php';
require_role('admin');
$my_h = $_SESSION['hospital_id'];
$h = $my_h;
$selected_hosp_name = get_hospital_name($my_h, $conn); 
$start_of_month = date('Y-m-01 00:00:00');
$end_of_month   = date('Y-m-t 23:59:59');
$blood_sent     = $conn->query("SELECT COALESCE(SUM(quantity),0) FROM transfers WHERE from_hospital_id = $h AND status = 'Completed' AND resource_type = 'Blood'  AND transfer_date BETWEEN '$start_of_month' AND '$end_of_month'")->fetch_row()[0];
$oxy_sent       = $conn->query("SELECT COALESCE(SUM(quantity),0) FROM transfers WHERE from_hospital_id = $h AND status = 'Completed' AND resource_type = 'Oxygen' AND transfer_date BETWEEN '$start_of_month' AND '$end_of_month'")->fetch_row()[0];
$blood_received = $conn->query("SELECT COALESCE(SUM(quantity),0) FROM transfers WHERE to_hospital_id = $h AND status = 'Completed' AND resource_type = 'Blood'  AND transfer_date BETWEEN '$start_of_month' AND '$end_of_month'")->fetch_row()[0];
$oxy_received   = $conn->query("SELECT COALESCE(SUM(quantity),0) FROM transfers WHERE to_hospital_id = $h AND status = 'Completed' AND resource_type = 'Oxygen' AND transfer_date BETWEEN '$start_of_month' AND '$end_of_month'")->fetch_row()[0];
$mr_hosp_q = $conn->query("SELECT h2.hospital_name, COUNT(*) as count FROM external_requests er JOIN hospitals h2 ON er.receiver_hospital_id = h2.hospital_id WHERE er.sender_hospital_id = $h GROUP BY er.receiver_hospital_id ORDER BY count DESC LIMIT 1")->fetch_assoc();
$most_requested_hospital = $mr_hosp_q ? $mr_hosp_q['hospital_name'] . " (" . $mr_hosp_q['count'] . " requests)" : "N/A";
$mr_bg_q = $conn->query("SELECT blood_group, COUNT(*) as count FROM requests WHERE hospital_id = $h AND resource_type = 'Blood' AND blood_group IS NOT NULL GROUP BY blood_group ORDER BY count DESC LIMIT 1")->fetch_assoc();
$most_requested_blood = $mr_bg_q ? $mr_bg_q['blood_group'] . " (" . $mr_bg_q['count'] . " times)" : "N/A";
$mr_oxy_q = $conn->query("SELECT cylinder_type, COUNT(*) as count FROM requests WHERE hospital_id = $h AND resource_type = 'Oxygen' AND cylinder_type IS NOT NULL GROUP BY cylinder_type ORDER BY count DESC LIMIT 1")->fetch_assoc();
$most_requested_oxy = $mr_oxy_q ? $mr_oxy_q['cylinder_type'] . " Cylinder (" . $mr_oxy_q['count'] . " times)" : "N/A";
$oos_blood  = $conn->query("SELECT blood_group, component FROM blood_stock WHERE hospital_id = $h AND quantity = 0 ORDER BY blood_group")->fetch_all(MYSQLI_ASSOC);
$oos_oxygen = $conn->query("SELECT cylinder_type FROM oxygen_stock WHERE hospital_id = $h AND quantity = 0 ORDER BY cylinder_type")->fetch_all(MYSQLI_ASSOC);
$req_pending   = $conn->query("SELECT COUNT(*) FROM requests WHERE hospital_id = $h AND status = 'Pending'")->fetch_row()[0];
$req_approved  = $conn->query("SELECT COUNT(*) FROM requests WHERE hospital_id = $h AND status = 'Approved'")->fetch_row()[0];
$req_rejected  = $conn->query("SELECT COUNT(*) FROM requests WHERE hospital_id = $h AND status = 'Rejected'")->fetch_row()[0];
$req_completed = $conn->query("SELECT COUNT(*) FROM requests WHERE hospital_id = $h AND status = 'Completed'")->fetch_row()[0];
$blood_added_events = $conn->query("SELECT COUNT(*) FROM audit_logs al JOIN users u ON al.user_id = u.user_id WHERE u.hospital_id = $h AND al.action = 'add_blood_stock'  AND al.created_at BETWEEN '$start_of_month' AND '$end_of_month'")->fetch_row()[0];
$oxy_added_events   = $conn->query("SELECT COUNT(*) FROM audit_logs al JOIN users u ON al.user_id = u.user_id WHERE u.hospital_id = $h AND al.action = 'add_oxygen_stock' AND al.created_at BETWEEN '$start_of_month' AND '$end_of_month'")->fetch_row()[0];
$pdf_rows = [
    ['Stock Flows', 'Blood Units Sent Out', (string)$blood_sent],
    ['Stock Flows', 'Blood Units Received', (string)$blood_received],
    ['Stock Flows', 'Blood Add Actions Count', (string)$blood_added_events],
    ['Stock Flows', 'Oxygen Cylinders Sent Out', (string)$oxy_sent],
    ['Stock Flows', 'Oxygen Cylinders Received', (string)$oxy_received],
    ['Stock Flows', 'Oxygen Add Actions Count', (string)$oxy_added_events],
    ['Key Indicators', 'Most Requested Partner Hospital', strip_tags($most_requested_hospital)],
    ['Key Indicators', 'Most Requested Blood Group', strip_tags($most_requested_blood)],
    ['Key Indicators', 'Most Requested Oxygen Type', strip_tags($most_requested_oxy)],
    ['Request Metrics', 'Pending Requests', (string)$req_pending],
    ['Request Metrics', 'Approved Requests', (string)$req_approved],
    ['Request Metrics', 'Rejected Requests', (string)$req_rejected],
    ['Request Metrics', 'Completed Requests', (string)$req_completed]
];
$oos_blood_text = empty($oos_blood) ? 'None' : implode(', ', array_map(fn($ob) => $ob['blood_group'] . ' (' . $ob['component'] . ')', $oos_blood));
$oos_oxygen_text = empty($oos_oxygen) ? 'None' : implode(', ', array_map(fn($oo) => $oo['cylinder_type'], $oos_oxygen));
$pdf_notes = "This monthly summary report provides a detailed view of resource flows, key request indicators, and metrics for " . $selected_hosp_name . ".\n\n" .
             "Out-of-Stock Blood Groups: " . $oos_blood_text . "\n" .
             "Out-of-Stock Oxygen Cylinders: " . $oos_oxygen_text;

$pdf_config = [
    'filename'       => 'Monthly_Resource_Summary_' . date('Y-m-d') . '.pdf',
    'title'          => 'Monthly Resource Summary',
    'hospital'       => sanitize($selected_hosp_name),
    'generatedBy'    => sanitize($full_name),
    'date'           => date('d F Y, H:i'),
    'filters'        => [
        ['label' => 'Report Period', 'value' => date('F Y')]
    ],
    'summary'        => [
        ['label' => 'Blood Sent', 'value' => (string)$blood_sent],
        ['label' => 'Blood Recv', 'value' => (string)$blood_received],
        ['label' => 'Oxy Sent',   'value' => (string)$oxy_sent],
        ['label' => 'Oxy Recv',   'value' => (string)$oxy_received]
    ],
    'chartIds'       => [],
    'tableHeaders'   => ['Category', 'Metric / Detail', 'Value'],
    'colWidths'      => [40, 100, 40],
    'tableRows'      => $pdf_rows,
    'notes'          => $pdf_notes
];
?>
<div class="mb-3">
    <a href="reports.php" class="text-link text-sm"><i class="fa fa-arrow-left"></i> Back to Reports</a>
</div>
<div class="page-header">
    <div>
        <div class="page-header-title">Monthly Resource Summary</div>
        <div class="page-header-subtitle">Viewing: <strong><?php echo sanitize($selected_hosp_name); ?></strong> &mdash; <?php echo date('F Y'); ?></div>
    </div>
    <button class="btn btn-secondary btn-print"><i class="fa fa-print"></i> Print</button>
    <button class="btn btn-primary" id="monthlySummaryPdfBtn" data-pdf-config='<?php echo json_encode($pdf_config, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'><i class="fa fa-file-pdf"></i> Download PDF</button>
</div>

<div class="page-row">
    <div class="list-gap-col">
        <div class="panel">
            <div class="panel-header"><span class="panel-title"><i class="fa fa-exchange-alt text-primary-icon"></i> Monthly Stock Flows (In / Out)</span></div>
            <div class="panel-body grid-2-col">
                <div class="flow-box">
                    <div class="flow-box-title"> Blood Flow</div>
                    <div class="flow-box-row">Units Sent Out: <strong class="text-danger"><?php echo $blood_sent; ?></strong></div>
                    <div class="flow-box-row">Units Received: <strong class="text-success"><?php echo $blood_received; ?></strong></div>
                    <div class="flow-box-sub">Add actions: <?php echo $blood_added_events; ?> times this month</div>
                </div>
                <div class="flow-box">
                    <div class="flow-box-title"> Oxygen Flow</div>
                    <div class="flow-box-row">Cylinders Sent Out: <strong class="text-danger"><?php echo $oxy_sent; ?></strong></div>
                    <div class="flow-box-row">Cylinders Received: <strong class="text-success"><?php echo $oxy_received; ?></strong></div>
                    <div class="flow-box-sub">Add actions: <?php echo $oxy_added_events; ?> times this month</div>
                </div>
            </div>
        </div>
        <div class="panel">
            <div class="panel-header"><span class="panel-title"><i class="fa fa-star text-accent-icon"></i> Most Requested Indicators</span></div>
            <div class="panel-body list-gap-col">
                <div class="list-gap-row">
                    <span class="text-md text-muted">Most Requested Partner Hospital:</span>
                    <strong class="text-md"><?php echo sanitize($most_requested_hospital); ?></strong>
                </div>
                <div class="list-gap-row">
                    <span class="text-md text-muted">Most Requested Blood Group:</span>
                    <strong class="text-md text-danger"><?php echo sanitize($most_requested_blood); ?></strong>
                </div>
                <div class="flex-between">
                    <span class="text-md text-muted">Most Requested Oxygen Type:</span>
                    <strong class="text-md text-info"><?php echo sanitize($most_requested_oxy); ?></strong>
                </div>
            </div>
        </div>
    </div>
    <div class="list-gap-col">
        <div class="panel">
            <div class="panel-header"><span class="panel-title"><i class="fa fa-exclamation-triangle text-danger-icon"></i> Out of Stock Resources</span></div>
            <div class="panel-body">
                <div class="mb-3">
                    <strong class="oos-title">Blood Groups (Qty = 0)</strong>
                    <?php if (empty($oos_blood)): ?>
                        <span class="text-sm text-success"><i class="fa fa-check-circle"></i> None</span>
                    <?php else: ?>
                        <div class="oos-badge-group">
                        <?php foreach ($oos_blood as $ob): ?>
                            <span class="badge badge-danger"><?php echo sanitize($ob['blood_group']); ?> (<?php echo sanitize($ob['component']); ?>)</span>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <strong class="oos-title">Oxygen Cylinders (Qty = 0)</strong>
                    <?php if (empty($oos_oxygen)): ?>
                        <span class="text-sm text-success"><i class="fa fa-check-circle"></i> None</span>
                    <?php else: ?>
                        <div class="oos-badge-group">
                        <?php foreach ($oos_oxygen as $oo): ?>
                            <span class="badge badge-danger"><?php echo sanitize($oo['cylinder_type']); ?></span>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="panel">
            <div class="panel-header"><span class="panel-title"><i class="fa fa-file-invoice text-primary-icon"></i> Staff Request Metrics</span></div>
            <div class="panel-body metric-grid-2">
                <div class="metric-item"><div class="metric-val"><?php echo $req_pending; ?></div><div class="metric-lbl">PENDING</div></div>
                <div class="metric-item"><div class="metric-val text-success"><?php echo $req_approved; ?></div><div class="metric-lbl">APPROVED</div></div>
                <div class="metric-item"><div class="metric-val text-danger"><?php echo $req_rejected; ?></div><div class="metric-lbl">REJECTED</div></div>
                <div class="metric-item"><div class="metric-val text-info"><?php echo $req_completed; ?></div><div class="metric-lbl">COMPLETED</div></div>
            </div>
        </div>
    </div>
</div>
<script src="../assets/js/vendor/jspdf.min.js"></script>
<script src="../assets/js/reports/pdf_report.js"></script>
<script src="../assets/js/reports/report_pdf_init.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
