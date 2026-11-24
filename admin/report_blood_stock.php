<?php
$page_title = "Blood Stock Report";
require_once __DIR__ . '/../includes/header.php';
require_role(['admin', 'blood_staff']);
$h    = $_SESSION['hospital_id'];
$role = $_SESSION['role'];
$filter_group = $_GET['group'] ?? '';
$sql = "SELECT bs.* FROM blood_stock bs WHERE bs.hospital_id = $h";
if ($filter_group) {
    $sql .= " AND bs.blood_group = '" . $conn->real_escape_string($filter_group) . "'";
}
$sql .= " ORDER BY bs.blood_group, bs.component";
$rows = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
$total_units = array_sum(array_column($rows, 'quantity'));
$oos_count   = count(array_filter($rows, fn($r) => $r['quantity'] <= 0));
$low_count   = count(array_filter($rows, fn($r) => $r['status'] === 'Low' || $r['status'] === 'Critical'));
$pdf_rows = [];
foreach ($rows as $i => $r) {
    $pdf_rows[] = [
        $i + 1,
        $r['blood_group'],
        $r['component'],
        (int)$r['quantity'],
        $r['status'],
        date('Y-m-d', strtotime($r['expiry_date'])),
        date('M j, Y', strtotime($r['updated_at'])),
    ];
}
$pdf_config = [
    'filename'    => 'Blood_Stock_Report_' . date("Y-m-d") . '.pdf',
    'title'       => 'Blood Stock Report',
    'hospital'    => sanitize($hospital_name),
    'generatedBy' => sanitize($full_name),
    'date'        => date("d F Y, H:i"),
    'filters'     => [
        ['label' => 'Blood Group', 'value' => $filter_group ?: "All Groups"],
        ['label' => 'Hospital',    'value' => sanitize($hospital_name)]
    ],
    'summary'     => [
        ['label' => 'Total Records',     'value' => (string)count($rows)],
        ['label' => 'Total Blood Units',  'value' => (string)$total_units],
        ['label' => 'Out of Stock Items', 'value' => (string)$oos_count],
        ['label' => 'Low / Critical',     'value' => (string)$low_count]
    ],
    'chartIds'    => [],
    'tableHeaders'=> ['No', 'Blood Group', 'Component', 'Qty (Units)', 'Status', 'Expiry Date', 'Last Updated'],
    'colWidths'   => [10,   20,             28,          22,            22,       26,             52],
    'tableRows'   => $pdf_rows,
    'statusColIndex' => 4,
    'notes'       => 'This report shows the current blood inventory for ' . sanitize($hospital_name) . '. All blood group stock levels, components, quantities, and expiry dates are listed. Critical and out-of-stock items should be reviewed immediately by the Hospital Administrator. Blood units approaching their expiry date must be prioritized for use or disposal.'
];
?>
<div class="mb-3">
    <a href="<?php echo ($role === 'admin') ? 'reports.php' : '../staff/basic_reports.php'; ?>" class="text-link text-sm"><i class="fa fa-arrow-left"></i> Back to Reports</a>
</div>
<div class="page-header">
    <div>
        <div class="page-header-title">Blood Stock Report</div>
        <div class="page-header-subtitle">Blood inventory for <strong><?php echo sanitize($hospital_name); ?></strong></div>
    </div>
    <button class="btn btn-secondary btn-print"><i class="fa fa-print"></i> Print</button>
    <button class="btn btn-primary" id="pdfBtn" data-pdf-config='<?php echo json_encode($pdf_config, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'><i class="fa fa-file-pdf"></i> Download PDF</button>
</div>
<div class="panel panel-mb">
    <div class="panel-body">
        <form method="GET" class="filter-bar-form">
            <div class="form-group m-0">
                <label>Blood Group</label>
                <select name="group" class="form-control">
                    <option value="">All Groups</option>
                    <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
                    <option value="<?php echo $g; ?>" <?php echo $filter_group === $g ? 'selected' : ''; ?>><?php echo $g; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Apply Filter</button>
            <a href="report_blood_stock.php" class="btn btn-secondary">Reset</a>
        </form>
    </div>
</div>
<div class="panel">
    <div class="panel-header">
        <span class="panel-title"><i class="fa fa-tint text-danger-icon"></i> Blood Inventory — <?php echo sanitize($hospital_name); ?></span>
        <span class="table-meta-text">Total Records: <?php echo count($rows); ?></span>
    </div>
    <div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Blood Group</th>
                <th>Component</th>
                <th>Quantity (Units)</th>
                <th>Status</th>
                <th>Expiry Date</th>
                <th>Last Updated</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $i => $r): ?>
        <tr>
            <td class="table-row-id"><?php echo $i + 1; ?></td>
            <td><span class="text-danger-bold"><?php echo sanitize($r['blood_group']); ?></span></td>
            <td><?php echo sanitize($r['component']); ?></td>
            <td><strong><?php echo $r['quantity']; ?></strong></td>
            <td><span class="badge <?php echo get_status_class($r['status']); ?>"><?php echo $r['status']; ?></span></td>
            <td><?php echo date('Y-m-d', strtotime($r['expiry_date'])); ?></td>
            <td class="table-row-date"><?php echo date('M j, Y H:i', strtotime($r['updated_at'])); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="tfoot-highlight">
                <td colspan="3" class="tfoot-cell">TOTAL UNITS</td>
                <td class="tfoot-cell" colspan="4"><?php echo $total_units; ?> units</td>
            </tr>
        </tfoot>
    </table>
    </div>
</div>
<script src="../assets/js/vendor/jspdf.min.js"></script>
<script src="../assets/js/reports/pdf_report.js"></script>
<script src="../assets/js/reports/report_pdf_init.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
