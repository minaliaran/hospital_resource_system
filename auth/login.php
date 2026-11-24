<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
if (isset($_SESSION['user_id'])) {
    header("Location: ../" . ($_SESSION['role'] === 'admin' ? 'admin' : 'staff') . "/dashboard.php");
    exit;
}
$error_msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if (empty($username) || empty($password)) {
        $error_msg = "Please enter both username and password.";
    } else {
        $stmt = $conn->prepare("SELECT u.*, h.hospital_name FROM users u JOIN hospitals h ON u.hospital_id = h.hospital_id WHERE u.username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$user) {
            $error_msg = "Username not found. Please check and try again.";
        } elseif ($user['status'] === 'inactive') {
            $error_msg = "Your account has been deactivated. Contact your Hospital Administrator.";
        } elseif (!password_verify($password, $user['password'])) {
            $error_msg = "Incorrect password. Please try again.";
        } else {
            $_SESSION['user_id']    = $user['user_id'];
            $_SESSION['hospital_id'] = $user['hospital_id'];
            $_SESSION['full_name']  = $user['full_name'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['role']       = $user['role'];
            log_action($user['user_id'], 'login', "Logged in successfully", $conn);
            header("Location: ../" . ($user['role'] === 'admin' ? 'admin' : 'staff') . "/dashboard.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In — HRCS | Hospital Resource Coordination System</title>
<meta name="description" content="Secure login for Colombo District Hospital Resource Coordination System.">
<link rel="stylesheet" href="../assets/css/vendor/fontawesome.min.css">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/login.css">
</head>
<body class="login-page">
<div class="login-split">
    <div class="login-left">
        <div class="login-left-content">
            <div class="moh-branding">
                <img src="../assets/images/hrcs_logo.png" alt="Government Emblem" class="moh-branding-logo">
                <div>
                    <div class="moh-branding-title">Colombo District Government Hospitals</div>
                    <div class="moh-branding-subtitle">Hospital Resource Coordination System</div>
                </div>
            </div>
            <h1 class="login-system-title">Hospital Resource<br>Coordination System</h1>
            <p class="login-system-subtitle">Colombo District Government Hospitals — Sri Lanka</p>
            <p class="login-system-desc">Secure internal platform for blood stock management, oxygen coordination, emergency request handling, and inter-hospital resource sharing.</p>
        </div>
        <div class="login-left-footer">Secure Internal Hospital System · © <?php echo date('Y'); ?> Colombo District Government Hospitals</div>
    </div>
    <div class="login-right">
        <div class="login-form-box">
            <div class="login-form-header">
                <h2>Sign In</h2>
                <p>Enter your credentials to access the system</p>
            </div>
            <?php if ($error_msg): ?>
            <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo sanitize($error_msg); ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['loggedout']) && empty($error_msg)): ?>
            <div class="alert alert-success"><i class="fa fa-check-circle"></i> You have been signed out successfully.</div>
            <?php endif; ?>
            <form method="POST" data-validate>
                <div class="form-group">
                    <label for="username">Username / Email</label>
                    <input type="text" id="username" name="username" class="form-control"
                           placeholder="e.g., nh_admin" required autofocus
                           value="<?php echo sanitize($_POST['username'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-action">
                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                        <button type="button" class="input-action-btn" id="togglePasswordBtn">SHOW</button>
                    </div>
                </div>
                <button type="submit" class="btn-login">Sign In <i class="fa fa-arrow-right"></i></button>
            </form>
            <p class="login-security-note"><i class="fa fa-lock"></i> Encrypted connection · Government network only</p>
            <div class="login-form-footer">
                Ministry of Health, Sri Lanka | Secure Internal Hospital System
            </div>
        </div>
    </div>
</div>
<script src="../assets/js/validation.js"></script>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/alerts.js"></script>
<script src="../assets/js/login.js"></script>
</body>
</html>
