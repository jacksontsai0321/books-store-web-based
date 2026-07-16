<?php
// Supports both POST (AJAX) and GET (PayPal redirect) cancellation.
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

// Determine order_id from POST JSON or GET param
$input = json_decode(file_get_contents('php://input'), true);
$orderId = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_array($input)) {
    $orderId = isset($input['order_id']) ? sanitize($input['order_id']) : '';
} else {
    $orderId = isset($_GET['order_id']) ? sanitize($_GET['order_id']) : '';
}

if (empty($orderId)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['error' => 'order_id is required']);
        exit;
    } else {
        // Redirect to cart when no order specified
        redirect('/cart/view.php');
    }
}

try {
    $order = getOrderDetails($pdo, $orderId);
    if (!$order || $order['member_id'] != $_SESSION['member_id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Order not found or access denied']);
        exit;
    }

    // Only cancel pending orders (do not remove completed payments)
    if (isset($order['status']) && $order['status'] !== 'Pending') {
        http_response_code(400);
        echo json_encode(['error' => 'Only pending orders can be cancelled']);
        exit;
    }

    try {
        $pdo->beginTransaction();
        // Delete order details
        $stmt = $pdo->prepare("DELETE FROM order_detail WHERE order_id = ?");
        $stmt->execute([$orderId]);
        // Delete any payment placeholders (if exists)
        $stmt = $pdo->prepare("DELETE FROM order_payment WHERE order_id = ?");
        $stmt->execute([$orderId]);
        // Delete order header
        $stmt = $pdo->prepare("DELETE FROM order_header WHERE order_id = ?");
        $stmt->execute([$orderId]);

        $pdo->commit();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Order cancelled and items preserved in cart']);
            exit;
        } else {
            // Redirect user back to cart view for GET cancellations (e.g., PayPal cancel_url)
            $_SESSION['success'] = 'Payment cancelled. Your items have been returned to the cart.';
            redirect('/cart/view.php');
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => 'Failed to cancel order: ' . $e->getMessage()]);
            exit;
        } else {
            $_SESSION['error'] = 'Failed to cancel order. Please contact support.';
            redirect('/cart/view.php');
        }
    }
} catch (Exception $e) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    } else {
        $_SESSION['error'] = 'An unexpected error occurred.';
        redirect('/cart/view.php');
    }
}
?>