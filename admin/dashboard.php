<?php
// Debug session on dashboard
if (isset($_GET['debug_session'])) {
    echo "<pre>";
    echo "Session Status: " . session_status() . "\n";
    echo "Session ID: " . session_id() . "\n";
    print_r($_SESSION);
    echo "</pre>";
    exit();
}

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/admin_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../includes/_admin_head.php';
require_once __DIR__ . '/../includes/_admin_header.php';
require_once __DIR__ . '/../includes/_admin_sidebar.php';

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM member");
    $memberCount = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM product");
    $productCount = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM order_header");
    $orderCount = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT SUM(total_amount) as total FROM order_header WHERE status = 'Completed'");
    $revenue = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $pdo->query("SELECT * FROM order_header ORDER BY order_id DESC LIMIT 5");
    $recentOrders = $stmt->fetchAll();
} catch (PDOException $e) {
    $memberCount = $productCount = $orderCount = $revenue = 0;
    $recentOrders = [];
}
?>

<div class="admin-main">
    <div class="admin-content">
        <h1 class="admin-page-title">Dashboard</h1>
        
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Total Members</h3>
                <div class="stat-value"><?php echo $memberCount; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Products</h3>
                <div class="stat-value"><?php echo $productCount; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Orders</h3>
                <div class="stat-value"><?php echo $orderCount; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Revenue</h3>
                <div class="stat-value"><?php echo formatPrice($revenue); ?></div>
            </div>
        </div>
        
        <h2 style="margin-top: 2rem;">Recent Orders</h2>
        <?php if (empty($recentOrders)): ?>
            <p>No orders yet.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Member</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['order_id']; ?></td>
                            <td>
                                <?php 
                                $member = getMemberById($pdo, $order['member_id']);
                                echo htmlspecialchars($member['full_name'] ?? 'N/A');
                                ?>
                            </td>
                            <td><?php echo formatPrice($order['total_amount']); ?></td>
                            <td><?php echo htmlspecialchars($order['status']); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($order['order_date'] ?? $order['created_at'])); ?></td>
                            <td>
                                <a href="/admin/order/view.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-primary">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/_admin_footer.php'; ?>

