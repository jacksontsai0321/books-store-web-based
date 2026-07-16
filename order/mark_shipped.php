<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/order/history.php');
}

$orderId = isset($_POST['order_id']) ? sanitize($_POST['order_id']) : '';
if (empty($orderId)) {
    $_SESSION['error'] = 'Order required.';
    redirect('/order/history.php');
}

try {
    $stmt = $pdo->prepare("SELECT * FROM order_header WHERE order_id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order || $order['member_id'] != $_SESSION['member_id']) {
        $_SESSION['error'] = 'Order not found.';
        redirect('/order/history.php');
    }

    if ($order['status'] === 'Completed') {
        $_SESSION['error'] = 'Completed orders cannot be updated.';
        redirect('/order/detail.php?id=' . urlencode($orderId));
    }

    if ($order['status'] === 'Shipped') {
        // Already marked
        $_SESSION['info'] = 'Order already marked as shipped.';
        redirect('/order/detail.php?id=' . urlencode($orderId));
    }

    // Prevent marking as received if a refund request is in progress
    if ($order['status'] === 'Refund Requested') {
        $_SESSION['error'] = 'Cannot mark as received while a refund request is in progress.';
        redirect('/order/detail.php?id=' . urlencode($orderId));
    }

    // Ensure shipped_at column exists
    try {
        $col = $pdo->query("SHOW COLUMNS FROM order_header LIKE 'shipped_at'")->fetch();
        if (!$col) {
            $pdo->exec("ALTER TABLE order_header ADD COLUMN shipped_at DATETIME DEFAULT NULL");
        }
    } catch (PDOException $e) {
        // non-fatal
    }

    // Update status to Shipped and set shipped_at
    $stmt = $pdo->prepare("UPDATE order_header SET status = 'Shipped', shipped_at = NOW() WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $_SESSION['success'] = 'Order marked as Shipped. You may request a refund within 7 days.';
    redirect('/order/detail.php?id=' . urlencode($orderId));
} catch (PDOException $e) {
    $_SESSION['error'] = 'Failed to update order status.';
    redirect('/order/detail.php?id=' . urlencode($orderId));
}
?>