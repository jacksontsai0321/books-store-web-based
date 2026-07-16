<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/functions.php';
$panelLabel = isSuperAdmin() ? 'Admin Panel' : 'Staff Panel';
?>

<header class="admin-header">
    <div class="admin-header-content">
        <div class="admin-logo">
            <h2><a href="/admin/dashboard.php" style="text-decoration:none;color:inherit;">📚 <?php echo $panelLabel; ?></a></h2>
        </div>
        <div class="admin-user-info">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>
            <a href="/admin/logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
</header>

