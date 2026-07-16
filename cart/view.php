<?php
$pageTitle = 'Shopping Cart';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../includes/_head.php';
require_once __DIR__ . '/../includes/_header.php';

$cartItems = getCartItems($pdo, $_SESSION['member_id']);
$cartTotal = calculateCartTotal($pdo, $_SESSION['member_id']);
?>

<main>
    <div class="container">
        <h1 class="page-title">Shopping Cart</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['payment_cancel']) && $_GET['payment_cancel'] == '1'): ?>
            <div class="success-message">Payment cancelled. Your items have been returned to the cart.</div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-messages">
                <div class="error-message"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            </div>
        <?php endif; ?>

        <?php if (empty($cartItems)): ?>
            <div class="info-message">
                <p>Your cart is empty. <a href="/product/list.php">Browse products</a> to add items.</p>
            </div>
        <?php else: ?>
            <div class="cart-items">
                <?php foreach ($cartItems as $item): ?>
                    <?php include __DIR__ . '/../includes/components/cart_item.php'; ?>
                <?php endforeach; ?>
            </div>
            
            <div class="cart-actions" style="display:flex; gap:1rem;">
                <form id="checkout-selection-form" method="POST" action="/order/checkout_paypal.php" class="cart-summary" style="margin:0;">
                    <button type="submit" class="btn btn-primary btn-large">Proceed to Payment</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/_footer.php'; ?>

