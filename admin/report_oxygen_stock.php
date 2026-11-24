<?php
$page_title = "Oxygen Stock Report";
require_once __DIR__ . '/../includes/header.php';
require_role(['admin', 'oxygen_staff']);
$h = $_SESSION['hospital_id'];
$filter_hosp = $_GET['hospital'] ?? '';
$filter_type = $_GET['type'] ?? '';
$sql = "SELECT os.*, h.hospital_name FROM oxygen_stock os JOIN hospitals h ON os.hospital_id = h.hospital_id WHERE 1=1";
if ($filter_hosp) {
    $sql .= " AND os.hospital_id = " . intval($filter_hosp);
}
if ($filter_type) {
    $sql .= " AND os.cylinder_type = '" . $conn->real_escape_string($filter_type) . "'";
}
$sql .= " ORDER BY h.hospital_name, os.cylinder_type";
$rows = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
$hospitals = $conn->query("SELECT hospital_id, hospital_name FROM hospitals WHERE status='active'")->fetch_all(MYSQLI_ASSOC);
$total_cylinders = array_sum(array_column($rows, 'quantity'));
$oos_count       = count(array_filter($rows, fn($r) => $r['quantity'] <= 0));
$filter_hosp_name = 'All Hospitals';
if ($filter_hosp) {
    foreach ($hospitals as $hosp) {
        if ($hosp['hospital_id'] == $filter_hosp) { $filter_hosp_name = $hosp['hospital_name']; break; }
    }
}
$pdf_rows = [];
foreach ($rows as $i => $r) {
    $pdf_rows[] = [$i + 1, $r['hospital_name'], $r['cylinder_type'] . ' Cylinder', $r['quantity'], $r['status'], date('M j, Y H:i', strtotime($r['updated_at']))];
}

$pdf_config = [
    'filename'       => 'Oxygen_Stock_Report_' . date('Y-m-d') . '.pdf',
    'title'          => 'Oxygen Stock Report',
    'hospital'       => sanitize($hospital_name),
    'generatedBy'    => sanitize($full_name),
    'date'           => date('d F Y, H:i'),
    'filters'        => [
        ['label' => 'Hospital Filter', 'value' => $filter_hosp_name],
        ['label' => 'Cylinder Type',   'value' => $filter_type ?: 'All Types']
    ],
    'summary'        => [
        ['label' => 'Total Records',      'value' => (string)count($rows)],
        ['label' => 'Total Cylinders',    'value' => (string)$total_cylinders],
        ['label' => 'Out of Stock Items', 'value' => (string)$oos_count]
    ],
    'chartIds'       => [],
    'tableHeaders'   => ['No', 'Hospital', 'Cylinder Type', 'Quantity', 'Status', 'Last Updated'],
    'colWidths'      => [10, 55, 28, 18, 20, 49],
    'tableRows'      => $pdf_rows,
    'statusColIndex' => 4,
    'notes'          => 'This report shows the oxygen cylinder inventory across hospitals. Small, Medium, and Large cylinder types are listed with their current quantities and status. Critical and out-of-stock items require immediate replenishment or inter-hospital transfer.'
];
?>
<div class="mb-3">
    <a href="<?php echo ($_SESSION['role'] === 'admin') ? 'reports.php' : '../staff/basic_reports.php'; ?>" class="text-link text-sm"><i class="fa fa-arrow-left"></i> Back to Reports</a>
</div>
<div class="page-header">
    <div>
        <div class="page-header-title">Oxygen Stock Report</div>
        <div class="page-header-subtitle">Analyze oxygen cylinder availability across all active hospitals</div>
    </div>
    <button class="btn btn-secondary btn-print"><i class="fa fa-print"></i> Print</button>
    <button class="btn btn-primary" id="oxyPdfBtn" data-pdf-config='<?php echo json_encode($pdf_config, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'><i class="fa fa-file-pdf"></i> Download PDF</button>
</div>
<div class="panel panel-mb">
    <div class="panel-body">
        <form method="GET" class="filter-bar-form">
            <div class="form-group m-0 min-w-160">
                <label>Filter Hospital</label>
                <select name="hospital" class="form-control">
                    <option value="">All Hospitals</option>
                    <?php foreach ($hospitals as $hosp): ?>
                    <option value="<?php echo $hosp['hospital_id']; ?>" <?php echo $filter_hosp == $hosp['hospital_id'] ? 'selected' : ''; ?>><?php echo sanitize($hosp['hospital_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group m-0">
                <label>Cylinder Type</label>
                <select name="type" class="form-control">
                    <option value="">All Types</option>
                    <?php foreach (['Small', 'Medium', 'Large', 'Portable', 'E-Type', 'D-Type', 'J-Type'] as $t): ?>
                    <option value="<?php echo $t; ?>" <?php echo $filter_type === $t ? 'selected' : ''; ?>><?php echo $t; ?> Cylinder</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Generate Filter</button>
            <a href="report_oxygen_stock.php" class="btn btn-secondary">Reset</a>
        </form>
    </div>
</div>
<div class="panel">
    <div class="panel-header">
        <span class="panel-title"><i class="fa fa-lungs text-info-icon"></i> Oxygen Cylinder Inventory Logs</span>
        <span class="table-meta-text">Total Records: <?php echo count($rows); ?></span>
    </div>
    <div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>Hospital</th>
                <th>Cylinder Type</th>
                <th>Quantity</th>
                <th>Status</th>
                <th>Last Updated</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        $total_cylinders = 0;
        foreach ($rows as $r): 
            $total_cylinders += $r['quantity'];
            $is_own_class = ($r['hospital_id'] == $h) ? 'table-row-highlight' : '';
        ?>
        <tr class="<?php echo $is_own_class; ?>">
            <td><?php echo sanitize($r['hospital_name']); ?><?php if ($r['hospital_id'] == $h): ?> <span class="badge badge-info badge-sm">You</span><?php endif; ?></td>
            <td><strong><?php echo sanitize($r['cylinder_type']); ?> Cylinder</strong></td>
            <td><strong><?php echo $r['quantity']; ?></strong></td>
            <td><span class="badge <?php echo get_status_class($r['status']); ?>"><?php echo $r['status']; ?></span></td>
            <td class="table-row-date"><?php echo date('M j, Y H:i', strtotime($r['updated_at'])); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="tfoot-highlight">
                <td colspan="2" class="tfoot-cell">TOTAL CYLINDERS</td>
                <td class="tfoot-cell" colspan="3"><?php echo $total_cylinders; ?> cylinders</td>
            </tr>
        </tfoot>
    </table>
    </div>
</div>
<script src="../assets/js/vendor/jspdf.min.js"></script>
<script src="../assets/js/reports/pdf_report.js"></script>
<script src="../assets/js/reports/report_pdf_init.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
