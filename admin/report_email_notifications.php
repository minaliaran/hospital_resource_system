<?php
$page_title = "Email Notification Report";
require_once __DIR__ . '/../includes/header.php';
require_role('admin');
$h = $_SESSION['hospital_id'];
$filter_status = $_GET['status'] ?? '';
$sql = "SELECT el.*, u.full_name FROM email_logs el LEFT JOIN users u ON el.sent_by = u.user_id WHERE (u.hospital_id = $h OR el.sent_by IS NULL)";
if ($filter_status) {
    $sql .= " AND el.status = '" . $conn->real_escape_string($filter_status) . "'";
}
$sql .= " ORDER BY el.sent_at DESC";
$rows = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
$pdf_rows = [];
foreach ($rows as $r) {
    $pdf_rows[] = [
        $r['recipient'],
        $r['subject'],
        ucfirst($r['status']),
        $r['full_name'] ?: 'System',
        date('Y-m-d H:i', strtotime($r['sent_at']))
    ];
}

$sent_count = count(array_filter($rows, fn($r) => $r['status'] === "sent"));
$failed_count = count(array_filter($rows, fn($r) => $r['status'] === "failed"));

$pdf_config = [
    'filename'       => 'Email_Notification_Report_' . date('Y-m-d') . '.pdf',
    'title'          => 'Email Notification Report',
    'hospital'       => sanitize($hospital_name),
    'generatedBy'    => sanitize($full_name),
    'date'           => date('d F Y, H:i'),
    'filters'        => [
        ['label' => 'Send Status', 'value' => $filter_status ?: 'All Statuses']
    ],
    'summary'        => [
        ['label' => 'Total Logs', 'value' => (string)count($rows)],
        ['label' => 'Sent',       'value' => (string)$sent_count],
        ['label' => 'Failed',     'value' => (string)$failed_count]
    ],
    'chartIds'       => [],
    'tableHeaders'   => ['Recipient Email', 'Subject', 'Send Status', 'Sent By', 'Sent At'],
    'colWidths'      => [45, 55, 20, 25, 35],
    'tableRows'      => $pdf_rows,
    'statusColIndex' => 2,
    'notes'          => 'This report lists all email notifications triggered by the system. It monitors communications sent for transfers, approvals, and system alerts.'
];
?>
<div class="mb-3">
    <a href="reports.php" class="text-link text-sm"><i class="fa fa-arrow-left"></i> Back to Reports</a>
</div>
<div class="page-header">
    <div>
        <div class="page-header-title">Email Notification Report</div>
        <div class="page-header-subtitle">Log of all emails dispatched by the system for coordination events</div>
    </div>
    <button class="btn btn-secondary btn-print"><i class="fa fa-print"></i> Print Report</button>
    <button class="btn btn-primary" id="emailPdfBtn" data-pdf-config='<?php echo json_encode($pdf_config, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'><i class="fa fa-file-pdf"></i> Download PDF</button>
</div>
<div class="panel panel-mb">
    <div class="panel-body">
        <form method="GET" class="filter-bar-form">
            <div class="form-group m-0">
                <label>Send Status</label>
                <select name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="sent" <?php echo $filter_status === 'sent' ? 'selected' : ''; ?>>Sent</option>
                    <option value="failed" <?php echo $filter_status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Generate Filter</button>
            <a href="report_email_notifications.php" class="btn btn-secondary">Reset</a>
        </form>
    </div>
</div>
<div class="panel">
    <div class="panel-header">
        <span class="panel-title"><i class="fa fa-envelope text-primary-icon"></i> System Email Dispatch Records</span>
        <span class="table-meta-text">Total Logs: <?php echo count($rows); ?></span>
    </div>
    <div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>Recipient Email</th>
                <th>Subject</th>
                <th>Send Status</th>
                <th>Sent By</th>
                <th>Sent At</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
            <td><code><?php echo sanitize($r['recipient']); ?></code></td>
            <td class="fw-600"><?php echo sanitize($r['subject']); ?></td>
            <td><span class="badge <?php echo $r['status'] === 'sent' ? 'badge-success' : 'badge-danger'; ?>"><?php echo ucfirst($r['status']); ?></span></td>
            <td><?php echo $r['full_name'] ? sanitize($r['full_name']) : '<span class="text-muted">System</span>'; ?></td>
            <td class="table-row-date"><?php echo date('M j, Y g:i a', strtotime($r['sent_at'])); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<script src="../assets/js/vendor/jspdf.min.js"></script>
<script src="../assets/js/reports/pdf_report.js"></script>
<script src="../assets/js/reports/report_pdf_init.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
