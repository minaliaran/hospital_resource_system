<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
function check_logged_in() {
    if (!isset($_SESSION['user_id'])) {
        $root_path = get_project_root_relative_path();
        header("Location: " . $root_path . "auth/login.php?error=unauthorized");
        exit;
    }
}
function require_role($allowed_roles) {
    check_logged_in();
    if (is_string($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        $root_path = get_project_root_relative_path();
        if ($_SESSION['role'] === 'admin') {
            header("Location: " . $root_path . "admin/dashboard.php?error=forbidden");
        } else {
            header("Location: " . $root_path . "staff/dashboard.php?error=forbidden");
        }
        exit;
    }
}
function get_project_root_relative_path() {
    $current_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $project_root_name = 'hospital_resource_system';
    $pos = strrpos($current_dir, '/' . $project_root_name);
    if ($pos !== false) {
        $subdirs = substr($current_dir, $pos + strlen($project_root_name) + 1);
        if ($subdirs) {
            $levels = count(explode('/', trim($subdirs, '/')));
            return str_repeat('../', $levels);
        }
    }
    $script_path = $_SERVER['SCRIPT_NAME'];
    if (strpos($script_path, '/admin/') !== false || strpos($script_path, '/staff/') !== false || strpos($script_path, '/auth/') !== false || strpos($script_path, '/actions/') !== false) {
        return '../';
    }
    return './';
}
