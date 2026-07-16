<?php
/**
 * Authentication Guard
 * Forces member login to access pages
 */

require_once __DIR__ . '/session.php';

if (!isset($_SESSION['member_id'])) {
    $_SESSION['error'] = 'Please login to access this page.';
    header('Location: /login.php');
    exit();
}
?>

