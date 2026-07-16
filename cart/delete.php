<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cartId = isset($_POST['cart_id']) ? sanitize($_POST['cart_id']) : '';
    
    if (empty($cartId)) {
        $_SESSION['error'] = 'Invalid cart item.';
        redirect('/cart/view.php');
    }
    
    try {
        // Verify cart item belongs to member
        $stmt = $pdo->prepare("DELETE FROM shopping_cart WHERE cart_id = ? AND member_id = ?");
        $stmt->execute([$cartId, $_SESSION['member_id']]);
        
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

        if ($stmt->rowCount() > 0) {
            // compute updated cart count
            $stmt2 = $pdo->prepare("SELECT COUNT(*) as cnt FROM shopping_cart WHERE member_id = ?");
            $stmt2->execute([$_SESSION['member_id']]);
            $cartCount = (int)($stmt2->fetch()['cnt'] ?? 0);

            if ($isAjax) {
                echo json_encode(['success' => true, 'message' => 'Item removed from cart.', 'cart_count' => $cartCount]);
                exit;
            }

            $_SESSION['success'] = 'Item removed from cart.';
            unset($_SESSION['selected_cart_ids']);
        } else {
            if ($isAjax) {
                echo json_encode(['success' => false, 'error' => 'Cart item not found.']);
                exit;
            }
            $_SESSION['error'] = 'Cart item not found.';
        }
    } catch (PDOException $e) {
        if ($isAjax) {
            echo json_encode(['success' => false, 'error' => 'Failed to remove item from cart.']);
            exit;
        }
        $_SESSION['error'] = 'Failed to remove item from cart.';
    }
}

redirect('/cart/view.php');
?>

