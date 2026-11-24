<?php
$page_title = "Reports Control Center";
require_once __DIR__ . '/../includes/header.php';
require_role('admin');
?>
<div class="page-header">
    <div>
        <div class="page-header-title">Reports Control Center</div>
        <div class="page-header-subtitle">Select a standard project report below to analyze resource coordination logs</div>
    </div>
</div>

<div class="reports-center-grid">
    <div class="stat-card report-hub-card">
        <div class="report-hub-top">
            <span class="badge badge-success">Admin Only</span>
        </div>
        <div>
            <h3 class="report-hub-title">1. Monthly Resource Summary</h3>
            <p class="report-hub-desc">Contains total blood/oxygen additions, transfers, most active hospitals, most requested group, and out-of-stock items.</p>
        </div>
        <div class="report-hub-footer">
            <a href="report_monthly_summary.php" class="btn btn-sm btn-primary">Generate Report &rarr;</a>
        </div>
    </div>

    <div class="stat-card report-hub-card">
        <div class="report-hub-top">
            <span class="badge badge-secondary">Admin & Blood Staff</span>
        </div>
        <div>
            <h3 class="report-hub-title">2. Blood Stock Report</h3>
            <p class="report-hub-desc">Details on blood unit availability, categorised by group and component, expiry tracking, and low stock alarms.</p>
        </div>
        <div class="report-hub-footer">
            <a href="report_blood_stock.php" class="btn btn-sm btn-primary">Generate Report &rarr;</a>
        </div>
    </div>

    <div class="stat-card report-hub-card">
        <div class="report-hub-top">
            <span class="badge badge-secondary">Admin & Oxy Staff</span>
        </div>
        <div>
            <h3 class="report-hub-title">3. Oxygen Stock Report</h3>
            <p class="report-hub-desc">Availability statistics for Small, Medium, and Large oxygen cylinders across the hospital inventory system.</p>
        </div>
        <div class="report-hub-footer">
            <a href="report_oxygen_stock.php" class="btn btn-sm btn-primary">Generate Report &rarr;</a>
        </div>
    </div>

    <div class="stat-card report-hub-card">
        <div class="report-hub-top">
            <span class="badge badge-secondary">All Users</span>
        </div>
        <div>
            <h3 class="report-hub-title">4. Emergency Request Report</h3>
            <p class="report-hub-desc">Full log of submitted emergency requests, priorities, reason, and approval/rejection statistics.</p>
        </div>
        <div class="report-hub-footer">
            <a href="report_emergency_requests.php" class="btn btn-sm btn-primary">Generate Report &rarr;</a>
        </div>
    </div>

    <div class="stat-card report-hub-card">
        <div class="report-hub-top">
            <span class="badge badge-success">Admin Only</span>
        </div>
        <div>
            <h3 class="report-hub-title">5. Transfer Report</h3>
            <p class="report-hub-desc">Auditing log of inter-hospital dispatches, containing sender/receiver metadata, vehicle IDs, and transit stages.</p>
        </div>
        <div class="report-hub-footer">
            <a href="report_transfers.php" class="btn btn-sm btn-primary">Generate Report &rarr;</a>
        </div>
    </div>

    <div class="stat-card report-hub-card">
        <div class="report-hub-top">
            <span class="badge badge-success">Admin Only</span>
        </div>
        <div>
            <h3 class="report-hub-title">6. Email Notification Report</h3>
            <p class="report-hub-desc">Complete email relay history including recipient emails, subjects, statuses (sent/failed), and triggers.</p>
        </div>
        <div class="report-hub-footer">
            <a href="report_email_notifications.php" class="btn btn-sm btn-primary">Generate Report &rarr;</a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

