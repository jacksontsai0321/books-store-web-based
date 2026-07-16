<?php
/**
 * Super Admin Guard
 * Allows only the primary admin (SUPER_ADMIN_ID) to proceed.
 */

require_once __DIR__ . '/admin_guard.php';

if (!isSuperAdmin()) {
    $_SESSION['error'] = 'You do not have permission to perform this action.';
    header('Location: /admin/dashboard.php');
    exit();
}
?>

