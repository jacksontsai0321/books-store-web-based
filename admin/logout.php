<?php
require_once __DIR__ . '/../config/session.php';

unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_role']);
session_destroy();
header('Location: /admin/login.php');
exit();
?>

