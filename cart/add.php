<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = isset($_POST['product_id']) ? sanitize($_POST['product_id']) : '';
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $returnTo = isset($_POST['return_to']) ? sanitize($_POST['return_to']) : '/product/list.php';
    
    if (empty($productId) || $quantity <= 0) {
        $_SESSION['error'] = 'Invalid product or quantity.';
        redirect('/product/list.php');
    }
    
    try {
        // Ensure member is verified
        $stmt = $pdo->prepare("SELECT is_verified, email FROM member WHERE member_id = ?");
        $stmt->execute([$_SESSION['member_id']]);
        $member = $stmt->fetch();
        if (!$member || empty($member['is_verified'])) {
            $_SESSION['verification_email'] = $member['email'] ?? '';
            $_SESSION['error'] = 'Please verify your email before adding to cart.';
            redirect('/verify_code.php');
        }

        // Check product exists and has stock
        $product = getProductById($pdo, $productId);
        if (!$product) {
            $_SESSION['error'] = 'Product not found.';
            redirect('/product/list.php');
        }
        
        if ($product['stock_quantity'] < $quantity) {
            $_SESSION['error'] = 'Insufficient stock available.';
            redirect('/product/detail.php?id=' . $productId);
        }
        
        // Check if item already in cart
        $stmt = $pdo->prepare("SELECT * FROM shopping_cart WHERE member_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['member_id'], $productId]);
        $existingItem = $stmt->fetch();
        
        if ($existingItem) {
            $newQuantity = $existingItem['quantity'] + $quantity;
            if ($newQuantity > $product['stock_quantity']) {
                $_SESSION['error'] = 'Cannot add more. Stock limit reached.';
                redirect('/product/detail.php?id=' . $productId);
            }
            $stmt = $pdo->prepare("UPDATE shopping_cart SET quantity = ? WHERE cart_id = ?");
            $stmt->execute([$newQuantity, $existingItem['cart_id']]);
        } else {
            $cartId = generateSequentialId($pdo, 'C', 'shopping_cart', 'cart_id');
            $stmt = $pdo->prepare("INSERT INTO shopping_cart (cart_id, member_id, product_id, quantity) VALUES (?, ?, ?, ?)");
            $stmt->execute([$cartId, $_SESSION['member_id'], $productId, $quantity]);
        }
        
        $_SESSION['success'] = 'Item added to cart!';
        unset($_SESSION['selected_cart_ids']);
        if (!empty($returnTo)) {
            redirect($returnTo);
        }
        redirect('/product/list.php');
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Failed to add item to cart.';
        redirect('/product/list.php');
    }
} else {
    redirect('/product/list.php');
}
?>

