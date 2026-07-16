<?php
$pageTitle = 'Checkout - PayPal';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

// This endpoint creates an order using the user's saved address and sets payment_method to 'PayPal'.
// It then redirects the user to the payment page where the PayPal button will start the checkout.

$member = getMemberById($pdo, $_SESSION['member_id']);
// If selected_cart_ids are POSTed from the cart, save into session so we use the same selection here
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_cart_ids'])) {
    $selectedCartIdsPost = array_map(function($id) { return sanitize($id); }, $_POST['selected_cart_ids']);
    $_SESSION['selected_cart_ids'] = $selectedCartIdsPost;
    // If only selection was submitted, convert to GET to avoid accidental reposts
    if (!isset($_POST['shipping_address'])) {
        redirect('/order/checkout_paypal.php');
    }
}

// If shipping address was submitted from checkout page, update member address so PayPal order can be created
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['shipping_address'])) {
    $shippingAddress = sanitize($_POST['shipping_address'] ?? '');
    if (!empty(trim($shippingAddress))) {
        if (trim($shippingAddress) != trim($member['address'] ?? '')) {
            $stmt = $pdo->prepare("UPDATE member SET address = ? WHERE member_id = ?");
            $stmt->execute([$shippingAddress, $_SESSION['member_id']]);
            $member = getMemberById($pdo, $_SESSION['member_id']);
        }
    } else if (empty(trim($member['address'] ?? ''))) {
        // No address provided anywhere; redirect back to checkout form to collect address
        $_SESSION['error'] = 'Please provide a shipping address before proceeding to PayPal.';
        redirect('/order/checkout.php');
    }
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

if (empty($cartItems)) {
    $_SESSION['error'] = 'Your cart is empty.';
    redirect('/cart/view.php');
}

// Require member have a shipping address on file; otherwise redirect to normal checkout
if (empty(trim($member['address'] ?? ''))) {
    $_SESSION['error'] = 'Please provide a shipping address before proceeding to PayPal.';
    redirect('/order/checkout.php');
}

$cartTotal = 0;
foreach ($cartItems as $item) {
    $cartTotal += $item['price'] * $item['quantity'];
}

// Do NOT create the DB order here. The order will be created when the user clicks the PayPal button (server endpoint `/order/paypal_create_order.php`).
// This prevents a pending order record being left behind when users cancel before paying.
if (function_exists('redirect')) {
    redirect('/order/payment.php');
} else {
    header('Location: /order/payment.php');
    exit;
}

?>