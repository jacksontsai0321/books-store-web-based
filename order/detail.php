<?php
$pageTitle = 'Order Detail';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../includes/_head.php';
require_once __DIR__ . '/../includes/_header.php';

$orderId = isset($_GET['id']) ? sanitize($_GET['id']) : '';

if (empty($orderId)) {
    // If no order id, send the user back to cart — this avoids landing on order history when a cancelled flow occurs.
    redirect('/cart/view.php');
}

$order = getOrderDetails($pdo, $orderId);

if (!$order || $order['member_id'] != $_SESSION['member_id']) {
    $_SESSION['error'] = 'Order not found.';
    redirect('/cart/view.php');
}

$orderItems = getOrderItems($pdo, $orderId);
?>

<main>
    <div class="container">
        <h1 class="page-title">Order Details</h1>
        
        <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <h2>Order Information</h2>
            <p><strong>Order Date:</strong> <?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></p>
            <p><strong>Total Amount:</strong> <?php echo formatPrice($order['total_amount']); ?></p>
            <p><strong>Shipping Address:</strong> <?php echo htmlspecialchars($order['shipping_address'] ?? $order['address'] ?? 'N/A'); ?></p>
            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
            
            <?php
            // Check if payment exists
            try {
                $stmt = $pdo->prepare("SELECT * FROM order_payment WHERE order_id = ? AND payment_status = 'Completed'");
                $stmt->execute([$orderId]);
                $payment = $stmt->fetch();
            } catch (PDOException $e) {
                $payment = null;
            }
            
            // Show payment button if order is pending and no payment exists
            if ($order['status'] == 'Pending' && !$payment):
            ?>
                <div style="margin-top: 1.5rem; padding: 1rem; background: #f0f7ff; border-radius: 4px; border-left: 4px solid #007bff;">
                    <p style="margin: 0 0 1rem 0;"><strong>Payment Required</strong></p>
                    <a href="/order/payment.php?order_id=<?php echo htmlspecialchars($orderId); ?>" class="btn btn-primary member-btn-primary">Make Payment</a>
                </div>
            <?php elseif ($payment): ?>
                <div style="margin-top: 1.5rem; padding: 1rem; background: #f0fff0; border-radius: 4px; border-left: 4px solid #28a745;">
                    <p style="margin: 0;"><strong>Payment Status:</strong> <?php echo htmlspecialchars($payment['payment_status']); ?></p>
                    <p style="margin: 0.5rem 0 0 0;"><strong>Transaction Ref:</strong> <?php echo htmlspecialchars($payment['fake_transaction_ref']); ?></p>
                </div>
            <?php endif; ?>

            <?php // Show "I have received" button when payment is completed and order is still in Processing. User clicks this to confirm receipt, which will move the order to Shipped and enable refund option.
            if ($payment && isset($payment['payment_status']) && $payment['payment_status'] === 'Completed' && $order['status'] === 'Processing'): ?>
                <div style="margin-top: 1.5rem;">
                    <form method="POST" action="/order/mark_shipped.php" onsubmit="return confirm('Confirm you have received the items?');">
                        <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($orderId); ?>">
                        <button type="submit" class="btn btn-primary member-btn-primary">I have received the items</button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($order['status'] === 'Shipped'): ?>
                <div style="margin-top: 1.5rem;">
                    <a href="/order/request_refund.php?order_id=<?php echo htmlspecialchars($orderId); ?>" class="btn btn-primary member-btn-primary">Request Refund</a>
                    <small style="display:block;margin-top:0.5rem;color:#666;">Refunds can be requested within 7 days after marking as received (Shipped) before the order auto-completes.</small>
                </div>
            <?php endif; ?>
            
            <h3 style="margin-top: 2rem;">Order Items</h3>
            <table class="data-table">
                <thead>
                    <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Subtotal</th>
                            <th>Review</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td>
                                <?php if (!empty($item['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($item['image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                         style="width: 50px; height: 50px; object-fit: cover; margin-right: 10px; vertical-align: middle;">
                                <?php endif; ?>
                                <?php echo htmlspecialchars($item['product_name']); ?>
                            </td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo formatPrice($item['price']); ?></td>
                            <td><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                            <td>
                                <?php if (in_array($order['status'], ['Shipped', 'Completed']) && isset($_SESSION['member_id']) && !hasMemberReviewedProduct($pdo, $_SESSION['member_id'], $item['product_id'])): ?>
                                    <a href="/order/review.php?order_id=<?php echo urlencode($orderId); ?>&product_id=<?php echo urlencode($item['product_id']); ?>" class="btn btn-sm btn-secondary member-btn-secondary">Write Review</a>
                                <?php else: ?>
                                    &nbsp;
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 2rem;">
            <a href="/order/history.php" class="btn btn-secondary member-btn-secondary">Back to Order History</a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/_footer.php'; ?>

