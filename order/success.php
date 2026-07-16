<?php
$pageTitle = 'Order Success';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../includes/_head.php';
require_once __DIR__ . '/../includes/_header.php';

$orderId = isset($_SESSION['order_id']) ? $_SESSION['order_id'] : '';
unset($_SESSION['order_id']);

if (empty($orderId)) {
    redirect('/order/history.php');
}

$order = getOrderDetails($pdo, $orderId);
if (!$order || $order['member_id'] != $_SESSION['member_id']) {
    redirect('/order/history.php');
}

$orderItems = getOrderItems($pdo, $orderId);
?>

<main>
    <div class="container">
        <div class="success-message" style="text-align: center; padding: 2rem;">
            <h1>Order Placed Successfully!</h1>
                <p>Thank you for your order.</p>
        </div>
        
        <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-top: 2rem;">
            <h2>Order Details</h2>
            <p><strong>Total Amount:</strong> <?php echo formatPrice($order['total_amount']); ?></p>
            <p><strong>Shipping Address:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></p>
            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
            
            <h3 style="margin-top: 2rem;">Order Items</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo formatPrice($item['price']); ?></td>
                            <td><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="/order/history.php" class="btn btn-primary member-btn-primary">View Order History</a>
            <a href="/product/list.php" class="btn btn-secondary member-btn-secondary">Continue Shopping</a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/_footer.php'; ?>

