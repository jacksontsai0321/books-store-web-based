<?php
$pageTitle = 'Payment Success';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../includes/_head.php';
require_once __DIR__ . '/../includes/_header.php';

$orderId = isset($_GET['order_id']) ? sanitize($_GET['order_id']) : '';

if (empty($orderId)) {
    redirect('/order/history.php');
}

// Get order and payment details
$order = getOrderDetails($pdo, $orderId);
if (!$order || $order['member_id'] != $_SESSION['member_id']) {
    redirect('/order/history.php');
}

try {
    $stmt = $pdo->prepare("SELECT * FROM order_payment WHERE order_id = ? ORDER BY payment_date DESC LIMIT 1");
    $stmt->execute([$orderId]);
    $payment = $stmt->fetch();
} catch (PDOException $e) {
    $payment = null;
}

if (!$payment) {
    $_SESSION['error'] = 'Payment record not found.';
    redirect('/order/detail.php?id=' . $orderId);
}
?>

<main>
    <div class="container">
        <div class="success-message" style="text-align: center; padding: 2rem;">
            <h1>Payment Successful!</h1>
            <p>Your payment has been processed successfully.</p>
        </div>
        
        <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-top: 2rem; max-width: 600px; margin-left: auto; margin-right: auto;">
            <h2>Payment Details</h2>
            <p><strong>Payment ID:</strong> <?php echo htmlspecialchars($payment['payment_id']); ?></p>
            <!-- Order ID hidden for member screens -->
            <p><strong>Amount Paid:</strong> <?php echo formatPrice($payment['amount']); ?></p>
            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($payment['method']); ?></p>
            <p><strong>Payment Status:</strong> <?php echo htmlspecialchars($payment['payment_status']); ?></p>
            <p><strong>Transaction Reference:</strong> <?php echo htmlspecialchars($payment['fake_transaction_ref']); ?></p>
            <p><strong>Payment Date:</strong> <?php echo date('Y-m-d H:i:s', strtotime($payment['payment_date'])); ?></p>
        </div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="/order/detail.php?id=<?php echo htmlspecialchars($orderId); ?>" class="btn btn-primary member-btn-primary">View Order Details</a>
            <a href="/order/history.php" class="btn btn-secondary member-btn-secondary">View Order History</a>
            <a href="/product/list.php" class="btn btn-secondary member-btn-secondary">Continue Shopping</a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/_footer.php'; ?>

