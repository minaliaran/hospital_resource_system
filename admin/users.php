<?php
$page_title = "Staff Users Management";
require_once __DIR__ . '/../includes/header.php';
require_role('admin');
$h = $_SESSION['hospital_id'];
$users = $conn->query("SELECT * FROM users WHERE hospital_id=$h AND role != 'admin' ORDER BY role, full_name")->fetch_all(MYSQLI_ASSOC);
?>
<div class="page-header">
    <div>
        <div class="page-header-title">Staff Users Management</div>
        <div class="page-header-subtitle">Manage Blood Staff and Oxygen Staff for <?php echo sanitize($hospital_name); ?></div>
    </div>
    <button class="btn btn-primary" data-modal-open="addUserModal"><i class="fa fa-user-plus"></i> Add Staff</button>
</div>
<div class="panel">
    <div class="panel-header">
        <span class="panel-title"><i class="fa fa-users text-primary"></i> Staff Accounts</span>
        <span class="text-sm text-muted"><?php echo count($users); ?> staff members</span>
    </div>
    <?php if (empty($users)): ?>
    <div class="empty-state"><i class="fa fa-user-slash"></i><p>No staff added yet. Click "Add Staff" to create a new account.</p></div>
    <?php else: ?>
    <div class="table-responsive">
    <table class="data-table">
        <thead><tr><th>Full Name</th><th>Username</th><th>Department / Role</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
            <td>
                <div class="flex-center-gap">
                    <div class="user-avatar-circle"><?php echo strtoupper(substr($u['full_name'],0,2)); ?></div>
                    <strong><?php echo sanitize($u['full_name']); ?></strong>
                </div>
            </td>
            <td><code class="code-tag"><?php echo sanitize($u['username']); ?></code></td>
            <td>
                <span class="badge <?php echo $u['role'] === 'blood_staff' ? 'badge-danger' : 'badge-info'; ?>">
                    <?php echo $u['role'] === 'blood_staff' ? 'Blood Staff' : 'Oxygen Staff'; ?>
                </span>
            </td>
            <td>
                <span class="badge <?php echo $u['status'] === 'active' ? 'badge-success' : 'badge-danger'; ?>"><?php echo ucfirst($u['status']); ?></span>
            </td>
            <td class="text-sm text-muted"><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
            <td>
                <div class="flex-center-gap">
                    <button type="button" class="btn btn-sm btn-secondary btn-edit-user" data-user="<?php echo htmlspecialchars(json_encode($u)); ?>"><i class="fa fa-edit"></i></button>
                    <form action="../actions/toggle_user_status.php" method="POST" class="d-inline">
                        <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                        <button type="submit" class="btn btn-sm <?php echo $u['status'] === 'active' ? 'btn-warning' : 'btn-success'; ?>" data-confirm="<?php echo $u['status'] === 'active' ? 'Deactivate' : 'Activate'; ?> this user?">
                            <i class="fa fa-<?php echo $u['status'] === 'active' ? 'ban' : 'check'; ?>"></i> <?php echo $u['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<div id="addUserModal" class="modal-overlay">
    <div class="modal-box modal-sm">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-user-plus text-primary"></i> Add Staff Account</span>
            <button class="modal-close" data-modal-close="addUserModal">&times;</button>
        </div>
        <form action="../actions/add_user.php" method="POST" data-validate>
            <div class="form-group">
                <label>Full Name <span class="required">*</span></label>
                <input type="text" name="full_name" class="form-control" required placeholder="e.g. Kumari Perera">
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Username <span class="required">*</span></label>
                    <input type="text" name="username" class="form-control" required placeholder="e.g. blood_staff1">
                </div>
                <div class="form-group">
                    <label>Role <span class="required">*</span></label>
                    <select name="role" class="form-control" required>
                        <option value="">Select role</option>
                        <option value="blood_staff">Blood Staff</option>
                        <option value="oxygen_staff">Oxygen Staff</option>
                    </select>
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Password <span class="required">*</span></label>
                    <input type="password" name="password" class="form-control" required placeholder="Min. 8 characters" minlength="6">
                </div>
                <div class="form-group">
                    <label>Confirm Password <span class="required">*</span></label>
                    <input type="password" name="confirm_password" class="form-control" required placeholder="Re-enter password">
                </div>
            </div>
            <div class="flex-end-gap">
                <button type="button" class="btn btn-secondary" data-modal-close="addUserModal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Create Account</button>
            </div>
        </form>
    </div>
</div>
<div id="editUserModal" class="modal-overlay">
    <div class="modal-box modal-sm">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-edit"></i> Update Staff Account</span>
            <button class="modal-close" data-modal-close="editUserModal">&times;</button>
        </div>
        <form action="../actions/update_user.php" method="POST">
            <input type="hidden" name="user_id" id="edit_uid">
            <div class="form-group">
                <label>Full Name <span class="required">*</span></label>
                <input type="text" name="full_name" id="edit_fullname" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" id="edit_uname_display" class="form-control readonly-field" readonly>
            </div>
            <div class="form-group">
                <label>New Password <span class="text-xs text-muted">(leave blank to keep current)</span></label>
                <input type="password" name="new_password" class="form-control" placeholder="Enter new password (optional)">
            </div>
            <div class="flex-end-gap">
                <button type="button" class="btn btn-secondary" data-modal-close="editUserModal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Update</button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="../assets/js/admin/admin_users.js?v=<?php echo time(); ?>"></script>
