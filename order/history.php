<?php
$pageTitle = 'Order History';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../includes/_head.php';
require_once __DIR__ . '/../includes/_header.php';

try {
    $stmt = $pdo->prepare("SELECT * FROM order_header WHERE member_id = ? ORDER BY order_date DESC");
    $stmt->execute([$_SESSION['member_id']]);
    $orders = $stmt->fetchAll();
    // Map order_date to created_at for compatibility
    foreach ($orders as $k => $order) {
        $orders[$k]['created_at'] = $order['order_date'] ?? '';
    }
} catch (PDOException $e) {
    $orders = [];
    $error = 'Unable to load orders.';
}
?>

<main>
    <div class="container">
        <h1 class="page-title">Order History</h1>
        
        <?php if (isset($error)): ?>
            <div class="error-messages">
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php elseif (empty($orders)): ?>
            <div class="info-message">
                <p>You have no orders yet. <a href="/product/list.php">Start shopping</a> to place your first order!</p>
            </div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                            <td><?php echo formatPrice($order['total_amount']); ?></td>
                            <td><?php echo htmlspecialchars($order['status']); ?></td>
                            <td>
                                <a href="/order/detail.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-primary">View Details</a>
                                <?php if ($order['status'] === 'Completed'): ?>
                                    <a href="/order/review.php?order_id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-secondary" style="margin-left: 0.5rem;">Review</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/_footer.php'; ?>

