<?php
$page_title = "Email History";
require_once __DIR__ . '/../includes/header.php';
require_role('admin');
$h = $_SESSION['hospital_id'];
$emails = $conn->query("SELECT el.*, u.full_name FROM email_logs el LEFT JOIN users u ON el.sent_by = u.user_id WHERE (u.hospital_id = $h OR el.sent_by IS NULL) ORDER BY el.sent_at DESC LIMIT 100")->fetch_all(MYSQLI_ASSOC);
?>
<div class="page-header">
    <div><div class="page-header-title">Email History</div><div class="page-header-subtitle">Log of all system-generated email notifications</div></div>
</div>
<div class="panel">
    <div class="panel-header"><span class="panel-title"><i class="fa fa-envelope text-primary-icon"></i> Sent Email Log</span><span class="table-meta-text"><?php echo count($emails); ?> emails</span></div>
    <?php if (empty($emails)): ?>
    <div class="empty-state"><i class="fa fa-envelope-open"></i><p>No email logs yet. Emails are recorded here when triggered by system actions.</p></div>
    <?php else: ?>
    <div class="table-responsive">
    <table class="data-table">
        <thead><tr><th>Recipient</th><th>Subject</th><th>Sent By</th><th>Status</th><th>Sent At</th><th>Preview</th></tr></thead>
        <tbody>
        <?php foreach ($emails as $e): 
            $email_data = [
                'subject' => $e['subject'],
                'to'      => $e['recipient'],
                'body'    => $e['message'],
                'date'    => date('M j, Y g:i a', strtotime($e['sent_at']))
            ];
            $json_str = htmlspecialchars(json_encode($email_data), ENT_QUOTES, 'UTF-8');
        ?>
        <tr>
            <td><code><?php echo sanitize($e['recipient']); ?></code></td>
            <td class="fw-600"><?php echo sanitize($e['subject']); ?></td>
            <td class="text-sm"><?php echo $e['full_name'] ? sanitize($e['full_name']) : '<span class="text-muted">System</span>'; ?></td>
            <td><span class="badge <?php echo $e['status'] === 'sent' ? 'badge-success' : 'badge-danger'; ?>"><?php echo ucfirst($e['status']); ?></span></td>
            <td class="table-row-id"><?php echo date('M j, Y g:i a', strtotime($e['sent_at'])); ?></td>
            <td><button type="button" class="btn btn-sm btn-secondary btn-preview-email" data-email='<?php echo $json_str; ?>' onclick='showEmailPreview(<?php echo json_encode($email_data); ?>)'><i class="fa fa-eye"></i> View</button></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<div id="emailPreviewModal" class="modal-overlay">
    <div class="modal-box modal-md">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-envelope text-primary"></i> Email Preview</span>
            <button class="modal-close" data-modal-close="emailPreviewModal">&times;</button>
        </div>
        <div class="email-preview-info">
            <div class="mb-1"><strong>To:</strong> <span id="prev_to"></span></div>
            <div class="mb-1"><strong>Subject:</strong> <span id="prev_subject" class="fw-600"></span></div>
            <div><strong>Date:</strong> <span id="prev_date"></span></div>
        </div>
        <div id="prev_body" class="email-preview-body"></div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="../assets/js/admin/admin_email_history.js?v=<?php echo time(); ?>"></script>
