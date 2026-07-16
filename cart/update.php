<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cartId = isset($_POST['cart_id']) ? sanitize($_POST['cart_id']) : '';
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Allow quantity 0 to indicate removal; handle deletion for AJAX and non-AJAX
    if (empty($cartId)) {
        $_SESSION['error'] = 'Invalid cart item.';
        redirect('/cart/view.php');
    }
    
    if ($quantity <= 0) {
        // delete cart item
        try {
            $stmt = $pdo->prepare("DELETE FROM shopping_cart WHERE cart_id = ? AND member_id = ?");
            $stmt->execute([$cartId, $_SESSION['member_id']]);
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
            $stmt2 = $pdo->prepare("SELECT COUNT(*) as cnt FROM shopping_cart WHERE member_id = ?");
            $stmt2->execute([$_SESSION['member_id']]);
            $cartCount = (int)($stmt2->fetch()['cnt'] ?? 0);
            if ($isAjax) {
                echo json_encode(['success' => true, 'message' => 'Item removed from cart.', 'cart_count' => $cartCount]);
                exit;
            }
            $_SESSION['success'] = 'Item removed from cart.';
            redirect('/cart/view.php');
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Failed to remove item from cart.';
            redirect('/cart/view.php');
        }
    }
    
    try {
        // Verify cart item belongs to member
        $stmt = $pdo->prepare("SELECT c.*, p.stock FROM shopping_cart c 
                              JOIN product p ON c.product_id = p.product_id 
                              WHERE c.cart_id = ? AND c.member_id = ?");
        $stmt->execute([$cartId, $_SESSION['member_id']]);
        $cartItem = $stmt->fetch();
        
        if (!$cartItem) {
            $_SESSION['error'] = 'Cart item not found.';
            redirect('/cart/view.php');
        }
        
        if ($quantity > $cartItem['stock']) {
            $_SESSION['error'] = 'Insufficient stock available.';
            redirect('/cart/view.php');
        }
        
        $stmt = $pdo->prepare("UPDATE shopping_cart SET quantity = ? WHERE cart_id = ?");
        $stmt->execute([$quantity, $cartId]);
        
        // If AJAX request, return JSON with updated subtotals
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
        if ($isAjax) {
            // Fetch updated cart item and totals
            $stmt = $pdo->prepare("SELECT c.cart_id, c.quantity, p.price FROM shopping_cart c JOIN product p ON c.product_id = p.product_id WHERE c.cart_id = ?");
            $stmt->execute([$cartId]);
            $updated = $stmt->fetch();
            $subtotal = 0;
            if ($updated) {
                $subtotal = $updated['quantity'] * $updated['price'];
            }
            $cartTotal = calculateCartTotal($pdo, $_SESSION['member_id']);
            echo json_encode([
                'success' => true,
                'subtotal_html' => 'Subtotal: ' . formatPrice($subtotal),
                'cart_total_html' => formatPrice($cartTotal)
            ]);
            exit;
        }
        
        $_SESSION['success'] = 'Cart updated successfully!';
        redirect('/cart/view.php');
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Failed to update cart.';
        redirect('/cart/view.php');
    }
} else {
    redirect('/cart/view.php');
}
?>

