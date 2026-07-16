<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/functions.php';
$canManageStaff = isSuperAdmin();
?>
<aside class="admin-sidebar">
    <nav class="admin-nav">
        <ul>
            <li><a href="/admin/dashboard.php">Dashboard</a></li>
            <li><a href="/admin/member/list.php">Members</a></li>
            <?php if ($canManageStaff): ?>
                <li><a href="/admin/admin/list.php">Staff</a></li>
            <?php endif; ?>
            <li><a href="/admin/category/list.php">Categories</a></li>
            <li><a href="/admin/product/list.php">Products</a></li>
            <li><a href="/admin/review/list.php">Reviews</a></li>
            <li><a href="/admin/order/list.php">Orders</a></li>
            <li><a href="/admin/report/refunds.php">Refund Requests</a></li>
            <li><a href="/admin/report/index.php">Reports</a></li>
        </ul>
    </nav>
</aside>

