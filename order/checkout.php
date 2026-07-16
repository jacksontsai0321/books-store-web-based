<?php
$pageTitle = 'Checkout';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../includes/_head.php';
require_once __DIR__ . '/../includes/_header.php';

$member = getMemberById($pdo, $_SESSION['member_id']);

// Handle selection coming from cart page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_cart_ids'])) {
    $selectedCartIds = array_map(function($id) { return sanitize($id); }, $_POST['selected_cart_ids']);
    $_SESSION['selected_cart_ids'] = $selectedCartIds;
    redirect('/order/checkout.php');
}

$selectedCartIds = isset($_SESSION['selected_cart_ids']) ? $_SESSION['selected_cart_ids'] : [];
$cartItemsAll = getCartItems($pdo, $_SESSION['member_id']);
$cartItems = [];
if (!empty($selectedCartIds)) {
    foreach ($cartItemsAll as $item) {
        if (in_array((string)$item['cart_id'], $selectedCartIds, true)) {
            $cartItems[] = $item;
        }
    }
} else {
    $cartItems = $cartItemsAll;
}

$cartTotal = 0;
foreach ($cartItems as $item) {
    $cartTotal += $item['price'] * $item['quantity'];
}

if (empty($cartItems)) {
    $_SESSION['error'] = 'Your cart is empty.';
    redirect('/cart/view.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shippingAddress = sanitize($_POST['shipping_address'] ?? '');
    if (empty($shippingAddress)) {
        $errors[] = 'Shipping address is required';
    } else {
        // Update member address if different and redirect to PayPal checkout which will create the order
        if ($shippingAddress != $member['address']) {
            $stmt = $pdo->prepare("UPDATE member SET address = ? WHERE member_id = ?");
            $stmt->execute([$shippingAddress, $_SESSION['member_id']]);
        }
        redirect('/order/checkout_paypal.php');
    }
}
?>

<main>
    <div class="container">
        <h1 class="page-title">Checkout</h1>
        
        <?php if (!empty($errors)): ?>
            <?php renderErrors($errors); ?>
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div>
                <h2>Order Summary</h2>
                <div class="cart-items">
                    <?php foreach ($cartItems as $item): ?>
                        <div style="display: flex; justify-content: space-between; padding: 1rem; background: white; margin-bottom: 0.5rem; border-radius: 4px;">
                            <div>
                                <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                <p>Qty: <?php echo $item['quantity']; ?> × <?php echo formatPrice($item['price']); ?></p>
                            </div>
                            <div>
                                <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="cart-summary">
                    <div class="cart-total">Total: <?php echo formatPrice($cartTotal); ?></div>
                </div>
            </div>
            
            <div>
                <form method="POST" action="/order/checkout_paypal.php" class="member-form">
                    <h2>Shipping Information</h2>
                    
                    <div class="form-group">
                        <label for="shipping_address">Shipping Address *</label>
                        <textarea id="shipping_address" name="shipping_address" rows="4" required><?php echo htmlspecialchars($member['address']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-large member-btn-primary">Proceed to PayPal</button>
                        <a href="/cart/view.php" class="btn btn-secondary member-btn-secondary">Back to Cart</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/_footer.php'; ?>

