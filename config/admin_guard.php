<?php
/**
 * Admin Authentication Guard
 * Forces admin login to access admin pages
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';

if (!isset($_SESSION['admin_id'])) {
    $_SESSION['error'] = 'Please login as admin to access this page.';
    header('Location: /admin/login.php');
    exit();
}

// Ensure role is set for downstream authorization checks
if (!isset($_SESSION['admin_role'])) {
    $_SESSION['admin_role'] = isSuperAdmin() ? 'admin' : 'staff';
}
?>

